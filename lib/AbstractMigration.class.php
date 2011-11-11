<?php

namespace lib;

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
        foreach ($this->up as $query) {
            Output::verbose('UP: ' . $query);
            if ($this->db->query($query))
                Output::verbose("Ok");
            else
                Output::verbose($this->db->error);
        }
        $verT = Helper::get('versiontable');
        $query = "INSERT INTO `{$verT}` SET `rev`={$this->rev}";
        Output::verbose($query);
        $this->db->query($query);
    }

    public function runDown() {
        foreach ($this->down as $query) {
            Output::verbose($query);
            $this->db->query($query);
        }
        $verT = Helper::get('versiontable');
        $query = "DELETE FROM `{$verT}` WHERE `rev`={$this->rev}";
        Output::verbose($query);
        $this->db->query($query);
    }
    
    public function getMetadata() {
        return $this->_metadata;
    }

}