<?php

/**
 * XMLForms 3.0 Beta
 * 
 *
 * @author cjuniorfox
 */
class XMLForms {

    const lib_XMLForms = "XMLForms/";
    const default_primary_key = "ID";
    const default_field_classname = 'inputXMLForm';
    const fieldclass_extension = "XMLForm";

    /**
     * Objeto Mysql utilizaod pelo XMLFormsr
     * 
     */
    protected $OBJMysql;
    protected $form_name;

    /**
     * XML do formulário usado.
     */
    public $XMLForm;

    /**
     * Lingua usada na formatação de variáveis.
     */
    public $language = "pt-br";

    /**
     * Inicializa objeto XMLForm, 
     * define lingua padrão,
     * inicializa demais bibliotecas e XML do formulário a ser usado.
     */
    public function __construct($xmlFile, $form_name, $vData = array()) {
        $this->include_lib();
        $this->form_name = $form_name;
        if (!file_exists($xmlFile)) {
            trigger_error("<b>Erro:</b> Em <i><b>xmlForms</b></i>, o arquivo <b>$xmlFile</b> n&atilde;o foi encontrado", E_USER_ERROR);
        }
        $view = new view();
        $xmlData = $view->process_view($vData, $xmlFile);
        $XML = new SimpleXMLElement($xmlData);
        if ($XML->language)
            $this->language = (string) $XML->language;
        $this->_xmlForm($XML, $form_name);
    }

    /**
     * Descarrega diretórios de objetos do XMLForms.
     */
    public function __destruct() {
        $path = str_replace(DIRECTORY_SEPARATOR, '/', realpath(dirname(__FILE__))) . self::lib_XMLForms;
        remove_to_library($path);
    }

    /**
     * Adiciona bibliotecas do XMLForms as bibliotecas do framework
     */
    public function include_lib() {
        $path = str_replace(DIRECTORY_SEPARATOR, '/', realpath(dirname(__FILE__))) . "/" . self::lib_XMLForms;
        add_to_library($path);
    }

    /**
     * Procura pela $tag no XML e retorna sua string
     * @return string
     */
    public function tag($tag) {
        if ($this->XMLForm->$tag)
            return (string) $this->XMLForm->$tag;
    }

    /**
     * Descobre e retorna a primary_key da tabela.
     * Este pode ser harbitrariamente definido no XML
     * Se o mesmo não foi definido no XML e não há primary_key na
     * tabela ou não há tabela, retorna nulo
     * @return mixed - Chave primária solicitada
     * @return null - não há chave primária para retornar
     */
    public function primary_key() {
        if(!isset($this->XMLForm->table))
            return null;
        if ($this->XMLForm->primary_key) {
            return (string) $this->XMLForm->primary_key;
        } else {
            $Mysql = $this->Mysql();
            $table = (string) $this->XMLForm->table;
            if($Mysql->table_exists($table)){
                return $Mysql->get_primary_key($table);
            }
            
        }
    }

    /**
     * Cria lista de campos que irão fazer parte de mysql select
     * @param array $fields_to_list - Limita os campos a serem listados, se este for nulo, retorna todos os campos listaveis.
     * @return array - Campos (fields) do SELECT.
     */
    public function mysql_fields($fields_to_list = array()) {
        $XMLForm = $this->XMLForm;
        $Mysql = $this->Mysql();
        $table = $this->table();
        $mysql_columns_list = $Mysql->get_columns_list($table);
        $fields = array();
        $pk = $this->primary_key();
        if (!$fields_to_list)
            $fields[] = $pk;
        elseif (array_search($pk, $fields_to_list) !== FALSE)
            $fields[] = $pk;
        
        foreach ($XMLForm->field as $XMLField) {
            $field_to_insert = NULL;
            $field_classname = XMLForms_fields::field_classname($XMLField);
            $mysql_field = NULL;
            if(class_exists($field_classname)){
                $fieldClass = new $field_classname($XMLField);
                $mysql_field = $fieldClass->mysql_field_name();
                $mysql_select_item = $mysql_field; //$mysql_select_item será o adicionado ao array. o $mysql_field é apenas para verificação
            }
            if(array_search($mysql_field, $fields)!== FALSE){
                 $mysql_field = NULL;
                 $mysql_select_item = NULL;
            }
                   
            //Existe |, então explode a string com este e define [0] como o $mysql_field e [1] como $mysql_select_item 
            if (strpos($mysql_field, "|") !== FALSE) {
                $tmp = explode("|",$mysql_field);
                 $mysql_field = $tmp[0];
                 $mysql_select_item = $tmp[1];
            }
            
            
            if ($mysql_field && $fields_to_list) {
                if (array_search($mysql_field, $fields_to_list) !== FALSE)
                    $field_to_insert = $mysql_select_item;
                else {
                    
                }
            } elseif ($mysql_field && !$fields_to_list){
                $field_to_insert = $mysql_select_item;
            }
                
            //Depois de processar tudo, se o campo existe na tabela, caso exista, insere o mesmo
            if($field_to_insert && array_search($mysql_field,$mysql_columns_list)!== FALSE)
                $fields[] = $field_to_insert;
        }
        return $fields;
    }

    /**
     * Instancia, ou não, o objeto Mysql.
     */
    protected function Mysql() {
        if (!$this->OBJMysql)
            $this->OBJMysql = new mysql();
        //$this->OBJMysql->debug = true;
        return $this->OBJMysql;
    }

    /**
     * Retorna o nome da tabela a ser usada no mysql.
     * Olha o XML, se existir view_table, retorna esta.
     * Se não, retorna table.
     */
    public function table() {
        if ($this->XMLForm->table_view)
            return (string) $this->XMLForm->table_view;
        return (string) $this->XMLForm->table;
    }

    /**
     * Retorna a classe do Field XML
     */
    protected function field_classname($XMLField) {
        $field_type = (string) $XMLField->field_type;
        if ($field_type)
            return $field_type . self::fieldclass_extension;
        else//Se não existir field_classname, aplica o nome default para o mesmo
            return self::default_field_classname;
    }

    /**
     * Carrega a variavel global $XMLForm que é o XML do form a ser usado.
     */
    private function _xmlForm($XML, $formName) {
        foreach ($XML->form as $XMLForm)
            if ($XMLForm['name'] == $formName)
                return $this->XMLForm = $XMLForm;
    }

}

?>
