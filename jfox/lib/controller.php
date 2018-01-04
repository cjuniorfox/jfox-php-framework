<?php

/**
 * Classe abstrata, para gerenciar os controladores...
 *
 * @author Junior
 * 
 * CHANGELOG
 * 
 * 2.0-2013.02.05 - Adicionado controlador global jsAction para gerar arquivos JS para a aplicação.
 * 2.1-2014.04.17 - Adicionado Template controller.
 */
abstract class controller {

    const version = 2.1;

    protected $global_vars;
    protected $controller_vars;
    protected $urlvar_array;
    protected $xml;
    private $_controller_name;
    private $_action_name;
    private $_template;
    private $_data_renderizated; /* Quando um controlador executado dentro de outro, informações renderizadas para serem retornadas */
    private $_subcontroller_process; /* Se true, não imprime direto a renderização, a armazena em _data_renderized */
    public $array_data = array();

    public function __construct($controller_name, $action_name, $urlvar_array, $subcontroller = false) {
        global $global_vars;
        $this->_subcontroller_process = $subcontroller;
        $this->global_vars = $global_vars;
        $this->_controller_name = $controller_name;
        $this->urlvar_array = $urlvar_array;
        $this->_action_name = $action_name;
        $action = $action_name . "Action";
        $this->controller_vars['view_path'] = $global_vars['view_path'] . $controller_name . "/";
        $this->controller_vars['controller_path'] = $global_vars['controller_path'] . $controller_name . "/";
        $this->controller_vars['controller_name'] = $controller_name;
        $this->controller_vars['action_name'] = $action_name;
        $this->controller_vars['controller_url'] = $global_vars['site_path'] . $controller_name . "/";
        $this->controller_vars['action_url'] = $this->controller_vars['controller_url'] . $action_name . "/";
        $this->controller_vars['urlvar_array'] = $urlvar_array;
        $this->controller_vars['xml_path'] = $this->controller_vars['controller_path'] . 'xml/';
        if (file_exists($this->controller_vars['xml_path'] . "index.xml")) {
            $this->xml = $this->loadxml('index');
        }
        $this->$action();
    }

    /**
     * IndexAction padrão, usado quando não há indexAction definido no controlador.
     * No index do controlador index, executa erro 404 quando aplicável.
     */
    public function indexAction() {
        /* Código para ERRO 404. */
        if ($this->controller_vars['controller_name'] == 'index' && $this->urlvar_array) {
            header("Status: 404 Not Found");
            $this->process_renderization(array('index', 'error404'));
        }
        /* Não é erro 404?, segue carregamento */
        $this->process_renderization($this->global_vars['template']);
    }

    /**
     * Caso o controlador opere arquivos javascripts, com este você pode criar
     * arquivos javascript apenas criando a view javascript e jogando na pasta js
     * dentro da view.
     */
    public function jsAction($cdata = array()) {
        if (!$this->urlvar_array[0])
            $js = $this->controller_vars['controller_name'];
        else
            $js = (string) $this->urlvar_array[0];
        header("Content-type: text/javascript");
        $this->process_renderization(array('blank', 'simple'), $cdata, "js/$js.js");
    }

    public function get_data_renderized() {
        return $this->_data_renderizated;
    }

    /**
     * Verifica se o arquivo de view existe na pasta view para o controlador em execução.
     * @param string $view_name - Nome da view desejada (sem .html), para view padrão, deixe em branco
     * @return bool - Existe ou não o arquivo de view
     */
    public function view_exists($view_name = null) {
        if (!$view_name)
            $view_name = $this->_action_name;
        $file = $this->_get_view_file($view_name);
        if (file_exists($file))
            return true;
        return false;
    }

    protected function loadxml($xmlname) {
        $view = new view();
        $xmldata = $view->process_view(array(), $this->controller_vars['xml_path'] . $xmlname . ".xml");
        $objSimpleXML = new SimpleXMLElement($xmldata);
        return $objSimpleXML;
    }

    /* Carrega a classe view, modifica as vars desejadas, e imprime o html de saida */

    protected function renderize() {
        /* Adiciona variaveis padrão do controlador ao array_data */
        if (is_array($this->array_data))
            $this->array_data = array_merge($this->array_data, $this->controller_vars);
        /* renderiza o template e a view */
        $this->_template->array_data = $this->array_data;
        $renderized = $this->_template->renderize_template();
        if ($this->_subcontroller_process) {
            /* Se for um subprocesso de controlador, armazeda dados renderizados em $this->_data_renderized */
            $this->_data_renderizated = $renderized;
            return null;
        }
        if ($this->_template->header())
            header($this->_template->header());
        if (isset($this->array_data['__DOMPDF'])) {
            /* Se for para retornar PDF, envia o header correto */
            header("Content-type:application/pdf");
        }
        /* Renderiza o template concatenado com a view, e encerra */
        die($renderized);
    }

    protected function process_renderization($template = array(), $controller_data = array(), $controller_view = null, $template_bodyname = 'template_body', $template_headername = 'template_header') {
        if ($controller_view == null) {
            $controller_view = $this->_action_name;
        }
        if (is_string($template)) {
            $template = array($template);
        } elseif (!is_array($template)) {
            $template = array();
        }
        $this->_template = new templateController($template);

        /* Caso tenha aplicado direto o controller_data no array_data[template_bodyname], passa o mesmo para controller_data para ser processado */
        if (!$controller_data && isset($this->array_data[$template_bodyname])) {
            $controller_data = $this->array_data[$template_bodyname];
        }
        $this->array_data[$template_bodyname] = '';
        $this->array_data[$template_headername] = '';
        $this->_process_controller_data($controller_data, $controller_view, $template_bodyname, $template_headername);
        /* Renderiza tudo */
        $this->renderize();
    }

    /**
     * Processa arquivo e retorna dados em JSON.
     * Atenção: É necessária view blank instalada para este método funcionar.
     * @param array $json - array com dados a serem convertidos para json
     */
    protected function process_json($json) {
        $this->array_data['template_body'] = json_encode($json);
        $this->process_renderization(array('blank', 'simple'), array(), '', 'null');
    }

    /**
     *  Declara o nome da classe a ser executada 
     */
    protected function declare_class($classname) {
        include_once($this->controller_vars['controller_path'] . "classes/$classname.php");
        $cvars = $this->controller_vars;
        /* Se existir, repassa os dados dos arquivos XML */
        if (isset($this->xml)) {
            $cvars['xml'] = $this->xml;
        }
        return new $classname($cvars);
    }

    protected function run_controller($controller_name, $action_name, $urlvar_array = null) {
        global $global_vars;
        $controllerFile = $global_vars['controller_path'] . $controller_name . "/" . $controller_name . "Controller.php";
        if (file_exists($controllerFile)) {
            /* Instancia e executa o novo controlador como um subprocesso do controlador atual */
            include_once $controllerFile;
            $OBJController_name = $controller_name . "Controller";
            if (!$urlvar_array) {
                $this->urlvar_array = $urlvar_array;
            }
            $objController = new $OBJController_name($controller_name, $action_name, $urlvar_array, true);
            /* Retorna as informações do process */
            return $objController->get_data_renderized(); /* Retorna o que foi renderizado do controlador */
        }
    }

    protected function run_template($template_name, $action_name, $urlvar_array = array()) {
        $templateVars = array_merge(array($template_name, $action_name), $urlvar_array);
        $objTemplateController = new templateController($templateVars);
        if ($objTemplateController->header()) {
            header($objTemplateController->header());
        }
        return $objTemplateController->renderize_template();
    }

    private function _process_controller_data($controller_data, $controller_view, $template_bodyname, $template_headername) {
        $this->array_data[$template_headername] = '';
        if (!is_array($controller_data)) {
            $this->array_data[$template_bodyname] = $controller_data;
            return null; /* Só pode-se adicionar chaves a controller_data se ele for array. Caso não seja, nao adiciona nada */
        }
        if (!isset($controller_data['__FILE'])) {
            $file = $this->_get_view_file($controller_view);
            if (file_exists($file)) {
                $controller_data["__FILE"] = $file;
            }
        }
        /* Se controller_data for um array */
        if (is_array($controller_data)) {
            /* Processa o Header (cabeçalho) */
            $headerfile = $this->controller_vars['view_path'] . $controller_view . '.header.html';
            if (file_exists($headerfile) && !isset($controller_data['_HEADER']['__FILE'])) {
                $controller_data['_HEADER']['__FILE'] = $headerfile;
            }
            if (isset($controller_data['_HEADER']))
                $this->array_data[$template_headername] = $controller_data['_HEADER'];
        }
        $this->array_data[$template_bodyname] = $controller_data;
    }

    private function _get_view_file($controller_view) {
        /* Verifica se no nome da view, ja não foi definida extensão */
        $cViewArr = explode(".", $controller_view);
        if (count($cViewArr) > 1) {
            $cViewExt = $cViewArr[count($cViewArr) - 1];
            unset($cViewArr[count($cViewArr) - 1]);
            $cViewName = implode(".", $cViewArr);
        } else {
            $cViewName = $controller_view;
            $cViewExt = "html";
        }
        return $this->controller_vars['view_path'] . "$cViewName.$cViewExt";
    }

}

?>
