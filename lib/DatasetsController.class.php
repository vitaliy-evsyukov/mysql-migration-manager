<?php

namespace lib;

/**
 * DatasetsController
 * Общий класс для контроллеров, связанных с датасетами
 * @author guyfawkes
 */
abstract class DatasetsController extends AbstractController {

    protected $_datasetInfo = array();

    public function __construct($db, $args) {
        // вынести в разбор параметров
        foreach ($args as $index => $arg) {
            if (is_string($arg)) {
                $arg_data = explode('=', $arg);
                $param_name = str_replace('-', '', $arg_data[0]);
                $param_value = str_replace('"', '', $arg_data[1]);
                $args[$param_name] = $param_value;
                unset($args[$index]);
            }
        }

        if (!empty($args['datasets'])) {
            if (is_string($args['datasets'])) {
                $datasets = explode(',', $args['datasets']);
                $args['datasets'] = array();
                foreach ($datasets as $dataset) {
                    $args['datasets'][trim($dataset)] = 1;
                }
            }
        } else {
            $args['datasets'] = array();
        }

        parent::__construct($db, $args);
    }

    /**
     * Загружает данные датасетов
     * @return array 
     */
    protected function loadDatasetInfo() {
        $load_data = (empty($this->args['loadData']) xor true);
        return Helper::getDatasetInfo($this->args['datasets'], $load_data);
    }

}

?>
