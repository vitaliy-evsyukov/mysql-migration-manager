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
        $this->db->query('START TRANSACTION;');
        // вынести хранимки для автороллбека транзакций в шаблон
        foreach ($datasets['sqlContent'] as $dataset => $query) {
            // если кто-то знает решение обработки ошибок лучше - подскажите
            $this->multiQuery($query, true);
        }
    }

}

?>
