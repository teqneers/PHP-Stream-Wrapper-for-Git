<?php
/*
 * Copyright (C) 2014 by TEQneers GmbH & Co. KG
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

/**
 * Git Stream Wrapper for PHP
 *
 * @category   TQ
 * @package    TQ_VCS
 * @subpackage VCS
 * @copyright  Copyright (C) 2014 by TEQneers GmbH & Co. KG
 */

namespace TQ\Vcs\Gaufrette;

use Gaufrette\Adapter as AdapterInterface;
use Gaufrette\Adapter\ChecksumCalculator;
use Gaufrette\Adapter\StreamFactory;
use Gaufrette\Stream\Local;
use Gaufrette\Util\Checksum;
use TQ\Vcs\Repository\RepositoryInterface;

/**
 * The Gaufrette VCS adapter
 *
 * @author     Stefan Gehrig <gehrigteqneers.de>
 * @category   TQ
 * @package    TQ_VCS
 * @subpackage VCS
 * @copyright  Copyright (C) 2014 by TEQneers GmbH & Co. KG
 */
class Adapter implements AdapterInterface, StreamFactory, ChecksumCalculator
{
    /**
     * The repository
     *
     * @var RepositoryInterface
     */
    protected $repository;

    /**
     * Creates a new VCS adapter to be used with a Gaufrette filesystem
     *
     * @param   RepositoryInterface     $repository
     */
    public function __construct(RepositoryInterface $repository)
    {
        $this->repository = $repository;
    }

    /**
     * Returns the repository
     *
     * @return  RepositoryInterface
     */
    public function getRepository()
    {
        return $this->repository;
    }

    /**
     * {@inheritDoc}
     */
    public function read($key)
    {
        return $this->repository->showFile($key);
    }

    /**
     * {@inheritDoc}
     */
    public function write($key, $content)
    {
        $this->repository->writeFile($key, $content);
        return true;
    }

    /**
     * {@inheritDoc}
     */
    public function rename($sourceKey, $targetKey)
    {
        $this->repository->renameFile($sourceKey, $targetKey);
        return true;
    }

    /**
     * {@inheritDoc}
     */
    public function exists($key)
    {
        return file_exists($this->repository->resolveFullPath($key));
    }

    /**
     * {@inheritDoc}
     */
    public function keys()
    {
        try {
            $iterator = new \RecursiveIteratorIterator(
                new \RecursiveDirectoryIterator($this->repository->getRepositoryPath(),
                      \FilesystemIterator::SKIP_DOTS
                    | \FilesystemIterator::UNIX_PATHS
                )
            );
        } catch (\Exception $e) {
            $iterator = new \EmptyIterator;
        }

        $keys = array();
        foreach ($iterator as $file) {
            $path   = $this->repository->resolveLocalPath($file);
            if (preg_match('~\.(?:svn|git)~i', $path)) {
                continue;
            }
            $keys[] = $key = $path;
            if ('.' !== dirname($key)) {
                $keys[] = dirname($key);
            }
        }
        $keys   = array_unique($keys);
        sort($keys);

        return $keys;
    }

    /**
     * {@inheritDoc}
     */
    public function mtime($key)
    {
        return filemtime($this->repository->resolveFullPath($key));
    }

    /**
     * {@inheritDoc}
     */
    public function delete($key)
    {
        $this->repository->removeFile($key);
        return true;
    }

    /**
     * {@inheritDoc}
     */
    public function isDirectory($key)
    {
        return is_dir($this->repository->resolveFullPath($key));
    }

    /**
     * {@inheritDoc}
     */
    public function createStream($key)
    {
        return new Local($this->repository->resolveFullPath($key));
    }

    /**
     * {@inheritDoc}
     */
    public function checksum($key)
    {
        return Checksum::fromFile($this->repository->resolveFullPath($key));
    }
}
