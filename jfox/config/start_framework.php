<?php
/**
 * Define carregamento de classes e inicia o frontController dando inicio a 
 * execução do Framework.
 */
include $jfox_path."config/global_vars.php";/*Carrega as variaveis globais basicas do framework na variavel global $global_vars*/
include $app_path."appconfig/app_global_vars.php";/*Complementa $global_vars com variaveis referentes a aplicação sendo executada*/

//include $jfox_path."config/load_lib.php";/*Carrega principais bibliotecas do Framework*/
//include $app_path."appconfig/load_applib.php";/*Carrega bibliotecas da aplicação*/

include $global_vars['lib_path']."functions.php";
include $global_vars['app_etc_path']."mysql_vars.php"; /*Carrega as variaveis de mysql*/
add_to_library($global_vars['lib_path']);
add_to_library($global_vars['applib_path']);
spl_autoload_register('autoload_lib');

new frontController(@$urlFormat);