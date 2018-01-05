<?php

/*
 * To change this template, choose Tools | Templates
 * and open the template in the editor.
 */

/**
 * Description of loginController
 *
 * @author juniorfox
 */
class loginController extends controller
{

    public function indexAction()
    {
        $this->process_renderization(array('blank','simple'));
    }

    /*Não retorna nada, apenas efetua o login do usuario*/
    public function post_loginAction()
    {
        $login_man = new login_man();
        $login_man->efetuar_login($_POST['login'], $_POST['senha']);
    }
    
    public function logoutAction(){
        $login_man = new login_man();
        $login_man->logout();
        $cdata = null;
        $this->process_renderization(array('blank','simple'), $cdata);
    }

    public function json_verificar_loginAction()
    {
        if ($GLOBALS['login_man'])
        {
            $data['login'] = "ok";
        } else
        {
            $data['login'] = 'fail';
        }
        /*Previne fazer cache*/
        $this->process_json($data);
    }
    
    public function json_verificar_loginexisteAction()
    {
        $arrData = array();
        $login_man = new login_man();
        $user_data = $login_man->verificar_login_existe($_GET['login']);
        if($user_data && $_GET['login']){
            $data['login'] = 'true';
        }else{
            $data['login'] = 'false';
        }
        $this->process_json($data);
    }
    
    /*Actions referentes a criação e exclusão de logins...*/
    
    public function gerenciarAction()
    {
        $cdata['_HEADER']['cliente_id'] = $_GET['cliente_id'];
        $this->process_renderization(array('blank','simple'), $cdata);
    }
    
    /*Retorna apenas mensagem de erro, caso o mesmo ocorra*/
    public function post_gerenciar_cadastrarAction()
    {
        $cliente_id = null;
        /*TODO:Implementar cliente_id*/
        $login_man = new login_man();
        $dados = $_POST;
        die($login_man->cadastrar_login($dados));
    }

}

?>