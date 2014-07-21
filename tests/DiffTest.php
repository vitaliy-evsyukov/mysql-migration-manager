<?php

/**
 * DiffTest
 *
 * @author guyfawkes
 */

class DiffTest extends Base
{
    public function providerParse()
    {
        return array(
            array(
                'simple.sql',
                'simple.php'
            ),
            array(
                'referenced.sql',
                'double.php'
            ),
            array(
                'referenced_tripple.sql',
                'tripple.php'
            )
        );
    }

    /**
     * @dataProvider providerParse
     * @param string $filename
     * @param        $expected
     */
    public function testParse($filename, $expected)
    {
        $service  = new \lib\dbDiff();
        $filename = __DIR__ . DIR_SEP . 'files' . DIR_SEP . $filename;
        $result   = $service->parseDiff(explode("\n", file_get_contents($filename)));
        $expected = __DIR__ . DIR_SEP . 'files' . DIR_SEP . $expected;
        $compare = null;
        include $expected;
        $this->assertSame($compare, $result);
    }
}