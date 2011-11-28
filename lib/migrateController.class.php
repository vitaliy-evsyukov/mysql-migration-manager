<?php

namespace lib;

class migrateController extends DatasetsController {

    protected $queries = array();

    public function runStrategy() {
        $mHelper = Helper::getAllMigrations();

        if (empty($mHelper['migrations'])) {
            throw new \Exception("Никаких ревизий еще не было создано");
        }
        $minMigration = current($mHelper['migrations']);
        $maxMigration = end($mHelper['migrations']);

        if (!isset($this->args['revision'])) {
            $revision = Helper::getCurrentRevision();
        }
        else {
            $revision = $this->args['revision'];
        }

        if (!isset($this->args['m'])) {
            $this->args['m'] = 'now';
        }
        $str = $this->args['m'];
        if (is_numeric($str)) {
            $search_migration = (int) $str;
            if ($search_migration !== 0) {
                $class = sprintf(
                        '%s\Migration%d', Helper::get('savedir'),
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
                    sprintf("Переданное значение миграции %s неверно", $str)
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

        $timeline = Helper::getTimeline($tablesList);
        $timestamp = 0;
        if ($revision > 0) {
            $timestamp = $mHelper['data'][$revision]['time'];
        }

        $target_str = 'начальной миграции (SQL)';
        if ($target_migration > 0) {
            $target_str = date('d.m.Y H:i:s', $target_migration);
        }
        if ($timestamp > 0) {
            $start_str = date('d.m.Y H:i:s', $timestamp);
        }
        else {
            $start_str = 'начальной ревизии';
        }

        if ($revision === $maxMigration && $target_migration >= $timestamp) {
            printf("Более новые миграции отсутствуют\n");
            return false;
        }
        else {
            printf("Начинается миграция от %s (ревизия %d) до %s\n", $start_str,
                    $revision, $target_str
            );
        }

        $direction = 'Up';
        if ($revision > 0) {
            $direction = $mHelper['data'][$revision]['time'] <= $target_migration ? 'Up' : 'Down';
        }

        if ($direction === 'Down') {
            $timeline = array_reverse($timeline, true);
        }
        $usedMigrations = array();
        foreach ($timeline as $time => $tables) {
            $time_str = 'начальную ревизию (SQL)';
            if ($time > 0) {
                $time_str = date('d.m.Y H:i:s', $time);
            }
            if ($direction == 'Down') {
                /*
                 * Если ревизия произошла после таймпстампа, от которого мы
                 * спускаемся вниз, пропускаем
                 */
                if ($time > $timestamp) {
                    printf("Пропускаем %s\n", $time_str);
                    continue;
                }
                /*
                 * Если прошли минимально подходящую ревизию, остановимся
                 */
                if ($time <= $target_migration) {
                    printf("%s уже не подходит, т.к. меньше либо равно %s\n",
                            $time_str, $target_str);
                    break;
                }
            }
            else {
                if ($time <= $timestamp) {
                    printf("Пропускаем %s, т.к. меньше либо равно %s\n",
                            $time_str, $start_str);
                    continue;
                }
                if ($time > $target_migration) {
                    printf("%s уже не подходит, т.к. больше %s\n", $time_str,
                            $target_str);
                    break;
                }
            }

            $revision = $time;
            foreach ($tables as $tablename => $rev) {
                if (is_int($rev)) {
                    printf("Выполняем ревизию от %s (№ %d)\n", $time_str, $rev);
                    // обратимся к нужному классу
                    if (!isset($usedMigrations[$rev])) {
                        printf(
                                "Выполняется миграция для следующих таблиц:\n--- %s\n",
                                implode("\n--- ", array_keys($tables))
                        );
                        Helper::applyMigration(
                                $rev, $this->db, $direction, $tables
                        );
                        $usedMigrations[$rev] = 1;
                        break;
                    }
                }
                else {
                    // это SQL-запрос
                    printf("Выполняем SQL для %s\n", $tablename);
                    $this->db->query($rev);
                }
            }
        }

        if ($target_migration === 0) {
            $revision = 0;
        }
        else {
            foreach ($mHelper['data'] as $num => $migration) {
                if ($migration['time'] === $revision) {
                    foreach ($mHelper['migrations'] as $k => $v) {
                        if ($v === $num) {
                            $revision = $num;
                            $direction == 'Down' ? $k-- : $k++;
                            if (isset($mHelper['migrations'][$k])) {
                                $revision = $mHelper['migrations'][$k];
                            }
                            break;
                        }
                    }
                    break;
                }
            }
        }

        Helper::writeRevisionFile($revision);
        return true;
    }

    public function _runStrategy() {
        $revision = 0;
        $db = Helper::getDbObject();


        if (empty($this->args))
            $this->args[] = 'now';

        $str = implode(' ', $this->args);

        $target_migration = strtotime($str);

        if (false === $target_migration)
            throw new \Exception("Time is not correct");

        $migrations = Helper::getAllMigrations();

        $revisions = Helper::getDatabaseVersions($db);
        if ($revisions === false)
            throw new \Exception('Could not access revisions table');

        if (!empty($revisions)) {
            $revision = max($revisions);
        }
        else {
            Output::error('Revision table is empty. Initial schema not applied properly?');
            exit(1);
        }

        $unapplied_migrations = array_diff($migrations, $revisions);

        if (empty($unapplied_migrations) && $revision == max($migrations) && $target_migration > $revision) {
            echo 'No new migrations available';
            return;
        }
        elseif ($revision < min($migrations) && $target_migration < $revision) {
            echo 'No older migrations available';
            return;
        }
        else {
            echo "Will migrate to: " . date('r', $target_migration) . PHP_EOL . PHP_EOL;
        }

        $direction = $revision <= $target_migration ? 'Up' : 'Down';

        if ($direction === 'Down') {
            $migrations = array_reverse($migrations);

            foreach ($migrations as $migration) {
                if ($migration > $revision)
                    continue;
                //Rollback only applied revisions, skip the others
                if (!in_array($migration, $revisions))
                    continue;
                if ($migration < $target_migration)
                    break;
                echo "ROLLBACK: " . date('r', $migration) . "\n";
                Helper::applyMigration($migration, $db, $direction);
            }
        }
        else {
            foreach ($migrations as $migration) {
                //Apply previously unapplied revisions to "catch up"
                if ($migration <= $revision && in_array($migration, $revisions))
                    continue;
                if ($migration > $target_migration)
                    break;
                echo "APPLY: " . date('r', $migration) . "\n";
                Helper::applyMigration($migration, $db, $direction);
            }
        }
    }

}

