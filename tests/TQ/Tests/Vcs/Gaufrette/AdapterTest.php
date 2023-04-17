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

namespace TQ\Tests\Vcs\Gaufrette;

use PHPUnit\Framework\TestCase;
use TQ\Svn\Cli\Binary as SvnBinary;
use TQ\Git\Cli\Binary as GitBinary;
use TQ\Tests\Helper;
use TQ\Vcs\Cli\Binary;
use TQ\Vcs\Gaufrette\Adapter;
use TQ\Vcs\Repository\RepositoryInterface;

class AdapterTest extends TestCase
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
        for ($i = 0; $i < 5; $i++) {
            $dir   = sprintf('dir_%d', $i);
            $path  = TESTS_REPO_PATH_1.'/'.$dir;
            Helper::createDirectory($path);
            file_put_contents($path.'/file.txt', sprintf('Directory %d File', $i));
            Helper::executeSvn(TESTS_REPO_PATH_1, sprintf('add %s',
                escapeshellarg($path)
            ));
        }
        Helper::executeSvn(TESTS_REPO_PATH_1, sprintf('commit --message=%s',
            escapeshellarg('Initial commit')
        ));

        Helper::createDirectory(TESTS_REPO_PATH_2);
        Helper::initEmptyGitRepository(TESTS_REPO_PATH_2);
        for ($i = 0; $i < 5; $i++) {
            $file   = sprintf('file_%d.txt', $i);
            $path   = TESTS_REPO_PATH_2.'/'.$file;
            file_put_contents($path, sprintf('File %d', $i));
            Helper::executeGit(TESTS_REPO_PATH_2, sprintf('add %s',
                escapeshellarg($file)
            ));
        }
        for ($i = 0; $i < 5; $i++) {
            $dir   = sprintf('dir_%d', $i);
            $path  = TESTS_REPO_PATH_2.'/'.$dir;
            Helper::createDirectory($path);
            file_put_contents($path.'/file.txt', sprintf('Directory %d File', $i));
            Helper::executeGit(TESTS_REPO_PATH_2, sprintf('add %s',
                escapeshellarg($path)
            ));
        }
        Helper::executeGit(TESTS_REPO_PATH_2, sprintf('commit --message=%s',
            escapeshellarg('Initial commit')
        ));

        clearstatcache();
    }

    /**
     * Tears down the fixture, for example, close a network connection.
     * This method is called after a test is executed.
     */
    protected function tearDown(): void
    {
        Helper::removeDirectory(TESTS_TMP_PATH);
    }

    /**
     * @param   string  $repositoryClass
     * @param   string  $path
     * @param   Binary  $binary
     * @return  Adapter
     */
    protected function createAdapter($repositoryClass, $path, Binary $binary): Adapter
    {
        /** @var $repository RepositoryInterface */
        $repository = call_user_func(array($repositoryClass, 'open'), $path, $binary);
        return new Adapter($repository);
    }

    /**
     * @dataProvider gaufretteAdapterRepositoryDataProvider
     *
     * @param   string  $repositoryClass
     * @param   string  $path
     * @param   Binary  $binary
     */
    public function testKeys($repositoryClass, $path, Binary $binary)
    {
        $adapter    = $this->createAdapter($repositoryClass, $path, $binary);
        $this->assertEquals(array(
            'dir_0',
            'dir_0/file.txt',
            'dir_1',
            'dir_1/file.txt',
            'dir_2',
            'dir_2/file.txt',
            'dir_3',
            'dir_3/file.txt',
            'dir_4',
            'dir_4/file.txt',
            'file_0.txt',
            'file_1.txt',
            'file_2.txt',
            'file_3.txt',
            'file_4.txt'
        ), $adapter->keys());
    }

    /**
     * @dataProvider gaufretteAdapterRepositoryDataProvider
     *
     * @param   string  $repositoryClass
     * @param   string  $path
     * @param   Binary  $binary
     */
    public function testRead($repositoryClass, $path, Binary $binary)
    {
        $adapter    = $this->createAdapter($repositoryClass, $path, $binary);
        $this->assertEquals('File 1', $adapter->read('file_1.txt'));
        $this->assertEquals('Directory 3 File', $adapter->read('dir_3/file.txt'));
    }

    /**
     * @dataProvider gaufretteAdapterRepositoryDataProvider
     *
     * @param   string  $repositoryClass
     * @param   string  $path
     * @param   Binary  $binary
     */
    public function testWrite($repositoryClass, $path, Binary $binary)
    {
        $adapter    = $this->createAdapter($repositoryClass, $path, $binary);
        $repository = $adapter->getRepository();

        $adapter->write('file_3.txt', 'New content');
        $this->assertEquals('New content', file_get_contents($path.'/file_3.txt'));
        $this->assertStringContainsString('file_3.txt', $repository->showCommit($repository->getCurrentCommit()));

        $adapter->write('dir_1/file.txt', 'New content');
        $this->assertEquals('New content', file_get_contents($path.'/dir_1/file.txt'));
        $this->assertStringContainsString('dir_1/file.txt', $repository->showCommit($repository->getCurrentCommit()));

        $adapter->write('new_file.txt', 'New content');
        $this->assertFileExists($path.'/new_file.txt');
        $this->assertEquals('New content', file_get_contents($path.'/new_file.txt'));
        $this->assertStringContainsString('new_file.txt', $repository->showCommit($repository->getCurrentCommit()));
    }

    /**
     * @dataProvider gaufretteAdapterRepositoryDataProvider
     *
     * @param   string  $repositoryClass
     * @param   string  $path
     * @param   Binary  $binary
     */
    public function testRenameFile($repositoryClass, $path, Binary $binary)
    {
        $adapter    = $this->createAdapter($repositoryClass, $path, $binary);
        $repository = $adapter->getRepository();

        $adapter->rename('file_1.txt', 'file_9.txt');
        $this->assertFileDoesNotExist($path.'/file_1.txt');
        $this->assertFileExists($path.'/file_9.txt');
        $this->assertEquals('File 1', file_get_contents($path.'/file_9.txt'));
        $this->assertStringContainsString('file_1.txt', $repository->showCommit($repository->getCurrentCommit()));
        $this->assertStringContainsString('file_9.txt', $repository->showCommit($repository->getCurrentCommit()));
    }

    /**
     * @dataProvider gaufretteAdapterRepositoryDataProvider
     *
     * @param   string  $repositoryClass
     * @param   string  $path
     * @param   Binary  $binary
     */
    public function testRenameFileInDirectory($repositoryClass, $path, Binary $binary)
    {
        $adapter    = $this->createAdapter($repositoryClass, $path, $binary);
        $repository = $adapter->getRepository();

        $adapter->rename('dir_1/file.txt', 'dir_1/new_file.txt');
        $this->assertFileDoesNotExist($path.'/dir_1/file.txt');
        $this->assertFileExists($path.'/dir_1/new_file.txt');
        $this->assertEquals('Directory 1 File', file_get_contents($path.'/dir_1/new_file.txt'));
        $this->assertStringContainsString('dir_1/file.txt', $repository->showCommit($repository->getCurrentCommit()));
        $this->assertStringContainsString('dir_1/new_file.txt', $repository->showCommit($repository->getCurrentCommit()));
    }

    /**
     * @dataProvider gaufretteAdapterRepositoryDataProvider
     *
     * @param   string  $repositoryClass
     * @param   string  $path
     * @param   Binary  $binary
     */
    public function testRenameFileAcrossDirectory($repositoryClass, $path, Binary $binary)
    {
        $adapter    = $this->createAdapter($repositoryClass, $path, $binary);
        $repository = $adapter->getRepository();

        $adapter->rename('dir_1/file.txt', 'dir_2/new_file.txt');
        $this->assertFileDoesNotExist($path.'/dir_1/file.txt');
        $this->assertFileExists($path.'/dir_2/new_file.txt');
        $this->assertEquals('Directory 1 File', file_get_contents($path.'/dir_2/new_file.txt'));
        $this->assertStringContainsString('dir_1/file.txt', $repository->showCommit($repository->getCurrentCommit()));
        $this->assertStringContainsString('dir_2/new_file.txt', $repository->showCommit($repository->getCurrentCommit()));
    }

    /**
     * @dataProvider gaufretteAdapterRepositoryDataProvider
     *
     * @param   string  $repositoryClass
     * @param   string  $path
     * @param   Binary  $binary
     */
    public function testRenameFileFromDirectoryToRoot($repositoryClass, $path, Binary $binary)
    {
        $adapter    = $this->createAdapter($repositoryClass, $path, $binary);
        $repository = $adapter->getRepository();

        $adapter->rename('dir_1/file.txt', 'new_file.txt');
        $this->assertFileDoesNotExist($path.'/dir_1/file.txt');
        $this->assertFileExists($path.'/new_file.txt');
        $this->assertEquals('Directory 1 File', file_get_contents($path.'/new_file.txt'));
        $this->assertStringContainsString('dir_1/file.txt', $repository->showCommit($repository->getCurrentCommit()));
        $this->assertStringContainsString('new_file.txt', $repository->showCommit($repository->getCurrentCommit()));
    }

    /**
     * @dataProvider gaufretteAdapterRepositoryDataProvider
     *
     * @param   string  $repositoryClass
     * @param   string  $path
     * @param   Binary  $binary
     */
    public function testRenameFileFromRootToDirectory($repositoryClass, $path, Binary $binary)
    {
        $adapter    = $this->createAdapter($repositoryClass, $path, $binary);
        $repository = $adapter->getRepository();

        $adapter->rename('file_1.txt', 'dir_1/new_file.txt');
        $this->assertFileDoesNotExist($path.'/file_1.txt');
        $this->assertFileExists($path.'/dir_1/new_file.txt');
        $this->assertEquals('File 1', file_get_contents($path.'/dir_1/new_file.txt'));
        $this->assertStringContainsString('file_1.txt', $repository->showCommit($repository->getCurrentCommit()));
        $this->assertStringContainsString('dir_1/new_file.txt', $repository->showCommit($repository->getCurrentCommit()));
    }

    /**
     * @dataProvider gaufretteAdapterRepositoryDataProvider
     *
     * @param   string  $repositoryClass
     * @param   string  $path
     * @param   Binary  $binary
     */
    public function testExists($repositoryClass, $path, Binary $binary)
    {
        $adapter    = $this->createAdapter($repositoryClass, $path, $binary);

        $this->assertTrue($adapter->exists('file_1.txt'));
        $this->assertTrue($adapter->exists('dir_1/file.txt'));
        $this->assertFalse($adapter->exists('file_9.txt'));
        $this->assertFalse($adapter->exists('dir_9/file.txt'));
        $this->assertFalse($adapter->exists('dir_1/does_not_exist.txt'));
        $this->assertFalse($adapter->exists('dir_9/does_not_exist.txt'));
    }

    /**
     * @dataProvider gaufretteAdapterRepositoryDataProvider
     *
     * @param   string  $repositoryClass
     * @param   string  $path
     * @param   Binary  $binary
     */
    public function testMtime($repositoryClass, $path, Binary $binary)
    {
        $adapter    = $this->createAdapter($repositoryClass, $path, $binary);

        $this->assertGreaterThan(0, $adapter->mtime('file_1.txt'));
        $this->assertGreaterThan(0, $adapter->mtime('dir_1/file.txt'));
    }

    /**
     * @dataProvider gaufretteAdapterRepositoryDataProvider
     *
     * @param   string  $repositoryClass
     * @param   string  $path
     * @param   Binary  $binary
     */
    public function testDelete($repositoryClass, $path, Binary $binary)
    {
        $adapter    = $this->createAdapter($repositoryClass, $path, $binary);
        $repository = $adapter->getRepository();

        $adapter->delete('file_1.txt');
        $this->assertFileDoesNotExist($path.'/file_1.txt');
        $this->assertStringContainsString('file_1.txt', $repository->showCommit($repository->getCurrentCommit()));

        $adapter->delete('dir_1/file.txt');
        $this->assertFileDoesNotExist($path.'/dir_1/file.txt');
        $this->assertStringContainsString('dir_1/file.txt', $repository->showCommit($repository->getCurrentCommit()));
    }

    /**
     * @dataProvider gaufretteAdapterRepositoryDataProvider
     *
     * @param   string  $repositoryClass
     * @param   string  $path
     * @param   Binary  $binary
     */
    public function testIsDirectory($repositoryClass, $path, Binary $binary)
    {
        $adapter    = $this->createAdapter($repositoryClass, $path, $binary);

        $this->assertTrue($adapter->isDirectory('dir_1'));
        $this->assertFalse($adapter->isDirectory('file_4.txt'));
        $this->assertFalse($adapter->isDirectory('dir_1/file.txt'));
    }

    /**
     * @dataProvider gaufretteAdapterRepositoryDataProvider
     *
     * @param   string  $repositoryClass
     * @param   string  $path
     * @param   Binary  $binary
     */
    public function testStream($repositoryClass, $path, Binary $binary)
    {
        $adapter    = $this->createAdapter($repositoryClass, $path, $binary);

        $this->assertInstanceOf('Gaufrette\Stream', $adapter->createStream('file_1.txt'));
    }

    /**
     * @dataProvider gaufretteAdapterRepositoryDataProvider
     *
     * @param   string  $repositoryClass
     * @param   string  $path
     * @param   Binary  $binary
     */
    public function testChecksum($repositoryClass, $path, Binary $binary)
    {
        $adapter    = $this->createAdapter($repositoryClass, $path, $binary);

        $this->assertEquals(32, strlen($adapter->checksum('file_1.txt')));
    }

    /**
     * @return  array
     */
    public function gaufretteAdapterRepositoryDataProvider(): array
    {
        return array(
            array(
                'TQ\Svn\Repository\Repository',
                TESTS_REPO_PATH_1,
                new SvnBinary(SVN_BINARY)
            ),
            array(
                '\TQ\Git\Repository\Repository',
                TESTS_REPO_PATH_2,
                new GitBinary(GIT_BINARY)
            )
        );
    }
}
