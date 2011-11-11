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

    public function __construct($current, $temp) {
        $this->_currentTable = $current;
        $this->_tempTable = $temp;
    }

    private function up($sql) {
        if (!strlen($sql))
            return;
        $this->difference['up'][] = $sql;
    }

    private function down($sql) {
        if (!strlen($sql))
            return;
        $this->difference['down'][] = $sql;
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
        
        $toogle = array('--list-tables', '');

        $tables = array($this->_currentTable, $this->_tempTable);
        $command = Helper::get('mysqldiff_command') . ' ' . implode(' ', $params_str);

        for ($i = 0; $i < 2; $i++) {
            $return_status = 0;
            $output = array();
            $last_line = exec($command . "{$toogle[$i]} {$tables[$i]} {$tables[1 - $i]}", $output, $return_status);
            print_r($output);
        }
        die();

        return $this->difference;
    }

}

