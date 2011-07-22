<?php
namespace TQ\Git;

class StreamWrapper
{
    /**
     *
     * @var GitBinary
     */
    protected static $gitBinary;

    /**
     *
     * @param   string              $protocol
     * @param   GitBinary|string    $gitBinary
     */
    public static function register($protocol, $gitBinary = null)
    {
        if ($gitBinary === null || is_string($gitBinary)) {
            $gitBinary  = new GitBinary($gitBinary);
        }
        if (!($gitBinary instanceof GitBinary)) {
            throw new \InvalidArgumentException(sprintf('The $gitBinary argument must either
                be a TQ\Git\GitBinary instance or a path to the Git binary (%s given)',
                (is_object($gitBinary)) ? get_class($gitBinary) : gettype($gitBinary)
            ));
        }

        self::$gitBinary    = $gitBinary;
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
     * @return  GitBinary
     */
    protected function getGitBinary()
    {
        return self::$gitBinary;
    }
}

