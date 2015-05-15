<?php

namespace lib;

use lib\Helper\Container;

/**
 * AbstractSchema
 * Абстрактный класс схем данных
 * @author Виталий Евсюков
 */
abstract class AbstractSchema
{

    const MIGRATED = 'migrated';
    const ORIGINAL = 'original';

    /**
     * Список запросов
     * @var array
     */
    protected $_queries = array();

    /**
     * Список в виде имя сущности => md5-хеш от ее описания
     * @var array
     */
    protected $_tables = array();

    /**
     * Редакция, которой соответствует схема
     * @var int
     */
    protected $_revision = 0;

    /**
     * Разворачивает схему данных
     * @param MysqliHelper $db
     * @param Container $container
     */
    public function load(MysqliHelper $db, Container $container)
    {
        if ((int) $container->getInit()->get('verbose') >= 3) {
            $container->getDb()->debugQueryMultipleDDL($db, $this->_queries);
        } else {
            $container->getDb()->queryMultipleDDL(
                $db,
                implode("\n", $this->_queries)
            );
        }
    }

    /**
     * Возвращает список таблиц
     * @return array
     */
    public function getTables()
    {
        return array_keys($this->_tables);
    }

    /**
     * Возвращает хеши сущностей
     * @return array
     */
    public function getHashes()
    {
        return $this->_tables;
    }

    /**
     * Возвращает список запросов
     * @return array
     */
    public function getQueries()
    {
        return $this->_queries;
    }

    /**
     * Возвращает номер ревизии схемы
     * @return int
     */
    public function getRevision()
    {
        return (int) $this->_revision;
    }
}