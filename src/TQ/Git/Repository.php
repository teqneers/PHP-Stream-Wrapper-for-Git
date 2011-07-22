<?php
namespace TQ\Git;

class Repository
{
    /**
     *
     * @var Binary
     */
    protected $Binary;

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
     * @param   Binary|null  $Binary
     * @return  Repository
     */
    public static function open($repositoryPath, Binary $Binary = null)
    {
        $Binary  = self::ensureBinary($Binary);

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

        return new static($repositoryRoot, $Binary);
    }

    /**
     *
     * @param   string          $repositoryPath
     * @param   integer         $mode
     * @param   Binary|null  $Binary
     * @return  Repository
     */
    public static function create($repositoryPath, $mode = 0755, Binary $Binary = null)
    {
        $Binary  = self::ensureBinary($Binary);

        if (!is_string($repositoryPath)) {
            throw new \InvalidArgumentException(sprintf(
                '"%s" is not a valid path', $repositoryPath
            ));
        }

        if (!file_exists($repositoryPath) && !mkdir($repositoryPath, $mode, true)) {
            throw new \InvalidArgumentException(sprintf(
                '"%s" does not exist and cannot be created', $repositoryPath
            ));
        } else if (self::findRepositoryRoot($repositoryPath) !== null) {
            throw new \InvalidArgumentException(sprintf(
                '"%s" is already a Git repository', $repositoryPath
            ));
        }

        if (!self::initRepository($Binary, $repositoryPath)) {
            throw new \InvalidArgumentException(sprintf(
                'Cannot initialize a Git repository in "%s"', $repositoryPath
            ));
        }
        return new static($repositoryPath, $Binary);
    }

    /**
     *
     * @param   Binary $Binary
     * @return  Binary
     */
    protected static function ensureBinary(Binary $Binary = null)
    {
        if (!$Binary) {
            $Binary  = new Binary();
        }
        return $Binary;
    }

    /**
     *
     * @param   Binary   $Binary
     * @param   string      $path
     * @return  boolean
     */
    protected static function initRepository(Binary $Binary, $path)
    {
        $result = $Binary->init($path);
        return $result->getReturnCode() == 0;
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
     * @param   Binary  $Binary
     */
    protected function __construct($repositoryPath, Binary $Binary)
    {
        $this->Binary        = $Binary;
        $this->repositoryPath   = rtrim($repositoryPath, DIRECTORY_SEPARATOR.'/');
    }

    /**
     *
     * @return  Binary
     */
    public function getBinary()
    {
        return $this->Binary;
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
    public function writeFile($path, $data, $commitMsg = null, $author = null, $fileMode = null, $dirMode = null)
    {
        $file       = $this->resolvePath($path);

        $fileMode   = ($fileMode === null) ? $this->getDefaultFileCreationMode() : (int)$fileMode;
        $dirMode    = ($dirMode === null) ? $this->getDefaultDirectoryCreationMode() : (int)$dirMode;
        $author     = ($author === null) ? $this->getDefaultAuthor() : (string)$author;

        $directory  = dirname($file);
        if (!file_exists($directory) && !mkdir($directory, $dirMode, true)) {
            throw new \InvalidArgumentException(sprintf('Cannot create "%s"', $directory));
        } else if (!file_exists($file)) {
            if (!touch($file)) {
                throw new \InvalidArgumentException(sprintf('Cannot create "%s"', $file));
            }
            if (!chmod($file, $fileMode)) {
                throw new \InvalidArgumentException(sprintf('Cannot chmod "%s" to %d', $file, $fileMode));
            }
        }

        if (!file_put_contents($file, $data)) {
            throw new \InvalidArgumentException(sprintf('Cannot write to "%s"', $file));
        }

        $result = $this->getBinary()->add($this->getRepositoryPath(), $file);

        if ($commitMsg === null) {
            $commitMsg  = sprintf('%s created or changed file "%s"', __CLASS__, $path);
        }

        $args   = array(
            'message'   => $commitMsg
        );
        if ($author === null) {
            $args['s']  = null;
        } else {
            $args['author']  = $author;
        }
        $result = $this->getBinary()->commit($this->getRepositoryPath(), $args);
    }
}

