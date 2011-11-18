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
        'versiontable' => array('req_val')
    );
    static protected $config = array(
        'config' => null, //path to alternate config file
        'host' => null,
        'user' => null,
        'password' => null,
        'db' => null,
        'savedir' => null,
        'verbose' => null,
        'versiontable' => null
    );
    private static $_lastRevision;

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
            Output::error('mmp: ' . reset(GetOpt::errors()));
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
                throw new \Exception("Директории {$dsdir} с наборами данных не существует");
            }

            $handle = opendir($dsdir);
            chdir($dsdir);

            while ($dir = readdir($handle)) {
                if (isset($datasets[$dir]) && is_dir($dir) && is_readable($dir)) {
                    $tablesFileName = $dir . DIR_SEP . Helper::get('reqtables');
                    if (is_file($tablesFileName) && is_readable($tablesFileName)) {
                        self::$_datasets['reqs'][$dir] = json_decode(file_get_contents($tablesFileName), true);
                        $datafile = $dir . DIR_SEP . self::get('reqdata');
                        if ($loadDatasetContent && is_file($datafile) && is_readable($datafile)) {
                            self::$_datasets['sqlContent'][$dir] = file_get_contents($datafile);
                        }
                    }
                }
            }

            closedir($handle);
            if (empty(self::$_datasets) || ( $loadDatasetContent && empty(self::$_datasets['sqlContent']) )) {
                throw new \Exception('Не найдены данные1 для разворачивания');
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
        } catch (\Exception $e) {
            throw new \Exception("Команда $name не опознана\n");
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

        $closure = function ($arr) use(&$closure, &$res) {
                    foreach ($arr as $table => $value) {
                        $res[$table] = 1;
                        if (is_array($value)) {
                            $closure($value);
                        }
                    }
                };

        $closure($refs);

        return array_diff_key($res, $tablesList);
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
        } else {
            if ($db)
                return $db;
            $db = new Mysqli($conf['host'], $conf['user'], $conf['password'], $conf['db']);
            return $db;
        }
        return new Mysqli($conf['host'], $conf['user'], $conf['password'], $conf['db']);
    }

    /**
     * Создает, если не было, директорию для миграций
     * @return void 
     */
    static function initDirForSavedMigrations() {
        if (is_dir(self::$config['savedir']))
            return;
        mkdir(self::$config['savedir'], 0775, true);
    }

    static public function get($key) {
        return isset(self::$config[$key]) ? self::$config[$key] : false;
    }

    /**
     * Создает объект соединения для временной БД
     * TODO: объединить методы создания соединения
     * @return Mysqli 
     */
    public static function getTmpDbObject() {
        $config = self::getConfig();
        $tmpname = $config['db'] . '_' . self::getCurrentVersion();
        $config['db'] = $tmpname;
        $db = self::getDbObject();
        $db->query("CREATE DATABASE `{$config['db']}`  DEFAULT CHARACTER SET utf8 COLLATE utf8_general_ci;");
        $tmpdb = self::getDbObject($config);
        register_shutdown_function(function() use($config, $tmpdb) {
                    $tmpdb->query("DROP DATABASE `{$config['db']}`");
                    Output::verbose("Временная база данных {$config['db']} была удалена");
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

    public static function applyMigration($revision, $db, $direction = 'Up') {
        $classname = self::get('savedir') . '\Migration' . $revision;
        $migration = new $classname($db);
        $method = 'run' . $direction;
        $migration->$method();
    }

    /**
     * Возвращает список всех миграций и связанных с ними данных
     * @return array 
     */
    public static function getAllMigrations() {
        $migrationsDir = DIR . self::get('savedir') . DIR_SEP;
        $migrationsListFile = $migrationsDir . self::get('versionfile');
        $result = array(
            'migrations' => array(),
            'data' => array()
        );
        if (is_file($migrationsListFile) && is_readable($migrationsListFile)) {
            $handler = fopen($migrationsListFile, 'r');
            if ($handler) {
                while (!feof($handler)) {
                    $line = trim(fgets($handler));
                    if (empty($line))
                        continue;
                    $parts = explode('|', $line);
                    $result['migrations'][] = $parts[0];
                    $result['data'][$parts[0]] = array(
                        'date' => $parts[1],
                        'time' => $parts[2]
                    );
                    self::$_lastRevision = $parts[0];
                }
            }
            else {
                throw new \Exception(sprintf("Не удается открыть файл %s\n", $migrationsListFile));
            }
        }
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

    /**
     * Записывает информацию о ревизии
     * @param int $revision Номер ревизии
     * @return int Таймстаймп для ревизии
     */
    public static function writeRevisionFile($revision) {
        $filename = DIR . self::get('savedir') . DIR_SEP . self::get('versionfile');
        if (is_file($filename) && !is_writable($filename)) {
            throw new \Exception(sprinf("Файл %s защищен от записи\n", $filename));
        }
        $handler = fopen($filename, 'a');
        $ts = time();
        fwrite($handler, sprintf("%d|%s|%d\n", $revision, date('d.m.Y H:i:s', $ts), $ts));
        fclose($handler);
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
    public static function loadTmpDb($db) {
        $db->query('SET foreign_key_checks = 0;');
        $classname = self::get('savedir') . '\Schema';
        $sc = new $classname;
        $sc->load($db);
        $tables = $sc->getTables();
        $migrations = self::getAllMigrations();
        foreach ($migrations['migrations'] as $revision) {
            self::applyMigration($revision, $db);
        }
        $db->query('SET foreign_key_checks = 1;');
    }

    /**
     * Рекурсивно разбивает массив (почти как print_r, дабы не парсить его вывод)
     * @param array $a
     * @param type $level
     * @return type 
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
            } else {
                if (is_string($v)) {
                    $tmp .= '"' . $v . '"';
                } else {
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
                $result['tmp'][] = "'$table' => array(\n\"" . implode("\",\n\"", $queries) . "\"\n)";
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
        $search = array('revision', 'up', 'down', 'meta');

        $tablesFormat = "array(\n\"%s\"\n)";
        $sep = "\",\n";

        $metadata = array(
            'timestamp' => $ts,
            'tables' => $diff['tables']['used'],
            'refs' => $diff['tables']['used'],
            'revision' => $version
        );
        unset($diff['tables']);
        
        $replace = array(
            $version,
            $sql['up'],
            $sql['down'],
            "array(\n" . implode(",\n", $metadata) . "\n)"
        );
        foreach ($search as &$placeholder) {
            $placeholder = '%%' . $placeholder . '%%';
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
