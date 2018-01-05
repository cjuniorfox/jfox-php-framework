<?php

/**
 * Bootstrap inicializa processos mandatorios para o programa em execucao
 * pode-se atribuir o bootstrap a criacao de tabelas padrao, autenticacao padrao
 * de usuarios, etc
 *
 * @author junior
 */
class bootstrap {

    private $_global_vars;
    private $_mysql_vars;

    public function  __construct() {
        global $global_vars;
        global $mysql_vars;
        $this->_global_vars = $global_vars;
        $this->_mysql_vars  = $mysql_vars;
        $this->_startMysql();
        session_start();/*Inicia a Session para todos os formularios*/
        $login_man = new login_man();
        $GLOBALS['login_man'] = $login_man->verificar_atual_logado();
    }

    private function _startMysql(){
        
        $this->_object_mysql = new mysql();
        //$this->_object_mysql->debug = true;
        $this->_object_mysql->execute_query_file($this->_global_vars['query_path']."tables.sql",$this->_mysql_vars);
    }
}
?>
