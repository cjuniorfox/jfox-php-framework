<?php

/**
 * Serve como base para o carregamento de bibliotecas javascript e entre outros
 * utilizado pelo framework.
 * 
 * Carrega coisas como JqueryUI, jQuery, demais bibliotecas como datepicker,
 * entre outros.
 *
 * @author cjuniorfox
 */
class phpjfox_frameworkAction extends template {

    const version = 1.0;

    private $_versions_vars = array(
        'jquery_version' => '1.9.1',
        'jqueryMigrate_version' => '1.1.1',
        'jqueryMigrate_mute' => 'false', //Quando true, apresenta no console mensagens com codigos depressiados do jquery
        'jqueryui_version' => '1.10.0',
        'jqueryui_theme' => 'smoothness',
        'jqueryAddress' => '1.5',
        'jqueryModalbox' => '2.0',
        'jqueryMessagebox' => '2.0'
    );

    private function _version_vars() {
        $arrVersions = $this->_versions_vars;
        foreach (array_keys($arrVersions) as $key) {
            if (isset($_GET[$key]))
                $arrVersions[$key] = $_GET[$key];
        }
        return $arrVersions;
    }

    public function action() {
        
    }

    public function jsfunctionsAction() {
        header('Content-type: application/javascript');
        $this->templateFile = 'js/functions.js';
    }

    public function jsjqueryAction() {
        header('Content-type: application/javascript');
        $this->array_data = array_merge($this->array_data, $this->_version_vars());
        $this->templateFile = 'js/jquery.js';
    }
    
    public function jscssfixesAction() {
        header('Content-type: application/javascript');
        $this->array_data = array_merge($this->array_data, $this->_version_vars());
        $this->templateFile = 'js/cssfixes.js';
    }

    public function jsextendedAction() {
        header('Content-type: application/javascript');
        $this->templateFile = 'js/extended.js';
        $this->array_data = array_merge($this->array_data, $this->_version_vars());
        
    }

    /**
     * JS para inicialização basica
     */
    public function jsbasic_iniAction() {
        header('Content-type: application/javascript');
        $this->array_data['functions'] = $this->process_template(array('phpjfox_framework', 'jsfunctions'));
        $this->array_data['jquery'] = $this->process_template(array('phpjfox_framework', 'jsjquery'));
        $this->array_data['cssfixes'] = $this->process_template(array('phpjfox_framework', 'jscssfixes'));
        $this->templateFile = 'js/basic_ini.js';
    }

    /**
     * JS para inicialização completa, iniciando este não precisa iniciar mais ninguem
     */
    public function jscomplete_iniAction() {
        header('Content-type: application/javascript');
        $this->array_data['functions'] = $this->process_template(array('phpjfox_framework', 'jsfunctions'));
        $this->array_data['jquery'] = $this->process_template(array('phpjfox_framework', 'jsjquery'));
        $this->array_data['cssfixes'] = $this->process_template(array('phpjfox_framework', 'cssfixes'));
        $this->array_data['extended'] = $this->process_template(array('phpjfox_framework', 'jsextended'));
        $this->templateFile = 'js/complete_ini.js';
    }

}

?>
