<?php

namespace lib;

/**
 * verifyController
 * Контроллер, занимающийся проверкой локальных (незарегистрированных в миграциях и схеме) изменениях БД пользователя
 * @author guyfawkes
 */

class verifyController extends DatasetsController
{
    /**
     * Запускает основную операцию контроллера
     */
    public function runStrategy()
    {
        Output::verbose('Starting verification of database');
        $currentRevision = Helper::getCurrentRevision();
        $this->args['createSchema']      = false;
        $this->args['useOriginalSchema'] = true;
        Registry::setSchemaType(AbstractSchema::ORIGINAL);
        $db                              = Helper::getTmpDbObject();
        $deployObj                       = Helper::getController('deploy', $this->args, $db);
        $deployObj->runStrategy();
        Registry::setSchemaType(null);
        $diffObj = new dbDiff($db, $this->db);
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
                Output::verbose(
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
                    Output::verbose(
                        sprintf("--- For table %s:\n%s", $tableName, implode("\n", $statementsText))
                    );
                }
            }
        }
        if (!$diffExists) {
            Output::verbose('Your database is actual and equal to original!');
        }
        // восстановим текущую ревизию
        Helper::writeRevisionFile($currentRevision);

        return (bool)$diffExists;
    }

}
