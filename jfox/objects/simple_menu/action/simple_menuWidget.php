<?php
/*
 * Widget de Menu Simples.
 */

/**
 *
 * @author Junior_FOX
 */
class simple_menuWidget extends widgetController {

    private $_object_mysql; /*Objeto que ira trabalhar com a tabela do menu*/
    private $_menu_template; /*Template do menu*/

    protected function _set_default_vars(){
        $this->set_default_widget_var('null','null');
    }

    protected function _check_error_vars(){
        $error = $this->test_widget_var('null');
        $error = $this->test_widget_var('menu_name');
        return $error;
    }

    public function action(){
        $this->_start_mysql();
        $this->_set_menu();
    }

    private function _start_mysql(){
        $this->_object_mysql = new mysql();
        $this->_object_mysql->execute_query_file($this->widget_env['viewsql_path']."tables.sql");
        
    }

    private function _set_menu(){
        $sql_variables['menu_name'] = $this->widget_vars['menu_name'];
        $result = $this->_object_mysql->execute_query_file($this->widget_env['viewsql_path']."set_menu.sql", $sql_variables);
        $this->array_data['fields'] =  $this->_set_fields($result);
        $this->array_data['__FILE'] = $this->widget_env['view_path']."templates/".$this->_menu_template."/index.html";
    }

   private function _set_fields($result){
       $trans = get_html_translation_table(HTML_ENTITIES);
       while($line = mysql_fetch_array($result)){
           $item['field_label']   = strtr($line['field_label'],$trans);
           $item['field_link']    = $line['field_link'];
           $fields[]                = $item;
           if(!$this->_menu_template) $this->_menu_template = $line['menu_template'];/*Fora do escolpo, apenas "pesca" do processo, o nome do template, e passa pra fora do objeto´*/
       }
       
       
       $fields['__FILE']= $this->widget_env['view_path']."templates/".$this->_menu_template."/index.fields.html";
       $fields['__COMMAND'] = 'list';
       return $fields;
    }
}

?>