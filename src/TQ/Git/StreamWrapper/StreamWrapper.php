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
use TQ\Git\StreamWrapper\FileBuffer\FileBuffer;
use TQ\Git\StreamWrapper\FileBuffer\Factory\Resolver;
use TQ\Git\StreamWrapper\FileBuffer\Factory\CommitFactory;
use TQ\Git\StreamWrapper\FileBuffer\Factory\DefaultFactory;
use TQ\Git\StreamWrapper\FileBuffer\Factory\HeadFileFactory;
use TQ\Git\StreamWrapper\FileBuffer\Factory\LogFactory;

/**
 * The streamwrapper that hooks into PHP's stream infrastructure
 *
 * @uses       TQ\Git\Cli\Binary
 * @uses       TQ\Git\Repository\Repository;
 * @uses       TQ\Git\StreamWrapper\PathInformation
 * @uses       TQ\Git\StreamWrapper\DirectoryBuffer
 * @uses       TQ\Git\StreamWrapper\FileBuffer\Factory\Resolver
 * @uses       TQ\Git\StreamWrapper\FileBuffer\Factory\CommitFactory
 * @uses       TQ\Git\StreamWrapper\FileBuffer\Factory\DefaultFactory
 * @uses       TQ\Git\StreamWrapper\FileBuffer\Factory\HeadFileFactory
 * @uses       TQ\Git\StreamWrapper\FileBuffer\Factory\LogFactory
 * @author     Stefan Gehrig <gehrigteqneers.de>
 * @category   TQ
 * @package    TQ_Git
 * @subpackage StreamWrapper
 * @copyright  Copyright (C) 2011 by TEQneers GmbH & Co. KG
 */
class StreamWrapper
{
    /**
     * The Git binary
     *
     * @var Binary
     */
    protected static $binary;

    /**
     * The registered protocol
     *
     * @var string
     */
    protected static $protocol;

    /**
     * The stream context if set
     *
     * @var resource
     */
    public $context;

    /**
     * The parsed stream context options
     *
     * @var array
     */
    protected $contextOptions;

    /**
     * The parsed stream context parameters
     *
     * @var array
     */
    protected $contextParameters;

    /**
     * The directory buffer if used on a directory
     *
     * @var DirectoryBuffer
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
     * Registers the stream wrapper with the given protocol
     *
     * @param   string           $protocol      The protocol (auch as "git")
     * @param   Binary|string    $binary        The Git binary
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
        if (!stream_wrapper_register($protocol, get_class(new static()))) {
            throw new \RuntimeException(sprintf('The protocol "%s" is already registered with the
                runtime or it cannot be registered', $protocol));
        }
        self::$protocol = $protocol;
    }

    /**
     * Unregisters the stream wrapper
     */
    public static function unregister()
    {
        if (!stream_wrapper_unregister(self::$protocol)) {
            throw new \RuntimeException(sprintf('The protocol "%s" cannot be unregistered
                from the runtime', self::$protocol));
        }
    }

    /**
     * Creates a new instance of the stream wrapper
     */
    public function __construct()
    {
    }

    /**
     * Parses the passed stream context and returns the context options
     * relevant for this stream wrapper
     *
     * @param   boolean $all    Return all options instead of just the relevant options
     * @return  array           The context options
     */
    protected function getContextOptions($all = false)
    {
        if ($this->contextOptions === null) {
            $this->contextOptions   = stream_context_get_options($this->context);
        }

        if (!$all && array_key_exists(self::$protocol, $this->contextOptions)) {
            return $this->contextOptions[self::$protocol];
        } else if ($all) {
            return $this->contextOptions;
        } else {
            return array();
        }
    }

    /**
     * Returns a context option - $default if option is not found
     *
     * @param   string  $option     The option to retrieve
     * @param   mixed   $default    The default value if $option is not found
     */
    protected function getContextOption($option, $default = null)
    {
        $options    = $this->getContextOptions();
        if (array_key_exists($option, $options)) {
            return $options[$option];
        } else {
            return $default;
        }
    }

    /**
     * Parses the passed stream context and returns the context parameters
     *
     * @return  array       The context parameters
     */
    protected function getContextParameters()
    {
        if ($this->contextParameters === null) {
            $this->contextParameters    = stream_context_get_params($this->context);
        }
        return $this->contextParameters;
    }

    /**
     * Returns a context parameter - $default if parameter is not found
     *
     * @param   string  $parameter  The parameter to retrieve
     * @param   mixed   $default    The default value if $parameter is not found
     */
    protected function getContextParameter($parameter, $default = null)
    {
        $parameters    = $this->getContextParameters();
        if (array_key_exists($parameter, $parameters)) {
            return $parameters[$parameter];
        } else {
            return $default;
        }
    }

    /**
     * Returns the path information for a given stream URL
     *
     * @param   string  $streamUrl      The URL given to the stream function
     * @return  PathInformation         The path information representing the stream URL
     */
    protected function getPath($streamUrl)
    {
        return new PathInformation($streamUrl, self::$protocol, self::$binary);
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
            $this->dirBuffer    = new DirectoryBuffer($listing);
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

            $pathTo = PathInformation::parseUrl($path_to, self::$protocol);
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
     * streamWrapper::stream_cast — Retrieve the underlaying resource
     *
     * @param   integer  $cast_as   Can be STREAM_CAST_FOR_SELECT when stream_select() is calling stream_cast()
     *                              or STREAM_CAST_AS_STREAM when stream_cast() is called for other uses.
     * @return  resource            Should return the underlying stream resource used by the wrapper, or FALSE.
     */
/*
    public function stream_cast($cast_as)
    {
    }
*/

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
     * streamWrapper::stream_lock — Advisory file locking
     *
     * @param   integer  $operation     operation is one of the following:
     *                                      LOCK_SH to acquire a shared lock (reader).
     *                                      LOCK_EX to acquire an exclusive lock (writer).
     *                                      LOCK_UN to release a lock (shared or exclusive).
     *                                      LOCK_NB if you don't want flock() to block while locking. (not supported on Windows)
     * @return  boolean                 Returns TRUE on success or FALSE on failure.
     */
/*
    public function stream_lock($operation)
    {
    }
*/

    /**
     * streamWrapper::stream_metadata — Change stream options
     *
     * @param   string   $path      The file path or URL to set metadata. Note that in the case of a URL,
     *                              it must be a :// delimited URL. Other URL forms are not supported.
     * @param   integer  $option    One of:
     *                                  PHP_STREAM_META_TOUCH (The method was called in response to touch())
     *                                  PHP_STREAM_META_OWNER_NAME (The method was called in response to chown() with string parameter)
     *                                  PHP_STREAM_META_OWNER (The method was called in response to chown())
     *                                  PHP_STREAM_META_GROUP_NAME (The method was called in response to chgrp())
     *                                  PHP_STREAM_META_GROUP (The method was called in response to chgrp())
     *                                  PHP_STREAM_META_ACCESS (The method was called in response to chmod())
     * @param   integer  $var       If option is
     *                                  PHP_STREAM_META_TOUCH: Array consisting of two arguments of the touch() function.
     *                                  PHP_STREAM_META_OWNER_NAME or PHP_STREAM_META_GROUP_NAME: The name of the owner
     *                                      user/group as string.
     *                                  PHP_STREAM_META_OWNER or PHP_STREAM_META_GROUP: The value owner user/group argument as integer.
     *                                  PHP_STREAM_META_ACCESS: The argument of the chmod() as integer.
     * @return  boolean             Returns TRUE on success or FALSE on failure. If option is not implemented, FALSE should be returned.
     */
/*
    public function stream_metadata($path, $option, $var)
    {
    }
*/

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

            $resolver           = $this->createBufferFactoryResolver();
            $factory            = $resolver->findFactory($path, $mode);
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
     * Creates the factory resolver
     *
     * @return  Resolver
     */
    protected function createBufferFactoryResolver()
    {
        $resolver    = new Resolver();
        $resolver->addFactory(new CommitFactory(), 100)
                 ->addFactory(new LogFactory(), 90)
                 ->addFactory(new HeadFileFactory(), 80)
                 ->addFactory(new DefaultFactory(), -100);
        return $resolver;
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
     * streamWrapper::stream_set_option
     *
     * @param   integer  $option    One of:
     *                                  STREAM_OPTION_BLOCKING (The method was called in response to stream_set_blocking())
     *                                  STREAM_OPTION_READ_TIMEOUT (The method was called in response to stream_set_timeout())
     *                                  STREAM_OPTION_WRITE_BUFFER (The method was called in response to stream_set_write_buffer())
     * @param   integer  $arg1      If option is
     *                                  STREAM_OPTION_BLOCKING: requested blocking mode (1 meaning block 0 not blocking).
     *                                  STREAM_OPTION_READ_TIMEOUT: the timeout in seconds.
     *                                  STREAM_OPTION_WRITE_BUFFER: buffer mode (STREAM_BUFFER_NONE or STREAM_BUFFER_FULL).
     * @param   integer  $arg2      If option is
     *                                  STREAM_OPTION_BLOCKING: This option is not set.
     *                                  STREAM_OPTION_READ_TIMEOUT: the timeout in microseconds.
     *                                  STREAM_OPTION_WRITE_BUFFER: the requested buffer size.
     * @return  boolean             Returns TRUE on success or FALSE on failure. If option is not implemented,
     *                              FALSE should be returned.
     */
/*
    public function stream_set_option($option, $arg1, $arg2)
    {
    }
*/

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

    /**
     * Checks if a bitmask has a specific flag set
     *
     * @param   integer     $mask   The bitmask
     * @param   integer     $flag   The flag to check
     * @return  boolean
     */
    protected static function maskHasFlag($mask, $flag)
    {
        $flag   = (int)$flag;
        return ((int)$mask & $flag) === $flag;
    }
}