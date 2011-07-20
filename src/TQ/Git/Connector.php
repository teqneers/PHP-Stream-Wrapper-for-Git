<?php
namespace TQ\Git;

class Connector
{
    /**
     *
     * @var string
     */
    protected $gitBinary;

    /**
     *
     * @return  string
     */
    public static function findGitBinary()
    {

    }

    /**
     *
     * @param   string|null $gitBinary
     */
    public function __construct($gitBinary = null)
    {
        if (!$gitBinary) {
            $gitBinary  = self::findGitBinary();
        }
        if (!is_string($gitBinary) || empty($gitBinary)) {
            throw new \InvalidArgumentException('No path to the Git binary found');
        }
        $this->gitBinary    = $gitBinary;
    }

    public function test() {
        return 1;
    }
}

