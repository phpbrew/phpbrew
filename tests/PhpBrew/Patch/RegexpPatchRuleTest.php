<?php
namespace PhpBrew\Patch;

/**
 * @small
 */
class RegexpPatchRuleTest extends \PHPUnit_Framework_TestCase
{
    public function testAllOfWithoutCondition()
    {
        $input =<<<EOC
SAPI_CLI_PATH = sapi/cli/php
BUILD_CLI = $(LIBTOOL) --mode=link $(CC) -export-dynamic
PHP_CGI_OBJS = sapi/cgi/cgi_main.lo sapi/cgi/fastcgi.lo
SAPI_CGI_PATH = sapi/cgi/php-cgi
BUILD_CGI = $(LIBTOOL) --mode=link $(CC) -export-dynamic
EOC;
        $expected =<<<EOC
SAPI_CLI_PATH = sapi/cli/php
BUILD_CLI = $(LIBTOOL) --mode=link $(CXX) -export-dynamic
PHP_CGI_OBJS = sapi/cgi/cgi_main.lo sapi/cgi/fastcgi.lo
SAPI_CGI_PATH = sapi/cgi/php-cgi
BUILD_CGI = $(LIBTOOL) --mode=link $(CXX) -export-dynamic
EOC;
        $rule = RegexpPatchRule::allOf(
            array(),
            '/\$\(CC\)/',
            '$(CXX)'
        );
        is($expected, $rule->apply($input));
    }

    public function testAllOfWithSingleCondition()
    {
        $input =<<<EOC
SAPI_CLI_PATH = sapi/cli/php
BUILD_CLI = $(LIBTOOL) --mode=link $(CC) -export-dynamic
PHP_CGI_OBJS = sapi/cgi/cgi_main.lo sapi/cgi/fastcgi.lo
SAPI_CGI_PATH = sapi/cgi/php-cgi
BUILD_CGI = $(LIBTOOL) --mode=link $(CC) -export-dynamic
EOC;
        $expected =<<<EOC
SAPI_CLI_PATH = sapi/cli/php
BUILD_CLI = $(LIBTOOL) --mode=link $(CXX) -export-dynamic
PHP_CGI_OBJS = sapi/cgi/cgi_main.lo sapi/cgi/fastcgi.lo
SAPI_CGI_PATH = sapi/cgi/php-cgi
BUILD_CGI = $(LIBTOOL) --mode=link $(CXX) -export-dynamic
EOC;
        $rule = RegexpPatchRule::allOf(
            array('/^BUILD_/'),
            '/\$\(CC\)/',
            '$(CXX)'
        );
        is($expected, $rule->apply($input));
    }

    public function testCreateAllOfWithMultipleConditions()
    {
        $input =<<<EOC
EXTRA_LDFLAGS_PROGRAM =                                                                                                 
EXTRA_LIBS = -lcrypt -lresolv -lcrypt -lrt -lrt -lm -ldl
ZEND_EXTRA_LIBS =                                                                                                       
INCLUDES = -I/home/phpbrew/.phpbrew/build/php-5.6.3/ext/date/lib
EXTRA_INCLUDES =                                                                                                        
INCLUDE_PATH = .:/usr/local/lib/php
EOC;
        $expected =<<<EOC
EXTRA_LDFLAGS_PROGRAM =                                                                                                 
EXTRA_LIBS = -lcrypt -lresolv -lcrypt -lrt -lrt -lm -ldl -stdc++
ZEND_EXTRA_LIBS =                                                                                                       
INCLUDES = -I/home/phpbrew/.phpbrew/build/php-5.6.3/ext/date/lib
EXTRA_INCLUDES =                                                                                                        
INCLUDE_PATH = .:/usr/local/lib/php
EOC;
        $rule = RegexpPatchRule::allOf(
            array('/^EXTRA_LIBS =/', '/-ldl$/'),
            '/^(.*)$/',
            '$1 -stdc++'
        );
        is($expected, $rule->apply($input));
    }

    public function testAlways()
    {
        $input =<<<EOC
SAPI_SHARED=libs/libphp\$(PHP_MAJOR_VERSION).\$SHLIB_DL_SUFFIX_NAME
SAPI_STATIC=libs/libphp\$(PHP_MAJOR_VERSION).a
SAPI_LIBTOOL=libphp\$(PHP_MAJOR_VERSION).la
EOC;
        $expected =<<<EOC
SAPI_SHARED=libs/libphp\$(PHP_VERSION).\$SHLIB_DL_SUFFIX_NAME
SAPI_STATIC=libs/libphp\$(PHP_VERSION).a
SAPI_LIBTOOL=libphp\$(PHP_VERSION).la
EOC;
        $rule = RegexpPatchRule::always(
            '#libphp\$\(PHP_MAJOR_VERSION\)\.#',
            'libphp$(PHP_VERSION).'
        );
        is($expected, $rule->apply($input));
    }

    public function testAnyOfWithoutCondition()
    {
        $input =<<<EOC
SAPI_CLI_PATH = sapi/cli/php
BUILD_CLI = $(LIBTOOL) --mode=link $(CC) -export-dynamic
PHP_CGI_OBJS = sapi/cgi/cgi_main.lo sapi/cgi/fastcgi.lo
SAPI_CGI_PATH = sapi/cgi/php-cgi
BUILD_CGI = $(LIBTOOL) --mode=link $(CC) -export-dynamic
EOC;
        $expected =<<<EOC
SAPI_CLI_PATH = sapi/cli/php
BUILD_CLI = $(LIBTOOL) --mode=link $(CXX) -export-dynamic
PHP_CGI_OBJS = sapi/cgi/cgi_main.lo sapi/cgi/fastcgi.lo
SAPI_CGI_PATH = sapi/cgi/php-cgi
BUILD_CGI = $(LIBTOOL) --mode=link $(CXX) -export-dynamic
EOC;
        $rule = RegexpPatchRule::anyOf(
            array(),
            '/\$\(CC\)/',
            '$(CXX)'
        );
        is($expected, $rule->apply($input));
    }

    public function testAnyOfWithSingleCondition()
    {
        $input =<<<EOC
SAPI_CLI_PATH = sapi/cli/php
BUILD_CLI = $(LIBTOOL) --mode=link $(CC) -export-dynamic
PHP_CGI_OBJS = sapi/cgi/cgi_main.lo sapi/cgi/fastcgi.lo
SAPI_CGI_PATH = sapi/cgi/php-cgi
BUILD_CGI = $(LIBTOOL) --mode=link $(CC) -export-dynamic
EOC;
        $expected =<<<EOC
SAPI_CLI_PATH = sapi/cli/php
BUILD_CLI = $(LIBTOOL) --mode=link $(CXX) -export-dynamic
PHP_CGI_OBJS = sapi/cgi/cgi_main.lo sapi/cgi/fastcgi.lo
SAPI_CGI_PATH = sapi/cgi/php-cgi
BUILD_CGI = $(LIBTOOL) --mode=link $(CXX) -export-dynamic
EOC;
        $rule = RegexpPatchRule::anyOf(
            array('/^BUILD_/'),
            '/\$\(CC\)/',
            '$(CXX)'
        );
        is($expected, $rule->apply($input));
    }

    public function testAnyOfWithMultipleConditions()
    {
        $input =<<<EOC
SAPI_CLI_PATH = sapi/cli/php
BUILD_CLI = $(LIBTOOL) --mode=link $(CC) -export-dynamic
PHP_CGI_OBJS = sapi/cgi/cgi_main.lo sapi/cgi/fastcgi.lo
SAPI_CGI_PATH = sapi/cgi/php-cgi
BUILD_CGI = $(LIBTOOL) --mode=link $(CC) -export-dynamic
EOC;
        $expected =<<<EOC
SAPI_CLI_PATH = sapi/cli/php
BUILD_CLI = $(LIBTOOL) --mode=link $(CXX) -export-dynamic
PHP_CGI_OBJS = sapi/cgi/cgi_main.lo sapi/cgi/fastcgi.lo
SAPI_CGI_PATH = sapi/cgi/php-cgi
BUILD_CGI = $(LIBTOOL) --mode=link $(CXX) -export-dynamic
EOC;
        $rule = RegexpPatchRule::anyOf(
            array('/^BUILD_CLI/', '/^BUILD_CGI/'),
            '/\$\(CC\)/',
            '$(CXX)'
        );
        is($expected, $rule->apply($input));
    }
}
