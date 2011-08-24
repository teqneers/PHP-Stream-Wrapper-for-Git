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
namespace TQ\Git\StreamWrapper;

/**
 * Resolves the file stream factory to use on a stream_open call
 *
 * @author     Stefan Gehrig <gehrigteqneers.de>
 * @category   TQ
 * @package    TQ_Git
 * @subpackage StreamWrapper
 * @copyright  Copyright (C) 2011 by TEQneers GmbH & Co. KG
 */
class FileStreamFactoryResolver
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
     * Adds a factory class to the list of possible factories
     *
     * @param   string      $class      The factory class name
     * @param   integer     $priority   The priority
     */
    public function addFactoryClass($class, $priority = 10)
    {
        $class  = (string)$class;
        if (strpos($class, '\\') === false) {
            $class  = __NAMESPACE__.'\\'.$class;
        }
        if (!class_exists($class)) {
            throw new \InvalidArgumentException(sprintf('"%s" does not exist', $class));
        }

        if (!is_subclass_of($class, __NAMESPACE__.'\\FileStreamFactory')) {
            throw new \InvalidArgumentException(sprintf('"%s" is not a valid factory', $class));
        }
        $this->factoryList->insert($class, $priority);
    }

    /**
     * Returns the file stream factory to handle the requested path
     *
     * @param   PathInformation     $path   The path information
     * @param   string              $mode   The mode used to open the path
     * @return  FileStreamFactory           The file stream factory to handle the path
     */
    public function resolveFactory(PathInformation $path, $mode)
    {
        foreach ($this->factoryList as $factoryClass) {
            $factory    = new $factoryClass();
            if ($factory->canHandle($path, $mode)) {
                return $factory;
            }
        }
        throw new \RuntimeException('No factory found to handle the requested path');
    }
}