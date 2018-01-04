<?php

/*
 * To change this template, choose Tools | Templates
 * and open the template in the editor.
 */

/**
 * Description of inputXMLForm
 *
 * @author cjuniorfox
 */
class inputXMLForm extends Fields{
    
    const meiomask_js = "{SITE_PUBLIC_PATH}resources/jquery.meiomask/js/meiomask.js";
        
    public function str_value($value){
        $value = self::default_values($value);
        $arrOut = parent::look_and_get_related($value);
        $field_name = (string) $this->XMLField['name'];
        /**
         * @todo: Executar PHP a partir do XML, comando perigoso. Verificar aplicações de segurança
         */
        if($this->XMLField->return_php_code){ 
            $eval_code = (string) $this->XMLField->return_php_code;
            eval($eval_code);
            $arrOut[$field_name] = $value;
        }else{
            $arrOut[$field_name] = parent::convertDatatoStr($arrOut[parent::originalKey.$field_name], $this->XMLField,$this->language);
        }
        return $arrOut;
    }
    
    public function createField(){
        $properties = $this->properties();
        $mask = (string) $this->XMLField->mask;
        if (!$mask)/* Se mascara não foi definida, define mascara a partir do tipo de campo determinado */
            $mask = $this->defineMask($this->XMLField);
        $str_properties = parent::propertiesToStr($properties);
        $this->HTML = "<input " . $str_properties . "/>";
        if($mask){
            $this->JS = $this->createMeiomask($mask);
        }
    }
    
    /**
     * Este sobrescreve mysql_property de forms
     * 
     * Verifica pelo tipo de campo setado no XML a propriedade a ser retornada.
     */
    public function mysql_property() {
        $XMLField = $this->XMLField;
        
        //Primeiro tenta tratar o relacionamento.
        if($XMLField->relate->primary_key && $XMLField->relate->table){
            return $this->mysql_property_relate();
        }
        //Não é relacionamento? processa o dado diretamente.
        return Fields::mysql_input_type_by_format($XMLField);
    }


    protected function properties(){
        $arrP = self::getProperties($this->XMLField);
        if(!isset($arrP['id']) && isset($arrP['name']))
            $arrP['id'] = $arrP['name'];
        if (!isset($arrP['type']))
            $arrP['type'] = 'text';
        $arrP['value'] = $this->value;
        return $arrP;
    }
    
    protected function createMeiomask($mask) {
        if (!$this->HEADER) {
            $this->HEADER = "<script src='".self::meiomask_js."'></script>";
        }
        $css_element_id = $this->get_css_element_id('input', $this->getProperties($this->XMLField));
        return "$(\"$css_element_id\").setMask($mask);";
    }
    
    protected function defineMask($objXML) {
        if ($objXML->format_real->enabled == 'true')
            return "'decimal'";
        elseif ($objXML->format_integet->enabled == 'true')
            return "'integer'";
    }   
}

?>
