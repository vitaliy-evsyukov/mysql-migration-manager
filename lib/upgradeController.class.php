<?php

namespace lib;

/**
 * upgradeController
 * Обновляет базу данных до нужной ревизии
 * @author guyfawkes
 */

class upgradeController extends AbstractController {

    /**
     * Запускает основную операцию контроллера
     */
    public function runStrategy() {
        $db     = Helper::getTmpDbObject(sprintf('full_temp_db_%d', time()));
        $chain  = Helper::getController('deploy', $this->args, $db);
        $create = new createController($this->db, $this->args);
        $create->setTempDb($db);
        $chain = new ControllersChain($chain);
        $chain->setController($create);
        $chain->runStrategy();
    }

}
