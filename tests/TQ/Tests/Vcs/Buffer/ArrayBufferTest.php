<?php
/*
 * Copyright (C) 2017 by TEQneers GmbH & Co. KG
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

namespace TQ\Tests\Vcs\Buffer;

use PHPUnit\Framework\TestCase;
use TQ\Vcs\Buffer\ArrayBuffer;

class ArrayBufferTest extends TestCase
{
    public function testIteration()
    {
        $listing    = array('a', 'b', 'c');
        $iterator   = new ArrayBuffer($listing);
        $i          = 0;
        while($iterator->valid()) {
            $this->assertEquals($listing[$i], $iterator->current());
            $this->assertEquals($i, $iterator->key());
            $i++;
            $iterator->next();
        }
        $this->assertCount($i, $listing);

        $iterator->rewind();
        $this->assertEquals(reset($listing), $iterator->current());
        $this->assertEquals(key($listing), $iterator->key());
    }

    public function testForeach()
    {
        $listing    = array('a', 'b', 'c');
        $iterator   = new ArrayBuffer($listing);
        $i          = 0;
        foreach ($iterator as $k => $v) {
            $this->assertEquals($listing[$i], $v);
            $this->assertEquals($i, $k);
            $i++;
        }
        $this->assertEquals(count($listing), $i);
    }
}
