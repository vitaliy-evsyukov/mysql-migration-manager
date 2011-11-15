<?php

namespace lib;

class dbDiff {

    protected $_currentTable;
    protected $_tempTable;

    /**
     * Запросы в обе стороны
     * @var array
     */
    private $difference = array('up' => array(), 'down' => array());
    private $_tables = array();

    public function __construct(Mysqli $current, Mysqli $temp) {
        $this->_currentTable = $this->getDbName($current);
        $this->_tempTable = $this->getDbName($temp);
    }

    private function getDbName(Mysqli $connection) {
        $sql = "SELECT DATABASE() as dbname";
        $res = $connection->query($sql);
        $row = $res->fetch_array(MYSQLI_ASSOC);
        $res->free_result();
        return $row['dbname'];
    }

    /**
     * Парсит вывод mysqldiff, составляет список использованных и неиспользованных таблиц
     * @param array $output Вывод mysqldiff
     * @return array 
     */
    private function parseDiff(array $output = array()) {
        $comment = '';
        $tmp = array();
        $result = array();
        $index = 0;

        foreach ($output as $line) {
            $line = trim($line);
            if (empty($line))
                continue;
            if (strpos($line, '--') === 0) {
                // это комментарий с именем таблицы
                $comment = explode('|', trim(substr($line, 2)));
                if (is_array($comment)) {
                    // множество зависимых таблиц
                    $tableName = array_shift($comment);
                    foreach ($comment as $table) {
                        $this->_tables['unused'][$table] = 1;
                    }
                    $comment = $tableName;
                }
                $this->_tables['used'][$comment] = 1;
                $tmp = array();
                $index = 0;
                isset($result[$comment]) && ($index = sizeof($result[$comment]));
            } else {
                $tmp[] = $line;
                if (!empty($comment)) {
                    // добавим предыдущие собранные данные в результирующий массив
                    $result[$comment][$index] = implode("\n", $tmp);
                }
            }
        }

        return $result;
    }

    /**
     * Делегирует работу mysqldiff 
     * @param array $tablesList Список таблиц
     * @return array 
     */
    public function getDifference(array $tablesList = array()) {
        $params = array('host', 'user', 'password');
        $params_str = array();
        foreach ($params as $param) {
            $value = Helper::get($param);
            if (!empty($value)) {
                $params_str[] = "--{$param}={$value}";
            }
        }

        $tables = array($this->_currentTable, $this->_tempTable);
        $dirs = array('up', 'down');
        $command = Helper::get('mysqldiff_command') . ' ' . implode(' ', $params_str);

        for ($i = 0; $i < 2; $i++) {
            $return_status = 0;
            $output = array();
            $last_line = exec($command . " --list-tables -n {$tables[$i]} {$tables[1 - $i]}", $output, $return_status);
            $this->difference[$dirs[$i]] = $this->parseDiff($output);
        }

        $this->_tables['unused'] = array_diff_key($this->_tables['unused'], $this->_tables['used']);

        return $this->difference;
    }

}

