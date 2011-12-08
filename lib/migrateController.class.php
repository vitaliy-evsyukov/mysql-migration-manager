<?php

namespace lib;

class migrateController extends DatasetsController {

    protected $queries = array();

    public function runStrategy() {
        $mHelper = Helper::getAllMigrations();

        if (empty($mHelper['migrations'])) {
            Output::verbose("No revision has been created", 1);
            return false;
        }
        $minMigration = current($mHelper['migrations']);
        $maxMigration = end($mHelper['migrations']);

        if (!isset($this->args['revision'])) {
            $revision = Helper::getCurrentRevision();
        }
        else {
            $revision = $this->args['revision'];
        }
        Output::verbose(sprintf('You are in revision %d', $revision), 1);

        if (!isset($this->args['m'])) {
            $this->args['m'] = 'now';
        }
        $str = $this->args['m'];
        if (is_numeric($str)) {
            $search_migration = (int) $str;
            if ($search_migration !== 0) {
                $class = sprintf(
                        '%s\Migration%d',
                        str_replace('/', '\\', Helper::get('savedir')),
                        $search_migration
                );
                $o = new $class;
                $meta = $o->getMetadata();
                $target_migration = $meta['timestamp'];
            }
            else {
                $target_migration = 0;
            }
        }
        else {
            $target_migration = strtotime($str);
        }

        if (false === $target_migration) {
            throw new \Exception(
                    sprintf("Incorrect migration value %s", $str)
            );
        }

        $datasets = $this->args['datasets'];
        $tablesList = array();
        if (!empty($datasets)) {
            if (!isset($this->args['loadData'])) {
                $this->args['loadData'] = false;
            }
            $datasets = $this->loadDatasetInfo();
            foreach ($datasets['reqs'] as $dataset) {
                foreach ($dataset['tables'] as $tableName) {
                    $tablesList[$tableName] = 1;
                }
            }
        }

        $timestamp = 0;
        if ($revision > 0) {
            $timestamp = $mHelper['data'][$revision]['time'];
        }

        $target_str = 'initial revision (SQL)';
        if ($target_migration > 0) {
            $target_str = date('d.m.Y H:i:s', $target_migration);
        }
        if ($timestamp > 0) {
            $start_str = date('d.m.Y H:i:s', $timestamp);
        }
        else {
            $start_str = 'initial revision';
        }

        if ($revision === $maxMigration && $target_migration >= $timestamp) {
            Output::verbose('There are no newer migrations', 1);
            return false;
        }
        else {
            Output::verbose(
                    sprintf("Starting migration from %s (revision %d) to %s\n",
                            $start_str, $revision, $target_str
                    ), 1
            );
        }

        $timeline = Helper::getTimeline($tablesList);

        $direction = 'Up';
        if ($revision > 0) {
            $direction = $mHelper['data'][$revision]['time'] <= $target_migration ? 'Up' : 'Down';
        }

        if ($direction === 'Down') {
            $timeline = array_reverse($timeline, true);
        }
        $usedMigrations = array();
        foreach ($timeline as $time => $tables) {
            $time_str = 'initial revision';
            if ($time > 0) {
                $time_str = date('d.m.Y H:i:s', $time);
            }
            if ($direction == 'Down') {
                /*
                 * Если ревизия произошла после таймпстампа, от которого мы
                 * спускаемся вниз, пропускаем
                 */
                if ($time > $timestamp) {
                    Output::verbose(
                            sprintf("%s skipped, because is is lesser %s",
                                    $time_str, $start_str), 2
                    );
                    continue;
                }
                /*
                 * Если прошли минимально подходящую ревизию, остановимся
                 */
                $revision = $time;
                if ($time <= $target_migration) {
                    if ($time === 0) {
                        $revision = 0;
                    }
                    Output::verbose(
                            sprintf("%s skipped, because is less or equal %s",
                                    $time_str, $target_str), 2
                    );
                    break;
                }
            }
            else {
                if ($time <= $timestamp) {
                    Output::verbose(
                            sprintf("%s skipped, because is less or equal %s\n",
                                    $time_str, $start_str), 2
                    );
                    continue;
                }
                if ($time > $target_migration) {
                    Output::verbose(
                            sprintf("%s skipped, because is greater %s\n",
                                    $time_str, $target_str), 2
                    );
                    break;
                }
                $revision = $time;
            }

            foreach ($tables as $tablename => $rev) {
                if (is_int($rev)) {
                    Output::verbose(sprintf("Executing migration for %s (# %d)\n",
                                    $time_str, $rev), 1);
                    // обратимся к нужному классу
                    if (!isset($usedMigrations[$rev])) {
                        Output::verbose(sprintf(
                                        "Execute migration for tables:\n--- %s",
                                        implode("\n--- ", array_keys($tables))
                                ), 2);
                        Helper::applyMigration(
                                $rev, $this->db, $direction, $tables
                        );
                        $usedMigrations[$rev] = 1;
                        break;
                    }
                }
                else {
                    // это SQL-запрос
                    Output::verbose(sprintf("Executing SQL for table: %s",
                                    $tablename), 1);
                    $this->db->query($rev);
                }
            }
        }

        if ($target_migration === 0) {
            $revision = 0;
        }
        else {
            print_r($mHelper);
            var_dump($revision);

            if (isset($mHelper['timestamps'][$revision])) {
                $revision = $mHelper['timestamps'][$revision];
            }
//            
//            foreach ($mHelper['data'] as $num => $migration) {
//                if ($migration['time'] === $revision) {
//                    foreach ($mHelper['migrations'] as $k => $v) {
//                        if ($v === $num) {
//                            $revision = $num;
//                            $direction == 'Down' ? $k-- : $k++;
//                            if (isset($mHelper['migrations'][$k])) {
//                                $revision = $mHelper['migrations'][$k];
//                            }
//                            break;
//                        }
//                    }
//                    break;
//                }
//            }
        }

        Helper::writeRevisionFile($revision);
        return true;
    }

}