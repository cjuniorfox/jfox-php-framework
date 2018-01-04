<?php

/*
 * To change this template, choose Tools | Templates
 * and open the template in the editor.
 */

/**
 * Description of pdfjsAction
 *
 * @author cjuniorfox
 */
class pdfjsAction extends template{
    public function action(){
        $this->array_data['pdf_url'] = $_GET['pdf'];
    }
    
    public function iframeAction(){
        $this->array_data['pdf_url'] = $_GET['pdf'];
        $this->array_data['rand_id'] = "pdfid".rand(1000, 9999);
    }
    
    public function ajaxAction(){
        
    }
    
    public function vuzitAction(){
        
    }
    public function jsacrobat_detectionAction(){
        header('Content-type: application/javascript');
        $this->array_data['__FILE'] = jfox_template_path($this->template_name . "/view/js/acrobat_detection.js");
    }
}

?>
