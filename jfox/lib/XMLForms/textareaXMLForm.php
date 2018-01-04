<?php

/*
 * To change this template, choose Tools | Templates
 * and open the template in the editor.
 */

/**
 * Description of textareaXMLForm
 *
 * @author cjuniorfox
 */
class textareaXMLForm extends inputXMLForm {

    const mysql_textarea_type = "longtext";

    public function createField() {
        $ckeditorConfig = self::_ckeditorConfig();
        if ($ckeditorConfig) {
            $field_name = (STRING) $this->XMLField['name'];
            $this->_createCKEditor($field_name, $ckeditorConfig, $this->value);
        } else {
            $str_properties = parent::propertiesToStr(self::properties());
            $this->HTML = "<textarea " . $str_properties . ">$this->value</textarea>";
        }
    }

    public function mysql_property() {
//Primeiro tenta tratar o relacionamento.
        if ($this->XMLField->relate->primary_key && $this->XMLField->relate->table) {
            return $this->mysql_property_relate();
        }
//Não é relacionamento? processa o dado diretamente.
        return self::mysql_textarea_type;
    }

    protected function properties() {
        $arrP = self::getProperties($this->XMLField);
        if (!isset($arrP['id']) && isset($arrP['name']))
            $arrP['id'] = $arrP['name'];
        return $arrP;
    }

    private function _ckeditorConfig() {
        if ($this->XMLField->ckeditor->enabled == "true") {
            $config = array();
            foreach ($this->XMLField->ckeditor->config as $config_item) {
                $config[(string) $config_item->key] = (string) $config_item->value;
            }
            return $config;
        }
    }

    /**
     * Cria Elemento CKEditor e aplica os códigos HTML e JS.
     */
    private function _createCKEditor($field_name, $config, $value = "") {
        $CKeditor = new CKEditor(ambient_vars::website_public_path() . "resources/ckeditor/");
        $this->HEADER = $CKeditor->init();
        $CKeditor->returnOutput = true;
        $CKeditor->textareaAttributes = self::properties();
        unset($CKeditor->textareaAttributes['name']);
        /* Envia as configs para o CKEditor */
        //$CKeditor->addEventHandler('blur', $this->_ckeditor_filltextbox($field_name));
        $this->HTML = $CKeditor->editor($field_name, $value, $config);
        $this->JS = $this->_ckeditor_js_onsubmit($field_name);
    }
    
    /**
     * Cria comando para preencher o textbox quando formulário é enviado.
     * Importante para aplicações que utilizam
     * Para este rodar, depende de Jquery.
     */
    private function _ckeditor_js_onsubmit($field_name){
        $form_name = $this->form_name;
        $function = $this->_ckeditor_filltextbox($field_name);
        return "$(\"form[name='$form_name']\").submit($function);";
    }

    /**
     * Retorna código javascript que faz o conteúdo do textarea ser preenchido.
     * @param string $field_name o nome do campo de texto a ser tratado
     * @return string código javascript.
     */
    private function _ckeditor_filltextbox($field_name) {
        $form_name = $this->form_name;
        $out = "function() {";
        $out .=     "var value = CKEDITOR.instances['$field_name'].getData();";
        $out .=     "var Objfield = document.forms['$form_name'].elements['$field_name'];";
        $out .=     "Objfield.value =  value;";
        $out .= "}";
        return $out;
    }

}
?>
