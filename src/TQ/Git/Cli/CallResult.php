<?php
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
     * @return string
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

