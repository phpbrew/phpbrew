Contribution Guideline
=======================

1. Please see "Trouble Shooting" page before you open a bug or issue:
   L<https://github.com/phpbrew/phpbrew/wiki/Troubleshooting>.
2. If you encountered some installation issue, please also attach your build log file (build.log) to improve the diagnosis process. for example:

         $ phpbrew ext install pdo_dblib
         ===> Installing pdo_dblib extension...
         Log stored at: /home/user/.phpbrew/build/php-5.4.39/ext/pdo_dblib/build.log

3. If the error message is not clear enough, you may add an extra option `--debug` after the program name in the command line, e.g.,

         $ phpbrew --debug ext install ...
         $ phpbrew --debug install ...
