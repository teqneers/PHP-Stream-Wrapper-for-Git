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

namespace TQ\Tests\Vcs\StreamWrapper;

use PHPUnit\Framework\TestCase;
use TQ\Vcs\StreamWrapper\PathFactoryInterface;

class PathFactoryTest extends TestCase
{
    /**
     * @dataProvider parsePathDataProvider
     *
     * @param   string  $path
     * @param   array   $expected
     */
    public function testParsePath($path, array $expected)
    {
        /** @var $factory PathFactoryInterface */
        $factory    = $this->getMockForAbstractClass(
            'TQ\Vcs\StreamWrapper\AbstractPathFactory',
            array('protocol')
        );

        $actual     = $factory->parsePath($path);
        $this->assertEquals($expected, $actual);
    }

    /**
     * @return  array
     */
    public function parsePathDataProvider(): array
    {
        return array(
            array(
                'protocol:///path/to/file',
                array(
                    'scheme' => 'protocol',
                    'host'   => '__global__',
                    'path'   => '/path/to/file'
                )
            ),
            array(
                'protocol:///path/to/file#HEAD^',
                array(
                    'scheme'   => 'protocol',
                    'host'     => '__global__',
                    'path'     => '/path/to/file',
                    'fragment' => 'HEAD^'
                )
            ),
            array(
                'protocol:///path/to/file?commit',
                array(
                    'scheme' => 'protocol',
                    'host'   => '__global__',
                    'path'   => '/path/to/file',
                    'query'  => 'commit'
                )
            ),
            array(
                'protocol:///C:\path\to\file',
                array(
                    'scheme' => 'protocol',
                    'host'   => '__global__',
                    'path'   => 'C:/path/to/file'
                )
            ),
            array(
                'protocol://repo1/path/to/file',
                array(
                    'scheme' => 'protocol',
                    'host'   => 'repo1',
                    'path'   => '/path/to/file'
                )
            ),
        );
    }
}
