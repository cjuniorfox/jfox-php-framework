<?php
/*
 * To change this template, choose Tools | Templates
 * and open the template in the editor.
 */

/**
 * Description of Fields
 *
 * @author cjuniorfox
 */
class Fields {

    protected $XMLField;

    const version = 1.0;

    public $request_primary_key = false;
    protected $primary_key_value = NULL;

    /**
     * Prefixo da chave aonde se encontra o valor original (não tratado) do campo
     */

    const originalKey = '__DATA::';
    const no_quotes = "<!!@@";
    const default_mysql_property = "VARCHAR";
    const default_mysql_size = '100';

    /**
     * Conteúdo do campo a ser retornado com o mesmo.
     */
    protected $value;
    protected $HTML;
    protected $JS;
    protected $HEADER;
    protected $form_name;
    protected $language = 'pt_br';
    protected $OBJMysql;

    /**
     * Este recebe valor quando o proprio campo encontra seu relacionamento,
     */
    protected $rel_value = '';

    /**
     * Quando multiSelect é true, este define o prefixo da chave que retornará o valor original do multi-select
     */
    public $selectKey = '';

    /**
     * Este identifica este campo como multiSelect. Ou seja, significa que este
     * utiliza para representar seu valor um identificador relativo e não o valor
     * em si.
     */
    public $multiSelect = false;

    /**
     * Cria objeto para trabalhar com o campo
     * @param SimpleXMLElement $XMLField - Campo XML
     * @param string $language - Lingua padrão utilizada no tratamento dos dados
     * @param string $value - Valor do campo a ser preenchido
     * @param string $form_name - Nome do formulário onde faz parte o campo a ser tratado
     */
    public function __construct($XMLField = null, $language = '', $value = '', $form_name = '') {
        $this->XMLField = $XMLField;
        $this->form_name = $form_name;
        $this->language = $language;
        $this->value = self::default_values($value);
    }

    /**
     * Usado por XMLForms_fields
     * Quando o objeto de campo exige um primary_key, este é chamado para
     * definir o valor de primary_key.
     */
    public function define_primary_key($primary_key_value) {
        $this->primary_key_value = $primary_key_value;
    }

    /**
     * Retorna string com código HTML do campo
     * @return string
     */
    public function HTML() {
        return $this->HTML;
    }

    /**
     * Retorna string com código Javascript para o campo
     * @return string
     */
    public function JS() {
        return $this->JS;
    }

    /**
     * Retorna Header com informações de inicialização do elemento.
     * @return string
     */
    public function HEADER() {
        return $this->HEADER;
    }

    /**
     * Retorna chave estrangeira do campo relacionado a este, caso o mesmo exista
     * @return string Chave estrangeira
     * @return null Caso não haja relacionamento neste campo
     */
    public function rel_key() {
        if ($this->XMLField->relate->rel_key)
            return (STRING) $this->XMLField->relate->rel_key;
    }

    public function field_name_or_rek_key() {
        $key = $this->rel_key();
        if ($key)
            return $key;
        elseif ($this->XMLField['name'])
            return (string) $this->XMLField['name'];
    }

    /**
     * Retorna array com atributos de inserção para o campo desejado, e trata os relacionamentos de tabela do campo.
     * @param array $POST - Dados enviados para postagem.
     * @return array - Se existirem dados para serem postados
     * @return NULL - Se não existirem dados para serem postados
     */
    public function sql_data($POST) {
        //Declara variaveis iniciais
        $field_name = (string) $this->XMLField['name'];
        $value = "";
        $key = $this->field_name_or_rek_key();
        if (!array_key_exists($key, $POST))
            return null; //Só segue abaixo se existir a chave postada no POST. Caso não exista, não há mais o que fazer.
        $value = $POST[$key];
        //Se value não existir, retorna a mensagem NULL
        if ($this->XMLField->relate->rel_key && array_key_exists($field_name, $POST)) { //Verifica se há relacionamento a ser processado.
            $relation_value = self::convertStrtoData($POST[$field_name], $this->XMLField, $this->language);
            //Este cuida de inserir, atualizar, o que fazer com o dado relacionado na tabela 
            //relacionada e retorna o iD do campo inserido.
            $value = XMLForms_post::process_data_related($relation_value, $value, $this->XMLField);
        } else { //Não há nada de relacionamento? então trata os dados normais.
            $value = self::convertStrtoData($value, $this->XMLField, $this->language);
        }
        //Aplica também o encode, caso os dados a serem cadastrados sejam codificados
        $crypt_field = (string) $this->XMLField->mysql_encode->crypt_field;
        if ($crypt_field && array_key_exists($crypt_field, $POST))
            $value = self::encodeSqlData($value, $this->XMLField, $POST[$crypt_field]);
        else
            $value = self::encodeSqlData($value, $this->XMLField);
        //Faz um último tratamento no value e efetua o retorno dos dados.
        if ($value) {
            if (strpos($value, self::no_quotes) === 0)
                $value = str_replace(self::no_quotes, "", $value);
            else
                $value = "'" . $value . "'";
            return array(
                'field' => $key,
                'value' => $value
            );
        }elseif ($value === "") {
            return array(
                'field' => $key,
                'value' => "NULL"
            );
        }
    }

    /**
     * Retorna código Javascript JQuery para criação do campo de relacionamento
     * para o campo que está sendo processado.
     * @return string Javascript do campo (caso ele exista)
     * @return null (Caso não exista campo para retornar).
     */
    public function JS_relate($value = '') {
        if (!$value && $this->rel_value)
            $value = $this->rel_value;
        if (!$this->XMLField->relate->rel_key) //Se não existir, já escapa do código
            return null;
        $rel_key = self::rel_key();
        ob_start();
        ?>
        <script type="text/javascript">
            $(function(){
                if(!$("form[name=<?= $this->form_name ?>] input[name='<?= $rel_key ?>']").length){
                    $('<input>').attr({
                        type : 'hidden',
                        name : '<?= $rel_key ?>',
                        value: '<?= $value ?>'
                    }).
                        appendTo("form[name=<?= $this->form_name ?>]");
                }
                                                                                                                                                                                                                                        
            });
        </script>
        <?
        $out = ob_get_clean();
        return $out;
    }

    /**
     * Retorna o Label do campo.
     */
    public function label() {
        $id = $label = null;
        $XMLField = $this->XMLField;
        if (!$XMLField['id'] && $XMLField['name'])
            $id = (string) $XMLField['name'];
        if ($XMLField['id'])
            $id = (string) $XMLField['id'];
        if ($XMLField->label)
            $label = (string) $XMLField->label;
        if ($id && $label)
            return "<label for=\"$id\">$label</label>";
        else
            return '';
    }

    /**
     * Retorna propriedades do elemento XML
     */
    public static function getProperties($objXML) {
        $prop = array();
        foreach ($objXML->attributes() as $name => $attribute) {
            $prop[$name] = (string) $attribute;
        }
        return $prop;
    }

    public static function propertiesToStr($properties) {
        $buffer = null;
        if (is_array($properties)) {
            foreach (array_keys($properties) as $fieldKey) {
                $buffer .= $fieldKey . '="' . $properties[$fieldKey] . '" ';
            }
        }
        return $buffer;
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
    public function autofill_related_fields($xmlfilepath, $form_name, $field_name, $rel_id_value) {
        $XMLForms_fields = new XMLForms_fields($xmlfilepath, $form_name);
        $XMLField = $XMLForms_fields->XMLField($field_name);
        $XMLForm = $XMLForms_fields->XMLForm;
        $this->language = $XMLForms_fields->language;

        $data = array(); /* Este tera o resultado da listagem de itens */
        $table = (string) $XMLField->relate->table;
        $field = (string) $XMLField['name'];
        $column = (string) $XMLField->relate->primary_key;
        $rel_key = (string) $XMLField->relate->rel_key;
        if (!$table || !$field) { /* Se algum destes elementos não for listado, retorna nulo */
            return $data;
        }

        $Mysql = new mysql();

        $form_data = $Mysql->get_data($table, array($column => $rel_id_value));
        /* Dados capturados? Agora varre $XmlForm e busca por campos relacionados 
         * ao mesmo rel_id_column e a mesma tabela.
         */
        foreach ($XMLForm->field as $XMLItem) {
            if ($XMLItem->relate->rel_key == $rel_key) {
                $fieldItem = (string) $XMLItem['name'];
                $data[$fieldItem] = self::convertDatatoStr($form_data[$fieldItem], $XMLItem, $this->language);
            }
        }
        return $data;
    }

    /**
     * Este é um DEFAULT caso o mesmo não tenha sido sobrescrito pelo objeto do ítem de formulário.
     * Retorna sempre a propriedade padrão
     * @return propriedade mysql
     */
    public function mysql_property() {
        $mysql_property = self::default_mysql_property;
        $size = self::mysql_size($this->XMLField, self::default_mysql_size);
        return "$mysql_property($size)";
    }

    public function mysql_property_relate() {
        $XMLField = $this->XMLField;
        if (!$XMLField->relate->primary_key || !$XMLField->relate->table)
            return NULL;
        $rel_primary_key = (string) $XMLField->relate->primary_key;
        $rel_table = (string) $XMLField->relate->table;
        $Mysql = $this->Mysql();
        $related_column_properties = $Mysql->get_columns($rel_table, $rel_primary_key);
        if (isset($related_column_properties[0]))
            return $related_column_properties[0]['Type'];
        else
            return "INT()";
    }

    /**
     * Este é um DEFAULT caso o mesmo não tenha sido sobrescrito pelo objeto do ítem de formulário.
     * Retorna o nome do field_name para o banco de dados.
     * Caso o ítem não tenha nome para ser retornado, retorna nulo
     * @return string - Nome do campo para o banco de dados
     * @return NULL - Item não tem nome do banco de dados.
     */
    public function mysql_field_name() {
        $XMLField = $this->XMLField;
        $mysql_field = NULL;
        if (!isset($XMLField['name']))
            return false;
        if ($XMLField['name']) {
            //Por padrão, define o field_name como o nome do campo mysql
            if ($XMLField['name'])
                $mysql_field = (string) $XMLField['name'];
            //Se tiver rel_key, este vai ser o nome do campo mysql
            if ($XMLField->relate->rel_key)
                $mysql_field = (string) $XMLField->relate->rel_key;
            //Se estiver criptogradado o campo desejado, o nome do campo é a descriptografia do mesmo
            if ($XMLField->mysql_encode->enabled == 'true' && ($XMLField->mysql_encode->encode_key || $XMLField->mysql_encode->crypt_field)) {
                $XMLField->mysql_encode->enabled;
                if ($XMLField->mysql_encode->encode_key) {
                    $crypt = "'" . $XMLField->mysql_encode->encode_key . "'";
                } elseif ($XMLField->mysql_encode->crypt_field) {
                    $crypt = (string) $XMLField->mysql_encode->crypt_field;
                }

                $mysql_field = "$mysql_field|DECODE($mysql_field,$crypt) AS $mysql_field";
            }
        }
        return $mysql_field;
    }

    /**
     * Recebe elemento XML do field e analisa suas propriedades
     * e retorna a propriedade do campo desejado para este elemento.
     * 
     * @param SimpleXMLElement $xmlField - Elemento XML do input ou inputDatePicker
     * @return string - Type da coluna no banco de dados
     */
    public static function mysql_input_type_by_format($XMLField) {
        $size = self::mysql_size($XMLField, self::default_mysql_size);
        if ($XMLField->format_date->enabled == 'true') /* Formato de data */
            return 'date';
        elseif ($XMLField->format_time->enabled == 'true') /* Formato de hora */
            return 'time';
        elseif ($XMLField->format_real->enabled == 'true') { /* Numero real decimal */
            if ($XMLField->format_real->subtype == 'monetary') /* Se financeiro */
                return 'decimal(10,2)';
            else /* Se for real, porém sem ser financeiro */
                return 'decimal()';
        }elseif ($XMLField->format_integer->enabled == 'true') /* Numero inteiro */
            return "int";
        else /* Quaisquer outro tipo */
            return "varchar($size)";
    }

    /**
     * Pega a string $value que deverá ser fornecida como dado cru do Mysql, e a converte para string
     * amigavel para impressão.
     * Este pode ser chamado via chamada estatica Fields::convertDatatoStr();
     * @param string $value - Valor a ser convertido
     * @param SimpleXMLElement - $XMLField - XML do campo com instruções de conversão.
     * @param string $language - Lingua a ser usada na conversão.
     * @return string - Valor convertido
     */
    public static function convertDatatoStr($value, $XMLField, $language) {
        if (!$value)
            return $value; //Se value for vazio, não há o que converter, retorna ele mesmo vazio.
        /* dataToStr: Quando true, converte DATA para STR, quando FALSE converte STR para DATA */
        $objLocalFormats = new local_formats();
        $arrTS = self::_get_format_type_subtype($XMLField);
        return $objLocalFormats->data_to_local_str($value, $arrTS['type'], $language, $arrTS['subtype']);
    }

    /**
     * Pega a stringamigavel para impressão $value, e a converte para string
     * de Mysql.
     * Este pode ser chamado via chamada estatica Fields::convertDatatoStr();
     * @param string $value - Valor a ser convertido
     * @param SimpleXMLElement - $XMLField - XML do campo com instruções de conversão.
     * @param string $language - Lingua a ser usada na conversão.
     * @return string - Valor convertido
     */
    public static function convertStrtoData($value, $XMLField, $language) {
        if (!$value)
            return $value; //Se value for NULO, não há o que converter.
        $objLocalFormats = new local_formats();
        $arrTS = self::_get_format_type_subtype($XMLField);
        return $objLocalFormats->local_str_to_data($value, $arrTS['type'], $language, $arrTS['subtype']);
    }

    public static function encodeSqlData($value, $XMLField, $encode_key = null) {
        $value = rtrim(stripslashes($value)); //Limpa espaços em branco no inicio e final, e também as malditas contra barras
        if ($XMLField->mysql_encode->enabled == 'true') {
            $key = (string) $XMLField->mysql_encode->crypt_field;
            if (!$encode_key) {
                $encode_key = $XMLField->mysql_encode->encode_key;
            }
            $value = self::no_quotes . "ENCODE('" . mysql_real_escape_string($value) . "','$encode_key')";
        } else {

            $value = mysql_real_escape_string($value);
        }
        return $value;
    }

    /**
     * Olha o XMLField e procura pelo tamanho da string, e define a mesma
     * como o tamanho da string para o campo.
     */
    public static function mysql_size($XMLField, $default_size) {
        if ($XMLField['maxlength'])
            $size = (string) $XMLField['maxlength'];
        elseif ($XMLField['size'])
            $size = (string) $XMLField['size'];
        else
            $size = $default_size;
        return $size;
    }

    /**
     * Procura por dados relacionados. Caso existam, cria um array na seguinte
     * estrutura:
     * array(
     *  'rel_key_name' => $value,
     *  'field_name' => $newValue "valor encontrado do relacionamento"
     * )
     */
    protected function look_and_get_related($value) {
        $XMLField = $this->XMLField;
        $relate = $XMLField->relate;
        $field_name = (string) $XMLField['name'];
        /* Antes de tudo, já define o arrOut padrão caso não exista relacionamento */
        $arrOut = array(
            self::originalKey . $field_name => $value,
        );
        if ($relate->table && $relate->primary_key) {
            $table = (string) $relate->table;
            $pk = (string) $relate->primary_key;
            $rel_k = (string) $relate->rel_key;
            $field_name = (string) $XMLField['name'];
            $Mysql = $this->Mysql();
            $data = $Mysql->search_one_field($table, $pk, $value);

            /* Agora monta o $arrOut com a estrutura de relacionamento */
            $arrOut = array(
                self::originalKey . $field_name => $data[$field_name],
                $rel_k => $value
            );
        }
        return $arrOut;
    }

    protected function get_css_element_id($element, $properties, $use_form_name = true) {
        $id_element = null;
        if (isset($properties['id']))
            $id_element = "#" . $properties['id'];
        elseif (isset($properties['name']))
            $id_element = $element . "[name='" . $properties['name'] . "']";
        if ($this->form_name && $use_form_name)
            return "form[name='$this->form_name'] $id_element";
        return $id_element;
    }

    protected function default_values($value) {
        if ($value == '$_DATE_TODAY')
            $value = $this->_str_date_today($value);
        if ($value == '$_LAST')
            $value = $this->_lastInsert($value);
        return $value;
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
     * Olha o XML e cria um array com as chaves type e subtype.
     */
    private function _get_format_type_subtype($XMLField) {
        $type = null;
        $subtype = null;
        if ($XMLField->format_real->enabled == 'true') {
            $subtype = (string) $XMLField->format_real->subtype;
            $type = 'real';
        } elseif ($XMLField->format_integer->enabled == 'true') {
            $type = 'integer';
        } elseif ($XMLField->format_date->enabled == 'true') {
            $subtype = (string) $XMLField->format_date->subtype;
            $type = 'date';
        } elseif ($XMLField->format_time->enabled == 'true') {
            $subtype = (string) $XMLField->format_time->subtype;
            $type = 'time';
        }
        return array(
            'type' => $type,
            'subtype' => $subtype
        );
    }

    /**
     * Busca ultima entrada relacionada.
     * Tem funcionamento misto, ou seja, funciona tanto para campos relacionados
     * a partir do relate quanto para relacionamentos criados pelo mysql_selectbox.
     */
    private function _lastInsert($value) {
        //Tenta descobrir qual o tipo de relacionamento usado. (relate ou mysql_selectbox).
        $relate = false;
        if ($this->XMLField->relate->table)
            $relate = true;
        elseif (!$this->XMLField->table)
            return $value;
        if ($relate) { //É relacionamento do tipo relate
            $table = (string) $this->XMLField->relate->table;
            $pri_key = (string) $this->XMLField->relate->primary_key;
            $column = (string) $this->XMLField['name'];
        } else { // É relacionamento do tipo mysql_selectbox
            $table = (string) $this->XMLField->table;
            $pri_key = (string) $this->XMLField->val_collumns;
            $column = $pri_key;
        }
        unset($relate);
        //Agora busca o valor do campo e retorna o mesmo
        $Mysql = $this->Mysql();
        $arrCommands = array(
            "orderby_field" => $pri_key,
            "orderby_descasc" => "DESC",
        );
        $data = $Mysql->get_data($table, array(), $arrCommands);
        //$this->rel_value = $data[$pri_key];
        return $data[$column];
    }

    private function _str_date_today() {
        $today = date("Y-m-d");
        $Local_formats = new local_formats();
        return $Local_formats->date_to_local_str($today, (string) $this->XMLField->format_date->subtype, $this->language);
    }

}
?>
