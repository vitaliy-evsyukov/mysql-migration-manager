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
        // сделать параметр для невыполнения этих запросов
        $this->db->query('SET foreign_key_checks = 0;');
    }

    public function runStrategy() {

        $toWork = array(
            'schema' => array(
                'datasets' => $this->args['datasets'],
                'loadData' => true
            ),
            'migrate' => array(),
            'applyds' => array('datasets' => $this->args['datasets'])
        );
        
        $toWork = array_reverse($toWork);
        
        print_r($toWork);die();
        
        $start = null;
        foreach ($toWork as $controller => $arguments) {
            $start = new ControllersChain($start);
        }
        
        // TODO: собрать в массив
        // Развернем начальную базу
        $schema = Helper::getController('schema', array(
                    'datasets' => $this->args['datasets'],
                    'loadData' => true
                        )
        );
        // Накатим миграции с использованием mysqldiff
        $migrate = Helper::getController('migrate');
        // Разворачиваем данные
        $applyds = Helper::getController('applyds', array('datasets' => $this->args['datasets']));
        $schema->setController($migrate);
        $migrate->setController($applyds);

        $schema->runStrategy();
    }

}

?>
