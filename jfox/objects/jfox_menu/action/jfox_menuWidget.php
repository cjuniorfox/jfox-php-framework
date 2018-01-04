<?php

/**
 * Widget que insere menus em paginas de internet
 *
 * @author junior
 */
class jfox_menuWidget extends widgetController {

    private $_object_mysql;

    protected function _set_default_vars() {
        $this->set_default_widget_var('null', 'null');
        $this->set_default_widget_var('table_menu_name', 'tb_jfox_menu');
        $this->set_default_widget_var('load_jquery', false);
        $this->set_default_widget_var('load_css', true);
    }

    protected function _check_error_vars() {
        $error = $this->test_widget_var('null');
        $error = $this->test_widget_var('id');
        $error = $this->test_widget_var('template');
        $error = $this->test_widget_var('table_menu_name');
        return $error;
    }

    public function action() {
        $this->_startMysql();
        $this->set_template($this->widget_vars['template']);
        $this->array_data['menu'] = $this->_setMenus($this->widget_vars['id'], true);
        $this->array_header['jquery'] = '';
        $this->array_header['css']['__FILE'] = $this->widget_env['template_path'] . "nocss.html";
        if ($this->widget_vars['load_jquery']) {
            $this->array_header['jquery'] = '<script src="/*SITE_PUBLIC_PATH*/resources/jquery_1.3.2/jquery-1.3.2.min.js" type="text/javascript"></script>';
        }
        if ($this->widget_vars['load_css']) {
            $this->array_header['css']['__FILE'] = $this->widget_env['template_path'] . "css.html";
        }
    }

    private function _setMenus($id, $masterMenu) {
        /* Submenus sao diferentes do menu principal, a variavel masterMenu testa se esta sendo processado o menu principal ou algum submenu */
        if ($masterMenu) {
            $menu['__FILE'] = $this->widget_env['template_path'] . "menu.mastermenu.html";
        } else {
            $menu['__FILE'] = $this->widget_env['template_path'] . "menu.submenu.html";
        }
        $menu['fields'] = $this->_setFields($id);
        return $menu;
    }

    private function _setFields($id) {
        
        $sql_variables['id'] = $id;
        $sql_variables['table_menu_name'] = $this->widget_vars['table_menu_name'];
        $result = $this->_object_mysql->search($sql_variables['table_menu_name'], array('submenu_id'=> $sql_variables['id']));
        while ($line = mysql_fetch_array($result)) {
            $item['name'] = $line['name'];
            $item['label'] = $line['label'];
            $item['link'] = $line['link'];
            $item['submenu'] = $this->_setMenus($line['id'], false);
            $fields[] = $item;
        }
        $fields['__COMMAND'] = "list";
        $fields['__FILE'] = $this->widget_env['template_path'] . "field.html";
        return $fields;
    }

    private function _startMysql() {
        $sql_variables['table_menu_name'] = $this->widget_vars['table_menu_name'];
        $this->_object_mysql = new mysql();
        $this->_object_mysql->utf8_encode = false;
        $this->_object_mysql->execute_query_file($this->widget_env['viewsql_path'] . "tables.sql", $sql_variables);
    }

}

?>
