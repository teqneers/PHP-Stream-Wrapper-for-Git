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

namespace TQ\Tests\Svn\Repository;

use TQ\Svn\Cli\Binary;
use TQ\Svn\Repository\Repository;
use TQ\Tests\Helper;

class SetupTest extends \PHPUnit_Framework_TestCase
{
    /**
     * Sets up the fixture, for example, open a network connection.
     * This method is called before a test is executed.
     */
    protected function setUp()
    {
        Helper::removeDirectory(TESTS_TMP_PATH);
        Helper::createDirectory(TESTS_TMP_PATH);
        Helper::createDirectory(TESTS_REPO_PATH_1);
        Helper::createDirectory(TESTS_REPO_PATH_2);

        Helper::initEmptySvnRepository(TESTS_REPO_PATH_1);

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

    /**
     * @param   string          $path
     * @return  Repository
     */
    protected function getRepository($path)
    {
        return Repository::open($path, new Binary(SVN_BINARY));
    }

    /**
     * @expectedException \InvalidArgumentException
     */
    public function testRepositoryOpenOnNonExistentPath()
    {
        $this->getRepository('/does/not/exist');
    }

    /**
     * @expectedException \InvalidArgumentException
     */
    public function testRepositoryOpenOnNonRepositoryPath()
    {
        $this->getRepository(TESTS_REPO_PATH_2);
    }

    public function testRepositoryOpenOnRepositoryPath()
    {
        $c  = $this->getRepository(TESTS_REPO_PATH_1);
        $this->assertInstanceOf('TQ\Svn\Repository\Repository', $c);
        $this->assertEquals(TESTS_REPO_PATH_1, $c->getRepositoryPath());
    }

    public function testRepositoryOpenOnSubPathOfRepositoryPath()
    {
        Helper::createDirectory(TESTS_REPO_PATH_1.'/test');

        $c  = $this->getRepository(TESTS_REPO_PATH_1.'/test');
        $this->assertInstanceOf('TQ\Svn\Repository\Repository', $c);
        $this->assertEquals(TESTS_REPO_PATH_1, $c->getRepositoryPath());
    }
}

