<?php

namespace lib;

/**
 * Output
 * Распечатывает сообщения
 */

class Output
{

    /**
     * Распечатывает сообщение в STDOUT
     * @static
     * @param string $msg   Текст сообщения
     * @param int    $level Уровень вывода
     */
    static function verbose($msg, $level = 1)
    {
        if (!Helper::get('quiet') && intval(Helper::get('verbose')) >= $level) {
            echo $msg, PHP_EOL;
        }
    }

    /**
     * Распечатывает сообщение в STDERR
     * @static
     * @param string $msg Текст сообщения
     */
    static function error($msg)
    {
        fwrite(STDERR, $msg . PHP_EOL);
    }

}

?>