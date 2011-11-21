<?php

namespace lib;

/**
 * Registry
 * Сохраняет список миграций
 * @author guyfawkes
 */
class Registry {

    private static $_migrations = array();
    private static $_refsMap = array();

    private function __construct() {
        
    }

    private function __clone() {
        
    }

    private function __wakeup() {
        
    }

    /**
     * Собирает данные в виде 
     * <code>
     * self::$_migrations = array(
     *      'table1' => array(
     *              111111 => 1,
     *              111112 => 2
     *      )
     * );
     * 
     * self::$_refsMap = array(
     *      'table1' => array(
     *              'table2' => 1,
     *              'table3' => 1
     *      )
     * );
     * </code>
     * 
     * Здесь для массива миграций указывается таблица, для нее - таймстампы, для них - ревизии
     * Для массива ссылок указывается таблица, а для нее - связанные таблицы.
     */
    private static function prepareMap() {
        // Вначале соберем все данные из папки схемы
        $schemadir = DIR . Helper::get('schemadir');
        if (!is_dir($schemadir) || !is_readable($schemadir)) {
            throw new \Exception("Директории {$schemadir} с описаниями таблиц не существует\n");
        }

        // SQL считается первой ревизией
        $handle = opendir($schemadir);
        chdir($schemadir);
        while ($file = readdir($handle)) {
            if ($file != '.' && $file != '..' && is_file($file)) {
                $tablename = pathinfo($file, PATHINFO_FILENAME);
                if (is_readable($file)) {
                    self::$_migrations[$tablename][0] = file_get_contents($file);
                }
            }
        }
        closedir($handle);

        $migratedir = DIR . Helper::get('savedir');
        if (is_dir($schemadir) && is_readable($migratedir)) {
            chdir($migratedir);
            $files = glob('Migration*.php');
            foreach ($files as $file) {
                $className = Helper::get('savedir') . '\\' . pathinfo(pathinfo($file, PATHINFO_FILENAME), PATHINFO_FILENAME);
                $class = new $className;
                if ($class instanceof AbstractMigration) {
                    $metadata = $class->getMetadata();
                    foreach ($metadata['tables'] as $tablename => $tmp) {
                        self::$_migrations[$tablename][$metadata['timestamp']] = $metadata['revision'];
                    }
                    foreach ($metadata['refs'] as $refTable => $tables) {
                        if (!isset(self::$_refsMap[$refTable])) {
                            self::$_refsMap[$refTable] = array();
                        }
                        self::$_refsMap[$refTable] = array_merge(self::$_refsMap[$refTable], $tables);
                    }
                }
            }
        }

        foreach (self::$_migrations as &$data) {
            ksort($data);
        }
    }

    /**
     * Возвращает картину миграций
     * @return array 
     */
    public static function getAllMigrations() {
        if (empty(self::$_migrations)) {
            self::prepareMap();
        }
        return self::$_migrations;
    }

    /**
     * Возвращает карту ссылок 
     * @return array 
     */
    public static function getAllRefs() {
        if (empty(self::$_refsMap)) {
            self::prepareMap();
        }
        return self::$_refsMap;
    }

}

?>
