<?php
namespace TQ\Tests\Git;

use TQ\Git;

class ConnectorTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @expectedException InvalidArgumentException
     */
    public function testConnectorOnNonExistantPath()
    {
        $c  = new Git\Connector('/does/not/exist');
    }

    /**
     * @expectedException InvalidArgumentException
     */
    public function testConnectorOnFile()
    {
        $c  = new Git\Connector(__FILE__);
    }

    /**
     * @expectedException InvalidArgumentException
     */
    public function testConnectorOnNonRepositoryPath()
    {
        $c  = new Git\Connector('/usr');
    }

    public function testConnectorOnRepositoryPath()
    {
        $c  = new Git\Connector(PROJECT_PATH);
    }
}

