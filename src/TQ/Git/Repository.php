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
        $gitBinary  = self::ensureGitBinary($gitBinary);

        if (   !is_string($repositoryPath)
            || !file_exists($repositoryPath)
            || !is_dir($repositoryPath))
        {
            throw new \InvalidArgumentException(sprintf(
                '"%s" is not a valid path', $repositoryPath
            ));
        }
        if (!self::isRepository($gitBinary, $repositoryPath)) {
            throw new \InvalidArgumentException(sprintf(
                '"%s" is not a valid Git repository', $repositoryPath
            ));
        }

        return new static($repositoryPath, $gitBinary);
    }

    /**
     *
     * @param   string          $repositoryPath
     * @param   integer         $mode
     * @param   GitBinary|null  $gitBinary
     * @return  Repository
     */
    public static function create($repositoryPath, $mode = 0755, GitBinary $gitBinary = null)
    {
        $gitBinary  = self::ensureGitBinary($gitBinary);

        if (!is_string($repositoryPath)) {
            throw new \InvalidArgumentException(sprintf(
                '"%s" is not a valid path', $repositoryPath
            ));
        }

        if (!file_exists($repositoryPath) && !mkdir($repositoryPath, $mode, true)) {
            throw new \InvalidArgumentException(sprintf(
                '"%s" does not exist and cannot be created', $repositoryPath
            ));
        } else if (self::isRepository($gitBinary, $repositoryPath)) {
            throw new \InvalidArgumentException(sprintf(
                '"%s" is already a Git repository', $repositoryPath
            ));
        }

        if (!self::initRepository($gitBinary, $repositoryPath)) {
            throw new \InvalidArgumentException(sprintf(
                'Cannot initialize a Git repository in "%s"', $repositoryPath
            ));
        }
        return new static($repositoryPath, $gitBinary);
    }

    /**
     *
     * @param   GitBinary $gitBinary
     * @return  GitBinary
     */
    protected static function ensureGitBinary(GitBinary $gitBinary = null)
    {
        if (!$gitBinary) {
            $gitBinary  = new GitBinary();
        }
        return $gitBinary;
    }

    /**
     *
     * @param   GitBinary   $gitBinary
     * @param   string      $path
     * @return  boolean
     */
    protected static function isRepository(GitBinary $gitBinary, $path)
    {
        $result = $gitBinary->status($path, '-s');
        return $result->getReturnCode() == 0;
    }

    /**
     *
     * @param   GitBinary   $gitBinary
     * @param   string      $path
     * @return  boolean
     */
    protected static function initRepository(GitBinary $gitBinary, $path)
    {
        $result = $gitBinary->init($path);
        return $result->getReturnCode() == 0;
    }

    /**
     *
     * @param   string     $repositoryPath
     * @param   GitBinary  $gitBinary
     */
    protected function __construct($repositoryPath, GitBinary $gitBinary)
    {
        $this->gitBinary        = $gitBinary;
        $this->repositoryPath   = rtrim($repositoryPath, DIRECTORY_SEPARATOR.'/');
    }

    /**
     *
     * @return  GitBinary
     */
    public function getGitBinary()
    {
        return $this->gitBinary;
    }

    /**
     *
     * @return  string
     */
    public function getRepositoryPath()
    {
        return $this->repositoryPath;
    }

    /**
     *
     * @param   string  $localPath
     * @return  string
     */
    public function resolvePath($localPath)
    {
        $localPath  = ltrim($localPath, DIRECTORY_SEPARATOR.'/');
        return $this->getRepositoryPath().'/'.$localPath;
    }

    /**
     *
     * @param   string          $path
     * @param   scalar|array    $data
     * @param   string|null     $commitMsg
     * @param   integer         $fileMode
     * @param   integer         $dirMode
     */
    public function addFile($path, $data, $commitMsg = null, $fileMode = 0644, $dirMode = 0755)
    {
        $file   = $this->resolvePath($path);
        if (file_exists($file)) {
            throw new \InvalidArgumentException(sprintf('"%s" already exists', $file));
        }

        $directory  = dirname($file);
        if (!file_exists($directory) && !mkdir($directory, $dirMode, true)) {
            throw new \InvalidArgumentException(sprintf('Cannot create "%s"', $directory));
        }

        if (!file_put_contents($file, $data)) {
            throw new \InvalidArgumentException(sprintf('Cannot write to "%s"', $file));
        }
        if (!chmod($file, $fileMode)) {
            throw new \InvalidArgumentException(sprintf('Cannot chmod "%s" to %d', $file, $fileMode));
        }

        $result = $this->getGitBinary()->add($this->getRepositoryPath(), $file);
        print_r($result);
    }
}

