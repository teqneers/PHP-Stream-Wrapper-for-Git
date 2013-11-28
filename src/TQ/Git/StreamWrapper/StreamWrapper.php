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
use TQ\Git\Repository\RepositoryRegistry;
use TQ\Vcs\Buffer\FileBuffer;
use TQ\Vcs\Buffer\ArrayBuffer;
use TQ\Git\StreamWrapper\FileBuffer\Factory;
use TQ\Vcs\StreamWrapper\AbstractStreamWrapper;

/**
 * The stream wrapper that hooks into PHP's stream infrastructure
 *
 * @author     Stefan Gehrig <gehrigteqneers.de>
 * @category   TQ
 * @package    TQ_Git
 * @subpackage StreamWrapper
 * @copyright  Copyright (C) 2011 by TEQneers GmbH & Co. KG
 */
class StreamWrapper extends AbstractStreamWrapper
{
    /**
     * The path factory
     *
     * @var PathFactory
     */
    protected static $pathFactory;

    /**
     * The directory buffer if used on a directory
     *
     * @var ArrayBuffer
     */
    protected $dirBuffer;

    /**
     * The file buffer if used on a file
     *
     * @var FileBuffer
     */
    protected $fileBuffer;

    /**
     * The opened path
     *
     * @var PathInformation
     */
    protected $path;

    /**
     * The buffer factory
     *
     * @var Factory
     */
    protected $bufferFactory;

    /**
     * Registers the stream wrapper with the given protocol
     *
     * @param   string                          $protocol    The protocol (such as "git")
     * @param   Binary|string|null|PathFactory  $binary      The Git binary or a path factory
     * @throws  \RuntimeException                            If $protocol is already registered
     */
    public static function register($protocol, $binary = null)
    {
        static::$protocol = $protocol;
        if ($binary instanceof PathFactory) {
            static::$pathFactory  = $binary;
        } else {
            $binary              = Binary::ensure($binary);
            static::$pathFactory  = new PathFactory(static::$protocol, $binary, null);
        }

        if (!stream_wrapper_register($protocol, get_called_class())) {
            throw new \RuntimeException(sprintf('The protocol "%s" is already registered with the
                runtime or it cannot be registered', $protocol));
        }
    }

    /**
     * Unregisters the stream wrapper
     */
    public static function unregister()
    {
        if (!stream_wrapper_unregister(static::$protocol)) {
            throw new \RuntimeException(sprintf('The protocol "%s" cannot be unregistered
                from the runtime', static::$protocol));
        }
    }

    /**
     * Returns the repository registry
     *
     * @return  RepositoryRegistry
     */
    public static function getRepositoryMap()
    {
        return static::$pathFactory->getRegistry();
    }

    /**
     * Returns the path information for a given stream URL
     *
     * @param   string  $streamUrl      The URL given to the stream function
     * @return  PathInformation         The path information representing the stream URL
     */
    protected function getPath($streamUrl)
    {
        return self::$pathFactory->createPathInformation($streamUrl);
    }

    /**
     * Creates the buffer factory
     *
     * @return  Factory
     */
    protected function getBufferFactory()
    {
        if ($this->bufferFactory === null) {
            $this->bufferFactory   = Factory::getDefault();
        }
        return $this->bufferFactory;
    }

    /**
     * streamWrapper::dir_closedir — Close directory handle
     *
     * @return  boolean     Returns TRUE on success or FALSE on failure.
     */
    public function dir_closedir()
    {
        $this->dirBuffer    = null;
        $this->path         = null;
        return true;
    }

    /**
     * streamWrapper::dir_opendir — Open directory handle
     *
     * @param   string   $path      Specifies the URL that was passed to {@see opendir()}.
     * @param   integer  $options   Whether or not to enforce safe_mode (0x04).
     * @return  boolean             Returns TRUE on success or FALSE on failure.
     */
    public function dir_opendir($path, $options)
    {
        try {
            $path               = $this->getPath($path);
            $repo               = $path->getRepository();
            $listing            = $repo->listDirectory($path->getLocalPath(), $path->getRef());
            $this->dirBuffer    = new ArrayBuffer($listing);
            $this->path         = $path;
            return true;
        } catch (\Exception $e) {
            trigger_error($e->getMessage(), E_USER_WARNING);
            return false;
        }
    }

    /**
     * streamWrapper::dir_readdir — Read entry from directory handle
     *
     * @return  string|false    Should return string representing the next filename, or FALSE if there is no next file.
     */
    public function dir_readdir()
    {
        $file   = $this->dirBuffer->current();
        $this->dirBuffer->next();
        return $file;
    }

    /**
     * streamWrapper::dir_rewinddir — Rewind directory handle
     *
     * @return  boolean     Returns TRUE on success or FALSE on failure.
     */
    public function dir_rewinddir()
    {
        $this->dirBuffer->rewind();
        return true;
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

            $pathTo = PathInformation::parsePath($path_to, self::$protocol);
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
     * streamWrapper::stream_close — Close an resource
     */
    public function stream_close()
    {
        $this->fileBuffer->close();
        $this->fileBuffer   = null;

        $repo   = $this->path->getRepository();
        $repo->add(array($this->path->getFullPath()));
        if ($repo->isDirty()) {
            $commitMsg      = $this->getContextOption('commitMsg', null);
            $author         = $this->getContextOption('author', null);
            $repo->commit($commitMsg, array($this->path->getFullPath()), $author);
        }

        $this->path         = null;
    }

    /**
     * streamWrapper::stream_eof — Tests for end-of-file on a file pointer
     *
     * @return  boolean     Should return TRUE if the read/write position is at the end of the stream
     *                      and if no more data is available to be read, or FALSE otherwise.
     */
    public function stream_eof()
    {
        return $this->fileBuffer->isEof();
    }

    /**
     * streamWrapper::stream_flush — Flushes the output
     *
     * @return  boolean     Should return TRUE if the cached data was successfully stored
     *                      (or if there was no data to store), or FALSE if the data could not be stored.
     */
    public function stream_flush()
    {
        return $this->fileBuffer->flush();
    }

    /**
     * streamWrapper::stream_open — Opens file or URL
     *
     * @param   string   $path          Specifies the URL that was passed to the original function.
     * @param   string   $mode          The mode used to open the file, as detailed for fopen().
     * @param   integer  $options       Holds additional flags set by the streams API. It can hold one or more of
     *                                      the following values OR'd together.
     *                                      STREAM_USE_PATH         If path is relative, search for the resource using
     *                                                              the include_path.
     *                                      STREAM_REPORT_ERRORS    If this flag is set, you are responsible for raising
     *                                                              errors using trigger_error() during opening of the
     *                                                              stream. If this flag is not set, you should not raise
     *                                                              any errors.
     * @param   string   $opened_path   If the path is opened successfully, and STREAM_USE_PATH is set in options, opened_path
     *                                  should be set to the full path of the file/resource that was actually opened.
     * @return  boolean                 Returns TRUE on success or FALSE on failure.
     */
    public function stream_open($path, $mode, $options, &$opened_path)
    {
        try {
            $path   = $this->getPath($path);

            $factory            = $this->getBufferFactory();
            $this->fileBuffer   = $factory->createFileBuffer($path, $mode);
            $this->path         = $path;

            if (self::maskHasFlag($options, STREAM_USE_PATH)) {
                $opened_path    = $this->path->getUrl();
            }

            return true;
        } catch (\Exception $e) {
            if (self::maskHasFlag($options, STREAM_REPORT_ERRORS)) {
                trigger_error($e->getMessage(), E_USER_WARNING);
            }
            return false;
        }
    }

    /**
     * streamWrapper::stream_read — Read from stream
     *
     * @param   integer  $count     How many bytes of data from the current position should be returned.
     * @return  string              If there are less than count bytes available, return as many as are available.
     *                              If no more data is available, return either FALSE or an empty string.
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
     * streamWrapper::stream_seek — Seeks to specific location in a stream
     *
     * @param   integer  $offset    The stream offset to seek to.
     * @param   integer  $whence    Possible values:
     *                                  SEEK_SET - Set position equal to offset bytes.
     *                                  SEEK_CUR - Set position to current location plus offset.
     *                                  SEEK_END - Set position to end-of-file plus offset.
     * @return  boolean             Return TRUE if the position was updated, FALSE otherwise.
     */
    public function stream_seek($offset, $whence = SEEK_SET)
    {
        return $this->fileBuffer->setPosition($offset, $whence);
    }

    /**
     * streamWrapper::stream_stat — Retrieve information about a file resource
     *
     * @return  array       stat() and fstat() result format
     *                      Numeric     Associative (since PHP 4.0.6)   Description
     *                      0           dev                             device number
     *                      1           ino                             inode number *
     *                      2           mode                            inode protection mode
     *                      3           nlink                           number of links
     *                      4           uid                             userid of owner *
     *                      5           gid                             groupid of owner *
     *                      6           rdev                            device type, if inode device
     *                      7           size                            size in bytes
     *                      8           atime                           time of last access (Unix timestamp)
     *                      9           mtime                           time of last modification (Unix timestamp)
     *                      10          ctime                           time of last inode change (Unix timestamp)
     *                      11          blksize                         blocksize of filesystem IO **
     *                      12          blocks                          number of 512-byte blocks allocated **
     *                      * On Windows this will always be 0.
     *                      ** Only valid on systems supporting the st_blksize type - other systems (e.g. Windows) return -1.
     */
    public function stream_stat()
    {
        return $this->fileBuffer->getStat();
    }

    /**
     * streamWrapper::stream_tell — Retrieve the current position of a stream
     *
     * @return  integer     Should return the current position of the stream.
     */
    public function stream_tell()
    {
        return $this->fileBuffer->getPosition();
    }

    /**
     * streamWrapper::stream_write — Write to stream
     *
     * @param   string  $data   Should be stored into the underlying stream.
     * @return  integer         Should return the number of bytes that were successfully stored, or 0 if none could be stored.
     */
    public function stream_write($data)
    {
        return $this->fileBuffer->write($data);
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

    /**
     * streamWrapper::url_stat — Retrieve information about a file
     *
     * mode bit mask:
     * S_IFMT     0170000   bit mask for the file type bit fields
     * S_IFSOCK   0140000   socket
     * S_IFLNK    0120000   symbolic link
     * S_IFREG    0100000   regular file
     * S_IFBLK    0060000   block device
     * S_IFDIR    0040000   directory
     * S_IFCHR    0020000   character device
     * S_IFIFO    0010000   FIFO
     * S_ISUID    0004000   set UID bit
     * S_ISGID    0002000   set-group-ID bit (see below)
     * S_ISVTX    0001000   sticky bit (see below)
     * S_IRWXU    00700     mask for file owner permissions
     * S_IRUSR    00400     owner has read permission
     * S_IWUSR    00200     owner has write permission
     * S_IXUSR    00100     owner has execute permission
     * S_IRWXG    00070     mask for group permissions
     * S_IRGRP    00040     group has read permission
     * S_IWGRP    00020     group has write permission
     * S_IXGRP    00010     group has execute permission
     * S_IRWXO    00007     mask for permissions for others (not in group)
     * S_IROTH    00004     others have read permission
     * S_IWOTH    00002     others have write permission
     * S_IXOTH    00001     others have execute permission
     *
     * @param   string  $path   The file path or URL to stat. Note that in the case of a URL, it must be a :// delimited URL.
     *                          Other URL forms are not supported.
     * @param   integer $flags  Holds additional flags set by the streams API. It can hold one or more of the following
     *                          values OR'd together.
     *                              STREAM_URL_STAT_LINK    For resources with the ability to link to other resource (such
     *                                                      as an HTTP Location: forward, or a filesystem symlink). This flag
     *                                                      specified that only information about the link itself should be returned,
     *                                                      not the resource pointed to by the link. This flag is set in response
     *                                                      to calls to lstat(), is_link(), or filetype().
     *                              STREAM_URL_STAT_QUIET   If this flag is set, your wrapper should not raise any errors. If this
     *                                                      flag is not set, you are responsible for reporting errors using the
     *                                                      trigger_error() function during stating of the path.
     * @return  array           Should return as many elements as stat() does. Unknown or unavailable values should be set to a
     *                          rational value (usually 0).
     */
    public function url_stat($path, $flags)
    {
        try {
            $path   = $this->getPath($path);
            if ($path->getRef() == 'HEAD' && file_exists($path->getFullPath())) {
                return stat($path->getFullPath());
            } else {
                $repo   = $path->getRepository();
                $info   = $repo->getObjectInfo($path->getLocalPath(), $path->getRef());

                $stat   = array(
                    'ino'       => 0,
                    'mode'      => $info['mode'],
                    'nlink'     => 0,
                    'uid'       => 0,
                    'gid'       => 0,
                    'rdev'      => 0,
                    'size'      => $info['size'],
                    'atime'     => 0,
                    'mtime'     => 0,
                    'ctime'     => 0,
                    'blksize'   => -1,
                    'blocks'    => -1,
                );
                return array_merge($stat, array_values($stat));
            }
        } catch (\Exception $e) {
            if (!self::maskHasFlag($flags, STREAM_URL_STAT_QUIET)) {
                trigger_error($e->getMessage(), E_USER_WARNING);
            }
            return false;
        }
    }
}