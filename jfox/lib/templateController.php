<?php

/*
 * To change this template, choose Tools | Templates
 * and open the template in the editor.
 */

/**
 * Description of templateController
 *
 * @author juniorfox
 */
class templateController {

    /**
     * Define header do documento para este template
     */
    public $templateName;
    public $array_data;
    private $_templateFile;
    private $_arrTemplate;
    private $_actionName;
    private $_objTemplate;

    public function __construct($arrTemplate = array()) {
        $this->_arrTemplate = $arrTemplate;
        $this->_define_template_name();
        $this->_load_template();
        $this->_define_action_name();
        $template_obj = $this->templateName . "Action";
        $this->_objTemplate = new $template_obj($this->templateName, $this->_actionName);
    }

    public function renderize_template() {
        if ($this->array_data) {
            $this->_objTemplate->array_data = $this->array_data + $this->_objTemplate->array_data;
        }
        return $this->_objTemplate->renderize_template();
    }

    public function actionProtected() {
        $actionName = $this->_actionName;
        return $this->_objTemplate->actionProtected($actionName);
    }
    
    /**
     * Retorna o header do template usado.
     */
    public function header(){
        return $this->_objTemplate->header;
    }

    private function _define_template_name() {
        $filename = '';
        if (isset($this->_arrTemplate[0])) {
            $suggestTemplName = $this->_arrTemplate[0];
            $filename = jfox_template_path($suggestTemplName . "/action/" . $suggestTemplName . "Action.php");
        }
        if (file_exists($filename)) {
            $this->templateName = $suggestTemplName;
            unset($this->_arrTemplate[0]);
        } elseif (isset($GLOBALS['global_vars']['template'])) {
            $this->templateName = $GLOBALS['global_vars']['template'];
            $filename = jfox_template_path( $this->templateName . "/action/" . $GLOBALS['global_vars']['template'] . "Action.php");
        }
        $this->_templateFile = $filename;
    }

    private function _load_template() {
        include_once($this->_templateFile);
    }

    private function _define_action_name() {
        $action_name = null;
        if (is_array($this->_arrTemplate) && $this->_arrTemplate) {
            foreach ($this->_arrTemplate as $value) {
                $arrTemplate[] = $value;
            }
            $action_name = $arrTemplate[0];
        }
        if (method_exists($this->templateName . "Action", $action_name . "Action")) {
            $this->_actionName = $action_name;
        }
    }

}

?>
