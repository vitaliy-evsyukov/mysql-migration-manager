<?php

namespace lib;

abstract class AbstractController {

    /**
     *
     * @var Mysqli 
     */
    protected $db = null;
    protected $args = array();

    function __construct($db = null, $args = array()) {
        $this->db = $db;
        $this->args = $args;
    }

    abstract public function runStrategy();
}