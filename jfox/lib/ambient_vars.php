<?php

/**
 * Classe que define as variaveis referentes ao protocolo, nome do servidor, e formar a URL
 * para acessar diretorio base do website.
 *
 * Classe incluida pelo global_vars.php e nao pelo index.php. Como acontece com as outras classes de bibliotecas
 * 
 * @version 1.03;
*/

class ambient_vars {
    
    const version = 1.03;

    /**
     * Retorna url do path base do site
     * @param $no_protocol - Quando true, não retorna http:// ou https://
     * @return string url PATH base do site
     */
    public static function  website_path($no_protocol = false) {
        if($no_protocol)
            return self::hostname().self::_string_port().self::_base_subdir();
        return self::protocol()."://".self::hostname().self::_string_port().self::_base_subdir();
    }
    
    public static function website_public_path(){
        /*Retorna URL onde roda a aplicacao com a designacao public, aplicada em index.php*/
        $public = null;
        if(isset($GLOBALS['public'])){
            $public = $GLOBALS['public'];
        }
        return self::protocol()."://".self::hostname().self::_string_port().self::_base_subdir().$public;
    }
    
    /**
     * Retorna o caminho relativo para encontrar o diretorio base aonde encontra-se o index.php base do sistema.
     */
    public static function base_path(){
        $path = null;
        $path_info = "/".str_replace(self::_base_subdir(),"",$_SERVER['REQUEST_URI']);
        $dirs = explode("/",$path_info);
        for($i=2; $i < count($dirs); $i++){
            $path.= "../";
        }
        return $path;
    }
    
    /**
     * Retorna o caminho relativo da base do site + o path definido como public.
     */
    
    public static function public_path(){
        $public = null;
        if(isset($GLOBALS['public'])){
            if($GLOBALS['public'] && $GLOBALS['public'] != './'){
                $public = $GLOBALS['public'];
            }
        }
        $base_path = self::base_path();
        return $public.$base_path;
    }
    public function __public_path(){
        /*Tem como objetivo retornar o caminho adicionando diretorio e ../ para o diretorio raiz da aplicação*/
        //print(self::_base_subdir());
        $path = null;
        $path_info = "/".str_replace(self::_base_subdir(),"",$_SERVER['REQUEST_URI']);
        if(isset($GLOBALS['public'])){
            if($GLOBALS['public'] && $GLOBALS['public'] != './'){
                $path = $GLOBALS['public'];
            }
        }
        $dirs = explode("/",$path_info);
        for($i=2; $i < count($dirs); $i++){
            $path.= "../";
        }
        return $path;
    }
    
    /*Retorna a URL chamada*/
    public static function self_url(){
        return self::protocol()."://".self::hostname().$_SERVER['REQUEST_URI'];
    }
    /*Retorna a URL chamada sem a query string (string de $_GET)*/
    public static function self_url_page(){
        return self::protocol()."://".self::hostname().str_replace("?".$_SERVER['QUERY_STRING'],"",$_SERVER['REQUEST_URI']);
    }
    
    public static function hostname() {
        return $_SERVER['HTTP_HOST'];
    }
    
    public static function protocol() {
        if($_SERVER['SERVER_PORT'] == '443'){
            return "https";
        }
        return 'http';
    }
    
    private static function _get_selffilename() {
        $temp = explode("/", $_SERVER['SCRIPT_NAME']);
        $filename_key_pos = count($temp) - 1;
        return $temp[$filename_key_pos];
    }
    
    private static function _string_port() {
        $port = $_SERVER['SERVER_PORT'];
        if($port != '80' && $port != '443') return ":".$port;
        else return "";
    }

    private static function _base_subdir() {
        return str_replace(self::_get_selffilename(),"", $_SERVER['SCRIPT_NAME']);
    }

    
}
?>