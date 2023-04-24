<?php
/*
 * Copyright (C) 2023 by TEQneers GmbH & Co. KG
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

class FileWriteTest extends TestCase
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

    public function testWriteNewFile()
    {
        $filePath   = sprintf('svn://%s/test.txt', TESTS_REPO_PATH_1);
        $file       = fopen($filePath, 'w');
        fwrite($file, 'Test');
        fclose($file);

        $this->assertFileExists(TESTS_REPO_PATH_1.'/test.txt');
        $this->assertEquals('Test', file_get_contents(TESTS_REPO_PATH_1.'/test.txt'));

        $c      = $this->getRepository();
        $commit = $c->showCommit($c->getCurrentCommit());
        $this->assertMatchesRegularExpression('~^\s{3}A /test.txt$~m', $commit);
    }

    public function testWriteNewFileWithContext()
    {
        $filePath   = sprintf('svn://%s/test.txt', TESTS_REPO_PATH_1);
        $cntxt  = stream_context_create(array(
            'svn'   => array(
                'commitMsg' => 'Hello World',
                'author'    => 'Luke Skywalker <skywalker@deathstar.com>'
            )
        ));
        $file       = fopen($filePath, 'w', false, $cntxt);
        fwrite($file, 'Test');
        fclose($file);

        $this->assertFileExists(TESTS_REPO_PATH_1.'/test.txt');
        $this->assertEquals('Test', file_get_contents(TESTS_REPO_PATH_1.'/test.txt'));

        $c      = $this->getRepository();
        $commit = $c->showCommit($c->getCurrentCommit());
        $this->assertMatchesRegularExpression('~^\s{3}A /test.txt$~m', $commit);
        $this->assertStringContainsString('Hello World', $commit);
        $this->assertStringContainsString('Luke Skywalker <skywalker@deathstar.com>', $commit);
    }

    public function testWriteExistingFile()
    {
        $filePath   = sprintf('svn://%s/file_0.txt', TESTS_REPO_PATH_1);
        $file       = fopen($filePath, 'w');
        fwrite($file, 'Test');
        fclose($file);

        $this->assertFileExists(TESTS_REPO_PATH_1.'/file_0.txt');
        $this->assertEquals('Test', file_get_contents(TESTS_REPO_PATH_1.'/file_0.txt'));

        $c      = $this->getRepository();
        $commit = $c->showCommit($c->getCurrentCommit());
        $this->assertMatchesRegularExpression('~^\s{3}M /file_0.txt$~m', $commit);
    }

    public function testAppendExistingFile()
    {
        $filePath   = sprintf('svn://%s/file_0.txt', TESTS_REPO_PATH_1);
        $file       = fopen($filePath, 'a');
        fwrite($file, 'Test');
        fclose($file);

        $this->assertFileExists(TESTS_REPO_PATH_1.'/file_0.txt');
        $this->assertEquals('File 0Test', file_get_contents(TESTS_REPO_PATH_1.'/file_0.txt'));

        $c      = $this->getRepository();
        $commit = $c->showCommit($c->getCurrentCommit());
        $this->assertMatchesRegularExpression('~^\s{3}M /file_0.txt$~m', $commit);
    }

    public function testWriteNewFileWithX()
    {
        $filePath   = sprintf('svn://%s/test.txt', TESTS_REPO_PATH_1);
        $file       = fopen($filePath, 'x');
        fwrite($file, 'Test');
        fclose($file);

        $this->assertFileExists(TESTS_REPO_PATH_1.'/test.txt');
        $this->assertEquals('Test', file_get_contents(TESTS_REPO_PATH_1.'/test.txt'));

        $c      = $this->getRepository();
        $commit = $c->showCommit($c->getCurrentCommit());
        $this->assertMatchesRegularExpression('~^\s{3}A /test.txt$~m', $commit);
    }

    public function testWriteNewFileWithC()
    {
        $filePath   = sprintf('svn://%s/test.txt', TESTS_REPO_PATH_1);
        $file       = fopen($filePath, 'c');
        fwrite($file, 'Test');
        fclose($file);

        $this->assertFileExists(TESTS_REPO_PATH_1.'/test.txt');
        $this->assertEquals('Test', file_get_contents(TESTS_REPO_PATH_1.'/test.txt'));

        $c      = $this->getRepository();
        $commit = $c->showCommit($c->getCurrentCommit());
        $this->assertMatchesRegularExpression('~^\s{3}A /test.txt$~m', $commit);
    }

    public function testWriteExistingFileWithC()
    {
        $filePath   = sprintf('svn://%s/file_0.txt', TESTS_REPO_PATH_1);
        $file       = fopen($filePath, 'c');
        fseek($file, 0, SEEK_END);
        fwrite($file, 'Test');
        fclose($file);

        $this->assertFileExists(TESTS_REPO_PATH_1.'/file_0.txt');
        $this->assertEquals('File 0Test', file_get_contents(TESTS_REPO_PATH_1.'/file_0.txt'));

        $c      = $this->getRepository();
        $commit = $c->showCommit($c->getCurrentCommit());
        $this->assertMatchesRegularExpression('~^\s{3}M /file_0.txt$~m', $commit);
    }

    public function testWriteAndReadNewFile()
    {
        $filePath   = sprintf('svn://%s/test.txt', TESTS_REPO_PATH_1);
        $file       = fopen($filePath, 'w+');
        fwrite($file, 'Test');
        fseek($file, -2, SEEK_END);
        $this->assertEquals('st', fread($file, 2));
        fseek($file, -2, SEEK_END);
        fwrite($file, 'xt');
        fclose($file);

        $this->assertFileExists(TESTS_REPO_PATH_1.'/test.txt');
        $this->assertEquals('Text', file_get_contents(TESTS_REPO_PATH_1.'/test.txt'));

        $c      = $this->getRepository();
        $commit = $c->showCommit($c->getCurrentCommit());
        $this->assertMatchesRegularExpression('~^\s{3}A /test.txt$~m', $commit);
    }

    public function testWriteNewFileWithFilePutContents()
    {
        $filePath   = sprintf('svn://%s/test.txt', TESTS_REPO_PATH_1);
        file_put_contents($filePath, 'Test');

        $this->assertFileExists(TESTS_REPO_PATH_1.'/test.txt');
        $this->assertEquals('Test', file_get_contents(TESTS_REPO_PATH_1.'/test.txt'));

        $c      = $this->getRepository();
        $commit = $c->showCommit($c->getCurrentCommit());
        $this->assertMatchesRegularExpression('~^\s{3}A /test.txt$~m', $commit);
    }

    public function testWriteExistingFileWithFilePutContents()
    {
        $filePath   = sprintf('svn://%s/file_0.txt', TESTS_REPO_PATH_1);
        file_put_contents($filePath, 'Test');

        $this->assertFileExists(TESTS_REPO_PATH_1.'/file_0.txt');
        $this->assertEquals('Test', file_get_contents(TESTS_REPO_PATH_1.'/file_0.txt'));

        $c      = $this->getRepository();
        $commit = $c->showCommit($c->getCurrentCommit());
        $this->assertMatchesRegularExpression('~^\s{3}M /file_0.txt$~m', $commit);
    }

    public function testAppendExistingFileWithFilePutContents()
    {
        $filePath   = sprintf('svn://%s/file_0.txt', TESTS_REPO_PATH_1);
        file_put_contents($filePath, 'Test', FILE_APPEND);

        $this->assertFileExists(TESTS_REPO_PATH_1.'/file_0.txt');
        $this->assertEquals('File 0Test', file_get_contents(TESTS_REPO_PATH_1.'/file_0.txt'));

        $c      = $this->getRepository();
        $commit = $c->showCommit($c->getCurrentCommit());
        $this->assertMatchesRegularExpression('~^\s{3}M /file_0.txt$~m', $commit);
    }
}
