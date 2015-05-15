<?php

namespace lib\Helper;

/**
 * IWritable
 * @package lib\Helper
 * @author  guyfawkes
 */

interface IWritable
{
    /**
     * Возвращает название шаблона
     * @return string
     */
    public function getTemplateName();

    /**
     * Возвращает набор строк, которые будут заменены
     * @return array
     */
    public function getPlaceholders();

    /**
     * Возвращает набор замен
     * @param Init $configuration Инстанс конфигуратора приложения для получения из него настроек
     * @return array
     */
    public function getReplacements(Init $configuration);
}