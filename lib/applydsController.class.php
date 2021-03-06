<?php

namespace lib;

/**
 * applydsController
 * Применяет указанные датасеты
 * @author Виталий Евсюков
 */
class applydsController extends DatasetsController
{

    public function runStrategy()
    {
        $datasets = $this->args['datasets'];
        if (empty($datasets)) {
            $this->verbose('Datasets were not passed', 1);
            return false;
        }
        if (!isset($this->args['loadData'])) {
            $this->args['loadData'] = true;
        }
        $datasets = $this->loadDatasetInfo();
        if (!empty($datasets['sqlContent'])) {
            $this->db->query('START TRANSACTION;');
            // вынести хранимки для автороллбека транзакций в шаблон

            foreach ($datasets['sqlContent'] as $dataset => $query) {
                $this->verbose(sprintf("Deploy dataset %s\n", $dataset), 1);
                // если кто-то знает решение обработки ошибок лучше - подскажите
                $this->multiQuery($query, true);
            }
            $this->verbose('Deploy of datasets completed', 1);
        }
        return true;
    }
}
