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
 
namespace TQ\Tests\Git\StreamWrapper;

use TQ\Git\StreamWrapper\FileBuffer;

class FileBufferTest extends \PHPUnit_Framework_TestCase
{
    public function testReadByByte()
    {
        $expected   = 'File 0';
        $buffer     = new FileBuffer($expected);
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
        $buffer     = new FileBuffer($expected);

        $buffer->setPosition(-1, SEEK_END);
        $this->assertEquals('0', $buffer->read(1));
        $this->assertEquals(6, $buffer->getPosition());
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
        $buffer     = new FileBuffer('File 0');
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
        $buffer     = new FileBuffer('ABCDEFGHIJKLMNOPQRSTUVWXYZ');
        $expected   = 'ABC1234567890NOPQRSTUVWXYZ';
        $buffer->setPosition(3, SEEK_SET);
        $buffer->write('1234567890');
        $actual     = $buffer->getBuffer();
        $this->assertEquals($expected, $actual);
    }

    public function testWriteAtStart()
    {
        $buffer     = new FileBuffer('ABCDEFGHIJKLMNOPQRSTUVWXYZ');
        $expected   = '1234567890KLMNOPQRSTUVWXYZ';
        $buffer->setPosition(0, SEEK_SET);
        $buffer->write('1234567890');
        $actual     = $buffer->getBuffer();
        $this->assertEquals($expected, $actual);
    }

    public function testWriteAtEnd()
    {
        $buffer     = new FileBuffer('ABCDEFGHIJKLMNOPQRSTUVWXYZ');
        $expected   = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ1234567890';
        $buffer->setPosition(0, SEEK_END);
        $buffer->write('1234567890');
        $actual     = $buffer->getBuffer();
        $this->assertEquals($expected, $actual);
    }

    public function testWriteOverlappingEnd()
    {
        $buffer     = new FileBuffer('ABCDEFGHIJKLMNOPQRSTUVWXYZ');
        $expected   = 'ABCDEFGHIJKLMNOPQRSTUVW1234567890';
        $buffer->setPosition(-3, SEEK_END);
        $buffer->write('1234567890');
        $actual     = $buffer->getBuffer();
        $this->assertEquals($expected, $actual);
    }

    public function testWriteInEmptyBuffer()
    {
        $buffer     = new FileBuffer('');
        $expected   = '1234567890';
        $buffer->write('1234567890');
        $actual     = $buffer->getBuffer();
        $this->assertEquals($expected, $actual);
    }
}

