<?php

namespace lib;

use \Mysqli;

abstract class AbstractController implements IController {

    /**
     *
     * @var Mysqli 
     */
    protected $db = null;
    protected $args = array();

    public function __construct(Mysqli $db = null, $args = array()) {
        $this->db = $db;
        if ($this->db && !$this->db->set_charset("utf8")) {
            throw new \Exception(sprintf("SET CHARACTER SET utf8 error: %s\n", $this->db->error));
        }
        $this->args = $args;
    }
    
}