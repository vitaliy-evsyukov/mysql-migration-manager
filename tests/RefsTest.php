<?php

use lib\Helper;

class RefsTest extends Base
{
    public function testRefsList()
    {
        $r = array(
            'managers'        => array(
                'new' => 1
            ),
            'new'             => array(
                'new_2'    => 1,
                'new_3'    => 1,
                'managers' => 1
            ),
            'managers_groups' => array(
                'new' => 1
            ),
            'new_3'           => array(
                'new_4' => 1
            ),
            'new_4'           => array(
                'new_5' => 1
            )
        );

        $t = array(
            'managers'        => 1,
            'managers_groups' => 1
        );

        $res = static::getContainer()->getMigrations()->getTablesReferences($r, $t);

        $e = array(
            'managers'        => 1,
            'new'             => 1,
            'new_2'           => 1,
            'new_3'           => 1,
            'managers_groups' => 1,
            'new_4'           => 1,
            'new_5'           => 1
        );

        $this->assertEquals($e, $res);
    }
}