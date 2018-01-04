<?php

/**
 * Objeto voltado para operaçõs financeiras.
 * Basea-se no uso de XML para definir campos, formulários e tabelas
 * 
 * DEPENDE DE XmlForms, Mysql e suas depedencias
 * 
 * Inserções e edições de registros são executados pelo objeto xmlForms.
 * o objeto cadastros.php e controle_financeiro.php são responsáveis por auxiliar
 * a correta inserção, e edição. Assim como são responsaveis pelas corretas operações
 * de extorno e impressão dos extratos.
 * 
 * Neste objeto temos:
 * Geração de matriz com extrato
 * Cálculo de balanço financeiro
 * Geração de array para operação de extorno
 * 
 * @version 1.3
 * -----------------------------------------------------------------------------
 * Changelog: 06.09.2012
 * -----------------------------------------------------------------------------
 *  Versao 1.3-2012.09.06- Criadas variações para o campo operation (na pratica usado por estorno) e debug
 *  Versao 1.2-2012.08.28- Resolvidos Bugs uso novos recursos XMLForms
 *  Versao 1.0-2012.08.23- Aplicado referencia ao campo da tabela (implementado no XML)
 *  Versao 1.0-2012-08.03- Efetuada versão 1.0 após testes
 *  Versao 0.1-2012.05.28- Criação do objeto
 * @author juniorfox
 */
class financial {

    public $version = 1.3;
    private $_XMLFReader; /* Objeto XMLForms */
    private $_Mysql; /* Objeto MySQL */
    private $_formName; /* Nome do form tratado nesta instancia */
    private $_arrSearch = array(); /* Array de busca usado para buscar extrato */
    private $_arrCommands = array(); /* Array de comandos de busca para buscar extrato */
    private $_arrSearchPeriod = array(); /* Array de busca apenas com dados sobre período */
    private $_arrCommandsPeriod = array(); /* Array de comandos de busca apenas sobre periodo */
    private $_tmp_prev_total_results = null; /* Cache de total de resultados utilizados pelo calc_prev_balance() */
    private $_arrForm = array(); /* Cache com array dos fields do xmlForm */

    /**
     *  Array com cache de referencias de id de formulário 
     */
    public $keyfields = array();

    /**
     * Se true, calcula o balanço anterior e adiciona ao extrato.
     */
    public $previous_balance = true;

    /**
     * Mensagem de balanço anterior do extrato
     */
    public $lbl_previous_balance = "Previous Balance";

    /**
     * Mensagem de Totais do extrato
     */
    public $totals = "Totals";

    /**
     * Código de paginas padrão para este.
     */
    public $language;

    /**
     * String para descricao de estorno
     */
    public $reverse_description = ' **ESTORNO**';

    /**
     * Atribui nome do XmlFile.
     * @param string $xmlFile - Local do arquivo XML no servidor
     */
    public function __construct($xmlFile, $formName) {
        add_to_library($GLOBALS['global_vars']['lib_path'] . 'laboratory/');
        /* Monta objetos necessários (dependencias) */
        if (!$formName)
            die('<b>Erro:</b> No objeto <b><i>Financial</i></b>, n&atilde;o foi
                definido <b>$formName</b>.');
        $this->_XMLFReader = new XMLForms_reader($xmlFile, $formName);
        $this->_XMLFReader->disableDataStr = true;
        $this->_formName = $formName;
        $this->_Mysql = new mysql();
        /* Busca lingua padrão do documento */
        $this->language = $this->_XMLFReader->language;
        $this->_check_view_from_XMLFReader();
    }

    /**
     * Faz os devidos calculos e retorna saldo a partir de XML aplicando os
     * determinados filtros
     * @return array - Matriz com o extrato
     */
    public function statement() {
        $matrix = $this->_get_statement_lines(); /* Busca campos do extrato */
        if ($this->previous_balance) {/* Calcula o balanco anterior se este for true */
            $prev_bal = $this->calc_prev_balance();
            $matrix = array_merge($this->_bal_stt($prev_bal), $matrix); /* Adiciona balanço anterior ao extrato */
        }

        $matrix = $this->_get_balance_statement($matrix); /* Calcula balanço do extrato e adiciona balanco a cada linha */
        $debits_credits_bal = $this->_get_deb_cred_bal_statement($matrix); /* Cria variaveis de debito e credito */
        /* Cria novo array com os seguintes campos: 'statement' com $matrix(extrato), debits(debitos), credits(creditos) e balance(balanco) */
        $data = array_merge(array('statement' => $matrix), $debits_credits_bal);
        /* Se não existir valor nenhum a ser retornado, retorna nulo */
        if (!$data['debits'] && !$data['credits'] && !$data['balance'] && !$data['statement'])
            return array();
        /* Há dados para o retorno, retona-os então */
        return $data;
    }

    /**
     * Faz o mesmo que statement, porém formata os valores nas chaves:
     *  - date, debit, credit, balance (dentro da matriz)
     *  - debits, credits e balance (de fora da matriz)
     * @return array - Matriz com o extrato formatado na lingua padrão.
     */
    public function statement_formatted() {
        $Local_formats = new local_formats();
        /* Se não existir valor nenhum a ser retornado, retorna nulo */
        $statement = $this->statement();
        if (!$statement)
            return array();
        $sf = array();
        $keys = array(
            $this->field('description') => 'string',
            $this->field('date') => 'date',
            $this->field('debit') => 'double',
            $this->field('credit') => 'double',
            'balance' => 'double',
        );
        /* primeiro calcula variaveis dentro da matriz */

        foreach ($statement['statement'] as $line) {
            $lf = $line;
            /* Converte os valores definidos na matriz keys */
            foreach (array_keys($keys) as $key) {
                if ($line[$key])
                    $lf[$key] = $Local_formats->data_to_local_str($line[$key], $keys[$key]);
                if ($this->field_reference($key))
                    $lf[$this->field_reference($key)] = $lf[$key];
            }
            $sf['statement'][] = $lf;
        }
        /* Agora trata os valores debits, credits e balance de fora da matriz */
        $keys = array('debits', 'credits', 'balance'); /* Como sao todos real, não preciso declarar o tipo */
        foreach ($keys as $key) {
            if ($statement[$key] !== true)
                $sf[$key] = $Local_formats->data_to_local_str($statement[$key], 'double');
        }
        return $sf;
    }

    /**
     * Define período de extrato a ser buscado
     * @param date $dt_start = data inicial de busca
     * @param date $dt_end = data final de busca
     */
    public function filter_by_period($dt_start, $dt_end = null) {
        /* Analisa as datas e verifica se as mesmas são válidas */
        if (!strtotime($dt_start))
            $dt_start = null;
        if (!strtotime($dt_end))
            $dt_end = null;
        if ($dt_start) {
            $this->_arrSearchPeriod[$this->field('date')] = $dt_start . "%";
        }
        if ($dt_end) {
            $this->_arrSearchPeriod[$this->field('date') . ' '] = $dt_end . "%";
        }

        $this->_arrCommandsPeriod = array(
            'logic_operator' => array(
                $this->field('date') => '>=',
                $this->field('date') . ' ' => '<='
            ),
            'condition' => 'AND'
        );
    }

    /**
     * Calcula o balanço anterior ao período filtrado.
     * Só aplicado quando filtrado período
     * 
     * @return real - Valor do balanço anterior
     * @return 0.00 - Se não foi setado período de busca
     * 
     * O Balanço filtrado atende aos demais filtros setados arrSearch
     */
    public function calc_prev_balance() {
        $step = 5000;
        $stepcount = 0;
        $this->_tmp_prev_total_results = null;
        /* Executa o primeiro passo de fora */
        $balance = $this->_recursive_prev_balance($step);
        $stepcount = $stepcount + $step;
        /* Executa os passos seguintes de dentro do while */
        while ($stepcount <= $this->_tmp_prev_total_results) {
            $balance = $balance + $this->_recursive_prev_balance($step, $stepcount);
            $stepcount = $stepcount + $step;
        }
        return $balance;
    }

    /**
     * Adiciona ou sobrepoe de busca para o extrato
     * os filtros 'date' e 'date ' não são aceitos por serem criticos no cálculo
     * do balanço.
     * @param string nome do filtro
     * @param string valor filtrado
     */
    public function add_filter($filter, $value) {
        if ($filter != 'date' || $filter != 'date ')
            $this->_arrSearch[$filter] = $value;
    }

    /**
     * Remove um filtro de busca da selação
     * @param string nome do filtro a ser removido
     */
    public function remove_filter($filter) {
        unset($this->_arrSearch[$filter]);
    }

    /**
     * Limpa todos os filtros de busca setados
     */
    public function clear_filters() {
        $this->_arrSearch = array();
    }

    /**
     * Modifica dados enviados no $formData para dados relativos a estorno modificando
     * a data, a operação e a descrição.
     * @param array $formData Os dados originais para gerar-se o estorno
     * @return array Dados do formdata estornados
     */
    public function create_reverse_operation($id_operation, $formData = array()) {
        /* Checa colunas */
        $this->_check_view_from_XMLFReader(true); //TRUE = Checar campo operacao 
        /* Agora aplica valores de estorno as chaves corretas */
        $formData[$this->field('description')] = $formData[$this->field('description')] . $this->reverse_description;
        $formData[$this->field('operation')] = $this->_revert_operation($formData[$this->field('operation')]);
        $formData[$this->field('date')] = '$_DATE_TODAY'; /* Variavel normalizada no XMLForms para apontar o dia de hoje */
        $formData[$this->field('return')] = $id_operation; /* Sinaliza que esta operação se trata de um estorno utilizando o ID da operação original */
        return $formData;
    }

    /**
     * Retorna o nome do campo a partir da referencia passada. 
     */
    public function field($field_reference) {
        /* Se ja existir o campo de referencia na cache ($this->keyfields), retorna o nome real do campo */
        if (isset($this->keyfields[$field_reference]))
            return $this->keyfields[$field_reference];

        if (!$this->_arrForm) {/* Usa cache para economizar rotina */
            $this->_arrForm = $this->_XMLFReader->listFields();
        }

        /* Não está na cache o campo de referencia? Varre o XML do form para encontrar este campo */
        foreach ($this->_arrForm as $arrField) { //Verifica se existe o campo com o nome propriamente dito ou o campo com o nome de referencia
            if (@$arrField['financial']['keyfield'] == $field_reference) {
                $this->keyfields[$field_reference] = (string) trim($arrField['@attributes']['name']);
                break;
            } elseif (@$arrField['@attributes']['name'] == $field_reference) {
                $this->keyfields[$field_reference] = (string) trim($arrField['@attributes']['name']);
                break;
            }
        }
        /* Mesmo assim não encontrou? então apela. Simplismente adiciona o próprio nome a chave field_reference e pronto */
        if (!isset($this->keyfields[$field_reference]))
            $this->keyfields[$field_reference] = $field_reference;
        /* Achou? Então executa este metodo recursivamente. Agora ele irá retornar da cache */
        if (isset($this->keyfields[$field_reference]))
            return $this->field($field_reference); /* Retorna ele mesmo, agora a partir da cache de dados */
    }

    /**
     * Retorna o inverso de field, ou seja, você da o campo e ele retorna a referencia
     */
    public function field_reference($field) {
        $found = false;
        /* Se ja existir o campo de referencia na cache ($this->keyfields), retorna o nome de referencia */
        foreach (array_keys($this->keyfields) as $key) {
            if ($this->keyfields[$key] == $field)
                return $key;
        }
        if (!$this->_arrForm) {/* Usa cache para economizar rotina */
            $this->_arrForm = $this->_XMLFReader->listFields();
        }

        /* Não está na cache o campo de referencia? Varre o XML do form para encontrar este campo */
        foreach ($this->_arrForm as $arrField) { //Verifica se existe o campo com o nome propriamente dito ou o campo com o nome de referencia
            if ($arrField['@attributes']['name'] == $field) {/* Encontrou? */
                if ($arrField['financial']['keyfield']) {
                    $field_reference = (string) trim($arrField['financial']['keyfield']);
                    $this->keyfields[$field_reference] = $arrField['@attributes']['name'];
                } else {
                    $field_reference = (string) trim($arrField['@attributes']['name']);
                }
                $this->keyfields[$field_reference] = $field_reference;
                $found = true;
                break;
            }
        }
        /* Encontrou? Executa recursivamente este método. Agora ele irá retornar corretamente */
        if ($found)
            return $this->field_reference($field);
    }

    /**
     * Chamado por calc_prev_balance() apenas.
     * Calcula o balanço anterior, mas faz o calculo a cada 100 registros para
     * evitar estouro de memória, adicionando o valor do balanço a cada passo
     * que é executado.
     */
    private function _recursive_prev_balance($step, $limit_start = 0) {
        if (!isset($this->_arrSearchPeriod[$this->field('date')]))
            return 0.00;
        elseif (!$this->_arrSearchPeriod[$this->field('date')]) {
            return 0.00;
        }
        $datePeriod = $this->_arrSearchPeriod[$this->field('date')];
        /* Busca filtros e remove filtro por período */
        $arrSearch = $this->_arrSearch;
        $arrCommands = $this->_arrCommands;
        /* Remove estes apenas por precaução. Não queremos que os filtros saem errados */
        unset($arrSearch[$this->field('date')], $arrSearch[$this->field('date') . ' ']);
        unset($arrCommands['logic_operator'][$this->field('date')], $arrCommands['logic_operator'][$this->field('date') . ' ']);
        /* Adiciona os filtros para esta busca */
        $arrSearch[$this->field('date')] = $datePeriod;
        $arrCommands['logic_operator'][$this->field('date')] = '<';
        $arrCommands['orderby_field'] = $this->field('date');
        $arrCommands['orderby_descasc'] = 'ASC';
        $arrCommands['condition'] = 'AND';
        /* Pega nome de view */
        $tb_view = $this->_check_view_from_XMLFReader();
        /* Caso tamanho total não tenha sido setado, define tamanho total da busca */
        if ($this->_tmp_prev_total_results === null) {
            $this->_tmp_prev_total_results = $this->_Mysql->count($tb_view);
        }
        $arrCommands['limits_start'] = $limit_start;
        $arrCommands['total_results'] = $step;
        /* Faz a busca dos dados */
        //$this->_Mysql->debug = true;
        $this->_Mysql->search($tb_view, $arrSearch, $arrCommands);
        $matrix = $this->_Mysql->matrix_fetch_array();
        //$this->_Mysql->debug = false;
        /* Verifica a data do ultimo registro retornado, este só pode ser menor que a data analisada */
        if ($matrix[count($matrix) - 1][$this->field('date')] >= $datePeriod) {
            echo "<b>Alerta</b>: Em <b> Finance::calc_prev_balance</b> 
                a maior data é superior ou igual a data filtrada para balanço.<br />
                A data analisada é <b>$datePeriod</b> e a maior data é 
            <b>" . $matrix[count($matrix) - 1][$this->field('date')] . "</b>";
        }
        /* Depois de todos estes testes, mudancas de variaveis e verificações, calcula o balanço anterior */
        if ($matrix) {
            $deb_credit = $this->_get_deb_cred_bal_statement($matrix);
            return $deb_credit['credits'] - $deb_credit['debits'];
        }
        /* Se caso no final não haja nada para retornar, retorna o valor 0 */
        return 0.00;
    }

    /**
     * Inverte o valor da operação, os valores aceitos são + e -
     * @param char $value operador a ser invertido
     * @param char operador com o valor inverso fo valor de $value
     */
    private function _revert_operation($value) {
        if ($value == "+")
            $value = "-";
        elseif ($value == "-")
            $value = "+";
        return $value;
    }

    /**
     * Gera linha com balanço para inserir no extrato
     */
    private function _bal_stt($prev_bal) {
        $matrix_line = array();

        if ($prev_bal == 0.00 || !isset($this->_arrSearchPeriod[$this->field('date')]))
            return array();
        /* Busca keys da primeira linha da matrix */
        $xmlFields = $this->_XMLFReader->listFields();
        foreach (array_keys($xmlFields) as $key) {
            $line[$key] = "";
        }
        /* Prepara campos */
        $date = str_replace("%", "", $this->_arrSearchPeriod[$this->field('date')]);

        $line[$this->field('description')] = $this->lbl_previous_balance;

        $line[$this->field('date')] = $date;
        $line['balance'] = $prev_bal;
        $matrix_line[] = $line;
        return $matrix_line;
    }

    /**
     * Para uso interno, retorna matriz com saldos dentro dos parametros
     * definidos do formulário XML
     * @return array - Matriz com lista de dados do formulario de extrato
     */
    private function _get_statement_lines() {
        /* arrCommands é um merge de comandos padrão (order by date) + comandos de busca + 
         * comandos de busca por período.
         */
        $num_of_regs = $this->_XMLFReader->filter_and_totals(
                $this->_merge_arrSearch(), $this->_merge_arrCommands()
        );
        $matrix = $this->_XMLFReader->do_unformatedlist(0, $num_of_regs);
        if ($matrix)
            return $matrix;
        return array(); /* Caso não encontre nada, retorna array em branco */
    }

    /**
     * Adiciona a matriz de extrato os valores referentes ao balanço
     * @param array $matrix - Matriz de extrato do BD
     * @param real $prev_balance - Balanco anterior de saldo
     * @return array - Matriz de extrato com coluna de balanco adicionada
     */
    private function _get_balance_statement($matrix) {
        $newMatrix = array();
        $bal = 0;
        /* busca linha do balanco anterior (caso exista) */
        if (isset($matrix[0]['balance']))
            if (is_real($matrix[0]['balance']))
                $bal = $matrix[0]['balance'];

        foreach ($matrix as $line) {
            $credit = $line[$this->field('credit')];
            $debit = $line[$this->field('debit')];
            $bal = $bal + $credit - $debit;
            $line['balance'] = $bal;
            $newMatrix[] = $line;
        }
        return $newMatrix;
    }

    /**
     * Varre a matriz de extrato e soma o total de crédito e debito.
     * @param array $matrix - Matriz com extrato pronto
     * @return array - Array com campos credits, debits e balance
     */
    private function _get_deb_cred_bal_statement($matrix) {
        $data = array(
            'credits' => 0.00,
            'debits' => 0.00
        );
        foreach ($matrix as $line) {
            $data['credits'] = $data['credits'] + $line[$this->field('credit')];
            $data['debits'] = $data['debits'] + $line[$this->field('debit')];
        }
        /* Carrega agora a ultima linha do extrato, e adiciona o valor do balanço desta */
        $data['balance'] = 0.00;
        if(array_key_exists('balance',$matrix[count($matrix) - 1])){
            $data['balance'] = $matrix[count($matrix) - 1]['balance'];
        }
        
        return $data;
    }

    /**
     * Abre SimpleXMLElement de xmlForms e busca pelo nome da view usada
     * para o financeiro. 
     * Testa se View existe e testa se as seguintes colunas existem e seus respectivos
     * tipos:
     *  operation : BOOLEAN()
     *  credit: DECIMAL(10,2),
     *  debit: DECIMAL(10,2),
     *  date: DATE
     * 
     * Caso algo esteja errado, imprime erro e sai.
     * @param Checar ou não o campo operação (usado pelo create_reverse_operation)
     * @return string nome da view
     */
    private function _check_view_from_XMLFReader($check_operation = false) {
        $SimpeXMLForm = $this->_XMLFReader->XMLForm;
        $tb_view = (string) $SimpeXMLForm->table_view;
        /* Testa se tb_view foi declarado */
        if (!$tb_view)
            die("<b>Erro:</b> Em <b><i>$this->_formName</i></b>, n&atilde;o foi
                definido <b>table_view</b>");

        /* Testa se colunas existem e são do tipo desejado */
        $columns = $this->_Mysql->get_columns($tb_view);
        $check_columns = array(
            $this->field('date') => true,
            $this->field('credit') => true,
            $this->field('debit') => true,
            $this->field('description') => true
        );
        if ($check_operation) /* Se foi definido para checar operacao... */
            $check_columns[$this->field('operation')] = true;
        /* Definidos os campos, agora testa se eles existem na view indicada */
        foreach ($columns as $column) {
            foreach (array_keys($check_columns) as $check) {
                if ($column['Field'] == $check)
                    unset($check_columns[$check]);
            }
        }
        if ($check_columns) {
            die("<b>Erro:</b> Em <b><i>$this->_formName</i></b>, as colunas 
                    <b>" . implode(',', array_keys($check_columns)) . " </b> n&atilde;o foram
                        encontradas");
        }
        return $tb_view;
    }

    /**
     * Mescla todos os comandos de busca para extrato
     */
    private function _merge_arrCommands() {
        return array_merge(
                        array(
                    'orderby_field' => array(
                        $this->field('date') => 'ASC',
                        'id' => 'ASC'
                    )
                        ), $this->_arrCommands, $this->_arrCommandsPeriod
        );
    }

    /**
     * Mescla todos os campos de busca para extrato
     */
    private function _merge_arrSearch() {
        return array_merge($this->_arrSearch, $this->_arrSearchPeriod);
    }

}

?>