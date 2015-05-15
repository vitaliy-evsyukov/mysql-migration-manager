<?php

namespace lib\Helper;

use lib\AbstractSchema;

/**
 * Schema
 *
 * @author  Виталий Евсюков
 * @package lib\Helper
 */
class Schema extends Helper
{
    /**
     * Тип оператора - создание таблицы
     */
    const TABLE_TYPE = 'TABLE';

    /**
     * Тип оператора - создание вьюхи
     */
    const VIEW_TYPE = 'VIEW';

    /**
     * Тип оператора - создание триггера
     */
    const TRIGGER_TYPE = 'TRIGGER';

    /**
     * Тип оператора - создание хранимой процедуры
     */
    const PROCEDURE_TYPE = 'PROCEDURE';

    /**
     * Тип оператора - создание функции
     */
    const FUNCTION_TYPE = 'FUNCTION';

    /**
     * Вспомогательные действия с файлами схемы
     * @static
     * @param array  $queries       Ссылка на массив запросов
     * @param array  $views         Ссылка на массив запросов для вьюх
     * @param array  $includeTables Массив таблиц, которые нужно использовать
     * @param string $file          Имя файла
     * @param array  $md5           Ссылка на массив хешей
     * @param bool   $listOnly      Отдать только названия сущностей и хеши
     * @return bool
     */
    private function schemaFileRoutines(
        array &$queries,
        array &$views,
        array &$md5,
        array $includeTables,
        $file,
        $listOnly
    )
    {
        $exclude = !empty($includeTables);
        // если файл - получим данные о его имени
        $fileInfo = pathinfo($file);
        // если это SQL-файл, заберем его содержимое
        if (strcasecmp($fileInfo['extension'], 'sql') === 0) {
            $entityname = $fileInfo['filename'];
            $this->output(
                sprintf(
                    '--- Get content for %s',
                    $entityname
                ),
                3
            );
            if ($exclude && !isset($includeTables[$entityname])) {
                return false;
            }
            $q = file_get_contents($file);
            if ($q === ';') {
                return false;
            }
            $md5[$entityname] = md5($q);
            if ($listOnly) {
                $queries[] = $entityname;
            } else {
                $tmp = array($entityname => $q);
                $inf = $this->getStatementInfo($q);
                if ($inf['type'] === self::TABLE_TYPE) {
                    $tmp[$entityname] = $this->stripTrash($q, self::TABLE_TYPE);
                    /*
                     * сложение необходимо для сохранения ключей массивов
                     * таблицы добавляем в начало массива
                     */
                    $queries = $tmp + $queries;
                } else {
                    if ($inf['type'] === self::VIEW_TYPE) {
                        $viewEntityName       = $entityname . '_view';
                        $tmp[$viewEntityName] = $this->stripTrash(
                            $q,
                            self::VIEW_TYPE,
                            array('definer' => $inf['definer'])
                        );
                        $tmp[$viewEntityName] = sprintf(
                            "DROP TABLE IF EXISTS %s;\n%s",
                            $entityname,
                            $tmp[$viewEntityName]
                        );
                        /**
                         * дописываем вьюхи в отдельный массив,
                         * который добавим в конец всего
                         */
                        $views += $tmp;
                    } else {
                        /**
                         *  Если это триггер, процедура или функция, меняем создателя
                         */
                        if (in_array($inf['type'], [self::TRIGGER_TYPE, self::PROCEDURE_TYPE, self::FUNCTION_TYPE])) {
                            $tmp[$entityname] = $this->stripTrash(
                                $q,
                                $inf['type'],
                                array(
                                    'definer' => $inf['definer'],
                                    'entity'  => $entityname
                                )
                            );
                            // и дописываем такие сущности в конец массива запросов
                            $queries += $tmp;
                        }
                    }
                }
            }
        }

        return true;
    }

    /**
     * Проходит по папке с файлами схемы и собирает их
     * @param array $includeTables Хеш с именами таблиц, которые нужно включать, в качестве ключей
     * @param bool  $listOnly      Нужно ли получить только список сущностей и их хеши
     * @return array Массив запросов
     */
    public function parseSchemaFiles(array $includeTables = array(), $listOnly = false)
    {
        $md5       = array();
        $queries   = array();
        $views     = array();
        $schemadir = $this->get('schemadir');
        if (!is_dir($schemadir) || !is_readable($schemadir)) {
            $this->output(
                sprintf('There are no schema files in %s', $schemadir),
                1
            );
        } else {
            $dirs = array($schemadir);
            while (true) {
                $files = $this->container->getFileSystem()->iterateDirectories($dirs);
                if ($files === false) {
                    break;
                }
                foreach ($files as $file) {
                    if (!$this->schemaFileRoutines(
                        $queries,
                        $views,
                        $md5,
                        $includeTables,
                        $file,
                        $listOnly
                    )
                    ) {
                        $this->output(sprintf('File %s was not processed', $file));
                    }
                }
            }
        }
        /**
         * вьюхи идут после хранимых процедур, функций и триггеров,
         * которые в свою очередь идут после таблиц
         */
        $queries += $views;

        // избавимся от возможных дублирующихся названий, т.к. могли быть собраны такие для временных таблиц для вьюх
        if ($listOnly) {
            $queries = array_unique($queries);
        }

        return array('queries' => $queries, 'md5' => $md5);
    }

    /**
     * Удаляет мусор из определений сущностей
     * @param string $content Описание сущности
     * @param string $type    Тип сущности
     * @param array  $extra   Массив дополнительной информации (ключи definer и entity)
     * @return string
     */
    public function stripTrash($content, $type, array $extra = array())
    {
        $search  = array();
        $replace = array();
        switch ($type) {
            case self::TABLE_TYPE:
                $search[] = 'AUTO_INCREMENT';
                if (preg_match('/\s*ENGINE=InnoDB\s*/ims', $content)) {
                    $search = array_merge(
                        array(
                            'CHECKSUM',
                            'AVG_ROW_LENGTH',
                            'DELAY_KEY_WRITE',
                            'ROW_FORMAT'
                        ),
                        $search
                    );
                }
                foreach ($search as $index => &$value) {
                    $pattern = "/ {$value}=\\w+/ims";
                    if (preg_match($pattern, $content, $m)) {
                        $value     = $m[0];
                        $replace[] = '';
                    } else {
                        unset($search[$index]);
                    }
                }
                if (!preg_match('/\s*IF\s+NOT\s+EXISTS\s+/ims', $content)) {
                    $search[]  = 'CREATE TABLE';
                    $replace[] = 'CREATE TABLE IF NOT EXISTS';
                }
                break;
            case self::VIEW_TYPE:
                if (!preg_match('/\s*OR\s+REPLACE\s+/ims', $content)) {
                    $search[]  = 'CREATE ';
                    $replace[] = 'CREATE OR REPLACE ';
                }
                break;
            default:
                if (!in_array($type, array('TRIGGER', 'FUNCTION', 'PROCEDURE'))) {
                    $type = $this->getStatementInfo($content)['type'];
                }
                if (!is_null($type)) {
                    $replaceString = null;
                    if (!preg_match('/DROP\s+(?:TRIGGER|FUNCTION|PROCEDURE)\s+/ims', $content)) {
                        $replaceString = "DROP %s IF EXISTS %s;\n%s\n";
                    }
                    if (!preg_match('/DELIMITER ;;/ims', $content)) {
                        $replaceString = "DROP %s IF EXISTS %s;\nDELIMITER ;;\n%s\nDELIMITER ;\n";
                    }
                    if (!is_null($replaceString)) {
                        $search[]  = $content;
                        $replace[] = sprintf(
                            $replaceString,
                            $type,
                            $extra['entity'],
                            $content
                        );
                    }
                }
                break;
        }

        $db                 = $this->container->getDb()->getCurrentDb();
        $needReplace        = true;
        $definerReplacement = null;
        if ($db) {
            $needReplace = !$db->isTemporary();
        } else {
            $this->output('NO current database setted', 3);
        }
        if ($needReplace) {
            $definerReplacement = trim((string) $this->get('routine_user'));
        }
        if (empty($definerReplacement)) {
            $definerReplacement = 'CURRENT_USER';
        }
        $this->output(
            sprintf(
                'Definers in %s will be replaced to %s',
                $db ? $db->getDatabaseName() : '<not defined>',
                $definerReplacement
            ),
            3
        );
        if (isset($extra['definer'])) {
            $search[]  = $extra['definer'];
            $replace[] = $definerReplacement;
        } else {
            if (preg_match('/DEFINER=(.*?)\s+/ims', $content, $m)) {
                $search[]  = $m[1];
                $replace[] = $definerReplacement;
            }
        }

        if (!empty($search)) {
            $content = str_replace($search, $replace, $content);
        }

        return $content;
    }


    /**
     * Возвращает данные оператора
     * @param string $statement
     * @param string $default
     * @return array
     */
    private function getStatementInfo($statement, $default = null)
    {
        $patternTable   = '/^\s*CREATE\s+TABLE\s+/ims';
        $patternView    = '/^CREATE(?:(?:.*?)\s+ALGORITHM=(?:.*?))?(?:\s+DEFINER=(.*?))?' .
            '(?:\s+SQL\s+SECURITY\s+(?:DEFINER|INVOKER))?\s+VIEW\s+(?:.*?)\s+(?:\(.*?\)\s+)' .
            '?AS\s+\(?(.*?)\)?\s*(?:WITH\s+(?:.*?))?;$/';
        $patternRoutine = '/^\s*CREATE\s+(?:.*\s+)?(?:DEFINER=(.*?))?\s+(?:.*\s+)?(TRIGGER|FUNCTION|PROCEDURE)/im';
        $patterns       = [
            $patternTable   => ['type' => self::TABLE_TYPE, 'definer' => null],
            $patternView    => ['type' => self::VIEW_TYPE, 'definer' => 1],
            $patternRoutine => ['type' => 2, 'definer' => 1]
        ];
        $statementType  = $default;
        $definerName    = $default;
        foreach ($patterns as $pattern => $captures) {
            if (preg_match($pattern, $statement, $matches)) {
                if (is_int($captures['type'])) {
                    $statementType = $matches[$captures['type']];
                } else {
                    $statementType = $captures['type'];
                }
                if (is_int($captures['definer'])) {
                    $definerName = $matches[$captures['definer']];
                } else {
                    $definerName = $captures['definer'];
                }
                break;
            }
        }
        return ['type' => $statementType, 'definer' => $definerName];
    }

    /**
     * Возвращает имя класса схемы
     * @param string $hash
     * @param bool   $migrated
     * @return \lib\AbstractSchema
     */
    public function getSchemaClassName($hash, $migrated)
    {
        return sprintf(
            '%s\Schema%s%s',
            $this->get('cachedir_ns'),
            (string) $hash,
            (bool) $migrated ? 'migrated' : ''
        );
    }

    /**
     * Загружает файл схемы и проверяет, нужно ли ее перезаписывать
     * @param bool   $migrated  Является ли схема результатом мигрирования
     * @param string $hash      Дополнительные символы в имени (хеш датасета)
     * @param bool   $deploying Должно ли происходить разворачивание файла схемы
     * @return bool
     */
    public function loadInstance($migrated, $hash, $deploying)
    {
        try {
            /**
             * Если схема не мигрированная - обычные проверки
             * Если схема мигрированная и при этом разворачивается - перезаписывать ее не нужно
             * Если схема мигрированная и при этом не разворачивается - также произведем проверки
             */
            $result = false;
            $this->output(
                sprintf(
                    'Schema is%smigrated and is%sdeploying',
                    $migrated ? ' ' : ' not ',
                    $deploying ? ' ' : ' not '
                ),
                3
            );
            if (!$migrated || !$deploying) {
                $className = $this->getSchemaClassName($hash, $migrated);
                /**
                 * @var AbstractSchema $class
                 */
                $class          = new $className;
                $schemaEntities = $class->getTables();
                $data           = $this->parseSchemaFiles(array(), true);
                $s1             = sizeof($data['queries']);
                $s2             = sizeof($schemaEntities);
                if ($s1 !== $s2) {
                    $this->output(
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
                        $this->output('There is difference from folders list and schema file\'s list: ', 2);
                        $this->output('--- ' . implode("\n--- ", $result), 2);
                    }
                    $result = !empty($result);
                }
                if (!$result) {
                    $schemaHashes = $class->getHashes();
                    foreach ($schemaHashes as $entityName => $hash) {
                        if ($hash !== $data['md5'][$entityName]) {
                            $this->output(
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
            $this->output(
                sprintf("There are some problems with schema file: \n%s", $e->getMessage()),
                3
            );

            return true;
        }
    }

    /**
     * Заменяет ревизию в файле схемы
     * @param string $filename
     * @param int    $revision
     */
    public function changeRevision($filename, $revision)
    {
        if (is_file($filename) && is_readable($filename) && is_writable($filename)) {
            $this->output(sprintf('Change revision in schema %s to %d', $filename, $revision), 2);
            $content = file_get_contents($filename);
            $content = preg_replace('/(\$_revision\s=\s)(\d+)?;/', '${1}' . $revision . ';', $content);
            file_put_contents($filename, $content);
        }
    }
}