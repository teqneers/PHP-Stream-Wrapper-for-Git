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
 * Git Stream Wrapper for PHP
 *
 * @package    TQ_Vcs
 * @subpackage Git
 * @subpackage StreamWrapper
 * @copyright  Copyright (C) 2011 by TEQneers GmbH & Co. KG
 */

namespace TQ\Git\StreamWrapper;
use TQ\Vcs\Repository\Repository;
use TQ\Vcs\StreamWrapper\PathInformation as PathInformationInterface;

/**
 * Represents a given stream wrapper path
 *
 * @author     Stefan Gehrig <gehrigteqneers.de>
 * @category   TQ
 * @package    TQ_Vcs
 * @subpackage Git
 * @copyright  Copyright (C) 2011 by TEQneers GmbH & Co. KG
 */
class PathInformation implements PathInformationInterface
{
    /**
     * The repository
     *
     * @var Repository
     */
    protected $repository;

    /**
     * The URL
     *
     * @var string
     */
    protected $url;

    /**
     * The absolute path to the resource
     *
     * @var string
     */
    protected $fullPath;

    /**
     * The version ref
     *
     * @var string
     */
    protected $ref;

    /**
     * Additional arguments
     *
     * @var array
     */
    protected $arguments;

    /**
     * The relative path to the resource based on the repository path
     *
     * Lazy instantiated
     *
     * @var string
     */
    protected $localPath;

    /**
     * Creates a new path information instance from a given URL
     *
     * @param   Repository  $repository     The repository instance
     * @param   string      $url            The URL
     * @param   string      $fullPath       The absolute path to the resource
     * @param   string      $ref            The version ref
     * @param   array       $arguments      The additional arguments given
     */
    public function __construct(Repository $repository, $url, $fullPath, $ref, array $arguments)
    {
        $this->repository   = $repository;
        $this->url          = (string)$url;
        $this->fullPath     = (string)$fullPath;
        $this->ref          = (string)$ref;
        $this->arguments    = $arguments;
    }

    /**
     * Returns the URL
     *
     * @return  string
     */
    public function getUrl()
    {
        return $this->url;
    }

    /**
     * Returns the repository instance
     *
     * @return  Repository
     */
    public function getRepository()
    {
        return $this->repository;
    }

    /**
     * Returns the absolute repository path
     *
     * @return  string
     */
    public function getRepositoryPath()
    {
        return $this->getRepository()->getRepositoryPath();
    }

    /**
     * Returns the absolute path to the resource
     *
     * @return  string
     */
    public function getFullPath()
    {
        return $this->fullPath;
    }

    /**
     * Returns the relative path to the resource based on the repository path
     *
     * @return  string
     */
    public function getLocalPath()
    {
        if (!$this->localPath) {
           $this->localPath = $this->repository->resolveLocalPath($this->fullPath);
        }
        return $this->localPath;
    }

    /**
     * Returns the version ref
     *
     * @return  string
     */
    public function getRef()
    {
        return $this->ref;
    }

    /**
     * Returns the additional arguments given
     *
     * @return  array
     */
    public function getArguments()
    {
        return $this->arguments;
    }

    /**
     * Checks if the given argument exists
     *
     * @param   string  $argument   The argument name
     * @return  boolean
     */
    public function hasArgument($argument)
    {
        return array_key_exists($argument, $this->arguments);
    }

    /**
     * Returns the given argument from the argument collection
     *
     * @param   string  $argument   The argument name
     * @return  string|null         The argument value or NULL if the argument does not exist
     */
    public function getArgument($argument)
    {
        return ($this->hasArgument($argument)) ? $this->arguments[$argument] : null;
    }
}