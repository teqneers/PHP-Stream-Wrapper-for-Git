<?php
namespace TQ\Git;

class Repository
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
     * @param   string          $repositoryPath
     * @param   GitBinary|null  $gitBinary
     * @return  Repository
     */
    public static function open($repositoryPath, GitBinary $gitBinary = null)
    {
        if (!$gitBinary) {
            $gitBinary  = new GitBinary();
        }

        if (   !is_string($repositoryPath)
            || !file_exists($repositoryPath)
            || !is_dir($repositoryPath))
        {
            throw new \InvalidArgumentException(sprintf(
                '"%s" is not a valid path', $repositoryPath
            ));
        }
        if (!$gitBinary->isRepository($repositoryPath)) {
            throw new \InvalidArgumentException(sprintf(
                '"%s" is not a valid Git repository', $repositoryPath
            ));
        }

        return new static($repositoryPath, $gitBinary);
    }

    /**
     *
     * @param   string          $repositoryPath
     * @param   GitBinary|null  $gitBinary
     * @return  Repository
     */
    public static function create($repositoryPath, GitBinary $gitBinary = null)
    {
        if (!$gitBinary) {
            $gitBinary  = new GitBinary();
        }

        if (!is_string($repositoryPath)) {
            throw new \InvalidArgumentException(sprintf(
                '"%s" is not a valid path', $repositoryPath
            ));
        }

        if (!file_exists($repositoryPath) && !mkdir($repositoryPath, 0644, true)) {
            throw new \InvalidArgumentException(sprintf(
                '"%s" does not exist and cannot be created', $repositoryPath
            ));
        } else if ($gitBinary->isRepository($repositoryPath)) {
            throw new \InvalidArgumentException(sprintf(
                '"%s" is already a Git repository', $repositoryPath
            ));
        }

        if (!$gitBinary->createRepository($repositoryPath)) {
            throw new \InvalidArgumentException(sprintf(
                'Cannot initialize a Git repository in "%s"', $repositoryPath
            ));
        }
        return new static($repositoryPath, $gitBinary);
    }

    /**
     *
     * @param   string     $repositoryPath
     * @param   GitBinary  $gitBinary
     */
    protected function __construct($repositoryPath, GitBinary $gitBinary)
    {

        $this->gitBinary        = $gitBinary;
        $this->repositoryPath   = $repositoryPath;
    }
}

