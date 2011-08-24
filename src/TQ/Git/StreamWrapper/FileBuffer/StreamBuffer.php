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
namespace TQ\Git\StreamWrapper\FileBuffer;

/**
 * Encapsulates a file stream buffer to be used in the streamwrapper
 *
 * @author     Stefan Gehrig <gehrigteqneers.de>
 * @category   TQ
 * @package    TQ_Git
 * @subpackage StreamWrapper
 * @copyright  Copyright (C) 2011 by TEQneers GmbH & Co. KG
 */
class StreamBuffer implements FileBuffer
{
    /**
     * The file resource
     *
     * @var resource
     */
    protected $stream;

    /**
     * Creates a neww file buffer with the given path
     *
     * @param   string  $path    The path
     * @param   string  $mode    The file mode
     */
    public function __construct($path, $mode = 'r+')
    {
        $this->stream   = @fopen($path, $mode);
        if ($this->stream === false) {
            throw new StreamException(sprintf('Cannot access "%s" in mode "%s"', $path, $mode));
        }
    }

    /**
     * Destructor closes file stream handle
     */
    public function __destruct()
    {
        $this->close();
    }

    /**
     * Returns the complete contents of the buffer
     *
     * @return  string
     */
    public function getBuffer()
    {
        $currentPos = $this->getPosition();
        $this->setPosition(0, SEEK_SET);
        $buffer = stream_get_contents($this->stream);
        $this->setPosition($currentPos, SEEK_SET);
        return $buffer;
    }

    /**
     * Returns true if the pointer is at the end of the buffer
     *
     * @return  boolean
     */
    public function isEof()
    {
        return feof($this->stream);
    }

    /**
     * Reads $count bytes from the buffer
     *
     * @param   integer     $count      The number of bytes to read
     * @return  string|null
     */
    public function read($count)
    {
        return fread($this->stream, $count);
    }

    /**
     * Writes the given date into the buffer at the current pointer position
     *
     * @param   string  $data       The data to write
     * @return  integer             The number of bytes written
     */
    public function write($data)
    {
        return fwrite($this->stream, $data);
    }

    /**
     * Returns the current pointer position
     *
     * @return integer
     */
    public function getPosition()
    {
        return ftell($this->stream);
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
        return (fseek($this->stream, $position, $whence) == 0);
    }

    /**
     * Returns the stat information for the buffer
     *
     * @return array
     */
    public function getStat()
    {
        return fstat($this->stream);
    }

    /**
     * Flushes the buffer to the storage media
     *
     * @return  boolean
     */
    public function flush()
    {
        return fflush($this->stream);
    }

    /**
     * Closes the buffer
     */
    public function close()
    {
        if ($this->stream !== null) {
            fclose($this->stream);
            $this->stream   = null;
        }
    }
}