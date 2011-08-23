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

require_once __DIR__.'/TQ/Tests/Helper.php';

if (file_exists($file = __DIR__.'/../autoload.php')) {
    require_once $file;
} elseif (file_exists($file = __DIR__.'/../autoload.php.dist')) {
    require_once $file;
}

use TQ\Tests\Helper;

define('PROJECT_PATH',      Helper::normalizeDirectorySeparator(dirname(__DIR__)));
define('SOURCE_PATH',       PROJECT_PATH.'/src');
define('TESTS_PATH',        Helper::normalizeDirectorySeparator(__DIR__));

if (defined('TEST_REPO_PATH') && is_string(TEST_REPO_PATH)) {
    define('TESTS_TMP_PATH',    Helper::normalizeDirectorySeparator(TEST_REPO_PATH).'/_tq_git_streamwrapper_tests');
} else {
    define('TESTS_TMP_PATH',    Helper::normalizeDirectorySeparator(sys_get_temp_dir()).'/_tq_git_streamwrapper_tests');
}
define('TESTS_REPO_PATH_1', TESTS_TMP_PATH.'/repo1');
define('TESTS_REPO_PATH_2', TESTS_TMP_PATH.'/repo2');
define('TESTS_REPO_PATH_3', TESTS_TMP_PATH.'/repo3');
