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
        foreach ($this->queries as $query) {
            $query = stripslashes($query);
            if (!$db->query($query)) {
                throw new \Exception(sprintf("Error in query \"%s\": %s (%d)\n", $query, $db->error, $db->errno));
            }
        }
    }
    
    /**
     * Возвращает список таблиц
     * @return array 
     */
    public function getTables() {
        return $this->tables;
    }

}