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
 * Simple class to iterate over the results of a directory listing
 *
 * @author     Stefan Gehrig <gehrigteqneers.de>
 * @category   TQ
 * @package    TQ_Git
 * @subpackage StreamWrapper
 * @copyright  Copyright (C) 2011 by TEQneers GmbH & Co. KG
 */
class DirectoryBuffer implements \Iterator
{
    /**
     * The directory listing
     * 
     * @var array
     */
    protected $listing;

    /**
     * Creates a directory buffer from an array
     *
     * @param   array   $listing    The directory listing
     */
    public function __construct(array $listing)
    {
        $this->listing  = $listing;
        reset($this->listing);
    }

    /**
     * Implements Iterator
     *
     * @link    http://php.net/manual/en/iterator.current.php
     * @return  string
     */
    public function current()
    {
        return current($this->listing);
    }

    /**
     * Implements Iterator
     *
     * @link    http://php.net/manual/en/iterator.next.php
     */
    public function next()
    {
        next($this->listing);
    }

    /**
     * Implements Iterator
     *
     * @link    http://php.net/manual/en/iterator.key.php
     * @return  integer|boolean     False on failure
     */
    public function key()
    {
        return key($this->listing);
    }

    /**
     * Implements Iterator
     *
     * @link    http://php.net/manual/en/iterator.valid.php
     * @return  boolean
     */
    public function valid()
    {
        return (key($this->listing) !== null);
    }

    /**
     * Implements Iterator
     *
     * @link    http://php.net/manual/en/iterator.rewind.php
     */
    public function rewind()
    {
        reset($this->listing);
    }
}