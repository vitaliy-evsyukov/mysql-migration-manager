<?php

/**
 * gcController
 * Удаляет базы данных, оставшиеся после некорректного завершения работы
 * @author Виталий Евсюков
 */

namespace lib;

class gcController extends DatasetsController
{

    public function runStrategy()
    {
        $ignored  = array();
        $continue = true;
        $hInit    = $this->container->getInit();
        while ($continue) {
            $tables   = array();
            $res      = $this->db->query('SHOW DATABASES;');
            $queries  = array();
            $patterns = array(
                '/db_\S{32}/',
                '/' . $hInit->get('db') . '_\S{10}/',
                '/^' . $hInit->get('tmp_db_name') . '(_\S{10})?/',
                '/test_mysqldiff-temp-[\d_]*/',
                '/full_temp_db_\S{10}/'
            );
            while ($row = $res->fetch_array(MYSQLI_NUM)) {
                $flag = false;
                foreach ($patterns as $pattern) {
                    $flag = preg_match($pattern, $row[0]) || $flag;
                    if ($flag) {
                        $name = $row[0];
                        if (!isset($ignored[$name])) {
                            $tables[]  = $name;
                            $queries[] = sprintf("DROP SCHEMA `%s`;", $name);
                            break;
                        }
                    }
                }
            }
            $res->free_result();
            if (!empty($queries)) {
                $list = implode("\n", $queries);
            } else {
                $list     = 'No trash databases found';
                $continue = false;
            }
            $this->verbose($list . "\n ", 2);
            if (!empty($queries)) {
                try {
                    $this->multiQuery($list);
                    $continue = false;
                    $this->verbose("Completed\n", 2);
                } catch (\Exception $e) {
                    $this->verbose("Error occured\n", 2);
                    foreach ($tables as $name) {
                        if (!isset($ignored[$name])) {
                            $ignored[$name] = '1';
                            break;
                        }
                    }
                }
            }
        }
        $this->verbose("Ignored:\n", 2);
        $this->verbose(implode("\n", array_keys($ignored)), 2);
    }
}
