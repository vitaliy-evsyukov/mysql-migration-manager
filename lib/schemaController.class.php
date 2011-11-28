<?php

namespace lib;

class schemaController extends DatasetsController {

    /**
     * Массив запросов
     * @var array 
     */
    protected $_queries = array();

    public function runStrategy() {
        Helper::initDirForSavedMigrations();
        $exclude = false;
        $datasets = $this->args['datasets'];

        $dshash = '';
        $json = array();
        if (!empty($datasets)) {
            ksort($datasets);
            $json = $this->loadDatasetInfo();
            $dshash = md5(implode('', array_keys($datasets)));
        }

        $classname = sprintf("%s\Schema%s", Helper::get('savedir'), $dshash);
        $fname = DIR . Helper::get('savedir') . DIR_SEP . "Schema{$dshash}.class.php";

        if ($this->askForRewrite($fname)) {
            if (!empty($datasets)) {
                foreach ($json['reqs'] as $dataset) {
                    foreach ($dataset['tables'] as $tablename) {
                        $this->_queries[$tablename] = 1;
                    }
                }
                $exclude = true;
            }

            $schemadir = DIR . Helper::get('schemadir');
            if (!is_dir($schemadir) || !is_readable($schemadir)) {
                throw new \Exception("Директории {$schemadir} с описаниями таблиц не существует\n");
            }

            $handle = opendir($schemadir);
            chdir($schemadir);
            while ($file = readdir($handle)) {
                if ($file != '.' && $file != '..' && is_file($file)) {
                    $tablename = pathinfo($file, PATHINFO_FILENAME);
                    if ($exclude && !isset($this->_queries[$tablename])) {
                        continue;
                    }
                    if (is_readable($file)) {
                        $this->_queries[$tablename] = file_get_contents($file);
                    } else {
                        throw new \Exception("SQL-файла описания таблицы {$tablename} не существует\n");
                    }
                }
            }
            closedir($handle);
            // Создадим структуру базы
            foreach ($this->_queries as $tablename => $query) {
                printf("Разворачиваем таблицу '%s'\n", $tablename);
                $this->db->query($query);
            }
            $this->writeInFile($fname, $dshash);
        } else {
            printf("Разворачиваем схему\n");
            $class = new $classname;
            $class->load($this->db);
        }
    }

    public function _runStrategy() {
        Helper::initDirForSavedMigrations();
        Helper::initVersionTable();

        $db = Helper::getDbObject();
        $result = $db->query('show tables');

        while ($row = $result->fetch_array(MYSQLI_NUM)) {
            $table = $row[0];
            $query = Helper::getSqlForTableCreation($table, $db);
            $this->queries[] = "DROP TABLE IF EXISTS `{$table}`";
            $this->queries[] = $query;
        }
        $vtab = Helper::get('versiontable');
        $res = $db->query("SELECT MAX(rev) FROM `{$vtab}`");
        $row = $res->fetch_array(MYSQLI_NUM);
        $this->queries[] = "INSERT INTO `{$vtab}` SET rev={$row[0]}";
        $this->writeInFile();
    }

    /**
     * TODO: объединить с записью миграции й
     * @param string $tpl 
     */
    protected function writeInFile($fname, $name, $tpl = 'tpl/schema.tpl') {
        $content = file_get_contents(DIR . $tpl);
        $search = array('queries', 'tables', 'name');
        foreach ($search as &$value) {
            $value = '%%' . $value . '%%';
        }
        $sep = "\",\n\"";
        $replace = array(
            '"' . implode($sep, $this->_queries) . '"',
            '"' . implode($sep, array_keys($this->_queries)) . '"',
            $name
        );
        file_put_contents($fname, str_replace($search, $replace, $content));
    }

    protected function askForRewrite($fname) {
        if (!file_exists($fname))
            return true;
        $c = '';
        do {
            if ($c != "\n") {
                printf("Файл схемы %s уже существует. Перезаписать? [y/n] ", $fname);
            }
            $c = trim(fgets(STDIN));
            if ($c === 'Y' or $c === 'y') {
                return true;
            }
            if ($c === 'N' or $c === 'n') {
                return false;
            }
        } while (true);
    }

}
