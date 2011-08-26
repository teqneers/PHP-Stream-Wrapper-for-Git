<?php
/*
 * Copyright (C) 2011 by TEQneers GmbH & Co. KG
 *
 * Permission is hereby granted, free of charge, to any person obtaining a copy
 * of this software and associated documentation files (the "Software"), to deal
 * in the Software without restriction, including without limitation the rights
 * to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
 * copies of the Software, and to permit persons to whom the Software is
 * furnished to do so, subject to the following conditions:
 *
 * The above copyright notice and this permission notice shall be included in
 * all copies or substantial portions of the Software.
 *
 * THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
 * IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
 * FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
 * AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
 * LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
 * OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN
 * THE SOFTWARE.
 */

/**
 * Git Streamwrapper for PHP
 *
 * @category   TQ
 * @package    TQ_Git
 * @subpackage Repository
 * @copyright  Copyright (C) 2011 by TEQneers GmbH & Co. KG
 */

/**
 * @namespace
 */
namespace TQ\Git\Repository;
use TQ\Git\Cli\Binary;
use TQ\Git\Cli\CallResult;
use TQ\Git\Cli\CallException;

/**
 * Provides access to a Git repository
 *
 * @uses       TQ\Git\Cli\Binary
 * @author     Stefan Gehrig <gehrigteqneers.de>
 * @category   TQ
 * @package    TQ_Git
 * @subpackage Repository
 * @copyright  Copyright (C) 2011 by TEQneers GmbH & Co. KG
 */
class Repository
{
    const RESET_STAGED  = 1;
    const RESET_WORKING = 2;
    const RESET_ALL     = 3;

    const BRANCHES_LOCAL    = 1;
    const BRANCHES_REMOTE   = 2;
    const BRANCHES_ALL      = 3;

    /**
     * The Git binary
     *
     * @var Binary
     */
    protected $binary;

    /**
     * The repository path
     *
     * @var string
     */
    protected $repositoryPath;

    /**
     * The mode used to create files when requested
     *
     * @var integer
     */
    protected $fileCreationMode  = 0644;

    /**
     * The mode used to create directories when requested
     *
     * @var integer
     */
    protected $directoryCreationMode = 0755;

    /**
     * The author used when committing changes
     *
     * @var string
     */
    protected $author;

    /**
     * Opens a Git repository on the file system, optionally creates and inits a new repository
     *
     * @param   string          $repositoryPath         The full path to the repository
     * @param   Binary|null     $binary                 The Git binary
     * @param   boolean|integer $createIfNotExists      False to fail on non-existing repositories, directory
     *                                                  creation mode, such as 0755  if the command
     *                                                  should create the directory and init the repository instead
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

        $repositoryRoot = self::findRepositoryRoot($repositoryPath);

        if ($repositoryRoot === null) {
            if (!$createIfNotExists) {
                throw new \InvalidArgumentException(sprintf(
                    '"%s" is not a valid path', $repositoryPath
                ));
            } else {
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
                $repositoryRoot = $repositoryPath;
            }
        }

        if ($repositoryRoot === null) {
            throw new \InvalidArgumentException(sprintf(
                '"%s" is not a valid Git repository', $repositoryPath
            ));
        }

        return new static($repositoryRoot, $binary);
    }

    /**
     * Inits a path to be used as a Git repository
     *
     * @param   Binary   $binary        The Git binary
     * @param   string   $path          The repository path
     */
    protected static function initRepository(Binary $binary, $path)
    {
        $result = $binary->init($path);
        self::throwIfError($result, sprintf('Cannot initialize a Git repository in "%s"', $path));
    }

    /**
     * Normalizes the directory separator to /
     *
     * @param   string  $path       The path
     * @return  string              The normalized path
     */
    public static function normalizeDirectorySeparator($path)
    {
        return str_replace(DIRECTORY_SEPARATOR, '/', $path);
    }

    /**
     * Tries to find the root directory for a given repository path
     *
     * @param   string      $path       The file system path
     * @return  string|null             NULL if the root cannot be found, the root path otherwise
     */
    public static function findRepositoryRoot($path)
    {
        $found   = null;
        $path    = self::normalizeDirectorySeparator($path);

        $drive  = null;
        if (preg_match('~^(\w:)(.+)~', $path, $parts)) {
            $drive  = $parts[1];
            $path   = $parts[2];
        }

        $pathParts  = explode('/', $path);
        while (count($pathParts) > 0 && $found === null) {
            $path   = implode('/', $pathParts);
            $gitDir = $path.'/'.'.git';
            if (file_exists($gitDir) && is_dir($gitDir)) {
                $found  = $path;
            }
            array_pop($pathParts);
        }

        if ($drive && $found) {
            $found  = $drive.$found;
        }

        return $found;
    }

    /**
     * Creates a new repository instance - use {@see open()} instead
     *
     * @param   string     $repositoryPath
     * @param   Binary  $binary
     */
    protected function __construct($repositoryPath, Binary $binary)
    {
        $this->binary           = $binary;
        $this->repositoryPath   = rtrim($repositoryPath, '/');
    }

    /**
     * Returns the Git binary
     *
     * @return  Binary
     */
    public function getBinary()
    {
        return $this->binary;
    }

    /**
     * Returns the full file system path to the repository
     *
     * @return  string
     */
    public function getRepositoryPath()
    {
        return $this->repositoryPath;
    }

    /**
     * Returns the mode used to create files when requested
     *
     * @return  integer
     */
    public function getFileCreationMode()
    {
        return $this->fileCreationMode;
    }

    /**
     * Sets the mode used to create files when requested
     *
     * @param   integer     $fileCreationMode   The mode, e.g. 644
     * @return  Repository
     */
    public function setFileCreationMode($fileCreationMode)
    {
        $this->fileCreationMode  = (int)$fileCreationMode;
        return $this;
    }

    /**
     * Returns the mode used to create directories when requested
     *
     * @return  integer
     */
    public function getDirectoryCreationMode()
    {
        return $this->directoryCreationMode;
    }

    /**
     * Sets the mode used to create directories when requested
     *
     * @param   integer     $directoryCreationMode   The mode, e.g. 755
     * @return  Repository
     */
    public function setDirectoryCreationMode($directoryCreationMode)
    {
        $this->directoryCreationMode  = (int)$directoryCreationMode;
        return $this;
    }

    /**
     * Returns the author used when committing changes
     *
     * @return  string
     */
    public function getAuthor()
    {
        return $this->author;
    }

    /**
     * Sets the author used when committing changes
     *
     * @param   string     $author      The author used when committing changes
     * @return  Repository
     */
    public function setAuthor($author)
    {
        $this->author  = (string)$author;
        return $this;
    }

    /**
     * Resolves an absolute path into a path relative to the repository path
     *
     * @param   string|array  $path         A file system path (or an array of paths)
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
            $path   = self::normalizeDirectorySeparator($path);
            if (strpos($path, $this->getRepositoryPath()) === 0) {
                $path  = substr($path, strlen($this->getRepositoryPath()));
            }
            return ltrim($path, '/');
        }
    }

    /**
     * Resolves a path relative to the repository into an absolute path
     *
     * @param   string|array  $path     A local path (or an array of paths)
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
            $path  = self::normalizeDirectorySeparator($path);
            $path  = ltrim($path, '/');
            return $this->getRepositoryPath().'/'.$path;
        }
    }

    /**
     * Returns the current commit hash
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
     * Commits the currently staged changes into the repository
     *
     * @param   string       $commitMsg         The commit message
     * @param   array|null   $file              Restrict commit to the given files or NULL to commit all staged changes
     * @param   string|null  $author            The author
     */
    public function commit($commitMsg, array $file = null, $author = null)
    {
        $author = $author ?: $this->getAuthor();
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
     * Resets the working directory and/or the staging area and discards all changes
     *
     * @param   integer     $what       Bit mask to indicate which parts should be resetted
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
     * Adds one or more files to the staging area
     *
     * @param   array   $file       The file(s) to be added or NULL to add all new and/or changed files to the staging area
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
     * Removes one or more files from the repository but does not commit the changes
     *
     * @param   array   $file           The file(s) to be removed
     * @param   boolean $recursive      True to recursively remove subdirectories
     * @param   boolean $force          True to continue even though Git reports a possible conflict
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
     * Renames a file but does not commit the changes
     *
     * @param   string  $fromPath   The source path
     * @param   string  $toPath     The destination path
     * @param   boolean $force      True to continue even though Git reports a possible conflict
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
     * Writes data to a file and commit the changes immediately
     *
     * @param   string          $path           The file path
     * @param   scalar|array    $data           The data to write to the file
     * @param   string|null     $commitMsg      The commit message used when committing the changes
     * @param   integer|null    $fileMode       The mode for creating the file
     * @param   integer|null    $dirMode        The mode for creating the intermediate directories
     * @param   boolean         $recursive      Create intermediate directories recursively if required
     * @param   string|null     $author         The author
     * @return  string                          The current commit hash
     */
    public function writeFile($path, $data, $commitMsg = null, $fileMode = null,
        $dirMode = null, $recursive = true, $author = null
    ) {
        $file       = $this->resolveFullPath($path);

        $fileMode   = $fileMode ?: $this->getFileCreationMode();
        $dirMode    = $dirMode ?: $this->getDirectoryCreationMode();

        $directory  = dirname($file);
        if (!file_exists($directory) && !mkdir($directory, (int)$dirMode, $recursive)) {
            throw new \RuntimeException(sprintf('Cannot create "%s"', $directory));
        } else if (!file_exists($file)) {
            if (!touch($file)) {
                throw new \RuntimeException(sprintf('Cannot create "%s"', $file));
            }
            if (!chmod($file, (int)$fileMode)) {
                throw new \RuntimeException(sprintf('Cannot chmod "%s" to %d', $file, (int)$fileMode));
            }
        }

        if (file_put_contents($file, $data) === false) {
            throw new \RuntimeException(sprintf('Cannot write to "%s"', $file));
        }

        $this->add(array($file));

        if ($commitMsg === null) {
            $commitMsg  = sprintf('%s created or changed file "%s"', __CLASS__, $path);
        }

        $this->commit($commitMsg, array($file), $author);

        return $this->getCurrentCommit();
    }

    /**
     * Removes a file and commit the changes immediately
     *
     * @param   string          $path           The file path
     * @param   string|null     $commitMsg      The commit message used when committing the changes
     * @param   boolean         $recursive      True to recursively remove subdirectories
     * @param   boolean         $force          True to continue even though Git reports a possible conflict
     * @param   string|null     $author         The author
     * @return  string                          The current commit hash
     */
    public function removeFile($path, $commitMsg = null, $recursive = false, $force = false, $author = null)
    {
        $this->remove(array($path), $recursive, $force);

        if ($commitMsg === null) {
            $commitMsg  = sprintf('%s deleted file "%s"', __CLASS__, $path);
        }

        $this->commit($commitMsg, array($path), $author);

        return $this->getCurrentCommit();
    }

    /**
     * Renames a file and commit the changes immediately
     *
     * @param   string          $fromPath       The source path
     * @param   string          $toPath         The destination path
     * @param   string|null     $commitMsg      The commit message used when committing the changes
     * @param   boolean         $force          True to continue even though Git reports a possible conflict
     * @param   string|null     $author         The author
     * @return  string                          The current commit hash
     */
    public function renameFile($fromPath, $toPath, $commitMsg = null, $force = false, $author = null)
    {
        $this->move($fromPath, $toPath, $force);

        if ($commitMsg === null) {
            $commitMsg  = sprintf('%s renamed/moved file "%s" to "%s"', __CLASS__, $fromPath, $toPath);
        }

        $this->commit($commitMsg, array($fromPath, $toPath), $author);

        return $this->getCurrentCommit();
    }

    /**
     * Returns the current repository log
     *
     * @param   integer|null    $limit      The maximum number of log entries returned
     * @param   integer|null    $skip       Number of log entries that are skipped from the beginning
     * @return  string
     */
    public function getLog($limit = null, $skip = null)
    {
        $arguments  = array(
            '--format'   => 'fuller',
            '--summary',
            '-z'
        );

        if ($limit !== null) {
            $arguments[]    = sprintf('-%d', $limit);
        }
        if ($skip !== null) {
            $arguments['--skip']    = (int)$skip;
        }

        $result = $this->getBinary()->log($this->getRepositoryPath(), $arguments);
        self::throwIfError($result, sprintf('Cannot retrieve log from "%s"',
            $this->getRepositoryPath()
        ));

        $output     = $result->getStdOut();
        $log        = array_map(function($f) {
            return trim($f);
        }, explode("\x0", $output));

        return $log;
    }

    /**
     * Returns a string containing information about the given commit
     *
     * @return  string  $hash       The commit ref
     * @return  string
     */
    public function showCommit($hash)
    {
        $result = $this->getBinary()->show($this->getRepositoryPath(), array(
            '--format' => 'fuller',
            $hash
        ));
        self::throwIfError($result, sprintf('Cannot retrieve commit "%s" from "%s"',
            $hash, $this->getRepositoryPath()
        ));

        return $result->getStdOut();
    }

    /**
     * Returns the content of a file at a given version
     *
     * @param   string  $file       The path to the file
     * @param   string  $ref        The version ref
     * @return  string
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
     * Returns information about an object at a given version
     *
     * The information returned is an array with the following structure
     * array(
     *      'type'  => blob|tree|commit,
     *      'mode'  => 0040000 for a tree, 0100000 for a blob, 0 otherwise,
     *      'size'  => the size
     * )
     *
     * @param   string  $path       The path to the object
     * @param   string  $ref        The version ref
     * @return  array               The object info
     */
    public function getObjectInfo($path, $ref = 'HEAD')
    {
        $info   = array(
            'type'  => null,
            'mode'  => 0,
            'size'  => 0
        );

        $result = $this->getBinary()->{'cat-file'}($this->getRepositoryPath(), array(
            '--batch-check'
        ), sprintf('%s:%s', $ref, $path));
        self::throwIfError($result, sprintf('Cannot cat-file "%s" at "%s" from "%s"',
            $path, $ref, $this->getRepositoryPath()
        ));
        $output = trim($result->getStdOut());

        $parts  = array();
        if (preg_match('/^(?<sha1>[0-9a-f]{40}) (?<type>\w+) (?<size>\d+)$/', $output, $parts)) {
            $mode   = 0;
            switch ($parts['type']) {
                case 'tree':
                    $mode   |= 0040000;
                    break;
                case 'blob':
                    $mode   |= 0100000;
                    break;
            }
            $info['sha1']   = $parts['sha1'];
            $info['type']   = $parts['type'];
            $info['mode']   = (int)$mode;
            $info['size']   = (int)$parts['size'];
        }
        return $info;
    }

    /**
     * List the directory at a given version
     *
     * @param   string  $directory      The path ot the directory
     * @param   string  $ref            The version ref
     * @return  array
     */
    public function listDirectory($directory = '.', $ref = 'HEAD')
    {
        $directory  = self::normalizeDirectorySeparator($directory);
        $directory  = rtrim($directory, '/').'/';
        $path       = $this->getRepositoryPath().'/'.$this->resolveLocalPath($directory);
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
     * Returns the current status of the working directory and the stagin area
     *
     * The returned array structure is
     *      array(
     *          'file'      => '...',
     *          'x'         => '.',
     *          'y'         => '.',
     *          'renamed'   => null/'...'
     *      )
     *
     * @return  array
     */
    public function getStatus()
    {
        $result = $this->getBinary()->status($this->getRepositoryPath(), array(
            '--short'
        ));
        self::throwIfError($result,
            sprintf('Cannot retrieve status from "%s"', $this->getRepositoryPath())
        );

        $output = rtrim($result->getStdOut());
        if (empty($output)) {
            return array();
        }

        $status = array_map(function($f) {
            $line   = rtrim($f);
            $parts  = array();
            preg_match('/^(?<x>.)(?<y>.)\s(?<f>.+?)(?:\s->\s(?<f2>.+))?$/', $line, $parts);

            $status = array(
                'file'      => $parts['f'],
                'x'         => trim($parts['x']),
                'y'         => trim($parts['y']),
                'renamed'   => (array_key_exists('f2', $parts)) ? $parts['f2'] : null
            );
            return $status;
        }, explode("\n", $output));
        return $status;
    }

    /**
     * Returns true if there are uncommitted changes in the working directory and/or the staging area
     *
     * @return  boolean
     */
    public function isDirty()
    {
        $status = $this->getStatus();
        return !empty($status);
    }

    /**
     * Returns the name of the current branch
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
     * Returns a list of the branches in the repository
     *
     * @param   integer     $which      Which branches to retrieve (all, local or remote-tracking)
     * @return  array
     */
    public function getBranches($which = self::BRANCHES_LOCAL)
    {
        $which       = (int)$which;
        $arguments  = array(
            '--no-color'
        );

        $local  = (($which & self::BRANCHES_LOCAL) == self::BRANCHES_LOCAL);
        $remote = (($which & self::BRANCHES_REMOTE) == self::BRANCHES_REMOTE);

        if ($local && $remote) {
            $arguments[] = '-a';
        } else if ($remote) {
            $arguments[] = '-r';
        }

        $result = $this->getBinary()->branch($this->getRepositoryPath(), $arguments);
        self::throwIfError($result,
            sprintf('Cannot retrieve branche from "%s"', $this->getRepositoryPath())
        );

        $output = rtrim($result->getStdOut());
        if (empty($output)) {
            return array();
        }

        $branches = array_map(function($b) {
            $line   = rtrim($b);
            if (strpos($line, '* ') === 0) {
                $line   = substr($line, 2);
            }
            return $line;
        }, explode("\n", $output));
        return $branches;
    }

    /**
     * Runs $function in a transactional scope committing all changes to the repository on success,
     * but rolling back all changes in the event of an Exception beeing thrown in the closure
     *
     * The closure $function will be called with a {@see TQ\Git\Repository\Transaction} as its only argument
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
            if ($this->isDirty()) {
                $commitMsg  = $transaction->getCommitMsg();
                if (empty($commitMsg)) {
                    $commitMsg  = sprintf(
                        '%s did a transactional commit in "%s"',
                        __CLASS__,
                        $this->getRepositoryPath()
                    );
                }
                $this->commit($commitMsg, null, $transaction->getAuthor());
            }
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
     * Internal method that checks if the CLI call has succeeded and throws an Excetion otherwise
     *
     * @param   CallResult  $result         The CLI result
     * @param   string      $message        The exception message
     */
    protected static function throwIfError(CallResult $result, $message)
    {
        if ($result->getReturnCode() > 0) {
            throw new CallException($message, $result);
        }
    }
}

