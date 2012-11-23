<?php

/**
 * gcController
 * Удаляет базы данных, оставшиеся после некорректного завершения работы
 * @author guyfawkes
 */

namespace lib;

class gcController extends DatasetsController
{

    public function runStrategy()
    {
        $res      = $this->db->query('SHOW DATABASES;');
        $queries  = array();
        $patterns = array(
            '/db_\S{32}/',
            '/' . Helper::get('db') . '_\S{10}/',
            '/^' . Helper::get('tmp_db_name') . '(_\S{10})?/',
            '/test_mysqldiff-temp-[\d_]*/',
            '/full_temp_db_\S{10}/'
        );
        while ($row = $res->fetch_array(MYSQLI_NUM)) {
            $flag = false;
            foreach ($patterns as $pattern) {
                $flag = preg_match($pattern, $row[0]) || $flag;
                if ($flag) {
                    $queries[] = sprintf("DROP SCHEMA `%s`;", $row[0]);
                    break;
                }
            }
        }
        $res->free_result();
        if (!empty($queries)) {
            $list = implode("\n", $queries);
        }
        else {
            $list = 'No trash databases found';
        }
        Output::verbose($list, 2);
        if (!empty($queries)) {
            $this->multiQuery($list);
        }
    }

}

?>
