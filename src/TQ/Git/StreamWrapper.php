<?php
namespace TQ\Git;

class StreamWrapper
{
    /**
     *
     * @var Binary
     */
    protected static $Binary;

    /**
     *
     * @param   string              $protocol
     * @param   Binary|string    $Binary
     */
    public static function register($protocol, $Binary = null)
    {
        if ($Binary === null || is_string($Binary)) {
            $Binary  = new Binary($Binary);
        }
        if (!($Binary instanceof Binary)) {
            throw new \InvalidArgumentException(sprintf('The $Binary argument must either
                be a TQ\Git\Binary instance or a path to the Git binary (%s given)',
                (is_object($Binary)) ? get_class($Binary) : gettype($Binary)
            ));
        }

        self::$Binary    = $Binary;
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
        return self::$Binary;
    }
}

