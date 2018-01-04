<?php

/*
 * To change this template, choose Tools | Templates
 * and open the template in the editor.
 */

/**
 * Description of XMLForms_reader
 * 
 *
 * @author Carlos Júnior
 * 
 * CHANGELOG:
 * 
 * 2.1 - Adicionado método add_array_data() 
 */
class XMLForms_reader extends XMLForms {

    const version = 2.1;

    private $_arrSearch;
    private $_arrCommands;

    /**
     * $_array_data é o campo que recebe o array de dados provenientes do banco de dados.
     * Este também pode ser alimentado diretamente pelo metodo add_array_data.
     */
    private $_array_data = array();

    /**
     * Le um dado específico da tabela utilizando o form XML como referência.
     */
    public function read($value = NULL, $field_name = NULL) {
        return $this->_readsomething('simple', $value, $field_name);
    }

    public function readreport($value = NULL, $field_name = NULL) {
        return $this->_readsomething('report', $value, $field_name);
    }

    public function readunformated($value = NULL, $field_name = NULL) {
        return $this->_readsomething('unformated', $value, $field_name);
    }

    /**
     * Este é usado quando queremos processar valores que não são originados do
     * banco de dados utilizando as regras aplicadas no XMLForms.
     * 
     * Modo de uso:
     * 
     * Adicione o array de valores a $array_data
     * use o método read(), ou readreport(), ou readunformated() sem nenhum valor sendo passado como parametro.
     * Quando os métodos read(), readreport() ou readunformated() recebem dados para processar do banco de dados, 
     * estes sobrescrevem o $_array_data com os dados coletados.
     */
    public function add_array_data($array_data){
        $this->_array_data = $array_data;
    }

    /**
     * Efetua a busca dentro dos filtros definidos no $arrSearch e $arrCommands
     * e retorna o total de resultados desta busca. 
     * Este é ótimo para listar um total de resultados que uma certa busca terá.
     * @param array $arrSearch - arrSearch para objeto Mysql
     * @param array $arrCommands - arrCommands para objeto Mysql
     * @return int Total de resultados.
     */
    public function filter_and_totals($arrSearch, $arrCommands = array()) {
        if (!is_array($arrSearch) && !is_array($arrCommands)) { //Se estes não forem array, não adiciona a busca (retorna nulo).
            return NULL;
        }
        //Restringe a busca (arrSearch) aos campos pesquisaveis (searcheable_fields), evitando assim adicionar algum campo que não faça parte do formulário.
        $searcheable_fields = $this->mysql_fields();
        $new_arrSearch = array();
        foreach (array_keys($arrSearch) as $fieldKey) {
            if (array_search($fieldKey, $searcheable_fields) !== FALSE)
                $new_arrSearch[$fieldKey] = $arrSearch[$fieldKey];
        }
        $this->_arrSearch = $new_arrSearch;
        $this->_arrCommands = $arrCommands;
        //Agora, prepara busca para retornar o total de resultados
        $arrSearch2 = $this->_arrSearch;
        $arrCommands2 = $this->_arrCommands;
        $arrCommands2['fields'] = 'COUNT(*) AS total';
        $table = $this->table();
        $Mysql = $this->Mysql();
        $data = $Mysql->get_data($table, $arrSearch2, $arrCommands2);
        return (int) $data['total'];
    }

    /**
     * Retorna lista de estrutura simples de resultados filtrados por filter_and_totals()
     */
    public function do_list($limits_start = NULL, $total_results = NULL, $fields_to_list = array()) {
        return $this->_do_some_list('simple', $limits_start, $total_results, $fields_to_list);
    }

    /**
     * Retorna lista de estrutura avancada de resultados filtrados por filter_and_totals()
     */
    public function do_reportlist($limits_start = NULL, $total_results = NULL, $fields_to_list = array()) {
        $data = array(
            'list' => '',
            'list_header' => ''
        );
        $data['list'] = $this->_do_some_list('report', $limits_start, $total_results, $fields_to_list);
        if (isset($data['list'][0]['item_list']))//Retorna a linha 0 em um novo array para titulo da listagem.
            $data['list_header'] = $data['list'][0]['item_list'];
        return $data;
    }

    /**
     * Retorna lista de estrutura simples de resultados filtrados por filter_and_totals()
     * e valores sem formatação.
     */
    public function do_unformatedlist($limits_start = NULL, $total_results = NULL, $fields_to_list = array()) {
        return $this->_do_some_list('unformated', $limits_start, $total_results, $fields_to_list);
    }

    /**
     * Retorna um array com os campos e suas propriedades.
     * @param string $formName - Nome do form a ser listados os campos
     * @return array - Campos do form e suas propriedades
     * @return false - Caso form não exista 
     * 
     */
    public function listFields() {
        $arrFields = array();
        $xmlForm = $this->XMLForm;
        $pk = $this->primary_key();
        if ($pk)
        /* Valores seguem padrão de um field de xmlField */
            $arrFields[$pk] = array(
                '@attributes' => array(
                    'name' => $pk
                ),
                'primary_key' => 'true'
            );

        /* Agora adiciona xmlFields a $arrFields com a seguinte organização: 
         * nome dos campos como chave, e xmlField como conteudo 
         */
        for ($i = 0; $i < count($xmlForm->field); $i++) {
            $xmlField = $xmlForm->field[$i];
            $fieldName = (string) $xmlForm->field[$i]['name'];
            if ($fieldName) {
                /* Checa se campo já foi inserido. Se foi, faz um merge entre os
                 * dois campos (array_merge_recursive)
                 */
                if (isset($arrFields[$fieldName]))
                    if (is_array($arrFields[$fieldName]))
                        $field = array_merge($arrFields[$fieldName], toArray($xmlField));
                    else {
                        //Vazio, pois aqui não tem else o if acima que tem
                    }
                else
                    $field = toArray($xmlField);
            }
            $arrFields[$fieldName] = $field;
        }
        /* Retorna arrFields */
        return $arrFields;
    }

    /**
     * Pega o resultado mysql e o trata aplicando as devidas regras do campo desejado.
     * @param array $line - Linha do resultado Mysql com os campos pare serem processados.
     * @param array $fields_to_list - Quando setado o array, limita a processar apenas os campos setados no mesmo.
     */
    private function _process_line($data = array(), $fields_to_list = array()) {
        if (!$data)
            return array(); //Se não há informações para processar, retorna em branco
        $arrOut = array(); // $data;
        $primary_key = parent::primary_key();
        //Adiciona originalmente a chave primaria do arrOut
        if (array_key_exists($primary_key, $data))
            $arrOut[$primary_key] = $data[$primary_key];
        //Agora adiciona os demais valores
        foreach ($this->XMLForm->field as $XMLField) {
            $field_name = (string) $XMLField['name'];
            $rel_field = (string) $XMLField->relate->rel_key;
            $field_classname = XMLForms_fields::field_classname($XMLField);
            $value = NULL;
            if ($rel_field && isset($data[$rel_field]))
                $value = $data[$rel_field];
            elseif (isset($data[$field_name]))
                $value = $data[$field_name];
            if (class_exists($field_classname)) {
                $fieldClass = new $field_classname($XMLField);
                $fieldOut = $fieldClass->str_value($value);
                if (!$fields_to_list)
                    $arrOut = array_merge($arrOut, $fieldOut);
                elseif (array_search($field_name, $fields_to_list) !== FALSE) //Se fields_to_list foi setado, limita-se apenas a listar resultados presentes no mesmo
                    $arrOut = array_merge($arrOut, $fieldOut);
            }
        }
        return $arrOut;
    }

    /**
     * Utilizado por todos os métodos do_list dependendo do list_type.
     * @param string $list_type se ele for:
     *  'simple' => lista simples, boa para views já pre-estruturadas
     *  'report' => retorna lista como reportList, ótimo para views automáticas
     *  'unformated'=> retorna dados sem tratamento de formatacao. Ótima para retornar dados para outros objetos.
     */
    private function _do_some_list($list_type, $limits_start, $total_results, $fields_to_list) {
        $list = array();
        $table = $this->table();
        $arrSearch = $this->_arrSearch;
        $arrCommands = $this->_arrCommands;
        if ($limits_start)
            $arrCommands['limits_start'] = $limits_start;
        if ($total_results)
            $arrCommands['total_results'] = $total_results;
        $arrCommands['fields'] = $this->mysql_fields();
        $Mysql = $this->Mysql();
        $res = $Mysql->search($table, $arrSearch, $arrCommands);
        while ($line = mysql_fetch_assoc($res)) {
            $formated_line = $this->_process_line($line, $fields_to_list);
            if ($list_type == 'simple')
                $list[] = $formated_line;
            elseif ($list_type == 'report')
                $list[]['item_list'] = $this->_line_as_report($formated_line);
            elseif ($list_type == 'unformated')
                $list[] = $this->_line_unformated($formated_line);
        }
        return $list;
    }

    /**
     * Pega informações provenientes do banco de dados. Caso $field_name e $value
     * não sejam passados, e exista alguma informação em $this->_array_data. Processa
     * o $_array_data.
     */
    private function _get_some_data($field_name, $value) {
        if ($field_name) {
            $table = $this->table();
            $Mysql = $this->Mysql();
            $arrSearch = array($field_name => $value);
            $arrCommands['fields'] = $this->mysql_fields();
            $res = $Mysql->search($table, $arrSearch, $arrCommands);
            return mysql_fetch_assoc($res);
        }elseif($this->_array_data)
            return $this->_array_data;
    }

    private function _readsomething($list_type, $value, $field_name) {
        if (!$field_name)
            $field_name = parent::primary_key();
        $data = $this->_get_some_data($field_name, $value);
        $formated_line = $this->_process_line($data);
        if ($list_type == 'simple')
            return $formated_line;
        elseif ($list_type == 'report')
            return $this->_line_as_report($formated_line);
        elseif ($list_type == 'unformated')
            return $this->_line_unformated($formated_line);
    }

    private function _line_as_report($line) {
        $report_item = array();
        //Adiciona a primary_key
        $primary_key = parent::primary_key();
        if (array_key_exists($primary_key, $line))
            $report_item[] = array(
                'type' => 'primary_key',
                'key' => $primary_key,
                'label' => $primary_key,
                'value' => $line[$primary_key]
            );
        foreach ($this->XMLForm->field as $XMLField) {
            $field_name = (string) $XMLField['name'];
            $field_type = (string) $XMLField->field_type;
            if (array_key_exists($field_name, $line)) {
                $label = (string) $XMLField->label;
                if (!$label)
                    $label = $field_name;
                $report_item[] = array(
                    'type' => $field_type,
                    'key' => $field_name,
                    'label' => $label,
                    'value' => $line[$field_name]
                );
            }
        }
        return $report_item;
    }

    private function _line_unformated($line) {
        $unformated_line = array();
        //Adiciona agora a primary key
        $primary_key = parent::primary_key();
        //Agora adiciona os campos sem formatacao
        $prefixo = Fields::originalKey;
        if (array_key_exists($primary_key, $line))
            $unformated_line[$primary_key] = $line[$primary_key];
        foreach (array_keys($line) as $key)
            if (array_key_exists($prefixo . $key, $line))
                $unformated_line[$key] = $line[$prefixo . $key];

        return $unformated_line;
    }

}

?>
