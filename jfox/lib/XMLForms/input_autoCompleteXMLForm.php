<?php
/*
 * To change this template, choose Tools | Templates
 * and open the template in the editor.
 */

/**
 * Description of input_autoCompleteXMLForm
 *
 * @author cjuniorfox
 */
class input_autoCompleteXMLForm extends inputXMLForm {

    const autocomplete_json = "{SITE_PATH}{controller_name}/input_autoComplete";
    const autofill_json = "{SITE_PATH}{controller_name}/autofill_related_fields";
    const minLength = 1;

    public function createField() {
        $this->JS = self::_js_code();
        parent::createField();
    }

    public function str_value($value) {
        return parent::str_value($value);
    }

    /**
     * Retorna Array de resultados para gerar JSON utilizado pelo objeto xmlForms.input_autoComplete.
     * @param SimpleXMLElement $xmlField - Elemento XML do campo.
     * @param string $term - Valor buscado.
     */
    public function autocomplete_list($xmlfilepath, $form_name, $term) {
        $XMLForms_fields = new XMLForms_fields($xmlfilepath, $form_name);
        $XMLField = $XMLForms_fields->XMLField($_GET['field']);
        $this->language = $XMLForms_fields->language;
        $data = array(); /* Este tera o resultado da listagem de itens */
        $table = (string) $XMLField->relate->table;
        $field = (string) $XMLField['name'];
        $maxRegs = (int) $XMLField->maxRegs;
        $key = (string) $XMLField->relate->primary_key;
        if (!$table || !$field) //Se não conseguiu table ou o field, retorna nulo.
            return array();
        $search = stringParaBusca($term);
        $arrCommands = array(
            'logic_operator' => 'REGEXP',
            'orderby' => $field . ' DESC'
        );
        if ($maxRegs)
            $arrCommands['total_results'] = $maxRegs;
        $Mysql = new mysql();
        $res = $Mysql->search($table, array($field => $search), $arrCommands);
        while ($line = mysql_fetch_assoc($res)) {
            $data[] = array(
                'id' => $line[$key],
                'label' => parent::convertDatatoStr($line[$field], $XMLField, $this->language),
                'value' => parent::convertDatatoStr($line[$field], $XMLField, $this->language)
            );
        }
        return $data;
    }

    private function _js_code() {
        $field_name = (string) $this->XMLField['name'];
        $rel_field = (string) $this->XMLField->relate->rel_key;
        $field_id = "form[name='$this->form_name'] input[name='$field_name']";
        $relField_id = "form[name='$this->form_name'] input[name='$rel_field']";
        $minLength = (int) $this->XMLField->minLength;
        if (!$minLength)
            $minLength = self::minLength;
        ob_start();
        ?>
        //<script>
                    var cache = {};
                    $("<?= $field_id ?>")
                    .autocomplete({
                        minLength: 2,
                        search: function(){
                            $("<?= $field_id ?>").addClass("searching");
                        },
                        source: function( request, response ) {
                            var term = request.term;
                            if ( term in cache ) {
                                response( cache[ term ] );
                                $("<?= $field_id ?>").removeClass("searching");
                                return;
                            }
                            var url = "<?= self::autocomplete_json ?>?form=<?= $this->form_name ?>&field=<?= $field_name ?>";
                            $.getJSON( url, request, function( data, status, xhr ) {
                                cache[ term ] = data;
                                $("<?= $field_id ?>").removeClass("searching");
                                response( data );
                            });
                        },
                        select : function(event,ui) {
                            $("<?= $relField_id ?>").val(ui.item.id);
                            $.getJSON("<?= self::autofill_json ?>?form=<?= $this->form_name ?>&field=<?= $field_name ?>&term="+ui.item.id,function(json){
                                $.each(json,function(key,value){
                                    $("form[name='<?= $this->form_name ?>'] [name="+key+"]").val(value);
                                    $("input").trigger('change');
                                })
                            })
                        }
                    });
                                            
                                            
                                            
                                    
            <?
            $out = ob_get_clean();
            return $out;
        }

    }
    ?>