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

    public function setTables(array $tablesList = array()) {
        $this->_tables = $tablesList;
    }

    private function runDirection($direction) {
        if (!empty($this->_tables)) {
            $direction = array_diff_key($this->_tables, $direction);
        }
        $query = array();
        foreach ($direction as $statements) {
            $query[] = implode("\n", $statements);
        }
        Helper::queryMultipleDDL($this->db, stripslashes(implode("\n", $query)));
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