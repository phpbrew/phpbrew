dnl $Id: config.m4,v 1.2 2002-10-23 18:55:10 derick Exp $
dnl config.m4 for extension quickhash

PHP_ARG_ENABLE(quickhash, whether to enable quickhash support,
[  --enable-quickhash      Enable quickhash support])

if test "$PHP_QUICKHASH" != "no"; then
  PHP_NEW_EXTENSION(quickhash, quickhash.c qh_inthash.c qh_intset.c qh_intstringhash.c qh_stringinthash.c qh_iterator.c lib/quickhash.c lib/hash-algorithms.c lib/iterator.c, $ext_shared)
  PHP_SUBST(QUICKHASH_SHARED_LIBADD)
  PHP_ADD_BUILD_DIR($ext_builddir/lib, 1)
fi
