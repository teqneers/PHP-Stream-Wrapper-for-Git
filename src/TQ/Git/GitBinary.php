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
    public static function findGitBinaryPath()
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
            $path  = self::findGitBinaryPath();
        }
        if (!is_string($path) || empty($path)) {
            throw new \InvalidArgumentException('No path to the Git binary found');
        }
        $this->path    = $path;
    }
}

