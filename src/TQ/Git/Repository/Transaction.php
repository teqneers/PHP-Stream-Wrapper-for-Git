<?php
namespace TQ\Git\Repository;

class Transaction
{
    /**
     *
     * @var Repository
     */
    protected $repository;

    /**
     *
     * @var string|null
     */
    protected $commitMsg;

    /**
     *
     * @var mixed
     */
    protected $result;

    /**
     *
     * @var string|null
     */
    protected $commitHash;

    /**
     *
     * @param   Repository  $binary
     */
    public function __construct(Repository $repository)
    {
        $this->repository   = $repository;
    }

    /**
     *
     * @return  Repository
     */
    public function getRepository()
    {
        return $this->repository;
    }

    /**
     *
     * @return  string
     */
    public function getRepositoryPath()
    {
        return $this->getRepository()->getRepositoryPath();
    }

    /**
     *
     * @return  string|null
     */
    public function getCommitMsg()
    {
        return $this->commitMsg;
    }

    /**
     *
     * @param   string|null $commitMsg
     */
    public function setCommitMsg($commitMsg)
    {
        if ($commitMsg === null) {
            $this->commitMsg    = null;
        } else {
            $this->commitMsg    = (string)$commitMsg;
        }
    }

    /**
     *
     * @return  mixed
     */
    public function getResult()
    {
        return $this->result;
    }

    /**
     *
     * @param   mixed $result
     */
    public function setResult($result)
    {
        $this->result   = $result;
    }

    /**
     *
     * @return  string|null
     */
    public function getCommitHash()
    {
        return $this->commitHash;
    }

    /**
     *
     * @param   string $result
     */
    public function setCommitHash($commitHash)
    {
        $this->commitHash   = $commitHash;
    }
}