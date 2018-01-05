<?php

/**
 * Este não é um controlador tradicional, este usa actions padrão do 
 * abstractController cadastros
 *
 * @author juniorfox
 */
class clientesController extends AbsC_cadastros {
    
    protected $form_name = 'cad_clientes';
    protected $xmlFileName = 'clientes.xml';
    
    /*Variaveis usadas na action imprimir_registro*/
    protected $irHeader1 = 'Cadastro de Clientes';
}

?>