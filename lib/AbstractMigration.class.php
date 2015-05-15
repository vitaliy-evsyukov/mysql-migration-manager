<?php

namespace lib;

use lib\Helper\Container;

/**
 * AbstractMigration
 * Абстрактный класс миграций
 * @author Виталий Евсюков
 */
abstract class AbstractMigration
{

    /**
     * Текущая БД
     * @var MysqliHelper
     */
    protected $db;

    /**
     * Запрос для накатки миграции
     * @var array
     */
    protected $up = array();

    /**
     * Запросы для отката миграции
     * @var array
     */
    protected $down = array();

    /**
     * Ревизия
     * @var int
     */
    protected $rev = 0;

    /**
     * Метаданные
     * @var array
     */
    protected $metadata = array();

    /**
     * Список таблиц в миграции
     * @var array
     */
    protected $tables = array();

    /**
     * @var Container
     */
    protected $container;

    /**
     * @param MysqliHelper $db БД, с которой будет работать миграция
     */
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
        $this->tables = $tablesList;
    }

    /**
     * Выполняет накатку или откат
     * @param string $direction Текстовое название направления
     * @throws \Exception
     */
    private function runDirection($direction)
    {
        if (!empty($this->tables)) {
            $direction = array_intersect_key($direction, $this->tables);
        }
        $start       = microtime(1);
        $helperPrint = $this->container->getOutput();
        if (!empty($direction)) {
            $res            = array(
                'start'  => array(),
                'finish' => array()
            );
            $definersChange = array('add_routine', 'change_routine', 'add_view', 'change_view');
            $changePrefix   = array('add_', 'change_');
            $helperDb       = $this->container->getDb();
            $helperSchema   = $this->container->getSchema();
            foreach ($direction as $table => $statements) {
                foreach ($statements as $statement) {
                    $key = 'start';
                    if (is_array($statement) && isset($statement['type'])) {
                        if (in_array($statement['type'], $definersChange, true)) {
                            $helperDb->setCurrentDb($this->db, 'Migration ' . $this->metadata['revision']);
                            $changeType       = strtoupper(str_replace($changePrefix, '', $statement['type']));
                            $statement['sql'] = $helperSchema->stripTrash(
                                $statement['sql'],
                                $changeType,
                                array('entity' => $table)
                            );
                        }
                        if (in_array($statement['type'], array('change_partitions', 'add_fk'))) {
                            $key = 'finish';
                        }
                        if ($statement['type'] === 'add_table') {
                            $res[$key][$table][] = sprintf(
                                'DROP TABLE IF EXISTS `%s`;',
                                $table
                            );
                        }
                        if (!isset($statement['sql'])) {
                            throw new \Exception(
                                sprintf(
                                    'For table %s in key "%s" there is not valid statement definition: %s',
                                    $table,
                                    $key,
                                    var_export($statement, true)
                                )
                            );
                        }
                        $res[$key][$table][] = $statement['sql'];
                    } else {
                        $helperDb->setCurrentDb($this->db, 'Migration ' . $this->metadata['revision']);
                        $statement           = $helperSchema->stripTrash(
                            $statement,
                            'routine',
                            array('entity' => $table)
                        );
                        $res[$key][$table][] = $statement;
                    }
                }
            }
            $direction = $res;
            unset($res);
            if ((int) $this->container->getInit()->get('verbose') >= 3) {
                foreach ($direction as $order => $ddl) {
                    $helperPrint->verbose(
                        sprintf('Run %s order of queries...', $order),
                        3
                    );
                    $helperDb->debugQueryMultipleDDL($this->db, $ddl);
                }
            } else {
                $query = array();
                foreach ($direction as $statements_group) {
                    foreach ($statements_group as $statements) {
                        $query[] = implode("\n", $statements);
                    }
                }
                $helperDb->queryMultipleDDL(
                    $this->db,
                    implode("\n", $query)
                );
            }
        }
        $helperPrint->verbose(
            sprintf('Summary execution time: %f', (microtime(1) - $start)),
            3
        );
    }

    /**
     * Накатывает миграцию
     * @param Container $container
     * @throws \Exception
     */
    public function runUp(Container $container)
    {
        $this->container = $container;
        $this->runDirection($this->up);
    }

    /**
     * Откатывает миграцию
     * @param Container $container
     * @throws \Exception
     */
    public function runDown(Container $container)
    {
        $this->container = $container;
        $this->runDirection($this->down);
    }

    /**
     * Возвращает метаданные
     * @return array
     */
    public function getMetadata()
    {
        return $this->metadata;
    }

    /**
     * Возвращает запросы
     * @return array
     */
    public function getStatements()
    {
        return array(
            'up'   => $this->up,
            'down' => $this->down
        );
    }
}