<?php

/*
 * Funções globais para JFox Framework
 */

/**
 * Adiciona diretório a lista de bibliotecas usáveis.
 * @param string $path - Diretório a ser adicionado.
 * @return NULL;
 */
function add_to_library($path) {
    $__LIBRARY = array();
    if (isset($GLOBALS['__LIBRARY']))
        global $__LIBRARY;
    if ($path) {
        //Busca se o path já foi adicionado. Caso tenha sido, escapa sem fazer nada
        if (is_array($__LIBRARY)) {
            foreach ($__LIBRARY as $some_path) {
                if ($some_path == $path)
                    return null;
            }
        }
        if (is_dir($path))
            $__LIBRARY[] = $path;
    }
    $GLOBALS['__LIBRARY'] = $__LIBRARY;
}

/**
 * Remove diretório a lista de bibliotecas usáveis.
 * @param string $path - Diretório a ser removido.
 * @return NULL;
 */
function remove_to_library($path) {
    if (isset($GLOBALS['__LIBRARY']))
        global $__LIBRARY;
    else //Se não existir $__LIBRARY, não tem o que fazer.
        return null;
    $key = array_search($path, $__LIBRARY);
    if ($key) {
        unset($__LIBRARY[$key]);
        $GLOBALS['__LIBRARY'] = $__LIBRARY;
    }
}

/**
 * Carrega as bibliotecas de  lib (bibliotecas framework), e applib (bibliotecas aplicacao)
 * @param string|Nome da classe a ser carregada.
 */
function autoload_lib($className) {
    global $__LIBRARY;
    //Carrega a biblioteca de traz pra frente, dando prioridade a bibliotecas adicionadas durante a execução
    foreach (array_reverse($__LIBRARY) as $path) {
        $filename = $path . "/" . $className . ".php";
        if (file_exists($filename)) {
            include_once($filename);
            return NULL;
        }
    }
}

function __autoload_lib($className) {
    global $global_vars;
    /* valida os campos de biblioteca */
    if (!isset($global_vars['lib_path'])) {
        $global_vars['lib_path'] = NULL;
    }
    if (!isset($global_vars['applib_path'])) {
        $global_vars['applib_path'] = NULL;
    }

    /* Verifica se os arquivos existem e carregam a biblioteca desejada. o AppLib
     * tem prioridade sobre o lib.
     */
    if (file_exists($global_vars['applib_path'] . $className . ".php")) {
        include($global_vars['applib_path'] . $className . ".php");
    } elseif (file_exists($global_vars['lib_path'] . $className . ".php")) {
        include($global_vars['lib_path'] . $className . ".php");
    }
}

/**
 * Efetua calculos entre valores de tempo.
 * 
 * Exemplo de uso:
 * Descubrir 5 dias anteriores a 25/05/2012 calc_data('25/05/2012','-5 days','d/m/Y')
 * resposta: 20/05/2012
 * 
 * @param string $str_time - String de data/tempo que deseja calcular
 * @param string $count_time string com calculo a fazer
 * @param string $format formato de origem e retorno dos dados
 * @return string data calculada
 *
 */
function calc_date($str_time, $count_time = "", $format = 'Y-m-d') {
    return gmdate($format, strtotime($count_time, strtotime($str_time)));
}

/**
 * Converte data em formato String para data em formato date usando MYSQL.
 */
function mysql_str_to_date($str_date, $sql_mask = "%m/%d/%Y") {
    $sql_mask;
    $row = mysql_fetch_array(mysql_query("SELECT STR_TO_DATE('$str_date', '$sql_mask') AS date;"));
    return $row[0];
}

/* Converte data em formato date para formato string usando MYSQL */

function mysql_date_to_str($date, $sql_mask = "%m/%d/%Y") {
    $row = mysql_fetch_array(mysql_query("SELECT DATE_FORMAT('$date', '$sql_mask') AS date;"));
    return $row['date'];
}

/* Converte data em formato date para formato string usando MYSQL */

function mysql_time_to_str($date, $sql_mask = "%m/%d/%Y") {
    $row = mysql_fetch_array(mysql_query("SELECT TIME_FORMAT('$date', '$sql_mask') AS date;"));
    return $row['date'];
}

/* Converte data em formato string para data em outro formato string usando MYSQL */

function mysql_strdate_to_strdate($str_date, $sql_mask_from = "%m/%d/%Y", $sql_mask_to = "%d/%m/%Y") {
    $row = mysql_fetch_array(mysql_query("SELECT DATE_FORMAT(STR_TO_DATE('$str_date', '$sql_mask_from'), '$sql_mask_to') AS date;"));
    return $row[0];
}

/* Converte mascara basica de TIME e DATE do PHP para mascara do SQL */

function convert_sql_mask($phpmask) {
    $key_is_php_value_is_sql = array(
        /* Day */'d' => '%d', 'D' => '%a', 'j' => '%e', 'l' => '%W', 'w' => "%w",
        /* Month */'F' => '%M', 'm' => "%m", 'M' => '%b', 'n' => '%c',
        /* Year */'Y' => "%Y", 'y' => '%y',
        /* Time */'A' => "%p", 'g' => '%l', 'G' => '%k', 'h' => '%h', 'H' => '%H', 'i' => '%i', 's' => '%s', 'u' => '%f'
    );
    $mask = str_replace(array_keys($key_is_php_value_is_sql), $key_is_php_value_is_sql, $phpmask);
    /* Agora, limpa os caracteres que foram escapados de serem convertidos e retorna */
    return str_replace("\%", "", $mask);
}

function date_to_str($date, $mask = "m/d/Y") {
    $time = strtotime($date);
    return gmdate($mask, $time);
}

/**
 * Converte array para UTF8.
 * @param array $data - Array em ISO-8859-1
 * @param decode - Se true, faz o caminho inverso (Converte UTF8 to ISO-8859-1)
 */
function convertArrayToUtf8($data, $decode = false) {
    if (is_array($data)) {
        foreach (array_keys($data) as $key) {
            $data[$key] = convertArrayToUtf8($data[$key],$decode);
        }
        return $data;
    } else {
        if ($decode)
            return utf8_decode($data);
        else
            return utf8_encode($data);
    }
}

/* Pega primeiro dia do mes atual */

function firstOfMonth() {
    return date("m/d/Y", strtotime(date('m') . '/01/' . date('Y') . ' 00:00:00'));
}

/* Pega ultimo dia do mes atual */

function lastOfMonth() {
    return date("m/d/Y", strtotime('-1 second', strtotime('+1 month', strtotime(date('m') . '/01/' . date('Y') . ' 00:00:00'))));
}

/* * *****************************************************************************
  Exemplo do artigo "PESQUISA COM ACENTOS DENTRO DO MYSQL"
  -
  drSolutions - Tecnologia e Informática
  -
  Diego M. Rodrigues
  diego@drsolutions.com.br
  -------------------------------------------------------------------------------
  Função que Montas as REGEXP
  Rev. Ago/2007
 * ***************************************************************************** */

function stringParaBusca($str) {
    //Transformando tudo em minúsculas
    $str = trim(strtolower($str));

    //Tirando espaços extras da string... "tarcila  almeida" ou "tarcila   almeida" viram "tarcila almeida"
    while (strpos($str, "  "))
        $str = str_replace("  ", " ", $str);

    //Agora, vamos trocar os caracteres perigosos "ã,á..." por coisas limpas "a"
    $caracteresPerigosos = array("Ã", "ã", "Õ", "õ", "á", "Á", "é", "É", "í", "Í", "ó", "Ó", "ú", "Ú", "ç", "Ç", "à", "À", "è", "È", "ì", "Ì", "ò", "Ò", "ù", "Ù", "ä", "Ä", "ë", "Ë", "ï", "Ï", "ö", "Ö", "ü", "Ü", "Â", "Ê", "Î", "Ô", "Û", "â", "ê", "î", "ô", "û", "!", "?", ",", "“", "”", "-", "\"", "\\", "/");
    $caracteresLimpos = array("a", "a", "o", "o", "a", "a", "e", "e", "i", "i", "o", "o", "u", "u", "c", "c", "a", "a", "e", "e", "i", "i", "o", "o", "u", "u", "a", "a", "e", "e", "i", "i", "o", "o", "u", "u", "A", "E", "I", "O", "U", "a", "e", "i", "o", "u", ".", ".", ".", ".", ".", ".", ".", ".", ".");
    $str = str_replace($caracteresPerigosos, $caracteresLimpos, $str);

    //Agora que não temos mais nenhum acento em nossa string, e estamos com ela toda em "lower",
    //vamos montar a expressão regular para o MySQL
    $caractresSimples = array("a", "e", "i", "o", "u", "c");
    $caractresEnvelopados = array("[a]", "[e]", "[i]", "[o]", "[u]", "[c]");
    $str = str_replace($caractresSimples, $caractresEnvelopados, $str);
    $caracteresParaRegExp = array(
        "(a|ã|á|à|ä|â|&atilde;|&aacute;|&agrave;|&auml;|&acirc;|Ã|Á|À|Ä|Â|&Atilde;|&Aacute;|&Agrave;|&Auml;|&Acirc;)",
        "(e|é|è|ë|ê|&eacute;|&egrave;|&euml;|&ecirc;|É|È|Ë|Ê|&Eacute;|&Egrave;|&Euml;|&Ecirc;)",
        "(i|í|ì|ï|î|&iacute;|&igrave;|&iuml;|&icirc;|Í|Ì|Ï|Î|&Iacute;|&Igrave;|&Iuml;|&Icirc;)",
        "(o|õ|ó|ò|ö|ô|&otilde;|&oacute;|&ograve;|&ouml;|&ocirc;|Õ|Ó|Ò|Ö|Ô|&Otilde;|&Oacute;|&Ograve;|&Ouml;|&Ocirc;)",
        "(u|ú|ù|ü|û|&uacute;|&ugrave;|&uuml;|&ucirc;|Ú|Ù|Ü|Û|&Uacute;|&Ugrave;|&Uuml;|&Ucirc;)",
        "(c|ç|Ç|&ccedil;|&Ccedil;)");
    $str = str_replace($caractresEnvelopados, $caracteresParaRegExp, $str);

    //Trocando espaços por .*
    $str = str_replace(" ", ".*", $str);

    //Retornando a String finalizada!
    return $str;
}

function str_chop($string, $length = 60, $center = false, $append = null) {
    // Set the default append string
    if ($append === null)
        $append = ($center === true) ? ' ... ' : '...';

    // Get some measurements
    $len_string = strlen($string);
    $len_append = strlen($append);

    // If the string is longer than the maximum length, we need to chop it
    if ($len_string > $length) {
        // Check if we want to chop it in half
        if ($center === true) {
            // Get the lengths of each segment
            $len_start = $length / 2;
            $len_end = $len_start - $len_append;

            // Get each segment
            $seg_start = substr($string, 0, $len_start);
            $seg_end = substr($string, $len_string - $len_end, $len_end);

            // Stick them together
            $string = $seg_start . $append . $seg_end;
        } else {
            // Otherwise, just chop the end off
            $string = substr($string, 0, $length - $len_append) . $append;
        }
    }

    return $string;
}

function stripslashes_deep($value) {
    $value = is_array($value) ?
            array_map('stripslashes_deep', $value) :
            stripslashes($value);

    return $value;
}

/**
 * @param string $email - String com e-mail a ser validado
 * @return bool - Se for um e-mail valido, retorna true, senão, false.
 */
function validate_email($email) {
    $mail_correcto = 0;
    //verifico umas coisas 
    if ((strlen($email) >= 6) && (substr_count($email, "@") == 1) && (substr($email, 0, 1) != "@") && (substr($email, strlen($email) - 1, 1) != "@")) {
        if ((!strstr($email, "'")) && (!strstr($email, "\"")) && (!strstr($email, "\\")) && (!strstr($email, "\$")) && (!strstr($email, " "))) {
            //vejo se tem caracter . 
            if (substr_count($email, ".") >= 1) {
                //obtenho a terminação do dominio 
                $term_dom = substr(strrchr($email, '.'), 1);
                //verifico que a terminação do dominio seja correcta 
                if (strlen($term_dom) > 1 && strlen($term_dom) < 5 && (!strstr($term_dom, "@"))) {
                    //verifico que o de antes do dominio seja correcto 
                    $antes_dom = substr($email, 0, strlen($email) - strlen($term_dom) - 1);
                    $caracter_ult = substr($antes_dom, strlen($antes_dom) - 1, 1);
                    if ($caracter_ult != "@" && $caracter_ult != ".") {
                        $mail_correcto = 1;
                    }
                }
            }
        }
    }

    if ($mail_correcto)
        return true;
    else
        return false;
}

/**
 * Converte strings formatadas no padrão GET em array
 * @param string $get_string - String no formato GET
 * @return array - Array de string GET
 */
function strGetToArray($get_string) {
    /* Para ser uma string get valida, precisa ter pelo menos =. testa se este existe
     * Caso não exista, retorna array vazio
     */
    if (!strstr($get_string, "="))
        return array();
    $newArr = array();
    $arr1 = explode("&", $get_string);
    foreach ($arr1 as $var) {
        $expl = explode("=", $var);
        $newArr[urldecode($expl[0])] = urldecode($expl[1]);
    }
    return $newArr;
}

/**
 * Converte arrays em string formatada no padrão GET.
 * @param array  Array de dados
 * @return string String com as variaveis no padrão GET 
 */
function arrayToStrGet($array) {
    $newArray = array();
    foreach (array_keys($array) as $key)
        $newArray[] = urlencode($key) . "=" . urlencode($array[$key]);
    return implode("&", $newArray);
}

/**
 * Pega um array simples e transforma o mesmo em uma arrayList para lista de GET
 * exemplo:
 * array(a=>1, b=2, c=3)
 * para
 * array(
 *  array(key=1,value=1),
 *  array(key=2,value=2),
 *  array(key=3,value=3)
 * );
 * @param array $array Nome do array de origem
 * @return array arrayList para view
 */
function arrayToList($array){
    $arrayList = array();
    foreach(array_keys($array) as $key){
        $arrayList[] = array('key'=> $key, 'value'=>$array[$key]);
    }
    return $arrayList;
}

/**
 * Converte elementos SimpleXML em arrays tradicionais
 */
function toArray(SimpleXMLElement $xml) {
    $array = (array) $xml;

    foreach (array_slice($array, 0) as $key => $value) {
        if ($value instanceof SimpleXMLElement) {
            $array[$key] = empty($value) ? NULL : toArray($value);
        }
    }
    return $array;
}

/**
 * Faz duas tentativas de carregar o arquivo. Uma usando o path de templates
 * da aplicação "app_template_path" e outra usando o path do framework "template_path".
 * Se a primeira existir, retorna o path a partir de "app_template_path". Caso contrario, retorna
 * o path do framework
 * @param string nome do arquivo
 * @return string nome do arquivo com diretorio
 */
function jfox_template_path($file) {
    $path = $GLOBALS['global_vars']['app_template_path'];
    if (file_exists($path . $file)) {
        return $path . $file;
    }
    return $GLOBALS['global_vars']['template_path'] . $file;
}

/**
 * Criptografa valor usando algorítmo base_64_encode utilizando valor e chave
 * 
 * @param string $value - Valor a ser criptografado
 * @param string $key   - Chave de descriptografia para ler valor criptografado
 * @param int    $iv_len- Passos usados pelo algorítmo de ramdomização
 * @return string - String criptografada
 * 
 * Função baseada nos algorítmos de M. Marcelo
 */
function base64_crypt($value, $key = 'null', $iv_len = 16) {
    $value .= "\x13";
    $n = strlen($value);
    if ($n % 16)
        $value .= str_repeat("\0", 16 - ($n % 16));
    $i = 0;
    $enc_value = randomize($iv_len);
    $iv = substr($key ^ $enc_value, 0, 512);
    while ($i < $n) {
        $Bloco = substr($value, $i, 16) ^ pack('H*', md5($iv));
        $enc_value .= $Bloco;
        $iv = substr($Bloco . $iv, 0, 512) ^ $key;
        $i += 16;
    }
    return base64_encode($enc_value);
}

/**
 * Descriptografa variáveis criptografadas pelo base64_crypt.
 * 
 * @param string $enc_value - Valor a ser descriptografado
 * @param string $key       - Chave de descriptografia para ler valor criptografado
 * @param int    $iv_len    - Passos usados pelo algorítmo de randomização
 * @param string            - Variável desejada.
 * 
 */
function base64_decrypt($enc_value, $key = 'null', $iv_len = 16) {
    $enc_value;
    $enc_value = base64_decode($enc_value);
    $n = strlen($enc_value);
    $i = $iv_len;
    $value = '';
    $iv = substr($key ^ substr($enc_value, 0, $iv_len), 0, 512);
    while ($i < $n) {
        $Bloco = substr($enc_value, $i, 16);
        $value .= $Bloco ^ pack('H*', md5($iv));
        $iv = substr($Bloco . $iv, 0, 512) ^ $key;
        $i += 16;
    }
    return preg_replace('/\\x13\\x00*$/', '', $value);
}

/**
 * Função utilizada para ramdomizar valores um certo passo de vezes
 * @param int $iv_len - Quantidade de passos que o algorítmo irá ramdomizar
 */
function ramdomize($iv_len) {
    $iv = '';
    while ($iv_len-- > 0) {
        $iv .= chr(mt_rand() & 0xff);
    }
    return $iv;
}

/* Obtém o input, e desfaz-se dos caracteres indesejados */

function generate_link_seo($input, $substitui = '-', $remover_palavras = true, $array_palavras = array()) {
    //Colocar em minúsculas, remover a pontuação
    $resultado = trim(ereg_replace(' +', ' ', preg_replace('/[^a-zA-Z0-9\s]/', '', strtolower($input))));

    //Remover as palavras que não ajudam no SEO
    //Coloco as palavras por defeito no remover_palavras(), assim eu não esse array
    if ($remover_palavras) {
        $resultado = remove_words($resultado, $substitui, $array_palavras);
    }

    //Converte os espaços para o que o utilizador quiser
    //Normalmente um hífen ou um underscore
    return str_replace(' ', $substitui, $resultado);
}

function remove_words($input, $substitui, $array_palavras = array(), $palavras_unicas = true) {
    //Separar todas as palavras baseadas em espaços
    $array_entrada = explode(' ', $input);

    //Criar o array de saída
    $resultado = array();

    //Faz-se um loop às palavras, remove-se as palavras indesejadas e mantém-se as que interessam
    foreach ($array_entrada as $palavra) {
        if (!in_array($palavra, $array_palavras) && ($palavras_unica ? !in_array($palavra, $resultado) : true)) {
            $resultado[] = $palavra;
        }
    }

    return implode($substitui, $resultado);
}

?>