<?php

namespace lib;

class upgradeController extends AbstractController {
    /**
     * Запускает основную операцию контроллера
     */
    public function runStrategy() {
        $db = Helper::getTmpDbObject(sprintf('full_temp_db_%d', time()));
        $chain = Helper::getController('deploy', array(), $db);
        $createController = new createController($this->db);
        $createController->setTempDb($db);
        $chain = new ControllersChain($chain);
        $chain->setController($createController);
        $chain->runStrategy();
    }

}
