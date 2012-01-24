<?php

namespace lib;

/**
 * createController
 * Создает ревизию и сохраняет ее
 * @author guyfawkes
 */
class createController extends DatasetsController {

    protected $queries = array();
    private $_tempDb = null;

    public function setTempDb(MysqliHelper $tempDb) {
        $this->_tempDb = $tempDb;
    }

    public function runStrategy() {
        if (!$this->_tempDb) {
            $tempDb = Helper::getTmpDbObject();
            Helper::loadTmpDb($tempDb);
        } else {
            $tempDb = $this->_tempDb;
        }
        Output::verbose('Starting to search changes', 1);
        $diffObj = new dbDiff($this->db, $tempDb);
        $diff = $diffObj->getDifference();
        Output::verbose('Search of changes completed', 1);
        if (!empty($diff['up']) || !empty($diff['down'])) {
            $revision = Helper::getLastRevision();
            $file_exists = true;
            $migrationFileName = '';
            while ($file_exists) {
                $migrationFileName = DIR . Helper::get('savedir') . DIR_SEP . "Migration{$revision}.class.php";
                if (is_file($migrationFileName)) {
                    Output::verbose(
                        sprintf(
                            "Revision # %d already exists, file name: %s",
                            $revision, $migrationFileName
                        ), 2
                    );
                    $revision++;
                }
                else {
                    $file_exists = false;
                }
            }
            Output::verbose(sprintf('Try to create revision %d', $revision), 2);
            $timestamp = Helper::writeRevisionFile($revision);
            $content = Helper::createMigrationContent(
                $revision, $diff, $timestamp
            );
            file_put_contents($migrationFileName, $content);
            Output::verbose(
                sprintf("Revision %d successfully created and saved in file %s",
                    $revision, $migrationFileName), 1
            );
        }
        else {
            Output::verbose('There are no changes in database structure now', 1);
        }
    }

}
