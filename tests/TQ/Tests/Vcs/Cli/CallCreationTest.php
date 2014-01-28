<?php
/*
 * Copyright (C) 2011 by TEQneers GmbH & Co. KG
 *
 * Permission is hereby granted, free of charge, to any person obtaining a copy
 * of this software and associated documentation files (the "Software"), to deal
 * in the Software without restriction, including without limitation the rights
 * to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
 * copies of the Software, and to permit persons to whom the Software is
 * furnished to do so, subject to the following conditions:
 *
 * The above copyright notice and this permission notice shall be included in
 * all copies or substantial portions of the Software.
 *
 * THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
 * IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
 * FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
 * AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
 * LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
 * OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN
 * THE SOFTWARE.
 */

namespace TQ\Tests\Vcs\Cli;

use TQ\Vcs\Cli\Binary;
use TQ\Tests\Helper;

class CallCreationTest extends \PHPUnit_Framework_TestCase
{
    protected function assertCliCommandEquals($expected, $actual)
    {
        if (strpos(PHP_OS, 'WIN') !== false) {
            $expected = Helper::normalizeEscapeShellArg($expected);
        }
        $this->assertEquals($expected, $actual);
    }

    /**
     * @return Binary
     */
    protected function createBinaryMock() {
        return $this->getMockForAbstractClass('TQ\Vcs\Cli\Binary', array('/usr/bin/command'));
    }

    public function testHandleSingleDash()
    {
        $binary = $this->createBinaryMock();
        $call   = $binary->createCall('/', 'command', array(
            '-a'
        ));
        $this->assertCliCommandEquals("/usr/bin/command 'command' -'a'", $call->getCmd());
    }

    public function testHandleDoubleDash()
    {
        $binary = $this->createBinaryMock();
        $call   = $binary->createCall('/', 'command', array(
            '--argument'
        ));
        $this->assertCliCommandEquals("/usr/bin/command 'command' --'argument'", $call->getCmd());
    }

    public function testHandleSingleDashWithValue()
    {
        $binary = $this->createBinaryMock();
        $call   = $binary->createCall('/', 'command', array(
            '-a' => 'value'
        ));
        $this->assertCliCommandEquals("/usr/bin/command 'command' -'a' 'value'", $call->getCmd());
    }

    public function testHandleDoubleDashWithValue()
    {
        $binary = $this->createBinaryMock();
        $call   = $binary->createCall('/', 'command', array(
            '--argument' => 'value'
        ));
        $this->assertCliCommandEquals("/usr/bin/command 'command' --'argument'='value'", $call->getCmd());
    }

    public function testIgnoreLoneDoubleDash()
    {
        $binary = $this->createBinaryMock();
        $call   = $binary->createCall('/', 'command', array(
            '--'
        ));
        $this->assertCliCommandEquals("/usr/bin/command 'command'", $call->getCmd());
    }

    public function testSimpleArgument()
    {
        $binary = $this->createBinaryMock();
        $call   = $binary->createCall('/', 'command', array(
            'option'
        ));
        $this->assertCliCommandEquals("/usr/bin/command 'command' 'option'", $call->getCmd());
    }

    public function testFilePathAsArgument()
    {
        $binary = $this->createBinaryMock();
        $call   = $binary->createCall('/', 'command', array(
            '/path/to/file'
        ));
        $this->assertCliCommandEquals("/usr/bin/command 'command' '/path/to/file'", $call->getCmd());
    }

    public function testFileModeSwitch()
    {
        $binary = $this->createBinaryMock();
        $call   = $binary->createCall('/', 'command', array(
            'option',
            '--',
            'path/to/file'
        ));
        $this->assertCliCommandEquals("/usr/bin/command 'command' 'option' -- 'path/to/file'", $call->getCmd());
    }

    public function testFileModeSwitchWithFileArgument()
    {
        $binary = $this->createBinaryMock();
        $call   = $binary->createCall('/', 'command', array(
            'option',
            '/path/to/file',
            '--',
            'path/to/file'
        ));
        $this->assertCliCommandEquals("/usr/bin/command 'command' 'option' '/path/to/file' -- 'path/to/file'", $call->getCmd());
    }
}

