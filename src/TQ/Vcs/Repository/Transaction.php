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

/**
 * Git Stream Wrapper for PHP
 *
 * @category   TQ
 * @package    TQ_VCS
 * @subpackage VCS
 * @copyright  Copyright (C) 2023 by TEQneers GmbH & Co. KG
 */

namespace TQ\Vcs\Repository;

/**
 * Encapsulates arguments passed to and from a transactional piece of code
 *
 * @author     Stefan Gehrig <gehrigteqneers.de>
 * @category   TQ
 * @package    TQ_VCS
 * @subpackage VCS
 * @copyright  Copyright (C) 2023 by TEQneers GmbH & Co. KG
 */
class Transaction
{
    /**
     * The VCS repository
     *
     * @var RepositoryInterface
     */
    protected $repository;

    /**
     * The commit message
     *
     * @var string|null
     */
    protected $commitMsg;

    /**
     * The author
     *
     * @var string|null
     */
    protected $author;

    /**
     * The return value of the transactional callback
     *
     * @var mixed
     */
    protected $result;

    /**
     * The commit hash
     *
     * @var string|null
     */
    protected $commitHash;

    /**
     * Creates a new transactional parameter
     *
     * @param   RepositoryInterface  $repository     The VCS repository
     */
    public function __construct(RepositoryInterface $repository)
    {
        $this->repository   = $repository;
    }

    /**
     * Returns the VCS repository
     *
     * @return  RepositoryInterface
     */
    public function getRepository()
    {
        return $this->repository;
    }

    /**
     * Returns the full file system path to the VCS repository
     *
     * @return  string
     */
    public function getRepositoryPath()
    {
        return $this->getRepository()->getRepositoryPath();
    }

    /**
     * Resolves a path relative to the repository into an absolute path
     *
     * @param   string  $path   The relative path to convert to an absolute  path
     * @return  string
     */
    public function resolvePath($path)
    {
        return $this->getRepository()->resolveFullPath($path);
    }

    /**
     * Returns the commit message that will be used when committing the transaction
     *
     * @return  string|null
     */
    public function getCommitMsg()
    {
        return $this->commitMsg;
    }

    /**
     * Sets  the commit message that will be used when committing the transaction
     *
     * @param   string|null $commitMsg      The commit message
     * @return  Transaction
     */
    public function setCommitMsg($commitMsg)
    {
        if ($commitMsg === null) {
            $this->commitMsg    = null;
        } else {
            $this->commitMsg    = (string)$commitMsg;
        }
        return $this;
    }

    /**
     * Returns the author that will be used when committing the transaction
     *
     * @return  string|null
     */
    public function getAuthor()
    {
        return $this->author;
    }

    /**
     * Sets  the author that will be used when committing the transaction
     *
     * @param   string|null     $author      The author
     * @return  Transaction
     */
    public function setAuthor($author)
    {
        if ($author === null) {
            $this->author    = null;
        } else {
            $this->author    = (string)$author;
        }
        return $this;
    }

    /**
     * Returns the return value of the closure executed in the transactional scope
     *
     * @return  mixed
     */
    public function getResult()
    {
        return $this->result;
    }

    /**
     * Sets the return value of the closure executed in the transactional scope
     *
     * @param   mixed $result       The return value
     * @return  Transaction
     */
    public function setResult($result)
    {
        $this->result   = $result;
        return $this;
    }

    /**
     * Returns the hash identifying the commit
     *
     * @return  string|null
     */
    public function getCommitHash()
    {
        return $this->commitHash;
    }

    /**
     * Sets the hash identifying the commit
     *
     * @param   string  $commitHash     The commit hash
     * @return  Transaction
     */
    public function setCommitHash($commitHash)
    {
        $this->commitHash   = $commitHash;
        return $this;
    }
}
