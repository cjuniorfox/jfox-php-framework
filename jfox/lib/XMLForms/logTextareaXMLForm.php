<?php

/**
 * Este cria um textarea utilizando CKEditor no formato de interação de dados
 * ideal para inserir textos com interações.
 *
 * @author cjuniorfox
 */
class logTextareaXMLForm extends textareaXMLForm {

    const editor_width = "850";
    const editor_height = "250";
    const button_value = 'Adicionar';
    const resource_file = 'logtextarea/logtextarea.css';

    public $request_primary_key = true;

    public function createField() {
        $this->_add_ckeditor_to_XML(); //Transforma o editor em um editor nos moldes desejados
        parent::createField(); //Deixa o textarea criar o campo.
        
        $this->HEADER .= $this->_header(); //Adiciona ao header já existente novos dados.
        $this->HTML = $this->_html(); //Sobrescreve HTML ja existente.
        $this->JS .= $this->_js(); //Adiciona JS ao código já existente
    }
    
    /**
     * Adiciona ao header a URL do CSS deste objeto.
     */
    private function _header(){
        $resources_path = ambient_vars::website_public_path() . "resources/";
        $resources_file = $resources_path.self::resource_file;       
        return "\n<link href=\"$resources_file\" rel=\"stylesheet\" type=\"text/css\"/>";
    }
    
    private function _html(){
        $button_value = self::button_value;
        $field_name = (string) $this->XMLField['name'];
       
        
        if ($this->XMLField->button_value)
            $button_value = (string) $this->XMLField->button_value;
        $out = "<div class=\"logTextareaBox\">";
        $out .=      "$this->HTML";
        $out .=      "<input id=\"logTextareaBox-input$field_name\" class=\"logTextareaBox-input\" type=\"text\" value=\"\"/>";
        $out .=      "<input id=\"logTextareaBox-button$field_name\" class=\"logTextareaBox-button\" type=\"button\" value=\"$button_value\"/>";
        $out .= "</div>";
        return $out;
    }

    private function _js() {
        $field_name = (string) $this->XMLField['name'];
        $form_name = $this->form_name;
        $button = "$('#logTextareaBox-button$field_name')";
        $input_text = "$('#logTextareaBox-input$field_name')";
        $CKEDITOR = "CKEDITOR.instances['$field_name']";
        $function_click = $this->_function_click($CKEDITOR, $input_text);
        $function_enterKey = $this->_function_enterKey($button);
        
        //Codigo JS
        $out = "CKEDITOR.on('instanceReady',function(){ $input_text.css('width', $CKEDITOR.config.width - $button.width() - 20); });";
        $out .=     "$button.click($function_click);";
        $out .=     "$input_text.keypress( $function_enterKey);";
        $out .=     "";
        $out .= "$('form[name=\"$form_name\"]').bind('remove',function(){ CKEDITOR.remove($field_name); });";
        return $out;
    }

    /**
     * Executa o Javascript que insere o valor digitado no campo
     * dentro do CKEditor textbox.
     * Este analisa a chave do XML insert_type.
     * Se este for 'below', o novo conteúdo será inserido abaixo
     * do texto original.
     * Já se este for 'above', o novo conteúdo será inserido acima
     * do texto original.
     * O padrão é above.
     */
    private function _function_click($CKEDITOR, $input_text) {
        $other_variables = $this->_add_other_variables();
        $insert_variables = "$other_variables '<strong>&gt;</strong> ' + $input_text.val()"; //Variaveis de texto a serem inseridas
        $function_autosave = "";
        if($this->XMLField->options->autosave == 'true' && $this->primary_key_value) //Só gera autosave se estas duas opções estiverem ok
            $function_autosave = $this->_function_autosave($CKEDITOR);
        
        $insert_type = "$CKEDITOR.setData($insert_variables + '<br />' + $CKEDITOR.getData());"; //above
        if ($this->XMLField->options->insert_position == 'below')
            $insert_type = "$CKEDITOR.setData($CKEDITOR.getData() + '<br />' + $insert_variables);";
        $out = "function(e){";
        $out .=     "e.preventDefault();";
        $out .=     "if($input_text.val()){";
        $out .=         "$CKEDITOR.setReadOnly(false);";
        $out .=         "setTimeout(function(){";
        $out .=             "var myDate = new Date();";
        $out .=             "var curr_hour = myDate.getHours();";
        $out .=             "var curr_min = myDate.getMinutes();";
        $out .=             "curr_min = curr_min + '';";
        $out .=             "if (curr_min.length == 1) curr_min = '0' + curr_min;";
        $out .=                 "$CKEDITOR.focus();";
        $out .=             "if($CKEDITOR.getData() == '')";
        $out .=                 "{ $CKEDITOR.setData($insert_variables) }";
        $out .=             "else";
        $out .=                 "{ $insert_type }";
        $out .=             "$input_text.val('');";
        $out .=             "$input_text.focus();";
        $out .=             "$CKEDITOR.setReadOnly(true);";
        $out .=             $function_autosave;              
        $out .=         "},500);";
        $out .=     "}else{";
        $out .=         "$input_text.focus();";
        $out .=     "}";
        $out .= "}";
        return $out;
    }

    private function _function_enterKey($button) {
        $out = "function(e){";
        $out .=     "if(e.which == 13) {";
        $out .=         "e.preventDefault();";
        $out .=         "$button.focus();";
        $out .=         "$button.click();";
        $out .=     "}";
        $out .="}";
        return $out;
    }
    
    private function _function_autosave($CKEDITOR){
        $form_name = $this->form_name;
        $field_name = (string) $this->XMLField['name'];
        $id_reg = $this->primary_key_value;

        
        $post_data[] = "data:$CKEDITOR.getData()";
        $post_data[] = "form:'$form_name'";
        $post_data[] = "id:'$id_reg'";
        $post_data[] = "field:'$field_name'";
        
        $post_url = "{SITE_PATH}{controller_name}/logTextarea_autosave";

        $out =     "setTimeout(function(){";
        $out .=         "$.post('$post_url',{".implode(",",$post_data)."},function(json){";
        //$out .=             "alert(json);";
        $out .=         "});";
        $out .=     "},500);";

        
        return $out;
    }

    private function _add_ckeditor_to_XML() {
        $width = self::editor_width;
        $height = self::editor_height;
        //Antes remove qualquer ckeditor pre-existente.
        if (isset($this->XMLField->ckeditor)) {
            foreach ($this->XMLField->ckeditor->config as $config) {
                if ($config->key == "width")
                    $width = (string) $config->value;
                elseif ($config->key == "height")
                    $height = (string) $config->value;
            }
            unset($this->XMLField->ckeditor);
        }


        $ckeditor = $this->XMLField->addChild("ckeditor");
        $ckeditor->addChild("enabled", "true");

        $config = $ckeditor->addChild("config");
        $config->addChild("key", "toolbar_Custom");
        $config->addChild("value", "{}");

        $config = $ckeditor->addChild("config");
        $config->addChild("key", "toolbar");
        $config->addChild("value", "Custom");

        $config = $ckeditor->addChild("config");
        $config->addChild("key", "toolbar");
        $config->addChild("value", "Custom");

        $config = $ckeditor->addChild("config");
        $config->addChild("key", "width");
        $config->addChild("value", $width);

        $config = $ckeditor->addChild("config");
        $config->addChild("key", "height");
        $config->addChild("value", $height);

        $config = $ckeditor->addChild("config");
        $config->addChild("key", "enterMode");
        $config->addChild("value", "2");

        $config = $ckeditor->addChild("config");
        $config->addChild("key", "readOnly");
        $config->addChild("value", "true");
    }

    /**
     * Adiciona outras variaveis como hora e data, pessoa logada ao logTextArea
     */
    private function _add_other_variables() {
        $out = "";
        //Adiciona inicialmente as Session_name
        @session_start();
        if ($this->XMLField->options->insert_date == 'true')
            $out .= "'<span style=\'color:#26b538;\'><strong>' + myDate.getDate() + '/' + (myDate.getMonth() + 1) + '/' + myDate.getFullYear() + '</strong></span> ' +";
        if ($this->XMLField->options->insert_time == 'true')
            $out .= "'<span style=\'color:#26b538;\'><strong>' + curr_hour + ':' + curr_min + '</strong></span> ' +";
        foreach ($this->XMLField->options->session_name as $session_name) {
            if (isset($_SESSION[(string) $session_name])) {
                $session_name = $_SESSION[(string) $session_name];
                $out .="'| <span style=\'color:#184d92;\'><strong>$session_name </strong></span> ' +";
            }
        }
        return $out;
    }
    
    
    
    
    /**
     * Método estático
     * Utilizado pelo objeto com objetivo de salvar automaticamente o conteúdo da janela de chat
     * Só funciona quando é passado o ID da postagem. Se não tiver ID, o autosalvar não funciona.
     * @param string $xmlFile - Arquivo XML que contém o campo
     * @param string $form_name - Nome do formulário usado
     * @param string $field_name - Nome do campo
     * @param string $reg_id - ID do registro a ser atualizado.
     * @param string $post_data - Conteúdo a ser salvo.
     * 
     * @return string - (OK, noChange ou NULL);
     */
    public static function autosave($xmlFile,$form_name,$field_name,$reg_id,$post_data){
        if(!$xmlFile || !$form_name || !$field_name || !$reg_id || !$post_data)
            return null;
        $XMLFF = new XMLForms_fields($xmlFile,$form_name);//Inicia XMLForms_fields.
        $XMLField = $XMLFF->XMLField($field_name);
        $table = (string) $XMLFF->XMLForm->table;
        $primary_key = $XMLFF->primary_key();
        $XMLFR = new XMLForms_reader($xmlFile,$form_name);//Inicia também o XMLForms_reader.
        $reg = $XMLFR->readunformated($reg_id); //Carrega registro para verificar se o mesmo existe ou foi alterado.
        if(!$reg)
            return NULL;

        $post_data = rtrim(stripslashes($post_data));
        $compare_data = rtrim(stripslashes($reg[$field_name]));
        if($XMLField->options->autosave != "true")
            return NULL;
        //Verifica se o autosave está habilitado
        if($XMLField->relate->enabled == "true")
            return self::_autosave_table_related($XMLField,$reg,$post_data);
        //Verifica se existe esta coluna no campo a ser processado.
        if(array_key_exists($field_name, $reg) === FALSE) //Se não existir o registro, retorna sem salvar.
            return NULL;
        //Verifica se por acaso ele não irá atualizar mais de um cadastro
        if($XMLFR->filter_and_totals(array($primary_key=>$reg_id)) > 1)
            return 'manyForms';
        //Verifica se o conteúdo foi modificado
        if(rtrim($compare_data) == rtrim($post_data))
            return "noChange";
        $Mysql = new mysql();
        $Mysql->update($table, array($field_name=>$post_data), array($primary_key=>$reg_id));
        return "OK";
    }
    
    private static function _autosave_table_related($XMLField,$reg,$post_data){
        if($XMLField->relate->enabled != "true" || !$reg || !$post_data) //Precisa de todos os campos para executar
            return NULL;
        $table = (string) $XMLField->relate->table;
        $field_name = (string) $XMLField['name'];
        $primary_key = (string) $XMLField->relate->primary_key;
        $rel_field = (string) $XMLField->relate->rel_field;
        if(!$table || !$field_name || !$primary_key)
            return null;
        $arrSearch = array($primary_key=>$reg[$rel_field]);
        $Mysql = new Mysql();
        $rel_reg = $Mysql->simple_data($table, $arrSearch); 
        ////Carrega os dados da tabela para comparar se existem e verificar se os dados novos sao diferentes dos já armazenados
        if(!$rel_reg)
            return NULL;
        elseif(isset($rel_reg))
            return NULL;
        elseif(rtrim($reg[$field_name]) == rtrim(str_replace("\\\"","\"",$post_data))) //Se os valores forem iguais, retorna sem salvar.
            return NULL;
        //Se passou em todas as verificações, salva
        $Mysql->update($table, array($field_name=>rtrim(str_replace("\\\"","\"",$post_data))), $arrSearch);
        return 'ok';
    }

}

?>