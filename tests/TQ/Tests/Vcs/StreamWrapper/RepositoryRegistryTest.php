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

namespace TQ\Tests\Vcs\StreamWrapper;

use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use TQ\Vcs\Repository\RepositoryInterface;
use TQ\Vcs\StreamWrapper\RepositoryRegistry;

class RepositoryRegistryTest extends TestCase
{
    /**
     * @return RepositoryInterface|MockObject
     */
    protected function createRepositoryMock()
    {
        return $this->getMockBuilder('TQ\Vcs\Repository\RepositoryInterface')
                    ->getMock();
    }

    public function testAddOneRepository()
    {
        $registry   = new RepositoryRegistry();
        $a          = $this->createRepositoryMock();
        $registry->addRepository('a', $a);
        $this->assertCount(1, $registry);
        $this->assertTrue($registry->hasRepository('a'));
        $this->assertFalse($registry->hasRepository('b'));
        $this->assertSame($a, $registry->getRepository('a'));
    }

    public function testAddArrayOfRepositories()
    {
        $registry   = new RepositoryRegistry();
        $a          = $this->createRepositoryMock();
        $b          = $this->createRepositoryMock();
        $registry->addRepositories(array(
            'a' => $a,
            'b' => $b
        ));
        $this->assertCount(2, $registry);
        $this->assertTrue($registry->hasRepository('a'));
        $this->assertTrue($registry->hasRepository('b'));
        $this->assertFalse($registry->hasRepository('c'));
        $this->assertSame($a, $registry->getRepository('a'));
        $this->assertSame($b, $registry->getRepository('b'));
    }

    public function testGetNonExistentRepositoryThrowsException()
    {
        $this->expectExceptionMessage("a does not exist in the registry");
        $this->expectException(\OutOfBoundsException::class);
        $registry   = new RepositoryRegistry();
        $registry->getRepository('a');
    }

    public function testTryGetNonExistentRepositoryReturnsNull()
    {
        $registry   = new RepositoryRegistry();
        $this->assertNull($registry->tryGetRepository('a'));
    }
}
