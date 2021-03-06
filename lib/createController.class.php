<?php

namespace lib;

use lib\Helper\Writer\Migration;


/**
 * createController
 * Создает ревизию и сохраняет ее
 * @author Виталий Евсюков
 */
class createController extends DatasetsController
{

    /**
     * Массив запросов
     * @var array
     */
    protected $queries = array();
    /**
     * Объект подключения к временной БД
     * @var \lib\MysqliHelper
     */
    private $tempDb = null;
    /**
     * Имя и путь к файлу миграции
     * @var string
     */
    private $migrationFileName = '';

    /**
     * Устанавливает подключение к временной БД
     * @param MysqliHelper $tempDb
     */
    public function setTempDb(MysqliHelper $tempDb)
    {
        $this->tempDb = $tempDb;
    }

    /**
     * Создает миграцию, если появились отличия в структуре
     * Если не указано подключение к временной БД, создает самостоятельно
     * Если файл с номером текущей ревизии уже существует, подбирает номера
     * После создания миграции меняет файлы маркера и списка ревизий
     */
    public function runStrategy()
    {
        $filesystemHelper   = $this->container->getFileSystem();
        $migratedSchema     = $this->container->getSchema()->getSchemaClassName('', true);
        $migratedSchemaFile = $this->container->getFileSystem()->getSchemaFile('', AbstractSchema::MIGRATED);
        if (is_file($migratedSchemaFile) && is_readable($migratedSchemaFile)) {
            /**
             * @var AbstractSchema $migratedSchemaObj
             */
            $migratedSchemaObj = new $migratedSchema;
            $migratedRevision  = $migratedSchemaObj->getRevision();
            $backupFileName    = sprintf('%s_%d.backup', $migratedSchemaFile, $migratedRevision);
            $this->verbose(sprintf('Backuped %s as %s', $migratedSchemaFile, $backupFileName), 1);
            copy($migratedSchemaFile, $backupFileName);
        }
        if (!$this->tempDb) {
            $dbHelper = $this->container->getDb();
            $tempDb   = $dbHelper->getTmpDbObject();
            $dbHelper->loadTmpDb($tempDb);
        } else {
            $tempDb = $this->tempDb;
        }
        $this->verbose('Starting to search changes', 1);
        $diffObj = new DbDiff($this->container->getInit()->get('mysqldiff_command'), $this->db, $tempDb);
        $diffObj->setOutput($this->container->getOutput());
        $diff = $diffObj->getDiff();
        $this->verbose('Search of changes completed', 1);
        if (!empty($diff['up']) || !empty($diff['down'])) {
            $revision   = $filesystemHelper->getLastRevision();
            $fileExists = true;
            $initHelper = $this->container->getInit();
            while ($fileExists) {
                $this->migrationFileName = $initHelper->get('savedir') . "Migration{$revision}.class.php";
                if (is_file($this->migrationFileName)) {
                    $this->verbose(
                        sprintf(
                            "Revision # %d already exists, file name: %s",
                            $revision,
                            $this->migrationFileName
                        ),
                        2
                    );
                    $revision++;
                } else {
                    $fileExists = false;
                }
            }
            $this->verbose(sprintf('Try to create revision %d', $revision), 2);
            $timestamp = $filesystemHelper->writeRevisionFile($revision);
            $migration = new Migration($diff, $revision, $timestamp);
            $filesystemHelper->writeInFile($this->migrationFileName, $migration);
            $this->verbose(
                sprintf(
                    "Revision %d successfully created and saved in file %s",
                    $revision,
                    $this->migrationFileName
                ),
                1
            );
        } else {
            $this->verbose(
                'There are no changes in database structure now',
                1
            );
        }
        /**
         * Добавилась миграция, нужно пересобрать карты связей и миграций
         * Если миграция не была добавлена, то карты связей и миграций должны быть в любом случае обнулены, т.к.
         * при использовании датасетов последняя связанная с ними миграция может быть не последней в списке, и поэтому
         * при вызове migrate-контроллера он будет накатывать все оставшиеся миграции
         */
        $this->container->getMigrations()->resetAll();
    }

    /**
     * Возвращает имя и путь к файлу миграции
     * @return string
     */
    public function getMigrationFileName()
    {
        return $this->migrationFileName;
    }
}
