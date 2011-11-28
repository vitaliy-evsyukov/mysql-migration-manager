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
            printf("Не указаны наборы данных\n");
            return false;
        }
        if (!isset($this->args['loadData']))  {
            $this->args['loadData'] = true;
        }
        $datasets = $this->loadDatasetInfo();
        $this->db->query('START TRANSACTION;');
        // вынести хранимки для автороллбека транзакций в шаблон
        
        foreach ($datasets['sqlContent'] as $dataset => $query) {
            printf("Разворачивается набор данных %s\n", $dataset);
            // если кто-то знает решение обработки ошибок лучше - подскажите
            $this->multiQuery($query, true);
        }
        printf("Разворачивание наборов данных успешно завершено\n");
    }

}

?>
