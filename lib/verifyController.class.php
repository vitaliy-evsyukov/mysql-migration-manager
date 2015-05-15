<?php

namespace lib;

/**
 * verifyController
 * Контроллер, занимающийся проверкой локальных (незарегистрированных в миграциях и схеме) изменениях БД пользователя
 * @author Виталий Евсюков
 */

class verifyController extends DatasetsController
{
    /**
     * Запускает основную операцию контроллера
     */
    public function runStrategy()
    {
        $filesystem = $this->container->getFileSystem();
        $migrations = $this->container->getMigrations();
        $database   = $this->container->getDb();
        $this->verbose('Starting verification of database');
        $currentRevision                 = $filesystem->getCurrentRevision();
        $this->args['createSchema']      = false;
        $this->args['useOriginalSchema'] = true;
        $migrations->setSchemaType(AbstractSchema::ORIGINAL);
        $db        = $database->getTmpDbObject();
        $deployObj = $this->container->getInit()->getController('deploy', $this->args, $db);
        $deployObj->runStrategy();
        $migrations->setSchemaType(null);
        $diffObj = new DbDiff($this->container->getInit()->get('mysqldiff_command'), $db, $this->db);
        $diffObj->setOutput($this->container->getOutput());
        $diff    = $diffObj->getDiff();

        $info = array(
            'up'   => array(
                'first'  => 'your',
                'second' => 'original'
            ),
            'down' => array(
                'first'  => 'original',
                'second' => 'your'
            )
        );

        $diffExists = 0;
        foreach ($info as $direction => $data) {
            if (!empty($diff[$direction])) {
                $diffExists++;
                $this->verbose(
                    sprintf(
                        'Statements which must be executed on %s database to make it similar to %s database:',
                        $data['first'],
                        $data['second']
                    ),
                    1
                );
                foreach ($diff[$direction] as $tableName => $statements) {
                    $statementsText = array();
                    foreach ($statements as $statement) {
                        $statementsText[] = '------ ' . $statement['sql'];
                    }
                    $this->verbose(
                        sprintf("--- For table %s:\n%s", $tableName, implode("\n", $statementsText))
                    );
                }
            }
        }
        if (!$diffExists) {
            $this->verbose('Your database is actual and equal to original!');
        }
        // восстановим текущую ревизию
        $filesystem->writeRevisionFile($currentRevision);

        return (bool) $diffExists;
    }

}
