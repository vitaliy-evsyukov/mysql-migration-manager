<?php

namespace lib;


/**
 * createController
 * Создает ревизию и сохраняет ее
 * @author guyfawkes
 */

class createController extends DatasetsController {

    /**
     * Массив запросов
     * @var array
     */
    protected $queries = array();
    /**
     * Объект подключения к временной БД
     * @var \lib\MysqliHelper
     */
    private $_tempDb = null;
    /**
     * Имя и путь к файлу миграции
     * @var string
     */
    private $_migrationFileName = '';

    /**
     * Устанавливает подключение к временной БД
     * @param MysqliHelper $tempDb
     */
    public function setTempDb(MysqliHelper $tempDb) {
        $this->_tempDb = $tempDb;
    }

    /**
     * Создает миграцию, если появились отличия в структуре
     * Если не указано подключение к временной БД, создает самостоятельно
     * Если файл с номером текущей ревизии уже существует, подбирает номера
     * После создания миграции меняет файлы маркера и списка ревизий
     */
    public function runStrategy() {
        if (!$this->_tempDb) {
            $tempDb = Helper::getTmpDbObject();
            Helper::loadTmpDb($tempDb);
        }
        else {
            $tempDb = $this->_tempDb;
        }
        Output::verbose('Starting to search changes', 1);
        $diffObj = new dbDiff($this->db, $tempDb);
        $diff    = $diffObj->getDifference();
        Output::verbose('Search of changes completed', 1);
        if (!empty($diff['up']) || !empty($diff['down'])) {
            $revision    = Helper::getLastRevision();
            $file_exists = true;
            while ($file_exists) {
                $this->_migrationFileName =
                    Helper::get('savedir') . "Migration{$revision}.class.php";
                if (is_file($this->_migrationFileName)) {
                    Output::verbose(
                        sprintf(
                            "Revision # %d already exists, file name: %s",
                            $revision, $this->_migrationFileName
                        ), 2
                    );
                    $revision++;
                }
                else {
                    $file_exists = false;
                }
            }
            Output::verbose(sprintf('Try to create revision %d', $revision), 2);
            $timestamp = Helper::writeRevisionFile($revision);
            $content   = Helper::createMigrationContent(
                $revision, $diff, $timestamp
            );
            file_put_contents($this->_migrationFileName, $content);
            Output::verbose(
                sprintf(
                    "Revision %d successfully created and saved in file %s",
                    $revision, $this->_migrationFileName
                ), 1
            );
            /**
             * Добавилась миграция, нужно пересобрать карты связей и миграций
             * TODO: передавать миграцию и получать только связанные с ней изменения
             */
            Registry::resetAll();
        }
        else {
            Output::verbose(
                'There are no changes in database structure now', 1
            );
        }
    }

    /**
     * Возвращает имя и путь к файлу миграции
     * @return string
     */
    public function getMigrationFileName() {
        return $this->_migrationFileName;
    }

}
