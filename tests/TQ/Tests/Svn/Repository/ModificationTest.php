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
use TQ\Vcs\Repository\Transaction;

class ModificationTest extends \PHPUnit_Framework_TestCase
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
    protected function tearDown()
    {
        Helper::removeDirectory(TESTS_TMP_PATH);
    }

    /**
     *
     * @return  Repository
     */
    protected function getRepository()
    {
        return Repository::open(TESTS_REPO_PATH_1, new Binary(SVN_BINARY));
    }

    public function testAddFile()
    {
        $c          = $this->getRepository();
        $revision   = $c->writeFile('test.txt', 'Test');
        $this->assertEquals(2, $revision);

        $this->assertFileExists(TESTS_REPO_PATH_1.'/test.txt');
        $this->assertEquals('Test', file_get_contents(TESTS_REPO_PATH_1.'/test.txt'));

        $commit = $c->showCommit($revision);
        $this->assertContains('A /test.txt', $commit);
    }

    public function testAddFileInSubdirectory()
    {
        $c          = $this->getRepository();
        $revision   = $c->writeFile('/directory/test.txt', 'Test');
        $this->assertEquals(2, $revision);

        $this->assertFileExists(TESTS_REPO_PATH_1.'/directory/test.txt');
        $this->assertEquals('Test', file_get_contents(TESTS_REPO_PATH_1.'/directory/test.txt'));

        $commit = $c->showCommit($revision);
        $this->assertContains('A /directory/test.txt', $commit);
    }

    public function testAddFileInSecondLevelSubdirectory()
    {
        $c          = $this->getRepository();
        $revision   = $c->writeFile('/dirA/dirB/test.txt', 'Test');
        $this->assertEquals(2, $revision);

        $this->assertFileExists(TESTS_REPO_PATH_1.'/dirA/dirB/test.txt');
        $this->assertEquals('Test', file_get_contents(TESTS_REPO_PATH_1.'/dirA/dirB/test.txt'));

        $commit = $c->showCommit($revision);
        $this->assertContains('A /dirA/dirB/test.txt', $commit);
    }

    public function testAddMultipleFiles()
    {
        $c  = $this->getRepository();
        for ($i = 0; $i < 5; $i++) {
            $revision   = $c->writeFile(sprintf('test_%s.txt', $i), $i);
            $this->assertEquals($i + 2, $revision);
            $commit = $c->showCommit($revision);
            $this->assertContains(sprintf('A /test_%d.txt', $i), $commit);
        }

        for ($i = 0; $i < 5; $i++) {
            $this->assertFileExists(TESTS_REPO_PATH_1.sprintf('/test_%s.txt', $i));
            $this->assertEquals($i, file_get_contents(TESTS_REPO_PATH_1.sprintf('/test_%s.txt', $i)));
        }
    }

    public function testRemoveFile()
    {
        $c          = $this->getRepository();
        $revision   = $c->removeFile('file_0.txt');
        $this->assertEquals(2, $revision);

        $this->assertFileNotExists(TESTS_REPO_PATH_1.'/file_0.txt');

        $commit = $c->showCommit($revision);
        $this->assertContains('D /file_0.txt', $commit);
    }

    public function testRemoveMultipleFiles()
    {
        $c  = $this->getRepository();
        for ($i = 0; $i < 5; $i++) {
            $revision   = $c->removeFile(sprintf('file_%s.txt', $i), $i);
            $this->assertEquals($i + 2, $revision);
            $commit = $c->showCommit($revision);
            $this->assertContains(sprintf('D /file_%d.txt', $i), $commit);
        }

        for ($i = 0; $i < 5; $i++) {
            $this->assertFileNotExists(TESTS_REPO_PATH_1.sprintf('/file_%d.txt', $i));
        }
    }

    public function testRemoveWildcardFile()
    {
        $c          = $this->getRepository();
        $revision   = $c->removeFile('file_*');
        $this->assertEquals(2, $revision);

        for ($i = 0; $i < 5; $i++) {
            $this->assertFileNotExists(TESTS_REPO_PATH_1.sprintf('/file_%d.txt', $i));
        }

        $commit = $c->showCommit($revision);
        $this->assertContains('D /file_0.txt', $commit);
        $this->assertContains('D /file_1.txt', $commit);
        $this->assertContains('D /file_2.txt', $commit);
        $this->assertContains('D /file_3.txt', $commit);
        $this->assertContains('D /file_4.txt', $commit);
    }

    public function testRemoveSubdirectory()
    {
        $c      = $this->getRepository();
        $c->writeFile('subdirectory/test.txt', 'Test');

        $revision   = $c->removeFile('subdirectory', null, true);
        $this->assertEquals(3, $revision);

        $this->assertFileNotExists(TESTS_REPO_PATH_1.'/subdirectory');

        $commit = $c->showCommit($revision);
        $this->assertContains('deleted file', $commit);
    }

    public function testMoveFile()
    {
        $c          = $this->getRepository();
        $revision   = $c->renameFile('file_0.txt', 'test.txt');
        $this->assertEquals(2, $revision);

        $this->assertFileNotExists(TESTS_REPO_PATH_1.'/file_0.txt');
        $this->assertFileExists(TESTS_REPO_PATH_1.'/test.txt');

        $commit = $c->showCommit($revision);
        $this->assertContains('A /test.txt', $commit);
        $this->assertContains('/file_0.txt', $commit);
    }

    public function testReset()
    {
        $c  = $this->getRepository();
        $this->assertFalse($c->isDirty());

        $file   = TESTS_REPO_PATH_1.'/test.txt';
        file_put_contents($file, 'Test');
        $this->assertTrue($c->isDirty());
        $c->reset();
        $this->assertFalse($c->isDirty());
        $this->assertFileNotExists($file);

        file_put_contents($file, 'Test');
        $c->add(array('test.txt'));
        $c->reset();
        $this->assertFalse($c->isDirty());
        $this->assertFileNotExists($file);
    }

    public function testTransactionalChangesNoException()
    {
        $c  = $this->getRepository();

        $result = $c->transactional(function(Transaction $t) {
            for ($i = 0; $i < 5; $i++) {
                $file   = $t->getRepositoryPath().'/'.sprintf('test_%s.txt', $i);
                file_put_contents($file, 'Test');
            }
            $t->setCommitMsg('Hello World');
            return 'This is the return value';
        });

        $this->assertEquals('This is the return value', $result->getResult());

        $this->assertFalse($c->isDirty());

        $list   = $c->listDirectory();
        $this->assertContains('file_0.txt', $list);
        $this->assertContains('file_1.txt', $list);
        $this->assertContains('file_2.txt', $list);
        $this->assertContains('file_3.txt', $list);
        $this->assertContains('file_4.txt', $list);
        $this->assertContains('test_0.txt', $list);
        $this->assertContains('test_1.txt', $list);
        $this->assertContains('test_2.txt', $list);
        $this->assertContains('test_3.txt', $list);
        $this->assertContains('test_4.txt', $list);

        $commit = $c->showCommit($result->getCommitHash());
        $this->assertContains($result->getCommitMsg(), $commit);
        $this->assertContains('A /test_0.txt', $commit);
        $this->assertContains('A /test_1.txt', $commit);
        $this->assertContains('A /test_2.txt', $commit);
        $this->assertContains('A /test_3.txt', $commit);
        $this->assertContains('A /test_4.txt', $commit);
    }

    public function testTransactionalChangesException()
    {
        $c  = $this->getRepository();


        try {
            $c->transactional(function(Transaction $t) {
                for ($i = 0; $i < 5; $i++) {
                    $file   = $t->getRepositoryPath().'/'.sprintf('test_%s.txt', $i);
                    file_put_contents($file, 'Test');
                }
                throw new \Exception('Test');
            });
            $this->fail('Exception not thrown');
        } catch (\Exception $e) {
            $this->assertEquals('Test', $e->getMessage());
            $this->assertFalse($c->isDirty());
        }
    }

    public function testTransactionalChangesRenameAndDelete()
    {
        $c  = $this->getRepository();

        $result = $c->transactional(function(Transaction $t) {
            unlink($t->resolvePath('file_0.txt'));
            rename($t->resolvePath('file_1.txt'), $t->resolvePath('test.txt'));
            $t->setCommitMsg('Hello World');
            return 'This is the return value';
        });

        $this->assertEquals('This is the return value', $result->getResult());

        $this->assertFalse($c->isDirty());

        $list   = $c->listDirectory();
        $this->assertNotContains('file_0.txt', $list);
        $this->assertNotContains('file_1.txt', $list);
        $this->assertContains('test.txt', $list);
        $this->assertContains('file_2.txt', $list);
        $this->assertContains('file_3.txt', $list);
        $this->assertContains('file_4.txt', $list);

        $commit = $c->showCommit($result->getCommitHash());
        $this->assertContains($result->getCommitMsg(), $commit);
        $this->assertContains('D /file_0.txt', $commit);
        $this->assertContains('D /file_1.txt', $commit);
        $this->assertContains('A /test.txt', $commit);
    }

    public function testTransactionalNoChanges()
    {
        $c              = $this->getRepository();
        $currentCommit  = $c->getCurrentCommit();

        $result = $c->transactional(function(Transaction $t) {
            $t->setCommitMsg('Hello World');
            return 'This is the return value';
        });

        $this->assertEquals('This is the return value', $result->getResult());
        $this->assertEquals($currentCommit, $result->getCommitHash());
        $this->assertEquals($currentCommit, $c->getCurrentCommit());
    }
}

