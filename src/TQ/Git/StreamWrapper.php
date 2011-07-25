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
    protected function __construct()
    {
        // StreamWrapper should be instantiatable from userland code
        // use StreamWrapper::register instead
    }

    /**
     *
     * @return  Binary
     */
    protected function getBinary()
    {
        return self::$binary;
    }
}

