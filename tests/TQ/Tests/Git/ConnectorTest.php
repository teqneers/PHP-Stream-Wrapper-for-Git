<?php
namespace TQ\Tests\Git;

use TQ\Git;

class ConnectorTest extends \PHPUnit_Framework_TestCase
{
    public function testTest() {
        $c  = new Git\Connector();
        $this->assertEquals(1, $c->test());
    }
}

