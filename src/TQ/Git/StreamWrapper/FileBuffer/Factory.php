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
 * @package    TQ_Git
 * @subpackage StreamWrapper
 * @copyright  Copyright (C) 2011 by TEQneers GmbH & Co. KG
 */

/**
 * @namespace
 */
namespace TQ\Git\StreamWrapper\FileBuffer;
use TQ\Git\StreamWrapper\FileBuffer\Factory\Factory as FactoryInterface;
use TQ\Git\StreamWrapper\PathInformation;
use TQ\Vcs\Buffer\FileBuffer;
use TQ\Git\StreamWrapper\FileBuffer\Factory\CommitFactory;
use TQ\Git\StreamWrapper\FileBuffer\Factory\DefaultFactory;
use TQ\Git\StreamWrapper\FileBuffer\Factory\HeadFileFactory;
use TQ\Git\StreamWrapper\FileBuffer\Factory\LogFactory;

/**
 * Resolves the file stream factory to use on a stream_open call
 *
 * @author     Stefan Gehrig <gehrigteqneers.de>
 * @category   TQ
 * @package    TQ_Git
 * @subpackage StreamWrapper
 * @copyright  Copyright (C) 2011 by TEQneers GmbH & Co. KG
 */
class Factory implements FactoryInterface
{
    /**
     * The list containing the possible factories
     *
     * @var \SplPriorityQueue
     */
    protected $factoryList;

    /**
     * Returns a default factory
     *
     * includes:
     * - CommitFactory
     * - LogFactory
     * - HeadFileFactory
     * - DefaultFactory
     *
     * @return  Factory
     */
    public static function getDefault()
    {
        $factory    = new self();
        $factory->addFactory(new CommitFactory(), 100)
                ->addFactory(new LogFactory(), 90)
                ->addFactory(new HeadFileFactory(), 80)
                ->addFactory(new DefaultFactory(), -100);
        return $factory;
    }

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
     * @param   FactoryInterface    $factory    The factory to add
     * @param   integer             $priority   The priority
     * @return  Factory                         The factory
     */
    public function addFactory(FactoryInterface $factory, $priority = 10)
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
     * @throws  \RuntimeException           If no factory is found to handle to the path
     */
    public function findFactory(PathInformation $path, $mode)
    {
        $factoryList    = clone $this->factoryList;
        foreach ($factoryList as $factory) {
            /** @var $factory Factory */
            if ($factory->canHandle($path, $mode)) {
                return $factory;
            }
        }
        throw new \RuntimeException('No factory found to handle the requested path');
    }

    /**
     * Returns true if this factory can handle the requested path
     *
     * @param   PathInformation     $path   The path information
     * @param   string              $mode   The mode used to open the file
     * @return  boolean                     True if this factory can handle the path
     */
    function canHandle(PathInformation $path, $mode)
    {
        try {
            $this->findFactory($path, $mode);
            return true;
        } catch (\RuntimeException $e) {
            return false;
        }
    }

    /**
     * Returns the file stream to handle the requested path
     *
     * @param   PathInformation     $path   The path information
     * @param   string              $mode   The mode used to open the path
     * @return  FileBuffer                  The file buffer to handle the path
     */
    function createFileBuffer(PathInformation $path, $mode)
    {
        $factory    = $this->findFactory($path, $mode);
        return $factory->createFileBuffer($path, $mode);
    }
}