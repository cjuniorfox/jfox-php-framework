<?php

$global_vars['public']         = $public;
$global_vars['resources_path'] = $public."resources/";
$global_vars['etc_path']       = $jfox_path."config/";
$global_vars['widget_path']     =$jfox_path."objects/";
$global_vars['function_path']  = $jfox_path."functions/";
$global_vars['lib_path']       = $jfox_path."lib/";
$global_vars['widget_path']    = $jfox_path."objects/";
$global_vars['template_path']  = $jfox_path."templates/";

/*Carrega as variaveis de ambiente*/
include $global_vars['lib_path']."ambient_vars.php"; /*Retorna as variaveis de ambiente, como endereco do site, hostname, etc*/
include $global_vars['lib_path']."local_formats.php"; /*Retorna as variaveis de ambiente, como endereco do site, hostname, etc*/

$ambient_vars = new ambient_vars();
$global_vars['site_path']      = $ambient_vars->website_path();
$global_vars['hostname']       = $ambient_vars->hostname();


$local_formats = new local_formats();
$global_vars['local_formats']  = $local_formats->xmlData;
?>
