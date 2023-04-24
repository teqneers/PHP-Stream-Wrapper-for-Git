<?php
/*
 * Copyright (C) 2023 by TEQneers GmbH & Co. KG
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

namespace TQ\Tests\Vcs\StreamWrapper\FileBuffer;

use PHPUnit\Framework\TestCase;
use TQ\Vcs\StreamWrapper\FileBuffer\Factory;
use TQ\Vcs\StreamWrapper\FileBuffer\FactoryInterface;
use TQ\Vcs\StreamWrapper\PathInformation;

class FactoryTest extends TestCase
{
    /**
     * @return  PathInformation
     */
    protected function createPathMock()
    {
        return $this->getMockBuilder('TQ\Vcs\StreamWrapper\PathInformation')
                    ->disableOriginalConstructor()
                    ->getMock();
    }

    /**
     * @return  FactoryInterface|\PHPUnit\Framework\MockObject\MockObject
     */
    protected function createFactoryMock()
    {
        return $this->getMockBuilder('TQ\Vcs\StreamWrapper\FileBuffer\FactoryInterface')
                    ->onlyMethods(array('canHandle', 'createFileBuffer'))
                    ->getMock();
    }

    public function testReturnsFactoryWhichIsResponsible()
    {
        $factory   = new Factory();

        $factory1   = $this->createFactoryMock();
        $factory1->expects($this->any())
                 ->method('canHandle')
                 ->will($this->returnValue(true));
        $factory2   = $this->createFactoryMock();
        $factory2->expects($this->any())
                 ->method('canHandle')
                 ->will($this->returnValue(false));

        $factory->addFactory($factory1, 10);
        $factory->addFactory($factory2, 30);

        $path   = $this->createPathMock();

        $this->assertSame($factory1, $factory->findFactory($path, 'r+'));
    }

    public function testReturnsFactoryWithHigherPriority()
    {
        $factory   = new Factory();

        $factory1   = $this->createFactoryMock();
        $factory1->expects($this->any())
                 ->method('canHandle')
                 ->will($this->returnValue(true));
        $factory2   = $this->createFactoryMock();
        $factory2->expects($this->any())
                 ->method('canHandle')
                 ->will($this->returnValue(true));

        $factory->addFactory($factory1, 10);
        $factory->addFactory($factory2, 30);

        $path   = $this->createPathMock();

        $this->assertSame($factory2, $factory->findFactory($path, 'r+'));
    }

    public function testFailsWithoutAnyFactoryResponsible()
    {
        $this->expectException(\RuntimeException::class);
        $factory   = new Factory();

        $factory1   = $this->createFactoryMock();
        $factory1->expects($this->any())
                 ->method('canHandle')
                 ->will($this->returnValue(false));
        $factory2   = $this->createFactoryMock();
        $factory2->expects($this->any())
                 ->method('canHandle')
                 ->will($this->returnValue(false));

        $factory->addFactory($factory1, 10);
        $factory->addFactory($factory2, 30);

        $path   = $this->createPathMock();

        $factory->findFactory($path, 'r+');
    }
}
