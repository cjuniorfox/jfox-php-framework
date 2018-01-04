<?php

/**
 * @author Carlos Junior
 * @version 1.1;
 * 
 * CHANGELOG
 * 1.1 - Adicionado jquery_selectbox ao objeto.
 * 1.0 - Criado elemento.
 * 
 */
class selectboxXMLForm extends Fields {

    const version = 1.1;

    /**
     * Conteúdo (campos) do selectbox. Este pode ser preenchido por classes filhas
     * deste objeto.
     */
    protected $content = array();

    /**
     * arrValue normal usado por str_value. É global e protected porque pode
     * ser preenchido pelas classes filhas deste objeto.
     */
    protected $arrOut = array();
    public $selectKey = "__SELECTBOX::";

    /**
     * Este identifica este campo como multiSelect. Ou seja, significa que este
     * utiliza para representar seu valor um identificador relativo e não o valor
     * em si.
     */
    public $multiSelect = true;

    public function createField() {
        $content = array_merge($this->_arrSelectbox(), $this->content);
        if (!$content) { /* Se não foi passado conteúdo, retorna conteúdo em branco */
            $content[] = array("label" => "Sem dados...", "value" => "");
        }
        $this->_define_jquery_dropdown();
        $properties = $this->properties();
        $this->HTML = "<select " . parent::propertiesToStr($properties) . ">" . $this->selectboxFeeder($content, $this->value) . "</select>";
    }

    public function str_value($value) {
        $field_name = (string) $this->XMLField['name'];

        //Este pode já ter sido preenchido por uma classe filha
        if (!array_key_exists($field_name, $this->arrOut)) {
            if (!$value && $this->XMLField->value)
                $value = (string) $this->XMLField->value; //Se valor não foi setado, e há valor padrão no XML. Define este como o valor do campo.
            $this->arrOut[$field_name] = self::_get_selected_selectbox($value);
            //Adiciona as demais chaves do item selecionado ao array
            $selectedItem = self::_get_selected_item($value);
            if (is_array($selectedItem)) {
                unset($selectedItem['value']);
                foreach (array_keys($selectedItem) as $key) {
                    $this->arrOut[$this->selectKey . $field_name . "::$key"] = $selectedItem[$key];
                }
            }
        }

        $this->arrOut[$this->selectKey . $field_name] = $value;
        return $this->arrOut;
    }

    public function mysql_property() {
        $XMLField = $this->XMLField;
        $set_value = array();
        foreach ($XMLField->item as $XMLItem) {
            $set_value[] = "'" . $XMLItem->value . "'";
        }
        return "SET(" . implode(",", $set_value) . ")";
    }

    public function mysql_field_name() {
        if ($this->XMLField['name'])
            return (string) $this->XMLField['name'];
    }

    /**
     * Retorna um array com as propriedades do elemento desejado.
     * @param SimpleXMLElement $xmlData - Campo ou elemento XML a se tirar propriedades. Se em branco, pressupoe-se o proprio campo XML
     */
    protected function properties($xmlData = NULL) {
        if (!$xmlData)
            $xmlData = $this->XMLField;
        $arrP = self::getProperties($xmlData);
        if (!isset($arrP['id']) && isset($arrP['name']))
            $arrP['id'] = $arrP['name'];
        return $arrP;
    }

    /**
     *  Retorna dados a serem inseridos dentro de um Selectbox qualquer. Pode
     * inserir dados em um selectbox criado pelo objeto como pode inserir
     * dados em um selectbox controlado por ajax ja impresso em um formulario.
     * Formatacao: array('valor'=>'label')
     */
    public function selectboxFeeder($content = array(), $valSel = '') {
        $buffer = null;
        $selected = null;
        foreach ($content as $item) {
            if ($valSel == $item['value']) /* Se o valor inserido for o que deve ser selecionado, escreve a tag HTML */
                $item['selected'] = "selected";
            $arrStr = array(); //Esta é o array que monta
            $label = "";
            if (isset($item['label'])) { //Se tiver label, adiciona label a string e remove a chave "label" do array, pois Label não é propriedades
                $label = $item['label'];
                unset($item['label']);
            }
            foreach (array_keys($item) as $itemKey) {
                $arrStr[] = "$itemKey=\"$item[$itemKey]\"";
            }
            $buffer .= "<option " . implode(" ", $arrStr) . ">$label</option>\n";
            $selected = null;
        }
        return $buffer;
    }

    /**
     * Retorna o Label do selectbox selecionado
     */
    private function _get_selected_selectbox($value) {
        $item = self::_get_selected_item($value);
        if ($item && isset($item['label']))
            return $item['label'];
    }

    /**
     * Retorna o item do selecbox selecionado (se houver)
     */
    private function _get_selected_item($value) {
        $arrSelectbox = self::_arrSelectbox();
        foreach ($arrSelectbox as $item)
            if ($item['value'] == $value)
                return $item;
    }

    /**
     * Busca no XML o conteúdo e alimenta com os valores corretos.
     */
    private function _arrSelectbox() {
        $objXML = $this->XMLField;
        $arrSBox = array();
        foreach ($objXML->item as $XmlOption) {
            $element = toArray($XmlOption);
            if (!isset($XmlOption->label) && isset($XmlOption->value)) //Se não existir label, define label como value
                $element['label'] = (string) $XmlOption->value;
            elseif (!isset($XmlOption->value) && isset($XmlOption->label)) //Caso seja o contrário, define value como label
                $element['value'] = (string) $XmlOption->label;
            $arrSBox[] = $element;
        }
        return $arrSBox;
    }

    //Cria o header para o dropdown em jquery
    private function _header() {
        ob_start();
        ?>
        <link rel="stylesheet" type="text/css" href="{SITE_PUBLIC_PATH}resources/jquery.ms-dropdown/css/msdropdown/dd.css" />
        <script type="text/javascript" src="{SITE_PUBLIC_PATH}resources/jquery.ms-dropdown/js/jquery.dd.min.js"></script>
        <?
        return ob_get_clean();
    }

    private function _js_jquery_dropdown() {
        $css_element_id = $this->get_css_element_id('input', $this->getProperties($this->XMLField));
        return "$(\"$css_element_id\").msDropDown();";
    }

    /**
     * Verifica no XML se há a chave jquery_dropdown = true. Caso esta exista, define o código HEADER e o javascript definido o selectbox como JqueryDropdown
     */
    private function _define_jquery_dropdown() {
        if ($this->XMLField->jquery_dropdown == 'true') {
            $this->HEADER = $this->_header();
            $this->JS = $this->_js_jquery_dropdown();
        }
    }

}
?>
