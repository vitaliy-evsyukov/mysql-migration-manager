<?php

namespace lib;

class initController extends DatasetsController {

    public function runStrategy() {
        if ($this->askForRewriteInformation()) {
            $this->dropAllTables();
            $datasets = $this->args['datasets'];
            $dshash = '';
            if (!empty($datasets)) {
                ksort($datasets);
                $dshash = md5(implode('', array_keys($datasets)));
            }
            try {
                $classname = sprintf(
                        "%s\Schema%s", Helper::get('savedir'), $dshash
                );
                $schema = new $classname;
                $schema->load($this->db);
                printf("Схема %s была успешно развернута\n", $classname);
                Helper::writeRevisionFile(0);
            }
            catch (\Exception $e) {
                printf("Не найдена схема данных\n");
            }
        }
        else {
            printf("Выход без изменений\n");
        }
    }

    private function askForRewriteInformation() {
        $c = '';
        do {
            if ($c != "\n") {
                printf("Вы точно уверены, что желаете перезаписать все таблицы в БД? [y/n] ");
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
