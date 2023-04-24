<?php
/*
 * Copyright (C) 2017 by TEQneers GmbH & Co. KG
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

namespace TQ\Tests\Svn\StreamWrapper;

use PHPUnit\Framework\TestCase;
use TQ\Svn\Cli\Binary;
use TQ\Svn\Repository\Repository;
use TQ\Svn\StreamWrapper\StreamWrapper;
use TQ\Tests\Helper;

class FileReadTest extends TestCase
{
    /**
     * Sets up the fixture, for example, open a network connection.
     * This method is called before a test is executed.
     */
    protected function setUp(): void
    {
        Helper::removeDirectory(TESTS_TMP_PATH);
        Helper::createDirectory(TESTS_TMP_PATH);
        Helper::createDirectory(TESTS_REPO_PATH_1);

        Helper::initEmptySvnRepository(TESTS_REPO_PATH_1);

        for ($i = 0; $i < 5; $i++) {
            $file   = sprintf('file_%d.txt', $i);
            $path   = TESTS_REPO_PATH_1.'/'.$file;
            file_put_contents($path, sprintf('File %d', $i));
            Helper::executeSvn(TESTS_REPO_PATH_1, sprintf('add %s',
                escapeshellarg($file)
            ));
        }
        Helper::executeSvn(TESTS_REPO_PATH_1, sprintf('commit --message=%s',
            escapeshellarg('Initial commit')
        ));

        clearstatcache();

        StreamWrapper::register('svn', new Binary(SVN_BINARY));
    }

    /**
     * Tears down the fixture, for example, close a network connection.
     * This method is called after a test is executed.
     */
    protected function tearDown(): void
    {
        Helper::removeDirectory(TESTS_TMP_PATH);

        StreamWrapper::unregister();
    }

    /**
     *
     * @return  Repository
     */
    protected function getRepository(): Repository
    {
        return Repository::open(TESTS_REPO_PATH_1, new Binary(SVN_BINARY));
    }

    public function testGetContentsOfFile()
    {
        for ($i = 0; $i < 5; $i++) {
            $file       = sprintf('svn://%s/file_%d.txt', TESTS_REPO_PATH_1, $i);
            $content    = file_get_contents($file);
            $this->assertEquals(sprintf('File %d', $i), $content);
        }
    }

    public function testReadFileByByte()
    {
        $filePath   = sprintf('svn://%s/file_0.txt', TESTS_REPO_PATH_1);
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
        $filePath   = sprintf('svn://%s/file_0.txt', TESTS_REPO_PATH_1);
        $file       = fopen($filePath, 'r');
        /* $expected   = 'File 0'; */

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
        $filePath   = sprintf('svn://%s/file_0.txt', TESTS_REPO_PATH_1);
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

        $file       = sprintf('svn://%s/test.txt', TESTS_REPO_PATH_1);

        $commit1 = $c->writeFile('test.txt', 'Test 1');
        $this->assertEquals('Test 1', file_get_contents($file));
        $this->assertEquals('Test 1', file_get_contents($file.'#HEAD'));
        $this->assertEquals('Test 1', file_get_contents($file.'#'.$commit1));

        $commit2 = $c->writeFile('test.txt', 'Test 2');
        $this->assertEquals('Test 2', file_get_contents($file));
        $this->assertEquals('Test 2', file_get_contents($file.'#HEAD'));
        $this->assertEquals('Test 2', file_get_contents($file.'#'.$commit2));
        $this->assertEquals('Test 1', file_get_contents($file.'#'.$commit1));

        $commit3 = $c->writeFile('test.txt', 'Test 3');
        $this->assertEquals('Test 3', file_get_contents($file));
        $this->assertEquals('Test 3', file_get_contents($file.'#HEAD'));
        $this->assertEquals('Test 3', file_get_contents($file.'#'.$commit3));
        $this->assertEquals('Test 2', file_get_contents($file.'#'.$commit2));
        $this->assertEquals('Test 1', file_get_contents($file.'#'.$commit1));
    }
}
