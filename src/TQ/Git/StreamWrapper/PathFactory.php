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
use TQ\Git\Repository\Repository;
use TQ\Git\Repository\RepositoryRegistry;

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
     * The repository registry
     *
     * @var RepositoryRegistry
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
     * @param   RepositoryRegistry  $map         The repository registry
     */
    public function __construct($protocol, $binary = null, RepositoryRegistry $map = null)
    {
        $this->protocol = $protocol;
        $this->binary   = Binary::ensure($binary);
        $this->map      = $map ?: new RepositoryRegistry();
    }

    /**
     * Returns the repository registry
     *
     * @return  RepositoryRegistry
     */
    public function getRegistry()
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
        $pathInfo     = $this->parsePath($streamUrl);
        $fullPath     = $pathInfo['path'];
        $repository   = Repository::open($fullPath, $this->binary, false);
        $ref          = isset($pathInfo['fragment']) ? $pathInfo['fragment'] : 'HEAD';

        $arguments  = array();
        if (isset($pathInfo['query'])) {
            parse_str($pathInfo['query'], $arguments);
        }

        $url    =  $this->protocol.'://'.$fullPath
                  .'#'.$ref
                  .'?'.http_build_query($arguments);

        return new PathInformation($repository, $url, $fullPath, $ref, $arguments);
    }

    /**
     * Returns path information for a given stream path
     *
     * @param   string      $streamUrl      The URL given to the stream function
     * @return  array                       An array containing information about the path
     */
    public function parsePath($streamUrl)
    {
        // normalize directory separators
        $path   = str_replace(array('\\', '/'), '/', $streamUrl);
        //fix path if fragment has been munged into the path (e.g. when using the RecursiveIterator)
        $path   = preg_replace('~^(.+?)(#[^/]+)(.*)$~', '$1$3$2', $path);

        /// fix /// paths to __global__ "host"
        $protocol   = $this->protocol;
        if (strpos($path, $protocol.':///') === 0) {
            $path   = str_replace($protocol.':///', $protocol.'://'.PathInformation::GLOBAL_PATH_HOST.'/', $path);
        }

        $info   = parse_url($path);
        if (isset($info['path']) && preg_match('~^/\w:.+~', $info['path'])) {
            $info['path']   = ltrim($info['path'], '/');
        }
        return $info;
    }
}