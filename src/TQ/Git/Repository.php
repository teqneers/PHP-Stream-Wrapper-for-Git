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
     * @param   string|array  $path
     * @return  string
     */
    public function resolveLocalPath($path)
    {
        if (is_array($path)) {
            $paths  = array();
            foreach ($path as $p) {
                $paths[]    = $this->resolveLocalPath($p);
            }
            return $paths;
        } else {
            if (strpos($path, $this->getRepositoryPath()) === 0) {
                $path  = substr($path, strlen($this->getRepositoryPath()));
            }
            return ltrim($path, DIRECTORY_SEPARATOR.'/');
        }
    }

    /**
     *
     * @param   string|array  $path
     * @return  string
     */
    public function resolveFullPath($path)
    {
        if (is_array($path)) {
            $paths  = array();
            foreach ($path as $p) {
                $paths[]    = $this->resolveFullPath($p);
            }
            return $paths;
        } else {
            if (strpos($path, $this->getRepositoryPath()) === 0) {
                return $path;
            }
            $path  = ltrim($path, DIRECTORY_SEPARATOR.'/');
            return $this->getRepositoryPath().'/'.$path;
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
            $args[] = '--';
            $args   = array_merge($args, $this->resolveLocalPath($file));
        }

        $result = $this->getBinary()->commit($this->getRepositoryPath(), $args);
        self::throwIfError($result, sprintf('Cannot commit to "%s"', $this->getRepositoryPath()));
    }

    /**
     *
     * @param   array   $file
     * @param   boolean $force
     */
    protected function add(array $file = null, $force = false)
    {
        $args   = array();
        if ($force) {
            $args[]  = '--force';
        }
        if ($file !== null) {
            $args[] = '--';
            $args   = array_merge($args, $this->resolveLocalPath($file));
        } else {
            $args[] = '--all';
        }

        $result = $this->getBinary()->add($this->getRepositoryPath(), $args);
        self::throwIfError($result, sprintf('Cannot add "%s" to "%s"',
            ($file !== null) ? implode(', ', $file) : '*', $this->getRepositoryPath()
        ));
    }

    /**
     *
     * @param   array   $file
     * @param   boolean $recursive
     * @param   boolean $force
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
        $args[] = '--';
        $args   = array_merge($args, $this->resolveLocalPath($file));

        $result = $this->getBinary()->rm($this->getRepositoryPath(), $args);
        self::throwIfError($result, sprintf('Cannot remove "%s" from "%s"',
            implode(', ', $file), $this->getRepositoryPath()
        ));
    }

    /**
     *
     * @param   string  $fromPath
     * @param   string  $toPath
     * @param   boolean $force
     */
    protected function move($fromPath, $toPath, $force = false)
    {
        $args   = array();
        if ($force) {
            $args[] = '--force';
        }
        $args[] = $this->resolveLocalPath($fromPath);
        $args[] = $this->resolveLocalPath($toPath);

        $result = $this->getBinary()->mv($this->getRepositoryPath(), $args);
        self::throwIfError($result, sprintf('Cannot move "%s" to "%s" in "%s"',
            $fromPath, $toPath, $this->getRepositoryPath()
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
        $file       = $this->resolveFullPath($path);

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
     * @param   string          $fromPath
     * @param   string          $toPath
     * @param   string|null     $commitMsg
     * @param   boolean         $force
     * @return  string
     */
    public function renameFile($fromPath, $toPath, $commitMsg = null, $force = false)
    {
        $this->move($fromPath, $toPath, $force);

        if ($commitMsg === null) {
            $commitMsg  = sprintf('%s renamed/moved file "%s" to "%s"', __CLASS__, $fromPath, $toPath);
        }

        $this->commit($commitMsg, array($fromPath, $toPath));

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
        $directory  = rtrim($directory, DIRECTORY_SEPARATOR.'/').DIRECTORY_SEPARATOR;
        $result     = $this->getBinary()->{'ls-tree'}($this->getRepositoryPath(), array(
            '--name-only',
            $ref,
            $this->resolveLocalPath($directory)
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

