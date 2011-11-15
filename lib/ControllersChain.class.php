<?php

namespace lib;

/**
 * ControllersChain
 * Цепочка обязанностей
 * @author guyfawkes
 */
class ControllersChain implements IController {
    const FK_OFF = 0;
    const FK_ON = 1;

    /**
     * Контроллер, который будет вызван
     * @var DatasetsController
     */
    protected $_controller = null;

    /**
     *
     * @var ControllersChain 
     */
    protected $_next = null;

    public function __construct(ControllersChain $handler = null) {
        $this->_next = $handler;
    }

    public function setController(DatasetsController $controller) {
        $this->_controller = $controller;
    }

    /**
     *
     * @return DatasetsController 
     */
    public function getController() {
        return $this->_controller;
    }

    public function runStrategy($state = 0) {
        if ($this->_controller) {
            if ($state === self::FK_OFF) {
                
            }
            $this->_controller->runStrategy();
            if ($this->_next) {
                $this->_next->runStrategy($state);
            }
        }
    }

}

?>
