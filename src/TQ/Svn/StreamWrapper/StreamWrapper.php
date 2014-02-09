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
 * @subpackage SVN
 * @copyright  Copyright (C) 2014 by TEQneers GmbH & Co. KG
 */

namespace TQ\Svn\StreamWrapper;
use TQ\Svn\Cli\Binary;
use TQ\Svn\StreamWrapper\FileBuffer\Factory;
use TQ\Vcs\StreamWrapper\AbstractStreamWrapper;
use TQ\Vcs\StreamWrapper\PathFactoryInterface;

/**
 * The stream wrapper that hooks into PHP's stream infrastructure
 *
 * @author     Stefan Gehrig <gehrigteqneers.de>
 * @category   TQ
 * @package    TQ_VCS
 * @subpackage SVN
 * @copyright  Copyright (C) 2014 by TEQneers GmbH & Co. KG
 */
class StreamWrapper extends AbstractStreamWrapper
{
    /**
     * Registers the stream wrapper with the given protocol
     *
     * @param   string                                   $protocol    The protocol (such as "svn")
     * @param   Binary|string|null|PathFactoryInterface  $binary      The SVN binary or a path factory
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
        return false;
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
        return false;
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
        return false;
    }

    /**
     * streamWrapper::unlink — Delete a file
     *
     * @param   string   $path  The file URL which should be deleted.
     * @return  boolean         Returns TRUE on success or FALSE on failure.
     */
    public function unlink($path)
    {
        return false;
    }
}