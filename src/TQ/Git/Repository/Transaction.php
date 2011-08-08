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

/**
 * Git Streamwrapper for PHP
 *
 * @category   TQ
 * @package    TQ_Git
 * @subpackage Repository
 * @copyright  Copyright (C) 2011 by TEQneers GmbH & Co. KG
 */

/**
 * @namespace
 */
namespace TQ\Git\Repository;

/**
 * Encapsulates arguments passed to and from a transactional piece of coce
 *
 * @author     Stefan Gehrig <gehrigteqneers.de>
 * @category   TQ
 * @package    TQ_Git
 * @subpackage Repository
 * @copyright  Copyright (C) 2011 by TEQneers GmbH & Co. KG
 */
class Transaction
{
    /**
     * The Git repository
     *
     * @var Repository
     */
    protected $repository;

    /**
     * The commit message
     *
     * @var string|null
     */
    protected $commitMsg;

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
     * @param   Repository  $binary The Git repository
     */
    public function __construct(Repository $repository)
    {
        $this->repository   = $repository;
    }

    /**
     *
     * @return  Repository
     */
    public function getRepository()
    {
        return $this->repository;
    }

    /**
     *
     * @return  string
     */
    public function getRepositoryPath()
    {
        return $this->getRepository()->getRepositoryPath();
    }

    /**
     *
     * @param   string  $path
     * @return  string
     */
    public function resolvePath($path)
    {
        return $this->getRepository()->resolveFullPath($path);
    }

    /**
     *
     * @return  string|null
     */
    public function getCommitMsg()
    {
        return $this->commitMsg;
    }

    /**
     *
     * @param   string|null $commitMsg
     */
    public function setCommitMsg($commitMsg)
    {
        if ($commitMsg === null) {
            $this->commitMsg    = null;
        } else {
            $this->commitMsg    = (string)$commitMsg;
        }
    }

    /**
     *
     * @return  mixed
     */
    public function getResult()
    {
        return $this->result;
    }

    /**
     *
     * @param   mixed $result
     */
    public function setResult($result)
    {
        $this->result   = $result;
    }

    /**
     *
     * @return  string|null
     */
    public function getCommitHash()
    {
        return $this->commitHash;
    }

    /**
     *
     * @param   string $result
     */
    public function setCommitHash($commitHash)
    {
        $this->commitHash   = $commitHash;
    }
}