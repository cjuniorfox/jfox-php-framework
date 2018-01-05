<?php
/***
 * Bem vindo ao Jfox Framework versão 2.0 beta
 * Os controladores sao chamados pela url no formato site/algo/action 
 * ou site/index.php/algo/action sendo que  "algo" é controlador e "action" a ação.
 * 
 * As views são os forms dos controladores.
 * 
 */
$program = "Hamdan - Contas a pagar e receber";
$public = "../";
$test_ambient = true; /*Se true = DESENVOLVIMENTO, false = PRODUCAO*/

$cliente = "h"; /*SR Programa: Nome da pasta para views e variaveis customizadas dos clientes*/
$prefixo = "h"; /*SR Programa: Prefixo usado no nome das tabelas*/
$jfox_path = "../../../jfox/"; /*Diretorio do Framework*/
$app_path = $jfox_path . "app/hamdan/";
if($test_ambient){
    $jfox_path = "../../../jfox/";
    $app_path  = "../../app/hamdan/"; /*Diretorio da Aplicação*/
}

include $jfox_path."config/start_framework.php";

?>