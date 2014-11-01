PHP_ARG_ENABLE(pthreads, whether to enable Threading API,
[  --enable-pthreads     Enable Threading API])
PHP_ARG_ENABLE(pthreads-pedantic, whether to enable pedantic locking,
[  --enable-pthreads-pedantic     Enable pedantic locking], no, no)

if test "$PHP_PTHREADS" != "no"; then
	AC_DEFINE(HAVE_PTHREADS, 1, [Wether you have user-land threading support])
	AC_MSG_CHECKING([checking for ZTS])   
	if test "$PHP_THREAD_SAFETY" != "no"; then
		AC_MSG_RESULT([ok])
	else
		AC_MSG_ERROR([pthreads requires ZTS, please re-compile PHP with ZTS enabled])
	fi
	if test "$PHP_PTHREADS_PEDANTIC" != "no"; then
	    AC_DEFINE(PTHREADS_PEDANTIC, 1, [Wether to use pedantic locking])
	fi
	PHP_NEW_EXTENSION(pthreads, php_pthreads.c src/lock.c src/globals.c src/prepare.c src/synchro.c src/state.c src/store.c src/resources.c src/modifiers.c src/handlers.c src/object.c, $ext_shared)
	PHP_ADD_BUILD_DIR($ext_builddir/src, 1)
	PHP_ADD_INCLUDE($ext_builddir)
	PHP_SUBST(PTHREADS_SHARED_LIBADD)
	PHP_ADD_MAKEFILE_FRAGMENT
fi
