<?php

/**
 * View: Recebe as varias em array, template (por arquivo ou string), e preenche 
 *       o conteudo com chaves pre-determinadas.
 *       Faz uso de algorítmo recursivo, caso tenha uma subview (view dentro de view), 
 *       trabalha esta como uma nova view, e a  inclue dentro da mesma view Pai.
 *       Trabalha com views em 2 modos:
 *          -Modo tradicional. Busca as chaves, e os substitue pelos valores em $array_vars;
 *          -Modo lista, Enumera chaves pai, cria array com a view de cada item da lista, junta os itens
 *           com a tag <!--glue algo-->(dentro da view), e retorna view.
 * 
 *       Pode-se Imprimir pdf, para tal, ao rodar $view->process_view($array_data),
 *          Adicione a chave [__DOMPDF] a $array_data com as seguintes subchaves:
 *          [paper_size] = letter(default), 4a0, a4, 8.5x11, outros
 *          [orientation] = portrait (default), landscape
 *          [relative_path] = substituira SITE_PATH por valor inserido aqui
 *          [stream] = caso queira baixar o pdf, define nome de arquivo a ser baixado.
 *                      caso fique em branco ou nao setado, imprime pdf no navegador
 * @version 2.15
 * -----------------------------------------------------------------------------
 * Changelog: 08.02.2013
 * -----------------------------------------------------------------------------
 *  Versao 2.15-2013.02.08- Adicionada variavel padrão RAND (um número aleatório entre 1000000 e 9999999.
 *  Versao 2.14-2013.30.01- Adicionado recurso autoDefaultVars
 *  Versao 2.13-2012.08.08- Adicionado BASE as variaveis padrão
 *  Versao 2.12-2012.04.19- Em process, se no encadeamento processado por process
 *              For definido __FILE em array_data, remote fulltemplate para o 
 *              arquivo de view explicitamente declarado ser processado.
 *  Versao 2.11-2012.04.18- adicionada variavel default {SELF_URL_PAGE} {SELF_URL_PAGE_ENCODED}
 *  Versao 2.10-2012.02.17- adicionada variável default {SELF_URL_ENCODED} (SELF_URL) com urlencode()
 *  Versao 2.9 - método "load_file" tornou-se público. process_view com desmembrado
 *               e organizado.
 *  Versao 2.8 - Atribue valores do array pai (quando houver ao subarray)
 *  Versao 2.7 - Converte HTML em PDF usando a ferramenta DOMPDF.
 *  Versao 2.6 - Correção de Bugs
 *  Versao 2.5 - Aplicado conceito de subtemplate dentro do mesmo arquivo view. 
 *               Não necessita mais criar varios arquivos pra uma view.
 *               O método anterior também é funcional.
 *  Versao 2.4 - Busca identificar sozinho se subview é lista e tenta identificar 
 *               automaticamente arquivo usado caso o mesmo não declarado.
 *  Versao 2.3 - *///Aceita views com vars de comentarios <!--*var*-->, /*var*/
/*  Versao 2.2 - Aceita views com vars: -!var!-, <?=$var?> e {var}
 *  Versao 2.1 - Correção de erros e bugs
 *  Versao 2.0 - Implementação para Framework
 *
 * @author Carlos Junior
 */
class view {
    
    const version = 2.15;

    private $_file_template;
    private $_DOMPdfCommands;
    
    /**
     * Quando True, busca automaticamente as variaveis padrão no $GLOBAL e as aplica
     * ao documento.
     */
    public $autoDefaultVars = true;
    
    /**
     * Define o nome da variável global aonde estarão as chaves padrão a serem aplicadas
     */
    public $keyDefaultVars = 'view_vars';
    
    /**
     * Este só pode receber array.
     * Recebe as chaves padrão aplicadas a view. OBS: Quando $autoDefaultVars é true, esta
     * é automaticamente alimentada na construção do objeto.
     */
    public $view_vars = array();
    
    
    public function __construct() {
        if($this->autoDefaultVars && isset($GLOBALS[$this->keyDefaultVars]))
            $this->view_vars = $GLOBALS[$this->keyDefaultVars];
    }

    /**
     * Principal função do objeto:
     * Recebe array com variaveis e arquivo de template ou string contendo o template completo.
     * 
     * @param Array() $array_vars : Coleção com chaves e valores a serem processados pela view.
     * @param string $file : Se aplicado, endereço do arquivo a ser processado.
     * @param string $template : Se aplicado, conteudo do template em si a ser processado.
     */
    public function process_view($array_vars = array(), $file = '', $template = '') {
        return $this->_process($array_vars, $file, $template);
    }

    /**
     * Função privada acima da publica de propósito. Process é a antiga process_view com todas as variaveis necessárias.
     */
    private function _process($array_data, $view_file = null, $fullTemplate = null, $prev_key = null, $parent_array = array()) {
        $fileName = $this->_define_fileName($array_data, $prev_key, $view_file);
        /*Se neste processo, arquivo foi definido manualmente, remove fulltemplate para carregar template do arquivo*/
        if(!isset($array_data['__FILE']))
            $array_data['__FILE'] = '';
        if($array_data['__FILE']){
            $fullTemplate = null;
        }
        if (isset($fileName)) {
            $view_file = $fileName;
        }
        $DOMPdfCommands = $this->_defineDOMPdfCommands($array_data);

        unset($array_data['__DOMPDF']);

        $command = $this->_define_command($array_data);
        $arrTempl = $this->_define_template($view_file, $fullTemplate);
        $template = $arrTempl['template'];
        $fullTemplate = $arrTempl['fullTemplate'];

        //Caso exista o __COMMAND de lista, busca pela string "glue" e retorna a funcao process_list_results
        if ($command == "list") {
            return $this->_process_list_results($array_data, $view_file, $fullTemplate, $this->_get_glue_string($template), $parent_array);
        }

        $data = $this->_process_array_data($array_data, $view_file, $fullTemplate, $template, $parent_array);

        /* Caso __DOMPDF esteja configurado, retorna metodo _generateDomPdf. Vide metodo para mais detalhes */
        if (isset($DOMPdfCommands)) {

            $data = $this->_generateDOMPdf($data);
        }
        return $data;
    }

    public function replace_vars($array_data, $template, $parent_array = array()) {
        /* Aplica o array_data as variaveis (keys) sem subkeys */
        $template = $this->_replace($array_data, $template);
        /* Aplica agora o array anterior as (keys) com a subkey PARENT {PARENT::key} */
        $template = $this->_replace($parent_array, $template, 'PARENT');
        return $template;
    }

    /**
     *  Carrega arquivo e retorna o conteudo do mesmo
     * 
     * @return string : Conteúdo do arquivo carregado
     * @param string $view_file : Endereço do arquivo a ser carregado no servidor
     */
    public function load_file($view_file) {
        if (!is_file($view_file)) {

            die("<b>Erro:</b> Em <i><b>function</b> load_file</i>, o arquivo <b>$view_file</b> n&atilde;o foi encontrado<br />");
        }
        $fileData = file_get_contents($view_file);
        return $fileData;
    }

    private function _replace($array_data, $template, $subkey = null) {
        /* Conectada a replace_vars */
        if ($subkey) {
            $subkey = $subkey . "::";
        }
        if ($array_data) {
            foreach (array_keys($array_data) as $value) {
                if (!is_array($array_data[$value])) {
                    $template = str_replace("<!--*$subkey$value*-->", $array_data[$value], $template);
                    $template = str_replace("/*$subkey$value*/", $array_data[$value], $template);
                    $template = str_replace("-!$subkey$value!-", $array_data[$value], $template);
                    $template = str_replace("<?=\$$subkey$value?>", $array_data[$value], $template);
                    $template = str_replace("{" . $subkey . $value . "}", $array_data[$value], $template);
                }
            }
        }
        return $template;
    }

    private function _define_fileName($array_data, $prev_key, $view_file) {
        /* Caso este seja processo filho, $array_data["__FILE"] esteja em branco, define automaticamente nome de arquivo baseado na chave recebida */
        $fileName = null;
        if (isset($array_data['__FILE'])) {
            $fileName = $array_data['__FILE'];
        }
        if ($prev_key && !$fileName && $view_file) {
            $fileName = $this->_get_subprocess_filename($view_file, $prev_key);
        }
        return $fileName;
    }

    private function _defineDOMPdfCommands($array_data) {
        /* Verifica se a saida sera convertida a PDF ou nao */
        if (isset($array_data['__DOMPDF'])) {
            if (!$array_data['__DOMPDF']) {
                $array_data['__DOMPDF'] = array('enable' => true);
            }
            return $this->_DOMPdfCommands = $array_data["__DOMPDF"];
        }
    }

    private function _define_command($array_data) {
        if (!isset($array_data["__COMMAND"])) {
            $command = '';
        } else {
            $command = $array_data["__COMMAND"];
        }
        /* Se nenhum comando definido, tenta descobrir que tipo de dados, é $array_data (Se é lista ou dados diretos) */
        if (!$command) {
            $command = $this->_if_is_list($array_data);
        }
        return $command;
    }

    private function _define_template($view_file, $fullTemplate) {
        $arrTempl = array();
        /* Caso não tenha recebido conteudo template, e arquivo seja válido, carrega template */
        if (!$fullTemplate && $view_file) {
            $fullTemplate = $this->load_file($view_file);
        }
        /* Testa conteudo da view sobre erros de implementação */
        $error_template_structure = $this->_test_subtemplate_scucture($fullTemplate);
        if ($error_template_structure) {
            die($this->_print_subtemplate_error($view_file, $error_template_structure));
        }
        $arrTempl['template'] = $this->_subtemplate_to_key($fullTemplate);
        $arrTempl['fullTemplate'] = $fullTemplate;
        return $arrTempl;
    }

    private function _process_array_data($array_data, $view_file, $fullTemplate, $template, $parent_array) {
        foreach (array_keys($array_data) as $key) {
            if (is_array($array_data[$key])) {
                /* Algoritmo recursivo, se chave for um subarray */
                $array_data[$key] = $this->_process($array_data[$key], $view_file, $this->_get_subtemplate($fullTemplate, $key), $key, $array_data);
            }
        }
        return $this->change_view_vars($array_data, $template, $parent_array);
    }
    
    private function _generateDOMPdf($html = "") {
        $view_dir = str_replace(DIRECTORY_SEPARATOR, '/', realpath(dirname(__FILE__)));
        require_once $view_dir . '/view/dompdf/dompdf_config.inc.php';
        
        if ($this->_DOMPdfCommands) {

            /* recebe comandos do DOMPdf e processa conforme desejado seguindo comandos passados por $DOMPdfCommands */
            if (!@$this->_DOMPdfCommands['paper_size']) {
                $this->_DOMPdfCommands['paper_size'] = 'letter';
            }
            if (!@$this->_DOMPdfCommands['orientation']) {
                $this->_DOMPdfCommands['orientation'] = 'portrait';
            }
            $dompdf = new DOMPDF();
            $dompdf->load_html($html);
            $dompdf->set_paper($this->_DOMPdfCommands['paper_size'], $this->_DOMPdfCommands['orientation']);
            $dompdf->render();
            if (@$this->_DOMPdfCommands['stream']) {
                $dompdf->stream($this->_DOMPdfCommands['stream']);
            } else {
                return $dompdf->output();
            }
        }
        return $html;
    }

    private function _get_subprocess_filename($view_file, $prev_key) {
        $ext = pathinfo($view_file, PATHINFO_EXTENSION);
        $newFileName = str_replace($ext, "$prev_key.$ext", $view_file);
        if (file_exists($newFileName)) {
            return $newFileName;
        } else {
            return $view_file;
        }
    }

    /* Tenta descobrir se array se trata de lista ou variaveis de impressao */

    private function _if_is_list($array_data) {
        $command = '';
        /* Remove chaves de comando, iniciadas por 2 underlines (__) */
        foreach (array_keys($array_data) as $key) {

            if (strpos($key, "__") !== false) {

                unset($array_data[$key]);
            }
        }
        $plk = count($array_data) - 1; /* Possible Last Key */
        if (isset($array_data[$plk])) {
            if (is_array($array_data[$plk])) {
                $command = 'list';
                if ($plk > 0 && !is_array($array_data[$plk - 1])) {
                    $command = '';
                }
            }
        }
        return $command;
    }

    private function change_view_vars($array_data = array(), $template = false, $parent_array = array()) {
        $arrVars = array();
        $arrVars = $this->_add_default_vars($array_data);
        $arrVars = $arrVars + $array_data;
        $template = $this->replace_vars($arrVars, $template, $parent_array);
        return $this->replace_vars($arrVars, $template, $parent_array); /* Roda novamente */
    }

    /**
     * Procura por <!--glue -->, e busca o glue de dentro do comentario, e retorna o valor...
     */
    private function _get_glue_string($template) {
        /* procura pelo glue, e extrai a string */
        $temp = explode('<!--glue', $template);
        if (count($temp) > 1) {
            $temp = explode("-->", $temp['1']);
            return $temp['0'];
        } else {
            return '';
        }
    }

    /* Funcao para formatar dados provenientes de uma lista de dados. As lista de foma ordenara,
     * e retorna uma string formatada com dados dentro...
     */

    private function _process_list_results($array_data, $view_file, $fullTemplate, $glue_string, $parent_array) {
        $array = array();
        $string = '';
        unset($array_data["__FILE"]);
        unset($array_data["__COMMAND"]);
        foreach ($array_data as $line_data) {
            $array[] = $this->_process($line_data, $view_file, $fullTemplate, null, $parent_array);
        }
        if ($array) {
            $string = implode($glue_string, $array);
        }
        return str_replace("<!--glue$glue_string-->", '', $string); /* Remove a instrucao de Glue, do arquivo de template e retorna tudo */
    }

    /**
     * Aqui vão as variaveis padrão a serem aplicadas na view
     * isso significa que não precisa setar tais variaveis na view, pois elas
     * são sempre aplciadas e em todos os niveis do array.
     */
    private function _add_default_vars() {
        $default_vars = array();
        //Tenta aplicar as views padrão passadas pelo view_vars
        if($this->view_vars && is_array($this->view_vars))
            $default_vars = $this->view_vars;
        $default_vars['PHP_SELF'] = $_SERVER['PHP_SELF'];
        //Se tiver dompdf, aplica as vars public
        if (isset($this->_DOMPdfCommands)) {
            $default_vars['PUBLIC'] = "";
        } else {
            $default_vars['PUBLIC'] = ambient_vars::public_path();
        }
        //Aplica demais variaveis padrão
        $ambient_vars = array(
            'HTTP_GET_QUERY'        => http_build_query($_GET),
            'BASE'                  => ambient_vars::base_path(),
            'PROTOCOL'              => ambient_vars::protocol(),
            'SITE_PATH'             => ambient_vars::website_path(),
            'NOPROT_SITE_PATH'      => ambient_vars::website_path(TRUE),
            'SITE_PUBLIC_PATH'      => ambient_vars::website_public_path(),
            'SELF_URL'              => ambient_vars::self_url(),
            'SELF_URL_PAGE'         => ambient_vars::self_url_page(),
            'SELF_URL_ENCODED'      => urlencode(ambient_vars::self_url()),
            'SELF_URL_PAGE_ENCODED' => urlencode(ambient_vars::self_url_page()),
            'YEAR'                  => date('Y'),
            'MONTH'                 => date('m'),
            'DAY'                   => date('d'),
            'TIME'                  => date('H:i:s'),
            'RAND'                  => rand(1000000,9999999)
        );
        
        return array_merge($ambient_vars,$default_vars);
    }

    public function set_template($view_file) {
        $this->_file_template = $view_file;
    }

    /* Abaixo funções para cuidar de subtemplates dentro do template
     * exemplo de subtemplate:
     * <!--begin chave-->Conteudo<!--end-->
     * Graças ao algorítmo recursivo Voce pode ter n subtemplates.
     */

    private function _get_subtemplate($template, $key) {
        $pre = null;
        $explodeStr = "<!--begin ";
        $end = "<!--end-->";
        $nivel = 0;
        $arrTmp = explode("<!--begin $key-->", $template);
        if (isset($arrTmp[1])) {
            $arrTmp = explode($explodeStr, $arrTmp[1]);
        } else {
            $arrTmp = null;
        }
        $i = 0;
        while ($i < count($arrTmp)) {
            $subtraendo = 0;
            $nivel++;
            $data[] = $arrTmp[$i];
            if (strpos($arrTmp[$i], $end)) {
                $subtraendo = (count(explode($end, $arrTmp[$i])) - 1);
                $nivel = $nivel - $subtraendo;
            }
            if (!$nivel) {
                $tmpData = $pre . implode($explodeStr, $data);
                $arrTmp = explode($end, $tmpData);
                unset($arrTmp[count($arrTmp) - 1]);
                return implode($end, $arrTmp);
            }
            $i++;
        }
    }

    private function _subtemplate_to_key($template) {
        $key = $this->_get_subtemplate_first_key($template);
        $template = str_replace(
                "<!--begin $key-->" . $this->_get_subtemplate($template, $key) . "<!--end-->", "{" . $key . "}", $template
        );
        if ($this->_get_subtemplate_first_key($template)) {
            return $this->_subtemplate_to_key($template);
        }
        return $template;
    }

    private function _get_subtemplate_first_key($template) {
        $arrTmp = explode("<!--begin ", $template);
        if (isset($arrTmp[1])) {
            $arrTmp = explode("-->", $arrTmp[1]);
            return $arrTmp[0];
        }
    }

    /* Testa se os subtemplates foram inicializados corretamente.
     * Se tudo estiver correto, retorna 0, senão, retorna nivel de subtemplate do erro
     */

    private function _test_subtemplate_scucture($template) {
        $nivel = 0;
        $arrTmp = explode("<!--begin ", $template);
        $i = 1;
        while ($i < count($arrTmp)) {
            $nivel++;
            if (strpos($arrTmp[$i], "<!--end-->")) {
                $nivel = $nivel - (count(explode("<!--end-->", $arrTmp[$i])) - 1);
            }
            $i++;
        }
        return $nivel;
    }

    private function _print_subtemplate_error($view_file, $errorlevel) {
        die("<b>Erro:</b> Em <i>View <b>function</b> __process</i>,
                a estrutura de subtemplate foi implementada errada.<br />
                    Arquivo:<b>\"$view_file\"</b><br />
                    Verifique se estrutura de subtemplates está de acordo com exemplo abaixo:
                <pre><b>
                    &lt;!--nome_da_chave1 --&gt;
                        **Conteudo**
                        &lt;!--nome_da_subchave --&gt;
                            **Conteudo**
                        &lt;!--end--&gt;
                    &lt;!--end--&gt;
                </b></pre>              
                Nivel de erro:<b> $errorlevel </b><br />");
    }

}

?>