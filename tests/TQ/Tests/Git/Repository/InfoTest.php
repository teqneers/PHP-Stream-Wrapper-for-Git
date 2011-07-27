<?php
namespace TQ\Tests\Git\Repository;

use TQ\Git\Cli\Binary;
use TQ\Git\Repository;
use TQ\Tests\Helper;

class InfoTest extends \PHPUnit_Framework_TestCase
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

    public function testShowLog()
    {
        $c  = $this->getRepository();
        $this->assertContains('Initial commit', $c->showLog());

        $hash   = $c->writeFile('/directory/test.txt', 'Test');
        $this->assertContains($hash, $c->showLog());
    }

    public function testShowCommit()
    {
        $c      = $this->getRepository();
        $hash   = $c->writeFile('test.txt', 'Test');
        $commit = $c->showCommit($hash);
        $this->assertContains('test.txt', $commit);
        $this->assertContains('Test', $commit);
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

        $list   = $c->listDirectory('HEAD^^');
        $this->assertContains('file_0.txt', $list);
        $this->assertContains('file_1.txt', $list);
        $this->assertContains('file_2.txt', $list);
        $this->assertContains('file_3.txt', $list);
        $this->assertContains('file_4.txt', $list);
        $this->assertNotContains('test.txt', $list);

        $list   = $c->listDirectory('HEAD^');
        $this->assertContains('file_0.txt', $list);
        $this->assertContains('file_1.txt', $list);
        $this->assertContains('file_2.txt', $list);
        $this->assertContains('file_3.txt', $list);
        $this->assertContains('file_4.txt', $list);
        $this->assertContains('test.txt', $list);

        $c->writeFile('directory/test.txt', 'Test');
        $list   = $c->listDirectory('HEAD', 'directory/');
        $this->assertContains('directory/test.txt', $list);

        $list   = $c->listDirectory('HEAD', 'directory');
        $this->assertContains('directory/test.txt', $list);
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
}

