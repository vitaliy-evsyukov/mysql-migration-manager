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

    public function getDifference() {
        $params = array('host', 'user', 'password');
        $params_str = array();
        foreach ($params as $param) {
            $params_str[] = '--'.$param.'='.Helper::get($param);
        }
        $params_str[] = '--list-tables';
        $command = Helper::get('mysqldiff_command') . implode(' ', $params_str) . " {$this->_currentTable} {$this->_tempTable}";
        return $this->difference;
    }

}

