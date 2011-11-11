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
     * reqs -------
     *            |- dataset1 -> JSON1
     *            |- dataset2 -> JSON2
     * sqlContent -
     *            |- dataset1 -> SQL1
     *            |- dataset2 -> SQL2
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
            $data = array();

            while ($dir = readdir($handle)) {
                if (isset($datasets[$dir]) && is_dir($dir) && is_readable($dir)) {
                    $tablesFileName = $dir . Helper::get('reqtables');

                    if (is_file($tablesFileName) && is_readable($tablesFileName)) {
                        self::$_datasets['reqs'][$dir] = json_encode(file_get_contents($tablesFileName));
                        $datafile = $dir . self::get('reqdata');
                        if ($loadDatasetContent && is_file($datafile) && is_readable($datafile)) {
                            self::$_datasets['sqlContent'][$dir] = file_get_contents($datafile);
                        }
                    }
                }
            }

            closedir($handle);
            if (empty(self::$_datasets) || ( $loadDatasetContent && empty(self::$_datasets['sqlContent']) )) {
                throw new \Exception('Не найдены данные для разворачивания');
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
            return new $ctrl(self::getDbObject(), $args);
        } catch (\Exception $e) {
            throw new \Exception("Неопознанная команда: $name\n");
        }
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

    static function getTmpDbObject() {
        $config = self::getConfig();
        $tmpname = $config['db'] . '_' . self::getCurrentVersion();
        $config['db'] = $tmpname;
        $db = self::getDbObject();
        $db->query("create database `{$config['db']}`");
        $tmpdb = self::getDbObject($config);
        register_shutdown_function(function() use($config, $tmpdb) {
                    Output::verbose("Shutdown: database {$config['db']} has been dropped");
                    $tmpdb->query("DROP DATABASE `{$config['db']}`");
                })
        ;
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

    static function applyMigration($revision, $db, $direction = 'Up') {
        $classname = self::get('savedir') . '\Migration' . $revision;
        $migration = new $classname($db);
        $method = 'run' . $direction;
        $migration->$method();
    }

    static function getAllMigrations() {
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

    static function loadTmpDb($db) {
        $fname = self::get('savedir') . '/Schema.php';
        if (!file_exists($fname)) {
            echo "File: {$fname} does not exist!\n";
            exit;
        }

        $db->query('SET foreign_key_checks = 0;');
        $classname = self::get('savedir') . '\Schema';
        $sc = new $classname;
        $sc->load($db);

        $migrations = self::getAllMigrations();
        foreach ($migrations as $revision) {
            self::applyMigration($revision, $db);
        }
        $db->query('SET foreign_key_checks = 1;');
    }

    private function sqlImplode(array $a) {
        $result = array();
        foreach ($a as $direction => $data) {
            foreach ($data as $table => $queries) {
                $result['tmp'][] = "'$table' => array(\n'" . implode("',\n'", $queries) . "'\n)";
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
     * @param string $tpl Шаблон класса
     * @return string Контент файла класса
     */
    public static function createMigrationContent($version, array $diff, $tpl = 'tpl/migration.tpl') {
        $version = (int) $version;
        $content = file_get_contents(DIR . $tpl);
        $search = array('revision', 'up', 'down', 'meta');
        $metadata = array(
            'timestamp' => time(),
            'tables' => "array(\n'" . implode("',\n'", $diff['tables']) . "'\n)",
            'revision' => $version
        );
        foreach ($metadata as $key => &$metaitem) {
            $metaitem = "'$key' =>  $metaitem";
        }
        unset($diff['tables']);
        $sql = self::sqlImplode($diff);
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
