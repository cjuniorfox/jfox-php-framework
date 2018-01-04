<?php

/**
 * Auxilia o controlador relacionado ao controle financeiro a operar.
 * 
 * Neste objeto temos:
 * 
 * Processos relativos a impressão de extrato
 *
 * @author juniorfox
 */
class controle_financeiro {

    public $version = 1.3;

    /**
     * Este recebe as chaves que sobrescreverão as keyfields passadas pelo XML
     */
    public $keyfields = array();
    /**
     * Mensagem a ser exibida quando extrato não for encontrado
     */
    public $msg_sem_extrato = "Não há lançamentos dentro do período desejado.";
    public $msg_sem_saldo = "Sem saldo";
    public $controller_vars;
    private $_lib;
    private $_view_path;
    private $_View; /* Objeto View usado */
    private $_Local_formats; /* Objeto Local_formats usado */
    private $_dateVars; /* Vars padrão de busca de data */
    private $_default_filters; /* Filtros padrão usados */

    public function __construct($controller_vars, $xmlFileName, $dateVars, $default_filters) {
        $this->controller_vars = $controller_vars;
        $this->_dateVars = $dateVars;
        $this->_default_filters = $default_filters;
        $this->_lib = str_replace(DIRECTORY_SEPARATOR, '/', realpath(dirname(__FILE__))) . "/" . get_class($this) . "/";
        $this->_view_path = $this->_lib . "view/";
        $this->_xmlFileName = $xmlFileName;
        $this->_View = new view();
        $this->_Local_formats = new local_formats();
    }

    /**
     * Recebe as variaveis e retorna as mesmas para imprimir o Extrato já no formado do controller_data.
     * @param string $formName - Nome do form que contém os dados sobre o extrato
     * @param array $getVars - Comandos passados por $_GET
     * @param string $lblPrevBalance - String que é exibida no campo de Balanço anterior
     */
    public function extrato($formName, $getVars, $lblPrevBalance = "Balanço anterior", $calc_prev_bal = true) {
        $cdata = array();
        /* Se setado prev_period, define periodo conforme tal var. Caso contrario, define pre_period como 15 dias */
        $prev_period = 15;
        if (isset($getVars['prev_period'])) {
            $prev_period = intval($getVars['prev_period']);
        }
        $data_inicio = null;
        /* Se data inicial foi passada, processa a mesma */
        if (isset($getVars[$this->_dateVars['dateStart']]))
            $data_inicio = $getVars[$this->_dateVars['dateStart']];
        /* Passa a variavel de data_inicio como data_final. Está certo */
        $date = $this->_processar_datas($getVars[$this->_dateVars['date']], $data_inicio, $prev_period);
        /* Processa valores de datas para impressão */
        $data_inicio = $this->_Local_formats->date_to_local_str($date['dateStart']);
        $data_fim = $this->_Local_formats->date_to_local_str($date['date']);
        $cdata['data_inicio'] = $data_inicio;
        $cdata['data_fim'] = $data_fim;
        /*Busca pelo extrato*/
        $cdata['extrato'] = $this->_buscar_extrato($formName, $getVars, $date['dateStart'], $date['date'], $lblPrevBalance, $calc_prev_bal);
        if ($cdata['extrato']) { /*Tem extrato, adiciona então as variaveis de data ao mesmo*/
            $cdata['extrato']['data_inicio'] = $data_inicio;
            $cdata['extrato']['data_fim'] = $data_fim;
            /**
             * Repete as vars de saldo das vars de extrato
             */
            $cdata['saldo'] = array(
                'debits' => $cdata['extrato']['debits'],
                'credits' => $cdata['extrato']['credits'],
                'balance' => $cdata['extrato']['balance']
            );
        } else {
            /* Se nada foi carregado, adiciona ao extrato e saldo a mensagem de erro */
            $cdata['extrato'] = $this->msg_sem_extrato;
            $cdata['saldo'] = $this->msg_sem_saldo;
        }
        return $cdata;
    }

    /**
     * Responsável por gerar estornos financeiros.
     * Este faz:
     * 1-Cria um novo formulário, para inserir um novo registro baseado em um registro anterior
     * com uma operação inversa.
     * 
     * 2-Registra na nova operação que esta é um estorno de outra operação
     * 
     * 3-Edita registro original informando que foi gerado um estorno da mesma.
     * O método é responsavel tanto por gerar o formulário no formato Ajax quanto postar os dados.
     * 
     * Quando dados não são postados, é retornado o formulário com as inforamções
     * Quando dados são postados, é retornado um JSON da operação informando se os dados foram postados (novo ID), 
     * ou mensagem de erro (erro).
     * 
     * @param string $formName Nome do form usado.
     * @return array - Formulário de postagem ou retorno de postagem efetuada
     */
    public function formulario_estorno($formName) {
        $array_data = array(
            'body' => '',
            'template' => array('blank', 'simple_noview')
        );
        if (!$_GET['id'])
        /* Se não foi definido id, interrompe e retorna JSON com mensagem de erro */
            return array_merge($array_data, array(
                        'body' => 'ID de Registro nao foi definido'
                    ));
        $objCadastro = $this->_load_cadastro();

        /* Modifica os valores do XML com valores relativos ao estorno */
        $ObjFinancial = $this->_load_financial($formName);
        $campo_estorno = $ObjFinancial->field('return'); /* Nome do campo estorno na tabela */
        $formData = $objCadastro->ler($formName, $_GET['id']);
        /* Verifica o campo de relacionamento de estorno. Se existir valores, interrompe e retorna JSON com mensagem de erro */
        if ($formData[$campo_estorno])
            return array_merge($array_data, array(
                        'body' => $this->_msgErro_estornoExistente($formData[$campo_estorno])
                    ));
        $formData = $ObjFinancial->create_reverse_operation($_GET['id'], $formData);
        $objCadastro->xmlForms->merge_postData($formData);
        $array_data = array_merge($array_data, $objCadastro->formulario_de_cadastro($formName, $_POST));
        /* Se foi enviada uma postagem, será gerado um JSON com ID da nova postagem. Caso este seja gerado,
         * Decodifica o JSON e adiona novo id ao registro original, conectando o mesmo ao registro estornado.
         */
        if ($array_data['JSON'])
            $this->_relacionar_estorno_a_registro($_GET['id'], $formName, $array_data['JSON'], $campo_estorno);
        $array_data['div_id'] = 'estorno'; /* Identifica formulário como estorno */
        return $array_data;
    }

    /**
     * Usado pelo metodo extrato. Acessa objeto financial e retorna o extrato
     */
    private function _buscar_extrato($formName, $getVars, $data_inicio, $data_fim = '', $lblPrevBalance = "Previous Balance", $calc_prev_bal = true) {
        $Financeiro = $this->_load_financial($formName);
        $Financeiro->previous_balance = $calc_prev_bal;
        /* Adiciona filtros de busca ao extrato */
        foreach ($this->_default_filters as $filtro) {
            if (isset($getVars[$filtro]))
                $Financeiro->add_filter($filtro, $getVars[$filtro]);
        }
        $Financeiro->filter_by_period($data_inicio, $data_fim);
        $Financeiro->lbl_previous_balance = $lblPrevBalance;
        return $Financeiro->statement_formatted();
    }

    /**
     * Relaciona registro de estorno ao registro estornado.
     * @param string $reg_id - Registro do lançamento estorno
     * @param string $JSON - JSON relativo a postagem do estorno
     * @param string $campo_estorno - Nome do campo de relacionamento de estorno na tabela.
     * @return NULL se tudo der certo, ou mensagem de erro se algo der errado.
     */
    private function _relacionar_estorno_a_registro($reg_id, $formName, $JSON, $campo_estorno) {
        $arrJSON = json_decode($JSON, true); /* Decodifica JSON */
        if (!$arrJSON['id']) /* Se não existir ID, houve um erro de postagem. Aborta a execução */
            return 'ID não definido';
        $objCadastro = $this->_load_cadastro();
        $formData = array_merge(
                $objCadastro->ler($formName, $reg_id), array($campo_estorno => $arrJSON['id'])
        );
        /* Para atualizar o registro, as condições em opções de formulario XML devem estar DESATIVADAS */
        $objCadastro->xmlForms->check_options = false;
        $retorno = $objCadastro->formulario_de_edicao($formName, $reg_id, $formData);
        /* Decodifica retorno para verificar se houve algum erro e retorna o mesmo */
        if (!$retorno['JSON'])
            return 'No metodo "Controle_financeiro->_relacionar_estorno_a_registro()", houve algum problema ao
                relacionar o estorno em "Cadastro->formulario_de_edicao()", pois o JSON não foi gerado.';
        $arrJSON = json_decode($retorno['JSON'], true);
        if ($arrJSON['erro'])
            return $arrJSON['erro'];
        /* Se a execução chegou até aqui, significa que tudo ocorreu corretamente. */
    }

    /**
     * Recebe array com datas para pesquisa retorna array com datas formatadas
     * para busca SQL.
     * 
     * Descobre quais são as variaveis de data olhando $this->_dateVars
     * 
     * @param string $final - Data final de busca
     * @param string $data_inicio - Data inicial de busca
     * @param boolean $hojeFinal - Se true, caso não seja passada data final no getVars, assume data de hoje como data final
     * @param int $diasInicio - Se definido, conta a quantidade de dias da data final para definir data inicial
     */
    private function _processar_datas($data_final = '', $data_inicio = '', $diasInicio = false) {
        $date = array(
            'dateStart' => null,
            'date' => null
        );
        if ($data_final)
            $date['date'] = $this->_Local_formats->local_str_to_date($data_final);
        else /* Se data_inicio não foi definido, defini data_inicio como hoje */
            $date['date'] = date("Y-m-d");
        if ($data_inicio)
            $date['dateStart'] = $this->_Local_formats->local_str_to_date($data_inicio);
        elseif ($diasInicio)
            $date['dateStart'] = calc_date($date['date'], "-$diasInicio days", "Y-m-d");
        return $date;
    }

    private function _load_financial($formName) {
        $xmlFileName = $this->controller_vars['xml_path'] . $this->_xmlFileName;
        $Financial = new financial($xmlFileName, $formName);
        $Financial->keyfields = $this->keyfields;
        return $Financial;
    }

    /**
     * Carrega o cadastro de CONTRATOS
     */
    private function _load_cadastro() {
        return new cadastros($this->controller_vars, $this->controller_vars['xml_path'] . $this->_xmlFileName);
    }

    private function _msgErro_estornoExistente($id_registro) {
        ob_start();
        ?>
        <script>
            $(function(){
                if($('#modal_estorno').length){
                    setTimeout(function(){
                        $('#modal_estorno').remove();
                    },4000);
                    $("#modal_estorno").bind({
                        remove: function(){abrir_extratoNoForm();}
                    })
                }
            })
        </script>
        <p>Estorno relacionado no registro  <strong><?= $id_registro ?></strong>.</p>
        <?
        return ob_get_clean();
    }

}
?>