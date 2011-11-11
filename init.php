<?php

define('DIR_SEP', DIRECTORY_SEPARATOR);
define('DIR', __DIR__ . DIR_SEP);

spl_autoload_register('mmpAutoload');

function mmpAutoload($class) {
    $filename = DIR . str_replace('\\', '/', $class) . '.class.php';
    
    if (file_exists($filename)) {
        require_once $filename;
    } else {
        print_r(array_reverse(debug_backtrace()));
        die("Class {$class} not found in " . DIR . "\n");
    }
}

