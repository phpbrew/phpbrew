Contribution
================


Installing the dependencies
-----------------------------

    $ composer install --dev


Changing Shell Script
---------------------

The main shell script is in `phpbrew.sh`, you may modify it then re-source it in your shell:

    source phpbrew.sh

If everything works fine, you may update it to the InitCommand class:

    scripts/update-init-script


Changing PHP Code
-----------------

If you need to modify PHP source, you may run `bin/phpbrew` to test your code:

    php bin/phpbrew {command}

If everything works fine, you may re-compile the phpbrew phar file:

    scripts/compile

The compilation needs `onion` to do the job, please download onion from http://github.com/c9s/Onion



PHP Release channels
--------------------

- http://snaps.php.net/
- http://www.php.net/releases/
- http://downloads.php.net/stas/


