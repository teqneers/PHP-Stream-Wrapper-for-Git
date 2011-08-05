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
 
namespace TQ\Git\StreamWrapper;

class FileBuffer
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
     * @return  string
     */
    public function getBuffer()
    {
        return $this->buffer;
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
     * @param   string  $data
     * @return  integer
     */
    public function write($data)
    {
        $dataLength     = strlen($data);
        $start          = substr($this->buffer, 0, $this->position);
        $end            = substr($this->buffer, $this->position + $dataLength);
        $this->buffer   = $start.$data.$end;
        return $dataLength;
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