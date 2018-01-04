<?php

/**
 * Template Padrao do site...
 *
 * @author Junior
 */
class appTemaAction extends template {
    
    const jqueryui_version = 'jquery-ui-1.10.0.custom';
    const jqueryui_theme = 'smoothness';

    public function action() {
        $this->actionProtected['action'] = true;
        $this->array_data = array_merge($this->array_data,self::_jquery_ui_vars());
        $this->array_data['program'] = $GLOBALS['program'];
    }
    
    public function loginAction(){
        $this->actionProtected['loginAction'] = true;
        $this->array_data['program'] = $GLOBALS['program'];
    }
    public function logoutAction(){
        $this->actionProtected['loginAction'] = true;
        $this->array_data['program'] = $GLOBALS['program'];
    }

    public function form_cadastroAction() {
        $this->actionProtected['form_cadastroAction'] = true;
        $this->_process_above_below();
    }

    public function form_updateAction() {
        $this->actionProtected['form_updateAction'] = true;
        $this->_process_above_below();
    }

    public function form_registro_impressaoAction() {
        $this->actionProtected['form_registro_impressaoAction'] = true;
    }
    
    public function form_busca_avancadaAction(){
        $this->actionProtected['form_busca_avancadaAction'] = true;
    }
    
    public function resultados_busca_avancadaAction(){
        $this->actionProtected['resultados_busca_avancadaAction'] = true;
    }
    
    public function printAction() {
        $this->actionProtected['printAction'] = true;
    }
    public function print_noviewAction() {
        $this->actionProtected['print_noviewAction'] = true;
    }

    public function listagemAction() {
        $this->actionProtected['listagemAction'] = true;
        $this->array_data['URL_LEFT'] = '';
        $this->array_data['URL_BODY'] = '';
    }

    public function listagemListarAction() {
        $this->actionProtected['listagemListar'] = true;
    }

    public function jsindexAction() {
        header('Content-type: application/javascript');
        $this->array_data['__FILE'] = jfox_template_path($this->template_name . "/view/js/index.js");
    }
    
    public function jsloginAction(){
        header('Content-type: application/javascript');
        $this->array_data['__FILE'] = jfox_template_path($this->template_name . "/view/js/login.js");

    }
    
    public function jslistagem_acoesAction(){
        header('Content-type: application/javascript');
        $this->array_data['focus_item'] = "";
        if (isset($_GET['focus_item'])) {
            $this->array_data['focus_item'] = $_GET['focus_item'];
        }
        $this->templateFile = "js/listagem_acoes.js";
    }
    
    public function cssindexAction() {
        header('Content-type: text/css');
        $this->array_data['__FILE'] = jfox_template_path($this->template_name . "/view/css/index.css");
        $this->array_data['css_form'] = $this->process_template(array('appTema','cssforms'));
    }
    
    public function cssformsAction() {
        header('Content-type: text/css');
        $this->array_data['__FILE'] = jfox_template_path($this->template_name . "/view/css/forms.css");
    }
    
    public function cssprintAction() {
        header('Content-type: text/css');
        $this->array_data['__FILE'] = jfox_template_path($this->template_name . "/view/css/print.css");
    }

    public function main_menuAction() {
        $login_man = new login_man();
        $login = $login_man->restrict_execution(); /* Alem de restringir, também retorna informações sobre login */
        //Define o menu
        $menu_id = 1;
        if(isset($GLOBALS['main_menu'])){
           $menu_id = $GLOBALS['main_menu'];
        }
        $mainMenu = $this->_load_mainMenu($menu_id);
        $this->array_data['login_nome'] = $login['NOME_SIMPLIFICADO'];
        $this->array_data['main_menu'] = $mainMenu['body'];
        $this->array_data['main_menu_header'] = $mainMenu['header'];
        $this->array_data['view_body'] = '';
    }
    
    public function delete_boxAction() {
        $this->actionProtected['delete_boxAction'] = true;
    }

    private function _load_mainMenu($menu_id) {
        $data = array();
        $widget_vars['id'] = $menu_id;
        $widget_vars['template'] = 'superfish';
        $widget_vars['table_menu_name'] = '{prefixo}_MAIN_MENU';
        $widget_vars['load_jquery'] = false;
        $widget_vars['load_css'] = false;
        $index_menu = new widgets('jfox_menu', $widget_vars);
        $data['body'] = $index_menu->show_widget();
        $data['header'] = $index_menu->show_widget_header();
        return $data;
    }
    
    private function _process_above_below(){
        if (!isset($this->array_data['above_form'])) {
            $this->array_data['above_form'] = null;
        }
        if (!isset($this->array_data['below_form'])) {
            $this->array_data['below_form'] = null;
        }
    }
    
    private static function _jquery_ui_vars(){
        return array(
            'jqueryui_theme' => self::jqueryui_theme,
            'jqueryui_version' => self::jqueryui_version
        );
    }

}