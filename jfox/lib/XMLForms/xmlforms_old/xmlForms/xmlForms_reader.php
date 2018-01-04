<?php

/**
 * Responsável por carregar registros relacionados ao XmlForms
 *
 * @author juniorfox
 * 
 * @version 1.2
 */
class xmlForms_reader extends xmlForms_convert_sql_str {
    
    public $version = 1.2;

    private $__makeList_search = array(); /* lista de buscas criadas para retornar resultados */
    private $_xml;
    private $_Mysql; /* Objeto Mysql usado na aplicação */

    /**Quando true, não converte data do BD para STR formatada. Retorna no formato do BD*/
    public $disableDataStr = false;

    public function __construct($xml) {
        $this->_xml = $xml;
        $this->_Mysql = new mysql();
    }

    /**
     * Retorna registro tratado SQL aos moldes de um $_POST
     * 
     * @param SimpleXMLElement $xmlForm | Formulário a ser lido os dados
     * @param INT $id_reg | ID do registro a ser lido
     * @param string 'print' $data_type |  retorna dados em formato de impressao
     * @return array Dados retornados da tabela
     */
    public function readFromTable($xmlForm, $id_reg,$pk) {
        $mysql_data = array();
        $table = $this->_load_table($xmlForm);
        $objMysql = new mysql();
        /* Busca Primary key da tabela, mesmo se setada view no XML */
        $arrCommands['fields'] = $this->_mysql_fields($xmlForm,$pk);
        /* Busca dados */
        $mysql_data = $objMysql->get_data($table, array($pk => $id_reg),$arrCommands);
        return $this->_process_form_values($xmlForm, $mysql_data);
    }

    /**
     * Retorna formulário em formato de lista para campos livres.
     * Similar ao getForms, porem sem criar necessariamente os campos, retornando
     * apenas os valores.
     * 
     * @return array|Valores de formulario
     * @param SimpleXmlElement - Formulario XML
     * @param id de primary_key do registo $id_reg
     */
    public function report($xmlForm, $id_reg) {
        $cdata = array();
        $table = $this->_load_table($xmlForm);
        $objMysql = new mysql();
        /* Busca Primary key da tabela, mesmo se setada view no XML */
        $pri_key_field = $objMysql->get_primary_key((string) $xmlForm->table);
        /* Busca dados */
        $mysql_data = $objMysql->get_data($table, array($pri_key_field => $id_reg));
        $data = $this->convertSqlDataToStr($xmlForm, $mysql_data, (string) $this->_xml->language);
        /* Cria relatorio em forma de lista baseado no label e valor do arquivo XML */
        foreach ($xmlForm->field as $field) {
            if ($field->post == "true") {
                $item['type'] = (string) $field->field_type;
                $item['label'] = (string) $field->label;
                $item['value'] = $data[(string) $field['name']];
                if ($field['maxlength']): $item['size'] = (string) $field['maxlength'];
                else : $item['size'] = strlen($item['value']);
                endif;
                $list[] = $item;
            }
        }
        $cdata['list'] = $list;
        $cdata['data'] = $data;
        return $cdata;
    }

    /**
     * Cria array com lista de dados de form desejado.
     * 
     * @return boolean false| Formulário não encontrado.
     * @return array| Matriz com lista de valores retornados.
     * @param SimpleXmlElement - Formulario XML
     * @param int| Para paginação, primeiro resultado $first_reg
     * @param int| Para paginação, numero de resultados por pagina $num_of_regs
     * @param string | Nome do campo Primary Key do resultado
     */
    public function makeList($xmlForm, $first_reg, $num_of_regs,$pk) {
        $matrix_list = array();
        $formName = (string) $xmlForm['name'];
        if (!isset($this->__makeList_search[$formName]['arrSearch']))
            $this->__makeList_search[$formName]['arrSearch'] = NULL;
        if (!isset($this->__makeList_search[$formName]['arrCommands'])) {
            $this->__makeList_search[$formName]['arrCommands'] = NULL;
        }
        /* Busca resultados baseado nos comandos de paginacao e $arrSearch e $arrCommands definidos no xmlForms::listForm_filter */
        $arrSearch = $this->__makeList_search[$formName]['arrSearch'];
        $arrCommands = $this->__makeList_search[$formName]['arrCommands'];
        $arrCommands['limits_start'] = $first_reg;
        $arrCommands['total_results'] = $num_of_regs;
        $arrCommands['fields'] = $this->_mysql_fields($xmlForm,$pk);
        $objMysql = new mysql();
        $table = $this->_load_table($xmlForm);
        $result = $objMysql->search($table, $arrSearch, $arrCommands);
        /* Alimenta lista com valores da tabela os formatando conforme definido em XML */
        while ($line = mysql_fetch_assoc($result)) {
            $matrix_list[] = $this->convertSqlDataToStr($xmlForm, $line, (string) $this->_xml->language, $pk);
        }
        return $matrix_list;
    }

    /**
     * Define, se necessario, filtros para serem usados em xmlForms::listForm
     * 
     * @return int|Linhas -Total de resultados que serão obtidos no xmlForms::listForm
     * @return boolean|false -Formulário não encontrado.
     * @param SimpleXmlElement - Formulario XML
     * @param array| Campos para pesquisa no formato mysql::arrSearch();
     */
    public function makeList_filter($xmlForm, $arrSearch = array(), $arrCommands = array()) {
        $formName = (string) $xmlForm['name'];
        if (!isset($this->__makeList_search[$formName])) {
            $this->__makeList_search[$formName]['arrSearch'] = array();
            $this->__makeList_search[$formName]['arrCommands'] = array();
        }
        if ($arrSearch)
            $this->__makeList_search[$formName]['arrSearch'] = $arrSearch;
        if ($arrCommands)
            $this->__makeList_search[$formName]['arrCommands'] = $arrCommands;
        /* Sobrepoe valores locais pelos valores armazenados. */
        $arrSearch = $this->__makeList_search[$formName]['arrSearch'];
        $arrCommands = $this->__makeList_search[$formName]['arrCommands'];
        /* Busca total de resultados */
        $arrCommands['fields'] = 'COUNT(*) AS total';
        $table = $this->_load_table($xmlForm);
        $objMysql = new mysql();
        $data = $objMysql->get_data($table, $arrSearch, $arrCommands);
        return $data['total'];
    }

    /**
     * Remove filtros de pesquisa para xmlForms::listForm
     * 
     * @param string| Nome do form a ser usado $formName
     */
    public function makeList_removeSearchFilter($formName) {
        if ($formName) {
            $this->__makeList_search[$formName]['arrSearch'] = array();
        }
    }

    /**
     * Remove comandos para xmlForms::listForm
     * 
     * @param string| Nome do form a ser usado $formName
     */
    public function makeList_removeSearchCommands($formName) {
        if ($formName) {
            $this->__makeList_search[$formName]['arrCommands'] = array();
        }
    }

    private function _load_table($xmlForm) {
        $tb = (string) $xmlForm->table;
        if ($xmlForm->table_view) {
            $tb = (string) $xmlForm->table_view;
        }
        return $tb;
    }

    /**
     * Retorna registro tratado SQL aos moldes de um $_POST
     * 
     * @param SimpleXMLElement $xmlForm | Formulário a ser lido os dados
     * @param array $mysql_data | Dados retornados do Mysql
     * @param string 'print' $data_type |  retorna dados em formato de impressao
     * @param string 'post' $data_type | retorna dados no formato que foram postados
     * @param string 'mysql' $data_type | retorna dados no formato de tabela
     * @return array Dados retornados da tabela
     */
    private function _process_form_values($xmlForm, $mysql_data) {
        $ArrStrData = array();
        /* Se data_type for mysql, retorna dados sem trata-los */
        for ($i = 0; $i < count($xmlForm->field); $i++) {
            /* Se existir campo de relacionamento, busca valor relacionado */
            $value = $this->_get_rel_value($xmlForm->field[$i], $mysql_data);
            /* Value ser valores agora significa valor relacinado. Caso tenha
             * valores, adiciona id do relacionamento ao resultado
             */
            if ($value) {
                $rel_column = (string) $xmlForm->field[$i]->relate->rel_key;
                if (isset($mysql_data[$rel_column]) && $rel_column) {
                    $ArrStrData[$rel_column] = $mysql_data[$rel_column];
                }
            }
            $fieldName = (string) $xmlForm->field[$i]['name'];
            /* Se se existir valor inserido diretamente na tabela e valor nao foi setado, retorna este */
            if (isset($mysql_data[$fieldName]) && !$value) {
                $value = $mysql_data[$fieldName];
            }

            /* Se algum valor foi agregado, trata valor e adiciona o mesmo ao ArrStrData[$fieldName] */
            if ($value)
                $ArrStrData[$fieldName] = $this->convertDataStr(true, $value, $xmlForm->field[$i], (string) $this->_xml->language);
        }
        return $ArrStrData;
    }

    /**
     * Localiza e processa valores relacionados em outras tabelas usando
     * apontadores para relacionar valores
     * @param SimpleXMLElement $xmlField - XML do campo a ser tratado
     * @param array $arrData fetch_array da pesquisa mysql a ser relacionda com o formulario
     */
    private function _get_rel_value($xmlField, $mysql_data) {
        /* Se existir campo de relacionamento, abre tabela relacionada e retorna campo do relacionamento */
        if ($xmlField->relate->primary_key) {
            $rel_column = (string) $xmlField->relate->rel_key;
            $val_collumns = (string) $xmlField->relate->primary_key;
            $lbl_column = (string) $xmlField['name'];
            $table = (string) $xmlField->relate->table;
            if ($mysql_data[$rel_column] && $table) {
                $relData = $this->_Mysql->get_data($table, array($val_collumns => $mysql_data[$rel_column]));
                if (isset($relData[$lbl_column]))
                    return $relData[$lbl_column];
            }
        }
    }
    
    /**
     * Cria lista de campos que irão fazer parte de mysql select
     * @param SimpleXMLElement $xmlForms - Formulário XML
     * @return array - Campos (fields) do SELECT.
     */
    private function _mysql_fields($xmlForm,$pk){
        $fields = array($pk);
        foreach($xmlForm->field as $XMLfield){
            if($XMLfield->post == 'true'){
                //Descobre o nome dentro de uma das situações abaixo.
                if($XMLfield['name'])
                    $fieldName = $XMLfield['name'];
                if($XMLfield->relate->rel_key)
                    $fieldName = $XMLfield->relate->rel_key;
                if($XMLfield->mysql_encode->enabled == 'true' && $XMLfield->mysql_encode->crypt_field)
                    $fieldName = "DECODE($fieldName,".$XMLfield->mysql_encode->crypt_field.") AS $fieldName";
            }
            if($fieldName)
                $fields[] = $fieldName;
            $fieldName = NULL;
        }
       return $fields;
    }

}

?>
