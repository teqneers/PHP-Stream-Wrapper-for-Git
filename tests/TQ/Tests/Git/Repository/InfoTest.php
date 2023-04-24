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

namespace TQ\Tests\Git\Repository;

use PHPUnit\Framework\TestCase;
use TQ\Git\Cli\Binary;
use TQ\Git\Repository\Repository;
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
        Helper::executeGit(TESTS_REPO_PATH_1, 'branch x-feature');

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
        return Repository::open(TESTS_REPO_PATH_1, new Binary(GIT_BINARY));
    }

    public function testGetCurrentBranch()
    {
        $c  = $this->getRepository();
        $this->assertEquals('master', $c->getCurrentBranch());
    }

    public function testGetBranches()
    {
        $c  = $this->getRepository();
        $this->assertEquals(array('master', 'x-feature'), $c->getBranches());
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
            'x'         => '?',
            'y'         => '?',
            'renamed'   => null
        ), $status[0]);

        $c->add(array('test.txt'));
        $this->assertTrue($c->isDirty());
        $status = $c->getStatus();
        $this->assertEquals(array(
            'file'      => 'test.txt',
            'x'         => 'A',
            'y'         => '',
            'renamed'   => null
        ), $status[0]);

        $c->commit('Commt file', array('test.txt'));
        $this->assertFalse($c->isDirty());
    }

    public function testGetLog()
    {
        $c      = $this->getRepository();
        $log    = $c->getLog();
        $this->assertCount(1, $log);
        $this->assertStringContainsString('Initial commit', $log[0]);

        $hash   = $c->writeFile('/directory/test.txt', 'Test');
        $log    = $c->getLog();
        $this->assertCount(2, $log);
        $this->assertStringContainsString($hash, $log[0]);
        $this->assertStringContainsString('Initial commit', $log[1]);

        $log    = $c->getLog(1);
        $this->assertCount(1, $log);
        $this->assertStringContainsString($hash, $log[0]);

        $log    = $c->getLog(array('limit' => 1));
        $this->assertCount(1, $log);
        $this->assertStringContainsString($hash, $log[0]);

        $log    = $c->getLog(1, 1);
        $this->assertCount(1, $log);
        $this->assertStringContainsString('Initial commit', $log[0]);

        $log    = $c->getLog(array('limit' => 1, 'skip' => 1));
        $this->assertCount(1, $log);
        $this->assertStringContainsString('Initial commit', $log[0]);

        $log    = $c->getLog(10,0);
        $this->assertCount(2, $log);
        $this->assertStringContainsString('Initial commit', $log[1]);

        $log    = $c->getLog(array('limit' => 10, 'skip' => 0));
        $this->assertCount(2, $log);
        $this->assertStringContainsString('Initial commit', $log[1]);

        $log    = $c->getLog(null, null);
        $this->assertCount(2, $log);
        $this->assertStringContainsString('Initial commit', $log[1]);

        $hash2 = $c->writeFile('file7.txt', 'Test create file 7', 'Test create file 7');
        $hash3 = $c->writeFile('file8.txt', 'Test create file 8', 'Test create file 8');

        $log    = $c->getLog(array('color', '--', 'file_0.txt'));
        $allLogs = implode("\n", $log);
        $this->assertCount(1, $log);
        $this->assertEquals(1, substr_count($allLogs, 'create mode'));
        $this->assertStringContainsString('Initial commit', $log[0]);

        $log    = $c->getLog(array('--', 'file7.txt'));
        $allLogs = implode("\n", $log);
        $this->assertCount(1, $log);
        $this->assertEquals(1, substr_count($allLogs, 'create mode'));
        $this->assertStringContainsString('Test create file 7', $log[0]);

        $log    = $c->getLog(array('--', 'file8.txt'));
        $allLogs = implode("\n", $log);
        $this->assertCount(1, $log);
        $this->assertEquals(1, substr_count($allLogs, 'create mode'));
        $this->assertStringContainsString('Test create file 8', $log[0]);

        $log = $c->getLog(array(
            'limit' => 3,
            'graph',
            'all',
            'pretty' => 'format:"%h -%d %s (%cr)"',  // Prettier in color, but phpunit sees that they're non-ascii strings and cracks under the pressure...
            'abbrev-commit',
            'date' => 'relative'
        ));

        $this->assertCount(3, $log);
        $this->assertStringContainsString(substr($hash3, 0, 7) . ' - ', $log[0]);
        $this->assertStringContainsString('Test create file 8', $log[0]);
        $this->assertStringContainsString(substr($hash2, 0, 7) . ' - Test create file 7', $log[1]);
        $this->assertStringContainsString(substr($hash, 0, 7) . ' - TQ\Git\Repository\Repository created or changed file "/directory/test.txt"', $log[2]);

        $log = $c->getLog(array(
            'pretty' => 'format:"%C(yellow)%h%Cred%d %Creset%s%Cblue [%cn]%Creset"',
            'decorate',
            'numstat'
        ));
    }

    public function testShowCommit()
    {
        $c      = $this->getRepository();
        $hash   = $c->writeFile('test.txt', 'Test');
        $commit = $c->showCommit($hash);
        $this->assertStringContainsString('test.txt', $commit);
        $this->assertStringContainsString('Test', $commit);
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

        $list   = $c->listDirectory('.', 'HEAD^^');
        $this->assertContains('file_0.txt', $list);
        $this->assertContains('file_1.txt', $list);
        $this->assertContains('file_2.txt', $list);
        $this->assertContains('file_3.txt', $list);
        $this->assertContains('file_4.txt', $list);
        $this->assertNotContains('test.txt', $list);

        $list   = $c->listDirectory('.', 'HEAD^');
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
        $this->assertEquals('Test 1', $c->showFile('test.txt', 'HEAD^'));

        $c->writeFile('test.txt', 'Test 3');
        $this->assertEquals('Test 3', $c->showFile('test.txt'));
        $this->assertEquals('Test 2', $c->showFile('test.txt', 'HEAD^'));
        $this->assertEquals('Test 1', $c->showFile('test.txt', 'HEAD^^'));
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

        file_put_contents($file1, "Staged1\n", FILE_APPEND);
        file_put_contents($file2, "Staged2\n", FILE_APPEND);
        $c->add(array($file1, $file2));

        $this->assertTrue($c->isDirty());

        file_put_contents($file1, "Unstaged1\n", FILE_APPEND);
        file_put_contents($file2, "Unstaged2\n", FILE_APPEND);

        $diff = $c->getDiff(array($file1), true);
        $this->assertEquals(array('file_1.txt'), array_keys($diff));
        $this->assertMatchesRegularExpression("/\\+Staged1$/", $diff['file_1.txt']);

        $diff = $c->getDiff(array($file1), false);
        $this->assertEquals(array('file_1.txt'), array_keys($diff));
        $this->assertMatchesRegularExpression("/ Staged1\\n\\+Unstaged1$/", $diff['file_1.txt']);

        $diff = $c->getDiff(null, true);
        $this->assertEquals(array('file_1.txt', 'file_2.txt'), array_keys($diff));
        $this->assertMatchesRegularExpression("/\\+Staged1$/", $diff['file_1.txt']);
        $this->assertMatchesRegularExpression("/\\+Staged2$/", $diff['file_2.txt']);

        $diff = $c->getDiff(null, false);
        $this->assertEquals(array('file_1.txt', 'file_2.txt'), array_keys($diff));
        $this->assertMatchesRegularExpression("/ Staged1\\n\\+Unstaged1$/", $diff['file_1.txt']);
        $this->assertMatchesRegularExpression("/ Staged2\\n\\+Unstaged2$/", $diff['file_2.txt']);
    }
}
