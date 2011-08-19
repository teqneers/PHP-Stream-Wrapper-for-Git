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
 * @subpackage Cli
 * @copyright  Copyright (C) 2011 by TEQneers GmbH & Co. KG
 */

/**
 * @namespace
 */
namespace TQ\Git\Cli;

/**
 * Represents a single CLI call
 *
 * @author     Stefan Gehrig <gehrigteqneers.de>
 * @category   TQ
 * @package    TQ_Git
 * @subpackage Cli
 * @copyright  Copyright (C) 2011 by TEQneers GmbH & Co. KG
 */
class Call
{
    /**
     * The CLI command to execute
     *
     * @var string
     */
    protected $cmd;

    /**
     * The working directory in which the call will be executed
     *
     * @var string|null
     */
    protected $cwd;

    /**
     * Environment variables - defaults to the current environment
     *
     * @var array|null
     */
    protected $env;

    /**
     * Factory method to create a call
     *
     * @param   string      $cmd    The CLI command to execute
     * @param   string|null $cwd    The working directory in which the call will be executed
     * @param   array|null  $env    Environment variables - defaults to the current environment
     * @return  Call
     */
    public static function create($cmd, $cwd = null, array $env = null) {
        return new static($cmd, $cwd, $env);
    }

    /**
     * Creates a new instance of a CLI call
     *
     * @param   string      $cmd    The CLI command to execute
     * @param   string|null $cwd    The working directory in which the call will be executed
     * @param   array|null  $env    Environment variables - defaults to the current environment
     */
    public function __construct($cmd, $cwd = null, array $env = null)
    {
        $this->setCmd($cmd);
        $this->setCwd($cwd);
        $this->setEnv($env);
    }

    /**
     * Returns the CLI command
     *
     * @return  string
     */
    public function getCmd()
    {
        return $this->cmd;
    }

    /**
     * Sets the CLI command
     *
     * @param   string  $cmd    The CLI command to execute
     * @return  Call
     */
    public function setCmd($cmd)
    {
        $this->cmd  = (string)$cmd;
        return $this;
    }

    /**
     * Returns the working directory for the call
     *
     * @return  string|null
     */
    public function getCwd()
    {
        return $this->cwd;
    }

    /**
     * Sets the working directory for the call
     *
     * @param   string|null  $cwd   The working directory in which the call will be executed
     * @return  Call
     */
    public function setCwd($cwd)
    {
        if (empty($cwd)) {
            $cwd    = null;
        } else {
            $cwd    = (string)$cwd;
        }
        $this->cwd  = $cwd;
        return $this;
    }

    /**
     * Returns the environment variables for the call - if overridden
     *
     * @return  array|null
     */
    public function getEnv()
    {
        return $this->env;
    }

    /**
     * Sets the environment variables for the call
     *
     * @param   array|null  $env    Environment variables - defaults to the current environment
     * @return  Call
     */
    public function setEnv(array $env = null)
    {
        $this->env  = $env;
        return $this;
    }

    /**
     * Executes the call usign the preconfigured command
     *
     * @param   string|null  $stdIn     Content that will be piped to the command
     * @return  CallResult
     */
    public function execute($stdIn = null)
    {
        $stdOut = fopen('php://temp', 'r');
        $stdErr = fopen('php://temp', 'r');

        $descriptorSpec = array(
           0 => array("pipe", "r"), // stdin is a pipe that the child will read from
           1 => $stdOut,            // stdout is a temp file that the child will write to
           2 => $stdErr             // stderr is a temp file that the child will write to
        );
        $pipes   = array();
        $process = proc_open(
            $this->getCmd(),
            $descriptorSpec,
            $pipes,
            $this->getCwd(),
            $this->getEnv()
        );

        if (is_resource($process)) {
            if ($stdIn !== null) {
                fwrite($pipes[0], (string)$stdIn);
            }
            fclose($pipes[0]);
            $returnCode = proc_close($process);
            return new CallResult($this, $stdOut, $stdErr, $returnCode);
        } else {
            fclose($stdOut);
            fclose($stdErr);
            throw new \RuntimeException(sprintf('Cannot execute "%s"', $cmd));
        }
    }
}

