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
        $start = microtime(1);
        if (!empty($this->_tables)) {
            $direction = array_intersect_key($direction, $this->_tables);
        }
        Output::verbose(
                sprintf('Intersection tables time: %f', (microtime(1) - $start)),
                3
        );
        $start = microtime(1);
        $query = array();
        foreach ($direction as $statements) {
            $query[] = implode("\n", $statements);
        }
        Output::verbose(
                sprintf('Implode time: %f', (microtime(1) - $start)), 3
        );
        $start = microtime(1);
        if (!empty($query)) {
            Helper::_debug_queryMultipleDDL($this->db, $direction);
//            try {
//                Helper::queryMultipleDDL($this->db,
//                        stripslashes(implode("\n", $query)));
//            }
//            catch (\Exception $e) {
//                $m = $e->getMessage();
//                $m_p = explode('|', $m);
//                throw new \Exception($query[(int) $m_p[1]] . ': ' . $m_p[0], $e->getCode());
//            }
        }
        Output::verbose(
                sprintf('Summary execution time: %f', (microtime(1) - $start)),
                3
        );
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