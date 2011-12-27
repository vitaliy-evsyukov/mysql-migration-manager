<?php

namespace lib;

abstract class AbstractController implements IController {

    /**
     *
     * @var Mysqli 
     */
    protected $db = null;
    protected $args = array();

    public function __construct(MysqliHelper $db = null, $args = array()) {
        $this->db = $db;
        Helper::initDirs();
        $this->args = $args;
    }

}