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

namespace TQ\Tests\Git\StreamWrapper\FileBuffer;

use TQ\Git\StreamWrapper\FileBuffer\StreamBuffer;
use TQ\Tests\Helper;

class StreamBufferTest extends \PHPUnit_Framework_TestCase
{
    /**
     * Sets up the fixture, for example, open a network connection.
     * This method is called before a test is executed.
     */
    protected function setUp()
    {
        Helper::removeDirectory(TESTS_TMP_PATH);
        mkdir(TESTS_TMP_PATH, 0777, true);

        file_put_contents(TESTS_TMP_PATH.'/file_0.txt', 'File 0');
        file_put_contents(TESTS_TMP_PATH.'/abc.txt', 'ABCDEFGHIJKLMNOPQRSTUVWXYZ');
        touch(TESTS_TMP_PATH.'/empty.txt');

        clearstatcache();
    }

    /**
     * Tears down the fixture, for example, close a network connection.
     * This method is called after a test is executed.
     */
    protected function tearDown()
    {
        Helper::removeDirectory(TESTS_TMP_PATH);
    }

    public function testReadByByte()
    {
        $expected   = 'File 0';
        $buffer     = new StreamBuffer(TESTS_TMP_PATH.'/file_0.txt');
        $expLength  = strlen($expected);
        for ($i = 0; $i < $expLength; $i++) {
            $this->assertEquals($i, $buffer->getPosition());
            $char = $buffer->read(1);
            $this->assertEquals($expected[$i], $char);
            $this->assertEquals($i + 1, $buffer->getPosition());
        }
    }

    public function testSeek()
    {
        $expected   = 'File 0';
        $buffer     = new StreamBuffer(TESTS_TMP_PATH.'/file_0.txt');

        $buffer->setPosition(-1, SEEK_END);
        $this->assertEquals('0', $buffer->read(1));
        $this->assertEquals(6, $buffer->getPosition());
        $this->assertFalse($buffer->isEof());
        $this->assertEmpty($buffer->read(1));
        $this->assertTrue($buffer->isEof());

        $buffer->setPosition(0, SEEK_SET);
        $this->assertEquals('F', $buffer->read(1));
        $this->assertEquals(1, $buffer->getPosition());

        $buffer->setPosition(3, SEEK_CUR);
        $this->assertEquals(' ', $buffer->read(1));
        $this->assertEquals(5, $buffer->getPosition());

        $buffer->setPosition(-2, SEEK_CUR);
        $this->assertEquals('e', $buffer->read(1));
        $this->assertEquals(4, $buffer->getPosition());
    }

    public function testReadInReverse()
    {
        $buffer     = new StreamBuffer(TESTS_TMP_PATH.'/file_0.txt');
        $expected   = '0 eliF';
        $actual     = '';

        $buffer->setPosition(-1, SEEK_END);
        while (($pos = $buffer->getPosition()) > 0) {
            $actual .= $buffer->read(1);
            $buffer->setPosition(-2, SEEK_CUR);
        }
        $actual .= $buffer->read(1);
        $this->assertEquals($expected, $actual);
    }

    public function testWriteInMiddle()
    {
        $buffer     = new StreamBuffer(TESTS_TMP_PATH.'/abc.txt');
        $expected   = 'ABC1234567890NOPQRSTUVWXYZ';
        $buffer->setPosition(3, SEEK_SET);
        $buffer->write('1234567890');
        $actual     = $buffer->getBuffer();
        $this->assertEquals($expected, $actual);
    }

    public function testWriteAtStart()
    {
        $buffer     = new StreamBuffer(TESTS_TMP_PATH.'/abc.txt');
        $expected   = '1234567890KLMNOPQRSTUVWXYZ';
        $buffer->setPosition(0, SEEK_SET);
        $buffer->write('1234567890');
        $actual     = $buffer->getBuffer();
        $this->assertEquals($expected, $actual);
    }

    public function testWriteAtEnd()
    {
        $buffer     = new StreamBuffer(TESTS_TMP_PATH.'/abc.txt');
        $expected   = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ1234567890';
        $buffer->setPosition(0, SEEK_END);
        $buffer->write('1234567890');
        $actual     = $buffer->getBuffer();
        $this->assertEquals($expected, $actual);
    }

    public function testWriteOverlappingEnd()
    {
        $buffer     = new StreamBuffer(TESTS_TMP_PATH.'/abc.txt');
        $expected   = 'ABCDEFGHIJKLMNOPQRSTUVW1234567890';
        $buffer->setPosition(-3, SEEK_END);
        $buffer->write('1234567890');
        $actual     = $buffer->getBuffer();
        $this->assertEquals($expected, $actual);
    }

    public function testWriteInEmptyBuffer()
    {
        $buffer     = new StreamBuffer(TESTS_TMP_PATH.'/empty.txt');
        $expected   = '1234567890';
        $buffer->write('1234567890');
        $actual     = $buffer->getBuffer();
        $this->assertEquals($expected, $actual);
    }
}

