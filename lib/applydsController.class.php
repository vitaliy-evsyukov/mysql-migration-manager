<?php

namespace lib;

/**
 * applydsController
 * Применяет указанные датасеты
 * @author guyfawkes
 */
class applydsController extends DatasetsController {

    public function runStrategy() {
        $datasets = $this->args['datasets'];
        if (empty($datasets)) {
            throw new \Exception("Не указаны наборы данных\n");
        }
        
    }

}

?>
