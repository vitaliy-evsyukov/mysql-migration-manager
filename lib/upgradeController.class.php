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
        // подключение к временной БД
        $db     = Helper::getTmpDbObject(sprintf('full_temp_db_%d', time()));
        $chain  = Helper::getController('deploy', $this->args, $db);
        /**
         * Для апгрейда мы должны считать временную и развернутую базу эталоном,
         * а переданную пользователем мы делаем "временной" и сравниваем
         */
        $create = Helper::getController('create', $this->args, $db);
        $create->getController()->setTempDb($this->db);
        $chain->setNext($create);
        $chain->runStrategy();
    }

}
