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
 * @subpackage StreamWrapper
 * @copyright  Copyright (C) 2011 by TEQneers GmbH & Co. KG
 */

/**
 * @namespace
 */
namespace TQ\Git\StreamWrapper;

/**
 * Encapsulates a file revision buffer to be used in the streamwrapper
 *
 * @author     Stefan Gehrig <gehrigteqneers.de>
 * @category   TQ
 * @package    TQ_Git
 * @subpackage StreamWrapper
 * @copyright  Copyright (C) 2011 by TEQneers GmbH & Co. KG
 */
class FileStringBuffer implements FileBuffer
{
    /**
     * The buffer contents
     *
     * @var string
     */
    protected $buffer;

    /**
     * The buffer length
     *
     * @var integer
     */
    protected $length;

    /**
     * The current pointer position
     *
     * @var integer
     */
    protected $position;

    /**
     * Creates a neww file buffer with the given contents
     *
     * @param   string  $content    The contents
     */
    public function __construct($buffer)
    {
        $this->buffer   = $buffer;
        $this->length   = strlen($buffer);
        $this->position = 0;
    }

    /**
     * Returns the complete contents of the buffer
     *
     * @return  string
     */
    public function getBuffer()
    {
        return $this->buffer;
    }

    /**
     * Returns true if the pointer is at the end of the buffer
     *
     * @return  boolean
     */
    public function isEof()
    {
        return ($this->position > $this->length);
    }

    /**
     * Reads $count bytes from the buffer
     *
     * @param   integer     $count      The number of bytes to read
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
     * Writes the given date into the buffer at the current pointer position
     *
     * @param   string  $data       The data to write
     * @return  integer             The number of bytes written
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
     * Returns the current pointer position
     *
     * @return integer
     */
    public function getPosition()
    {
        return $this->position;
    }

    /**
     * Sets the pointer position
     *
     * @param   integer     $position   The position in bytes
     * @param   integer     $whence     The reference from where to measure $position (SEEK_SET, SEEK_CUR or SEEK_END)
     * @return  boolean                 True if the position could be set
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

    /**
     * Returns the stat information for the buffer
     *
     * @return array
     */
    public function getStat()
    {
        $stat   = array(
            'ino'       => 0,
            'mode'      => 0,
            'nlink'     => 0,
            'uid'       => 0,
            'gid'       => 0,
            'rdev'      => 0,
            'size'      => 0,
            'atime'     => 0,
            'mtime'     => 0,
            'ctime'     => 0,
            'blksize'   => 0,
            'blocks'    => 0,
        );
        return array_merge($stat, array_values($stat));
    }
}