<?php
namespace TQ\Git;

use TQ\Git\Cli\Binary;
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
     * @param   Binary|null     $binary
     * @param   boolean|integer $createIfNotExists
     * @return  Repository
     */
    public static function open($repositoryPath, Binary $binary = null, $createIfNotExists = false)
    {
        if (!$binary) {
            $binary  = new Binary();
        }

        if (!is_string($repositoryPath)) {
            throw new \InvalidArgumentException(sprintf(
                '"%s" is not a valid path', $repositoryPath
            ));
        }

        if (   !$createIfNotExists
            && (!file_exists($repositoryPath) || !is_dir($repositoryPath))
        ) {
            throw new \InvalidArgumentException(sprintf(
                '"%s" is not a valid path', $repositoryPath
            ));
        }

        if ($createIfNotExists) {
            if (!file_exists($repositoryPath) && !mkdir($repositoryPath, $createIfNotExists, true)) {
                throw new \RuntimeException(sprintf(
                    '"%s" cannot be created', $repositoryPath
                ));
            } else if (!is_dir($repositoryPath)) {
                throw new \InvalidArgumentException(sprintf(
                    '"%s" is not a valid path', $repositoryPath
                ));
            }
            self::initRepository($binary, $repositoryPath);
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
     * @param   string|array  $localPath
     * @return  string
     */
    public function resolvePath($localPath)
    {
        if (is_array($localPath)) {
            $paths  = array();
            foreach ($localPath as $f) {
                $paths[]    = $this->resolvePath($f);
            }
            return $paths;
        } else {
            if (strpos($localPath, $this->getRepositoryPath()) === 0) {
                return $localPath;
            }
            $localPath  = ltrim($localPath, DIRECTORY_SEPARATOR.'/');
            return $this->getRepositoryPath().'/'.$localPath;
        }
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
     * @param   string       $commitMsg
     * @param   array|null   $file
     */
    protected function commit($commitMsg, array $file = null)
    {
        $author = $this->getAuthor();
        $args   = array(
            '--message'   => $commitMsg
        );
        if ($author !== null) {
            $args['--author']  = $author;
        }
        if ($file !== null) {
            $args   = array_merge($args, $this->resolvePath($file));
        }

        $result = $this->getBinary()->commit($this->getRepositoryPath(), $args);
        self::throwIfError($result, sprintf('Cannot commit to "%s"', $this->getRepositoryPath()));
    }

    /**
     *
     * @param   array   $file
     * @param   boolean $force
     * @return  string
     */
    protected function add(array $file, $force = false)
    {
        $args   = array();
        if ($force) {
            $args[]  = '--force';
        }
        $args   = array_merge($args, $this->resolvePath($file));

        $result = $this->getBinary()->add($this->getRepositoryPath(), $args);
        self::throwIfError($result, sprintf('Cannot add "%s" to "%s"',
            implode(', ', $file), $this->getRepositoryPath()
        ));
    }

    /**
     *
     * @param   array   $file
     * @param   boolean $recursive
     * @param   boolean $force
     * @return  string
     */
    protected function remove(array $file, $recursive = false, $force = false)
    {
        $args   = array();
        if ($recursive) {
            $args[] = '-r';
        }
        if ($force) {
            $args[] = '--force';
        }
        $args   = array_merge($args, $this->resolvePath($file));

        $result = $this->getBinary()->rm($this->getRepositoryPath(), $args);
        self::throwIfError($result, sprintf('Cannot remove "%s" from "%s"',
            implode(', ', $file), $this->getRepositoryPath()
        ));
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

        $this->add(array($file));

        if ($commitMsg === null) {
            $commitMsg  = sprintf('%s created or changed file "%s"', __CLASS__, $path);
        }

        $this->commit($commitMsg, array($file));

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
        $this->remove(array($path), $recursive, $force);

        if ($commitMsg === null) {
            $commitMsg  = sprintf('%s deleted file "%s"', __CLASS__, $path);
        }

        $this->commit($commitMsg, array($path));

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
     * @param   string  $file
     * @param   string  $ref
     */
    public function showFile($file, $ref = 'HEAD')
    {
        $result = $this->getBinary()->show($this->getRepositoryPath(), array(
            sprintf('%s:%s', $ref, $file)
        ));
        self::throwIfError($result, sprintf('Cannot lshow "%s" at "%s" from "%s"',
            $file, $ref, $this->getRepositoryPath()
        ));

        return $result->getStdOut();
    }

    /**
     *
     * @param   string  $ref
     * @param   string  $directory
     * @return  string
     */
    public function listDirectory($ref = 'HEAD', $directory = '.')
    {
        $result = $this->getBinary()->{'ls-tree'}($this->getRepositoryPath(), array(
            '-r',
            '-t',
            '--long',
            '--full-tree',
            $ref,
            $this->resolvePath($directory)
        ));
        self::throwIfError($result, sprintf('Cannot list directory "%s" at "%s" from "%s"',
            $directory, $ref, $this->getRepositoryPath()
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

