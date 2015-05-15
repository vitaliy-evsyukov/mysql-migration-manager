<?php

namespace lib\Helper;

/**
 * Container
 * Контейнер зависимостей
 * @author  Виталий Евсюков
 * @package lib\Helper
 */

class Container
{
    /**
     * Набор созданных зависимостей
     * @var Helper[]
     */
    private $created = [];

    /**
     * Возвращает зависимость по имени класса
     * @param string $className Имя класса
     * @return Helper
     */
    private function getInstance($className)
    {
        $className = '\\lib\\Helper\\' . $className;
        if (!isset($this->created[$className])) {
            $this->created[$className] = new $className($this);
        }
        return $this->created[$className];
    }

    /**
     * Возвращает хелпер конфигураций
     * @return Init
     */
    public function getInit()
    {
        return $this->getInstance('Init');
    }

    /**
     * Возвращает хелпер работы с ФС
     * @return Filesystem
     */
    public function getFileSystem()
    {
        return $this->getInstance('Filesystem');
    }

    /**
     * Возвращает хелпер вывода на экран
     * @return Output
     */
    public function getOutput()
    {
        return $this->getInstance('Output');
    }

    /**
     * Возвращает хелпер работы с БД
     * @return Db
     */
    public function getDb()
    {
        return $this->getInstance('Db');
    }

    /**
     * Возвращает хелпер работы со схемой
     * @return Schema
     */
    public function getSchema()
    {
        return $this->getInstance('Schema');
    }

    /**
     * Возвращает хелпер работы с миграциями
     * @return Migrations
     */
    public function getMigrations()
    {
        return $this->getInstance('Migrations');
    }
}