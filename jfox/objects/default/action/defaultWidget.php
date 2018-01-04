<?php
/*
 * Widget padrao para ser usado de modelo para os demais.
 */

/**
 *
 * @author Junior_FOX
 */
class defaultWidget extends widgetController {

    protected function _set_default_vars(){
        $this->set_default_widget_var('null','null'); /*Linha apenas de exemplo*/
    }

    protected function _check_error_vars(){
        $error = $this->test_widget_var('null'); /*Linha apenas de exemplo*/
        return $error;
    }
    
    public function action(){
        /*Your Code Here*/
    }
}

?>