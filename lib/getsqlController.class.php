<?php

/**
 * getsqlController
 * Получить описания всех таблиц
 * @author guyfawkes
 */

namespace lib;

class getsqlController extends AbstractController {

    private $_choice = null;

    public function runStrategy() {
        $res = $this->db->query('SHOW TABLES');
        $path = DIR . Helper::get('schemadir') . DIR_SEP;
        while ($row = $res->fetch_array(MYSQLI_NUM)) {
            Output::verbose(sprintf('Get table %s description', $row[0]), 1);
            $q = "SHOW CREATE TABLE {$row[0]}";
            $desc = $this->db->query($q);
            $data = $desc->fetch_row();
            $filename = sprintf('%s%s', $path, $row[0]);
            $c = null;
            if (is_null($this->_choice)) {
                $c = $this->askForRewrite($filename . '.sql');
            } else {
                $c = $this->_choice;
            }
            if (!$c) {
                $filename .= md5(time());
            }
            $filename .= '.sql';
            file_put_contents($filename, $data[1]);
        }
        Output::verbose('Files successfully created', 1);
    }

    // TODO: рефакторинг
    protected function askForRewrite($fname) {
        if (Helper::get('quiet') || !file_exists($fname))
            return true;
        $c = '';
        do {
            if ($c != "\n") {
                printf("File %s already exists. Do you want to override it? [y/n/Yes to all/No to all] ",
                        $fname);
            }
            $c = trim(fgets(STDIN));
            if ($c === 'Y' or $c === 'y') {
                if ($c === 'Y') {
                    $this->_choice = true;
                }
                return true;
            }
            if ($c === 'N' or $c === 'n') {
                if ($c === 'N') {
                    $this->_choice = false;
                }
                return false;
            }
        }
        while (true);
    }

}

?>
