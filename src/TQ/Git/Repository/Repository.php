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
 * @subpackage Git
 * @copyright  Copyright (C) 2017 by TEQneers GmbH & Co. KG
 */

namespace TQ\Git\Repository;
use TQ\Vcs\FileSystem;
use TQ\Vcs\Repository\AbstractRepository;
use TQ\Git\Cli\Binary;
use TQ\Vcs\Cli\CallResult;

/**
 * Provides access to a Git repository
 *
 * @uses       TQ\Git\Cli\Binary
 * @author     Stefan Gehrig <gehrigteqneers.de>
 * @category   TQ
 * @package    TQ_VCS
 * @subpackage Git
 * @copyright  Copyright (C) 2017 by TEQneers GmbH & Co. KG
 */
class Repository extends AbstractRepository
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
    protected $git;

        /**
     * Opens a Git repository on the file system, optionally creates and initializes a new repository
     *
     * @param   string               $repositoryPath        The full path to the repository
     * @param   Binary|string|null   $git                   The Git binary
     * @param   boolean|integer      $createIfNotExists     False to fail on non-existing repositories, directory
     *                                                      creation mode, such as 0755  if the command
     *                                                      should create the directory and init the repository instead
     * @param   array|null           $initArguments         Arguments to be passed to git-init if initializing a
     *                                                      repository
     * @param   boolean              $findRepositoryRoot    False to use the repository path as the root directory.
     *
     * @return  Repository
     * @throws  \RuntimeException                       If the path cannot be created
     * @throws  \InvalidArgumentException               If the path is not valid or if it's not a valid Git repository
     */
    public static function open($repositoryPath, $git = null, $createIfNotExists = false, $initArguments = null, $findRepositoryRoot = true)
    {
        $git = Binary::ensure($git);
        $repositoryRoot = null;

        if (!is_string($repositoryPath)) {
            throw new \InvalidArgumentException(sprintf(
                '"%s" is not a valid path', $repositoryPath
            ));
        }

        if ($findRepositoryRoot) {
            $repositoryRoot = self::findRepositoryRoot($repositoryPath);
        }

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
                self::initRepository($git, $repositoryPath, $initArguments);
                $repositoryRoot = $repositoryPath;
            }
        }

        if ($repositoryRoot === null) {
            throw new \InvalidArgumentException(sprintf(
                '"%s" is not a valid Git repository', $repositoryPath
            ));
        }

        return new static($repositoryRoot, $git);
    }

    /**
     * Initializes a path to be used as a Git repository
     *
     * @param   Binary   $git           The Git binary
     * @param   string   $path          The repository path
     * @param   array    $initArguments Arguments to pass to git-init when initializing the repository
     */
    protected static function initRepository(Binary $git, $path, $initArguments = null)
    {
        $initArguments = $initArguments ?: Array();

        /** @var $result CallResult */
        $result = $git->{'init'}($path, $initArguments);
        $result->assertSuccess(sprintf('Cannot initialize a Git repository in "%s"', $path));
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
            $gitDir = $p.'/'.'.git';
            return file_exists($gitDir) && is_dir($gitDir);
        });
    }

    /**
     * Creates a new repository instance - use {@see open()} instead
     *
     * @param   string     $repositoryPath
     * @param   Binary     $git
     */
    protected function __construct($repositoryPath, Binary $git)
    {
        $this->git   = $git;
        parent::__construct($repositoryPath);
    }

    /**
     * Returns the Git binary
     *
     * @return  Binary
     */
    public function getGit()
    {
        return $this->git;
    }

    /**
     * Returns the current commit hash
     *
     * @return  string
     */
    public function getCurrentCommit()
    {
        /** @var $result CallResult */
        $result = $this->getGit()->{'rev-parse'}($this->getRepositoryPath(), array(
             '--verify',
            'HEAD'
        ));
        $result->assertSuccess(sprintf('Cannot rev-parse "%s"', $this->getRepositoryPath()));
        return $result->getStdOut();
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
            $args['--author']  = $author;
        }

        foreach($extraArgs as $value) {
           $args[] = $value;
        }

        if ($file !== null) {
            $args[] = '--';
            $args   = array_merge($args, $this->resolveLocalPath($file));
        }

        /** @var $result CallResult */
        $result = $this->getGit()->{'commit'}($this->getRepositoryPath(), $args);
        $result->assertSuccess(sprintf('Cannot commit to "%s"', $this->getRepositoryPath()));
    }

    /**
     * Resets the working directory and/or the staging area and discards all changes
     *
     * @param   integer     $what       Bit mask to indicate which parts should be reset
     */
    public function reset($what = self::RESET_ALL)
    {
        $what   = (int)$what;
        if (($what & self::RESET_STAGED) == self::RESET_STAGED) {
            /** @var $result CallResult */
            $result = $this->getGit()->{'reset'}($this->getRepositoryPath(), array('--hard'));
            $result->assertSuccess(sprintf('Cannot reset "%s"', $this->getRepositoryPath()));
        }

        if (($what & self::RESET_WORKING) == self::RESET_WORKING) {
            /** @var $result CallResult */
            $result = $this->getGit()->{'clean'}($this->getRepositoryPath(), array(
                '--force',
                '-x',
                '-d'
            ));
            $result->assertSuccess(sprintf('Cannot clean "%s"', $this->getRepositoryPath()));
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

        /** @var $result CallResult */
        $result = $this->getGit()->{'add'}($this->getRepositoryPath(), $args);
        $result->assertSuccess(sprintf('Cannot add "%s" to "%s"',
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

        /** @var $result CallResult */
        $result = $this->getGit()->{'rm'}($this->getRepositoryPath(), $args);
        $result->assertSuccess(sprintf('Cannot remove "%s" from "%s"',
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

        /** @var $result CallResult */
        $result = $this->getGit()->{'mv'}($this->getRepositoryPath(), $args);
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

        $this->commit($commitMsg, array($file), $author);

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
    public function createDirectory($path, $commitMsg = null, $dirMode = null, $recursive = true, $author = null)
    {
        if ($commitMsg === null) {
            $commitMsg  = sprintf('%s created directory "%s"', __CLASS__, $path);
        }
        return $this->writeFile($path.'/.gitkeep', '', $commitMsg, 0666, $dirMode, $recursive, $author);
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
     * Prepares a list of named arguments for use as command-line arguments.
     * Preserves ordering, while prepending - and -- to argument names, then leaves value alone.
     *
     * @param   array           $namedArguments    Named argument list to format
     * @return  array
     **/
    protected function _prepareNamedArgumentsForCLI($namedArguments) {
        $filteredArguments = array();
        $doneParsing = false;

        foreach ($namedArguments as $name => $value) {
            if ($value === false) {
                continue;
            }

            if (is_integer($name)) {
                $name = $value;
                $noValue = true;
            } elseif (is_bool($value)) {
                $noValue = true;
            } elseif (is_null($value)) {
                continue;
            } else {
                $noValue = false;
            }

            if ($name == '--') {
                $doneParsing = true;
            }

            if (!$doneParsing) {
                $name = preg_replace('{^(\w|\d+)$}', '-$0', $name);
                $name = preg_replace('{^[^-]}', '--$0', $name);
            }

            if ($noValue) {
                $filteredArguments[] = $name;
                continue;
            }

            $filteredArguments[$name] = $value;
        }

        return $filteredArguments;
    }

    /**
     * _parseNamedArguments
     *
     * Takes a set of regular arguments and a set of extended/named arguments, combines them, and returns the results.
     *
     * The merging method is far from foolproof, but should take care of the vast majority of situations.  Where it fails is function calls
     * in which the an argument is regular-style, is an array, and only has keys which are present in the named arguments.
     *
     * The easy way to trigger it would be to pass an empty array in one of the arguments.
     *
     * There's a bunch of array_splices.  Those are in place so that if named arguments have orders that they should be called in,
     * they're not disturbed.  So... calling with
     *      getLog(5, ['reverse', 'diff' => 'git', 'path/to/repo/file.txt']
     * will keep things in the order for the git call:
     *      git-log --limit=5 --skip=10 --reverse --diff=git path/to/to/repo/file.txt
     * and will put defaults at the beginning of the call, as well.
     *
     * @param   array $regularStyleArguments     An ordered list of the names of regular-style arguments that should be accepted.
     * @param   array $namedStyleArguments       An associative array of named arguments to their default value,
     *                                                 or null where no default is desired.
     * @param   array $arguments                 The result of func_get_args() in the original function call we're helping.
     * @param   int   $skipNamedTo               Index to which array arguments should be assumed NOT to be named arguments.
     * @return  array                            A filtered associative array of the resulting arguments.
     */
    protected function _parseNamedArguments($regularStyleArguments, $namedStyleArguments, $arguments, $skipNamedTo = 0) {
        $namedArguments = array();

        foreach ($regularStyleArguments as $name) {
            if (!isset($namedStyleArguments[$name])) {
                $namedStyleArguments[$name] = null;
            }
        }

        // We'll just step through the arguments and depending on whether the keys and values look appropriate, decide if they
        // are named arguments or regular arguments.
        foreach ($arguments as $index => $argument) {
            // If it's a named argument, we'll keep the whole thing.
            // Also keeps extra numbered arguments inside the named argument structure since they probably have special significance.
            if (is_array($argument) && $index >= $skipNamedTo) {
                $diff = array_diff_key($argument, $namedStyleArguments);
                $diffKeys = array_keys($diff);

                $integerDiffKeys = array_filter($diffKeys, 'is_int');
                $diffOnlyHasIntegerKeys = (count($diffKeys) === count($integerDiffKeys));

                if (empty($diff) || $diffOnlyHasIntegerKeys) {
                    $namedArguments = array_merge($namedArguments, $argument);
                    continue;
                }

                throw new \InvalidArgumentException('Unexpected named argument key: ' . implode(', ', $diffKeys));
            }

            if (empty($regularStyleArguments[$index])) {
                throw new \InvalidArgumentException("The argument parser received too many arguments!");
            }

            $name = $regularStyleArguments[$index];
            $namedArguments[$name] = $argument;
        }

        $defaultArguments = array_filter($namedStyleArguments,
            function($value) { return !is_null($value); }
        );

        // Insert defaults (for arguments that have no value) at the beginning
        $defaultArguments = array_diff_key($defaultArguments, $namedArguments);
        $namedArguments = array_merge($defaultArguments, $namedArguments);

        return $namedArguments;
    }

    /**
     * Returns the current repository log
     *
     * @param   integer|null    $limit      The maximum number of log entries returned
     * @param   integer|null    $skip       Number of log entries that are skipped from the beginning
     * @return  array
     */
    public function getLog($limit = null, $skip = null)
    {
        $regularStyleArguments = array(
            'limit',
            'skip'
        );

        $namedStyleArguments = array(
            'abbrev' => null,
            'abbrev-commit' => null,
            'after' => null,
            'all' => null,
            'all-match' => null,
            'ancestry-path' => null,
            'author' => null,
            'basic-regexp' => null,
            'before' => null,
            'binary' => null,
            'bisect' => null,
            'boundary' => null,
            'branches' => null,
            'break-rewrites' => null,
            'cc' => null,
            'check' => null,
            'cherry' => null,
            'cherry-mark' => null,
            'cherry-pick' => null,
            'children' => null,
            'color' => null,
            'color-words' => null,
            'combined' => null,
            'committer' => null,
            'date' => null,
            'date-order' => null,
            'decorate' => null,
            'dense' => null,
            'diff-filter' => null,
            'dirstat' => null,
            'do-walk' => null,
            'dst-prefix' => null,
            'encoding' => null,
            'exit-code' => null,
            'ext-diff' => null,
            'extended-regexp' => null,
            'find-copies' => null,
            'find-copies-harder' => null,
            'find-renames' => null,
            'first-parent' => null,
            'fixed-strings' => null,
            'follow' => null,
            'format' => 'fuller',
            'full-diff' => null,
            'full-history' => null,
            'full-index' => null,
            'function-context' => null,
            'glob' => null,
            'graph' => null,
            'grep' => null,
            'grep-reflog' => null,
            'histogram' => null,
            'ignore-all-space' => null,
            'ignore-missing' => null,
            'ignore-space-at-eol' => null,
            'ignore-space-change' => null,
            'ignore-submodules' => null,
            'inter-hunk-context' => null,
            'irreversible-delete' => null,
            'left-only' => null,
            'left-right' => null,
            'log-size' => null,
            'max-count' => null,
            'max-parents' => null,
            'merge' => null,
            'merges' => null,
            'min-parents' => null,
            'minimal' => null,
            'name-only' => null,
            'name-status' => null,
            'no-abbrev' => null,
            'no-abbrev-commit' => null,
            'no-color' => null,
            'no-decorate' => null,
            'no-ext-diff' => null,
            'no-max-parents' => null,
            'no-merges' => null,
            'no-min-parents' => null,
            'no-notes' => null,
            'no-prefix' => null,
            'no-renames' => null,
            'no-textconv' => null,
            'no-walk' => null,
            'not' => null,
            'notes' => null,
            'numstat' => null,
            'objects' => null,
            'objects-edge' => null,
            'oneline' => null,
            'parents' => null,
            'patch' => null,
            'patch-with-raw' => null,
            'patch-with-stat' => null,
            'patience' => null,
            'perl-regexp' => null,
            'pickaxe-all' => null,
            'pickaxe-regex' => null,
            'pretty' => null,
            'raw' => null,
            'regexp-ignore-case' => null,
            'relative' => null,
            'relative-date' => null,
            'remotes' => null,
            'remove-empty' => null,
            'reverse' => null,
            'right-only' => null,
            'shortstat' => null,
            'show-notes' => null,
            'show-signature' => null,
            'simplify-by-decoration' => null,
            'simplify-merges' => null,
            'since' => null,
            'skip' => null,
            'source' => null,
            'sparse' => null,
            'src-prefix' => null,
            'stat' => null,
            'stat-count' => null,
            'stat-graph-width' => null,
            'stat-name-width' => null,
            'stat-width' => null,
            'stdin' => null,
            'submodule' => null,
            'summary' => true,
            'tags' => null,
            'text' => null,
            'textconv' => null,
            'topo-order' => null,
            'unified' => null,
            'unpacked' => null,
            'until' => null,
            'verify' => null,
            'walk-reflogs' => null,
            'word-diff' => null,
            'word-diff-regex' => null,
            'z' => true
        );

        $arguments = func_get_args();

        $arguments = $this->_parseNamedArguments($regularStyleArguments, $namedStyleArguments, $arguments);

        if (!empty($arguments['limit'])) {
            $limit = '' . (int) $arguments['limit'];

            unset($arguments['limit']);
            array_unshift($arguments, $limit);
        }

        $arguments = $this->_prepareNamedArgumentsForCLI($arguments);

        /** @var $result CallResult */
        $result = $this->getGit()->{'log'}($this->getRepositoryPath(), $arguments);
        $result->assertSuccess(sprintf('Cannot retrieve log from "%s"',
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
     * @param  string  $hash       The commit ref
     * @return  string
     */
    public function showCommit($hash)
    {
        /** @var $result CallResult */
        $result = $this->getGit()->{'show'}($this->getRepositoryPath(), array(
            '--format' => 'fuller',
            $hash
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
        $result = $this->getGit()->{'show'}($this->getRepositoryPath(), array(
            sprintf('%s:%s', $ref, $file)
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
     */
    public function getObjectInfo($path, $ref = 'HEAD')
    {
        $info   = array(
            'type'  => null,
            'mode'  => 0,
            'size'  => 0
        );

        /** @var $result CallResult */
        $result = $this->getGit()->{'cat-file'}($this->getRepositoryPath(), array(
            '--batch-check'
        ), sprintf('%s:%s', $ref, $path));
        $result->assertSuccess(sprintf('Cannot cat-file "%s" at "%s" from "%s"',
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
        $directory  = FileSystem::normalizeDirectorySeparator($directory);
        $directory  = $this->resolveLocalPath(rtrim($directory, '/') . '/');
        $path       = $this->getRepositoryPath();

        /** @var $result CallResult */
        $result = $this->getGit()->{'ls-tree'}($path, array(
            '--name-only',
            '--full-name',
            '-z',
            $ref,
            $directory
        ));
        $result->assertSuccess(sprintf('Cannot list directory "%s" at "%s" from "%s"',
            $directory, $ref, $this->getRepositoryPath()
        ));

        $output     = $result->getStdOut();
        $listing    = array_map(function($f) use ($directory) {
            return str_replace($directory, '', trim($f));
        }, explode("\x0", $output));
        return $listing;
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
        /** @var $result CallResult */
        $result = $this->getGit()->{'status'}($this->getRepositoryPath(), array(
            '--short'
        ));
        $result->assertSuccess(
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
     * Returns the diff of a file
     *
     * @param   string  $file       The path to the file
     * @param   bool    $staged     Should the diff return for the staged file
     * @return  string[]
     */
    public function getDiff(array $files = null, $staged = false)
    {
        $diffs = array();

        if (is_null($files)) {
            $files    = array();
            $status   = $this->getStatus();
            $modified = ($staged ? 'x' : 'y');

            foreach ($status as $entry) {
               if ($entry[$modified] !== 'M') {
                        continue;
                }

                $files[] = $entry['file'];
            }
        }

        $files = array_map(array($this, 'resolveLocalPath'), $files);

        foreach ($files as $file) {
            $args = array();

            if ($staged) {
                $args[] = '--staged';
            }

            $args[] = $file;

            $result = $this->getGit()->{'diff'}($this->getRepositoryPath(), $args);
            $result->assertSuccess(sprintf('Cannot show diff for %s from "%s"',
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
     * Returns the name of the current branch
     *
     * @return  string
     */
    public function getCurrentBranch()
    {
        /** @var $result CallResult */
        $result = $this->getGit()->{'rev-parse'}($this->getRepositoryPath(), array(
            '--symbolic-full-name',
            '--abbrev-ref',
            'HEAD'
        ));
        $result->assertSuccess(
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

        /** @var $result CallResult */
        $result = $this->getGit()->{'branch'}($this->getRepositoryPath(), $arguments);
        $result->assertSuccess(
            sprintf('Cannot retrieve branch from "%s"', $this->getRepositoryPath())
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
            $line   = ltrim($line);
            return $line;
        }, explode("\n", $output));
        return $branches;
    }

    /**
     * Returns the remote info
     *
     * @return  array
     */
    public function getCurrentRemote()
    {
        /** @var $result CallResult */
        $result = $this->getGit()->{'remote'}($this->getRepositoryPath(), array(
             '-v'
        ));
        $result->assertSuccess(sprintf('Cannot remote "%s"', $this->getRepositoryPath()));

        $tmp = $result->getStdOut();

        preg_match_all('/([a-z]*)\h(.*)\h\((.*)\)/', $tmp, $matches);

        $retVar = array();
        foreach($matches[0] as $key => $value)
            $retVar[$matches[1][$key]][$matches[3][$key]] = $matches[2][$key];

        return $retVar;
    }

    /**
     * Resolves an absolute path into a path relative to the repository path
     *
     * @param   string|array  $path         A file system path (or an array of paths)
     * @return  string|array                Either a single path or an array of paths is returned
     */
    public function resolveLocalPath($path)
    {
        $path = parent::resolveLocalPath($path);
        if ($path === '') {
            $path = '.';
        }
        return $path;
    }
}
