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

namespace TQ\Tests\Git\Cli;

use TQ\Git\Cli\Binary;
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

    public function testHandleSingleDash()
    {
        $binary = new Binary('/usr/bin/git');
        $call   = $binary->createGitCall('/', 'command', array(
            '-a'
        ));
        $this->assertCliCommandEquals("/usr/bin/git 'command' -'a'", $call->getCmd());
    }

    public function testHandleDoubleDash()
    {
        $binary = new Binary('/usr/bin/git');
        $call   = $binary->createGitCall('/', 'command', array(
            '--argument'
        ));
        $this->assertCliCommandEquals("/usr/bin/git 'command' --'argument'", $call->getCmd());
    }

    public function testHandleSingleDashWithValue()
    {
        $binary = new Binary('/usr/bin/git');
        $call   = $binary->createGitCall('/', 'command', array(
            '-a' => 'value'
        ));
        $this->assertCliCommandEquals("/usr/bin/git 'command' -'a' 'value'", $call->getCmd());
    }

    public function testHandleDoubleDashWithValue()
    {
        $binary = new Binary('/usr/bin/git');
        $call   = $binary->createGitCall('/', 'command', array(
            '--argument' => 'value'
        ));
        $this->assertCliCommandEquals("/usr/bin/git 'command' --'argument'='value'", $call->getCmd());
    }

    public function testIgnoreLoneDoubleDash()
    {
        $binary = new Binary('/usr/bin/git');
        $call   = $binary->createGitCall('/', 'command', array(
            '--'
        ));
        $this->assertCliCommandEquals("/usr/bin/git 'command'", $call->getCmd());
    }

    public function testSimpleArgument()
    {
        $binary = new Binary('/usr/bin/git');
        $call   = $binary->createGitCall('/', 'command', array(
            'option'
        ));
        $this->assertCliCommandEquals("/usr/bin/git 'command' 'option'", $call->getCmd());
    }

    public function testFilePathAsArgument()
    {
        $binary = new Binary('/usr/bin/git');
        $call   = $binary->createGitCall('/', 'command', array(
            '/path/to/file'
        ));
        $this->assertCliCommandEquals("/usr/bin/git 'command' '/path/to/file'", $call->getCmd());
    }

    public function testFileModeSwitch()
    {
        $binary = new Binary('/usr/bin/git');
        $call   = $binary->createGitCall('/', 'command', array(
            'option',
            '--',
            'path/to/file'
        ));
        $this->assertCliCommandEquals("/usr/bin/git 'command' 'option' -- 'path/to/file'", $call->getCmd());
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
        $this->assertCliCommandEquals("/usr/bin/git 'command' 'option' '/path/to/file' -- 'path/to/file'", $call->getCmd());
    }
}

