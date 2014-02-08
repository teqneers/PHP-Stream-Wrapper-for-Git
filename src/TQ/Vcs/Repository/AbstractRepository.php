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
 * @package    TQ_Vcs
 * @subpackage Vcs
 * @copyright  Copyright (C) 2014 by TEQneers GmbH & Co. KG
 */

namespace TQ\Vcs\Repository;
use TQ\Vcs\FileSystem;

/**
 * Base class for Vcs repositories
 *
 * @uses       TQ\Git\Cli\Binary
 * @author     Stefan Gehrig <gehrigteqneers.de>
 * @category   TQ
 * @package    TQ_Vcs
 * @subpackage Vcs
 * @copyright  Copyright (C) 2014 by TEQneers GmbH & Co. KG
 */
abstract class AbstractRepository implements Repository
{
    /**
     * The repository path
     *
     * @var string
     */
    protected $repositoryPath;

    /**
     * The mode used to create files when requested
     *
     * @var integer
     */
    protected $fileCreationMode  = 0644;

    /**
     * The mode used to create directories when requested
     *
     * @var integer
     */
    protected $directoryCreationMode = 0755;

    /**
     * The author used when committing changes
     *
     * @var string
     */
    protected $author;

    /**
     * Creates a new repository instance - use {@see open()} instead
     *
     * @param   string     $repositoryPath
     */
    protected function __construct($repositoryPath)
    {
        $this->repositoryPath   = rtrim($repositoryPath, '/');
    }

    /**
     * Returns the full file system path to the repository
     *
     * @return  string
     */
    public function getRepositoryPath()
    {
        return $this->repositoryPath;
    }

    /**
     * Returns the mode used to create files when requested
     *
     * @return  integer
     */
    public function getFileCreationMode()
    {
        return $this->fileCreationMode;
    }

    /**
     * Sets the mode used to create files when requested
     *
     * @param   integer     $fileCreationMode   The mode, e.g. 644
     * @return  Repository
     */
    public function setFileCreationMode($fileCreationMode)
    {
        $this->fileCreationMode  = (int)$fileCreationMode;
        return $this;
    }

    /**
     * Returns the mode used to create directories when requested
     *
     * @return  integer
     */
    public function getDirectoryCreationMode()
    {
        return $this->directoryCreationMode;
    }

    /**
     * Sets the mode used to create directories when requested
     *
     * @param   integer     $directoryCreationMode   The mode, e.g. 755
     * @return  Repository
     */
    public function setDirectoryCreationMode($directoryCreationMode)
    {
        $this->directoryCreationMode  = (int)$directoryCreationMode;
        return $this;
    }

    /**
     * Returns the author used when committing changes
     *
     * @return  string
     */
    public function getAuthor()
    {
        return $this->author;
    }

    /**
     * Sets the author used when committing changes
     *
     * @param   string     $author      The author used when committing changes
     * @return  Repository
     */
    public function setAuthor($author)
    {
        $this->author  = (string)$author;
        return $this;
    }

    /**
     * Resolves an absolute path into a path relative to the repository path
     *
     * @param   string|array  $path         A file system path (or an array of paths)
     * @return  string|array                Either a single path or an array of paths is returned
     */
    public function resolveLocalPath($path)
    {
        if (is_array($path)) {
            $paths  = array();
            foreach ($path as $p) {
                $paths[]    = $this->resolveLocalPath($p);
            }
            return $paths;
        } else {
            $path   = FileSystem::normalizeDirectorySeparator($path);
            if (strpos($path, $this->getRepositoryPath()) === 0) {
                $path  = substr($path, strlen($this->getRepositoryPath()));
            }
            return ltrim($path, '/');
        }
    }

    /**
     * Resolves a path relative to the repository into an absolute path
     *
     * @param   string|array  $path     A local path (or an array of paths)
     * @return  string|array            Either a single path or an array of paths is returned
     */
    public function resolveFullPath($path)
    {
        if (is_array($path)) {
            $paths  = array();
            foreach ($path as $p) {
                $paths[]    = $this->resolveFullPath($p);
            }
            return $paths;
        } else {
            if (strpos($path, $this->getRepositoryPath()) === 0) {
                return $path;
            }
            $path  = FileSystem::normalizeDirectorySeparator($path);
            $path  = ltrim($path, '/');
            return $this->getRepositoryPath().'/'.$path;
        }
    }

    /**
     * Runs $function in a transactional scope committing all changes to the repository on success,
     * but rolling back all changes in the event of an Exception being thrown in the closure
     *
     * The closure $function will be called with a {@see TQ\Vcs\Repository\Transaction} as its only argument
     *
     * @param   \Closure   $function        The callback used inside the transaction
     * @return  Transaction
     * @throws  \Exception                  Rethrows every exception happening inside the transaction
     */
    public function transactional(\Closure $function)
    {
        try {
            $transaction    = new Transaction($this);
            $result         = $function($transaction);

            $this->add(null);
            if ($this->isDirty()) {
                $commitMsg  = $transaction->getCommitMsg();
                if (empty($commitMsg)) {
                    $commitMsg  = sprintf(
                        '%s did a transactional commit in "%s"',
                        __CLASS__,
                        $this->getRepositoryPath()
                    );
                }
                $this->commit($commitMsg, null, $transaction->getAuthor());
            }
            $commitHash  = $this->getCurrentCommit();
            $transaction->setCommitHash($commitHash);
            $transaction->setResult($result);
            return $transaction;
        } catch (\Exception $e) {
            $this->reset();
            throw $e;
        }
    }
}

