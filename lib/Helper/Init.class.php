<?php

namespace lib\Helper;

use lib\AbstractController;
use lib\ControllersChain;
use lib\DatasetsController;
use lib\GetOpt;
use lib\helpController;
use lib\IController;
use lib\MysqliHelper;

/**
 * Init
 * Хелпер для конфигурации приложения
 * @author  Виталий Евсюков
 * @package lib\Helper
 */
class Init extends Helper
{

    /**
     * @var array
     */
    protected $configTpl = array(
        'config'          => array('short' => 'c', 'req_val'),
        'host'            => array('req_val'),
        'user'            => array('req_val'),
        'password'        => array('req_val'),
        'db'              => array('req_val'),
        'port'            => array('req_val'),
        'savedir'         => array('req_val'),
        'verbose'         => array('req_val'),
        'versionfile'     => array('req_val'),
        'stop-on-failure' => array('req_val'),
        'quiet'           => array('short' => 'q', 'no_val'),
        'version_marker'  => array('req_val'),
        'tmp_host'        => array('req_val'),
        'tmp_user'        => array('req_val'),
        'tmp_password'    => array('req_val'),
        'tmp_port'        => array('req_val'),
        'cachedir'        => array('req_val'),
        'schemadir'       => array('req_val'),
        'reqtables'       => array('req_val'),
        'tmp_db_name'     => array('req_val'),
        'tmp_add_suffix'  => array('req_val'),
        'routine_user'    => array('req_val')
    );

    /**
     * @var array
     */
    protected $config = array(
        'config'          => null, //path to alternate config file
        'host'            => null,
        'user'            => null,
        'password'        => null,
        'db'              => null,
        'port'            => null,
        'savedir'         => null,
        'cachedir'        => null,
        'reqtables'       => null,
        'schemadir'       => null,
        'verbose'         => null,
        'stop-on-failure' => true,
        'versionfile'     => null,
        'version_marker'  => null,
        'tmp_db_name'     => null,
        'tmp_add_suffix'  => null,
        'tmp_port'        => null,
        'routine_user'    => null
    );

    /**
     * Возвращает значение параметра из конфигурации
     * @static
     * @param string $key Название параметра
     * @return mixed|bool Значение или false в случае неудачи
     */
    public function get($key)
    {
        return isset($this->config[$key]) ? $this->config[$key] : false;
    }

    /**
     * Устанавливает значение параметра
     * @static
     * @param string $key   Название параметра
     * @param mixed  $value Значение
     */
    public function set($key, $value)
    {
        $this->config[$key] = $value;
    }

    /**
     * Возвращает присутствие элемента в текущей конфигурации
     * @param string $key
     * @return bool
     */
    public function exists($key)
    {
        return isset($this->config[$key]);
    }

    /**
     * Устанавливает конфигурацию
     * @param $cnf
     */
    public function setConfig($cnf)
    {
        $this->config = array_replace($this->config, $cnf);
    }

    /**
     * Возвращает конфигурацию
     * @return array
     */
    public function getConfig()
    {
        return $this->config;
    }

    /**
     * Parse command line into config options and commands with its parameters
     * @param array $args List of arguments provided from command line
     * @return array
     */
    public function parseCommandLineArgs(array $args)
    {
        $parsed_args = array(
            'options' => array(),
            'command' => array(
                'name' => null,
                'args' => array()
            )
        );

        array_shift($args);
        $opts = GetOpt::extractLeft($args, $this->configTpl);
        if ($opts === false) {
            $errors = (array) GetOpt::errors();
            $this->container->getOutput()->error('mmm: ' . reset($errors));
            exit(1);
        } else {
            $parsed_args['options'] = $opts;
        }

        //if we didn't traverse the full array just now, move on to command parsing
        if (!empty($args)) {
            $parsed_args['command']['name'] = array_shift($args);
        }

        //consider any remaining arguments as command arguments
        $parsed_args['command']['args'] = $args;

        return $parsed_args;
    }

    /**
     * Возвращает объект контроллера или цепочку ответственности
     * Без переданных параметров возвращает "help" контроллер
     * @param string                 $name Имя контроллера
     * @param array                  $args Массив аргументов
     * @param \lib\MysqliHelper|null $db   Объект подключения к БД
     * @throws \Exception
     * @return AbstractController|ControllersChain
     */
    public function getController(
        $name = null,
        $args = array(),
        MysqliHelper $db = null
    )
    {
        if (empty($name)) {
            return new helpController();
        }

        $ctrlName = 'lib\\' . $name . 'Controller'; // http://php.net/manual/en/language.namespaces.dynamic.php
        try {
            if (!$db) {
                $db = $this->container->getDb()->getDbObject();
            }
            $ctrl = new $ctrlName($db, $args, $this->container);
            if ($ctrl instanceof DatasetsController) {
                // обернем его в цепочку
                $chain = new ControllersChain(null, $this->container);
                $ctrl->setChain($chain);
                $chain->setController($ctrl);

                return $chain;
            }

            return $ctrl;
        } catch (\Exception $e) {
            $message = $e->getMessage();
            if ($e->getCode() === NO_COMMAND) {
                $message = sprintf(
                    "%s\nCommand %s not recognized",
                    $message,
                    $name
                );
            }
            throw new \Exception($message, $e->getCode());
        }
    }

    /**
     * Возвращает наименование действия, выполняемого контроллером
     * @param IController $controller Экземпляр класса
     * @return string
     */
    public function getActionName(IController $controller)
    {
        $name = get_class($controller);
        $name = substr($name, strrpos($name, '\\') + 1);
        return substr($name, 0, stripos($name, 'Controller'));
    }
}