<?php
/*
 * Copyright (C) 2023 by TEQneers GmbH & Co. KG
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
 * @subpackage SVN
 * @copyright  Copyright (C) 2023 by TEQneers GmbH & Co. KG
 */

namespace TQ\Svn\StreamWrapper;
use TQ\Svn\Cli\Binary;
use TQ\Svn\Repository\Repository;
use TQ\Vcs\Repository\RepositoryInterface;
use TQ\Vcs\StreamWrapper\AbstractPathFactory;
use TQ\Vcs\StreamWrapper\RepositoryRegistry;

/**
 * Creates path information for a given stream URL
 *
 * @author     Stefan Gehrig <gehrigteqneers.de>
 * @category   TQ
 * @package    TQ_VCS
 * @subpackage SVN
 * @copyright  Copyright (C) 2023 by TEQneers GmbH & Co. KG
 */
class PathFactory extends AbstractPathFactory
{
    /**
     * The SVN binary
     *
     * @var Binary
     */
    protected $svn;

    /**
     * Creates a path factory
     *
     * @param   string                   $protocol    The protocol (such as "svn")
     * @param   Binary|string|null       $svn         The SVN binary
     * @param   RepositoryRegistry|null  $map         The repository registry
     */
    public function __construct($protocol, $svn = null, RepositoryRegistry $map = null)
    {
        parent::__construct($protocol, $map);
        $this->svn   = Binary::ensure($svn);
    }

    /**
     * Creates a new Repository instance for the given path
     *
     * @param   string      $path       The path
     * @return  RepositoryInterface
     */
    protected function createRepositoryForPath($path)
    {
        return Repository::open($path, $this->svn);
    }
}
