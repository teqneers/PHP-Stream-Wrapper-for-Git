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
 * @copyright  Copyright (C) 2017 by TEQneers GmbH & Co. KG
 */

namespace TQ\Vcs;

/**
 * A collection of methods used for interacting with the file system
 *
 * @author     Stefan Gehrig <gehrigteqneers.de>
 * @category   TQ
 * @package    TQ_VCS
 * @subpackage VCS
 * @copyright  Copyright (C) 2017 by TEQneers GmbH & Co. KG
 */
class FileSystem
{
    /**
     * Normalizes the directory separator to /
     *
     * @param   string  $path       The path
     * @return  string              The normalized path
     */
    public static function normalizeDirectorySeparator($path)
    {
        return str_replace(array('\\', '/'), '/', $path);
    }

    /**
     * Bubbles up a path until $comparator returns true
     *
     * @param   string      $path              The path
     * @param   \Closure    $comparator        The callback used inside when bubbling to determine a finding
     * @return  string|null                    The path that is found or NULL otherwise
     */
    public static function bubble($path, \Closure $comparator)
    {
        $found   = null;
        $path    = self::normalizeDirectorySeparator($path);

        $drive  = null;
        if (preg_match('~^(\w:)(.+)~', $path, $parts)) {
            $drive  = $parts[1];
            $path   = $parts[2];
        }

        $pathParts  = explode('/', $path);
        while (count($pathParts) > 0 && $found === null) {
            $path   = implode('/', $pathParts);
            if ($comparator($path)) {
                $found  = $path;
            }
            array_pop($pathParts);
        }

        if ($drive && $found) {
            $found  = $drive.$found;
        }

        return $found;
    }
}
