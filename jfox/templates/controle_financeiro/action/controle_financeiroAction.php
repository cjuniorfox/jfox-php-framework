<?php

/*
 * Template para tratar com o controle financeiro.
 * Ideal para uso junto do appTema
 * 
 * Depende de Jquery e JqueryUI
 * 
 * Template falso, isso significa que apesar de ser um template, este se 
 * comporta como views genericas
 * 
 * @author cjuniorfox
 */
class controle_financeiroAction extends template {
    
    /**
     * Tela de saldo do form
     */
    public function action() {
        $this->actionProtected['action'] = true;
    }
    
    public function filtrosAction(){
        $this->actionProtected['filtrosAction'] = true;
        $this->array_data['js_extrato'] = $this->process_template(array('controle_financeiro','js_extrato'));;
    }
    
    public function extratoAction(){
        $this->actionProtected['extatoAction'] = true;
    }
    
    public function extratoNoFormAction(){
        $this->array_data['js_extrato'] = $this->process_template(array('controle_financeiro','js_extrato'));;
    }
    
    public function cssextratoAction(){
        $this->array_data['__FILE'] = jfox_template_path($this->template_name . "/view/css/extrato.css");
    }
    
    public function js_extratoAction(){
        $this->array_data['__FILE'] = jfox_template_path($this->template_name . "/view/js/js_extrato.js");
    }
}

?>
