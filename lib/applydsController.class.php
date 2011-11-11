<?php

namespace lib;

/**
 * applydsController
 * Применяет указанные датасеты
 * @author guyfawkes
 */
class applydsController extends DatasetsController {

    public function runStrategy() {
        $datasets = $this->args['datasets'];
        if (empty($datasets)) {
            throw new \Exception("Не указаны наборы данных\n");
        }
        $datasets = $this->loadDatasetInfo();
        $this->db->query('SET foreign_key_checks = 0;');
        $this->db->query('START TRANSACTION;');
        // вынести хранимки для автороллбека транзакций в шаблон
        foreach ($datasets['sqlContent'] as $dataset => $query) {
            // если кто-то знает решение обработки ошибок лучше - подскажите
            try {
                $ret = $this->db->multi_query($query);
                $text = $this->db->error;
                $code = $this->db->errno;
                if (!$ret) {
                    throw new \Exception($text, $code);
                }
                do {
                } while ($this->db->next_result());
                $text = $this->db->error;
                $code = $this->db->errno;
                if ($code) {
                    throw new \Exception($text, $code);
                }
                $this->db->query('COMMIT;');
            } catch (Exception $e) {
                $this->db->query('ROLLBACK;');
                throw new \Exception("Произошла ошибка\n{$e->getMessage()} ({$e->getCode()})");
            }
        }

        $this->db->query('SET foreign_key_checks = 1;');
    }

}

?>
