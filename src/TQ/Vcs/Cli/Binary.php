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
 * @subpackage Cli
 * @copyright  Copyright (C) 2011 by TEQneers GmbH & Co. KG
 */

/**
 * @namespace
 */
namespace TQ\Vcs\Cli;

/**
 * Encapsulates access to the a VCS command line binary
 *
 * @author     Stefan Gehrig <gehrigteqneers.de>
 * @category   TQ
 * @package    TQ_Git
 * @subpackage Cli
 * @copyright  Copyright (C) 2011 by TEQneers GmbH & Co. KG
 */
abstract class Binary
{
    /**
     * The file system path to a VCS binary
     *
     * @var string
     */
    protected $path;

    /**
     * Ensures that the given arguments is a valid VCS binary
     *
     * @param   Binary|string|null          $binary     The VCS binary
     * @return  static
     * @throws  \InvalidArgumentException               If $binary is not a valid VCS binary
     */
    public static function ensure($binary)
    {
        if ($binary === null || is_string($binary)) {
            $binary  = new static($binary);
        }
        if (!($binary instanceof static)) {
            throw new \InvalidArgumentException(
                sprintf(
                    'The $binary argument must either be a TQ\Vcs\Binary
                    instance or a path to the VCS binary (%s given)',
                    (is_object($binary)) ? get_class($binary) : gettype($binary)
                )
            );
        }
        return $binary;
    }


    /**
     * Checks if the current system is Windows
     *
     * @return  boolean     True if we're on a Windows machine
     */
    protected static function isWindows()
    {
        return (strpos(PHP_OS, 'WIN') !== false);
    }

    /**
     * Creates a VCS binary interface
     *
     * @param   string   $path              The path to the VCS binary
     * @throws  \InvalidArgumentException   If no VCS binary is found
     */
    public function __construct($path)
    {
        if (!is_string($path) || empty($path)) {
            throw new \InvalidArgumentException('No path to the VCS binary found');
        }
        $this->path    = $path;
    }

    /**
     * Create a call to the VCS binary for later execution
     *
     * @param   string  $path           The full path to the VCS repository
     * @param   string  $command        The VCS command, e.g. show, commit or add
     * @param   array   $arguments      The command arguments
     * @return  Call
     */
    public function createCall($path, $command, array $arguments)
    {
        if (!self::isWindows()) {
            $binary = escapeshellcmd($this->path);
        } else {
            $binary = $this->path;
        }
        if (!empty($command)) {
            $command    = escapeshellarg($command);
        }

        list($args, $files) = $this->sanitizeCommandArguments($arguments);
        $cmd                = $this->createCallCommand($binary, $command, $args, $files);
        $call               = $this->doCreateCall($cmd, $path);
        return $call;
    }

    /**
     * The call factory
     *
     * @param   string  $cmd        The command string to be executed
     * @param   string  $path       The working directory
     * @return  Call
     */
    protected function doCreateCall($cmd, $path)
    {
        return Call::create($cmd, $path);
    }

    /**
     * Creates the command string to be executed
     *
     * @param   string      $binary     The path to the binary
     * @param   string      $command    The VCS command
     * @param   array       $args       The list of command line arguments (sanitized)
     * @param   array       $files      The list of files to be added to the command line call
     * @return  string                  The command string to be executed
     */
    protected function createCallCommand($binary, $command, array $args, array $files)
    {
        $cmd    = trim(sprintf('%s %s %s', $binary, $command, implode(' ', $args)));
        if (count($files) > 0) {
            $cmd    .= ' -- '.implode(' ', $files);
        }
        return $cmd;
    }

    /**
     * Sanitizes a command line argument
     *
     * @param   string      $key        The argument key
     * @param   string      $value      The argument value (can be empty)
     * @return  string
     */
    protected function sanitizeCommandArgument($key, $value)
    {
        $key  = ltrim($key, '-');
        if (strlen($key) == 1 || is_numeric($key)) {
            $arg = sprintf('-%s', escapeshellarg($key));
            if ($value !== null) {
                $arg    .= ' '.escapeshellarg($value);
            }
        } else {
            $arg = sprintf('--%s', escapeshellarg($key));
            if ($value !== null) {
                $arg    .= '='.escapeshellarg($value);
            }
        }
        return $arg;
    }

    /**
     * Sanitizes a list of command line arguments and splits them into args and files
     *
     * @param   array       $arguments      The list of arguments
     * @return  array                       An array with (args, files)
     */
    protected function sanitizeCommandArguments(array $arguments)
    {
        $args       = array();
        $files      = array();
        $fileMode   = false;
        foreach ($arguments as $k => $v) {
            if ($v === '--' || $k === '--') {
                $fileMode   = true;
                continue;
            }
            if (is_int($k)) {
                if (strpos($v, '-') === 0) {
                    $args[]  = $this->sanitizeCommandArgument($v, null);
                } else if ($fileMode) {
                    $files[] = escapeshellarg($v);
                } else {
                    $args[]  = escapeshellarg($v);
                }
            } else {
                if (strpos($k, '-') === 0) {
                    $args[] = $this->sanitizeCommandArgument($k, $v);
                }
            }
        }
        return array($args, $files);
    }

    /**
     * Extracts the CLI call parameters from the arguments to a magic method call
     *
     * @param   string  $method             The VCS command, e.g. show, commit or add
     * @param   array   $arguments          The command arguments with the path to the VCS
     *                                      repository being the first argument
     * @return  array                       An array with (path, method, args, stdIn)
     * @throws \InvalidArgumentException    If the method is called with less than one argument
     */
    protected function extractCallParametersFromMagicCall($method, array $arguments)
    {
        if (count($arguments) < 1) {
            throw new \InvalidArgumentException(sprintf(
                '"%s" must be called with at least one argument denoting the path', $method
            ));
        }

        $path   = array_shift($arguments);
        $args   = array();
        $stdIn  = null;

        if (count($arguments) > 0) {
            $args   = array_shift($arguments);
            if (!is_array($args)) {
                $args   = array($args);
            }

            if (count($arguments) > 0) {
                $stdIn  = array_shift($arguments);
                if (!is_string($stdIn)) {
                    $stdIn   = null;
                }
            }
        }
        return array($path, $method, $args, $stdIn);
    }

    /**
     * Method overloading - allows calling VCS commands directly as class methods
     *
     * @param   string  $method             The VCS command, e.g. show, commit or add
     * @param   array   $arguments          The command arguments with the path to the VCS
     *                                      repository being the first argument
     * @return  CallResult
     */
    public function __call($method, array $arguments)
    {
        list($path, $method, $args, $stdIn) = $this->extractCallParametersFromMagicCall($method, $arguments);

        $call   = $this->createCall($path, $method, $args);
        return $call->execute($stdIn);
    }
}

