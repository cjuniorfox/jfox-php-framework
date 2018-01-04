<?php

/*
 * To change this template, choose Tools | Templates
 * and open the template in the editor.
 */

/**
 * Description of jquery_fileupload
 *
 * @author cjuniorfox
 */
class jquery_fileuploadXMLForm extends Fields {
    
    const uploader = "{SITE_PATH}{controller_name}/uploadify";
    
    public function str_value($value){
        $arrOut = parent::look_and_get_related($value);
        return $arrOut;
    }
    
    public function upload_file($xmlfilepath,$form_name){
        $XMLForms_fields = new XMLForms_fields($xmlfilepath,$form_name);
        $XMLField = $XMLForms_fields->XMLField($_POST['name']);
        if($XMLField->field_type != 'jquery_fileupload')
            return 'Error';
        $folder = (string) $XMLField->file->folder;
        $imagesx = (int) $XMLField->image->imagesx;
        $imagesy = (int) $XMLField->image->imagesy;
        $image_size = (int) $XMLField->image->image_size;
        $objJF = new jquery_fileUpload();
        return $objJF->upload_image($folder,$imagesx,$imagesy,$image_size);
    }

    public function createField() {
        $properties = self::_properties();
        $objJF = new jquery_fileUpload(ambient_vars::website_public_path() . "resources/uploadify/");
        $objJF->config = self::_config();
        $objJF->upload_folder = (string) $this->XMLField->file->folder;
        //Agora cria o header e o objeto em si
        $this->HEADER = $objJF->header();
        $this->HTML = $objJF->insert_field($properties['name'], $properties['id'], $this->value, '{SITE_PATH}');      
    }
    
    /**
     * Apenas repassa o padrão.
     */
    public function mysql_property() {
        return parent::mysql_property();
    }

    private function _properties() {
        $arrP = self::getProperties($this->XMLField);
        if (!isset($arrP['id']) && isset($arrP['name']))
            $arrP['id'] = $arrP['name'];
        return $arrP;
    }

    private function _config() {
        $objXML = $this->XMLField;
        return array(
            'method' => 'post',
            'formData' => self::_uploadPostData(),
            'uploader' => self::uploader,
            'fileTypeExts' => (string) $objXML->file->fileExt,
            'fileTypeDesc' => (string) $objXML->file->fileDesc,
            'fileSizeLimit' => (string) $objXML->image->image_size."B"
        );
    }

    private function _uploadPostData(){
        $arrRequest = array();
        $name = (string) $this->XMLField['name'];
        $arrRequest[] = "'name':'" . $name . "'";
        if ($arrRequest)
                return "@@{" . implode(",", $arrRequest) . "}";
    }
    
    private function __uploadPostData($image_config) {
        $arr_jsImage = array();
        if ($image_config) {
            if ($image_config['imagesx'])
                $arr_jsImage[] = "'imagesx':'" . $image_config['imagesx'] . "'";
            if ($image_config['imagesy'])
                $arr_jsImage[] = "'imagesy':'" . $image_config['imagesy'] . "'";
            if ($image_config['image']['image_size']) {
                $arr_jsImage[] = "'image_size':'" . $image_config['image_size'] . "'";
            }
            if ($arr_jsImage)
                return "@@{" . implode(",", $arr_jsImage) . "}";
        }
    }
}

?>
