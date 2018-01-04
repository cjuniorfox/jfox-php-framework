<?php

/*
 * To change this template, choose Tools | Templates
 * and open the template in the editor.
 */

/**
 * Description of buttonXMLForm
 *
 * @author cjuniorfox
 */
class buttonXMLForm extends inputXMLForm {

    public function str_value($value) {
        return array();
    }
    
    public function mysql_field_name(){
        if($this->XMLField->enable_table == "true")
            return parent::mysql_field_name ();
        return NULL;
    }

    public function createField() {
        if (!$this->XMLField['type'])
            $this->XMLField['type'] = 'submit';
        if (!$this->XMLField['name'])
            $this->XMLField['name'] = 'submit';
        return parent::createField();
    }

}

?>
