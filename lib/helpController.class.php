<?php

namespace lib;

/**
 * helpController
 * Выводит справку
 * @author guyfawkes
 */
class helpController implements IController {

    public function runStrategy() {
        $content = file_get_contents(DIR . 'tpl/help.tpl') . "\n";
        if (strtoupper(substr(PHP_OS, 0, 3)) === 'WIN') {
            $content = mb_convert_encoding($content, 'CP866', 'UTF-8');
        }
        printf($content);
    }

}
