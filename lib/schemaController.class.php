<?php

/**
 * schemaController
 * Создает и/или разворачивает схему данных
 * @author guyfawkes
 */

namespace lib;

class schemaController extends DatasetsController
{

    /**
     * Массив запросов
     * @var array
     */
    protected $_queries = array();

    public function runStrategy()
    {
        $datasets = $this->args['datasets'];
        $revision = 0;
        if (isset($this->args['revision'])) {
            $revision = (int) $this->args['revision'];
        }

        $dshash = '';
        $json   = array();
        if (!empty($datasets)) {
            ksort($datasets);
            $json   = $this->loadDatasetInfo();
            $dshash = md5(implode('', array_keys($datasets)));
            Registry::setHash($dshash);
        }
        if (isset($this->args['excludeDatasets']) && ($this->args['excludeDatasets'] === 0)) {
            $datasets = array();
        }

        if ($revision) {
            $schemaType = AbstractSchema::MIGRATED;
        } else {
            if (!empty($this->args['useOriginalSchema'])) {
                $schemaType = AbstractSchema::ORIGINAL;
            } else {
                $schemaType = null;
            }
        }

        $fname            = Helper::getSchemaFile($dshash, $schemaType);
        $isMigratedSchema = (strpos($fname, 'migrated') !== false);
        $message          = "Schema's file %s already exists. Do you want to override it? [y/n] ";
        $checkFn          = array('\lib\AbstractSchema', 'loadInstance');
        $params           = array($isMigratedSchema, $dshash, empty($this->args['notDeploy']));

        if (Helper::askToRewrite($fname, $message, $checkFn, $params)) {
            if (!empty($datasets)) {
                foreach ($json['reqs'] as $dataset) {
                    foreach ($dataset['tables'] as $tablename) {
                        $this->_queries[$tablename] = '1';
                    }
                }
                Registry::setTablesList($this->_queries);
            }
            Output::verbose('Parsing schema files...', 1);
            $this->_queries = Helper::parseSchemaFiles($this->_queries);
            Output::verbose('Parsing finished', 1);
            if (!empty($this->_queries)) {
                Helper::writeInFile($fname, $dshash, $this->_queries, 'tpl/schema.tpl', $revision);
                if (empty($this->args['notDeploy'])) {
                    $this->_queries = $this->_queries['queries'];
                    // Создадим структуру базы
                    Output::verbose('Deploying schema...', 1);
                    if ((int) Helper::get('verbose') === 3) {
                        Helper::_debug_queryMultipleDDL($this->db, $this->_queries);
                    } else {
                        Helper::queryMultipleDDL(
                            $this->db,
                            implode("\n", $this->_queries)
                        );
                    }
                    Output::verbose('Schema deploy finished', 1);
                }
            } else {
                Output::verbose('No tables found. File is not created', 1);
            }
        } else {
            $classname      = Helper::getSchemaClassName($dshash, $isMigratedSchema);
            $class          = new $classname;
            $schemaRevision = $class->getRevision();
            if ($revision && ($revision !== $schemaRevision)) {
                Helper::changeRevision($fname, $revision);
            } else {
                Output::verbose('Set revision number to ' . $schemaRevision, 3);
                $revision = $schemaRevision;
            }
            if (empty($this->args['notDeploy'])) {
                Output::verbose('Deploying schema', 1);
                $class->load($this->db);
                Output::verbose('Schema deploy finished', 1);
            }
        }
        Helper::setCurrentTempDb($this->db);
        Helper::writeRevisionFile($revision);
    }

}