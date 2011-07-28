<?php
namespace TQ\Tests\Git\Repository;

use TQ\Git\Cli\Binary;
use TQ\Git\Repository\Repository;
use TQ\Tests\Helper;

class SetupTest extends \PHPUnit_Framework_TestCase
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
        Helper::removeDirectory(TESTS_TMP_PATH);
    }

    /**
     *
     * @param   string          $path
     * @paran   boolean|integer $create
     * @return  Repository
     */
    protected function getRepository($path, $create = false)
    {
        return Repository::open($path, new Binary(GIT_BINARY), $create);
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
        $this->assertInstanceOf('TQ\Git\Repository\Repository', $c);
    }

    public function testRepositoryCreateOnExistingRepositoryPath()
    {
        $c  = $this->getRepository(TESTS_REPO_PATH_1, 0755);
        $this->assertInstanceOf('TQ\Git\Repository\Repository', $c);
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
        $this->assertInstanceOf('TQ\Git\Repository\Repository', $c);
    }

    public function testRepositoryCreateOnCreateablePath()
    {
        $c  = $this->getRepository(TESTS_REPO_PATH_3, 0755);
        $this->assertInstanceOf('TQ\Git\Repository\Repository', $c);
    }
}

