<?php

define('DIR_SEP', DIRECTORY_SEPARATOR);
define('DIR', __DIR__ . DIR_SEP);
date_default_timezone_set('Europe/Moscow');
spl_autoload_register('mmpAutoload');

function mmpAutoload($class) {
    $filename = DIR . str_replace('\\', '/', $class) . '.class.php';

    if (file_exists($filename)) {
        require_once $filename;
    }
    else {
        $backtrace = array_reverse(debug_backtrace());
        $debug = array();
        $order = array('file', 'line', 'class', 'function');
        foreach ($backtrace as $item) {
            $tmp = array();
            foreach ($order as $field) {
                if (empty($item[$field])) {
                    $tmp[$field] = '<empty>';
                }
                else {
                    $tmp[$field] = $item[$field];
                }
            }
            $debug[] = vsprintf("File %s, line: %d. Class: %s, function: %s.",
                    $tmp);
        }
        throw new Exception(
                sprintf(
                        "Класс %s не найден в %s\nBack trace:\n%s\n", $class,
                        DIR,
                        implode("\n", $debug
                        )
                )
        );
    }
}

