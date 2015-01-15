dnl $Id: config.m4 250039 2008-01-06 20:40:26Z helly $
dnl config.m4 for extension SPL Types

PHP_ARG_ENABLE(spl-types, enable SPL Types suppport,
[  --disable-spl-types     Disable SPL Types], yes)

if test "$PHP_SPL_TYPES" != "no"; then
  AC_DEFINE(HAVE_SPL_TYPES, 1, [Whether you want SPL Types support]) 
  PHP_NEW_EXTENSION(spl_types, php_spl_types.c spl_type.c, $ext_shared)
  PHP_ADD_EXTENSION_DEP(spl_types, spl)
fi
