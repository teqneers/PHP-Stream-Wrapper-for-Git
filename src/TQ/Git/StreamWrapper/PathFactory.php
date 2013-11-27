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
namespace TQ\Git\StreamWrapper;
use TQ\Git\Cli\Binary;

/**
 * Creates path information for a given stream URL
 *
 * @author     Stefan Gehrig <gehrigteqneers.de>
 * @category   TQ
 * @package    TQ_Git
 * @subpackage StreamWrapper
 * @copyright  Copyright (C) 2011 by TEQneers GmbH & Co. KG
 */
class PathFactory
{
    /**
     * The path repository map
     *
     * @var PathRepositoryMap
     */
    protected $map;

    /**
     * The Git binary
     *
     * @var Binary
     */
    protected $binary;

    /**
     * The registered protocol
     *
     * @var string
     */
    protected $protocol;

    /**
     * Registers the stream wrapper with the given protocol
     *
     * @param   string              $protocol    The protocol (such as "git")
     * @param   Binary|string|null  $binary      The Git binary
     * @param   PathRepositoryMap   $map         The path repository map
     */
    public function __construct($protocol, $binary = null, PathRepositoryMap $map = null)
    {
        $this->protocol = $protocol;
        $this->binary   = Binary::ensure($binary);
        $this->map      = $map ?: new PathRepositoryMap();
    }

    /**
     * Returns the path repository map
     *
     * @return  PathRepositoryMap
     */
    public function getMap()
    {
        return $this->map;
    }

    /**
     * Returns the path information for a given stream URL
     *
     * @param   string  $streamUrl      The URL given to the stream function
     * @return  PathInformation         The path information representing the stream URL
     */
    public function createPathInformation($streamUrl)
    {
        return new PathInformation($streamUrl, $this->protocol, $this->binary);
    }
}