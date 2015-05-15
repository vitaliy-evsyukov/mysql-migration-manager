<?php

/**
 * Base
 *
 * @author guyfawkes
 */
abstract class Base extends PHPUnit_Framework_TestCase
{
    /**
     * @var \lib\Helper\Container
     */
    protected static $container;

    /**
     * @return \lib\Helper\Container
     */
    protected static function getContainer()
    {
        if (!static::$container) {
            static::$container = new \lib\Helper\Container();
        }
        return static::$container;
    }

    public static function setUpBeforeClass()
    {
        static::getContainer()->getInit()->set('verbose', 0);
        parent::setUpBeforeClass();
    }
} 