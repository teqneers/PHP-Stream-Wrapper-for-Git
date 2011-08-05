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

class CallResult
{
    /**
     *
     * @var string
     */
    protected $stdOut;
    /**
     *
     * @var string
     */
    protected $stdErr;
    /**
     *
     * @var integer
     */
    protected $returnCode;

    /**
     *
     * @var Call
     */
    protected $cliCall;

    /**
     *
     * @param Call       $cliCall
     * @param string     $stdOut
     * @param string     $stdErr
     * @param integer    $returnCode
     */
    public function __construct(Call $cliCall, $stdOut, $stdErr, $returnCode)
    {
        $this->cliCall      = $cliCall;
        $this->stdOut       = trim((string)$stdOut);
        $this->stdErr       = trim((string)$stdErr);
        $this->returnCode   = (int)$returnCode;
    }

    /**
     *
     * @return Call
     */
    public function cliCall()
    {
        return $this->cliCall;
    }

    /**
     *
     * @return string
     */
    public function getStdOut()
    {
        return $this->stdOut;
    }

    /**
     *
     * @return  boolean
     */
    public function hasStdOut()
    {
        return !empty($this->stdOut);
    }

    /**
     *
     * @return string
     */
    public function getStdErr()
    {
        return $this->stdErr;
    }

    /**
     *
     * @return  boolean
     */
    public function hasStdErr()
    {
        return !empty($this->stdErr);
    }

    /**
     *
     * @return integer
     */
    public function getReturnCode()
    {
        return $this->returnCode;
    }
}

