<?php

/*
 * To change this template, choose Tools | Templates
 * and open the template in the editor.
 */

/**
 * Description of XMLForms_tables
 *
 * @author cjuniorfox
 */
class XMLForms_tables extends XMLForms {

    /**
     * Cria tabelas a partir de valores passados no xmlForm
     */
    public function create_table() {
        if (strtolower($this->XMLForm->create_table) != 'true') {
            return null;
        }
        $table = (string) $this->XMLForm->table;
        $pk = (string) $this->primary_key();
        $this->_table($table, $pk);

        foreach ($this->XMLForm->field as $XMLField) {
            if ($XMLField->post == "true")
                $this->_column($table, $XMLField);
        }
    }
    
    /**
     * Método estático, pode ser chamado como XMLForms->tables::create_table_from_xml
     * 
     * @param string $xmlFile - Path do arquivo XML a ser usado
     * @param string $form_name - Nome do formulário.
     */
    public static function create_table_from_xml($xmlFile, $form_name) {
        $OXMLT = new XMLForms_tables($xmlFile, $form_name);
        $OXMLT->create_table();
    }

    private function _table($table_name, $pk = "") {
        $Mysql = $this->Mysql();
        if (!$pk)
            $pk = parent::default_primary_key;
        $query = "CREATE TABLE IF NOT EXISTS `$table_name` (`$pk` int(11) NOT NULL AUTO_INCREMENT, PRIMARY KEY (`$pk`))DEFAULT CHARSET=utf8;\n";
        $Mysql->execute_query($query);
    }

    /**
     * Verifica se deve-se criar um atualizar o campo desejado.
     * @param SimpleXMLElement $xmlField - XML do campo a ser inserido desejado
     */
    private function _column($table, $XMLField) {
        $field_classname = $this->field_classname($XMLField);
        $Mysql = $this->Mysql();
        if (class_exists($field_classname)) {
            
            $Field = new $field_classname($XMLField, $this->language);
            $field_name = $Field->field_name_or_rek_key();
            if ($XMLField->relate->rel_key && $XMLField->relate->table)
                $this->_table_from_rel_field($XMLField);
            $table_columns = $this->_table_columns($table);
            $mysql_property = $Field->mysql_property();

            if (isset($table_columns[$field_name])) {
                /* Se propriedades da coluna no banco de dados forem diferentes das do XML, altera a mesma */
                if (strtoupper($table_columns[$field_name]) != strtoupper($mysql_property)) {
                    $Mysql->alter_table($table, $field_name, "MODIFY", $mysql_property, $this->_default_value($XMLField));
                }
            } else {
                /* Se coluna de XML não existe em banco de dados, cria-se a mesma */
                $Mysql->alter_table($table, $field_name, "ADD", $mysql_property, $this->_default_value($XMLField));
            }
        }
    }

    private function _table_from_rel_field($xmlField) {
        /* Se tabela relacionada for somente leitura, escapa sem fazer nada */
        if ($xmlField->relate->readonly == 'true')
            return null;
        /* Isola xml do campo do resto do XML */
        $xml = $xmlField->asXML();
        $newXmlField = simplexml_load_string($xml);


        $table = (string) $newXmlField->relate->table;
        $pk = (string) $newXmlField->relate->primary_key;
        /* Primeiro cria tabela */
        $this->_table($table, $pk);

        /* Edita caracteristicas do campo e roda collumns para campo em tabela relacionada */
        unset($newXmlField->relate);
        $newXmlField->field_type = $xmlField->field_type;
        $this->_column($table, $newXmlField);
        /* Destroi elemento para limpar memória */
        unset($newXmlField);
    }

    /**
     * Retorna array com nome das colunas da tabela a ser trabalhada
     */
    private function _table_columns($table) {
        $Mysql = $this->Mysql();
        $table_columns = array();
        $list = $Mysql->get_columns($table);
        foreach ($list as $data) {
            $table_columns[$data['Field']] = $data['Type'];
        }
        return $table_columns;
    }
    
    /**
     * Busca valor padrão no XML, e define este como valor padrão a ser registrado no BD
     * @param $xmlField - XML do campo a ser trabalhado
     */
    private function _default_value($XMLField) {
        $value = "NULL"; /* Atribui valor padrão NULL, caso nada exista */
        if ($XMLField->value > '' && strpos($XMLField->value, '$') === false) {
            $value = (string) "'$XMLField->value'";
        }
        return $value;
    }

}

?>
