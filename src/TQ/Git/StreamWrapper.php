<?php
namespace TQ\Git;

class StreamWrapper
{
    /**
     *
     * @var Connector
     */
    protected static $connector;

    /**
     *
     * @param   string              $protocol
     * @param   Connector|string    $connector
     */
    public static function register($protocol, $connector = null)
    {
        if ($connector === null || is_string($connector)) {
            $connector  = new Connector($connector);
        }
        if (!($connector instanceof Connector)) {
            throw new \InvalidArgumentException(sprintf('The $connector argument must either
                be a TQ\Git\Connector instance or a path to the Git binary (%s given)',
                (is_object($connector)) ? get_class($connector) : gettype($connector)
            ));
        }

        self::$connector    = $connector;
        if (!stream_register_wrapper($protocol, __CLASS__)) {
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
     * @return  Connector
     */
    protected function getConnector()
    {
        return self::$connector;
    }
}

