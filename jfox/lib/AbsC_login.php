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
class AbsC_login extends controller {

    public function indexAction() {
        return $this->process_renderization(array('appTema', 'login'));
    }

    /* Não retorna nada, apenas efetua o login do usuario */

    public function post_loginAction() {
        $login_man = new login_man();
        $login_man->efetuar_login($_POST['login'], $_POST['senha']);
    }

    public function logoutAction() {
        $login_man = new login_man();
        $login_man->logout();
        $this->process_renderization(array('appTema', 'logout'));
    }

    public function json_verificar_loginAction() {
        if ($GLOBALS['login_man']) {
            $data['login'] = "ok";
        } else {
            $data['login'] = 'fail';
        }
        /* Previne fazer cache */
        $this->process_json($data);
    }

    public function json_verificar_loginexisteAction() {
        $data = array();
        $login_man = new login_man();
        $user_data = $login_man->verificar_login_existe($_GET['login']);
        if ($user_data && $_GET['login']) {
            $data['login'] = 'true';
        } else {
            $data['login'] = 'false';
        }
        $this->process_json($data);
    }
}
?>
