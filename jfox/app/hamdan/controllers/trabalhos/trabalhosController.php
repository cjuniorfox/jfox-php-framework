<?php

/**
 * Este controlador é apenas apontados, pois usa o controlador abstrato
 * cadastroAbastractController.
 *
 * @author juniorfox
 */
class trabalhosController extends AbsC_cadastros {
    
    protected $form_name = 'cad_trabalhos';
    protected $xmlFileName = 'trabalhos.xml';
    
    /*Variaveis usadas na action imprimir_registro*/
    protected $irHeader1 = 'Cadastro de Trabalhos';
    
    /**
     * Modificação específica para o sistema do Hamdan, adiciona extrato a
     * visualização de registro de JOB
     */
    public function updateAction(){
        /*Pega o ID, e repassa como ID_TRABALHO, para extrato direto*/
        $arrayVars = array();
        if(isset($_GET['id']))
            $arrayVars['ID_TRABALHO'] = $_GET['id'];
        $arrayVars['FATURADO'] = "S"; /*Listar apenas registros faturados*/
        $arrayVars['prev_period'] = '0';
        $this->array_data['below_form'] = $this->run_controller('controle_financeiro','extrato',array(arrayToStrGet($arrayVars)));
        parent::updateAction();
    }
}

?>