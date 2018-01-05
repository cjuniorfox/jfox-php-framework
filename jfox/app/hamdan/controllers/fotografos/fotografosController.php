<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

/**
 * Description of fotografosController
 *
 * @author cjuniorfox
 */
class fotografosController extends AbsC_cadastros{
    protected $form_name = 'cad_fotografos';
    protected $xmlFileName = 'fotografos.xml';
    
    /*Variaveis usadas na action imprimir_registro*/
    protected $irHeader1 = 'Cadastro de Fotografos';
}
