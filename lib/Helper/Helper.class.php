<?php

namespace lib\Helper;

/**
 * Helper
 *
 * @author  Виталий Евсюков
 * @package lib\Helper
 */

abstract class Helper
{
    /**
     * @var Container
     */
    protected $container;

    /**
     * @param Container $container
     */
    public function __construct(Container $container)
    {
        $this->container = $container;
    }

    /**
     * Возвращает значение параметра из конфигурации
     * @static
     * @param string $key Название параметра
     * @return mixed|bool Значение или false в случае неудачи
     */
    public function get($key)
    {
        return $this->container->getInit()->get($key);
    }

    /**
     * Устанавливает значение параметра
     * @static
     * @param string $key   Название параметра
     * @param mixed  $value Значение
     */
    public function set($key, $value)
    {
        $this->container->getInit()->set($key, $value);
    }

    /**
     * Возвращает присутствие элемента в текущей конфигурации
     * @param string $key
     * @return bool
     */
    public function exists($key)
    {
        return $this->container->getInit()->exists($key);
    }

    /**
     * Устанавливает конфигурацию
     * @param $cnf
     */
    public function setConfig($cnf)
    {
        $this->container->getInit()->setConfig($cnf);
    }

    /**
     * Возвращает конфигурацию
     * @return array
     */
    public function getConfig()
    {
        return $this->container->getInit()->getConfig();
    }

    /**
     * Выводит сообщение на экран
     * @param string $message
     * @param int    $level
     */
    protected function output($message, $level = 1)
    {
        $this->container->getOutput()->verbose($message, $level);
    }
}