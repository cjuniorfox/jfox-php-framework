<?php

/**
 * Verifica sessoes e gerencia logins.
 * @version 2.1
 * @author Carlos Junior
 * 
 * Faz uso da variavel global login_manVars
 * 
 */
class login_man {

    public $version = 2.1;
    public $table = '{prefixo}_VISAO_CLIENTE_USERS';
    public $fields = array(
        'ID' => 'ID',
        'LOGIN' => 'LOGIN',
        'SENHA' => 'SENHA',
        'NOME_COMPLETO' => 'NOME_COMPLETO',
        'NOME_SIMPLIFICADO' => 'NOME_SIMPLIFICADO'
    );

    /**
     * Se receber valor, este valor será processado como chave de criptografia e
     * descriptografia de senha.
     */
    public $crypt_passwd = null;

    /**
     * Se este for setado, a chave de criptografia passa a ser um campo do registro lido.
     * Este sobrepoe $crypt_passwd.
     * 
     * Vale ressaltar que o nome do campo é o campo relativo e não o nome real do campo,
     * ex: se receber valor "ID", o campo usado será "CLIENTE_USER_ID"
     */
    public $crypt_field = null;

    public function __construct() {
        if (isset($GLOBALS['login_manVars']))
            $this->_process_global_lmVars($GLOBALS['login_manVars']);
    }

    public function msg_erro($view_file) {
        if (file_exists($view_file)) {
            $view = new view();
            die($view->process_view(array(), $view_file));
            return false;
        } else {
            die();
            return false;
        }
    }

    /**
     * METODO ATIVO (Interrompe execução e executa ação.
     * Verifica e valida $_SESSION['LOGIN'].
     * Se for válido, retorna dados do usuario logado
     * Senão, interrompe execução, e imprime view de escape. 
     */
    public function restrict_execution($view_file = NULL) {
        $login_data = $this->verificar_atual_logado();
        if (!$login_data) {
            return $this->msg_erro($view_file);
        }
        return $login_data;
    }

    /**
     * Similar ao restrict_execution, impede que a execução prossiga caso
     * a regra aplicada não tenha valor igual ao solicitado.
     * @param (string) $rule_nam, o nome da regra padrão a ser aplicado.
     * @param (mixed) $value, o valor da regra aplicada
     * @param (string) $view_file, o arquivo view para exibir o erro, caso o mesmo aconteça.
     * @return NULL - Se não existir dados de login ou o usuário não estiver logado.
     * @return array - Dados do login caso o passe na regra.
     * 
     * Esta função encerra a execução prematuramente "die()" caso rode $this->msg_erro();
     */
    public function restrict_rule($rule_name, $value, $view_file = null) {
        $login_data = $this->verificar_atual_logado();
        $f = $this->fields;
        if (!$login_data)// Só executa se existir alguem logado no sistema
            return null;
        $this->restrict_execution($view_file);
        $user_data = $this->_get_login_data($login_data[$f['LOGIN']]);
        if(!array_key_exists($rule_name,$user_data))
                return $this->msg_erro ($view_file);
        elseif($user_data[$rule_name]!= $value)
            return $this->msg_erro ($view_file);
        //Se não deu erro, significa que tudo está certo, portanto retorna $login_data
        return $login_data;
    }

    /*
     * Se login Existir, retorna Array com dados de login.
     * Se login Não-Existir, retorna FALSE.
     */

    public function verificar_atual_logado() {
        $f = $this->fields;
        $login_data = array();
        $this->_session_start();
        if (isset($_SESSION['LOGIN'])) {
                $login_db = $this->_get_login_data($_SESSION['LOGIN']);
                if ($login_db[$f['LOGIN']] == $_SESSION['LOGIN']) {
                    $login_data['LOGIN'] = $login_db[$f['LOGIN']];
                    $login_data['ID'] = $login_db[$f['ID']];
                    $login_data['NOME_SIMPLIFICADO'] = $login_db[$f['NOME_SIMPLIFICADO']];
                    return $login_data;
                }
        }
        return false;
    }

    /* Verifica se o login existe.
     * Retorna array() se existir
     * Retorna false se não existir.
     */

    public function verificar_login_existe($login) {
        return $this->_get_login_data($login);
    }

    /*     * *
     * Testa se dados inseridos são válidos para logar ou não.
     * Caso sejam, registra $_SESSION['LOGIN'] e retorna TRUE,
     * Caso contrario, retorna FALSE
     */

    public function efetuar_login($login, $senha) {
        $f = $this->fields;
        if ($login && $senha) {
            $login_data = $this->_get_login_data($login, $senha);
            if (isset($login_data[$f['LOGIN']]) && $login_data[$f['SENHA']]) {
                if ($login_data[$f['LOGIN']] && $login_data[$f['SENHA']]) {
                    $_SESSION['LOGIN'] = $login_data[$f['LOGIN']];
                    $_SESSION['LOGIN_NAME'] = $login_data[$f['NOME_SIMPLIFICADO']];
                    return true;
                }
            }
        }
        return false;
    }

    /**
     * Cadastra logins na tabela de 
     * 
     * @return falso se cadastro ocorrer OK,
     * @return string de erro se ocorrer erro.
     */
    public function cadastrar_login($campos) {
        $f = $this->fields;
        if ($this->_get_login_data($campos['login'])) {
            return "O login que deseja cadastrar ja existe <br />";
        }
        if (!$campos['senha']) {
            return "A senha não pode estar em branco <br />";
        }
        $mysql = new mysql();
        $mysql->insert($this->table, array(
            $f['LOGIN'] => $campos['login'],
            $f['SENHA'] => $campos['senha'],
            $f['NOME_COMPLETO'] => $campos['nome_completo'],
            $f['NOME_SIMPLIFICADO'] => $campos['nome_simplificado']
        ));
        return false;
    }

    /**
     * Carrega uma propriedade do usuário logado.
     * Caso o usuário não possua tal parametro, define o valor
     * para o mesmo usando $default_value. O tipo do campo é definido pelo
     * tipo do default_value.
     * @param string $parameter_name - Nome do parametro a ser aplicado
     * @param mixed $default_value - Valor padrão a ser aplicado.
     * @return NULL - Não há ninguem logado
     * @return mixed - Parametro pedido, ou $default_value caso não tenha sido setado o mesmo.
     */
    public function load_parameter($parameter_name, $default_value) {
        $login = $this->verificar_atual_logado();
        $f = $this->fields;
        if (!$login)// Só executa se existir alguem logado no sistema
            return null;
        $Mysql = new mysql();
        $table_columns_list = $Mysql->get_columns_list($this->table);
        if (array_search($parameter_name, $table_columns_list)) {
            $arrSearch = array(
                $f['LOGIN'] => $login[$f['LOGIN']]
            );
            $data = $Mysql->simple_data($this->table, $arrSearch);
            return $data[$parameter_name];
        } else {
            $this->_create_login_column($parameter_name, $default_value);
            return $this->load_parameter($parameter_name, $default_value); //Agora roda recursivamente, acredita-se que o dado já foi inserido
        }
    }

    /*
     * Verifica e remove login do usuário.
     * Caso remoção tenha sido OK, retorna TRUE
     * Caso contrário, retorna FALSE.
     */

    public function logout() {
        if (isset($_SESSION['LOGIN'])) {
            unset($_SESSION['LOGIN']);
            return true;
        }
        return false;
    }

    /**
     * Cria coluna do BD mysql a partir dos dados postados
     */
    private function _create_login_column($parameter, $default_value) {
        $login = $this->verificar_atual_logado();
        if (!$login)
            return null;
        $f = $this->fields;
        $Mysql = new mysql();
        $Mysql->alter_table($this->table, $parameter, 'ADD', $this->_get_mysql_type($default_value));
        $Mysql->update($this->table, array($parameter => $default_value), array($f['LOGIN'] => $login[$f['LOGIN']]));
    }

    /**
     * Verifica o tipo de dado Mysql a partir do tipo de variavel de value
     */
    private function _get_mysql_type($value) {
        if (is_int($value))
            return "int(11)";
        elseif (is_string($value))
            return "varchar(" . count($value) * 3 . ")";
        elseif (is_bool($value))
            return "BOOL";
    }

    private function _session_start() {
        if (!isset($_SESSION)) {
            session_start();
        }
    }

    private function _get_login_data($login, $senha = null) {
        $f = $this->fields;
        $search_array[$f['LOGIN']] = $login;

        $mysql = new mysql();
        $data = $mysql->simple_data($this->table, $search_array, "AND");
        if ($data) {
            if ($senha && $senha == $this->_decrypt($data)) {
                return $data;
            } elseif (!$senha) {
                return $data;
            }
        }
        return null;
    }

    /**
     * Retorna password do banco de dados descriptografando-o. Caso necessário.
     */
    private function _decrypt($data) {
        $f = $this->fields;
        $dpasswd = $data[$f['SENHA']];
        if ($this->crypt_passwd) /* Se foi setada chave de criptografia, use esta */ {
            $dpasswd = base64_decrypt($dpasswd, $this->crypt_passwd);
        }
        if ($this->crypt_field) /* Se foi setado campo de criptografia, use ele (sobrepoe crypt_password */ {
            $dpasswd = base64_decrypt($dpasswd, $data[$f[$this->crypt_field]]);
        }
        return $dpasswd;
    }

    /**
     * Esta função é chamada pelo método construtor:
     * Busca valores em $lmVars, e os repassa para as variaveis globais de
     * importância do objeto.
     * 
     * @param array $lmVars - Array com varivais de referência sobre campos e tabelas.
     */
    private function _process_global_lmVars($lmVars) {
        if (@$lmVars['table']) {
            $this->table = $lmVars['table'];
        }
        if (@$lmVars['crypt_passwd']) {
            $this->crypt_passwd = $lmVars['crypt_passwd'];
        }
        if (@$lmVars['crypt_field']) {
            $this->crypt_field = $lmVars['crypt_field'];
        }
        if (@is_array($lmVars['fields'])) {
            foreach (array_keys($lmVars['fields']) as $k) {
                $this->fields[$k] = $lmVars['fields'][$k];
            }
        }
    }

}

?>
