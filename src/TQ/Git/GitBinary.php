<?php
namespace TQ\Git;

class GitBinary
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
            $result = SystemCall::create('which git')->execute();
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
     * @param   array   $parameters
     * @return  SystemCall
     */
    protected function createGitCall($path, array $parameters)
    {
        $cmd    = escapeshellcmd($this->path);
        array_walk($parameters, function(&$p) {
            if (is_array($p) && count($p) == 2) {
                $p  = sprintf('%s=%s', escapeshellarg($p[0]), escapeshellarg($p[1]));
            } else if (is_string($p)) {
                $p  = escapeshellarg($p);
            }
        });

        $call   = SystemCall::create(sprintf('%s %s', $cmd, implode(' ', $parameters)), $path);
        return $call;
    }

    /**
     *
     * @param   string  $method
     * @param   array   $arguments
     * @return  SystemCallResult
     */
    public function __call($method, array $arguments)
    {
        if (count($arguments) < 1) {
            throw new \InvalidArgumentException(sprintf(
                '"%s" must be called with at least one argument denoting the path', $method
            ));
        }
        $path   = array_shift($arguments);
        array_unshift($arguments, $method);
        $call   = $this->createGitCall($path, $arguments);

        return $call->execute();
    }

    /**
     *
     * @param   string  $path
     * @return  boolean
     */
    public function isRepository($path)
    {
        $result = $this->status($path, '-s');
        return $result->getReturnCode() == 0;
    }

    /**
     *
     * @param   string  $path
     * @return  boolean
     */
    public function createRepository($path)
    {
        $result = $this->init($path);
        return $result->getReturnCode() == 0;
    }


}

