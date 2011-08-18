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
 * The result of a CLI call - provides access to stdout, stderror and the return code
 *
 * @author     Stefan Gehrig <gehrigteqneers.de>
 * @category   TQ
 * @package    TQ_Git
 * @subpackage Cli
 * @copyright  Copyright (C) 2011 by TEQneers GmbH & Co. KG
 */
class CallResult
{
    /**
     * The contents of stdout
     *
     * @var string
     */
    protected $stdOut;

    /**
     * The contents of stderr
     *
     * @var string
     */
    protected $stdErr;

    /**
     * The return code
     *
     * @var integer
     */
    protected $returnCode;

    /**
     * Reference to the call that resulted in this result
     *
     * @var Call
     */
    protected $cliCall;

    /**
     * Creates a new result container for a CLI call
     *
     * @param Call       $cliCall       Reference to the call that resulted in this result
     * @param string     $stdOut        The contents of stdout
     * @param string     $stdErr        The contents of stderr
     * @param integer    $returnCode    The return code
     */
    public function __construct(Call $cliCall, $stdOut, $stdErr, $returnCode)
    {
        $this->cliCall      = $cliCall;
        $this->stdOut       = rtrim((string)$stdOut);
        $this->stdErr       = rtrim((string)$stdErr);
        $this->returnCode   = (int)$returnCode;
    }

    /**
     * Returns the reference to the call that resulted in this result
     *
     * @return Call
     */
    public function getCliCall()
    {
        return $this->cliCall;
    }

    /**
     * Returns the contents of stdout
     *
     * @return string
     */
    public function getStdOut()
    {
        return $this->stdOut;
    }

    /**
     * Returns true if the call resulted in stdout to be populated
     *
     * @return  boolean
     */
    public function hasStdOut()
    {
        return !empty($this->stdOut);
    }

    /**
     * Returns the contents of stderr
     *
     * @return string
     */
    public function getStdErr()
    {
        return $this->stdErr;
    }

    /**
     * Returns true if the call resulted in stderr to be populated
     *
     * @return  boolean
     */
    public function hasStdErr()
    {
        return !empty($this->stdErr);
    }

    /**
     * Returns the return code
     *
     * @return integer
     */
    public function getReturnCode()
    {
        return $this->returnCode;
    }
}

