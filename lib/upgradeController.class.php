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
        $path    = sprintf(
            '%s/%s_temp_migration_%d/', sys_get_temp_dir(),
            Helper::get('prefix'), time()
        );
        $saveDir = str_replace('\\', '/', Helper::get('savedir_ns'));
        $saveDir = sprintf('%s%s/', $path, $saveDir);
        $pathes  = array($path, $saveDir);
        foreach ($pathes as $p) {
            if (!is_dir($p)) {
                if (!mkdir($p, 0777, true)) {
                    throw new \Exception(sprintf(
                        'Cannot to create directory %s', $path
                    ));
                }
                else {
                    Output::verbose(
                        sprintf('Temporary directory %s created', $p), 1
                    );
                }
            }
        }
        set_include_path(
            implode(PATH_SEPARATOR, array(get_include_path(), $path))
        );
        $chain = Helper::getController('deploy', $this->args, $db);
        /**
         * Для апгрейда мы должны считать временную и развернутую базу эталоном,
         * а переданную пользователем мы делаем "временной" и сравниваем
         */
        $create = Helper::getController('create', $this->args, $db);
        $create->getController()->setTempDb($this->db);
        // мигрировать начать нужно с нуля
        $this->args['revision'] = 0;
        $migrate                = Helper::getController(
            'migrate', $this->args, $this->db
        );
        $create->setNext($migrate);
        $chain->setNext($create);
        // подменим для контроллеров путь к миграциям
        $tempSave = array('savedir' => $saveDir);
        $chain->setSandbox(
            array(
                 'schema'  => $tempSave,
                 'create'  => $tempSave,
                 'migrate' => array(1 => $tempSave)
            )
        );
        $chain->runStrategy();

    }

}
