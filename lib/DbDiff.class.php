<?php

namespace lib;

use lib\Helper\Output;

/**
 * dbDiff
 * Получает и парсит выдачу mysqldiff
 * @author Виталий Евсюков
 */
class DbDiff
{
    /**
     * Эталонная БД
     * @var MysqliHelper
     */
    private $currentAdapter;

    /**
     * Временная БД
     * @var MysqliHelper
     */
    private $tempAdapter;

    /**
     * Эталонная схема
     * @var string
     */
    private $currentSchema;

    /**
     * Временная схема
     * @var string
     */
    private $tempSchema;

    /**
     * Строка запуска mysqldiff
     * @var string
     */
    private $diffCommand;

    /**
     * Инстанс принтера на экран
     * @var Output
     */
    private $output;

    /**
     * Запросы в обе стороны, связанные и использованные таблицы
     * @var array
     */
    private $diff = array(
        'up'     => array(),
        'down'   => array(),
        'tables' => array(
            'used' => array(),
            'refs' => array()
        )
    );

    /**
     * Создает экземпляр класса dbDiff для двух соединений
     * @param string       $diffCommand Команда mysqldiff
     * @param MysqliHelper $current     Соединение с текущей БД
     * @param MysqliHelper $temp        Соединение с временной БД
     */
    public function __construct(
        $diffCommand,
        MysqliHelper $current = null,
        MysqliHelper $temp = null
    )
    {
        $current && ($this->currentSchema = $current->getDatabaseName());
        $temp && ($this->tempSchema = $temp->getDatabaseName());
        $this->currentAdapter = $current;
        $this->tempAdapter    = $temp;
        $this->diffCommand    = $diffCommand;
    }

    public function setOutput(Output $output)
    {
        $this->output = $output;
    }

    /**
     * Добавляет описание связи
     * @param string $tableName       Описываемая таблица
     * @param string $referencedTable Связанная с ней
     */
    private function addReferenced($tableName, $referencedTable)
    {
        $this->diff['tables']['refs'][$tableName][$referencedTable] = 1;
    }

    /**
     * Помечает таблицу как использованную
     * @param string $tableName
     */
    private function markUsed($tableName)
    {
        $this->diff['tables']['used'][$tableName] = 1;
    }

    /**
     * Парсит вывод mysqldiff, составляет список использованных и неиспользованных таблиц
     * @param array $output Вывод mysqldiff
     * @return array
     */
    public function parseDiff(array $output = array())
    {
        $comment     = '';
        $tableName   = '';
        $refTable    = '';
        $refPosition = '';
        $tmp         = array();
        $result      = array();
        $referenced  = array();
        $index       = 0;
        $rIndex      = 0;
        $endMarker   = '-- }';
        $actionType  = '';
        foreach ($output as $line) {
            // если строка состоит только из whitespace'ов
            if (ctype_space($line)) {
                continue;
            }
            if (strpos($line, '--') === 0) {
                // это комментарий с информацией о запросе
                $comment .= str_replace('`', '', substr($line, 2));
                if (strpos($line, $endMarker) === 0) {
                    // это конец комментария
                    try {
                        $data    = json_decode($comment);
                        $comment = '';
                        if ($data) {
                            $refPosition = '';
                            $refTable    = '';
                            $tableName   = $data->name;
                            $actionType  = $data->action_type;
                            if (isset($data->referenced_tables) && is_array($data->referenced_tables)) {
                                // множество зависимых таблиц
                                foreach ($data->referenced_tables as $table) {
                                    $this->addReferenced($tableName, $table);
                                }
                            }
                            if (preg_match('/^change_fk_(after|before)_(.*?)$/', $actionType, $matched)) {
                                $refPosition = $matched[1];
                                $refTable    = $matched[2];
                                $this->markUsed($refTable);
                            }
                            $this->markUsed($tableName);
                            $tmp    = array();
                            $index  = 0;
                            $rIndex = 0;
                            if (isset($result['desc'][$tableName])) {
                                $index = sizeof($result['desc'][$tableName]);
                            }
                            if (isset($referenced[$tableName][$refTable][$refPosition])) {
                                $rIndex = sizeof($referenced[$tableName][$refTable][$refPosition]);
                            }
                        }
                    } catch (\Exception $e) {
                        if ($this->output) {
                            $this->output->error($e->getMessage());
                        }
                        $tableName = '';
                    }
                }
            } else {
                $tmp[] = $line;
                if (!empty($tableName)) {
                    $statements = trim(implode("\n", $tmp));
                    if (!empty($refTable)) {
                        $referenced[$tableName][$refTable][$refPosition][$rIndex] = $statements;
                    } else {
                        /*
                         * добавим предыдущие собранные данные в результирующий массив
                         * делать это необходимо здесь, поскольку иначе нужна
                         * дополнительная проверка, достигнут ли конец массива,
                         * и "неиспользованный" остаток все равно придется добавить
                         * к последнему обнаруженному комментарию с именем таблицы
                         */
                        $result['desc'][$tableName][$index] = array(
                            'type' => $actionType,
                            'sql'  => $statements
                        );
                    }
                }
            }
        }

        if (!empty($referenced)) {
            foreach ($referenced as $tableName => $references) {
                foreach ($references as $refTable => $statements) {
                    $this->verbose(
                        sprintf('Append referenced statements to %s from %s', $refTable, $tableName),
                        3
                    );
                    if (isset($statements['after'])) {
                        $result['desc'][$refTable][] = array(
                            'type' => 'change_fk',
                            'sql'  => implode("\n", $statements['after'])
                        );
                    }
                    if (isset($statements['before'])) {
                        if (!isset($result['desc'][$refTable])) {
                            $result['desc'][$refTable] = array();
                        }
                        array_unshift(
                            $result['desc'][$refTable],
                            array(
                                'type' => 'change_fk',
                                'sql'  => implode("\n", $statements['before'])
                            )
                        );
                    }
                }
            }
        }

        return $result;
    }

    /**
     * Возвращает настройки адаптера в виде массива параметров для mysqldiff
     * @param MysqliHelper $adapter
     * @param string       $suffix
     * @return array
     */
    private function getAdapterParameters(MysqliHelper $adapter, $suffix = '')
    {
        $params     = array('host', 'user', 'password', 'port');
        $parameters = array();
        foreach ($params as $param) {
            if ($adapter) {
                $methodName = 'get' . ucfirst($param);
                $value      = $adapter->{$methodName}();
                if (!empty($value)) {
                    $parameters[] = "--{$param}{$suffix}={$value}";
                }
            }
        }
        return $parameters;
    }

    /**
     * Возвращает данные о связях таблиц
     * @param MysqliHelper $tempAdapter
     * @return array
     * @throws \Exception
     */
    public function fetchReferences(MysqliHelper $tempAdapter)
    {
        $start      = microtime(1);
        $parameters = $this->getAdapterParameters($tempAdapter);
        $command    = sprintf(
            "%s %s  --no-old-defs --refs %s",
            $this->diffCommand,
            implode(' ', $parameters),
            $tempAdapter->getDatabaseName()
        );
        $this->verbose(sprintf('Executing command %s', $command), 3);
        $output = array();
        $status = -1;
        exec($command, $output, $status);
        if ($status) {
            throw new \Exception("Cannot get references:\n" . implode("\n", $output));
        }
        $this->verbose(
            sprintf(
                'References search completed in: %f seconds',
                (microtime(1) - $start)
            ),
            3
        );
        $result = array();
        if (!empty($output)) {
            $this->parseDiff($output);
            $result = $this->getTablesInfo();
            $result = $result['refs'];
        }

        return $result;
    }

    /**
     * Делегирует работу mysqldiff и возвращает различия между двумя базами
     * @throws \Exception
     * @return array
     */
    public function getDiff()
    {
        $groups   = array(1, 2);
        $schemas  = array($this->currentSchema, $this->tempSchema);
        $adapters = array($this->currentAdapter, $this->tempAdapter);
        $dirs     = array('down', 'up');


        for ($i = 0; $i < 2; $i++) {
            $parameters = array();
            foreach ($groups as $index => $k) {
                $adapter = ($adapters[$index] instanceof MysqliHelper) ? $adapters[$index] : null;
                if ($adapter) {
                    $parameters = array_merge($parameters, $this->getAdapterParameters($adapter, $k));
                }
            }
            $command = sprintf(
                '%s %s',
                $this->diffCommand,
                implode(' ', $parameters)
            );
            $output  = array();
            $full    = sprintf(
                '%s --list-tables --no-old-defs --save-quotes %s %s',
                $command,
                $schemas[$i],
                $schemas[1 - $i]
            );
            $this->verbose("Command {$full}", 2);
            exec($full, $output, $return_status);
            if ($return_status) {
                throw new \Exception("Cannot get differences:\n" . implode("\n", $output));
            }
            if (!empty($output)) {
                $result                = $this->parseDiff($output);
                $this->diff[$dirs[$i]] = $result['desc'];
            } else {
                $this->verbose(
                    sprintf('Command %s returned nothing', $full),
                    3
                );
            }
            $adapters = array_reverse($adapters);
        }

        return $this->diff;
    }

    /**
     * Возвращает информацию об использованных и ссылающихся таблицах
     * Результат - хеш из ключей used и refs
     * @return array
     */
    public function getTablesInfo()
    {
        return $this->diff['tables'];
    }

    /**
     * Выводит сообщение на экран
     * @param string $message
     * @param int    $level
     */
    private function verbose($message, $level)
    {
        if ($this->output) {
            $this->output->verbose($message, $level);
        }
    }
}

