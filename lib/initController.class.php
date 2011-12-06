<?php

namespace lib;

class initController extends DatasetsController {

    public function runStrategy() {
        if ($this->askForRewriteInformation()) {
            $datasets = $this->args['datasets'];
            $dshash = '';
            if (!empty($datasets)) {
                ksort($datasets);
                $dshash = md5(implode('', array_keys($datasets)));
            }
            try {
                $classname = sprintf(
                        "%s\Schema%s",
                        str_replace('/', '\\', Helper::get('cachedir')), $dshash
                );
                $schema = new $classname;
                $this->dropAllTables();
                $schema->load($this->db);
                printf("Schema %s was successfully deployed\n", $classname);
                Helper::writeRevisionFile(0);
            }
            catch (\Exception $e) {
                Output::verbose('Schema not found', 1);
                Output::verbose($e->getMessage(), 2);
            }
        }
        else {
            Output::verbose("Exit without any changes", 1);
        }
    }

    private function askForRewriteInformation() {
        if (Helper::get('quiet')) {
            return true;
        }
        $c = '';
        do {
            if ($c != "\n") {
                printf("Are you really shure you want to delete ALL tables in DB [y/n] ");
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
