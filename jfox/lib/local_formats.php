<?php
/* 
 * To change this template, choose Tools | Templates
 * and open the template in the editor.
*/

/**
 * Description of local_formats
 *
 * @author juniorfox
 */
class local_formats {
    public $xmlData; /*XML com dados regionais de formatacao*/
    private $_format; /*Formato padrão*/
    
    const xml_local_formats = "xml/local_formats.xml";

    public function  __construct($fileName = null) {
        if(!$fileName) {
            $fileName = $GLOBALS['global_vars']['etc_path'].self::xml_local_formats;
        }
        $xmldata = file_get_contents($fileName);
        $this->xmlData = new SimpleXMLElement($xmldata);
        $this->_format = (string) $this->xmlData->default;
    }

    /**
     * Converte dados (data) formatados para SQL, PHP, etc. Em dados formatados
     * e imprimiveis a partir de formato definido em arquivo XML.
     * 
     * @return string : informação formatada
     * @param string $string : Dado a ser formatado
     * @param string $type : Tipo de dado a ser formatado. pode ser 'date', 'time' or 'real'
     * @param string $subtype : O nome do subtipo de formatação. Exemplo: 'num'
     */
    public function data_to_local_str($string,$type,$format = null,$subtype = null){
        return $this->_format(true, $string, $type, $format, $subtype);
    }
    
    /**
     * Converte dados formatados e imprimeveis em dados processaveis em PHP e SQL,
     * usando formato definido em arquivo XML.
     * 
     * @return string : informação formatada
     * @param string $string : Dado a ser formatado
     * @param string $type : Tipo de dado a ser formatado. pode ser 'date', 'time' or 'real'
     * @param string $subtype : O nome do subtipo de formatação. Exemplo: 'num'
     */
    public function local_str_to_data($string,$type,$format = null,$subtype = null){
        return $this->_format(false, $string, $type, $format, $subtype);
    }

    /**
     * Converte data (calendario) em formado de DADOS processaveis para string
     * imprimivel e formatada a partir de arquivo XML.
     */
    public function date_to_local_str($date, $subtype = 'num', $format = null) {
        if(!$format) {
            $format = (string) $this->_format;
        }
        if(!$subtype) {
            $subtype = 'num';
        }
        $sql_mask = convert_sql_mask($this->_getMask($format, 'date', $subtype));
        
        $local_str_date = mysql_date_to_str($date,$sql_mask);
        $week = date_to_str($date,"D");
        $month  = date_to_str($date,"M");
        $local_str_date = str_replace($month, $this->xmlData->$format->date->months->$month, $local_str_date);
        $local_str_date = str_replace($week, $this->xmlData->$format->date->weeks->$week, $local_str_date);
        return $local_str_date;
    }

    
    public function local_str_to_date($local_str,$subtype = 'num', $format = null) {
        if(!$format) {
            $format = (string) $this->_format;
        }
        $arrMonths = (array) $this->xmlData->$format->date->months;
        $arrWeeks = (array) $this->xmlData->$format->date->weeks;
        /*Converte os dias da semana e mes para o ingles baseando-se no XML*/
        $local_str = str_replace($arrMonths,array_keys($arrMonths),$local_str);
        $local_str = str_replace($arrWeeks,array_keys($arrWeeks),$local_str);
        /*Converte mascara para mascara de sql*/
        $sql_mask = convert_sql_mask($this->_getMask($format, 'date', $subtype));
        $date = mysql_str_to_date($local_str, $sql_mask);
        return $date;
    }
    

    public function time_to_local_str($time,$subtype = null, $format = null) {
        if(!$subtype) {
            $subtype = 'short';
        }
        if(!$format) {
            $format = $this->_format;
        }
        $sql_mask = convert_sql_mask($this->_getMask($format, 'time', $subtype));
        return mysql_time_to_str($time, $sql_mask);
    }

    public function local_str_to_time($time,$subtype = null, $format = null) {
        if(!$subtype) {
            $subtype = 'short';
        }
        if(!$format) {
            $format = $this->_format;
        }
        $sql_mask = convert_sql_mask($this->_getMask($format, 'time', $subtype));
        return mysql_str_to_date($time, $sql_mask);
    }

    public function real_to_local_str($real,$subtype = null, $format = null) {
        if(!$subtype) {
            $subtype = 'monetary';
        }
        if(!$format) {
            $format = (string) $this->_format;
        }
        $decimals = (int) $this->xmlData->$format->real->$subtype->decimals;
        $dec_point = (string) $this->xmlData->$format->real->$subtype->dec_point;
        $thousands_sep = (string) $this->xmlData->$format->real->$subtype->thousands_sep;
        return number_format($real, $decimals, $dec_point, $thousands_sep);
    }

    public function local_str_to_real($real,$subtype = null, $format = null) {
        if(!$subtype) {
            $subtype = 'monetary';
        }
        if(!$format) {
            $format = (string) $this->_format;
        }
        $dec_point = (string) $this->xmlData->$format->real->$subtype->dec_point;
        $thousands_sep = (string) $this->xmlData->$format->real->$subtype->thousands_sep;
        $replacers = array($thousands_sep=>'',$dec_point=>'.');
        return (real) str_replace(array_keys($replacers),$replacers,$real);
    }
    
    public function integer_to_local_str($integer,$subtype, $format){
        if(!$format) {
            $format = (string) $this->_format;
        }
        $thousands_sep = (string) $this->xmlData->$format->real->$subtype->thousands_sep;
        return number_format($integer,0,"",$thousands_sep);
    }
    
    public function local_str_to_integer($string,$format){
        if(!$format) {
            $format = (string) $this->_format;
        }
        $dec_point = '';
        $thousands_sep = (string) $this->xmlData->$format->integer->thousands_sep;
        $replacers = array($thousands_sep=>'',$dec_point=>'.');
        $value = str_replace(array_keys($replacers),$replacers,$string);
        /*Remove a parte decimal e mantém apenas a parte inteira do numero*/
        $arrValue = explode(".",$value);
        return (integer) $arrValue[0];
    }
    
    /**
     * Busca mascara de conversão no XML. Caso tal mascara não exista, retorna 
     * o proprio subtipo como mascara
     */
    private function _getMask($format,$type,$subtype){
        $mask = (string) $this->xmlData->$format->$type->$subtype;
        if(!$mask)//Se não existir mascara no XML, define o proprio nome do subtipo como sendo a mascara em si
            $mask = $subtype;
        return $mask;
    }

    private function _format($dataToStr,$string,$type,$format = null,$subtype = null) {
        /*$dataToStr: TRUE = corverter STR para DATA, FALSE converter DATA para STR.*/
        $xmlData = $this->xmlData;
        if(!$format) {
            $format = $this->_format;
        }
        if($type == 'date') {
            if($dataToStr) return $this->date_to_local_str($string, $subtype, $format);
            else return $this->local_str_to_date($string, $subtype, $format);
        }elseif($type == 'time') {
            if($dataToStr) return $this->time_to_local_str($string, $subtype, $format);
            else return $this->local_str_to_time($string, $subtype, $format);
        }elseif($type == 'real' || $type == 'double') {
            if($dataToStr) return $this->real_to_local_str($string, $subtype, $format);
            else return $this->local_str_to_real($string, $subtype, $format);
        }elseif($type == 'integer' || $type == 'int'){
            if($dataToStr) return $this->integer_to_local_str($string, $subtype, $format);
            else return $this->local_str_to_integer($string,$format);
        }else {
            return $string;
        }
    }

    //EM DESENVOLVIMENTO, FUNCOES PARA PHP => 5.3.0
    public function _format_date($sql_date, $subtype = null, $format = null) {
        if(!$subtype) {
            $subtype = 'num';
        }
        if(!$format) {
            $format = (string) $this->_format;
        }
        $date = DateTime::createFromFormat('Y-m-d', $sql_date);
        $dateString = $date->format((string)$this->xmlData->$format->date->$subtype);
        /*Pega valores por extenso em ingles e o converte para extenso conforme definido no XML*/
        $week = $date->format('D');
        $month = $date->format("M");
        $dateString = str_replace($month, $this->xmlData->$format->date->months->$month, $dateString);
        $dateString = str_replace($week, $this->xmlData->$format->date->weeks->$week, $dateString);
        return $dateString;
    }
}
?>