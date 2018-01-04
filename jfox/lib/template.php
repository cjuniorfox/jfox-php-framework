<?php

/**
 * Processa as informacoes contidas na template
 *
 * @author Junior
 */
abstract class template {

    protected $global_vars;
    protected $actionProtected; /*Array com actions que não devem ser carregadas via URL*/
    protected $template_name;
    protected $template_vars; /*Array com valores relativo as vars do template*/
    protected $action_name;
    private $_action; /*O nome da Action a ser executada*/
    public $array_data;
    public $templateFile; /* Publica, definida pelo action do template, apenas o nome do arquivo sem diretorio */
    
    /**
     * Define o header do template usado.
     * ex: Content-type=text/css
     */
    public $header;

    public function __construct($template_name, $action_name = null) {
        global $global_vars;
        $this->array_data = array();
        $this->global_vars = $global_vars;
        if ($template_name) {
            $this->template_name = $template_name;
        } elseif (isset($global_vars['template'])) {
            $this->template_name = $global_vars['template'];
        }
        if (method_exists($template_name . "Action", $action_name . "Action")) {
            $this->action_name = $action_name;
            $action = $action_name . "Action";
        } else {
            $action = "action";
            $this->templateFile = "index.html";
        }
        $this->_set_template_vars();
        /*Se existir global_begin, execute-o antes de continuar*/
        if(method_exists($this->template_name . "Action", "global_begin")){
            $this->global_begin();
        }
        $this->_action = $action;
        $this->$action();
    }

    /*
     * Retorna o template processado
     */

    public function renderize_template() {
        $dir = jfox_template_path($this->template_name . "/view/");
        if ($this->action_name) {
            $file = $this->action_name . ".html";
        }else{
            $file = "index.html";
        }
        if ($this->templateFile) {
            $file = $this->templateFile;
        }
        if (!isset($this->array_data["__FILE"])) {
            $this->array_data["__FILE"] = $dir . $file;
        }
        /*Se existir {action}_post, executa-o antes de renderizar*/
        if(method_exists($this->template_name . "Action", $this->_action."_post")){
            $postAction = $this->_action."_post";
            $this->$postAction();
        }
        /*Se existir global_end, execute-o antes de renderizar*/
        if(method_exists($this->template_name . "Action", "global_end")){
            $this->global_end();
        }
        $view = new view();
        return $view->process_view($this->array_data, $this->array_data["__FILE"]);
    }
    
    public function actionProtected($action_name){
        if(!$action_name){
            $action_name = "action";
        }
        if(isset($this->actionProtected[$action_name])){
            return true;
        }
        return false;
    }
    
    protected function process_template($templateVars) {
        $objTemplateController = new templateController($templateVars);
        return $objTemplateController->renderize_template();
    }

    private function _set_template_vars(){
        /*Cria os valores para popular $this->template_vars*/
        $this->template_vars['view_path'] = jfox_template_path("/".$this->template_name."/view/");
        $this->template_vars['action_path'] = jfox_template_path("/".$this->template_name."/action");
    }

}

?>
