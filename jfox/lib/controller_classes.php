<?php
/**
 * Classe de controle das classes que sao trabalhadas dentro dos controladores
 *
 * @author juniorfox
 */
abstract  class controller_classes extends controller {
    protected $global_vars;
    protected $controller_vars;

    public function  __construct($controller_vars) {
       global $global_vars;
       $this->global_vars       = $global_vars;
       $this->controller_vars   = $controller_vars;
       if(file_exists($this->controller_vars['xml_path']."index.xml"))  {
            $this->xml = $this->loadxml('index');
        }
    }

    protected function check_empty_fields($array_fieldname){
        $error = false;
        foreach ($array_fieldname as $fieldname){
            if(!$_POST[$fieldname]) $error .= "Campo $fieldname está vazio. <br />";
        }
        return $error;
    }
}
?>
