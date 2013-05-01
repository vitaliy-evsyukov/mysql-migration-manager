<?php

namespace lib;

use \Mysqli;

/**
 * AbstractMigration
 * Абстрактный класс миграций
 * @author guyfawkes
 */
abstract class AbstractMigration
{

    /**
     * @var MysqliHelper
     */
    protected $db;
    protected $up = array();
    protected $down = array();
    protected $rev = 0;
    protected $metadata = array();
    protected $_tables = array();

    public function __construct(MysqliHelper $db = null)
    {
        $this->db = $db;
    }

    /**
     * Устанавливает таблицы, операторы для которых необходимо выполнять
     * @param array $tablesList
     */
    public function setTables(array $tablesList = array())
    {
        $this->_tables = $tablesList;
    }

    private function runDirection($direction)
    {
        if (!empty($this->_tables)) {
            $direction = array_intersect_key($direction, $this->_tables);
        }
        $start = microtime(1);
        if (!empty($direction)) {
            $res = array(
                'start'  => array(),
                'finish' => array()
            );
            foreach ($direction as $table => $statements) {
                foreach ($statements as $statement) {
                    $key = 'start';
                    if (is_array($statement) && isset($statement['type'])) {
                        if (in_array(
                            $statement['type'],
                            array('change_partitions', 'add_fk')
                        )
                        ) {
                            $key = 'finish';
                        }
                        $res[$key][$table][] = $statement['sql'];
                    } else {
                        $res[$key][$table][] = $statement;
                    }
                }
            }
            $direction = $res;
            unset($res);
            if ((int) Helper::get('verbose') === 3) {
                foreach ($direction as $order => $ddl) {
                    Output::verbose(
                        sprintf('Run %s order of queries...', $order),
                        3
                    );
                    Helper::_debug_queryMultipleDDL($this->db, $ddl);
                }
            } else {
                $query = array();
                foreach ($direction as $statements_group) {
                    foreach ($statements_group as $statements) {
                        $query[] = implode("\n", $statements);
                    }
                }
                Helper::queryMultipleDDL(
                    $this->db,
                    implode("\n", $query)
                );
            }
        }
        Output::verbose(
            sprintf('Summary execution time: %f', (microtime(1) - $start)),
            3
        );
    }

    public function runUp()
    {
        $this->runDirection($this->up);
    }

    public function runDown()
    {
        $this->runDirection($this->down);
    }

    public function getMetadata()
    {
        return $this->metadata;
    }

    public function getStatements()
    {
        return array(
            'up'   => $this->up,
            'down' => $this->down
        );
    }

}