<?php

/**
 * Este serve como controlador abstrato para controladores d econtrole financeiro
 * depende de AbsC_cadastros e suas dependencias
 *
 * @author juniorfox
 * 
 * @version 1.2;
 */
class AbsC_controle_financeiro extends AbsC_cadastros {

    public $version = 1.2;

    /* Combinação do $_GET com get passado via urlvar_array[0]. Quando aplicavel */
    private $_newGet = array();

    /* Variaveis para filtro de data */
    protected $dateVars = array(
        'date' => 'date',
        'dateStart' => 'dateStart'
    );
    protected $default_filters = array('operation', 'credit', 'debit', 'description');
    protected $prevBalance = true;
    protected $lblPrevBalance = "Previous Balance";

    /**
     * Adicione aqui as chaves de controle financeiro que sobrescreverão as 
     * definidas no xml (field->financial->keyfield)
     */
    protected $keyfields = array();

    /**
     * Nome do form no XML que contém os campos do controle financeiro.
     */
    protected $form_name = 'controle_financeiro';

    /**
     * Nome do form no XML que contém os filtros de busca para o extrato/saldo
     */
    protected $cf_form_name = "cf_filtros_extrato";
    protected $URL_BODY = ""; /* Saldo é carregado por filtrosExtrato */
    protected $URL_LEFT = "{controller_name}/filtros";

    /** 
     * URL a ser chamada e carregada na BODY quando action filtrosExtrato busca por um resultado 
     */
    protected $search_results = '{SITE_PATH}{controller_name}/saldo.extrato=true';
    protected $extrato = '{SITE_PATH}{controller_name}/extrato';
    
    /**
     * URL a ser chamada caso seja pedida uma impressão de saldo/extrato
     */
    protected $print_results = '{SITE_PATH}{controller_name}/imprimir_saldo.extrato=true&display=true';

    /**
     * Label dos campos débitos, créditos e balanço
     */
    protected $deb_cred_label = array(
        'lbl_debits' => 'Retiradas',
        'lbl_credits' => 'Depósitos',
        'lbl_balance' => 'Balanço'
    );

    /**
     * Exibe formulário com campos a serem usados como filtros de busca de resultados
     * por padrão, o template do controle_financeiro otimiza este form para o formato
     * de duas colunas do appTema
     */
    public function filtrosAction() {
        $objCadastro = $this->_load_cadastro();
        $cdata = $objCadastro->formulario_de_cadastro($this->cf_form_name);
        $cdata['form']['div_id'] = $cdata['formName'] = $this->cf_form_name;
        $cdata['search_results'] = $this->search_results; /* URL onde serão carregados os resultados da busca */
        $cdata['print_results'] = $this->print_results; /*URL de resultados de busca quando pedida impressão*/
        $this->array_data = array_merge($this->array_data, $cdata);
        $this->process_renderization(array('controle_financeiro', 'filtros'), $cdata);
    }

    /**
     * Imprime o extrato
     * 
     * @param boolean return (caso este seja 1 (para tal, deve ser chamado como metodo e não action)
     * retorna valores de $cdata.
     * 
     */
    public function extratoAction($return = false) {
        $this->_defineNewGet($this->urlvar_array);
        $Controle_financeiro = $this->_load_controle_financeiro();
        $cdata = $Controle_financeiro->extrato($this->form_name, $this->_newGet, $this->lblPrevBalance, $this->prevBalance);
        if ($cdata['extrato']) /* Se algum extrato for retornado */
            if (is_array($cdata['extrato'])) {
                /* Mescla no extrato e no saldo os labels com créditos, débitos e balanço */
                $cdata['extrato'] = array_merge($cdata['extrato'], $this->deb_cred_label);
                /* Busca arquivo (template ou não) para o extrato */
                $cdata['extrato']['__FILE'] = $this->_path_arquivo_extrato();
                $cdata['extrato']['css'] = $this->run_controller($this->controller_vars['controller_name'], 'cssextrato');
                if(array_key_exists('display', $this->_newGet))
                    $cdata['extrato']['cssdisplay'] = 'block';
                else
                    $cdata['extrato']['cssdisplay'] = 'none';
            }
        if ($cdata['saldo'])
            if (is_array($cdata['saldo']))
                $cdata['saldo'] = array_merge($cdata['saldo'], $this->deb_cred_label);
        if ($return) /* Se este for true, significa que foi passado parametro e este não foi executado com action */
            return $cdata;
        if (is_array($cdata['extrato'])) { /*Se existir extrato, imprime extrato*/
            $this->array_data = array_merge($this->array_data, $cdata['extrato']);
            $this->process_renderization(array('controle_financeiro', 'extrato'), $cdata);
        }else { /*Não existe o extrato? Então imprime na tela apenas a mensagem de erro (sem HTML)*/
            $this->array_data['body'] = $cdata['extrato'];
            $this->process_renderization(array('blank', 'simple_noview'));
        }
    }
    
    public function imprimir_saldoAction(){
        $this->array_data['body'] = $this->run_controller($this->controller_vars['controller_name'], 'saldo', $this->urlvar_array);
        $this->process_renderization(array('appTema','print_noview'));
    }

    /**
     * Imprime o saldo apenas.Este executa todo o extratoAction, porém retorna só
     * o saldo.
     * Se receber do $this->_newGet a var extrato, carrega o extrato na DIV extrato.
     * $this->_newGet é processado por extratoAction
     */
    public function saldoAction() {
        $this->_defineNewGet($this->urlvar_array);
        $cdata = $this->extratoAction(true);
        $cdata['css'] = $this->run_controller($this->controller_vars['controller_name'], 'cssextrato');
        if (!isset($this->_newGet['extrato']))
            $cdata['extrato'] = '';
        $this->array_data = array_merge($this->array_data, $cdata);
        $this->process_renderization('controle_financeiro', $cdata);
    }

    /**
     * Imprime formulário de operação.
     */
    public function efetuar_operacaoAction() {
        $this->_restrict_execution();
        $this->array_data['below_form'] = $this->run_controller($this->controller_vars['controller_name'], 'extratoNoForm');
        parent::cadastroAction();
    }

    /**
     * Imprime formulário que efetua o estorno.
     */
    public function estornoAction() {
        $this->_restrict_execution();
        $Controle_financeiro = $this->_load_controle_financeiro();
        $this->array_data = array_merge($this->array_data, $Controle_financeiro->formulario_estorno($this->form_name));
        $this->process_renderization($this->array_data['template']);
    }

    public function cssextratoAction() {
        $this->process_renderization(array('controle_financeiro', 'cssextrato'));
    }
    
    public function extratoNoFormAction(){
        $this->array_data['formName'] = $this->form_name;
        $this->array_data['extrato'] = $this->extrato;
        $this->process_renderization(array('controle_financeiro', 'extratoNoForm'));
    }

    /**
     * Método usado para mesclar a variavel do PHP $_GET com dados passados pela
     * url via $urlvar_array (Multiget do framework).
     */
    private function _defineNewGet($urlvar_array) {
        $getUrlVar = array();
        //Checa se existe urlvar_array. Caso tenha, converte o mesmo para array
        if (is_array($urlvar_array))
            if (isset($urlvar_array[0]))
                $getUrlVar = strGetToArray($urlvar_array[0]);
        return $this->_newGet = array_merge($_GET, $getUrlVar);
    }

    private function _load_controle_financeiro() {
        $Controle_financeiro = new controle_financeiro(
                        $this->controller_vars,
                        $this->xmlFileName,
                        $this->dateVars,
                        $this->default_filters
        );
        $Controle_financeiro->keyfields = $this->keyfields;
        return $Controle_financeiro;
    }

    /**
     * Retorna o caminho do path do extrato
     */
    private function _path_arquivo_extrato() {
        $template_extrato = $GLOBALS['global_vars']['template_path'] . "controle_financeiro/view/extrato.html";
        $view_extrato = $this->controller_vars['view_path'] . "extrato.html";
        if (file_exists($view_extrato))
            return $view_extrato;
        elseif (file_exists($template_extrato))
            return $template_extrato;
        die('Erro: Em <strong>AbsC_controle_financeiro</strong>, n&atilde;o foi 
            encontrado <i>view</i> ou <i>template</i> para extrato.<br />');
    }

}

?>
