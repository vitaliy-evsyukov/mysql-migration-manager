<?php

namespace lib;

/**
 * migrateController
 * Выполняет миграции
 * @author guyfawkes
 */
class migrateController extends DatasetsController
{

    protected $queries = array();

    protected $_argKeys = array('verify');

    public function runStrategy()
    {
        $mHelper = Helper::getAllMigrations();

        if (empty($mHelper['migrations'])) {
            Output::verbose("No revisions have been created", 1);

            return false;
        }
        $minMigration = current($mHelper['migrations']);
        Output::verbose(
            sprintf('Minimal migration number is %d', $minMigration),
            1
        );
        $maxMigration = end($mHelper['migrations']);

        if (!isset($this->args['revision'])) {
            $revision = Helper::getCurrentRevision();
        } else {
            $revision = $this->args['revision'];
        }

        if ($revision > $maxMigration) {
            $revision = $maxMigration;
        }

        Output::verbose(sprintf('You are at revision %d', $revision), 1);
        if (!isset($this->args['m'])) {
            $this->args['m'] = 'now';
        }
        $str = $this->args['m'];
        if (is_numeric($str)) {
            $search_migration = (int) $str;
            if ($search_migration !== 0) {
                $class            = sprintf(
                    '%s\Migration%d',
                    Helper::get('savedir_ns'),
                    $search_migration
                );
                $o                = new $class;
                $meta             = $o->getMetadata();
                $target_migration = $meta['timestamp'];
            } else {
                $target_migration = 0;
            }
        } else {
            $target_migration = strtotime($str);
        }

        if (false === $target_migration) {
            throw new \Exception(
                sprintf("Incorrect migration value %s", $str)
            );
        }

        $datasets   = $this->args['datasets'];
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
        } else {
            $start_str = 'initial revision';
        }

        if ($revision === $maxMigration && $target_migration >= $timestamp) {
            Output::verbose('There are no newer migrations', 1);

            return false;
        } else {
            Output::verbose(
                sprintf(
                    "Starting migration from %s (revision %d) to %s\n",
                    $start_str,
                    $revision,
                    $target_str
                ),
                1
            );
        }

        $isModified = false;
        if (isset($this->args['verify'])) {
            $verifyObj  = Helper::getController('verify', $this->args, $this->db);
            $isModified = $verifyObj->runStrategy();
        }

        if ($isModified) {
            Output::verbose('Migration canceled');

            return false;
        }

        $direction = 'Up';
        if ($revision > 0) {
            $direction = $mHelper['data'][$revision]['time'] <= $target_migration ? 'Up' : 'Down';
        }

        Helper::setCurrentDb($this->db, 'Migrate controller');
        $timeline = Helper::getTimeline($tablesList, true, ($direction === 'Down'));

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
                        sprintf(
                            "%s skipped, because is is lesser %s",
                            $time_str,
                            $start_str
                        ),
                        2
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
                        sprintf(
                            "%s skipped, because is less or equal %s",
                            $time_str,
                            $target_str
                        ),
                        2
                    );
                    break;
                }
            } else {
                if ($time <= $timestamp) {
                    Output::verbose(
                        sprintf(
                            "%s skipped, because is less or equal %s\n",
                            $time_str,
                            $start_str
                        ),
                        2
                    );
                    continue;
                }
                if ($time > $target_migration) {
                    Output::verbose(
                        sprintf(
                            "%s skipped, because is greater %s\n",
                            $time_str,
                            $target_str
                        ),
                        2
                    );
                    break;
                }
                $revision = $time;
            }

            foreach ($tables as $tablename => $rev) {
                if (is_int($rev)) {
                    Output::verbose(
                        sprintf(
                            "Executing migration for %s (# %d)\n",
                            $time_str,
                            $rev
                        ),
                        1
                    );
                    // обратимся к нужному классу
                    if (!isset($usedMigrations[$rev])) {
                        Output::verbose(
                            sprintf(
                                "Execute migration in database %s for tables:\n--- %s",
                                $this->db->getDatabaseName(),
                                implode("\n--- ", array_keys($tables))
                            ),
                            2
                        );
                        Helper::applyMigration(
                            $rev,
                            $this->db,
                            $direction,
                            $tables
                        );
                        $usedMigrations[$rev] = 1;
                        break;
                    }
                } else {
                    // это SQL-запрос
                    Output::verbose(
                        sprintf(
                            "Executing SQL for table: %s",
                            $tablename
                        ),
                        1
                    );
                    $this->db->query($rev);
                }
            }
        }

        if ($target_migration === 0) {
            $revision = 0;
        } else {
            if (isset($mHelper['timestamps'][$revision])) {
                $revision = $mHelper['timestamps'][$revision];
            }
        }

        if ((int) $revision !== 1) {
            Helper::writeRevisionFile($revision);
        }

        // если принудительно не запретили создавать схему
        if (!isset($this->args['createSchema']) || ($this->args['createSchema'] !== false)) {
            $this->createMigratedSchema($revision);
        }

        return true;
    }

    /**
     * Создает мигрированную схему
     * @param int $revision Ревизия, для которой создается схема
     */
    public function createMigratedSchema($revision)
    {
        $revision = (int) $revision;
        if ($revision !== 1) {
            $tmpDir = sys_get_temp_dir() . '/tmp_schema/';
            Output::verbose(
                sprintf(
                    'Create schema after migration with revision %d, files will be saved in folder %s',
                    $revision,
                    $tmpDir
                ),
                1
            );
            $this->removeTmpSchemaDir($tmpDir);
            $chain                   = Helper::getController('getsql', $this->args, $this->db);
            $this->args['revision']  = $revision;
            $this->args['notDeploy'] = true;
            // получить схему нужно для всех таблиц, которые есть в БД, но записать как мигрированную, если датасеты переданы
            $this->args['excludeDatasets'] = 0;
            $sandbox                       = array('schemadir' => $tmpDir);
            $schemaObj                     = Helper::getController('schema', $this->args, $this->db);
            $chain->setNext($schemaObj);
            $chain->setSandbox(
                array(
                     'getsql' => $sandbox,
                     'schema' => $sandbox
                )
            );
            $chain->runStrategy();
            $this->removeTmpSchemaDir($tmpDir);
            Output::verbose(
                sprintf(
                    'Migrated schema\'s creation with revision %d finished, folder %s removed',
                    $revision,
                    $tmpDir
                ),
                1
            );
        }
    }

    /**
     * Рекурсивно удаляет папку, куда сложена временная съема
     * @param string $tmpDir
     */
    private function removeTmpSchemaDir($tmpDir)
    {
        if (is_dir($tmpDir)) {
            /**
             * Удаление вложенных папок и файлов и затем удаление директории
             */
            $it = new \RecursiveIteratorIterator(
                new \RecursiveDirectoryIterator($tmpDir),
                \RecursiveIteratorIterator::CHILD_FIRST
            );
            foreach ($it as $file) {
                if (in_array($file->getBasename(), array('.', '..'))) {
                    continue;
                } elseif ($file->isDir()) {
                    rmdir($file->getPathname());
                } elseif ($file->isFile() || $file->isLink()) {
                    unlink($file->getPathname());
                }
            }
            rmdir($tmpDir);
        }
    }

}