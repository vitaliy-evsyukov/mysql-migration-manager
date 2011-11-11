<?php

namespace lib;

abstract class AbstractSchema {

    /**
     * Разворачивает схему данных
     * @param type $db 
     */
    public function load($db) {
        foreach ($this->queries as $query) {
            Output::verbose($query);
            if (!$db->query($query)) {
                Output::verbose("Fail\n{$db->error}");
            }
        }
    }

}