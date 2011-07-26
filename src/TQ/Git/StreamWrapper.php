<?php
namespace TQ\Git;

class StreamWrapper
{
    /**
     *
     * @var Binary
     */
    protected static $binary;

    /**
     *
     * @var resource
     */
    public $context;

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
    }

    /**
     *
     */
    public function __construct()
    {
    }

    /**
     *
     * @return  Binary
     */
    protected function getBinary()
    {
        return self::$binary;
    }

    /**
     *
     * @return  boolean
     */
    public function dir_closedir()
    {
    }

    /**
     *
     * @param   string   $path
     * @param   integer  $options
     * @return  boolean
     */
    public function dir_opendir($path, $options)
    {
    }

    /**
     *
     * @return  string
     */
    public function dir_readdir()
    {
    }

    /**
     *
     * @return  boolean
     */
    public function dir_rewinddir()
    {
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

