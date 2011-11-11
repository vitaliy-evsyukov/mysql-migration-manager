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
        if (!$this->db->set_charset("utf8")) {
            throw new \Exception("Error loading character set utf8: %s\n", $this->db->error);
        }
        $this->args = $args;
    }

    abstract public function runStrategy();
}