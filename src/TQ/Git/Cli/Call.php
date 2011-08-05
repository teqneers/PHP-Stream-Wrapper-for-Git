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
 
namespace TQ\Git\Cli;

class Call
{
    /**
     *
     * @var string
     */
    protected $cmd;

    /**
     *
     * @var string|null
     */
    protected $cwd;

    /**
     *
     * @var array|null
     */
    protected $env;

    /**
     *
     * @param   string      $cmd
     * @param   string|null $cwd
     * @param   array|null  $env
     * @return  Call
     */
    public static function create($cmd, $cwd = null, array $env = null) {
        return new static($cmd, $cwd, $env);
    }

    /**
     *
     * @param   string      $cmd
     * @param   string|null $cwd
     * @param   array|null  $env
     */
    public function __construct($cmd, $cwd = null, array $env = null)
    {
        $this->setCmd($cmd);
        $this->setCwd($cwd);
        $this->setEnv($env);
    }

    /**
     *
     * @return  string
     */
    public function getCmd()
    {
        return $this->cmd;
    }

    /**
     *
     * @param   string  $cmd
     * @return  Call
     */
    public function setCmd($cmd)
    {
        $this->cmd  = (string)$cmd;
        return $this;
    }

    /**
     *
     * @return  string|null
     */
    public function getCwd()
    {
        return $this->cwd;
    }

    /**
     *
     * @param   string|null  $cwd
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
     *
     * @return  array|null
     */
    public function getEnv()
    {
        return $this->env;
    }

    /**
     *
     * @param   array|null  $env
     * @return  Call
     */
    public function setEnv(array $env = null)
    {
        $this->env  = $env;
        return $this;
    }

    /**
     *
     * @param   string|null  $stdIn
     * @return  CallResult
     */
    public function execute($stdIn = null)
    {
        $descriptorspec = array(
           0 => array("pipe", "r"), // stdin is a pipe that the child will read from
           1 => array("pipe", "w"), // stdout is a pipe that the child will write to
           2 => array("pipe", "w")  // stderr is a pipe that the child will write to
        );
        $pipes   = array();
        $process = proc_open(
            $this->getCmd(),
            $descriptorspec,
            $pipes,
            $this->getCwd(),
            $this->getEnv()
        );

        if (is_resource($process)) {
            if ($stdIn !== null) {
                fwrite($pipes[0], (string)$stdIn);
            }
            fclose($pipes[0]);

            $stdOut = stream_get_contents($pipes[1]);
            fclose($pipes[1]);
            $stdErr = stream_get_contents($pipes[2]);
            fclose($pipes[2]);

            $returnCode = proc_close($process);
            return new CallResult($this, $stdOut, $stdErr, $returnCode);
        }
        throw new \RuntimeException(sprintf('Cannot execute "%s"', $cmd));
    }
}

