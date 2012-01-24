<?php

namespace lib;

use \Mysqli;

/**
 * AbstractMigration
 * Абстрактный класс миграций
 * @author guyfawkes
 */
abstract class AbstractMigration {

    /**
     *
     * @var MysqliHelper
     */
    protected $db;
    protected $up = array();
    protected $down = array();
    protected $rev = 0;
    protected $metadata = array();
    protected $_tables = array();

    public function __construct(MysqliHelper $db = null) {
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
        if (!empty($direction)) {
            if ((int) Helper::get('verbose') === 3) {
                Helper::_debug_queryMultipleDDL($this->db, $direction);
            }
            else {
                $start_i = microtime(1);
                $query = array();
                foreach ($direction as $statements) {
                    $query[] = implode("\n", $statements);
                }
                Output::verbose(
                        sprintf('Implode time: %f', (microtime(1) - $start_i)),
                        3
                );
                Helper::queryMultipleDDL(
                        $this->db, implode("\n", $query)
                );
            }
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