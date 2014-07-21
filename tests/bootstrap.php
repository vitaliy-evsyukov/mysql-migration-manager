<?php

$pathOld = get_include_path();
require_once '..' . DIRECTORY_SEPARATOR . 'init.php';
$pathNew = get_include_path();
set_include_path($pathNew . PATH_SEPARATOR . $pathOld);
require_once 'Base.php';