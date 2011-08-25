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
 * @subpackage StreamWrapper
 * @copyright  Copyright (C) 2011 by TEQneers GmbH & Co. KG
 */

/**
 * @namespace
 */
namespace TQ\Git\StreamWrapper\FileBuffer\Factory;
use TQ\Git\StreamWrapper\PathInformation;

/**
 * Resolves the file stream factory to use on a stream_open call
 *
 * @author     Stefan Gehrig <gehrigteqneers.de>
 * @category   TQ
 * @package    TQ_Git
 * @subpackage StreamWrapper
 * @copyright  Copyright (C) 2011 by TEQneers GmbH & Co. KG
 */
class Resolver
{
    /**
     * The list containing the possible factories
     *
     * @var \SplPriorityQueue
     */
    protected $factoryList;

    /**
     * Creates a new factory resolver
     */
    public function __construct()
    {
        $this->factoryList  = new \SplPriorityQueue();
    }

    /**
     * Adds a factory to the list of possible factories
     *
     * @param   Factory     $class      The factory
     * @param   integer     $priority   The priority
     * @return  Resolver                The resolver
     */
    public function addFactory(Factory $factory, $priority = 10)
    {
        $this->factoryList->insert($factory, $priority);
        return $this;
    }

    /**
     * Returns the file stream factory to handle the requested path
     *
     * @param   PathInformation     $path   The path information
     * @param   string              $mode   The mode used to open the path
     * @return  Factory                     The file buffer factory to handle the path
     */
    public function findFactory(PathInformation $path, $mode)
    {
        foreach ($this->factoryList as $factory) {
            if ($factory->canHandle($path, $mode)) {
                return $factory;
            }
        }
        throw new \RuntimeException('No factory found to handle the requested path');
    }
}