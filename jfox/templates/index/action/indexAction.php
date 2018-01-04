<?php
/**
 * Template Padrao do site...
 *
 * @author Junior
 */
class indexAction extends template {

	public function action(){
            $mainMenu = $this->_load_mainMenu();
            $this->array_data['mainMenu'] = $mainMenu['body'];
            $this->array_data['mainMenu_header'] = $mainMenu['header'];
            $this->array_data['template_header'] = $this->_template_headers();
		/*Code for Default template actions*/
	}
        
        public function error404Action(){
            
        }


        /*Funcoes internas do controlador*/

        private function _load_mainMenu(){
            $widget_vars['id'] = '1';
            $widget_vars['template'] = 'jdmenu';
            $widget_vars['table_menu_name'] = 'menu_principal';
            $index_menu = new widgets('jfox_menu',$widget_vars);
            $data['body'] = $index_menu->show_widget();
            $data['header'] = $index_menu->show_widget_header();
            return $data;
        }

        private function _template_headers(){
            $header = '<script language="javascript" src="-!SITE_PATH!-resources/jquery_1.3.2/jquery-1.3.2.min.js"></script>';
            $header.= '<script language="javascript" src="-!SITE_PATH!-resources/js.urlencode_decode/urlencode_decode.js"></script>';
            return $header;
        }
}
?>
