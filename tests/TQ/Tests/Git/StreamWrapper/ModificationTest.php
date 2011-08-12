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

class ModificationTest extends \PHPUnit_Framework_TestCase
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
            escapeshellcmd(GIT_BINARY)
        ));

        for ($i = 0; $i < 5; $i++) {
            $file   = sprintf('file_%d.txt', $i);
            $path   = TESTS_REPO_PATH_1.'/'.$file;
            file_put_contents($path, sprintf('File %d', $i));
            exec(sprintf('cd %s && %s add %s',
                escapeshellarg(TESTS_REPO_PATH_1),
                escapeshellcmd(GIT_BINARY),
                escapeshellarg($file)
            ));
        }

        exec(sprintf('cd %s && %s commit --message=%s',
            escapeshellarg(TESTS_REPO_PATH_1),
            escapeshellcmd(GIT_BINARY),
            escapeshellarg('Initial commit')
        ));

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

    public function testUnlinkFile()
    {
        $filePath   = sprintf('git://%s/file_0.txt', TESTS_REPO_PATH_1);
        unlink($filePath);

        $this->assertFileNotExists(TESTS_REPO_PATH_1.'/file_0.txt');

        $c      = $this->getRepository();
        $commit = $c->showCommit($c->getCurrentCommit());
        $this->assertContains('--- a/file_0.txt', $commit);
    }

    /**
     * @expectedException PHPUnit_Framework_Error_Warning
     */
    public function testUnlinkFileNonHead()
    {
        $path   = sprintf('git://%s/file_0.txt#HEAD^', TESTS_REPO_PATH_1);
        unlink($path);
    }

    /**
     * @expectedException PHPUnit_Framework_Error_Warning
     */
    public function testUnlinkNonExistantFile()
    {
        $path   = sprintf('git://%s/file_does_not_exist.txt', TESTS_REPO_PATH_1);
        unlink($path);
    }

    /**
     * @expectedException PHPUnit_Framework_Error_Warning
     */
    public function testUnlinkNonFile()
    {
        $c  = $this->getRepository();
        $c->writeFile('directory/test.txt', 'Test');

        $path   = sprintf('git://%s/directory', TESTS_REPO_PATH_1);
        unlink($path);
    }

    public function testRenameFile()
    {
        $filePathFrom   = sprintf('git://%s/file_0.txt', TESTS_REPO_PATH_1);
        $filePathTo     = sprintf('git://%s/test.txt', TESTS_REPO_PATH_1);
        rename($filePathFrom, $filePathTo);

        $this->assertFileNotExists(TESTS_REPO_PATH_1.'/file_0.txt');
        $this->assertFileExists(TESTS_REPO_PATH_1.'/test.txt');

        $c      = $this->getRepository();
        $commit = $c->showCommit($c->getCurrentCommit());
        $this->assertContains('--- a/file_0.txt', $commit);
        $this->assertContains('+++ b/test.txt', $commit);
    }

    /**
     * @expectedException PHPUnit_Framework_Error_Warning
     */
    public function testRenameFileNonHead()
    {
        $filePathFrom   = sprintf('git://%s/file_0.txt#HEAD^', TESTS_REPO_PATH_1);
        $filePathTo     = sprintf('git://%s/test.txt', TESTS_REPO_PATH_1);
        rename($filePathFrom, $filePathTo);
    }

    /**
     * @expectedException PHPUnit_Framework_Error_Warning
     */
    public function testRenameNonExistantFile()
    {
        $filePathFrom   = sprintf('git://%s/file_does_not_exist.txt', TESTS_REPO_PATH_1);
        $filePathTo     = sprintf('git://%s/test.txt', TESTS_REPO_PATH_1);
        rename($filePathFrom, $filePathTo);
    }

    /**
     * @expectedException PHPUnit_Framework_Error_Warning
     */
    public function testRenameNonFile()
    {
        $c  = $this->getRepository();
        $c->writeFile('directory/test.txt', 'Test');

        $filePathFrom   = sprintf('git://%s/directory', TESTS_REPO_PATH_1);
        $filePathTo     = sprintf('git://%s/test.txt', TESTS_REPO_PATH_1);
        rename($filePathFrom, $filePathTo);
    }

    public function testRmdirDirectory()
    {
        $c  = $this->getRepository();
        $c->writeFile('directory/test.txt', 'Test');
        $this->assertFileExists(TESTS_REPO_PATH_1.'/directory');
        $this->assertFileExists(TESTS_REPO_PATH_1.'/directory/test.txt');

        $dirPath   = sprintf('git://%s/directory', TESTS_REPO_PATH_1);
        rmdir($dirPath);

        $this->assertFileNotExists(TESTS_REPO_PATH_1.'/directory');

        $c      = $this->getRepository();
        $commit = $c->showCommit($c->getCurrentCommit());
        $this->assertContains('--- a/directory/test.txt', $commit);
    }

    /**
     * @expectedException PHPUnit_Framework_Error_Warning
     */
    public function testRmdirDirectoryNonHead()
    {
        $c  = $this->getRepository();
        $c->writeFile('directory/test.txt', 'Test');

        $dirPath   = sprintf('git://%s/directory#HEAD^', TESTS_REPO_PATH_1);
        rmdir($dirPath);
    }

    /**
     * @expectedException PHPUnit_Framework_Error_Warning
     */
    public function testRmdirNonExistantDirectory()
    {
        $path   = sprintf('git://%s/directory_does_not_exist', TESTS_REPO_PATH_1);
        rmdir($path);
    }

    /**
     * @expectedException PHPUnit_Framework_Error_Warning
     */
    public function testRmdirNonDirectory()
    {
        $path   = sprintf('git://%s/file_0.txt', TESTS_REPO_PATH_1);
        rmdir($path);
    }
}

