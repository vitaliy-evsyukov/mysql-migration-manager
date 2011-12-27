<?php

namespace lib;

/**
 * DatasetsController
 * Общий класс для контроллеров, связанных с датасетами
 * @author guyfawkes
 */
abstract class DatasetsController extends AbstractController {

    protected $_datasetInfo = array();

    /**
     *
     * @var ControllersChain 
     */
    protected $_chain = null;

    public function __construct($db, $args) {
        // вынести в разбор параметров
        foreach ($args as $index => $arg) {
            if (is_string($arg)) {
                $arg_data = explode('=', $arg);
                $param_name = str_replace('-', '', $arg_data[0]);
                $param_value = str_replace('"', '', $arg_data[1]);
                $args[$param_name] = $param_value;
                unset($args[$index]);
            }
        }

        if (!empty($args['datasets'])) {
            if (is_string($args['datasets'])) {
                $datasets = explode(',', $args['datasets']);
                $args['datasets'] = array();
                foreach ($datasets as $dataset) {
                    $args['datasets'][trim($dataset)] = 1;
                }
            }
        }
        else {
            $args['datasets'] = array();
        }

        parent::__construct($db, $args);
    }

    /**
     * Дает добавить к текущей цепи дополнительные элементы
     * @param ControllersChain $chain 
     */
    public function setChain(ControllersChain $chain) {
        $this->_chain = $chain;
    }

    /**
     * Переключает проверку внешних ключей
     * @param int $state 
     */
    public function toogleFK($state) {
        $state = (int) $state;
        if (!in_array($state, array(0, 1))) {
            throw new \Exception("Invalid foreign keys checks status: {$state}\n");
        }
        $command = "SET foreign_key_checks = {$state};";
        if ($state) {
            $this->db->query($command);
        } else {
            $this->db->setCommand($command);
        }
    }

    /**
     * Загружает данные датасетов
     * @return array 
     */
    protected function loadDatasetInfo() {
        $load_data = (empty($this->args['loadData']) xor true);
        return Helper::getDatasetInfo($this->args['datasets'], $load_data);
    }

    /**
     * Корректно выполняет множество запросов
     * @param string $query Запросы с разделителем
     * @param bool $inTransaction Запросы исполняются в транзакции
     */
    protected function multiQuery($query, $inTransaction = false) {
        $counter = 1;
        try {
            $ret = $this->db->multi_query($query);
            $text = $this->db->error;
            $code = $this->db->errno;
            if (!$ret) {
                throw new \Exception($text, $code);
            }
            do {
                $counter++;
            }
            while ($this->db->next_result());
            $text = $this->db->error;
            $code = $this->db->errno;
            if ($code) {
                throw new \Exception($text, $code);
            }
            $inTransaction && $this->db->query('COMMIT;');
        }
        catch (\Exception $e) {
            $inTransaction && $this->db->query('ROLLBACK;');
            throw new \Exception("An error was occured: {$e->getMessage()} ({$e->getCode()}). Line: {$counter}");
        }
    }

    protected function dropAllTables() {
        $res = $this->db->query('SHOW FULL TABLES;');
        $queries = array();
        while ($row = $res->fetch_array(MYSQLI_NUM)) {
            $what = $row[1] === 'VIEW' ? 'VIEW' : 'TABLE';
            $queries[$row[0]] = sprintf("DROP %s %s;", $what, $row[0]);
        }
        $res->free_result();
        if (!empty($queries)) {
            Output::verbose("Views and tables are dropping now", 1);
            Output::verbose(sprintf("--- %s",
                            implode("\n--- ", array_keys($queries))), 2);
            $this->multiQuery(implode('', $queries));
        }
    }

}

?>
