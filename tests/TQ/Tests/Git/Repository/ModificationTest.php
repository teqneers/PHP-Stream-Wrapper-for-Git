<?php
namespace TQ\Tests\Git\Repository;

use TQ\Git\Cli\Binary;
use TQ\Git\Repository;
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
    }

    public function testAddFileInSubdirectory()
    {
        $c      = $this->getRepository();
        $hash   = $c->writeFile('/directory/test.txt', 'Test');
        $this->assertEquals(40, strlen($hash));

        $this->assertFileExists(TESTS_REPO_PATH_1.'/directory/test.txt');
        $this->assertEquals('Test', file_get_contents(TESTS_REPO_PATH_1.'/directory/test.txt'));
    }

    public function testAddMultipleFiles()
    {
        $c  = $this->getRepository();
        for ($i = 0; $i < 5; $i++) {
            $hash   = $c->writeFile(sprintf('test_%s.txt', $i), $i);
            $this->assertEquals(40, strlen($hash));
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
    }

    public function testRemoveMultipleFiles()
    {
        $c  = $this->getRepository();
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
        $c      = $this->getRepository();
        $hash   = $c->removeFile('file_*');
        $this->assertEquals(40, strlen($hash));

        for ($i = 0; $i < 5; $i++) {
            $this->assertFileNotExists(TESTS_REPO_PATH_1.sprintf('/file_%s.txt', $i));
        }
    }
}

