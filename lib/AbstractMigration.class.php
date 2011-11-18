<?php

namespace lib;
use \Mysqli;

abstract class AbstractMigration {

    /**
     *
     * @var Mysqli
     */
    protected $db;
    protected $up = array();
    protected $down = array();
    protected $rev = 0;
    protected $_metadata = array();

    public function __construct(mysqli $db) {
        $this->db = $db;
    }

    public function runUp() {
        return true;
    }

    public function runDown() {
        return true;
    }
    
    public function getMetadata() {
        return $this->_metadata;
    }

}