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
     * Порт, на котором висит сервер БД
     * @var int
     */
    private $_port = 3306;
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
     * Массив паттернов для замены в запросах
     * @var array
     */
    private static $_replacements = null;

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
        $this->_db = @new Mysqli($this->_host, $this->_user, $this->_password, '', $this->_port);
        if ($this->_db->connect_errno) {
            throw new \Exception(
                sprintf(
                    "Database connect error occured: %s (%d)\nCredentials:\nHost: %s\nUser: %s\nPassword: %s\nPort: %d",
                    $this->_db->connect_error,
                    $this->_db->connect_errno,
                    $this->_host,
                    $this->_user,
                    $this->_password,
                    $this->_port
                )
            );
        }
        $this->_db->select_db($this->_databaseName);
        if (!$this->_db->set_charset("utf8")) {
            throw new \Exception(
                sprintf(
                    'SET CHARACTER SET utf8 error: %s',
                    $this->db->error
                )
            );
        }
        ;
        $this->executeCommands();
    }

    /**
     * Сохраняет последнюю ошибку
     * @param string $message
     * @param int    $code
     */
    private function setError($message, $code)
    {
        $this->_lastError = sprintf('%s (%d)', $message, $code);
    }

    /**
     * Преобразует запрос в массиве аргументов
     * @param string $name
     * @param array  $arguments
     * @return array
     */
    private function prepareQ($name, $arguments)
    {
        if (is_null(self::$_replacements)) {
            $replace             = Helper::getReplaceVariables();
            self::$_replacements = array();
            if (!empty($replace)) {
                foreach ($replace as $dbName => $replaceName) {
                    self::$_replacements['p'][] = '/(\b' . $dbName . '\b)/';
                    self::$_replacements['r'][] = $replaceName;
                }
            }
        }

        if (!empty(self::$_replacements) && (strpos($name, 'query') !== false)) {
            $count = 0;
            if (!empty($arguments[0])) {
                $preArg       = $arguments[0];
                $arguments[0] = preg_replace(
                    self::$_replacements['p'],
                    self::$_replacements['r'],
                    $arguments[0],
                    -1,
                    $count
                );
                if (!empty($count)) {
                    Output::verbose(
                        sprintf("Original statement: %s\nReplaced statement:%s\n", $preArg, $arguments[0]),
                        3
                    );
                }
            }
        }

        return $arguments;
    }


    /**
     * @param string $host     Адрес сервера БД
     * @param string $user     Имя пользователя
     * @param string $password Пароль
     * @param string $db       Имя базы данных
     * @param int    $port     Порт сервера БД
     */
    public function __construct($host, $user, $password, $db = '', $port = 3306)
    {
        $this->_host         = $host;
        $this->_user         = $user;
        $this->_password     = $password;
        $this->_databaseName = $db;
        $this->_port         = is_null($port) ? 3306 : (int)$port;
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
        // почистим список от дубликатов
        $this->_commands = array_unique($this->_commands);
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
            $arguments = $this->prepareQ($name, $arguments);
            $result    = call_user_func_array($callback, $arguments);
            $errno     = $this->_db->errno;
            $error     = $this->_db->error;
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
     * Возвращает последнюю ошибку
     * @return string
     */
    public function getLastError()
    {
        return $this->_lastError;
    }

    /**
     * Возвращает адаптер Mysqli
     * @return \Mysqli
     */
    public function getDb()
    {
        return $this->_db;
    }

    /**
     * Возвращает имя текущей базы данных
     * @return string
     */
    public function getDatabaseName()
    {
        return $this->_databaseName;
    }

    /**
     * Возвращает имя хоста БД
     * @return string
     */
    public function getHost()
    {
        return $this->_host;
    }

    /**
     * Возвращает пароль пользователя БД
     * @return string
     */
    public function getPassword()
    {
        return $this->_password;
    }

    /**
     * Возвращает имя пользователя БД
     * @return string
     */
    public function getUser()
    {
        return $this->_user;
    }

    /**
     * Возвращает порт БД
     * @return int
     */
    public function getPort()
    {
        return $this->_port;
    }

}

?>
