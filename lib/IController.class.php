<?php

namespace lib;

/**
 * IController
 *
 * @author guyfawkes
 */
interface IController {
    
    /**
     * Запускает основную операцию контроллера
     */
    public function runStrategy();
    
}

?>
