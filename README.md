Git Streamwrapper for PHP
=========================

[![Build Status](https://secure.travis-ci.org/teqneers/PHP-Stream-Wrapper-for-Git.png)](http://travis-ci.org/teqneers/PHP-Stream-Wrapper-for-Git)

*Git Streamwrapper for PHP* is a PHP library that allows PHP code to interact with one or multiple Git repositories from within an application. The library consists of a Git repository abstraction that can be used to programatically access Git repositories and of a stream wrapper that can be hooked into the PHP stream infrastructure to allow the developer to use file and directory access functions directly on files in a Git repository. The library provides means to access status information on a Git repository, such as the log, the current repository status or commit information, as well.

The *Git Streamwrapper for PHP* core is a wrapper around the Git command line binary so it is required to have Git installed on the machine running the PHP code. ***Git Streamwrapper for PHP* does not include a Git protocol abstraction**, it relies on the Git command line binary for all its functionality.

**The code is currently running stable (see comments on Windows below) and should be API-stable. It's however not feature-complete - so please feel free to request features you require.**


Examples
--------

### Using the repository abstraction

```php
use TQ\Git\Cli\Binary;
use TQ\Git\Repository\Repository;
// open an already initialized repository
$git = Repository::open('/path/to/your/repository', new Binary('/usr/bin/git'));

// open repository and create path and init repository if necessary
$git = Repository::open('/path/to/your/repository', new Binary('/usr/bin/git'), 0755);

// get current branch
$branch = $git->getCurrentBranch();

// get status of working directory
$status = $git->getStatus();
// are there uncommitted changes in the staging area or in the working directory
$isDirty = $git->isDirty();

// retrieve the commit log limited to $limit entries skipping the first $skip
$log = $git->getLog($limit, $skip);

// retrieve the second to last commit
$commit = $git->showCommit('HEAD^');

// list the directory contents two commits before
$list  = $git->listDirectory('.', 'HEAD^^');

// show contents of file $file at commit abcd123...
$contents = $git->showFile($file, 'abcd123');

// write a file and commit the changes
$commit = $git->writeFile('test.txt', 'Test', 'Added test.txt');

// remove multiple files
$commit = $git->removeFile('file_*', 'Removed all files not needed any more');

// rename a file
$commit = $c->renameFile('test.txt', 'test.txt-old', 'Made a backup copy');

// do some file operations and commit all changes at once
$result = $git->transactional(function(TQ\Git\Repository\Transaction $t) {
    file_put_contents($t->getRepositoryPath().'/text1.txt', 'Test 1');
    file_put_contents($t->getRepositoryPath().'/text2.txt', 'Test 2');

    unlink($t->resolvePath('old.txt'));
    rename($t->resolvePath('to_keep.txt'), $t->resolvePath('test3.txt'));

    $t->setCommitMsg('Don\'t know what to write here');

    // if we throw an execption from within the callback the changes are discarded
    // throw new Exception('No we don\'t want to to these changes');
    // note: the exception will be re-thrown by the repository so you have to catch
    // the exception yourself outside the transactional scope.
});
```

### Using the streamwrapper

```php
use TQ\Git\Cli\Binary;
use TQ\Git\StreamWrapper\StreamWrapper;

// register the wrapper
StreamWrapper::register('git', new Binary('/usr/bin/git'));

// read the contents of a file
$content = file_get_contents('git:///path/to/your/repository/file_0.txt');

// show contents of a file at commit abcd123...
$content = file_get_contents('git:///path/to/your/repository/file_0.txt#abcd123');

// show contents of a file two commits before
$content = file_get_contents('git:///path/to/your/repository/file_0.txt#HEAD^^');

// show the directory information two commits before
$directory = file_get_contents('git:///path/to/your/repository/#HEAD^^');

// list directory contents two commits before
$dir = opendir('git:///path/to/your/repository/subdir#HEAD^^');
while ($f = readdir($dir)) {
    echo $f.PHP_EOL;
}
closedir($dir);

// recursively traverse the repository two commits before
$dir = new RecursiveDirectoryIterator('git:///path/to/your/repository#HEAD^^');
$it  = new RecursiveIteratorIterator($dir, RecursiveIteratorIterator::SELF_FIRST);
foreach ($it as $fileInfo) {
    echo str_repeat(' ', $it->getDepth() * 3).$fileInfo->getFilename().PHP_EOL;
}

// retrieve the second to last commit
$commit = file_get_contents('git:///path/to/your/repository?commit&ref=HEAD^^');

// retrieve the commit log limited to 5entries skipping the first 2
$log = file_get_contents('git:///path/to/your/repository?log&limit=5&skip=2');

// remove a file - change is committed to the repository
unlink('git:///path/to/your/repository/file_to_delete.txt');

// rename a file - change is committed to the repository
rename('git:///path/to/your/repository/old.txt', 'git:///path/to/your/repository/new.txt');

// remove a directory - change is committed to the repository
rmdir('git:///path/to/your/repository/directory_to_delete');

// create a directory - change is committed to the repository
// this creates a .gitkeep file in new_directory because Git does not track directories
mkdir('git:///path/to/your/repository/new_directory');

// write to a file - change is committed to the repository when file is closed
$file = fopen('git:///path/to/your/repository/test.txt', 'w');
fwrite($file, 'Test');
fclose($file);

// support for stream context
$context = stream_context_create(array(
    'git'   => array(
        'commitMsg' => 'Hello World',
        'author'    => 'Luke Skywalker <skywalker@deathstar.com>'
    )
));
$file = fopen('git:///path/to/your/repository/test.txt', 'w', false, $context);
fwrite($file, 'Test');
fclose($file); // file gets committed with the preset commit message and author

// append to a file using file_put_contents using a custom author and commit message
$context = stream_context_create(array(
    'git'   => array(
        'commitMsg' => 'Hello World',
        'author'    => 'Luke Skywalker <skywalker@deathstar.com>'
    )
));
file_put_contents('git:///path/to/your/repository/test.txt', 'Test', FILE_APPEND, $context);

// unregister the wrapper if needed
StreamWrapper::unregister();
```

Requirements
------------

- PHP > 5.3.0
- Git installed on the machine running the PHP code

Run tests
---------

1. clone the repository
2. copy `phpunit.xml.dist` to `phpunit.xml`
3. adjust the `GIT_BINARY` constant in `phpunit.xml` to the path to your Git binary
4. run `phpunit` from within the cloned project folder

Please note that the library has been tested on a Mac OS X 10.7 with the bundled PHP 5.3.6 (git version 1.7.6), on several Ubuntu Linux installations and on Windows Vista running PHP 5.3.7 (1.7.6.msysgit.0). Due to currently unknown reasons the test run a bit unstable on Windows. All tests should be *green* but during cleanup there may be the possibility that some access restrictions randomly kick in and prevent the cleanup code from removing the test directories. 

The unit test suite is continuously tested with [Travis CI](http://travis-ci.org/) on PHP 5.3 and 5.4 and its current status is: [![Build Status](https://secure.travis-ci.org/teqneers/PHP-Stream-Wrapper-for-Git.png)](http://travis-ci.org/teqneers/PHP-Stream-Wrapper-for-Git)

Contribute
----------

Please feel free to use the Git issue tracking to report back any problems or errors. You're encouraged to clone the repository and send pull requests if you'd like to contribute actively in developing the library.

License
-------

Copyright (C) 2011 by TEQneers GmbH & Co. KG

Permission is hereby granted, free of charge, to any person obtaining a copy of this software and associated documentation files (the "Software"), to deal in the Software without restriction, including without limitation the rights to use, copy, modify, merge, publish, distribute, sublicense, and/or sell copies of the Software, and to permit persons to whom the Software is furnished to do so, subject to the following conditions:

The above copyright notice and this permission notice shall be included in all copies or substantial portions of the Software.

THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY, FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM, OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN THE SOFTWARE.