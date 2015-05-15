<?php

namespace lib;

use lib\Helper\Container;

/**
 * ControllersChain
 * Цепочка обязанностей
 * @author Виталий Евсюков
 */
class ControllersChain implements IController
{
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
    private $controller = null;

    /**
     * Следующий элемент цепочки
     * @var ControllersChain
     */
    private $next = null;

    /**
     * Песочница окружения
     * @var array
     */
    private $sandbox = array();

    /**
     * Массив количества выполнений каждого контроллера
     * @var array
     */
    private $controllerIndexes = array();

    /**
     * Контейнер зависимостей
     * @var Container
     */
    private $container;

    /**
     * @param ControllersChain $handler   Следующий обработчик в цепочке
     * @param Container        $container Контейнер зависимостей
     */
    public function __construct(ControllersChain $handler = null, Container $container)
    {
        $this->next      = $handler;
        $this->container = $container;
    }

    /**
     * Установить выполняемый в элементе цепочки контроллер
     * @param DatasetsController $controller
     */
    public function setController(DatasetsController $controller)
    {
        $this->controller = $controller;
    }

    /**
     * Возвращает контроллер
     * @return \lib\DatasetsController|null
     */
    public function getController()
    {
        return $this->controller;
    }

    /**
     * Запускает цепочку
     * @param int $state Состояние элемента, определяющее поведение
     * @return mixed
     */
    public function runStrategy($state = 0)
    {
        $state  = (int) $state;
        $result = null;
        if ($this->controller) {
            if ($state === self::FK_OFF) {
                $this->controller->toogleFK($state);
            }
            $action = $this->container->getInit()->getActionName($this->controller);
            $this->container->getOutput()->verbose(
                sprintf('Run %s', $action),
                3
            );
            $this->runSandbox($action);
            $result = $this->controller->runStrategy();
            $this->resetSandbox();
            $state++;
            if ($this->next) {
                $this->next->setSandbox($this->sandbox);
                $this->next->setIndexesCounter($this->controllerIndexes);
                $this->next->runStrategy($state);
            }
            if ($state == self::FK_ON) {
                $this->controller->toogleFK($state);
            }
        }
        return $result;
    }

    /**
     * Установить следующий элемент цепочки
     * @param \lib\ControllersChain $next
     */
    public function setNext(ControllersChain $next)
    {
        $this->next = $next;
    }

    /**
     * Получить следующий элемент цепочки
     * @return \lib\ControllersChain|null
     */
    public function getNext()
    {
        return $this->next;
    }

    /**
     * Установить окружение и сохранить предыдущее состояние
     * @param array $sandbox Массив нового окружения
     */
    public function setSandbox(array $sandbox = array())
    {
        if (!isset($sandbox['original'])) {
            $sandbox['original'] = array($this->container->getInit()->getConfig());
        }
        $this->sandbox           = $sandbox;
        $this->controllerIndexes = array();
        $keys                    = array_keys($sandbox);
        foreach ($keys as $key) {
            $this->controllerIndexes[$key] = -1;
            if (!is_int(key($sandbox[$key]))) {
                $this->sandbox[$key] = array($this->sandbox[$key]);
            }
        }
    }

    /**
     * Установить счетчик
     * @param array $counter
     */
    public function setIndexesCounter(array $counter)
    {
        $this->controllerIndexes = $counter;
    }

    /**
     * Выполнить изменение окружения
     * @param string $action
     */
    private function runSandbox($action)
    {
        if (isset($this->sandbox[$action])) {
            $currentIndex = 0;
            /**
             * Если запускаем не приведение к оригинальному состоянию, то для текущего действия увеличим счетчик, т.к.
             * при вложенных друг в друга цепочках ответственностей может понадобиться иметь разное окружение
             */
            if ($action !== 'original') {
                $this->controllerIndexes[$action]++;
                $currentIndex = $this->controllerIndexes[$action];
            }
            /**
             * Если для текущего уровня задано окружение, применим его
             */
            if (isset($this->sandbox[$action][$currentIndex])) {
                $this->container->getOutput()->verbose(
                    sprintf(
                        'Run sandboxing for %s at index %d',
                        $action,
                        $currentIndex
                    ),
                    3
                );
                $initHelper = $this->container->getInit();
                foreach ($this->sandbox[$action][$currentIndex] as $key => $value) {
                    $initHelper->set($key, $value);
                }
            }
        }
    }

    /**
     * Сбросить окружение к начальному состоянию
     */
    public function resetSandbox()
    {
        $this->runSandbox('original');
    }
}