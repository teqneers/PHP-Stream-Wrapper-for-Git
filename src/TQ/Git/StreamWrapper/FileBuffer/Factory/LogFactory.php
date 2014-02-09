<?php
/*
 * Copyright (C) 2014 by TEQneers GmbH & Co. KG
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
 * Git Stream Wrapper for PHP
 *
 * @category   TQ
 * @package    TQ_VCS
 * @subpackage Git
 * @copyright  Copyright (C) 2014 by TEQneers GmbH & Co. KG
 */

namespace TQ\Git\StreamWrapper\FileBuffer\Factory;
use TQ\Vcs\Repository\RepositoryInterface;
use TQ\Vcs\StreamWrapper\FileBuffer\Factory\AbstractLogFactory;

/**
 * Factory to create a log buffer
 *
 * @author     Stefan Gehrig <gehrigteqneers.de>
 * @category   TQ
 * @package    TQ_VCS
 * @subpackage Git
 * @copyright  Copyright (C) 2014 by TEQneers GmbH & Co. KG
 */
class LogFactory extends AbstractLogFactory
{
    /**
     * Creates the log string to be fed into the string buffer
     *
     * @param   RepositoryInterface     $repository The repository
     * @param   integer|null            $limit      The maximum number of log entries returned
     * @param   integer|null            $skip       Number of log entries that are skipped from the beginning
     * @return  string
     */
    protected function createLogString(RepositoryInterface $repository, $limit, $skip)
    {
        return implode(
            str_repeat(PHP_EOL, 3),
            $repository->getLog($limit, $skip)
        );
    }
}