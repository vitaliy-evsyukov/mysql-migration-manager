<?php

namespace lib;

use \Mysqli;

/**
 * MysqliHelper
 * Обертка над Mysqli
 * @author guyfawkes
 */
class MysqliHelper
{

    /**
     * Номер ошибки "MySQL server has gone away"
     */
    const MYSQL_SERVER_HAS_GONE_AWAY = 2006;
    /**
     * Экземпляр Mysqli-адаптера
     * @var Mysqli
     */
    private $_db = null;
    /**
     * Имя базы данных
     * @var string
     */
    private $_databaseName = '';
    /**
     * Адрес сервера БД
     * @var string
     */
    private $_host = '';
    /**
     * Имя пользователя
     * @var string
     */
    private $_user = '';
    /**
     * Пароль пользователя
     * @var string
     */
    private $_password = '';
    /**
     * Массив обязательных команд при (пере)подключении
     * @var array
     */
    private $_commands = array();
    /**
     * Количество попыток переустановить соединение с сервером БД
     * @var int
     */
    private $_retriesCount = 3;

    /**
     * Последняя произошедшая ошибка
     * @var string
     */
    private $_lastError = '';

    /**
     * @param string $host     Адрес сервера БД
     * @param string $user     Имя пользователя
     * @param string $password Пароль
     * @param string $db       Имя базы данных
     */
    public function __construct($host, $user, $password, $db = '')
    {
        $this->_host         = $host;
        $this->_user         = $user;
        $this->_password     = $password;
        $this->_databaseName = $db;
        $this->connect();
    }

    /**
     * Добавляет команду или замещает список команд, которые нужно выполнять при реконнекте
     * @param array|string $command
     */
    public function setCommand($command)
    {
        if (!is_array($command)) {
            $this->_commands[] = $command;
        }
        else {
            $this->_commands = $command;
        }
        $this->executeCommands();
    }

    /**
     * Устанавливает количество попыток для реконнекта
     * @param int $count
     */
    public function setRetriesCount($count)
    {
        $this->_retriesCount = (int)$count;
    }

    /**
     * Получить имя текущей базы данных
     * @return string
     */
    public function getDatabaseName()
    {
        return $this->_databaseName;
    }

    /**
     * Устанавливает используемую базу данных
     * @param string $dbname
     * @return bool
     */
    public function select_db($dbname)
    {
        $r = $this->_db->select_db($dbname);
        if ($r) {
            $this->_databaseName = $dbname;
        }

        return $r;
    }

    /**
     * Вызывает метод Mysqli
     * @param string $name      Имя метода
     * @param array  $arguments Массив аргументов
     * @return mixed
     * @throws \Exception
     */
    public function __call($name, $arguments)
    {
        if (!method_exists($this->_db, $name)) {
            throw new \Exception(sprintf('Method %s does not exists', $name));
        }
        $callback = array(&$this->_db, $name);
        if (!is_callable($callback)) {
            throw new \Exception(sprintf('Method %s is not callable', $name));
        }
        $counter = 0;
        while (true) {
            $result = call_user_func_array($callback, $arguments);
            $errno  = $this->_db->errno;
            $error  = $this->_db->error;
            $this->setError($error, $errno);
            if ($errno === self::MYSQL_SERVER_HAS_GONE_AWAY) {
                if (++$counter > $this->_retriesCount) {
                    throw new \Exception(sprintf('%s (%d)', $error, $errno));
                }
                else {
                    /*
                    Output::verbose(
                        sprintf(
                            "Method '%s' got arguments %s. Result: error %s, errno %d",
                            $name, print_r($arguments, true), $error, $errno
                        ), 3
                    );*/
                    Output::verbose(
                        sprintf(
                            '#%d: Trying to reconnect...',
                            $counter
                        ),
                        1
                    );
                    $this->connect();
                }
            }
            else {
                return $result;
            }
        }
    }

    /**
     * Возвращает значение параметра из класса-обертки или из Mysqli
     * @param string $name
     * @return mixed
     */
    public function __get($name)
    {
        return $this->_db->$name;
    }

    /**
     * Выполняет набор обязательных команд при (пере)подключении
     */
    private function executeCommands()
    {
        if (!empty($this->_commands)) {
            foreach ($this->_commands as $command) {
                $this->query($command);
            }
        }
    }

    /**
     * Устанавливает соединение с сервером БД
     * @throws \Exception
     */
    private function connect()
    {
        $this->_db = @new Mysqli($this->_host, $this->_user, $this->_password);
        if ($this->_db->connect_errno) {
            throw new \Exception(sprintf(
                                     'Database connect error occured: %s (%d)',
                                     $this->_db->connect_error,
                                     $this->_db->connect_errno
                                 ));
        }
        $this->_db->select_db($this->_databaseName);
        if (!$this->_db->set_charset("utf8")) {
            throw new \Exception(sprintf(
                                     'SET CHARACTER SET utf8 error: %s',
                                     $this->db->error
                                 ));
        }
        ;
        $this->executeCommands();
    }

    /**
     * Сохраняет последнюю ошибку
     * @param string $message
     * @param int $code
     */
    private function setError($message, $code) {
        $this->_lastError = sprintf('%s (%d)', $message, $code);
    }

    /**
     * Возвращает последнюю ошибку
     * @return string
     */
    public function getLastError() {
        return $this->_lastError;
    }

}

?>
