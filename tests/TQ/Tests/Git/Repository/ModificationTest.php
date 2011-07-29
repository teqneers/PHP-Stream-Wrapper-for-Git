<?php
namespace TQ\Tests\Git\Repository;

use TQ\Git\Cli\Binary;
use TQ\Git\Repository\Repository;
use TQ\Git\Repository\Transaction;
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
        return Repository::open(TESTS_REPO_PATH_1, new Binary(GIT_BINARY));
    }

    public function testAddFile()
    {
        $c      = $this->getRepository();
        $hash   = $c->writeFile('test.txt', 'Test');
        $this->assertEquals(40, strlen($hash));

        $this->assertFileExists(TESTS_REPO_PATH_1.'/test.txt');
        $this->assertEquals('Test', file_get_contents(TESTS_REPO_PATH_1.'/test.txt'));

        $commit = $c->showCommit($hash);
        $this->assertContains('+++ b/test.txt', $commit);
    }

    public function testAddFileInSubdirectory()
    {
        $c      = $this->getRepository();
        $hash   = $c->writeFile('/directory/test.txt', 'Test');
        $this->assertEquals(40, strlen($hash));

        $this->assertFileExists(TESTS_REPO_PATH_1.'/directory/test.txt');
        $this->assertEquals('Test', file_get_contents(TESTS_REPO_PATH_1.'/directory/test.txt'));

        $commit = $c->showCommit($hash);
        $this->assertContains('+++ b/directory/test.txt', $commit);
    }

    public function testAddMultipleFiles()
    {
        $c  = $this->getRepository();
        for ($i = 0; $i < 5; $i++) {
            $hash   = $c->writeFile(sprintf('test_%s.txt', $i), $i);
            $this->assertEquals(40, strlen($hash));
            $commit = $c->showCommit($hash);
            $this->assertContains(sprintf('+++ b/test_%d.txt', $i), $commit);
        }

        for ($i = 0; $i < 5; $i++) {
            $this->assertFileExists(TESTS_REPO_PATH_1.sprintf('/test_%s.txt', $i));
            $this->assertEquals($i, file_get_contents(TESTS_REPO_PATH_1.sprintf('/test_%s.txt', $i)));
        }
    }

    public function testRemoveFile()
    {
        $c      = $this->getRepository();
        $hash   = $c->removeFile('file_0.txt');
        $this->assertEquals(40, strlen($hash));

        $this->assertFileNotExists(TESTS_REPO_PATH_1.'/file_0.txt');

        $commit = $c->showCommit($hash);
        $this->assertContains('--- a/file_0.txt', $commit);
    }

    public function testRemoveMultipleFiles()
    {
        $c  = $this->getRepository();
        for ($i = 0; $i < 5; $i++) {
            $hash   = $c->removeFile(sprintf('file_%s.txt', $i), $i);
            $this->assertEquals(40, strlen($hash));
            $commit = $c->showCommit($hash);
            $this->assertContains(sprintf('--- a/file_%d.txt', $i), $commit);
        }

        for ($i = 0; $i < 5; $i++) {
            $this->assertFileNotExists(TESTS_REPO_PATH_1.sprintf('/file_%d.txt', $i));
        }
    }

    public function testRemoveWildcardFile()
    {
        $c      = $this->getRepository();
        $hash   = $c->removeFile('file_*');
        $this->assertEquals(40, strlen($hash));

        for ($i = 0; $i < 5; $i++) {
            $this->assertFileNotExists(TESTS_REPO_PATH_1.sprintf('/file_%d.txt', $i));
        }

        $commit = $c->showCommit($hash);
        $this->assertContains('--- a/file_0.txt', $commit);
        $this->assertContains('--- a/file_1.txt', $commit);
        $this->assertContains('--- a/file_2.txt', $commit);
        $this->assertContains('--- a/file_3.txt', $commit);
        $this->assertContains('--- a/file_4.txt', $commit);
    }

    public function testMoveFile()
    {
        $c      = $this->getRepository();
        $hash   = $c->renameFile('file_0.txt', 'test.txt');
        $this->assertEquals(40, strlen($hash));

        $this->assertFileNotExists(TESTS_REPO_PATH_1.'/file_0.txt');
        $this->assertFileExists(TESTS_REPO_PATH_1.'/test.txt');

        $commit = $c->showCommit($hash);
        $this->assertContains('--- a/file_0.txt', $commit);
        $this->assertContains('+++ b/test.txt', $commit);
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

        file_put_contents($file, 'Test');
        $this->assertTrue($c->isDirty());
        $c->reset(Repository::RESET_WORKING);
        $this->assertFalse($c->isDirty());
        $this->assertFileNotExists($file);

        file_put_contents($file, 'Test');
        $c->add(array('test.txt'));
        $this->assertTrue($c->isDirty());
        $c->reset(Repository::RESET_WORKING);
        $this->assertTrue($c->isDirty());
        $this->assertFileExists($file);
        $c->reset(Repository::RESET_STAGED);
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
        $this->assertContains('+++ b/test_0.txt', $commit);
        $this->assertContains('+++ b/test_1.txt', $commit);
        $this->assertContains('+++ b/test_2.txt', $commit);
        $this->assertContains('+++ b/test_3.txt', $commit);
        $this->assertContains('+++ b/test_4.txt', $commit);
    }

    public function testTransactionalChangesException()
    {
        $c  = $this->getRepository();


        try {
            $result = $c->transactional(function(Transaction $t) {
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
}

