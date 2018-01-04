<?php

/*
 * To change this template, choose Tools | Templates
 * and open the template in the editor.
 */

/**
 * Description of xmlForms_options
 *
 * @author juniorfox
 */
class xmlForms_options extends xmlForms{
    
    /**
     * Verifica por opção update->disable no documento XML
     * @param string option | Nome da opção de disable a ser processada
     * @param SimpleXMLElement $xmlForm | XML do formulário tratado
     * 
     */
    
    public function disable($option,$xmlForm){
        $erro = null;
        $xmlDisable = $xmlForm->options->disable->$option;
        if($xmlDisable){
            /*Varre a opção por fields e verifica se value do disable bate com
             * o value do field em si
             */
            foreach($xmlDisable->field as $xmlDField){
                $erro .= xmlForms_options::_disableField($xmlForm,$xmlDField);
            }
        }
        return $erro;
    }
    private function _disableField($xmlForm,$xmlDField){
        $fId = $this->xmlItemId($xmlForm, 'field', (string) $xmlDField['name']);
        $xmlField = $xmlForm->field[$fId];

        if( (string) $xmlField->value == (string) $xmlDField->value)
            return $xmlDField->message;
    }
}

?>
