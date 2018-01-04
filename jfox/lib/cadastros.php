<?php

/**
 * 
 * @version 2.1
 * 
 * Description of cadastros
 * 
 * Cuida de fazer a ponte na criação de tala de cadastros e pequisas em geral.
 *
 * @author Carlos Junior
 */
class cadastros {
    /*     * objeto xmlForms */

    const paginacao_res_pp = 10;
    const pag = 'pag'; //Variavel usada para definir paginação.
    const paginacao_template = 'simple_jquery';
    const msg_caixa_busca = "Digite sua busca";
    const msg_lista_sem_itens = "Sem resultados";
    const LISTAR_SEM_REGISTROS = "<blockquote>Sem registros...</blockquote>";

    /**
     * Array com a lista de todos os campos que podem automaticamente virar uma listagem de campos para lista_campos
     */
    private $_auto_field_types = array('input', 'inputDatePicker', 'input_autoComplete', 'mysql_selectbox', 'selectbox');
    static $version = 2.1;
    public $xmlForms;
    public $xmlFile;
    /*     * Controller_vars a ser herdado pelo controlador que chama este objeto */
    private $_controller_vars = array();
    /*     * Caso este seja definido, Será definido template e action do template e $templateName é invalidado */
    private $templateArray = array();
    /*     * Aplicavel apenas se templateArray não foi definido, define nome do template, as actions são definidos pelos métodos */
    public $templateName = 'appTema';

    /**
     * Usado em Cadastro e update.
     * Quando setado como True e ao cadastro ou update ser postado, registra
     * primary_key do registro salvo/atualizado na session: $_SESSION['cadastros'][$form_name][$campos[pk]]
     */
    public $pk_on_session = false;

    /**
     * Variavel padrão para identificar a session usada pelo objeto quando aplicado. padrão %%cadastros
     */
    public $session_name = '%%cadastros';

    public function __construct($controller_vars, $xmlForms) {
        $this->xmlFile = $xmlForms;

        $this->_controller_vars = $controller_vars;
    }

    public function registro($form_name, $id) {
        $XMLFR = new XMLForms_reader($this->xmlFile, $form_name);
        if ($id) {
            return $XMLFR->readreport($id);
        }
    }

    public function ler($form_name, $id) {
        $XMLFR = new XMLForms_reader($this->xmlFile, $form_name);
        if ($id) {
            return $XMLFR->read($id);
        }
    }

    /**
     * Cria formulário de cadastro
     * @return array - Dados retornados
     * @param string $form_name - Nome do formulario a ser criado
     * @param array $arrValues = Dados a preencher os campos do formulário
     */
    public function formulario_de_cadastro($form_name, $arrValues = array()) {
        $arrForm = array();
        $OXMLF = new XMLForms_fields($this->xmlFile, $form_name);
        if ($OXMLF->XMLForm->create_table == "true") {
            XMLForms_tables::create_table_from_xml($this->xmlFile, $form_name);
        }
        if ($arrValues)
            $OXMLF->overwrite_values($arrValues);
        $arrForm['form'] = $OXMLF->create_form();
        $arrForm['div_id'] = 'cadastro';
        $arrForm['title'] = $OXMLF->tag('title');
        $arrForm['message'] = $OXMLF->tag('message');
        //Adiciona, se aplicavel, os comandos para exibir mensagem de redirecionamento para consulta de registro, ou auto_redirect que redireciona automaticamente o registro
        $arrForm['show_redirect_message'] = $OXMLF->tag('show_redirect_message');
        $arrForm['auto_redirect'] = $OXMLF->tag('auto_redirect');
        //Se as tags nao tiverem sido adicionadas, adiciona a estas o valor padrão false.
        if (!$arrForm['show_redirect_message'])
            $arrForm['show_redirect_message'] = 'false';
        if (!$arrForm['auto_redirect'])
            $arrForm['auto_redirect'] = 'false';
        return $arrForm;
    }

    /**
     * Posta os dados e retorna ID ou mensagem de erro.
     * Ambos são para serem usados em respostas JSON
     * @param string $form_name - Nome do formulário trabalhado
     * @param array $POST - Dados postados
     */
    public function inserir_novo_cadastro($form_name, $POST) {

        $OXMLP = new XMLForms_post($this->xmlFile, $form_name);
        $erro = $OXMLP->insert($POST);
        if ($erro)
            return array(
                'id' => NULL,
                'msg_erro' => $erro
            );
        $primary_key = $OXMLP->primary_key();
        $id = $OXMLP->last_insert[$primary_key];
        $table = $OXMLP->table();
        //Pega a posicao do elemento na lista de resultados
        $Mysql = new mysql();
        $list_pos = $Mysql->get_line_position($table, $primary_key, $id);
        //list_pos é usado para, quando depois de cadastrar o sistema redirecionar para lista de resultados, listar a pagina e o res desejado.
        $pag = ceil($list_pos / self::paginacao_res_pp);
        $this->_PK_on_session($form_name, $OXMLP->last_insert[$primary_key]);
        return array(
            'id' => $id,
            'list_pos' => $list_pos,
            'pag' => $pag,
            'msg_erro' => NULL
        );
    }

    public function remover_cadastro($form_name, $id_reg, $field_name = NULL) {

        $OXMLP = new XMLForms_post($this->xmlFile, $form_name);
        $erro = $OXMLP->delete_one_line($id_reg, $field_name);
        if ($erro)
            return array(
                'message' => $erro
            );
        else
            return array(
                'message' => 'ok'
            );
    }

    /**
     * Atualiza um cadastro existente.
     * @param string $form_name - Nome do formulário trabalhado
     * @param array $POST - Dados postados
     * @param string $id_reg - ID do registro a ser atualizado
     * @param string $field_name - Nome do campo que será atualizado. Este pode ser NULL. Caso seja, será definido a PK como field
     */
    public function atualizar_um_cadastro($form_name, $POST, $id_reg, $field_name = NULL) {

        $OXMLP = new XMLForms_post($this->xmlFile, $form_name);
        $erro = $OXMLP->update($POST, $id_reg, $field_name);
        if ($erro)
            return array(
                'id' => NULL,
                'msg_erro' => $erro
            );
        $this->_PK_on_session($form_name, $id_reg);
        return array(
            'id' => $id_reg,
            'msg_erro' => NULL
        );
    }

    /**
     * Carrega dados de um determinado registro, envia ao formulário de cadastro,
     * que cria o mesmo adicionando aos campos os valores da postagem carregada.
     */
    public function formulario_de_edicao($form_name, $id_reg) {

        $OXMLR = new XMLForms_reader($this->xmlFile, $form_name);
        $arrValues = $OXMLR->read($id_reg);
        return $this->formulario_de_cadastro($form_name, $arrValues);
    }

    /**
     * Responsável por criar a lista de resultados (aquela a esquerda) padrão
     * do cadastros.
     * @param string $form_name - Nome do formulário
     * @param string $busca - Termo utilizado na busca
     * @param array $campos - Campos usados na busca
     * @param array $lista_campo - Campos que serão impressos pela listagem
     * @param array arrCommands - Comandos para pesquisa. Este pode ficar em branco
     * @param bool $mostrar_link_documento - Quando true, adiciona a view o link para exibir pdf do documento.
     */
    public function listar($form_name, $busca, $busca_campos, $lista_campos, $arrCommands = Array(), $mostrar_link_documento = false) {
        $OXMLR = new XMLForms_reader($this->xmlFile, $form_name);
        if (!$busca_campos)
            $busca_campos = $OXMLR->mysql_fields();
        if (!$lista_campos)
            $lista_campos = $this->_criar_lista_campos($OXMLR);
        $arrSearch = $this->_arrSearch_busca_simples($busca, $busca_campos);
        $arrCommands = array_merge(array('logic_operator' => 'REGEXP'), $arrCommands);
        $p = $this->_paginacao($OXMLR->filter_and_totals($arrSearch, $arrCommands));
        $lista = $OXMLR->do_reportlist($p['limits']['start'], $p['limits']['end']);
        if (!$lista)//Se não existir lista, retorna mensagem de que não existem itens
            $lista = self::msg_lista_sem_items;
        if (!$busca)
            $busca = self::msg_caixa_busca;
//Agora alimenta o array_data
        return array(
            'pesq_value' => $busca,
            'paginacao' => $p['links'],
            'list' => self::_organizar_lista($lista['list'], $lista_campos, $mostrar_link_documento),
            'template' => array($this->templateName, 'listagemListar')
        );
    }

    public function listar_busca_avancada($form_name, $arrPesq, $lista_campos = array(), $arrCommands = Array(), $campo_ordenar) {
        //Remove a paginação do arrPesq, caso exista.
        if (array_key_exists(self::pag, $arrPesq))
            unset($arrPesq[self::pag]);
        $Reader = new XMLForms_reader($this->xmlFile, $form_name);
        if ($campo_ordenar) {
            if ($Reader->mysql_fields(array($campo_ordenar))) {
                $arrCommands['orderby'] = $campo_ordenar . " ASC";
            }
        }
        if (!$lista_campos)
            $lista_campos = $this->_criar_lista_campos($OXMLR);
        $arrCommands = array_merge(Array('condition' => 'AND'), $arrCommands);
        $totais = $Reader->filter_and_totals($arrPesq, $arrCommands);
        $Pag = new pagination($totais, 10, self::pag, self::paginacao_template);
        $Pag->templVars['source_page'] = $this->_controller_vars['controller_name'] . "/" . $this->_controller_vars['action_name'];
        $Pag->templVars['target'] = "$('#listagem-body')";
        $Pag->templVars['links_name'] = "listagem-body";
        $limites = $Pag->limits();
        /* $array_data começa a ser preenchido daqui. Tudo colocado acima daqui será sobrescrito */
        $array_data = $Reader->do_reportlist($limites['start'], $limites['end'], $lista_campos);
        $array_data['total_resultados'] = $totais;
        if ($array_data['list_header'])
            $array_data['foot_colspan'] = count($array_data['list_header']);
        $array_data['paginacao'] = $Pag->do_links();
        $array_data['campo_ordenar'] = $campo_ordenar;
        if (!isset($array_data['item_list']))
            $array_data['item_list'] = self::msg_lista_sem_itens;
        return $array_data;
    }

    /**
     * Verifica se $arrTemplate passado é array ou string.
     *  
     * Se for Array, alimenta $this->templateArray,
     * Se for String, alimenta $this->templateName 
     * com o valor de $arrTemplate
     */
    public function definir_template($template) {
        if (is_array($template) && $template)
            $this->templateArray = $template;
        elseif (is_string($template))
            $this->templateName = $template;
    }

    /**
     * Organiza lista para ser aplicavel corretamente na view.
     * @param array $lista - Lista de Itens a serem organizados
     * @param array $campos - Nomes dos campos a serem adicionados a lista
     * @param bool $mostrar_view_documento - Quando true, permite mostrar na lista o link para a view do documento.
     * @return array - Lista de itens organizada
     */
    private static function _organizar_lista($lista, $campos, $mostrar_view_documento) {
        //Aplica NULL as variáveis usadas por padrão.
        $item_list = $first_item = $primary_key = $mini_info_label = $mini_info_value = NULL;
        if ($mostrar_view_documento)
            $links_documento = array();
        else
            $links_documento = null;
        $novaLista = array();
        $novaLinha = array();
        //Define itens de cabeçalho para listagem de itens
        $first_item_key = $mini_info_key = null;
        if (isset($campos[0]))
            $first_item_key = $campos[0];
        if (isset($campos[1]))
            $mini_info_key = $campos[1];
        foreach ($lista as $sublista) {
            foreach ($sublista as $linha) {
                foreach ($linha as $item) {//Corre todos os registros
                    if ($item['key'] == $first_item_key) //Se ter definido o first Item
                        $first_item = $item['value'];
                    elseif ($item['key'] == $mini_info_key) { //Se definido o mini info
                        $mini_info_label = $item['label'];
                        $mini_info_value = $item['value'];
                    } elseif (array_search($item['key'], $campos) !== FALSE) { //Ao processar first_item e mini_info, processa demais valores
                        $item_list[] = array(
                            'value' => $item['value'],
                            'label' => $item['label']
                        );
                    }
                    if ($item['type'] == 'primary_key') //Captura a primary key para aplicar sua variável aonde necessário
                        $primary_key = $item['value'];
                }
                if(!$item_list){
                    $item_list = NULL;
                }
                $novaLinha[] = array(
                    'link_documento' => $links_documento,
                    'item_list' => $item_list,
                    'first_item' => $first_item,
                    'primary_key' => $primary_key,
                    'mini_info_label' => $mini_info_label,
                    'mini_info_value' => $mini_info_value
                );
                $item_list = array();
            }
            $novaLista[] = $novaLinha;
        }
        if (!$novaLinha) { //Se não existirem registros, retorna mensagem de erro
            return self::LISTAR_SEM_REGISTROS;
        }
        return $novaLinha;
    }

    /**
     * Este é usado quando não há uma lista de campos para ser adicionada.
     * Acessa o XMLForm e cria uma lista de campos que sejam os listados na var global auto_field_campos
     * @param T_Object $OXMLF - Objeto já instanciado de XMLForms_fields
     */
    private function _criar_lista_campos($OXMLF) {
        $lista_campos = array();
        $XMLForm = $OXMLF->XMLForm;
        foreach ($XMLForm->field as $Field) {
            if (array_search($Field->field_type, $this->_auto_field_types) !== FALSE)
                $lista_campos[] = (string) $Field['name'];
        }
        return $lista_campos;
    }

    /**
     * Cria o arrSearch padrão para busca simples
     */
    private function _arrSearch_busca_simples($busca, $campos) {
        $arrSearch = array();
        $busca = stringParaBusca($busca);
        foreach ($campos as $chave) {
            $arrSearch[$chave] = $busca;
        }
        return $arrSearch;
    }

    private function _paginacao($total_res) {

        $Pagination = new pagination($total_res, self::paginacao_res_pp, self::pag, self::paginacao_template);
        $Pagination->templVars['source_page'] = $this->_controller_vars['controller_name'] . "/" . $this->_controller_vars['action_name'];
        $Pagination->templVars['target'] = "$('#listagem-left')";
        $Pagination->templVars['links_name'] = "listagem-left";
        return array(
            'limits' => $Pagination->limits(),
            'links' => $Pagination->do_links()
        );
    }

    private function _PK_on_session($form_name, $id) {
        if (!$this->pk_on_session)
            return null;
        @session_start();
        $_SESSION[$this->session_name][$form_name] = $id;
    }

    /**
     * Retorna url a ser acessada quando um registro é gravado ou atualizado
     */
    public function target_url($form_name, $formType = "insert") {
        $XMLForms = new XMLForms($this->xmlFile, $form_name);
        $target = $insert = $update = "{SELF_URL}";
        if ($XMLForms->tag('target_url'))
            $insert = $update = $target = (string) $XMLForms->tag('target_url');
        if ($XMLForms->tag('target_url_insert'))
            $update = $insert = (string) $XMLForms->tag('target_url_insert');
        if ($XMLForms->tag('target_url_update'))
            $update = (string) $XMLForms->tag('target_url_update');
        /* Capturou valores? agora retorna o valor desejado */
        switch ($formType) {
            case "insert":
                return $insert;
            case "update":
                return $update;
        }
        return $target;
    }

}

?>