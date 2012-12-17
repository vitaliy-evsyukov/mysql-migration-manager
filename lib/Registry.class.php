<?php

namespace lib;

/**
 * Registry
 * Сохраняет список миграций
 * @author guyfawkes
 */
class Registry
{

    /**
     * @var array
     */
    private static $_migrations = array();
    /**
     * @var array
     */
    private static $_refsMap = array();

    private function __construct()
    {

    }

    private function __clone()
    {

    }

    private function __wakeup()
    {

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
     * self::$_refsMap = array(
     *      'table1' => array(
     *              'table2' => 1,
     *              'table3' => 1
     *      )
     * );
     * </code>
     * Здесь для массива миграций указывается таблица, для нее - таймстампы, для них - ревизии
     * Для массива ссылок указывается таблица, а для нее - связанные таблицы.
     * @param bool $loadSQL Загружать ли содержимое SQL-файлов
     * @param bool $getRefs Искать ли начальные связи
     * @param bool $full
     */
    private static function prepareMap($loadSQL = true, $getRefs = true, $full = false)
    {
        $minRevision = 0;
        if ($loadSQL) {
            /*
             * Вначале соберем все данные из папки схемы
             * SQL считается первой ревизией
             */
            Output::verbose('Starting to search initial revisions', 1);
            $path           = Helper::get('cachedir');
            $fname          = Helper::getSchemaFile();
            $refs_fname     = "{$path}References.class.php";
            $ns             = Helper::get('cachedir_ns');
            $message        = sprintf(
                'Parse schema directory %s or get schema from file %s? [y/n] ',
                Helper::get('schemadir'),
                $fname
            );
            $schemaRewrited = false;
            $schemaMigrated = (strpos($fname, 'migrated') !== false);
            $params         = array($schemaMigrated, '', true);
            if (Helper::askToRewrite($fname, $message, array('\lib\AbstractSchema', 'loadInstance'), $params)) {
                // здесь мы можем перезаписывать только не "мигрированную" схему
                $schemaRewrited = true;
                $queries        = Helper::parseSchemaFiles();
                Helper::writeInFile($fname, '', $queries);
                $queries = $queries['queries'];
            }
            else {
                $classname   = Helper::getSchemaClassName('', $schemaMigrated);
                $schemaObj   = new $classname;
                $queries     = $schemaObj->getQueries();
                $minRevision = $schemaObj->getRevision();
                unset($schemaObj);
            }
            if (!empty($queries)) {
                foreach ($queries as $tablename => &$q) {
                    self::$_migrations[$tablename][0] = $q;
                }
                if ($getRefs) {
                    Output::verbose('Starting to search initial references', 1);
                    // получить начальные связи
                    $message = sprintf(
                        'Refresh initial references or get from cache %s? [y/n] ',
                        $refs_fname
                    );
                    if (Helper::askToRewrite(
                        $refs_fname,
                        $message,
                        function () use ($schemaRewrited) {
                            return $schemaRewrited;
                        }
                    )
                    ) {
                        self::$_refsMap = Helper::getInitialRefs($queries);
                        Helper::createReferencesCache(
                            $refs_fname,
                            self::$_refsMap
                        );
                    }
                    else {
                        $classname      = sprintf('%s\References', $ns);
                        $refsObj        = new $classname;
                        self::$_refsMap = $refsObj->getRefs();
                        unset($refsObj);
                    }
                }
            }
            else {
                Output::verbose(
                    'No initial revisions and references found',
                    1
                );
            }
            unset($queries);
        }
        Output::verbose('Collecting maps of revisions and references', 1);
        if ($full) {
            $minRevision = 0;
        }
        self::parseMigrations(true, $minRevision);

        foreach (self::$_migrations as &$data) {
            ksort($data);
        }
        Output::verbose('Collecting completed', 1);
    }

    /**
     * Возвращает картину миграций
     * @static
     * @param bool $loadSQL
     * @param bool $getRefs
     * @param bool $full
     * @return array
     */
    public static function getAllMigrations($loadSQL = true, $getRefs = true, $full = false)
    {
        if (empty(self::$_migrations)) {
            self::prepareMap($loadSQL, $getRefs, $full);
        }

        return self::$_migrations;
    }

    /**
     * Возвращает карту ссылок
     * @return array
     */
    public static function getAllRefs()
    {
        if (empty(self::$_refsMap)) {
            self::prepareMap();
        }

        return self::$_refsMap;
    }

    /**
     * Сбрасывает кеши связей и миграций
     * @static
     */
    public static function resetAll()
    {
        self::$_refsMap    = array();
        self::$_migrations = array();
    }

    /**
     * Находит файлы миграций и сохраняет необходимые данные из них
     * @static
     * @param bool $check       Проверять, есть ли данный файл в списке миграций
     * @param int  $minRevision Собирать только те ревизии, которые больше переданной
     */
    public static function parseMigrations($check = false, $minRevision = 0)
    {
        $migratedir  = Helper::get('savedir');
        $minRevision = (int)$minRevision;
        if (is_dir($migratedir) && is_readable($migratedir)) {
            chdir($migratedir);
            if ($check) {
                $mHelper = Helper::getAllMigrations();
            }
            $files = glob('Migration*.php');
            foreach ($files as $file) {
                // Наименование имеет вид типа Migration2.class.php
                $className =
                    Helper::get('savedir_ns') . '\\' .
                        pathinfo(
                            pathinfo(
                                $file,
                                PATHINFO_FILENAME
                            ),
                            PATHINFO_FILENAME
                        );
                $class     = new $className;
                if ($class instanceof AbstractMigration) {
                    $metadata = $class->getMetadata();
                    if ($check) {
                        if (!isset($mHelper['timestamps'][$metadata['timestamp']])) {
                            continue;
                        }
                    }
                    if ((int)$metadata['revision'] > $minRevision) {
                        Output::verbose(
                            sprintf('Add migration %s to list', $file),
                            2
                        );
                        foreach ($metadata['tables'] as $tablename => $tmp) {
                            self::$_migrations[$tablename][$metadata['timestamp']] = $metadata['revision'];
                        }
                        foreach ($metadata['refs'] as $refTable => $tables) {
                            if (!isset(self::$_refsMap[$refTable])) {
                                self::$_refsMap[$refTable] = array();
                            }
                            self::$_refsMap[$refTable] = array_merge(
                                self::$_refsMap[$refTable],
                                $tables
                            );
                        }
                    }
                    else {
                        Output::verbose(
                            sprintf(
                                'Migration %s cannot be added to the list, because of its revision is lower or equal to %d',
                                $file,
                                $minRevision
                            ),
                            2
                        );
                    }
                }
            }
        }
    }

}