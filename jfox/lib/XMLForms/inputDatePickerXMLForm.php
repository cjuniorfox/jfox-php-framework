<?php

/*
 * To change this template, choose Tools | Templates
 * and open the template in the editor.
 */

/**
 * Description of input_datePickerXMLForm
 *
 * @author cjuniorfox
 */
class inputDatePickerXMLForm extends inputXMLform {
    
    public function createField(){
        $css_element_id = $this->get_css_element_id('input', $this->getProperties($this->XMLField));
        $this->XMLField['readonly'] = 'readonly';
        $this->JS = "$(\"$css_element_id\").datepicker({changeMonth:true, changeYear:true, dateFormat: \"".$this->mask()."\"});";
        parent::createField();
    }
    
    public function mask(){
        $objLocalFormats = new local_formats();
        $xmlLocalFormats = $objLocalFormats->xmlData;
        $language = $this->language;
        $subtype = (string) $this->XMLField->format_date->subtype;
        /* Busca variaveis padrão e efetua procedimento de criação de componente */
        $mask = (string) $xmlLocalFormats->$language->date->$subtype;
        if(!$mask){
            $mask = $subtype;
        }
            
        $mask = str_replace(array('d', 'm', 'Y','D', 'M', 'Y'), array('dd', 'mm', 'yy','DD','MM','YY'), $mask);
        $mask = str_replace(array('\dd','\mm','\yy','\DD','\MM','\YY'),array("'d'","'m'","'y'","'D'","'M'","'Y'"),$mask);
        return $mask;
    }
}

?>
