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
use TQ\Git\Cli\CallResult;

class CallResultTest extends \PHPUnit_Framework_TestCase
{
    public function testSuccessfulCall()
    {
        $binary = new Binary(GIT_BINARY);
        $call   = $binary->createGitCall('/', '', array(
            '--version'
        ));
        $result = $call->execute();

        $this->assertTrue($result->hasStdOut());
        $this->assertFalse($result->hasStdErr());
        $this->assertEmpty($result->getStdErr());
        $this->assertEquals(0, $result->getReturnCode());
        $this->assertStringStartsWith('git version', $result->getStdOut());
        $this->assertSame($call, $result->getCliCall());
    }

    public function testFailedCall()
    {
        $binary = new Binary(GIT_BINARY);
        $call   = $binary->createGitCall('/', 'unknowncommand', array());
        $result = $call->execute();

        $this->assertFalse($result->hasStdOut());
        $this->assertTrue($result->hasStdErr());
        $this->assertEmpty($result->getStdOut());
        $this->assertNotEmpty($result->getStdErr());
        $this->assertGreaterThan(0, $result->getReturnCode());
    }
}

