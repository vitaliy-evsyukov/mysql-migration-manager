<?php

namespace lib;

use \Mysqli;

class dbDiff {

    protected $_currentTable;
    protected $_tempTable;

    /**
     * Запросы в обе стороны, связанные и использованные таблицы
     * @var array
     */
    private $_difference = array(
        'up' => array(),
        'down' => array(),
        'tables' => array(
            'used' => array(),
            'refs' => array()
        )
    );

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
            $line = addslashes(trim($line));
            if (empty($line)) {
                continue;
            }
            if (strpos($line, '--') === 0) {
                // это комментарий с именем таблицы
                $comment = explode('|', trim(substr($line, 2)));
                if (is_array($comment)) {
                    // множество зависимых таблиц
                    $tableName = array_shift($comment);
                    foreach ($comment as $table) {
                        $this->_difference['tables']['refs'][$tableName][$table] = 1;
                    }
                    $comment = $tableName;
                }
                $this->_difference['tables']['used'][$comment] = 1;
                $tmp = array();
                $index = 0;
                isset($result['desc'][$comment]) && ($index = sizeof($result['desc'][$comment]));
            } else {
                $tmp[] = $line;
                if (!empty($comment)) {
                    // добавим предыдущие собранные данные в результирующий массив
                    $result['desc'][$comment][$index] = implode("\n", $tmp);
                }
            }
        }

        return $result;
    }

    /**
     * Делегирует работу mysqldiff 
     * @return array 
     */
    public function getDifference() {

        $params = array('host', 'user', 'password');
        $params_str = array();
        foreach ($params as $param) {
            $value = Helper::get($param);
            if (!empty($value)) {
                $params_str[] = "--{$param}={$value}";
            }
        }

        $tables = array($this->_currentTable, $this->_tempTable);
        $dirs = array('down', 'up');
        $command = Helper::get('mysqldiff_command') . ' ' . implode(' ', $params_str);

        $tablesList = array('used' => array(), 'refs' => array());

        for ($i = 0; $i < 2; $i++) {
            $return_status = 0;
            $output = array();
            $last_line = exec($command . " --list-tables --no-old-defs --save-quotes {$tables[$i]} {$tables[1 - $i]}", $output, $return_status);
            if (!empty($output)) {
                $result = $this->parseDiff($output);
                $this->_difference[$dirs[$i]] = $result['desc'];
            }
        }

        return $this->_difference;
    }

}

