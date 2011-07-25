<?php
namespace TQ\Git;

use TQ\Git\Cli;

class GitCallException extends \RuntimeException implements Exception
{
    /**
     *
     * @var Cli\CallResult
     */
    protected $cliCallResult;

    /**
     *
     * @param string            $message
     * @param Cli\CallResult    $cliCallResult
     */
    public function __construct($message, Cli\CallResult $cliCallResult)
    {
        $this->cliCallResult    = $cliCallResult;

        parent::__construct(
            sprintf($message.' [%s]', $cliCallResult->getStdErr()),
            $cliCallResult->getReturnCode()
        );
    }
}