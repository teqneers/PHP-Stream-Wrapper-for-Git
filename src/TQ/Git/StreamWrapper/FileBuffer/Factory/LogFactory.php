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
namespace TQ\Git\StreamWrapper\FileBuffer\Factory;
use TQ\Git\StreamWrapper\PathInformation;
use TQ\Git\StreamWrapper\FileBuffer\StringBuffer;

/**
 * Factory to create a log buffer
 *
 * @author     Stefan Gehrig <gehrigteqneers.de>
 * @category   TQ
 * @package    TQ_Git
 * @subpackage StreamWrapper
 * @copyright  Copyright (C) 2011 by TEQneers GmbH & Co. KG
 */
class LogFactory implements Factory
{
    /**
     * Returns true if this factory can handle the requested path
     *
     * @param   PathInformation     $path   The path information
     * @param   string              $mode   The mode used to open the file
     * @return  boolean                     True if this factory can handle the path
     */
    public function canHandle(PathInformation $path, $mode)
    {
        return $path->hasArgument('log');
    }

    /**
     * Returns the file stream to handle the requested path
     *
     * @param   PathInformation     $path   The path information
     * @param   string              $mode   The mode used to open the path
     * @return  FileBuffer                  The file buffer to handle the path
     */
    public function createFileBuffer(PathInformation $path, $mode)
    {
        $repo   = $path->getRepository();
        $buffer = implode(
            str_repeat(PHP_EOL, 3),
            $repo->getLog($path->getArgument('limit'), $path->getArgument('skip'))
        );
        return new StringBuffer($buffer, array(), 'r');
    }

}