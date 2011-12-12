<?php

namespace lib;

use \Mysqli;

class Helper {

    protected static $_datasets = array();
    static protected $config_tpl = array(
        'config' => array('short' => 'c', 'req_val'),
        'host' => array('req_val'),
        'user' => array('req_val'),
        'password' => array('req_val'),
        'db' => array('req_val'),
        'savedir' => array('req_val'),
        'verbose' => array('req_val'),
        'versionfile' => array('req_val'),
        'quiet' => array('short' => 'q', 'no_val'),
        'version_marker' => array('req_val'),
        'tmp_host' => array('req_val'),
        'tmp_user' => array('req_val'),
        'tmp_password' => array('req_val'),
        'cachedir' => array('req_val')
    );
    static protected $config = array(
        'config' => null, //path to alternate config file
        'host' => null,
        'user' => null,
        'password' => null,
        'db' => null,
        'savedir' => null,
        'cachedir' => null,
        'verbose' => null,
        'versionfile' => null,
        'version_marker' => null
    );
    private static $_revisionLines = array();
    private static $_lastRevision = 0;
    private static $_currRevision = -1;

    static function setConfig($cnf) {
        self::$config = array_replace(self::$config, $cnf);
    }

    static function getConfig() {
        return self::$config;
    }

    /**
     * Parse command line into config options and commands with its parameters
     *
     * $param array $args List of arguments provided from command line
     */
    static function parseCommandLineArgs($args) {
        $parsed_args = array('options' => array(), 'command' => array('name' => null, 'args' => array()));

        array_shift($args);
        $opts = GetOpt::extractLeft($args, self::$config_tpl);
        if ($opts === false) {
            Output::error('mmm: ' . reset(GetOpt::errors()));
            exit(1);
        }
        else
            $parsed_args['options'] = $opts;

        //if we didn't traverse the full array just now, move on to command parsing
        if (!empty($args))
            $parsed_args['command']['name'] = array_shift($args);

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
     * @param array $dataset Массив имен датасетов
     * @param bool $loadDatasetContent Загружать ли содержимое SQL датасетов
     * @return array 
     */
    public static function getDatasetInfo(array $datasets, $loadDatasetContent = false) {
        if (empty(self::$_datasets)) {
            $dsdir = DIR . Helper::get('datasetsdir');
            // получить данные
            if (!is_dir($dsdir) || !is_readable($dsdir)) {
                throw new \Exception(
                        sprintf("Directory %s with datasets is not exists",
                                $dsdir)
                );
            }

            $handle = opendir($dsdir);
            chdir($dsdir);

            while ($dir = readdir($handle)) {
                if (isset($datasets[$dir]) && is_dir($dir) && is_readable($dir)) {
                    $tablesFileName = $dir . DIR_SEP . Helper::get('reqtables');
                    if (is_file($tablesFileName) && is_readable($tablesFileName)) {
                        self::$_datasets['reqs'][$dir] = json_decode(file_get_contents($tablesFileName),
                                true);
                        $datafile = $dir . DIR_SEP . self::get('reqdata');
                        if ($loadDatasetContent && is_file($datafile) && is_readable($datafile)) {
                            self::$_datasets['sqlContent'][$dir] = file_get_contents($datafile);
                        }
                    }
                }
            }

            closedir($handle);
            if (empty(self::$_datasets) || ( $loadDatasetContent && empty(self::$_datasets['sqlContent']) )) {
                throw new \Exception('Data for deploy not found');
            }
        }

        return self::$_datasets;
    }

    /**
     * TODO: прикрутить пул контроллеров
     * Get available controller object
     *
     * With no parameters supplied, returns "help" controller
     *
     * @param string $name Controller name
     * @param array $args Optional controller arguments
     * @return AbstractController Initialized controller, False if not found
     */
    static function getController($name=null, $args=array()) {
        if (empty($name))
            return new helpController;

        $ctrl = 'lib\\' . $name . 'Controller'; // http://php.net/manual/en/language.namespaces.dynamic.php
        try {
            $ctrl = new $ctrl(self::getDbObject(), $args);
            if ($ctrl instanceof DatasetsController) {
                // обернем его в цепочку
                $chain = new ControllersChain();
                $ctrl->setChain($chain);
                $chain->setController($ctrl);
                return $chain;
            }
            return $ctrl;
        }
        catch (\Exception $e) {
            Output::verbose($e->getMessage(), 2);
            throw new \Exception("Command $name was not recognized");
        }
    }

    /**
     * Возвращает связанные со списком таблицы
     * @param array $refs Хеш, где ключ - имя таблицы, значение - хеш вида имя_связанной_таблицы => 1
     * @param array $tablesList Хеш вида имя_таблицы => 1
     * @return array Связанные таблицы, не входящие в список
     */
    public static function getRefs(array $refs = array(), array $tablesList = array()) {
        $res = array();
        $existsRefs = array_intersect_key($refs, $tablesList);

        $closure = function ($arr) use(&$closure, &$res, $refs) {
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

    public static function prepareDb(Mysqli $connection, $dbName) {
        $res = $connection->query('SHOW DATABASES;');
        $dbs = array();
        $flag = false;
        while ($row = $res->fetch_array(MYSQLI_NUM)) {
            if ($row[0] === $dbName) {
                Output::verbose(
                        sprintf('Found database %s in current databases',
                                $dbName), 2
                );
                $flag = true;
                break;
            }
        }
        if (!$flag) {
            Output::verbose(sprintf('Create database %s', $dbName), 2);
            $connection->query("CREATE DATABASE `{$dbName}` DEFAULT CHARACTER SET cp1251 COLLATE cp1251_general_ci;");
        }
        $connection->select_db($dbName);
    }

    /**
     * Возвращает объект соединения
     * @staticvar <type> $db
     * @param array $config
     * @return Mysqli
     */
    static function getDbObject($config=array()) {
        static $db = null;
        $conf = self::$config;
        if (count($config)) {
            foreach ($config as $option => $value) {
                $conf[$option] = $value;
            }
        }
        else {
            if ($db)
                return $db;
            $db = new Mysqli($conf['host'], $conf['user'], $conf['password']);
            self::prepareDb($db, $conf['db']);
            return $db;
        }
        $t = new Mysqli($conf['host'], $conf['user'], $conf['password']);
        self::prepareDb($t, $conf['db']);
        return $t;
    }

    /**
     * Создает, если не было, директорию для миграций
     * @return void 
     */
    static function initDirForSavedMigrations() {
        $s = false;
        $c = false;
        if (is_dir(DIR . self::$config['savedir'])) {
            $s = true;
        }
        if (is_dir(DIR . self::$config['cachedir'])) {
            $c = true;
        }
        if ($c && $s) {
            return true;
        }
        !$s && mkdir(DIR . self::$config['savedir'], 0775, true);
        !$c && mkdir(DIR . self::$config['cachedir'], 0775, true);
    }

    static public function get($key) {
        return isset(self::$config[$key]) ? self::$config[$key] : false;
    }

    /**
     * Создает объект соединения для временной БД
     * TODO: объединить методы создания соединения
     * @return Mysqli 
     */
    public static function getTmpDbObject($tmpname = '') {
        $config = self::getConfig();
        if (empty($tmpname)) {
            $tmpname = $config['db'] . '_' . self::getCurrentVersion();
        }

        $c = array();
        $params = array('host', 'password', 'user');
        foreach ($params as $p) {
            $c[$p] = $config['tmp_' . $p];
        }
        $c['db'] = $tmpname;
        unset($config);
        $tmpdb = self::getDbObject($c);
        if (!$tmpdb->set_charset("utf8")) {
            throw new \Exception(sprintf("SET CHARACTER SET utf8 error: %s\n",
                            $tmpdb->error));
        }
        register_shutdown_function(function() use($c, $tmpdb) {
                    $tmpdb->query("DROP DATABASE `{$c['db']}`");
                    Output::verbose("Temporary database {$c['db']} was deleted",
                            2);
                });
        return $tmpdb;
    }

    static function initVersionTable() {
        $db = self::getDbObject();
        $tbl = self::get('versiontable');
        $rev = self::getCurrentVersion();
        $db->query("DROP TABLE IF EXISTS `{$tbl}`");
        $db->query("CREATE TABLE `{$tbl}` (`rev` BIGINT(20) UNSIGNED) ENGINE=MyISAM");
        $db->query("TRUNCATE `{$tbl}`");
        $db->query("INSERT INTO `{$tbl}` VALUES({$rev})");
    }

    static function getCurrentVersion() {
        return time();
    }

    static function getSqlForTableCreation($tname, $db) {
        $tres = $db->query("SHOW CREATE TABLE `{$tname}`");
        $trow = $tres->fetch_array(MYSQLI_NUM);
        $query = preg_replace('#AUTO_INCREMENT=\S+#is', '', $trow[1]);
        $query = preg_replace("#\n\s*#", ' ', $query);
        $query = addcslashes($query, '\\\''); //escape slashes and single quotes
        return $query;
    }

    static function getDatabaseVersion(Mysqli $db) {
        $tbl = self::get('versiontable');
        $res = $db->query("SELECT max(rev) FROM `{$tbl}`");
        if ($res === false)
            return false;
        $row = $res->fetch_array(MYSQLI_NUM);
        return intval($row[0]);
    }

    /**
     * Get all revisions that have been applied to the database
     *
     * @param Mysqli $db Database instance
     * @return array|bool List of applied revisions, False on error
     */
    static function getDatabaseVersions(Mysqli $db) {
        $result = array();
        $tbl = self::get('versiontable');
        $res = $db->query("SELECT rev FROM `{$tbl}` ORDER BY rev ASC");
        if ($res === false)
            return false;

        while ($row = $res->fetch_array(MYSQLI_NUM))
            $result[] = $row[0];

        return $result;
    }

    /**
     * Выполняет запросы с отладкой. Не останавливается в случае ошибки
     * @param Mysqli $db
     * @param array $queries 
     */
    public static function _debug_queryMultipleDDL(Mysqli $db, array $queries) {
        foreach ($queries as $table => $stmts) {
            Output::verbose(sprintf('Executing query for table %s', $table), 2);
            foreach ($stmts as $qs) {
                $qs = stripslashes($qs);
                $q = explode(";\n", $qs);
                if (!is_array($q)) {
                    $q = array($q);
                }
                foreach ($q as $i) {
                    if (empty($i)) {
                        continue;
                    }
                    //Output::verbose("   $i", 3);
                    if (!$db->query($i)) {
                        Output::error(
                                sprintf('   %s: %s (%d)', $i, $db->error,
                                        $db->errno)
                        );
                    }
                }
            }
        }
    }

    /**
     * Выполняет запросы DDL
     * @param Mysqli $db
     * @param string $queries 
     */
    public static function queryMultipleDDL(Mysqli $db, $queries) {
        $start = microtime(1);
        $ret = $db->multi_query($queries);
        Output::verbose(
                sprintf('multi_query time: %f', (microtime(1) - $start)), 3
        );
        $text = $db->error;
        $code = $db->errno;
        if (!$ret) {
            throw new \Exception($text, $code);
        }
        do {
            
        }
        while ($db->next_result());
        Output::verbose(
                sprintf('Result set looping time: %f', (microtime(1) - $start)),
                3
        );
        $text = $db->error;
        $code = $db->errno;
        if ($code) {
            throw new \Exception($text, $code);
        }
    }

    /**
     *
     * @param type $data
     * @return array 
     */
    public static function getInitialRefs($data) {
        $dbName = 'db_' . md5(time());
        $db = self::getTmpDbObject($dbName);
        $db->query("SET foreign_key_checks = 0;");
        self::queryMultipleDDL($db, $data);
        $db->query("SET foreign_key_checks = 1;");

        $params = array('host', 'user', 'password');
        $params_str = array();
        foreach ($params as $param) {
            $value = Helper::get('tmp_' . $param);
            if (!empty($value)) {
                $params_str[] = "--{$param}={$value}";
            }
        }
        $start = microtime(1);
        $command = sprintf(
                "%s %s  --no-old-defs --refs %s",
                self::get('mysqldiff_command'), implode(' ', $params_str),
                $dbName
        );
        $output = array();
        $status = -1;
        exec($command, $output, $status);
        if (empty($output)) {
            throw new \Exception(
                    sprintf("An error was occured in command %s, return code %d",
                            $command, $status)
            );
        }
        Output::verbose(
                sprintf('References search in mysqldiff: %f seconds',
                        (microtime(1) - $start)), 3
        );
        $result = array();
        foreach ($output as $line) {
            $line = trim($line);
            if (empty($line)) {
                continue;
            }
            $tables = explode('|', $line);
            $tableName = array_shift($tables);
            foreach ($tables as $table) {
                $result[$tableName][$table] = 1;
            }
        }
        return $result;
    }

    /**
     * Возвращает информацию о изменениях таблиц в базе с течением времени
     * @param array $tablesList 
     * @return array 
     */
    public static function getTimeline(array $tablesList = array()) {
        $migrations = Registry::getAllMigrations();
        if (!empty($tablesList)) {
            $refs = Registry::getAllRefs();
            $tablesToAdd = self::getRefs($refs, $tablesList);
            $tablesList = array_merge($tablesList, $tablesToAdd);
        }
        else {
            $tablesList = $migrations;
        }
        $timeline = array();
        foreach ($tablesList as $tableName => $t) {
            foreach ($migrations[$tableName] as $timestamp => $revision) {
                $timeline[$timestamp][$tableName] = $revision;
            }
        }
        ksort($timeline);
        return $timeline;
    }

    public static function applyMigration($revision, $db, $direction = 'Up', array $tablesList = array()) {
        $classname = str_replace('/', '\\', self::get('savedir')) . '\Migration' . $revision;
        $migration = new $classname($db);
        $migration->setTables($tablesList);
        $method = 'run' . $direction;
        $migration->$method();
    }

    /**
     * Возвращает список всех миграций и связанных с ними данных
     * @return array 
     */
    public static function getAllMigrations() {
        self::$_revisionLines = array();
        self::$_currRevision = -1;
        $migrationsDir = DIR . self::get('savedir') . DIR_SEP;
        $migrationsListFile = $migrationsDir . self::get('versionfile');
        $markerFile = $migrationsDir . self::get('version_marker');
        $result = array(
            'migrations' => array(),
            'data' => array()
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
                    $parts = explode('|', $line);
                    // TODO: упростить структуру данных
                    $migrationId = (int) $parts[0];
                    $time = (int) $parts[2];
                    $result['migrations'][] = $migrationId;
                    $result['data'][$migrationId] = array(
                        'date' => $parts[1],
                        'time' => $time,
                        'revn' => $migrationId
                    );
                    $result['timestamps'][$time] = $migrationId;
                    self::$_lastRevision = $migrationId;
                }
                fclose($handler);
            }
            else {
                throw new \Exception(sprintf("Failed to open file %s",
                                $migrationsListFile));
            }
        }
        if (self::$_currRevision === -1) {
            self::$_currRevision = self::$_lastRevision;
        }
        usort(
                $result['migrations'],
                function ($a, $b) use ($result) {
                    return ($result['data'][$a]['time'] > $result['data'][$b]['time']) ? 1 : -1;
                }
        );
        return $result;
    }

    /**
     * Получить номер ожидаемой ревизии
     * @return int 
     */
    public static function getLastRevision() {
        if (!self::$_lastRevision) {
            self::getAllMigrations();
        }
        return++self::$_lastRevision;
    }

    public static function getCurrentRevision() {
        if (self::$_currRevision === -1) {
            self::getAllMigrations();
        }
        return self::$_currRevision;
    }

    public static function getRevisionLines() {
        if (empty(self::$_revisionLines)) {
            self::getAllMigrations();
        }
        return self::$_revisionLines;
    }

    /**
     * Записывает информацию о ревизии
     * @param int $revision Номер ревизии
     * @return int Таймстаймп для ревизии
     */
    public static function writeRevisionFile($revision) {
        $path = DIR . self::get('savedir') . DIR_SEP;
        $filename = $path . self::get('versionfile');
        $marker = $path . self::get('version_marker');
        if (is_file($filename) && !is_writable($filename)) {
            throw new \Exception(sprintf("File %s is write-protected", $filename));
        }
        $ts = time();
        $lines = self::getRevisionLines();
        //print_r($lines);
        //var_dump($revision);
        $b = ($revision === 0);
        foreach ($lines as $line) {
            $data = explode('|', $line);
            if ((int) $data[0] === $revision) {
                $b = true;
            }
        }
        if (!$b) {
            $lines[] = sprintf(
                    "%d|%s|%d", $revision, date('d.m.Y H:i:s', $ts), $ts
            );
        }
        self::$_revisionLines = $lines;
        file_put_contents($filename, implode("\n", $lines));
        if (is_file($marker) && !is_writable($marker)) {
            throw new \Exception(sprintf('Cannot write revision marker to file: %s',
                            $marker));
        }
        file_put_contents($marker, "#{$revision}");
        return $ts;
    }

    static function _getAllMigrations() {
        $dir = self::get('savedir');
        $files = glob($dir . '/Migration*.php');
        $result = array();
        foreach ($files as $file) {
            $key = preg_replace('#[^0-9]#is', '', $file);
            $result[] = $key;
        }
        sort($result, SORT_NUMERIC);
        return $result;
    }

    /**
     * Загружает начальную схему в базу и накатывает все миграции
     * @param Mysqli $db Соединение с сервером БД
     */
    public static function loadTmpDb(Mysqli $db) {
        Output::verbose("Deploy temporary database", 1);
        $db->query("SET foreign_key_checks = 0;");
        $timeline = self::getTimeline();
        $usedMigrations = array();
        foreach ($timeline as $tables) {
            foreach ($tables as $tablename => $revision) {
                $start = microtime(1);
                if (is_int($revision)) {
                    $what = sprintf("migration %d for table %s", $revision,
                            $tablename);
                    // обратимся к нужному классу
                    if (!isset($usedMigrations[$revision])) {
                        self::applyMigration($revision, $db);
                        $usedMigrations[$revision] = 1;
                    }
                }
                else {
                    $what = sprintf("sql for %s", $tablename);
                    // это SQL-запрос
                    $db->query($revision);
                }
                $stop = microtime(1);
                $t = $stop - $start;
                if (!Helper::get('quiet')) {
                    Output::verbose(
                            sprintf('Completed %s; time: %f seconds', $what, $t),
                            3
                    );
                }
            }
        };
        $db->query("SET foreign_key_checks = 1;");
        Output::verbose("Deploy was finished\n", 1);
    }

    /**
     * Рекурсивно превращает массив в строку
     * @param array $a Массив (любой вложенности)
     * @param int $level Уровень отступа
     * @return string Строка 
     */
    public static function recursiveImplode(array $a, $level = 1, $spacer = ' ') {
        $result = array();
        $depth = str_repeat($spacer, $level * 3);
        $depth2 = str_repeat($spacer, ($level - 1) * 3);
        foreach ($a as $k => $v) {
            $tmp = $depth;
            if (!is_int($k)) {
                $tmp = sprintf('%s"%s" => ', $depth, $k);
            }
            if (is_array($v)) {
                $tmp .= self::recursiveImplode($v, ($level + 1), $spacer);
            }
            else {
                if (is_string($v)) {
                    $tmp .= '"' . $v . '"';
                }
                else {
                    $tmp .= $v;
                }
            }
            $result[] = $tmp;
        }
        $sep = ",\n";
        return sprintf("array(\n%s\n%s)", implode($sep, $result), $depth2);
    }

    /**
     * Возможно deprecated в будущем
     * @param array $a
     * @return string 
     */
    private static function sqlImplode(array $a) {
        $result = array();
        foreach ($a as $direction => $data) {
            foreach ($data as $table => $queries) {
                $result['tmp'][] = "'$table' => array(\n\"" . implode("\",\n\"",
                                $queries) . "\"\n)";
            }
            $result[$direction] = "array(\n" . implode(",\n", $result['tmp']) . "\n)";
            unset($result['tmp']);
        }
        return $result;
    }

    /**
     * Создает класс миграции
     * @param int $version Ревизия
     * @param array $diff Массив различий
     * @param int $ts timestamp
     * @param string $tpl Шаблон класса
     * @return string Контент файла класса
     */
    public static function createMigrationContent($version, array $diff, $ts, $tpl = 'tpl/migration.tpl') {
        $version = (int) $version;
        $content = file_get_contents(DIR . $tpl);
        $search = array('revision', 'up', 'down', 'meta', 'ns');

        $metadata = array(
            'timestamp' => $ts,
            'tables' => $diff['tables']['used'],
            'refs' => $diff['tables']['refs'],
            'revision' => $version
        );
        unset($diff['tables']);

        $replace = array(
            $version,
            self::recursiveImplode($diff['up'], 2),
            self::recursiveImplode($diff['down'], 2),
            self::recursiveImplode($metadata, 2),
            str_replace('/', '\\', self::get('savedir'))
        );
        foreach ($search as &$placeholder) {
            $placeholder = "%%{$placeholder}%%";
        }
        return str_replace($search, $replace, $content);
    }

    /**
     * DEPRECATED
     * @param type $version
     * @param type $diff
     * @return type 
     */
    static function _createMigrationContent($version, $diff) {
        $content = "<?php\n class Migration{$version} extends AbstractMigration\n{\n" .
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

}
