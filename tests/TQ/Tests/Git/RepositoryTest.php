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
     * @expectedException InvalidArgumentException
     */
    public function testRepositoryOpenOnNonExistantPath()
    {
        $c  = Git\Repository::open('/does/not/exist', $this->getGit());
    }

    /**
     * @expectedException InvalidArgumentException
     */
    public function testRepositoryOpenOnFile()
    {
        $c  = Git\Repository::open(__FILE__, $this->getGit());
    }

    /**
     * @expectedException InvalidArgumentException
     */
    public function testRepositoryOpenOnNonRepositoryPath()
    {
        $c  = Git\Repository::open('/usr', $this->getGit());
    }

    public function testRepositoryOpenOnRepositoryPath()
    {
        $c  = Git\Repository::open(TESTS_REPO_PATH_1, $this->getGit());
        $this->assertInstanceOf('TQ\Git\Repository', $c);
    }

    /**
     * @expectedException InvalidArgumentException
     */
    public function testRepositoryCreateOnExistingRepositoryPath()
    {
        $c  = Git\Repository::create(TESTS_REPO_PATH_1, $this->getGit());
    }

    /**
     * @expectedException InvalidArgumentException
     */
    public function testRepositoryCreateOnFile()
    {
        $c  = Git\Repository::create(__FILE__, 0755, $this->getGit());
    }

    public function testRepositoryCreateOnExistingPath()
    {
        $c  = Git\Repository::create(TESTS_REPO_PATH_2, 0755, $this->getGit());
        $this->assertInstanceOf('TQ\Git\Repository', $c);
    }

    public function testRepositoryCreateOnCreateablePath()
    {
        $c  = Git\Repository::create(TESTS_REPO_PATH_3, 0755, $this->getGit());
        $this->assertInstanceOf('TQ\Git\Repository', $c);
    }

    public function testAddFile()
    {
        $c  = Git\Repository::open(TESTS_REPO_PATH_1, $this->getGit());
        $c->commitFile('test.txt', 'Test');

        $this->assertFileExists(TESTS_REPO_PATH_1.'/test.txt');
        $this->assertEquals('Test', file_get_contents(TESTS_REPO_PATH_1.'/test.txt'));
    }

    public function testAddFileInSubdirectory()
    {
        $c  = Git\Repository::open(TESTS_REPO_PATH_1, $this->getGit());
        $c->commitFile('/directory/test.txt', 'Test');

        $this->assertFileExists(TESTS_REPO_PATH_1.'/directory/test.txt');
        $this->assertEquals('Test', file_get_contents(TESTS_REPO_PATH_1.'/directory/test.txt'));
    }

    public function testAddMultipleFiles()
    {
        $c  = Git\Repository::open(TESTS_REPO_PATH_1, $this->getGit());
        for ($i = 0; $i < 5; $i++) {
            $c->commitFile(sprintf('file_%s.txt', $i), $i);
        }

        for ($i = 0; $i < 5; $i++) {
            $this->assertFileExists(TESTS_REPO_PATH_1.sprintf('/file_%s.txt', $i));
            $this->assertEquals($i, file_get_contents(TESTS_REPO_PATH_1.sprintf('/file_%s.txt', $i)));
        }
    }

    public function testShowLog()
    {
        $c  = Git\Repository::open(TESTS_REPO_PATH_1, $this->getGit());
        $this->assertContains('Initial commit', $c->showLog());
    }
}

