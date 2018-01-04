<?php

/**
 * Este widget inclui um mural de recados na pagina acessada.
 *
 * @author junior
 */
class comentariosWidget extends widgetController {

    private $_name;             /*Nome enviado no formulario de postagem*/
    private $_email;            /*E-mail enviado no formulario de postagem*/
    private $_comment;          /*Comentario enviado no formulario de postagem*/
    private $_freecap;          /*Variavel de checagem Freecap*/
    private $_del;              /*ID do comentario a ser deletado*/
    private $_submit;            /*Flag de submit*/

    private $_field_name;       /*Nome da variavel para nome*/
    private $_field_email;      /*Nome da variavel para e-mail*/
    private $_field_comment;    /*Nome da variavel para comentario*/
    private $_field_freecap;    /*Nome da variavel para checagem Freecap*/
    private $_field_submit;     /*Nome da variavel que envia a flag de SUBMIT*/
    private $_get_del;          /*Nome da variavel que permite deletar a postagem*/

    private $_index_XML;        /*Objeto SimpleXML, com variaves XML inserias em views/xml/index.xml*/

    private $_tblComentarios;   /*Nome da tabela no banco de dados*/
    private $_id_related;       /*Identificacao do comentario*/
    private $_admin_status;     /*Caso esteja TRUE, permite ao usuario deletar recados*/
    private $_title;            /*Titulo para aparecer nos comentarios*/
    private $_start_line;       /*Linha que se comeca a imprimir os comentarios*/
    private $_quant_com;        /*Quantidade de comentarios na pagina exibida*/

    private $_errors;           /*Array com possiveis erros retornaveis*/



    public function Action(){
        $ckeck_widget_vars = $this->_test_empty_widget_vars();
        if(!$ckeck_widget_vars){
            $this->_parse_default_for_empty_widget_vars();
            $this->_parse_widget_vars();
            $this->_parse_XML();
            $this->_parse_get_post();
            $this->_start_mysql();
            if($this->_submit){
                $errors = $this->_check_errors();
                if(!$errors)    $this->_post_it();
            }
            $this->_del_comment();
            $this->_parse_print_error_messages();
            $this->_parse_print_values();
        }else{
            $this->array_data['error']  = $ckeck_widget_vars;
            $this->array_data['__FILE'] = $this->widget_env['view_path']."error.html";
        }
    }
    
/**
 * Testa as variaveis abaixo passadas de widget_vars estao ou nao vazias
 * e preenxe com valor padrao as que sao possiveis de preenxer
 */
    private function _test_empty_widget_vars(){
        /*Testa as variaveis*/
        $error = $this->test_widget_var('id_related');
        $error .= $this->test_widget_var('admin_status');
        return $error;
    }
    
    private function _parse_widget_vars(){
        /*Parse do nome da tabela e dados relacionados*/
        if(isset($this->widget_vars['tblComentarios'])) $this->_tblComentarios  = $this->widget_vars['tblComentarios'];
        if(isset($this->widget_vars['id_related']))     $this->_id_related      = $this->widget_vars['id_related'];
        if(isset($this->widget_vars['admin_status']))   $this->_admin_status    = $this->widget_vars['admin_status'];
        /*Parse do nome dos campos*/
        if(isset($this->widget_vars['field_name']))     $this->_field_name      = $this->widget_vars['field_name'];
        if(isset($this->widget_vars['field_email']))    $this->_field_email     = $this->widget_vars['field_email'];
        if(isset($this->widget_vars['field_freecap']))  $this->_field_comment   = $this->widget_vars['field_freecap'];
        if(isset($this->widget_vars['field_comment']))  $this->_field_comment   = $this->widget_vars['field_comment'];
        if(isset($this->widget_vars['field_submit']))   $this->_field_submit    = $this->widget_vars['field_submit'];
        if(isset($this->widget_vars['get_del']))        $this->_get_del         = $this->widget_vars['get_del'];
        /*Parse do titulo da postagem*/
        if(isset($this->widget_vars['title']))          $this->_title           = $this->widget_vars['title'];
    }

    private function _parse_XML(){
        $this->_index_XML = simplexml_load_file($this->widget_env['view_path']."xml/index.xml");
    }

    private function _parse_default_for_empty_widget_vars(){
        if(!isset($this->widget_vars['title ']))         $this->_title          = 'title';
        if(!isset($this->widget_vars['tblComentarios'])) $this->_tblComentarios = 'tblComentarios';
        if(!isset($this->widget_vars['field_name']))     $this->_field_name     = 'comment_name';
        if(!isset($this->widget_vars['field_email']))    $this->_field_email    = 'comment_email';
        if(!isset($this->widget_vars['field_comment']))  $this->_field_comment  = 'comment_comment';
        if(!isset($this->widget_vars['field_freecap']))  $this->_field_freecap  = 'comment_freecap';
        if(!isset($this->widget_vars['field_submit']))   $this->_field_submit   = 'comment_submit';
        if(!isset($this->widget_vars['get_del']))        $this->_get_del        = 'com_id_del';
    }

/*
 * Repassa as variaveis passadas em $_GET e $_POST para o objeto
 */
    private function _parse_get_post(){
        if($_POST){
            $this->_name    = $_POST[$this->_field_name];
            $this->_email   = $_POST[$this->_field_email];
            $this->_comment = $_POST[$this->_field_comment];
            $this->_freecap = $_POST[$this->_field_freecap];
            $this->_submit  = $_POST[$this->_field_submit];
        }else{
            $this->_name    = '';
            $this->_email   = '';
            $this->_comment = '';
            $this->_submit  = '';
        }
        if($_GET){
            $this->_del     = $_GET[$this->_get_del];
        }
    }

/*
 * Carrega a tabela SQL dos comentarios...
 */
    private function _start_mysql(){
        global $mysql_vars;
        $tblComentarios = $this->_tblComentarios;
        $id_related     = $this->_id_related;

        $connection     = mysql_connect($mysql_vars['host'], $mysql_vars['login'], $mysql_vars['passwd']) or die(mysql_error());
        $db             = mysql_select_db($mysql_vars['database']) or die(mysql_error());
        $check          = mysql_fetch_array(mysql_query("CHECK TABLE $tblComentarios")) or die(mysql_error());
		if ($check['Msg_text'] != 'OK'){
			mysql_query(" CREATE TABLE `$tblComentarios` (
			`id` INT NOT NULL AUTO_INCREMENT ,
			`id_related` INT NOT NULL ,
			`name` VARCHAR( 160 ) NOT NULL ,
			`email` VARCHAR( 100 ) NOT NULL ,
			`comment` TEXT NOT NULL ,
			`date` DATE NOT NULL ,
			`ip` VARCHAR(16) NOT NULL,
			PRIMARY KEY ( `id` )
			) ENGINE = MYISAM ") or die(mysql_error());
		}
    }

    private function _check_errors(){
        $errors = false;
        /*Checa primeiro se os campos estao vazios*/
        if(!$this->_name)                   $errors['name']     = true;
        if(!$this->_email)                  $errors['email']    = true;
        if(!$this->_comment)                $errors['comment']  = true;
        if(!$this->_get_freecap_status())   $errors['freecap']  = true;
        /*Agora verifica se a postagem ja existe*/
        $read_comment = $this->_read_last_comment();
        if($read_comment['comment'] == $this->_comment && $this->_comment) $errors['comment_repeated'] = true;
        $this->_errors = $errors;
        return $errors;
    }

    private function _load_comments(){
        $tblComentarios = $this->_tblComentarios;
        $id_related     = $this->_id_related;
        $query          = "SELECT * FROM $tblComentarios WHERE id_related =  '$id_related'";
        $sql            = mysql_query($query) or die(mysql_error());
        while($line = mysql_fetch_array($sql)){
            $line['get_del'] = $this->widget_vars['get_del'];
            $comments[] = $line;
        }
        if($this->_admin_status)    $comments['__FILE'] = $this->widget_env['view_path']."index.comments-w_del.html";
        else                        $comments['__FILE'] = $this->widget_env['view_path']."index.comments-wo_del.html";
        $comments['__COMMAND']  = 'list';
        return $comments;
    }

    private function _read_last_comment($id_related = false){
        $tblComentarios = $this->_tblComentarios;
        $query          = "SELECT * FROM $tblComentarios";
        if($id_related){
            $query .= " WHERE id_related = '$id_related'";
        }
        $query .= " ORDER BY id DESC";
        $sql = mysql_query($query) or die(mysql_error());
        return mysql_fetch_array($sql);
    }

/*
 * Verifica qual o $_GET que deleta a postagem, verifica seu valor, e o status de $admin_status
 * se tudo for true, deleta a postagem passada na variavel de $_GET.
 */
    private function _del_comment(){
        if($this->_admin_status && $this->_del){
            $id_related     = $this->_id_related;
            $tblComentarios = $this->_tblComentarios;
            $id             = $this->_del;
            $query          = "DELETE FROM $tblComentarios WHERE id_related = '$id_related' AND id = '$id'";
            mysql_query($query) or die(mysql_error());
        }
    }
    

    private function _post_it(){
        $tblComentarios = $this->_tblComentarios;
        $id_related     = $this->_id_related;
        $name           = $this->_name;
        $email          = $this->_email;
        $comment        = $this->_comment;
        $date           = date("Y-m-d");
        $ip             = $_SERVER['REMOTE_ADDR'];
        $query          = "INSERT INTO $tblComentarios (id_related, name, email, comment, date, ip) VALUES ('$id_related','$name','$email','$comment', '$date', '$ip')";
		mysql_query($query) or die(mysql_error());
    }

    /*Repassa os erros para impressão*/
    private function _parse_print_error_messages(){
        if($this->_errors['name']){
            $this->array_data['error_name'] = $this->_index_XML->error_messages->name;
        }else{
           $this->array_data['error_name']= '';
        }
        if($this->_errors['email']){
            $this->array_data['error_email'] = $this->_index_XML->error_messages->email;
        }else{
           $this->array_data['error_email']= '';
        }
        if($this->_errors['comment']){
            $this->array_data['error_comment'] = $this->_index_XML->error_messages->comment;
        }else{
           $this->array_data['error_comment']= '';
        }
        if($this->_errors['comment_repeated']){
            $this->array_data['error_comment_repeated'] = $this->_index_XML->error_messages->comment_repeated;
        }else{
           $this->array_data['error_comment_repeated']= '';
        }
        if($this->_errors['freecap']){
            $this->array_data['error_freecap'] = $this->_index_XML->error_messages->freecap;
        }else{
           $this->array_data['error_freecap']= '';
        }
    }
/*
 *  Ele ira repassar os valores para as variaveis de impressao
 */
    private function _parse_print_values(){
        /*Carrega todos os comentarios, e coloca na variavel correta*/
        $comments                           = $this->_load_comments();
        $this->array_data['title']          = $this->_title;
        $this->array_data['comments']       = $comments;
        $this->array_data['field_name']     = $this->_field_name;
        $this->array_data['field_email']    = $this->_field_email;
        $this->array_data['field_comment']  = $this->_field_comment;
        $this->array_data['field_freecap']  = $this->_field_freecap;
        $this->array_data['field_submit']   = $this->_field_submit;
        $this->array_data['name']           = '';
        $this->array_data['email']          = '';
        $this->array_data['comment']        = '';
        $this->array_data['comments']       = $comments;
        /*Se $this->errors() for true, e algo tiver sido postado, preenxe os campos com dados postados... */
        if(isset($_POST[$this->_field_submit]) && $this->_errors){
            $this->array_data['name']       = $this->_name;
            $this->array_data['email']      = $this->_email;
            $this->array_data['comment']    = $this->_comment;
        }
    }

    /*Busca a palavra enviada no Freecap, e verifica se a palavra é válida ou não.*/
    private function _get_freecap_status(){
        session_start();
        /*Verifica se a sessao do FreeCap foi iniciada*/
        if(!empty($_SESSION['freecap_word_hash']) && !empty($this->_freecap)){
            if($_SESSION['hash_func'](strtolower($this->_freecap))==$_SESSION['freecap_word_hash']){
                $_SESSION['freecap_attempts'] = 0;
                $_SESSION['freecap_word_hash'] = false;
                $passed = true;
            }else{
                $passed = false;
            }
        }else{
            $passed = false;
        }
        return $passed;
    }

}
?>
