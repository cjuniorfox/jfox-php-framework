<?php

/**
 * XMLFORMS 2
 * @version 2.3.4;
 * -Gerencia formulários a partir de arquivos de comando XML.
 *
 * @author juniorfox
 * -----------------------------------------------------------------------------
 * Changelog: 20.09.2012
 * -----------------------------------------------------------------------------
 * 2.4  -2012.09.20- Adicionado método formTag
 * 2.3.3-2012.08.28- Adicionado recurso $disableDataStr
 * 2.3.2-2012.08.03- Adicionado metodo getPK e varoavel global $def_primary_key
 * 2.3.1-2012.06.25- Adicionado recurso de submeter inserções e atualizações de 
 *                  registro a situações implementadas na tag "options" no XML do form
 * 2.3-2012.05.29-Adicionado metodo que retorna lingua usada no XML.
 * 2.2-2012.04.13-Adicionado método formTitle retornando titulo do formulário.
 * 2.1-2012.03.29-Implementado recurso de criar e editar tabelas automaticamente.
 * 
 */
class xmlForms {

    public $version = 2.4;

    /**
     * -Instancia objeto XMLForms.
     * -Define arquivo XML a ser usado, e variaveis de view que poderão ser usados neste.
     * 
     * @param   string  |URL do arquivo XML $xmlFile,
     * @param   array   |Variaveis a serem aplicadas por View no arquivo XML.
     */
    private $_lib; /* Diretorio aonde se encontra a biblioteca de xmlForms */
    private $_xml; /* XML com os formularios a serem tratados */
    private $_form = array(); /* Formulario atualmente em trabalho */
    private $_Post; /* Objeto responsavel por postar dados */
    private $_Reader; /* Objeto responsavel por ler os dados da tabela */
    private $_postData = array(); /* Dados postados capturados pelo metodo postForms() ou capturados pelo readForm() */
    private $_lastInsertData; /* Quando postado, retorna a ultima inserção */

    /*     * Se true (padrão), analisa casos da tag "options" do XML antes de inserir ou atualizar (insert or update) */
    public $check_options = true;

    /*     * Nome do campo Primary key padrão quando o mesmo não for setado pelo XML */
    public $def_primary_key = "ID";

    /*     * Quando true, não converte data do BD para STR formatada. Retorna no formato do BD */
    public $disableDataStr = false;

    public function __construct($xmlFile, $vData = array()) {
        $this->_include_lib();
        if (!file_exists($xmlFile)) {
            die("<b>Erro:</b> Em <i><b>xmlForms</b></i>, o arquivo <b>$xmlFile</b> n&atilde;o foi encontrado");
        }
        $view = new view();
        $xmlData = $view->process_view($vData, $xmlFile);
        $this->_xml = new SimpleXMLElement($xmlData);
    }

    /**
     * Cria e edita tabelas no banco de dados baseando suas informações em
     * arquivo XML.
     * @param string $formName - Nome do formulário.
     */
    public function manage_table($formName) {
        $idForm = $this->idForm($formName);
        $Tables = new xmlForms_tables($this->_xml->form[$idForm]);
        $Tables->def_primary_key = $this->def_primary_key;
        $Tables->table_from_xml();
    }

    /**
     * Cria formuário a partir de instruções em arquivo XML.
     * Retorna Array com matriz de valores a serem aplicados. Segue valores abaixo
     * Estrutura de retorno
     * 
      Array(
     *      'headers' => (string) [Cabeçalho do formulario]
      'js' => array(
      0 => array(
      'js_code' => '[linha de comando javascript]'),
      1 => array(
      'js_code' => '[linha de comando javascript]')
      ),
      'form_properties' => (string) '[Propriedades do elemento <form>]',
      'form_data' => array(
      0 => array(
      'label' => (string) 'Label do form',
      'field' => (string) 'HTML do campo propriamente dito'
      )
      1 => array(
      'label' => (string) 'Label do form',
      'field' => (string) 'HTML do campo propriamente dito'
      )
      )
      );
     * 
     * @return array | Matriz com valores a serem aplicados no formulário.
     * @param string|Nome do form $formName
     *  
     */
    public function getForms($formName) {
        if ($this->_postData) {
            $this->_putPostOnXmlform($formName);
        }
        $idForm = $this->idForm($formName);
        $this->_createForm($idForm);
        return $this->_form;
    }

    /**
     * Retorna Primary Key do formulário solicitado
     * Caso Primary Key não tenha sido setado no xml do form, 
     * retorna PK padrão definido em $this->def_primary_key.
     * @param string $formName Nome do form a ser consultado
     * @return string Nome do campo Primary Key
     */
    public function getPK($formName) {
        $idForm = $this->idForm($formName);
        $xmlForm = $this->_xml->form[$idForm];
        $pk = (string) $xmlForm->primary_key;
        if(!$pk){
            $objMysql = new mysql();
            $pk = $objMysql->get_primary_key($xmlForm->table);
        }
        if (!$pk)
            $pk = $this->def_primary_key;
        return $pk;
    }

    /**
     * Retorna nome do form setado no XML.
     * @param string $formName - Define nome do form a retornar titulo.
     * @return string - Titulo do form
     */
    public function formTitle($formName) {
        $idForm = $this->idForm($formName);
        return (string) $this->_xml->form[$idForm]->title;
    }
    
    /**
     * Retorna tag específica de um form
     * @param string $formName - Nome do form a ser retornada tag
     * @param string $tag - Tag desejada
     * @return SimpleXMLElement tag específica
     * @example echo (string) $XMLForms->formTag('form1','title'); //titulo do form
     */
    public function formTag($formName,$tag){
        $idForm = $this->idForm($formName);
        return $this->_xml->form[$idForm]->$tag;
    }

    /**
     * Para ser usado por elemento inut_autoComplete. Recebe nome do form,campo
     * e valor filtrado para busca e complemento do jquery_autoComplete.
     * Retorna JSON para duas situações:
     * se autoFill for true, retorna JSON com formulario para preencher automaticamete
     * outros campos.
     * Se autoFill for false, retorna JSON com itens para alimentar busca
     */
    public function getForms_input_autoCompleteJSON($formName, $fieldName, $term, $autoFill) {

        /* Busca XML do campo */
        $xmlForm = $this->_xml->form[$this->idForm($formName)];
        $xmlField = $this->xmlField($formName, $fieldName);
        /* Se o campo não for do tipo input_autoComplete, escapa retornando um JSON vazio */
        if ((string) $xmlField->field_type != 'input_autoComplete') {
            return json_encode(array());
        }
        /* Passou no teste? Segue a execução */
        include_once($this->_lib . "fields/input_autoComplete.php");
        $Input_AC = new input_autoComplete();
        if ($autoFill)
            return json_encode($Input_AC->array_AC_form($xmlField, $xmlForm, $term, $this->_xml->language));
        else
            return json_encode($Input_AC->search_itens_list($xmlField, $term, $this->_xml->language));
    }

    /**
     * Posta dados do formulario XML definido em tabela também definida no XML.
     * 
     * -Verifica erros e retorna erro antes de postar
     * 
     * Retornos:
     *      string se ocorreu algum erro ou excessão na postagem
     *      NULL se tudo ocorrer bem
     * 
     * @return string|Mensagem de errro ou NULL se ocorreu tudo bem
     * @param array|Valores recebidos de $_POST em $postData.
     * @param string|Nome do form a ser analisado para postar dados $formName
     * 
     */
    public function postForms($postData, $formName = null) {
        return $this->_postOrUpdateForm($postData, $formName);
    }

    /**
     * Atualiza registro definido pela chave primaria $id_reg.
     * 
     * @return null Se tudo der certo
     * @return string Mensagem de erro, se ocorrer algum erro.
     */
    public function updateForm($postData, $formName, $id_reg) {
        return $this->_postOrUpdateForm($postData, $formName, $id_reg);
    }

    /**
     *  Retorna dados de pesquisa feita na ultima insercao de dados feitas por $this->_insertInTable 
     * 
     * @return array|Dados da ultima postagem
     * @return false|Não houve uma ultima postagem ou nada a retornar
     */
    public function getLastInsert() {
        return $this->_lastInsertData;
    }

    /**
     * Retorna array com valores de formulário.
     * 
     * @return array|Valores de formulario
     * @param Nome do formulário $formName
     * @param id de primary_key do registo $id_reg
     */
    public function readForm($formName, $id_reg) {
        $idForm = $this->idForm($formName);
        if ($idForm === false) {
            return false;
        }
        $xmlForm = $this->_xml->form[$idForm];
        if (!$this->_Reader) {
            $this->_Reader = new xmlForms_reader($this->_xml);
        }
        $this->_Reader->disableDataStr = $this->disableDataStr; //Repassa variavel global
        return $this->_postData = $this->_Reader->readFromTable($xmlForm, $id_reg,$this->getPK($formName));
    }

    /**
     * Retorna o formulário em formato de lista usadndo formulário padrão.
     * 
     */
    public function formReport($formName, $id_reg) {
        $idForm = $this->idForm($formName);
        if ($idForm === false) {
            return false;
        }
        $xmlForm = $this->_xml->form[$idForm];

        if (!$this->_Reader) {
            $this->_Reader = new xmlForms_reader($this->_xml);
        }
        $this->_Reader->disableDataStr = $this->disableDataStr; //Repassa variavel global
        return $this->_Reader->report($xmlForm, $id_reg);
    }

    /**
     * Cria array com lista de dados de form desejado.
     * 
     * @return boolean false| Formulário não encontrado.
     * @return array| Matriz com lista de valores retornados.
     * @param string| Nome do form a ser usado $formName
     * @param int| Para paginação, primeiro resultado $first_reg
     * @param int| Para paginação, numero de resultados por pagina $num_of_regs
     */
    public function listForm($formName, $first_reg, $num_of_regs) {
        $idForm = $this->idForm($formName);
        if ($idForm === false) {
            return false;
        }
        $xmlForm = $this->_xml->form[$idForm];
        if (!$this->_Reader) {
            $this->_Reader = new xmlForms_reader($this->_xml);
        }
        $this->_Reader->disableDataStr = $this->disableDataStr; //Repassa variavel global
        return $this->_Reader->makeList($xmlForm, $first_reg, $num_of_regs,$this->getPK($formName));
    }

    /**
     * Define, se necessario, filtros para serem usados em xmlForms::listForm
     * 
     * @return int|Linhas -Total de resultados que serão obtidos no xmlForms::listForm
     * @return boolean|false -Formulário não encontrado.
     * @param string| Nome do form a ser usado $formName
     * @param array| Campos para pesquisa no formato mysql::arrSearch();
     */
    public function listForm_filter($formName, $arrSearch = array(), $arrCommands = array()) {
        $idForm = $this->idForm($formName);
        if ($idForm === false) {
            return false;
        }
        $xmlForm = $this->_xml->form[$idForm];
        if (!$this->_Reader) {
            $this->_Reader = new xmlForms_reader($this->_xml);
        }
        return $this->_Reader->makeList_filter($xmlForm, $arrSearch, $arrCommands);
    }

    /**
     * Remove filtros de pesquisa para xmlForms::listForm
     * 
     * @param string| Nome do form a ser usado $formName
     */
    public function listForm_removeSearchFilter($formName) {
        if (!$this->_Reader) {
            $this->_Reader = new xmlForms_reader($this->_xml);
        }
        return $this->_Reader->makeList_removeSearchFilter($formName);
    }

    /**
     * Remove comandos para xmlForms::listForm
     * 
     * @param string| Nome do form a ser usado $formName
     */
    public function listForm_removeSearchCommands($formName) {
        if (!$this->_Reader) {
            $this->_Reader = new xmlForms_reader($this->_xml);
        }
        return $this->_Reader->makeList_removeSearchCommands($formName);
    }

    /**
     * Retorna total de resultados de um form.
     * 
     * @return int| Total de resultados que serão obtidos no xmlForms::listForm
     * @param string| Bine di form a ser usado $formName.
     */
    public function listForm_totalResults($formName) {
        $idForm = $this->idForm($formName);
        if ($idForm === false) {
            return false;
        }
        $xmlForm = $this->_xml->form[$idForm];
        if (!$this->_Reader) {
            $this->_Reader = new xmlForms_reader($this->_xml);
        }
        return $this->_Reader->makeList_filter($xmlForm);
    }

    /**
     * Retorna um array com os campos e suas propriedades.
     * @param string $formName - Nome do form a ser listados os campos
     * @return array - Campos do form e suas propriedades
     * @return false - Caso form não exista 
     * 
     */
    public function listFields($formName) {
        $arrFields = array();
        $idForm = $this->idForm($formName);
        if ($idForm === false)
            return false;
        $xmlForm = $this->_xml->form[$idForm];

        /* Adiciona Chave primária ao $arrFields(caso aplicavel) */
        $objMysql = new mysql();
        $pk = (string) $xmlForm->primary_key;
        if (!$pk)
            $pk = $objMysql->get_primary_key((string) $xmlForm->table);
        if (is_string($pk) && $pk)
        /* Valores seguem padrão de um field de xmlField */
            $arrFields[$pk] = array(
                '@attributes' => array(
                    'name' => $pk
                ),
                'primary_key' => 'true'
            );

        /* Agora adiciona xmlFields a $arrFields com a seguinte organização: 
         * nome dos campos como chave, e xmlField como conteudo 
         */
        for ($i = 0; $i < count($xmlForm->field); $i++) {
            $xmlField = $xmlForm->field[$i];
            $fieldName = $this->_get_fieldName($xmlField);
            if ($fieldName) {
                /* Checa se campo já foi inserido. Se foi, faz um merge entre os
                 * dois campos (array_merge_recursive)
                 */
                if (isset($arrFields[$fieldName]))
                    if (is_array($arrFields[$fieldName]))
                        $field = array_merge($arrFields[$fieldName],toArray($xmlField));
                    else {
                        //Vazio, pois aqui não tem else o if acima que tem
                    }
                else
                    $field = toArray($xmlField);
            }
            $arrFields[$fieldName] = $field;
        }
        /* Retorna arrFields */
        return $arrFields;
    }

    /**
     * Retorna SimpleXMLElement de formulário desejado
     * @param string $formName - Nome do formulário
     * @return SimpleXMLElement - XML do formulário
     */
    public function getXmlForm($formName) {
        return $this->_xml->form[$this->idForm($formName)];
    }

    /**
     * Retorna lingua padrão definida no XML
     * @return string lingua utilizada
     */
    public function language() {
        return (string) $this->_xml->language;
    }

    /**
     * Mescla dados enviados com dados já pre-armazenados no $this->_postData.
     * $this->_postData é responsável por receber os valores necessários para
     * se enviar um formulário ou imprimir um formulário na tela
     * @param array $arrData - Array com dados para serem mesclados a postData
     */
    public function merge_postData($arrData = array()) {
        if (is_array($arrData) && is_array($this->_postData))
            $this->_postData = array_merge($this->_postData, $arrData);
    }

    /**
     * Descobre nome do campo.
     * Caso campo seja o campo da tabela tratada, retorna o nome do campo
     * propriamente dito (chave name na tag field.
     * 
     * Caso este field esteja relacionado a uma chave estrangeira, significa que
     * a chave name é na verdade o campo da tabela relacionada e não da tabela
     * tratada naquele momento, então retorna o nome da chave estrangeira e não
     * o nome do campo em si.
     */
    private function _get_fieldName($xmlField) {
        $fieldName = null;
        if ($xmlField->relate->rel_key)
            $fieldName = (string) $xmlField->relate->rel_key;
        else
            $fieldName = (string) $xmlField['name'];
        return $fieldName;
    }

    /**
     * Pega valores alimentados de um POST e os insere no XML
     * @param string $formName | Nome do form a ser tratado
     * @param array $postData | Dados enviados via Post (se não declarado, assumira
     * $this->_postData
     */
    private function _putPostOnXmlform($formName, $postData = array()) {
        if (!$postData)
            $postData = $this->_postData;
        $idForm = $this->idForm($formName);
        for ($i = 0; $i < count($this->_xml->form[$idForm]->field); $i++) {
            $field = (string) $this->_xml->form[$idForm]->field[$i]['name'];
            if (isset($postData[$field])) {
                $this->_xml->form[$idForm]->field[$i]->value = $postData[$field];
            }
            /* Se existir valor relativo a relacionamento, adiciona o mesmo ao xml */
            if ($this->_xml->form[$idForm]->field[$i]->relate->rel_key) {
                $rel_column = (string) $this->_xml->form[$idForm]->field[$i]->relate->rel_key;
                if (isset($postData[$rel_column]))
                    $this->_xml->form[$idForm]->field[$i]->rel_value = $postData[$rel_column];
            }
        }
    }

    private function _postOrUpdateForm($postData, $formName, $id_reg = null) {
        $error = null;
        if ($postData) {
            $this->_postData = $postData;
        }
        if (!$this->_Post)
            $this->_Post = new xmlForms_post($this->_xml);
        if (!$this->_Reader)
            $this->_Reader = new xmlForms_reader($this->_xml);
        $xmlForm = $this->getXmlForm($formName);
        if ($id_reg) {
            /* Se existir id_reg, adiciona ao $xmlForm os dados usados previamente */
            $prev_data = $this->_Reader->readFromTable($xmlForm, $id_reg,$this->getPK($formName));
            $this->_putPostOnXmlform($formName, $prev_data);
            /* Se check_options estiver habilitado, Checa por opções de update antes de atualizar */
            if ($this->check_options)
                $error = xmlForms_options::disable('update', $xmlForm);
            if (!$error) /* Atualiza ou recebe erro de atualização */
                $error = $this->_Post->update($postData, $xmlForm, $id_reg);
        }
        else {
            /* Checa por opcoes antes de postar */
            $error = $error = xmlForms_options::disable('post', $xmlForm);
            if (!$error) /* Posta ou recebe erro de postagem */
                $error = $this->_Post->post($postData, $xmlForm);
        }

        $this->_lastInsertData = $this->_Post->lastInsertData;
        return $error;
    }

    /* Inclue bibliotecas de XMLForms */

    private function _include_lib() {
        $this->_lib = $this->_pluginDir = str_replace(DIRECTORY_SEPARATOR, '/', realpath(dirname(__FILE__))) . "/xmlForms/";
        include_once($this->_lib . "xmlForms_convert_sql_str.php");

        include_once($this->_lib . "xmlForms_fields.php");
        include_once($this->_lib . "xmlForms_reader.php");
        include_once($this->_lib . "xmlForms_post.php");
        include_once($this->_lib . "xmlForms_tables.php");
        include_once($this->_lib . "xmlForms_options.php");
    }

    /**
     * Cria campos de um formulário a partir do form do XML passado por idForm
     */
    private function _createForm($idForm) {
        $xmlForm = $this->_xml->form[$idForm];
        $XmlForms_fields = new xmlForms_fields($this->_xml, $xmlForm);
        foreach($xmlForm->field as $xmlField){
            $XmlForms_fields->insertField($xmlField);
        }
        $this->_form = $XmlForms_fields->getForm();
        /* Adiciona ao header existente os headers do XML */
        $this->_form['headers'] .= $form->headers;
    }

    protected function xmlField($formName, $fieldName) {
        $idForm = $this->idForm($formName);
        $idField = $this->idField($this->_xml->form[$idForm], $fieldName);
        return $this->_xml->form[$idForm]->field[$idField];
    }

    /**
     * Retorna ID do form a partir do FormName.
     * Retorna FALSE caso form não seja encontrado
     * 
     * @param string | Nome do form a ser pesquisado $formName
     * @return int | Id do form desejado
     * @return boolean false | Form não encontrado
     */
    protected function idForm($formName) {
        return $this->xmlItemId($this->_xml, 'form', $formName);
    }

    protected function idField($xmlField, $fieldName) {
        return $this->xmlItemId($xmlField, 'field', $fieldName);
    }

    protected function xmlItemId($xml, $item, $itemName) {
        $xmlItem = $xml->$item;
        for ($i = 0; $i < count($xmlItem); $i++) {
            if ($itemName) {
                if ($xmlItem[$i]['name'] == $itemName) {
                    return $i;
                }
            }
        }
        return false;
    }

}

?>