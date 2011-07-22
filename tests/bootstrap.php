<?php
require_once __DIR__.'/TQ/Tests/Helper.php';

if (file_exists($file = __DIR__.'/../autoload.php')) {
    require_once $file;
} elseif (file_exists($file = __DIR__.'/../autoload.php.dist')) {
    require_once $file;
}

if (!defined('GIT_BINARY')) {
    define('GIT_BINARY',        '/opt/local/bin/git');
}

define('PROJECT_PATH',      dirname(__DIR__));
define('SOURCE_PATH',       PROJECT_PATH.'/src');
define('TESTS_PATH',        __DIR__);
define('TESTS_TMP_PATH',    sys_get_temp_dir().'/_tq_git_streamwrapper_tests');
define('TESTS_REPO_PATH_1', TESTS_TMP_PATH.'/repo1');
define('TESTS_REPO_PATH_2', TESTS_TMP_PATH.'/repo2');
define('TESTS_REPO_PATH_3', TESTS_TMP_PATH.'/repo3');
