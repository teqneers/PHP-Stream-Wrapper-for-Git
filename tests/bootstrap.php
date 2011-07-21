<?php
if (file_exists($file = __DIR__.'/../autoload.php')) {
    require_once $file;
} elseif (file_exists($file = __DIR__.'/../autoload.php.dist')) {
    require_once $file;
}

define('PROJECT_PATH',      dirname(__DIR__));
define('SOURCE_PATH',       PROJECT_PATH.'/src');
define('TESTS_PATH',        __DIR__);
define('TESTS_TMP_PATH',    TESTS_PATH.'/_temp');
define('TESTS_REPO_PATH',   TESTS_TMP_PATH.'/test');
