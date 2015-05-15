<?php

/**
 * recoverController
 * Восстанавливает содержимое revisions.txt
 * @author Виталий Евсюков
 */

namespace lib;

class recoverController extends AbstractController
{
    public function runStrategy()
    {
        $migrations = $this->container->getMigrations();
        $migrations->parseMigrations();
        $lines        = array();
        $list         = $migrations->getAllMigrations(false);
        $maxRevision = -1;
        foreach ($list as $data) {
            foreach ($data as $timestamp => $revision) {
                if (isset($lines[$timestamp])) {
                    continue;
                }
                $lines[$timestamp] = sprintf(
                    "%d|%s|%d",
                    $revision,
                    date('d.m.Y H:i:s', $timestamp),
                    $timestamp
                );
                if ($maxRevision < $revision) {
                    $maxRevision = $revision;
                }
            }
        }
        ksort($lines);

        $content = array(
            'versionfile'    => implode("\n", $lines),
            'version_marker' => "#{$maxRevision}"
        );

        $initHelper = $this->container->getInit();
        foreach ($content as $key => $value) {
            $filename = $initHelper->get('savedir') . $initHelper->get($key);
            file_put_contents($filename, $value);
            $this->verbose(
                sprintf(
                    "File %s was successfully restored",
                    $filename
                ),
                1
            );
        }
    }
}
