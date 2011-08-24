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

class FileReadTest extends \PHPUnit_Framework_TestCase
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

    public function testGetContentsOfFile()
    {
        for ($i = 0; $i < 5; $i++) {
            $file       = sprintf('git://%s/file_%d.txt', TESTS_REPO_PATH_1, $i);
            $content    = file_get_contents($file);
            $this->assertEquals(sprintf('File %d', $i), $content);
        }
    }

    public function testReadFileByByte()
    {
        $filePath   = sprintf('git://%s/file_0.txt', TESTS_REPO_PATH_1);
        $file       = fopen($filePath, 'r');
        $expected   = 'File 0';
        $expLength  = strlen($expected);
        for ($i = 0; $i < $expLength; $i++) {
            $this->assertEquals($i, ftell($file));
            $buffer = fgetc($file);
            $this->assertEquals($expected[$i], $buffer);
            $this->assertEquals($i + 1, ftell($file));
        }
        fclose($file);
    }

    public function testSeekInFile()
    {
        $filePath   = sprintf('git://%s/file_0.txt', TESTS_REPO_PATH_1);
        $file       = fopen($filePath, 'r');
        $expected   = 'File 0';

        fseek($file, -1, SEEK_END);
        $this->assertEquals('0', fgetc($file));
        $this->assertEquals(6, ftell($file));
        $this->assertTrue(feof($file));

        fseek($file, 0, SEEK_SET);
        $this->assertEquals('F', fgetc($file));
        $this->assertEquals(1, ftell($file));

        fseek($file, 3, SEEK_CUR);
        $this->assertEquals(' ', fgetc($file));
        $this->assertEquals(5, ftell($file));

        fseek($file, -2, SEEK_CUR);
        $this->assertEquals('e', fgetc($file));
        $this->assertEquals(4, ftell($file));

        fclose($file);
    }

    public function testReadFileInReverse()
    {
        $filePath   = sprintf('git://%s/file_0.txt', TESTS_REPO_PATH_1);
        $file       = fopen($filePath, 'r');
        $expected   = '0 eliF';
        $actual     = '';

        fseek($file, -1, SEEK_END);
        while (($pos = ftell($file)) > 0) {
            $actual .= fgetc($file);
            fseek($file, -2, SEEK_CUR);
        }
        $actual .= fgetc($file);

        fclose($file);

        $this->assertEquals($expected, $actual);
    }

    public function testGetContentsOfFileWithRef()
    {
        $c      = $this->getRepository();

        $file       = sprintf('git://%s/test.txt', TESTS_REPO_PATH_1);

        $commit1 = $c->writeFile('test.txt', 'Test 1');
        $this->assertEquals('Test 1', file_get_contents($file));
        $this->assertEquals('Test 1', file_get_contents($file.'#HEAD'));
        $this->assertEquals('Test 1', file_get_contents($file.'#'.$commit1));

        $commit2 = $c->writeFile('test.txt', 'Test 2');
        $this->assertEquals('Test 2', file_get_contents($file));
        $this->assertEquals('Test 2', file_get_contents($file.'#HEAD'));
        $this->assertEquals('Test 2', file_get_contents($file.'#'.$commit2));
        $this->assertEquals('Test 1', file_get_contents($file.'#HEAD^'));
        $this->assertEquals('Test 1', file_get_contents($file.'#'.$commit1));

        $commit3 = $c->writeFile('test.txt', 'Test 3');
        $this->assertEquals('Test 3', file_get_contents($file));
        $this->assertEquals('Test 3', file_get_contents($file.'#HEAD'));
        $this->assertEquals('Test 3', file_get_contents($file.'#'.$commit3));
        $this->assertEquals('Test 2', file_get_contents($file.'#HEAD^'));
        $this->assertEquals('Test 2', file_get_contents($file.'#'.$commit2));
        $this->assertEquals('Test 1', file_get_contents($file.'#HEAD^^'));
        $this->assertEquals('Test 1', file_get_contents($file.'#'.$commit1));
    }

    public function testGetContentsOfDirectory()
    {
        $c      = $this->getRepository();

        $dir    = sprintf('git://%s', TESTS_REPO_PATH_1);
        $this->assertEquals(Helper::normalizeNewLines("tree HEAD:

file_0.txt
file_1.txt
file_2.txt
file_3.txt
file_4.txt"), Helper::normalizeNewLines(file_get_contents($dir)));

        $c->removeFile('file_0.txt');
        $c->renameFile('file_1.txt', 'file_x.txt');
        $this->assertEquals(Helper::normalizeNewLines("tree HEAD:

file_2.txt
file_3.txt
file_4.txt
file_x.txt"), Helper::normalizeNewLines(file_get_contents($dir)));

        $this->assertEquals(Helper::normalizeNewLines("tree HEAD^:

file_1.txt
file_2.txt
file_3.txt
file_4.txt"), Helper::normalizeNewLines(file_get_contents($dir.'#HEAD^')));

        $this->assertEquals(Helper::normalizeNewLines("tree HEAD^^:

file_0.txt
file_1.txt
file_2.txt
file_3.txt
file_4.txt"), Helper::normalizeNewLines(file_get_contents($dir.'#HEAD^^')));
    }
}

