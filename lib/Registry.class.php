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
    private static function prepareMap($loadSQL = true) {
        if ($loadSQL) {
            // Вначале соберем все данные из папки схемы
            $schemadir = DIR . Helper::get('schemadir');
            if (!is_dir($schemadir) || !is_readable($schemadir)) {
                throw new \Exception("Directory {$schemadir} with tables definitions is not exists\n");
            }

            // SQL считается первой ревизией
            Output::verbose('Starting to search initial revisions', 1);
            $handle = opendir($schemadir);
            chdir($schemadir);
            $queries = array();
            while ($file = readdir($handle)) {
                if ($file != '.' && $file != '..' && is_file($file)) {
                    $tablename = pathinfo($file, PATHINFO_FILENAME);
                    if (is_readable($file)) {
                        $q = file_get_contents($file);
                        $queries[] = $q;
                        self::$_migrations[$tablename][0] = $q;
                    }
                }
            }
            closedir($handle);
            Output::verbose('Starting to search initial references', 1);
            self::$_refsMap = Helper::getInitialRefs(implode("\n", $queries));
            unset($queries);
        }
        Output::verbose('Collecting maps of revisions and references', 1);
        $migratedir = DIR . Helper::get('savedir');
        if (is_dir($migratedir) && is_readable($migratedir)) {
            chdir($migratedir);
            $mHelper = Helper::getAllMigrations();
            $files = glob('Migration*.php');
            foreach ($files as $file) {
                $className = str_replace('/', '\\', Helper::get('savedir')) . '\\' . pathinfo(pathinfo($file,
                                        PATHINFO_FILENAME), PATHINFO_FILENAME);
                $class = new $className;
                if ($class instanceof AbstractMigration) {
                    $metadata = $class->getMetadata();
                    if (!isset($mHelper['timestamps'][$metadata['timestamp']])) {
                        continue;
                    }
                    Output::verbose(
                            sprintf('Add migration %s to list', $file), 2
                    );
                    foreach ($metadata['tables'] as $tablename => $tmp) {
                        self::$_migrations[$tablename][$metadata['timestamp']] = $metadata['revision'];
                    }
                    foreach ($metadata['refs'] as $refTable => $tables) {
                        if (!isset(self::$_refsMap[$refTable])) {
                            self::$_refsMap[$refTable] = array();
                        }
                        self::$_refsMap[$refTable] = array_merge(self::$_refsMap[$refTable],
                                $tables);
                    }
                }
            }
        }

        foreach (self::$_migrations as &$data) {
            ksort($data);
        }
        Output::verbose('Collecting completed', 1);
    }

    /**
     * Возвращает картину миграций
     * @return array 
     */
    public static function getAllMigrations($loadSQL = true) {
        if (empty(self::$_migrations)) {
            self::prepareMap($loadSQL);
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
