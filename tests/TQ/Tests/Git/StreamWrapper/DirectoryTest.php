<?php
namespace TQ\Tests\Git\StreamWrapper;

use TQ\Git\Cli\Binary;
use TQ\Git\Repository;
use TQ\Git\StreamWrapper;
use TQ\Tests\Helper;

class DirectoryTest extends \PHPUnit_Framework_TestCase
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

        for ($i = 0; $i < 5; $i++) {
            $dir   = sprintf('dir_%d', $i);
            $path  = TESTS_REPO_PATH_1.'/'.$dir;
            mkdir($path, 0777);
            file_put_contents($path.'/file.txt', sprintf('Directory %d File', $i));
            exec(sprintf('cd %s && %s add %s',
                escapeshellarg(TESTS_REPO_PATH_1),
                escapeshellcmd(GIT_BINARY),
                escapeshellarg($path)
            ));
        }

        exec(sprintf('cd %s && %s commit --message=%s',
            escapeshellarg(TESTS_REPO_PATH_1),
            escapeshellcmd(GIT_BINARY),
            escapeshellarg('Initial commit')
        ));

        StreamWrapper::register('git', new Binary(GIT_BINARY));
    }

    /**
     * Tears down the fixture, for example, close a network connection.
     * This method is called after a test is executed.
     */
    protected function tearDown()
    {
        Helper::removeDirectory(TESTS_TMP_PATH);

        StreamWrapper::unregister();
    }

    /**
     *
     * @return  Repository
     */
    protected function getRepository()
    {
        return Repository::open(TESTS_REPO_PATH_1, new Binary(GIT_BINARY));
    }

    public function testListDirectory()
    {
        $dir    = opendir('git://'.TESTS_REPO_PATH_1);
        $i      = 0;
        while ($f = readdir($dir)) {
            if ($i < 5) {
                $this->assertEquals(sprintf('dir_%d', $i), $f);
            } else {
                $this->assertEquals(sprintf('file_%d.txt', $i % 5), $f);
            }
            $i++;
        }
        closedir($dir);
        $this->assertEquals(10, $i);
    }

    public function testListSubDirectory()
    {
        $dir    = opendir('git://'.TESTS_REPO_PATH_1.'/dir_0');
        $i      = 0;
        while ($f = readdir($dir)) {
            $this->assertEquals('file.txt', $f);
            $i++;
        }
        closedir($dir);
        $this->assertEquals(1, $i);
    }

    public function testListDirectoryWithRef()
    {
        $c  = $this->getRepository();
        $firstCommit   = $c->writeFile('test_0.txt', 'Test 0');
        $c->writeFile('test_1.txt', 'Test 1');

        $dir    = opendir('git://'.TESTS_REPO_PATH_1);
        $i      = 0;
        while ($f = readdir($dir)) {
            if ($i < 5) {
                $this->assertEquals(sprintf('dir_%d', $i), $f);
            } else if ($i < 10) {
                $this->assertEquals(sprintf('file_%d.txt', $i % 5), $f);
            } else {
                $this->assertEquals(sprintf('test_%d.txt', $i % 10), $f);
            }
            $i++;
        }
        closedir($dir);
        $this->assertEquals(12, $i);

        $dir    = opendir('git://'.TESTS_REPO_PATH_1.'#HEAD^');
        $i      = 0;
        while ($f = readdir($dir)) {
            if ($i < 5) {
                $this->assertEquals(sprintf('dir_%d', $i), $f);
            } else if ($i < 10) {
                $this->assertEquals(sprintf('file_%d.txt', $i % 5), $f);
            } else {
                $this->assertEquals(sprintf('test_%d.txt', $i % 10), $f);
            }
            $i++;
        }
        closedir($dir);
        $this->assertEquals(11, $i);

        $dir    = opendir('git://'.TESTS_REPO_PATH_1.'#HEAD^^');
        $i      = 0;
        while ($f = readdir($dir)) {
            if ($i < 5) {
                $this->assertEquals(sprintf('dir_%d', $i), $f);
            } else {
                $this->assertEquals(sprintf('file_%d.txt', $i % 5), $f);
            }
            $i++;
        }
        closedir($dir);
        $this->assertEquals(10, $i);

        $dir    = opendir('git://'.TESTS_REPO_PATH_1.'#'.$firstCommit);
        $i      = 0;
        while ($f = readdir($dir)) {
            if ($i < 5) {
                $this->assertEquals(sprintf('dir_%d', $i), $f);
            } else if ($i < 10) {
                $this->assertEquals(sprintf('file_%d.txt', $i % 5), $f);
            } else {
                $this->assertEquals(sprintf('test_%d.txt', $i % 10), $f);
            }
            $i++;
        }
        closedir($dir);
        $this->assertEquals(11, $i);
    }

}

