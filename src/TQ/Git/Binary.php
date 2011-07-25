<?php
namespace TQ\Git;

use TQ\Git\Cli;

/**
 * @method  Cli\Call  status(string $path, mixed $args...)
 * @method  Cli\Call  init(string $path, mixed $args...)
 * @method  Cli\Call  add(string $path, mixed $args...)
 * @method  Cli\Call  commit(string $path, mixed $args...)
 */
class Binary
{
    /**
     *
     * @var string
     */
    protected $path;

    /**
     *
     * @return  string
     */
    public static function locateBinary()
    {
        if (PHP_OS != 'Windows') {
            $result = Cli\Call::create('which git')->execute();
            return $result->getStdOut();
        }
        return '';
    }

    /**
     *
     * @param   string|null $path
     */
    public function __construct($path = null)
    {
        if (!$path) {
            $path  = self::locateBinary();
        }
        if (!is_string($path) || empty($path)) {
            throw new \InvalidArgumentException('No path to the Git binary found');
        }
        $this->path    = $path;
    }

    /**
     *
     * @param   string  $path
     * @param   string  $command
     * @param   array   $arguments
     * @return  Cli\Call
     */
    protected function createGitCall($path, $command, array $arguments)
    {
        $cmd        = escapeshellcmd($this->path);
        $command    = escapeshellarg($command);
        $args       = array();
        array_walk($arguments, function($v, $k) use(&$args) {
            if (is_int($k)) {
                $args[] = escapeshellarg($v);
            } else {
                $k  = ltrim($k, '-');
                $k  = escapeshellarg($k);
                if (strlen($k) == 1) {
                    if ($v === null) {
                        $args[] = sprintf('-%s', $k);
                    } else {
                        $args[] = sprintf('-%s %s', $k, escapeshellarg($v));
                    }
                } else {
                    if ($v === null) {
                        $args[] = sprintf('--%s', $k);
                    } else {
                        $args[] = sprintf('--%s=%s', $k, escapeshellarg($v));
                    }
                }
            }
        });

        $call   = Cli\Call::create(sprintf('%s %s %s', $cmd, $command, implode(' ', $args)), $path);

        return $call;
    }

    /**
     *
     * @param   string  $method
     * @param   array   $arguments
     * @return  Cli\CallResult
     */
    public function __call($method, array $arguments)
    {
        if (count($arguments) < 1) {
            throw new \InvalidArgumentException(sprintf(
                '"%s" must be called with at least one argument denoting the path', $method
            ));
        }
        $path   = array_shift($arguments);

        if (count($arguments) >= 1) {
            $args   = array_shift($arguments);
            if (!is_array($args)) {
                $args   = array($args);
            }
        } else {
            $args   = array();
        }

        $call   = $this->createGitCall($path, $method, $args);
        return $call->execute();
    }
}

