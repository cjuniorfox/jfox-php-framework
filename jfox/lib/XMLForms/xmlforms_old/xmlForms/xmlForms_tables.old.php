<?php

/**
 * Gerencia tabelas no banco de dados criando tabelas e editando campos
 * baseando-se em instruções passadas pelo arquivo XML.
 * 
 * A PRIMARY KEY=ID É CRIADA COMO PADRÃO, FUTURAMENTE ESTE SERÁ MUDADO PARA UM
 * CRIAVEL PELO PRÓPRIO USUÁRIO.
 *
 * @author juniorfox
 */
class xmlForms_tables {

    private $_xmlForm;
    private $_Mysql;
    private $_table_columns = array(); /* array com nome das colunas ja existentes no array */

    /**
     * Registra xml do formulário em memória para trabalhar com a mesma
     */
    public function __construct($xmlForm) {
        $this->_xmlForm = $xmlForm;
        $this->_Mysql = new mysql();
        //$this->_Mysql->debug = true;
    }

    /**
     * Gerencia tabela e seus campos baseando-se em informações passadas via XML.
     */
    public function table_from_xml() {
        $table = (string) $this->_xmlForm->table;
        $pk = (string) $this->_xmlForm->primary_key;
        $this->_table($table, $pk);
        for ($i = 0; $i < count($this->_xmlForm); $i++) {
            if ($this->_xmlForm->field[$i]->post == "true")
                $this->_collumns($table, $this->_xmlForm->field[$i]);
        }
    }

    /**
     * Cria tabela caso a mesma não exista
     * @param string $table_name - Nome da tabela a ser criada.
     * @param string $pk - Nome da chave Primary Key, caso nada seja setado, define o nome padrão ID
     * 
     * OBS: Por questões de relacionamento, a primary key é criada e não mudará de nome caso mude seu
     * nome no XML. esta mudança acontece apenas se excluir a tabela e cria-la novamente.
     */
    private function _table($table_name, $pk = "") {
        if (!$pk)
            $pk = "ID";
        $query = "CREATE TABLE IF NOT EXISTS `$table_name` (`$pk` int(11) NOT NULL AUTO_INCREMENT, PRIMARY KEY (`$pk`));";
        $this->_Mysql->execute_query($query);
    }

    /**
     * Verifica se deve-se criar um atualizar o campo desejado.
     * @param SimpleXMLElement $xmlField - XML do campo a ser inserido desejado
     * @param string $field_name - Caso queira criar seu proprio nome de campo, de algum valor a este
     * @param string $type - Ignora o tipo passado por XML e define manualmente de que tipo o campo é
     */
    private function _collumns($table, $xmlField, $field_name = '', $type = '') {
        if (!$field_name)
            if($xmlField->rel_column)
                $field_name = (string) $xmlField->rel_column;
            else
                $field_name = (string) $xmlField['name'];
        /* Na maioria das vezes, type está em branco. Apenas em caso type seja definido manualmente, este é passado */
        $column_properties = $this->_get_field_properties($xmlField, $type);
        $table_columns = $this->_table_columns();
        if (isset($table_columns[$field_name])) {
            /* Se propriedades da coluna no banco de dados forem diferentes das do XML, altera a mesma */
            if ($table_columns[$field_name] != $column_properties) {
                $this->_Mysql->alter_table($table, $field_name, "MODIFY", $column_properties, $this->_default_value($xmlField));
            }
        }else
        /* Se coluna de XML não existe em banco de dados, cria-se a mesma */
            $this->_Mysql->alter_table($table, $field_name, "ADD", $column_properties, $this->_default_value($xmlField));
    }

    /**
     * Busca valor padrão no XML, e define este como valor padrão a ser registrado no BD
     * @param $xmlField - XML do campo a ser trabalhado
     */
    private function _default_value($xmlField) {
        $value = "NULL"; /* Atribui valor padrão NULL, caso nada exista */
        if ($xmlField->value > '' && strpos($xmlField->value, '$') === false) {
            $value = (string) "'$xmlField->value'";
        }
        return $value;
    }

    /**
     * Retorna array com nome das colunas da tabela a ser trabalhada
     */
    private function _table_columns() {
        if (!$this->_table_columns) {
            $list = $this->_Mysql->get_columns($this->_xmlForm->table);
            foreach ($list as $data) {
                $table_columns[$data['Field']] = $data['Type'];
            }
            $this->_table_columns = $table_columns;
        }
        return $this->_table_columns;
    }

    private function _get_field_properties($xmlField, $type = '') {
        if (!$type)
            $type = $xmlField->field_type;
        /* Faz o teste de cada tipo e retorna as propriedades do campo de acordo que este necessita ter */
        if ($type == 'input' || $type == 'password' || $type == 'inputDatePicker') {
            return $this->_get_input_type($xmlField);
        } elseif ($type == 'jquery_fileupload') {
            return "VARCHAR(128)";
        } elseif ($type == 'textarea') {
            return "longtext";
        } elseif ($type == 'selectbox') {
            return $this->_get_selectbox_type($xmlField);
        } elseif ($type == 'mysql_selectbox') {
            return $this->_get_mysql_selectbox_type($xmlField);
        } elseif ($type == 'input_autoComplete') {
            return $this->_get_mysql_selectbox_type($xmlField);
        }
    }

    /**
     * Busca no elemento field do xml seu tamanho.
     * Caso não encontrar tamanho, retorna valor padrão passado por default.
     * @param $xmlField - Elemento XML do field
     * @param $default - Valor padrão, caso nenhum valor tenha sido passado
     * @return int - Valor do field.
     */
    private function _get_element_size($xmlField, $default = 0) {
        if (isset($xmlField['maxlength']))
            return $xmlField['maxlength'];
        elseif (isset($xmlField['size']))
            return $xmlField['size'];
        else
            return $default;
    }

    /**
     * Recebe elemento XML do tipo input ou inputDatePicker, analisa suas propriedades
     * e retorna a propriedade do campo desejado para este elemento.
     * 
     * @param SimpleXMLElement $xmlField - Elemento XML do input ou inputDatePicker
     * @return string - Type da coluna no banco de dados
     */
    private function _get_input_type($xmlField) {
        $size = $this->_get_element_size($xmlField, 20);
        if ($xmlField->format_date->enabled == 'true') /* Formato de data */
            return 'date';
        elseif ($xmlField->format_time->enabled == 'true') /* Formato de hora */
            return 'time';
        elseif ($xmlField->format_real->enabled == 'true') { /* Numero real decimal */
            if ($xmlField->format_real->subtype == 'monetary') /* Se financeiro */
                return 'decimal(10,2)';
            else /* Se for real, porém sem ser financeiro */
                return 'decimal()';
        }
        else /* Quaisquer outro tipo */
            return "varchar($size)";
    }

    private function _get_selectbox_type($xmlField) {
        $set_value = array();
        for ($i = 0; $i < count($xmlField->item); $i++) {
            $set_value[] = "'" . $xmlField->item[$i]->value . "'";
        }
        return "SET(" . implode(",", $set_value) . ")";
    }

    private function _get_mysql_selectbox_type($xmlField) {
        $rel_table = (string) $xmlField->table;
        $rel_column = (string) $xmlField->val_collumns;
        if($xmlField->lbl_collumns)
            $rel_lbl_column = (string) $xmlField->lbl_collumns;
        else
            $rel_lbl_column = (string) $xmlField['name'];

        /* Verifica se tabela e colunas relacionadas existem. Caso existam, tenta criar as mesmas */
        $this->_table($rel_table, $rel_column);
        /* Verifica a coluna de label. Caso ela não exista a cria. Caso exista, não altera seu valor */
        if (!$this->_Mysql->column_exists($rel_table, $rel_lbl_column)) {
            echo $rel_lbl_column;
            $this->_collumns($rel_table, $xmlField, $rel_lbl_column, 'input');
        }
        /* Verifica agora se a coluna de relacionamento existe. Caso não exista, retorna um erro */
        if (!$this->_Mysql->column_exists($rel_table, $rel_column))
            die("Erro: <b>xmlForms_tables</b> a referencia <b>$rel_column</b> em <b>$rel_table</b> n&atilde;o existe.<br />");
        $column = $this->_Mysql->get_columns($rel_table, $rel_column);
        return $column[0]['Type'];
    }

}

?>
