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
            /*
             * Вначале соберем все данные из папки схемы
             * SQL считается первой ревизией
             */
            Output::verbose('Starting to search initial revisions', 1);
            $fname = DIR . Helper::get('cachedir') . DIR_SEP . "Schema.class.php";
            $message = sprintf(
                    'Parse schema directory %s again? [y/n] ',
                    Helper::get('schemadir')
            );
            if (!file_exists($fname) || Helper::askToRewrite($fname, $message)) {
                $queries = Helper::parseSchemaFiles();
                // TODO: create schema here
            } else {
                $classname = sprintf(
                        "%s\Schema",
                        str_replace('/', '\\', Helper::get('cachedir'))
                );
                $schemaObj = new $classname;
                $queries = $schemaObj->getQueries();
                unset($schemaObj);
            }
            if (!empty($queries)) {
                foreach ($queries as $tablename => &$q) {
                    $q = stripslashes($q);
                    self::$_migrations[$tablename][0] = $q;
                }
                Output::verbose('Starting to search initial references', 1);
                self::$_refsMap = Helper::getInitialRefs(implode("\n", $queries));
            } else {
                Output::verbose('No initial revisions and references found', 1);
            }
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
                                                                                                       PATHINFO_FILENAME),
                                                                                                       PATHINFO_FILENAME);
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
