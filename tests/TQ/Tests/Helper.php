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

namespace TQ\Tests;

class Helper
{
    public static function removeDirectory($path)
    {
        clearstatcache();
        if (!file_exists($path)) {
            return;
        }

        if (!is_dir($path)) {
            throw new \InvalidArgumentException(sprintf('"%s" is not a directory', $path));
        }

        $dirIt  = new \RecursiveDirectoryIterator($path,
              \RecursiveDirectoryIterator::SKIP_DOTS
            | \RecursiveDirectoryIterator::KEY_AS_PATHNAME
            | \RecursiveDirectoryIterator::CURRENT_AS_FILEINFO
        );
        $it = new \RecursiveIteratorIterator($dirIt,
            \RecursiveIteratorIterator::CHILD_FIRST
        );

        foreach ($it as $p => $f) {
            if ($f->isDir()) {
                rmdir($p);
            } else if ($f->isFile()) {
                chmod($p, 0777);
                unlink($p);
            }
        }
        rmdir($path);
    }

    public static function normalizeDirectorySeparator($path)
    {
        return str_replace(DIRECTORY_SEPARATOR, '/', $path);
    }

    public static function normalizeNewLines($string)
    {
        return str_replace("\r\n", "\n", $string);
    }

    public static function normalizeEscapeShellArg($command)
    {
        return str_replace("'", '"', $command);
    }
}