dnl
dnl $Id$
dnl

PHP_ARG_WITH(yaml, [whether to enable LibYAML suppot],
[  --with-yaml[[=DIR]]       Enable LibYAML support.
                          DIR is the path to LibYAML install prefix])


if test "$PHP_YAML" != "no"; then

  AC_MSG_CHECKING([for yaml headers])
  for i in "$PHP_YAML" "$prefix" /usr /usr/local; do
    if test -r "$i/include/yaml.h"; then
      PHP_YAML_DIR=$i
      AC_MSG_RESULT([found in $i])
      break
    fi
  done
  if test -z "$PHP_YAML_DIR"; then
    AC_MSG_RESULT([not found])
    AC_MSG_ERROR([Please install libyaml])
  fi

  PHP_ADD_INCLUDE($PHP_YAML_DIR/include)
  dnl recommended flags for compilation with gcc
  dnl CFLAGS="$CFLAGS -Wall -fno-strict-aliasing"

  export OLD_CPPFLAGS="$CPPFLAGS"
  export CPPFLAGS="$CPPFLAGS $INCLUDES -DHAVE_YAML"
  AC_CHECK_HEADER([yaml.h], [], AC_MSG_ERROR(['yaml.h' header not found]))
  PHP_SUBST(YAML_SHARED_LIBADD)

  PHP_ADD_LIBRARY_WITH_PATH(yaml, $PHP_YAML_DIR/lib, YAML_SHARED_LIBADD)
  export CPPFLAGS="$OLD_CPPFLAGS"

  PHP_SUBST(YAML_SHARED_LIBADD)
  AC_DEFINE(HAVE_YAML, 1, [ ])
  PHP_NEW_EXTENSION(yaml, yaml.c parse.c emit.c detect.c , $ext_shared)
fi
