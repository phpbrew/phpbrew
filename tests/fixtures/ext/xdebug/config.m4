dnl $Id: config.m4,v 1.28 2008-07-31 00:43:35 sniper Exp $
dnl config.m4 for extension xdebug

PHP_ARG_ENABLE(xdebug, whether to enable eXtended debugging support,
[  --enable-xdebug         Enable Xdebug support])

if test "$PHP_XDEBUG" != "no"; then
  AC_MSG_CHECKING([Check for supported PHP versions])
  PHP_XDEBUG_FOUND_VERSION=`${PHP_CONFIG} --version`
  PHP_XDEBUG_FOUND_VERNUM=`echo "${PHP_XDEBUG_FOUND_VERSION}" | $AWK 'BEGIN { FS = "."; } { printf "%d", ([$]1 * 100 + [$]2) * 100 + [$]3;}'`
dnl  if test "$PHP_XDEBUG_FOUND_VERNUM" -lt "50400"; then
    AC_MSG_RESULT([supported ($PHP_XDEBUG_FOUND_VERSION)])
dnl  else
dnl    AC_MSG_ERROR([not supported. Need a PHP version < 5.4.0 (found $PHP_XDEBUG_FOUND_VERSION)])
dnl  fi
  
  AC_DEFINE(HAVE_XDEBUG,1,[ ])

dnl Check for new current_execute_data field in zend_executor_globals
  old_CPPFLAGS=$CPPFLAGS
  CPPFLAGS="$INCLUDES $CPPFLAGS"

  AC_CHECK_FUNCS(gettimeofday)

  PHP_CHECK_LIBRARY(m, cos, [ PHP_ADD_LIBRARY(m,, XDEBUG_SHARED_LIBADD) ])

  CPPFLAGS=$old_CPPFLAGS

  PHP_NEW_EXTENSION(xdebug, xdebug.c xdebug_code_coverage.c xdebug_com.c xdebug_compat.c xdebug_handler_dbgp.c xdebug_handlers.c xdebug_llist.c xdebug_hash.c xdebug_private.c xdebug_profiler.c xdebug_set.c xdebug_stack.c xdebug_str.c xdebug_superglobals.c xdebug_tracing.c xdebug_var.c xdebug_xml.c usefulstuff.c, $ext_shared,,,,yes)
  PHP_SUBST(XDEBUG_SHARED_LIBADD)
  PHP_ADD_MAKEFILE_FRAGMENT
fi
