<?php
namespace TQ\Tests\Git;

use TQ\Git;
use TQ\Tests\Helper;

class RepositoryTest extends \PHPUnit_Framework_TestCase
{
    /**
     * Sets up the fixture, for example, open a network connection.
     * This method is called before a test is executed.
     *
     */
    protected function setUp()
    {
        Helper::removeDirectory(TESTS_TMP_PATH);
        mkdir(TESTS_TMP_PATH, 0777, true);
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
     * @expectedException InvalidArgumentException
     */
    public function testRepositoryOpenOnNonExistantPath()
    {
        $c  = Git\Repository::open('/does/not/exist');
    }

    /**
     * @expectedException InvalidArgumentException
     */
    public function testRepositoryOpenOnFile()
    {
        $c  = Git\Repository::open(__FILE__);
    }

    /**
     * @expectedException InvalidArgumentException
     */
    public function testRepositoryOpenOnNonRepositoryPath()
    {
        $c  = Git\Repository::open('/usr');
    }

    public function testRepositoryOpenOnRepositoryPath()
    {
        $c  = Git\Repository::open(PROJECT_PATH);
    }

    /**
     * @expectedException InvalidArgumentException
     */
    public function testRepositoryCreateOnExistingRepositoryPath()
    {
        $c  = Git\Repository::create(PROJECT_PATH);
    }

    /**
     * @expectedException InvalidArgumentException
     */
    public function testRepositoryCreateOnFile()
    {
        $c  = Git\Repository::create(__FILE__);
    }

    public function testRepositoryCreateOnExistingPath()
    {
        $c  = Git\Repository::create(TESTS_TMP_PATH);
    }

    public function testRepositoryCreateOnCreateablePath()
    {
        $c  = Git\Repository::create(TESTS_REPO_PATH);
    }
}

