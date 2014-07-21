<?php

/**
* Base
* 
* @author guyfawkes
*/

abstract class Base extends PHPUnit_Framework_TestCase
{
    public static function setUpBeforeClass() {
        \lib\Helper::set('verbose', 0);
        parent::setUpBeforeClass();
    }
} 