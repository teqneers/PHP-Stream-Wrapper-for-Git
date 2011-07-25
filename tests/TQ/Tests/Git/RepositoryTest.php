<?php
namespace TQ\Tests\Git;

use TQ\Git;
use TQ\Tests\Helper;

class RepositoryTest extends \PHPUnit_Framework_TestCase
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
        //Helper::removeDirectory(TESTS_TMP_PATH);
    }

    /**
     *
     * @return Git\Binary
     */
    protected function getGit()
    {
        return new Git\Binary(GIT_BINARY);
    }

    /**
     *
     * @param   string          $path
     * @paran   boolean|integer $create
     * @return  Git\Repository
     */
    protected function getRepository($path, $create = false)
    {
        if ($create) {
            return Git\Repository::create($path, $create, $this->getGit());
        } else {
            return Git\Repository::open($path, $this->getGit());
        }
    }

    /**
     * @expectedException InvalidArgumentException
     */
    public function testRepositoryOpenOnNonExistantPath()
    {
        $c  = $this->getRepository('/does/not/exist', false);
    }

    /**
     * @expectedException InvalidArgumentException
     */
    public function testRepositoryOpenOnFile()
    {
        $c  = $this->getRepository(__FILE__, false);
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
        $this->assertInstanceOf('TQ\Git\Repository', $c);
    }

    /**
     * @expectedException InvalidArgumentException
     */
    public function testRepositoryCreateOnExistingRepositoryPath()
    {
        $c  = $this->getRepository(TESTS_REPO_PATH_1, 0755);
    }

    /**
     * @expectedException InvalidArgumentException
     */
    public function testRepositoryCreateOnFile()
    {
        $c  = $this->getRepository(__FILE__, 0755);
    }

    public function testRepositoryCreateOnExistingPath()
    {
        $c  = $this->getRepository(TESTS_REPO_PATH_2, 0755);
        $this->assertInstanceOf('TQ\Git\Repository', $c);
    }

    public function testRepositoryCreateOnCreateablePath()
    {
        $c  = $this->getRepository(TESTS_REPO_PATH_3, 0755);
        $this->assertInstanceOf('TQ\Git\Repository', $c);
    }

    public function testAddFile()
    {
        $c      = $this->getRepository(TESTS_REPO_PATH_1, false);
        $hash   = $c->writeFile('test.txt', 'Test');
        $this->assertEquals(40, strlen($hash));

        $this->assertFileExists(TESTS_REPO_PATH_1.'/test.txt');
        $this->assertEquals('Test', file_get_contents(TESTS_REPO_PATH_1.'/test.txt'));
    }

    public function testAddFileInSubdirectory()
    {
        $c      = $this->getRepository(TESTS_REPO_PATH_1, false);
        $hash   = $c->writeFile('/directory/test.txt', 'Test');
        $this->assertEquals(40, strlen($hash));

        $this->assertFileExists(TESTS_REPO_PATH_1.'/directory/test.txt');
        $this->assertEquals('Test', file_get_contents(TESTS_REPO_PATH_1.'/directory/test.txt'));
    }

    public function testAddMultipleFiles()
    {
        $c  = $this->getRepository(TESTS_REPO_PATH_1, false);
        for ($i = 0; $i < 5; $i++) {
            $hash   = $c->writeFile(sprintf('test_%s.txt', $i), $i);
            $this->assertEquals(40, strlen($hash));
        }

        for ($i = 0; $i < 5; $i++) {
            $this->assertFileExists(TESTS_REPO_PATH_1.sprintf('/test_%s.txt', $i));
            $this->assertEquals($i, file_get_contents(TESTS_REPO_PATH_1.sprintf('/test_%s.txt', $i)));
        }
    }

    public function testShowLog()
    {
        $c  = $this->getRepository(TESTS_REPO_PATH_1, false);
        $this->assertContains('Initial commit', $c->showLog());

        $hash   = $c->writeFile('/directory/test.txt', 'Test');
        $this->assertContains($hash, $c->showLog());
    }

    public function testRemoveFile()
    {
        $c      = $this->getRepository(TESTS_REPO_PATH_1, false);
        $hash   = $c->removeFile('file_0.txt');
        $this->assertEquals(40, strlen($hash));

        $this->assertFileNotExists(TESTS_REPO_PATH_1.'/file_0.txt');
    }

    public function testRemoveMultipleFiles()
    {
        $c  = $this->getRepository(TESTS_REPO_PATH_1, false);
        for ($i = 0; $i < 5; $i++) {
            $hash   = $c->removeFile(sprintf('file_%s.txt', $i), $i);
            $this->assertEquals(40, strlen($hash));
        }

        for ($i = 0; $i < 5; $i++) {
            $this->assertFileNotExists(TESTS_REPO_PATH_1.sprintf('/file_%s.txt', $i));
        }
    }

    public function testRemoveWildcardFile()
    {
        $c      = $this->getRepository(TESTS_REPO_PATH_1, false);
        $hash   = $c->removeFile('file_*');
        $this->assertEquals(40, strlen($hash));

        for ($i = 0; $i < 5; $i++) {
            $this->assertFileNotExists(TESTS_REPO_PATH_1.sprintf('/file_%s.txt', $i));
        }
    }

    public function testShowCommit()
    {
        $c      = $this->getRepository(TESTS_REPO_PATH_1, false);
        $hash   = $c->writeFile('test.txt', 'Test');
        $commit = $c->showCommit($hash);
        $this->assertContains('test.txt', $commit);
        $this->assertContains('Test', $commit);
    }
}

