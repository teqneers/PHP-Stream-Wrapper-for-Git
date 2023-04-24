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

namespace TQ\Tests\Git\StreamWrapper;

use PHPUnit\Framework\TestCase;
use TQ\Git\Cli\Binary;
use TQ\Git\Repository\Repository;
use TQ\Git\StreamWrapper\StreamWrapper;
use TQ\Tests\Helper;

class FileOperationTest extends TestCase
{
    /**
     * Sets up the fixture, for example, open a network connection.
     * This method is called before a test is executed.
     */
    protected function setUp(): void
    {
        Helper::removeDirectory(TESTS_TMP_PATH);
        Helper::createDirectory(TESTS_TMP_PATH);
        Helper::createDirectory(TESTS_REPO_PATH_1);

        Helper::initEmptyGitRepository(TESTS_REPO_PATH_1);

        for ($i = 0; $i < 5; $i++) {
            $file   = sprintf('file_%d.txt', $i);
            $path   = TESTS_REPO_PATH_1.'/'.$file;
            file_put_contents($path, sprintf('File %d', $i));
            Helper::executeGit(TESTS_REPO_PATH_1, sprintf('add %s',
                escapeshellarg($file)
            ));
        }

        Helper::executeGit(TESTS_REPO_PATH_1, sprintf('commit --message=%s',
            escapeshellarg('Initial commit')
        ));

        clearstatcache();

        StreamWrapper::register('git', new Binary(GIT_BINARY));
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

    /**
     *
     * @return  Repository
     */
    protected function getRepository(): Repository
    {
        return Repository::open(TESTS_REPO_PATH_1, new Binary(GIT_BINARY));
    }

    public function testUnlinkFile()
    {
        $path   = sprintf('git://%s/file_0.txt', TESTS_REPO_PATH_1);
        unlink($path);

        $this->assertFileDoesNotExist(TESTS_REPO_PATH_1.'/file_0.txt');

        $c      = $this->getRepository();
        $commit = $c->showCommit($c->getCurrentCommit());
        $this->assertStringContainsString('--- a/file_0.txt', $commit);
    }

    public function testUnlinkFileWithContext()
    {
        $path   = sprintf('git://%s/file_0.txt', TESTS_REPO_PATH_1);
        $cntxt  = stream_context_create(array(
            'git'   => array(
                'commitMsg' => 'Hello World',
                'author'    => 'Luke Skywalker <skywalker@deathstar.com>'
            )
        ));
        unlink($path, $cntxt);

        $c      = $this->getRepository();
        $commit = $c->showCommit($c->getCurrentCommit());
        $this->assertStringContainsString('Hello World', $commit);
        $this->assertStringContainsString('Luke Skywalker <skywalker@deathstar.com>', $commit);
    }

    public function testRenameFile()
    {
        $pathFrom   = sprintf('git://%s/file_0.txt', TESTS_REPO_PATH_1);
        $pathTo     = sprintf('git://%s/test.txt', TESTS_REPO_PATH_1);
        rename($pathFrom, $pathTo);

        $this->assertFileDoesNotExist(TESTS_REPO_PATH_1.'/file_0.txt');
        $this->assertFileExists(TESTS_REPO_PATH_1.'/test.txt');

        $c      = $this->getRepository();
        $commit = $c->showCommit($c->getCurrentCommit());

        $this->assertStringContainsString('file_0.txt', $commit);
        $this->assertStringContainsString('test.txt', $commit);
    }

    public function testRenameFileWithContext()
    {
        $pathFrom   = sprintf('git://%s/file_0.txt', TESTS_REPO_PATH_1);
        $pathTo     = sprintf('git://%s/test.txt', TESTS_REPO_PATH_1);
        $cntxt      = stream_context_create(array(
            'git'   => array(
                'commitMsg' => 'Hello World',
                'author'    => 'Luke Skywalker <skywalker@deathstar.com>'
            )
        ));
        rename($pathFrom, $pathTo, $cntxt);

        $c      = $this->getRepository();
        $commit = $c->showCommit($c->getCurrentCommit());
        $this->assertStringContainsString('Hello World', $commit);
        $this->assertStringContainsString('Luke Skywalker <skywalker@deathstar.com>', $commit);
    }

    public function testRmdirDirectory()
    {
        $c  = $this->getRepository();
        $c->writeFile('directory/test.txt', 'Test');
        $this->assertFileExists(TESTS_REPO_PATH_1.'/directory');
        $this->assertFileExists(TESTS_REPO_PATH_1.'/directory/test.txt');

        $path   = sprintf('git://%s/directory', TESTS_REPO_PATH_1);
        rmdir($path);

        $this->assertFileDoesNotExist(TESTS_REPO_PATH_1.'/directory');

        $c      = $this->getRepository();
        $commit = $c->showCommit($c->getCurrentCommit());

        $this->assertStringContainsString('--- a/directory/test.txt', $commit);
    }

    public function testRmdirDirectoryWithContext()
    {
        $c  = $this->getRepository();
        $c->writeFile('directory/test.txt', 'Test');
        $this->assertFileExists(TESTS_REPO_PATH_1.'/directory');
        $this->assertFileExists(TESTS_REPO_PATH_1.'/directory/test.txt');

        $path   = sprintf('git://%s/directory', TESTS_REPO_PATH_1);
        $cntxt  = stream_context_create(array(
            'git'   => array(
                'commitMsg' => 'Hello World',
                'author'    => 'Luke Skywalker <skywalker@deathstar.com>'
            )
        ));
        rmdir($path, $cntxt);

        $c      = $this->getRepository();
        $commit = $c->showCommit($c->getCurrentCommit());
        $this->assertStringContainsString('Hello World', $commit);
        $this->assertStringContainsString('Luke Skywalker <skywalker@deathstar.com>', $commit);
    }

    public function testMkdirDirectory()
    {
        $path   = sprintf('git://%s/directory', TESTS_REPO_PATH_1);
        mkdir($path);

        $this->assertFileExists(TESTS_REPO_PATH_1.'/directory');
        $this->assertFileExists(TESTS_REPO_PATH_1.'/directory/.gitkeep');

        $c      = $this->getRepository();
        $commit = $c->showCommit($c->getCurrentCommit());
        $this->assertStringContainsString('b/directory/.gitkeep', $commit);
    }

    public function testMkdirDirectoryRecursively()
    {
        $path   = sprintf('git://%s/directory/directory', TESTS_REPO_PATH_1);
        mkdir($path, 0777, true);

        $this->assertFileExists(TESTS_REPO_PATH_1.'/directory');
        $this->assertFileExists(TESTS_REPO_PATH_1.'/directory/directory');
        $this->assertFileExists(TESTS_REPO_PATH_1.'/directory/directory/.gitkeep');

        $c      = $this->getRepository();
        $commit = $c->showCommit($c->getCurrentCommit());
        $this->assertStringContainsString('b/directory/directory/.gitkeep', $commit);
    }

    public function testMkdirDirectoryWithContext()
    {
        $path   = sprintf('git://%s/directory', TESTS_REPO_PATH_1);
        $cntxt  = stream_context_create(array(
            'git'   => array(
                'commitMsg' => 'Hello World',
                'author'    => 'Luke Skywalker <skywalker@deathstar.com>'
            )
        ));
        mkdir($path, 0777, false, $cntxt);

        $c      = $this->getRepository();
        $commit = $c->showCommit($c->getCurrentCommit());
        $this->assertStringContainsString('Hello World', $commit);
        $this->assertStringContainsString('Luke Skywalker <skywalker@deathstar.com>', $commit);
    }
}
