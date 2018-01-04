<?php

/**
 * @version 1.17
 * Esta classe trabalha com mysql
 * Depende da classe view.php
 *
 * -----------------------------------------------------------------------------
 * Changelog: 11.03.2013
 * -----------------------------------------------------------------------------
 * 1.17-Adicionado mysql_close() em __destruct();
 * 1.16-Corrigido bug do multi-query (várias querys a pular de linha).
 * 1.15-Adicionada capacidade de array de campos no order_by
 * 1.14-Implementado objeto auxiliar sql_parse para processar querys sql
 * 1.13-Implementada funcao alter table
 * 1.12-Implementado recurso de listar tabelas
 * 1.11-Implementada função delete.
 * 1.10-Implementado recurso para tratar como UTF-8, bancos de dados ISO8859-1
 *1.9.1-Correção do bug para separar comandos SQL a serem executados.
 * 1.9- Transformada em publico o metodo "create_set_cause()"
 *      Adicionada array quotes ao arrCommands, agora pode-se definir que caracter
 *      usar como aspas.
 * 1.8- Adicionado nokeys a array_command, se true, substitue o 
 *      mysql_fetch_array por mysql_fetch_assoc. (fetch array sem chaves de ID)
 * 1.7- Correção de Bugs.
 * 1.6- Buscas relacionadas 1 para N, e inseridas em chave de matriz_fetch_array
 * 1.5- Formatação de campos com o uso do objeto local_formats.
 * 1.4- Mudança da forma de inserir search e variaveis, agora este acontece por
 *      passagem de arrays e não de comandos.
 * 1.3- Uso da classe View, agora este executa arquivos .sql nos moldes do
 *      objeto View.
 * 1.2- Implementação de matriz automatica de resultados
 * 1.1- Implementação de comandos para passagem de querys
 * 1.0- Criação da ferramenta para o Framework Jfox
 * @author junior
 */
class mysql {
    
    const version = 1.17;
    
    private $_mysql_resource; /*Conexão Mysql em si*/
    
    private $_mysql_vars;
    private $_query; /* Exibe o ultimo comando sql executado */
    private $_result; /* Sempre que ha um resultado de pesquisa SQL, ele recebe o que foi retornado */
    private $_arrRelatedQuery; /* Array com querys de busca relacionada e nome do campo a ser relacionado */
    public $debug = false; /* Quando TRUE, ativa o modo debug, imprimindo código mysql para leitura */
    public $secure = true; /* Padrao: true. Quando TRUE, aplica tecnica de seguranca adicionando contra-barras em SQL injetados */
    public $utf8_encode = false; /*Necessario estar true para trabalhar com tabelas/banco de dados ISO-8859-1 em projetos UTF-8 */

    public function __construct($host = null, $login = null, $passwd = null, $db_name = null) {
        if (isset($GLOBALS['mysql_vars'])) {
            $this->_mysql_vars = $GLOBALS['mysql_vars'];
        }
        
        /*Valores passados no construct sobrepoem valores globais*/
        foreach(array('host','login','passwd','db_name') as $field){
            if($$field){
                $this->_mysql_vars[$field] = $field;
            }
        }
       

        foreach (array('host', 'login', 'passwd', 'db_name') as $field) {
            $this->_mysql_vars[$field] = $$field or $this->_mysql_vars[$field] = $GLOBALS['mysql_vars'][$field];
        }
        $this->_mysql_resource = mysql_connect($this->_mysql_vars['host'], $this->_mysql_vars['login'], $this->_mysql_vars['passwd']) or die(mysql_error());
        mysql_select_db($this->_mysql_vars['db_name']) or die(mysql_error());
    }
    
    public function execute_query_file($filename, $sql_variables = array()) {
        $view = new view();
        $sql_data = $view->process_view($sql_variables, $filename);
        return $this->execute_query($sql_data);
    }

    public function execute_query($query) {
        $View = new view();
        $query = $View->replace_vars($this->_mysql_vars, $query);
        if($this->utf8_encode){
            $query = utf8_decode($query);
        }else{
            mysql_query("SET NAMES 'utf8'");
        }

        $sql_array = preg_split("[;(\n)|;(\r)]",$query);
        foreach ($sql_array as $stmt) {
            if (strlen($stmt) > 3) {
            //if (strlen(trim($query)) > 3) {
                if ($this->debug) {
                    $this->_print_query($stmt);
                }
                $this->_result = mysql_query($stmt) or die(mysql_error());
            }
        }
        $this->_query = $stmt;
        return $this->_result; /* Ira sempre retornar o ultimo resultado executado  no arquivo MYSQL */
    }

    public function search_one_field($table, $field, $value) {
        $arrSearch = array();
        $arrSearch[$field] = $value;
        return $this->get_data($table, $arrSearch);
    }
    
    /**
     * Retorna a última linha (possivelmente ultima inserida) de uma tabela
     * @return array ultima linha da tabela.
     */
    public function get_last_line($table){
        $arrFields = $this->get_columns_list($table);
        $orderby_fields = array();
        foreach ($arrFields as $field){
            $orderby_fields[$field] = "DESC";
        }
        $arrCommands = array(
            'orderby_field' => $orderby_fields
        );
        return $this->get_data($table,array(),$arrCommands);
    }

    /**
     * Revisão de simple_data, cria uma query baseada em passagem de comandos, executa e retorna um array da primeira resposta 
     * 

     *  Cria uma matriz com os resultados retornados de fetch array em 
     * um unico array com todas as linhas e resultados 
     * @param string $table - Nome da tabela
     * @param array - $arrSearch - array de pesquisa
     * @param array - $arrCommands - Comandos para pesquisa
     * @param string $format - Lingua a ser formatados o resultado mysql.
     * @param array subtype - Nome do campo e formatação do campo
     * @param array options - Demais opções para pesquisa (@example $options = array('slug' => array('field'=>'campo', 'delimiter'=> '-'))
     */


    public function get_data($table, $arrSearch = null, $arrCommands = array(), $format = null, $subtype = array(),$options = null) {
        /* Vide função search para opções a serem usadas no arrCommands */
        $arrCommands['total_results'] = 1;
        $result = $this->search($table, $arrSearch, $arrCommands);
        if (mysql_num_rows($result)) {
            if (isset($arrCommands['nokeys'])) {
                $line = mysql_fetch_assoc($result);
            } else {
                $line = mysql_fetch_array($result);
            }
            if ($format) {
                $line = $this->_format_mysql_array_fields($line, $result, $format, $subtype);
            }
            if ($this->_arrRelatedQuery) {
                $line = $this->_get_related_data($line); /* Adiciona pesquisa adicional para dados relacionados */
            }
            if($this->utf8_encode){
                $line = convertArrayToUtf8($line);
            }
            if($this->secure){
                $line = stripslashes_deep($line);
            }
            $line = $this->_set_options($line,$options);
            return $line; /* Voltar para cá */
        }
        return null; /* Caso nao tenha nada para se retonar, retorna null */
    }

    /* Revisão de simple_search, cria uma query baseada em passagem de comandos, executa e retorna um fetch_array */

    public function search($table, $arrSearch = null, $arrCommands = array()) {
        $query = $this->create_query_from_arrays($table,$arrSearch,$arrCommands);
        $this->_result = $this->execute_query($query);
        return $this->_result;
    }
    
    /**
     * Cria query Mysql a partir dos arrays $arrSearch e $arrCommands
     * @param string $table - Tabela da query
     * @param array $arrSearch - Array com os campos de pesquisa
     * @param array $arrCommands - Array com os comandos a serem aplicados na pesquisa.
     * return string - Query MYSQL desejada.
     */
    public function create_query_from_arrays($table, $arrSearch, $arrCommands, $statement = "SELECT"){
        $cmd = $this->_define_array_cmd($arrCommands);
        if(!$cmd['orderby_field'] && $cmd['orderby']){
            $cmd['orderby_field'] = $cmd['orderby']; 
        }
        return $this->_create_mysql_query($table, $statement, $cmd['fields'], $arrSearch, $cmd['condition'], $cmd['logic_operator'], $cmd['limits_start'], $cmd['total_results'], $cmd['orderby_field'], $cmd['orderby_descasc'], $cmd['quotes']); 
    }

    /* Cria uma busca relacionada a ultima busca e adiciona um array de resultado a uma chave de resultado */

    public function add_related_search($table, $colField = 'related', $arrSearch = NULL, $arrCommands = array(), $someArray = array(), $format = NULL, $subtype = array()) {
        $cmd = $this->_define_array_cmd($arrCommands);
        $rel_search_query['colField'] = $colField; /* Nome da coluna adicional que recebera o array de relacionamento */
        $rel_search_query['someArray'] = $someArray; /* array adicional a chave relacionada. Ideal para arrays de VIEW */
        $rel_search_query['format'] = $format; /* Lingua de formatação de campos. EX: pt-br */
        $rel_search_query['subtype'] = $subtype; /* Array de formatação: EX: array('data'=> 'num') */
        $rel_search_query['query'] = $this->_create_mysql_query($table, 'SELECT', $cmd['fields'], $arrSearch, $cmd['condition'], $cmd['logic_operator'], $cmd['limits_start'], $cmd['total_results'], $cmd['orderby_field'], $cmd['orderby_descasc'], $cmd['quotes']);

        $this->_arrRelatedQuery[] = $rel_search_query;
    }

    public function clear_related_search() {
        $this->_arrRelatedQuery = NULL;
    }
    
    public function count($table,$arrSearch = array(),$arrCommands = array()){
        /*Remove limites dos tamanhos, caso eles existam*/
        if(isset($arrCommands['limits_start']))
            unset($arrCommands['limits_start']);
        if(isset($arrCommands['total_results']))
            unset($arrCommands['total_results']);
        $columns = $this->get_columns($table);
        $arrCommands['fields'] = "COUNT(`".$columns[0]['Field']."`) AS size";
        $data = $this->get_data($table, $arrSearch, $arrCommands);
        return (int) $data['size'];
    }
    
    /**
     * Retorna a posição de uma linha em uma lista ordenada por uma pesquisa.
     * Ótimo para saber em que posição uma linha estára em um fetch array.
     * Para uso em querys, use operador de igualdade estrito, pois a primeira
     * posição é retornada como 0, e caso o item não exista o retorno é NULL.
     * 
     * @param string $table - Tabela a ser pesquisada.
     * @param string $column - Coluna aonde será encontrado o item desejado
     * @param string $term - Valor do item a ser encontrado na lista
     * @param array - $arrSearch - Array com termos de busca da lista desejada.
     * @param array - $arrCommands - Array com os termos de comando da lista desejada.
     * 
     * @return int - Posição do ítem na lista (0 = primeiro registro
     * @return Bool False caso ítem não exista na lista.
     */
    public function get_line_position($table,$column,$term, $arrSearch = array(), $arrCommands = array()){
        $rank_column = "(@rownum:=@rownum+1) - 1 AS `__pos`";
        
        //Cria query da busca tradicionamente usada.
        $query_table = $this->create_query_from_arrays($table, $arrSearch, $arrCommands);
        //Este tera o alias de view para a query criada
        $myview = "($query_table) AS `TABLE`";
        //Agora cria query que fará a contagem dos elementos da lista
        $query_table = $this->create_query_from_arrays($myview,array(),array('fields'=> array("*",$rank_column)));
        //Esta é uma nova view utilizando a view anterior para adicionar os resultados
        $myview = "($query_table) AS `COUNTER_LIST`";
        
        //Agora efetua a contagem dos elementos e busca pelo elemento desejado.
        $this->execute_query("SET @rownum = 0;");//zera contador
        $data = $this->get_data($myview, array($column=>$term));
        $this->execute_query("SET @rownum = 0;");//zera contador novamente
        if(!$data)//Se não encontrou o elemento desejado
            return false;
        else{
            return (int) $data['__pos'];
        }
    }
    
    /**
     * Olha o $arrCommands e adiciona a coluna desejada ao mesmo.
     * OBS: Se não existir fields definidos no $arrCommands, adiciona o field coringa *,
     * mais o field desejado.
     * 
     * @param string $field - Campo a ser adicionado,
     * @param array $arrCommands - Array com os comandos em uso
     * @return array - $arrCommands modificado com a coluna desejada.
     */
    public function add_field_to_arrCommands($field,$arrCommands){
        if(!isset($arrCommands['fields'])){
            $arrCommands['fields'] = array("*",$field);
        }elseif(is_array($arrCommands['fields'])){
            $arrCommands['fields'][] = $field;
        }else{
            $tmp_field = $arrCommands['fields'];
            unset($arrCommands['fields']);
            $arrCommands['fields'] = array($tmp_field,$field);
        }
        return $arrCommands;
    }
    
    private function _define_array_cmd($arrCommands) {
        $cmd = array(
            'fields' => '*', /* Campos que devem ser retornados pelo mysql */
            'condition' => 'OR', /* Condição, escolha entre OR ou AND */
            'logic_operator' => '=', /* Operador logico, pode ser =, LIKE */
            'limits_start' => null, /* Primeira linha a ser retornada da pesquisa */
            'total_results' => null, /* Total maximo de resultados a ser retornado */
            'orderby_field' => null, /* Defina aqui a coluna de ordenação */
            'orderby' => null, /*Junta em uma var só o orderby_field e o orderby_descasc. Coloque tudo no orderby*/
            'orderby_descasc' => null, /* Escolha entre DESC ou ASC */
            'quotes' => array() /* Array de aspas usadas nos campos definidos nas keys do array */
        );
        foreach (array_keys($arrCommands) as $key) {
            $cmd[$key] = $arrCommands[$key];
        }
        return $cmd;
    }

    /**
     * @deprecated
     *  Depreciada, mantida apenas por compatibilidade 
     */

    public function simple_search($table, $arrSearch = null, $condition = "OR", $logic_operator = "=", $limits_start = null, $total_results = null, $orderby_field = null, $orderby_desasc = null, $quotes = array()) {
        return $this->search($table, $arrSearch, array(
                    'condition' => $condition,
                    'logic_operator' => $logic_operator,
                    'limits_start' => $limits_start,
                    'total_results' => $total_results,
                    'orderby_field' => $orderby_field,
                    'orderby_descasc' => $orderby_desasc,
                    'quotes' => $quotes
                ));
    }

    /**
     * Descobre quem é a primary key da tabela e retorna seu nome.
     * 
     * @return string | Nome da coluna
     * @return boolean|false Não foi encontrada primary key
     */
    public function get_primary_key($table){
        $query = "SHOW COLUMNS FROM $table WHERE `Key` = 'PRI'";
        $result = $this->execute_query($query);
        $data = mysql_fetch_array($result);
        return $data['Field'];
    }
    
    /**
     * Retorna matriz com colunas da tabela e suas propriedades
     * @param string $table - nome da tabela a ser consultada
     * @param string $column_name - caso queira dados de apenas uma coluna.
     * Chaves:
     * [int]
     *  Field | Nome da coluna
     *  Type | Tipo de dados.
     *  Null | Yes ou no, se campo é valido Null ou não
     *  Key | PRI ou NULL, se campo é primary key ou não
     *  Default| Valor ou NULL, valor usado como padrão na coluna.
     *  Exttra | Valores Extra, se é auto_increment por exemplo.
     */
    public function get_columns($table,$column_name = null){
        $query = "SHOW COLUMNS FROM $table";
        if($column_name)
            $query .= " WHERE `Field` = '$column_name'";
        $this->execute_query($query);
        return $this->matrix_fetch_array();
    }
    
    /**
     * Retorna lista de colunas de uma determinada tabela.
     * @param string $table - nome da tabela a ser consultada
     * @param string $column_name - caso queira dados de apenas uma coluna.
     * @return array - lista de colunas da tabela.
     */
    public function get_columns_list($table,$column_name = null){
        $arrColumns = array();
        $columns = $this->get_columns($table, $column_name);
        foreach ($columns as $column) {
            $arrColumns[] = $column['Field'];
        }
        return $arrColumns;
    }
    
    /**
     * Retorna matriz com tabelas do banco de dados
     * Chaves:
     * Tables_in_{db_name}
     */
    public function get_tables(){
        $query = "SHOW TABLES";
        $this->execute_query($query);
        return $this->matrix_fetch_array();
    }
    
    /**
     * Checa se coluna existe.
     * 
     * @param string $table - Nome da tabela
     * @param string $column - Nome da coluna
     * @return boolean - Se coluna existe ou não.
     */
    public function column_exists($table,$column){
        $list = $this->get_columns($table);
        foreach($list as $data){
            if($data['Field'] == $column)
                return true;
        }
        return false;
    }
    
    /**
     * Checa se tabela existe.
     * 
     * @param string $table - Nome da tabela
     * @return boolean - Se tabela existe ou não.
     */
    public function table_exists($table){
        if(!$table)
            return false;
        $query = "SHOW TABLES LIKE '$table'";
        $res = $this->execute_query($query);
        while(mysql_fetch_array($res))
            return true;
        //Não encontrou? retorna false
        return false;
    }
    
    /**
     * Executa o MYSQL alter table em tabela
     * @param string $table | Nome da tablea a ser alterada
     * @param string $field | Campo da tabela a ser adicionado, alterado ou removido
     * @param string $action| Ação a ser executada. suportados: ADD(padrão),ALTER,CHANGE,MODIFY,DROP,DISABLE,ENABLE
     * 
     * @return retorno SQL da ação.
     */
    public function alter_table($table,$field,$action = "ADD",$type = "int(11)", $default = "NULL"){
        $query = "ALTER TABLE $table $action $field $type";
        if($default != "DROP" || $default != "DISABLE" || $default != "ENABLE")
            $query .= " DEFAULT $default";
        return $this->execute_query($query);
    }
    
    /**
     * @deprecated mantida apenas por compatibilidade
     */

    public function simple_data($table, $arrSearch = null, $condition = "OR", $logic_operator = "=", $limits_start = null) {
        return $this->get_data($table, $arrSearch, array(
                    'condition' => $condition,
                    'logic_operator' => $logic_operator,
                    'limits_start' => $limits_start,
                ));
    }

    public function simple_insert($table, $field, $value) {
        $query = "INSERT INTO $table ($field) VALUE ('$value')";
        return $this->execute_query($query);
    }

    /* Criara a query INSERT. As chaves do array $data_array sera os FIELDS e os dados do $data_array os VALUES */

    /**
     * Cria query de INSERT e executa a mesma. Retorna o resultado SQL da operacao
     * 
     * @param string $table - Nome da Tabela
     * @param string $data_array - Array com chaves a serem inseridas e valores preenchidos em cada chave.
     * 
     * @return Resultado SQL.
     */
    public function insert($table, $data_array) {
        $data_array = $this->_apply_security($data_array);
        $field_string = implode(",", array_keys($data_array));
        $value_string = implode("','", $data_array);
        $query = "INSERT INTO $table ($field_string) VALUES ('$value_string')";
        return $this->execute_query($query);
    }

    public function insert_no_quotes($table, $data_array) {
        /* Funcao identica ao insert, porem, nao coloca as aspas nos valores a serem inseridos. */
        /* ATENÇÃO, mysql_real_escape_string não é aplicado aqui */
        $field_string = implode(",", array_keys($data_array));
        $value_string = implode(",", $data_array);
        $query = "INSERT INTO $table ($field_string) VALUES ($value_string)";
        $this->execute_query($query);
        $this->_query = $query;
    }

    public function delete($table,$arrSearch,$arrCommands = array()){
        $cmd = $this->_define_array_cmd($arrCommands);
        $query = $this->_create_mysql_query($table,"DELETE", $cmd['fields'], $arrSearch, $cmd['condition'], $cmd['logic_operator'], null, null, null, null, $cmd['quotes']);
        $this->_result = $this->execute_query($query);
        return $this->_result;
    }

    public function update($table, $data_array, $where_array, $condition = "OR", $logic_operator = '=') {
        $set_cause = $this->create_set_cause($data_array, ",", "=",array(),true);
        $where_cause = $this->create_set_cause($where_array, $condition, $logic_operator);
        $query = "UPDATE `$table` SET $set_cause WHERE $where_cause";
        return $this->execute_query($query);
    }
    
    public function update_no_quotes($table, $data_array, $where_array, $condition = "OR", $logic_operator = '='){
        foreach(array_keys($data_array) as $key){
            $arrQuotes[$key] = "";
        }
        $set_cause = $this->create_set_cause($data_array, ",", "=",$arrQuotes,true);
        $where_cause = $this->create_set_cause($where_array, $condition, $logic_operator);
        $query = "UPDATE `$table` SET $set_cause WHERE $where_cause";
        return $this->execute_query($query);
    }

    /**
     *  Cria uma matriz com os resultados retornados de fetch array em 
     * um unico array com todas as linhas e resultados 
     * @param mysql_resource $res - Recurso SQL (Se não usado, usa-se a última execução do objeto Mysql
     * @param string $format - Lingua a ser formatados o resultado mysql.
     * @param array subtype - Nome do campo e formatação do campo
     * @param array options - Demais opções para pesquisa (@example $options = array('slug' => array('field'=>'campo', 'delimiter'=> '-'))
     */
    public function matrix_fetch_array($res = null, $format = null, $subtype = array(),$options = null) {
        $matrix = null;
        if (!$res) {
            $res = $this->_result;
        }
        while ($line = mysql_fetch_array($res)) {
            if ($format) {
                $line = $this->_format_mysql_array_fields($line, $res, $format, $subtype);
            }
            if ($this->_arrRelatedQuery) {
                $line = $this->_get_related_data($line); /* Adiciona pesquisa adicional para dados relacionados */
            }
            $line = $this->_set_options($line,$options);
            $matrix[] = $line;
        }
        if($this->utf8_encode){
            $matrix = convertArrayToUtf8($matrix);
        }
        return $matrix;
    }

    public function result() {
        return $this->_result;
    }

    public function query() {
        return $this->_query;
    }

    public function create_set_cause($set_array, $condition = "OR", $logic_operator = "=", $arrQuotes = array(),$unsecure = false) {
        $array_query = null;
        if(is_array($condition)){
            $arrCondition = $condition;
            $condition = "";
            foreach(array_keys($set_array) as $key){
                if(!isset($arrCondition[$key])){
                    $arrCondition[$key] = "OR";
                }
            }
            /*Remove a condição do primeiro item do array (este não faz condição com ninguem*/
            $keys = array_keys($set_array);
            $arrCondition[$keys[0]] = "";
            unset($keys);
        }
        foreach (array_keys($set_array) as $key) {
            /* Cria aspas padrao para campos nao citados. O $quotes padrão é o ' */
            if (!isset($arrQuotes[$key])) {
                $arrQuotes[$key] = "'";
            }
            /* Antes filtra as tags de SQL vazias para nao pesquisar por elas */
            if ($set_array[$key] == "" || $set_array[$key] == "%%" || $set_array[$key] == "%") {
                unset($set_array[$key]);
            }
            if(!$unsecure){
                $set_array = $this->_apply_security($set_array);
            }
        }
        /* Tratamento especial pra $logic_operator.
         * Se este for um array, define operador logico especifico para cada filtro aplicando o operador especifico para cada caso.
         *  Senao, aplica string do logic_operator em todos os resultados */
        if (is_array($logic_operator)) {
            foreach (array_keys($set_array) as $key) {
                $quote = $arrQuotes[$key]; /* Aspas a serem aplicadas nos campos */
                if (isset($logic_operator[$key])) {
                   $query = " " . trim($key) . " $logic_operator[$key] $quote$set_array[$key]$quote ";
                } else {
                    $query = " " . trim($key) . " = $quote$set_array[$key]$quote ";
                }
                if(isset($arrCondition)){
                    
                    $query = " ".$arrCondition[$key].$query;
                }
                $array_query[] = $query;
            }
        } else {
            foreach (array_keys($set_array) as $key) {
                $quote = $arrQuotes[$key];
                $query = " " . trim($key) . " $logic_operator $quote$set_array[$key]$quote ";
                if(isset($arrCondition)){
                    
                    $query = " ".$arrCondition[$key].$query;
                }
                $array_query[] = $query;
            }
        }
        if ($array_query) {
            return implode($condition . "\n", $array_query);
        }
    }
    
    /* Pega um resultado */

    private function _get_related_data($data = array()) {
        foreach ($this->_arrRelatedQuery as $relatedQuery) {
            foreach (array_keys($data) as $key) {
                if (!is_array($data[$key])) {
                    $relatedQuery['query'] = str_replace('{' . $key . '}', $data[$key], $relatedQuery['query']);
                }
            }
            $res = $this->execute_query($relatedQuery['query']);
            $data[$relatedQuery['colField']] = array();
            if ($relatedQuery['someArray']) {
                $data[$relatedQuery['colField']] = $relatedQuery['someArray'];
            }
            while ($line = mysql_fetch_array($res)) {
                if ($relatedQuery['format']) {
                    $data[$relatedQuery['colField']][] = $this->_format_mysql_array_fields($line, $this->_result, $relatedQuery['format'], $relatedQuery['subtype']);
                } else {
                    $data[$relatedQuery['colField']][] = $line;
                }
            }
            /* Se a linha for em branco, adiciona um array com as chaves vazias */
            if (!$data[$relatedQuery['colField']]) {
                for ($i = 0; $i < mysql_num_fields($res); $i++) {
                    $line[mysql_field_name($res, $i)] = null;
                }
                $data[$relatedQuery['colField']][] = $line;
            }
        }
        return $data;
    }
    
    /**
     * Verifica o tipo de variavel de orderby, se for string, retorna o conteudo, se for array, retorna a colecao de string
     * O orderby pode ser tratado de duas formas, a mais tradicional diretamente passando por string o orderby_field e o orderby_descasc
     * ou pode-se definir via array apenas o orderby_field. Desta forma, o orderby_descasc é descartado, e no orderby_field,
     * o nome da chave do array deve ser o campo, e o conteúdo se é desc ou asc. Exemplo
     * 
     * $orderby_field = array(
     *                          'ID'=> 'ASC',
     *                          'data'=> 'DESC'
     *                       );
     * 
     */
    private function _assign_orderby($orderby_field,$orderby_descasc){
        /*Processa o orderby_descasc*/
        if(is_array($orderby_descasc)){
            $fields = $orderby_descasc;
        }
        if(is_array($orderby_field)){
            foreach(array_keys($orderby_field) as $fieldkey){
                $fields[] = "$fieldkey $orderby_field[$fieldkey]";
            }
            return "ORDER BY ".implode(',',$fields);
        }
        if(is_string($orderby_field) && is_string($orderby_descasc))
            return " ORDER BY $orderby_field $orderby_descasc ";
        return null;
    }
    
    /*Define no campo os valores aplicados no array options*/
    private function _set_options($line,$options){
        if(isset($options['slug']))
            $line = $this->_set_slug($line,$options['slug']);
        return $line;        
    }
    
    private function _set_slug($line,$slug_options){
        foreach($slug_options as $slug_item){
            $field_name = $slug_item['field'];
            $line[$field_name."-slug"] = StringToSlug::gen($line[$field_name]);                 
        }
        return $line;
    }

    /* cria a $query para simple_search */
    private function _create_mysql_query($table, $sql_command, $fields, $arrSearch, $condition, $logic_operator, $limits_start, $total_results, $orderby_field, $orderby_descasc, $arrQuotes) {
        if (is_array($fields)) {
            $fields = implode(",", $fields);
        }
        if (!$fields) {
            $fields = "*";
        }
        $where_cause = null;
        $sql_command = strtoupper($sql_command);
        /*Vefifica qual comando SQL está sendo passado*/
        if($sql_command == "SELECT"){
            $query = "SELECT $fields \nFROM $table ";
        }elseif($sql_command == "DELETE"){
            $query = "DELETE FROM $table ";
        }       
        if (is_array($arrSearch)) {
            $where_cause = $this->create_set_cause($arrSearch, $condition, $logic_operator, $arrQuotes);
        }
        if ($where_cause) {
            $query .= "WHERE\n $where_cause";
        }
        if(!isset($orderby_descasc)){
            $orderby_descasc = '';
        }
        if ($orderby_field) {
            $query .= $this->_assign_orderby($orderby_field, $orderby_descasc);
        }
        if ($limits_start && !$total_results) {
            $total_results = 100; /* Valor harbitrário, caso total results não tenha valor algum */
        }
        if ($limits_start) {
            $query .= " \nLIMIT $limits_start, $total_results";
        } elseif ($total_results) {
            $query .= " \nLIMIT  $total_results";
        }
        return $query;
    }

    private function _apply_security($arrFields) {
        
        if ($this->secure) {
            foreach (array_keys($arrFields) as $key) {
                $arrFields[$key] = mysql_real_escape_string($arrFields[$key]);
            }
        }
        return $arrFields;
    }

    private function _format_mysql_array_fields($mysqlArr, $res, $format, $arrSubtype) {
        $data = array();
        $objLocalFormats = new local_formats();
        for ($i = 0; $i < mysql_num_fields($res); $i++) {
            $field = mysql_field_name($res, $i);
            $fieldType = mysql_field_type($res, $i);
            if (isset($arrSubtype[$fieldType])) {
                $subType = $arrSubtype[$fieldType];
            } else {
                $subType = null;
            }
            $data[$field] = $objLocalFormats->data_to_local_str($mysqlArr[$field], $fieldType, $format, $subType);
        }
        return $data;
    }

    private function _print_query($query) {
        ?>
        <pre style="
             font-family:  Courier New, Courier, Arial, Verdana, sans-serif;
             font-weight: bold;
             color:#8cff80;
             border: 2px solid #090;
             background-color:#000;
             width: 95%;
             margin: 20px auto;
             padding:3px;
             overflow: hidden;
             "
             ><?= $query ?></pre>
        <?
    }
}
?>