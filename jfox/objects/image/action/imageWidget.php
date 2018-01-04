<?php
/*
 * Trabalha com imagens em geral, carregando-as e postando as mesmas.
 * Depende diretamente da biblioteca image_manager.php
 */

/**
 *
 * @author Junior_FOX
 */
class imageWidget extends widgetController {

    private $_imageAdress;
    private $_imgString;
    private $_imageUrl;

    protected function _set_default_vars(){
        $this->set_default_widget_var('idName', 'imageWidget');/*Nome padrão do formulário que envia as imagens*/
        $this->set_default_widget_var('null','null'); /*Linha apenas de exemplo*/
        $this->set_default_widget_var('readOnly', TRUE);/*No modo readOnly apenas lista o arquivo, não faz o upload do mesmo*/
        $this->set_default_widget_var('useForm', TRUE);/*Usar form proprio? Marcar como False, caso queira compartilhar o form com outros campos no formulario */
        $this->set_default_widget_var('formProps', $this->_set_default_form_props());/*Array com propriedades padrão do form*/
        $this->set_default_widget_var('imgProps', array());
        $this->set_default_widget_var('imgpath', $this->global_vars['images_path']); /*Diretorio padrao das imagens*/
        $this->set_default_widget_var('imageX', '300');/*Resolução X máxima*/
        $this->set_default_widget_var('imageY', '300');/*Resolução Y máxima*/
        $this->set_default_widget_var('fileSize', 1048576);/*Tamanho máximo de arquivo a ser enviado para o servidor*/
    }

    protected function _check_error_vars(){
        $error = $this->test_widget_var('null'); /*Linha apenas de exemplo*/
        $error.= $this->test_widget_var('fileName');/*Nome do arquivo de imagem*/
        $error.= $this->test_widget_var('name');/*Nome do widget, usado para identifica-lo durante a postagem*/
        return $error;
    }

    private function _set_default_form_props(){/*define a propriedade padrão do form usado para envio de imagens*/
        return array(
            'name' => $this->widget_vars['idName'].'form',
            'id' => $this->widget_vars['idName'].'form',
            'method' => 'POST',
            'action' => '#'
        );
    }

    /*Adiciona uma propriedade fundamental para o upload de imagens ao formulario*/

    private function _add_props_form(){
        $this->widget_vars['formProps']['enctype'] = "multipart/form-data";
    }

    public function action(){
        $this->_set_image_adress();
        $this->_add_props_form();
        $aData['error'] = $this->_post_image();
        $aData['formProps'] = $this->_props_to_str('formProps');
        $aData['name'] = $this->widget_vars['name'];
        $aData['image'] = $this->_load_image();
        $aData['__FILE'] = $this->_set_template();
        $aData['fileField'] = $this->widget_vars['idName'];
        $this->array_data = $aData;
    }

    private function _set_image_adress(){
        $this->_imageAdress = $this->widget_vars['imgpath'].$this->widget_vars['fileName'];
        return $this->_imageAdress;
    }

    private function _set_image_url(){
        $imageAdress = $this->_set_image_adress();
        $this->_imageUrl = $this->global_vars['site_path'].$imageAdress;
        return $this->_imageUrl;
    }

    private function _props_to_str($propName){
        $str = null;
        $props = $this->widget_vars[$propName];
        if($props){
            foreach(array_keys($props) as $propKey){
                $str.= " $propKey = \"$props[$propKey]\"";
            }
        }
        $this->_imgString = $str;
        return $str;
    }


    private function _load_image(){
        $imageUrl = $this->_set_image_url();
        $imageProps = $this->_props_to_str("imgProps");
        if(file_exists($this->_imageAdress)){
            $imgString = "<img src= \"$imageUrl\" $imageProps />";
        }else{
            $imgString = "<div $imageProps>&nbsp;</div>";
        }
        return $imgString;
    }

    private function _set_template(){
        if(!$this->widget_vars['readOnly']){
            if($this->widget_vars['useForm']){
                return $this->widget_env['template_path']."form_post.html";
            }else{
                return $this->widget_env['template_path']."post.html";
            }
        }else{
            return $this->widget_env['template_path']."index.html";
        }
    }
    
    private function _post_image(){
        $name = $this->widget_vars['name'];
        $readOnly = $this->widget_vars['readOnly'];
        if(@$_POST[$name] == $name && !$readOnly){
            return $this->_send_file();
        }
    }

    /* A _post_image depende diretamente do objeto image_manager.php*/
    private function _send_file(){
        $fileField = $this->widget_vars['idName'];
        $objImageMan = new image_manager($_FILES[$fileField], $this->_imageAdress, $this->widget_vars['fileSize']);
        $error = $objImageMan->check_post_file();
        if(!$error){
            $objImageMan->max_image_dimensions($this->widget_vars['imageX'], $this->widget_vars['imageY']);
            $objImageMan->save();
        }
        return $error;
    }
    
}

?>