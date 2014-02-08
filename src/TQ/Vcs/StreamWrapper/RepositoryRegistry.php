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
 * @category   TQ
 * @package    TQ_Vcs
 * @subpackage Vcs
 * @copyright  Copyright (C) 2011 by TEQneers GmbH & Co. KG
 */

namespace TQ\Vcs\StreamWrapper;
use TQ\Vcs\Repository\Repository;

/**
 * Manages multiples repositories by keys
 *
 * @author     Stefan Gehrig <gehrigteqneers.de>
 * @category   TQ
 * @package    TQ_Vcs
 * @subpackage Vcs
 * @copyright  Copyright (C) 2011 by TEQneers GmbH & Co. KG
 */
class RepositoryRegistry
{
    /**
     * The repository map
     *
     * @var array
     */
    protected $map  = array();

    /**
     * Adds a single repository
     *
     * @param   string               $key        The key
     * @param   Repository           $repository The repository
     * @return  RepositoryRegistry
     */
    public function addRepository($key, Repository $repository)
    {
        $this->map[$key]    = $repository;
        return $this;
    }

    /**
     * Adds multiple repositories
     *
     * @param   array      $repositories    The repositories (key => repository)
     * @return  RepositoryRegistry
     */
    public function addRepositories(array $repositories)
    {
        foreach ($repositories as $key => $repository) {
            $this->addRepository($key, $repository);
        }
        return $this;
    }

    /**
     * Returns true if the repository is registered in the map
     *
     * @param   string      $key        The key
     * @return  boolean
     */
    public function hasRepository($key)
    {
        return isset($this->map[$key]);
    }

    /**
     * Returns the repository if it is registered in the map, throws exception otherwise
     *
     * @param   string      $key        The key
     * @return  Repository
     * @throws  \OutOfBoundsException   If the key does not exist
     */
    public function getRepository($key)
    {
        $repository = $this->tryGetRepository($key);
        if ($repository === null) {
            throw new \OutOfBoundsException($key.' does not exist in the registry');
        }
        return $repository;
    }

    /**
     * Returns the repository if it is registered in the map, NULL otherwise
     *
     * @param   string      $key        The key
     * @return  Repository|null
     */
    public function tryGetRepository($key)
    {
        if ($this->hasRepository($key)) {
            return $this->map[$key];
        } else {
            return null;
        }

    }

    /**
     * Count elements of an object
     *
     * @link    http://php.net/manual/en/countable.count.php
     * @return  integer     The custom count as an integer
     */
    public function count()
    {
        return count($this->map);
    }
}