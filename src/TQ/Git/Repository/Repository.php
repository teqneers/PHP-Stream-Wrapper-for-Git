<?php
namespace TQ\Git\Repository;

use TQ\Git\Cli\Binary;
use TQ\Git\Cli\CallResult;
use TQ\Git\Cli\CallException;

class Repository
{
    const RESET_STAGED  = 1;
    const RESET_WORKING = 2;
    const RESET_ALL     = 3;

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
        $found  = null;
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
    public function commit($commitMsg, array $file = null)
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
     * @param   integer     $what
     */
    public function reset($what = self::RESET_ALL)
    {
        $what   = (int)$what;
        if (($what & self::RESET_STAGED) == self::RESET_STAGED) {
            $result = $this->getBinary()->reset($this->getRepositoryPath(), array('--hard'));
            self::throwIfError($result, sprintf('Cannot reset "%s"', $this->getRepositoryPath()));
        }

        if (($what & self::RESET_WORKING) == self::RESET_WORKING) {
            $result = $this->getBinary()->clean($this->getRepositoryPath(), array(
                '--force',
                '-x',
                '-d'
            ));
            self::throwIfError($result, sprintf('Cannot clean "%s"', $this->getRepositoryPath()));
        }
    }

    /**
     *
     * @param   array   $file
     * @param   boolean $force
     */
    public function add(array $file = null, $force = false)
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
    public function remove(array $file, $recursive = false, $force = false)
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
    public function move($fromPath, $toPath, $force = false)
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
        self::throwIfError($result, sprintf('Cannot show "%s" at "%s" from "%s"',
            $file, $ref, $this->getRepositoryPath()
        ));

        return $result->getStdOut();
    }

    /**
     *
     * @param   string  $directory
     * @param   string  $ref
     * @return  array
     */
    public function listDirectory($directory = '.', $ref = 'HEAD')
    {
        $directory  = rtrim($directory, DIRECTORY_SEPARATOR.'/').DIRECTORY_SEPARATOR;
        $path       = $this->getRepositoryPath().DIRECTORY_SEPARATOR.$this->resolveLocalPath($directory);
        $result     = $this->getBinary()->{'ls-tree'}($path, array(
            '--name-only',
            '-z',
            $ref
        ));
        self::throwIfError($result, sprintf('Cannot list directory "%s" at "%s" from "%s"',
            $directory, $ref, $this->getRepositoryPath()
        ));

        $output     = $result->getStdOut();
        $listing    = array_map(function($f) {
            return trim($f);
        }, explode("\x0", $output));
        return $listing;
    }

    /**
     *
     * @return  array
     */
    public function getStatus()
    {
        $result = $this->getBinary()->status($this->getRepositoryPath(), array(
            '--short',
            '-z'
        ));
        self::throwIfError($result,
            sprintf('Cannot retrieve status from "%s"', $this->getRepositoryPath())
        );

        $output = trim($result->getStdOut());
        if (empty($output)) {
            return array();
        }

        $status = array_map(function($f) {
            $line   = trim($f);

            $parts  = array();
            preg_match('/^(?<x>.)(?<y>.)\s(?<f>.+)(?:\s->\s(?<f2>.+))?$/', $line, $parts);

            $status = array(
                'file'      => $parts['f'],
                'x'         => trim($parts['x']),
                'y'         => trim($parts['y']),
                'renamed'   => (array_key_exists('f2', $parts)) ? $parts['f2'] : null
            );
            return $status;
        }, explode("\x0", $output));
        return $status;
    }

    /**
     *
     * @return  boolean
     */
    public function isDirty()
    {
        $status = $this->getStatus();
        return !empty($status);
    }

    /**
     *
     * @return  string
     */
    public function getCurrentBranch()
    {
        $result = $this->getBinary()->{'name-rev'}($this->getRepositoryPath(), array(
            '--name-only',
            'HEAD'
        ));
        self::throwIfError($result,
            sprintf('Cannot retrieve current branch from "%s"', $this->getRepositoryPath())
        );

        return $result->getStdOut();
    }

    /**
     *
     * @param   \Closure   $function
     * @return  Transaction
     */
    public function transactional(\Closure $function)
    {
        try {
            $transaction    = new Transaction($this);
            $result         = $function($transaction);
            $this->add(null);

            $commitMsg  = $transaction->getCommitMsg();
            if (empty($commitMsg)) {
                $commitMsg  = sprintf(
                    '%s did a transactional commit in "%s"',
                    __CLASS__,
                    $this->getRepositoryPath()
                );
            }
            $this->commit($commitMsg, null);
            $commitHash  = $this->getCurrentCommit();
            $transaction->setCommitHash($commitHash);
            $transaction->setResult($result);
            return $transaction;
        } catch (\Exception $e) {
            $this->reset();
            throw $e;
        }
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

