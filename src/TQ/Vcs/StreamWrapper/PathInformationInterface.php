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

namespace TQ\Vcs\StreamWrapper;
use TQ\Vcs\Repository\RepositoryInterface;

/**
 * Represents a given stream wrapper path
 *
 * @author     Stefan Gehrig <gehrigteqneers.de>
 * @category   TQ
 * @package    TQ_VCS
 * @subpackage VCS
 * @copyright  Copyright (C) 2014 by TEQneers GmbH & Co. KG
 */
interface PathInformationInterface
{
    /**
     * The host name used for global paths
     */
    const GLOBAL_PATH_HOST  = '__global__';

    /**
     * Returns the URL
     *
     * @return  string
     */
    public function getUrl();

    /**
     * Returns the repository instance
     *
     * @return  RepositoryInterface
     */
    public function getRepository();

    /**
     * Returns the absolute repository path
     *
     * @return  string
     */
    public function getRepositoryPath();

    /**
     * Returns the absolute path to the resource
     *
     * @return  string
     */
    public function getFullPath();

    /**
     * Returns the relative path to the resource based on the repository path
     *
     * @return  string
     */
    public function getLocalPath();

    /**
     * Returns the version ref
     *
     * @return  string
     */
    public function getRef();

    /**
     * Returns the additional arguments given
     *
     * @return  array
     */
    public function getArguments();

    /**
     * Checks if the given argument exists
     *
     * @param   string  $argument   The argument name
     * @return  boolean
     */
    public function hasArgument($argument);

    /**
     * Returns the given argument from the argument collection
     *
     * @param   string  $argument   The argument name
     * @return  string|null         The argument value or NULL if the argument does not exist
     */
    public function getArgument($argument);
}