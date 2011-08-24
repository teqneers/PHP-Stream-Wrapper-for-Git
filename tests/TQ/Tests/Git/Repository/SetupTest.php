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

namespace TQ\Tests\Git\Repository;

use TQ\Git\Cli\Binary;
use TQ\Git\Repository\Repository;
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
        mkdir(TESTS_TMP_PATH, 0777, true);
        mkdir(TESTS_REPO_PATH_1, 0777, true);
        mkdir(TESTS_REPO_PATH_2, 0777, true);

        exec(sprintf('cd %s && %s init',
            escapeshellarg(TESTS_REPO_PATH_1),
            GIT_BINARY
        ));

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
     *
     * @param   string          $path
     * @paran   boolean|integer $create
     * @return  Repository
     */
    protected function getRepository($path, $create = false)
    {
        return Repository::open($path, new Binary(GIT_BINARY), $create);
    }

    /**
     * @expectedException InvalidArgumentException
     */
    public function testRepositoryOpenOnNonExistantPath()
    {
        $c  = $this->getRepository('/does/not/exist', false);
    }

    public function testRepositoryOpenOnFile()
    {
        $c  = $this->getRepository(__FILE__, false);
        $this->assertInstanceOf('TQ\Git\Repository\Repository', $c);
        $this->assertEquals(PROJECT_PATH, $c->getRepositoryPath());
    }

    /**
     * @expectedException InvalidArgumentException
     */
    public function testRepositoryOpenOnNonRepositoryPath()
    {
        $c  = $this->getRepository('/usr', false);
    }

    public function testRepositoryOpenOnRepositoryPath()
    {
        $c  = $this->getRepository(TESTS_REPO_PATH_1, false);
        $this->assertInstanceOf('TQ\Git\Repository\Repository', $c);
    }

    public function testRepositoryCreateOnExistingRepositoryPath()
    {
        $c  = $this->getRepository(TESTS_REPO_PATH_1, 0755);
        $this->assertInstanceOf('TQ\Git\Repository\Repository', $c);
    }

    public function testRepositoryCreateOnFile()
    {
        $c  = $this->getRepository(__FILE__, 0755);
        $this->assertInstanceOf('TQ\Git\Repository\Repository', $c);
        $this->assertEquals(PROJECT_PATH, $c->getRepositoryPath());
    }

    public function testRepositoryCreateOnExistingPath()
    {
        $c  = $this->getRepository(TESTS_REPO_PATH_2, 0755);
        $this->assertInstanceOf('TQ\Git\Repository\Repository', $c);
    }

    public function testRepositoryCreateOnCreateablePath()
    {
        $c  = $this->getRepository(TESTS_REPO_PATH_3, 0755);
        $this->assertInstanceOf('TQ\Git\Repository\Repository', $c);
    }
}

