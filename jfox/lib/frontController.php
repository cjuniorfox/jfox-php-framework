<?php

/*
 * Verifica URL Digitada e carrega controlador ou template desejado.
 *
 * @author Junior
 * @version 1.3
 */

class frontController {

    public $version = 1.3;
    private $_controller_name;
    private $_action_name;
    private $_controller;
    private $_controllerValues;
    private $_controllerArray;
    private $_page; /* Comandos passados via url apos o index.php */
    private $_isTemplate; /* Se invocada /_template e não existir controlador, este recebe true */
    private $_templateVars; /* Caso seja template, recebe as vars para processar template */
    private $_urlFormat; /* quando path, significa que url seria index.php/controlador. Quando GET seria index.php/?controlador */

    public function __construct($urlFormat = 'path') {
        $this->_urlFormat = $urlFormat;
        $this->_get_controller_string();
        $this->_load_controller_and_get_controllerValues();
        $this->_define_actionName_and_controllerVars();
        $this->_start_bootstrap();
        if ($this->_isTemplate) {
            $this->_process_template(); /* Se template não for processado ou bypassado, vai direto para execute_controller */
        }
        $this->_execute_controller();
    }

    /**
     * Dependendo da versão do PHP, o server retorna chaves diferentes.
     * Aqui vai tentando identificar as chaves conhecidas para descobrir qual
     * URL foi dada entrada.
     */
    private function _get_controller_string() {
        if ($this->_urlFormat == strtolower('get')) {
            $this->_page = $this->_controller_string_get();
        } else {
            $this->_page = $this->_controller_string_path();
        }
    }

    /**
     * Retorna string do controlador quando o mesmo é definido por GET
     */
    private function _controller_string_get() {
        //Busca a string do controlador
        $self = $_SERVER['QUERY_STRING'];
        //O GET atual não é valido como GET. Manipula variavel global $_GET do PHP
        //para receber os valores $_GET que foram passados.
        $tmpArr = explode('?', $_SERVER['REQUEST_URI']);
        if (isset($tmpArr[1]))
            $_GET = strGetToArray($tmpArr[1]);
        else
            $_GET = array();
        return "/" . $self;
        //Manipula var global $_GET para receber o GET desejado e não o GET real da URL inserida.
    }

    /**
     * Retorna a string do controlador quando a mesma é configurada por path
     * @return string
     */
    private function _controller_string_path() {
        if (isset($_SERVER['SUPHP_URI'])) {
            $self = str_replace("\\", "/", $_SERVER['SUPHP_URI']);
        } elseif (isset($_SERVER['ORIG_PATH_INFO'])) {
            $self = str_replace("\\", "/", $_SERVER['SCRIPT_NAME'] . $_SERVER['ORIG_PATH_INFO']);
        } elseif (isset($_SERVER['PATH_INFO'])) {
            $self = str_replace("\\", "/", $_SERVER['SCRIPT_NAME'] . $_SERVER['PATH_INFO']);
        } else { //Não encontrou redirecionamento? Envia endereço raiz.
            $self = str_replace("\\", "/", $_SERVER['SCRIPT_NAME']);
        }
        $tmp_explode = explode($_SERVER['SCRIPT_NAME'], $self);
        /* Verifica se existem dados em $values (repassados em URL. Caso nao
          exista variaveis registradas, define ''index' default para controller e action */
        foreach($tmp_explode as $string_path){
            if ($string_path){
                return $string_path;
            }
        }
        return '/';
    }

    private function _load_controller_and_get_controllerValues() {
        $values = array();
        global $global_vars;
        $page = str_replace(".", "/", $this->_page);
        if ($page && $page != "/") {
            $values = explode("/", $page);
        }
        if (isset($values[0])) {
            while (!$values[0] && count($values) > 1) {
                array_shift($values);
            }
        }

        if (!isset($values[0])) {
            $values[0] = "index";
        }
        $controller_name = $values[0];
        $controller_path = $global_vars['controller_path'] . $controller_name . "/";
        $controllerFile = $controller_path . $controller_name . "Controller.php";
        if (file_exists($controllerFile)) {
            unset($values[0]);
        } else {
            $controller_path = $global_vars['controller_path'] . "index/";
            $controllerFile = $controller_path . "indexController.php";
            $controller_name = "index";
        }
        /* Caso _template seja invocado, e não exista controlador/index_view com esse nome, marca is_template como true */
        if ($controller_name != "_template" && @$values[0] == "_template") {
            $this->_isTemplate = true;
            $tmpTemplates = $values;
            unset($tmpTemplates[0]);
            foreach ($tmpTemplates as $tmpvalue) {
                $this->_templateVars[] = $tmpvalue;
            }
        }
        if (file_exists($controller_path . "classes/")) {
            add_to_library($controller_path . "classes/");
        }
        include_once $controllerFile;
        $this->_controller_name = $controller_name;
        $this->_controllerValues = $values;
    }

    private function _define_actionName_and_controllerVars() {
        $controllerValues = $this->_controllerValues;
        $revContrlrValues = array_reverse($this->_controllerValues);
        $this->_action_name = null;
        /* Ira agora testar se a action solicitada existe. Caso nao exista,
         * ira remover as values de traz pra frente, e inseri-las em um array
         * que podera ser acessado pelo controlador
         */
        $method_exists = false;
        $num_data = count($revContrlrValues);
        for ($i = 0; $i <= $num_data; $i++) {
            $sugActionName = implode("", $revContrlrValues);
            if (method_exists($this->_controller_name . "Controller", $sugActionName . "Action")) {
                $method_exists = true;
                $this->_isTemplate = false; /* Caso seja encontrado o metodo com nome _template, este é processado */
                $this->_action_name = $sugActionName;
            } elseif (!$method_exists) {
                if (isset($revContrlrValues[$i])) {
                    $controller_array[] = $revContrlrValues[$i];
                }
                unset($revContrlrValues[$i]);
            }
        }
        if (!$this->_action_name) {
            $this->_action_name = 'index';
        }
        if (isset($controller_array)) {
            $this->_controllerArray = array_reverse($controller_array);
        } else {
            $this->_controllerArray = array();
        }
    }

    private function _start_bootstrap() {
        new bootstrap();
    }

    private function _process_template() {
        $objTemplateController = new templateController($this->_templateVars);
        if (!$objTemplateController->actionProtected()) {
            if ($objTemplateController->header())
                header($objTemplateController->header());
            die($objTemplateController->renderize_template());
        }
    }

    private function _execute_controller() {
        $controller_name = $this->_controller_name;
        $action_name = $this->_action_name;
        $controllerArray = $this->_controllerArray;
        $controller = $controller_name . "Controller";
        $this->_controller = new $controller($controller_name, $action_name, $controllerArray);
    }

}

?>
