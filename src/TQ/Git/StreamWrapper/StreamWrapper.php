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
 * @subpackage Git
 * @copyright  Copyright (C) 2014 by TEQneers GmbH & Co. KG
 */

namespace TQ\Git\StreamWrapper;
use TQ\Git\Cli\Binary;
use TQ\Git\StreamWrapper\FileBuffer\Factory;
use TQ\Vcs\StreamWrapper\AbstractStreamWrapper;
use TQ\Vcs\StreamWrapper\PathFactoryInterface;

/**
 * The stream wrapper that hooks into PHP's stream infrastructure
 *
 * @author     Stefan Gehrig <gehrigteqneers.de>
 * @category   TQ
 * @package    TQ_VCS
 * @subpackage Git
 * @copyright  Copyright (C) 2014 by TEQneers GmbH & Co. KG
 */
class StreamWrapper extends AbstractStreamWrapper
{
    /**
     * Registers the stream wrapper with the given protocol
     *
     * @param   string                                   $protocol    The protocol (such as "git")
     * @param   Binary|string|null|PathFactoryInterface  $binary      The Git binary or a path factory
     * @throws  \RuntimeException                                     If $protocol is already registered
     */
    public static function register($protocol, $binary = null)
    {
        $bufferFactory  = Factory::getDefault();
        if ($binary instanceof PathFactoryInterface) {
            $pathFactory  = $binary;
        } else {
            $binary         = Binary::ensure($binary);
            $pathFactory    = new PathFactory($protocol, $binary, null);
        }
        parent::doRegister($protocol, $pathFactory, $bufferFactory);
    }

    /**
     * streamWrapper::mkdir — Create a directory
     *
     * @param   string   $path      Directory which should be created.
     * @param   integer  $mode      The value passed to {@see mkdir()}.
     * @param   integer  $options   A bitwise mask of values, such as STREAM_MKDIR_RECURSIVE.
     * @return  boolean             Returns TRUE on success or FALSE on failure.
     */
    public function mkdir($path, $mode, $options)
    {
        try {
            $path   = $this->getPath($path);
            if ($path->getRef() != 'HEAD') {
                throw new \Exception(sprintf(
                    'Cannot create a non-HEAD directory [%s#%s]', $path->getFullPath(), $path->getRef()
                ));
            }
            if (file_exists($path->getFullPath())) {
                throw new \Exception(sprintf('Path %s already exists', $path->getFullPath()));
            }

            $recursive  = self::maskHasFlag($options, STREAM_MKDIR_RECURSIVE);

            $repo   = $path->getRepository();

            $commitMsg      = $this->getContextOption('commitMsg', null);
            $author         = $this->getContextOption('author', null);

            $repo->writeFile($path->getLocalPath().'/.gitkeep', '', $commitMsg, 0666, $mode, $recursive, $author);
            return true;
        } catch (\Exception $e) {
            trigger_error($e->getMessage(), E_USER_WARNING);
            return false;
        }
    }

    /**
     * streamWrapper::rename — Renames a file or directory
     *
     * @param   string   $path_from     The URL to the current file.
     * @param   string   $path_to       The URL which the $path_from should be renamed to.
     * @return  boolean                 Returns TRUE on success or FALSE on failure.
     */
    public function rename($path_from, $path_to)
    {
        try {
            $pathFrom   = $this->getPath($path_from);
            if ($pathFrom->getRef() != 'HEAD') {
                throw new \Exception(sprintf(
                    'Cannot rename a non-HEAD file [%s#%s]', $pathFrom->getFullPath(), $pathFrom->getRef()
                ));
            }
            if (!file_exists($pathFrom->getFullPath())) {
                throw new \Exception(sprintf('Path %s not found', $pathFrom->getFullPath()));
            }

            if (!is_file($pathFrom->getFullPath())) {
                throw new \Exception(sprintf('Path %s is not a file', $pathFrom->getFullPath()));
            }

            $pathTo = self::$pathFactory->parsePath($path_to);
            $pathTo = $pathTo['path'];

            if (strpos($pathTo, $pathFrom->getRepositoryPath()) !== 0) {
                throw new \Exception(sprintf('Cannot rename across repositories [%s -> %s]',
                    $pathFrom->getFullPath(), $pathTo));
            }

            $repo   = $pathFrom->getRepository();

            $commitMsg      = $this->getContextOption('commitMsg', null);
            $author         = $this->getContextOption('author', null);

            $repo->renameFile($pathFrom->getLocalPath(), $pathTo, $commitMsg, false, $author);
            return true;
        } catch (\Exception $e) {
            trigger_error($e->getMessage(), E_USER_WARNING);
            return false;
        }
    }

    /**
     * streamWrapper::rmdir — Removes a directory
     *
     * @param   string   $path      The directory URL which should be removed.
     * @param   integer  $options   A bitwise mask of values, such as STREAM_MKDIR_RECURSIVE.
     * @return  boolean             Returns TRUE on success or FALSE on failure.
     */
    public function rmdir($path, $options)
    {
        try {
            $path   = $this->getPath($path);
            if ($path->getRef() != 'HEAD') {
                throw new \Exception(sprintf(
                    'Cannot remove a non-HEAD directory [%s#%s]', $path->getFullPath(), $path->getRef()
                ));
            }
            if (!file_exists($path->getFullPath())) {
                throw new \Exception(sprintf('Path %s not found', $path->getFullPath()));
            }
            if (!is_dir($path->getFullPath())) {
                throw new \Exception(sprintf('Path %s is not a directory', $path->getFullPath()));
            }

            $options    |= STREAM_MKDIR_RECURSIVE;
            $recursive  = self::maskHasFlag($options, STREAM_MKDIR_RECURSIVE);

            $repo   = $path->getRepository();

            $commitMsg      = $this->getContextOption('commitMsg', null);
            $author         = $this->getContextOption('author', null);

            $repo->removeFile($path->getLocalPath(), $commitMsg, $recursive, false, $author);
            return true;
        } catch (\Exception $e) {
            trigger_error($e->getMessage(), E_USER_WARNING);
            return false;
        }
    }

    /**
     * streamWrapper::unlink — Delete a file
     *
     * @param   string   $path  The file URL which should be deleted.
     * @return  boolean         Returns TRUE on success or FALSE on failure.
     */
    public function unlink($path)
    {
        try {
            $path   = $this->getPath($path);
            if ($path->getRef() != 'HEAD') {
                throw new \Exception(sprintf(
                    'Cannot unlink a non-HEAD file [%s#%s]', $path->getFullPath(), $path->getRef()
                ));
            }
            if (!file_exists($path->getFullPath())) {
                throw new \Exception(sprintf('Path %s not found', $path->getFullPath()));
            }
            if (!is_file($path->getFullPath())) {
                throw new \Exception(sprintf('Path %s is not a file', $path->getFullPath()));
            }

            $repo   = $path->getRepository();

            $commitMsg      = $this->getContextOption('commitMsg', null);
            $author         = $this->getContextOption('author', null);

            $repo->removeFile($path->getLocalPath(), $commitMsg, false, false, $author);
            return true;
        } catch (\Exception $e) {
            trigger_error($e->getMessage(), E_USER_WARNING);
            return false;
        }
    }
}