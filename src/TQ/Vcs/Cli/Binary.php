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
                    'The $binary argument must either
                     be a TQ\Vcs\Binary instance or a path to the VCS binary (%s given)',
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
    abstract public function createCall($path, $command, array $arguments);

    /**
     * Method overloading - allows calling VCS commands directly as class methods
     *
     * @param   string  $method             The VCS command, e.g. show, commit or add
     * @param   array   $arguments          The command arguments with the path to the VCS
     *                                      repository being the first argument
     * @return  CallResult
     * @throws \InvalidArgumentException    If the method is called with less than one argument
     */
    public function __call($method, array $arguments)
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

        $call   = $this->createCall($path, $method, $args);
        return $call->execute($stdIn);
    }
}

