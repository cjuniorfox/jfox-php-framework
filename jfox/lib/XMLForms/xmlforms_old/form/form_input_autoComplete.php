<?php

class form_input_autoComplete {

    public function js_code($name, $rel_id, $json_source, $formName) {
        $css_element_id = "form[name='$formName'] input[name='$name']";
        $css_related_field = "form[name='$formName'] input[name='$rel_id']";
        ob_start();
        ?>
        //<script>
            $("<?= $css_element_id ?>").autocomplete({
                source:"<?= $json_source ?>?form=<?= $formName ?>&field=<?= $name ?>",
                minLength: 1,
                select : function(event,ui) {
                    $("<?= $css_related_field ?>").val(ui.item.id);
                    $.getJSON("<?= $json_source ?>?form=<?= $formName ?>&field=<?= $name ?>&term="+ui.item.id+"&autofill=true",function(json){
                        $.each(json,function(key,value){
                            $("form[name='<?= $formName ?>'] [name="+key+"]").val(value);
                        })
                    })
                }
            });
            <?
            $contents = ob_get_contents();
            ob_end_clean();
            return $contents;
        }

    }
    ?>
