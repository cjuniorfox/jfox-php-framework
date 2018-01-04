<?php

/**
 * Trabalho relativo a relacionamento de campos em xmlForms
 *
 * @author juniorfox
 */
class xmlForms_related {
    
    private $_Mysql;
    
    public function __construct() {
        $this->_Mysql = new mysql();
    }
    
    /**
     * Trabalha com valores relativos a relacionamento de dados.
     * Verifica duas coisas
     * 1- Se campo realmente se trata de um relacionamento
     *  -Se não existir retorna falso
     * 2- Se o valor relacionado foi postado em $this->postData
     *  -Se sim, retorna em $f[field] $xmlField->rel_column e em value, o valor postado
     *  -Se não, busca por valor relacionado e retorna aos moldes informados acima
     * @param string $value - Valor postado a ser analisado e tratado
     * @param SimpleXMLElement $xmlField - XML do campo a ser tratado
     * @param array $postData - Valores que foram postados.
     * @return array - Valores a serem registrados na tabela
     * @return null - Caso não seja um relacionamento com alerta em tela
     */
    public function process_value($value, $xmlField,$postData = array()) {
        if (!$xmlField->relate)
            return null;
        $rel_column = (string) $xmlField->relate->rel_key;
        /* Verifica e insere, ou não valor em tabela relacionada */
        if($xmlField->relate->readonly != "true") //Só insere se o relacionado não for readonly
            $this->_table_insert($value, $xmlField, $postData);
        /* Caso rel_column ja tenha sido postado */
        if ($postData[$rel_column])
            return array(
                'fieldName' => $rel_column,
                'value' => $postData[$rel_column]
            );
        /* Se não tenha sido, busca valor na tabela relacionada e retorna o mesmo */
        return array(
            'fieldName' => $rel_column,
            /*Adiciona de forma compulsoria o valor em postData, para a busca seguinte*/
            'value' => $this->_rel_value($value, $xmlField)
        );
    }

    /**
     * Busca valor em tabela relacionada, caso o mesmo não exista, insere seu valor
     * @param string $value - Valor do label a ser processado
     * @param SimpleXMLElement $xmlField - XML do campo tratado
     * @param array $postData - Valores que foram postados.
     */
    private function _table_insert($value, $xmlField, $postData = array()) {
        $field_lbl =  (string) $xmlField['name'];
        $field_id = (string) $xmlField->relate->primary_key;
        $rel_column = (string) $xmlField->relate->rel_key;
        $table = (string) $xmlField->relate->table;
        /* Caso tabela ou nome da coluna label não existam, faz nada */
        if (!$field_lbl || !$table || !$value)
            return null;
        $arrSearch = array($field_lbl => $value);
        /* Se $this->postData[$rel_column] existir, pode ser uma atualização de
         * registro. Se for, atualiza o mesmo
         */
        if($postData[$rel_column]){
            $whereArr = array($field_id => $postData[$rel_column]);
            if($this->_Mysql->search($table, $whereArr))
                    $this->_Mysql->update ($table, $arrSearch, $whereArr);
        }
        if($this->_Mysql->search($table,array($field_id => $postData[$rel_column])))
        if (!$this->_Mysql->get_data($table, $arrSearch)) {
            $this->_Mysql->insert($table, $arrSearch);
        }
    }

    /**
     * Busca em tabela relacionada o valor passado em $value e retorna o valor
     * de seu ID. Os nomes das colunas são passados pelo $xmlField;
     * @param string $value - Valor do label a ser processado
     * @param SimpleXMLElement $xmlField - XML do campo tratado
     */
    private function _rel_value($value, $xmlField) {
        $field_lbl = (string) $xmlField['name'];
        $field_id = (string) $xmlField->relate->primary_key;
        $table = (string) $xmlField->relate->table;
        if (!$field_id && $table) /* Caso não tenha sido setado no XML, busca na tabela */
            $field_id = $this->_Mysql->get_primary_key($table);
        /* Todos os valores são importantes, caso não existam, retorna nulo */
        if (!$field_lbl || !$table || !$field_id)
            return null;
        $data = $this->_Mysql->get_data($table, array($field_lbl => $value));
        return  $data[$field_id];
    }
}

?>