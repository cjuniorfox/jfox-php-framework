<?php

/*
 * To change this template, choose Tools | Templates
 * and open the template in the editor.
 */

/**
 * Description of XMLForms_post
 *
 * @author cjuniorfox
 */
class XMLForms_post extends XMLForms {

    const version = 2.0;
    const no_data_insert = " :Sem dados para inserir.";
    const no_table_to_insert = " :Sem tabela para inserir dados";
    const update_no_id_reg = "Update: Sem id de registro";
    const no_data_to_delete = "Sem dados para deletar.";
    const more_than_one_to_delete = "Foi selecionado mais de um registro para deletar.";

    /**
     * Este é populado pelo metodo post. Retorna a última linha inserida no formulário.
     * Só recebe valor quando há uma postagem
     */
    public $last_insert = array();

    /**
     * Checa erros e caso esteja tudo bem, posta novo registro no formulário.
     * @param array $POST - Dados a serem postados
     * @return NULL Postagem com sucesso
     * @return string Mensagem de erro.
     */
    public function insert($POST) {
        //Checa por erros
        $errors = $this->check_errors($POST);
        if ($errors)
            return $errors;
        //Não existem erros, executa a inserção com o post_direct()
        $this->insert_direct($POST);
    }

    /**
     * Checa erros e caso esteja tudo bem, atualiza registro no formulário.
     * @param array $POST - Dados a serem postados
     * @param string $id_reg - Registro que será atualizado
     * @param string $field_name - Nome do campo a ser atualizado (usa chave primária se este for null)
     * @return NULL Postagem com sucesso
     * @return string Mensagem de erro.
     */
    public function update($POST, $id_reg, $field = null) {
        //Checa por erros
        $errors = $this->check_errors($POST,$this->primary_key(),$id_reg);
        if ($errors)
            return $errors;
        //Não existem erros, executa a inserção com o post_direct()
        $this->update_direct($POST, $id_reg, $field);
    }

    /**
     * Remove um registro específico da tabela do formulário.
     * Apenas remove se o registro se referenciar a apenas 1 linha e não mais
     * @param string $id_reg - Registro que será atualizado
     * @param string $field_name - Nome do campo a ser atualizado (usa chave primária se este for null)
     * @return NULL - Remoção com sucesso
     * @return string - Mensagem de erro.
     * 
     */
    public function delete_one_line($id_reg, $field_name) {
        $table = (string) $this->XMLForm->table; //Busca a tabela mesmo, não usa a view.
        if (!$field_name) //Se não tiver field_name, atribui a primary_key como o mesmo
            $field_name = $this->primary_key();
        $arrDelete = array($field_name => $id_reg);
        $arrCommands['fields'] = 'COUNT(*) AS total';
        $Mysql = $this->Mysql(); //Verica se apenas um registro está sendo deletado.
        $data = $Mysql->get_data($table, $arrDelete, $arrCommands);
        if ((int) $data['total'] == 1) {
            $Mysql->delete($table, $arrDelete);
            return NULL;
        } elseif ((int) $data['total'] == 0)
            return self::no_data_to_delete;
        elseif ((int) $data['total'] > 1)
            return self::more_than_one_to_delete;
    }

    /**
     * Checa erros de postagem e retorna mensagens de erros, caso algum seja encontrado.
     * 
     * @return null - Nenhum erro foi encontrado.
     * @return string - String com mensagem de erro.
     */
    public function check_errors($POST,$pk = NULL, $id_reg = NULL) {
        $errors = NULL;
        foreach ($this->XMLForm->field as $XMLField) {
            if ($XMLField->format_date->enabled == "true")
                $errors .= XMLForms_errors::format_date($XMLField, $POST);
            if ($XMLField->is_int->enabled == "true")
                $errors .= XMLForms_errors::is_int($XMLField, $POST);
            if ($XMLField->min_length->enabled == "true")
                $errors .= XMLForms_errors::min_length($XMLField, $POST);
            if ($XMLField->not_null->enabled == "true")
                $errors .= XMLForms_errors::not_null($XMLField, $POST);
            if ($XMLField->repeat->enabled == "true")
                $errors .= XMLForms_errors::repeat($XMLField, $POST);
            if ($XMLField->unique->enabled == "true")
                $errors .= XMLForms_errors::unique($XMLField, $POST, $this->table(), $pk, $id_reg);
            if ($XMLField->validate_email->enabled == "true")
                $errors .= XMLForms_errors::validate_email($XMLField, $POST);
        }
        return $errors;
    }

    /**
     * Posta dados sem efetuar nenhuma verificação.
     * @param array $POST - Dados a serem postados
     * @return NULL Postagem com sucesso
     * @return string Mensagem de erro.
     */
    public function insert_direct($POST) {
        return $this->_post($POST);
    }

    /**
     * Atualiza dados sem efetuar nenhuma verificação.
     * @param array $POST - Dados a serem postados
     * @param string $id_reg - Registro que será atualizado
     * @param string $field_name - Nome do campo a ser atualizado (usa chave primária se este for null)
     * @return NULL Postagem com sucesso
     * @return string Mensagem de erro.
     */
    public function update_direct($POST, $id_reg, $field_name = NULL) {
        if (!$field_name) //Se não tiver field_name, atribui a primary_key como o mesmo
            $field_name = $this->primary_key();
        $where_array = array($field_name => $id_reg);
        if (!$id_reg)
            return self::update_no_id_reg;
        return $this->_post($POST, $where_array);
    }

    /**
     * Faz todo o tratamento necessário para um relacionamento de dados, como inserir ou atualizar
     * etc.
     * @param string $relation_value - Valor relacionado (O valor relacionado ao si (ex: cliente nome:Carlos)
     * @param string $key_value - O ID do valor relacionado (a chave estrangeira) (ex: cliente id:22)
     * @param SimpleXMLElement $XMLField - O XML do campo a ser usado no tratamento.
     */
    public static function process_data_related($relation_value, $key_value, $XMLField) {
        $table = (string) $XMLField->relate->table;
        $pk = (string) $XMLField->relate->primary_key;
        $field_name = (string) $XMLField['name'];
        if (!$table || !$pk || !$field_name || !$relation_value)
            return $key_value; //São importantes rel_key, table e primary_key. Se não existirem, retorna o key_value que recebeu (se não recebeu retorna nulo).
        $Mysql = new mysql();
        if ($key_value) {
            if ($XMLField->relate->readonly == "true") //Se for readonly e tem ID, não pode atualizar registro. Então retorna já o $key_value
                return $key_value;
            $data = $Mysql->simple_data($table, array($pk => $key_value));
            if ($data)
                if ($data[$field_name] && $relation_value)
                    return self::_update_data_related($relation_value, $key_value, $XMLField); //Existe mas é diferente? Então atualiza o dado e retorna seu ID
                else {
                    
                }//Vazio porque este esta relacionado ao if do meio
            else
                return self::_insert_data_related($relation_value, $XMLField); //Foi passado um ID mas não existe, insere novo e retorna novo ID
        }else { //Não tem $key_value?  Então primeiro analisa uma possível nova inserção.
            $data = $Mysql->get_data($table, array($field_name => $relation_value));
            if ($data)
                return $data[$pk]; //Se existir o dado relacionado, mas não foi setado o ID, retorna o ID do dado.
            elseif ($XMLField->relate->readonly != "true") //Se for readonly, consequentimente não consegue inserir um novo registro, logo retorna nulo
                return self::_insert_data_related($relation_value, $XMLField); //Não existe? então insere o mesmo e retorna o novo ID do dado.
        }
    }

    private static function _update_data_related($relation_value, $key_value, $XMLField) {
        $table = (string) $XMLField->relate->table;
        $pk = (string) $XMLField->relate->primary_key;
        $field_name = (string) $XMLField['name'];
        $arrUpdate = array(
            $field_name => $relation_value
        );
        $where_array = array(
            $pk => $key_value
        );
        $Mysql = new mysql();
        $Mysql->update($table, $arrUpdate, $where_array);
        return $key_value;
    }

    private static function _insert_data_related($relation_value, $XMLField) {
        $table = (string) $XMLField->relate->table;
        $field_name = (string) $XMLField['name'];
        $arrInsert = array(
            $field_name => $relation_value
        );
        $Mysql = new mysql();
        $Mysql->insert($table, $arrInsert);
        return mysql_insert_id();
    }

    /**
     * Este cuida de enviar os dados do formulário para tabela.
     * Se tiver $where_array, efetua um update (atualização)
     * Se não tiver, efetua uma nova inserção.
     */
    public function _post($POST, $where_array = array()) {
        $arrPost = array();
        foreach ($this->XMLForm as $XMLField) { //Processa campo a campo, apenas os campos postaveis
            if ($XMLField->post == "true") {
                $field_classname = XMLForms_fields::field_classname($XMLField);
                if (class_exists($field_classname)) {
                    $FieldClass = new $field_classname($XMLField);
                    $post_field = $FieldClass->sql_data($POST); //O objeto do field diz o que deverá ser salvo
                    if ($post_field) {//Se este retornar algum valor, adiciona o mesmo ao $arrInsert
                        $key = $post_field['field'];
                        $value = $post_field['value'];
                        if ($value)
                            $arrPost[$key] = $value;
                    }
                }
            }
        }
        //Agora verifica se existem dados para serem inseridos. Caso existam, prontamente efetua a inserção dos mesmos
        if ($arrPost) {
            $table = (string) $this->XMLForm->table;
            if ($table) {
                $Mysql = $this->Mysql();
                if ($where_array)
                    $Mysql->update_no_quotes($table, $arrPost, $where_array);
                else
                    $Mysql->insert_no_quotes($table, $arrPost);
                $this->last_insert = $Mysql->get_last_line($table);
            } else {
                return $this->form_name . self::no_table_to_insert;
            }
        }else
            return $this->form_name . self::no_data_insert;
    }

}

?>
