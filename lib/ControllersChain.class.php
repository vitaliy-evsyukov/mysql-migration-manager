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
    /**
     * Песочница окружения
     * @var array
     */
    private $_sandbox = array();
    /**
     * Массив количества выполнений каждого контроллера
     * @var array
     */
    private $_controllerIndexes = array();


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
     * @return \lib\DatasetsController|null
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
            $action = Helper::getActionName($this->_controller);
            Output::verbose(
                sprintf('Run %s', $action), 3
            );
            $this->runSandbox($action);
            $this->_controller->runStrategy();
            $this->resetSandbox();
            $state++;
            if ($this->_next) {
                $this->_next->setSandbox($this->_sandbox);
                $this->_next->setIndexesCounter($this->_controllerIndexes);
                $this->_next->runStrategy($state);
            }
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
     * @return \lib\ControllersChain|null
     */
    public function getNext() {
        return $this->_next;
    }

    /**
     * Установить окружение и сохранить предыдущее состояние
     * @param array $sandbox Массив нового окружения
     */
    public function setSandbox(array $sandbox = array()) {
        if (!isset($sandbox['original'])) {
            $sandbox['original'] = array(Helper::getConfig());
        }
        $this->_sandbox           = $sandbox;
        $this->_controllerIndexes = array();
        $keys                     = array_keys($sandbox);
        foreach ($keys as $key) {
            $this->_controllerIndexes[$key] = -1;
            if (!is_int(key($sandbox[$key]))) {
                $this->_sandbox[$key] = array($this->_sandbox[$key]);
            }
        }
    }

    /**
     * Установить счетчик
     * @param array $counter
     */
    public function setIndexesCounter(array $counter) {
        $this->_controllerIndexes = $counter;
    }

    /**
     * Выполнить изменение окружения
     * @param string $action
     */
    private function runSandbox($action) {
        if (isset($this->_sandbox[$action])) {
            $currentIndex = 0;
            /**
             * Если запускаем не приведение к оригинальному состоянию, то для текущего действия увеличим счетчик, т.к.
             * при вложенных друг в друга цепочках ответственностей может понадобиться иметь разное окружение
             */
            if ($action !== 'original') {
                $this->_controllerIndexes[$action]++;
                $currentIndex = $this->_controllerIndexes[$action];
            }
            /**
             * Если для текущего уровня задано окружение, применим его
             */
            if (isset($this->_sandbox[$action][$currentIndex])) {
                Output::verbose(
                    sprintf(
                        'Run sandboxing for %s at index %d', $action,
                        $currentIndex
                    ), 3
                );
                foreach ($this->_sandbox[$action][$currentIndex] as $key =>
                         $value)
                {
                    Helper::set($key, $value);
                }
            }
        }
    }

    /**
     * Сбросить окружение к начальному состоянию
     */
    public function resetSandbox() {
        $this->runSandbox('original');
    }

}

?>
