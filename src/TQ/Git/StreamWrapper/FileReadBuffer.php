<?php
namespace TQ\Git\StreamWrapper;

class FileReadBuffer
{
    /**
     *
     * @var string
     */
    protected $buffer;

    /**
     *
     * @var integer
     */
    protected $length;

    /**
     *
     * @var integer
     */
    protected $position;

    /**
     *
     * @param   string  $content
     */
    public function __construct($buffer)
    {
        $this->buffer   = $buffer;
        $this->length   = strlen($buffer);
        $this->position = 0;
    }

    /**
     *
     * @return  boolean
     */
    public function isEof()
    {
        return ($this->position >= $this->length);
    }

    /**
     *
     * @param   integer     $count
     * @return  string|null
     */
    public function read($count)
    {
        if ($this->isEof()) {
            return null;
        }
        $buffer         = substr($this->buffer, $this->position, $count);
        $this->position += $count;
        return $buffer;
    }

    /**
     *
     * @return integer
     */
    public function getPosition()
    {
        return $this->position;
    }

    /**
     *
     * @param   integer $position
     * @param   integer  $whence
     * @return  boolean
     */
    public function setPosition($position, $whence)
    {
        switch ($whence) {
            case SEEK_SET:
                $this->position    = $position;
                break;
            case SEEK_CUR:
                $this->position    += $position;
                break;
            case SEEK_END:
                $this->position    = $this->length + $position;
                break;
            default:
                return false;
        }

        if ($this->position < 0) {
            $this->position    = 0;
            return false;
        } else if ($this->position > $this->length) {
            $this->position    = $this->length;
            return false;
        } else {
            return true;
        }
    }
}