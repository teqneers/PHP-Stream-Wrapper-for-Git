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

use TQ\Git\Cli\Binary;
use TQ\Git\Repository\Repository;
use TQ\Git\StreamWrapper\StreamWrapper;
use TQ\Tests\Helper;

class FileStatTest extends \PHPUnit_Framework_TestCase
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

        exec(sprintf('cd %s && %s init',
            escapeshellarg(TESTS_REPO_PATH_1),
            GIT_BINARY
        ));

        $path   = TESTS_REPO_PATH_1.'/test.txt';
        file_put_contents($path, 'File 1');
        exec(sprintf('cd %s && %s add %s',
            escapeshellarg(TESTS_REPO_PATH_1),
            GIT_BINARY,
            escapeshellarg($path)
        ));

        exec(sprintf('cd %s && %s commit --message=%s',
            escapeshellarg(TESTS_REPO_PATH_1),
            GIT_BINARY,
            escapeshellarg('Commit 1')
        ));

        $path   = TESTS_REPO_PATH_1.'/directory';
        mkdir($path, 0777);
        file_put_contents($path.'/test.txt', 'Directory File 1');
        exec(sprintf('cd %s && %s add %s',
            escapeshellarg(TESTS_REPO_PATH_1),
            GIT_BINARY,
            escapeshellarg($path)
        ));

        exec(sprintf('cd %s && %s commit --message=%s',
            escapeshellarg(TESTS_REPO_PATH_1),
            GIT_BINARY,
            escapeshellarg('Commit 2')
        ));

        $path   = TESTS_REPO_PATH_1.'/test.txt';
        file_put_contents($path, 'File 1 New');
        exec(sprintf('cd %s && %s add %s',
            escapeshellarg(TESTS_REPO_PATH_1),
            GIT_BINARY,
            escapeshellarg($path)
        ));

        exec(sprintf('cd %s && %s commit --message=%s',
            escapeshellarg(TESTS_REPO_PATH_1),
            GIT_BINARY,
            escapeshellarg('Commit 3')
        ));

        clearstatcache();

        StreamWrapper::register('git', new Binary(GIT_BINARY));
    }

    /**
     * Tears down the fixture, for example, close a network connection.
     * This method is called after a test is executed.
     */
    protected function tearDown()
    {
        Helper::removeDirectory(TESTS_TMP_PATH);

        StreamWrapper::unregister();
    }

    /**
     *
     * @return  Repository
     */
    protected function getRepository()
    {
        return Repository::open(TESTS_REPO_PATH_1, new Binary(GIT_BINARY));
    }

    public function testUrlStatFileWorkingDirectory()
    {
        $filePath   = sprintf('git://%s/test.txt', TESTS_REPO_PATH_1);
        $stat       = stat($filePath);
        $this->assertEquals(26, count($stat));
        $this->assertEquals(0100000, $stat['mode'] & 0100000);
        $this->assertEquals(10, $stat['size']);

    }

    public function testUrlStatDirWorkingDirectory()
    {
        $dirPath    = sprintf('git://%s/directory', TESTS_REPO_PATH_1);
        $stat       = stat($dirPath);
        $this->assertEquals(26, count($stat));
        $this->assertEquals(0040000, $stat['mode'] & 0040000);
    }

    public function testUrlStatFileHistory()
    {
        $filePath   = sprintf('git://%s/test.txt#HEAD^', TESTS_REPO_PATH_1);
        $stat       = stat($filePath);
        $this->assertEquals(26, count($stat));
        $this->assertEquals(0100000, $stat['mode'] & 0100000);
        $this->assertEquals(6, $stat['size']);

    }

    public function testUrlStatDirHistory()
    {
        $dirPath    = sprintf('git://%s/directory#HEAD^', TESTS_REPO_PATH_1);
        $stat       = stat($dirPath);
        $this->assertEquals(26, count($stat));
        $this->assertEquals(0040000, $stat['mode'] & 0040000);
    }
}

