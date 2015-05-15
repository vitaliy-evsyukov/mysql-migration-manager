<?php

namespace lib\Helper\Writer;

use lib\Helper\Init;

/**
 * References
 * Запись данных о связях таблиц
 * @author  Виталий Евсюков
 * @package lib\Helper\Writer
 */
class References extends AbstractWriter
{
    protected $tplName = 'tpl/references.tpl';

    protected $placeholders = array('ns', 'refs', 'hash');

    /**
     * Набор связей таблиц
     * @var array
     */
    private $references = [];

    /**
     * Хеш от набора таблиц, для которых получены связи
     * @var string
     */
    private $hash;

    /**
     * @param array  $references Набор связей
     * @param string $hash       Хеш таблиц
     */
    public function __construct(array $references, $hash)
    {
        $this->references = $references;
        $this->hash       = $hash;
    }

    public function getReplacements(Init $configuration)
    {
        return array(
            $configuration->get('cachedir_ns'),
            $this->recursiveImplode($this->references, 2),
            $this->hash
        );
    }
}