<?php

namespace PhpBrew\Patches;

use CLIFramework\Logger;
use PhpBrew\Buildable;
use PhpBrew\PatchKit\DiffPatchRule;
use PhpBrew\PatchKit\Patch;

/**
 * Fix support for libedit in PHP 5.4 to PHP 7.1.
 */
class ReadlinePatch extends Patch
{
    public function desc()
    {
        return 'fix readline detection on PHP 5.3 to PHP 7.1';
    }

    public function match(Buildable $build, Logger $logger)
    {
        return version_compare($build->getVersion(), '5.4', '>=')
            || version_compare($build->getVersion(), '7.1', '<=');
    }

    /**
     * @link https://git.php.net/?p=php-src.git;a=commit;h=1ea58b6e78355437b79fb7b1f287ba6688fb1c57
     *
     * @codeCoverageIgnore
     */
    public function rules()
    {
        return array(
            DiffPatchRule::fromPatch(
                <<<'EOP'
From: =?UTF-8?q?Ond=C5=99ej=20Sur=C3=BD?= <ondrej@sury.org>
Date: Mon, 17 Dec 2018 09:56:06 +0000
Subject: Fix rl_completion_matches detection

Also fix a typo when checking for rl_on_new_line in readline library.
---
 ext/readline/config.m4 | 17 +++++++++++++++--
 1 file changed, 15 insertions(+), 2 deletions(-)

diff --git a/ext/readline/config.m4 b/ext/readline/config.m4
index d63df8bef9..3995bd7587 100644
--- a/ext/readline/config.m4
+++ b/ext/readline/config.m4
@@ -60,13 +60,20 @@ if test "$PHP_READLINE" && test "$PHP_READLINE" != "no"; then
     -L$READLINE_DIR/$PHP_LIBDIR $PHP_READLINE_LIBS
   ])
 
-  PHP_CHECK_LIBRARY(edit, rl_on_new_line,
+  PHP_CHECK_LIBRARY(readline, rl_on_new_line,
   [
     AC_DEFINE(HAVE_RL_ON_NEW_LINE, 1, [ ])
   ],[],[
     -L$READLINE_DIR/$PHP_LIBDIR $PHP_READLINE_LIBS
   ])
 
+  PHP_CHECK_LIBRARY(readline, rl_completion_matches,
+  [
+    AC_DEFINE(HAVE_RL_COMPLETION_MATCHES, 1, [ ])
+  ],[],[
+    -L$READLINE_DIR/$PHP_LIBDIR $PHP_READLINE_LIBS
+  ])
+
   AC_DEFINE(HAVE_LIBREADLINE, 1, [ ])
 
 elif test "$PHP_LIBEDIT" != "no"; then
@@ -114,11 +121,17 @@ elif test "$PHP_LIBEDIT" != "no"; then
     -L$READLINE_DIR/$PHP_LIBDIR
   ])
 
+  PHP_CHECK_LIBRARY(edit, rl_completion_matches,
+  [
+    AC_DEFINE(HAVE_RL_COMPLETION_MATCHES, 1, [ ])
+  ],[],[
+    -L$READLINE_DIR/$PHP_LIBDIR $PHP_READLINE_LIBS
+  ])
+
   AC_DEFINE(HAVE_LIBEDIT, 1, [ ])
 fi
 
 if test "$PHP_READLINE" != "no" || test "$PHP_LIBEDIT" != "no"; then
-  AC_CHECK_FUNCS([rl_completion_matches])
   PHP_NEW_EXTENSION(readline, readline.c readline_cli.c, $ext_shared, cli)
   PHP_SUBST(READLINE_SHARED_LIBADD)
 fi
-- 
2.27.0

EOP
            )->strip(1)
        );
    }
}
