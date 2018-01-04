<?php
/**
 * Esta classe processa as informacoes necessarias para operar as Widgets...
 *
 * @author Junior
 */
 
abstract class widgetController {
	protected $global_vars; /*Variaveis globais*/
	protected $widget_name; /*Nome da Widget*/
	protected $widget_vars; /*Variaveis da Widget que sao passados de quem a chama*/
	protected $array_data;  /*Variaveis referentes a impressao de body*/
        protected $array_header;/*Variaveis referentes a impressao de header*/
        protected $widget_env;  /*Variaveis de ambiente do Widget, como Pasta de Views, pasta de actions, etc*/
       


	public function __construct($widget_name,$widget_vars){
            global $global_vars;
            $this->global_vars                      = $global_vars;
            $this->widget_name                      = $widget_name;
            $this->widget_vars                      = $widget_vars;
            $this->widget_env['path']              = $global_vars['widget_path'].$widget_name."/";
            $this->widget_env['lib_path']          = $this->widget_env['path']."resources/lib/";
            $this->widget_env['view_path']         = $this->widget_env['path']."views/";
            $this->widget_env['viewsql_path']      = $this->widget_env['path']."viewsql/";
            $this->widget_env['action_path']       = $this->widget_env['path']."action/";
            $this->widget_env['res_path']          = $this->widget_env['path']."resources/";
            $this->widget_env['template_path']     = $this->widget_env['view_path']; /*Diretorio aonde se encontram os templates*/
            $this->array_data['error'];
            $this->_set_default_vars();
            $this->_check_error_vars();
            if($this->array_data['error']){
                $this->array_data['__FILE'] = $this->widget_env['view_path']."error.html";
            }else{
                $this->action();
            }
        }

	public function show_widget(){
            if(!isset($this->array_data["__FILE"])){
                $this->array_data["__FILE"] = $this->widget_env['template_path']."index.html";
            }
            $view = new view();
            return $view->process_view($this->array_data,$this->array_data["__FILE"]);
	}

        public function show_widget_header(){
            $this->array_header["__FILE"] = $this->widget_env['template_path']."header.html";
            $this->array_header["___WIDGET_RES_path"] = $this->widget_env['res_path'];
            $view = new view();
            return $view->process_view($this->array_header,$this->array_header['__FILE']);
        }

        protected function set_template($template_name){/*O template sera sempre uma pasta dentro da pasta View, com os arquivos padrao*/
            if(file_exists($this->widget_env['view_path']."templates/".$template_name."/index.html")){
                $this->widget_env['template_path'] = $this->widget_env['view_path']."templates/".$template_name."/";
            }else{
                echo "<b>Erro:</b> Em <i>widget</i>, o template <b>$template_name</b> não foi encontrado.<br />";
            }
        }

        protected function set_default_widget_var($name,$value){
            if(!isset($this->widget_vars[$name]))
                   $this->widget_vars[$name] = $value;
        }

        protected function test_widget_var($widget_var){
            $widget_name = $this->widget_name;
            if(!isset($this->widget_vars[$widget_var]))
                    $this->array_data['error'].= "<b>Widget[$widget_name]</b> <em>Error: no</em> <em><b>widget_vars:</b> variavel <b>'$widget_var'</b> </em> nao definido.<br/>";
        }

        protected function start_mysql($table_name,$mysql_fields){
            global $mysql_vars;

            $connection     = mysql_connect($mysql_vars['host'], $mysql_vars['login'], $mysql_vars['passwd']) or die(mysql_error());
            $db             = mysql_select_db($mysql_vars['database']) or die(mysql_error());
            $check          = mysql_fetch_array(mysql_query("CHECK TABLE $table_name")) or die(mysql_error());
		if ($check['Msg_text'] != 'OK'){
			mysql_query(" CREATE TABLE `$table_name` (
                        $mysql_fields
                        ) ENGINE = MYISAM ") or die(mysql_error());
		}
           
        }
}
?>
