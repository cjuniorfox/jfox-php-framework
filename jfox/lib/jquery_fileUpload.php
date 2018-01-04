<?php
/*
 * To change this template, choose Tools | Templates
 * and open the template in the editor.
 */

/**
 * Description of jquery_fileUpload
 *
 * @author juniorfox
 */
class jquery_fileUpload {

    const version = "3.1";
    const buttonText = "Send File";

    /**
     * Aonde encontram-se arquivos base do jquery.fileupload
     */
    public $basePath;

    /**
     * Configurações pertinentes ao fileUpload
     */
    public $config;

    /**
     * Diretório aonde os arquivos serão enviados
     */
    public $upload_folder;
    private $_pluginDir; /* Diretorio interno contendo arquivos usados pelo jquery_fileUpload */
    private $_init = false; /* Define se cabecalho ja foi enviado ou não */

    public function __construct($basePath = null) {
        if (!empty($basePath)) {
            $this->basePath = $basePath;
        }
        $this->_pluginDir = str_replace(DIRECTORY_SEPARATOR, '/', realpath(dirname(__FILE__))) . "/jquery_fileUpload/";
    }

    public function insert_field($field_name, $field_id, $fieldValue = '', $url_path = '') {
        $this->_default_config();
        $out = $this->_init();
        $this->config['onUploadSuccess'] = $this->_js_onComplete($field_id, $url_path);
        foreach (array_keys($this->config) as $key) {
            if (strpos($this->config[$key], "@@") === 0) {
                $value = str_replace("@@", "", $this->config[$key]);
            } else {
                $value = "'" . $this->config[$key] . "'";
            }
            $data['config'][] = array(
                'key' => $key,
                'value' => $value
            );
        }
        $data['field_name'] = $field_name;
        $data['field_id'] = $field_id;
        $data['uploader_name'] = $field_name . "_uploader";
        $data['uploader_id'] = $field_id . "_uploader";
        $data['fieldValue'] = $fieldValue;
        $view = new view();
        $out.= $view->process_view($data, $this->_pluginDir . "script.html");
        return $out;
    }

    /**
     * Script padrão para upload de arquivo
     */
    public function upload_file() {
        if (!empty($_FILES)) {
            $tempFile = $_FILES['Filedata']['tmp_name'];
            $targetPath = "./" . $_REQUEST['folder'] . '/';
            $targetFile = str_replace('//', '/', $targetPath) . $_FILES['Filedata']['name'];

            // $fileTypes  = str_replace('*.','',$_REQUEST['fileext']);
            // $fileTypes  = str_replace(';','|',$fileTypes);
            // $typesArray = split('\|',$fileTypes);
            // $fileParts  = pathinfo($_FILES['Filedata']['name']);
            // if (in_array($fileParts['extension'],$typesArray)) {
            // Uncomment the following line if you want to make the directory if it doesn't exist
            // mkdir(str_replace('//','/',$targetPath), 0755, true);

            move_uploaded_file($tempFile, $targetFile);
            //echo str_replace($_SERVER['DOCUMENT_ROOT'],'',$targetFile);
            // } else {
            // 	echo 'Invalid file type.';
            // }
        }
    }

    public function upload_image($folder = null, $imagesx = null, $imagesy = null, $image_size = null) {
        if (!empty($_FILES)) {
            if (!$folder && $_REQUEST['folder'])
                $folder = $_REQUEST['folder'];
            if (!$imagesx && $_REQUEST['imagesx'])
                $imagesx = $_REQUEST['imagesx'];
            if (!$imagesy && $_REQUEST['imagesy'])
                $imagesy = $_REQUEST['imagesy'];
            if (!$image_size && $_REQUEST['image_size'])
                $image_size = $_REQUEST['image_size'];
            /* Se em momento nenhum fora definido image_size, define-se tamanho padrao de 1MB */
            if (!$image_size)
                $image_size = 1048576;
            $targetPath = "./" . $folder . '/';

            $targetFile = str_replace('//', '/', $targetPath) . $_FILES['Filedata']['name'];
            $image_manager = new image_manager($_FILES['Filedata'], $targetFile, $image_size);
            if ($imagesx || $imagesy) {
                $image_manager->max_image_dimensions($imagesx, $imagesy);
            }

            if (!$erro = $image_manager->check_post_file()) { /* Se não houver nenhum erro */
                $image_manager->save();
                return $folder . $_FILES['Filedata']['name'];
            }else
                echo $erro;
        }
        //Se algo der errado, retorna msg de erro
        return 'Invalid file type.';
    }

    public function header() {
        return $this->_init(true);
    }

    public function config($key, $value) {
        $this->config[$key] = $value;
    }

    public function clear_config() {
        $this->config = array();
        $this->_default_config();
    }

    private function _init($reinit = false) {
        if (!$this->_init || $reinit == true) {
            ob_start();
            ?>
            <link rel="stylesheet" type="text/css" href="<?= $this->basePath ?>uploadify.css">
            <script type="text/javascript" src="<?= $this->basePath ?>jquery.uploadify-<?= self::version ?>.min.js"></script>
            <script type="text/javascript" src="<?= $this->basePath ?>image_loader.js"></script>
            <?
            $headers = ob_get_clean();
            $this->_init = true;
            return $headers;
        }
        return NULL;
    }

    /**
     * Traz configurações default a ferramenta
     */
    private function _default_config() {
        $default = array(
            'swf' => $this->basePath . "uploadify.swf",
            'auto' => '@@true',
            'method' => 'post'
        );
        foreach (array_keys($default) as $key) {
            if (@!$this->config[$key]) {
                $this->config[$key] = $default[$key];
            }
        }
    }

    private function _js_onComplete($field_id, $url_path) {
        return "@@
        function(file, data, response) {
            var uploaded = '$url_path$this->upload_folder/' + file.name;
            $('#$field_id').val('$this->upload_folder/' + file.name);
            image_loader('#uploadify_$field_id .image_content', uploaded);
        }
        ";
    }

}
?>
