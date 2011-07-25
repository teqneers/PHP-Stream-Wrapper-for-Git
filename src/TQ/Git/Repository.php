<?php
namespace TQ\Git;

use TQ\Git\Cli\CallResult;
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
    protected $fileCreationMode  = 0644;

    /**
     *
     * @var integer
     */
    protected $directoryCreationMode = 0755;

    /**
     *
     * @var string
     */
    protected $author;

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
        self::throwIfError($result, sprintf('Cannot initialize a Git repository in "%s"', $path));
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
            $gitDir = $path.DIRECTORY_SEPARATOR.'.git';
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
    public function getFileCreationMode()
    {
        return $this->fileCreationMode;
    }

    /**
     *
     * @param   integer     $fileCreationMode
     * @return  Repository
     */
    public function setFileCreationMode($fileCreationMode)
    {
        $this->fileCreationMode  = (int)$fileCreationMode;
        return $this;
    }

    /**
     *
     * @return  integer
     */
    public function getDirectoryCreationMode()
    {
        return $this->directoryCreationMode;
    }

    /**
     *
     * @param   integer     $directoryCreationMode
     * @return  Repository
     */
    public function setDirectoryCreationMode($directoryCreationMode)
    {
        $this->directoryCreationMode  = (int)$directoryCreationMode;
        return $this;
    }

    /**
     *
     * @return  string
     */
    public function getAuthor()
    {
        return $this->author;
    }

    /**
     *
     * @param   string     $author
     * @return  Repository
     */
    public function setAuthor($author)
    {
        $this->author  = (string)$author;
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
     * @return  string
     */
    public function getCurrentCommit()
    {
        $result = $this->getBinary()->{'rev-parse'}($this->getRepositoryPath(), array(
             '--verify',
            'HEAD'
        ));
        self::throwIfError($result, sprintf('Cannot rev-parse "%s"', $this->getRepositoryPath()));
        return $result->getStdOut();
    }

    /**
     *
     * @param   string          $commitMsg
     * @param   string|null     $file
     */
    protected function commit($commitMsg, $file = null)
    {
        $author = $this->getAuthor();
        $args   = array(
            '--message'   => $commitMsg
        );
        if ($author !== null) {
            $args['--author']  = $author;
        }
        if ($file !== null) {
            $args[]  = $file;
        }

        $result = $this->getBinary()->commit($this->getRepositoryPath(), $args);
        self::throwIfError($result, sprintf('Cannot commit "to "%s"', $this->getRepositoryPath()));
    }

    /**
     *
     * @param   string          $path
     * @param   scalar|array    $data
     * @param   string|null     $commitMsg
     * @return  string
     */
    public function writeFile($path, $data, $commitMsg = null)
    {
        $file       = $this->resolvePath($path);

        $fileMode   = $this->getFileCreationMode();
        $dirMode    = $this->getDirectoryCreationMode();

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
        self::throwIfError($result, sprintf('Cannot add "%s" to "%s"',
            $file, $this->getRepositoryPath()
        ));

        if ($commitMsg === null) {
            $commitMsg  = sprintf('%s created or changed file "%s"', __CLASS__, $path);
        }

        $this->commit($commitMsg, $file);

        return $this->getCurrentCommit();
    }

    /**
     *
     * @param   string          $path
     * @param   string|null     $commitMsg
     * @param   boolean         $recursive
     * @param   boolean         $force
     * @return  string
     */
    public function removeFile($path, $commitMsg = null, $recursive = false, $force = false)
    {
        $file   = $this->resolvePath($path);

        $args   = array();
        if ($recursive) {
            $args[] = '-r';
        }
        if ($force) {
            $args[] = '--force';
        }
        $args[]  = $file;

        $result = $this->getBinary()->rm($this->getRepositoryPath(), $args);
        self::throwIfError($result, sprintf('Cannot remove "%s" from "%s"',
            $file, $this->getRepositoryPath()
        ));

        if ($commitMsg === null) {
            $commitMsg  = sprintf('%s deleted file "%s"', __CLASS__, $path);
        }

        $this->commit($commitMsg, $file);

        return $this->getCurrentCommit();
    }

    /**
     *
     * @return  string
     */
    public function showLog()
    {
        $result = $this->getBinary()->log($this->getRepositoryPath(), array(
            '--format'   => 'fuller',
            '--graph'
        ));
        self::throwIfError($result, sprintf('Cannot retrieve log from "%s"',
            $this->getRepositoryPath()
        ));
        return $result->getStdOut();
    }

    /**
     *
     * @return  string  $hash
     * @return  string
     */
    public function showCommit($hash)
    {
        $result = $this->getBinary()->show($this->getRepositoryPath(), array(
            $hash
        ));
        self::throwIfError($result, sprintf('Cannot retrieve commit "%s" from "%s"',
            $hash, $this->getRepositoryPath()
        ));

        return $result->getStdOut();
    }

    /**
     *
     * @param   CallResult  $result
     * @param   string      $message
     */
    protected static function throwIfError(CallResult $result, $message)
    {
        if ($result->getReturnCode() > 0) {
            throw new CallException($message, $result);
        }
    }
}

