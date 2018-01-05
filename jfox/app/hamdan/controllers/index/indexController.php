<?php

/*
 * To change this template, choose Tools | Templates
 * and open the template in the editor.
 */

/**
 * Description of indexController
 *
 * @author juniorfox
 */
class indexController extends controller
{
    /*    public function indexAction()
      {

      $cdata['view_body'] = 'Este é um exemplo de funcionamento do controlador';
      $cdata['_HEADER']['view_header'] = 'teste de header';
      $this->process_renderization($this->global_vars['template'], $cdata);
      }
     */

    public function indexAction(){
        /*Método em branco, pois tudo é cuidado pelo template index*/
        $this->process_renderization($this->global_vars['template']);
    }

    public function tela_principalAction()
    {
        $login_man = new login_man();
        $login_man->restrict_execution($this->global_vars['view_path'] . "login/logout.html");

        $cdata['view_body'] = '';
        $this->process_renderization(array('blank','simple'), $cdata);
    }

}

?>
