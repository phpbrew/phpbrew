dnl $Id$

PHP_ARG_WITH(libevent, for libevent support,
[  --with-libevent             Include libevent support])

if test "$PHP_LIBEVENT" != "no"; then
  SEARCH_PATH="/usr /usr/local"
  SEARCH_FOR="/include/event.h"

  if test "$PHP_LIBEVENT" = "yes"; then
    AC_MSG_CHECKING([for libevent headers in default path])
    for i in $SEARCH_PATH ; do
      if test -r $i/$SEARCH_FOR; then
        LIBEVENT_DIR=$i
        AC_MSG_RESULT(found in $i)
      fi
    done
  else
    AC_MSG_CHECKING([for libevent headers in $PHP_LIBEVENT])
    if test -r $PHP_LIBEVENT/$SEARCH_FOR; then
      LIBEVENT_DIR=$PHP_LIBEVENT
      AC_MSG_RESULT([found])
    fi
  fi

  if test -z "$LIBEVENT_DIR"; then
    AC_MSG_RESULT([not found])
    AC_MSG_ERROR([Cannot find libevent headers])
  fi

  PHP_ADD_INCLUDE($LIBEVENT_DIR/include)

  LIBNAME=event
  LIBSYMBOL=event_base_new

  if test "x$PHP_LIBDIR" = "x"; then
    PHP_LIBDIR=lib
  fi

  PHP_CHECK_LIBRARY($LIBNAME,$LIBSYMBOL,
  [
    PHP_ADD_LIBRARY_WITH_PATH($LIBNAME, $LIBEVENT_DIR/$PHP_LIBDIR, LIBEVENT_SHARED_LIBADD)
  ],[
    AC_MSG_ERROR([wrong libevent version {1.4.+ is required} or lib not found])
  ],[
    -L$LIBEVENT_DIR/$PHP_LIBDIR 
  ])

  PHP_ADD_EXTENSION_DEP(libevent, sockets, true)
  PHP_SUBST(LIBEVENT_SHARED_LIBADD)
  PHP_NEW_EXTENSION(libevent, libevent.c, $ext_shared)
fi
