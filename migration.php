#!/usr/bin/env php
<?php

require_once __DIR__ . '/init.php';

$initHelper = $container->getInit();
$printHelper = $container->getOutput();
$cliParams = $initHelper->parseCommandLineArgs($argv);

if (empty($cliParams['options']['config'])) {
    $cliParams['options']['config'] = __DIR__ . DIR_SEP . 'config.ini';
}

if (file_exists($cliParams['options']['config'])) {
    $config = parse_ini_file($cliParams['options']['config']);
    $config = array_replace($config, $cliParams['options']); //command line overrides everything
} else {
    $printHelper->error('mmm: could not find config file "' . $cliParams['options']['config'] . '"');
    exit(1);
}

register_shutdown_function(
    function () use ($printHelper) {
        $lastError = error_get_last();
        if ($lastError) {
            $printHelper->error(
                sprintf('Error occured: %s (%s:%d)', $lastError['message'], $lastError['file'], $lastError['line'])
            );
            exit(3);
        }
    }
);

$initHelper->setConfig($config);


try {
    $controller = $initHelper->getController($cliParams['command']['name'], $cliParams['command']['args']);
    $controller->runStrategy();
} catch (Exception $e) {
    $printHelper->error($e->getMessage());
    exit(2);
}