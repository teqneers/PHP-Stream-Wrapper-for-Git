<?php
namespace TQ\Git;

use TQ\Git\Cli\CallException;

class Repository
{
    /**
     *
     * @var Binary
     */
    protected $binary;

    /**
     *
     * @var string
     */
    protected $repositoryPath;

    /**
     *
     * @var integer
     */
    protected $defaultFileCreationMode  = 0644;

    /**
     *
     * @var integer
     */
    protected $defaultDirectoryCreationMode = 0755;

    /**
     *
     * @var string
     */
    protected $defaultAuthor;

    /**
     *
     * @param   string          $repositoryPath
     * @param   Binary|null  $binary
     * @return  Repository
     */
    public static function open($repositoryPath, Binary $binary = null)
    {
        $binary  = self::ensureBinary($binary);

        if (   !is_string($repositoryPath)
            || !file_exists($repositoryPath)
            || !is_dir($repositoryPath))
        {
            throw new \InvalidArgumentException(sprintf(
                '"%s" is not a valid path', $repositoryPath
            ));
        }

        $repositoryRoot = self::findRepositoryRoot($repositoryPath);
        if ($repositoryRoot === null) {
            throw new \InvalidArgumentException(sprintf(
                '"%s" is not a valid Git repository', $repositoryPath
            ));
        }

        return new static($repositoryRoot, $binary);
    }

    /**
     *
     * @param   string          $repositoryPath
     * @param   integer         $mode
     * @param   Binary|null  $binary
     * @return  Repository
     */
    public static function create($repositoryPath, $mode = 0755, Binary $binary = null)
    {
        $binary  = self::ensureBinary($binary);

        if (!is_string($repositoryPath)) {
            throw new \InvalidArgumentException(sprintf(
                '"%s" is not a valid path', $repositoryPath
            ));
        }

        if (!file_exists($repositoryPath) && !mkdir($repositoryPath, $mode, true)) {
            throw new \RuntimeException(sprintf(
                '"%s" does not exist and cannot be created', $repositoryPath
            ));
        } else if (self::findRepositoryRoot($repositoryPath) !== null) {
            throw new \InvalidArgumentException(sprintf(
                '"%s" is already a Git repository', $repositoryPath
            ));
        }

        self::initRepository($binary, $repositoryPath);

        return new static($repositoryPath, $binary);
    }

    /**
     *
     * @param   Binary $binary
     * @return  Binary
     */
    protected static function ensureBinary(Binary $binary = null)
    {
        if (!$binary) {
            $binary  = new Binary();
        }
        return $binary;
    }

    /**
     *
     * @param   Binary   $binary
     * @param   string   $path
     */
    protected static function initRepository(Binary $binary, $path)
    {
        $result = $binary->init($path);
        if ($result->getReturnCode() > 0) {
            throw new CallException(
                sprintf('Cannot initialize a Git repository in "%s"', $path),
                $result
            );
        }
    }

    /**
     *
     * @param   string      $path
     * @return  string|null
     */
    public static function findRepositoryRoot($path)
    {
        $found      = null;
        $path       = realpath($path);
        if (!$path) {
            return $found;
        }

        $pathParts  = explode(DIRECTORY_SEPARATOR, $path);
        while (count($pathParts) > 0 && $found === null) {
            $path   = implode(DIRECTORY_SEPARATOR, $pathParts);
            $gitDir = $path.'/.git';
            if (file_exists($gitDir) && is_dir($gitDir)) {
                $found  = $path;
            }
            array_pop($pathParts);
        }
        return $found;
    }

    /**
     *
     * @param   string     $repositoryPath
     * @param   Binary  $binary
     */
    protected function __construct($repositoryPath, Binary $binary)
    {
        $this->binary           = $binary;
        $this->repositoryPath   = rtrim($repositoryPath, DIRECTORY_SEPARATOR.'/');
    }

    /**
     *
     * @return  Binary
     */
    public function getBinary()
    {
        return $this->binary;
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
     * @return  integer
     */
    public function getDefaultFileCreationMode()
    {
        return $this->defaultFileCreationMode;
    }

    /**
     *
     * @param   integer     $defaultFileCreationMode
     * @return  Repository
     */
    public function setDefaultFileCreationMode($defaultFileCreationMode)
    {
        $this->defaultFileCreationMode  = (int)$defaultFileCreationMode;
        return $this;
    }

    /**
     *
     * @return  integer
     */
    public function getDefaultDirectoryCreationMode()
    {
        return $this->defaultDirectoryCreationMode;
    }

    /**
     *
     * @param   integer     $defaultDirectoryCreationMode
     * @return  Repository
     */
    public function setDefaultDirectoryCreationMode($defaultDirectoryCreationMode)
    {
        $this->defaultDirectoryCreationMode  = (int)$defaultDirectoryCreationMode;
        return $this;
    }

    /**
     *
     * @return  string
     */
    public function getDefaultAuthor()
    {
        return $this->defaultAuthor;
    }

    /**
     *
     * @param   integer     $defaultAuthor
     * @return  Repository
     */
    public function setDefaultAuthor($defaultAuthor)
    {
        $this->defaultAuthor  = (string)$defaultAuthor;
        return $this;
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
     * @param   string|null     $author
     * @param   integer|null    $fileMode
     * @param   integer|null    $dirMode
     */
    public function commitFile($path, $data, $commitMsg = null, $author = null, $fileMode = null, $dirMode = null)
    {
        $file       = $this->resolvePath($path);

        $fileMode   = ($fileMode === null) ? $this->getDefaultFileCreationMode() : (int)$fileMode;
        $dirMode    = ($dirMode === null) ? $this->getDefaultDirectoryCreationMode() : (int)$dirMode;
        $author     = ($author === null) ? $this->getDefaultAuthor() : (string)$author;

        $directory  = dirname($file);
        if (!file_exists($directory) && !mkdir($directory, $dirMode, true)) {
            throw new \RuntimeException(sprintf('Cannot create "%s"', $directory));
        } else if (!file_exists($file)) {
            if (!touch($file)) {
                throw new \RuntimeException(sprintf('Cannot create "%s"', $file));
            }
            if (!chmod($file, $fileMode)) {
                throw new \RuntimeException(sprintf('Cannot chmod "%s" to %d', $file, $fileMode));
            }
        }

        if (!file_put_contents($file, $data)) {
            throw new \RuntimeException(sprintf('Cannot write to "%s"', $file));
        }

        $result = $this->getBinary()->add($this->getRepositoryPath(), $file);
        if ($result->getReturnCode() > 0) {
            throw new CallException(
                sprintf('Cannot add "%s" to "%s"', $file, $this->getRepositoryPath()),
                $result
            );
        }

        if ($commitMsg === null) {
            $commitMsg  = sprintf('%s created or changed file "%s"', __CLASS__, $path);
        }

        $args   = array(
            '--message'   => $commitMsg
        );
        if ($author === null) {
            $args[]  = '--signoff';
        } else {
            $args['--author']  = $author;
        }

        $result = $this->getBinary()->commit($this->getRepositoryPath(), $args);
        if ($result->getReturnCode() > 0) {
            throw new CallException(
                sprintf('Cannot commit "%s" to "%s"', $file, $this->getRepositoryPath()),
                $result
            );
        }
    }

    /**
     *
     * @return  string
     */
    public function showLog()
    {
        $args   = array(
            '--format'   => 'fuller',
            '--graph'
        );

        $result = $this->getBinary()->log($this->getRepositoryPath(), $args);
        if ($result->getReturnCode() > 0) {
            throw new CallException(
                sprintf('Cannot retrieve log from "%s"', $this->getRepositoryPath()),
                $result
            );
        }
        return $result->getStdOut();
    }
}

