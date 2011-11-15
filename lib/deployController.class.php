<?php

namespace lib;

/**
 * deployController
 * Разворачивает данные с нужными датасетами и накатывает нужные миграции
 * Если миграции не указаны, то накатываются все 
 * Если 
 * @author guyfawkes
 */
class deployController extends DatasetsController {

    private function dropAllTables() {
        $res = $this->db->query('SHOW TABLES;');
        $queries = array();
        while ($row = $res->fetch_array()) {
            $queries[] = "DROP TABLE {$row[0]};";
        }
        $this->db->multi_query(implode('', $queries));
    }

    public function runStrategy() {
        $this->dropAllTables();
        $toWork = array(
            'schema' => array(
                'datasets' => $this->args['datasets'],
                'loadData' => true
            ),
            'migrate' => array(),
            'applyds' => array('datasets' => $this->args['datasets'])
        );
        
        $start = null;
        foreach ($toWork as $controller => $arguments) {
            $start = new ControllersChain($start);
            $controllerName = 'lib\\'.$controller.'Controller';
            $start->setController(new $controllerName($this->db, $arguments));
        }
        $start->runStrategy();
        die();
    }

}

?>
