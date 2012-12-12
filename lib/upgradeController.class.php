<?php

namespace lib;

/**
 * upgradeController
 * Обновляет базу данных до нужной ревизии
 * @author guyfawkes
 */

class upgradeController extends AbstractController
{

    /**
     * Запускает основную операцию контроллера
     */
    public function runStrategy()
    {
        // повторение команды для базы, которую нужно проапгрейдить
        $this->db->setCommand('SET foreign_key_checks = 0');
        // подключение к временной БД
        $dbName = '';
        if ((int)Helper::get('tmp_add_suffix')) {
            $dbName = 'full_temp_db_' . Helper::get('tmp_db_name');
        }
        $db = Helper::getTmpDbObject($dbName);
        // путь для сохранения временной миграции
        $path    = sprintf(
            '%s/%s_temp_migration_%d/',
            sys_get_temp_dir(),
            $dbName,
            time()
        );
        $saveDir = str_replace('\\', '/', Helper::get('savedir_ns'));
        $saveDir = sprintf('%s%s/', $path, $saveDir);
        $pathes  = array($path, $saveDir);
        foreach ($pathes as $p) {
            if (!is_dir($p)) {
                if (!mkdir($p, 0777, true)) {
                    throw new \Exception(
                        sprintf(
                            'Cannot to create directory %s',
                            $path
                        )
                    );
                }
                else {
                    Output::verbose(
                        sprintf('Temporary directory %s created', $p),
                        1
                    );
                }
            }
        }
        set_include_path(
            implode(PATH_SEPARATOR, array(get_include_path(), $path))
        );
        $this->args['overrideRevision'] = true;
        $chain = Helper::getController('deploy', $this->args, $db);
        /**
         * Для апгрейда мы должны считать временную и развернутую базу эталоном,
         * а переданную пользователем мы делаем "временной" и сравниваем
         */
        $create = Helper::getController('create', $this->args, $db);
        $create->getController()->setTempDb($this->db);
        // мигрировать начать нужно с нуля
        $this->args['revision'] = 0;
        $this->args['createSchema'] = false;
        $migrate                = Helper::getController(
            'migrate',
            $this->args,
            $this->db
        );
        $create->setNext($migrate);
        $chain->setNext($create);
        // подменим для контроллеров путь к миграциям
        $tempSave = array('savedir' => $saveDir);
        $chain->setSandbox(
            array(
                 'schema'  => $tempSave,
                 'create'  => $tempSave,
                 // для migrate задаем окружение для индекса 1, т.к. индекс 0 у migrate происходит в пределах deploy
                 'migrate' => array(1 => $tempSave)
            )
        );
        $chain->runStrategy();
    }

}
