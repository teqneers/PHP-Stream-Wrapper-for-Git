<?php
namespace TQ\Git;

class Connector
{
    /**
     *
     * @var GitBinary
     */
    protected $gitBinary;

    /**
     *
     * @var string
     */
    protected $repositoryPath;

    /**
     *
     * @param   GitBinary|null $gitBinary
     */
    public function __construct($repositoryPath, GitBinary $gitBinary = null)
    {
        if (!$gitBinary) {
            $gitBinary  = new GitBinary();
        }
        $this->gitBinary    = $gitBinary;

        if (   !is_string($repositoryPath)
            || !file_exists($repositoryPath)
            || !is_dir($repositoryPath))
        {
            throw new \InvalidArgumentException(sprintf(
                '"%s" is not a valid path', $repositoryPath
            ));
        }
        if (!$this->gitBinary->isRepository($repositoryPath)) {
            throw new \InvalidArgumentException(sprintf(
                '"%s" is not a valid Git repository', $repositoryPath
            ));
        }
        $this->repositoryPath   = $repositoryPath;
    }

    public function test() {
        return 1;
    }
}

