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

    private static function prepareMap() {
        // Вначале соберем все данные из папки схемы
        $schemadir = DIR . Helper::get('schemadir');
        if (!is_dir($schemadir) || !is_readable($schemadir)) {
            throw new \Exception("Директории {$schemadir} с описаниями таблиц не существует\n");
        }

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
                $className = 'lib\\' . pathinfo($file, PATHINFO_FILENAME);
                $class = new $className;
                if ($class instanceof AbstractMigration) {
                    $metadata = $class->getMetadata();
                    foreach ($metadata['tables'] as $tablename => $tmp) {
                        self::$_migrations[$tablename][$metadata['timestamp']] = $metadata['revision'];
                    }
                    self::$_refsMap = array_merge(self::$_refsMap, $metadata['refs']);
                }
            }
        }
        
    }

    public static function getMap() {
        
    }

}

?>
