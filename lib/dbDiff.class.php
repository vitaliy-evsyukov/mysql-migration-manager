<?php

namespace lib;

use \Mysqli;

/**
 * dbDiff
 * Получает и парсит выдачу mysqldiff
 * @author guyfawkes
 */

class dbDiff {

    private $_currentAdapter;
    protected $_currentTable;
    protected $_tempTable;

    /**
     * Запросы в обе стороны, связанные и использованные таблицы
     * @var array
     */
    private $_difference = array(
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
    public function __construct(MysqliHelper $current, MysqliHelper $temp) {
        $this->_currentTable   = $this->getDbName($current);
        $this->_tempTable      = $this->getDbName($temp);
        $this->_currentAdapter = $current;
    }

    /**
     * Получает имя базы данных
     * @param MysqliHelper $connection Объект соединения
     * @return string
     */
    private function getDbName(MysqliHelper $connection) {
        /*
          $sql = "SELECT DATABASE() as dbname";
          $res = $connection->query($sql);
          $row = $res->fetch_array(MYSQLI_ASSOC);
          $res->free_result();
          return $row['dbname'];
         */
        return $connection->getDatabaseName();
    }

    /**
     * Парсит вывод mysqldiff, составляет список использованных и неиспользованных таблиц
     * @param array $output Вывод mysqldiff
     * @return array
     */
    private function parseDiff(array $output = array()) {
        $comment = '';
        $tmp     = array();
        $result  = array();
        $index   = 0;
        foreach ($output as $line) {
            // если строка состоит только из whitespace'ов
            if (ctype_space($line)) {
                continue;
            }
            if (strpos($line, '--') === 0) {
                // это комментарий с именем таблицы
                $comment = explode('|', trim(substr($line, 2)));
                $comment = str_replace('`', '', $comment);
                if (is_array($comment)) {
                    // множество зависимых таблиц
                    $tableName = array_shift($comment);
                    foreach ($comment as $table) {
                        $this->_difference['tables']['refs'][$tableName][$table] =
                            1;
                    }
                    $comment = $tableName;
                }
                $this->_difference['tables']['used'][$comment] = 1;
                $tmp                                           = array();
                $index                                         = 0;
                isset($result['desc'][$comment]) &&
                ($index = sizeof($result['desc'][$comment]));
            }
            else {
                $tmp[] = $line;
                if (!empty($comment)) {
                    // добавим предыдущие собранные данные в результирующий массив
                    $result['desc'][$comment][$index] =
                        trim(implode("\n", $tmp));
                }
            }
        }

        return $result;
    }

    /**
     * Делегирует работу mysqldiff
     * @return array
     */
    public function getDifference() {

        $params = array('host', 'user', 'password');
        $groups = array('', 'tmp_');

        $tables = array($this->_currentTable, $this->_tempTable);
        $dirs   = array('down', 'up');

        for ($i = 0; $i < 2; $i++) {
            $params_str = array();
            foreach ($params as $param) {
                foreach ($groups as $index => $g) {
                    $value = Helper::get($g . $param);
                    $k     = $index + 1;
                    if (!empty($value)) {
                        $params_str[] = "--{$param}{$k}={$value}";
                    }
                }
            }
            $command = sprintf(
                '%s %s', Helper::get('mysqldiff_command'),
                implode(' ', $params_str)
            );
            $output  = array();
            $full    = sprintf(
                '%s --list-tables --no-old-defs --save-quotes %s %s', $command,
                $tables[$i], $tables[1 - $i]
            );
            Output::verbose("Command {$full}", 2);
            exec($full, $output, $return_status);
            if (!empty($output)) {
                $result                       = $this->parseDiff($output);
                $this->_difference[$dirs[$i]] = $result['desc'];
            }
            else {
                Output::verbose(
                    sprintf('Command %s returned nothing', $full), 3
                );
            }
            $groups = array_reverse($groups);
        }

        return $this->_difference;
    }

}

