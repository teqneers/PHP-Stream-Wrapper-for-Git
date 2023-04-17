<?php
/*
 * Copyright (C) 2017 by TEQneers GmbH & Co. KG
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
 * Git Stream Wrapper for PHP
 *
 * @category   TQ
 * @package    TQ_VCS
 * @subpackage SVN
 * @copyright  Copyright (C) 2018 by TEQneers GmbH & Co. KG
 */

namespace TQ\Svn\Repository;
use TQ\Vcs\FileSystem;
use TQ\Vcs\Repository\AbstractRepository;
use TQ\Svn\Cli\Binary;
use TQ\Vcs\Cli\CallResult;

/**
 * Provides access to a SVN repository
 *
 * @uses       \TQ\Svn\Cli\Binary
 * @author     Stefan Gehrig <gehrigteqneers.de>
 * @category   TQ
 * @package    TQ_VCS
 * @subpackage SVN
 * @copyright  Copyright (C) 2018 by TEQneers GmbH & Co. KG
 */
class Repository extends AbstractRepository
{
    /**
     * The SVN binary
     *
     * @var Binary
     */
    protected $svn;

    /**
     * Opens a SVN repository on the file system
     *
     * @param   string               $repositoryPath        The full path to the repository
     * @param   Binary|string|null   $svn                   The SVN binary
     * @return  Repository
     * @throws  \RuntimeException                       If the path cannot be created
     * @throws  \InvalidArgumentException               If the path is not valid or if it's not a valid SVN repository
     */
    public static function open($repositoryPath, $svn = null)
    {
        $svn = Binary::ensure($svn);

        if (!is_string($repositoryPath)) {
            throw new \InvalidArgumentException(sprintf(
                '"%s" is not a valid path', $repositoryPath
            ));
        }

        $repositoryRoot = self::findRepositoryRoot($svn, $repositoryPath);

        if ($repositoryRoot === null) {
            throw new \InvalidArgumentException(sprintf(
                '"%s" is not a valid SVN repository', $repositoryPath
            ));
        }

        return new static($repositoryRoot, $svn);
    }

    /**
     * Tries to find the root directory for a given repository path
     *
     * @param   Binary      $svn        The SVN binary
     * @param   string      $path       The file system path
     * @return  string|null             NULL if the root cannot be found, the root path otherwise
     */
    public static function findRepositoryRoot(Binary $svn, $path)
    {
        $hasSvnDir  = function($path) {
            $svnDir = $path.'/'.'.svn';
            return file_exists($svnDir) && is_dir($svnDir);
        };

        $pathWithSvnDir = FileSystem::bubble($path, $hasSvnDir);

        if (is_null($pathWithSvnDir)) {
            return null;
        }

        $root       = $pathWithSvnDir;
        $parentDir  = dirname($pathWithSvnDir);
        while ($hasSvnDir($parentDir) && strlen($root) > 1) {
            $root      = dirname($root);
            $parentDir = dirname($parentDir);
        }
        return $root;
    }

    /**
     * Creates a new repository instance - use {@see open()} instead
     *
     * @param   string     $repositoryPath
     * @param   Binary     $svn
     */
    protected function __construct($repositoryPath, Binary $svn)
    {
        $this->svn   = $svn;
        parent::__construct($repositoryPath);
    }

    /**
     * Returns the SVN binary
     *
     * @return  Binary
     */
    public function getSvn()
    {
        return $this->svn;
    }

    /**
     * Returns the current commit hash
     *
     * @return  string
     * @throws  \RuntimeException
     */
    public function getCurrentCommit()
    {
        /** @var $result CallResult */
        $result = $this->getSvn()->{'info'}($this->getRepositoryPath(), array(
            '--xml',
            '--revision' => 'HEAD'
        ));
        $result->assertSuccess(sprintf('Cannot get info for "%s"', $this->getRepositoryPath()));

        $xml    = simplexml_load_string($result->getStdOut());
        if (!$xml) {
            throw new \RuntimeException(sprintf('Cannot read info XML for "%s"', $this->getRepositoryPath()));
        }

        $commit = $xml->xpath('/info/entry/commit[@revision]');
        if (empty($commit)) {
            throw new \RuntimeException(sprintf('Cannot read info XML for "%s"', $this->getRepositoryPath()));
        }

        $commit = reset($commit);
        return (string)($commit['revision']);
    }

    /**
     * Commits the currently staged changes into the repository
     *
     * @param   string       $commitMsg         The commit message
     * @param   array|null   $file              Restrict commit to the given files or NULL to commit all staged changes
     * @param   array        $extraArgs         Allow the user to pass extra args eg array('-i')
     * @param   string|null  $author            The author
     */
    public function commit($commitMsg, array $file = null, $author = null, array $extraArgs = array())
    {
        $author = $author ?: $this->getAuthor();
        $args   = array(
            '--message'   => $commitMsg
        );
        if ($author !== null) {
            $args['--username']  = $author;
        }
        if ($file !== null) {
            $args[] = '--';
            $args   = array_merge($args, $this->resolveLocalGlobPath($file));
        }

        /** @var $result CallResult */
        $result = $this->getSvn()->{'commit'}($this->getRepositoryPath(), $args);
        $result->assertSuccess(sprintf('Cannot commit to "%s"', $this->getRepositoryPath()));
    }

    /**
     * Resets the working directory and/or the staging area and discards all changes
     *
     * @throws  \RuntimeException
     */
    public function reset()
    {
        /** @var $result CallResult */
        $result = $this->getSvn()->{'revert'}($this->getRepositoryPath(), array(
            '--recursive',
            '--',
            '.'
        ));
        $result->assertSuccess(sprintf('Cannot reset "%s"', $this->getRepositoryPath()));

        $status = $this->getStatus();
        foreach ($status as $item) {
            $file   = $this->resolveFullPath($item['file']);
            if (@unlink($file) !== true || $item['status'] !== 'unversioned') {
                throw new \RuntimeException('Cannot delete file "'.$item['file'].'"');
            }
        }
    }

    /**
     * Adds one or more files to the staging area
     *
     * @param   array|null  $file       The file(s) to be added or NULL to add all new and/or changed files to the staging area
     * @param   boolean     $force
     */
    public function add(array $file = null, $force = false)
    {
        $args   = array();
        if ($force) {
            $args[]  = '--force';
        }
        if ($file !== null) {
            $status = $this->getStatus();
            if (empty($status)) {
                return;
            }

            $files  = $this->resolveLocalGlobPath($file);
            foreach ($this->getStatus() as $status) {
                if (   $status['status'] != 'unversioned'
                    && in_array($status['file'], $files)
                ) {
                    array_splice($files, array_search($status['file'], $files), 1);
                }
            }

            if (empty($files)) {
                return;
            }

            $args[] = '--parents';
            $args[] = '--';
            $args   = array_merge($args, $files);
        } else {
            $toAdd      = array();
            $toRemove   = array();
            foreach ($this->getStatus() as $status) {
                if ($status['status'] == 'missing') {
                    $toRemove[] = $this->resolveLocalPath($status['file']);
                } else if ($status['status'] == 'unversioned') {
                    $toAdd[] = $this->resolveLocalPath($status['file']);
                }
            }

            if (!empty($toRemove)) {
                $this->remove($toRemove, false, $force);
            }
            if (empty($toAdd)) {
                return;
            }

            $args['--depth']    = 'infinity';
            $args[]             = '--';
            $args               = array_merge($args, $toAdd);
        }

        /** @var $result CallResult */
        $result = $this->getSvn()->{'add'}($this->getRepositoryPath(), $args);
        $result->assertSuccess(sprintf('Cannot add "%s" to "%s"',
            ($file !== null) ? implode(', ', $file) : '*', $this->getRepositoryPath()
        ));
    }

    /**
     * Removes one or more files from the repository but does not commit the changes
     *
     * @param   array   $file           The file(s) to be removed
     * @param   boolean $recursive      True to recursively remove subdirectories
     * @param   boolean $force          True to continue even though SVN reports a possible conflict
     */
    public function remove(array $file, $recursive = false, $force = false)
    {
        $args   = array();
        if ($force) {
            $args[] = '--force';
        }
        $args[] = '--';
        $args   = array_merge($args, $this->resolveLocalGlobPath($file));

        /** @var $result CallResult */
        $result = $this->getSvn()->{'delete'}($this->getRepositoryPath(), $args);
        $result->assertSuccess(sprintf('Cannot remove "%s" from "%s"',
            implode(', ', $file), $this->getRepositoryPath()
        ));
    }

    /**
     * Renames a file but does not commit the changes
     *
     * @param   string  $fromPath   The source path
     * @param   string  $toPath     The destination path
     * @param   boolean $force      True to continue even though SVN reports a possible conflict
     */
    public function move($fromPath, $toPath, $force = false)
    {
        $args   = array();
        if ($force) {
            $args[] = '--force';
        }
        $args[] = $this->resolveLocalPath($fromPath);
        $args[] = $this->resolveLocalPath($toPath);

        /** @var $result CallResult */
        $result = $this->getSvn()->{'move'}($this->getRepositoryPath(), $args);
        $result->assertSuccess(sprintf('Cannot move "%s" to "%s" in "%s"',
            $fromPath, $toPath, $this->getRepositoryPath()
        ));
    }

    /**
     * Writes data to a file and commit the changes immediately
     *
     * @param   string          $path           The file path
     * @param   string|array    $data           The data to write to the file
     * @param   string|null     $commitMsg      The commit message used when committing the changes
     * @param   integer|null    $fileMode       The mode for creating the file
     * @param   integer|null    $dirMode        The mode for creating the intermediate directories
     * @param   boolean         $recursive      Create intermediate directories recursively if required
     * @param   string|null     $author         The author
     * @return  string                          The current commit hash
     * @throws  \RuntimeException               If the file could not be written
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

        $this->commit($commitMsg, null, $author);

        return $this->getCurrentCommit();
    }

    /**
     * Writes data to a file and commit the changes immediately
     *
     * @param   string          $path           The directory path
     * @param   string|null     $commitMsg      The commit message used when committing the changes
     * @param   integer|null    $dirMode        The mode for creating the intermediate directories
     * @param   boolean         $recursive      Create intermediate directories recursively if required
     * @param   string|null     $author         The author
     * @return  string                          The current commit hash
     * @throws  \RuntimeException               If the directory could not be created
     */
    public function createDirectory($path, $commitMsg = null, $dirMode = null, $recursive = true, $author = null) {
        $directory  = $this->resolveFullPath($path);
        $dirMode    = $dirMode ?: $this->getDirectoryCreationMode();

        if (file_exists($directory) || !mkdir($directory, (int)$dirMode, $recursive)) {
            throw new \RuntimeException(sprintf('Cannot create "%s"', $directory));
        }

        $this->add(array($this->resolveLocalPath($directory)));

        if ($commitMsg === null) {
            $commitMsg  = sprintf('%s created directory "%s"', __CLASS__, $path);
        }

        $this->commit($commitMsg, null, $author);

        return $this->getCurrentCommit();
    }

    /**
     * Removes a file and commit the changes immediately
     *
     * @param   string          $path           The file path
     * @param   string|null     $commitMsg      The commit message used when committing the changes
     * @param   boolean         $recursive      True to recursively remove subdirectories
     * @param   boolean         $force          True to continue even though SVN reports a possible conflict
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
     * @param   boolean         $force          True to continue even though SVN reports a possible conflict
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
     * @return  array
     * @throws  \RuntimeException
     */
    public function getLog($limit = null, $skip = null)
    {
        $arguments  = array(
            '--xml',
            '--revision'    => 'HEAD:0'
        );


        $skip   = ($skip === null) ? 0 : (int)$skip;
        if ($limit !== null) {
            $arguments['--limit']    = (int)($limit + $skip);
        }

        /** @var $result CallResult */
        $result = $this->getSvn()->{'log'}($this->getRepositoryPath(), $arguments);
        $result->assertSuccess(sprintf('Cannot retrieve log from "%s"',
            $this->getRepositoryPath()
        ));

        $xml    = simplexml_load_string($result->getStdOut());
        if (!$xml) {
            throw new \RuntimeException(sprintf('Cannot read log XML for "%s"', $this->getRepositoryPath()));
        }
        $logEntries = new \ArrayIterator($xml->xpath('/log/logentry'));

        if ($limit !== null) {
            $logEntries = new \LimitIterator($logEntries, $skip, $limit);
        }

        $log = array();
        foreach ($logEntries as $item) {
            $log[]   = array(
                (string)$item['revision'],
                (string)$item->author,
                (string)$item->date,
                (string)$item->msg
            );
        }
        return $log;
    }

    /**
     * Returns a string containing information about the given commit
     *
     * @param  string  $hash       The commit ref
     * @return  string
     */
    public function showCommit($hash)
    {
        /** @var $result CallResult */
        $result = $this->getSvn()->{'log'}($this->getRepositoryPath(), array(
            '-v',
            '-r' => $hash
        ));
        $result->assertSuccess(sprintf('Cannot retrieve commit "%s" from "%s"',
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
        /** @var $result CallResult */
        $result = $this->getSvn()->{'cat'}($this->getRepositoryPath(), array(
            '--revision'    => $ref,
            $file
        ));
        $result->assertSuccess(sprintf('Cannot show "%s" at "%s" from "%s"',
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
     * @throws  \RuntimeException
     */
    public function getObjectInfo($path, $ref = 'HEAD')
    {
        /** @var $result CallResult */
        $result = $this->getSvn()->{'info'}($this->getRepositoryPath(), array(
            '--xml',
            '--revision' => $ref,
            $this->resolveLocalPath($path)
        ));
        $result->assertSuccess(sprintf('Cannot get info for "%s" at "%s" from "%s"',
            $path, $ref, $this->getRepositoryPath()
        ));

        $xml    = simplexml_load_string($result->getStdOut());
        if (!$xml) {
            throw new \RuntimeException(sprintf('Cannot read info XML for "%s" at "%s" from "%s"',
                $path, $ref, $this->getRepositoryPath()
            ));
        }

        $entry = $xml->xpath('/info/entry');
        if (count($entry) !== 1) {
            throw new \RuntimeException(sprintf('Cannot read info XML for "%s" at "%s" from "%s"',
                $path, $ref, $this->getRepositoryPath()
            ));
        }
        $entry  = reset($entry);
        $mode   = 0;
        switch ((string)$entry['kind']) {
            case 'dir':
                $mode   |= 0040000;
                break;
            case 'file':
                $mode   |= 0100000;
                break;
        }
        return array(
            'type'  => (string)$entry['kind'],
            'mode'  => (int)$mode,
            'size'  => 0
        );
    }

    /**
     * List the directory at a given version
     *
     * @param   string  $directory      The path ot the directory
     * @param   string  $ref            The version ref
     * @return  array
     * @throws  \RuntimeException
     */
    public function listDirectory($directory = '.', $ref = 'HEAD')
    {
        $directory  = FileSystem::normalizeDirectorySeparator($directory);
        $directory  = rtrim($directory, '/').'/';

        $args   = array(
            '--xml',
            '--revision' => $ref,
            $this->resolveLocalPath($directory)
        );

        /** @var $result CallResult */
        $result = $this->getSvn()->{'list'}($this->getRepositoryPath(), $args);
        $result->assertSuccess(sprintf('Cannot list directory "%s" at "%s" from "%s"',
            $directory, $ref, $this->getRepositoryPath()
        ));

        $xml    = simplexml_load_string($result->getStdOut());
        if (!$xml) {
            throw new \RuntimeException(sprintf('Cannot read list XML for "%s" at "%s" from "%s"',
                $directory, $ref, $this->getRepositoryPath()
            ));
        }

        $list = array();
        foreach ($xml->xpath('/lists/list/entry') as $item) {
            $list[]   = (string)$item->name;
        }
        return $list;
    }

    /**
     * Returns the current status of the working directory
     *
     * The returned array structure is
     *      array(
     *          'file'      => '...',
     *          'status'    => '...'
     *      )
     *
     * @return  array
     * @throws  \RuntimeException
     */
    public function getStatus()
    {
        /** @var $result CallResult */
        $result = $this->getSvn()->{'status'}($this->getRepositoryPath(), array(
            '--xml'
        ));
        $result->assertSuccess(
            sprintf('Cannot retrieve status from "%s"', $this->getRepositoryPath())
        );

        $xml    = simplexml_load_string($result->getStdOut());
        if (!$xml) {
            throw new \RuntimeException(sprintf('Cannot read status XML for "%s"', $this->getRepositoryPath()));
        }

        $status = array();
        foreach ($xml->xpath('/status/target/entry') as $entry) {
            $status[]   = array(
                'file'      => (string)$entry['path'],
                'status'    => (string)$entry->{'wc-status'}['item']
            );
        }
        return $status;
    }

    /**
     * Returns the diff of a files
     *
     * @param   array|null  $files       The path to the files
     * @return  string[]
     */
    public function getDiff(array $files = null)
    {
        $diffs = array();

        if (is_null($files)) {
            $status = $this->getStatus();

            $files = [];
            foreach ($status as $entry) {
                if ($entry['status'] !== 'modified') {
                    continue;
                }

                $files[] = $entry['file'];
            }

            asort($files);
        }

        $files = array_map(array($this, 'resolveLocalPath'), $files);

        foreach ($files as $file) {
            $result = $this->getSvn()->{'diff'}($this->getRepositoryPath(), $file);
            $result->assertSuccess(sprintf('Cannot show diff for "%s" from "%s"',
                $file, $this->getRepositoryPath()
            ));

            $diffs[$file] = $result->getStdOut();
        }

        return $diffs;
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
     * Resolves an absolute path containing glob wildcards into a path relative to the repository path
     *
     * @param   array       $files      The list of files
     * @return  array
     */
    protected function resolveLocalGlobPath(array $files)
    {
        $absoluteFiles  = $this->resolveFullPath($files);
        $expandedFiles  = array();
        foreach ($absoluteFiles as $absoluteFile) {
            $globResult     = glob($absoluteFile);
            if (   empty($globResult)
                && stripos($absoluteFile, '*') === false
                && !file_exists($absoluteFile)
            ) {
                $expandedFiles[]    = $absoluteFile;
            } else {
                $expandedFiles  = array_merge($expandedFiles, $globResult);
            }
        }
        return $this->resolveLocalPath($expandedFiles);
    }
}

