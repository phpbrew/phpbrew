<?php

namespace PhpBrew\Patches;

use CLIFramework\Logger;
use PhpBrew\Buildable;
use PhpBrew\PatchKit\DiffPatchRule;
use PhpBrew\PatchKit\Patch;

/**
 * As of recently, freetype-config has been removed in favor of pkg-config. It has been fixed in PHP 7.4beta1
 * but the older PHP sources need to be patched in order to be able to compile them on newer Ubuntu/Debian
 * distributions.
 */
class FreeTypePatch extends Patch
{
    public function desc()
    {
        return 'replace freetype-config with pkg-config on php older than 7.4';
    }

    public function match(Buildable $build, Logger $logger)
    {
        return $build->isEnabledVariant('gd') && version_compare($build->getVersion(), '7.4', '<=');
    }

    /**
     * @link https://git.archlinux.org/svntogit/packages.git/commit/?id=65fd0dc1649537b7fd1d539b769251aacec88719
     */
    public function rules()
    {
        return array(
            DiffPatchRule::fromPatch(
                <<<'EOP'
diff -u -r php-7.2.5/ext/gd/config.m4 php-7.2.5-freetype/ext/gd/config.m4
--- php-7.2.5/ext/gd/config.m4	2018-04-24 17:09:54.000000000 +0200
+++ php-7.2.5-freetype/ext/gd/config.m4	2018-05-09 14:49:03.647108948 +0200
@@ -186,6 +186,9 @@
 AC_DEFUN([PHP_GD_FREETYPE2],[
   if test "$PHP_FREETYPE_DIR" != "no"; then
 
+    AC_PATH_PROG(PKG_CONFIG, pkg-config, no)
+
+    AC_MSG_CHECKING([for freetype])
     for i in $PHP_FREETYPE_DIR /usr/local /usr; do
       if test -f "$i/bin/freetype-config"; then
         FREETYPE2_DIR=$i
@@ -194,13 +197,20 @@
       fi
     done
 
-    if test -z "$FREETYPE2_DIR"; then
+    if test -n "$FREETYPE2_CONFIG"; then
+      FREETYPE2_CFLAGS=`$FREETYPE2_CONFIG --cflags`
+      FREETYPE2_LIBS=`$FREETYPE2_CONFIG --libs`
+      AC_MSG_RESULT([found in $FREETYPE2_DIR])
+    elif test "$PKG_CONFIG" != "no" && $PKG_CONFIG --exists freetype2; then
+      FREETYPE2_DIR=pkg-config
+      FREETYPE2_CFLAGS=`$PKG_CONFIG freetype2 --cflags`
+      FREETYPE2_LIBS=`$PKG_CONFIG freetype2 --libs`
+      AC_MSG_RESULT([found by pkg-config])
+    else
+      AC_MSG_RESULT([not found])
       AC_MSG_ERROR([freetype-config not found.])
     fi
 
-    FREETYPE2_CFLAGS=`$FREETYPE2_CONFIG --cflags`
-    FREETYPE2_LIBS=`$FREETYPE2_CONFIG --libs`
-
     PHP_EVAL_INCLINE($FREETYPE2_CFLAGS)
     PHP_EVAL_LIBLINE($FREETYPE2_LIBS, GD_SHARED_LIBADD)
     AC_DEFINE(HAVE_LIBFREETYPE,1,[ ])
EOP
            )->strip(1)
        );
    }
}
