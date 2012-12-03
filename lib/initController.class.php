<?php

namespace lib;

/**
 * initController
 * Приводит содержимое схемы к начальному
 * @author guyfawkes
 */
class initController extends DatasetsController {

    public function runStrategy() {
        if ($this->askForRewriteInformation()) {
            $datasets = $this->args['datasets'];
            $dshash   = '';
            if (!empty($datasets)) {
                ksort($datasets);
                $dshash = md5(implode('', array_keys($datasets)));
            }
            try {
                $this->dropAllDBEntities();
                $classname = sprintf(
                    '%s\Schema%s', Helper::get('cachedir_ns'), $dshash
                );
                $schema    = new $classname;
                $schema->load($this->db);
                Output::verbose(
                    sprintf(
                        "Schema %s was successfully deployed", $classname
                    ), 1
                );
                Helper::writeRevisionFile(0);
            }
            catch (\Exception $e) {
                Output::verbose('Schema not found', 1);
                Output::verbose($e->getMessage(), 3);
                $schema = Helper::getController('schema');
                $schema->runStrategy();
            }
        }
        else {
            Output::verbose("Exit without any changes", 1);
        }
    }

    /**
     * Запрашивает удаление всех таблиц в БД
     * @return bool
     */
    private function askForRewriteInformation() {
        if (Helper::get('quiet')) {
            return true;
        }
        $c = '';
        do {
            if ($c != "\n") {
                Output::verbose(
                    "Are you really shure you want to delete ALL tables in DB [y/n] ",
                    1
                );
            }
            $c = trim(fgets(STDIN));

            if ($c === 'Y' or $c === 'y') {
                return true;
            }
            if ($c === 'N' or $c === 'n') {
                return false;
            }
        }
        while (true);
    }

}
