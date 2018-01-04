<?php

/**
 * Este é responsável pela postagem das informações no banco de dados.
 *
 * @author juniorfox
 */
class xmlForms_post extends xmlForms_convert_sql_str {

    public $postData = array(); /* Informações postadas do formulario */
    public $lastInsertData = array(); /* Quando inserido, retorna dados da ultima postagem */
    private $_lib; /* Diretório da biblioteca do xmlField */
    private $_xml; /* Documento XML */
    private $_Mysql; /* Objeto Mysql a ser usado no objeto */

    public function __construct($xml) {
        $this->_xml = $xml;
        $this->_lib = str_replace(DIRECTORY_SEPARATOR, '/', realpath(dirname(__FILE__))) . "/";
        $this->_Mysql = new mysql();
        // $this->_Mysql->debug = true;
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
    public function post($postData, $xmlForm) {
        if (!$xmlForm) {
            return "XMLForm n&atilde;o foi definido.<br />";
        }
        $error = null;
        $this->postData = $postData;
        $error .= $this->_CheckErrors($xmlForm);
        if ($error) {
            return $error;
        }
        $this->_insertOrUpdateInTable($xmlForm);
    }

    /**
     * Atualiza registro definido pela chave primaria $id_reg.
     * 
     * @return null Se tudo der certo
     * @return string Mensagem de erro, se ocorrer algum erro.
     */
    public function update($postData, $xmlForm, $id_reg) {
        $error = NULL;
        $this->postData = $postData;
        $table = (string) $xmlForm->table;
        $pri_key_field = $this->_Mysql->get_primary_key($table);
        /* Verifica se registro ja existe na tabela */
        $data = $this->_Mysql->get_data($table, array($pri_key_field => $id_reg));
        if (!$data) {
            $error .= "O Registro <b>$id_reg</b> n&aacute;o existe na tabela <b>$table</b>";
        } else {
            $error .= $this->_CheckErrors($xmlForm, 'is update');
        }
        if (!$error) {
            $this->_insertOrUpdateInTable($xmlForm, $id_reg);
        }
        return $error;
    }

    /* POST: Insere dados no banco de dados conforme informacoes do xml. Se tudo ocorrer bem, retorna true */

    /**
     * Insere novo registro ou atualiza registro ja existente.
     * 
     * Se receber  $id_reg, é um update
     * Se não, é um insert
     * 
     * @param SimpleXMLElement formulário $xmlForm
     * @param int Identificador de chave primaria $idreg
     * @return Array Se tudo ocorrer bem com dados da postagem
     * @return NULL Se algo errado acontecer
     */
    private function _insertOrUpdateInTable($xmlForm, $idReg = NULL) {
        $f = array('field' => null, 'value' => null); /* Nome do campo e valor a ser processado no insert */
        $table = (string) $xmlForm->table;
        $arrPost = array();
        $pri_key_field = $this->_Mysql->get_primary_key($table);
        for ($i = 0; $i < count($xmlForm->field); $i++) {
            if ($xmlForm->field[$i]->post == 'true') {
                /* Define nome do campo e valor a ser processado, dependendo do tipo de campo usado */
                $fieldName = (string) $xmlForm->field[$i]['name'];
                $f = $this->_define_fieldName_and_value($this->postData[$fieldName], $xmlForm->field[$i]);
                if ($f['value'] && !$arrPost[$f['fieldName']]) { //Só adiciona ao array campos que realmente tenham valor
                    /* Se for atualizacao, cria $arrPost com aspas, senão, cria sem */
                    if ($idReg) {
                        $arrPost[$f['fieldName']] = $this->_mysql_encode($f['value'], $xmlForm->field[$i], false);
                    } else {
                        $arrPost[$f['fieldName']] = $this->_mysql_encode($f['value'], $xmlForm->field[$i], true);
                    }
                }
            }
        }
 
        if ($idReg) {
            $this->_Mysql->update($table, $arrPost, array($pri_key_field => $idReg));
            return $this->lastInsertData = $this->_Mysql->get_data($table, array($pri_key_field => $idReg));
        } else {
            $this->_Mysql->insert_no_quotes($table, $arrPost);
            return $this->lastInsertData = $this->_Mysql->get_last_line($table);
        }
    }

    /**
     * Recebe xmlField e valor postado.
     * Processa os dados e retorna o nome da chave e valor a ser inserido em tabela
     * @param string $value - Valor postado a ser analisado e tratado
     * @param SimpleXMLElement $xmlField - XML do campo a ser tratado
     * @return array - Valores a serem registrados na tabela
     */
    private function _define_fieldName_and_value($value, $xmlField) {
        /* Verifica se existe relacionamentos para tratar */
        if ($xmlField->relate) {
            include_once($this->_lib . "xmlForms_related.php");
            $Related = new xmlForms_related();
            $field = $Related->process_value($value, $xmlField, $this->postData);
            $this->postData[$field['fieldName']] = $field['value'];
            return $field;
        }
        /* Caso seja um valor normal, retorna dados diretamente */
        return array(
            'fieldName' => (string) $xmlField['name'],
            'value' => $value
        );
    }

    /**
     * Converte dados para valores SQL.
     * 
     * @return string| Variavel no formato SQL
     * @param string | Valor a ser convertido $value;
     * @param SimpleXMLElement | XML do campo a ser convertido
     * @param boolean | Se true, aplica aspas a na variavel a ser retornada 
     */
    private function _mysql_encode($value, $xmlField, $quotes) {
        if ($xmlField->mysql_encode->enabled == 'true') {
            $key = (string) $xmlField->mysql_encode->crypt_field;
            if (@!$enc_key = $this->postData[$key]) {
                $enc_key = $xmlField->mysql_encode->encode_key;
            }
            $value = "ENCODE('" . mysql_real_escape_string($value) . "','$enc_key')";
        } else {
            $value = $this->convertDataStr(false, $value, $xmlField, (string) $this->_xml->language);
            if ($quotes) {
                $value = '"' . mysql_real_escape_string($value) . '"';
            }
        }
        return $value;
    }

    /* POST: Verifica os campos antes de postar, respeitando as infs dos fields no XML */

    private function _CheckErrors($form, $is_update = false) {
        $error = null;
        for ($i = 0; $i < count($form->field); $i++) {
            $error .= $this->_CheckErrors_fieldNotNull($form->field[$i]);
            /* Se for update, não checa esta etapa */
            if (!$is_update)
                $error .= $this->_CheckErrors_fieldUnique($form->field[$i], (string) $form->table);
            $error .= $this->_CheckErrors_fieldIsInt($form->field[$i]);
            $error .= $this->_CheckErrors_fieldPasswdRepeat($form->field[$i]);
            $error .= $this->_CheckErrors_fieldMinLength($form->field[$i]);
            $error .= $this->_CheckErrors_fieldValidateEmail($form->field[$i]);
            $error .= $this->_CheckErrors_fieldFormatDate($form->field[$i]);
        }
        return $error;
    }

    /* POST: Checa se campos de senha estão repetidos, caso o mesmo tenha sido setado no documento XML */

    private function _CheckErrors_fieldPasswdRepeat($field) {
        $fieldName = (string) $field['name'];
        $repeatField = (string) $field->repeat->repeatField;
        if (!$field->repeat->repeatField && $this->postData[$fieldName . '2'])
            $repeatField = $fieldName . '2';
        if ($field->repeat->enabled == "true" && $field->post == "true" && $this->postData[$fieldName] && (string) $this->postData[$fieldName] !== (string) $this->postData[$repeatField]) {
            return trim((string) $field->repeat->message);
        }
    }

    /* POST: Depende da biblioteca mail.php, verifica se a sintaxe do e-mail foi escrita corretamente */

    private function _CheckErrors_fieldValidateEmail($field) {
        $fieldName = (string) $field['name'];
        if ($field->validate_email->enabled == "true" && $field->post == "true" && $this->postData[$fieldName]) {
            $mail = new mail();
            if (!$mail->check_email_address((string) $this->postData[$fieldName])) {
                return trim((string) $field->validate_email->message);
            }
        }
    }

    /* POST: Verifica se o campo tem a quantidade mínima de caracteres solicitado no documento XML */

    private function _CheckErrors_fieldMinLength($field) {
        $fieldName = (string) $field['name'];
        if ($field->min_length->enabled == "true" && $field->post == "true" && $this->postData[$fieldName] && strlen($this->postData[$fieldName]) < (int) $field->min_length->size) {
            return trim((string) $field->min_length->message);
        }
    }

    /* POST: Verifica se o campo solicitado não está vazio, conforme solicitado no documento XML */

    private function _CheckErrors_fieldNotNull($field) {
        $fieldName = (string) $field['name'];
        if ($field->not_null->enabled == "true" && $field->post == 'true' && !$this->postData[$fieldName] && (string) $this->postData[$fieldName] != '0') {
            return trim((string) $field->not_null->message);
        }
    }

    /* POST: Verifica se o campo solicitado não possui dados repetidos na tabela, conforme solicitado no documento XML */

    private function _CheckErrors_fieldUnique($field, $table) {
        $fieldName = (string) $field['name'];
        if ($field->unique->enabled == "true" && $field->post == 'true' && $this->postData[$fieldName]) {
            $arrPesq = array($fieldName => $this->postData[$fieldName]);
            $result = $this->_Mysql->simple_data($table, $arrPesq);
            if ($result) {
                return trim((string) $field->unique->message);
            }
        }
    }

    /* POST: Verifica se o campo solicitado é um número inteiro */

    private function _CheckErrors_fieldIsInt($field) {
        $fieldName = (string) $field['name'];
        if ($field->is_int->enabled == "true" && $field->post == 'true') {
            if ((string) $this->postData[$fieldName] != (string) (int) $this->postData[$fieldName]) {
                return trim((string) $field->is_int->message);
            }
        }
    }

    /* POST: Verifica se dados inseridos são data e estão no formato solicitado */

    private function _CheckErrors_fieldFormatDate($field) {
        $fieldName = (string) $field['name'];
        if ($field->format_date->enabled == "true" && $field->post == 'true' && $this->postData[$fieldName]) {
            $date = mysql_str_to_date((string) $this->postData[$fieldName], convert_sql_mask((string) $field->format_date->mask));
            if (!$date) {
                return trim((string) $field->format_date->message);
            }
        }
    }

}

?>