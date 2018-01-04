<?php

/**
 * Widget que insere menus em paginas de internet
 *
 * @author junior
 */
class jqueryslideshowWidget extends widgetController {

    protected function _set_default_vars() {
        $this->set_default_widget_var('null', 'null');
        $this->set_default_widget_var('xmlFile', $this->global_vars['public'] . "resources/jqueryslideshows/exemplo.xml");
        $this->set_default_widget_var('template', 'index');
        $this->set_default_widget_var('banner_id', 'featured');
        $this->set_default_widget_var('width', '892px');
        $this->set_default_widget_var('height', '300px');
        $this->set_default_widget_var('load_jquery', 'false');
        $this->set_default_widget_var('load_jquery-ui', 'false');
    }

    protected function _check_error_vars() {
        $error = $this->test_widget_var('null');
        return $error;
    }

    public function action() {
        $this->set_template($this->widget_vars['template']);
        if (isset($this->widget_vars['xmlFile'])) {
            $this->_js_framework(); /* Define-se se carrega-se ou nao o framework jquery */
            /*Vars de conteudo*/
            $this->array_data['wapper'] = $this->_processXML();
            $this->array_data['javascript'] = $this->array_data['wapper']['javascript'];
            $this->array_data['width'] = $this->widget_vars['width'];
            $this->array_data['height'] = $this->widget_vars['height'];
            $this->array_data['banner_id'] = $this->widget_vars['banner_id'];

            /*Vars de header*/
            $this->array_header['banner_id'] = $this->widget_vars['banner_id'];
            $this->array_header['width'] = $this->widget_vars['width'];
            $this->array_header['height'] = $this->widget_vars['height'];
            unset($this->array_data['wapper']['javascript']);
        }
        $this->array_data['navigator'] = $this->array_data['wapper'];
    }

    private function _js_framework() {
        $frameworks = array('jquery','jquery-ui');
        foreach ($frameworks as $item) {
            if ($this->widget_vars["load_$item"] == 'true') {
                $this->array_header[$item] = array();
            } else {
                $this->array_header[$item] = NULL;
            }
        }
    }

    private function _processXML() {
        $view = new view();
        $xmlData = $view->process_view(array(), $this->widget_vars['xmlFile']);
        $xml = new SimpleXMLElement($xmlData);
        for ($i = 0; $i < count($xml->wapper); $i++) {
            $data[] = array(
                'title' => (string) $xml->wapper[$i]->title,
                'content' => (string) $xml->wapper[$i]->content,
                'content_min' => (string) $xml->wapper[$i]->content_min,
                'url_thumb' => (string) $xml->wapper[$i]->url_thumb,
                'url_image' => (string) $xml->wapper[$i]->url_image,
                'link' => (string) $xml->wapper[$i]->link,
                'num_id' => $i
            );
        }
        $data['javascript'] = (string) $xml->javascript;
        return $data;
    }

}

?>
