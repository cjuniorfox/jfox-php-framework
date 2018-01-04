<?php

/**
 * Cuida do algorítmo que inclue cada campo no formulário
 *
 * @author juniorfox
 */
class xmlForms_fields {

    private $_objForm; /* Objeto que cria os forms */
    private $_xml; /* Arquivo XML usado pela aplicacao */
    private $_xmlForm; /* XML do formulario a ser criado */
    public $debug = false;

    /**
     * @param SimpleXMLElement $xml - XML base do formulario
     */
    public function __construct($xml, $xmlForm) {
        $this->_objForm = new form((string) $xmlForm['name']);
        $this->_xml = $xml;
        $this->_xmlForm = $xmlForm;
    }

    public function insertField($xmlField) {
        /* Descobre nome do metodo que executará o campo, dependendo de suas propriedades */
        $type = (string) $xmlField->field_type;
        $method_name = '_insert' . $type;
        /* Se metodo existir, executa ele */
        if (method_exists('xmlForms_fields', $method_name)) {
            /* Mas antes verifica se existe algum campo de relacionamento, caso exista,
             * cria campo de relacionamento antes
             */
            if ($xmlField->relate)
                $this->_relId_hiddenField((string) $xmlField->relate->rel_key, $this->_xmlForm['name'], $xmlField->rel_value);
            $this->$method_name($xmlField, $this->_getProperties($xmlField));
        } elseif ($this->debug) {
            echo "$type não encontrado<br />";
        }
    }

    public function getForm() {
        $formProperties = $this->_getProperties($this->_xmlForm);
        return $this->_objForm->getForm($formProperties);
    }

    private function _getProperties($objXML) {
        $prop = array();
        foreach ($objXML->attributes() as $name => $attribute) {
            $prop[$name] = (string) $attribute;
        }
        return $prop;
    }

    private function _insertCode($objXML, $fieldProp) {
        /* $fieldProp não é usado, só esta aqui porque o metodo faz a chamada de forma padrão */
        $fieldProp = null;
        $this->_objForm->insertCode($objXML->code);
    }

    private function _insertInput($objXML, $fieldProp) {
        $label = (string) $objXML->label;
        $mask = (string) $objXML->mask;
        if (!$mask)/* Se mascara não foi definida, define mascara a partir do tipo de campo determinado */
            $mask = $this->_input_defineMask($objXML);
        $value = $this->_defaults_values_insertField($objXML, (string) $objXML->value);
        $this->_objForm->insertInput($fieldProp['name'], $value, $label, $fieldProp, $mask);
    }

    private function _insertPassword($objXML, $fieldProp) {
        $label = (string) $objXML->label;
        $mask = (string) $objXML->mask;
        $value = $this->_defaults_values_insertField($objXML, (string) $objXML->value);
        $this->_objForm->insertPassword($fieldProp['name'], $value, $label, $fieldProp, $mask);
    }

    private function _insertInputDatepicker($objXML, $fieldProp) {
        /* Busca dados de formatação */
        $objLocalFormats = new local_formats();
        $xmlLocalFormats = $objLocalFormats->xmlData;
        $language = $this->_xml->language;
        $subtype = (string) $objXML->format_date->subtype;
        /* Busca variaveis padrão e efetua procedimento de criação de componente */
        $label = (string) $objXML->label;
        $value = $this->_defaults_values_insertField($objXML, (string) $objXML->value);
        $mask = (string) $xmlLocalFormats->$language->date->$subtype;
        if(!$mask){
            $mask = $subtype;
        }
            
        $mask = str_replace(array('d', 'm', 'Y','D', 'M', 'Y'), array('dd', 'mm', 'yy','DD','MM','YY'), $mask);
        $mask = str_replace(array('\dd','\mm','\yy','\DD','\MM','\YY'),array("'d'","'m'","'y'","'D'","'M'","'Y'"),$mask);
        $this->_objForm->insertInputDatepicker($fieldProp['name'], $value, $label, $fieldProp, $mask);
    }

    private function _insertTextarea($objXML, $fieldProp) {
        /* Se ckeditor estiver habilitado, retorna propriedades e envia o mesmo para o objeto */
        $ckeditorConfig = null;
        if ($objXML->ckeditor->enabled== "true") {
            $ckeditorConfig = $this->_ckeditorConfig($objXML->ckeditor->config);
        }
        /* Cria elemento com rotina padrão */
        $label = (string) $objXML->label;
        $value = $this->_defaults_values_insertField($objXML, (string) $objXML->value);
        $this->_objForm->insertTextarea($fieldProp['name'], $value, $label, $fieldProp, $ckeditorConfig);
    }

    private function _insertButton($objXML, $fieldProp) {
        $label = (string) $objXML->label;
        $value = (string) $objXML->value;
        $this->_objForm->insertButton($fieldProp, $value, $label);
    }

    private function _insertJquery_fileupload($objXML, $fieldProp) {
        $label = (string) $objXML->label;
        $value = (string) $objXML->value;
        $config = array(
            'upload_folder' => (string) $objXML->file->folder,
            'upload_script' => (string) $objXML->file->upload_script,
            'fileExt' => (string) $objXML->file->fileExt,
            'fileDesc' => (string) $objXML->file->fileDesc,
            'link_msg' => (string) $objXML->link_msg,
            /* configurações de imagem */
            'image' => array(
                'imagesx' => (string) $objXML->image->imagesx,
                'imagesy' => (string) $objXML->image->imagesy,
                'image_size' => (string) $objXML->image->image_size
            )
        );
        $this->_objForm->insertJquery_fileupload($fieldProp['name'], $value, $label, $fieldProp, $config);
    }

    private function _insertInput_autoComplete($objXML, $fieldProp) {
        $label = (string) $objXML->label;
        $mask = (string) $objXML->mask;
        $value = $this->_defaults_values_insertField($objXML, (string) $objXML->value);
        $name = (string) $objXML['name'];
        $rel_column = (string) $objXML->relate->rel_key;
        if ($value == '$_LAST')
            $value = $this->_mysql_selectboxLastInsert($objXML, $name);
        $json_source = (string) $objXML->json_source;
        $this->_objForm->insertInput_autoComplete($name, $value, $label, $fieldProp, $mask, $json_source, $rel_column);
    }

    private function _insertMysql_selectbox($objXML, $fieldProp) {
        $label = (string) $objXML->label;
        $fieldName = (string) $fieldProp['name'];
        /* Adiciona array do selectbox normal e do mysql */
        $mysql_data = $this->_sqlArrSelectbox($objXML);
        $xml_data = $this->_arrSelectbox($objXML);
        $data = array_merge($xml_data, $mysql_data);
        if (!$data) {
            $data[] = array('label' => 'Sem dados...', 'value'=> '');
        }
        $valSel = (string) $objXML->value;
        if ($valSel == '$_LAST')
            $valSel = $this->_mysql_selectboxLastInsert($objXML);
        $this->_objForm->insertSelectbox($fieldName, $data, $valSel, $label, $fieldProp);
    }

    /**
     *  Sintaxe XML (Selectbox)
     * <field name='%NOME%'>
     *      <field_type>selectbox</field_type>
     *      <post>true</post>
     *      <item>
     *          <label>%LABEL%</label>
     *          <value>%VALUE%</value>
     *          <selected>true</selected>
     *      </item>
     *      <item>
     *          <label>%LABEL%</label>
     *          <value>%VALUE%</value>
     *      </item>
     * </field>
     */
    private function _insertSelectbox($objXML, $fieldProp) {
        $label = (string) $objXML->label;
        $fieldName = (string) $fieldProp['name'];
        $data = $this->_arrSelectbox($objXML);
        $idSel = $this->_getSelectedSelectbox($objXML);
        $valSel = (string) $objXML->item[$idSel]->value;
        $this->_objForm->insertSelectbox($fieldName, $data, $valSel, $label, $fieldProp);
    }

    private function _arrSelectbox($objXML) {
        /* TODO: Este ainda não pega dados de updateData. Verificar se pega de postData */
        $arrSBox = array();
        for ($i = 0; $i < count($objXML->item); $i++) {
            $value = (string) $objXML->item[$i]->value;
            $label = (string) $objXML->item[$i]->label;
            /*Caso label não seja aplicado, define como label do item o próprio value*/
            if(!$label && $value) $label = $value;
            $arrSBox[] = array(
                'value' => $value,
                'label' => $label
            );
        }
        return $arrSBox;
    }

    private function _mysql_selectboxLastInsert($objXML, $field = '') {
        $Mysql = new mysql();
        $table = (string) $objXML->table;
        if (!$table)
            $table = $objXML->relate->table;
        $pri_key = $Mysql->get_primary_key($table);
        if (!$field) {
            $field = $pri_key;
        }
        $arrCommands = array(
            "orderby_field" => $pri_key,
            "orderby_descasc" => "DESC",
        );
        $data = $Mysql->get_data($table, array(), $arrCommands);
        return $data[$field];
    }

    private function _sqlArrSelectbox($objXML) {
        /* TODO: Este ainda não pega dados de updateData. Verificar se pega de postData */
        $arrSBox = array();
        $table = (string) $objXML->table;
        $order_column = (string) $objXML->order_by->column;
        $order_desasc = (string) $objXML->order_by->order;
        $mysql = new mysql();
        $sqlData = $mysql->simple_search($table, null, 'or', '=', null, null, $order_column, $order_desasc);
        $valCollumns = (string) $objXML->val_collumns;
        $lblCollumns = (string) $objXML->lbl_collumns;
        while ($data = mysql_fetch_array($sqlData)) {
            $value = $data[$valCollumns];
            $label = $data[$lblCollumns];
            $arrSBox[] = array(
                'value' => $value,
                'label' => $label
            );
        }
        return $arrSBox;
    }

    private function _getSelectedSelectbox($objXML) {
        $sId = null;
        for ($i = 0; $i < count($objXML->item); $i++) {
            if ($objXML->value) {
                if ((string) $objXML->item[$i]->value == (string) $objXML->value) {
                    $sId = $i;
                }
            } elseif ($objXML->item[$i]->selected == 'true') {
                $sId = $i;
            }
        }
        return $sId;
    }

    private function _ckeditorConfig($xmlConfig) {
        $config = array();
        foreach ($xmlConfig as $config_item) {
            $config[(string) $config_item->key] = (string) $config_item->value;
        }
        return $config;
    }

    /**
     * Insere campo hidden de relacionamento, relativo ao campo que sera inserido
     * na tabela quando o mesmo for salvo pelo sistema.
     */
    private function _relId_hiddenField($rel_id, $formName, $value = null) {
        ob_start();
        /* Captura buffer */
        ?>
        <script type="text/javascript">
            $(function(){
                if(!$("form[name=<?= $formName ?>] input[name='<?= $rel_id ?>']").length){
                    $('<input>').attr({
                        type : 'hidden',
                        name : '<?= $rel_id ?>',
                        value: '<?= $value ?>'
                    }).appendTo("form[name=<?= $formName ?>]");
                }
                                
            });
        </script>
        <?
        /* Coloca buffer em arquivo e não imprime o mesmo */
        $contents = ob_get_contents();
        ob_end_clean();
        $this->_objForm->insert_header($rel_id, $contents);
    }

    private function _str_date_today($ObjXML) {
        $today = date("Y-m-d");
        $Local_formats = new local_formats();
        return $Local_formats->date_to_local_str($today, (string) $ObjXML->format_date->subtype, (string) $this->_xml->language);
    }

    /* Insere caso valor seja apontador para valores padrões, insere um destes valores */

    private function _defaults_values_insertField($ObjXML, $value) {
        if ($value == '$_DATE_TODAY')
            $value = $this->_str_date_today($ObjXML, $value);
        return $value;
    }

    /**
     * Define mascara para meiomask baseado nas configurações de formatação passadas
     * pelo XML. (Caso a mascara não tenha sido definida este define a partir do 
     * formato de dados mysql.
     */
    private function _input_defineMask($objXML) {
        if ($objXML->format_real->enabled == 'true')
            return "'decimal'";
        elseif ($objXML->format_integet->enabled == 'true')
            return "'integer'";
    }

}
?>
