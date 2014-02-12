<?php

namespace lib;

use \Mysqli;

/**
 * Helper
 * Статический класс, предоставляющий функции, использующиеся в различных местах системы, и доступ к конфигурационным параметрам
 */
class Helper
{

    /**
     * @var array
     */
    protected static $_datasets = array();
    /**
     * @var array
     */
    static protected $config_tpl = array(
        'config'          => array('short' => 'c', 'req_val'),
        'host'            => array('req_val'),
        'user'            => array('req_val'),
        'password'        => array('req_val'),
        'db'              => array('req_val'),
        'port'            => array('req_val'),
        'savedir'         => array('req_val'),
        'verbose'         => array('req_val'),
        'versionfile'     => array('req_val'),
        'stop-on-failure' => array('req_val'),
        'quiet'           => array('short' => 'q', 'no_val'),
        'version_marker'  => array('req_val'),
        'tmp_host'        => array('req_val'),
        'tmp_user'        => array('req_val'),
        'tmp_password'    => array('req_val'),
        'tmp_port'        => array('req_val'),
        'cachedir'        => array('req_val'),
        'schemadir'       => array('req_val'),
        'reqtables'       => array('req_val'),
        'tmp_db_name'     => array('req_val'),
        'tmp_add_suffix'  => array('req_val'),
        'routine_user'    => array('req_val')
    );
    /**
     * @var array
     */
    static protected $config = array(
        'config'          => null, //path to alternate config file
        'host'            => null,
        'user'            => null,
        'password'        => null,
        'db'              => null,
        'port'            => null,
        'savedir'         => null,
        'cachedir'        => null,
        'reqtables'       => null,
        'schemadir'       => null,
        'verbose'         => null,
        'stop-on-failure' => true,
        'versionfile'     => null,
        'version_marker'  => null,
        'tmp_db_name'     => null,
        'tmp_add_suffix'  => null,
        'tmp_port'        => null,
        'routine_user'    => null
    );
    /**
     * @var array
     */
    private static $_revisionLines = array();
    /**
     * Массив переменных замены. В каждом запросе происходит подмена указанной БД на взятую из конфига
     * @var array
     */
    private static $_replaceVariables = null;
    /**
     * @var int
     */
    private static $_lastRevision = 0;
    /**
     * @var int
     */
    private static $_currRevision = -1;
    /**
     * Текущая разворачиваемая временная БД
     * @var \lib\MysqliHelper
     */
    private static $_currentTempDb = null;
    /**
     * Текущая БД
     * @var \lib\MysqliHelper
     */
    private static $currentDb = null;
    /**
     * Приведены ли стандартные директории к виду полных путей
     * @var bool
     */
    private static $_prepareStandardDirectories = false;
    /**
     * Массив с действиями, которые уже запросили ввода пользователя
     * Необходим для того, чтобы в цепочках не повторять вопросы
     * @var array
     */
    private static $_executedRequests = array();

    /**
     * @static
     * @param $cnf
     */
    static function setConfig($cnf)
    {
        self::$config = array_replace(self::$config, $cnf);
    }

    /**
     * @static
     * @return array
     */
    static function getConfig()
    {
        return self::$config;
    }

    /**
     * Установить временную БД
     * @static
     * @param \lib\MysqliHelper $db
     */
    public static function setCurrentTempDb(MysqliHelper $db)
    {
        self::$_currentTempDb = $db;
    }

    /**
     * Parse command line into config options and commands with its parameters
     * @param array $args List of arguments provided from command line
     * @return array
     */
    static function parseCommandLineArgs(array $args)
    {
        $parsed_args = array(
            'options' => array(),
            'command' => array(
                'name' => null,
                'args' => array()
            )
        );

        array_shift($args);
        $opts = GetOpt::extractLeft($args, self::$config_tpl);
        if ($opts === false) {
            $errors = (array) GetOpt::errors();
            Output::error('mmm: ' . reset($errors));
            exit(1);
        } else {
            $parsed_args['options'] = $opts;
        }

        //if we didn't traverse the full array just now, move on to command parsing
        if (!empty($args)) {
            $parsed_args['command']['name'] = array_shift($args);
        }

        //consider any remaining arguments as command arguments
        $parsed_args['command']['args'] = $args;

        return $parsed_args;
    }

    /**
     * Возвращает массив с данными по датасетам
     * Массив состоит из ключей:
     * <pre>
     * reqs -------
     *            |- dataset1 -> JSON1
     *            |- dataset2 -> JSON2
     * sqlContent -
     *            |- dataset1 -> SQL1
     *            |- dataset2 -> SQL2
     * </pre>
     * @static
     * @param array $datasets           Массив имен датасетов
     * @param bool  $loadDatasetContent Загружать ли содержимое SQL датасетов
     * @return array
     * @throws \Exception
     */
    public static function getDatasetInfo(array $datasets, $loadDatasetContent = false)
    {
        if (empty(self::$_datasets)) {
            $dsdir = DIR . Helper::get('datasetsdir');
            // получить данные
            if (!is_dir($dsdir) || !is_readable($dsdir)) {
                throw new \Exception(
                    sprintf(
                        "Directory %s with datasets is not exists",
                        $dsdir
                    )
                );
            }

            $handle = opendir($dsdir);
            chdir($dsdir);

            while ($dir = readdir($handle)) {
                // если в хеше датасетов такой есть, то начнем читать папку с ним
                if (isset($datasets[$dir]) && is_dir($dir) && is_readable($dir)) {
                    $tablesFileName = $dir . DIR_SEP . Helper::get('reqtables');
                    if (is_file($tablesFileName) && is_readable($tablesFileName)) {
                        self::$_datasets['reqs'][$dir] = json_decode(
                            file_get_contents($tablesFileName),
                            true
                        );
                        $datafile                      = $dir . DIR_SEP . self::get('reqdata');
                        if ($loadDatasetContent && is_file($datafile) && is_readable($datafile)) {
                            self::$_datasets['sqlContent'][$dir] = file_get_contents($datafile);
                        }
                    }
                }
            }

            closedir($handle);
            if (empty(self::$_datasets) || ($loadDatasetContent && empty(self::$_datasets['sqlContent']))) {
                throw new \Exception('Data for deploy not found');
            }
        }

        return self::$_datasets;
    }

    /**
     * Получить объект контроллера или цепочку ответственности
     * Без переданных параметров возвращает "help" контроллер
     * @param string                 $name Имя контроллера
     * @param array                  $args Массив аргументов
     * @param \lib\MysqliHelper|null $db   Объект подключения к БД
     * @throws \Exception
     * @return AbstractController|ControllersChain
     */
    public static function getController(
        $name = null,
        $args = array(),
        MysqliHelper $db = null
    )
    {
        if (empty($name)) {
            return new helpController;
        }

        $ctrlName = 'lib\\' . $name . 'Controller'; // http://php.net/manual/en/language.namespaces.dynamic.php
        try {
            if (!$db) {
                $db = self::getDbObject();
            }
            $ctrl = new $ctrlName($db, $args);
            if ($ctrl instanceof DatasetsController) {
                // обернем его в цепочку
                $chain = new ControllersChain();
                $ctrl->setChain($chain);
                $chain->setController($ctrl);

                return $chain;
            }

            return $ctrl;
        } catch (\Exception $e) {
            $message = $e->getMessage();
            if ($e->getCode() === NO_COMMAND) {
                $message = sprintf(
                    "%s\nCommand %s not recognized",
                    $message,
                    $name
                );
            }
            throw new \Exception($message, $e->getCode());
        }
    }

    /**
     * Возвращает связанные со списком таблицы
     * @param array $refs       Хеш, где ключ - имя таблицы, значение - хеш вида имя_связанной_таблицы => 1
     * @param array $tablesList Хеш вида имя_таблицы => 1
     * @return array Связанные таблицы, не входящие в список
     */
    public static function getRefs(
        array $refs = array(),
        array $tablesList = array()
    )
    {
        $res        = array();
        $existsRefs = array_intersect_key($refs, $tablesList);

        $closure = function ($arr) use (&$closure, &$res, $refs) {
            foreach ($arr as $table => $value) {
                if (!isset($res[$table])) {
                    $res[$table] = 1;
                    if (isset($refs[$table]) && is_array($refs[$table])) {
                        $closure($refs[$table]);
                    }
                }
            }
        };

        $closure($existsRefs);

        return $res;
    }

    /**
     * Выбирает базу данных и создает ее, если не было
     * @param \lib\MysqliHelper $connection
     * @param string            $dbName
     * @throws \Exception
     */
    public static function prepareDb(MysqliHelper $connection, $dbName)
    {
        $res  = $connection->query('SHOW DATABASES;');
        $flag = false;
        while ($row = $res->fetch_array(MYSQLI_NUM)) {
            if ($row[0] === $dbName) {
                Output::verbose(
                    sprintf(
                        'Found database %s in current databases',
                        $dbName
                    ),
                    2
                );
                $flag = true;
                break;
            }
        }
        if (!$flag) {
            Output::verbose(sprintf('Create database %s', $dbName), 2);
            $query = "CREATE DATABASE `{$dbName}` DEFAULT CHARACTER SET cp1251 COLLATE cp1251_general_ci;";
            if (!$connection->query($query)) {
                throw new \Exception(
                    sprintf(
                        'Cannot create database %s. Reason: %s',
                        $dbName,
                        $connection->getLastError()
                    )
                );
            }
        }
        $connection->select_db($dbName);
    }

    /**
     * Возвращает объект соединения
     * @param array $config
     * @return \lib\MysqliHelper
     */
    public static function getDbObject($config = array())
    {
        static $db = null;
        $conf = self::$config;
        if (count($config)) {
            foreach ($config as $option => $value) {
                $conf[$option] = $value;
            }
        } else {
            if ($db) {
                return $db;
            }
            $db = new MysqliHelper($conf['host'], $conf['user'], $conf['password'], '', $conf['port']);
            self::prepareDb($db, $conf['db']);

            return $db;
        }
        $t = new MysqliHelper($conf['host'], $conf['user'], $conf['password'], '', $conf['port']);
        self::prepareDb($t, $conf['db']);

        return $t;
    }

    /**
     * Создает, если не было, директорию для миграций
     * @param array|string $dirs Список директорий. Если не указан, создаются и проверяются стандартные
     * @return void
     */
    public static function initDirs($dirs = array())
    {
        if (!is_array($dirs)) {
            $dirs = array($dirs);
        }
        if (empty($dirs)) {
            // папки по умолчанию
            if (!self::$_prepareStandardDirectories) {
                $dirs       = array('savedir', 'cachedir', 'schemadir');
                $namespaces = array(
                    'savedir'  => SAVEDIR_NS,
                    'cachedir' => CACHEDIR_NS
                );
                foreach ($dirs as &$dir) {
                    if (isset($namespaces[$dir])) {
                        self::set("{$dir}_ns", $namespaces[$dir]);
                    }
                    self::set($dir, DIR . self::get($dir) . DIR_SEP);
                }
                self::$_prepareStandardDirectories = true;
            }
        }
        foreach ($dirs as $dir) {
            if (isset(self::$config[$dir])) {
                $dirname = self::$config[$dir];
            } else {
                $dirname = $dir;
                $dir     = basename(rtrim($dir, '/'));
            }
            if (!is_dir($dirname)) {
                mkdir($dirname, 0775, true);
                Output::verbose(
                    sprintf('Created %s directory in path: %s', $dir, $dirname),
                    3
                );
            }
        }
    }

    /**
     * Возвращает значение параметра из конфигурации
     * @static
     * @param string $key Название параметра
     * @return mixed|bool Значение или false в случае неудачи
     */
    public static function get($key)
    {
        return isset(self::$config[$key]) ? self::$config[$key] : false;
    }

    /**
     * Устанавливает значение параметра
     * @static
     * @param string $key   Название параметра
     * @param mixed  $value Значение
     */
    public static function set($key, $value)
    {
        self::$config[$key] = $value;
    }

    /**
     * Создает объект соединения для временной БД
     * @param string $tmpname Имя временной базы данных
     * @throws \Exception
     * @return \lib\MysqliHelper
     */
    public static function getTmpDbObject($tmpname = '')
    {
        $config = self::getConfig();
        if (empty($tmpname)) {
            $tmpname = $config['tmp_db_name'];
        }
        $tmpname .= self::getCurrentVersion();
        $c      = array();
        $params = array('host', 'password', 'user', 'port');
        foreach ($params as $p) {
            $c[$p] = $config['tmp_' . $p];
        }
        $c['db'] = $tmpname;
        unset($config);
        $tmpdb = self::getDbObject($c);
        $tmpdb->setIsTemporary(true);
        if (!$tmpdb->set_charset("utf8")) {
            throw new \Exception(
                sprintf(
                    "SET CHARACTER SET utf8 error: %s\n",
                    $tmpdb->error
                )
            );
        }
        register_shutdown_function(
            function () use ($c, $tmpdb) {
                $tmpdb->query("DROP DATABASE `{$c['db']}`");
                Output::verbose(
                    "Temporary database {$c['db']} was deleted",
                    2
                );
            }
        );

        return $tmpdb;
    }

    /**
     * @static
     * @return int
     */
    static function getCurrentVersion()
    {
        return (int) self::get('tmp_add_suffix') ? '_' . time() : '';
    }

    /**
     * @static
     * @param $tname
     * @param $db
     * @return mixed|string
     */
    static function getSqlForTableCreation($tname, $db)
    {
        $tres  = $db->query("SHOW CREATE TABLE `{$tname}`");
        $trow  = $tres->fetch_array(MYSQLI_NUM);
        $query = preg_replace('#AUTO_INCREMENT=\S+#is', '', $trow[1]);
        $query = preg_replace("#\n\s*#", ' ', $query);
        $query = addcslashes($query, '\\\''); //escape slashes and single quotes
        return $query;
    }

    /**
     * @static
     * @param \Mysqli $db
     * @return bool|int
     */
    static function getDatabaseVersion(Mysqli $db)
    {
        $tbl = self::get('versiontable');
        $res = $db->query("SELECT max(rev) FROM `{$tbl}`");
        if ($res === false) {
            return false;
        }
        $row = $res->fetch_array(MYSQLI_NUM);

        return intval($row[0]);
    }

    /**
     * Get all revisions that have been applied to the database
     * @param MysqliHelper $db Database instance
     * @return array|bool List of applied revisions, False on error
     */
    static function getDatabaseVersions(MysqliHelper $db)
    {
        $result = array();
        $tbl    = self::get('versiontable');
        $res    = $db->query("SELECT rev FROM `{$tbl}` ORDER BY rev ASC");
        if ($res === false) {
            return false;
        }

        while ($row = $res->fetch_array(MYSQLI_NUM)) {
            $result[] = $row[0];
        }

        return $result;
    }

    /**
     * Выполняет запросы с отладкой.
     * @param \lib\MysqliHelper $db
     * @param array             $queries
     */
    public static function _debug_queryMultipleDDL(
        MysqliHelper $db,
        array $queries
    )
    {
        foreach ($queries as $table => $stmts) {
            Output::verbose(sprintf('Executing queries for %s', $table), 2);
            /*
             * Для одной сущности может быть как одна, так и несколько
             * групп запросов, содержащих строки с SQL-операторами
             */
            if (!is_array($stmts)) {
                $stmts = array($stmts);
            }
            $summa_time = 0;
            foreach ($stmts as $queries) {
                /*
                 * Из строки множества операторов необходимо вычленить
                 * эти операторы, при этом возможны пустые строки
                 */
                $ds = mb_stripos($queries, 'DELIMITER ;;');
                $l  = mb_strlen('DELIMITER ;;');
                if ($ds !== false) {
                    $offset = $ds + $l;
                    $df     = mb_stripos($queries, 'DELIMITER ;', $offset);
                    if ($df !== false) {
                        $tmp     = array(
                            mb_substr($queries, $offset, $df - $offset)
                        );
                        $before  = mb_substr($queries, 0, $offset - $l);
                        $after   = mb_substr($queries, $df + $l - 1);
                        $queries = array_merge(
                            explode(";\n", $before),
                            $tmp,
                            explode(";\n", $after)
                        );
                    }
                }
                if (!is_array($queries)) {
                    $queries = explode(";\n", $queries);
                }
                // каждый запрос может состоять из нескольких запросов
                foreach ($queries as $query) {
                    $query = trim($query);
                    if (empty($query)) {
                        continue;
                    }
                    $start = microtime(1);
                    if (!$db->query($query)) {
                        Output::error(
                            sprintf(
                                '   %s: %s (%d)',
                                $query,
                                $db->error,
                                $db->errno
                            )
                        );
                    }
                    $summa_time += (microtime(1) - $start);
                }
            }
            Output::verbose(
                sprintf(
                    'Summary queries executing time for %s is %f secs.',
                    $table,
                    $summa_time
                ),
                3
            );
        }
    }

    /**
     * Выполняет множественные запросы DDL
     * @param \lib\MysqliHelper $db
     * @param string            $queries
     */
    public static function queryMultipleDDL(MysqliHelper $db, $queries)
    {
        $queries = str_ireplace(
            "DELIMITER ;\n",
            '',
            str_ireplace(
                "DELIMITER ;;\n",
                '',
                $queries
            )
        );
        $queries = str_replace(';;', ';', $queries);
        $start   = microtime(1);
        $ret     = $db->multi_query($queries);
        Output::verbose(
            sprintf(
                'Started multiple DDL execution: multi_query time: %f',
                (microtime(1) - $start)
            ),
            2
        );
        $text = $db->error;
        $code = $db->errno;
        if (!$ret) {
            Output::error(
                sprintf('%s (%d)', $text, $code)
            );
        }
        do {
            $result = $db->use_result();
            if ($result) {
                $result->free();
            }
        } while ($db->more_results() && $db->next_result());
        Output::verbose(
            sprintf(
                'Multiple DDL execution finished: result set looping time: %f',
                (microtime(1) - $start)
            ),
            2
        );
        $text = $db->error;
        $code = $db->errno;
        if ($code) {
            Output::error(
                sprintf('%s (%d)', $text, $code)
            );
        }
    }

    /**
     * Получить список начальных связей таблиц в БД
     * @param array $data Массив запросов
     * @throws \Exception
     * @return array
     */
    public static function getInitialRefs($data)
    {
        if (!self::$_currentTempDb) {
            $db = self::getTmpDbObject();
            $db->setCommand("SET foreign_key_checks = 0;");
            if ((int) self::get('verbose') === 3) {
                self::_debug_queryMultipleDDL($db, $data);
            } else {
                self::queryMultipleDDL($db, implode("\n", $data));
            }
            $db->query("SET foreign_key_checks = 1;");
        } else {
            $db = self::$_currentTempDb;
        }

        $params     = array('host', 'user', 'password', 'port');
        $params_str = array();
        foreach ($params as $param) {
            $value = Helper::get('tmp_' . $param);
            if (!empty($value)) {
                $params_str[] = "--{$param}={$value}";
            }
        }
        $start   = microtime(1);
        $command = sprintf(
            "%s %s  --no-old-defs --refs %s",
            self::get('mysqldiff_command'),
            implode(' ', $params_str),
            $db->getDatabaseName()
        );
        Output::verbose(sprintf('Executing command %s', $command), 3);
        $output = array();
        $status = -1;
        exec($command, $output, $status);
        if ($status) {
            throw new \Exception("Cannot get references:\n" . implode("\n", $output));
        }
        Output::verbose(
            sprintf(
                'References search completed in: %f seconds',
                (microtime(1) - $start)
            ),
            3
        );
        $result = array();
        if (!empty($output)) {
            $diffObj = new dbDiff();
            $diffObj->parseDiff($output);
            $result = $diffObj->getTablesInfo();
            $result = $result['refs'];
        }

        return $result;
    }

    /**
     * Возвращает информацию о изменениях таблиц в базе с течением времени
     * @param array $tablesList Список необходимых таблиц
     * @param bool  $getRefs    Нужно ли получать начальные связи
     * @param bool  $full       Нужно ли получать все миграции
     * @return array
     */
    public static function getTimeline(array $tablesList = array(), $getRefs = true, $full = false)
    {
        $currentReplacement = self::get('routine_user');
        self::set('routine_user', '');
        $migrations  = Registry::getAllMigrations(true, $getRefs, $full);
        $tablesToAdd = array();
        if (!empty($tablesList)) {
            // получить все связи таблиц
            $refs = Registry::getAllRefs();
            // получить те, которые связаны
            $tablesToAdd = self::getRefs($refs, $tablesList);
            // получить те, которых не хватает. Это не нужно для мержа, но нужно в условии ниже
            $tablesToAdd = array_diff_key($tablesToAdd, $tablesList);
            // объединить те, которые были пераданы, с теми, которые с ними связаны
            $tablesList = array_merge($tablesList, $tablesToAdd);
        } else {
            $tablesList = $migrations;
        }
        $timeline = array();
        foreach ($tablesList as $tableName => $t) {
            if (!isset($migrations[$tableName][0])) {
                Output::verbose('Try to get SQL for table ' . $tableName, 3);
                $currentVerbose = self::get('verbose');
                self::set('verbose', 0);
                $parsedData = self::parseSchemaFiles(array($tableName => 1));
                if (!empty($parsedData['queries'][$tableName])) {
                    $isReceived                = true;
                    $migrations[$tableName][0] = $parsedData['queries'][$tableName];
                } else {
                    $isReceived = false;
                }
                self::set('verbose', $currentVerbose);
                Output::verbose(
                    sprintf('SQL for table %s was%sreceived', $tableName, $isReceived ? ' ' : ' NOT '),
                    3
                );
                /**
                 * Если была получена хоты бы начальная ревизия (SQL), то массив миграций для таблицы уже не пуст
                 * Если же начальной ревизии нет, но нет и остальных ревизий, то пропустим такую таблицу
                 * Получить начальную ревизию необходимо для того, чтобы создать таблицы, которые не созданы в
                 * последующих миграциях, а созданы в рамках начального наполнения БД, в противном случае мы получаем
                 * ошибку выполнения операций над таблицей, которая не создана
                 * С другой стороны, нас удовлетворит результат, когда начальной ревизии нет, но есть другие ревизии -
                 * это означает, что таблица была создана в первой из них
                 * Однако таблицы, которые были указаны в списке таблиц ошибочно, не могут никак быть обработаны
                 * корректно по причине простого отсутствия данных по ним, и должны быть пропущены
                 */
                if (!$isReceived && !isset($migrations[$tableName])) {
                    Output::verbose("There aren't migrations for table {$tableName}", 3);
                    continue;
                }
            }
            foreach ($migrations[$tableName] as $timestamp => $revision) {
                /**
                 * Если были таблицы, которых не хватало, добавим их в таймлайн с меткой времени, равной 1
                 * Это необходимо для того, чтобы пропустить нулевую ревизию (SQL) и выполнить ревизию SQL для них
                 */
                if (!empty($tablesToAdd) && ($timestamp === 0) && isset($tablesToAdd[$tableName])) {
                    $timestamp = 1;
                }
                $timeline[$timestamp][$tableName] = $revision;
            }
        }
        ksort($timeline);

        if ($getRefs) {
            Output::verbose(sprintf("Summary of tables:\n--- %s", implode("\n--- ", array_keys($tablesList))), 3);
            Output::verbose(
                sprintf(
                    "Tables which are referenced:\n--- %s",
                    implode(
                        "\n--- ",
                        empty($tablesToAdd) ? array_keys(Registry::getAllRefs()) : array_keys($tablesToAdd)
                    )
                ),
                3
            );
        }
        self::set('routine_user', $currentReplacement);

        return $timeline;
    }

    /**
     * Выполняет миграцию
     * @static
     * @param                   $revision   Номер ревизии
     * @param \lib\MysqliHelper $db         Объект соединения
     * @param string            $direction  Направление (Up или Down)
     * @param array             $tablesList Список таблиц, операторы которых необходимо выполнить. Если пуст, выполняются все.
     */
    public static function applyMigration(
        $revision,
        MysqliHelper $db,
        $direction = 'Up',
        array $tablesList = array()
    )
    {
        $classname = self::get('savedir_ns') . '\Migration' . $revision;
        $migration = new $classname($db);
        $migration->setTables($tablesList);
        $method = 'run' . $direction;
        $migration->$method();
    }

    /**
     * Возвращает список всех миграций и связанных с ними данных
     * @throws \Exception
     * @return array
     */
    public static function getAllMigrations()
    {
        self::$_revisionLines = array();
        self::$_currRevision  = -1;
        $migrationsDir        = self::get('savedir') . '/';
        $migrationsListFile   = $migrationsDir . self::get('versionfile');
        $markerFile           = $migrationsDir . self::get('version_marker');
        $result               = array(
            'migrations' => array(),
            'data'       => array()
        );
        if (is_file($markerFile) && is_readable($markerFile)) {
            $handler = fopen($markerFile, 'r');
            if ($handler) {
                while (!feof($handler)) {
                    $line = trim(fgets($handler));
                    if (empty($line)) {
                        continue;
                    }
                    if ($line[0] === '#') {
                        self::$_currRevision = (int) substr($line, 1);
                        break;
                    }
                }
                fclose($handler);
            }
        }
        if (is_file($migrationsListFile) && is_readable($migrationsListFile)) {
            $handler = fopen($migrationsListFile, 'r');
            if ($handler) {
                while (!feof($handler)) {
                    $line = trim(fgets($handler));
                    if (empty($line)) {
                        continue;
                    }
                    self::$_revisionLines[] = $line;
                    $parts                  = explode('|', $line);
                    // TODO: упростить структуру данных
                    $migrationId                  = (int) $parts[0];
                    $time                         = (int) $parts[2];
                    $result['migrations'][]       = $migrationId;
                    $result['data'][$migrationId] = array(
                        'date' => $parts[1],
                        'time' => $time,
                        'revn' => $migrationId
                    );
                    $result['timestamps'][$time]  = $migrationId;
                    self::$_lastRevision          = $migrationId;
                }
                fclose($handler);
            } else {
                throw new \Exception(sprintf("Failed to open file %s", $migrationsListFile));
            }
        }
        if (self::$_currRevision === -1) {
            self::$_currRevision = self::$_lastRevision;
        }
        usort(
            $result['migrations'],
            function ($a, $b) use ($result) {
                return ($result['data'][$a]['time'] >
                    $result['data'][$b]['time']) ? 1 : -1;
            }
        );

        return $result;
    }

    /**
     * Получить номер ожидаемой ревизии
     * @return int
     */
    public static function getLastRevision()
    {
        if (!self::$_lastRevision) {
            self::getAllMigrations();
        }

        return ++self::$_lastRevision;
    }

    /**
     * Получает номер текущей ревизии
     * @static
     * @return int
     */
    public static function getCurrentRevision()
    {
        if (self::$_currRevision === -1) {
            self::getAllMigrations();
        }

        return self::$_currRevision;
    }

    /**
     * Получает список строк ревизий
     * @static
     * @return array
     */
    public static function getRevisionLines()
    {
        if (empty(self::$_revisionLines)) {
            self::getAllMigrations();
        }

        return self::$_revisionLines;
    }

    /**
     * Записывает информацию о ревизии
     * @param int $revision Номер ревизии
     * @throws \Exception
     * @return int Таймстаймп для ревизии
     */
    public static function writeRevisionFile($revision)
    {
        $path     = self::get('savedir');
        $filename = $path . self::get('versionfile');
        $marker   = $path . self::get('version_marker');
        $ts       = time();
        $lines    = self::getRevisionLines();
        $b        = ($revision === 0);
        foreach ($lines as $line) {
            $data = explode('|', $line);
            if ((int) $data[0] === $revision) {
                $b = true;
                break;
            }
        }
        if (!$b) {
            $lines[] = sprintf(
                "%d|%s|%d",
                $revision,
                date('d.m.Y H:i:s', $ts),
                $ts
            );
            if (is_file($filename) && !is_writable($filename)) {
                throw new \Exception(
                    sprintf(
                        "File %s is write-protected",
                        $filename
                    )
                );
            }
            file_put_contents($filename, implode("\n", $lines));
        }
        self::$_revisionLines = $lines;
        if (is_file($marker) && !is_writable($marker)) {
            throw new \Exception(
                sprintf(
                    'Cannot write revision marker to file: %s',
                    $marker
                )
            );
        }
        file_put_contents($marker, "#{$revision}");

        return $ts;
    }

    /**
     * @static
     * @return array
     */
    static function _getAllMigrations()
    {
        $dir    = self::get('savedir');
        $files  = glob($dir . '/Migration*.php');
        $result = array();
        foreach ($files as $file) {
            $key      = preg_replace('#[^0-9]#is', '', $file);
            $result[] = $key;
        }
        sort($result, SORT_NUMERIC);

        return $result;
    }

    /**
     * Загружает начальную схему в базу и накатывает все миграции
     * @param \lib\MysqliHelper $db Соединение с сервером БД
     */
    public static function loadTmpDb(MysqliHelper $db)
    {
        Output::verbose(
            sprintf("Deploy temporary database %s", $db->getDatabaseName()),
            1
        );
        $db->setCommand("SET foreign_key_checks = 0;");
        /**
         * Так как при разворачивании временной БД выполняются все операторы,
         * то строить связи нет необходимости, равно как и передавать список
         * необходимых таблиц
         */
        self::setCurrentDb($db, 'loadTmpDb');
        $timeline       = self::getTimeline(array(), false);
        $usedMigrations = array();
        foreach ($timeline as $tables) {
            foreach ($tables as $tablename => $revision) {
                if (is_int($revision)) {
                    $what = sprintf(
                        "migration %d for table %s",
                        $revision,
                        $tablename
                    );
                    // обратимся к нужному классу
                    if (!isset($usedMigrations[$revision])) {
                        self::applyMigration($revision, $db);
                        $usedMigrations[$revision] = 1;
                    }
                } else {
                    $what = sprintf("sql for %s", $tablename);
                    // это SQL-запрос
                    self::_debug_queryMultipleDDL(
                        $db,
                        array($tablename => $revision)
                    );
                }
                Output::verbose(sprintf('--- Execution of %s finished', $what), 3);
            }
        }
        $db->query("SET foreign_key_checks = 1;");
        Output::verbose("Deploy temporary database was finished", 1);
        /**
         * Отсортируем массив использованных миграций по ключам по возрастанию, переставим внутренний указатель
         * массива на его последний элемент и получим ключ этого элемента
         */
        ksort($usedMigrations);
        end($usedMigrations);
        $revision = key($usedMigrations);

        if (!is_null($revision)) {
            $migrationController = self::getController('migrate', array(), $db)->getController();
            $migrationController->createMigratedSchema($revision);
        }
    }

    /**
     * Рекурсивно превращает массив любой вложенности в строку
     * @static
     * @param array  $a      Массив
     * @param int    $level  Уровень начального отступа
     * @param bool   $nowdoc Необходимо ли использовать Nowdoc или делать простые строки
     * @param string $spacer Строка, которой отбивается отступ
     * @return string
     */
    public static function recursiveImplode(
        array $a,
        $level = 1,
        $nowdoc = true,
        $spacer = ' '
    )
    {
        $resultStr = 'array()';
        if (!empty($a)) {
            $result   = array();
            $depth    = str_repeat($spacer, $level * 4);
            $depth2   = str_repeat($spacer, ($level - 1) * 4);
            $last_key = key(array_slice($a, -1, 1, true));
            foreach ($a as $k => $v) {
                $tmp = $depth;
                if (!is_int($k)) {
                    // выведем строковые ключи
                    $tmp = sprintf("%s'%s' => ", $depth, $k);
                }
                if (is_array($v)) {
                    // если значение - массив, рекурсивно обработаем его
                    $tmp .= self::recursiveImplode(
                        $v,
                        ($level + 1),
                        $nowdoc,
                        $spacer
                    );
                } else {
                    if (is_string($v)) {
                        /**
                         * Если необходимо использовать Nowdoc, применим
                         * ее только для SQL-операторов
                         * array - возможно, в будущем будет вайтлист ключей
                         */
                        if ($nowdoc && !in_array($k, array('type'))) {
                            $tmp .= "<<<'EOT'\n";
                            $tmp .= $v;
                            $tmp .= "\nEOT";
                            if ($k !== $last_key) {
                                $tmp .= "\n";
                            }
                        } else {
                            $tmp .= "'{$v}'";
                        }
                    } else {
                        $tmp .= $v;
                    }
                }
                $result[] = $tmp;
            }
            $sep       = ",\n";
            $resultStr = sprintf("array(\n%s\n%s)", implode($sep, $result), $depth2);
        }

        return $resultStr;
    }

    /**
     * DEPRECATED
     * @param array $a
     * @return string
     */
    private static function sqlImplode(array $a)
    {
        $result = array();
        foreach ($a as $direction => $data) {
            foreach ($data as $table => $queries) {
                $result['tmp'][] = "'$table' => array(\n\"" . implode(
                        "\",\n\"",
                        $queries
                    ) . "\"\n)";
            }
            $result[$direction] =
                "array(\n" . implode(",\n", $result['tmp']) . "\n)";
            unset($result['tmp']);
        }

        return $result;
    }

    /**
     * Утилитарная работа с получением содержимого шаблона и заменой
     * @static
     * @param array  $search  Массив местозаменителей
     * @param array  $replace Массив соответствующих им замен
     * @param string $tpl     Имя файла шаблона
     * @return string
     * @throws \Exception
     */
    private static function createContent(array $search, array $replace, $tpl)
    {
        $tpl_file = DIR . $tpl;
        if (is_file($tpl_file) && is_readable($tpl_file)) {
            $content = file_get_contents($tpl_file);
        } else {
            throw new \Exception(
                sprintf(
                    'Template file %s not exists or is not readable',
                    $tpl_file
                )
            );
        }
        foreach ($search as &$placeholder) {
            $placeholder = "%%{$placeholder}%%";
        }

        return str_replace($search, $replace, $content);
    }

    /**
     * Создает класс миграции
     * @param int    $version Ревизия
     * @param array  $diff    Массив различий
     * @param int    $ts      timestamp
     * @param string $tpl     Шаблон класса
     * @return string Контент файла класса
     */
    public static function createMigrationContent(
        $version,
        array $diff,
        $ts,
        $tpl = 'tpl/migration.tpl'
    )
    {
        $version = (int) $version;
        $search  = array('revision', 'up', 'down', 'meta', 'ns');

        $metadata = array(
            'timestamp' => $ts,
            'tables'    => $diff['tables']['used'],
            'refs'      => $diff['tables']['refs'],
            'revision'  => $version
        );
        unset($diff['tables']);

        $replace = array(
            $version,
            self::recursiveImplode($diff['up'], 2),
            self::recursiveImplode($diff['down'], 2),
            self::recursiveImplode($metadata, 2),
            self::get('savedir_ns')
        );

        return self::createContent($search, $replace, $tpl);
    }

    /**
     * Заменяет ревизию в файле схемы
     * @param string $filename
     * @param int    $revision
     */
    public static function changeRevision($filename, $revision)
    {
        if (is_file($filename) && is_readable($filename) && is_writable($filename)) {
            Output::verbose(sprintf('Change revision in schema %s to %d', $filename, $revision), 2);
            $content = file_get_contents($filename);
            $content = preg_replace('/(\$_revision\s=\s)(\d+)?;/', '${1}' . $revision . ';', $content);
            file_put_contents($filename, $content);
        }
    }

    /**
     * Сохраняет файл схемы
     * TODO: объединить с записью миграциий
     * @param string $fname    Имя файла
     * @param string $name     Имя схемы данных
     * @param array  $queries  Массив запросов
     * @param string $tpl      Путь к файлу шаблона
     * @param int    $revision Номер ревизии
     */
    public static function writeInFile(
        $fname,
        $name,
        array $queries,
        $tpl = 'tpl/schema.tpl',
        $revision = 0
    )
    {
        $hashes    = $queries['md5'];
        $hashesTmp = array();
        $separator = ",\n" . str_repeat(' ', 8);
        foreach ($hashes as $entityName => $hash) {
            $hashesTmp[] = sprintf('"%s" => "%s"', $entityName, $hash);
        }
        $queries = $queries['queries'];
        $search  = array('queries', 'tables', 'name', 'ns', 'revision');
        $replace = array(
            self::recursiveImplode($queries, 1, true),
            implode($separator, $hashesTmp),
            $revision ? $name . 'migrated' : $name,
            self::get('cachedir_ns'),
            $revision
        );
        if (!file_exists($fname) || is_writable($fname)) {
            Output::verbose(sprintf('Write file %s', $fname), 3);
            file_put_contents(
                $fname,
                self::createContent($search, $replace, $tpl)
            );
        }
    }

    /**
     * Создать файл кеша связей таблиц
     * @static
     * @param string $filename   Имя файла
     * @param array  $references Массив связей
     * @param string $hash       Хеш датасета, если используется
     * @param string $tpl        Файл шаблона
     */
    public static function createReferencesCache($filename, array $references, $hash, $tpl = 'tpl/references.tpl')
    {
        $search  = array('ns', 'refs', 'hash');
        $replace = array(
            self::get('cachedir_ns'),
            self::recursiveImplode($references, 2),
            $hash
        );
        if (!file_exists($filename) || is_writable($filename)) {
            file_put_contents(
                $filename,
                self::createContent($search, $replace, $tpl)
            );
            Output::verbose(
                sprintf('References cache saved in file %s', $filename),
                1
            );
        }
    }

    /**
     * Устанавливает текущую БД
     * @param MysqliHelper $db
     * @param string       $from Опциональное название места
     */
    public static function setCurrentDb(MysqliHelper $db, $from = null)
    {
        self::$currentDb = $db;
        Output::verbose(
            sprintf(
                'Set %s as current database from %s',
                $db->getCredentials(),
                is_null($from) ? 'undefined place' : $from
            ),
            3
        );
    }

    /**
     * Удаляет мусор из определений сущностей
     * @static
     * @param string $content Описание сущности
     * @param string $type    Тип сущности
     * @param array  $extra   Массив дополнительной информации (ключи definer и entity)
     * @return string
     */
    public static function stripTrash($content, $type, array $extra = array())
    {
        $search  = array();
        $replace = array();
        switch ($type) {
            case 'TABLE':
                $search[] = 'AUTO_INCREMENT';
                if (preg_match('/\s*ENGINE=InnoDB\s*/ims', $content)) {
                    $search = array_merge(
                        array(
                            'CHECKSUM',
                            'AVG_ROW_LENGTH',
                            'DELAY_KEY_WRITE',
                            'ROW_FORMAT'
                        ),
                        $search
                    );
                }
                foreach ($search as $index => &$value) {
                    $pattern = "/ {$value}=\w+/ims";
                    if (preg_match($pattern, $content, $m)) {
                        $value     = $m[0];
                        $replace[] = '';
                    } else {
                        unset($search[$index]);
                    }
                }
                if (!preg_match('/\s*IF\s+NOT\s+EXISTS\s+/ims', $content)) {
                    $search[]  = 'CREATE TABLE';
                    $replace[] = 'CREATE TABLE IF NOT EXISTS';
                }
                break;
            case 'VIEW':
                if (!preg_match('/\s*OR\s+REPLACE\s+/ims', $content)) {
                    $search[]  = 'CREATE ';
                    $replace[] = 'CREATE OR REPLACE ';
                }
                break;
            default:
                if (!in_array($type, array('TRIGGER', 'FUNCTION', 'PROCEDURE'))) {
                    $type = static::getStatementType($content);
                }
                if (!is_null($type)) {
                    $replaceString = null;
                    if (!preg_match('/DROP\s+(?:TRIGGER|FUNCTION|PROCEDURE)\s+/ims', $content)) {
                        $replaceString = "DROP %s IF EXISTS %s;\n%s\n";
                    }
                    if (!preg_match('/DELIMITER ;;/ims', $content)) {
                        $replaceString = "DROP %s IF EXISTS %s;\nDELIMITER ;;\n%s\nDELIMITER ;\n";
                    }
                    if (!is_null($replaceString)) {
                        $search[]  = $content;
                        $replace[] = sprintf(
                            $replaceString,
                            $type,
                            $extra['entity'],
                            $content
                        );
                    }
                }
                break;
        }

        $db                 = self::$currentDb;
        $needReplace        = true;
        $definerReplacement = null;
        if ($db) {
            $needReplace = !$db->isTemporary();
        } else {
            Output::verbose('NO current database setted', 3);
        }
        if ($needReplace) {
            $definerReplacement = trim((string) Helper::get('routine_user'));
        }
        if (empty($definerReplacement)) {
            $definerReplacement = 'CURRENT_USER';
        }
        Output::verbose(
            sprintf(
                'Definers in %s will be replaced to %s',
                $db ? $db->getDatabaseName() : '<not defined>',
                $definerReplacement
            ),
            3
        );
        if (isset($extra['definer'])) {
            $search[]  = $extra['definer'];
            $replace[] = $definerReplacement;
        } else {
            if (preg_match('/DEFINER=(.*?)\s+/ims', $content, $m)) {
                $search[]  = $m[1];
                $replace[] = $definerReplacement;
            }
        }


        if (!empty($search)) {
            $content = str_replace($search, $replace, $content);
        }

        return $content;
    }

    /**
     * Возвращает тип оператора
     * @param string $statement
     * @param string $default
     * @return string
     */
    private static function getStatementType($statement, $default = null)
    {
        $patternTable   = '/^\s*CREATE\s+TABLE\s+/ims';
        $patternView    = '/^CREATE(?:(?:.*?)\s+ALGORITHM=(?:.*?))?(?:\s+DEFINER=(.*?))?' .
            '(?:\s+SQL\s+SECURITY\s+(?:DEFINER|INVOKER))?\s+VIEW\s+(?:.*?)\s+' .
            '(?:\(.*?\)\s+)?AS\s+\(?(.*?)\)?\s*(?:WITH\s+(?:.*?))?;$/';
        $patternRoutine = '/^\s*CREATE\s+(?:.*\s+)?(?:DEFINER=(.*?))?\s+(?:.*\s+)?(TRIGGER|FUNCTION|PROCEDURE)/im';
        $patterns       = array($patternTable => 'TABLE', $patternView => 'VIEW', $patternRoutine => 2);
        $statementType  = $default;
        foreach ($patterns as $pattern => $value) {
            if (preg_match($pattern, $statement, $matches)) {
                if (is_int($value)) {
                    $statementType = $matches[$value];
                } else {
                    $statementType = $value;
                }
                break;
            }
        }
        return $statementType;
    }

    /**
     * Вспомогательные действия с файлами схемы
     * @static
     * @param array  $queries       Ссылка на массив запросов
     * @param array  $views         Ссылка на массив запросов для вьюх
     * @param array  $includeTables Массив таблиц, которые нужно использовать
     * @param string $file          Имя файла
     * @param array  $md5           Ссылка на массив хешей
     * @param bool   $listOnly      Отдать только названия сущностей и хеши
     * @return bool
     */
    private static function schemaFileRoutines(
        array &$queries,
        array &$views,
        array $includeTables,
        $file,
        &$md5,
        $listOnly
    )
    {
        $exclude        = !empty($includeTables);
        $patternTable   = '/^\s*CREATE\s+TABLE\s+/ims';
        $patternView    = '/^CREATE(?:(?:.*?)\s+ALGORITHM=(?:.*?))?(?:\s+DEFINER=(.*?))?(?:\s+SQL\s+SECURITY\s+(?:DEFINER|INVOKER))?\s+VIEW\s+(?:.*?)\s+(?:\(.*?\)\s+)?AS\s+\(?(.*?)\)?\s*(?:WITH\s+(?:.*?))?;$/';
        $patternRoutine =
            '/^\s*CREATE\s+(?:.*\s+)?(?:DEFINER=(.*?))?\s+(?:.*\s+)?(TRIGGER|FUNCTION|PROCEDURE)/im';
        // если файл - получим данные о его имени
        $fileInfo = pathinfo($file);
        // если это SQL-файл, заберем его содержимое
        if (strcasecmp($fileInfo['extension'], 'sql') === 0) {
            $entityname = $fileInfo['filename'];
            Output::verbose(
                sprintf(
                    '--- Get content for %s',
                    $entityname
                ),
                3
            );
            if ($exclude && !isset($includeTables[$entityname])) {
                return false;
            }
            $q = file_get_contents($file);
            if ($q === ';') {
                return false;
            }
            $md5[$entityname] = md5($q);
            if ($listOnly) {
                $queries[] = $entityname;
            } else {
                $tmp = array($entityname => $q);
                if (preg_match($patternTable, $q)) {
                    $tmp[$entityname] = self::stripTrash($q, 'TABLE');
                    /*
                     * сложение необходимо для сохранения ключей массивов
                     * таблицы добавляем в начало массива
                     */
                    $queries = $tmp + $queries;
                } else {
                    $matches = array();
                    if (preg_match($patternView, $q, $matches)) {
                        $view_entityname       = $entityname . '_view';
                        $tmp[$view_entityname] = self::stripTrash(
                            $q,
                            'VIEW',
                            array('definer' => $matches[1])
                        );
                        $tmp[$view_entityname] = sprintf(
                            "DROP TABLE IF EXISTS %s;\n%s",
                            $entityname,
                            $tmp[$view_entityname]
                        );
                        /**
                         * дописываем вьюхи в отдельный массив,
                         * который добавим в конец всего
                         */
                        $views += $tmp;
                    } else {
                        /**
                         *  Если это триггер, процедура или функция, меняем создателя
                         */
                        if (preg_match($patternRoutine, $q, $matches)) {
                            $tmp[$entityname] = self::stripTrash(
                                $q,
                                $matches[2],
                                array(
                                    'definer' => $matches[1],
                                    'entity'  => $entityname
                                )
                            );
                            // и дописываем такие сущности в конец массива запросов
                            $queries += $tmp;
                        }
                    }
                }
            }
        }

        return true;
    }

    /**
     * Проходит по папке с файлами схемы и собирает их
     * @param array $includeTables Хеш с именами таблиц, которые нужно включать, в качестве ключей
     * @param bool  $listOnly      Нужно ли получить только список сущностей и их хеши
     * @return array Массив запросов
     */
    public static function parseSchemaFiles(array $includeTables = array(), $listOnly = false)
    {
        $md5       = array();
        $queries   = array();
        $views     = array();
        $schemadir = Helper::get('schemadir');
        if (!is_dir($schemadir) || !is_readable($schemadir)) {
            Output::verbose(
                sprintf('There are no schema files in %s', $schemadir),
                1
            );
        } else {
            $workingDirectory = getcwd();
            $dirs             = array($schemadir);
            while (!empty($dirs)) {
                $dir = array_pop($dirs);
                Output::verbose(sprintf('Come into %s directory', $dir), 3);
                $handle = opendir($dir);
                chdir($dir);
                $dirLen = mb_strlen($dir);
                if (mb_substr($dir, $dirLen - 1, 1) !== DIR_SEP) {
                    $dir .= DIR_SEP;
                }
                while ($file = readdir($handle)) {
                    if ($file != '.' && $file != '..' && is_readable($file)) {
                        if (is_file($file)) {
                            if (self::schemaFileRoutines(
                                $queries,
                                $views,
                                $includeTables,
                                $file,
                                $md5,
                                $listOnly
                            )
                            ) {
                                continue;
                            }
                        } elseif (is_dir($file)) {
                            /**
                             * Если это директория, то допишем ее имя к строке
                             * поддиректорий и добавим в стек директорий
                             */
                            $dir_to_add = $dir . $file;
                            array_push($dirs, $dir_to_add);
                            Output::verbose(
                                sprintf('Add subdirectory %s', $dir_to_add),
                                3
                            );
                        }
                    }
                }
                closedir($handle);
            }
            // возвращаем назад рабочую директорию
            chdir($workingDirectory);
        }
        /**
         * вьюхи идут после хранимых процедур, функций и триггеров,
         * которые в свою очередь идут после таблиц
         */
        $queries += $views;

        // избавимся от возможных дублирующихся названий, т.к. могли быть собраны такие для временных таблиц для вьюх
        if ($listOnly) {
            $queries = array_unique($queries);
        }

        return array('queries' => $queries, 'md5' => $md5);
    }

    /**
     * Спрашивает у пользователя, необходимо ли перезаписывать файл
     * @param string   $filename Имя файла
     * @param string   $message  Сообщение
     * @param callable $fn       Функция, использумая для дополнительной проверки
     * @param array    $fnArgs   Аргументы функции проверки
     * @return boolean Результат ввода пользователя
     */
    public static function askToRewrite($filename, $message = '', $fn = null, $fnArgs = array())
    {
        $hash = md5($filename);
        if (self::getAnswer($hash)) {
            // если уже отвечали, нет смысла запрашивать ввод пользователя
            return false;
        } else {
            $choices = array(
                'y' => true,
                'n' => false
            );
            if (self::get('quiet') || !file_exists($filename)) {
                self::saveAnswer($hash, 'y');
                $c = true;
            } else {
                if (!is_null($fn)) {
                    $c = call_user_func_array($fn, $fnArgs);
                    self::saveAnswer($hash, array_search($c, $choices, true));
                } else {
                    $realC = '';
                    do {
                        if ($realC !== "\n") {
                            if (empty($message)) {
                                $message =
                                    'File %s already exists. Do you really want to override it? [y/n] ';
                            }
                            printf($message, $filename);
                        }
                        $realC = fgets(STDIN);
                        $c     = mb_strtolower(trim($realC));
                        if (isset($choices[$c])) {
                            self::saveAnswer($hash, $c);
                            $c = $choices[$c];
                            break;
                        }
                    } while (true);
                }
            }

            return $c;
        }
    }

    /**
     * Возвращает наименование действия, выполняемого контроллером
     * @static
     * @param IController $controller Экземпляр класса
     * @return string
     */
    public static function getActionName(IController $controller)
    {
        $name = get_class($controller);
        $name = substr($name, strrpos($name, '\\') + 1);

        return substr($name, 0, stripos($name, 'Controller'));
    }

    /**
     * DEPRECATED
     * @param type $version
     * @param type $diff
     * @return type
     */
    static function _createMigrationContent($version, $diff)
    {
        $content =
            "<?php\n class Migration{$version} extends AbstractMigration\n{\n" .
            "  protected \$up = array(\n";
        foreach ($diff['up'] as $sql) {
            $content .= "    '{$sql}',\n";
        }
        $content .= "  );\n  protected \$down = array(\n";

        foreach ($diff['down'] as $sql) {
            $content .= "    '{$sql}',\n";
        }
        $content .= "  );\n  protected \$rev = {$version};\n}\n";

        return $content;
    }

    /**
     * Сохраняет ответ в списке
     * @static
     * @param string $answer Название вопроса (ключ)
     * @param string $value  Ответ пользователя (по умолчанию "1")
     */
    public static function saveAnswer($answer, $value = '1')
    {
        self::$_executedRequests[$answer] = $value;
    }

    /**
     * Получить сохраненный ответ пользователя
     * @static
     * @param string $answer Название вопроса (ключ)
     * @return mixed|bool Ответ пользователя, по умолчанию false
     */
    public static function getAnswer($answer)
    {
        $value = false;
        isset(self::$_executedRequests[$answer]) && ($value = self::$_executedRequests[$answer]);

        return $value;
    }

    /**
     * Возвращает имя файла схемы (или имя "по умолчанию", даже если файла не существует)
     * @param string $hash       Хеш от датасетов
     * @param string $schemaType По умолчанию должна возвращаться "мигрированная" схема или "обычная"
     * @throws \Exception
     * @return string
     */
    public static function getSchemaFile($hash = '', $schemaType = null)
    {
        $path         = self::get('cachedir');
        $pattern      = '%sSchema%s%s.class.php';
        $files        = array(
            AbstractSchema::MIGRATED => sprintf($pattern, $path, $hash, 'migrated'),
            AbstractSchema::ORIGINAL => sprintf($pattern, $path, $hash, '')
        );
        $index        = -1;
        $needMigrated = ($schemaType === AbstractSchema::MIGRATED);
        foreach ($files as $key => $file) {
            // если файл существует
            if (is_file($file)) {
                $index = $key;
                Output::verbose('Founded schema file ' . $file, 2);
            }
            // если файл найден или нужна только мигрированная схема, выходим из цикла
            if ((!$schemaType && ($index !== -1)) || ($index === $schemaType) || $needMigrated) {
                break;
            }
        }

        if ($index === -1) {
            $index = $needMigrated ? AbstractSchema::MIGRATED : AbstractSchema::ORIGINAL;
        }

        $file = $files[$index];

        if ($needMigrated) {
            unset(self::$_executedRequests[md5($file)]);
        }

        // и доступен на чтение и запись, вернем его
        if (is_file($file) && (!is_readable($file) || !is_writable($file))) {
            throw new \Exception(sprintf('Cannot get schema file (tried %s, but has not RW access', $file));
        }

        return $file;
    }

    /**
     * Возвращает имя класса схемы
     * @param string $hash
     * @param bool   $migrated
     * @return \lib\AbstractSchema
     */
    public static function getSchemaClassName($hash, $migrated)
    {
        return sprintf(
            '%s\Schema%s%s',
            self::get('cachedir_ns'),
            (string) $hash,
            (bool) $migrated ? 'migrated' : ''
        );
    }

    /**
     * Возвращает массив переменных замены
     * @return array
     */
    public static function getReplaceVariables()
    {
        if (is_null(self::$_replaceVariables)) {
            $tmp = array();
            foreach (self::$config as $key => $value) {
                if (strpos($key, 'database.') === 0) {
                    $dbName = trim(str_replace('database.', '', $key));
                    $value  = trim($value);
                    if (!empty($dbName)) {
                        $tmp[$dbName] = $value;
                        Output::verbose(
                            sprintf(
                                'In all queries database %s will be replaced to %s',
                                $dbName,
                                $value
                            ),
                            3
                        );
                    }
                }
            }
            uksort(
                $tmp,
                function ($k1, $k2) {
                    $l1 = strlen($k1);
                    $l2 = strlen($k2);
                    if ($l1 === $l2) {
                        return 0;
                    }

                    return $l1 > $l2 ? -1 : 1;
                }
            );
            self::$_replaceVariables = $tmp;
        }

        return self::$_replaceVariables;
    }

}