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
        $entities   = array('TABLE', 'PROCEDURE', 'FUNCTION', 'TRIGGER');
        $operations = array(
            'ops'   => array(
                'SHOW %sS',
                "SHOW %s STATUS WHERE Db='" . Helper::get('db') . "'"
            ),
            'links' => array(
                'TABLE'     => 0,
                'TRIGGER'   => 0,
                'FUNCTION'  => 1,
                'PROCEDURE' => 1
            ),
            'cols'  => array(
                'TABLE'     => array(
                    'list' => 0,
                    'def'  => 1
                ),
                'TRIGGER'   => array(
                    'list' => 'Trigger',
                    'def'  => 'SQL Original Statement'
                ),
                'PROCEDURE' => array(
                    'list' => 'Name',
                    'def'  => 'Create Procedure'
                ),
                'FUNCTION'  => array(
                    'list' => 'Name',
                    'def'  => 'Create Function'
                )
            )
        );
        $opts       = array();
        foreach ($this->args as $arg) {
            if (is_string($arg)) {
                $parts = explode('=', $arg);
                if (sizeof($parts) != 2) {
                    $parts[1] = '1';
                }
                $opts[strtoupper(trim($parts[0], '-'))] = $parts[1];
            }
        }
        $path   = Helper::get('schemadir');
        $suffix = md5(time());
        foreach ($entities as $entity) {
            $op      = sprintf(
                $operations['ops'][$operations['links'][$entity]], $entity
            );
            $e_lower = strtolower($entity);
            Helper::initDirs(sprintf('%s%ss', $path, $e_lower));
            Output::verbose(sprintf('Receiving list of %ss', $e_lower), 1);
            $res = $this->db->query($op);
            while ($row = $res->fetch_array(MYSQLI_BOTH)) {
                $col   = $row[$operations['cols'][$entity]['list']];
                $value = $operations['cols'][$entity]['def'];
                if (!empty($opts[$entity])) {
                    if (!preg_match('/^' . $opts[$entity] . '/', $col)) {
                        continue;
                    }
                }
                Output::verbose(
                    sprintf(
                        'Get %s %s description', $e_lower, $col
                    ), 1
                );
                $q    = "SHOW CREATE {$entity} {$col}";
                $desc = $this->db->query($q);
                $data = $desc->fetch_array(MYSQLI_BOTH);
                if (isset($data[$value])) {
                    $filename = sprintf('%s%ss/%s.sql', $path, $e_lower, $col);
                    if (file_exists($filename)) {
                        $c = null;
                        if (is_null($this->_choice)) {
                            $c = $this->askForRewrite($filename);
                        }
                        else {
                            $c = $this->_choice;
                        }
                        if (!$c) {
                            $filename .= $suffix;
                        }
                    }
                    $data[$value] .= str_repeat(
                        ';', (int) ($entity !== 'TABLE') + 1
                    );
                    file_put_contents($filename, $data[$value]);
                }
                else {
                    Output::verbose(
                        sprintf(
                            'Cannot to get description of %s %s',
                            $e_lower, $col
                        ), 1
                    );
                }
            }
        }
        Output::verbose('Files successfully created', 1);
    }

    // TODO: рефакторинг
    protected function askForRewrite($fname) {
        if (Helper::get('quiet')) {
            return true;
        }
        $c = '';
        do {
            if ($c != "\n") {
                printf(
                    "File %s already exists. Do you want to override it? [y/n/Yes to all/No to all] ",
                    $fname
                );
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
