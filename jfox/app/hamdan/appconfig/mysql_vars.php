<?php
global $prefixo; /*Captura o prefixo das tabelas, se necessário */
/*
 * Carrega as vars de mysql em $mysql_vars
 */
if ($test_ambient)
{
    /*Ambiente de Desenvolvimento*/
    $mysql_vars['host'] = 'localhost';
    $mysql_vars['db_name'] = 'hamdan';
    $mysql_vars['login'] = 'root';
    $mysql_vars['passwd'] = '123';    
    $mysql_vars['prefixo'] = strtoupper($prefixo);
} else
{
    /*Em Produção*/
    $mysql_vars['host'] = 'localhost';
    $mysql_vars['db_name'] = 'radiovro_sr_programa';
    $mysql_vars['login'] = 'radiovro_sr';
    $mysql_vars['passwd'] = 'sr12345';
    $mysql_vars['prefixo'] = strtoupper($prefixo);
}
?>
