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
        // повторение команды для базы, которую нужно проапгрейдить
        $this->db->setCommand('SET foreign_key_checks = 0');
        // подключение к временной БД
        $db = Helper::getTmpDbObject(sprintf('full_temp_db_%d', time()));
        // путь для сохранения временной миграции
        $path = sprintf('%s_temp_migration_%d', Helper::get('prefix'), time());
        if (!is_dir($path)) {
            if (!mkdir($path, 0777, true)) {
                throw new \Exception(sprintf(
                    'Cannot to create directory %s', $path
                ));
            }
            else {
                Output::verbose(
                    sprintf('Temporary directory %s created', $path), 1
                );
            }
        }
        $chain = Helper::getController('deploy', $this->args, $db);
        /**
         * Для апгрейда мы должны считать временную и развернутую базу эталоном,
         * а переданную пользователем мы делаем "временной" и сравниваем
         */
        $create = Helper::getController('create', $this->args, $db);
        $create->getController()->setTempDb($this->db);
        $create->getController()->setSandbox(array('savedir' => $path));
        $this->args['revision'] = 0;
        $migrate                = Helper::getController(
            'migrate', $this->args, $this->db
        );
        $create->setNext($migrate);
        $chain->setNext($create);
        $chain->runStrategy();

    }

}
