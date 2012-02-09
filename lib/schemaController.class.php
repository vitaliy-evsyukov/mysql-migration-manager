<?php

/**
 * schemaController
 * Создает и/или разворачивает схему данных
 * @author guyfawkes
 */

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
        $json   = array();
        if (!empty($datasets)) {
            ksort($datasets);
            $json   = $this->loadDatasetInfo();
            $dshash = md5(implode('', array_keys($datasets)));
        }

        $classname = sprintf(
            '%s\Schema%s', Helper::get('cachedir_ns'), $dshash
        );
        $fname     = Helper::get('cachedir') . "Schema{$dshash}.class.php";
        $message   = "Schema's file %s already exists. " .
                     "Do you want to override it? [y/n] ";
        if (Helper::askToRewrite($fname, $message)) {
            if (!empty($datasets)) {
                foreach ($json['reqs'] as $dataset) {
                    foreach ($dataset['tables'] as $tablename) {
                        $this->_queries[$tablename] = '1';
                    }
                }
            }
            Output::verbose('Parsing schema files...', 1);
            $this->_queries = Helper::parseSchemaFiles($this->_queries);
            Output::verbose('Parsing finished', 1);
            if (!empty($this->_queries)) {
                Helper::writeInFile($fname, $dshash, $this->_queries);
                // Создадим структуру базы
                Output::verbose('Deploying schema...', 1);
                if ((int) Helper::get('verbose') === 3) {
                    Helper::_debug_queryMultipleDDL($this->db, $this->_queries);
                }
                else {
                    Helper::queryMultipleDDL(
                        $this->db, implode("\n", $this->_queries)
                    );
                }
                Output::verbose('Schema deploy finished', 1);
            }
            else {
                Output::verbose('No tables found. File is not created', 1);
            }
        }
        else {
            Output::verbose('Deploying schema', 1);
            $class = new $classname;
            $class->load($this->db);
            Output::verbose('Schema deploy finished', 1);
        }
        Helper::setCurrentTempDb($this->db);
        Helper::writeRevisionFile(0);
    }

}