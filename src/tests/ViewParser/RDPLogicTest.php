<?php

use PHPUnit\Framework\TestCase;
use slenderize\ViewParser as Parser;

class RDPLogicTest extends TestCase
{
    /**
     * @dataProvider expectedAndActuals
     */
    public function testExpectedAndActual($base_name)
    {
        $rdp = new Parser();
        $this->assertEquals($rdp->parse(dirname(__FILE__) . '/views/' . $base_name), true);
        $this->assertEquals($rdp->compose(), file_get_contents(dirname(__FILE__) . '/views/' . $base_name . '-expected'));
    }

    public function expectedAndActuals(): array
    {
        return require(dirname(__FILE__) . '/config/runthese.php');
    }
}
