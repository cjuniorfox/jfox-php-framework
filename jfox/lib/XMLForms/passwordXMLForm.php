<?php

/*
 * To change this template, choose Tools | Templates
 * and open the template in the editor.
 */

/**
 * Description of passwordXMLForm
 *
 * @author cjuniorfox
 */
class passwordXMLForm extends inputXMLForm {
   
    public function createField() {
        $this->value = ""; //Para password, o valor é sempre vazio
        if (!$this->XMLField['type']){
            $this->XMLField['type'] = 'password';
        }
        return parent::createField();
    }
}

?>
