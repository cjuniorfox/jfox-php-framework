<?php

/**
 * @version 1.1
 * 
 * Este serve como controlador abstrato para controladores que trabalham com 
 * formularios de cadastro padrão. Quando for criar um controller para interagir
 * com cadastros, extenda seu controlador a cadastrosAbstractController ao invés
 * de controller
 *
 * -----------------------------------------------------------------------------
 * Changelog: 18.04.2012
 * -----------------------------------------------------------------------------
 * 1.1-2012.09.17-Adicionadas variáveis de POST.
 * 1.1-2012.08.03-Adicionado recurso para, em conjunto com Objeto Cadsatros, 
 *                  Salvar o primary key em $_SESSION.
 * 1.0-2012.04.18-Criado objeto baseado no controlador.
 * 
 * @author juniorfox
 */
abstract class AbsC_cadastros extends controller {
    /* Variaveis de segurança */

    public $version = 2.0;
    protected $template = 'appTema';
    protected $session_prefixo = "CADASTROS::";
    
    const documento_sem_dados = "<blockquote>N&atilde;o h&aacute; dados neste documento para serem impressos.</bloquote>";
    

    /**
     * Usado por resultados_busca_avancada.
     * É usado para preencher o arrCommands da busca_avancada
     */
    protected $busca_avancada_comandos = Array();

    /**
     * @param array - Quando definido, as chaves inseridas neste array sobrepoem
     * valores enviados via $_POST em cadastroAction() e updateAction().
     */
    protected $POST = array();
    protected $GET = array();
    protected $restrict_execution = true;
    protected $restrict_execution_rule = array('rule' => '', 'value' => '');

    /**
     * Nome do formulário a ser usado
     */
    protected $form_name = '';

    /**
     * Nome do formulário que lista os campos para busca avancada
     */
    protected $formBuscaAvancada = "";

    /**
     * Arquivo XML
     */
    protected $xmlFileName = 'index.xml';

    /* Variaveis usadas na action index */
    protected $URL_LEFT = '{SITE_PATH}{controller_name}/listar'; /* url a ser carregada a esquerda quando corpo do index é carregado */
    protected $URL_BODY = '';

    /* Variaveis usadas na action listar, campos que serão usados na busca simples */
    protected $pesq_campos = array();

    /* Variaveis usadas na action listar, campos que serão impressos. O primeiro do array irá para o título. */
    protected $lista_campos = array();
    
    /**
     * array Commands usado em listar
     */
    protected $lista_arrCommands = array();
    
    /**
     * Nome alternativo de view para documento pdf, usado em listar, documento normalmente é contrato, OS, algo do tipo.
     */
    protected $view_documento = 'documento';
    

    /* Variaveis usadas na action imprimir_registro */
    protected $irHeader1 = '';
    protected $irHeader2 = '';

    /**
     * Usado em Cadastro e update.
     * Quando setado como True e ao cadastro ou update ser postado, registra
     * primary_key do registro salvo/atualizado na session: $_SESSION['%%cadastros'][$form_name]
     */
    protected $pk_on_session = false;

    public function indexAction() {
        $this->_restrict_execution();
        //Se existirem os $_GET['l'] ou $_GET['b'], estes sobrescrevem URL_LEFT e URL_BODY respectivamente.
        if(isset($_GET['l']))
            $this->URL_LEFT = urldecode($_GET['l']);
        if(isset($_GET['b']))
            $this->URL_BODY = urldecode($_GET['b']);
        $this->array_data['URL_LEFT'] = $this->URL_LEFT;
        $this->array_data['URL_BODY'] = $this->URL_BODY;
        $this->process_renderization(array('appTema', 'listagem'));
    }

    /**
     * Lista e busca concatenada. Exibe campo de busca simples (apenas um campo de busca geral),
     * e logo abaixo a listagem de resultados
     */
    public function listarAction() {
        $this->_restrict_execution();
        if (!isset($this->array_data))
            $this->array_data = array();
        $objCadastro = $this->_load_cadastro();
        $term = '';
        $focus_item = '';
        if (isset($_GET['term']))
            $term = $_GET['term'];
        if(isset($_GET['item']))
            $focus_item = $_GET['item']; //Id do item a ser automaticamente expandido ao carregar.
        $view_documento_existe = $this->view_exists($this->view_documento); //Se este for true, envia ao metodo lista comando para mostrar links de documentos pdf.
        $cdata = $objCadastro->listar($this->form_name, $term, $this->pesq_campos, $this->lista_campos, $this->lista_arrCommands, $view_documento_existe);
        //Adiciona a tag do item a ser focado
        $this->array_data['focus_item'] = $focus_item;
        
        $this->array_data = array_merge($this->array_data, $cdata);

        $this->process_renderization($this->array_data['template'], $cdata);
    }

    /**
     * Busca avançada. A esquerda exibe os filtros de busca retornando resultados a direita.
     */
    public function busca_avancadaAction() {
        $Cadastro = $this->_load_cadastro();
        $cdata = $Cadastro->formulario_de_cadastro($this->formBuscaAvancada);
        $this->array_data['form']['div_id'] = $cdata['formName'] = $this->formBuscaAvancada;
        $this->array_data = array_merge($this->array_data, $cdata);
        $this->process_renderization(array($this->template, 'form_busca_avancada'), $cdata);
    }

    /**
     * Imprime resultados pertinentes a busca avançada
     */
    public function resultados_busca_avancadaAction() {
        $campo_ordenar = null;
        $busca_avancada_comandos = Array();
        if ($this->busca_avancada_comandos && is_array($this->busca_avancada_comandos))
            $busca_avancada_comandos = $this->busca_avancada_comandos;
        $Cadastro = $this->_load_cadastro();
        $GET = array_merge($_GET, $this->GET);
        //Se foi setado a $GET['__col'], adiciona a mesma ao busca_avancada_comandos e remove-a do get.
        if(isset($GET['__col'])){
           $campo_ordenar = $GET['__col'];
           unset($GET['__col']);
        }
        unset($GET['__col']);
        $cdata = $Cadastro->listar_busca_avancada($this->form_name, $GET, $this->lista_campos, $busca_avancada_comandos,$campo_ordenar);
        $this->array_data = array_merge($this->array_data, $cdata);
        $this->process_renderization(array($this->template, 'resultados_busca_avancada'), $cdata);
    }

    public function cadastroAction() {
        $this->_restrict_execution();
        $Cadastro = $this->_load_cadastro();
        //Primeiro verifica se algo foi postado. Caso tenha sido, envia postagem e retorna o JSON.
        $POST = array_merge($_POST, $this->POST);
        $GET = array_merge($_GET, $this->GET);
        if ($POST) {
            $data = $Cadastro->inserir_novo_cadastro($this->form_name, $POST);
            $this->process_renderization(array('blank', 'simple'), json_encode($data));
        }
        //Nada foi postado? então gera e imprime o formulário de cadastro.
        /*Se recebido ID, abre um formulário de cadastro novo pre-preenchendo os dados de um
         * Cadastro pré-existente. Se não for recebido ID, carrega cadastro em branco normalmente.
         */
        if(@$GET['id'])
            $array_data = $Cadastro->formulario_de_edicao ($this->form_name, $GET['id']);
        else
            $array_data = $Cadastro->formulario_de_cadastro($this->form_name);
        $array_data['target_url'] = $Cadastro->target_url($this->form_name, 'insert');
        $this->array_data = array_merge($this->array_data, $array_data);
        $this->process_renderization(array($this->template, 'form_cadastro'));
    }

    public function updateAction() {
        $POST = array_merge($_POST, $this->POST);
        $GET = array_merge($_GET, $this->GET);
        $this->_restrict_execution();
        $Cadastro = $this->_load_cadastro();
        //Verifica se id_reg foi passado.
        if (!array_key_exists('id', $GET))
            $this->process_renderization(array('blank', 'simple'), 'Sem id do registro...');
        else {
            if ($POST) {
                $data = $Cadastro->atualizar_um_cadastro($this->form_name, $POST, $GET['id']);
                $this->process_json($data);
            }
            //Não foi postado? então carrega dados e imprime o formulário de edição. 
            $array_data = $Cadastro->formulario_de_edicao($this->form_name, $GET['id'], $POST);
            $array_data['target_url'] = $Cadastro->target_url($this->form_name, 'update');
            $this->array_data = array_merge($this->array_data, $array_data);
            $this->process_renderization(array($this->template, 'form_update'));
        }
    }

    /**
     * Quando documento existe, imprime o mesmo.
     * Depende do nome do arquivo de template.
     * se receber .pdf no final do $this->_urlvar_array, imprime a saida como PDF.
     */
    public function documentoAction() {
        $this->_restrict_execution();
        //Se a extensão passada na URL for .pdf, retorna pdf e não html
        @$last_array = $this->urlvar_array[count($this->urlvar_array) - 1];
        if ($last_array == 'pdf') {
            $this->array_data['__DOMPDF'] = array();
        }
        $objCadastro = $this->_load_cadastro();
        $cdata = $objCadastro->ler($this->form_name, $this->urlvar_array[0]);
        if(!$cdata){
            $this->process_renderization(array('blank', 'simple'), self::documento_sem_dados, $this->view_documento);
        }
        //Adiciona a data por extenso ao documento
        $Local_formats = new local_formats();
        $cdata['DATA_HOJE_EXTENSO'] = $Local_formats->date_to_local_str(date('Y-m-d'), 'extense');
        $this->process_renderization(array('blank', 'simple'), $cdata,$this->view_documento);
    }

    public function imprimir_registroAction() {
        $this->_restrict_execution();
        if (!isset($this->array_data))
            $this->array_data = array();
        $objCadastro = $this->_load_cadastro();
        $this->array_data = array_merge($objCadastro->registro($this->form_name, $_GET['id']));
        $this->array_data['header1'] = $this->irHeader1;
        $this->array_data['header2'] = $this->irHeader2;
        $this->process_renderization(array('appTema', 'form_registro_impressao'));
    }

    /**
     * Action necessária para retornar dados para input_autoComplete
     */
    public function input_autoCompleteAction() {
        if (!isset($_GET['form']) || !isset($_GET['field']))
            die(); /* Parametros necessarios */
        XMLForms::include_lib();
        $xmlfilepath = $this->controller_vars['xml_path'] . $this->xmlFileName;
        $data = input_autoCompleteXMLForm::autocomplete_list($xmlfilepath, $_GET['form'], $_GET['term']);
        $this->array_data['body'] = json_encode($data);
        $this->process_renderization(array('blank', 'simple_noview'));
    }

    public function autofill_related_fieldsAction() {
        if (!isset($_GET['form']) || !isset($_GET['field']) || !isset($_GET['term']))
            die(); /* Parametros necessarios */
        XMLForms::include_lib();
        $xmlfilepath = $this->controller_vars['xml_path'] . $this->xmlFileName;
        $data = Fields::autofill_related_fields($xmlfilepath, $_GET['form'], $_GET['field'], $_GET['term']);
        $this->array_data['body'] = json_encode($data);
        $this->process_renderization(array('blank', 'simple_noview'));
    }

    public function uploadifyAction() {
        if (!isset($_POST))
            die();
        XMLForms::include_lib();
        $xmlfilepath = $this->controller_vars['xml_path'] . $this->xmlFileName;
        jquery_fileuploadXMLForm::upload_file($xmlfilepath, $this->form_name);
    }

    /**
     * Método que salva automaticamente o conteúdo do form.
     * Se imprimir um JSON com valor ok, salvou com sucesso.
     * Se não retornar nada, ocorreu algum erro.
     */
    public function logTextarea_autosaveAction() {
        if (!isset($_POST['form']) || !isset($_POST['field']) || !isset($_POST['id']) || !isset($_POST['data'])) {
            print_r($_GET);
            print_r($_POST);
            $this->process_json(NULL);
            die(); /* Parametros necessarios */
        }

        XMLForms::include_lib();
        $xmlfilepath = $this->controller_vars['xml_path'] . $this->xmlFileName;
        $data = logTextareaXMLForm::autosave($xmlfilepath, $_POST['form'], $_POST['field'], $_POST['id'], $_POST['data']);
        $this->process_json($data);
    }

    /**
     * Para aumentar a segurança, o deletar é feito em duas etapas. A primeira
     * é executar este para registrar em SESSION a chave que será deletada.
     * Este também imprime a caixa com mensagem de deleção.
     */
    public function delete_boxAction() {
        if (!array_key_exists('id', $_GET))
            die(); /* Parametros necessarios */
        $_SESSION[$this->session_prefixo . "delete"] = $_GET['id'];
        if ($_SESSION[$this->session_prefixo . "delete"] == $_GET['id']) {
            $this->array_data['delete_id'] = $_GET['id'];
            $this->process_renderization(array($this->template, 'delete_box'));
        }

        else
            $this->process_json(array('return' => 'fail'));
    }

    /**
     * Esta é a segunda etapa, caso a chave bata com o dado registrado na SESSION,
     * o dado é deletado.
     */
    public function deletarAction() {
        if (!array_key_exists('id', $_GET) || !array_key_exists($this->session_prefixo . "delete", $_SESSION))
            die(); /* Parametros necessarios */
        if ($_SESSION[$this->session_prefixo . "delete"] != $_GET['id'])
            $this->process_json(array('message' => 'Session_differ'));
        else {
            $id = $_GET['id'];
            $Cadastros = $this->_load_cadastro();
            $data = $Cadastros->remover_cadastro($this->form_name, $id);
            $this->process_json($data);
        }
    }

    public function testeAction() {

        echo $_SERVER['HTTP_USER_AGENT'] . "<hr />\n";
        $browser = get_browser();
        foreach ($browser as $name => $value) {
            echo "<b>$name</b> $value <br />\n";
        }
    }

    /**
     * Restringe execução
     * @param string $rule - Se aplicado, restringe o acesso a uma regra específica
     * @param string $rule_value - Só funciona se $rule foi específicado, o valor da regra desejada.
     */
    protected function _restrict_execution() {
        if($this->restrict_execution)
            $this->_restrict_execution_recursive($this->restrict_execution_rule);
    }

    /**
     * Este é chamado por _restrict_execution()
     * Varre o array e executa de forma recursiva o array para adicionar uma ou
     * mais regras de execução.
     * @param array $arrRest_exec - Array com a regra ou a lista de regras a ser seguidas.
     */
    protected function _restrict_execution_recursive($arrRest_exec) {
        if (@is_array($arrRest_exec[count($arrRest_exec) - 1])) { //Se este array for uma lista de arrays, executa recursivamente a lista
            foreach ($arrRest_exec as $rest_exec_item) {
                $this->_restrict_execution_recursive($rest_exec_item);
            }
        } else {//Abaixo é executado o passo em si e não a lista de passos.
            if ($arrRest_exec) {
                $login_man = new login_man();
                $login_man->restrict_execution($this->global_vars['view_path'] . "login/logout.html");
                if ($arrRest_exec['rule'] && $arrRest_exec['value']) {
                    $login_man->restrict_rule($arrRest_exec['rule'], $arrRest_exec['value']);
                }
            }
        }
    }

    /**
     * Carrega o cadastro de CONTRATOS
     */
    protected function _load_cadastro() {
        $objCadastro = new cadastros($this->controller_vars, $this->controller_vars['xml_path'] . $this->xmlFileName);
        $objCadastro->definir_template($this->template);
        /* Envia a objCadastro variavel aplicada em $this->pk_on_session */
        $objCadastro->pk_on_session = $this->pk_on_session;
        return $objCadastro;
    }

}

?>
