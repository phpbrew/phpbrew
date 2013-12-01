# Todo

- Ability to switch curl, wget or pure php downloader.
- Smarter build tasks
    - xdebug (enable zend extension with absolute path)
    - build with extensions
- Mirror option
- Separate install command to sub-tasks, so we can run tasks separately
    - Fetch command.
    - Build command.
    - Install command.
    - Test command.
- Inherit option
    - Copy variants from previous install.
    - Copy config file from previous install.
- Extra variants: Add extra variant type for non-configure options:
    - phpunit variant
    - zendframework variant
    - pyrus variant
    - xdebug variant
    - apc variant
- Fallback handler when DOMDocument is not found.
- pure bash support.

- PHP 5.2 compatibility (or pure bash script?)
    Phar requires PHP 5.2.0 or newer. Additional features
    require the SPL extension in order to take advantage of
    iteration and array access to a Phar's file contents. The
    phar stream does not require any additional extensions to
    function.

# Done

x variant sets
x Extension Builder
x Extension disable/enable command
x `--patch={file}` option support.
