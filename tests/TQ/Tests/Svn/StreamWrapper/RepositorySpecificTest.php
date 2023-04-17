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

namespace TQ\Tests\Svn\StreamWrapper;

use PHPUnit\Framework\TestCase;
use TQ\Svn\Cli\Binary;
use TQ\Svn\Repository\Repository;
use TQ\Svn\StreamWrapper\StreamWrapper;
use TQ\Tests\Helper;

class RepositorySpecificTest extends TestCase
{
    /**
     * Sets up the fixture, for example, open a network connection.
     * This method is called before a test is executed.
     */
    protected function setUp(): void
    {
        Helper::removeDirectory(TESTS_TMP_PATH);
        Helper::createDirectory(TESTS_TMP_PATH);

        $repositories  = array(TESTS_REPO_PATH_1, TESTS_REPO_PATH_2, TESTS_REPO_PATH_3);

        foreach ($repositories as $n => $repository) {
            Helper::createDirectory($repository);
            Helper::initEmptySvnRepository($repository);

             for ($i = 0; $i < 5; $i++) {
                $file   = sprintf('file_%d.txt', $i);
                $path   = $repository.'/'.$file;
                file_put_contents($path, sprintf('Repository %d - File %d', $n + 1, $i + 1));
                Helper::executeSvn($repository, sprintf('add %s',
                    escapeshellarg($file)
                ));
            }

            Helper::executeSvn($repository, sprintf('commit --message=%s',
                escapeshellarg('Initial commit')
            ));
        }

        clearstatcache();

        $binary = new Binary(SVN_BINARY);
        StreamWrapper::register('svn', $binary);
        StreamWrapper::getRepositoryRegistry()->addRepositories(
            array(
                'repo1' => Repository::open(TESTS_REPO_PATH_1, $binary),
                'repo2' => Repository::open(TESTS_REPO_PATH_2, $binary),
            )
        );
    }

    /**
     * Tears down the fixture, for example, close a network connection.
     * This method is called after a test is executed.
     */
    protected function tearDown(): void
    {
        Helper::removeDirectory(TESTS_TMP_PATH);

        StreamWrapper::unregister();
    }

    public function testReadGlobal()
    {
        $file   = sprintf('svn://%s/file_0.txt', TESTS_REPO_PATH_3);
        $this->assertEquals('Repository 3 - File 1', file_get_contents($file));
    }

    public function testReadFromRepositoryInRegistry1()
    {
        $file   = 'svn://repo1/file_0.txt';
        $this->assertEquals('Repository 1 - File 1', file_get_contents($file));
    }

    public function testReadFromRepositoryInRegistry2()
    {
        $file   = 'svn://repo2/file_0.txt';
        $this->assertEquals('Repository 2 - File 1', file_get_contents($file));
    }

    public function testListDirectoryGlobal()
    {
        $path   = 'svn://'.TESTS_REPO_PATH_3;
        $dir    = opendir($path);
        $i      = 0;
        while ($f = readdir($dir)) {
            $this->assertEquals(sprintf('file_%d.txt', $i), $f);
            $this->assertEquals(
                sprintf('Repository %d - File %d', 3, $i + 1),
                file_get_contents($path.'/'.$f)
            );
            $i++;
        }
        closedir($dir);
        $this->assertEquals(5, $i);
    }

    public function testListDirectoryInRegistry1()
    {
        $path   = 'svn://repo1';
        $dir    = opendir($path);
        $i      = 0;
        while ($f = readdir($dir)) {
            $this->assertEquals(sprintf('file_%d.txt', $i), $f);
            $this->assertEquals(
                sprintf('Repository %d - File %d', 1, $i + 1),
                file_get_contents($path.'/'.$f)
            );
            $i++;
        }
        closedir($dir);
        $this->assertEquals(5, $i);
    }

    public function testListDirectoryInRegistry2()
    {
        $path   = 'svn://repo2';
        $dir    = opendir($path);
        $i      = 0;
        while ($f = readdir($dir)) {
            $this->assertEquals(sprintf('file_%d.txt', $i), $f);
            $this->assertEquals(
                sprintf('Repository %d - File %d', 2, $i + 1),
                file_get_contents($path.'/'.$f)
            );
            $i++;
        }
        closedir($dir);
        $this->assertEquals(5, $i);
    }
}
