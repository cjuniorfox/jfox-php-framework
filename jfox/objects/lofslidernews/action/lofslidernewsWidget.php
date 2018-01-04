<?php

/**
 * Widget que insere menus em paginas de internet
 *
 * @author junior
 */
class lofslidernewsWidget extends widgetController {

    protected function _set_default_vars() {
        $this->set_default_widget_var('null', 'null');
        $this->set_default_widget_var('xmlFile', $this->global_vars['public'] . "resources/lofslidernews/exemplo.xml");
        $this->set_default_widget_var('template', 'index');
        $this->set_default_widget_var('width', '892px');
        $this->set_default_widget_var('height', '300px');
    }

    protected function _check_error_vars() {
        $error = $this->test_widget_var('null');
        return $error;
    }

    public function action() {
        $this->set_template($this->widget_vars['template']);
        if (isset($this->widget_vars['xmlFile'])) {
            $this->array_data['wapper'] = $this->_processXML();
            $this->array_data['javascript'] = $this->array_data['wapper']['javascript'];
            $this->array_data['width'] = $this->widget_vars['width'];
            $this->array_data['height'] = $this->widget_vars['height'];
            unset($this->array_data['wapper']['javascript']);
        }
        $this->array_data['navigator'] = $this->array_data['wapper'];
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
                'link' => (string) $xml->wapper[$i]->link
            );
        }
        $data['javascript'] = (string) $xml->javascript;
        return $data;
    }

}

?>
