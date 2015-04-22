CHANGES
=======

### Version 1.19.1 - Wed Apr 22 22:28:47 2015

- Always make sure phpbrew root/home exists for issue #475
- Added options to speicfy phpbrew root/home for install command.
- Fixed `use` command argument validation for supporting 'latest'
- Merge pull request #466 from vasiliy-pdk/error_during_tests_fix
- CONNECT_TIMEOUT env variable and --connect_timeout option was added. Fatal Error during tests fixed

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
