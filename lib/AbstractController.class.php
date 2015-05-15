<?php

namespace lib;

use lib\Helper\Container;

/**
 * AbstractController
 * Абстрактный класс контроллеров действий
 * @author Виталий Евсюков
 */
abstract class AbstractController implements IController
{
    /**
     * БД, с которой работает контроллер
     * @var MysqliHelper
     */
    protected $db = null;

    /**
     * Набор аргументов контроллера
     * @var array
     */
    protected $args = array();

    /**
     * Контейнер зависимостей
     * @var Container
     */
    protected $container;

    /**
     * Создает экземлпяр класса контроллера
     * @param MysqliHelper|null $db        Объект соединения
     * @param array             $args      Массив аргументов, переданных пользователем
     * @param Container         $container Контейнер зависимостей
     */
    public function __construct(MysqliHelper $db = null, $args = array(), Container $container)
    {
        $this->db        = $db;
        $this->container = $container;
        $this->container->getFileSystem()->initDirs();
        $this->args = $args;
    }

    /**
     * Выводит текст на экран
     * @param string $message Текст
     * @param int    $level   Уровень сообщения
     */
    protected function verbose($message, $level = 1)
    {
        $this->container->getOutput()->verbose($message, $level);
    }
}