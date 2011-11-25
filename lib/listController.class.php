<?php

namespace lib;

class listController extends AbstractController {

    protected $queries = array();

    public function runStrategy() {
        $migrations = Helper::getAllMigrations();
        $current = Helper::getCurrentRevision();
        printf("Сейчас вы находитесь на ревизии %d\n\n", $current);
        printf(
                $this->draw_text_table(
                        $migrations['data'],
                        array('revn' => '№', 'date' => 'Дата', 'time' => 'Метка времени')
                )
        );
    }

    private function draw_text_table(array $table, array $headers) {
        $cell_lengths = array();
        foreach ($table AS $row) {
            $cell_count = 0;
            foreach ($headers AS $key => $value) {
                $cell = $row[$key];
                $cell_length = mb_strlen($cell);
                $cell_count++;
                if (!isset($cell_lengths[$key]) || $cell_length > $cell_lengths[$key]) {
                    $cell_lengths[$key] = $cell_length;
                }
            }
        }

        print_r($cell_lengths);

        $bar = '+';
        $header = '|';

        foreach ($headers AS $key => $fieldname) {
            $length = $cell_lengths[$key];
            $bar .= str_pad('', $length + 2, '-') . "+";
            if (mb_strlen($fieldname) > $length) {
                $fieldname = mb_substr($fieldname, 0, $length);
            }
            $diff = strlen($fieldname) - mb_strlen($fieldname);
            $header .= ' ' . str_pad($fieldname, $length + $diff, ' ',
                            STR_PAD_RIGHT) . " |";
        }

        // шапка
        $output = sprintf("%s\n%s\n%s\n", $bar, $header, $bar);

        foreach ($table AS $row) {
            $output .= "|";

            foreach ($headers AS $key => $value) {
                $cell = $row[$key];
                $output .= ' ' . str_pad($cell, $cell_lengths[$key], ' ',
                                STR_PAD_RIGHT) . " |";
            }
            $output .= "\n";
        }

        $output .= $bar . "\n";

        return $output;
    }

    public function _runStrategy() {

        $db = Helper::getDbObject();

        $migrations = Helper::getAllMigrations();

        $revisions = Helper::getDatabaseVersions($db);
        $revision = Helper::getDatabaseVersion($db);

        foreach ($migrations as $migration) {
            $prefix = ($migration == $revision) ? ' *** ' : '     ';

            //Mark any unapplied revisions
            if ($migration < $revision && !in_array($migration, $revisions))
                $prefix .= '[n] ';
            else
                $prefix .= '    ';

            echo $prefix . date('r', $migration) . "\n";
        }
    }

}

