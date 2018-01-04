<?php

/*
 * To change this template, choose Tools | Templates
 * and open the template in the editor.
*/

/**
 * Cuida de criar a paginacao de formularios...
 *
 * @author juniorfox
 */
class pagination {

    private $_total_pages;  /* Total de paginas */
    private $_total_results;/*Total de resultados*/
    private $_page;         /* Pagina atual a ser impressa */
    private $_rpp;          /* Resultados por pagina */
    private $_page_var;     /* Nome da variavel de paginação atribuida no $_GET */
    public $templateDir;    /* Diretorio onde encontra-se templates de paginacao */
    public $template;       /* Nome do template de paginacao */
    public $xmlData;        /* Dados XML do template escolhido */
    public $templVars;      /* Variaveis de template a mais para serem aplicadas aos links, quando solicitado */


    public function __construct($total, $rpp, $page_var = 'page',$template = 'simple') {
        $page = null;
        if (isset($_GET[$page_var])) {
            $page = $_GET[$page_var];
        }
        if (!$page) {
            $page = 1;
        }
        $this->_page_var = $page_var;
        $this->_total_pages = ceil($total / $rpp); /* Retorna o total de paginas */
        $this->_total_results = $total;
        if (!$this->_total_pages) {
            $this->_total_pages = 1;
        }
        if ($page >= $this->_total_pages) {
            $page = $this->_total_pages;
        }
        $this->_page = $page; /* Retorna a pagina atual a ser impressa */
        $this->_rpp = $rpp; /* Retorna quantidade de resultados por pagina */
        $this->template = $template;
        $this->templateDir = str_replace(DIRECTORY_SEPARATOR, '/', realpath(dirname(__FILE__)))."/pagination/";
    }

    public function limits() {/* Retorna Array os valores a serem inseridos em "LIMIT", na query de SQL */
        $limit['start'] = (($this->_page - 1) * $this->_rpp);
        $limit['end'] = ($this->_rpp);
        return $limit;
    }

    public function do_links() {/* Cria os links a serem impressos */
        $this->_getTemplateXML();
        /* Adiciona vars do $this->templVars ao array de impressao */
        if (is_array($this->templVars) && $this->templVars) {
            $vars = $this->templVars;
        }
        $vars['first'] = $this->_do_first();
        $vars['prevs'] = $this->_do_prevs();
        $vars['prev'] = $this->_do_prev();
        $vars['actual'] = $this->_do_actual();
        $vars['next'] = $this->_do_next();
        $vars['nexts'] = $this->_do_nexts();
        $vars['last'] = $this->_do_last();
        foreach(array_keys($vars) as $key) {
            if(is_array($vars[$key])) {
                $vars[$key]['__COMMAND'] = 'list';
                $vars[$key]['__FILE'] = $this->templateDir . $this->template . '/' . $this->template . '.links.html';
            }
        }
        /* Adiciona demais variaveis */
        $vars['page'] = $this->_page;
        $vars['res_per_page'] = $this->_rpp;
        $vars['total'] = $this->_total_pages;
        $vars['total_results'] = $this->_total_results;
        $view = new view();
        return $view->process_view($vars, $this->templateDir . $this->template . '/' . $this->template . '.html');
    }

    private function _do_first() {
        /*Cria link primeira pagina*/
        $arrFirstLink = array();
        if ($this->_page > 1 && strtolower((string) $this->xmlData->first->doFirst) == 'yes') {
            $link = $this->_apply_templVars();
            $link['page_name'] = 1;
            $link['link'] = $this->_make_get_url(1);
            if($this->xmlData->first->label) {
                $link['page_name'] = (string) $this->xmlData->first->label;
            }
            $arrFirstLink[] = $link;
        }
        return $arrFirstLink;
    }

    private function _do_prev() {
        /* Cria o link anterior*/
        $arrPrevLink = array();
        if(strtolower((string) $this->xmlData->prev->doPrev) == 'yes') {
            $doFirstFlag =  strtolower((string)$this->xmlData->first->doFirst);
            $prevPage = $this->_page - 1;
            if(($prevPage > 1 && $doFirstFlag == 'yes') || ($prevPage > 0 && $doFirstFlag != 'yes') ) {
                $link = $this->_apply_templVars();
                $link['page_name'] = $prevPage;
                $link['link'] = $this->_make_get_url($prevPage);
                if($this->xmlData->prev->label) {
                    $link['page_name'] = $this->xmlData->prev->label;
                }
                $arrPrevLink[] = $link;
            }
        }
        return $arrPrevLink;
    }

    private function _do_prevs() {
        /* Cria codigos dos links anteriores */
        $arrPrevLinks = array();
        $firstPage = $this->_page - $this->xmlData->prev->linksPerPage;
        /*Verica se listar primeiro esta true no XML. Se estiver, este comeca a contar a partir do link 2, caso não, conta do link 1*/
        $min_page = 1;
        $doFirstFlag =  strtolower((string)$this->xmlData->first->doFirst);
        if($doFirstFlag == 'yes') {
            $min_page = 2;
        }
        if($firstPage < $min_page) {
            $firstPage = $min_page;
        }
        if(strtolower((string) $this->xmlData->prev->doAllPrevs) == 'yes') {
            $begin_listing = 1;
            if(strtolower((string) $this->xmlData->prev->hidePrevForAll) == 'yes') {
                $begin_listing = 2;
            }
            for($i = $this->_page - $begin_listing; $i >= $firstPage; $i--) {
                $link = $this->_apply_templVars();
                $link['page_name'] = $i;
                $link['link'] = $this->_make_get_url($i);
                $arrPrevLinks[] = $link;
            }
        }
        return array_reverse($arrPrevLinks);
    }

    private function _do_next() {
        /*Cria link seguinte*/
        $arrNextLink = array();
        if(strtolower((string) $this->xmlData->next->doNext) == 'yes') {
            $nextPage = $this->_page + 1;
            if($nextPage <= $this->_total_pages) {
                $link = $this->_apply_templVars();
                $link['page_name'] = $nextPage;
                $link['link'] = $this->_make_get_url($nextPage);
                if($this->xmlData->next->label) {
                    $link['page_name'] = $this->xmlData->next->label;
                }
                $arrNextLink[] = $link;
            }
        }
        return $arrNextLink;
    }

    private function _do_nexts() {
        /* Cria links depois do proximo ate antes do ultimo, ou limitado pela quantidade de links no XML */
        $arrNextLinks = array();
        if($this->xmlData->next->doAllNexts) {
            $total_pages = $this->_total_pages;
            if(strtolower((string) $this->xmlData->last->doLast) == 'yes') {
                $total_pages = $this->_total_pages - 1;
            }
            if(($this->xmlData->next->linksPerPage + $this->_page) < $this->_total_pages & $this->xmlData->next->linksPerPage) {
                $total_pages = $this->_page + $this->xmlData->next->linksPerPage;
            }
            $begin_listing = 1;
            if(strtolower((string) $this->xmlData->next->hideNextForAll) == 'yes') {
                $begin_listing = 2;
            }
            for ($i = $this->_page + $begin_listing; $i <= $total_pages; $i++) {
                $link = $this->_apply_templVars();
                $link['page_name'] = $i;
                $link['link'] = $this->_make_get_url($i);
                $arrNextLinks[] = $link;
            }
        }

        return $arrNextLinks;
    }

    private function _do_last() {
        /*Cria link para ultima pagina*/
        $arrLastLink = array();
        if ($this->_page < $this->_total_pages && strtolower((string) $this->xmlData->last->doLast) == 'yes') {
            $link = $this->_apply_templVars();
            $link['link'] = $this->_make_get_url($this->_total_pages);
            $link['page_name'] = $this->_total_pages;
            if($this->xmlData->last->label) {
                $link['page_name'] = (string) $this->xmlData->last->label;
            }
            $arrLastLink[] = $link;
        }
        return $arrLastLink;
    }

    private function _do_actual() {
        $page_name = $this->_page;
        if ($this->_page == 1 && $this->xmlData->first->label && strtolower((string) $this->xmlData->first->doFirst) == 'yes') {
            $page_name = (string) $this->xmlData->first->label;
        }
        if ($this->_page == $this->_total_pages && $this->xmlData->last->label && strtolower((string) $this->xmlData->last->doLast) == 'yes') {
            $page_name = (string) $this->xmlData->last->label;
        }
        if ($page_name != 1 && $page_name != $this->_total_pages) {
            return $page_name;
        } else {
            return "";
        }
    }

    private function _apply_templVars() {
        if (is_array($this->templVars) && $this->templVars) {
            return $this->templVars;
        }
    }

    private function _make_get_url($page) {
        $get = $_GET;
        $get[$this->_page_var] = $page;
        $url = NULL;
        foreach (array_keys($get) as $get_key) {
            if (!$url) {
                $url = "?$get_key=" . $get[$get_key];
            } else {
                $url .= "&$get_key=" . $get[$get_key];
            }
        }
        return $url;
    }

    private function _getTemplateXML() {
        $fileName = $this->templateDir . $this->template . '/' . $this->template . '.xml';
        $handle = fopen($fileName, 'r');
        $xmldata = fread($handle, filesize($fileName));
        $this->xmlData = new SimpleXMLElement($xmldata);
    }

}

?>