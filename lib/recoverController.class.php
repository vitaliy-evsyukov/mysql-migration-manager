<?php

/**
 * recoverController
 * Восстанавливает содержимое revisions.txt
 * @author guyfawkes
 */

namespace lib;

class recoverController implements IController {

    public function runStrategy() {
        $lines = array();
        $list = Registry::getAllMigrations(false);
        foreach ($list as $tablename => $data) {
            foreach ($data as $timestamp => $revision) {
                $lines[$timestamp] = sprintf("%d|%s|%d", $revision,
                        date('d.m.Y H:i:s', $timestamp), $timestamp);
            }
        }
        ksort($lines);
        $lines[] = "#{$revision}";
        $filename = DIR . Helper::get('savedir') . DIR_SEP . Helper::get('versionfile');
        file_put_contents($filename, implode("\n", $lines));
        printf("Файл %s был успешно восстановлен\n", $filename);
    }

}

?>
