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
 
namespace TQ\Git\StreamWrapper;

use TQ\Git\Repository\Repository;
use TQ\Git\Cli\Binary;

class PathInformation
{
    /**
     *
     * @var Repository
     */
    protected $repository;

    /**
     *
     * @var string
     */
    protected $fullPath;

    /**
     *
     * @var string
     */
    protected $localPath;

    /**
     *
     * @var string
     */
    protected $ref;

    /**
     *
     * @var array
     */
    protected $arguments;

    /**
     *
     * @param   Binary  $binary
     * @param   array   $url
     */
    public function __construct(Binary $binary, array $url)
    {
        $this->fullPath     = DIRECTORY_SEPARATOR.$url['host'].$url['path'];
        $this->repository   = Repository::open($this->fullPath, $binary, false);
        $this->localPath    = $this->repository->resolveLocalPath($this->fullPath);
        $this->ref          = (array_key_exists('fragment', $url)) ? $url['fragment'] : 'HEAD';

        $arguments  = array();
        if (array_key_exists('query', $url)) {
            parse_str($url['query'], $arguments);
        }
        $this->arguments    = $arguments;
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
     * @return  string
     */
    public function getFullPath()
    {
        return $this->fullPath;
    }

    /**
     *
     * @return  string
     */
    public function getLocalPath()
    {
        return $this->localPath;
    }

    /**
     *
     * @return  string
     */
    public function getRef()
    {
        return $this->ref;
    }

    /**
     *
     * @return  array
     */
    public function getArguments()
    {
        return $this->arguments;
    }

    /**
     *
     * @param   string  $argument
     * @return  boolean
     */
    public function hasArgument($argument)
    {
        return array_key_exists($argument, $this->arguments);
    }

    /**
     *
     * @param   string  $argument
     * @return  string|null
     */
    public function getArgument($argument)
    {
        return ($this->hasArgument($argument)) ? $this->arguments[$argument] : null;
    }
}