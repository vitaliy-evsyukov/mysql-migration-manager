<?php

namespace lib;

/**
 * IController
 * Интерфейс контроллеров
 * @author guyfawkes
 */
interface IController {
    
    /**
     * Запускает основную операцию контроллера
     */
    public function runStrategy();
    
}

?>
