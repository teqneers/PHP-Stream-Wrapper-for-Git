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

class StatusReadTest extends TestCase
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

    public function testReadCommit() {

        $c          = $this->getRepository();
        $commits    = array();
        for ($i = 0; $i < 5; $i++) {
            $commits[]  = $c->writeFile(sprintf('test_%d.txt', $i), sprintf('This is file %d', $i));
        }

        foreach ($commits as $c => $commitRevision) {
            $commitUrl  = sprintf('svn://%s?commit&ref=%s', TESTS_REPO_PATH_1, $commitRevision);
            $content    = file_get_contents($commitUrl);

            $this->assertStringContainsString('r'.$commitRevision.' |', $content);
            $this->assertStringContainsString(sprintf('A /test_%d.txt', $c), $content);
        }
    }

    public function testReadLog() {

        $c          = $this->getRepository();
        $commits    = array();
        for ($i = 0; $i < 5; $i++) {
            $commits[]  = $c->writeFile(sprintf('test_%d.txt', $i), sprintf('This is file %d', $i));
        }

        $logUrl  = sprintf('svn://%s?log', TESTS_REPO_PATH_1);
        $log     = file_get_contents($logUrl);
        foreach ($commits as $commitRevision) {
            $this->assertStringContainsString('r'.$commitRevision.' |', $log);
        }

        $logUrl  = sprintf('svn://%s?log&limit=%d', TESTS_REPO_PATH_1, 1);
        $log     = file_get_contents($logUrl);
        foreach ($commits as $c => $commitRevision) {
            if ($c == count($commits) - 1) {
                $this->assertStringContainsString('r'.$commitRevision.' |', $log);
            } else {
                $this->assertStringNotContainsString('r'.$commitRevision.' |', $log);
            }
        }

        $logUrl  = sprintf('svn://%s?log&limit=%d', TESTS_REPO_PATH_1, 2);
        $log     = file_get_contents($logUrl);
        foreach ($commits as $c => $commitRevision) {
            if ($c >= count($commits) - 2) {
                $this->assertStringContainsString('r'.$commitRevision.' |', $log);
            } else {
                $this->assertStringNotContainsString('r'.$commitRevision.' |', $log);
            }
        }

        $logUrl  = sprintf('svn://%s?log&limit=%d&skip=%d', TESTS_REPO_PATH_1, 2, 1);
        $log     = file_get_contents($logUrl);
        foreach ($commits as $c => $commitRevision) {
            if (($c >= count($commits) - 3) && ($c < count($commits) - 1)) {
                $this->assertStringContainsString('r'.$commitRevision.' |', $log);
            } else {
                $this->assertStringNotContainsString('r'.$commitRevision.' |', $log);
            }
        }
    }
}
