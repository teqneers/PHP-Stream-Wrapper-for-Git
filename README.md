Git Streamwrapper for PHP
=========================

*Git Streamwrapper for PHP* is a PHP library that allows PHP code to interact with one or multiple Git repositories from within an application. The library consists of a Git repository abstraction that can be used to prgramatically access Git repositories and of a stream wrapper that can be hooked into the PHP stream infrastructure to allow the developer to use file and directory access functions directly on files in a Git repository. The library provides means to access status information on a Git repository, such as the log, the current repository status or commit information, as well.

The *Git Streamwrapper for PHP* core is a wrapper around the Git command line binary so it is required to have Git installed on the machine running the PHP code. ***Git Streamwrapper for PHP* does not include a Git protocol abstraction**, it relies on the Git command line binary for all its functionality.

**The code is currently in an early pre-alpha state so it is neither extensively tested nor feature-complete or API-stable.**

Examples
--------

### Using the repository abstraction



### Using the streamwrapper



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

Please note that the library is currently in a pre-alpha state and was tested only on a Mac OS X 10.7 with the bundled PHP 5.3.6. The code should run on all *nix-based systems though, but currenty you're not able to run the tests on a Windows-based machine.

Contribute
----------

Please feel free to use the Git issue tracking to report back any problems or errors. You're encouraged to clone the repository and send pull requests if you'd like to contribute actively in developing the library.