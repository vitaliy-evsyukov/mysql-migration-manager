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
        // TODO: собрать в массив
        // Развернем начальную базу
        $schema = Helper::getController('schema', 
                                        array(
                                            'datasets' => $this->args['datasets'],
                                            'loadData' => true
                                        )
        );
        $schema->runStrategy();
        // Накатим миграции с использованием mysqldiff
        $migrate = Helper::getController('migrate');
        $migrate->runStrategy();
        // Разворачиваем данные
        $applyds = Helper::getController('applyds', array('datasets' => $this->args['datasets']));
        $applyds->runStrategy();
    }

}

?>
