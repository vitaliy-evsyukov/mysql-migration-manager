<?php

namespace lib;

use \Mysqli;

abstract class AbstractMigration {

    /**
     *
     * @var Mysqli
     */
    protected $db;
    protected $up = array();
    protected $down = array();
    protected $rev = 0;
    protected $metadata = array();
    protected $_tables = array();

    public function __construct(mysqli $db = null) {
        $this->db = $db;
    }

    /**
     * Устанавливает таблицы, операторы для которых необходимо выполнять
     * @param array $tablesList 
     */
    public function setTables(array $tablesList = array()) {
        $this->_tables = $tablesList;
    }

    private function runDirection($direction) {
        if (!empty($this->_tables)) {
            $direction = array_intersect_key($direction, $this->_tables);
        }
        $query = array();
        foreach ($direction as $statements) {
            $query[] = implode("\n", $statements);
        }
        if (!empty($query)) {
            Helper::queryMultipleDDL($this->db,
                    stripslashes(implode("\n", $query)));
        }
    }

    public function runUp() {
        $this->runDirection($this->up);
    }

    public function runDown() {
        $this->runDirection($this->down);
    }

    public function getMetadata() {
        return $this->metadata;
    }

    public function getStatements() {
        return array(
            'up' => $this->up,
            'down' => $this->down
        );
    }

}