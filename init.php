<?php

define('DIR_SEP', DIRECTORY_SEPARATOR);
define('DIR', __DIR__ . DIR_SEP);
define('NO_COMMAND', -1);
date_default_timezone_set('Europe/Moscow');
spl_autoload_register('mmpAutoload');
mb_internal_encoding('UTF-8');
set_include_path(DIR);

function mmpAutoload($class) {
    $include_parts = explode(PATH_SEPARATOR, get_include_path());
    foreach ($include_parts as $dir) {
        $filename = $dir . str_replace('\\', '/', $class) . '.class.php';
        if (file_exists($filename)) {
            require_once $filename;
            return;
        }
    }
    $backtrace = array_reverse(debug_backtrace());
    $debug     = array();
    $order     = array('file', 'line', 'class', 'function');
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
        $debug[] = vsprintf(
            "File %s, line: %d. Class: %s, function: %s.",
            $tmp
        );
    }
    throw new Exception(sprintf(
        "Class %s not found in %s\nBack trace:\n%s\n", $class, $dir,
        implode("\n", $debug)
    ), NO_COMMAND);

}

