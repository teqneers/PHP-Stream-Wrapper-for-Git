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
 * Creates path information for a given stream URL
 *
 * @author     Stefan Gehrig <gehrigteqneers.de>
 * @category   TQ
 * @package    TQ_VCS
 * @subpackage VCS
 * @copyright  Copyright (C) 2014 by TEQneers GmbH & Co. KG
 */
abstract class AbstractPathFactory implements PathFactoryInterface
{
    /**
     * The repository registry
     *
     * @var RepositoryRegistry
     */
    protected $map;

    /**
     * The registered protocol
     *
     * @var string
     */
    protected $protocol;

    /**
     * Creates a path factory
     *
     * @param   string              $protocol    The protocol (such as "vcs")
     * @param   RepositoryRegistry  $map         The repository registry
     */
    public function __construct($protocol, RepositoryRegistry $map = null)
    {
        $this->protocol = $protocol;
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
     * Returns the repository for the given path information
     *
     * @param   array       $pathInfo       An array containing information about the path
     * @return  RepositoryInterface
     */
    protected function getRepository(array $pathInfo)
    {
        if ($pathInfo['host'] === PathInformationInterface::GLOBAL_PATH_HOST) {
            return $this->createRepositoryForPath($pathInfo['path']);
        } else {
            return $this->map->getRepository($pathInfo['host']);
        }
    }

    /**
     * Creates a new Repository instance for the given path
     *
     * @param   string      $path       The path
     * @return  RepositoryInterface
     */
    abstract protected function createRepositoryForPath($path);

    /**
     * Returns the path information for a given stream URL
     *
     * @param   string  $streamUrl      The URL given to the stream function
     * @return  PathInformation         The path information representing the stream URL
     */
    public function createPathInformation($streamUrl)
    {
        $pathInfo     = $this->parsePath($streamUrl);
        $repository   = $this->getRepository($pathInfo);
        $ref          = isset($pathInfo['fragment']) ? $pathInfo['fragment'] : 'HEAD';

        $arguments  = array();
        if (isset($pathInfo['query'])) {
            parse_str($pathInfo['query'], $arguments);
        }

        $fullPath   = $repository->resolveFullPath($pathInfo['path']);
        $url        =  $this->protocol.'://'.$fullPath
                      .'#'.$ref
                      .'?'.http_build_query($arguments);

        return new PathInformation($repository, $url, $fullPath, $ref, $arguments);
    }

    /**
     * Returns path information for a given stream path
     *
     * @param   string      $streamUrl      The URL given to the stream function
     * @return  array                       An array containing information about the path
     * @throws \InvalidArgumentException    If the URL is invalid
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
            $path   = str_replace($protocol.':///', $protocol.'://'.PathInformationInterface::GLOBAL_PATH_HOST.'/', $path);
        }

        $info   = parse_url($path);
        if ($info === false) {
            throw new \InvalidArgumentException('Url "'.$streamUrl.'" is not a valid path');
        }
        if (isset($info['path']) && preg_match('~^/\w:.+~', $info['path'])) {
            $info['path']   = ltrim($info['path'], '/');
        } else if (!isset( $info['path'])) {
             $info['path']  = '/';
        }
        return $info;
    }
}