<?php

/*
 * To change this template, choose Tools | Templates
 * and open the template in the editor.
 */

/**
 * Gerencia imagens, como postar, checar, redimensionar, criar formularios, etc
 *
 * @author juniorfox
 */
class image_manager {

    private $_file; /* Array de arquivo em $_FILE['arquivo'] */
    private $_image_name; /* Nome da imagem a ser salva */
    private $_max_file_size; /* Tamanho maximo de arquivo em Bytes */
    private $_error; /* String com mensagens de erro */
    private $_image; /* Imagem trabalhavel pelo PHP */
    private $_imagesx; /* Resolução maxima da imagem em X */
    private $_imagesy; /* Resolução maxima da imagem em y */
    public $error_id; /* Armazena erros pelo ID do mesmo */

    public function __construct($file, $image_name, $max_file_size) {
        $this->_file = $file;
        $this->_image_name = $image_name;
        $this->_max_file_size = $max_file_size;
        $this->_error = $this->_check_post_file(); /* Checa se arquivo de imagem é válido */
    }

    public function check_post_file() {
        return $this->_error;
    }

    public function max_image_dimensions($imagesx, $imagesy = 0) {
        $this->_imagesx = $imagesx;
        $this->_imagesy = $imagesy;
    }

    public function save() {
        $this->_image = $this->_open_image();
        if ($this->_image) { /* opeen_image carrega a imagem na var $this->_image */
            imagejpeg($this->_resize_image(), $this->_image_name, 80);
        }
    }

    private function _check_post_file() {
        $error = null;
        $this->error_id = array();
        /* Verifica o mime-type do arquivo é de imagem */
        if(!getimagesize($this->_file['tmp_name'])){
            $error .= "Arquivo em formato inválido!  	A imagem deve ser jpg, jpeg, bmp, gif ou png. Envie outro arquivo";
            $this->error_id[0] = true;
        }
        //Verifica tamanho do arquivo em bytes
        if ($this->_file['size'] > $this->_max_file_size) {
            $error .= "-Arquivo muito grande<br>, envie um arquivo com no<br> maximo " . (string) (int) ($this->_max_file_size / 1024) . " Kbytes.<br />";
            $this->error_id[1] = true;
        }
        $this->_error = $error;
        return $error;
    }

    private function _open_image() {
        /* Carrega o arquivo postado, como uma imagem, para ser processada pelo PHP */
        $im = @imagecreatefromjpeg($this->_file['tmp_name']);
        if ($im !== false) {
            return $im;
        }
        $im = @imagecreatefromgif($this->_file['tmp_name']);
        if ($im !== false) {
            return $im;
        }
        $im = @imagecreatefrompng($this->_file['tmp_name']);
        if ($im !== false) {
            return $im;
        }
        $im = @imagecreatefromgd($this->_file['tmp_name']);
        if ($im !== false) {
            return $im;
        }
        $im = @imagecreatefromgd2($this->_file['tmp_name']);
        if ($im !== false) {
            return $im;
        }
        $im = @imagecreatefromwbmp($this->_file['tmp_name']);
        if ($im !== false) {
            return $im;
        }
        $im = @imagecreatefromxbm($this->_file['tmp_name']);
        if ($im !== false) {
            return $im;
        }
        $im = @imagecreatefromxpm($this->_file['tmp_name']);
        if ($im !== false) {
            return $im;
        }
        $im = @imagecreatefromstring(file_get_contents($this->_file['tmp_name']));
        if ($im !== false) {
            return $im;
        }
        return false;
    }

    private function _resize_image() {
        /* Carrega as dimensoes atuais da imagem postada */
        $actual_x = imagesx($this->_image);
        $actual_y = imagesy($this->_image);
        /* Para manter a proporcao de imagem, define tamanho de y a partir do novo tamanho x */
        if ($this->_imagesx) {
            $new_size_y = ($actual_y * $this->_imagesx) / $actual_x;
            $new_size_x = $this->_imagesx;
        }
        /* Se, apos novos tamanhos, $new_size_y for maior que $this->_imagesy (tamanho maximo y definido), seta-se y como padrao e refaz o processo, usando Y como padrao) */
        if ($this->_imagesy && $new_size_y > $this->_imagesy) {
            $new_size_x = ($actual_x * $this->_imagesy) / $actual_y;
            $new_size_y = $this->_imagesy;
        }
        /* Cria imagem em branco nas dimensoes novas */
        $new_image = imagecreatetruecolor($new_size_x, $new_size_y);
        /* Copia imagem para a nova imagem criada */
        imagecopyresampled($new_image, $this->_image, 0, 0, 0, 0, $new_size_x, $new_size_y, $actual_x, $actual_y);
        return $new_image;
    }

}

?>