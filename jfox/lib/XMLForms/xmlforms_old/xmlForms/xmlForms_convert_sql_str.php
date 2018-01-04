<?php

/**
 * Relativa as funcoes de conversao de dados dentro do grupo xmlForms
 *
 * @version 1.1
 * 
 * @author juniorfox
 */
abstract class xmlForms_convert_sql_str {

    public $version = 1.1;

    /**
     *  Quando true, não converte data do BD para STR formatada. Retorna no formato do BD 
     */
    public $disableDataStr = false;

    protected function convertDataStr($dataToStr, $value, $xmlField, $format) {
        /* dataToStr: Quando true, converte DATA para STR, quando FALSE converte STR para DATA */
        $objLocalFormats = new local_formats();
        $type = null;
        $subtype = null;
        if ($xmlField->format_real->enabled == 'true') {
            $subtype = (string) $xmlField->format_real->subtype;
            $type = 'real';
        } elseif ($xmlField->format_integer->enabled == 'true') {
            $type = 'integer';
        } elseif ($xmlField->format_date->enabled == 'true') {
            $subtype = (string) $xmlField->format_date->subtype;
            $type = 'date';
        } elseif ($xmlField->format_time->enabled == 'true') {
            $subtype = (string) $xmlField->format_time->subtype;
            $type = 'time';
        }
        if ($dataToStr) { //Se este for true, transforma dados SQL para STR
            if ($this->disableDataStr) //Se $this->disableDataStr for true, retorna valor sem formata-lo
                return $value;
            return $objLocalFormats->data_to_local_str($value, $type, $format, $subtype); // Se $this->disableDataStr for false, retorna valores formatados
        }else //Se não for, faz o inverso, transforma STR para dados SQL
            return $objLocalFormats->local_str_to_data($value, $type, $format, $subtype); // Se $dataToStr for false, faz o inverso, converte local_str para data
    }

    /**
     * Recebe $xmlForm, a linha do mysql_fetch_array, e retorna valores array
     * de valores marcados como POST no XML no formato definido no XML.
     * 
     * @param SimpleXMLObject XML do Form $xmlForm
     * @param array Linha do mysql_fetch_array $line
     * @return array Dados formatados para str de acordo com XML
     */
    protected function convertSqlDataToStr($xmlForm, $line, $format, $pk = null) {
        $formatted_line = array();
        $objMysql = new mysql();
        /* Busca pelo primary_key do formulario solicitado na tabela a ser lida (mesmo se setada view, o PK é buscado da tabela */
        if (!$pk)
            $pk = $objMysql->get_primary_key((string) $xmlForm->table);
        /* Adiciona chave primária a lista de resultados */
        if ($pk)
            $formatted_line[$pk] = $line[$pk];
        /* Agora adiciona demais resultados */
        for ($i = 0; $i < count($xmlForm->field); $i++) {
            if ($xmlForm->field[$i]->relate->rel_key)
                $fieldName = (string) $xmlForm->field[$i]->relate->rel_key;
            else
                $fieldName = (string) $xmlForm->field[$i]['name'];
            if ($fieldName) {
                if (isset($line[$fieldName]))
                    $formatted_line[$fieldName] = $this->_getFieldStr($xmlForm->field[$i], $line[$fieldName], $format);
                else
                    $formatted_line[$fieldName] = NULL;
            }
        }
        return $formatted_line;
    }

    private function _getFieldStr($xmlField, $value, $format) {
        if ($xmlField->field_type == 'selectbox') {
            return $this->_getFieldStr_selectbox($xmlField, $value);
        } elseif ($xmlField->field_type == 'mysql_selectbox' || $xmlField->field_type == 'input_autoComplete') {
            return $this->_getFieldStr_mysql_selectbox($xmlField, $value);
        } elseif ($xmlField->field_type == 'jquery_fileupload') {
            return $this->_getFieldStr_jquery_fileUpload($xmlField, $value);
        } else {
            /* Se for quaisquer outro tipo, executa retorna valor convertido por converDataToStr */
            return $this->convertDataStr(true, $value, $xmlField, $format);
        }
    }

    /**
     * Retorna label de selectbox baseado no VALUE
     * @return string| Valor atualizado e formatado
     * @return null |Algum erro aconteceu
     * @param SimpleXMLElement | Campo $xmlField
     * @param string | valor a ser apurado $value
     */
    private function _getFieldStr_selectbox($xmlField, $value) {
        for ($i = 0; $i < count($xmlField->item); $i++) {
            if ((string) $xmlField->item[$i]->value == $value) {
                return (string) $xmlField->item[$i]->label;
            }
        }
    }

    /**
     * Retorna Valor do SELECTBOX baseada na tabela do mysql_selectbox
     * 
     * @return string| Valor atualizado e formatado
     * @return null |Algum erro aconteceu
     * @param SimpleXMLElement | Campo $xmlField
     * @param string | valor a ser apurado $value
     */
    private function _getFieldStr_mysql_selectbox($xmlField, $value) {
        $arrSelectbox = $this->_sqlArrSelectbox($xmlField);
        if(isset($arrSelectbox[$value]))
            return $arrSelectbox[$value];
        
    }

    private function _getFieldStr_jquery_fileUpload($xmlField, $value) {
        if ($value) {
            //return "/*SITE_PUBLIC_PATH*/".$value;
            return "<img src=\"/*SITE_PATH*/$value\"></img>";
        }
    }

    /* Cria um array formatado para alimentar um selecbox baseado em dados da de tabela mysql */

    private function _sqlArrSelectbox($objXML) {
        $arrSBOX = array();
        $table = $this->_load_table($objXML);
        if (!$table)
            $table = (string) $objXML->relate->table;
        $order_column = (string) $objXML->order_by->column;
        $order_desasc = (string) $objXML->order_by->order;
        $mysql = new mysql();
        $sqlData = $mysql->simple_search($table, null, 'or', '=', null, null, $order_column, $order_desasc);
        $valCollumns = (string) $objXML->val_collumns;
        if (!$valCollumns)
            $valCollumns = (string) $objXML->relate->primary_key;
        if ($objXML->lbl_collumns)
            $lblCollumns = (string) $objXML->lbl_collumns;
        else
            $lblCollumns = (string) $objXML['name'];
        while ($data = mysql_fetch_array($sqlData)) {
            $value = $data[$valCollumns];
            $label = $data[$lblCollumns];
            $arrSBOX[$value] = $label;
        }
        return $arrSBOX;
    }

    private function _load_table($xmlForm) {
        $tb = (string) $xmlForm->table;
        if ($xmlForm->table_view) {
            $tb = (string) $xmlForm->table_view;
        }
        return $tb;
    }

}

?>
