<?php

namespace lib;

class schemaController extends DatasetsController {

    /**
     * Массив запросов
     * @var array 
     */
    protected $_queries = array();

    public function runStrategy() {
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
                        $this->_queries[$tablename] = '1';
                    }
                }
            }
            $this->_queries = Helper::parseSchemaFiles($this->_queries);
            if (!empty($this->_queries)) {
                // Создадим структуру базы
                Output::verbose('Deploy tables...', 1);
                foreach ($this->_queries as $tablename => $query) {
                    Output::verbose(sprintf("Deploy table '%s'", $tablename), 2);
                    $this->db->query(stripslashes($query));
                }
                Output::verbose('Tables deploy finished', 1);
                $this->writeInFile($fname, $dshash);
            }
            else {
                Output::verbose('No tables found. File not created', 1);
            }
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
        $tpl_file = DIR . $tpl;
        if (file_exists($tpl_file)) {
            $content = file_get_contents($tpl_file);
        }
        else {
            throw new \Exception(
                    sprintf('Template file %s not exists', $tpl_file)
            );
        }
        $search = array('queries', 'tables', 'name', 'ns');
        foreach ($search as &$value) {
            $value = '%%' . $value . '%%';
        }
        $sep = "\",\n".str_repeat(' ', 8).'"';
        $replace = array(
            '"' . implode($sep, $this->_queries) . '"',
            '"' . implode($sep, array_keys($this->_queries)) . '"',
            $name,
            str_replace('/', '\\', Helper::get('cachedir'))
        );
        if (!file_exists($fname) || is_writable($fname)) {
            file_put_contents($fname, str_replace($search, $replace, $content));
        }
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
