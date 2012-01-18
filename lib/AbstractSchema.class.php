<?php

namespace lib;

/**
 * AbstractSchema
 * Абстрактный класс схем данных
 * @author guyfawkes
 */
abstract class AbstractSchema {

    protected $queries = array();
    protected $tables = array();

    /**
     * Разворачивает схему данных
     * @param MysqliHelper $db 
     */
    public function load(MysqliHelper $db) {
        if ((int) Helper::get('verbose') === 3) {
            /*
            foreach ($this->queries as $tablename => $query) {
                Output::verbose(sprintf('Executing schema SQL for %s', $tablename), 1);
                $query = stripslashes($query);
                if (!$db->query($query)) {
                    Output::error(sprintf("Error in query \"%s\": %s (%d)\n",
                                    $query, $db->error, $db->errno));
                }
                Output::verbose(sprintf('Completed schema SQL for %s', $tablename), 1);
            }
            */
            Helper::_debug_queryMultipleDDL($db, $this->queries);
        }
        else {
            Helper::queryMultipleDDL(
                    $db, stripslashes(implode("\n", $this->queries))
            );
        }
    }

    /**
     * Возвращает список таблиц
     * @return array 
     */
    public function getTables() {
        return $this->tables;
    }
    
    /**
     * Возвращает список запросов
     * @return array 
     */
    public function getQueries() {
        return $this->queries;
    }

}