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
 * Interface for file buffers used in the stream wrapper
 *
 * @author     Stefan Gehrig <gehrigteqneers.de>
 * @category   TQ
 * @package    TQ_Git
 * @subpackage StreamWrapper
 * @copyright  Copyright (C) 2011 by TEQneers GmbH & Co. KG
 */
interface FileBuffer
{
    /**
     * Returns the complete contents of the buffer
     *
     * @return  string
     */
    function getBuffer();

    /**
     * Returns true if the pointer is at the end of the buffer
     *
     * @return  boolean
     */
    function isEof();

    /**
     * Reads $count bytes from the buffer
     *
     * @param   integer     $count      The number of bytes to read
     * @return  string|null
     */
    function read($count);

    /**
     * Writes the given date into the buffer at the current pointer position
     *
     * @param   string  $data       The data to write
     * @return  integer             The number of bytes written
     */
    function write($data);

    /**
     * Returns the current pointer position
     *
     * @return integer
     */
    function getPosition();

    /**
     * Sets the pointer position
     *
     * @param   integer     $position   The position in bytes
     * @param   integer     $whence     The reference from where to measure $position (SEEK_SET, SEEK_CUR or SEEK_END)
     * @return  boolean                 True if the position could be set
     */
    function setPosition($position, $whence);

    /**
     * Returns the stat information for the buffer
     *
     * @return array
     */
    function getStat();

    /**
     * Flushes the buffer to the storage media
     *
     * @return  boolean
     */
    function flush();

    /**
     * Closes the buffer
     */
    function close();
}