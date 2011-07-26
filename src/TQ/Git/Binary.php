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
        $handleArg  = function($key, $value) {
            $key  = ltrim($key, '-');
            if (strlen($key) == 1) {
                $arg = sprintf('-%s', escapeshellarg($key));
                if ($value !== null) {
                    $arg    .= ' '.escapeshellarg($value);
                }
            } else {
                $arg = sprintf('--%s', escapeshellarg($key));
                if ($value !== null) {
                    $arg    .= '='.escapeshellarg($value);
                }
            }
            return $arg;
        };

        $binary     = escapeshellcmd($this->path);
        $command    = escapeshellarg($command);
        $args       = array();
        $files      = array();
        array_walk($arguments, function($v, $k) use(&$args, &$files, $handleArg) {
            if ($v === '--' || $k === '--') {
                return;
            }
            if (is_int($k)) {
                if (strpos($v, '-') === 0) {
                    $args[]  = $handleArg($v, null);
                } else if (strpos($v, '/') !== false) {
                    $files[] = escapeshellarg($v);
                } else {
                    $args[]  = escapeshellarg($v);
                }
            } else {
                if (strpos($k, '-') === 0) {
                    $args[] = $handleArg($k, $v);
                }
            }
        });

        $cmd    = sprintf('%s %s %s', $binary, $command, implode(' ', $args));
        if (count($files) > 0) {
            $cmd    .= ' -- '.implode(' ', $files);
        }

        $call   = Cli\Call::create($cmd, $path);
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

