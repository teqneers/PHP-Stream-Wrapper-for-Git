<?php
namespace TQ\Git;

class StreamWrapper
{
    /**
     *
     * @var Repository
     */
    protected static $Repository;

    /**
     *
     * @param   string              $protocol
     * @param   Repository|string    $Repository
     */
    public static function register($protocol, $Repository = null)
    {
        if ($Repository === null || is_string($Repository)) {
            $Repository  = new Repository($Repository);
        }
        if (!($Repository instanceof Repository)) {
            throw new \InvalidArgumentException(sprintf('The $Repository argument must either
                be a TQ\Git\Repository instance or a path to the Git binary (%s given)',
                (is_object($Repository)) ? get_class($Repository) : gettype($Repository)
            ));
        }

        self::$Repository    = $Repository;
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
     * @return  Repository
     */
    protected function getRepository()
    {
        return self::$Repository;
    }
}

