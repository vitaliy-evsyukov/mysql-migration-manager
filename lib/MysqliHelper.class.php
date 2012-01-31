<?php

namespace lib;

use \Mysqli;

/**
 * MysqliHelper
 * Обертка над Mysqli
 * @author guyfawkes
 */
class MysqliHelper {

    const MYSQL_SERVER_HAS_GONE_AWAY = 2006;
    /**
     *
     * @var Mysqli
     */
    private $_db = null;
    private $_databaseName = '';
    private $_host = '';
    private $_user = '';
    private $_password = '';
    private $_commands = array();
    private $_retriesCount = 3;

    public function __construct($host, $user, $password, $db = '') {
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
    public function setCommand($command) {
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
    public function setRetriesCount($count) {
        $this->_retriesCount = (int) $count;
    }

    /**
     * Получить имя текущей базы данных
     * @return string
     */
    public function getDatabaseName() {
        return $this->_databaseName;
    }

    /**
     * Устанавливает используемую базу данных
     * @param string $dbname
     * @return bool
     */
    public function select_db($dbname) {
        $r = $this->_db->select_db($dbname);
        if ($r) {
            $this->_databaseName = $dbname;
        }
        return $r;
    }

    public function __call($name, $arguments) {
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
                            '#%d: Trying to reconnect...', $counter
                        ), 1
                    );
                    $this->connect();
                }
            }
            else {
                return $result;
            }
        }
    }

    public function __get($name) {
        return $this->_db->$name;
    }

    private function executeCommands() {
        var_dump($this->_databaseName);
        var_dump($this->_commands);
        if (!empty($this->_commands)) {
            foreach ($this->_commands as $command) {
                $this->query($command);
            }
        }
    }

    private function connect() {
        $this->_db = @new Mysqli($this->_host, $this->_user, $this->_password);
        if ($this->_db->connect_errno) {
            throw new \Exception(sprintf(
                'Database connect error occured: %s (%d)',
                $this->_db->connect_error, $this->_db->connect_errno
            ));
        }
        $this->_db->select_db($this->_databaseName);
        if (!$this->_db->set_charset("utf8")) {
            throw new \Exception(sprintf(
                'SET CHARACTER SET utf8 error: %s', $this->db->error
            ));
        }
        ;
        $this->executeCommands();
    }

}

?>
