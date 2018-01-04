<?php

/*
 * To change this template, choose Tools | Templates
 * and open the template in the editor.
 */

/**
 * Description of XMLForms_fields
 *
 * @author cjuniorfox
 */
class XMLForms_fields extends XMLForms {

    /**
     * Array com os valores a serem inseridos nos campos quando os mesmos forem impressos
     */
    private $_arrValues = array();

    /**
     * A ser populado por _get_field.
     * Os campos do formulário propriamente dito.
     */
    private $_arrFields = array();

    /**
     * A ser populado por _get_field
     * Cabeçalhos de inicialização das ferramentas usadas no XMLForms.
     */
    private $_arrHeader = array();

    /**
     * A ser populado por _get_field
     * Os códigos Javascript adicionados ao form
     */
    private $_arrJS = array(0 => array('js_code' => ''));

    /**
     * Alguns objetos de formulário exigem o dado do primary_key para algum uso.
     * Este registra o valor da $_primary_key caso o mesmo esteja presente no $arrHeader.
     */
    private $_primary_key_value; //Este possui o valor de primary_key e não o nome da chave do mesmo

    /**
     * Cria formulário a partir do XML
     * 
     * @param array $arrValues - Valores a serem aplicados nos campos do formulário.
     *      Deve-se usar a estrutura: $arrValues['nome_do_campo'] = 'valor'
     * 
     * @return array - Array de VIEW para impressão pelo controlador.
     */

    public function create_form() {
        $primary_key = $this->primary_key();
        if (isset($this->_arrValues[$primary_key]))
            $this->_primary_key_value = $this->_arrValues[$primary_key];
        $this->_fields();
        $arrForm_properties = Fields::getProperties($this->XMLForm);
        //A propriedade action está urlencoded por causa dos caracteres de escape do XML. portanto, decodifica este antes
        if (array_key_exists('action', $arrForm_properties))
            $arrForm_properties['action'] = urldecode($arrForm_properties['action']);
        return array(
            'headers' => $this->_headers(),
            'form_data' => $this->_arrFields,
            'js' => $this->_arrJS,
            'arrForm_properties' => $arrForm_properties,
            'form_properties' => Fields::propertiesToStr($arrForm_properties)
        );
    }

    /**
     * Quando setado, sobreescreve valores padrão dos campo por valores definidos
     * no $arrValues
     */
    public function overwrite_values($arrValues = array()) {
        foreach (array_keys($arrValues) as $key) {
            $this->_arrValues[$key] = $arrValues[$key];
        }
    }

    public function XMLField($field_name) {
        $XMLForm = $this->XMLForm;
        foreach ($XMLForm->field as $XMLField)
            if ($field_name == (string) $XMLField['name'])
                return $XMLField;
    }

    /**
     * Retorna array com o HTML dos campos
     * 
     * @param array $arrValues - Valores a serem aplicados nos campos do formulário.
     *      Deve-se usar a estrutura: $arrValues['nome_do_campo'] = 'valor'
     */
    private function _fields() {
        $XMLForm = $this->XMLForm;
        foreach ($XMLForm->field as $XMLField) {
            $this->_get_field($XMLField);
        }
    }

    /**
     * Busca pelo field e popula as variáveis com os valores relativos a este
     * Adiciona ao XMLField a chave class com o field_type do elemento. Caso o elemento já tenha class, adiciona o field_type como uma class a mais
     * Aplica também valores padrão ao $this->_arrValues.
     */
    private function _get_field($XMLField) {
        $field_name = (string) $XMLField['name'];
        $fieldType = (string) $XMLField->field_type;
        //Adiciona o field_type como classe do elemento. Caso o elemento já tenha classe definida, adiciona como uma classe adicional
        if ($XMLField['class']) {
            $XMLField['class'] = $XMLField['class'] . " " . $XMLField->field_type;
        } else {
            $XMLField['class'] = $XMLField->field_type;
        }
        $field_classname = $this->field_classname($XMLField);
        if (class_exists($field_classname) && $fieldType) {
            $key = self::_get_mysql_key_for_field($field_classname, $field_name);
            if ($key && array_key_exists($key, $this->_arrValues))
                $value = (string) $this->_arrValues[$key];
            elseif ($this->_is_multiselect_field($field_classname, $field_name) && isset($this->_arrValues[$field_name]))
                $value = $this->_arrValues[$field_name];
            else
                $value = (string) $XMLField->value;
            $OBJField = new $field_classname($XMLField, $this->language, $value, $this->form_name);
            if ($OBJField->request_primary_key)//Se o objeto exigir primary_key, envia o valor do mesmo ao objeto.
                $OBJField->define_primary_key($this->_primary_key_value); //Este é o valor do primary_key e não o nome da chave
            $OBJField->createField();
            $field = array(
                'field' => $OBJField->HTML(),
                'label' => $OBJField->label(),
                'name' => (string) $XMLField['name']
            );
            $this->_arrFields[] = $field;
            $JS = $OBJField->JS();
            $HEADER = $OBJField->HEADER();
            //Relacionamento entre tabelas
            $rel_key = $OBJField->rel_key();
            $rel_value = '';
            if (isset($this->_arrValues[$rel_key]))
                $rel_value = $this->_arrValues[$rel_key];
            $JS_relate = $OBJField->JS_relate($rel_value);
            //Aplica as variaveis corretas em seu lugar. HEADER, do JS e do relacionamento (RELATE)
            if ($JS)
                $this->_arrJS[]['js_code'] = $JS;
            if ($HEADER && !isset($this->_arrHeader[$fieldType])) //Se ja tiver sido adicionado antes, não adiciona novamente.
                $this->_arrHeader[$fieldType] = $HEADER;
            if ($rel_key && !isset($this->_arrHeader["_rel::" . $rel_key])) //Se ja tiver sido adicionado antes, não adiciona novamente.
                $this->_arrHeader["_rel::" . $rel_key] = $JS_relate;
        }
    }

    private function _get_mysql_key_for_field($field_classname, $field_name) {
        $OBJField = new $field_classname();
        if ($OBJField->multiSelect)
            return (string) $OBJField->selectKey . $field_name;
        return (string) $field_name;
    }

    /**
     * Verifica se o campo desejado é multiselect ou não
     * @return bool - Verdadeiro se campo for multiselect e false se não for
     */
    private function _is_multiselect_field($field_classname, $field_name) {
        $OBJField = new $field_classname();
        if ($OBJField->multiSelect)
            return true;
        return false;
    }

    private function _headers() {
        $buffer = null;
        foreach ($this->_arrHeader as $line) {
            $buffer .=$line . "\n";
        }
        return $buffer;
    }

}

?>
