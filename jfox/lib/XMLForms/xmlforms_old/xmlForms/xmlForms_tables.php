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

    /*
     * Nome do campo Primary key padrão quando o mesmo não for setado pelo XML 
     */
    public $def_primary_key = "ID";
    
    /**
     * Tipo padrão de campo (quando o mesmo precisa ser inserido e o mesmo não é
     * definido.
     */
    public $default_input_type = "VARCHAR(128)";

    /**
     * Registra xml do formulário em memória para trabalhar com a mesma
     */
    public function __construct($xmlForm) {
        $this->_xmlForm = $xmlForm;
        $this->_Mysql = new mysql();
    }

    /**
     * Cria tabelas a partir de valores passados no xmlForm
     */
    public function table_from_xml() {
        if(strtolower($this->_xmlForm->create_table) != 'true')
            return null;
        $table = (string) $this->_xmlForm->table;
        $pk = (string) $this->_xmlForm->primary_key;
        $this->_table($table, $pk);
        for ($i = 0; $i < count($this->_xmlForm); $i++) {
            if ($this->_xmlForm->field[$i]->post == "true")
                $this->_columns($table, $this->_xmlForm->field[$i]);
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
            $pk = $this->def_primary_key;
        $query = "CREATE TABLE IF NOT EXISTS `$table_name` (`$pk` int(11) NOT NULL AUTO_INCREMENT, PRIMARY KEY (`$pk`))DEFAULT CHARSET=utf8;\n";
        $this->_Mysql->execute_query($query);

    }

    /**
     * Verifica se deve-se criar um atualizar o campo desejado.
     * @param SimpleXMLElement $xmlField - XML do campo a ser inserido desejado
     */
    private function _columns($table, $xmlField) {
        $field_name = (string) $xmlField['name'];
        /* Se for campo relacionado, trata tabelas de coluna relacionada */
        if ($xmlField->relate->rel_key) {
            $field_name = (string) $xmlField->relate->rel_key;
            $this->_table_from_rel_field($xmlField);
        }
        $table_columns = $this->_table_columns($table);
        $column_properties = $this->_get_field_properties($xmlField);
        
        if (isset($table_columns[$field_name])) {
            /* Se propriedades da coluna no banco de dados forem diferentes das do XML, altera a mesma */
            if ($table_columns[$field_name] != $column_properties) {
                $this->_Mysql->alter_table($table, $field_name, "MODIFY", $column_properties, $this->_default_value($xmlField));
            }
        } else {
            /* Se coluna de XML não existe em banco de dados, cria-se a mesma */
            $this->_Mysql->alter_table($table, $field_name, "ADD", $column_properties, $this->_default_value($xmlField));
        }
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
    private function _table_columns($table) {
        $table_columns = array();
        $list = $this->_Mysql->get_columns($table);
        foreach ($list as $data) {
            $table_columns[$data['Field']] = $data['Type'];
        }
        return $table_columns;
    }

    private function _get_field_properties($xmlField) {
        /* Se for field de relacionamento, retorna ID do chave primaria do campo relacionado.
         * Caso este não exista, retorna valor padrão (INT)
         */
        if ($xmlField->relate->primary_key && $xmlField->relate->table) {
            $table_columns = $this->_table_columns((string) $xmlField->relate->table);
            if ($table_columns[(string) $xmlField->relate->primary_key])
                return $table_columns[(string) $xmlField->relate->primary_key];
            else
                return "INT()";
        }
        /* Não é relacionado, segue processamento padrão. */
        $type = (string) $xmlField->field_type;
        /* Faz o teste de cada tipo e retorna as propriedades do campo de acordo que este necessita ter */
        if ($type == 'input' || $type == 'password' || $type == 'inputDatePicker' || $type == "input_autoComplete") {
            return $this->_get_input_type($xmlField);
        } elseif ($type == 'jquery_fileupload') {
            return $this->default_input_type;
        } elseif ($type == 'textarea') {
            return "longtext";
        } elseif ($type == 'selectbox') {
            return $this->_get_selectbox_type($xmlField);
        } elseif ($type == 'mysql_selectbox') {
            return $this->_get_mysql_selectbox_type($xmlField);
        }
        /*Nenhum tipo foi retornado? Que pena, retorna então o tipo ja registrado na tabela ou tipo padrão*/
        $field_name = (string) $xmlField['name'];
        
        $table_columns = $this->_table_columns($this->_xmlForm->table);
        $input_type = $this->default_input_type;
        if(isset($table_columns[$field_name]))
            $input_type = $table_columns[$field_name];
        return $input_type;
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
        }elseif ($xmlField->format_integer->enabled == 'true') /* Numero inteiro */
            return "int";
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
        $padrao = $this->default_input_type;
        $rel_table = (string) $xmlField->table;
        $rel_column = (string) $xmlField->val_collumns;
        /* Primeiro verifica se tabela existe. Caso não exista, retorna campo como padrão $padrão */
        if (!$this->_Mysql->table_exists($rel_table))
            return $padrao;
        /* Se tabela existe, agora testa coluna, caso não exista, retorna o mesmo padrão */
        if (!$this->_Mysql->column_exists($rel_table, $rel_column))
            return $padrao;
        $column = $this->_Mysql->get_columns($rel_table, $rel_column);
        return $column[0]['Type'];
    }

    private function _table_from_rel_field($xmlField) {
        /*Se tabela relacionada for somente leitura, escapa sem fazer nada*/
        if($xmlField->relate->readonly ==  'true')
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

        $this->_columns($table, $newXmlField);
        /* Destroi elemento para limpar memória */
        unset($newXmlField);
    }

}

?>