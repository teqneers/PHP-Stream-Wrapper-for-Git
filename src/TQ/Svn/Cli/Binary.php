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
 * Git Stream Wrapper for PHP
 *
 * @category   TQ
 * @package    TQ_Vcs
 * @subpackage Svn
 * @copyright  Copyright (C) 2011 by TEQneers GmbH & Co. KG
 */

namespace TQ\Svn\Cli;
use TQ\Vcs\Cli\Binary as VcsBinary;
use TQ\Vcs\Cli\Call;

/**
 * Encapsulates access to th SVN command line binary
 *
 * @author     Stefan Gehrig <gehrigteqneers.de>
 * @category   TQ
 * @package    TQ_Vcs
 * @subpackage Svn
 * @copyright  Copyright (C) 2011 by TEQneers GmbH & Co. KG
 */
class Binary extends VcsBinary
{
    /**
     * Try to find the SVN binary on the system
     *
     * @return  string
     */
    public static function locateBinary()
    {
        if (!self::isWindows()) {
            $result = Call::create('which svn')->execute();
            return $result->getStdOut();
        }
        return '';
    }

    /**
     * Creates a SVN binary interface
     *
     * If no path is given the class tries to find the correct
     * binary {@see locateBinary()}
     *
     * @param   string|null $path           The path to the SVN binary or NULL to auto-detect
     * @throws  \InvalidArgumentException   If no binary is found
     */
    public function __construct($path = null)
    {
        if (!$path) {
            $path  = self::locateBinary();
        }
        parent::__construct($path);
    }
}

