<?php
/**
 * Ajuda ao Controlador a trabalhar com seu XML de variaveis
 *
 * @author Carlos Junior
 */
class xmlcontrollervars {

    private $_xmldir;

    public function __construct($controller_vars) {
        $this->_xmldir = $controller_vars['controller_path'].'xml/';
    }
    
    public function loadxml($xmlname){
        $view       = new view();
        $xmldata    = $view->process_view(array(), $this->_xmldir.$xmlname.".xml");
        $objSimpleXML = new SimpleXMLElement($xmldata);
        return $objSimpleXML;
    }
}
?>
