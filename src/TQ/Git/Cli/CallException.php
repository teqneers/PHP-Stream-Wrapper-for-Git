<?php
namespace TQ\Git\Cli;

class CallException extends \RuntimeException implements Exception
{
    /**
     *
     * @var CallResult
     */
    protected $cliCallResult;

    /**
     *
     * @param string        $message
     * @param CallResult    $cliCallResult
     */
    public function __construct($message, CallResult $cliCallResult)
    {
        $this->cliCallResult    = $cliCallResult;

        parent::__construct(
            sprintf($message.' [%s]', $cliCallResult->getStdErr()),
            $cliCallResult->getReturnCode()
        );
    }
}