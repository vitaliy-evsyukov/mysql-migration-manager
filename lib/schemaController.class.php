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

        $classname = sprintf("%s\Schema%s",
                str_replace('/', '\\', Helper::get('cachedir')), $dshash);
        $fname = DIR . Helper::get('cachedir') . DIR_SEP . "Schema{$dshash}.class.php";

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
                throw new \Exception(
                        sprintf('Directory %s with tables definitions does not exists',
                                $schemadir)
                );
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
                    }
                    else {
                        throw new \Exception(
                                sprintf("SQL-file with %s descriptions does not exists",
                                        $tablename)
                        );
                    }
                }
            }
            closedir($handle);
            // Создадим структуру базы
            Output::verbose('Deploy tables...', 1);
            foreach ($this->_queries as $tablename => $query) {
                Output::verbose(sprintf("Deploy table '%s'", $tablename), 2);
                $this->db->query($query);
            }
            Output::verbose('Tables deploy finished', 1);
            $this->writeInFile($fname, $dshash);
        }
        else {
            Output::verbose('Deploy schema', 1);
            $class = new $classname;
            $class->load($this->db);
            Output::verbose('Schema deploy finished', 1);
        }
    }

    /**
     * TODO: объединить с записью миграциий
     * @param string $tpl 
     */
    protected function writeInFile($fname, $name, $tpl = 'tpl/schema.tpl') {
        $content = file_get_contents(DIR . $tpl);
        $search = array('queries', 'tables', 'name', 'ns');
        foreach ($search as &$value) {
            $value = '%%' . $value . '%%';
        }
        $sep = "\",\n\"";
        $replace = array(
            '"' . implode($sep, $this->_queries) . '"',
            '"' . implode($sep, array_keys($this->_queries)) . '"',
            $name,
            str_replace('/', '\\', Helper::get('cachedir'))
        );
        file_put_contents($fname, str_replace($search, $replace, $content));
    }

    protected function askForRewrite($fname) {
        if (Helper::get('quiet') || !file_exists($fname))
            return true;
        $c = '';
        do {
            if ($c != "\n") {
                printf("Schema's file %s already exists. Do you want to override it? [y/n] ",
                        $fname);
            }
            $c = trim(fgets(STDIN));
            if ($c === 'Y' or $c === 'y') {
                return true;
            }
            if ($c === 'N' or $c === 'n') {
                return false;
            }
        }
        while (true);
    }

}
