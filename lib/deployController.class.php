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

    public function runStrategy() {

        $this->dropAllTables();
        $toWork = array(
            'schema' => array(
                'datasets' => $this->args['datasets'],
                'loadData' => true
            ),
            'migrate' => array(
                'datasets' => $this->args['datasets'],
                'revision' => 0
            ),
            'applyds' => array('datasets' => $this->args['datasets'])
        );

        $start = null;
        foreach ($toWork as $controller => $arguments) {
            $start = new ControllersChain($start);
            $controllerName = 'lib\\' . $controller . 'Controller';
            $start->setController(new $controllerName($this->db, $arguments));
        }
        $start->runStrategy();
    }

}

?>
