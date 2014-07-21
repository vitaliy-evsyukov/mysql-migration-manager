<?php

namespace lib;

/**
 * AbstractSchema
 * Абстрактный класс схем данных
 * @author guyfawkes
 */
abstract class AbstractSchema
{

    const MIGRATED = 'migrated';
    const ORIGINAL = 'original';

    /**
     * Список запросов
     * @var array
     */
    protected $_queries = array();

    /**
     * Список в виде имя сущности => md5-хеш от ее описания
     * @var array
     */
    protected $_tables = array();

    /**
     * Редакция, которой соответствует схема
     * @var int
     */
    protected $_revision = 0;

    /**
     * Разворачивает схему данных
     * @param MysqliHelper $db
     */
    public function load(MysqliHelper $db)
    {
        if ((int) Helper::get('verbose') >= 3) {
            /*
            foreach ($this->queries as $tablename => $query) {
                Output::verbose(sprintf('Executing schema SQL for %s', $tablename), 1);
                $query = stripslashes($query);
                if (!$db->query($query)) {
                    Output::error(sprintf("Error in query \"%s\": %s (%d)\n",
                                    $query, $db->error, $db->errno));
                }
                Output::verbose(sprintf('Completed schema SQL for %s', $tablename), 1);
            }
            */
            Helper::_debug_queryMultipleDDL($db, $this->_queries);
        } else {
            Helper::queryMultipleDDL(
                $db,
                implode("\n", $this->_queries)
            );
        }
    }

    /**
     * Возвращает список таблиц
     * @return array
     */
    public function getTables()
    {
        return array_keys($this->_tables);
    }

    /**
     * Возвращает хеши сущностей
     * @return array
     */
    public function getHashes()
    {
        return $this->_tables;
    }

    /**
     * Возвращает список запросов
     * @return array
     */
    public function getQueries()
    {
        return $this->_queries;
    }

    /**
     * Возвращает номер ревизии схемы
     * @return int
     */
    public function getRevision()
    {
        return (int) $this->_revision;
    }

    /**
     * Загружает файл схемы и проверяет, нужно ли ее перезаписывать
     * @param bool   $migrated  Является ли схема результатом мигрирования
     * @param string $hash      Дополнительные символы в имени (хеш датасета)
     * @param bool   $deploying Должно ли происходить разворачивание файла схемы
     * @return bool
     */
    public static function loadInstance($migrated, $hash, $deploying)
    {
        try {
            /**
             * Если схема не мигрированная - обычные проверки
             * Если схема мигрированная и при этом разворачивается - перезаписывать ее не нужно
             * Если схема мигрированная и при этом не разворачивается - также произведем проверки
             */
            $result = false;
            Output::verbose(
                sprintf(
                    'Schema is%smigrated and is%sdeploying',
                    $migrated ? ' ' : ' not ',
                    $deploying ? ' ' : ' not '
                ),
                3
            );
            if (!$migrated || !$deploying) {
                $className      = Helper::getSchemaClassName($hash, $migrated);
                $class          = new $className;
                $schemaEntities = $class->getTables();
                $data           = Helper::parseSchemaFiles(array(), true);
                $s1             = sizeof($data['queries']);
                $s2             = sizeof($schemaEntities);
                if ($s1 !== $s2) {
                    Output::verbose(
                        sprintf(
                            'List of database entities in folders (length is %d) and schema file (length is %d) are not equal:',
                            $s1,
                            $s2
                        ),
                        2
                    );
                    $result = true;
                } else {
                    $result = array_diff($data['queries'], $schemaEntities);
                    if (!empty($result)) {
                        Output::verbose('There is difference from folders list and schema file\'s list: ', 2);
                        Output::verbose('--- ' . implode("\n--- ", $result), 2);
                    }
                    $result = !empty($result);
                }
                if (!$result) {
                    $schemaHashes = $class->getHashes();
                    foreach ($schemaHashes as $entityName => $hash) {
                        if ($hash !== $data['md5'][$entityName]) {
                            Output::verbose(
                                sprintf(
                                    'There is checksum mismatch for entity %s: schema file\'s hash is %s, but entity file\'s hash is %s',
                                    $entityName,
                                    $hash,
                                    $data['md5'][$entityName]
                                )
                            );
                            $result = true;
                        }
                    }
                }
            }

            return $result;
        } catch (\Exception $e) {
            Output::verbose(
                sprintf("There are some problems with schema file: \n%s", $e->getMessage()),
                3
            );

            return true;
        }
    }

}