<?php

namespace lib;

/**
 * deployController
 * Разворачивает данные с нужными датасетами и накатывает нужные миграции
 * Если миграции не указаны, то накатываются все
 * Если датасеты не указаны, то выводится соответствующее сообщение
 * @author guyfawkes
 */

class deployController extends DatasetsController {

    /**
     * Делегирует работу последовательно схеме, миграциям и применению датасетов
     * Удаляет все содержимое БД перед началом работы
     */
    public function runStrategy() {
        $this->dropAllDBEntities();
        $toWork = array(
            'schema'  => array(
                'datasets' => $this->args['datasets'],
                'loadData' => true
            ),
            'migrate' => array(
                'datasets' => $this->args['datasets'],
                'revision' => 0
            ),
            'applyds' => array('datasets' => $this->args['datasets'])
        );
        $toWork = array_reverse($toWork);
        $start  = $this->getChain()->getNext();
        foreach ($toWork as $controller => $arguments) {
            $start          = new ControllersChain($start);
            $controllerName = 'lib\\' . $controller . 'Controller';
            $start->setController(new $controllerName($this->db, $arguments));
        }
        $this->getChain()->setNext($start);
    }

}

?>
