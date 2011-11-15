<?php

define('DIR_SEP', DIRECTORY_SEPARATOR);
define('DIR', __DIR__ . DIR_SEP);

spl_autoload_register('mmpAutoload');

function mmpAutoload($class) {
    $filename = DIR . str_replace('\\', '/', $class) . '.class.php';

    if (file_exists($filename)) {
        require_once $filename;
    } else {
        $backtrace = array_reverse(debug_backtrace());
        $debug = array();
        $order = array('file', 'line', 'class', 'function');
        foreach ($backtrace as $item) {
            $tmp = array();
            foreach ($order as $field) {
                if (empty($item[$field])) {
                    $tmp[$field] = '<отсутствует>';
                } else {
                    $tmp[$field] = $item[$field];
                }
            }
            $debug[] = vsprintf("Файл %s, строка: %d. Класс:%s, функция: %s.", $tmp);
        }
        echo sprintf("Класс %s не найден в %s\n\n", $class, DIR);
        echo implode("\n", $debug);
        die("\n");
    }
}

