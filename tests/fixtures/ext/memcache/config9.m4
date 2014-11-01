dnl
dnl $Id: config9.m4 245380 2007-11-01 14:30:16Z mikl $
dnl

PHP_ARG_ENABLE(memcache, whether to enable memcache support,
[  --enable-memcache       Enable memcache support])

PHP_ARG_ENABLE(memcache-session, whether to enable memcache session handler support,
[  --disable-memcache-session       Disable memcache session handler support], yes, no)

if test -z "$PHP_ZLIB_DIR"; then
PHP_ARG_WITH(zlib-dir, for the location of ZLIB,
[  --with-zlib-dir[=DIR]   memcache: Set the path to ZLIB install prefix.], no, no)
fi

if test -z "$PHP_DEBUG"; then
  AC_ARG_ENABLE(debug,
  [  --enable-debug          compile with debugging symbols],[
    PHP_DEBUG=$enableval
  ],[
    PHP_DEBUG=no
  ]) 
fi

if test "$PHP_MEMCACHE" != "no"; then

  if test "$PHP_ZLIB_DIR" != "no" && test "$PHP_ZLIB_DIR" != "yes"; then
    if test -f "$PHP_ZLIB_DIR/include/zlib/zlib.h"; then
      PHP_ZLIB_DIR="$PHP_ZLIB_DIR"
      PHP_ZLIB_INCDIR="$PHP_ZLIB_DIR/include/zlib"
    elif test -f "$PHP_ZLIB_DIR/include/zlib.h"; then
      PHP_ZLIB_DIR="$PHP_ZLIB_DIR"
      PHP_ZLIB_INCDIR="$PHP_ZLIB_DIR/include"
    else
      AC_MSG_ERROR([Can't find ZLIB headers under "$PHP_ZLIB_DIR"])
    fi
  else
    for i in /usr/local /usr; do
      if test -f "$i/include/zlib/zlib.h"; then
        PHP_ZLIB_DIR="$i"
        PHP_ZLIB_INCDIR="$i/include/zlib"
      elif test -f "$i/include/zlib.h"; then
        PHP_ZLIB_DIR="$i"
        PHP_ZLIB_INCDIR="$i/include"
      fi
    done
  fi

  dnl # zlib
  AC_MSG_CHECKING([for the location of zlib])
  if test "$PHP_ZLIB_DIR" = "no"; then
    AC_MSG_ERROR([memcache support requires ZLIB. Use --with-zlib-dir=<DIR> to specify prefix where ZLIB include and library are located])
  else
    AC_MSG_RESULT([$PHP_ZLIB_DIR])
    if test "z$PHP_LIBDIR" != "z"; then
    dnl PHP5+
      PHP_ADD_LIBRARY_WITH_PATH(z, $PHP_ZLIB_DIR/$PHP_LIBDIR, MEMCACHE_SHARED_LIBADD)
    else 
    dnl PHP4
      PHP_ADD_LIBRARY_WITH_PATH(z, $PHP_ZLIB_DIR/lib, MEMCACHE_SHARED_LIBADD)
    fi
    PHP_ADD_INCLUDE($PHP_ZLIB_INCDIR)
  fi
 
  if test "$PHP_MEMCACHE_SESSION" != "no"; then 
	AC_MSG_CHECKING([for session includes])
    session_inc_path=""

    if test -f "$abs_srcdir/include/php/ext/session/php_session.h"; then
      session_inc_path="$abs_srcdir/include/php"
    elif test -f "$abs_srcdir/ext/session/php_session.h"; then
      session_inc_path="$abs_srcdir"
    elif test -f "$phpincludedir/ext/session/php_session.h"; then
      session_inc_path="$phpincludedir"
    else
      for i in php php4 php5 php6; do
        if test -f "$prefix/include/$i/ext/session/php_session.h"; then
          session_inc_path="$prefix/include/$i"
        fi
      done
    fi

    if test "$session_inc_path" = ""; then
      AC_MSG_ERROR([Cannot find php_session.h])
    else
      AC_MSG_RESULT([$session_inc_path])
    fi
  fi

  AC_MSG_CHECKING([for memcache session support])
  if test "$PHP_MEMCACHE_SESSION" != "no"; then
    AC_MSG_RESULT([enabled])
    AC_DEFINE(HAVE_MEMCACHE_SESSION,1,[Whether memcache session handler is enabled])
    AC_DEFINE(HAVE_MEMCACHE,1,[Whether you want memcache support])
    PHP_NEW_EXTENSION(memcache, memcache.c memcache_queue.c memcache_standard_hash.c memcache_consistent_hash.c memcache_session.c, $ext_shared,,-I$session_inc_path)
    ifdef([PHP_ADD_EXTENSION_DEP],
    [
      PHP_ADD_EXTENSION_DEP(memcache, session)
    ])					   
  else 
    AC_MSG_RESULT([disabled])
    AC_DEFINE(HAVE_MEMCACHE,1,[Whether you want memcache support])
    PHP_NEW_EXTENSION(memcache, memcache.c memcache_queue.c memcache_standard_hash.c memcache_consistent_hash.c, $ext_shared)
  fi

dnl this is needed to build the extension with phpize and -Wall

  if test "$PHP_DEBUG" = "yes"; then
    CFLAGS="$CFLAGS -Wall"
  fi

fi
