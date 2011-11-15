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

        if (!empty($datasets)) {
            $json = $this->loadDatasetInfo();
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

        // Создадим структуру базы, отключив проверки внешних ключей
        !$this->_controller && $this->db->query('SET foreign_key_checks = 0;');
        foreach ($this->_queries as $tablename => $query) {
            echo "$query\n";
            $this->db->query("DROP TABLE IF EXISTS {$tablename};");
            $this->db->query($query);
        }
        !$this->_controller && $this->db->query('SET foreign_key_checks = 1;');
        parent::runStrategy();
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

    protected function writeInFile() {
        $content = "<?php\n" .
                "class Schema extends AbstractSchema\n" .
                "{\n" .
                "  protected \$queries = array(\n";
        foreach ($this->queries as $q) {
            $content .= "    '{$q}',\n";
        }
        $content.="  );\n" .
                "}\n" .
                "\n";
        $fname = Helper::get('savedir') . '/schema.php';
        $this->askForRewrite($fname);
        file_put_contents($fname, $content);
    }

    protected function askForRewrite($fname) {
        if (!file_exists($fname))
            return;
        $c = '';
        do {
            if ($c != "\n")
                echo "File: {$fname} already exists! Can I rewrite it [y/n]? ";
            $c = fread(STDIN, 1);

            if ($c === 'Y' or $c === 'y') {
                return;
            }
            if ($c === 'N' or $c === 'n') {
                echo "\nExit without saving\n";
                exit;
            }
        } while (true);
    }

}
