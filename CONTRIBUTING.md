Contribution Guideline
=======================

This project makes used of [GNU Make](https://www.gnu.org/software/make/). You can find the most
important commands by executing `make` or `make help`.

1. Please see "Trouble Shooting" page before you open a bug or issue:
   L<https://github.com/phpbrew/phpbrew/wiki/Troubleshooting>.
2. If you encountered some installation issue, please also attach your build log file (build.log) to improve the diagnosis process. for example:

         $ phpbrew ext install pdo_dblib
         ===> Installing pdo_dblib extension...
         Log stored at: /home/user/.phpbrew/build/php-5.4.39/ext/pdo_dblib/build.log

3. If the error message is not clear enough, you may add an extra option `--debug` after the program name in the command line, e.g.,

         $ phpbrew --debug ext install ...
         $ phpbrew --debug install ...
4. Before you send the pull request, please rebase & squash your commits. See this guide for details:  https://git-scm.com/book/zh-tw/v2/Git-Tools-Rewriting-History

5. If the PR is for the releasing version, please use the following steps to create related PR:

    - Ensuring the extra branch-alias setting is matched for releasing version in the composer.json.
    - Editing the src/PhpBrew/Console.php to modify the VERSION variable.
    - Using the `make sign` command to create the Phar file and make the GPG sign with local GPG key.
