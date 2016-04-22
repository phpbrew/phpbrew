CHANGES
=======

### Version 1.20.8 - Sat Apr 23 01:41:37 2016

- Fixed openssl build issue on darwin https://github.com/phpbrew/phpbrew/issues/636
- Replaced ':' with `PATH_SEPARATOR` to make the path generation indenpendant.
- Few fixes

### Version 1.20.7 - Sun Apr 17 18:13:29 2016

- Commit e1fc2a4: Merge pull request #683 from Simplesmente/master

   add file README.pt-br.md and translating

- Commit 7ad9da5: Merge pull request #701 from zvook/master

   Network is unreachable php.net fix

- Commit f1fdd1d: Merge pull request #703 from jhdxr/develop

   fix issues about known command

- Commit 5ca79aa: Merge pull request #653 from jhdxr/feature/down

   refactor downloading related part ( #571 )

- Commit 1475c8c: Merge pull request #652 from jhdxr/feature/fish

   bugfix and improvements for fish

- Commit 2e41279: Merge pull request #637 from morozov/virtual-variant-value

   Do not override explicitly specified values with the default from virtual variants

### Version 1.20.6 - Fri Mar  4 13:41:32 2016

phpbrewrc:

- Improved .phpbrewrc searching effiency and fixed a bug related to $PWD.
- Added help message about enabling .phpbrewrc in init command.
- .phpbrewrc searching will be disabled by default and have to be enabled manually.

command improvement:
- Added arginfo to env command
- Fixed phar file for dump() function by updating package dependencies to avoid
  compiling packages from require-dev section (cliframework)
- Allow user value of variants contains dot character

fish:
- Added proper redirect to STDERR for Fish shell


### Version 1.20.4 - Mon Dec 14 19:37:01 2015

- Commit 2e41279: Merge pull request #637 from morozov/virtual-variant-value

   Do not override explicitly specified values with the default from virtual variants

- Commit 728ff9b: Merge pull request #634 from kurotaky/fix-phpbrew-bin-path-connection

   Does not connect the phpbrew bin path when `$PHPBREW_BIN` is blank

- Commit 3c5d6d8: Merge pull request #631 from Dexus/Dexus-patch-1

   Fix ReleaseList.php and Options forwarding

### Version 1.20.3 - Mon Dec  7 23:56:32 2015

- Fixed duplicated getBuild method in SystemCommandException
- Fixed class loading for symfony yaml component with new cliframework

### Version 1.20.2 - Mon Dec  7 09:15:23 2015

- added better php7 build support.
- fixed apxs2 patch for php7.
- improved travis-ci build speed.
- improved build process error message by dumping the last 5 lines of log to
  console directly.
- moved built phpbrew phar file into build/phpbrew
- created a symbol link 'phpbrew' under the root of project directory and make
  it pointing to 'build/phpbrew'

### Version 1.20.1 - Thu Jul 27 16:30:27 2015

- sensibly forces redownload when using wget #554
- adds MSSQL extension to php src bundled extensions list #551

### Version 1.20.0 - Thu Jul 16 00:11:58 2015

- Added php app store.

### Version 1.19.7 - Fri Jun 26 16:50:20 2015 -0300

- dries `ext clean` + gives status in all failure cases
- update completion
- fixes --old flag for known and update commands closes #526 improves
  description of --connect-timeout
- command download: update argument completion
- fix zsh completion: avoid single quote

Merged pull requests:

- Commit 060c6e0: Merge pull request #531 from jhdxr/fix/530

   update doc for known command, add --http-proxy and --http-proxy-auth

- Commit ae15d68: Merge pull request #525 from jhdxr/fix/524

   self-update download to a tmp file first instead of overwrite the exi…

### Version 1.19.1 - Tue May 19 23:05:20 2015

- Fixed OS X compatibility regarding `whereis -b` #503

### Version 1.19.0 - Mon May 18 21:17:13 2015

- PHP7 (next unstable version) install support
- Updadted completion scripts thanks to awesome CliFramework
- Fixed false failure of ./buildconf step
- Fixed PHP 5.3 error with JSON_PRETTY_PRINT
- Fixed phpbrew prepends $PATH on every cd command #487
- Deprecated php-releases.json use php.net json endpoint from now on
- Fix bug in variant parser #495
- List installed php versions in decrescent order
- Improved URL policy to locate older minor releases whenever URLs are altered
- Allow overriding PHP when runnning phpbrew #94

### Version 1.18.5 - Wed Apr 22 22:28:47 2015

- Always make sure phpbrew root/home exists for issue #475
- Added options to speicfy phpbrew root/home for install command.
- Fixed `use` command argument validation for supporting 'latest'
- Merged pull request #466 from vasiliy-pdk/error_during_tests_fix
- CONNECT_TIMEOUT env variable and --connect_timeout option was added. Fatal Error during tests fixed
- Added more variants to `+everything` #457
- Use log tail hint with "tail -F" instead of "tail -f"
- added support for SPL_Types extension install #456
- Use cp insteaad of mv on Makefile install task
- Removed redundant if statements in UrlDownloader.
- Merged pull request #444 from shinnya/fix/download_always_fai

### Version 1.15 - Tue Oct 14 20:23:54 2014

- Used CurlKit instead of command line curl or wget to download the distribution files.
- Added more options to the install command, added options:
  - `--no-clean`
  - `--no-install`
  - `--no-patch`
  - `--build-dir=DIR`
  - `--make-jobs=N` is now renamed to `--jobs=N`

  Please run `phpbrew help install` to see the details of the command options.

- Directory for the downloaded distribution files is now separated.
- date.timezone and phar.readonly ini file patch is fixed.
- Error redirection is now improved.
- Use JSON meta data for PHP releases.
- Added `--update` option to `known` command, this can update the release meta data:

    phpbrew known --update

- Improve command help generator.
- Since the release meta info is stored in the cache, known command is now faster.
- Included the multi-arch libdir fix.
- Use some of the default variants if `+default` is not set.
- Fix getoptionkit bug for variant parsing: https://github.com/phpbrew/phpbrew/issues/353
- Fix use, switch commands for switching to an aliased build.

Development updates:

- Variant info is refactored into BuildSettings class.
- VariantParser is refactored and simplified.
- Builder class is removed.
- Install command class is refactored with the `Build` class.
- Upgraded CLIFramework to 2.0.x
- Upgraded CurlKit to 1.0
- Upgraded PEARX 1.0.3 to fix 404 page not found when distribution file does not exist.

### Version 1.12 - Wed Dec 11 09:56:22 2013

- Install command now run commands below after installations:

    pear config-set temp_dir $HOME/.phpbrew/tmp/pear/temp
    pear config-set cache_dir $HOME/.phpbrew/tmp/pear/cache_dir
    pear config-set download_dir $HOME/.phpbrew/tmp/pear/download_dir
    pear config-set auto_discover 1

### Version 1.11.3 - Sun Dec  8 14:38:14 2013

- Fixed libdir detection
- Enabled `xml` variant by default
- Renamed `xml_all` variant to xml
- Fix +iconv variant ( --with-iconv=/usr won't be compiled on systems with gnu iconv  )
- Fix +gd variant ( --with-gd=/usr won't be compiled, --with-gd=shared,$prefix works)

### Version 1.11 - Wed Dec  4 13:28:00 2013

- Added platform prefix setup command:

        phpbrew lookup-prefix macports
        phpbrew lookup-prefix homebrew
        phpbrew lookup-prefix debian

- Variant builder is improved with the lookup-prefix
- Better path detection.
- Freetype include path fix for +gd variant


        +gd=shared should work for Mac OS platform


- platform libdir is supported, now supports for include/lib paths under 

        $prefix/i386-linux-gnu/
        $prefix/x86_64-linux-gnu/

### Version 1.10 - Tue Dec  3 22:55:22 2013

- Added 'opcache' variant.
- Added fpm management support.
- Added quick commands to switch between directories.
- Added phpbrew/bin directory to install shared executables, e.g. composer, phpunit, onion ...etc

### Version 1.8.22 - Mon Nov 18 17:23:29 2013

- Copy php-fpm default config to {php-version}/etc/

### Version 1.8.3 - Sat Mar  9 19:38:22 2013

- Add new extension installer.
- Fix extension enable feature.
- Refactor installation tasks to task classes.
- Can save variant information.
- Show variants and options when listing phps
- Provide a patch for php5.3 msgformat libstdc++ bug on 64bit machines.

### Version 1.3.3 - 一  4/30 11:27:09 2012

- Added posix variant.
- Added calendar variant.
- Improve install-ext command.

### Version 1.3.1 - 三  3/14 02:20:08 2012

- Fixed bash shell redirection bug.
- Added install-ext command.
- Added iconv variant.
- Added PHP version info prompt.

### Version 1.2.0 - 二  3/ 6 10:50:51 2012

- SAPI confliction check.
- show tail command usage.
- pipe error and stdout to build.log.
- show default variants with star.
- Add bz2, fpm, cgi, cli variants.

### Version 1.1.0

- openssl variant
- variant command
- self-update command
