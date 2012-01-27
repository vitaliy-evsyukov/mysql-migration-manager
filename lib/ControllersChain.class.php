<?php

namespace lib;

/**
 * ControllersChain
 * Цепочка обязанностей
 * @author guyfawkes
 */

class ControllersChain implements IController {

    /**
     * Константа для выключения проверки внешних ключей
     */
    const FK_OFF = 0;
    /**
     * Константа, восстанавливающая проверку внешних ключей
     */
    const FK_ON = 1;

    /**
     * Контроллер, который будет вызван
     * @var DatasetsController
     */
    protected $_controller = null;

    /**
     * Следующий элемент цепочки
     * @var ControllersChain
     */
    protected $_next = null;

    public function __construct(ControllersChain $handler = null) {
        $this->_next = $handler;
    }

    /**
     * Установить выполняемый в элементе цепочки контроллер
     * @param DatasetsController $controller
     */
    public function setController(DatasetsController $controller) {
        $this->_controller = $controller;
    }

    /**
     * Вернуть контроллер
     * @return DatasetsController
     */
    public function getController() {
        return $this->_controller;
    }

    /**
     * Запускает цепочку
     * @param int $state Состояние элемента, определяющее поведение
     */
    public function runStrategy($state = 0) {
        $state = (int) $state;
        if ($this->_controller) {
            if ($state === self::FK_OFF) {
                $this->_controller->toogleFK($state);
            }
            $state++;
            if ($this->_next) {
                $this->_next->runStrategy($state);
            }
            Output::verbose(
                sprintf('Run %s', get_class($this->_controller)), 3
            );
            $this->_controller->runStrategy();
            if ($state == self::FK_ON) {
                $this->_controller->toogleFK($state);
            }
        }
    }

    /**
     * Установить следующий элемент цепочки
     * @param \lib\ControllersChain $next
     */
    public function setNext(ControllersChain $next) {
        $this->_next = $next;
    }

    /**
     * Получить следующий элемент цепочки
     * @return \lib\ControllersChain
     */
    public function getNext() {
        return $this->_next;
    }

}

?>
