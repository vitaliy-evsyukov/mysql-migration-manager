<?php

namespace lib;

/**
 * listController
 * Показывает список доступных миграций
 * @author Виталий Евсюков
 */

class listController extends AbstractController
{

    /**
     * @var array
     */
    protected $queries = array();

    public function runStrategy()
    {
        $filesystem = $this->container->getFileSystem();
        $migrations = $filesystem->getAllMigrations();
        $current    = $filesystem->getCurrentRevision();
        if (empty($migrations['migrations'])) {
            $this->verbose('There aren\'t revisons', 1);
        } else {
            $this->verbose(sprintf("Current revision: %d\n", $current), 1);
            $this->verbose(
                sprintf(
                    $this->drawTextTable(
                        $migrations['data'],
                        array(
                            'revn' => '#',
                            'date' => 'Date&Time',
                            'time' => 'Timestamp'
                        )
                    )
                ),
                1
            );
        }
        $this->verbose(sprintf("Current revision: %d\n", $current), 1);
    }

    /**
     * Отрисовывает ASCII-таблицу
     * @param array $table   Двумерный массив
     * @param array $headers Заголовки колонок
     * @return string Строка с таблицей
     */
    private function drawTextTable(array $table, array $headers)
    {
        $cell_lengths = array();
        foreach ($table AS $row) {
            $cell_count = 0;
            foreach ($headers AS $key => $value) {
                $cell        = $row[$key];
                $cell_length = mb_strlen($cell);
                $cell_count++;
                if (!isset($cell_lengths[$key]) ||
                    $cell_length > $cell_lengths[$key]
                ) {
                    $cell_lengths[$key] = $cell_length;
                }
            }
        }

        $bar    = '+';
        $header = '|';

        foreach ($headers AS $key => $fieldname) {
            $length = $cell_lengths[$key];
            $bar .= str_pad('', $length + 2, '-') . "+";
            if (mb_strlen($fieldname) > $length) {
                $fieldname = mb_substr($fieldname, 0, $length);
            }
            $diff = strlen($fieldname) - mb_strlen($fieldname);
            $header .= ' ' . str_pad(
                    $fieldname,
                    $length + $diff,
                    ' ',
                    STR_PAD_RIGHT
                ) . " |";
        }

        // шапка
        $output = sprintf("%s\n%s\n%s\n", $bar, $header, $bar);

        foreach ($table AS $row) {
            $output .= "|";

            foreach ($headers AS $key => $value) {
                $cell = $row[$key];
                $output .= ' ' . str_pad(
                        $cell,
                        $cell_lengths[$key],
                        ' ',
                        STR_PAD_RIGHT
                    ) . " |";
            }
            $output .= "\n";
        }

        $output .= $bar . "\n";

        return $output;
    }

}