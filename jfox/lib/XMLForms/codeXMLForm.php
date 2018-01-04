<?php

/*
 * To change this template, choose Tools | Templates
 * and open the template in the editor.
 */

/**
 * Description of codeXMLForm
 *
 * @author cjuniorfox
 */
class codeXMLForm extends Fields {
    
    public function str_value($value){
        return array();
    }
    
    public function createField(){
        $XMLField = $this->XMLField;
        if($XMLField->code)
            $this->HTML = (string) $XMLField->code;
    }
}

?>
