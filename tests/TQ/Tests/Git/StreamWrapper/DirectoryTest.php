<?php
/*
 * Copyright (C) 2011 by TEQneers GmbH & Co. KG
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

namespace TQ\Tests\Git\StreamWrapper;

use TQ\Git\Cli\Binary;
use TQ\Git\Repository\Repository;
use TQ\Git\StreamWrapper\StreamWrapper;
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
            GIT_BINARY
        ));

        for ($i = 0; $i < 5; $i++) {
            $file   = sprintf('file_%d.txt', $i);
            $path   = TESTS_REPO_PATH_1.'/'.$file;
            file_put_contents($path, sprintf('File %d', $i));
            exec(sprintf('cd %s && %s add %s',
                escapeshellarg(TESTS_REPO_PATH_1),
                GIT_BINARY,
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
                GIT_BINARY,
                escapeshellarg($path)
            ));
        }

        exec(sprintf('cd %s && %s commit --message=%s',
            escapeshellarg(TESTS_REPO_PATH_1),
            GIT_BINARY,
            escapeshellarg('Initial commit')
        ));

        clearstatcache();

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

    public function testListDirectoryWithIterator()
    {
        $dir    = new \FilesystemIterator(
            'git://'.TESTS_REPO_PATH_1,
            \FilesystemIterator::KEY_AS_FILENAME | \FilesystemIterator::CURRENT_AS_FILEINFO
        );
        $i      = 0;
        foreach ($dir as $f => $fi) {
            if ($i < 5) {
                $this->assertEquals(sprintf('dir_%d', $i), $f);
            } else {
                $this->assertEquals(sprintf('file_%d.txt', $i % 5), $f);
            }
            $i++;
        }
        $this->assertEquals(10, $i);
    }

    public function testListDirectoryWithRecursiveIterator()
    {
        $dir    = new \RecursiveDirectoryIterator(
            'git://'.TESTS_REPO_PATH_1,
            \FilesystemIterator::KEY_AS_FILENAME | \FilesystemIterator::CURRENT_AS_FILEINFO
        );
        $it     = new \RecursiveIteratorIterator($dir, \RecursiveIteratorIterator::SELF_FIRST);
        $i      = 0;
        foreach ($it as $f => $fi) {
            if ($i < 10) {
                if ($i % 2 === 0) {
                    $this->assertEquals(sprintf('dir_%d', $i / 2), $f);
                } else {
                    $this->assertEquals('file.txt', $f);
                }
            } else {
                $this->assertEquals(sprintf('file_%d.txt', $i % 5), $f);
            }
            $i++;
        }
        $this->assertEquals(15, $i);
    }

    public function testListDirectoryWithRefWithRecursiveIterator()
    {
        $c  = $this->getRepository();
        for ($i = 0; $i < 5; $i++) {
            $dir   = sprintf('dir_%d', $i);
            $path  = TESTS_REPO_PATH_1.'/'.$dir.'/test.txt';
            $c->writeFile($path, 'Test');
        }
        $c->writeFile('test.txt', 'Test');

        $dir    = new \RecursiveDirectoryIterator(
            'git://'.TESTS_REPO_PATH_1,
            \FilesystemIterator::KEY_AS_FILENAME | \FilesystemIterator::CURRENT_AS_FILEINFO
        );
        $it = new \RecursiveIteratorIterator($dir, \RecursiveIteratorIterator::SELF_FIRST);
        $i  = 0;
        $ex = array(
            'dir_0',
            'file.txt',
            'test.txt',
            'dir_1',
            'file.txt',
            'test.txt',
            'dir_2',
            'file.txt',
            'test.txt',
            'dir_3',
            'file.txt',
            'test.txt',
            'dir_4',
            'file.txt',
            'test.txt',
            'file_0.txt',
            'file_1.txt',
            'file_2.txt',
            'file_3.txt',
            'file_4.txt',
            'test.txt'
        );
        foreach ($it as $f => $fi) {
            $this->assertEquals($ex[$i], $f);
            $i++;
        }
        $this->assertEquals(count($ex), $i);

        $dir    = new \RecursiveDirectoryIterator(
            'git://'.TESTS_REPO_PATH_1.'#HEAD^',
            \FilesystemIterator::KEY_AS_FILENAME | \FilesystemIterator::CURRENT_AS_FILEINFO
        );
        $it = new \RecursiveIteratorIterator($dir, \RecursiveIteratorIterator::SELF_FIRST);
        $i  = 0;
        $ex = array(
            'dir_0',
            'file.txt',
            'test.txt',
            'dir_1',
            'file.txt',
            'test.txt',
            'dir_2',
            'file.txt',
            'test.txt',
            'dir_3',
            'file.txt',
            'test.txt',
            'dir_4',
            'file.txt',
            'test.txt',
            'file_0.txt',
            'file_1.txt',
            'file_2.txt',
            'file_3.txt',
            'file_4.txt',
        );
        foreach ($it as $f => $fi) {
            $this->assertEquals($ex[$i], $f);
            $i++;
        }
        $this->assertEquals(count($ex), $i);

        $dir    = new \RecursiveDirectoryIterator(
            'git://'.TESTS_REPO_PATH_1.'#HEAD^^',
            \FilesystemIterator::KEY_AS_FILENAME | \FilesystemIterator::CURRENT_AS_FILEINFO
        );
        $it = new \RecursiveIteratorIterator($dir, \RecursiveIteratorIterator::SELF_FIRST);
        $i  = 0;
        $ex = array(
            'dir_0',
            'file.txt',
            'test.txt',
            'dir_1',
            'file.txt',
            'test.txt',
            'dir_2',
            'file.txt',
            'test.txt',
            'dir_3',
            'file.txt',
            'test.txt',
            'dir_4',
            'file.txt',
            'file_0.txt',
            'file_1.txt',
            'file_2.txt',
            'file_3.txt',
            'file_4.txt'
        );
        foreach ($it as $f => $fi) {
            $this->assertEquals($ex[$i], $f);
            $i++;
        }
        $this->assertEquals(count($ex), $i);

        $dir    = new \RecursiveDirectoryIterator(
            'git://'.TESTS_REPO_PATH_1.'#HEAD^^^',
            \FilesystemIterator::KEY_AS_FILENAME | \FilesystemIterator::CURRENT_AS_FILEINFO
        );
        $it = new \RecursiveIteratorIterator($dir, \RecursiveIteratorIterator::SELF_FIRST);
        $i  = 0;
        $ex = array(
            'dir_0',
            'file.txt',
            'test.txt',
            'dir_1',
            'file.txt',
            'test.txt',
            'dir_2',
            'file.txt',
            'test.txt',
            'dir_3',
            'file.txt',
            'dir_4',
            'file.txt',
            'file_0.txt',
            'file_1.txt',
            'file_2.txt',
            'file_3.txt',
            'file_4.txt'
        );
        foreach ($it as $f => $fi) {
            $this->assertEquals($ex[$i], $f);
            $i++;
        }
        $this->assertEquals(count($ex), $i);

        $dir    = new \RecursiveDirectoryIterator(
            'git://'.TESTS_REPO_PATH_1.'#HEAD^^^^',
            \FilesystemIterator::KEY_AS_FILENAME | \FilesystemIterator::CURRENT_AS_FILEINFO
        );
        $it = new \RecursiveIteratorIterator($dir, \RecursiveIteratorIterator::SELF_FIRST);
        $i  = 0;
        $ex = array(
            'dir_0',
            'file.txt',
            'test.txt',
            'dir_1',
            'file.txt',
            'test.txt',
            'dir_2',
            'file.txt',
            'dir_3',
            'file.txt',
            'dir_4',
            'file.txt',
            'file_0.txt',
            'file_1.txt',
            'file_2.txt',
            'file_3.txt',
            'file_4.txt'
        );
        foreach ($it as $f => $fi) {
            $this->assertEquals($ex[$i], $f);
            $i++;
        }
        $this->assertEquals(count($ex), $i);

        $dir    = new \RecursiveDirectoryIterator(
            'git://'.TESTS_REPO_PATH_1.'#HEAD^^^^^',
            \FilesystemIterator::KEY_AS_FILENAME | \FilesystemIterator::CURRENT_AS_FILEINFO
        );
        $it = new \RecursiveIteratorIterator($dir, \RecursiveIteratorIterator::SELF_FIRST);
        $i  = 0;
        $ex = array(
            'dir_0',
            'file.txt',
            'test.txt',
            'dir_1',
            'file.txt',
            'dir_2',
            'file.txt',
            'dir_3',
            'file.txt',
            'dir_4',
            'file.txt',
            'file_0.txt',
            'file_1.txt',
            'file_2.txt',
            'file_3.txt',
            'file_4.txt'
        );
        foreach ($it as $f => $fi) {
            $this->assertEquals($ex[$i], $f);
            $i++;
        }
        $this->assertEquals(count($ex), $i);

        $dir    = new \RecursiveDirectoryIterator(
            'git://'.TESTS_REPO_PATH_1.'#HEAD^^^^^^',
            \FilesystemIterator::KEY_AS_FILENAME | \FilesystemIterator::CURRENT_AS_FILEINFO
        );
        $it = new \RecursiveIteratorIterator($dir, \RecursiveIteratorIterator::SELF_FIRST);
        $i  = 0;
        $ex = array(
            'dir_0',
            'file.txt',
            'dir_1',
            'file.txt',
            'dir_2',
            'file.txt',
            'dir_3',
            'file.txt',
            'dir_4',
            'file.txt',
            'file_0.txt',
            'file_1.txt',
            'file_2.txt',
            'file_3.txt',
            'file_4.txt'
        );
        foreach ($it as $f => $fi) {
            $this->assertEquals($ex[$i], $f);
            $i++;
        }
        $this->assertEquals(count($ex), $i);
    }
}

