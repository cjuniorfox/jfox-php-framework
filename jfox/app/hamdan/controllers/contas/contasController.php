<?php

/**
 * Este controlador é apenas apontados, pois usa o controlador abstrato
 * cadastroAbastractController.
 *
 * @author juniorfox
 */
class contasController extends AbsC_cadastros {
    
    protected $form_name = 'cad_contas';
    protected $xmlFileName = 'contas.xml';
    
    /*Variaveis para index*/
    protected $URL_LEFT = '{SITE_PATH}contas/listar';
    
    /*Variaveis usadas na action imprimir_registro*/
    protected $irHeader1 = 'Cadastro de Contas';
}

?>