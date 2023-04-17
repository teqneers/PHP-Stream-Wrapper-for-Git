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

use PHPUnit\Framework\TestCase;
use TQ\Svn\Cli\Binary;
use TQ\Svn\Repository\Repository;
use TQ\Tests\Helper;

class InfoTest extends TestCase
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

        Helper::initEmptySvnRepository(TESTS_REPO_PATH_1);

        for ($i = 0; $i < 5; $i++) {
            $file   = sprintf('file_%d.txt', $i);
            $path   = TESTS_REPO_PATH_1.'/'.$file;
            file_put_contents($path, sprintf('File %d', $i));
            Helper::executeSvn(TESTS_REPO_PATH_1, sprintf('add %s',
                escapeshellarg($file)
            ));
        }
        Helper::executeSvn(TESTS_REPO_PATH_1, sprintf('commit --message=%s',
            escapeshellarg('Initial commit')
        ));

        clearstatcache();
    }

    /**
     * Tears down the fixture, for example, close a network connection.
     * This method is called after a test is executed.
     */
    protected function tearDown(): void
    {
        Helper::removeDirectory(TESTS_TMP_PATH);
    }

    /**
     *
     * @return  Repository
     */
    protected function getRepository(): Repository
    {
        return Repository::open(TESTS_REPO_PATH_1, new Binary(SVN_BINARY));
    }

    public function testGetStatus()
    {
        $c  = $this->getRepository();
        $this->assertFalse($c->isDirty());

        $file   = TESTS_REPO_PATH_1.'/test.txt';
        file_put_contents($file, 'Test');
        $this->assertTrue($c->isDirty());
        $status = $c->getStatus();
        $this->assertEquals(array(
            'file'      => 'test.txt',
            'status'    => 'unversioned',
        ), $status[0]);

        $c->add(array('test.txt'));
        $this->assertTrue($c->isDirty());
        $status = $c->getStatus();
        $this->assertEquals(array(
            'file'      => 'test.txt',
            'status'    => 'added',
        ), $status[0]);

        $c->commit('Commt file', array('test.txt'));
        $this->assertFalse($c->isDirty());
    }

    public function testGetLog()
    {
        $c      = $this->getRepository();
        $log    = $c->getLog();
        $this->assertCount(1, $log);
        $this->assertStringContainsString('Initial commit', $log[0][3]);

        $revision   = $c->writeFile('/directory/test.txt', 'Test');
        $log        = $c->getLog();

        $this->assertCount(2, $log);
        $this->assertEquals($revision, $log[0][0]);
        $this->assertStringContainsString('Initial commit', $log[1][3]);

        $log    = $c->getLog(1);
        $this->assertCount(1, $log);
        $this->assertEquals($revision, $log[0][0]);

        $log    = $c->getLog(1, 1);
        $this->assertCount(1, $log);
        $this->assertStringContainsString('Initial commit', $log[0][3]);

        $log    = $c->getLog(10,0);
        $this->assertCount(2, $log);
        $this->assertStringContainsString('Initial commit', $log[1][3]);
    }

    public function testShowCommit()
    {
        $c          = $this->getRepository();
        $revision   = $c->writeFile('test.txt', 'Test');
        $commit = $c->showCommit($revision);
        $this->assertStringContainsString('test.txt', $commit);
        $this->assertStringContainsString('TQ\Svn\Repository\Repository created or changed file "test.txt"', $commit);
    }

    public function testListDirectory()
    {
        $c      = $this->getRepository();

        $list   = $c->listDirectory();
        $this->assertContains('file_0.txt', $list);
        $this->assertContains('file_1.txt', $list);
        $this->assertContains('file_2.txt', $list);
        $this->assertContains('file_3.txt', $list);
        $this->assertContains('file_4.txt', $list);
        $this->assertNotContains('test.txt', $list);

        $c->writeFile('test.txt', 'Test');
        $list   = $c->listDirectory();
        $this->assertContains('file_0.txt', $list);
        $this->assertContains('file_1.txt', $list);
        $this->assertContains('file_2.txt', $list);
        $this->assertContains('file_3.txt', $list);
        $this->assertContains('file_4.txt', $list);
        $this->assertContains('test.txt', $list);

        $c->removeFile('test.txt');
        $list   = $c->listDirectory();
        $this->assertContains('file_0.txt', $list);
        $this->assertContains('file_1.txt', $list);
        $this->assertContains('file_2.txt', $list);
        $this->assertContains('file_3.txt', $list);
        $this->assertContains('file_4.txt', $list);
        $this->assertNotContains('test.txt', $list);

        $list   = $c->listDirectory('.', '1');
        $this->assertContains('file_0.txt', $list);
        $this->assertContains('file_1.txt', $list);
        $this->assertContains('file_2.txt', $list);
        $this->assertContains('file_3.txt', $list);
        $this->assertContains('file_4.txt', $list);
        $this->assertNotContains('test.txt', $list);

        $list   = $c->listDirectory('.', '2');
        $this->assertContains('file_0.txt', $list);
        $this->assertContains('file_1.txt', $list);
        $this->assertContains('file_2.txt', $list);
        $this->assertContains('file_3.txt', $list);
        $this->assertContains('file_4.txt', $list);
        $this->assertContains('test.txt', $list);

        $c->writeFile('directory/test.txt', 'Test');
        $list   = $c->listDirectory('directory/', 'HEAD');
        $this->assertContains('test.txt', $list);

        $list   = $c->listDirectory('directory', 'HEAD');
        $this->assertContains('test.txt', $list);
    }

    public function testShowFile()
    {
        $c      = $this->getRepository();

        $this->assertEquals('File 0', $c->showFile('file_0.txt'));

        $c->writeFile('test.txt', 'Test 1');
        $this->assertEquals('Test 1', $c->showFile('test.txt'));

        $c->writeFile('test.txt', 'Test 2');
        $this->assertEquals('Test 2', $c->showFile('test.txt'));
        $this->assertEquals('Test 1', $c->showFile('test.txt', '2'));

        $c->writeFile('test.txt', 'Test 3');
        $this->assertEquals('Test 3', $c->showFile('test.txt'));
        $this->assertEquals('Test 2', $c->showFile('test.txt', '3'));
        $this->assertEquals('Test 1', $c->showFile('test.txt', '2'));
    }

    public function testGetDiff()
    {
        $c = $this->getRepository();

        $file1   = TESTS_REPO_PATH_1.'/file_1.txt';
        $file2   = TESTS_REPO_PATH_1.'/file_2.txt';

        file_put_contents($file1, "\n", FILE_APPEND);
        file_put_contents($file2, "\n", FILE_APPEND);
        $c->commit('Prepare for diff', array($file1, $file2));

        $this->assertFalse($c->isDirty());

        file_put_contents($file1, "Unstaged1\n", FILE_APPEND);
        file_put_contents($file2, "Unstaged2\n", FILE_APPEND);
        $this->assertTrue($c->isDirty());

        $diff = $c->getDiff(array($file1));
        $this->assertEquals(array('file_1.txt'), array_keys($diff));
        $this->assertMatchesRegularExpression("/\\+Unstaged1$/", $diff['file_1.txt']);

        $diff = $c->getDiff();
        $this->assertEquals(array('file_1.txt', 'file_2.txt'), array_keys($diff));
        $this->assertMatchesRegularExpression("/\\+Unstaged1$/", $diff['file_1.txt']);
        $this->assertMatchesRegularExpression("/\\+Unstaged2$/", $diff['file_2.txt']);
    }
}
