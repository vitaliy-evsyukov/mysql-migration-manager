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

    public function __construct(MysqliHelper $db, array $args = array()) {
        // вынести в разбор параметров
        foreach ($args as $index => $arg) {
            if (is_string($arg)) {
                $arg_data = explode('=', $arg);
                /**
                 * Если параметр был передан корректно (в форме имя=значение)
                 */
                if (sizeof($arg_data) !== 1) {
                    $param_name        = str_replace('-', '', $arg_data[0]);
                    $param_value       = str_replace('"', '', $arg_data[1]);
                    $args[$param_name] = $param_value;
                }
                unset($args[$index]);
            }
        }

        if (!empty($args['datasets'])) {
            if (is_string($args['datasets'])) {
                $datasets         = explode(',', $args['datasets']);
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
        }
        else {
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
     * @param string $query         Запросы с разделителем
     * @param bool   $inTransaction Запросы исполняются в транзакции
     */
    protected function multiQuery($query, $inTransaction = false) {
        $counter = 1;
        try {
            $ret  = $this->db->multi_query($query);
            $text = $this->db->error;
            $code = $this->db->errno;
            if (!$ret) {
                throw new \Exception($text, $code);
            }
            do {
                $counter++;
            } while ($this->db->next_result());
            $text = $this->db->error;
            $code = $this->db->errno;
            if ($code) {
                throw new \Exception($text, $code);
            }
            $inTransaction && $this->db->query('COMMIT;');
        } catch (\Exception $e) {
            $inTransaction && $this->db->query('ROLLBACK;');
            throw new \Exception("An error was occured: {$e->getMessage()} ({$e->getCode()}). Line: {$counter}");
        }
    }

    /**
     * Удаляет все содержимое БД
     */
    protected function dropAllDBEntities() {
        $operations = array(
            'list'  => array(
                "SHOW FULL TABLES WHERE Table_type LIKE '%%%s'",
                'SHOW %sS',
                "SHOW %s STATUS WHERE Db='" . $this->db->getDatabaseName() . "'"
            ),
            'links' => array(
                'FUNCTION'  => array(
                    'list' => 2,
                    'name' => 1
                ),
                'PROCEDURE' => array(
                    'list' => 2,
                    'name' => 1
                ),
                'TRIGGER'   => array(
                    'list' => 1,
                    'name' => 0
                ),
                'VIEW'      => array(
                    'list' => 0,
                    'name' => 0
                ),
                'TABLE'     => array(
                    'list' => 0,
                    'name' => 0
                )
            )
        );

        $queries = array();
        foreach ($operations['links'] as $entity => $definition) {
            $query = $operations['list'][$definition['list']];
            $res   = $this->db->query(sprintf($query, $entity));
            if ($res) {
                while ($row = $res->fetch_array(MYSQLI_NUM)) {
                    $name           = $row[$definition['name']];
                    $queries[$name] = sprintf("DROP %s %s;", $entity, $name);
                }
                $res->free_result();
            }
        }

        if (!empty($queries)) {
            Output::verbose("Views and tables and routines are dropping now", 1);
            Output::verbose(
                sprintf(
                    "--- %s", implode("\n--- ", array_keys($queries))
                ), 2
            );
            $this->multiQuery(implode('', $queries));
        }
    }

    /**
     * Получить цепочку ответственности
     * @return \lib\ControllersChain
     */
    public function getChain() {
        return $this->_chain;
    }

}

?>