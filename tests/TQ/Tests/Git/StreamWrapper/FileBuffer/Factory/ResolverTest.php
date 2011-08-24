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

namespace TQ\Tests\Git\StreamWrapper\FileBuffer\Factory;

use TQ\Git\StreamWrapper\FileBuffer\Factory\Resolver;
use TQ\Git\StreamWrapper\FileBuffer\Factory\Factory;
use TQ\Git\StreamWrapper\PathInformation;

class ResolverTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @return  PathInformation
     */
    protected function createPathMock()
    {
        return $this->getMock(
            'TQ\Git\StreamWrapper\PathInformation',
            array(),
            array(),
            '',
            false
        );
    }

    /**
     * @return  Factory
     */
    protected function createFactoryMock()
    {
        return $this->getMock(
            'TQ\Git\StreamWrapper\FileBuffer\Factory\Factory',
            array('canHandle', 'createFileBuffer')
        );
    }

    public function testReturnsFactoryWhichIsResponsible()
    {
        $resolver   = new Resolver();

        $factory1   = $this->createFactoryMock();
        $factory1->expects($this->any())
                 ->method('canHandle')
                 ->will($this->returnValue(true));
        $factory2   = $this->createFactoryMock();
        $factory2->expects($this->any())
                 ->method('canHandle')
                 ->will($this->returnValue(false));

        $resolver->addFactory($factory1, 10);
        $resolver->addFactory($factory2, 30);

        $path   = $this->createPathMock();

        $this->assertSame($factory1, $resolver->findFactory($path, 'r+'));
    }

    public function testReturnsFactoryWithHigherPriority()
    {
        $resolver   = new Resolver();

        $factory1   = $this->createFactoryMock();
        $factory1->expects($this->any())
                 ->method('canHandle')
                 ->will($this->returnValue(true));
        $factory2   = $this->createFactoryMock();
        $factory2->expects($this->any())
                 ->method('canHandle')
                 ->will($this->returnValue(true));

        $resolver->addFactory($factory1, 10);
        $resolver->addFactory($factory2, 30);

        $path   = $this->createPathMock();

        $this->assertSame($factory2, $resolver->findFactory($path, 'r+'));
    }

    /**
     * @expectedException \RuntimeException
     */
    public function testFailsWithoutAnyFactoryResponsible()
    {
        $resolver   = new Resolver();

        $factory1   = $this->createFactoryMock();
        $factory1->expects($this->any())
                 ->method('canHandle')
                 ->will($this->returnValue(false));
        $factory2   = $this->createFactoryMock();
        $factory2->expects($this->any())
                 ->method('canHandle')
                 ->will($this->returnValue(false));

        $resolver->addFactory($factory1, 10);
        $resolver->addFactory($factory2, 30);

        $path   = $this->createPathMock();

        $resolver->findFactory($path, 'r+');
    }
}

