<?php

/**
 * Выполняет команду и перехватывает вывод
 * @param string $cmd
 * @param string $stdout STDOUT
 * @param string $stderr STDERR
 * @return int Код возврата
 */
function cmd_exec($cmd, &$stdout, &$stderr) {
    $descriptorspec = array(
        0 => array("pipe", "r"),
        1 => array("pipe", "w"),
        2 => array("pipe", "w")
    );
    $proc           = proc_open($cmd, $descriptorspec, $pipes);

    if (!is_resource($proc)) {
        $stderr = 'Не удалось открыть процесс разворачивания БД';
        return 255;
    }

    fclose($pipes[0]); //Don't really want to give any input

    $stdout = stream_get_contents($pipes[1]);
    fclose($pipes[1]);

    $stderr = stream_get_contents($pipes[2]);
    fclose($pipes[2]);

    $exit = proc_close($proc);
    return $exit;
}

$res = array(
    'success' => false,
    'message' => 'Не переданы параметры',
    'info'    => ''
);

if (isset($_REQUEST['params'])) {
    $params = urldecode($_REQUEST['params']);
    $stdout = '';
    $stderr = '';
    cmd_exec("./migration.php {$params}", $stdout, $stderr);
    $res = array(
        'success' => empty($stderr),
        'message' => $stderr,
        'info'    => $stdout
    );
}

echo json_encode($res);

?>