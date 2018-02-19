<?php
/*
 * Copyright (C) 2017 by TEQneers GmbH & Co. KG
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
 * @subpackage VCS
 * @copyright  Copyright (C) 2018 by TEQneers GmbH & Co. KG
 */

namespace TQ\Vcs\StreamWrapper\FileBuffer\Factory;
use TQ\Vcs\Buffer\FileBufferInterface;
use TQ\Vcs\StreamWrapper\FileBuffer\FactoryInterface;
use TQ\Vcs\Buffer\StringBuffer;
use TQ\Vcs\StreamWrapper\PathInformationInterface;

/**
 * Factory to create a commit buffer
 *
 * @author     Stefan Gehrig <gehrigteqneers.de>
 * @category   TQ
 * @package    TQ_VCS
 * @subpackage VCS
 * @copyright  Copyright (C) 2018 by TEQneers GmbH & Co. KG
 */
class CommitFactory implements FactoryInterface
{
    /**
     * Returns true if this factory can handle the requested path
     *
     * @param   PathInformationInterface     $path   The path information
     * @param   string                       $mode   The mode used to open the file
     * @return  boolean                              True if this factory can handle the path
     */
    public function canHandle(PathInformationInterface $path, $mode)
    {
        return $path->hasArgument('commit');
    }

    /**
     * Returns the file stream to handle the requested path
     *
     * @param   PathInformationInterface     $path   The path information
     * @param   string                       $mode   The mode used to open the path
     * @return  FileBufferInterface                  The file buffer to handle the path
     */
    public function createFileBuffer(PathInformationInterface $path, $mode)
    {
        $repo   = $path->getRepository();
        $buffer = $repo->showCommit($path->getArgument('ref'));
        return new StringBuffer($buffer, array(), 'r');
    }

}
