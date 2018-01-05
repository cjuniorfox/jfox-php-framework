<?php

/**
 * Este controlador é apenas apontados, pois usa o controlador abstrato
 * cadastroAbastractController.
 *
 * @author juniorfox
 */
class controle_financeiroController extends AbsC_controle_financeiro {
    
    protected $form_name = 'controle_financeiro';
    /*Nome do form de filtro de pesquisa*/
    protected $cf_form_name = 'cf_filtros_extrato'; 
    protected $xmlFileName = 'controle_financeiro.xml';
    
    
    /*Variaveis usadas na action imprimir_registro*/
    protected $irHeader1 = 'Controle Financeiro';
    
    /*Variaveis de controle financeiro*/
    
    protected $dateVars = array(
        'date' => 'DATA', /*Data limite da pesquisa*/
        'dateStart' => 'data_inicio' /*Data de inicio da pesquisa*/
    );
    protected $dataFinal = "data_final";
    
    protected $default_filters = array('ID_FORNECEDOR','ID_TRABALHO' ,'FATURADO' ,'ID_CONTA', 'ID_CLIENTE', 'ID_AGENCIA');
    
    protected $lblPrevBalance = "Saldo Anterior";
    
}

?>