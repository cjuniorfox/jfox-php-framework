<?php

/**
 * Rotinas de verificação de erros do XMLForms.
 * Mais utilizado por XMLForms_post
 *
 * @author cjuniorfox
 */
class XMLForms_errors {

    const version = 1.0;

    /**
     * Verifica se dados inseridos são data e estão no formato solicitado.
     * @param SimpleXMLElement $XMLField - Campo XML a ser verificado.
     * @param array $POST - Array com postagem do formulário.
     * @return null - Não há erros
     * @return string - Há erros, retorna mensagem de erro.
     */
    public static function format_date($XMLField, $POST) {
        $field_name = (string) $XMLField['name'];
        if (
                $XMLField->format_date->enabled == "true" &&
                $XMLField->post == 'true' &&
                $POST[$field_name]
        ) {
            $date = mysql_str_to_date((string) $POST[$field_name], convert_sql_mask((string) $XMLField->format_date->mask));
            if (!$date)
                return trim((string) $XMLField->format_date->message);
        }
    }

    /**
     * Verifica se o campo solicitado é um número inteiro.
     * @param SimpleXMLElement $XMLField - Campo XML a ser verificado.
     * @param array $POST - Array com postagem do formulário.
     * @return null - Não há erros
     * @return string - Há erros, retorna mensagem de erro.
     */
    public static function is_int($XMLField, $POST) {
        $field_name = (string) $XMLField['name'];
        if (
                $XMLField->is_int->enabled == "true" &&
                $XMLField->post == 'true'
        ) {
            if ((string) $POST[$field_name] != (string) (int) $POST[$field_name]) {
                return trim((string) $XMLField->is_int->message);
            }
        }
    }

    /**
     * Verifica se valor postado tem o tamanho mínimo permitido
     * @param SimpleXMLElement $XMLField - Campo XML a ser verificado.
     * @param array $POST - Array com postagem do formulário.
     * @return null - Não há erros
     * @return string - Há erros, retorna mensagem de erro.
     */
    public static function min_length($XMLField, $POST) {
        $field_name = (string) $XMLField['name'];
        if (
                $XMLField->min_length->enabled == "true" &&
                $XMLField->post == "true" &&
                $POST[$field_name] &&
                strlen($POST[$field_name]) < (int) $XMLField->min_length->size &&
                (int) $XMLField->min_length->size > 0
        ) {
            return trim((string) $XMLField->min_length->message);
        }
    }

    /**
     * Verifica se o campo não é nulo.
     * @param SimpleXMLElement $XMLField - Campo XML a ser verificado.
     * @param array $POST - Array com postagem do formulário.
     * @return null - Não há erros
     * @return string - Há erros, retorna mensagem de erro.
     */
    public static function not_null($XMLField, $POST) {
        $field_name = (string) $XMLField['name'];
        if (
                $XMLField->not_null->enabled == "true" &&
                $XMLField->post == 'true' &&
                @!(string) $POST[$field_name]
        )
            return trim((string) $XMLField->not_null->message);
    }

    /**
     * Verifica se o valor de um determinado campo está sendo repetido em outro.
     * Normalmente usado para autenticar passwords.
     * 
     * @param SimpleXMLElement $XMLField - Campo XML a ser verificado.
     * @param array $POST - Array com postagem do formulário.
     * @return null - Não há erros
     * @return string - Há erros, retorna mensagem de erro.
     */
    public static function repeat($XMLField, $POST) {
        $field_name = (string) $XMLField['name'];
        $field_repeat = (string) $XMLField->repeat->repeatField;
        if (!$XMLField->repeat->repeatField && $POST[$field_name . '2'])
            $field_repeat = $field_name . '2';
        if (
                $XMLField->repeat->enabled == "true" &&
                $XMLField->post == "true" &&
                $POST[$field_name] &&
                (string) $POST[$field_name] !== (string) $POST[$field_repeat]
        )
            return trim((string) $XMLField->repeat->message);
    }

    /**
     * Verifica se ja foi inserida na tabela outro campo com o mesmo valor do 
     * sendo inserido agora.
     * @param SimpleXMLElement $XMLField - Campo XML a ser verificado.
     * @param array $POST - Array com postagem do formulário.
     * @return null - Não há erros
     * @return string - Há erros, retorna mensagem de erro.
     */
    public static function unique($XMLField, $POST, $table, $primary_key, $pk_value) {
        $field_name = (string) $XMLField['name'];
        $Mysql = new mysql();
        if (
                $XMLField->unique->enabled == "true" &&
                $XMLField->post == 'true' &&
                $POST[$field_name]
        ) {
            $arrCommands = array('condition' => 'AND');
            $arrPesq = array($field_name => $POST[$field_name]);
            if($primary_key && $pk_value){
                $arrPesq[$primary_key] = $pk_value;
                $arrCommands['logic_operator'][$primary_key] = "<>";
            }
            $data = $Mysql->get_data($table, $arrPesq,$arrCommands);       
            if ($data)
                return trim((string) $XMLField->unique->message);
        }
    }

    /**
     * Valida e-mail inserido.
     * Depende do objeto PHPMailer
     * @param SimpleXMLElement $XMLField - Campo XML a ser verificado.
     * @param array $POST - Array com postagem do formulário.
     * @return null - Não há erros
     * @return string - Há erros, retorna mensagem de erro.
     */
    public static function validate_email($XMLField, $POST) {
        $field_name = (string) $XMLField['name'];
        if (
                $XMLField->validate_email->enabled == "true" &&
                $XMLField->post == "true" &&
                $POST[$field_name] &&
                !PHPMailer::ValidateAddress($POST[$field_name])
        )
            return trim((string) $XMLField->validate_email->message);
    }

}

?>