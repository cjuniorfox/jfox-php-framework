<?php

/*
 * Tudo gira em torno do id da postagem
 * 
 * -Se ID ainda não foi definido:
 *      -Carrega tabela, campo e valor no xmlField
 *      -Busca por elemento
 *      -Se existir, retorna ID valor do mesmo
 *      -Se não existir, insere valor e retorna ID do valor inserido.
 * 
 * -Se ID já foi definido:
 *      -Carrega tabela, campo e valor do xmlField
 *      -Carrega campo a partir do ID definido
 *      -Atualiza a coluna com o valor informado em value
 *      -Retorna ID ja pre-carregado
 * 
 * ATENCAO, CASO A PRIMARY KEY NAO ESTEJA DEFINIDA, DEFINE A PK COMO A PRIMEIRA COLUNA
 */

/**
 * Description of input_autoComplete
 *
 * @author juniorfox
 */
class input_autoComplete extends xmlForms_convert_sql_str {

    public $ID = 0; /* ID do elemento a ser criado ou postado */
    private $_Mysql; /* Objeto MySQL */

    public function __construct() {
        $this->_Mysql = new mysql();
    }

    /**
     * Retorna Array de resultados para gerar JSON utilizado pelo objeto xmlForms.input_autoComplete.
     * @param SimpleXMLElement $xmlField - Elemento XML do campo.
     * @param string $term - Valor buscado.
     */
    public function search_itens_list($xmlField, $term, $format) {
        $data = array(); /* Este tera o resultado da listagem de itens */
        $table = (string) $xmlField->relate->table;
        $field = (string) $xmlField['name'];
        $maxLen= (int)    $xmlField->maxlength;
        $pk = $this->_get_pk($xmlField);
        $search = stringParaBusca($term);
        if (!$table || !$field) { /* Se algum destes elementos não for listado, retorna nulo */
            return $data;
        }
        $arrCommands = array('logic_operator' => 'REGEXP');
        if($maxLen)
            $arrCommands['total_results'] = $maxLen;
        
        $res = $this->_Mysql->search($table, array($field => $search), $arrCommands);
        while ($line = mysql_fetch_array($res)) {
            $data[] = array(
                'id' => $line[$pk],
                'label' => $this->convertDataStr(true, $line[$field], $xmlField, $format),
                'value' => $this->convertDataStr(true, $line[$field], $xmlField, $format)
            );
        }
        return $data;
    }

    /**
     * O objetivo é retornar um JSON com dados para auto-preencher campos que estejam
     * relacionados com um certo ID no formulário.
     * @param SimpleXMLElemet $xmlForm - Form a ser rastreado
     * @param SimpleXMLElemet $xmlField - Field a ser usado como referencia
     * @param string $rel_id_value - Chave estrangeira aonde será buscados os dados da tabela.
     * @param string $format - Lingua na qual devera ser formatado o resultado
     * 
     */
    public function array_AC_form($xmlField, $xmlForm, $rel_id_value, $format) {
        $data = array(); /* Este tera o resultado da listagem de itens */
        $table = (string) $xmlField->relate->table;
        $field = (string) $xmlField['name'];
        $column = (string) $xmlField->relate->primary_key;
        $rel_key = (string) $xmlField->relate->rel_key;
        if (!$table || !$field) { /* Se algum destes elementos não for listado, retorna nulo */
            return $data;
        }
       
        $form_data = $this->_Mysql->get_data($table, array($column => $rel_id_value));
        /* Dados capturados? Agora varre $XmlForm e busca por campos relacionados 
         * ao mesmo rel_id_column e a mesma tabela.
         */
        foreach($xmlForm->field as $XmlItem){
            if($XmlItem->relate->rel_key == $rel_key){
                $fieldItem = (string) $XmlItem['name'];
                $data[$fieldItem] = $this->convertDataStr(true, $form_data[$fieldItem], $XmlItem, $format);
            }
        }
            
        
        return $data;
    }
    
    /**
     * Tenta buscar a primary key da tabela
     */
    private function _get_pk($xmlField){
        $table = (string) $xmlField->relate->table;
        if($xmlField->relate->rel_key)
            $pk = (string) $XmlItem->relate->rel_key;
        else
            $pk = $this->_Mysql->get_primary_key($table);
        /*Não há PK? define então a primeira coluna da view como a primary key*/
        if(!$pk){
            $columns = $this->_Mysql->get_columns_list($table);
            $pk = $columns[0];
        }
        return $pk;
    }


}

?>