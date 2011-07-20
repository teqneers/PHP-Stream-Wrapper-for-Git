<?php
namespace TQ\Git;

class Connector
{
    /**
     *
     * @var GitBinary
     */
    protected $gitBinary;

    /**
     *
     * @param   GitBinary|null $gitBinary
     */
    public function __construct(GitBinary $gitBinary = null)
    {
        if (!$gitBinary) {
            $gitBinary  = new GitBinary();
        }
        $this->gitBinary    = $gitBinary;
    }

    public function test() {
        return 1;
    }
}

