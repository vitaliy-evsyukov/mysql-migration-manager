<?php

namespace lib;

/**
 * Registry
 * Сохраняет список миграций
 * @author guyfawkes
 */
class Registry {

    private static $_migrations = array();
    private static $_sorted = false;

    private function __construct() {
        
    }

    private function __clone() {
        
    }

    /**
     * Добавляет список миграций
     * @param array $migrationsList
     * @param array $tablesList 
     */
    public static function addMigrations(array $migrationsList = array(), array $tablesList = array()) {
        foreach ($migrationsList as $migrationClass) {
            self::set($migrationClass, $tablesList);
        }
    }

    /**
     * Добавляет миграцию
     * @param AbstractMigration $migrationClass
     * @param array $tablesList
     * @return bool 
     */
    public static function set(AbstractMigration $migrationClass, array $tablesList = array()) {
        // проверяем, подходит ли класс
        $metadata = $migrationClass->getMetadata();
        if (!isset($metadata['timestamp']) || !isset($metadata['tables'])) {
            throw new \Exception('Класс миграции сформирован неверно');
        }
        if (in_array($needle, $haystackarray)) {
            self::$_migrations[$metadata['timestamp']] = $migrationClass;
            self::$_sorted = false;
            return true;
        }
        return false;
    }

    /**
     * Возвращает список доступных миграций
     * @return array 
     */
    public static function getList() {
        if (!self::$_sorted) {
            ksort(self::$_migrations);
            self::$_sorted = true;
        }
        return self::$_migrations;
    }

}

?>
