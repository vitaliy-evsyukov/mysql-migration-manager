<?php

namespace lib;

abstract class AbstractSchema {

    protected $queries = array();
    protected $tables = array();

    /**
     * Разворачивает схему данных
     * @param Mysqli $db 
     */
    public function load($db) {
        if ((int) Helper::get('verbose') === 3) {
            foreach ($this->queries as $query) {
                $query = stripslashes($query);
                if (!$db->query($query)) {
                    Output::error(sprintf("Error in query \"%s\": %s (%d)\n",
                                    $query, $db->error, $db->errno));
                }
            }
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