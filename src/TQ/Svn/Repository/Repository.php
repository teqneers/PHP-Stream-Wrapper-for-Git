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
 * Git Stream Wrapper for PHP
 *
 * @category   TQ
 * @package    TQ_Git
 * @subpackage Repository
 * @copyright  Copyright (C) 2011 by TEQneers GmbH & Co. KG
 */

/**
 * @namespace
 */
namespace TQ\Svn\Repository;
use TQ\Vcs\FileSystem;
use TQ\Vcs\Repository\AbstractRepository;
use TQ\Svn\Cli\Binary;
use TQ\Vcs\Cli\CallResult;

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
class Repository extends AbstractRepository
{
    /**
     * The SVN binary
     *
     * @var Binary
     */
    protected $binary;

    /**
     * Opens a SVN repository on the file system
     *
     * @param   string               $repositoryPath        The full path to the repository
     * @param   Binary|string|null   $binary                The SVN binary
     * @return  Repository
     * @throws  \RuntimeException                       If the path cannot be created
     * @throws  \InvalidArgumentException               If the path is not valid or if it's not a valid SVN repository
     */
    public static function open($repositoryPath, $binary = null)
    {
        $binary = Binary::ensure($binary);

        if (!is_string($repositoryPath)) {
            throw new \InvalidArgumentException(sprintf(
                '"%s" is not a valid path', $repositoryPath
            ));
        }

        $repositoryRoot = self::findRepositoryRoot($repositoryPath);

        if ($repositoryRoot === null) {
            throw new \InvalidArgumentException(sprintf(
                '"%s" is not a valid SVN repository', $repositoryPath
            ));
        }

        return new static($repositoryRoot, $binary);
    }

    /**
     * Tries to find the root directory for a given repository path
     *
     * @param   string      $path       The file system path
     * @return  string|null             NULL if the root cannot be found, the root path otherwise
     */
    public static function findRepositoryRoot($path)
    {
        return FileSystem::bubble($path, function($p) {
            $gitDir = $p.'/'.'.svn';
            return file_exists($gitDir) && is_dir($gitDir);
        });
    }

    /**
     * Creates a new repository instance - use {@see open()} instead
     *
     * @param   string     $repositoryPath
     * @param   Binary     $binary
     */
    protected function __construct($repositoryPath, Binary $binary)
    {
        $this->binary   = $binary;
        parent::__construct($repositoryPath);
    }

    /**
     * Returns the SVN binary
     *
     * @return  Binary
     */
    public function getBinary()
    {
        return $this->binary;
    }

    /**
     * Returns the current commit hash
     *
     * @return  string
     */
    public function getCurrentCommit()
    {

    }

    /**
     * Commits the currently staged changes into the repository
     *
     * @param   string       $commitMsg         The commit message
     * @param   array|null   $file              Restrict commit to the given files or NULL to commit all staged changes
     * @param   array        $extraArgs         Allow the user to pass extra args eg array('-i')
     * @param   string|null  $author            The author
     */
    public function commit($commitMsg, array $file = null, $author = null, $extraArgs = array())
    {

    }

    /**
     * Resets the working directory and/or the staging area and discards all changes
     */
    public function reset()
    {
    }

    /**
     * Adds one or more files to the staging area
     *
     * @param   array   $file       The file(s) to be added or NULL to add all new and/or changed files to the staging area
     * @param   boolean $force
     */
    public function add(array $file = null, $force = false)
    {

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

    }

    /**
     * Returns a string containing information about the given commit
     *
     * @param  string  $hash       The commit ref
     * @return  string
     */
    public function showCommit($hash)
    {

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

    }

    /**
     * Returns the current status of the working directory and the staging area
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
}

