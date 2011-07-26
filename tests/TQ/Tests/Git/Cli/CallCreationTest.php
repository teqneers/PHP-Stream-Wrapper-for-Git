<?php
namespace TQ\Tests\Git\Cli;

use TQ\Git\Cli\Binary;

class CallCreationTest extends \PHPUnit_Framework_TestCase
{
    public function testHandleSingleDash()
    {
        $binary = new Binary('/usr/bin/git');
        $call   = $binary->createGitCall('/', 'command', array(
            '-a'
        ));
        $this->assertEquals("/usr/bin/git 'command' -'a'", $call->getCmd());
    }

    public function testHandleDoubleDash()
    {
        $binary = new Binary('/usr/bin/git');
        $call   = $binary->createGitCall('/', 'command', array(
            '--argument'
        ));
        $this->assertEquals("/usr/bin/git 'command' --'argument'", $call->getCmd());
    }

    public function testHandleSingleDashWithValue()
    {
        $binary = new Binary('/usr/bin/git');
        $call   = $binary->createGitCall('/', 'command', array(
            '-a' => 'value'
        ));
        $this->assertEquals("/usr/bin/git 'command' -'a' 'value'", $call->getCmd());
    }

    public function testHandleDoubleDashWithValue()
    {
        $binary = new Binary('/usr/bin/git');
        $call   = $binary->createGitCall('/', 'command', array(
            '--argument' => 'value'
        ));
        $this->assertEquals("/usr/bin/git 'command' --'argument'='value'", $call->getCmd());
    }

    public function testIgnoreLoneDoubleDash()
    {
        $binary = new Binary('/usr/bin/git');
        $call   = $binary->createGitCall('/', 'command', array(
            '--'
        ));
        $this->assertEquals("/usr/bin/git 'command'", $call->getCmd());
    }

    public function testSimpleArgument()
    {
        $binary = new Binary('/usr/bin/git');
        $call   = $binary->createGitCall('/', 'command', array(
            'option'
        ));
        $this->assertEquals("/usr/bin/git 'command' 'option'", $call->getCmd());
    }

    public function testFilePathArgumentDetection()
    {
        $binary = new Binary('/usr/bin/git');
        $call   = $binary->createGitCall('/', 'command', array(
            '/path/to/file'
        ));
        $this->assertEquals("/usr/bin/git 'command' -- '/path/to/file'", $call->getCmd());
    }

    public function testFileModeSwitch()
    {
        $binary = new Binary('/usr/bin/git');
        $call   = $binary->createGitCall('/', 'command', array(
            'option',
            '--',
            'path/to/file'
        ));
        $this->assertEquals("/usr/bin/git 'command' 'option' -- 'path/to/file'", $call->getCmd());
    }

    public function testFileModeSwitchWithFileArgument()
    {
        $binary = new Binary('/usr/bin/git');
        $call   = $binary->createGitCall('/', 'command', array(
            'option',
            '/path/to/file',
            '--',
            'path/to/file'
        ));
        $this->assertEquals("/usr/bin/git 'command' 'option' -- '/path/to/file' 'path/to/file'", $call->getCmd());
    }
}

