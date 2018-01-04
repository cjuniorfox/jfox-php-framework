<?php

/**
 * FORM V1.0
 * 
 * -Cria e gerencia formularios, criado para trabalhar com objeto xmlForms
 *
 * @author juniorfox
 */
class form {

    private $_form = array(
        'js' => array(
            0 => array('js_code' => '')
        )
    ); /* Array com os forms criados */
    private $_formName; /*Nome do Form*/
    private $_arrHeader = array(); /* Array pertinente ao cabeçalho da brincadeira */
    private $_CkEditor; /* Objeto CkEditor para campos de texto formatados */
    private $_lib; /*Diretorio de bibliotecas usadas pelo objeto Form*/

    
    public function __construct($formName){
        $this->_formName = $formName;
        $this->_lib = $this->_lib = $this->_pluginDir = str_replace(DIRECTORY_SEPARATOR, '/', realpath(dirname(__FILE__))) . "/form/";
    }
    /**
     * Retorna o formulario desejado (Quando formName declarado) ou varios forms
     * 
     * @param string $formName - Nome do formulario que deseja ser retornado
     */
    public function getForm($form_properties) {
        if (isset($form_properties['action'])) {
            $form_properties['action'] = urldecode($form_properties['action']);
        }
        $this->_createForm($form_properties);
        $this->_form['headers'] = "";
        if ($this->_arrHeader) {
            $this->_form['headers'] = $this->_headers();
        }
        return $this->_form;
    }

    /**
     * Insere um header ao form
     * @param string $header_name - Nome do header a ser inserido
     * @param string $value - Valor do Header a ser inserido
     * @param boolean $overvrite - Se TRUE, caso header ja exista, sobrepõe header
     * existente. DEFAULT FALSE
     * @return boolean - Se header pré-existente, retorna true, caso contrario, retorna false 
     */
    public function insert_header($header_name,$value,$overvrite = FALSE){
        $exists = false;
        if(isset($this->_arrHeader[$header_name])) $exists = true;
        if($overvrite || !$exists) $this->_arrHeader[$header_name] = $value;
        return $exists;        
    }
    
    /* Neste trecho são tratados todos os campos que serão inseridos */

    public function insertCode($code) {
        $data['field'] = $code;
        $data['label'] = "";
        $data['name'] = "";
        $this->_form['form_data'][] = $data;
    }

    public function insertInput($name, $value = null, $label = null, $properties = array(), $mask = null) {
        if ($mask) {
            $this->_form['js'][]['js_code'] = $this->_createMeiomask($name, $mask);
        }
        $this->_insertInputFields($name, $value, $label, $properties);
    }
    
    public function insertInput_autoComplete($name, $value = null, $label = null, $properties = array(), $mask = null,$json_source = '',$rel_id = ''){
        include_once($this->_lib."form_input_autoComplete.php");
        $Input_AC = new form_input_autoComplete();
        $this->_form['js'][]['js_code'] = $Input_AC->js_code($name,$rel_id,$json_source,$this->_formName);
        $this->insertInput($name, $value, $label, $properties, $mask);
    }

    public function insertPassword($name, $value = null, $label = null, $properties = array(), $mask = null) {
        if ($mask) {
            $this->_create_js_mask($properties['id'], $mask);
        }
        $properties['type'] = 'password';
        $this->_insertInputFields($name, $value, $label, $properties);
    }

    public function insertInputDatepicker($name, $value = null, $label = null, $properties = array(), $mask = null) {
        $css_element_id = $this->_get_css_element_id($name, 'input', $properties);
        $this->_form['js'][]['js_code'] = "$(\"$css_element_id\").datepicker({changeMonth:true, changeYear:true, dateFormat: \"$mask\"});";
        $properties['readonly'] = 'readonly';
        $this->_insertInputFields($name, $value, $label, $properties);
    }

    /**
     * 
     * Responsavel por inserir o campo TextArea.
     * Este também pode inserir um campo utilizando o recurso ckeditor (editor formatavel).
     * 
     * @param string $name - Nome do componente
     * @param string $value - Valor padrão do componente
     * @param string $label - Label do componente
     * @param array $properties - Propriedades do componente
     * @param array $ckeditorConfig - Configurador da ferramenta ckeditor. Quando setada, criar como ckeditor.
     */
    public function insertTextarea($name, $value = null, $label = null, $properties = array(), $ckeditorConfig = array()) {
        if (!isset($properties['id'])) {
            $properties['id'] = $name;
        }
        
        if ($ckeditorConfig) {
            $data['field'] = $this->_ckeditor($name, $ckeditorConfig, $value);
            $this->_arrHeader[] = $this->_ckeditor_js($name);
        }else
            $data['field'] = "<textarea " . $this->_propertiesToStr($properties) . ">$value</textarea>";
        $data['label'] = $this->_label($name, $label);
        $data['name'] = $name;
        $this->_form['form_data'][] = $data;
    }

    public function insertButton($properties, $value = null, $label = null) {
        if (!isset($properties['name']))
            $properties['name'] = 'submit';
        if (!isset($properties['type']))
            $properties['type'] = 'submit';
        $this->_insertInputFields($properties['name'], $value, $label, $properties);
    }

    public function insertJquery_fileupload($name, $value = null, $label = null, $properties = null, $fileupload_config = null) {
        if (isset($properties['id']))
            $id = $properties['id'];
        else
            $id = $name;
        $objAmbientVars = new ambient_vars();

        /* Carrega o objeto que inicializa o Jquery_FileUpload */
        $objJF = new jquery_fileUpload($objAmbientVars->website_public_path() . "resources/uploadify/");
        $objJF->upload_folder = $fileupload_config['upload_folder'];
        if ($fileupload_config['upload_script']) {
            $objJF->upload_script = $fileupload_config['upload_script'];
        }

        /* Define configurações de extensão e Descrição da Imagem */
        if ($fileupload_config['fileExt']) {
            $objJF->config['fileExt'] = $fileupload_config['fileExt'];
        }
        if ($fileupload_config['fileDesc']) {
            $objJF->config['fileDesc'] = $fileupload_config['fileDesc'];
        }

        /* Se existirem definições de imagem, as aplica como scripts */
        $imgConfigs = array();
        if ($fileupload_config['image']) {
            if ($fileupload_config['image']['imagesx'])
                $imgConfigs[] = "'imagesx':'" . $fileupload_config['image']['imagesx'] . "'";
            if ($fileupload_config['image']['imagesy'])
                $imgConfigs[] = "'imagesy':'" . $fileupload_config['image']['imagesy'] . "'";
            if ($fileupload_config['image']['image_size']) {
                $imgConfigs[] = "'image_size':'" . $fileupload_config['image']['image_size'] . "'";
                $objJF->config['sizeLimit'] = $fileupload_config['image']['image_size'];
            }
            $objJF->config['scriptData'] = "@@{" . implode(",", $imgConfigs) . "}";
        }

        /* Cria elemento */
        $data['label'] = $this->_label($name, $label);
        $data['field'] = $objJF->insert_field($name, $id, (string) $fileupload_config['link_msg'], $value, '{SITE_PATH}');
        $data['name'] = $name;
        $this->_form['form_data'][] = $data;
    }

    public function insertSelectbox($name, $content = array(), $valSel = '', $label = null, $properties = array()) {
        if(!$content){ /*Se não foi passado conteúdo, retorna conteúdo em branco*/
            $content[] = array("label"=>"Sem dados...","value"=>"");
        }
        if (!isset($properties['id']))
            $properties['id'] = $name;
        $properties['name'] = $name;


        $data['field'] = "<select " . $this->_propertiesToStr($properties) . ">" . $this->selectboxFeeder($content, $valSel) . "</select>";
        $data['label'] = $this->_label($name, $label);
        $data['name'] = $name;
        $this->_form['form_data'][] = $data;
    }

    /**
     *  Retorna dados a serem inseridos dentro de um Selectbox qualquer. Pode
     * inserir dados em um selectbox criado pelo objeto como pode inserir
     * dados em um selectbox controlado por ajax ja impresso em um formulario.
     * Formatacao: array('valor'=>'label')
     */
    public function selectboxFeeder($content = array(), $valSel = '') {
        $buffer = null;
        $selected = null;
        foreach ($content as $item) {
            if ($valSel == $item['value']) { /* Se o valor inserido for o que deve ser selecionado, escreve a tag HTML */
                $selected = "selected=\"selected\"";
            }
            $buffer .= "<option value=\"$item[value]\" $selected >$item[label]</option>";
            $selected = null;
        }
        return $buffer;
    }

    /**
     * Cria a instancia na variavel $_form, adiciona propriedades padrão e
     * propriedades relativas ao formulario
     * 
     * @param string $formName - Nome do formulário
     * @param string
     */
    private function _createForm($form_properties) {
        /* Define name e as propriedades padrão de um form */
        $default_properties = array(
            'name' => $this->_formName,
            'method' => 'POST',
            'action' => '#'
        );
        foreach (array_keys($default_properties) as $prop_key) {
            if (!isset($form_properties[$prop_key])) {
                $form_properties[$prop_key] = $default_properties[$prop_key];
            }
        }
        $this->_form['arrForm_properties'] = $form_properties;
        $this->_form['form_properties'] = $this->_propertiesToStr($form_properties);
    }

    /**
     * Insere todos os campos de tag input
     */
    private function _insertInputFields($name, $value, $label, $properties) {
        $data = array();
        if (!isset($properties['type']))
            $properties['type'] = 'text';
        if ($name) {
            $properties['name'] = $name;
        }
        if ($value) {
            $properties['value'] = $value;
        }
        if (!isset($properties['id']) && $name)
            $properties['id'] = $name;
        $data['label'] = $this->_label($name, $label);
        $data['field'] = "<input " . $this->_propertiesToStr($properties) . "/>";
        $data['name'] = $name;
        $this->_form['form_data'][] = $data;
    }

    private function _ckeditor($id, $config, $value = "") {
        if (!$this->_CkEditor) {
            $ambient_vars = new ambient_vars();
            $this->CkEditor = new CKEditor($ambient_vars->website_public_path() . "resources/ckeditor/");
            $this->CkEditor->returnOutput = true;
        }
        /* Envia as configs para o CKEditor */
        return $this->CkEditor->editor($id, $value, $config);
        //return $this->CkEditor->replace($id, $config);
    }

    private function _ckeditor_js($name) {
        ob_start();
        ?>
        <script type="text/javascript">
            $(function(){  
                $('form').submit(function(event){
                    event.preventDefault();
                   // alert(CKEDITOR.instances.<?= $name ?>.getData());
                   var value = CKEDITOR.instances.<?= $name ?>.getData();
                  // CKEDITOR.instances.<?= $name ?>.destroy();
                   $("textarea[name='<?=$name?>']").val(value);
                });
            })
        </script>
        <?
        $contents = ob_get_contents();
        ob_end_clean();
        return $contents;
    }

    /**
     * Verifica se label existe e insere o mesmo
     * @return string - Codigo fonte do label e ser aplicado
     * 
     */
    private function _label($name, $label) {
        if ($name && $label)
            return "<label for=\"$name\">$label</label>";
        else
            return '';
    }

    private function _headers() {
        $buffer = null;
        foreach ($this->_arrHeader as $line) {
            $buffer .=$line . "\n";
        }
        return $buffer;
    }

    private function _createMeiomask($name, $mask) {
        if (!isset($this->arrHeader['js_mask'])) {
            $this->_arrHeader['js_mask'] = "<script src='/*SITE_PUBLIC_PATH*/resources/jquery.meiomask/js/meiomask.js'></script>";
        }
        $css_element_id = $this->_get_css_element_id($name, 'input', $properties);
        return "$(\"$css_element_id\").setMask($mask);";
    }

    private function _propertiesToStr($properties) {
        $buffer = null;
        if (is_array($properties)) {
            foreach (array_keys($properties) as $fieldKey) {
                $buffer .= $fieldKey . '="' . $properties[$fieldKey] . '" ';
            }
        }
        return $buffer;
    }
    
    private function _get_css_element_id($name, $element, $properties, $use_form_name = true){
        $id_element = null;
        if(isset($properties['id']))
            $id_element = "#".$properties['id'];
        elseif(isset($name))
            $id_element = $element."[name='$name']";
        if($this->_formName && $use_form_name)
            return "form[name='$this->_formName'] $id_element";
        return $id_element;
    }

}
?>
