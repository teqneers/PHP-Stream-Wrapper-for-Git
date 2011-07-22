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
     * @return Git\GitBinary
     */
    protected function getGit()
    {
        return new Git\GitBinary(GIT_BINARY);
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
    }

    public function testRepositoryCreateOnCreateablePath()
    {
        $c  = Git\Repository::create(TESTS_REPO_PATH_3, 0755, $this->getGit());
    }

    public function testAddFile()
    {
        $c  = Git\Repository::open(TESTS_REPO_PATH_1, $this->getGit());
        $c->addFile('test.txt', 'Test');
    }
}

