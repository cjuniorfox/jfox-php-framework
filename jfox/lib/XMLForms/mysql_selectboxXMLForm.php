<?php

/*
 * To change this template, choose Tools | Templates
 * and open the template in the editor.
 */

/**
 * Description of mysql_selectboxXMLForm
 *
 * @author cjuniorfox
 */
class mysql_selectboxXMLForm extends selectboxXMLForm {

    public function createField() {
        $this->content = $this->_sqlArrSelectbox();
        parent::createField();
    }

    public function str_value($value) {
        //permite executar primeiramente o algorítmo do selectboxXMLForm, pois este tem prioridade
        parent::str_value($value);
        $field_name = (string) $this->XMLField['name'];
        $table = (string) $this->XMLField->table;
        $val_columns = (string) $this->XMLField->val_collumns;
        $lbl_columns = (string) $this->XMLField->lbl_collumns;
        if (!isset($this->arrOut[$field_name])) {
            if (!$value && $this->XMLField->value)
                $value = (string) $this->XMLField->value;
            $Mysql = new mysql();
            $data = $Mysql->get_data($table, array($val_columns => $value));

            $this->arrOut[$field_name] = $data[$lbl_columns];
            $this->arrOut[parent::originalKey . $field_name] = $data[$lbl_columns];
        }
        /*  O valor pode não estar na tabela. Caso não esteja, vai para o
         * selectbox tradicional e busca o valor. Caso já esteja, o selectbox
         * irá imprimi-lo diretamente.
         *        
         */
        return $this->arrOut;
    }

    public function mysql_property() {
        $XMLField = $this->XMLField;
        $def_property = parent::default_mysql_property;
        $def_size = parent::default_mysql_size;
        $default = "$def_property($def_size)";
        $rel_table = (string) $XMLField->table;
        $rel_column = (string) $XMLField->val_collumns;
        /* Primeiro verifica se tabela existe. Caso não exista, retorna campo como padrão $padrão */

        $Mysql = new mysql();
        if (!$Mysql->table_exists($rel_table))
            return $default;
        /* Se tabela existe, agora testa coluna, caso não exista, retorna o mesmo padrão */
        if (!$Mysql->column_exists($rel_table, $rel_column))
            return $default;
        $column = $Mysql->get_columns($rel_table, $rel_column);
        return $column[0]['Type'];
    }

    private function _sqlArrSelectbox() {
        $objXML = $this->XMLField;
        $arrSBox = array();
        $table = (string) $objXML->table;
        $order_column = (string) $objXML->order_by->column;
        $order_desasc = (string) $objXML->order_by->order;
        $mysql = new mysql();
        $sqlData = $mysql->simple_search($table, null, 'or', '=', null, null, $order_column, $order_desasc);
        $valCollumns = (string) $objXML->val_collumns;
        $lblCollumns = (string) $objXML->lbl_collumns;
        while ($data = mysql_fetch_array($sqlData)) {
            $value = $data[$valCollumns];
            $label = $data[$lblCollumns];
            $arrSBox[] = array(
                'value' => $value,
                'label' => $label
            );
        }
        return $arrSBox;
    }

}

?>
