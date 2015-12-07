Hacking
==========

Run composer install:

    $ copmoser install --prefer-source

Initialize

    $ php bin/phpbrew init

To hack with phpbrew shell script:

    $ vim phpbrew.sh

And then update the init script to the command class:

    $ ./scripts/update-init-script

List known versions:

    $ php bin/phpbrew known

Test your command through the `bin/phpbrew`:

    $ php bin/phpbrew -d install --no-test 5.4.0RC7

To show which phpbrew is running:

    $ unset -f phpbrew
    $ which phpbrew

Re-compile phar file for testing:

    $ scripts/compile

Add yourself to the contributor list in README.md

Then send pull request.
