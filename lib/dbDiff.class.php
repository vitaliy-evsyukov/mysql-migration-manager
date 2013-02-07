<?php

namespace lib;

use \Mysqli;

/**
 * dbDiff
 * Получает и парсит выдачу mysqldiff
 * @author guyfawkes
 */

class dbDiff
{

    /**
     * @var MysqliHelper
     */
    private $_currentAdapter;
    /**
     * @var MysqliHelper
     */
    private $_tempAdapter;
    protected $_currentTable;
    protected $_tempTable;

    /**
     * Запросы в обе стороны, связанные и использованные таблицы
     * @var array
     */
    private $_diff = array(
        'up'     => array(),
        'down'   => array(),
        'tables' => array(
            'used' => array(),
            'refs' => array()
        )
    );

    /**
     * Создает экземпляр класса dbDiff для двух соединений
     * @param MysqliHelper $current Соединение с текущей БД
     * @param MysqliHelper $temp    Соединение с временной БД
     */
    public function __construct(
        MysqliHelper $current = null, MysqliHelper $temp = null
    )
    {
        $current && ($this->_currentTable = $current->getDatabaseName());
        $temp && ($this->_tempTable = $temp->getDatabaseName());
        $this->_currentAdapter = $current;
        $this->_tempAdapter    = $temp;
    }

    /**
     * Добавляет описание связи
     * @param string $tableName       Описываемая таблица
     * @param string $referencedTable Связанная с ней
     */
    private function addReferenced($tableName, $referencedTable)
    {
        $this->_diff['tables']['refs'][$tableName][$referencedTable] = 1;
    }

    /**
     * Помечает таблицу как использованную
     * @param string $tableName
     */
    private function markUsed($tableName)
    {
        $this->_diff['tables']['used'][$tableName] = 1;
    }

    /**
     * Парсит вывод mysqldiff, составляет список использованных и неиспользованных таблиц
     * @param array $output Вывод mysqldiff
     * @return array
     */
    public function parseDiff(array $output = array())
    {
        $comment    = '';
        $tableName  = '';
        $tmp        = array();
        $result     = array();
        $index      = 0;
        $endMarker  = '-- }';
        $actionType = '';
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
                            $tableName  = $data->name;
                            $actionType = $data->action_type;
                            if (isset($data->referenced_tables) &&
                                is_array($data->referenced_tables)
                            ) {
                                // множество зависимых таблиц
                                foreach ($data->referenced_tables as $table) {
                                    $this->addReferenced($tableName, $table);
                                }
                            }
                            $this->markUsed($tableName);
                            $tmp   = array();
                            $index = 0;
                            isset($result['desc'][$tableName]) &&
                                ($index = sizeof($result['desc'][$tableName]));
                        }
                    } catch (\Exception $e) {
                        Output::error($e->getMessage());
                        $tableName = '';
                    }
                }
            }
            else {
                $tmp[] = $line;
                if (!empty($tableName)) {
                    /*
                    * добавим предыдущие собранные данные в результирующий массив
                    * делать это необходимо здесь, поскольку иначе нужна
                    * дополнительная проверка, достигнут ли конец массива,
                    * и "неиспользованный" остаток все равно придется добавить
                    * к последнему обнаруженному комментарию с именем таблицы
                    */
                    $result['desc'][$tableName][$index] = array(
                        'type' => $actionType,
                        'sql'  => trim(implode("\n", $tmp))
                    );
                }
            }
        }

        return $result;
    }

    /**
     * Делегирует работу mysqldiff
     * @return array
     */
    public function getDiff()
    {
        $params   = array('host', 'user', 'password');
        $groups   = array(1, 2);
        $tables   = array($this->_currentTable, $this->_tempTable);
        $adapters = array($this->_currentAdapter, $this->_tempAdapter);
        $dirs     = array('down', 'up');

        for ($i = 0; $i < 2; $i++) {
            $params_str = array();
            foreach ($groups as $index => $k) {
                $adapter = ($adapters[$index] instanceof MysqliHelper) ? $adapters[$index] : null;
                foreach ($params as $param) {
                    if ($adapter) {
                        $methodName = 'get' . ucfirst($param);
                        $value      = $adapter->{$methodName}();
                        if (!empty($value)) {
                            $params_str[] = "--{$param}{$k}={$value}";
                        }
                    }
                }
            }
            $command = sprintf(
                '%s %s',
                Helper::get('mysqldiff_command'),
                implode(' ', $params_str)
            );
            $output  = array();
            $full    = sprintf(
                '%s --list-tables --no-old-defs --save-quotes %s %s',
                $command,
                $tables[$i],
                $tables[1 - $i]
            );
            Output::verbose("Command {$full}", 2);
            exec($full, $output, $return_status);
            if (!empty($output)) {
                $result                 = $this->parseDiff($output);
                $this->_diff[$dirs[$i]] = $result['desc'];
            }
            else {
                Output::verbose(
                    sprintf('Command %s returned nothing', $full),
                    3
                );
            }
            $adapters = array_reverse($adapters);
        }

//        var_dump($this->_diff);
//        die();

        return $this->_diff;
    }

    /**
     * Возвращает информацию об использованных и ссылающихся таблицах
     * Результат - хеш из ключей used и refs
     * @return array
     */
    public function getTablesInfo()
    {
        return $this->_diff['tables'];
    }

}

