<?php
/**
 * Gerencia os Widgets, pecas para serem encaixadas nos templates, ou nos
 * formularios...
 *
 * @author Junior
 */
class widgets {
	private   $_widget_name;
	private   $_widget_vars;
	private   $_erro;
	private   $_widget_body_renderized;
        private   $_widget_header_renderized;

	public function __construct($widget_name,$widget_vars = ''){
		global $global_vars;
		$this->_widget_name = $widget_name;
		$this->_widget_vars = $widget_vars;
		$widget_file = $global_vars['widget_path'].$widget_name."/action/".$widget_name."Widget.php";
		/*Primeiro checa se o arquivo de Widget */
		if(!file_exists($widget_file)){
			$this->_erro = true;
			$this->_widget_renderized = "<b>Erro:</b> Em <i><b>Widget</b> $widget_name </i>, o arquivo <b> $widget_file </b> n&atilde;o foi encontrado<br />";
		}
		/*Caso nada tenha dado errado, monta e processa o Widget*/
		if(!$this->_erro){
			include_once $widget_file;
			$widget_class = $widget_name."Widget";
			$widget = new $widget_class($widget_name,$widget_vars);
			$this->_widget_body_renderized = $widget->show_widget();
                        $this->_widget_header_renderized = $widget->show_widget_header();
		}
	}
/*Apenas retorna o show_widget do Widget carregado...*/
	public function show_widget(){
		return $this->_widget_body_renderized;
	}

        public function show_widget_header(){
            return $this->_widget_header_renderized;
        }
}
?>
