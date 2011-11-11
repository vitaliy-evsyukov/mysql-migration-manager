#!/usr/bin/env php
<?php

use lib\Helper,    lib\Output;

require_once __DIR__ . '/init.php';

$cli_params = Helper::parseCommandLineArgs($argv);

if (empty($cli_params['options']['config'])) {
    $cli_params['options']['config'] = __DIR__ . DIR_SEP . 'config.ini';
}

if (file_exists($cli_params['options']['config'])) {
    $config = parse_ini_file($cli_params['options']['config']);
    $config = array_replace($config, $cli_params['options']); //command line overrides everything
} else {
    Output::error('mmp: could not find config file "' . $cli_params['options']['config'] . '"');
    exit(1);
}

Helper::setConfig($config);

try {
    $controller = Helper::getController($cli_params['command']['name'], $cli_params['command']['args']);
    $controller->runStrategy();
} catch (Exception $e) {
    Output::error($e->getMessage());
    Helper::getController('help')->runStrategy();
    exit(1);
}

