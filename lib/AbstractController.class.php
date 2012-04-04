<?php

namespace lib;

/**
 * AbstractController
 * Абстрактный класс контроллеров действий
 * @author guyfawkes
 */
abstract class AbstractController implements IController {

    /**
     *
     * @var MysqliHelper
     */
    protected $db = null;
    /**
     * @var array
     */
    protected $args = array();

    /**
     * Создает экземлпяр класса контроллера
     * @param MysqliHelper|null $db Объект соединения
     * @param array $args Массив аргументов, переданных пользователем
     */
    public function __construct(MysqliHelper $db = null, $args = array()) {
        $this->db = $db;
        Helper::initDirs();
        $this->args = $args;
    }

}