<?php

/**
 * recoverController
 * Восстанавливает содержимое revisions.txt
 * @author guyfawkes
 */

namespace lib;

class recoverController implements IController {

    public function runStrategy() {
        Registry::parseMigrations();
        $lines        = array();
        $list         = Registry::getAllMigrations(false);
        $max_revision = -1;
        foreach ($list as $data) {
            foreach ($data as $timestamp => $revision) {
                if (isset($lines[$timestamp])) {
                    continue;
                }
                $lines[$timestamp] = sprintf(
                    "%d|%s|%d", $revision, date('d.m.Y H:i:s', $timestamp),
                    $timestamp
                );
                if ($max_revision < $revision) {
                    $max_revision = $revision;
                }
            }
        }
        ksort($lines);
        $filename = Helper::get('savedir') . Helper::get('versionfile');
        file_put_contents($filename, implode("\n", $lines));
        Output::verbose(
            sprintf("File %s was successfully restored\n", $filename), 1
        );
    }

}

?>
