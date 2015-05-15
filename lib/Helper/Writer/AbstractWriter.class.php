<?php

namespace lib\Helper\Writer;

use lib\Helper\IWritable;

/**
* AbstractWriter
* Абстрактный класс записи данных
* @author Виталий Евсюков
* @package lib\Helper\Writer
*/

abstract class AbstractWriter implements IWritable
{
    /**
     * Название шаблона
     * @var string
     */
    protected $tplName;

    /**
     * Набор плейсхолдеров в шаблоне, которые будут заменены
     * @var array
     */
    protected $placeholders = [];

    public function getTemplateName()
    {
        return $this->tplName;
    }

    public function getPlaceholders()
    {
        return $this->placeholders;
    }

    /**
     * Рекурсивно превращает массив любой вложенности в строку
     * @param array  $data   Массив
     * @param int    $level  Уровень начального отступа
     * @param bool   $nowdoc Необходимо ли использовать Nowdoc или делать простые строки
     * @param string $spacer Строка, которой отбивается отступ
     * @return string
     */
    protected function recursiveImplode(
        array $data,
        $level = 1,
        $nowdoc = true,
        $spacer = ' '
    )
    {
        $resultStr = 'array()';
        if (!empty($data)) {
            $result   = array();
            $depth    = str_repeat($spacer, $level * 4);
            $depth2   = str_repeat($spacer, ($level - 1) * 4);
            $last_key = key(array_slice($data, -1, 1, true));
            foreach ($data as $k => $v) {
                $tmp = $depth;
                if (!is_int($k)) {
                    // выведем строковые ключи
                    $tmp = sprintf("%s'%s' => ", $depth, $k);
                }
                if (is_array($v)) {
                    // если значение - массив, рекурсивно обработаем его
                    $tmp .= $this->recursiveImplode(
                        $v,
                        ($level + 1),
                        $nowdoc,
                        $spacer
                    );
                } else {
                    if (is_string($v)) {
                        /**
                         * Если необходимо использовать Nowdoc, применим
                         * ее только для SQL-операторов
                         * array - возможно, в будущем будет вайтлист ключей
                         */
                        if ($nowdoc && !in_array($k, array('type'))) {
                            $tmp .= "<<<'EOT'\n";
                            $tmp .= $v;
                            $tmp .= "\nEOT";
                            if ($k !== $last_key) {
                                $tmp .= "\n";
                            }
                        } else {
                            $tmp .= "'{$v}'";
                        }
                    } else {
                        $tmp .= $v;
                    }
                }
                $result[] = $tmp;
            }
            $sep       = ",\n";
            $resultStr = sprintf("array(\n%s\n%s)", implode($sep, $result), $depth2);
        }

        return $resultStr;
    }
}