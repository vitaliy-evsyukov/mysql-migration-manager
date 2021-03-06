<?php

namespace lib\Helper\Writer;

use lib\Helper\Init;

/**
 * Migration
 * Запись данных о миграциях
 * @author  Виталий Евсюков
 * @package lib\Helper\Writer
 */
class Migration extends AbstractWriter
{
    protected $tplName = 'tpl/migration.tpl';

    protected $placeholders = array('revision', 'up', 'down', 'meta', 'ns');

    /**
     * Метка времени UNIX для момента создания миграции
     * @var int
     */
    private $timestamp;

    /**
     * Версия (ревизия) миграции
     * @var int
     */
    private $version;

    /**
     * Набор отличий в базах данных, которыми по сути и является миграция
     * @var array
     */
    private $diffs = [];

    /**
     * @param array $diffs     Набор разниц в базах
     * @param int   $version   Версия миграций
     * @param int   $timestamp Время создания
     */
    public function __construct(array $diffs, $version, $timestamp)
    {
        $this->diffs     = $diffs;
        $this->version   = (int) $version;
        $this->timestamp = (int) $timestamp;
    }

    public function getReplacements(Init $configuration)
    {
        $metadata = array(
            'timestamp' => $this->timestamp,
            'tables'    => $this->diffs['tables']['used'],
            'refs'      => $this->diffs['tables']['refs'],
            'revision'  => $this->version
        );
        unset($this->diffs['tables']);

        return array(
            $this->version,
            $this->recursiveImplode($this->diffs['up'], 2),
            $this->recursiveImplode($this->diffs['down'], 2),
            $this->recursiveImplode($metadata, 2),
            $configuration->get('savedir_ns')
        );
    }
}