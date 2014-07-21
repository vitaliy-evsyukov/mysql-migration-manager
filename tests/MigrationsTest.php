<?php

class MigrationsTest extends Base
{

    private $_timeline = null;
    private $_dict = null;
    private static $_messages = array();

    public function setUp()
    {
        $this->_timeline = array(
            0          => array(
                'table1' => array(
                    'stmt11',
                    'stmt12'
                ),
                'table2' => array(
                    'stmt21',
                    'stmt22'
                )
            ),
            1322727273 => array(
                'table3' => array(
                    'stmt31',
                    'stmt32',
                    'stmt33',
                    'stmt34'
                ),
                'table4' => array(
                    'stmt41'
                ),
                'table5' => array(
                    'stmt51',
                    'stmt52',
                    'stmt53'
                ),
                'table6' => array(
                    'stmt61',
                    'stmt62'
                )
            ),
            1323345416 => array(
                'table7' => array(
                    'stmt71',
                    'stmt72',
                    'stmt73'
                )
            ),
            1323345447 => array(
                'table8'  => array(
                    'stmt81',
                    'stmt82',
                    'stmt83'
                ),
                'table9'  => array(
                    'stmt91',
                    'stmt92'
                ),
                'table10' => array(
                    'stmt101'
                ),
            )
        );
        $this->_dict     = array(
            1 => 1322727273,
            2 => 1323345416,
            3 => 1323345447
        );
    }

    private function migrate($start, $stop = '')
    {
        if (empty($stop)) {
            $stop = 'now';
        }
        if (is_numeric($stop)) {
            $search_migration = (int) $stop;
            if ($search_migration !== 0) {
                $target_migration = $this->_dict[$search_migration];
            } else {
                $target_migration = 0;
            }
        } else {
            $target_migration = strtotime($stop, 1323351278);
        }

        $direction = 'Up';
        if ($start > 0) {
            $direction = $this->_dict[$start] <= $target_migration ? 'Up' : 'Down';
        }

        $timeline = $this->_timeline;
        if ($direction === 'Down') {
            $timeline = array_reverse($timeline, 1);
        }

        $timestamp = 0;
        if ($start > 0) {
            $timestamp = $this->_dict[$start];
        }

        $revision          = 0;
        $i                 = $start > 0 ? $this->_dict[$start] : 0;
        self::$_messages[] = "Initial migration: {$i}; Target migration: $target_migration";
        foreach ($timeline as $time => $tables) {
            self::$_messages[] = "Start: $start; Stop: $stop; Time: $time";
            if ($direction == 'Down') {
                /*
                 * Если ревизия произошла после таймпстампа, от которого мы
                 * спускаемся вниз, пропускаем
                 */
                if ($time > $timestamp) {
                    self::$_messages[] = ' skipped';
                    continue;
                }
                /*
                 * Если прошли минимально подходящую ревизию, остановимся
                 */
                $revision = $time;
                if ($time <= $target_migration) {
                    self::$_messages[] = ' breaked';
                    break;
                }
            } else {
                if ($time <= $timestamp) {
                    self::$_messages[] = ' skipped';
                    continue;
                }
                if ($time > $target_migration) {
                    self::$_messages[] = ' breaked';
                    break;
                }
                $revision = $time;
            }
            self::$_messages[] = ' migrated';
        }

        self::$_messages[] = "Revision: $revision";
        if ($revision > 0) {
            $tmp = array_flip($this->_dict);
            return $tmp[$revision];
        }
        return 0;
    }

    public function testMigrateNum()
    {
        $res = $this->migrate(0, 2);
        $this->assertEquals(2, $res);
        $res = $this->migrate(3, 1);
        $this->assertEquals(1, $res);
        $res = $this->migrate(2);
        $this->assertEquals(3, $res);
    }

    public function testMigrateDate()
    {
        $res = $this->migrate(0, '+10 days');
        $this->assertEquals(3, $res);
        $res = $this->migrate(3, '-5 days');
        $this->assertEquals(1, $res);
        $res = $this->migrate(3, '11:57:00 UTC');
        $this->assertEquals(2, $res);
        $res = $this->migrate(1, '11:57:00 UTC');
        $this->assertEquals(2, $res);
    }

    public static function tearDownAfterClass()
    {
        echo implode("\n", self::$_messages);
    }

}

?>