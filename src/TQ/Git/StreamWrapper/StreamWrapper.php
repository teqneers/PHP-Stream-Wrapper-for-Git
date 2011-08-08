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
use TQ\Git\Cli\Binary;
use TQ\Git\Repository\Repository;

/**
 * The streamwrapper that hooks into PHP's stream infrastructure
 *
 * @uses       TQ\Git\Cli\Binary
 * @uses       TQ\Git\Repository\Repository;
 * @uses       TQ\Git\StreamWrapper\PathInformation
 * @uses       TQ\Git\StreamWrapper\DirectoryBuffer
 * @uses       TQ\Git\StreamWrapper\FileBuffer
 * @author     Stefan Gehrig <gehrigteqneers.de>
 * @category   TQ
 * @package    TQ_Git
 * @subpackage StreamWrapper
 * @copyright  Copyright (C) 2011 by TEQneers GmbH & Co. KG
 */
class StreamWrapper
{
    /**
     *
     * @var Binary
     */
    protected static $binary;

    /**
     *
     * @var string
     */
    protected static $protocol;

    /**
     *
     * @var resource
     */
    public $context;

    /**
     *
     * @var DirectoryBuffer
     */
    protected $dirBuffer;

    /**
     *
     * @var FileBuffer
     */
    protected $fileBuffer;

    /**
     *
     * @param   string              $protocol
     * @param   Binary|string    $binary
     */
    public static function register($protocol, $binary = null)
    {
        if ($binary === null || is_string($binary)) {
            $binary  = new Binary($binary);
        }
        if (!($binary instanceof Binary)) {
            throw new \InvalidArgumentException(sprintf('The $binary argument must either
                be a TQ\Git\Binary instance or a path to the Git binary (%s given)',
                (is_object($binary)) ? get_class($binary) : gettype($binary)
            ));
        }

        self::$binary    = $binary;
        if (!stream_wrapper_register($protocol, __CLASS__)) {
            throw new \RuntimeException(sprintf('The protocol "%s" is already registered with the
                runtime or it cannot be registered', $protocol));
        }
        self::$protocol = $protocol;
    }

    /**
     *
     */
    public static function unregister()
    {
        if (!stream_wrapper_unregister(self::$protocol)) {
            throw new \RuntimeException(sprintf('The protocol "%s" cannot be unregistered
                from the runtime', self::$protocol));
        }
    }

    /**
     *
     */
    public function __construct()
    {
    }

    /**
     *
     * @param   string  $streamUrl
     * @return  PathInformation
     */
    protected function getPath($streamUrl)
    {
        $path   = ltrim(substr($streamUrl, strlen(self::$protocol) + 3), DIRECTORY_SEPARATOR.'/');
        $url    = parse_url(self::$protocol.'://'.$path);
        return new PathInformation(self::$binary, $url);
    }

    /**
     *
     * @return  boolean
     */
    public function dir_closedir()
    {
        $this->dirBuffer    = null;
        return true;
    }

    /**
     *
     * @param   string   $path
     * @param   integer  $options
     * @return  boolean
     */
    public function dir_opendir($path, $options)
    {
        try {
            $path               = $this->getPath($path);
            $repo               = $path->getRepository();
            $listing            = $repo->listDirectory($path->getLocalPath(), $path->getRef());
            $this->dirBuffer    = new DirectoryBuffer($listing);
            return true;
        } catch (Exception $e) {
            trigger_error($e->getMessage(), E_USER_WARNING);
            return false;
        }
    }

    /**
     *
     * @return  string|false
     */
    public function dir_readdir()
    {
        $file   = $this->dirBuffer->current();
        $this->dirBuffer->next();
        return $file;
    }

    /**
     *
     * @return  boolean
     */
    public function dir_rewinddir()
    {
        $this->dirBuffer->rewind();
        return true;
    }

    /**
     *
     * @param   string   $path
     * @param   integer  $mode
     * @param   integer  $options
     * @return  boolean
     */
    public function mkdir($path, $mode, $options)
    {
    }

    /**
     *
     * @param   string   $path_from
     * @param   string   $path_to
     * @return  boolean
     */
    public function rename($path_from, $path_to)
    {
    }

    /**
     *
     * @param   string   $path
     * @param   integer  $options
     * @return  boolean
     */
    public function rmdir($path, $options)
    {
    }

    /**
     *
     * @param   integer  $cast_as
     * @return  resource
     */
/*
    public function stream_cast($cast_as)
    {
    }
*/

    /**
     *
     */
    public function stream_close()
    {
        $this->fileBuffer   = null;
    }

    /**
     *
     * @return  boolean
     */
    public function stream_eof()
    {
        return $this->fileBuffer->isEof();
    }

    /**
     *
     * @return  boolean
     */
/*
    public function stream_flush()
    {
    }
*/

    /**
     *
     * @param   integer  $operation
     * @return  boolean
     */
/*
    public function stream_lock($operation)
    {
    }
*/

    /**
     *
     * @param   string   $path
     * @param   integer  $option
     * @param   integer  $var
     * @return  boolean
     */
/*
    public function stream_metadata($path, $option, $var)
    {
    }
*/

    /**
     *
     * @param   string   $path
     * @param   string   $mode
     * @param   integer  $options
     * @param   string   $opened_path
     * @return  boolean
     */
    public function stream_open($path, $mode, $options, &$opened_path)
    {
        try {
            $path   = $this->getPath($path);
            $repo   = $path->getRepository();

            if ($path->hasArgument('ref')) {
                $buffer = $repo->showCommit($path->getArgument('ref'));
            } else if ($path->hasArgument('log')) {
                $buffer = implode(
                    str_repeat(PHP_EOL, 3),
                    $repo->getLog(
                        $path->getArgument('limit'),
                        $path->getArgument('skip')
                    )
                );
            } else {
                $buffer = $repo->showFile($path->getLocalPath(), $path->getRef());
            }
            $this->fileBuffer   = new FileBuffer($buffer);
            return true;
        } catch (Exception $e) {
            trigger_error($e->getMessage(), E_USER_WARNING);
            return false;
        }
    }

    /**
     *
     * @param   integer  $count
     * @return  string
     */
    public function stream_read($count)
    {
        $buffer = $this->fileBuffer->read($count);
        if ($buffer === null) {
            return false;
        }
        return $buffer;
    }

    /**
     *
     * @param   integer  $offset
     * @param   integer  $whence
     * @return  boolean
     */
    public function stream_seek($offset, $whence = SEEK_SET)
    {
        return $this->fileBuffer->setPosition($offset, $whence);
    }

    /**
     *
     * @param   integer  $option
     * @param   integer  $arg1
     * @param   integer  $arg2
     * @return  boolean
     */
/*
    public function stream_set_option($option, $arg1, $arg2)
    {
    }
*/

    /**
     *
     * @return  array
     */
    public function stream_stat()
    {
    }

    /**
     *
     * @return  integer
     */
    public function stream_tell()
    {
        return $this->fileBuffer->getPosition();
    }

    /**
     *
     * @param   string  $data
     * @return  integer
     */
    public function stream_write($data)
    {
    }

    /**
     *
     * @param   string   $path
     * @return  boolean
     */
    public function unlink($path)
    {
    }

    /**
     *
     * @param   string  $path
     * @param   integer $flags
     * @return  array
     */
/*
    public function url_stat($path, $flags)
    {
    }
*/
}

