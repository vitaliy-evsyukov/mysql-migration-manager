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
        $max_revision = -1;
        foreach ($list as $tablename => $data) {
            foreach ($data as $timestamp => $revision) {
                if (isset($lines[$timestamp])) {
                    continue;
                }
                $lines[$timestamp] = sprintf("%d|%s|%d", $revision,
                        date('d.m.Y H:i:s', $timestamp), $timestamp);
                if ($max_revision < $revision) {
                    $max_revision = $revision;
                }
            }
        }
        ksort($lines);
        $lines[] = "#{$max_revision}";
        $filename = DIR . Helper::get('savedir') . DIR_SEP . Helper::get('versionfile');
        file_put_contents($filename, implode("\n", $lines));
        Output::verbose(sprintf("File %s was successfully restored\n", $filename), 1);
    }

}

?>
