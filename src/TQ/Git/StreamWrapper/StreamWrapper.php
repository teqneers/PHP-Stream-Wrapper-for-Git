<?php
namespace TQ\Git\StreamWrapper;

use TQ\Git\Cli\Binary;
use TQ\Git\Repository\Repository;

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
     * @var array
     */
    protected $dirBuffer;

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
            $this->dirBuffer    = $repo->listDirectory($path->getLocalPath(), $path->getRef());
            reset($this->dirBuffer);
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
        $file   = current($this->dirBuffer);
        if ($file) {
            next($this->dirBuffer);
        }
        return $file;
    }

    /**
     *
     * @return  boolean
     */
    public function dir_rewinddir()
    {
        reset($this->dirBuffer);
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
    public function stream_cast($cast_as)
    {
    }

    /**
     *
     */
    public function stream_close()
    {
    }

    /**
     *
     * @return  boolean
     */
    public function stream_eof()
    {
    }

    /**
     *
     * @return  boolean
     */
    public function stream_flush()
    {
    }

    /**
     *
     * @param   integer  $operation
     * @return  boolean
     */
    public function stream_lock($operation)
    {
    }

    /**
     *
     * @param   string   $path
     * @param   integer  $option
     * @param   integer  $var
     * @return  boolean
     */
    public function stream_metadata($path, $option, $var)
    {
    }

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
    }

    /**
     *
     * @param   integer  $count
     * @return  string
     */
    public function stream_read($count)
    {
    }

    /**
     *
     * @param   integer  $offset
     * @param   integer  $whence
     * @return  boolean
     */
    public function stream_seek($offset, $whence = SEEK_SET)
    {
    }

    /**
     *
     * @param   integer  $option
     * @param   integer  $arg1
     * @param   integer  $arg2
     * @return  boolean
     */
    public function stream_set_option($option, $arg1, $arg2)
    {
    }

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
    public function url_stat($path, $flags)
    {
    }
}

