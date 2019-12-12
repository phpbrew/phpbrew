<?php

namespace PhpBrew\Patches;

use CLIFramework\Logger;
use PhpBrew\Buildable;
use PhpBrew\PatchKit\DiffPatchRule;
use PhpBrew\PatchKit\Patch;

class PHP53Patch extends Patch
{
    public function desc()
    {
        return 'php5.3.29 multi-sapi';
    }

    public function match(Buildable $build, Logger $logger)
    {
        // todo: not sure if this works for linux?
        return $build->osName === 'Darwin' && version_compare($build->getVersion(), '5.3.29') === 0;
    }

    /**
     * phpcs:disable Generic.Files.LineLength.TooLong
     *
     * @link https://gist.github.com/javian/bfcbd5bc5874ee9c539fb3d642cdce3e
     */
    public function rules()
    {
        return array(
            DiffPatchRule::fromPatch(
                <<<'EOP'
diff --git a/Makefile.global b/Makefile.global
index 3a5b1c2..7450162 100644
--- a/Makefile.global
+++ b/Makefile.global
@@ -13,6 +13,8 @@ all: $(all_targets)
 	
 build-modules: $(PHP_MODULES) $(PHP_ZEND_EX)
 
+build-binaries: $(PHP_BINARIES)
+
 libphp$(PHP_MAJOR_VERSION).la: $(PHP_GLOBAL_OBJS) $(PHP_SAPI_OBJS)
 	$(LIBTOOL) --mode=link $(CC) $(CFLAGS) $(EXTRA_CFLAGS) -rpath $(phptempdir) $(EXTRA_LDFLAGS) $(LDFLAGS) $(PHP_RPATHS) $(PHP_GLOBAL_OBJS) $(PHP_SAPI_OBJS) $(EXTRA_LIBS) $(ZEND_EXTRA_LIBS) -o $@
 	-@$(LIBTOOL) --silent --mode=install cp $@ $(phptempdir)/$@ >/dev/null 2>&1
@@ -35,6 +37,8 @@ install-sapi: $(OVERALL_TARGET)
 	fi
 	@$(INSTALL_IT)
 
+install-binaries: build-binaries $(install_binary_targets)
+
 install-modules: build-modules
 	@test -d modules && \
 	$(mkinstalldirs) $(INSTALL_ROOT)$(EXTENSION_DIR)
diff --git a/Zend/acinclude.m4 b/Zend/acinclude.m4
index 77430ab..5aeed94 100644
--- a/Zend/acinclude.m4
+++ b/Zend/acinclude.m4
@@ -17,12 +17,7 @@ AC_DEFUN([LIBZEND_BISON_CHECK],[
       if test -n "$bison_version_vars"; then
         set $bison_version_vars
         bison_version="${1}.${2}"
-        for bison_check_version in $bison_version_list; do
-          if test "$bison_version" = "$bison_check_version"; then
-            php_cv_bison_version="$bison_check_version (ok)"
-            break
-          fi
-        done
+        php_cv_bison_version="$bison_version (ok)"
       fi
     ])
   fi
diff --git a/Zend/zend_language_parser.y b/Zend/zend_language_parser.y
index d24fc9c..4314efb 100644
--- a/Zend/zend_language_parser.y
+++ b/Zend/zend_language_parser.y
@@ -38,10 +38,6 @@
 
 #define YYERROR_VERBOSE
 #define YYSTYPE znode
-#ifdef ZTS
-# define YYPARSE_PARAM tsrm_ls
-# define YYLEX_PARAM tsrm_ls
-#endif
 
 
 %}
@@ -49,6 +45,13 @@
 %pure_parser
 %expect 2
 
+%code requires {
+#ifdef ZTS
+# define YYPARSE_PARAM tsrm_ls
+# define YYLEX_PARAM tsrm_ls
+#endif
+}
+
 %left T_INCLUDE T_INCLUDE_ONCE T_EVAL T_REQUIRE T_REQUIRE_ONCE
 %left ','
 %left T_LOGICAL_OR
diff --git a/acinclude.m4 b/acinclude.m4
index 2681b79..605a9ba 100644
--- a/acinclude.m4
+++ b/acinclude.m4
@@ -194,7 +194,7 @@ dnl the path is interpreted relative to the top build-directory.
 dnl
 dnl which array to append to?
 AC_DEFUN([PHP_ADD_SOURCES],[
-  PHP_ADD_SOURCES_X($1, $2, $3, ifelse($4,cli,PHP_CLI_OBJS,ifelse($4,sapi,PHP_SAPI_OBJS,PHP_GLOBAL_OBJS)))
+  PHP_ADD_SOURCES_X($1, $2, $3, ifelse($4,sapi,PHP_SAPI_OBJS,PHP_GLOBAL_OBJS))
 ])
 
 dnl
@@ -772,7 +772,7 @@ dnl
 AC_DEFUN([PHP_BUILD_SHARED],[
   PHP_BUILD_PROGRAM
   OVERALL_TARGET=libphp[]$PHP_MAJOR_VERSION[.la]
-  php_build_target=shared
+  php_sapi_module=shared
   
   php_c_pre=$shared_c_pre
   php_c_meta=$shared_c_meta
@@ -789,7 +789,7 @@ dnl
 AC_DEFUN([PHP_BUILD_STATIC],[
   PHP_BUILD_PROGRAM
   OVERALL_TARGET=libphp[]$PHP_MAJOR_VERSION[.la]
-  php_build_target=static
+  php_sapi_module=static
 ])
 
 dnl
@@ -798,14 +798,13 @@ dnl
 AC_DEFUN([PHP_BUILD_BUNDLE],[
   PHP_BUILD_PROGRAM
   OVERALL_TARGET=libs/libphp[]$PHP_MAJOR_VERSION[.bundle]
-  php_build_target=static
+  php_sapi_module=static
 ])
 
 dnl
 dnl PHP_BUILD_PROGRAM
 dnl
 AC_DEFUN([PHP_BUILD_PROGRAM],[
-  OVERALL_TARGET=[]ifelse($1,,php,$1)
   php_c_pre='$(LIBTOOL) --mode=compile $(CC)'
   php_c_meta='$(COMMON_FLAGS) $(CFLAGS_CLEAN) $(EXTRA_CFLAGS)'
   php_c_post=
@@ -826,8 +825,6 @@ AC_DEFUN([PHP_BUILD_PROGRAM],[
   shared_cxx_meta='$(COMMON_FLAGS) $(CXXFLAGS_CLEAN) $(EXTRA_CXXFLAGS) '$pic_setting
   shared_cxx_post=
   shared_lo=lo
-
-  php_build_target=program
 ])
 
 dnl
@@ -873,32 +870,46 @@ EOF
 dnl
 dnl PHP_SELECT_SAPI(name, type[, sources [, extra-cflags [, build-target]]])
 dnl
-dnl Selects the SAPI name and type (static, shared, programm)
+dnl Selects the SAPI name and type (static, shared, bundle, program)
 dnl and optionally also the source-files for the SAPI-specific
 dnl objects.
 dnl
 AC_DEFUN([PHP_SELECT_SAPI],[
-  if test "$PHP_SAPI" != "default"; then
-AC_MSG_ERROR([
+  if test "$2" = "program"; then
+    PHP_BINARIES="$PHP_BINARIES $1"
+  elif test "$PHP_SAPI" != "none"; then
+    AC_MSG_ERROR([
 +--------------------------------------------------------------------+
 |                        *** ATTENTION ***                           |
 |                                                                    |
 | You've configured multiple SAPIs to be build. You can build only   |
-| one SAPI module and CLI binary at the same time.                   |
+| one SAPI module plus CGI, CLI and FPM binaries at the same time.   |
 +--------------------------------------------------------------------+
 ])
+  else
+    PHP_SAPI=$1
   fi
 
-  PHP_SAPI=$1
-  
+  PHP_ADD_BUILD_DIR([sapi/$1])
+
+  PHP_INSTALLED_SAPIS="$PHP_INSTALLED_SAPIS $1"
+
   case "$2" in
   static[)] PHP_BUILD_STATIC;;
   shared[)] PHP_BUILD_SHARED;;
   bundle[)] PHP_BUILD_BUNDLE;;
-  program[)] PHP_BUILD_PROGRAM($5);;
+  program[)] PHP_BUILD_PROGRAM;;
   esac
     
-  ifelse($3,,,[PHP_ADD_SOURCES([sapi/$1],[$3],[$4],[sapi])])
+  ifelse($2,program,[
+    install_binaries="install-binaries"
+    install_binary_targets="$install_binary_targets install-$1"
+    PHP_SUBST(PHP_[]translit($1,a-z0-9-,A-Z0-9_)[]_OBJS)
+    ifelse($3,,,[PHP_ADD_SOURCES_X([sapi/$1],[$3],[$4],PHP_[]translit($1,a-z0-9-,A-Z0-9_)[]_OBJS)])
+  ],[
+    install_sapi="install-sapi"
+    ifelse($3,,,[PHP_ADD_SOURCES([sapi/$1],[$3],[$4],[sapi])])
+  ])
 ])
 
 dnl deprecated
diff --git a/aclocal.m4 b/aclocal.m4
index 07b5e61..d114137 100644
--- a/aclocal.m4
+++ b/aclocal.m4
@@ -194,7 +194,7 @@ dnl the path is interpreted relative to the top build-directory.
 dnl
 dnl which array to append to?
 AC_DEFUN([PHP_ADD_SOURCES],[
-  PHP_ADD_SOURCES_X($1, $2, $3, ifelse($4,cli,PHP_CLI_OBJS,ifelse($4,sapi,PHP_SAPI_OBJS,PHP_GLOBAL_OBJS)))
+  PHP_ADD_SOURCES_X($1, $2, $3, ifelse($4,sapi,PHP_SAPI_OBJS,PHP_GLOBAL_OBJS))
 ])
 
 dnl
@@ -772,7 +772,7 @@ dnl
 AC_DEFUN([PHP_BUILD_SHARED],[
   PHP_BUILD_PROGRAM
   OVERALL_TARGET=libphp[]$PHP_MAJOR_VERSION[.la]
-  php_build_target=shared
+  php_sapi_module=shared
   
   php_c_pre=$shared_c_pre
   php_c_meta=$shared_c_meta
@@ -789,7 +789,7 @@ dnl
 AC_DEFUN([PHP_BUILD_STATIC],[
   PHP_BUILD_PROGRAM
   OVERALL_TARGET=libphp[]$PHP_MAJOR_VERSION[.la]
-  php_build_target=static
+  php_sapi_module=static
 ])
 
 dnl
@@ -798,14 +798,13 @@ dnl
 AC_DEFUN([PHP_BUILD_BUNDLE],[
   PHP_BUILD_PROGRAM
   OVERALL_TARGET=libs/libphp[]$PHP_MAJOR_VERSION[.bundle]
-  php_build_target=static
+  php_sapi_module=static
 ])
 
 dnl
 dnl PHP_BUILD_PROGRAM
 dnl
 AC_DEFUN([PHP_BUILD_PROGRAM],[
-  OVERALL_TARGET=[]ifelse($1,,php,$1)
   php_c_pre='$(LIBTOOL) --mode=compile $(CC)'
   php_c_meta='$(COMMON_FLAGS) $(CFLAGS_CLEAN) $(EXTRA_CFLAGS)'
   php_c_post=
@@ -826,8 +825,6 @@ AC_DEFUN([PHP_BUILD_PROGRAM],[
   shared_cxx_meta='$(COMMON_FLAGS) $(CXXFLAGS_CLEAN) $(EXTRA_CXXFLAGS) '$pic_setting
   shared_cxx_post=
   shared_lo=lo
-
-  php_build_target=program
 ])
 
 dnl
@@ -873,32 +870,46 @@ EOF
 dnl
 dnl PHP_SELECT_SAPI(name, type[, sources [, extra-cflags [, build-target]]])
 dnl
-dnl Selects the SAPI name and type (static, shared, programm)
+dnl Selects the SAPI name and type (static, shared, bundle, program)
 dnl and optionally also the source-files for the SAPI-specific
 dnl objects.
 dnl
 AC_DEFUN([PHP_SELECT_SAPI],[
-  if test "$PHP_SAPI" != "default"; then
-AC_MSG_ERROR([
+  if test "$2" = "program"; then
+    PHP_BINARIES="$PHP_BINARIES $1"
+  elif test "$PHP_SAPI" != "none"; then
+    AC_MSG_ERROR([
 +--------------------------------------------------------------------+
 |                        *** ATTENTION ***                           |
 |                                                                    |
 | You've configured multiple SAPIs to be build. You can build only   |
-| one SAPI module and CLI binary at the same time.                   |
+| one SAPI module plus CGI, CLI and FPM binaries at the same time.   |
 +--------------------------------------------------------------------+
 ])
+  else
+    PHP_SAPI=$1
   fi
 
-  PHP_SAPI=$1
-  
+  PHP_ADD_BUILD_DIR([sapi/$1])
+
+  PHP_INSTALLED_SAPIS="$PHP_INSTALLED_SAPIS $1"
+
   case "$2" in
   static[)] PHP_BUILD_STATIC;;
   shared[)] PHP_BUILD_SHARED;;
   bundle[)] PHP_BUILD_BUNDLE;;
-  program[)] PHP_BUILD_PROGRAM($5);;
+  program[)] PHP_BUILD_PROGRAM;;
   esac
     
-  ifelse($3,,,[PHP_ADD_SOURCES([sapi/$1],[$3],[$4],[sapi])])
+  ifelse($2,program,[
+    install_binaries="install-binaries"
+    install_binary_targets="$install_binary_targets install-$1"
+    PHP_SUBST(PHP_[]translit($1,a-z0-9-,A-Z0-9_)[]_OBJS)
+    ifelse($3,,,[PHP_ADD_SOURCES_X([sapi/$1],[$3],[$4],PHP_[]translit($1,a-z0-9-,A-Z0-9_)[]_OBJS)])
+  ],[
+    install_sapi="install-sapi"
+    ifelse($3,,,[PHP_ADD_SOURCES([sapi/$1],[$3],[$4],[sapi])])
+  ])
 ])
 
 dnl deprecated
diff --git a/configure b/configure
index c1e8bc2..1cfa6fa 100755
--- a/configure
+++ b/configure
@@ -3019,12 +3019,7 @@ else
       if test -n "$bison_version_vars"; then
         set $bison_version_vars
         bison_version="${1}.${2}"
-        for bison_check_version in $bison_version_list; do
-          if test "$bison_version" = "$bison_check_version"; then
-            php_cv_bison_version="$bison_check_version (ok)"
-            break
-          fi
-        done
+        php_cv_bison_version="$bison_version (ok)"
       fi
     
 fi
@@ -3268,7 +3263,6 @@ echo "$ac_t""$ac_cv_gcc_arg_no_cpp_precomp" 1>&6
     ;;
   *netware*)
     
-  OVERALL_TARGET=php
   php_c_pre='$(LIBTOOL) --mode=compile $(CC)'
   php_c_meta='$(COMMON_FLAGS) $(CFLAGS_CLEAN) $(EXTRA_CFLAGS)'
   php_c_post=
@@ -3290,8 +3284,6 @@ echo "$ac_t""$ac_cv_gcc_arg_no_cpp_precomp" 1>&6
   shared_cxx_post=
   shared_lo=lo
 
-  php_build_target=program
-
     
   
   case /main in
@@ -3424,7 +3416,7 @@ esac
 # Disable PIC mode by default where it is known to be safe to do so,
 # to avoid the performance hit from the lost register
 echo $ac_n "checking whether to force non-PIC code in shared modules""... $ac_c" 1>&6
-echo "configure:3428: checking whether to force non-PIC code in shared modules" >&5
+echo "configure:3425: checking whether to force non-PIC code in shared modules" >&5
 case $host_alias in
   i?86-*-linux*|i?86-*-freebsd*)
     if test "${with_pic+set}" != "set" || test "$with_pic" = "no"; then
@@ -3454,7 +3446,7 @@ esac
 
 
 echo $ac_n "checking whether /dev/urandom exists""... $ac_c" 1>&6
-echo "configure:3458: checking whether /dev/urandom exists" >&5 
+echo "configure:3455: checking whether /dev/urandom exists" >&5 
 if test -r "/dev/urandom" && test -c "/dev/urandom"; then 
   cat >> confdefs.h <<\EOF
 #define HAVE_DEV_URANDOM 1
@@ -3515,7 +3507,7 @@ if test "$cross_compiling" = yes; then
 
 else
   cat > conftest.$ac_ext <<EOF
-#line 3519 "configure"
+#line 3516 "configure"
 #include "confdefs.h"
 
 #include <pthread.h>
@@ -3533,7 +3525,7 @@ int main() {
     return pthread_create(&thd, NULL, thread_routine, &data);
 } 
 EOF
-if { (eval echo configure:3537: \"$ac_link\") 1>&5; (eval $ac_link) 2>&5; } && test -s conftest${ac_exeext} && (./conftest; exit) 2>/dev/null
+if { (eval echo configure:3534: \"$ac_link\") 1>&5; (eval $ac_link) 2>&5; } && test -s conftest${ac_exeext} && (./conftest; exit) 2>/dev/null
 then
   
   pthreads_working=yes
@@ -3553,7 +3545,7 @@ fi
   CFLAGS=$save_CFLAGS
 
   echo $ac_n "checking for pthreads_cflags""... $ac_c" 1>&6
-echo "configure:3557: checking for pthreads_cflags" >&5
+echo "configure:3554: checking for pthreads_cflags" >&5
 if eval "test \"`echo '$''{'ac_cv_pthreads_cflags'+set}'`\" = set"; then
   echo $ac_n "(cached) $ac_c" 1>&6
 else
@@ -3575,7 +3567,7 @@ if test "$cross_compiling" = yes; then
 
 else
   cat > conftest.$ac_ext <<EOF
-#line 3579 "configure"
+#line 3576 "configure"
 #include "confdefs.h"
 
 #include <pthread.h>
@@ -3593,7 +3585,7 @@ int main() {
     return pthread_create(&thd, NULL, thread_routine, &data);
 } 
 EOF
-if { (eval echo configure:3597: \"$ac_link\") 1>&5; (eval $ac_link) 2>&5; } && test -s conftest${ac_exeext} && (./conftest; exit) 2>/dev/null
+if { (eval echo configure:3594: \"$ac_link\") 1>&5; (eval $ac_link) 2>&5; } && test -s conftest${ac_exeext} && (./conftest; exit) 2>/dev/null
 then
   
   pthreads_working=yes
@@ -3623,7 +3615,7 @@ fi
 echo "$ac_t""$ac_cv_pthreads_cflags" 1>&6
 
 echo $ac_n "checking for pthreads_lib""... $ac_c" 1>&6
-echo "configure:3627: checking for pthreads_lib" >&5
+echo "configure:3624: checking for pthreads_lib" >&5
 if eval "test \"`echo '$''{'ac_cv_pthreads_lib'+set}'`\" = set"; then
   echo $ac_n "(cached) $ac_c" 1>&6
 else
@@ -3645,7 +3637,7 @@ if test "$cross_compiling" = yes; then
 
 else
   cat > conftest.$ac_ext <<EOF
-#line 3649 "configure"
+#line 3646 "configure"
 #include "confdefs.h"
 
 #include <pthread.h>
@@ -3663,7 +3655,7 @@ int main() {
     return pthread_create(&thd, NULL, thread_routine, &data);
 } 
 EOF
-if { (eval echo configure:3667: \"$ac_link\") 1>&5; (eval $ac_link) 2>&5; } && test -s conftest${ac_exeext} && (./conftest; exit) 2>/dev/null
+if { (eval echo configure:3664: \"$ac_link\") 1>&5; (eval $ac_link) 2>&5; } && test -s conftest${ac_exeext} && (./conftest; exit) 2>/dev/null
 then
   
   pthreads_working=yes
@@ -3731,9 +3723,7 @@ fi
    ;;
  esac
 
-PHP_SAPI=default
 
-  OVERALL_TARGET=php
   php_c_pre='$(LIBTOOL) --mode=compile $(CC)'
   php_c_meta='$(COMMON_FLAGS) $(CFLAGS_CLEAN) $(EXTRA_CFLAGS)'
   php_c_post=
@@ -3755,8 +3745,7 @@ PHP_SAPI=default
   shared_cxx_post=
   shared_lo=lo
 
-  php_build_target=program
-
+PHP_SAPI=none
 
 
 
@@ -3794,7 +3783,7 @@ ext_output=$PHP_AOLSERVER
 
 
 echo $ac_n "checking for AOLserver support""... $ac_c" 1>&6
-echo "configure:3798: checking for AOLserver support" >&5
+echo "configure:3792: checking for AOLserver support" >&5
 
 if test "$PHP_AOLSERVER" != "no"; then
   if test -d "$PHP_AOLSERVER/include"; then
@@ -3848,23 +3837,32 @@ if test "$PHP_AOLSERVER" != "no"; then
 EOF
 
   
-  if test "$PHP_SAPI" != "default"; then
-{ echo "configure: error: 
+  if test "shared" = "program"; then
+    PHP_BINARIES="$PHP_BINARIES aolserver"
+  elif test "$PHP_SAPI" != "none"; then
+    { echo "configure: error: 
 +--------------------------------------------------------------------+
 |                        *** ATTENTION ***                           |
 |                                                                    |
 | You've configured multiple SAPIs to be build. You can build only   |
-| one SAPI module and CLI binary at the same time.                   |
+| one SAPI module plus CGI, CLI and FPM binaries at the same time.   |
 +--------------------------------------------------------------------+
 " 1>&2; exit 1; }
+  else
+    PHP_SAPI=aolserver
   fi
 
-  PHP_SAPI=aolserver
   
+  
+    BUILD_DIR="$BUILD_DIR sapi/aolserver"
+  
+
+
+  PHP_INSTALLED_SAPIS="$PHP_INSTALLED_SAPIS aolserver"
+
   case "shared" in
   static) 
   
-  OVERALL_TARGET=php
   php_c_pre='$(LIBTOOL) --mode=compile $(CC)'
   php_c_meta='$(COMMON_FLAGS) $(CFLAGS_CLEAN) $(EXTRA_CFLAGS)'
   php_c_post=
@@ -3886,14 +3884,11 @@ EOF
   shared_cxx_post=
   shared_lo=lo
 
-  php_build_target=program
-
   OVERALL_TARGET=libphp$PHP_MAJOR_VERSION.la
-  php_build_target=static
+  php_sapi_module=static
 ;;
   shared) 
   
-  OVERALL_TARGET=php
   php_c_pre='$(LIBTOOL) --mode=compile $(CC)'
   php_c_meta='$(COMMON_FLAGS) $(CFLAGS_CLEAN) $(EXTRA_CFLAGS)'
   php_c_post=
@@ -3915,10 +3910,8 @@ EOF
   shared_cxx_post=
   shared_lo=lo
 
-  php_build_target=program
-
   OVERALL_TARGET=libphp$PHP_MAJOR_VERSION.la
-  php_build_target=shared
+  php_sapi_module=shared
   
   php_c_pre=$shared_c_pre
   php_c_meta=$shared_c_meta
@@ -3930,7 +3923,6 @@ EOF
 ;;
   bundle) 
   
-  OVERALL_TARGET=php
   php_c_pre='$(LIBTOOL) --mode=compile $(CC)'
   php_c_meta='$(COMMON_FLAGS) $(CFLAGS_CLEAN) $(EXTRA_CFLAGS)'
   php_c_post=
@@ -3952,13 +3944,10 @@ EOF
   shared_cxx_post=
   shared_lo=lo
 
-  php_build_target=program
-
   OVERALL_TARGET=libs/libphp$PHP_MAJOR_VERSION.bundle
-  php_build_target=static
+  php_sapi_module=static
 ;;
   program) 
-  OVERALL_TARGET=php
   php_c_pre='$(LIBTOOL) --mode=compile $(CC)'
   php_c_meta='$(COMMON_FLAGS) $(CFLAGS_CLEAN) $(EXTRA_CFLAGS)'
   php_c_post=
@@ -3979,12 +3968,12 @@ EOF
   shared_cxx_meta='$(COMMON_FLAGS) $(CXXFLAGS_CLEAN) $(EXTRA_CXXFLAGS) '$pic_setting
   shared_cxx_post=
   shared_lo=lo
-
-  php_build_target=program
 ;;
   esac
     
   
+    install_sapi="install-sapi"
+    
   
   case sapi/aolserver in
   "") ac_srcdir="$abs_srcdir/"; unset ac_bdir; ac_inc="-I. -I$abs_srcdir" ;;
@@ -4027,6 +4016,7 @@ EOF
   done
 
 
+  
 
   INSTALL_IT="\$(INSTALL) -m 0755 $SAPI_SHARED \$(INSTALL_ROOT)$PHP_AOLSERVER/bin/"
 fi
@@ -4059,7 +4049,7 @@ ext_output=$PHP_APXS
 
 
 echo $ac_n "checking for Apache 1.x module support via DSO through APXS""... $ac_c" 1>&6
-echo "configure:4063: checking for Apache 1.x module support via DSO through APXS" >&5
+echo "configure:4058: checking for Apache 1.x module support via DSO through APXS" >&5
 
 if test "$PHP_APXS" != "no"; then
   if test "$PHP_APXS" = "yes"; then
@@ -4144,23 +4134,32 @@ IFS="- /.
   esac
 
   
-  if test "$PHP_SAPI" != "default"; then
-{ echo "configure: error: 
+  if test "$build_type" = "program"; then
+    PHP_BINARIES="$PHP_BINARIES apache"
+  elif test "$PHP_SAPI" != "none"; then
+    { echo "configure: error: 
 +--------------------------------------------------------------------+
 |                        *** ATTENTION ***                           |
 |                                                                    |
 | You've configured multiple SAPIs to be build. You can build only   |
-| one SAPI module and CLI binary at the same time.                   |
+| one SAPI module plus CGI, CLI and FPM binaries at the same time.   |
 +--------------------------------------------------------------------+
 " 1>&2; exit 1; }
+  else
+    PHP_SAPI=apache
   fi
 
-  PHP_SAPI=apache
   
+  
+    BUILD_DIR="$BUILD_DIR sapi/apache"
+  
+
+
+  PHP_INSTALLED_SAPIS="$PHP_INSTALLED_SAPIS apache"
+
   case "$build_type" in
   static) 
   
-  OVERALL_TARGET=php
   php_c_pre='$(LIBTOOL) --mode=compile $(CC)'
   php_c_meta='$(COMMON_FLAGS) $(CFLAGS_CLEAN) $(EXTRA_CFLAGS)'
   php_c_post=
@@ -4182,14 +4181,11 @@ IFS="- /.
   shared_cxx_post=
   shared_lo=lo
 
-  php_build_target=program
-
   OVERALL_TARGET=libphp$PHP_MAJOR_VERSION.la
-  php_build_target=static
+  php_sapi_module=static
 ;;
   shared) 
   
-  OVERALL_TARGET=php
   php_c_pre='$(LIBTOOL) --mode=compile $(CC)'
   php_c_meta='$(COMMON_FLAGS) $(CFLAGS_CLEAN) $(EXTRA_CFLAGS)'
   php_c_post=
@@ -4211,10 +4207,8 @@ IFS="- /.
   shared_cxx_post=
   shared_lo=lo
 
-  php_build_target=program
-
   OVERALL_TARGET=libphp$PHP_MAJOR_VERSION.la
-  php_build_target=shared
+  php_sapi_module=shared
   
   php_c_pre=$shared_c_pre
   php_c_meta=$shared_c_meta
@@ -4226,7 +4220,6 @@ IFS="- /.
 ;;
   bundle) 
   
-  OVERALL_TARGET=php
   php_c_pre='$(LIBTOOL) --mode=compile $(CC)'
   php_c_meta='$(COMMON_FLAGS) $(CFLAGS_CLEAN) $(EXTRA_CFLAGS)'
   php_c_post=
@@ -4248,13 +4241,10 @@ IFS="- /.
   shared_cxx_post=
   shared_lo=lo
 
-  php_build_target=program
-
   OVERALL_TARGET=libs/libphp$PHP_MAJOR_VERSION.bundle
-  php_build_target=static
+  php_sapi_module=static
 ;;
   program) 
-  OVERALL_TARGET=php
   php_c_pre='$(LIBTOOL) --mode=compile $(CC)'
   php_c_meta='$(COMMON_FLAGS) $(CFLAGS_CLEAN) $(EXTRA_CFLAGS)'
   php_c_post=
@@ -4275,12 +4265,12 @@ IFS="- /.
   shared_cxx_meta='$(COMMON_FLAGS) $(CXXFLAGS_CLEAN) $(EXTRA_CXXFLAGS) '$pic_setting
   shared_cxx_post=
   shared_lo=lo
-
-  php_build_target=program
 ;;
   esac
     
   
+    install_sapi="install-sapi"
+    
   
   case sapi/apache in
   "") ac_srcdir="$abs_srcdir/"; unset ac_bdir; ac_inc="-I. -I$abs_srcdir" ;;
@@ -4323,6 +4313,7 @@ EOF
   done
 
 
+  
 
 
   # Test whether apxs support -S option
@@ -4390,7 +4381,7 @@ ext_output=$PHP_APACHE
 
 
 echo $ac_n "checking for Apache 1.x module support""... $ac_c" 1>&6
-echo "configure:4394: checking for Apache 1.x module support" >&5
+echo "configure:4390: checking for Apache 1.x module support" >&5
 
 if test "$PHP_SAPI" != "apache" && test "$PHP_APACHE" != "no"; then
   
@@ -4422,23 +4413,32 @@ EOF
     APACHE_INCLUDE=-I$PHP_APACHE/src
     APACHE_TARGET=$PHP_APACHE/src
     
-  if test "$PHP_SAPI" != "default"; then
-{ echo "configure: error: 
+  if test "static" = "program"; then
+    PHP_BINARIES="$PHP_BINARIES apache"
+  elif test "$PHP_SAPI" != "none"; then
+    { echo "configure: error: 
 +--------------------------------------------------------------------+
 |                        *** ATTENTION ***                           |
 |                                                                    |
 | You've configured multiple SAPIs to be build. You can build only   |
-| one SAPI module and CLI binary at the same time.                   |
+| one SAPI module plus CGI, CLI and FPM binaries at the same time.   |
 +--------------------------------------------------------------------+
 " 1>&2; exit 1; }
+  else
+    PHP_SAPI=apache
   fi
 
-  PHP_SAPI=apache
   
+  
+    BUILD_DIR="$BUILD_DIR sapi/apache"
+  
+
+
+  PHP_INSTALLED_SAPIS="$PHP_INSTALLED_SAPIS apache"
+
   case "static" in
   static) 
   
-  OVERALL_TARGET=php
   php_c_pre='$(LIBTOOL) --mode=compile $(CC)'
   php_c_meta='$(COMMON_FLAGS) $(CFLAGS_CLEAN) $(EXTRA_CFLAGS)'
   php_c_post=
@@ -4460,14 +4460,11 @@ EOF
   shared_cxx_post=
   shared_lo=lo
 
-  php_build_target=program
-
   OVERALL_TARGET=libphp$PHP_MAJOR_VERSION.la
-  php_build_target=static
+  php_sapi_module=static
 ;;
   shared) 
   
-  OVERALL_TARGET=php
   php_c_pre='$(LIBTOOL) --mode=compile $(CC)'
   php_c_meta='$(COMMON_FLAGS) $(CFLAGS_CLEAN) $(EXTRA_CFLAGS)'
   php_c_post=
@@ -4489,10 +4486,8 @@ EOF
   shared_cxx_post=
   shared_lo=lo
 
-  php_build_target=program
-
   OVERALL_TARGET=libphp$PHP_MAJOR_VERSION.la
-  php_build_target=shared
+  php_sapi_module=shared
   
   php_c_pre=$shared_c_pre
   php_c_meta=$shared_c_meta
@@ -4504,7 +4499,6 @@ EOF
 ;;
   bundle) 
   
-  OVERALL_TARGET=php
   php_c_pre='$(LIBTOOL) --mode=compile $(CC)'
   php_c_meta='$(COMMON_FLAGS) $(CFLAGS_CLEAN) $(EXTRA_CFLAGS)'
   php_c_post=
@@ -4526,13 +4520,10 @@ EOF
   shared_cxx_post=
   shared_lo=lo
 
-  php_build_target=program
-
   OVERALL_TARGET=libs/libphp$PHP_MAJOR_VERSION.bundle
-  php_build_target=static
+  php_sapi_module=static
 ;;
   program) 
-  OVERALL_TARGET=php
   php_c_pre='$(LIBTOOL) --mode=compile $(CC)'
   php_c_meta='$(COMMON_FLAGS) $(CFLAGS_CLEAN) $(EXTRA_CFLAGS)'
   php_c_post=
@@ -4553,12 +4544,12 @@ EOF
   shared_cxx_meta='$(COMMON_FLAGS) $(CXXFLAGS_CLEAN) $(EXTRA_CXXFLAGS) '$pic_setting
   shared_cxx_post=
   shared_lo=lo
-
-  php_build_target=program
 ;;
   esac
     
   
+    install_sapi="install-sapi"
+    
   
   case sapi/apache in
   "") ac_srcdir="$abs_srcdir/"; unset ac_bdir; ac_inc="-I. -I$abs_srcdir" ;;
@@ -4601,6 +4592,7 @@ EOF
   done
 
 
+  
 
     APACHE_INSTALL="mkdir -p $APACHE_TARGET; cp $SAPI_STATIC $APACHE_INSTALL_FILES $APACHE_TARGET"
     PHP_LIBS="-L. -lphp3"
@@ -4624,23 +4616,32 @@ EOF
       mkdir $APACHE_TARGET
     fi
     
-  if test "$PHP_SAPI" != "default"; then
-{ echo "configure: error: 
+  if test "static" = "program"; then
+    PHP_BINARIES="$PHP_BINARIES apache"
+  elif test "$PHP_SAPI" != "none"; then
+    { echo "configure: error: 
 +--------------------------------------------------------------------+
 |                        *** ATTENTION ***                           |
 |                                                                    |
 | You've configured multiple SAPIs to be build. You can build only   |
-| one SAPI module and CLI binary at the same time.                   |
+| one SAPI module plus CGI, CLI and FPM binaries at the same time.   |
 +--------------------------------------------------------------------+
 " 1>&2; exit 1; }
+  else
+    PHP_SAPI=apache
   fi
 
-  PHP_SAPI=apache
   
+  
+    BUILD_DIR="$BUILD_DIR sapi/apache"
+  
+
+
+  PHP_INSTALLED_SAPIS="$PHP_INSTALLED_SAPIS apache"
+
   case "static" in
   static) 
   
-  OVERALL_TARGET=php
   php_c_pre='$(LIBTOOL) --mode=compile $(CC)'
   php_c_meta='$(COMMON_FLAGS) $(CFLAGS_CLEAN) $(EXTRA_CFLAGS)'
   php_c_post=
@@ -4662,14 +4663,11 @@ EOF
   shared_cxx_post=
   shared_lo=lo
 
-  php_build_target=program
-
   OVERALL_TARGET=libphp$PHP_MAJOR_VERSION.la
-  php_build_target=static
+  php_sapi_module=static
 ;;
   shared) 
   
-  OVERALL_TARGET=php
   php_c_pre='$(LIBTOOL) --mode=compile $(CC)'
   php_c_meta='$(COMMON_FLAGS) $(CFLAGS_CLEAN) $(EXTRA_CFLAGS)'
   php_c_post=
@@ -4691,10 +4689,8 @@ EOF
   shared_cxx_post=
   shared_lo=lo
 
-  php_build_target=program
-
   OVERALL_TARGET=libphp$PHP_MAJOR_VERSION.la
-  php_build_target=shared
+  php_sapi_module=shared
   
   php_c_pre=$shared_c_pre
   php_c_meta=$shared_c_meta
@@ -4706,7 +4702,6 @@ EOF
 ;;
   bundle) 
   
-  OVERALL_TARGET=php
   php_c_pre='$(LIBTOOL) --mode=compile $(CC)'
   php_c_meta='$(COMMON_FLAGS) $(CFLAGS_CLEAN) $(EXTRA_CFLAGS)'
   php_c_post=
@@ -4728,13 +4723,10 @@ EOF
   shared_cxx_post=
   shared_lo=lo
 
-  php_build_target=program
-
   OVERALL_TARGET=libs/libphp$PHP_MAJOR_VERSION.bundle
-  php_build_target=static
+  php_sapi_module=static
 ;;
   program) 
-  OVERALL_TARGET=php
   php_c_pre='$(LIBTOOL) --mode=compile $(CC)'
   php_c_meta='$(COMMON_FLAGS) $(CFLAGS_CLEAN) $(EXTRA_CFLAGS)'
   php_c_post=
@@ -4755,12 +4747,12 @@ EOF
   shared_cxx_meta='$(COMMON_FLAGS) $(CXXFLAGS_CLEAN) $(EXTRA_CXXFLAGS) '$pic_setting
   shared_cxx_post=
   shared_lo=lo
-
-  php_build_target=program
 ;;
   esac
     
   
+    install_sapi="install-sapi"
+    
   
   case sapi/apache in
   "") ac_srcdir="$abs_srcdir/"; unset ac_bdir; ac_inc="-I. -I$abs_srcdir" ;;
@@ -4803,6 +4795,7 @@ EOF
   done
 
 
+  
 
     APACHE_INSTALL="mkdir -p $APACHE_TARGET; cp $SAPI_STATIC $APACHE_TARGET/libmodphp5.a; cp $APACHE_INSTALL_FILES $APACHE_TARGET; cp $srcdir/sapi/apache/apMakefile.tmpl $APACHE_TARGET/Makefile.tmpl; cp $srcdir/sapi/apache/apMakefile.libdir $APACHE_TARGET/Makefile.libdir"
     PHP_LIBS="-Lmodules/php5 -L../modules/php5 -L../../modules/php5 -lmodphp5"
@@ -4837,23 +4830,32 @@ EOF
       mkdir $APACHE_TARGET
     fi
     
-  if test "$PHP_SAPI" != "default"; then
-{ echo "configure: error: 
+  if test "static" = "program"; then
+    PHP_BINARIES="$PHP_BINARIES apache"
+  elif test "$PHP_SAPI" != "none"; then
+    { echo "configure: error: 
 +--------------------------------------------------------------------+
 |                        *** ATTENTION ***                           |
 |                                                                    |
 | You've configured multiple SAPIs to be build. You can build only   |
-| one SAPI module and CLI binary at the same time.                   |
+| one SAPI module plus CGI, CLI and FPM binaries at the same time.   |
 +--------------------------------------------------------------------+
 " 1>&2; exit 1; }
+  else
+    PHP_SAPI=apache
   fi
 
-  PHP_SAPI=apache
   
+  
+    BUILD_DIR="$BUILD_DIR sapi/apache"
+  
+
+
+  PHP_INSTALLED_SAPIS="$PHP_INSTALLED_SAPIS apache"
+
   case "static" in
   static) 
   
-  OVERALL_TARGET=php
   php_c_pre='$(LIBTOOL) --mode=compile $(CC)'
   php_c_meta='$(COMMON_FLAGS) $(CFLAGS_CLEAN) $(EXTRA_CFLAGS)'
   php_c_post=
@@ -4875,14 +4877,11 @@ EOF
   shared_cxx_post=
   shared_lo=lo
 
-  php_build_target=program
-
   OVERALL_TARGET=libphp$PHP_MAJOR_VERSION.la
-  php_build_target=static
+  php_sapi_module=static
 ;;
   shared) 
   
-  OVERALL_TARGET=php
   php_c_pre='$(LIBTOOL) --mode=compile $(CC)'
   php_c_meta='$(COMMON_FLAGS) $(CFLAGS_CLEAN) $(EXTRA_CFLAGS)'
   php_c_post=
@@ -4904,10 +4903,8 @@ EOF
   shared_cxx_post=
   shared_lo=lo
 
-  php_build_target=program
-
   OVERALL_TARGET=libphp$PHP_MAJOR_VERSION.la
-  php_build_target=shared
+  php_sapi_module=shared
   
   php_c_pre=$shared_c_pre
   php_c_meta=$shared_c_meta
@@ -4919,7 +4916,6 @@ EOF
 ;;
   bundle) 
   
-  OVERALL_TARGET=php
   php_c_pre='$(LIBTOOL) --mode=compile $(CC)'
   php_c_meta='$(COMMON_FLAGS) $(CFLAGS_CLEAN) $(EXTRA_CFLAGS)'
   php_c_post=
@@ -4941,13 +4937,10 @@ EOF
   shared_cxx_post=
   shared_lo=lo
 
-  php_build_target=program
-
   OVERALL_TARGET=libs/libphp$PHP_MAJOR_VERSION.bundle
-  php_build_target=static
+  php_sapi_module=static
 ;;
   program) 
-  OVERALL_TARGET=php
   php_c_pre='$(LIBTOOL) --mode=compile $(CC)'
   php_c_meta='$(COMMON_FLAGS) $(CFLAGS_CLEAN) $(EXTRA_CFLAGS)'
   php_c_post=
@@ -4968,12 +4961,12 @@ EOF
   shared_cxx_meta='$(COMMON_FLAGS) $(CXXFLAGS_CLEAN) $(EXTRA_CXXFLAGS) '$pic_setting
   shared_cxx_post=
   shared_lo=lo
-
-  php_build_target=program
 ;;
   esac
     
   
+    install_sapi="install-sapi"
+    
   
   case sapi/apache in
   "") ac_srcdir="$abs_srcdir/"; unset ac_bdir; ac_inc="-I. -I$abs_srcdir" ;;
@@ -5016,6 +5009,7 @@ EOF
   done
 
 
+  
 
     PHP_LIBS="-Lmodules/php5 -L../modules/php5 -L../../modules/php5 -lmodphp5"
     APACHE_INSTALL="mkdir -p $APACHE_TARGET; cp $SAPI_STATIC $APACHE_TARGET/libmodphp5.a; cp $APACHE_INSTALL_FILES $APACHE_TARGET; cp $srcdir/sapi/apache/apMakefile.tmpl $APACHE_TARGET/Makefile.tmpl; cp $srcdir/sapi/apache/apMakefile.libdir $APACHE_TARGET/Makefile.libdir"
@@ -5046,23 +5040,32 @@ EOF
     APACHE_INCLUDE="-I$PHP_APACHE/apache -I$PHP_APACHE/ssl/include"
     APACHE_TARGET=$PHP_APACHE/apache
     
-  if test "$PHP_SAPI" != "default"; then
-{ echo "configure: error: 
+  if test "static" = "program"; then
+    PHP_BINARIES="$PHP_BINARIES apache"
+  elif test "$PHP_SAPI" != "none"; then
+    { echo "configure: error: 
 +--------------------------------------------------------------------+
 |                        *** ATTENTION ***                           |
 |                                                                    |
 | You've configured multiple SAPIs to be build. You can build only   |
-| one SAPI module and CLI binary at the same time.                   |
+| one SAPI module plus CGI, CLI and FPM binaries at the same time.   |
 +--------------------------------------------------------------------+
 " 1>&2; exit 1; }
+  else
+    PHP_SAPI=apache
   fi
 
-  PHP_SAPI=apache
   
+  
+    BUILD_DIR="$BUILD_DIR sapi/apache"
+  
+
+
+  PHP_INSTALLED_SAPIS="$PHP_INSTALLED_SAPIS apache"
+
   case "static" in
   static) 
   
-  OVERALL_TARGET=php
   php_c_pre='$(LIBTOOL) --mode=compile $(CC)'
   php_c_meta='$(COMMON_FLAGS) $(CFLAGS_CLEAN) $(EXTRA_CFLAGS)'
   php_c_post=
@@ -5084,14 +5087,11 @@ EOF
   shared_cxx_post=
   shared_lo=lo
 
-  php_build_target=program
-
   OVERALL_TARGET=libphp$PHP_MAJOR_VERSION.la
-  php_build_target=static
+  php_sapi_module=static
 ;;
   shared) 
   
-  OVERALL_TARGET=php
   php_c_pre='$(LIBTOOL) --mode=compile $(CC)'
   php_c_meta='$(COMMON_FLAGS) $(CFLAGS_CLEAN) $(EXTRA_CFLAGS)'
   php_c_post=
@@ -5113,10 +5113,8 @@ EOF
   shared_cxx_post=
   shared_lo=lo
 
-  php_build_target=program
-
   OVERALL_TARGET=libphp$PHP_MAJOR_VERSION.la
-  php_build_target=shared
+  php_sapi_module=shared
   
   php_c_pre=$shared_c_pre
   php_c_meta=$shared_c_meta
@@ -5128,7 +5126,6 @@ EOF
 ;;
   bundle) 
   
-  OVERALL_TARGET=php
   php_c_pre='$(LIBTOOL) --mode=compile $(CC)'
   php_c_meta='$(COMMON_FLAGS) $(CFLAGS_CLEAN) $(EXTRA_CFLAGS)'
   php_c_post=
@@ -5150,13 +5147,10 @@ EOF
   shared_cxx_post=
   shared_lo=lo
 
-  php_build_target=program
-
   OVERALL_TARGET=libs/libphp$PHP_MAJOR_VERSION.bundle
-  php_build_target=static
+  php_sapi_module=static
 ;;
   program) 
-  OVERALL_TARGET=php
   php_c_pre='$(LIBTOOL) --mode=compile $(CC)'
   php_c_meta='$(COMMON_FLAGS) $(CFLAGS_CLEAN) $(EXTRA_CFLAGS)'
   php_c_post=
@@ -5177,12 +5171,12 @@ EOF
   shared_cxx_meta='$(COMMON_FLAGS) $(CXXFLAGS_CLEAN) $(EXTRA_CXXFLAGS) '$pic_setting
   shared_cxx_post=
   shared_lo=lo
-
-  php_build_target=program
 ;;
   esac
     
   
+    install_sapi="install-sapi"
+    
   
   case sapi/apache in
   "") ac_srcdir="$abs_srcdir/"; unset ac_bdir; ac_inc="-I. -I$abs_srcdir" ;;
@@ -5225,6 +5219,7 @@ EOF
   done
 
 
+  
 
     PHP_LIBS="-Lmodules/php5 -L../modules/php5 -L../../modules/php5 -lmodphp5"
     APACHE_INSTALL="mkdir -p $APACHE_TARGET; cp $SAPI_STATIC $APACHE_TARGET/libmodphp5.a; cp $APACHE_INSTALL_FILES $APACHE_TARGET"
@@ -5531,23 +5526,32 @@ IFS="- /.
   *aix*)
     EXTRA_LDFLAGS="$EXTRA_LDFLAGS -Wl,-brtl -Wl,-bI:$APXS_LIBEXECDIR/httpd.exp"
     
-  if test "$PHP_SAPI" != "default"; then
-{ echo "configure: error: 
+  if test "shared" = "program"; then
+    PHP_BINARIES="$PHP_BINARIES apache2filter"
+  elif test "$PHP_SAPI" != "none"; then
+    { echo "configure: error: 
 +--------------------------------------------------------------------+
 |                        *** ATTENTION ***                           |
 |                                                                    |
 | You've configured multiple SAPIs to be build. You can build only   |
-| one SAPI module and CLI binary at the same time.                   |
+| one SAPI module plus CGI, CLI and FPM binaries at the same time.   |
 +--------------------------------------------------------------------+
 " 1>&2; exit 1; }
+  else
+    PHP_SAPI=apache2filter
   fi
 
-  PHP_SAPI=apache2filter
   
+  
+    BUILD_DIR="$BUILD_DIR sapi/apache2filter"
+  
+
+
+  PHP_INSTALLED_SAPIS="$PHP_INSTALLED_SAPIS apache2filter"
+
   case "shared" in
   static) 
   
-  OVERALL_TARGET=php
   php_c_pre='$(LIBTOOL) --mode=compile $(CC)'
   php_c_meta='$(COMMON_FLAGS) $(CFLAGS_CLEAN) $(EXTRA_CFLAGS)'
   php_c_post=
@@ -5569,14 +5573,11 @@ IFS="- /.
   shared_cxx_post=
   shared_lo=lo
 
-  php_build_target=program
-
   OVERALL_TARGET=libphp$PHP_MAJOR_VERSION.la
-  php_build_target=static
+  php_sapi_module=static
 ;;
   shared) 
   
-  OVERALL_TARGET=php
   php_c_pre='$(LIBTOOL) --mode=compile $(CC)'
   php_c_meta='$(COMMON_FLAGS) $(CFLAGS_CLEAN) $(EXTRA_CFLAGS)'
   php_c_post=
@@ -5598,10 +5599,8 @@ IFS="- /.
   shared_cxx_post=
   shared_lo=lo
 
-  php_build_target=program
-
   OVERALL_TARGET=libphp$PHP_MAJOR_VERSION.la
-  php_build_target=shared
+  php_sapi_module=shared
   
   php_c_pre=$shared_c_pre
   php_c_meta=$shared_c_meta
@@ -5613,7 +5612,6 @@ IFS="- /.
 ;;
   bundle) 
   
-  OVERALL_TARGET=php
   php_c_pre='$(LIBTOOL) --mode=compile $(CC)'
   php_c_meta='$(COMMON_FLAGS) $(CFLAGS_CLEAN) $(EXTRA_CFLAGS)'
   php_c_post=
@@ -5635,13 +5633,10 @@ IFS="- /.
   shared_cxx_post=
   shared_lo=lo
 
-  php_build_target=program
-
   OVERALL_TARGET=libs/libphp$PHP_MAJOR_VERSION.bundle
-  php_build_target=static
+  php_sapi_module=static
 ;;
   program) 
-  OVERALL_TARGET=php
   php_c_pre='$(LIBTOOL) --mode=compile $(CC)'
   php_c_meta='$(COMMON_FLAGS) $(CFLAGS_CLEAN) $(EXTRA_CFLAGS)'
   php_c_post=
@@ -5662,12 +5657,12 @@ IFS="- /.
   shared_cxx_meta='$(COMMON_FLAGS) $(CXXFLAGS_CLEAN) $(EXTRA_CXXFLAGS) '$pic_setting
   shared_cxx_post=
   shared_lo=lo
-
-  php_build_target=program
 ;;
   esac
     
   
+    install_sapi="install-sapi"
+    
   
   case sapi/apache2filter in
   "") ac_srcdir="$abs_srcdir/"; unset ac_bdir; ac_inc="-I. -I$abs_srcdir" ;;
@@ -5710,6 +5705,7 @@ EOF
   done
 
 
+  
 
     INSTALL_IT="$INSTALL_IT $SAPI_LIBTOOL" 
     ;;
@@ -5725,23 +5721,32 @@ EOF
   PHP_VAR_SUBST="$PHP_VAR_SUBST MH_BUNDLE_FLAGS"
 
     
-  if test "$PHP_SAPI" != "default"; then
-{ echo "configure: error: 
+  if test "bundle" = "program"; then
+    PHP_BINARIES="$PHP_BINARIES apache2filter"
+  elif test "$PHP_SAPI" != "none"; then
+    { echo "configure: error: 
 +--------------------------------------------------------------------+
 |                        *** ATTENTION ***                           |
 |                                                                    |
 | You've configured multiple SAPIs to be build. You can build only   |
-| one SAPI module and CLI binary at the same time.                   |
+| one SAPI module plus CGI, CLI and FPM binaries at the same time.   |
 +--------------------------------------------------------------------+
 " 1>&2; exit 1; }
+  else
+    PHP_SAPI=apache2filter
   fi
 
-  PHP_SAPI=apache2filter
   
+  
+    BUILD_DIR="$BUILD_DIR sapi/apache2filter"
+  
+
+
+  PHP_INSTALLED_SAPIS="$PHP_INSTALLED_SAPIS apache2filter"
+
   case "bundle" in
   static) 
   
-  OVERALL_TARGET=php
   php_c_pre='$(LIBTOOL) --mode=compile $(CC)'
   php_c_meta='$(COMMON_FLAGS) $(CFLAGS_CLEAN) $(EXTRA_CFLAGS)'
   php_c_post=
@@ -5763,14 +5768,11 @@ EOF
   shared_cxx_post=
   shared_lo=lo
 
-  php_build_target=program
-
   OVERALL_TARGET=libphp$PHP_MAJOR_VERSION.la
-  php_build_target=static
+  php_sapi_module=static
 ;;
   shared) 
   
-  OVERALL_TARGET=php
   php_c_pre='$(LIBTOOL) --mode=compile $(CC)'
   php_c_meta='$(COMMON_FLAGS) $(CFLAGS_CLEAN) $(EXTRA_CFLAGS)'
   php_c_post=
@@ -5792,10 +5794,8 @@ EOF
   shared_cxx_post=
   shared_lo=lo
 
-  php_build_target=program
-
   OVERALL_TARGET=libphp$PHP_MAJOR_VERSION.la
-  php_build_target=shared
+  php_sapi_module=shared
   
   php_c_pre=$shared_c_pre
   php_c_meta=$shared_c_meta
@@ -5807,7 +5807,6 @@ EOF
 ;;
   bundle) 
   
-  OVERALL_TARGET=php
   php_c_pre='$(LIBTOOL) --mode=compile $(CC)'
   php_c_meta='$(COMMON_FLAGS) $(CFLAGS_CLEAN) $(EXTRA_CFLAGS)'
   php_c_post=
@@ -5829,13 +5828,10 @@ EOF
   shared_cxx_post=
   shared_lo=lo
 
-  php_build_target=program
-
   OVERALL_TARGET=libs/libphp$PHP_MAJOR_VERSION.bundle
-  php_build_target=static
+  php_sapi_module=static
 ;;
   program) 
-  OVERALL_TARGET=php
   php_c_pre='$(LIBTOOL) --mode=compile $(CC)'
   php_c_meta='$(COMMON_FLAGS) $(CFLAGS_CLEAN) $(EXTRA_CFLAGS)'
   php_c_post=
@@ -5856,12 +5852,12 @@ EOF
   shared_cxx_meta='$(COMMON_FLAGS) $(CXXFLAGS_CLEAN) $(EXTRA_CXXFLAGS) '$pic_setting
   shared_cxx_post=
   shared_lo=lo
-
-  php_build_target=program
 ;;
   esac
     
   
+    install_sapi="install-sapi"
+    
   
   case sapi/apache2filter in
   "") ac_srcdir="$abs_srcdir/"; unset ac_bdir; ac_inc="-I. -I$abs_srcdir" ;;
@@ -5904,6 +5900,7 @@ EOF
   done
 
 
+  
 
     SAPI_SHARED=libs/libphp5.so
     INSTALL_IT="$INSTALL_IT $SAPI_SHARED"
@@ -5913,23 +5910,32 @@ EOF
     `ln -s $APXS_BINDIR/httpd _APP_`
     EXTRA_LIBS="$EXTRA_LIBS _APP_"
     
-  if test "$PHP_SAPI" != "default"; then
-{ echo "configure: error: 
+  if test "shared" = "program"; then
+    PHP_BINARIES="$PHP_BINARIES apache2filter"
+  elif test "$PHP_SAPI" != "none"; then
+    { echo "configure: error: 
 +--------------------------------------------------------------------+
 |                        *** ATTENTION ***                           |
 |                                                                    |
 | You've configured multiple SAPIs to be build. You can build only   |
-| one SAPI module and CLI binary at the same time.                   |
+| one SAPI module plus CGI, CLI and FPM binaries at the same time.   |
 +--------------------------------------------------------------------+
 " 1>&2; exit 1; }
+  else
+    PHP_SAPI=apache2filter
   fi
 
-  PHP_SAPI=apache2filter
   
+  
+    BUILD_DIR="$BUILD_DIR sapi/apache2filter"
+  
+
+
+  PHP_INSTALLED_SAPIS="$PHP_INSTALLED_SAPIS apache2filter"
+
   case "shared" in
   static) 
   
-  OVERALL_TARGET=php
   php_c_pre='$(LIBTOOL) --mode=compile $(CC)'
   php_c_meta='$(COMMON_FLAGS) $(CFLAGS_CLEAN) $(EXTRA_CFLAGS)'
   php_c_post=
@@ -5951,14 +5957,11 @@ EOF
   shared_cxx_post=
   shared_lo=lo
 
-  php_build_target=program
-
   OVERALL_TARGET=libphp$PHP_MAJOR_VERSION.la
-  php_build_target=static
+  php_sapi_module=static
 ;;
   shared) 
   
-  OVERALL_TARGET=php
   php_c_pre='$(LIBTOOL) --mode=compile $(CC)'
   php_c_meta='$(COMMON_FLAGS) $(CFLAGS_CLEAN) $(EXTRA_CFLAGS)'
   php_c_post=
@@ -5980,10 +5983,8 @@ EOF
   shared_cxx_post=
   shared_lo=lo
 
-  php_build_target=program
-
   OVERALL_TARGET=libphp$PHP_MAJOR_VERSION.la
-  php_build_target=shared
+  php_sapi_module=shared
   
   php_c_pre=$shared_c_pre
   php_c_meta=$shared_c_meta
@@ -5995,7 +5996,6 @@ EOF
 ;;
   bundle) 
   
-  OVERALL_TARGET=php
   php_c_pre='$(LIBTOOL) --mode=compile $(CC)'
   php_c_meta='$(COMMON_FLAGS) $(CFLAGS_CLEAN) $(EXTRA_CFLAGS)'
   php_c_post=
@@ -6017,13 +6017,10 @@ EOF
   shared_cxx_post=
   shared_lo=lo
 
-  php_build_target=program
-
   OVERALL_TARGET=libs/libphp$PHP_MAJOR_VERSION.bundle
-  php_build_target=static
+  php_sapi_module=static
 ;;
   program) 
-  OVERALL_TARGET=php
   php_c_pre='$(LIBTOOL) --mode=compile $(CC)'
   php_c_meta='$(COMMON_FLAGS) $(CFLAGS_CLEAN) $(EXTRA_CFLAGS)'
   php_c_post=
@@ -6044,12 +6041,12 @@ EOF
   shared_cxx_meta='$(COMMON_FLAGS) $(CXXFLAGS_CLEAN) $(EXTRA_CXXFLAGS) '$pic_setting
   shared_cxx_post=
   shared_lo=lo
-
-  php_build_target=program
 ;;
   esac
     
   
+    install_sapi="install-sapi"
+    
   
   case sapi/apache2filter in
   "") ac_srcdir="$abs_srcdir/"; unset ac_bdir; ac_inc="-I. -I$abs_srcdir" ;;
@@ -6092,28 +6089,38 @@ EOF
   done
 
 
+  
 
     INSTALL_IT="$INSTALL_IT $SAPI_LIBTOOL"
     ;;
   *)
     
-  if test "$PHP_SAPI" != "default"; then
-{ echo "configure: error: 
+  if test "shared" = "program"; then
+    PHP_BINARIES="$PHP_BINARIES apache2filter"
+  elif test "$PHP_SAPI" != "none"; then
+    { echo "configure: error: 
 +--------------------------------------------------------------------+
 |                        *** ATTENTION ***                           |
 |                                                                    |
 | You've configured multiple SAPIs to be build. You can build only   |
-| one SAPI module and CLI binary at the same time.                   |
+| one SAPI module plus CGI, CLI and FPM binaries at the same time.   |
 +--------------------------------------------------------------------+
 " 1>&2; exit 1; }
+  else
+    PHP_SAPI=apache2filter
   fi
 
-  PHP_SAPI=apache2filter
   
+  
+    BUILD_DIR="$BUILD_DIR sapi/apache2filter"
+  
+
+
+  PHP_INSTALLED_SAPIS="$PHP_INSTALLED_SAPIS apache2filter"
+
   case "shared" in
   static) 
   
-  OVERALL_TARGET=php
   php_c_pre='$(LIBTOOL) --mode=compile $(CC)'
   php_c_meta='$(COMMON_FLAGS) $(CFLAGS_CLEAN) $(EXTRA_CFLAGS)'
   php_c_post=
@@ -6135,14 +6142,11 @@ EOF
   shared_cxx_post=
   shared_lo=lo
 
-  php_build_target=program
-
   OVERALL_TARGET=libphp$PHP_MAJOR_VERSION.la
-  php_build_target=static
+  php_sapi_module=static
 ;;
   shared) 
   
-  OVERALL_TARGET=php
   php_c_pre='$(LIBTOOL) --mode=compile $(CC)'
   php_c_meta='$(COMMON_FLAGS) $(CFLAGS_CLEAN) $(EXTRA_CFLAGS)'
   php_c_post=
@@ -6164,10 +6168,8 @@ EOF
   shared_cxx_post=
   shared_lo=lo
 
-  php_build_target=program
-
   OVERALL_TARGET=libphp$PHP_MAJOR_VERSION.la
-  php_build_target=shared
+  php_sapi_module=shared
   
   php_c_pre=$shared_c_pre
   php_c_meta=$shared_c_meta
@@ -6179,7 +6181,6 @@ EOF
 ;;
   bundle) 
   
-  OVERALL_TARGET=php
   php_c_pre='$(LIBTOOL) --mode=compile $(CC)'
   php_c_meta='$(COMMON_FLAGS) $(CFLAGS_CLEAN) $(EXTRA_CFLAGS)'
   php_c_post=
@@ -6201,13 +6202,10 @@ EOF
   shared_cxx_post=
   shared_lo=lo
 
-  php_build_target=program
-
   OVERALL_TARGET=libs/libphp$PHP_MAJOR_VERSION.bundle
-  php_build_target=static
+  php_sapi_module=static
 ;;
   program) 
-  OVERALL_TARGET=php
   php_c_pre='$(LIBTOOL) --mode=compile $(CC)'
   php_c_meta='$(COMMON_FLAGS) $(CFLAGS_CLEAN) $(EXTRA_CFLAGS)'
   php_c_post=
@@ -6228,12 +6226,12 @@ EOF
   shared_cxx_meta='$(COMMON_FLAGS) $(CXXFLAGS_CLEAN) $(EXTRA_CXXFLAGS) '$pic_setting
   shared_cxx_post=
   shared_lo=lo
-
-  php_build_target=program
 ;;
   esac
     
   
+    install_sapi="install-sapi"
+    
   
   case sapi/apache2filter in
   "") ac_srcdir="$abs_srcdir/"; unset ac_bdir; ac_inc="-I. -I$abs_srcdir" ;;
@@ -6276,6 +6274,7 @@ EOF
   done
 
 
+  
  
     INSTALL_IT="$INSTALL_IT $SAPI_LIBTOOL"
     ;;
@@ -6335,7 +6334,7 @@ ext_output=$PHP_APXS2
 
 
 echo $ac_n "checking for Apache 2.0 handler-module support via DSO through APXS""... $ac_c" 1>&6
-echo "configure:6339: checking for Apache 2.0 handler-module support via DSO through APXS" >&5
+echo "configure:6343: checking for Apache 2.0 handler-module support via DSO through APXS" >&5
 
 if test "$PHP_APXS2" != "no"; then
   if test "$PHP_APXS2" = "yes"; then
@@ -6432,23 +6431,32 @@ IFS="- /.
   *aix*)
     EXTRA_LDFLAGS="$EXTRA_LDFLAGS -Wl,-brtl -Wl,-bI:$APXS_LIBEXECDIR/httpd.exp"
     
-  if test "$PHP_SAPI" != "default"; then
-{ echo "configure: error: 
+  if test "shared" = "program"; then
+    PHP_BINARIES="$PHP_BINARIES apache2handler"
+  elif test "$PHP_SAPI" != "none"; then
+    { echo "configure: error: 
 +--------------------------------------------------------------------+
 |                        *** ATTENTION ***                           |
 |                                                                    |
 | You've configured multiple SAPIs to be build. You can build only   |
-| one SAPI module and CLI binary at the same time.                   |
+| one SAPI module plus CGI, CLI and FPM binaries at the same time.   |
 +--------------------------------------------------------------------+
 " 1>&2; exit 1; }
+  else
+    PHP_SAPI=apache2handler
   fi
 
-  PHP_SAPI=apache2handler
   
+  
+    BUILD_DIR="$BUILD_DIR sapi/apache2handler"
+  
+
+
+  PHP_INSTALLED_SAPIS="$PHP_INSTALLED_SAPIS apache2handler"
+
   case "shared" in
   static) 
   
-  OVERALL_TARGET=php
   php_c_pre='$(LIBTOOL) --mode=compile $(CC)'
   php_c_meta='$(COMMON_FLAGS) $(CFLAGS_CLEAN) $(EXTRA_CFLAGS)'
   php_c_post=
@@ -6470,14 +6478,11 @@ IFS="- /.
   shared_cxx_post=
   shared_lo=lo
 
-  php_build_target=program
-
   OVERALL_TARGET=libphp$PHP_MAJOR_VERSION.la
-  php_build_target=static
+  php_sapi_module=static
 ;;
   shared) 
   
-  OVERALL_TARGET=php
   php_c_pre='$(LIBTOOL) --mode=compile $(CC)'
   php_c_meta='$(COMMON_FLAGS) $(CFLAGS_CLEAN) $(EXTRA_CFLAGS)'
   php_c_post=
@@ -6499,10 +6504,8 @@ IFS="- /.
   shared_cxx_post=
   shared_lo=lo
 
-  php_build_target=program
-
   OVERALL_TARGET=libphp$PHP_MAJOR_VERSION.la
-  php_build_target=shared
+  php_sapi_module=shared
   
   php_c_pre=$shared_c_pre
   php_c_meta=$shared_c_meta
@@ -6514,7 +6517,6 @@ IFS="- /.
 ;;
   bundle) 
   
-  OVERALL_TARGET=php
   php_c_pre='$(LIBTOOL) --mode=compile $(CC)'
   php_c_meta='$(COMMON_FLAGS) $(CFLAGS_CLEAN) $(EXTRA_CFLAGS)'
   php_c_post=
@@ -6536,13 +6538,10 @@ IFS="- /.
   shared_cxx_post=
   shared_lo=lo
 
-  php_build_target=program
-
   OVERALL_TARGET=libs/libphp$PHP_MAJOR_VERSION.bundle
-  php_build_target=static
+  php_sapi_module=static
 ;;
   program) 
-  OVERALL_TARGET=php
   php_c_pre='$(LIBTOOL) --mode=compile $(CC)'
   php_c_meta='$(COMMON_FLAGS) $(CFLAGS_CLEAN) $(EXTRA_CFLAGS)'
   php_c_post=
@@ -6563,12 +6562,12 @@ IFS="- /.
   shared_cxx_meta='$(COMMON_FLAGS) $(CXXFLAGS_CLEAN) $(EXTRA_CXXFLAGS) '$pic_setting
   shared_cxx_post=
   shared_lo=lo
-
-  php_build_target=program
 ;;
   esac
     
   
+    install_sapi="install-sapi"
+    
   
   case sapi/apache2handler in
   "") ac_srcdir="$abs_srcdir/"; unset ac_bdir; ac_inc="-I. -I$abs_srcdir" ;;
@@ -6611,6 +6610,7 @@ EOF
   done
 
 
+  
 
     INSTALL_IT="$INSTALL_IT $SAPI_LIBTOOL" 
     ;;
@@ -6626,23 +6626,32 @@ EOF
   PHP_VAR_SUBST="$PHP_VAR_SUBST MH_BUNDLE_FLAGS"
 
     
-  if test "$PHP_SAPI" != "default"; then
-{ echo "configure: error: 
+  if test "bundle" = "program"; then
+    PHP_BINARIES="$PHP_BINARIES apache2handler"
+  elif test "$PHP_SAPI" != "none"; then
+    { echo "configure: error: 
 +--------------------------------------------------------------------+
 |                        *** ATTENTION ***                           |
 |                                                                    |
 | You've configured multiple SAPIs to be build. You can build only   |
-| one SAPI module and CLI binary at the same time.                   |
+| one SAPI module plus CGI, CLI and FPM binaries at the same time.   |
 +--------------------------------------------------------------------+
 " 1>&2; exit 1; }
+  else
+    PHP_SAPI=apache2handler
   fi
 
-  PHP_SAPI=apache2handler
   
+  
+    BUILD_DIR="$BUILD_DIR sapi/apache2handler"
+  
+
+
+  PHP_INSTALLED_SAPIS="$PHP_INSTALLED_SAPIS apache2handler"
+
   case "bundle" in
   static) 
   
-  OVERALL_TARGET=php
   php_c_pre='$(LIBTOOL) --mode=compile $(CC)'
   php_c_meta='$(COMMON_FLAGS) $(CFLAGS_CLEAN) $(EXTRA_CFLAGS)'
   php_c_post=
@@ -6664,14 +6673,11 @@ EOF
   shared_cxx_post=
   shared_lo=lo
 
-  php_build_target=program
-
   OVERALL_TARGET=libphp$PHP_MAJOR_VERSION.la
-  php_build_target=static
+  php_sapi_module=static
 ;;
   shared) 
   
-  OVERALL_TARGET=php
   php_c_pre='$(LIBTOOL) --mode=compile $(CC)'
   php_c_meta='$(COMMON_FLAGS) $(CFLAGS_CLEAN) $(EXTRA_CFLAGS)'
   php_c_post=
@@ -6693,10 +6699,8 @@ EOF
   shared_cxx_post=
   shared_lo=lo
 
-  php_build_target=program
-
   OVERALL_TARGET=libphp$PHP_MAJOR_VERSION.la
-  php_build_target=shared
+  php_sapi_module=shared
   
   php_c_pre=$shared_c_pre
   php_c_meta=$shared_c_meta
@@ -6708,7 +6712,6 @@ EOF
 ;;
   bundle) 
   
-  OVERALL_TARGET=php
   php_c_pre='$(LIBTOOL) --mode=compile $(CC)'
   php_c_meta='$(COMMON_FLAGS) $(CFLAGS_CLEAN) $(EXTRA_CFLAGS)'
   php_c_post=
@@ -6730,13 +6733,10 @@ EOF
   shared_cxx_post=
   shared_lo=lo
 
-  php_build_target=program
-
   OVERALL_TARGET=libs/libphp$PHP_MAJOR_VERSION.bundle
-  php_build_target=static
+  php_sapi_module=static
 ;;
   program) 
-  OVERALL_TARGET=php
   php_c_pre='$(LIBTOOL) --mode=compile $(CC)'
   php_c_meta='$(COMMON_FLAGS) $(CFLAGS_CLEAN) $(EXTRA_CFLAGS)'
   php_c_post=
@@ -6757,12 +6757,12 @@ EOF
   shared_cxx_meta='$(COMMON_FLAGS) $(CXXFLAGS_CLEAN) $(EXTRA_CXXFLAGS) '$pic_setting
   shared_cxx_post=
   shared_lo=lo
-
-  php_build_target=program
 ;;
   esac
     
   
+    install_sapi="install-sapi"
+    
   
   case sapi/apache2handler in
   "") ac_srcdir="$abs_srcdir/"; unset ac_bdir; ac_inc="-I. -I$abs_srcdir" ;;
@@ -6805,6 +6805,7 @@ EOF
   done
 
 
+  
 
     SAPI_SHARED=libs/libphp5.so
     INSTALL_IT="$INSTALL_IT $SAPI_SHARED"
@@ -6814,23 +6815,32 @@ EOF
     `ln -s $APXS_BINDIR/httpd _APP_`
     EXTRA_LIBS="$EXTRA_LIBS _APP_"
     
-  if test "$PHP_SAPI" != "default"; then
-{ echo "configure: error: 
+  if test "shared" = "program"; then
+    PHP_BINARIES="$PHP_BINARIES apache2handler"
+  elif test "$PHP_SAPI" != "none"; then
+    { echo "configure: error: 
 +--------------------------------------------------------------------+
 |                        *** ATTENTION ***                           |
 |                                                                    |
 | You've configured multiple SAPIs to be build. You can build only   |
-| one SAPI module and CLI binary at the same time.                   |
+| one SAPI module plus CGI, CLI and FPM binaries at the same time.   |
 +--------------------------------------------------------------------+
 " 1>&2; exit 1; }
+  else
+    PHP_SAPI=apache2handler
   fi
 
-  PHP_SAPI=apache2handler
   
+  
+    BUILD_DIR="$BUILD_DIR sapi/apache2handler"
+  
+
+
+  PHP_INSTALLED_SAPIS="$PHP_INSTALLED_SAPIS apache2handler"
+
   case "shared" in
   static) 
   
-  OVERALL_TARGET=php
   php_c_pre='$(LIBTOOL) --mode=compile $(CC)'
   php_c_meta='$(COMMON_FLAGS) $(CFLAGS_CLEAN) $(EXTRA_CFLAGS)'
   php_c_post=
@@ -6852,14 +6862,11 @@ EOF
   shared_cxx_post=
   shared_lo=lo
 
-  php_build_target=program
-
   OVERALL_TARGET=libphp$PHP_MAJOR_VERSION.la
-  php_build_target=static
+  php_sapi_module=static
 ;;
   shared) 
   
-  OVERALL_TARGET=php
   php_c_pre='$(LIBTOOL) --mode=compile $(CC)'
   php_c_meta='$(COMMON_FLAGS) $(CFLAGS_CLEAN) $(EXTRA_CFLAGS)'
   php_c_post=
@@ -6881,10 +6888,8 @@ EOF
   shared_cxx_post=
   shared_lo=lo
 
-  php_build_target=program
-
   OVERALL_TARGET=libphp$PHP_MAJOR_VERSION.la
-  php_build_target=shared
+  php_sapi_module=shared
   
   php_c_pre=$shared_c_pre
   php_c_meta=$shared_c_meta
@@ -6896,7 +6901,6 @@ EOF
 ;;
   bundle) 
   
-  OVERALL_TARGET=php
   php_c_pre='$(LIBTOOL) --mode=compile $(CC)'
   php_c_meta='$(COMMON_FLAGS) $(CFLAGS_CLEAN) $(EXTRA_CFLAGS)'
   php_c_post=
@@ -6918,13 +6922,10 @@ EOF
   shared_cxx_post=
   shared_lo=lo
 
-  php_build_target=program
-
   OVERALL_TARGET=libs/libphp$PHP_MAJOR_VERSION.bundle
-  php_build_target=static
+  php_sapi_module=static
 ;;
   program) 
-  OVERALL_TARGET=php
   php_c_pre='$(LIBTOOL) --mode=compile $(CC)'
   php_c_meta='$(COMMON_FLAGS) $(CFLAGS_CLEAN) $(EXTRA_CFLAGS)'
   php_c_post=
@@ -6945,12 +6946,12 @@ EOF
   shared_cxx_meta='$(COMMON_FLAGS) $(CXXFLAGS_CLEAN) $(EXTRA_CXXFLAGS) '$pic_setting
   shared_cxx_post=
   shared_lo=lo
-
-  php_build_target=program
 ;;
   esac
     
   
+    install_sapi="install-sapi"
+    
   
   case sapi/apache2handler in
   "") ac_srcdir="$abs_srcdir/"; unset ac_bdir; ac_inc="-I. -I$abs_srcdir" ;;
@@ -6993,28 +6994,38 @@ EOF
   done
 
 
+  
 
     INSTALL_IT="$INSTALL_IT $SAPI_LIBTOOL"
     ;;
   *)
     
-  if test "$PHP_SAPI" != "default"; then
-{ echo "configure: error: 
+  if test "shared" = "program"; then
+    PHP_BINARIES="$PHP_BINARIES apache2handler"
+  elif test "$PHP_SAPI" != "none"; then
+    { echo "configure: error: 
 +--------------------------------------------------------------------+
 |                        *** ATTENTION ***                           |
 |                                                                    |
 | You've configured multiple SAPIs to be build. You can build only   |
-| one SAPI module and CLI binary at the same time.                   |
+| one SAPI module plus CGI, CLI and FPM binaries at the same time.   |
 +--------------------------------------------------------------------+
 " 1>&2; exit 1; }
+  else
+    PHP_SAPI=apache2handler
   fi
 
-  PHP_SAPI=apache2handler
   
+  
+    BUILD_DIR="$BUILD_DIR sapi/apache2handler"
+  
+
+
+  PHP_INSTALLED_SAPIS="$PHP_INSTALLED_SAPIS apache2handler"
+
   case "shared" in
   static) 
   
-  OVERALL_TARGET=php
   php_c_pre='$(LIBTOOL) --mode=compile $(CC)'
   php_c_meta='$(COMMON_FLAGS) $(CFLAGS_CLEAN) $(EXTRA_CFLAGS)'
   php_c_post=
@@ -7036,14 +7047,11 @@ EOF
   shared_cxx_post=
   shared_lo=lo
 
-  php_build_target=program
-
   OVERALL_TARGET=libphp$PHP_MAJOR_VERSION.la
-  php_build_target=static
+  php_sapi_module=static
 ;;
   shared) 
   
-  OVERALL_TARGET=php
   php_c_pre='$(LIBTOOL) --mode=compile $(CC)'
   php_c_meta='$(COMMON_FLAGS) $(CFLAGS_CLEAN) $(EXTRA_CFLAGS)'
   php_c_post=
@@ -7065,10 +7073,8 @@ EOF
   shared_cxx_post=
   shared_lo=lo
 
-  php_build_target=program
-
   OVERALL_TARGET=libphp$PHP_MAJOR_VERSION.la
-  php_build_target=shared
+  php_sapi_module=shared
   
   php_c_pre=$shared_c_pre
   php_c_meta=$shared_c_meta
@@ -7080,7 +7086,6 @@ EOF
 ;;
   bundle) 
   
-  OVERALL_TARGET=php
   php_c_pre='$(LIBTOOL) --mode=compile $(CC)'
   php_c_meta='$(COMMON_FLAGS) $(CFLAGS_CLEAN) $(EXTRA_CFLAGS)'
   php_c_post=
@@ -7102,13 +7107,10 @@ EOF
   shared_cxx_post=
   shared_lo=lo
 
-  php_build_target=program
-
   OVERALL_TARGET=libs/libphp$PHP_MAJOR_VERSION.bundle
-  php_build_target=static
+  php_sapi_module=static
 ;;
   program) 
-  OVERALL_TARGET=php
   php_c_pre='$(LIBTOOL) --mode=compile $(CC)'
   php_c_meta='$(COMMON_FLAGS) $(CFLAGS_CLEAN) $(EXTRA_CFLAGS)'
   php_c_post=
@@ -7129,12 +7131,12 @@ EOF
   shared_cxx_meta='$(COMMON_FLAGS) $(CXXFLAGS_CLEAN) $(EXTRA_CXXFLAGS) '$pic_setting
   shared_cxx_post=
   shared_lo=lo
-
-  php_build_target=program
 ;;
   esac
     
   
+    install_sapi="install-sapi"
+    
   
   case sapi/apache2handler in
   "") ac_srcdir="$abs_srcdir/"; unset ac_bdir; ac_inc="-I. -I$abs_srcdir" ;;
@@ -7177,6 +7179,7 @@ EOF
   done
 
 
+  
  
     INSTALL_IT="$INSTALL_IT $SAPI_LIBTOOL"
     ;;
@@ -7237,7 +7240,7 @@ ext_output=$PHP_APACHE_HOOKS
 
 
 echo $ac_n "checking for Apache 1.x (hooks) module support via DSO through APXS""... $ac_c" 1>&6
-echo "configure:7241: checking for Apache 1.x (hooks) module support via DSO through APXS" >&5
+echo "configure:7249: checking for Apache 1.x (hooks) module support via DSO through APXS" >&5
 
 if test "$PHP_APACHE_HOOKS" != "no"; then
   if test "$PHP_APACHE_HOOKS" = "yes"; then
@@ -7322,23 +7325,32 @@ IFS="- /.
   esac
 
   
-  if test "$PHP_SAPI" != "default"; then
-{ echo "configure: error: 
+  if test "$build_type" = "program"; then
+    PHP_BINARIES="$PHP_BINARIES apache_hooks"
+  elif test "$PHP_SAPI" != "none"; then
+    { echo "configure: error: 
 +--------------------------------------------------------------------+
 |                        *** ATTENTION ***                           |
 |                                                                    |
 | You've configured multiple SAPIs to be build. You can build only   |
-| one SAPI module and CLI binary at the same time.                   |
+| one SAPI module plus CGI, CLI and FPM binaries at the same time.   |
 +--------------------------------------------------------------------+
 " 1>&2; exit 1; }
+  else
+    PHP_SAPI=apache_hooks
   fi
 
-  PHP_SAPI=apache_hooks
   
+  
+    BUILD_DIR="$BUILD_DIR sapi/apache_hooks"
+  
+
+
+  PHP_INSTALLED_SAPIS="$PHP_INSTALLED_SAPIS apache_hooks"
+
   case "$build_type" in
   static) 
   
-  OVERALL_TARGET=php
   php_c_pre='$(LIBTOOL) --mode=compile $(CC)'
   php_c_meta='$(COMMON_FLAGS) $(CFLAGS_CLEAN) $(EXTRA_CFLAGS)'
   php_c_post=
@@ -7360,14 +7372,11 @@ IFS="- /.
   shared_cxx_post=
   shared_lo=lo
 
-  php_build_target=program
-
   OVERALL_TARGET=libphp$PHP_MAJOR_VERSION.la
-  php_build_target=static
+  php_sapi_module=static
 ;;
   shared) 
   
-  OVERALL_TARGET=php
   php_c_pre='$(LIBTOOL) --mode=compile $(CC)'
   php_c_meta='$(COMMON_FLAGS) $(CFLAGS_CLEAN) $(EXTRA_CFLAGS)'
   php_c_post=
@@ -7389,10 +7398,8 @@ IFS="- /.
   shared_cxx_post=
   shared_lo=lo
 
-  php_build_target=program
-
   OVERALL_TARGET=libphp$PHP_MAJOR_VERSION.la
-  php_build_target=shared
+  php_sapi_module=shared
   
   php_c_pre=$shared_c_pre
   php_c_meta=$shared_c_meta
@@ -7404,7 +7411,6 @@ IFS="- /.
 ;;
   bundle) 
   
-  OVERALL_TARGET=php
   php_c_pre='$(LIBTOOL) --mode=compile $(CC)'
   php_c_meta='$(COMMON_FLAGS) $(CFLAGS_CLEAN) $(EXTRA_CFLAGS)'
   php_c_post=
@@ -7426,13 +7432,10 @@ IFS="- /.
   shared_cxx_post=
   shared_lo=lo
 
-  php_build_target=program
-
   OVERALL_TARGET=libs/libphp$PHP_MAJOR_VERSION.bundle
-  php_build_target=static
+  php_sapi_module=static
 ;;
   program) 
-  OVERALL_TARGET=php
   php_c_pre='$(LIBTOOL) --mode=compile $(CC)'
   php_c_meta='$(COMMON_FLAGS) $(CFLAGS_CLEAN) $(EXTRA_CFLAGS)'
   php_c_post=
@@ -7453,12 +7456,12 @@ IFS="- /.
   shared_cxx_meta='$(COMMON_FLAGS) $(CXXFLAGS_CLEAN) $(EXTRA_CXXFLAGS) '$pic_setting
   shared_cxx_post=
   shared_lo=lo
-
-  php_build_target=program
 ;;
   esac
     
   
+    install_sapi="install-sapi"
+    
   
   case sapi/apache_hooks in
   "") ac_srcdir="$abs_srcdir/"; unset ac_bdir; ac_inc="-I. -I$abs_srcdir" ;;
@@ -7501,6 +7504,7 @@ EOF
   done
 
 
+  
 
 
   # Test whether apxs support -S option
@@ -7568,7 +7572,7 @@ ext_output=$PHP_APACHE_HOOKS_STATIC
 
 
 echo $ac_n "checking for Apache 1.x (hooks) module support""... $ac_c" 1>&6
-echo "configure:7572: checking for Apache 1.x (hooks) module support" >&5
+echo "configure:7581: checking for Apache 1.x (hooks) module support" >&5
 
 if test "$PHP_SAPI" != "apache" && test "$PHP_SAPI" != "apache_hooks" && test "$PHP_APACHE_HOOKS_STATIC" != "no"; then
 
@@ -7600,23 +7604,32 @@ EOF
     APACHE_INCLUDE=-I$PHP_APACHE_HOOKS_STATIC/src
     APACHE_TARGET=$PHP_APACHE_HOOKS_STATIC/src
     
-  if test "$PHP_SAPI" != "default"; then
-{ echo "configure: error: 
+  if test "static" = "program"; then
+    PHP_BINARIES="$PHP_BINARIES apache_hooks"
+  elif test "$PHP_SAPI" != "none"; then
+    { echo "configure: error: 
 +--------------------------------------------------------------------+
 |                        *** ATTENTION ***                           |
 |                                                                    |
 | You've configured multiple SAPIs to be build. You can build only   |
-| one SAPI module and CLI binary at the same time.                   |
+| one SAPI module plus CGI, CLI and FPM binaries at the same time.   |
 +--------------------------------------------------------------------+
 " 1>&2; exit 1; }
+  else
+    PHP_SAPI=apache_hooks
   fi
 
-  PHP_SAPI=apache_hooks
   
+  
+    BUILD_DIR="$BUILD_DIR sapi/apache_hooks"
+  
+
+
+  PHP_INSTALLED_SAPIS="$PHP_INSTALLED_SAPIS apache_hooks"
+
   case "static" in
   static) 
   
-  OVERALL_TARGET=php
   php_c_pre='$(LIBTOOL) --mode=compile $(CC)'
   php_c_meta='$(COMMON_FLAGS) $(CFLAGS_CLEAN) $(EXTRA_CFLAGS)'
   php_c_post=
@@ -7638,14 +7651,11 @@ EOF
   shared_cxx_post=
   shared_lo=lo
 
-  php_build_target=program
-
   OVERALL_TARGET=libphp$PHP_MAJOR_VERSION.la
-  php_build_target=static
+  php_sapi_module=static
 ;;
   shared) 
   
-  OVERALL_TARGET=php
   php_c_pre='$(LIBTOOL) --mode=compile $(CC)'
   php_c_meta='$(COMMON_FLAGS) $(CFLAGS_CLEAN) $(EXTRA_CFLAGS)'
   php_c_post=
@@ -7667,10 +7677,8 @@ EOF
   shared_cxx_post=
   shared_lo=lo
 
-  php_build_target=program
-
   OVERALL_TARGET=libphp$PHP_MAJOR_VERSION.la
-  php_build_target=shared
+  php_sapi_module=shared
   
   php_c_pre=$shared_c_pre
   php_c_meta=$shared_c_meta
@@ -7682,7 +7690,6 @@ EOF
 ;;
   bundle) 
   
-  OVERALL_TARGET=php
   php_c_pre='$(LIBTOOL) --mode=compile $(CC)'
   php_c_meta='$(COMMON_FLAGS) $(CFLAGS_CLEAN) $(EXTRA_CFLAGS)'
   php_c_post=
@@ -7704,13 +7711,10 @@ EOF
   shared_cxx_post=
   shared_lo=lo
 
-  php_build_target=program
-
   OVERALL_TARGET=libs/libphp$PHP_MAJOR_VERSION.bundle
-  php_build_target=static
+  php_sapi_module=static
 ;;
   program) 
-  OVERALL_TARGET=php
   php_c_pre='$(LIBTOOL) --mode=compile $(CC)'
   php_c_meta='$(COMMON_FLAGS) $(CFLAGS_CLEAN) $(EXTRA_CFLAGS)'
   php_c_post=
@@ -7731,12 +7735,12 @@ EOF
   shared_cxx_meta='$(COMMON_FLAGS) $(CXXFLAGS_CLEAN) $(EXTRA_CXXFLAGS) '$pic_setting
   shared_cxx_post=
   shared_lo=lo
-
-  php_build_target=program
 ;;
   esac
     
   
+    install_sapi="install-sapi"
+    
   
   case sapi/apache_hooks in
   "") ac_srcdir="$abs_srcdir/"; unset ac_bdir; ac_inc="-I. -I$abs_srcdir" ;;
@@ -7779,6 +7783,7 @@ EOF
   done
 
 
+  
 
     APACHE_HOOKS_INSTALL="mkdir -p $APACHE_TARGET; cp $SAPI_STATIC $APACHE_HOOKS_INSTALL_FILES $APACHE_TARGET"
     PHP_LIBS="-L. -lphp3"
@@ -7802,23 +7807,32 @@ EOF
       mkdir $APACHE_TARGET
     fi
     
-  if test "$PHP_SAPI" != "default"; then
-{ echo "configure: error: 
+  if test "static" = "program"; then
+    PHP_BINARIES="$PHP_BINARIES apache_hooks"
+  elif test "$PHP_SAPI" != "none"; then
+    { echo "configure: error: 
 +--------------------------------------------------------------------+
 |                        *** ATTENTION ***                           |
 |                                                                    |
 | You've configured multiple SAPIs to be build. You can build only   |
-| one SAPI module and CLI binary at the same time.                   |
+| one SAPI module plus CGI, CLI and FPM binaries at the same time.   |
 +--------------------------------------------------------------------+
 " 1>&2; exit 1; }
+  else
+    PHP_SAPI=apache_hooks
   fi
 
-  PHP_SAPI=apache_hooks
   
+  
+    BUILD_DIR="$BUILD_DIR sapi/apache_hooks"
+  
+
+
+  PHP_INSTALLED_SAPIS="$PHP_INSTALLED_SAPIS apache_hooks"
+
   case "static" in
   static) 
   
-  OVERALL_TARGET=php
   php_c_pre='$(LIBTOOL) --mode=compile $(CC)'
   php_c_meta='$(COMMON_FLAGS) $(CFLAGS_CLEAN) $(EXTRA_CFLAGS)'
   php_c_post=
@@ -7840,14 +7854,11 @@ EOF
   shared_cxx_post=
   shared_lo=lo
 
-  php_build_target=program
-
   OVERALL_TARGET=libphp$PHP_MAJOR_VERSION.la
-  php_build_target=static
+  php_sapi_module=static
 ;;
   shared) 
   
-  OVERALL_TARGET=php
   php_c_pre='$(LIBTOOL) --mode=compile $(CC)'
   php_c_meta='$(COMMON_FLAGS) $(CFLAGS_CLEAN) $(EXTRA_CFLAGS)'
   php_c_post=
@@ -7869,10 +7880,8 @@ EOF
   shared_cxx_post=
   shared_lo=lo
 
-  php_build_target=program
-
   OVERALL_TARGET=libphp$PHP_MAJOR_VERSION.la
-  php_build_target=shared
+  php_sapi_module=shared
   
   php_c_pre=$shared_c_pre
   php_c_meta=$shared_c_meta
@@ -7884,7 +7893,6 @@ EOF
 ;;
   bundle) 
   
-  OVERALL_TARGET=php
   php_c_pre='$(LIBTOOL) --mode=compile $(CC)'
   php_c_meta='$(COMMON_FLAGS) $(CFLAGS_CLEAN) $(EXTRA_CFLAGS)'
   php_c_post=
@@ -7906,13 +7914,10 @@ EOF
   shared_cxx_post=
   shared_lo=lo
 
-  php_build_target=program
-
   OVERALL_TARGET=libs/libphp$PHP_MAJOR_VERSION.bundle
-  php_build_target=static
+  php_sapi_module=static
 ;;
   program) 
-  OVERALL_TARGET=php
   php_c_pre='$(LIBTOOL) --mode=compile $(CC)'
   php_c_meta='$(COMMON_FLAGS) $(CFLAGS_CLEAN) $(EXTRA_CFLAGS)'
   php_c_post=
@@ -7933,12 +7938,12 @@ EOF
   shared_cxx_meta='$(COMMON_FLAGS) $(CXXFLAGS_CLEAN) $(EXTRA_CXXFLAGS) '$pic_setting
   shared_cxx_post=
   shared_lo=lo
-
-  php_build_target=program
 ;;
   esac
     
   
+    install_sapi="install-sapi"
+    
   
   case sapi/apache_hooks in
   "") ac_srcdir="$abs_srcdir/"; unset ac_bdir; ac_inc="-I. -I$abs_srcdir" ;;
@@ -7981,6 +7986,7 @@ EOF
   done
 
 
+  
 
     APACHE_HOOKS_INSTALL="mkdir -p $APACHE_TARGET; cp $SAPI_STATIC $APACHE_TARGET/libmodphp5.a; cp $APACHE_HOOKS_INSTALL_FILES $APACHE_TARGET; cp $srcdir/sapi/apache_hooks/apMakefile.tmpl $APACHE_TARGET/Makefile.tmpl; cp $srcdir/sapi/apache_hooks/apMakefile.libdir $APACHE_TARGET/Makefile.libdir"
     PHP_LIBS="-Lmodules/php5 -L../modules/php5 -L../../modules/php5 -lmodphp5"
@@ -8015,23 +8021,32 @@ EOF
       mkdir $APACHE_TARGET
     fi
     
-  if test "$PHP_SAPI" != "default"; then
-{ echo "configure: error: 
+  if test "static" = "program"; then
+    PHP_BINARIES="$PHP_BINARIES apache_hooks"
+  elif test "$PHP_SAPI" != "none"; then
+    { echo "configure: error: 
 +--------------------------------------------------------------------+
 |                        *** ATTENTION ***                           |
 |                                                                    |
 | You've configured multiple SAPIs to be build. You can build only   |
-| one SAPI module and CLI binary at the same time.                   |
+| one SAPI module plus CGI, CLI and FPM binaries at the same time.   |
 +--------------------------------------------------------------------+
 " 1>&2; exit 1; }
+  else
+    PHP_SAPI=apache_hooks
   fi
 
-  PHP_SAPI=apache_hooks
   
+  
+    BUILD_DIR="$BUILD_DIR sapi/apache_hooks"
+  
+
+
+  PHP_INSTALLED_SAPIS="$PHP_INSTALLED_SAPIS apache_hooks"
+
   case "static" in
   static) 
   
-  OVERALL_TARGET=php
   php_c_pre='$(LIBTOOL) --mode=compile $(CC)'
   php_c_meta='$(COMMON_FLAGS) $(CFLAGS_CLEAN) $(EXTRA_CFLAGS)'
   php_c_post=
@@ -8053,14 +8068,11 @@ EOF
   shared_cxx_post=
   shared_lo=lo
 
-  php_build_target=program
-
   OVERALL_TARGET=libphp$PHP_MAJOR_VERSION.la
-  php_build_target=static
+  php_sapi_module=static
 ;;
   shared) 
   
-  OVERALL_TARGET=php
   php_c_pre='$(LIBTOOL) --mode=compile $(CC)'
   php_c_meta='$(COMMON_FLAGS) $(CFLAGS_CLEAN) $(EXTRA_CFLAGS)'
   php_c_post=
@@ -8082,10 +8094,8 @@ EOF
   shared_cxx_post=
   shared_lo=lo
 
-  php_build_target=program
-
   OVERALL_TARGET=libphp$PHP_MAJOR_VERSION.la
-  php_build_target=shared
+  php_sapi_module=shared
   
   php_c_pre=$shared_c_pre
   php_c_meta=$shared_c_meta
@@ -8097,7 +8107,6 @@ EOF
 ;;
   bundle) 
   
-  OVERALL_TARGET=php
   php_c_pre='$(LIBTOOL) --mode=compile $(CC)'
   php_c_meta='$(COMMON_FLAGS) $(CFLAGS_CLEAN) $(EXTRA_CFLAGS)'
   php_c_post=
@@ -8119,13 +8128,10 @@ EOF
   shared_cxx_post=
   shared_lo=lo
 
-  php_build_target=program
-
   OVERALL_TARGET=libs/libphp$PHP_MAJOR_VERSION.bundle
-  php_build_target=static
+  php_sapi_module=static
 ;;
   program) 
-  OVERALL_TARGET=php
   php_c_pre='$(LIBTOOL) --mode=compile $(CC)'
   php_c_meta='$(COMMON_FLAGS) $(CFLAGS_CLEAN) $(EXTRA_CFLAGS)'
   php_c_post=
@@ -8146,12 +8152,12 @@ EOF
   shared_cxx_meta='$(COMMON_FLAGS) $(CXXFLAGS_CLEAN) $(EXTRA_CXXFLAGS) '$pic_setting
   shared_cxx_post=
   shared_lo=lo
-
-  php_build_target=program
 ;;
   esac
     
   
+    install_sapi="install-sapi"
+    
   
   case sapi/apache_hooks in
   "") ac_srcdir="$abs_srcdir/"; unset ac_bdir; ac_inc="-I. -I$abs_srcdir" ;;
@@ -8194,6 +8200,7 @@ EOF
   done
 
 
+  
 
     PHP_LIBS="-Lmodules/php5 -L../modules/php5 -L../../modules/php5 -lmodphp5"
     APACHE_HOOKS_INSTALL="mkdir -p $APACHE_TARGET; cp $SAPI_STATIC $APACHE_TARGET/libmodphp5.a; cp $APACHE_HOOKS_INSTALL_FILES $APACHE_TARGET; cp $srcdir/sapi/apache_hooks/apMakefile.tmpl $APACHE_TARGET/Makefile.tmpl; cp $srcdir/sapi/apache_hooks/apMakefile.libdir $APACHE_TARGET/Makefile.libdir"
@@ -8224,23 +8231,32 @@ EOF
     APACHE_INCLUDE="-I$PHP_APACHE_HOOKS_STATIC/apache -I$PHP_APACHE_HOOKS_STATIC/ssl/include"
     APACHE_TARGET=$PHP_APACHE_HOOKS_STATIC/apache
     
-  if test "$PHP_SAPI" != "default"; then
-{ echo "configure: error: 
+  if test "static" = "program"; then
+    PHP_BINARIES="$PHP_BINARIES apache_hooks"
+  elif test "$PHP_SAPI" != "none"; then
+    { echo "configure: error: 
 +--------------------------------------------------------------------+
 |                        *** ATTENTION ***                           |
 |                                                                    |
 | You've configured multiple SAPIs to be build. You can build only   |
-| one SAPI module and CLI binary at the same time.                   |
+| one SAPI module plus CGI, CLI and FPM binaries at the same time.   |
 +--------------------------------------------------------------------+
 " 1>&2; exit 1; }
+  else
+    PHP_SAPI=apache_hooks
   fi
 
-  PHP_SAPI=apache_hooks
   
+  
+    BUILD_DIR="$BUILD_DIR sapi/apache_hooks"
+  
+
+
+  PHP_INSTALLED_SAPIS="$PHP_INSTALLED_SAPIS apache_hooks"
+
   case "static" in
   static) 
   
-  OVERALL_TARGET=php
   php_c_pre='$(LIBTOOL) --mode=compile $(CC)'
   php_c_meta='$(COMMON_FLAGS) $(CFLAGS_CLEAN) $(EXTRA_CFLAGS)'
   php_c_post=
@@ -8262,14 +8278,11 @@ EOF
   shared_cxx_post=
   shared_lo=lo
 
-  php_build_target=program
-
   OVERALL_TARGET=libphp$PHP_MAJOR_VERSION.la
-  php_build_target=static
+  php_sapi_module=static
 ;;
   shared) 
   
-  OVERALL_TARGET=php
   php_c_pre='$(LIBTOOL) --mode=compile $(CC)'
   php_c_meta='$(COMMON_FLAGS) $(CFLAGS_CLEAN) $(EXTRA_CFLAGS)'
   php_c_post=
@@ -8291,10 +8304,8 @@ EOF
   shared_cxx_post=
   shared_lo=lo
 
-  php_build_target=program
-
   OVERALL_TARGET=libphp$PHP_MAJOR_VERSION.la
-  php_build_target=shared
+  php_sapi_module=shared
   
   php_c_pre=$shared_c_pre
   php_c_meta=$shared_c_meta
@@ -8306,7 +8317,6 @@ EOF
 ;;
   bundle) 
   
-  OVERALL_TARGET=php
   php_c_pre='$(LIBTOOL) --mode=compile $(CC)'
   php_c_meta='$(COMMON_FLAGS) $(CFLAGS_CLEAN) $(EXTRA_CFLAGS)'
   php_c_post=
@@ -8328,13 +8338,10 @@ EOF
   shared_cxx_post=
   shared_lo=lo
 
-  php_build_target=program
-
   OVERALL_TARGET=libs/libphp$PHP_MAJOR_VERSION.bundle
-  php_build_target=static
+  php_sapi_module=static
 ;;
   program) 
-  OVERALL_TARGET=php
   php_c_pre='$(LIBTOOL) --mode=compile $(CC)'
   php_c_meta='$(COMMON_FLAGS) $(CFLAGS_CLEAN) $(EXTRA_CFLAGS)'
   php_c_post=
@@ -8355,12 +8362,12 @@ EOF
   shared_cxx_meta='$(COMMON_FLAGS) $(CXXFLAGS_CLEAN) $(EXTRA_CXXFLAGS) '$pic_setting
   shared_cxx_post=
   shared_lo=lo
-
-  php_build_target=program
 ;;
   esac
     
   
+    install_sapi="install-sapi"
+    
   
   case sapi/apache_hooks in
   "") ac_srcdir="$abs_srcdir/"; unset ac_bdir; ac_inc="-I. -I$abs_srcdir" ;;
@@ -8403,6 +8410,7 @@ EOF
   done
 
 
+  
 
     PHP_LIBS="-Lmodules/php5 -L../modules/php5 -L../../modules/php5 -lmodphp5"
     APACHE_HOOKS_INSTALL="mkdir -p $APACHE_TARGET; cp $SAPI_STATIC $APACHE_TARGET/libmodphp5.a; cp $APACHE_HOOKS_INSTALL_FILES $APACHE_TARGET"
@@ -8445,7 +8453,7 @@ fi
 php_enable_mod_charset=no
 
 echo $ac_n "checking whether to enable Apache charset compatibility option""... $ac_c" 1>&6
-echo "configure:8449: checking whether to enable Apache charset compatibility option" >&5
+echo "configure:8462: checking whether to enable Apache charset compatibility option" >&5
 # Check whether --enable-mod-charset or --disable-mod-charset was given.
 if test "${enable_mod_charset+set}" = set; then
   enableval="$enable_mod_charset"
@@ -8477,7 +8485,7 @@ if test "$APACHE_HOOKS_MODULE" = "yes"; then
         
   gcc_arg_name=ac_cv_gcc_arg_rdynamic
   echo $ac_n "checking whether $CC supports -rdynamic""... $ac_c" 1>&6
-echo "configure:8481: checking whether $CC supports -rdynamic" >&5
+echo "configure:8494: checking whether $CC supports -rdynamic" >&5
 if eval "test \"`echo '$''{'ac_cv_gcc_arg_rdynamic'+set}'`\" = set"; then
   echo $ac_n "(cached) $ac_c" 1>&6
 else
@@ -8520,7 +8528,7 @@ if test -n "$APACHE_HOOKS_INSTALL"; then
 
   
 echo $ac_n "checking for member fd in BUFF *""... $ac_c" 1>&6
-echo "configure:8524: checking for member fd in BUFF *" >&5
+echo "configure:8537: checking for member fd in BUFF *" >&5
 if eval "test \"`echo '$''{'ac_cv_php_fd_in_buff'+set}'`\" = set"; then
   echo $ac_n "(cached) $ac_c" 1>&6
 else
@@ -8532,14 +8540,14 @@ else
     CPPFLAGS="$CPPFLAGS $APACHE_INCLUDE"
   fi
   cat > conftest.$ac_ext <<EOF
-#line 8536 "configure"
+#line 8549 "configure"
 #include "confdefs.h"
 #include <httpd.h>
 int main() {
 conn_rec *c; int fd = c->client->fd;
 ; return 0; }
 EOF
-if { (eval echo configure:8543: \"$ac_compile\") 1>&5; (eval $ac_compile) 2>&5; }; then
+if { (eval echo configure:8556: \"$ac_compile\") 1>&5; (eval $ac_compile) 2>&5; }; then
   rm -rf conftest*
   
     ac_cv_php_fd_in_buff=yes
@@ -8613,7 +8621,7 @@ ext_output=$PHP_CAUDIUM
 
 
 echo $ac_n "checking for Caudium support""... $ac_c" 1>&6
-echo "configure:8617: checking for Caudium support" >&5
+echo "configure:8630: checking for Caudium support" >&5
 
 if test "$PHP_CAUDIUM" != "no"; then
   if test "$prefix" = "NONE"; then CPREF=/usr/local/; fi
@@ -8683,7 +8691,7 @@ if test "$PHP_CAUDIUM" != "no"; then
       PIKE_C_INCLUDE=/usr/local/include/`basename $PIKE`
     fi
     echo $ac_n "checking for C includes in $PIKE_C_INCLUDE""... $ac_c" 1>&6
-echo "configure:8687: checking for C includes in $PIKE_C_INCLUDE" >&5
+echo "configure:8700: checking for C includes in $PIKE_C_INCLUDE" >&5
     if test -f $PIKE_C_INCLUDE/version.h; then
       PIKE_TEST_VER=`$PIKE -e 'string v; int rel;sscanf(version(), "Pike v%s release %d", v, rel); write(v+"."+rel);'`
       ###### VERSION MATCH CHECK #######
@@ -8754,23 +8762,32 @@ echo "configure:8687: checking for C includes in $PIKE_C_INCLUDE" >&5
 EOF
 
   
-  if test "$PHP_SAPI" != "default"; then
-{ echo "configure: error: 
+  if test "shared" = "program"; then
+    PHP_BINARIES="$PHP_BINARIES caudium"
+  elif test "$PHP_SAPI" != "none"; then
+    { echo "configure: error: 
 +--------------------------------------------------------------------+
 |                        *** ATTENTION ***                           |
 |                                                                    |
 | You've configured multiple SAPIs to be build. You can build only   |
-| one SAPI module and CLI binary at the same time.                   |
+| one SAPI module plus CGI, CLI and FPM binaries at the same time.   |
 +--------------------------------------------------------------------+
 " 1>&2; exit 1; }
+  else
+    PHP_SAPI=caudium
   fi
 
-  PHP_SAPI=caudium
   
+  
+    BUILD_DIR="$BUILD_DIR sapi/caudium"
+  
+
+
+  PHP_INSTALLED_SAPIS="$PHP_INSTALLED_SAPIS caudium"
+
   case "shared" in
   static) 
   
-  OVERALL_TARGET=php
   php_c_pre='$(LIBTOOL) --mode=compile $(CC)'
   php_c_meta='$(COMMON_FLAGS) $(CFLAGS_CLEAN) $(EXTRA_CFLAGS)'
   php_c_post=
@@ -8792,14 +8809,11 @@ EOF
   shared_cxx_post=
   shared_lo=lo
 
-  php_build_target=program
-
   OVERALL_TARGET=libphp$PHP_MAJOR_VERSION.la
-  php_build_target=static
+  php_sapi_module=static
 ;;
   shared) 
   
-  OVERALL_TARGET=php
   php_c_pre='$(LIBTOOL) --mode=compile $(CC)'
   php_c_meta='$(COMMON_FLAGS) $(CFLAGS_CLEAN) $(EXTRA_CFLAGS)'
   php_c_post=
@@ -8821,10 +8835,8 @@ EOF
   shared_cxx_post=
   shared_lo=lo
 
-  php_build_target=program
-
   OVERALL_TARGET=libphp$PHP_MAJOR_VERSION.la
-  php_build_target=shared
+  php_sapi_module=shared
   
   php_c_pre=$shared_c_pre
   php_c_meta=$shared_c_meta
@@ -8836,7 +8848,6 @@ EOF
 ;;
   bundle) 
   
-  OVERALL_TARGET=php
   php_c_pre='$(LIBTOOL) --mode=compile $(CC)'
   php_c_meta='$(COMMON_FLAGS) $(CFLAGS_CLEAN) $(EXTRA_CFLAGS)'
   php_c_post=
@@ -8858,13 +8869,10 @@ EOF
   shared_cxx_post=
   shared_lo=lo
 
-  php_build_target=program
-
   OVERALL_TARGET=libs/libphp$PHP_MAJOR_VERSION.bundle
-  php_build_target=static
+  php_sapi_module=static
 ;;
   program) 
-  OVERALL_TARGET=php
   php_c_pre='$(LIBTOOL) --mode=compile $(CC)'
   php_c_meta='$(COMMON_FLAGS) $(CFLAGS_CLEAN) $(EXTRA_CFLAGS)'
   php_c_post=
@@ -8885,12 +8893,12 @@ EOF
   shared_cxx_meta='$(COMMON_FLAGS) $(CXXFLAGS_CLEAN) $(EXTRA_CXXFLAGS) '$pic_setting
   shared_cxx_post=
   shared_lo=lo
-
-  php_build_target=program
 ;;
   esac
     
   
+    install_sapi="install-sapi"
+    
   
   case sapi/caudium in
   "") ac_srcdir="$abs_srcdir/"; unset ac_bdir; ac_inc="-I. -I$abs_srcdir" ;;
@@ -8933,6 +8941,7 @@ EOF
   done
 
 
+  
 
   INSTALL_IT="\$(INSTALL) -m 0755 $SAPI_SHARED $PHP_CAUDIUM/lib/$PIKE_VERSION/PHP5.so"
   RESULT="  *** Pike binary used:         $PIKE
@@ -8972,44 +8981,232 @@ ext_output=$PHP_CLI
 
 
 echo $ac_n "checking for CLI build""... $ac_c" 1>&6
-echo "configure:8976: checking for CLI build" >&5
+echo "configure:8990: checking for CLI build" >&5
 if test "$PHP_CLI" != "no"; then
   
   src=$abs_srcdir/sapi/cli/Makefile.frag
-  ac_srcdir=$abs_srcdir/sapi/cli
-  ac_builddir=sapi/cli
+  ac_srcdir=$ext_srcdir
+  ac_builddir=$ext_builddir
   test -f "$src" && $SED -e "s#\$(srcdir)#$ac_srcdir#g" -e "s#\$(builddir)#$ac_builddir#g" $src  >> Makefile.fragments
 
-  SAPI_CLI_PATH=sapi/cli/php
+
+    SAPI_CLI_PATH=sapi/cli/php
+
+    
+  if test "program" = "program"; then
+    PHP_BINARIES="$PHP_BINARIES cli"
+  elif test "$PHP_SAPI" != "none"; then
+    { echo "configure: error: 
++--------------------------------------------------------------------+
+|                        *** ATTENTION ***                           |
+|                                                                    |
+| You've configured multiple SAPIs to be build. You can build only   |
+| one SAPI module plus CGI, CLI and FPM binaries at the same time.   |
++--------------------------------------------------------------------+
+" 1>&2; exit 1; }
+  else
+    PHP_SAPI=cli
+  fi
+
+  
+  
+    BUILD_DIR="$BUILD_DIR sapi/cli"
+  
+
+
+  PHP_INSTALLED_SAPIS="$PHP_INSTALLED_SAPIS cli"
+
+  case "program" in
+  static) 
+  
+  php_c_pre='$(LIBTOOL) --mode=compile $(CC)'
+  php_c_meta='$(COMMON_FLAGS) $(CFLAGS_CLEAN) $(EXTRA_CFLAGS)'
+  php_c_post=
+  php_cxx_pre='$(LIBTOOL) --mode=compile $(CXX)'
+  php_cxx_meta='$(COMMON_FLAGS) $(CXXFLAGS_CLEAN) $(EXTRA_CXXFLAGS)'
+  php_cxx_post=
+  php_lo=lo
+
+  case $with_pic in
+    yes) pic_setting='-prefer-pic';;
+    no)  pic_setting='-prefer-non-pic';;
+  esac
+
+  shared_c_pre='$(LIBTOOL) --mode=compile $(CC)'
+  shared_c_meta='$(COMMON_FLAGS) $(CFLAGS_CLEAN) $(EXTRA_CFLAGS) '$pic_setting
+  shared_c_post=
+  shared_cxx_pre='$(LIBTOOL) --mode=compile $(CXX)'
+  shared_cxx_meta='$(COMMON_FLAGS) $(CXXFLAGS_CLEAN) $(EXTRA_CXXFLAGS) '$pic_setting
+  shared_cxx_post=
+  shared_lo=lo
+
+  OVERALL_TARGET=libphp$PHP_MAJOR_VERSION.la
+  php_sapi_module=static
+;;
+  shared) 
+  
+  php_c_pre='$(LIBTOOL) --mode=compile $(CC)'
+  php_c_meta='$(COMMON_FLAGS) $(CFLAGS_CLEAN) $(EXTRA_CFLAGS)'
+  php_c_post=
+  php_cxx_pre='$(LIBTOOL) --mode=compile $(CXX)'
+  php_cxx_meta='$(COMMON_FLAGS) $(CXXFLAGS_CLEAN) $(EXTRA_CXXFLAGS)'
+  php_cxx_post=
+  php_lo=lo
+
+  case $with_pic in
+    yes) pic_setting='-prefer-pic';;
+    no)  pic_setting='-prefer-non-pic';;
+  esac
+
+  shared_c_pre='$(LIBTOOL) --mode=compile $(CC)'
+  shared_c_meta='$(COMMON_FLAGS) $(CFLAGS_CLEAN) $(EXTRA_CFLAGS) '$pic_setting
+  shared_c_post=
+  shared_cxx_pre='$(LIBTOOL) --mode=compile $(CXX)'
+  shared_cxx_meta='$(COMMON_FLAGS) $(CXXFLAGS_CLEAN) $(EXTRA_CXXFLAGS) '$pic_setting
+  shared_cxx_post=
+  shared_lo=lo
+
+  OVERALL_TARGET=libphp$PHP_MAJOR_VERSION.la
+  php_sapi_module=shared
+  
+  php_c_pre=$shared_c_pre
+  php_c_meta=$shared_c_meta
+  php_c_post=$shared_c_post
+  php_cxx_pre=$shared_cxx_pre
+  php_cxx_meta=$shared_cxx_meta
+  php_cxx_post=$shared_cxx_post
+  php_lo=$shared_lo
+;;
+  bundle) 
+  
+  php_c_pre='$(LIBTOOL) --mode=compile $(CC)'
+  php_c_meta='$(COMMON_FLAGS) $(CFLAGS_CLEAN) $(EXTRA_CFLAGS)'
+  php_c_post=
+  php_cxx_pre='$(LIBTOOL) --mode=compile $(CXX)'
+  php_cxx_meta='$(COMMON_FLAGS) $(CXXFLAGS_CLEAN) $(EXTRA_CXXFLAGS)'
+  php_cxx_post=
+  php_lo=lo
+
+  case $with_pic in
+    yes) pic_setting='-prefer-pic';;
+    no)  pic_setting='-prefer-non-pic';;
+  esac
+
+  shared_c_pre='$(LIBTOOL) --mode=compile $(CC)'
+  shared_c_meta='$(COMMON_FLAGS) $(CFLAGS_CLEAN) $(EXTRA_CFLAGS) '$pic_setting
+  shared_c_post=
+  shared_cxx_pre='$(LIBTOOL) --mode=compile $(CXX)'
+  shared_cxx_meta='$(COMMON_FLAGS) $(CXXFLAGS_CLEAN) $(EXTRA_CXXFLAGS) '$pic_setting
+  shared_cxx_post=
+  shared_lo=lo
+
+  OVERALL_TARGET=libs/libphp$PHP_MAJOR_VERSION.bundle
+  php_sapi_module=static
+;;
+  program) 
+  php_c_pre='$(LIBTOOL) --mode=compile $(CC)'
+  php_c_meta='$(COMMON_FLAGS) $(CFLAGS_CLEAN) $(EXTRA_CFLAGS)'
+  php_c_post=
+  php_cxx_pre='$(LIBTOOL) --mode=compile $(CXX)'
+  php_cxx_meta='$(COMMON_FLAGS) $(CXXFLAGS_CLEAN) $(EXTRA_CXXFLAGS)'
+  php_cxx_post=
+  php_lo=lo
+
+  case $with_pic in
+    yes) pic_setting='-prefer-pic';;
+    no)  pic_setting='-prefer-non-pic';;
+  esac
+
+  shared_c_pre='$(LIBTOOL) --mode=compile $(CC)'
+  shared_c_meta='$(COMMON_FLAGS) $(CFLAGS_CLEAN) $(EXTRA_CFLAGS) '$pic_setting
+  shared_c_post=
+  shared_cxx_pre='$(LIBTOOL) --mode=compile $(CXX)'
+  shared_cxx_meta='$(COMMON_FLAGS) $(CXXFLAGS_CLEAN) $(EXTRA_CXXFLAGS) '$pic_setting
+  shared_cxx_post=
+  shared_lo=lo
+;;
+  esac
+    
+  
+    install_binaries="install-binaries"
+    install_binary_targets="$install_binary_targets install-cli"
+    
+  PHP_VAR_SUBST="$PHP_VAR_SUBST PHP_CLI_OBJS"
+
+    
+  case sapi/cli in
+  "") ac_srcdir="$abs_srcdir/"; unset ac_bdir; ac_inc="-I. -I$abs_srcdir" ;;
+  /*) ac_srcdir=`echo "sapi/cli"|cut -c 2-`"/"; ac_bdir=$ac_srcdir; ac_inc="-I$ac_bdir -I$abs_srcdir/$ac_bdir" ;;
+  *) ac_srcdir="$abs_srcdir/sapi/cli/"; ac_bdir="sapi/cli/"; ac_inc="-I$ac_bdir -I$ac_srcdir" ;;
+  esac
+  
+  
+
+  b_c_pre=$php_c_pre
+  b_cxx_pre=$php_cxx_pre
+  b_c_meta=$php_c_meta
+  b_cxx_meta=$php_cxx_meta
+  b_c_post=$php_c_post
+  b_cxx_post=$php_cxx_post
+  b_lo=$php_lo
+
+
+  old_IFS=$IFS
+  for ac_src in php_cli.c php_cli_readline.c; do
+  
+      IFS=.
+      set $ac_src
+      ac_obj=$1
+      IFS=$old_IFS
+      
+      PHP_CLI_OBJS="$PHP_CLI_OBJS $ac_bdir$ac_obj.lo"
+
+      case $ac_src in
+        *.c) ac_comp="$b_c_pre  $ac_inc $b_c_meta -c $ac_srcdir$ac_src -o $ac_bdir$ac_obj.$b_lo $b_c_post" ;;
+        *.s) ac_comp="$b_c_pre  $ac_inc $b_c_meta -c $ac_srcdir$ac_src -o $ac_bdir$ac_obj.$b_lo $b_c_post" ;;
+        *.S) ac_comp="$b_c_pre  $ac_inc $b_c_meta -c $ac_srcdir$ac_src -o $ac_bdir$ac_obj.$b_lo $b_c_post" ;;
+        *.cpp|*.cc|*.cxx) ac_comp="$b_cxx_pre  $ac_inc $b_cxx_meta -c $ac_srcdir$ac_src -o $ac_bdir$ac_obj.$b_lo $b_cxx_post" ;;
+      esac
+
+    cat >>Makefile.objects<<EOF
+$ac_bdir$ac_obj.lo: $ac_srcdir$ac_src
+	$ac_comp
+EOF
+  done
+
   
-  PHP_VAR_SUBST="$PHP_VAR_SUBST SAPI_CLI_PATH"
 
 
   case $host_alias in
   *aix*)
-    if test "$php_build_target" = "shared"; then
-      BUILD_CLI="echo '\#! .' > php.sym && echo >>php.sym && nm -BCpg \`echo \$(PHP_GLOBAL_OBJS) \$(PHP_CLI_OBJS) | sed 's/\([A-Za-z0-9_]*\)\.lo/.libs\/\1.o/g'\` | \$(AWK) '{ if (((\$\$2 == \"T\") || (\$\$2 == \"D\") || (\$\$2 == \"B\")) && (substr(\$\$3,1,1) != \".\")) { print \$\$3 } }' | sort -u >> php.sym && \$(LIBTOOL) --mode=link \$(CC) -export-dynamic \$(CFLAGS_CLEAN) \$(EXTRA_CFLAGS) \$(EXTRA_LDFLAGS_PROGRAM) \$(LDFLAGS) -Wl,-brtl -Wl,-bE:php.sym \$(PHP_RPATHS) \$(PHP_GLOBAL_OBJS) \$(PHP_CLI_OBJS) \$(EXTRA_LIBS) \$(ZEND_EXTRA_LIBS) -o \$(SAPI_CLI_PATH)"
+    if test "$php_sapi_module" = "shared"; then
+      BUILD_CLI="echo '\#! .' > php.sym && echo >>php.sym && nm -BCpg \`echo \$(PHP_GLOBAL_OBJS) \$(PHP_BINARY_OBJS) \$(PHP_CLI_OBJS) | sed 's/\([A-Za-z0-9_]*\)\.lo/.libs\/\1.o/g'\` | \$(AWK) '{ if (((\$\$2 == \"T\") || (\$\$2 == \"D\") || (\$\$2 == \"B\")) && (substr(\$\$3,1,1) != \".\")) { print \$\$3 } }' | sort -u >> php.sym && \$(LIBTOOL) --mode=link \$(CC) -export-dynamic \$(CFLAGS_CLEAN) \$(EXTRA_CFLAGS) \$(EXTRA_LDFLAGS_PROGRAM) \$(LDFLAGS) -Wl,-brtl -Wl,-bE:php.sym \$(PHP_RPATHS) \$(PHP_GLOBAL_OBJS) \$(PHP_CLI_OBJS) \$(EXTRA_LIBS) \$(ZEND_EXTRA_LIBS) -o \$(SAPI_CLI_PATH)"
     else
-      BUILD_CLI="echo '\#! .' > php.sym && echo >>php.sym && nm -BCpg \`echo \$(PHP_GLOBAL_OBJS) \$(PHP_CLI_OBJS) | sed 's/\([A-Za-z0-9_]*\)\.lo/\1.o/g'\` | \$(AWK) '{ if (((\$\$2 == \"T\") || (\$\$2 == \"D\") || (\$\$2 == \"B\")) && (substr(\$\$3,1,1) != \".\")) { print \$\$3 } }' | sort -u >> php.sym && \$(LIBTOOL) --mode=link \$(CC) -export-dynamic \$(CFLAGS_CLEAN) \$(EXTRA_CFLAGS) \$(EXTRA_LDFLAGS_PROGRAM) \$(LDFLAGS) -Wl,-brtl -Wl,-bE:php.sym \$(PHP_RPATHS) \$(PHP_GLOBAL_OBJS) \$(PHP_CLI_OBJS) \$(EXTRA_LIBS) \$(ZEND_EXTRA_LIBS) -o \$(SAPI_CLI_PATH)"
+      BUILD_CLI="echo '\#! .' > php.sym && echo >>php.sym && nm -BCpg \`echo \$(PHP_GLOBAL_OBJS) \$(PHP_BINARY_OBJS) \$(PHP_CLI_OBJS) | sed 's/\([A-Za-z0-9_]*\)\.lo/\1.o/g'\` | \$(AWK) '{ if (((\$\$2 == \"T\") || (\$\$2 == \"D\") || (\$\$2 == \"B\")) && (substr(\$\$3,1,1) != \".\")) { print \$\$3 } }' | sort -u >> php.sym && \$(LIBTOOL) --mode=link \$(CC) -export-dynamic \$(CFLAGS_CLEAN) \$(EXTRA_CFLAGS) \$(EXTRA_LDFLAGS_PROGRAM) \$(LDFLAGS) -Wl,-brtl -Wl,-bE:php.sym \$(PHP_RPATHS) \$(PHP_GLOBAL_OBJS) \$(PHP_CLI_OBJS) \$(EXTRA_LIBS) \$(ZEND_EXTRA_LIBS) -o \$(SAPI_CLI_PATH)"
     fi
     ;;
   *darwin*)
-    BUILD_CLI="\$(CC) \$(CFLAGS_CLEAN) \$(EXTRA_CFLAGS) \$(EXTRA_LDFLAGS_PROGRAM) \$(LDFLAGS) \$(NATIVE_RPATHS) \$(PHP_GLOBAL_OBJS:.lo=.o) \$(PHP_CLI_OBJS:.lo=.o) \$(PHP_FRAMEWORKS) \$(EXTRA_LIBS) \$(ZEND_EXTRA_LIBS) -o \$(SAPI_CLI_PATH)"
+    BUILD_CLI="\$(CC) \$(CFLAGS_CLEAN) \$(EXTRA_CFLAGS) \$(EXTRA_LDFLAGS_PROGRAM) \$(LDFLAGS) \$(NATIVE_RPATHS) \$(PHP_GLOBAL_OBJS:.lo=.o) \$(PHP_BINARY_OBJS:.lo=.o) \$(PHP_CLI_OBJS:.lo=.o) \$(PHP_FRAMEWORKS) \$(EXTRA_LIBS) \$(ZEND_EXTRA_LIBS) -o \$(SAPI_CLI_PATH)"
     ;;
   *netware*)
-    BUILD_CLI="\$(LIBTOOL) --mode=link \$(CC) -export-dynamic \$(CFLAGS_CLEAN) \$(EXTRA_CFLAGS) \$(EXTRA_LDFLAGS_PROGRAM) \$(LDFLAGS) \$(PHP_RPATHS) \$(PHP_CLI_OBJS) \$(EXTRA_LIBS) \$(ZEND_EXTRA_LIBS) -Lnetware -lphp5lib -o \$(SAPI_CLI_PATH)"
+    BUILD_CLI="\$(LIBTOOL) --mode=link \$(CC) -export-dynamic \$(CFLAGS_CLEAN) \$(EXTRA_CFLAGS) \$(EXTRA_LDFLAGS_PROGRAM) \$(LDFLAGS) \$(PHP_RPATHS) \$(PHP_BINARY_OBJS) \$(PHP_CLI_OBJS) \$(EXTRA_LIBS) \$(ZEND_EXTRA_LIBS) -Lnetware -lphp5lib -o \$(SAPI_CLI_PATH)"
     ;;
   *)
-    BUILD_CLI="\$(LIBTOOL) --mode=link \$(CC) -export-dynamic \$(CFLAGS_CLEAN) \$(EXTRA_CFLAGS) \$(EXTRA_LDFLAGS_PROGRAM) \$(LDFLAGS) \$(PHP_RPATHS) \$(PHP_GLOBAL_OBJS) \$(PHP_CLI_OBJS) \$(EXTRA_LIBS) \$(ZEND_EXTRA_LIBS) -o \$(SAPI_CLI_PATH)"
+    BUILD_CLI="\$(LIBTOOL) --mode=link \$(CC) -export-dynamic \$(CFLAGS_CLEAN) \$(EXTRA_CFLAGS) \$(EXTRA_LDFLAGS_PROGRAM) \$(LDFLAGS) \$(PHP_RPATHS) \$(PHP_GLOBAL_OBJS) \$(PHP_BINARY_OBJS) \$(PHP_CLI_OBJS) \$(EXTRA_LIBS) \$(ZEND_EXTRA_LIBS) -o \$(SAPI_CLI_PATH)"
     ;;
   esac
-  INSTALL_CLI="\$(mkinstalldirs) \$(INSTALL_ROOT)\$(bindir); \$(INSTALL) -m 0755 \$(SAPI_CLI_PATH) \$(INSTALL_ROOT)\$(bindir)/\$(program_prefix)php\$(program_suffix)\$(EXEEXT)"
 
+    PHP_EXECUTABLE="\$(top_builddir)/\$(SAPI_CLI_PATH)"
   
-  PHP_VAR_SUBST="$PHP_VAR_SUBST BUILD_CLI"
+  PHP_VAR_SUBST="$PHP_VAR_SUBST PHP_EXECUTABLE"
+
+
+    
+  PHP_VAR_SUBST="$PHP_VAR_SUBST SAPI_CLI_PATH"
 
   
-  PHP_VAR_SUBST="$PHP_VAR_SUBST INSTALL_CLI"
+  PHP_VAR_SUBST="$PHP_VAR_SUBST BUILD_CLI"
+
 
   
   PHP_OUTPUT_FILES="$PHP_OUTPUT_FILES sapi/cli/php.1"
@@ -9022,7 +9219,7 @@ echo "$ac_t""$PHP_CLI" 1>&6
 php_with_continuity=no
 
 echo $ac_n "checking for Continuity support""... $ac_c" 1>&6
-echo "configure:9026: checking for Continuity support" >&5
+echo "configure:9228: checking for Continuity support" >&5
 # Check whether --with-continuity or --without-continuity was given.
 if test "${with_continuity+set}" = set; then
   withval="$with_continuity"
@@ -9046,7 +9243,7 @@ if test "$PHP_CONTINUITY" != "no"; then
     { echo "configure: error: Please specify the path to the root of your Continuity server using --with-continuity=DIR" 1>&2; exit 1; }
   fi
   echo $ac_n "checking for Continuity include files""... $ac_c" 1>&6
-echo "configure:9050: checking for Continuity include files" >&5
+echo "configure:9252: checking for Continuity include files" >&5
   if test -d $PHP_CONTINUITY/include ; then
     CAPI_INCLUDE=$PHP_CONTINUITY/include
     echo "$ac_t""Continuity Binary Distribution" 1>&6
@@ -9055,23 +9252,32 @@ echo "configure:9050: checking for Continuity include files" >&5
   fi
 
   
-  if test "$PHP_SAPI" != "default"; then
-{ echo "configure: error: 
+  if test "shared" = "program"; then
+    PHP_BINARIES="$PHP_BINARIES continuity"
+  elif test "$PHP_SAPI" != "none"; then
+    { echo "configure: error: 
 +--------------------------------------------------------------------+
 |                        *** ATTENTION ***                           |
 |                                                                    |
 | You've configured multiple SAPIs to be build. You can build only   |
-| one SAPI module and CLI binary at the same time.                   |
+| one SAPI module plus CGI, CLI and FPM binaries at the same time.   |
 +--------------------------------------------------------------------+
 " 1>&2; exit 1; }
+  else
+    PHP_SAPI=continuity
   fi
 
-  PHP_SAPI=continuity
   
+  
+    BUILD_DIR="$BUILD_DIR sapi/continuity"
+  
+
+
+  PHP_INSTALLED_SAPIS="$PHP_INSTALLED_SAPIS continuity"
+
   case "shared" in
   static) 
   
-  OVERALL_TARGET=php
   php_c_pre='$(LIBTOOL) --mode=compile $(CC)'
   php_c_meta='$(COMMON_FLAGS) $(CFLAGS_CLEAN) $(EXTRA_CFLAGS)'
   php_c_post=
@@ -9093,14 +9299,11 @@ echo "configure:9050: checking for Continuity include files" >&5
   shared_cxx_post=
   shared_lo=lo
 
-  php_build_target=program
-
   OVERALL_TARGET=libphp$PHP_MAJOR_VERSION.la
-  php_build_target=static
+  php_sapi_module=static
 ;;
   shared) 
   
-  OVERALL_TARGET=php
   php_c_pre='$(LIBTOOL) --mode=compile $(CC)'
   php_c_meta='$(COMMON_FLAGS) $(CFLAGS_CLEAN) $(EXTRA_CFLAGS)'
   php_c_post=
@@ -9122,10 +9325,8 @@ echo "configure:9050: checking for Continuity include files" >&5
   shared_cxx_post=
   shared_lo=lo
 
-  php_build_target=program
-
   OVERALL_TARGET=libphp$PHP_MAJOR_VERSION.la
-  php_build_target=shared
+  php_sapi_module=shared
   
   php_c_pre=$shared_c_pre
   php_c_meta=$shared_c_meta
@@ -9137,7 +9338,6 @@ echo "configure:9050: checking for Continuity include files" >&5
 ;;
   bundle) 
   
-  OVERALL_TARGET=php
   php_c_pre='$(LIBTOOL) --mode=compile $(CC)'
   php_c_meta='$(COMMON_FLAGS) $(CFLAGS_CLEAN) $(EXTRA_CFLAGS)'
   php_c_post=
@@ -9159,13 +9359,10 @@ echo "configure:9050: checking for Continuity include files" >&5
   shared_cxx_post=
   shared_lo=lo
 
-  php_build_target=program
-
   OVERALL_TARGET=libs/libphp$PHP_MAJOR_VERSION.bundle
-  php_build_target=static
+  php_sapi_module=static
 ;;
   program) 
-  OVERALL_TARGET=php
   php_c_pre='$(LIBTOOL) --mode=compile $(CC)'
   php_c_meta='$(COMMON_FLAGS) $(CFLAGS_CLEAN) $(EXTRA_CFLAGS)'
   php_c_post=
@@ -9186,12 +9383,12 @@ echo "configure:9050: checking for Continuity include files" >&5
   shared_cxx_meta='$(COMMON_FLAGS) $(CXXFLAGS_CLEAN) $(EXTRA_CXXFLAGS) '$pic_setting
   shared_cxx_post=
   shared_lo=lo
-
-  php_build_target=program
 ;;
   esac
     
   
+    install_sapi="install-sapi"
+    
   
   case sapi/continuity in
   "") ac_srcdir="$abs_srcdir/"; unset ac_bdir; ac_inc="-I. -I$abs_srcdir" ;;
@@ -9234,6 +9431,7 @@ EOF
   done
 
 
+  
 
   
   if test "$CAPI_INCLUDE" != "/usr/include"; then
@@ -9304,7 +9502,7 @@ ext_output=$PHP_EMBED
 
 
 echo $ac_n "checking for embedded SAPI library support""... $ac_c" 1>&6
-echo "configure:9308: checking for embedded SAPI library support" >&5
+echo "configure:9511: checking for embedded SAPI library support" >&5
 
 if test "$PHP_EMBED" != "no"; then
   case "$PHP_EMBED" in
@@ -9322,23 +9520,32 @@ if test "$PHP_EMBED" != "no"; then
   esac
   if test "$PHP_EMBED_TYPE" != "no"; then
     
-  if test "$PHP_SAPI" != "default"; then
-{ echo "configure: error: 
+  if test "$PHP_EMBED_TYPE" = "program"; then
+    PHP_BINARIES="$PHP_BINARIES embed"
+  elif test "$PHP_SAPI" != "none"; then
+    { echo "configure: error: 
 +--------------------------------------------------------------------+
 |                        *** ATTENTION ***                           |
 |                                                                    |
 | You've configured multiple SAPIs to be build. You can build only   |
-| one SAPI module and CLI binary at the same time.                   |
+| one SAPI module plus CGI, CLI and FPM binaries at the same time.   |
 +--------------------------------------------------------------------+
 " 1>&2; exit 1; }
+  else
+    PHP_SAPI=embed
   fi
 
-  PHP_SAPI=embed
   
+  
+    BUILD_DIR="$BUILD_DIR sapi/embed"
+  
+
+
+  PHP_INSTALLED_SAPIS="$PHP_INSTALLED_SAPIS embed"
+
   case "$PHP_EMBED_TYPE" in
   static) 
   
-  OVERALL_TARGET=php
   php_c_pre='$(LIBTOOL) --mode=compile $(CC)'
   php_c_meta='$(COMMON_FLAGS) $(CFLAGS_CLEAN) $(EXTRA_CFLAGS)'
   php_c_post=
@@ -9360,14 +9567,11 @@ if test "$PHP_EMBED" != "no"; then
   shared_cxx_post=
   shared_lo=lo
 
-  php_build_target=program
-
   OVERALL_TARGET=libphp$PHP_MAJOR_VERSION.la
-  php_build_target=static
+  php_sapi_module=static
 ;;
   shared) 
   
-  OVERALL_TARGET=php
   php_c_pre='$(LIBTOOL) --mode=compile $(CC)'
   php_c_meta='$(COMMON_FLAGS) $(CFLAGS_CLEAN) $(EXTRA_CFLAGS)'
   php_c_post=
@@ -9389,10 +9593,8 @@ if test "$PHP_EMBED" != "no"; then
   shared_cxx_post=
   shared_lo=lo
 
-  php_build_target=program
-
   OVERALL_TARGET=libphp$PHP_MAJOR_VERSION.la
-  php_build_target=shared
+  php_sapi_module=shared
   
   php_c_pre=$shared_c_pre
   php_c_meta=$shared_c_meta
@@ -9404,7 +9606,6 @@ if test "$PHP_EMBED" != "no"; then
 ;;
   bundle) 
   
-  OVERALL_TARGET=php
   php_c_pre='$(LIBTOOL) --mode=compile $(CC)'
   php_c_meta='$(COMMON_FLAGS) $(CFLAGS_CLEAN) $(EXTRA_CFLAGS)'
   php_c_post=
@@ -9426,13 +9627,10 @@ if test "$PHP_EMBED" != "no"; then
   shared_cxx_post=
   shared_lo=lo
 
-  php_build_target=program
-
   OVERALL_TARGET=libs/libphp$PHP_MAJOR_VERSION.bundle
-  php_build_target=static
+  php_sapi_module=static
 ;;
   program) 
-  OVERALL_TARGET=php
   php_c_pre='$(LIBTOOL) --mode=compile $(CC)'
   php_c_meta='$(COMMON_FLAGS) $(CFLAGS_CLEAN) $(EXTRA_CFLAGS)'
   php_c_post=
@@ -9453,12 +9651,12 @@ if test "$PHP_EMBED" != "no"; then
   shared_cxx_meta='$(COMMON_FLAGS) $(CXXFLAGS_CLEAN) $(EXTRA_CXXFLAGS) '$pic_setting
   shared_cxx_post=
   shared_lo=lo
-
-  php_build_target=program
 ;;
   esac
     
   
+    install_sapi="install-sapi"
+    
   
   case sapi/embed in
   "") ac_srcdir="$abs_srcdir/"; unset ac_bdir; ac_inc="-I. -I$abs_srcdir" ;;
@@ -9501,6 +9699,7 @@ EOF
   done
 
 
+  
 
     
   
@@ -9580,7 +9779,7 @@ ext_output=$PHP_FPM
 
 
 echo $ac_n "checking for FPM build""... $ac_c" 1>&6
-echo "configure:9584: checking for FPM build" >&5
+echo "configure:9788: checking for FPM build" >&5
 if test "$PHP_FPM" != "no"; then
   echo "$ac_t""$PHP_FPM" 1>&6
 
@@ -9588,12 +9787,12 @@ if test "$PHP_FPM" != "no"; then
   for ac_func in setenv clearenv setproctitle
 do
 echo $ac_n "checking for $ac_func""... $ac_c" 1>&6
-echo "configure:9592: checking for $ac_func" >&5
+echo "configure:9796: checking for $ac_func" >&5
 if eval "test \"`echo '$''{'ac_cv_func_$ac_func'+set}'`\" = set"; then
   echo $ac_n "(cached) $ac_c" 1>&6
 else
   cat > conftest.$ac_ext <<EOF
-#line 9597 "configure"
+#line 9801 "configure"
 #include "confdefs.h"
 /* System header to define __stub macros and hopefully few prototypes,
     which can conflict with char $ac_func(); below.  */
@@ -9616,7 +9815,7 @@ $ac_func();
 
 ; return 0; }
 EOF
-if { (eval echo configure:9620: \"$ac_link\") 1>&5; (eval $ac_link) 2>&5; } && test -s conftest${ac_exeext}; then
+if { (eval echo configure:9824: \"$ac_link\") 1>&5; (eval $ac_link) 2>&5; } && test -s conftest${ac_exeext}; then
   rm -rf conftest*
   eval "ac_cv_func_$ac_func=yes"
 else
@@ -9643,14 +9842,14 @@ done
 
   
 echo $ac_n "checking for library containing socket""... $ac_c" 1>&6
-echo "configure:9647: checking for library containing socket" >&5
+echo "configure:9851: checking for library containing socket" >&5
 if eval "test \"`echo '$''{'ac_cv_search_socket'+set}'`\" = set"; then
   echo $ac_n "(cached) $ac_c" 1>&6
 else
   ac_func_search_save_LIBS="$LIBS"
 ac_cv_search_socket="no"
 cat > conftest.$ac_ext <<EOF
-#line 9654 "configure"
+#line 9858 "configure"
 #include "confdefs.h"
 /* Override any gcc2 internal prototype to avoid an error.  */
 /* We use char because int might match the return type of a gcc2
@@ -9661,7 +9860,7 @@ int main() {
 socket()
 ; return 0; }
 EOF
-if { (eval echo configure:9665: \"$ac_link\") 1>&5; (eval $ac_link) 2>&5; } && test -s conftest${ac_exeext}; then
+if { (eval echo configure:9869: \"$ac_link\") 1>&5; (eval $ac_link) 2>&5; } && test -s conftest${ac_exeext}; then
   rm -rf conftest*
   ac_cv_search_socket="none required"
 else
@@ -9672,7 +9871,7 @@ rm -f conftest*
 test "$ac_cv_search_socket" = "no" && for i in socket; do
 LIBS="-l$i  $ac_func_search_save_LIBS"
 cat > conftest.$ac_ext <<EOF
-#line 9676 "configure"
+#line 9880 "configure"
 #include "confdefs.h"
 /* Override any gcc2 internal prototype to avoid an error.  */
 /* We use char because int might match the return type of a gcc2
@@ -9683,7 +9882,7 @@ int main() {
 socket()
 ; return 0; }
 EOF
-if { (eval echo configure:9687: \"$ac_link\") 1>&5; (eval $ac_link) 2>&5; } && test -s conftest${ac_exeext}; then
+if { (eval echo configure:9891: \"$ac_link\") 1>&5; (eval $ac_link) 2>&5; } && test -s conftest${ac_exeext}; then
   rm -rf conftest*
   ac_cv_search_socket="-l$i"
 break
@@ -9705,14 +9904,14 @@ else :
 fi
   
 echo $ac_n "checking for library containing inet_addr""... $ac_c" 1>&6
-echo "configure:9709: checking for library containing inet_addr" >&5
+echo "configure:9913: checking for library containing inet_addr" >&5
 if eval "test \"`echo '$''{'ac_cv_search_inet_addr'+set}'`\" = set"; then
   echo $ac_n "(cached) $ac_c" 1>&6
 else
   ac_func_search_save_LIBS="$LIBS"
 ac_cv_search_inet_addr="no"
 cat > conftest.$ac_ext <<EOF
-#line 9716 "configure"
+#line 9920 "configure"
 #include "confdefs.h"
 /* Override any gcc2 internal prototype to avoid an error.  */
 /* We use char because int might match the return type of a gcc2
@@ -9723,7 +9922,7 @@ int main() {
 inet_addr()
 ; return 0; }
 EOF
-if { (eval echo configure:9727: \"$ac_link\") 1>&5; (eval $ac_link) 2>&5; } && test -s conftest${ac_exeext}; then
+if { (eval echo configure:9931: \"$ac_link\") 1>&5; (eval $ac_link) 2>&5; } && test -s conftest${ac_exeext}; then
   rm -rf conftest*
   ac_cv_search_inet_addr="none required"
 else
@@ -9734,7 +9933,7 @@ rm -f conftest*
 test "$ac_cv_search_inet_addr" = "no" && for i in nsl; do
 LIBS="-l$i  $ac_func_search_save_LIBS"
 cat > conftest.$ac_ext <<EOF
-#line 9738 "configure"
+#line 9942 "configure"
 #include "confdefs.h"
 /* Override any gcc2 internal prototype to avoid an error.  */
 /* We use char because int might match the return type of a gcc2
@@ -9745,7 +9944,7 @@ int main() {
 inet_addr()
 ; return 0; }
 EOF
-if { (eval echo configure:9749: \"$ac_link\") 1>&5; (eval $ac_link) 2>&5; } && test -s conftest${ac_exeext}; then
+if { (eval echo configure:9953: \"$ac_link\") 1>&5; (eval $ac_link) 2>&5; } && test -s conftest${ac_exeext}; then
   rm -rf conftest*
   ac_cv_search_inet_addr="-l$i"
 break
@@ -9770,17 +9969,17 @@ fi
 do
 ac_safe=`echo "$ac_hdr" | sed 'y%./+-%__p_%'`
 echo $ac_n "checking for $ac_hdr""... $ac_c" 1>&6
-echo "configure:9774: checking for $ac_hdr" >&5
+echo "configure:9978: checking for $ac_hdr" >&5
 if eval "test \"`echo '$''{'ac_cv_header_$ac_safe'+set}'`\" = set"; then
   echo $ac_n "(cached) $ac_c" 1>&6
 else
   cat > conftest.$ac_ext <<EOF
-#line 9779 "configure"
+#line 9983 "configure"
 #include "confdefs.h"
 #include <$ac_hdr>
 EOF
 ac_try="$ac_cpp conftest.$ac_ext >/dev/null 2>conftest.out"
-{ (eval echo configure:9784: \"$ac_try\") 1>&5; (eval $ac_try) 2>&5; }
+{ (eval echo configure:9988: \"$ac_try\") 1>&5; (eval $ac_try) 2>&5; }
 ac_err=`grep -v '^ *+' conftest.out | grep -v "^conftest.${ac_ext}\$"`
 if test -z "$ac_err"; then
   rm -rf conftest*
@@ -9810,17 +10009,17 @@ done
 do
 ac_safe=`echo "$ac_hdr" | sed 'y%./+-%__p_%'`
 echo $ac_n "checking for $ac_hdr""... $ac_c" 1>&6
-echo "configure:9814: checking for $ac_hdr" >&5
+echo "configure:10018: checking for $ac_hdr" >&5
 if eval "test \"`echo '$''{'ac_cv_header_$ac_safe'+set}'`\" = set"; then
   echo $ac_n "(cached) $ac_c" 1>&6
 else
   cat > conftest.$ac_ext <<EOF
-#line 9819 "configure"
+#line 10023 "configure"
 #include "confdefs.h"
 #include <$ac_hdr>
 EOF
 ac_try="$ac_cpp conftest.$ac_ext >/dev/null 2>conftest.out"
-{ (eval echo configure:9824: \"$ac_try\") 1>&5; (eval $ac_try) 2>&5; }
+{ (eval echo configure:10028: \"$ac_try\") 1>&5; (eval $ac_try) 2>&5; }
 ac_err=`grep -v '^ *+' conftest.out | grep -v "^conftest.${ac_ext}\$"`
 if test -z "$ac_err"; then
   rm -rf conftest*
@@ -9850,17 +10049,17 @@ done
 do
 ac_safe=`echo "$ac_hdr" | sed 'y%./+-%__p_%'`
 echo $ac_n "checking for $ac_hdr""... $ac_c" 1>&6
-echo "configure:9854: checking for $ac_hdr" >&5
+echo "configure:10058: checking for $ac_hdr" >&5
 if eval "test \"`echo '$''{'ac_cv_header_$ac_safe'+set}'`\" = set"; then
   echo $ac_n "(cached) $ac_c" 1>&6
 else
   cat > conftest.$ac_ext <<EOF
-#line 9859 "configure"
+#line 10063 "configure"
 #include "confdefs.h"
 #include <$ac_hdr>
 EOF
 ac_try="$ac_cpp conftest.$ac_ext >/dev/null 2>conftest.out"
-{ (eval echo configure:9864: \"$ac_try\") 1>&5; (eval $ac_try) 2>&5; }
+{ (eval echo configure:10068: \"$ac_try\") 1>&5; (eval $ac_try) 2>&5; }
 ac_err=`grep -v '^ *+' conftest.out | grep -v "^conftest.${ac_ext}\$"`
 if test -z "$ac_err"; then
   rm -rf conftest*
@@ -9890,17 +10089,17 @@ done
 do
 ac_safe=`echo "$ac_hdr" | sed 'y%./+-%__p_%'`
 echo $ac_n "checking for $ac_hdr""... $ac_c" 1>&6
-echo "configure:9894: checking for $ac_hdr" >&5
+echo "configure:10098: checking for $ac_hdr" >&5
 if eval "test \"`echo '$''{'ac_cv_header_$ac_safe'+set}'`\" = set"; then
   echo $ac_n "(cached) $ac_c" 1>&6
 else
   cat > conftest.$ac_ext <<EOF
-#line 9899 "configure"
+#line 10103 "configure"
 #include "confdefs.h"
 #include <$ac_hdr>
 EOF
 ac_try="$ac_cpp conftest.$ac_ext >/dev/null 2>conftest.out"
-{ (eval echo configure:9904: \"$ac_try\") 1>&5; (eval $ac_try) 2>&5; }
+{ (eval echo configure:10108: \"$ac_try\") 1>&5; (eval $ac_try) 2>&5; }
 ac_err=`grep -v '^ *+' conftest.out | grep -v "^conftest.${ac_ext}\$"`
 if test -z "$ac_err"; then
   rm -rf conftest*
@@ -9929,17 +10128,17 @@ done
 
   
   echo $ac_n "checking for prctl""... $ac_c" 1>&6
-echo "configure:9933: checking for prctl" >&5
+echo "configure:10137: checking for prctl" >&5
 
   cat > conftest.$ac_ext <<EOF
-#line 9936 "configure"
+#line 10140 "configure"
 #include "confdefs.h"
  #include <sys/prctl.h> 
 int main() {
 prctl(0, 0, 0, 0, 0);
 ; return 0; }
 EOF
-if { (eval echo configure:9943: \"$ac_compile\") 1>&5; (eval $ac_compile) 2>&5; }; then
+if { (eval echo configure:10147: \"$ac_compile\") 1>&5; (eval $ac_compile) 2>&5; }; then
   rm -rf conftest*
   
     cat >> confdefs.h <<\EOF
@@ -9962,17 +10161,17 @@ rm -f conftest*
   have_clock_gettime=no
 
   echo $ac_n "checking for clock_gettime""... $ac_c" 1>&6
-echo "configure:9966: checking for clock_gettime" >&5
+echo "configure:10170: checking for clock_gettime" >&5
 
   cat > conftest.$ac_ext <<EOF
-#line 9969 "configure"
+#line 10173 "configure"
 #include "confdefs.h"
  #include <time.h> 
 int main() {
 struct timespec ts; clock_gettime(CLOCK_MONOTONIC, &ts);
 ; return 0; }
 EOF
-if { (eval echo configure:9976: \"$ac_link\") 1>&5; (eval $ac_link) 2>&5; } && test -s conftest${ac_exeext}; then
+if { (eval echo configure:10180: \"$ac_link\") 1>&5; (eval $ac_link) 2>&5; } && test -s conftest${ac_exeext}; then
   rm -rf conftest*
   
     have_clock_gettime=yes
@@ -9990,20 +10189,20 @@ rm -f conftest*
 
   if test "$have_clock_gettime" = "no"; then
     echo $ac_n "checking for clock_gettime in -lrt""... $ac_c" 1>&6
-echo "configure:9994: checking for clock_gettime in -lrt" >&5
+echo "configure:10198: checking for clock_gettime in -lrt" >&5
 
     SAVED_LIBS="$LIBS"
     LIBS="$LIBS -lrt"
 
     cat > conftest.$ac_ext <<EOF
-#line 10000 "configure"
+#line 10204 "configure"
 #include "confdefs.h"
  #include <time.h> 
 int main() {
 struct timespec ts; clock_gettime(CLOCK_MONOTONIC, &ts);
 ; return 0; }
 EOF
-if { (eval echo configure:10007: \"$ac_link\") 1>&5; (eval $ac_link) 2>&5; } && test -s conftest${ac_exeext}; then
+if { (eval echo configure:10211: \"$ac_link\") 1>&5; (eval $ac_link) 2>&5; } && test -s conftest${ac_exeext}; then
   rm -rf conftest*
   
       have_clock_gettime=yes
@@ -10032,13 +10231,13 @@ EOF
 
   if test "$have_clock_gettime" = "no"; then
     echo $ac_n "checking for clock_get_time""... $ac_c" 1>&6
-echo "configure:10036: checking for clock_get_time" >&5
+echo "configure:10240: checking for clock_get_time" >&5
 
     if test "$cross_compiling" = yes; then
     { echo "configure: error: can not run test program while cross compiling" 1>&2; exit 1; }
 else
   cat > conftest.$ac_ext <<EOF
-#line 10042 "configure"
+#line 10246 "configure"
 #include "confdefs.h"
  #include <mach/mach.h>
       #include <mach/clock.h>
@@ -10062,7 +10261,7 @@ else
       }
     
 EOF
-if { (eval echo configure:10066: \"$ac_link\") 1>&5; (eval $ac_link) 2>&5; } && test -s conftest${ac_exeext} && (./conftest; exit) 2>/dev/null
+if { (eval echo configure:10270: \"$ac_link\") 1>&5; (eval $ac_link) 2>&5; } && test -s conftest${ac_exeext} && (./conftest; exit) 2>/dev/null
 then
   
       have_clock_get_time=yes
@@ -10093,10 +10292,10 @@ EOF
   have_broken_ptrace=no
 
   echo $ac_n "checking for ptrace""... $ac_c" 1>&6
-echo "configure:10097: checking for ptrace" >&5
+echo "configure:10301: checking for ptrace" >&5
 
   cat > conftest.$ac_ext <<EOF
-#line 10100 "configure"
+#line 10304 "configure"
 #include "confdefs.h"
 
     #include <sys/types.h>
@@ -10105,7 +10304,7 @@ int main() {
 ptrace(0, 0, (void *) 0, 0);
 ; return 0; }
 EOF
-if { (eval echo configure:10109: \"$ac_compile\") 1>&5; (eval $ac_compile) 2>&5; }; then
+if { (eval echo configure:10313: \"$ac_compile\") 1>&5; (eval $ac_compile) 2>&5; }; then
   rm -rf conftest*
   
     have_ptrace=yes
@@ -10123,13 +10322,13 @@ rm -f conftest*
 
   if test "$have_ptrace" = "yes"; then
     echo $ac_n "checking whether ptrace works""... $ac_c" 1>&6
-echo "configure:10127: checking whether ptrace works" >&5
+echo "configure:10331: checking whether ptrace works" >&5
 
     if test "$cross_compiling" = yes; then
     { echo "configure: error: can not run test program while cross compiling" 1>&2; exit 1; }
 else
   cat > conftest.$ac_ext <<EOF
-#line 10133 "configure"
+#line 10337 "configure"
 #include "confdefs.h"
 
       #include <unistd.h>
@@ -10200,7 +10399,7 @@ else
       }
     
 EOF
-if { (eval echo configure:10204: \"$ac_link\") 1>&5; (eval $ac_link) 2>&5; } && test -s conftest${ac_exeext} && (./conftest; exit) 2>/dev/null
+if { (eval echo configure:10408: \"$ac_link\") 1>&5; (eval $ac_link) 2>&5; } && test -s conftest${ac_exeext} && (./conftest; exit) 2>/dev/null
 then
   
       echo "$ac_t""yes" 1>&6
@@ -10231,10 +10430,10 @@ EOF
 
   if test "$have_broken_ptrace" = "yes"; then
     echo $ac_n "checking for mach_vm_read""... $ac_c" 1>&6
-echo "configure:10235: checking for mach_vm_read" >&5
+echo "configure:10439: checking for mach_vm_read" >&5
 
     cat > conftest.$ac_ext <<EOF
-#line 10238 "configure"
+#line 10442 "configure"
 #include "confdefs.h"
  #include <mach/mach.h>
       #include <mach/mach_vm.h>
@@ -10245,7 +10444,7 @@ int main() {
     
 ; return 0; }
 EOF
-if { (eval echo configure:10249: \"$ac_compile\") 1>&5; (eval $ac_compile) 2>&5; }; then
+if { (eval echo configure:10453: \"$ac_compile\") 1>&5; (eval $ac_compile) 2>&5; }; then
   rm -rf conftest*
   
       have_mach_vm_read=yes
@@ -10281,13 +10480,13 @@ EOF
 
   if test -n "$proc_mem_file" ; then
     echo $ac_n "checking for proc mem file""... $ac_c" 1>&6
-echo "configure:10285: checking for proc mem file" >&5
+echo "configure:10489: checking for proc mem file" >&5
   
     if test "$cross_compiling" = yes; then
     { echo "configure: error: can not run test program while cross compiling" 1>&2; exit 1; }
 else
   cat > conftest.$ac_ext <<EOF
-#line 10291 "configure"
+#line 10495 "configure"
 #include "confdefs.h"
 
       #define _GNU_SOURCE
@@ -10317,7 +10516,7 @@ else
       }
     
 EOF
-if { (eval echo configure:10321: \"$ac_link\") 1>&5; (eval $ac_link) 2>&5; } && test -s conftest${ac_exeext} && (./conftest; exit) 2>/dev/null
+if { (eval echo configure:10525: \"$ac_link\") 1>&5; (eval $ac_link) 2>&5; } && test -s conftest${ac_exeext} && (./conftest; exit) 2>/dev/null
 then
   
       echo "$ac_t""$proc_mem_file" 1>&6
@@ -10361,9 +10560,9 @@ EOF
 
   
   echo $ac_n "checking if gcc supports __sync_bool_compare_and_swap""... $ac_c" 1>&6
-echo "configure:10365: checking if gcc supports __sync_bool_compare_and_swap" >&5
+echo "configure:10569: checking if gcc supports __sync_bool_compare_and_swap" >&5
   cat > conftest.$ac_ext <<EOF
-#line 10367 "configure"
+#line 10571 "configure"
 #include "confdefs.h"
 
 int main() {
@@ -10374,7 +10573,7 @@ int main() {
   
 ; return 0; }
 EOF
-if { (eval echo configure:10378: \"$ac_link\") 1>&5; (eval $ac_link) 2>&5; } && test -s conftest${ac_exeext}; then
+if { (eval echo configure:10582: \"$ac_link\") 1>&5; (eval $ac_link) 2>&5; } && test -s conftest${ac_exeext}; then
   rm -rf conftest*
   
     echo "$ac_t""yes" 1>&6
@@ -10397,17 +10596,17 @@ rm -f conftest*
   have_lq=no
 
   echo $ac_n "checking for TCP_INFO""... $ac_c" 1>&6
-echo "configure:10401: checking for TCP_INFO" >&5
+echo "configure:10605: checking for TCP_INFO" >&5
 
   cat > conftest.$ac_ext <<EOF
-#line 10404 "configure"
+#line 10608 "configure"
 #include "confdefs.h"
  #include <netinet/tcp.h> 
 int main() {
 struct tcp_info ti; int x = TCP_INFO;
 ; return 0; }
 EOF
-if { (eval echo configure:10411: \"$ac_compile\") 1>&5; (eval $ac_compile) 2>&5; }; then
+if { (eval echo configure:10615: \"$ac_compile\") 1>&5; (eval $ac_compile) 2>&5; }; then
   rm -rf conftest*
   
     have_lq=tcp_info
@@ -10432,17 +10631,17 @@ EOF
 
   if test "$have_lq" = "no" ; then
     echo $ac_n "checking for SO_LISTENQLEN""... $ac_c" 1>&6
-echo "configure:10436: checking for SO_LISTENQLEN" >&5
+echo "configure:10640: checking for SO_LISTENQLEN" >&5
 
     cat > conftest.$ac_ext <<EOF
-#line 10439 "configure"
+#line 10643 "configure"
 #include "confdefs.h"
  #include <sys/socket.h> 
 int main() {
 int x = SO_LISTENQLIMIT; int y = SO_LISTENQLEN;
 ; return 0; }
 EOF
-if { (eval echo configure:10446: \"$ac_compile\") 1>&5; (eval $ac_compile) 2>&5; }; then
+if { (eval echo configure:10650: \"$ac_compile\") 1>&5; (eval $ac_compile) 2>&5; }; then
   rm -rf conftest*
   
       have_lq=so_listenq
@@ -10468,17 +10667,17 @@ EOF
 
 	
 	echo $ac_n "checking for sysconf""... $ac_c" 1>&6
-echo "configure:10472: checking for sysconf" >&5
+echo "configure:10676: checking for sysconf" >&5
 
 	cat > conftest.$ac_ext <<EOF
-#line 10475 "configure"
+#line 10679 "configure"
 #include "confdefs.h"
  #include <unistd.h> 
 int main() {
 sysconf(_SC_CLK_TCK);
 ; return 0; }
 EOF
-if { (eval echo configure:10482: \"$ac_compile\") 1>&5; (eval $ac_compile) 2>&5; }; then
+if { (eval echo configure:10686: \"$ac_compile\") 1>&5; (eval $ac_compile) 2>&5; }; then
   rm -rf conftest*
   
 		cat >> confdefs.h <<\EOF
@@ -10499,17 +10698,17 @@ rm -f conftest*
 
 	
 	echo $ac_n "checking for times""... $ac_c" 1>&6
-echo "configure:10503: checking for times" >&5
+echo "configure:10707: checking for times" >&5
 
 	cat > conftest.$ac_ext <<EOF
-#line 10506 "configure"
+#line 10710 "configure"
 #include "confdefs.h"
  #include <sys/times.h> 
 int main() {
 struct tms t; times(&t);
 ; return 0; }
 EOF
-if { (eval echo configure:10513: \"$ac_compile\") 1>&5; (eval $ac_compile) 2>&5; }; then
+if { (eval echo configure:10717: \"$ac_compile\") 1>&5; (eval $ac_compile) 2>&5; }; then
   rm -rf conftest*
   
 		cat >> confdefs.h <<\EOF
@@ -10530,10 +10729,10 @@ rm -f conftest*
 
 	
 	echo $ac_n "checking for kqueue""... $ac_c" 1>&6
-echo "configure:10534: checking for kqueue" >&5
+echo "configure:10738: checking for kqueue" >&5
 
 	cat > conftest.$ac_ext <<EOF
-#line 10537 "configure"
+#line 10741 "configure"
 #include "confdefs.h"
  
 		#include <sys/types.h>
@@ -10550,7 +10749,7 @@ int main() {
 	
 ; return 0; }
 EOF
-if { (eval echo configure:10554: \"$ac_compile\") 1>&5; (eval $ac_compile) 2>&5; }; then
+if { (eval echo configure:10758: \"$ac_compile\") 1>&5; (eval $ac_compile) 2>&5; }; then
   rm -rf conftest*
   
 		cat >> confdefs.h <<\EOF
@@ -10571,10 +10770,10 @@ rm -f conftest*
 
 	
 	echo $ac_n "checking for port framework""... $ac_c" 1>&6
-echo "configure:10575: checking for port framework" >&5
+echo "configure:10779: checking for port framework" >&5
 
 	cat > conftest.$ac_ext <<EOF
-#line 10578 "configure"
+#line 10782 "configure"
 #include "confdefs.h"
  
 		#include <port.h>
@@ -10590,7 +10789,7 @@ int main() {
 	
 ; return 0; }
 EOF
-if { (eval echo configure:10594: \"$ac_compile\") 1>&5; (eval $ac_compile) 2>&5; }; then
+if { (eval echo configure:10798: \"$ac_compile\") 1>&5; (eval $ac_compile) 2>&5; }; then
   rm -rf conftest*
   
 		cat >> confdefs.h <<\EOF
@@ -10611,10 +10810,10 @@ rm -f conftest*
 
 	
 	echo $ac_n "checking for /dev/poll""... $ac_c" 1>&6
-echo "configure:10615: checking for /dev/poll" >&5
+echo "configure:10819: checking for /dev/poll" >&5
 
 	cat > conftest.$ac_ext <<EOF
-#line 10618 "configure"
+#line 10822 "configure"
 #include "confdefs.h"
  
 		#include <stdio.h>
@@ -10632,7 +10831,7 @@ int main() {
 	
 ; return 0; }
 EOF
-if { (eval echo configure:10636: \"$ac_compile\") 1>&5; (eval $ac_compile) 2>&5; }; then
+if { (eval echo configure:10840: \"$ac_compile\") 1>&5; (eval $ac_compile) 2>&5; }; then
   rm -rf conftest*
   
 		cat >> confdefs.h <<\EOF
@@ -10653,10 +10852,10 @@ rm -f conftest*
 
 	
 	echo $ac_n "checking for epoll""... $ac_c" 1>&6
-echo "configure:10657: checking for epoll" >&5
+echo "configure:10861: checking for epoll" >&5
 
 	cat > conftest.$ac_ext <<EOF
-#line 10660 "configure"
+#line 10864 "configure"
 #include "confdefs.h"
  
 		#include <sys/epoll.h>
@@ -10685,7 +10884,7 @@ int main() {
 	
 ; return 0; }
 EOF
-if { (eval echo configure:10689: \"$ac_compile\") 1>&5; (eval $ac_compile) 2>&5; }; then
+if { (eval echo configure:10893: \"$ac_compile\") 1>&5; (eval $ac_compile) 2>&5; }; then
   rm -rf conftest*
   
 		cat >> confdefs.h <<\EOF
@@ -10706,10 +10905,10 @@ rm -f conftest*
 
 	
 	echo $ac_n "checking for poll""... $ac_c" 1>&6
-echo "configure:10710: checking for poll" >&5
+echo "configure:10914: checking for poll" >&5
 
 	cat > conftest.$ac_ext <<EOF
-#line 10713 "configure"
+#line 10917 "configure"
 #include "confdefs.h"
  
 		#include <poll.h>
@@ -10728,7 +10927,7 @@ int main() {
 	
 ; return 0; }
 EOF
-if { (eval echo configure:10732: \"$ac_compile\") 1>&5; (eval $ac_compile) 2>&5; }; then
+if { (eval echo configure:10936: \"$ac_compile\") 1>&5; (eval $ac_compile) 2>&5; }; then
   rm -rf conftest*
   
 		cat >> confdefs.h <<\EOF
@@ -10749,10 +10948,10 @@ rm -f conftest*
 
 	
 	echo $ac_n "checking for select""... $ac_c" 1>&6
-echo "configure:10753: checking for select" >&5
+echo "configure:10957: checking for select" >&5
 
 	cat > conftest.$ac_ext <<EOF
-#line 10756 "configure"
+#line 10960 "configure"
 #include "confdefs.h"
  
 		/* According to POSIX.1-2001 */
@@ -10776,7 +10975,7 @@ int main() {
 	
 ; return 0; }
 EOF
-if { (eval echo configure:10780: \"$ac_compile\") 1>&5; (eval $ac_compile) 2>&5; }; then
+if { (eval echo configure:10984: \"$ac_compile\") 1>&5; (eval $ac_compile) 2>&5; }; then
   rm -rf conftest*
   
 		cat >> confdefs.h <<\EOF
@@ -10896,27 +11095,33 @@ EOF
 
 
   
+  
+    BUILD_DIR="$BUILD_DIR sapi/fpm/fpm"
+  
+
+  
   PHP_OUTPUT_FILES="$PHP_OUTPUT_FILES sapi/fpm/php-fpm.conf sapi/fpm/init.d.php-fpm sapi/fpm/php-fpm.service sapi/fpm/php-fpm.8 sapi/fpm/status.html"
 
   
   src=$abs_srcdir/sapi/fpm/Makefile.frag
-  ac_srcdir=$abs_srcdir/sapi/fpm
-  ac_builddir=sapi/fpm
+  ac_srcdir=$ext_srcdir
+  ac_builddir=$ext_builddir
   test -f "$src" && $SED -e "s#\$(srcdir)#$ac_srcdir#g" -e "s#\$(builddir)#$ac_builddir#g" $src  >> Makefile.fragments
 
 
   SAPI_FPM_PATH=sapi/fpm/php-fpm
   
-  PHP_VAR_SUBST="$PHP_VAR_SUBST SAPI_FPM_PATH"
-
-  
   if test "$fpm_trace_type" && test -f "$abs_srcdir/sapi/fpm/fpm/fpm_trace_$fpm_trace_type.c"; then
     PHP_FPM_TRACE_FILES="fpm/fpm_trace.c fpm/fpm_trace_$fpm_trace_type.c"
   fi
   
   PHP_FPM_CFLAGS="-I$abs_srcdir/sapi/fpm"
+
+  FPM_EXTRA_LIBS="$LIBEVENT_LIBS"
+  
+  PHP_VAR_SUBST="$PHP_VAR_SUBST FPM_EXTRA_LIBS"
+
  
-  INSTALL_IT=":"
   PHP_FPM_FILES="fpm/fastcgi.c \
     fpm/fpm.c \
     fpm/fpm_children.c \
@@ -10949,23 +11154,32 @@ EOF
   "
 
   
-  if test "$PHP_SAPI" != "default"; then
-{ echo "configure: error: 
+  if test "program" = "program"; then
+    PHP_BINARIES="$PHP_BINARIES fpm"
+  elif test "$PHP_SAPI" != "none"; then
+    { echo "configure: error: 
 +--------------------------------------------------------------------+
 |                        *** ATTENTION ***                           |
 |                                                                    |
 | You've configured multiple SAPIs to be build. You can build only   |
-| one SAPI module and CLI binary at the same time.                   |
+| one SAPI module plus CGI, CLI and FPM binaries at the same time.   |
 +--------------------------------------------------------------------+
 " 1>&2; exit 1; }
+  else
+    PHP_SAPI=fpm
   fi
 
-  PHP_SAPI=fpm
   
+  
+    BUILD_DIR="$BUILD_DIR sapi/fpm"
+  
+
+
+  PHP_INSTALLED_SAPIS="$PHP_INSTALLED_SAPIS fpm"
+
   case "program" in
   static) 
   
-  OVERALL_TARGET=php
   php_c_pre='$(LIBTOOL) --mode=compile $(CC)'
   php_c_meta='$(COMMON_FLAGS) $(CFLAGS_CLEAN) $(EXTRA_CFLAGS)'
   php_c_post=
@@ -10987,14 +11201,11 @@ EOF
   shared_cxx_post=
   shared_lo=lo
 
-  php_build_target=program
-
   OVERALL_TARGET=libphp$PHP_MAJOR_VERSION.la
-  php_build_target=static
+  php_sapi_module=static
 ;;
   shared) 
   
-  OVERALL_TARGET=php
   php_c_pre='$(LIBTOOL) --mode=compile $(CC)'
   php_c_meta='$(COMMON_FLAGS) $(CFLAGS_CLEAN) $(EXTRA_CFLAGS)'
   php_c_post=
@@ -11016,10 +11227,8 @@ EOF
   shared_cxx_post=
   shared_lo=lo
 
-  php_build_target=program
-
   OVERALL_TARGET=libphp$PHP_MAJOR_VERSION.la
-  php_build_target=shared
+  php_sapi_module=shared
   
   php_c_pre=$shared_c_pre
   php_c_meta=$shared_c_meta
@@ -11031,7 +11240,6 @@ EOF
 ;;
   bundle) 
   
-  OVERALL_TARGET=php
   php_c_pre='$(LIBTOOL) --mode=compile $(CC)'
   php_c_meta='$(COMMON_FLAGS) $(CFLAGS_CLEAN) $(EXTRA_CFLAGS)'
   php_c_post=
@@ -11053,13 +11261,10 @@ EOF
   shared_cxx_post=
   shared_lo=lo
 
-  php_build_target=program
-
   OVERALL_TARGET=libs/libphp$PHP_MAJOR_VERSION.bundle
-  php_build_target=static
+  php_sapi_module=static
 ;;
   program) 
-  OVERALL_TARGET='$(SAPI_FPM_PATH)'
   php_c_pre='$(LIBTOOL) --mode=compile $(CC)'
   php_c_meta='$(COMMON_FLAGS) $(CFLAGS_CLEAN) $(EXTRA_CFLAGS)'
   php_c_post=
@@ -11080,13 +11285,16 @@ EOF
   shared_cxx_meta='$(COMMON_FLAGS) $(CXXFLAGS_CLEAN) $(EXTRA_CXXFLAGS) '$pic_setting
   shared_cxx_post=
   shared_lo=lo
-
-  php_build_target=program
 ;;
   esac
     
   
-  
+    install_binaries="install-binaries"
+    install_binary_targets="$install_binary_targets install-fpm"
+    
+  PHP_VAR_SUBST="$PHP_VAR_SUBST PHP_FPM_OBJS"
+
+    
   case sapi/fpm in
   "") ac_srcdir="$abs_srcdir/"; unset ac_bdir; ac_inc="-I. -I$abs_srcdir" ;;
   /*) ac_srcdir=`echo "sapi/fpm"|cut -c 2-`"/"; ac_bdir=$ac_srcdir; ac_inc="-I$ac_bdir -I$abs_srcdir/$ac_bdir" ;;
@@ -11112,7 +11320,7 @@ EOF
       ac_obj=$1
       IFS=$old_IFS
       
-      PHP_SAPI_OBJS="$PHP_SAPI_OBJS $ac_bdir$ac_obj.lo"
+      PHP_FPM_OBJS="$PHP_FPM_OBJS $ac_bdir$ac_obj.lo"
 
       case $ac_src in
         *.c) ac_comp="$b_c_pre $PHP_FPM_CFLAGS $ac_inc $b_c_meta -c $ac_srcdir$ac_src -o $ac_bdir$ac_obj.$b_lo $b_c_post" ;;
@@ -11127,24 +11335,28 @@ $ac_bdir$ac_obj.lo: $ac_srcdir$ac_src
 EOF
   done
 
-
+  
 
 
   case $host_alias in
       *aix*)
-        BUILD_FPM="echo '\#! .' > php.sym && echo >>php.sym && nm -BCpg \`echo \$(PHP_GLOBAL_OBJS) \$(PHP_SAPI_OBJS) | sed 's/\([A-Za-z0-9_]*\)\.lo/\1.o/g'\` | \$(AWK) '{ if (((\$\$2 == \"T\") || (\$\$2 == \"D\") || (\$\$2 == \"B\")) && (substr(\$\$3,1,1) != \".\")) { print \$\$3 } }' | sort -u >> php.sym && \$(LIBTOOL) --mode=link \$(CC) -export-dynamic \$(CFLAGS_CLEAN) \$(EXTRA_CFLAGS) \$(EXTRA_LDFLAGS_PROGRAM) \$(LDFLAGS) -Wl,-brtl -Wl,-bE:php.sym \$(PHP_RPATHS) \$(PHP_GLOBAL_OBJS) \$(PHP_SAPI_OBJS) \$(EXTRA_LIBS) \$(SAPI_EXTRA_LIBS) \$(ZEND_EXTRA_LIBS) -o \$(SAPI_FPM_PATH)"
+        BUILD_FPM="echo '\#! .' > php.sym && echo >>php.sym && nm -BCpg \`echo \$(PHP_GLOBAL_OBJS) \$(PHP_BINARY_OBJS) \$(PHP_FPM_OBJS) | sed 's/\([A-Za-z0-9_]*\)\.lo/\1.o/g'\` | \$(AWK) '{ if (((\$\$2 == \"T\") || (\$\$2 == \"D\") || (\$\$2 == \"B\")) && (substr(\$\$3,1,1) != \".\")) { print \$\$3 } }' | sort -u >> php.sym && \$(LIBTOOL) --mode=link \$(CC) -export-dynamic \$(CFLAGS_CLEAN) \$(EXTRA_CFLAGS) \$(EXTRA_LDFLAGS_PROGRAM) \$(LDFLAGS) -Wl,-brtl -Wl,-bE:php.sym \$(PHP_RPATHS) \$(PHP_GLOBAL_OBJS) \$(PHP_FPM_OBJS) \$(EXTRA_LIBS) \$(FPM_EXTRA_LIBS) \$(ZEND_EXTRA_LIBS) -o \$(SAPI_FPM_PATH)"
         ;;
       *darwin*)
-        BUILD_FPM="\$(CC) \$(CFLAGS_CLEAN) \$(EXTRA_CFLAGS) \$(EXTRA_LDFLAGS_PROGRAM) \$(LDFLAGS) \$(NATIVE_RPATHS) \$(PHP_GLOBAL_OBJS:.lo=.o) \$(PHP_SAPI_OBJS:.lo=.o) \$(PHP_FRAMEWORKS) \$(EXTRA_LIBS) \$(SAPI_EXTRA_LIBS) \$(ZEND_EXTRA_LIBS) -o \$(SAPI_FPM_PATH)"
+        BUILD_FPM="\$(CC) \$(CFLAGS_CLEAN) \$(EXTRA_CFLAGS) \$(EXTRA_LDFLAGS_PROGRAM) \$(LDFLAGS) \$(NATIVE_RPATHS) \$(PHP_GLOBAL_OBJS:.lo=.o) \$(PHP_BINARY_OBJS:.lo=.o) \$(PHP_FPM_OBJS:.lo=.o) \$(PHP_FRAMEWORKS) \$(EXTRA_LIBS) \$(FPM_EXTRA_LIBS) \$(ZEND_EXTRA_LIBS) -o \$(SAPI_FPM_PATH)"
       ;;
       *)
-        BUILD_FPM="\$(LIBTOOL) --mode=link \$(CC) -export-dynamic \$(CFLAGS_CLEAN) \$(EXTRA_CFLAGS) \$(EXTRA_LDFLAGS_PROGRAM) \$(LDFLAGS) \$(PHP_RPATHS) \$(PHP_GLOBAL_OBJS) \$(PHP_SAPI_OBJS) \$(EXTRA_LIBS) \$(SAPI_EXTRA_LIBS) \$(ZEND_EXTRA_LIBS) -o \$(SAPI_FPM_PATH)"
+        BUILD_FPM="\$(LIBTOOL) --mode=link \$(CC) -export-dynamic \$(CFLAGS_CLEAN) \$(EXTRA_CFLAGS) \$(EXTRA_LDFLAGS_PROGRAM) \$(LDFLAGS) \$(PHP_RPATHS) \$(PHP_GLOBAL_OBJS) \$(PHP_BINARY_OBJS) \$(PHP_FPM_OBJS) \$(EXTRA_LIBS) \$(FPM_EXTRA_LIBS) \$(ZEND_EXTRA_LIBS) -o \$(SAPI_FPM_PATH)"
       ;;
   esac
 
   
+  PHP_VAR_SUBST="$PHP_VAR_SUBST SAPI_FPM_PATH"
+
+  
   PHP_VAR_SUBST="$PHP_VAR_SUBST BUILD_FPM"
 
+
 else
   echo "$ac_t""no" 1>&6
 fi
@@ -11154,7 +11366,7 @@ fi
 php_with_isapi=no
 
 echo $ac_n "checking for Zeus ISAPI support""... $ac_c" 1>&6
-echo "configure:11158: checking for Zeus ISAPI support" >&5
+echo "configure:11375: checking for Zeus ISAPI support" >&5
 # Check whether --with-isapi or --without-isapi was given.
 if test "${with_isapi+set}" = set; then
   withval="$with_isapi"
@@ -11222,23 +11434,32 @@ EOF
   fi
 
   
-  if test "$PHP_SAPI" != "default"; then
-{ echo "configure: error: 
+  if test "shared" = "program"; then
+    PHP_BINARIES="$PHP_BINARIES isapi"
+  elif test "$PHP_SAPI" != "none"; then
+    { echo "configure: error: 
 +--------------------------------------------------------------------+
 |                        *** ATTENTION ***                           |
 |                                                                    |
 | You've configured multiple SAPIs to be build. You can build only   |
-| one SAPI module and CLI binary at the same time.                   |
+| one SAPI module plus CGI, CLI and FPM binaries at the same time.   |
 +--------------------------------------------------------------------+
 " 1>&2; exit 1; }
+  else
+    PHP_SAPI=isapi
   fi
 
-  PHP_SAPI=isapi
   
+  
+    BUILD_DIR="$BUILD_DIR sapi/isapi"
+  
+
+
+  PHP_INSTALLED_SAPIS="$PHP_INSTALLED_SAPIS isapi"
+
   case "shared" in
   static) 
   
-  OVERALL_TARGET=php
   php_c_pre='$(LIBTOOL) --mode=compile $(CC)'
   php_c_meta='$(COMMON_FLAGS) $(CFLAGS_CLEAN) $(EXTRA_CFLAGS)'
   php_c_post=
@@ -11260,14 +11481,11 @@ EOF
   shared_cxx_post=
   shared_lo=lo
 
-  php_build_target=program
-
   OVERALL_TARGET=libphp$PHP_MAJOR_VERSION.la
-  php_build_target=static
+  php_sapi_module=static
 ;;
   shared) 
   
-  OVERALL_TARGET=php
   php_c_pre='$(LIBTOOL) --mode=compile $(CC)'
   php_c_meta='$(COMMON_FLAGS) $(CFLAGS_CLEAN) $(EXTRA_CFLAGS)'
   php_c_post=
@@ -11289,10 +11507,8 @@ EOF
   shared_cxx_post=
   shared_lo=lo
 
-  php_build_target=program
-
   OVERALL_TARGET=libphp$PHP_MAJOR_VERSION.la
-  php_build_target=shared
+  php_sapi_module=shared
   
   php_c_pre=$shared_c_pre
   php_c_meta=$shared_c_meta
@@ -11304,7 +11520,6 @@ EOF
 ;;
   bundle) 
   
-  OVERALL_TARGET=php
   php_c_pre='$(LIBTOOL) --mode=compile $(CC)'
   php_c_meta='$(COMMON_FLAGS) $(CFLAGS_CLEAN) $(EXTRA_CFLAGS)'
   php_c_post=
@@ -11326,13 +11541,10 @@ EOF
   shared_cxx_post=
   shared_lo=lo
 
-  php_build_target=program
-
   OVERALL_TARGET=libs/libphp$PHP_MAJOR_VERSION.bundle
-  php_build_target=static
+  php_sapi_module=static
 ;;
   program) 
-  OVERALL_TARGET=php
   php_c_pre='$(LIBTOOL) --mode=compile $(CC)'
   php_c_meta='$(COMMON_FLAGS) $(CFLAGS_CLEAN) $(EXTRA_CFLAGS)'
   php_c_post=
@@ -11353,12 +11565,12 @@ EOF
   shared_cxx_meta='$(COMMON_FLAGS) $(CXXFLAGS_CLEAN) $(EXTRA_CXXFLAGS) '$pic_setting
   shared_cxx_post=
   shared_lo=lo
-
-  php_build_target=program
 ;;
   esac
     
   
+    install_sapi="install-sapi"
+    
   
   case sapi/isapi in
   "") ac_srcdir="$abs_srcdir/"; unset ac_bdir; ac_inc="-I. -I$abs_srcdir" ;;
@@ -11401,6 +11613,7 @@ EOF
   done
 
 
+  
 
   INSTALL_IT="\$(SHELL) \$(srcdir)/install-sh -m 0755 $SAPI_SHARED \$(INSTALL_ROOT)$ZEUSPATH/web/bin/"
 fi
@@ -11408,7 +11621,7 @@ fi
 
 
 echo $ac_n "checking for LiteSpeed support""... $ac_c" 1>&6
-echo "configure:11412: checking for LiteSpeed support" >&5
+echo "configure:11630: checking for LiteSpeed support" >&5
 
 
 php_with_litespeed=no
@@ -11462,26 +11675,32 @@ if test "$PHP_LITESPEED" != "no"; then
 
   SAPI_LITESPEED_PATH=sapi/litespeed/php
   
-  PHP_VAR_SUBST="$PHP_VAR_SUBST SAPI_LITESPEED_PATH"
-
-  
-  if test "$PHP_SAPI" != "default"; then
-{ echo "configure: error: 
+  if test "program" = "program"; then
+    PHP_BINARIES="$PHP_BINARIES litespeed"
+  elif test "$PHP_SAPI" != "none"; then
+    { echo "configure: error: 
 +--------------------------------------------------------------------+
 |                        *** ATTENTION ***                           |
 |                                                                    |
 | You've configured multiple SAPIs to be build. You can build only   |
-| one SAPI module and CLI binary at the same time.                   |
+| one SAPI module plus CGI, CLI and FPM binaries at the same time.   |
 +--------------------------------------------------------------------+
 " 1>&2; exit 1; }
+  else
+    PHP_SAPI=litespeed
   fi
 
-  PHP_SAPI=litespeed
   
+  
+    BUILD_DIR="$BUILD_DIR sapi/litespeed"
+  
+
+
+  PHP_INSTALLED_SAPIS="$PHP_INSTALLED_SAPIS litespeed"
+
   case "program" in
   static) 
   
-  OVERALL_TARGET=php
   php_c_pre='$(LIBTOOL) --mode=compile $(CC)'
   php_c_meta='$(COMMON_FLAGS) $(CFLAGS_CLEAN) $(EXTRA_CFLAGS)'
   php_c_post=
@@ -11503,14 +11722,11 @@ if test "$PHP_LITESPEED" != "no"; then
   shared_cxx_post=
   shared_lo=lo
 
-  php_build_target=program
-
   OVERALL_TARGET=libphp$PHP_MAJOR_VERSION.la
-  php_build_target=static
+  php_sapi_module=static
 ;;
   shared) 
   
-  OVERALL_TARGET=php
   php_c_pre='$(LIBTOOL) --mode=compile $(CC)'
   php_c_meta='$(COMMON_FLAGS) $(CFLAGS_CLEAN) $(EXTRA_CFLAGS)'
   php_c_post=
@@ -11532,10 +11748,8 @@ if test "$PHP_LITESPEED" != "no"; then
   shared_cxx_post=
   shared_lo=lo
 
-  php_build_target=program
-
   OVERALL_TARGET=libphp$PHP_MAJOR_VERSION.la
-  php_build_target=shared
+  php_sapi_module=shared
   
   php_c_pre=$shared_c_pre
   php_c_meta=$shared_c_meta
@@ -11547,7 +11761,6 @@ if test "$PHP_LITESPEED" != "no"; then
 ;;
   bundle) 
   
-  OVERALL_TARGET=php
   php_c_pre='$(LIBTOOL) --mode=compile $(CC)'
   php_c_meta='$(COMMON_FLAGS) $(CFLAGS_CLEAN) $(EXTRA_CFLAGS)'
   php_c_post=
@@ -11569,13 +11782,10 @@ if test "$PHP_LITESPEED" != "no"; then
   shared_cxx_post=
   shared_lo=lo
 
-  php_build_target=program
-
   OVERALL_TARGET=libs/libphp$PHP_MAJOR_VERSION.bundle
-  php_build_target=static
+  php_sapi_module=static
 ;;
   program) 
-  OVERALL_TARGET='$(SAPI_LITESPEED_PATH)'
   php_c_pre='$(LIBTOOL) --mode=compile $(CC)'
   php_c_meta='$(COMMON_FLAGS) $(CFLAGS_CLEAN) $(EXTRA_CFLAGS)'
   php_c_post=
@@ -11596,13 +11806,16 @@ if test "$PHP_LITESPEED" != "no"; then
   shared_cxx_meta='$(COMMON_FLAGS) $(CXXFLAGS_CLEAN) $(EXTRA_CXXFLAGS) '$pic_setting
   shared_cxx_post=
   shared_lo=lo
-
-  php_build_target=program
 ;;
   esac
     
   
-  
+    install_binaries="install-binaries"
+    install_binary_targets="$install_binary_targets install-litespeed"
+    
+  PHP_VAR_SUBST="$PHP_VAR_SUBST PHP_LITESPEED_OBJS"
+
+    
   case sapi/litespeed in
   "") ac_srcdir="$abs_srcdir/"; unset ac_bdir; ac_inc="-I. -I$abs_srcdir" ;;
   /*) ac_srcdir=`echo "sapi/litespeed"|cut -c 2-`"/"; ac_bdir=$ac_srcdir; ac_inc="-I$ac_bdir -I$abs_srcdir/$ac_bdir" ;;
@@ -11628,7 +11841,7 @@ if test "$PHP_LITESPEED" != "no"; then
       ac_obj=$1
       IFS=$old_IFS
       
-      PHP_SAPI_OBJS="$PHP_SAPI_OBJS $ac_bdir$ac_obj.lo"
+      PHP_LITESPEED_OBJS="$PHP_LITESPEED_OBJS $ac_bdir$ac_obj.lo"
 
       case $ac_src in
         *.c) ac_comp="$b_c_pre "" $ac_inc $b_c_meta -c $ac_srcdir$ac_src -o $ac_bdir$ac_obj.$b_lo $b_c_post" ;;
@@ -11643,23 +11856,25 @@ $ac_bdir$ac_obj.lo: $ac_srcdir$ac_src
 EOF
   done
 
-
+  
  
-  INSTALL_IT="@echo \"Installing PHP LiteSpeed into: \$(INSTALL_ROOT)\$(bindir)/\"; \$(INSTALL) -m 0755 \$(SAPI_LITESPEED_PATH) \$(INSTALL_ROOT)\$(bindir)/lsphp"
   case $host_alias in
   *darwin*)
-    BUILD_LITESPEED="\$(CC) \$(CFLAGS_CLEAN) \$(EXTRA_CFLAGS) \$(EXTRA_LDFLAGS_PROGRAM) \$(LDFLAGS) \$(NATIVE_RPATHS) \$(PHP_GLOBAL_OBJS:.lo=.o) \$(PHP_SAPI_OBJS:.lo=.o) \$(PHP_FRAMEWORKS) \$(EXTRA_LIBS) \$(ZEND_EXTRA_LIBS) -o \$(SAPI_LITESPEED_PATH)"
+    BUILD_LITESPEED="\$(CC) \$(CFLAGS_CLEAN) \$(EXTRA_CFLAGS) \$(EXTRA_LDFLAGS_PROGRAM) \$(LDFLAGS) \$(NATIVE_RPATHS) \$(PHP_GLOBAL_OBJS:.lo=.o) \$(PHP_BINARY_OBJS:.lo=.o) \$(PHP_LITESPEED_OBJS:.lo=.o) \$(PHP_FRAMEWORKS) \$(EXTRA_LIBS) \$(ZEND_EXTRA_LIBS) -o \$(SAPI_LITESPEED_PATH)"
     ;;
   *cygwin*)
     SAPI_LITESPEED_PATH=sapi/litespeed/php.exe
-    BUILD_LITESPEED="\$(LIBTOOL) --mode=link \$(CC) -export-dynamic \$(CFLAGS_CLEAN) \$(EXTRA_CFLAGS) \$(EXTRA_LDFLAGS_PROGRAM) \$(LDFLAGS) \$(PHP_RPATHS) \$(PHP_GLOBAL_OBJS) \$(PHP_SAPI_OBJS) \$(EXTRA_LIBS) \$(ZEND_EXTRA_LIBS) -o \$(SAPI_LITESPEED_PATH)"
+    BUILD_LITESPEED="\$(LIBTOOL) --mode=link \$(CC) -export-dynamic \$(CFLAGS_CLEAN) \$(EXTRA_CFLAGS) \$(EXTRA_LDFLAGS_PROGRAM) \$(LDFLAGS) \$(PHP_RPATHS) \$(PHP_GLOBAL_OBJS) \$(PHP_BINARY_OBJS) \$(PHP_LITESPEED_OBJS) \$(EXTRA_LIBS) \$(ZEND_EXTRA_LIBS) -o \$(SAPI_LITESPEED_PATH)"
     ;;
   *)
-    BUILD_LITESPEED="\$(LIBTOOL) --mode=link \$(CC) -export-dynamic \$(CFLAGS_CLEAN) \$(EXTRA_CFLAGS) \$(EXTRA_LDFLAGS_PROGRAM) \$(LDFLAGS) \$(PHP_RPATHS) \$(PHP_GLOBAL_OBJS) \$(PHP_SAPI_OBJS) \$(EXTRA_LIBS) \$(ZEND_EXTRA_LIBS) -o \$(SAPI_LITESPEED_PATH)"
+    BUILD_LITESPEED="\$(LIBTOOL) --mode=link \$(CC) -export-dynamic \$(CFLAGS_CLEAN) \$(EXTRA_CFLAGS) \$(EXTRA_LDFLAGS_PROGRAM) \$(LDFLAGS) \$(PHP_RPATHS) \$(PHP_GLOBAL_OBJS) \$(PHP_BINARY_OBJS) \$(PHP_LITESPEED_OBJS) \$(EXTRA_LIBS) \$(ZEND_EXTRA_LIBS) -o \$(SAPI_LITESPEED_PATH)"
     ;;
   esac
 
   
+  PHP_VAR_SUBST="$PHP_VAR_SUBST SAPI_LITESPEED_PATH"
+
+  
   PHP_VAR_SUBST="$PHP_VAR_SUBST BUILD_LITESPEED"
 
 fi
@@ -11671,7 +11886,7 @@ echo "$ac_t""$PHP_LITESPEED" 1>&6
 php_with_milter=no
 
 echo $ac_n "checking for Milter support""... $ac_c" 1>&6
-echo "configure:11675: checking for Milter support" >&5
+echo "configure:11895: checking for Milter support" >&5
 # Check whether --with-milter or --without-milter was given.
 if test "${with_milter+set}" = set; then
   withval="$with_milter"
@@ -11719,23 +11934,32 @@ if test "$PHP_MILTER" != "no"; then
   test -f "$src" && $SED -e "s#\$(srcdir)#$ac_srcdir#g" -e "s#\$(builddir)#$ac_builddir#g" $src  >> Makefile.fragments
 
   
-  if test "$PHP_SAPI" != "default"; then
-{ echo "configure: error: 
+  if test "program" = "program"; then
+    PHP_BINARIES="$PHP_BINARIES milter"
+  elif test "$PHP_SAPI" != "none"; then
+    { echo "configure: error: 
 +--------------------------------------------------------------------+
 |                        *** ATTENTION ***                           |
 |                                                                    |
 | You've configured multiple SAPIs to be build. You can build only   |
-| one SAPI module and CLI binary at the same time.                   |
+| one SAPI module plus CGI, CLI and FPM binaries at the same time.   |
 +--------------------------------------------------------------------+
 " 1>&2; exit 1; }
+  else
+    PHP_SAPI=milter
   fi
 
-  PHP_SAPI=milter
   
+  
+    BUILD_DIR="$BUILD_DIR sapi/milter"
+  
+
+
+  PHP_INSTALLED_SAPIS="$PHP_INSTALLED_SAPIS milter"
+
   case "program" in
   static) 
   
-  OVERALL_TARGET=php
   php_c_pre='$(LIBTOOL) --mode=compile $(CC)'
   php_c_meta='$(COMMON_FLAGS) $(CFLAGS_CLEAN) $(EXTRA_CFLAGS)'
   php_c_post=
@@ -11757,14 +11981,11 @@ if test "$PHP_MILTER" != "no"; then
   shared_cxx_post=
   shared_lo=lo
 
-  php_build_target=program
-
   OVERALL_TARGET=libphp$PHP_MAJOR_VERSION.la
-  php_build_target=static
+  php_sapi_module=static
 ;;
   shared) 
   
-  OVERALL_TARGET=php
   php_c_pre='$(LIBTOOL) --mode=compile $(CC)'
   php_c_meta='$(COMMON_FLAGS) $(CFLAGS_CLEAN) $(EXTRA_CFLAGS)'
   php_c_post=
@@ -11786,10 +12007,8 @@ if test "$PHP_MILTER" != "no"; then
   shared_cxx_post=
   shared_lo=lo
 
-  php_build_target=program
-
   OVERALL_TARGET=libphp$PHP_MAJOR_VERSION.la
-  php_build_target=shared
+  php_sapi_module=shared
   
   php_c_pre=$shared_c_pre
   php_c_meta=$shared_c_meta
@@ -11801,7 +12020,6 @@ if test "$PHP_MILTER" != "no"; then
 ;;
   bundle) 
   
-  OVERALL_TARGET=php
   php_c_pre='$(LIBTOOL) --mode=compile $(CC)'
   php_c_meta='$(COMMON_FLAGS) $(CFLAGS_CLEAN) $(EXTRA_CFLAGS)'
   php_c_post=
@@ -11823,13 +12041,10 @@ if test "$PHP_MILTER" != "no"; then
   shared_cxx_post=
   shared_lo=lo
 
-  php_build_target=program
-
   OVERALL_TARGET=libs/libphp$PHP_MAJOR_VERSION.bundle
-  php_build_target=static
+  php_sapi_module=static
 ;;
   program) 
-  OVERALL_TARGET='$(SAPI_MILTER_PATH)'
   php_c_pre='$(LIBTOOL) --mode=compile $(CC)'
   php_c_meta='$(COMMON_FLAGS) $(CFLAGS_CLEAN) $(EXTRA_CFLAGS)'
   php_c_post=
@@ -11850,13 +12065,16 @@ if test "$PHP_MILTER" != "no"; then
   shared_cxx_meta='$(COMMON_FLAGS) $(CXXFLAGS_CLEAN) $(EXTRA_CXXFLAGS) '$pic_setting
   shared_cxx_post=
   shared_lo=lo
-
-  php_build_target=program
 ;;
   esac
     
   
-  
+    install_binaries="install-binaries"
+    install_binary_targets="$install_binary_targets install-milter"
+    
+  PHP_VAR_SUBST="$PHP_VAR_SUBST PHP_MILTER_OBJS"
+
+    
   case sapi/milter in
   "") ac_srcdir="$abs_srcdir/"; unset ac_bdir; ac_inc="-I. -I$abs_srcdir" ;;
   /*) ac_srcdir=`echo "sapi/milter"|cut -c 2-`"/"; ac_bdir=$ac_srcdir; ac_inc="-I$ac_bdir -I$abs_srcdir/$ac_bdir" ;;
@@ -11882,7 +12100,7 @@ if test "$PHP_MILTER" != "no"; then
       ac_obj=$1
       IFS=$old_IFS
       
-      PHP_SAPI_OBJS="$PHP_SAPI_OBJS $ac_bdir$ac_obj.lo"
+      PHP_MILTER_OBJS="$PHP_MILTER_OBJS $ac_bdir$ac_obj.lo"
 
       case $ac_src in
         *.c) ac_comp="$b_c_pre  $ac_inc $b_c_meta -c $ac_srcdir$ac_src -o $ac_bdir$ac_obj.$b_lo $b_c_post" ;;
@@ -11897,7 +12115,7 @@ $ac_bdir$ac_obj.lo: $ac_srcdir$ac_src
 EOF
   done
 
-
+  
  
   
 
@@ -11948,8 +12166,7 @@ EOF
 
 
 
-  BUILD_MILTER="\$(LIBTOOL) --mode=link \$(CC) -export-dynamic \$(CFLAGS_CLEAN) \$(EXTRA_CFLAGS) \$(EXTRA_LDFLAGS) \$(LDFLAGS) \$(PHP_RPATHS) \$(PHP_GLOBAL_OBJS) \$(PHP_SAPI_OBJS) \$(EXTRA_LIBS) \$(ZEND_EXTRA_LIBS) -o \$(SAPI_MILTER_PATH)"
-  INSTALL_IT="\$(INSTALL) -m 0755 \$(SAPI_MILTER_PATH) \$(bindir)/php-milter"
+  BUILD_MILTER="\$(LIBTOOL) --mode=link \$(CC) -export-dynamic \$(CFLAGS_CLEAN) \$(EXTRA_CFLAGS) \$(EXTRA_LDFLAGS) \$(LDFLAGS) \$(PHP_RPATHS) \$(PHP_GLOBAL_OBJS) \$(PHP_BINARY_OBJS) \$(PHP_MILTER_OBJS) \$(EXTRA_LIBS) \$(ZEND_EXTRA_LIBS) -o \$(SAPI_MILTER_PATH)"
   
   PHP_VAR_SUBST="$PHP_VAR_SUBST SAPI_MILTER_PATH"
 
@@ -11963,7 +12180,7 @@ fi
 php_with_nsapi=no
 
 echo $ac_n "checking for NSAPI support""... $ac_c" 1>&6
-echo "configure:11967: checking for NSAPI support" >&5
+echo "configure:12189: checking for NSAPI support" >&5
 # Check whether --with-nsapi or --without-nsapi was given.
 if test "${with_nsapi+set}" = set; then
   withval="$with_nsapi"
@@ -11987,7 +12204,7 @@ if test "$PHP_NSAPI" != "no"; then
     { echo "configure: error: Please specify the path to the root of your Netscape/iPlanet/Sun Webserver using --with-nsapi=DIR" 1>&2; exit 1; }
   fi
   echo $ac_n "checking for NSAPI include files""... $ac_c" 1>&6
-echo "configure:11991: checking for NSAPI include files" >&5
+echo "configure:12213: checking for NSAPI include files" >&5
   if test -d $PHP_NSAPI/include ; then
     NSAPI_INC_DIR="$PHP_NSAPI/include"
     echo "$ac_t""Netscape 3.x / Sun 7.x style" 1>&6
@@ -11995,17 +12212,17 @@ echo "configure:11991: checking for NSAPI include files" >&5
 do
 ac_safe=`echo "$ac_hdr" | sed 'y%./+-%__p_%'`
 echo $ac_n "checking for $ac_hdr""... $ac_c" 1>&6
-echo "configure:11999: checking for $ac_hdr" >&5
+echo "configure:12221: checking for $ac_hdr" >&5
 if eval "test \"`echo '$''{'ac_cv_header_$ac_safe'+set}'`\" = set"; then
   echo $ac_n "(cached) $ac_c" 1>&6
 else
   cat > conftest.$ac_ext <<EOF
-#line 12004 "configure"
+#line 12226 "configure"
 #include "confdefs.h"
 #include <$ac_hdr>
 EOF
 ac_try="$ac_cpp conftest.$ac_ext >/dev/null 2>conftest.out"
-{ (eval echo configure:12009: \"$ac_try\") 1>&5; (eval $ac_try) 2>&5; }
+{ (eval echo configure:12231: \"$ac_try\") 1>&5; (eval $ac_try) 2>&5; }
 ac_err=`grep -v '^ *+' conftest.out | grep -v "^conftest.${ac_ext}\$"`
 if test -z "$ac_err"; then
   rm -rf conftest*
@@ -12040,17 +12257,17 @@ done
 do
 ac_safe=`echo "$ac_hdr" | sed 'y%./+-%__p_%'`
 echo $ac_n "checking for $ac_hdr""... $ac_c" 1>&6
-echo "configure:12044: checking for $ac_hdr" >&5
+echo "configure:12266: checking for $ac_hdr" >&5
 if eval "test \"`echo '$''{'ac_cv_header_$ac_safe'+set}'`\" = set"; then
   echo $ac_n "(cached) $ac_c" 1>&6
 else
   cat > conftest.$ac_ext <<EOF
-#line 12049 "configure"
+#line 12271 "configure"
 #include "confdefs.h"
 #include <$ac_hdr>
 EOF
 ac_try="$ac_cpp conftest.$ac_ext >/dev/null 2>conftest.out"
-{ (eval echo configure:12054: \"$ac_try\") 1>&5; (eval $ac_try) 2>&5; }
+{ (eval echo configure:12276: \"$ac_try\") 1>&5; (eval $ac_try) 2>&5; }
 ac_err=`grep -v '^ *+' conftest.out | grep -v "^conftest.${ac_ext}\$"`
 if test -z "$ac_err"; then
   rm -rf conftest*
@@ -12133,23 +12350,32 @@ done
 EOF
 
   
-  if test "$PHP_SAPI" != "default"; then
-{ echo "configure: error: 
+  if test "shared" = "program"; then
+    PHP_BINARIES="$PHP_BINARIES nsapi"
+  elif test "$PHP_SAPI" != "none"; then
+    { echo "configure: error: 
 +--------------------------------------------------------------------+
 |                        *** ATTENTION ***                           |
 |                                                                    |
 | You've configured multiple SAPIs to be build. You can build only   |
-| one SAPI module and CLI binary at the same time.                   |
+| one SAPI module plus CGI, CLI and FPM binaries at the same time.   |
 +--------------------------------------------------------------------+
 " 1>&2; exit 1; }
+  else
+    PHP_SAPI=nsapi
   fi
 
-  PHP_SAPI=nsapi
   
+  
+    BUILD_DIR="$BUILD_DIR sapi/nsapi"
+  
+
+
+  PHP_INSTALLED_SAPIS="$PHP_INSTALLED_SAPIS nsapi"
+
   case "shared" in
   static) 
   
-  OVERALL_TARGET=php
   php_c_pre='$(LIBTOOL) --mode=compile $(CC)'
   php_c_meta='$(COMMON_FLAGS) $(CFLAGS_CLEAN) $(EXTRA_CFLAGS)'
   php_c_post=
@@ -12171,14 +12397,11 @@ EOF
   shared_cxx_post=
   shared_lo=lo
 
-  php_build_target=program
-
   OVERALL_TARGET=libphp$PHP_MAJOR_VERSION.la
-  php_build_target=static
+  php_sapi_module=static
 ;;
   shared) 
   
-  OVERALL_TARGET=php
   php_c_pre='$(LIBTOOL) --mode=compile $(CC)'
   php_c_meta='$(COMMON_FLAGS) $(CFLAGS_CLEAN) $(EXTRA_CFLAGS)'
   php_c_post=
@@ -12200,10 +12423,8 @@ EOF
   shared_cxx_post=
   shared_lo=lo
 
-  php_build_target=program
-
   OVERALL_TARGET=libphp$PHP_MAJOR_VERSION.la
-  php_build_target=shared
+  php_sapi_module=shared
   
   php_c_pre=$shared_c_pre
   php_c_meta=$shared_c_meta
@@ -12215,7 +12436,6 @@ EOF
 ;;
   bundle) 
   
-  OVERALL_TARGET=php
   php_c_pre='$(LIBTOOL) --mode=compile $(CC)'
   php_c_meta='$(COMMON_FLAGS) $(CFLAGS_CLEAN) $(EXTRA_CFLAGS)'
   php_c_post=
@@ -12237,13 +12457,10 @@ EOF
   shared_cxx_post=
   shared_lo=lo
 
-  php_build_target=program
-
   OVERALL_TARGET=libs/libphp$PHP_MAJOR_VERSION.bundle
-  php_build_target=static
+  php_sapi_module=static
 ;;
   program) 
-  OVERALL_TARGET=php
   php_c_pre='$(LIBTOOL) --mode=compile $(CC)'
   php_c_meta='$(COMMON_FLAGS) $(CFLAGS_CLEAN) $(EXTRA_CFLAGS)'
   php_c_post=
@@ -12264,12 +12481,12 @@ EOF
   shared_cxx_meta='$(COMMON_FLAGS) $(CXXFLAGS_CLEAN) $(EXTRA_CXXFLAGS) '$pic_setting
   shared_cxx_post=
   shared_lo=lo
-
-  php_build_target=program
 ;;
   esac
     
   
+    install_sapi="install-sapi"
+    
   
   case sapi/nsapi in
   "") ac_srcdir="$abs_srcdir/"; unset ac_bdir; ac_inc="-I. -I$abs_srcdir" ;;
@@ -12312,6 +12529,7 @@ EOF
   done
 
 
+  
 
   INSTALL_IT="\$(INSTALL) -m 0755 $SAPI_SHARED \$(INSTALL_ROOT)$PHP_NSAPI/bin/"
 fi
@@ -12323,7 +12541,7 @@ fi
 php_with_phttpd=no
 
 echo $ac_n "checking for PHTTPD support""... $ac_c" 1>&6
-echo "configure:12327: checking for PHTTPD support" >&5
+echo "configure:12550: checking for PHTTPD support" >&5
 # Check whether --with-phttpd or --without-phttpd was given.
 if test "${with_phttpd+set}" = set; then
   withval="$with_phttpd"
@@ -12388,23 +12606,32 @@ if test "$PHP_PHTTPD" != "no"; then
 EOF
 
   
-  if test "$PHP_SAPI" != "default"; then
-{ echo "configure: error: 
+  if test "shared" = "program"; then
+    PHP_BINARIES="$PHP_BINARIES phttpd"
+  elif test "$PHP_SAPI" != "none"; then
+    { echo "configure: error: 
 +--------------------------------------------------------------------+
 |                        *** ATTENTION ***                           |
 |                                                                    |
 | You've configured multiple SAPIs to be build. You can build only   |
-| one SAPI module and CLI binary at the same time.                   |
+| one SAPI module plus CGI, CLI and FPM binaries at the same time.   |
 +--------------------------------------------------------------------+
 " 1>&2; exit 1; }
+  else
+    PHP_SAPI=phttpd
   fi
 
-  PHP_SAPI=phttpd
   
+  
+    BUILD_DIR="$BUILD_DIR sapi/phttpd"
+  
+
+
+  PHP_INSTALLED_SAPIS="$PHP_INSTALLED_SAPIS phttpd"
+
   case "shared" in
   static) 
   
-  OVERALL_TARGET=php
   php_c_pre='$(LIBTOOL) --mode=compile $(CC)'
   php_c_meta='$(COMMON_FLAGS) $(CFLAGS_CLEAN) $(EXTRA_CFLAGS)'
   php_c_post=
@@ -12426,14 +12653,11 @@ EOF
   shared_cxx_post=
   shared_lo=lo
 
-  php_build_target=program
-
   OVERALL_TARGET=libphp$PHP_MAJOR_VERSION.la
-  php_build_target=static
+  php_sapi_module=static
 ;;
   shared) 
   
-  OVERALL_TARGET=php
   php_c_pre='$(LIBTOOL) --mode=compile $(CC)'
   php_c_meta='$(COMMON_FLAGS) $(CFLAGS_CLEAN) $(EXTRA_CFLAGS)'
   php_c_post=
@@ -12455,10 +12679,8 @@ EOF
   shared_cxx_post=
   shared_lo=lo
 
-  php_build_target=program
-
   OVERALL_TARGET=libphp$PHP_MAJOR_VERSION.la
-  php_build_target=shared
+  php_sapi_module=shared
   
   php_c_pre=$shared_c_pre
   php_c_meta=$shared_c_meta
@@ -12470,7 +12692,6 @@ EOF
 ;;
   bundle) 
   
-  OVERALL_TARGET=php
   php_c_pre='$(LIBTOOL) --mode=compile $(CC)'
   php_c_meta='$(COMMON_FLAGS) $(CFLAGS_CLEAN) $(EXTRA_CFLAGS)'
   php_c_post=
@@ -12492,13 +12713,10 @@ EOF
   shared_cxx_post=
   shared_lo=lo
 
-  php_build_target=program
-
   OVERALL_TARGET=libs/libphp$PHP_MAJOR_VERSION.bundle
-  php_build_target=static
+  php_sapi_module=static
 ;;
   program) 
-  OVERALL_TARGET=php
   php_c_pre='$(LIBTOOL) --mode=compile $(CC)'
   php_c_meta='$(COMMON_FLAGS) $(CFLAGS_CLEAN) $(EXTRA_CFLAGS)'
   php_c_post=
@@ -12519,12 +12737,12 @@ EOF
   shared_cxx_meta='$(COMMON_FLAGS) $(CXXFLAGS_CLEAN) $(EXTRA_CXXFLAGS) '$pic_setting
   shared_cxx_post=
   shared_lo=lo
-
-  php_build_target=program
 ;;
   esac
     
   
+    install_sapi="install-sapi"
+    
   
   case sapi/phttpd in
   "") ac_srcdir="$abs_srcdir/"; unset ac_bdir; ac_inc="-I. -I$abs_srcdir" ;;
@@ -12567,6 +12785,7 @@ EOF
   done
 
 
+  
 
   INSTALL_IT="\$(INSTALL) -m 0755 $SAPI_SHARED \$(INSTALL_ROOT)$PHP_PHTTPD/modules/"
 fi
@@ -12577,7 +12796,7 @@ fi
 php_with_pi3web=no
 
 echo $ac_n "checking for Pi3Web support""... $ac_c" 1>&6
-echo "configure:12581: checking for Pi3Web support" >&5
+echo "configure:12805: checking for Pi3Web support" >&5
 # Check whether --with-pi3web or --without-pi3web was given.
 if test "${with_pi3web+set}" = set; then
   withval="$with_pi3web"
@@ -12738,23 +12957,32 @@ EOF
   fi
 
   
-  if test "$PHP_SAPI" != "default"; then
-{ echo "configure: error: 
+  if test "shared" = "program"; then
+    PHP_BINARIES="$PHP_BINARIES pi3web"
+  elif test "$PHP_SAPI" != "none"; then
+    { echo "configure: error: 
 +--------------------------------------------------------------------+
 |                        *** ATTENTION ***                           |
 |                                                                    |
 | You've configured multiple SAPIs to be build. You can build only   |
-| one SAPI module and CLI binary at the same time.                   |
+| one SAPI module plus CGI, CLI and FPM binaries at the same time.   |
 +--------------------------------------------------------------------+
 " 1>&2; exit 1; }
+  else
+    PHP_SAPI=pi3web
   fi
 
-  PHP_SAPI=pi3web
   
+  
+    BUILD_DIR="$BUILD_DIR sapi/pi3web"
+  
+
+
+  PHP_INSTALLED_SAPIS="$PHP_INSTALLED_SAPIS pi3web"
+
   case "shared" in
   static) 
   
-  OVERALL_TARGET=php
   php_c_pre='$(LIBTOOL) --mode=compile $(CC)'
   php_c_meta='$(COMMON_FLAGS) $(CFLAGS_CLEAN) $(EXTRA_CFLAGS)'
   php_c_post=
@@ -12776,14 +13004,11 @@ EOF
   shared_cxx_post=
   shared_lo=lo
 
-  php_build_target=program
-
   OVERALL_TARGET=libphp$PHP_MAJOR_VERSION.la
-  php_build_target=static
+  php_sapi_module=static
 ;;
   shared) 
   
-  OVERALL_TARGET=php
   php_c_pre='$(LIBTOOL) --mode=compile $(CC)'
   php_c_meta='$(COMMON_FLAGS) $(CFLAGS_CLEAN) $(EXTRA_CFLAGS)'
   php_c_post=
@@ -12805,10 +13030,8 @@ EOF
   shared_cxx_post=
   shared_lo=lo
 
-  php_build_target=program
-
   OVERALL_TARGET=libphp$PHP_MAJOR_VERSION.la
-  php_build_target=shared
+  php_sapi_module=shared
   
   php_c_pre=$shared_c_pre
   php_c_meta=$shared_c_meta
@@ -12820,7 +13043,6 @@ EOF
 ;;
   bundle) 
   
-  OVERALL_TARGET=php
   php_c_pre='$(LIBTOOL) --mode=compile $(CC)'
   php_c_meta='$(COMMON_FLAGS) $(CFLAGS_CLEAN) $(EXTRA_CFLAGS)'
   php_c_post=
@@ -12842,13 +13064,10 @@ EOF
   shared_cxx_post=
   shared_lo=lo
 
-  php_build_target=program
-
   OVERALL_TARGET=libs/libphp$PHP_MAJOR_VERSION.bundle
-  php_build_target=static
+  php_sapi_module=static
 ;;
   program) 
-  OVERALL_TARGET=php
   php_c_pre='$(LIBTOOL) --mode=compile $(CC)'
   php_c_meta='$(COMMON_FLAGS) $(CFLAGS_CLEAN) $(EXTRA_CFLAGS)'
   php_c_post=
@@ -12869,12 +13088,12 @@ EOF
   shared_cxx_meta='$(COMMON_FLAGS) $(CXXFLAGS_CLEAN) $(EXTRA_CXXFLAGS) '$pic_setting
   shared_cxx_post=
   shared_lo=lo
-
-  php_build_target=program
 ;;
   esac
     
   
+    install_sapi="install-sapi"
+    
   
   case sapi/pi3web in
   "") ac_srcdir="$abs_srcdir/"; unset ac_bdir; ac_inc="-I. -I$abs_srcdir" ;;
@@ -12917,6 +13136,7 @@ EOF
   done
 
 
+  
 
   INSTALL_IT="\$(SHELL) \$(srcdir)/install-sh -m 0755 $SAPI_SHARED \$(INSTALL_ROOT)$PI3PATH/bin/"
 fi
@@ -12949,7 +13169,7 @@ ext_output=$PHP_ROXEN
 php_enable_roxen_zts=no
 
 echo $ac_n "checking whether Roxen module is build using ZTS""... $ac_c" 1>&6
-echo "configure:12953: checking whether Roxen module is build using ZTS" >&5
+echo "configure:13178: checking whether Roxen module is build using ZTS" >&5
 # Check whether --enable-roxen-zts or --disable-roxen-zts was given.
 if test "${enable_roxen_zts+set}" = set; then
   enableval="$enable_roxen_zts"
@@ -12970,7 +13190,7 @@ echo "$ac_t""$ext_output" 1>&6
 
 RESULT=
 echo $ac_n "checking for Roxen/Pike support""... $ac_c" 1>&6
-echo "configure:12974: checking for Roxen/Pike support" >&5
+echo "configure:13199: checking for Roxen/Pike support" >&5
 if test "$PHP_ROXEN" != "no"; then
   if test ! -d $PHP_ROXEN ; then
     { echo "configure: error: You did not specify a directory" 1>&2; exit 1; }
@@ -13029,23 +13249,32 @@ if test "$PHP_ROXEN" != "no"; then
 EOF
 
   
-  if test "$PHP_SAPI" != "default"; then
-{ echo "configure: error: 
+  if test "shared" = "program"; then
+    PHP_BINARIES="$PHP_BINARIES roxen"
+  elif test "$PHP_SAPI" != "none"; then
+    { echo "configure: error: 
 +--------------------------------------------------------------------+
 |                        *** ATTENTION ***                           |
 |                                                                    |
 | You've configured multiple SAPIs to be build. You can build only   |
-| one SAPI module and CLI binary at the same time.                   |
+| one SAPI module plus CGI, CLI and FPM binaries at the same time.   |
 +--------------------------------------------------------------------+
 " 1>&2; exit 1; }
+  else
+    PHP_SAPI=roxen
   fi
 
-  PHP_SAPI=roxen
   
+  
+    BUILD_DIR="$BUILD_DIR sapi/roxen"
+  
+
+
+  PHP_INSTALLED_SAPIS="$PHP_INSTALLED_SAPIS roxen"
+
   case "shared" in
   static) 
   
-  OVERALL_TARGET=php
   php_c_pre='$(LIBTOOL) --mode=compile $(CC)'
   php_c_meta='$(COMMON_FLAGS) $(CFLAGS_CLEAN) $(EXTRA_CFLAGS)'
   php_c_post=
@@ -13067,14 +13296,11 @@ EOF
   shared_cxx_post=
   shared_lo=lo
 
-  php_build_target=program
-
   OVERALL_TARGET=libphp$PHP_MAJOR_VERSION.la
-  php_build_target=static
+  php_sapi_module=static
 ;;
   shared) 
   
-  OVERALL_TARGET=php
   php_c_pre='$(LIBTOOL) --mode=compile $(CC)'
   php_c_meta='$(COMMON_FLAGS) $(CFLAGS_CLEAN) $(EXTRA_CFLAGS)'
   php_c_post=
@@ -13096,10 +13322,8 @@ EOF
   shared_cxx_post=
   shared_lo=lo
 
-  php_build_target=program
-
   OVERALL_TARGET=libphp$PHP_MAJOR_VERSION.la
-  php_build_target=shared
+  php_sapi_module=shared
   
   php_c_pre=$shared_c_pre
   php_c_meta=$shared_c_meta
@@ -13111,7 +13335,6 @@ EOF
 ;;
   bundle) 
   
-  OVERALL_TARGET=php
   php_c_pre='$(LIBTOOL) --mode=compile $(CC)'
   php_c_meta='$(COMMON_FLAGS) $(CFLAGS_CLEAN) $(EXTRA_CFLAGS)'
   php_c_post=
@@ -13133,13 +13356,10 @@ EOF
   shared_cxx_post=
   shared_lo=lo
 
-  php_build_target=program
-
   OVERALL_TARGET=libs/libphp$PHP_MAJOR_VERSION.bundle
-  php_build_target=static
+  php_sapi_module=static
 ;;
   program) 
-  OVERALL_TARGET=php
   php_c_pre='$(LIBTOOL) --mode=compile $(CC)'
   php_c_meta='$(COMMON_FLAGS) $(CFLAGS_CLEAN) $(EXTRA_CFLAGS)'
   php_c_post=
@@ -13160,12 +13380,12 @@ EOF
   shared_cxx_meta='$(COMMON_FLAGS) $(CXXFLAGS_CLEAN) $(EXTRA_CXXFLAGS) '$pic_setting
   shared_cxx_post=
   shared_lo=lo
-
-  php_build_target=program
 ;;
   esac
     
   
+    install_sapi="install-sapi"
+    
   
   case sapi/roxen in
   "") ac_srcdir="$abs_srcdir/"; unset ac_bdir; ac_inc="-I. -I$abs_srcdir" ;;
@@ -13208,6 +13428,7 @@ EOF
   done
 
 
+  
 
   INSTALL_IT="\$(INSTALL) -m 0755 $SAPI_SHARED $PIKE_MODULE_DIR/PHP5.so"
   RESULT="yes
@@ -13256,7 +13477,7 @@ ext_output=$PHP_THTTPD
 
 
 echo $ac_n "checking for thttpd""... $ac_c" 1>&6
-echo "configure:13260: checking for thttpd" >&5
+echo "configure:13486: checking for thttpd" >&5
 
 if test "$PHP_THTTPD" != "no"; then
   if test ! -d $PHP_THTTPD; then
@@ -13289,7 +13510,7 @@ if test "$PHP_THTTPD" != "no"; then
         
   gcc_arg_name=ac_cv_gcc_arg_rdynamic
   echo $ac_n "checking whether $CC supports -rdynamic""... $ac_c" 1>&6
-echo "configure:13293: checking whether $CC supports -rdynamic" >&5
+echo "configure:13519: checking whether $CC supports -rdynamic" >&5
 if eval "test \"`echo '$''{'ac_cv_gcc_arg_rdynamic'+set}'`\" = set"; then
   echo $ac_n "(cached) $ac_c" 1>&6
 else
@@ -13360,23 +13581,32 @@ echo "$ac_t""$ac_cv_gcc_arg_rdynamic" 1>&6
   fi
 
   
-  if test "$PHP_SAPI" != "default"; then
-{ echo "configure: error: 
+  if test "static" = "program"; then
+    PHP_BINARIES="$PHP_BINARIES thttpd"
+  elif test "$PHP_SAPI" != "none"; then
+    { echo "configure: error: 
 +--------------------------------------------------------------------+
 |                        *** ATTENTION ***                           |
 |                                                                    |
 | You've configured multiple SAPIs to be build. You can build only   |
-| one SAPI module and CLI binary at the same time.                   |
+| one SAPI module plus CGI, CLI and FPM binaries at the same time.   |
 +--------------------------------------------------------------------+
 " 1>&2; exit 1; }
+  else
+    PHP_SAPI=thttpd
   fi
 
-  PHP_SAPI=thttpd
   
+  
+    BUILD_DIR="$BUILD_DIR sapi/thttpd"
+  
+
+
+  PHP_INSTALLED_SAPIS="$PHP_INSTALLED_SAPIS thttpd"
+
   case "static" in
   static) 
   
-  OVERALL_TARGET=php
   php_c_pre='$(LIBTOOL) --mode=compile $(CC)'
   php_c_meta='$(COMMON_FLAGS) $(CFLAGS_CLEAN) $(EXTRA_CFLAGS)'
   php_c_post=
@@ -13398,14 +13628,11 @@ echo "$ac_t""$ac_cv_gcc_arg_rdynamic" 1>&6
   shared_cxx_post=
   shared_lo=lo
 
-  php_build_target=program
-
   OVERALL_TARGET=libphp$PHP_MAJOR_VERSION.la
-  php_build_target=static
+  php_sapi_module=static
 ;;
   shared) 
   
-  OVERALL_TARGET=php
   php_c_pre='$(LIBTOOL) --mode=compile $(CC)'
   php_c_meta='$(COMMON_FLAGS) $(CFLAGS_CLEAN) $(EXTRA_CFLAGS)'
   php_c_post=
@@ -13427,10 +13654,8 @@ echo "$ac_t""$ac_cv_gcc_arg_rdynamic" 1>&6
   shared_cxx_post=
   shared_lo=lo
 
-  php_build_target=program
-
   OVERALL_TARGET=libphp$PHP_MAJOR_VERSION.la
-  php_build_target=shared
+  php_sapi_module=shared
   
   php_c_pre=$shared_c_pre
   php_c_meta=$shared_c_meta
@@ -13442,7 +13667,6 @@ echo "$ac_t""$ac_cv_gcc_arg_rdynamic" 1>&6
 ;;
   bundle) 
   
-  OVERALL_TARGET=php
   php_c_pre='$(LIBTOOL) --mode=compile $(CC)'
   php_c_meta='$(COMMON_FLAGS) $(CFLAGS_CLEAN) $(EXTRA_CFLAGS)'
   php_c_post=
@@ -13464,13 +13688,10 @@ echo "$ac_t""$ac_cv_gcc_arg_rdynamic" 1>&6
   shared_cxx_post=
   shared_lo=lo
 
-  php_build_target=program
-
   OVERALL_TARGET=libs/libphp$PHP_MAJOR_VERSION.bundle
-  php_build_target=static
+  php_sapi_module=static
 ;;
   program) 
-  OVERALL_TARGET=php
   php_c_pre='$(LIBTOOL) --mode=compile $(CC)'
   php_c_meta='$(COMMON_FLAGS) $(CFLAGS_CLEAN) $(EXTRA_CFLAGS)'
   php_c_post=
@@ -13491,12 +13712,13 @@ echo "$ac_t""$ac_cv_gcc_arg_rdynamic" 1>&6
   shared_cxx_meta='$(COMMON_FLAGS) $(CXXFLAGS_CLEAN) $(EXTRA_CXXFLAGS) '$pic_setting
   shared_cxx_post=
   shared_lo=lo
-
-  php_build_target=program
 ;;
   esac
     
   
+    install_sapi="install-sapi"
+    
+  
 
 fi
 echo "$ac_t""$PHP_THTTPD" 1>&6
@@ -13525,24 +13747,24 @@ ext_output=$PHP_TUX
 
 
 echo $ac_n "checking for TUX""... $ac_c" 1>&6
-echo "configure:13529: checking for TUX" >&5
+echo "configure:13756: checking for TUX" >&5
 if test "$PHP_TUX" != "no"; then
   INSTALL_IT="\$(INSTALL) -m 0755 $SAPI_SHARED $PHP_TUX/php5.tux.so"
   for ac_hdr in tuxmodule.h
 do
 ac_safe=`echo "$ac_hdr" | sed 'y%./+-%__p_%'`
 echo $ac_n "checking for $ac_hdr""... $ac_c" 1>&6
-echo "configure:13536: checking for $ac_hdr" >&5
+echo "configure:13763: checking for $ac_hdr" >&5
 if eval "test \"`echo '$''{'ac_cv_header_$ac_safe'+set}'`\" = set"; then
   echo $ac_n "(cached) $ac_c" 1>&6
 else
   cat > conftest.$ac_ext <<EOF
-#line 13541 "configure"
+#line 13768 "configure"
 #include "confdefs.h"
 #include <$ac_hdr>
 EOF
 ac_try="$ac_cpp conftest.$ac_ext >/dev/null 2>conftest.out"
-{ (eval echo configure:13546: \"$ac_try\") 1>&5; (eval $ac_try) 2>&5; }
+{ (eval echo configure:13773: \"$ac_try\") 1>&5; (eval $ac_try) 2>&5; }
 ac_err=`grep -v '^ *+' conftest.out | grep -v "^conftest.${ac_ext}\$"`
 if test -z "$ac_err"; then
   rm -rf conftest*
@@ -13570,23 +13792,32 @@ fi
 done
 
   
-  if test "$PHP_SAPI" != "default"; then
-{ echo "configure: error: 
+  if test "shared" = "program"; then
+    PHP_BINARIES="$PHP_BINARIES tux"
+  elif test "$PHP_SAPI" != "none"; then
+    { echo "configure: error: 
 +--------------------------------------------------------------------+
 |                        *** ATTENTION ***                           |
 |                                                                    |
 | You've configured multiple SAPIs to be build. You can build only   |
-| one SAPI module and CLI binary at the same time.                   |
+| one SAPI module plus CGI, CLI and FPM binaries at the same time.   |
 +--------------------------------------------------------------------+
 " 1>&2; exit 1; }
+  else
+    PHP_SAPI=tux
   fi
 
-  PHP_SAPI=tux
   
+  
+    BUILD_DIR="$BUILD_DIR sapi/tux"
+  
+
+
+  PHP_INSTALLED_SAPIS="$PHP_INSTALLED_SAPIS tux"
+
   case "shared" in
   static) 
   
-  OVERALL_TARGET=php
   php_c_pre='$(LIBTOOL) --mode=compile $(CC)'
   php_c_meta='$(COMMON_FLAGS) $(CFLAGS_CLEAN) $(EXTRA_CFLAGS)'
   php_c_post=
@@ -13608,14 +13839,11 @@ done
   shared_cxx_post=
   shared_lo=lo
 
-  php_build_target=program
-
   OVERALL_TARGET=libphp$PHP_MAJOR_VERSION.la
-  php_build_target=static
+  php_sapi_module=static
 ;;
   shared) 
   
-  OVERALL_TARGET=php
   php_c_pre='$(LIBTOOL) --mode=compile $(CC)'
   php_c_meta='$(COMMON_FLAGS) $(CFLAGS_CLEAN) $(EXTRA_CFLAGS)'
   php_c_post=
@@ -13637,10 +13865,8 @@ done
   shared_cxx_post=
   shared_lo=lo
 
-  php_build_target=program
-
   OVERALL_TARGET=libphp$PHP_MAJOR_VERSION.la
-  php_build_target=shared
+  php_sapi_module=shared
   
   php_c_pre=$shared_c_pre
   php_c_meta=$shared_c_meta
@@ -13652,7 +13878,6 @@ done
 ;;
   bundle) 
   
-  OVERALL_TARGET=php
   php_c_pre='$(LIBTOOL) --mode=compile $(CC)'
   php_c_meta='$(COMMON_FLAGS) $(CFLAGS_CLEAN) $(EXTRA_CFLAGS)'
   php_c_post=
@@ -13674,13 +13899,10 @@ done
   shared_cxx_post=
   shared_lo=lo
 
-  php_build_target=program
-
   OVERALL_TARGET=libs/libphp$PHP_MAJOR_VERSION.bundle
-  php_build_target=static
+  php_sapi_module=static
 ;;
   program) 
-  OVERALL_TARGET=php
   php_c_pre='$(LIBTOOL) --mode=compile $(CC)'
   php_c_meta='$(COMMON_FLAGS) $(CFLAGS_CLEAN) $(EXTRA_CFLAGS)'
   php_c_post=
@@ -13701,12 +13923,12 @@ done
   shared_cxx_meta='$(COMMON_FLAGS) $(CXXFLAGS_CLEAN) $(EXTRA_CXXFLAGS) '$pic_setting
   shared_cxx_post=
   shared_lo=lo
-
-  php_build_target=program
 ;;
   esac
     
   
+    install_sapi="install-sapi"
+    
   
   case sapi/tux in
   "") ac_srcdir="$abs_srcdir/"; unset ac_bdir; ac_inc="-I. -I$abs_srcdir" ;;
@@ -13749,6 +13971,7 @@ EOF
   done
 
 
+  
 
   echo "$ac_t""$PHP_TUX" 1>&6
 else
@@ -13779,7 +14002,7 @@ ext_output=$PHP_WEBJAMES
 
 
 echo $ac_n "checking for webjames""... $ac_c" 1>&6
-echo "configure:13783: checking for webjames" >&5
+echo "configure:14011: checking for webjames" >&5
 
 if test "$PHP_WEBJAMES" != "no"; then
   
@@ -13829,23 +14052,32 @@ if test "$PHP_WEBJAMES" != "no"; then
   fi
 
   
-  if test "$PHP_SAPI" != "default"; then
-{ echo "configure: error: 
+  if test "static" = "program"; then
+    PHP_BINARIES="$PHP_BINARIES webjames"
+  elif test "$PHP_SAPI" != "none"; then
+    { echo "configure: error: 
 +--------------------------------------------------------------------+
 |                        *** ATTENTION ***                           |
 |                                                                    |
 | You've configured multiple SAPIs to be build. You can build only   |
-| one SAPI module and CLI binary at the same time.                   |
+| one SAPI module plus CGI, CLI and FPM binaries at the same time.   |
 +--------------------------------------------------------------------+
 " 1>&2; exit 1; }
+  else
+    PHP_SAPI=webjames
   fi
 
-  PHP_SAPI=webjames
   
+  
+    BUILD_DIR="$BUILD_DIR sapi/webjames"
+  
+
+
+  PHP_INSTALLED_SAPIS="$PHP_INSTALLED_SAPIS webjames"
+
   case "static" in
   static) 
   
-  OVERALL_TARGET=php
   php_c_pre='$(LIBTOOL) --mode=compile $(CC)'
   php_c_meta='$(COMMON_FLAGS) $(CFLAGS_CLEAN) $(EXTRA_CFLAGS)'
   php_c_post=
@@ -13867,14 +14099,11 @@ if test "$PHP_WEBJAMES" != "no"; then
   shared_cxx_post=
   shared_lo=lo
 
-  php_build_target=program
-
   OVERALL_TARGET=libphp$PHP_MAJOR_VERSION.la
-  php_build_target=static
+  php_sapi_module=static
 ;;
   shared) 
   
-  OVERALL_TARGET=php
   php_c_pre='$(LIBTOOL) --mode=compile $(CC)'
   php_c_meta='$(COMMON_FLAGS) $(CFLAGS_CLEAN) $(EXTRA_CFLAGS)'
   php_c_post=
@@ -13896,10 +14125,8 @@ if test "$PHP_WEBJAMES" != "no"; then
   shared_cxx_post=
   shared_lo=lo
 
-  php_build_target=program
-
   OVERALL_TARGET=libphp$PHP_MAJOR_VERSION.la
-  php_build_target=shared
+  php_sapi_module=shared
   
   php_c_pre=$shared_c_pre
   php_c_meta=$shared_c_meta
@@ -13911,7 +14138,6 @@ if test "$PHP_WEBJAMES" != "no"; then
 ;;
   bundle) 
   
-  OVERALL_TARGET=php
   php_c_pre='$(LIBTOOL) --mode=compile $(CC)'
   php_c_meta='$(COMMON_FLAGS) $(CFLAGS_CLEAN) $(EXTRA_CFLAGS)'
   php_c_post=
@@ -13933,13 +14159,10 @@ if test "$PHP_WEBJAMES" != "no"; then
   shared_cxx_post=
   shared_lo=lo
 
-  php_build_target=program
-
   OVERALL_TARGET=libs/libphp$PHP_MAJOR_VERSION.bundle
-  php_build_target=static
+  php_sapi_module=static
 ;;
   program) 
-  OVERALL_TARGET=php
   php_c_pre='$(LIBTOOL) --mode=compile $(CC)'
   php_c_meta='$(COMMON_FLAGS) $(CFLAGS_CLEAN) $(EXTRA_CFLAGS)'
   php_c_post=
@@ -13960,12 +14183,12 @@ if test "$PHP_WEBJAMES" != "no"; then
   shared_cxx_meta='$(COMMON_FLAGS) $(CXXFLAGS_CLEAN) $(EXTRA_CXXFLAGS) '$pic_setting
   shared_cxx_post=
   shared_lo=lo
-
-  php_build_target=program
 ;;
   esac
     
   
+    install_sapi="install-sapi"
+    
   
   case sapi/webjames in
   "") ac_srcdir="$abs_srcdir/"; unset ac_bdir; ac_inc="-I. -I$abs_srcdir" ;;
@@ -14008,6 +14231,7 @@ EOF
   done
 
 
+  
 
   echo "$ac_t""yes, using $PHP_WEBJAMES" 1>&6
 else
@@ -14037,16 +14261,14 @@ ext_output=$PHP_CGI
 
 
 
-if test "$PHP_SAPI" = "default"; then
-  echo $ac_n "checking whether to build CGI binary""... $ac_c" 1>&6
-echo "configure:14043: checking whether to build CGI binary" >&5
-  if test "$PHP_CGI" != "no"; then
+echo $ac_n "checking for CGI build""... $ac_c" 1>&6
+echo "configure:14271: checking for CGI build" >&5
+if test "$PHP_CGI" != "no"; then
     echo "$ac_t""yes" 1>&6
-
     echo $ac_n "checking for socklen_t in sys/socket.h""... $ac_c" 1>&6
-echo "configure:14048: checking for socklen_t in sys/socket.h" >&5
+echo "configure:14275: checking for socklen_t in sys/socket.h" >&5
     cat > conftest.$ac_ext <<EOF
-#line 14050 "configure"
+#line 14277 "configure"
 #include "confdefs.h"
 #include <sys/socket.h>
 EOF
@@ -14066,9 +14288,9 @@ rm -f conftest*
 
 
     echo $ac_n "checking for sun_len in sys/un.h""... $ac_c" 1>&6
-echo "configure:14070: checking for sun_len in sys/un.h" >&5
+echo "configure:14297: checking for sun_len in sys/un.h" >&5
     cat > conftest.$ac_ext <<EOF
-#line 14072 "configure"
+#line 14299 "configure"
 #include "confdefs.h"
 #include <sys/un.h>
 EOF
@@ -14088,7 +14310,7 @@ rm -f conftest*
 
 
     echo $ac_n "checking whether cross-process locking is required by accept()""... $ac_c" 1>&6
-echo "configure:14092: checking whether cross-process locking is required by accept()" >&5
+echo "configure:14319: checking whether cross-process locking is required by accept()" >&5
     case "`uname -sr`" in
       IRIX\ 5.* | SunOS\ 5.* | UNIX_System_V\ 4.0)	
         echo "$ac_t""yes" 1>&6
@@ -14117,29 +14339,34 @@ EOF
         SAPI_CGI_PATH=sapi/cgi/php-cgi
         ;;
     esac
-    
-  PHP_VAR_SUBST="$PHP_VAR_SUBST SAPI_CGI_PATH"
-
 
-        INSTALL_IT="@echo \"Installing PHP CGI binary: \$(INSTALL_ROOT)\$(bindir)/\"; \$(INSTALL) -m 0755 \$(SAPI_CGI_PATH) \$(INSTALL_ROOT)\$(bindir)/\$(program_prefix)php-cgi\$(program_suffix)\$(EXEEXT)"
-    
-  if test "$PHP_SAPI" != "default"; then
-{ echo "configure: error: 
+        
+  if test "program" = "program"; then
+    PHP_BINARIES="$PHP_BINARIES cgi"
+  elif test "$PHP_SAPI" != "none"; then
+    { echo "configure: error: 
 +--------------------------------------------------------------------+
 |                        *** ATTENTION ***                           |
 |                                                                    |
 | You've configured multiple SAPIs to be build. You can build only   |
-| one SAPI module and CLI binary at the same time.                   |
+| one SAPI module plus CGI, CLI and FPM binaries at the same time.   |
 +--------------------------------------------------------------------+
 " 1>&2; exit 1; }
+  else
+    PHP_SAPI=cgi
   fi
 
-  PHP_SAPI=cgi
   
+  
+    BUILD_DIR="$BUILD_DIR sapi/cgi"
+  
+
+
+  PHP_INSTALLED_SAPIS="$PHP_INSTALLED_SAPIS cgi"
+
   case "program" in
   static) 
   
-  OVERALL_TARGET=php
   php_c_pre='$(LIBTOOL) --mode=compile $(CC)'
   php_c_meta='$(COMMON_FLAGS) $(CFLAGS_CLEAN) $(EXTRA_CFLAGS)'
   php_c_post=
@@ -14161,14 +14388,11 @@ EOF
   shared_cxx_post=
   shared_lo=lo
 
-  php_build_target=program
-
   OVERALL_TARGET=libphp$PHP_MAJOR_VERSION.la
-  php_build_target=static
+  php_sapi_module=static
 ;;
   shared) 
   
-  OVERALL_TARGET=php
   php_c_pre='$(LIBTOOL) --mode=compile $(CC)'
   php_c_meta='$(COMMON_FLAGS) $(CFLAGS_CLEAN) $(EXTRA_CFLAGS)'
   php_c_post=
@@ -14190,10 +14414,8 @@ EOF
   shared_cxx_post=
   shared_lo=lo
 
-  php_build_target=program
-
   OVERALL_TARGET=libphp$PHP_MAJOR_VERSION.la
-  php_build_target=shared
+  php_sapi_module=shared
   
   php_c_pre=$shared_c_pre
   php_c_meta=$shared_c_meta
@@ -14205,7 +14427,6 @@ EOF
 ;;
   bundle) 
   
-  OVERALL_TARGET=php
   php_c_pre='$(LIBTOOL) --mode=compile $(CC)'
   php_c_meta='$(COMMON_FLAGS) $(CFLAGS_CLEAN) $(EXTRA_CFLAGS)'
   php_c_post=
@@ -14227,13 +14448,10 @@ EOF
   shared_cxx_post=
   shared_lo=lo
 
-  php_build_target=program
-
   OVERALL_TARGET=libs/libphp$PHP_MAJOR_VERSION.bundle
-  php_build_target=static
+  php_sapi_module=static
 ;;
   program) 
-  OVERALL_TARGET='$(SAPI_CGI_PATH)'
   php_c_pre='$(LIBTOOL) --mode=compile $(CC)'
   php_c_meta='$(COMMON_FLAGS) $(CFLAGS_CLEAN) $(EXTRA_CFLAGS)'
   php_c_post=
@@ -14254,13 +14472,16 @@ EOF
   shared_cxx_meta='$(COMMON_FLAGS) $(CXXFLAGS_CLEAN) $(EXTRA_CXXFLAGS) '$pic_setting
   shared_cxx_post=
   shared_lo=lo
-
-  php_build_target=program
 ;;
   esac
     
   
-  
+    install_binaries="install-binaries"
+    install_binary_targets="$install_binary_targets install-cgi"
+    
+  PHP_VAR_SUBST="$PHP_VAR_SUBST PHP_CGI_OBJS"
+
+    
   case sapi/cgi in
   "") ac_srcdir="$abs_srcdir/"; unset ac_bdir; ac_inc="-I. -I$abs_srcdir" ;;
   /*) ac_srcdir=`echo "sapi/cgi"|cut -c 2-`"/"; ac_bdir=$ac_srcdir; ac_inc="-I$ac_bdir -I$abs_srcdir/$ac_bdir" ;;
@@ -14286,7 +14507,7 @@ EOF
       ac_obj=$1
       IFS=$old_IFS
       
-      PHP_SAPI_OBJS="$PHP_SAPI_OBJS $ac_bdir$ac_obj.lo"
+      PHP_CGI_OBJS="$PHP_CGI_OBJS $ac_bdir$ac_obj.lo"
 
       case $ac_src in
         *.c) ac_comp="$b_c_pre  $ac_inc $b_c_meta -c $ac_srcdir$ac_src -o $ac_bdir$ac_obj.$b_lo $b_c_post" ;;
@@ -14301,40 +14522,53 @@ $ac_bdir$ac_obj.lo: $ac_srcdir$ac_src
 EOF
   done
 
-
+  
 
 
     case $host_alias in
       *aix*)
-        BUILD_CGI="echo '\#! .' > php.sym && echo >>php.sym && nm -BCpg \`echo \$(PHP_GLOBAL_OBJS) \$(PHP_SAPI_OBJS) | sed 's/\([A-Za-z0-9_]*\)\.lo/\1.o/g'\` | \$(AWK) '{ if (((\$\$2 == \"T\") || (\$\$2 == \"D\") || (\$\$2 == \"B\")) && (substr(\$\$3,1,1) != \".\")) { print \$\$3 } }' | sort -u >> php.sym && \$(LIBTOOL) --mode=link \$(CC) -export-dynamic \$(CFLAGS_CLEAN) \$(EXTRA_CFLAGS) \$(EXTRA_LDFLAGS_PROGRAM) \$(LDFLAGS) -Wl,-brtl -Wl,-bE:php.sym \$(PHP_RPATHS) \$(PHP_GLOBAL_OBJS) \$(PHP_SAPI_OBJS) \$(EXTRA_LIBS) \$(ZEND_EXTRA_LIBS) -o \$(SAPI_CGI_PATH)"
+        if test "$php_sapi_module" = "shared"; then
+          BUILD_CGI="echo '\#! .' > php.sym && echo >>php.sym && nm -BCpg \`echo \$(PHP_GLOBAL_OBJS) \$(PHP_BINARY_OBJS) \$(PHP_CGI_OBJS) | sed 's/\([A-Za-z0-9_]*\)\.lo/.libs\/\1.o/g'\` | \$(AWK) '{ if (((\$\$2 == \"T\") || (\$\$2 == \"D\") || (\$\$2 == \"B\")) && (substr(\$\$3,1,1) != \".\")) { print \$\$3 } }' | sort -u >> php.sym && \$(LIBTOOL) --mode=link \$(CC) -export-dynamic \$(CFLAGS_CLEAN) \$(EXTRA_CFLAGS) \$(EXTRA_LDFLAGS_PROGRAM) \$(LDFLAGS) -Wl,-brtl -Wl,-bE:php.sym \$(PHP_RPATHS) \$(PHP_GLOBAL_OBJS) \$(PHP_CGI_OBJS) \$(EXTRA_LIBS) \$(ZEND_EXTRA_LIBS) -o \$(SAPI_CGI_PATH)"
+        else
+          BUILD_CGI="echo '\#! .' > php.sym && echo >>php.sym && nm -BCpg \`echo \$(PHP_GLOBAL_OBJS) \$(PHP_BINARY_OBJS) \$(PHP_CGI_OBJS) | sed 's/\([A-Za-z0-9_]*\)\.lo/\1.o/g'\` | \$(AWK) '{ if (((\$\$2 == \"T\") || (\$\$2 == \"D\") || (\$\$2 == \"B\")) && (substr(\$\$3,1,1) != \".\")) { print \$\$3 } }' | sort -u >> php.sym && \$(LIBTOOL) --mode=link \$(CC) -export-dynamic \$(CFLAGS_CLEAN) \$(EXTRA_CFLAGS) \$(EXTRA_LDFLAGS_PROGRAM) \$(LDFLAGS) -Wl,-brtl -Wl,-bE:php.sym \$(PHP_RPATHS) \$(PHP_GLOBAL_OBJS) \$(PHP_CGI_OBJS) \$(EXTRA_LIBS) \$(ZEND_EXTRA_LIBS) -o \$(SAPI_CGI_PATH)"
+        fi
         ;;
       *darwin*)
-        BUILD_CGI="\$(CC) \$(CFLAGS_CLEAN) \$(EXTRA_CFLAGS) \$(EXTRA_LDFLAGS_PROGRAM) \$(LDFLAGS) \$(NATIVE_RPATHS) \$(PHP_GLOBAL_OBJS:.lo=.o) \$(PHP_SAPI_OBJS:.lo=.o) \$(PHP_FRAMEWORKS) \$(EXTRA_LIBS) \$(ZEND_EXTRA_LIBS) -o \$(SAPI_CGI_PATH)"
+        BUILD_CGI="\$(CC) \$(CFLAGS_CLEAN) \$(EXTRA_CFLAGS) \$(EXTRA_LDFLAGS_PROGRAM) \$(LDFLAGS) \$(NATIVE_RPATHS) \$(PHP_GLOBAL_OBJS:.lo=.o) \$(PHP_BINARY_OBJS:.lo=.o) \$(PHP_CGI_OBJS:.lo=.o) \$(PHP_FRAMEWORKS) \$(EXTRA_LIBS) \$(ZEND_EXTRA_LIBS) -o \$(SAPI_CGI_PATH)"
       ;;
       *)
-        BUILD_CGI="\$(LIBTOOL) --mode=link \$(CC) -export-dynamic \$(CFLAGS_CLEAN) \$(EXTRA_CFLAGS) \$(EXTRA_LDFLAGS_PROGRAM) \$(LDFLAGS) \$(PHP_RPATHS) \$(PHP_GLOBAL_OBJS) \$(PHP_SAPI_OBJS) \$(EXTRA_LIBS) \$(ZEND_EXTRA_LIBS) -o \$(SAPI_CGI_PATH)"
+        BUILD_CGI="\$(LIBTOOL) --mode=link \$(CC) -export-dynamic \$(CFLAGS_CLEAN) \$(EXTRA_CFLAGS) \$(EXTRA_LDFLAGS_PROGRAM) \$(LDFLAGS) \$(PHP_RPATHS) \$(PHP_GLOBAL_OBJS) \$(PHP_BINARY_OBJS) \$(PHP_CGI_OBJS) \$(EXTRA_LIBS) \$(ZEND_EXTRA_LIBS) -o \$(SAPI_CGI_PATH)"
       ;;
     esac
 
+        
+  PHP_VAR_SUBST="$PHP_VAR_SUBST SAPI_CGI_PATH"
+
     
   PHP_VAR_SUBST="$PHP_VAR_SUBST BUILD_CGI"
 
-
-  elif test "$PHP_CLI" != "no"; then
-    echo "$ac_t""no" 1>&6
-    OVERALL_TARGET=
-    PHP_SAPI=cli   
-  else
-    { echo "configure: error: No SAPIs selected." 1>&2; exit 1; }  
-  fi
+else
+  echo "$ac_t""yes" 1>&6
 fi
 
 
 
 echo $ac_n "checking for chosen SAPI module""... $ac_c" 1>&6
-echo "configure:14336: checking for chosen SAPI module" >&5
+echo "configure:14563: checking for chosen SAPI module" >&5
 echo "$ac_t""$PHP_SAPI" 1>&6
 
+echo $ac_n "checking for executable SAPI binaries""... $ac_c" 1>&6
+echo "configure:14567: checking for executable SAPI binaries" >&5
+if test "$PHP_BINARIES"; then
+  echo "$ac_t""$PHP_BINARIES" 1>&6
+else
+  echo "$ac_t""none" 1>&6
+fi
+
+if test -z "$PHP_INSTALLED_SAPIS"; then
+  { echo "configure: error: Nothing to build." 1>&2; exit 1; }
+fi
+
 if test "$enable_maintainer_zts" = "yes"; then
   
 if test -n "$ac_cv_pthreads_lib"; then
@@ -14389,7 +14623,7 @@ fi
   # Extract the first word of "sendmail", so it can be a program name with args.
 set dummy sendmail; ac_word=$2
 echo $ac_n "checking for $ac_word""... $ac_c" 1>&6
-echo "configure:14393: checking for $ac_word" >&5
+echo "configure:14632: checking for $ac_word" >&5
 if eval "test \"`echo '$''{'ac_cv_path_PROG_SENDMAIL'+set}'`\" = set"; then
   echo $ac_n "(cached) $ac_c" 1>&6
 else
@@ -14428,7 +14662,7 @@ fi
 
 
   echo $ac_n "checking whether system uses EBCDIC""... $ac_c" 1>&6
-echo "configure:14432: checking whether system uses EBCDIC" >&5
+echo "configure:14671: checking whether system uses EBCDIC" >&5
 if eval "test \"`echo '$''{'ac_cv_ebcdic'+set}'`\" = set"; then
   echo $ac_n "(cached) $ac_c" 1>&6
 else
@@ -14439,7 +14673,7 @@ else
 
 else
   cat > conftest.$ac_ext <<EOF
-#line 14443 "configure"
+#line 14682 "configure"
 #include "confdefs.h"
 
 int main(void) { 
@@ -14447,7 +14681,7 @@ int main(void) {
 } 
 
 EOF
-if { (eval echo configure:14451: \"$ac_link\") 1>&5; (eval $ac_link) 2>&5; } && test -s conftest${ac_exeext} && (./conftest; exit) 2>/dev/null
+if { (eval echo configure:14690: \"$ac_link\") 1>&5; (eval $ac_link) 2>&5; } && test -s conftest${ac_exeext} && (./conftest; exit) 2>/dev/null
 then
   
   ac_cv_ebcdic=yes
@@ -14475,7 +14709,7 @@ EOF
 
 
 echo $ac_n "checking whether byte ordering is bigendian""... $ac_c" 1>&6
-echo "configure:14479: checking whether byte ordering is bigendian" >&5
+echo "configure:14718: checking whether byte ordering is bigendian" >&5
 if eval "test \"`echo '$''{'ac_cv_c_bigendian_php'+set}'`\" = set"; then
   echo $ac_n "(cached) $ac_c" 1>&6
 else
@@ -14485,7 +14719,7 @@ else
   ac_cv_c_bigendian_php=unknown
 else
   cat > conftest.$ac_ext <<EOF
-#line 14489 "configure"
+#line 14728 "configure"
 #include "confdefs.h"
 
 int main(void)
@@ -14501,7 +14735,7 @@ int main(void)
 }
   
 EOF
-if { (eval echo configure:14505: \"$ac_link\") 1>&5; (eval $ac_link) 2>&5; } && test -s conftest${ac_exeext} && (./conftest; exit) 2>/dev/null
+if { (eval echo configure:14744: \"$ac_link\") 1>&5; (eval $ac_link) 2>&5; } && test -s conftest${ac_exeext} && (./conftest; exit) 2>/dev/null
 then
   ac_cv_c_bigendian_php=yes
 else
@@ -14527,7 +14761,7 @@ EOF
 
 
   echo $ac_n "checking whether writing to stdout works""... $ac_c" 1>&6
-echo "configure:14531: checking whether writing to stdout works" >&5
+echo "configure:14770: checking whether writing to stdout works" >&5
 if eval "test \"`echo '$''{'ac_cv_write_stdout'+set}'`\" = set"; then
   echo $ac_n "(cached) $ac_c" 1>&6
 else
@@ -14538,7 +14772,7 @@ else
     
 else
   cat > conftest.$ac_ext <<EOF
-#line 14542 "configure"
+#line 14781 "configure"
 #include "confdefs.h"
 
 #ifdef HAVE_UNISTD_H
@@ -14556,7 +14790,7 @@ main()
 }
     
 EOF
-if { (eval echo configure:14560: \"$ac_link\") 1>&5; (eval $ac_link) 2>&5; } && test -s conftest${ac_exeext} && (./conftest; exit) 2>/dev/null
+if { (eval echo configure:14799: \"$ac_link\") 1>&5; (eval $ac_link) 2>&5; } && test -s conftest${ac_exeext} && (./conftest; exit) 2>/dev/null
 then
   
       ac_cv_write_stdout=yes
@@ -14631,12 +14865,12 @@ test -d /usr/ucblib &&
   unset found
   
   echo $ac_n "checking for socket""... $ac_c" 1>&6
-echo "configure:14635: checking for socket" >&5
+echo "configure:14874: checking for socket" >&5
 if eval "test \"`echo '$''{'ac_cv_func_socket'+set}'`\" = set"; then
   echo $ac_n "(cached) $ac_c" 1>&6
 else
   cat > conftest.$ac_ext <<EOF
-#line 14640 "configure"
+#line 14879 "configure"
 #include "confdefs.h"
 /* System header to define __stub macros and hopefully few prototypes,
     which can conflict with char socket(); below.  */
@@ -14659,7 +14893,7 @@ socket();
 
 ; return 0; }
 EOF
-if { (eval echo configure:14663: \"$ac_link\") 1>&5; (eval $ac_link) 2>&5; } && test -s conftest${ac_exeext}; then
+if { (eval echo configure:14902: \"$ac_link\") 1>&5; (eval $ac_link) 2>&5; } && test -s conftest${ac_exeext}; then
   rm -rf conftest*
   eval "ac_cv_func_socket=yes"
 else
@@ -14677,12 +14911,12 @@ if eval "test \"`echo '$ac_cv_func_'socket`\" = yes"; then
 else
   echo "$ac_t""no" 1>&6
  echo $ac_n "checking for __socket""... $ac_c" 1>&6
-echo "configure:14681: checking for __socket" >&5
+echo "configure:14920: checking for __socket" >&5
 if eval "test \"`echo '$''{'ac_cv_func___socket'+set}'`\" = set"; then
   echo $ac_n "(cached) $ac_c" 1>&6
 else
   cat > conftest.$ac_ext <<EOF
-#line 14686 "configure"
+#line 14925 "configure"
 #include "confdefs.h"
 /* System header to define __stub macros and hopefully few prototypes,
     which can conflict with char __socket(); below.  */
@@ -14705,7 +14939,7 @@ __socket();
 
 ; return 0; }
 EOF
-if { (eval echo configure:14709: \"$ac_link\") 1>&5; (eval $ac_link) 2>&5; } && test -s conftest${ac_exeext}; then
+if { (eval echo configure:14948: \"$ac_link\") 1>&5; (eval $ac_link) 2>&5; } && test -s conftest${ac_exeext}; then
   rm -rf conftest*
   eval "ac_cv_func___socket=yes"
 else
@@ -14743,7 +14977,7 @@ EOF
   unset ac_cv_lib_socket___socket
   unset found
   echo $ac_n "checking for socket in -lsocket""... $ac_c" 1>&6
-echo "configure:14747: checking for socket in -lsocket" >&5
+echo "configure:14986: checking for socket in -lsocket" >&5
 ac_lib_var=`echo socket'_'socket | sed 'y%./+-%__p_%'`
 if eval "test \"`echo '$''{'ac_cv_lib_$ac_lib_var'+set}'`\" = set"; then
   echo $ac_n "(cached) $ac_c" 1>&6
@@ -14751,7 +14985,7 @@ else
   ac_save_LIBS="$LIBS"
 LIBS="-lsocket  $LIBS"
 cat > conftest.$ac_ext <<EOF
-#line 14755 "configure"
+#line 14994 "configure"
 #include "confdefs.h"
 /* Override any gcc2 internal prototype to avoid an error.  */
 /* We use char because int might match the return type of a gcc2
@@ -14762,7 +14996,7 @@ int main() {
 socket()
 ; return 0; }
 EOF
-if { (eval echo configure:14766: \"$ac_link\") 1>&5; (eval $ac_link) 2>&5; } && test -s conftest${ac_exeext}; then
+if { (eval echo configure:15005: \"$ac_link\") 1>&5; (eval $ac_link) 2>&5; } && test -s conftest${ac_exeext}; then
   rm -rf conftest*
   eval "ac_cv_lib_$ac_lib_var=yes"
 else
@@ -14782,7 +15016,7 @@ else
   echo "$ac_t""no" 1>&6
 
     echo $ac_n "checking for __socket in -lsocket""... $ac_c" 1>&6
-echo "configure:14786: checking for __socket in -lsocket" >&5
+echo "configure:15025: checking for __socket in -lsocket" >&5
 ac_lib_var=`echo socket'_'__socket | sed 'y%./+-%__p_%'`
 if eval "test \"`echo '$''{'ac_cv_lib_$ac_lib_var'+set}'`\" = set"; then
   echo $ac_n "(cached) $ac_c" 1>&6
@@ -14790,7 +15024,7 @@ else
   ac_save_LIBS="$LIBS"
 LIBS="-lsocket  $LIBS"
 cat > conftest.$ac_ext <<EOF
-#line 14794 "configure"
+#line 15033 "configure"
 #include "confdefs.h"
 /* Override any gcc2 internal prototype to avoid an error.  */
 /* We use char because int might match the return type of a gcc2
@@ -14801,7 +15035,7 @@ int main() {
 __socket()
 ; return 0; }
 EOF
-if { (eval echo configure:14805: \"$ac_link\") 1>&5; (eval $ac_link) 2>&5; } && test -s conftest${ac_exeext}; then
+if { (eval echo configure:15044: \"$ac_link\") 1>&5; (eval $ac_link) 2>&5; } && test -s conftest${ac_exeext}; then
   rm -rf conftest*
   eval "ac_cv_lib_$ac_lib_var=yes"
 else
@@ -14833,11 +15067,11 @@ fi
   found=no
 else
   cat > conftest.$ac_ext <<EOF
-#line 14837 "configure"
+#line 15076 "configure"
 #include "confdefs.h"
 main() { return (0); }
 EOF
-if { (eval echo configure:14841: \"$ac_link\") 1>&5; (eval $ac_link) 2>&5; } && test -s conftest${ac_exeext} && (./conftest; exit) 2>/dev/null
+if { (eval echo configure:15080: \"$ac_link\") 1>&5; (eval $ac_link) 2>&5; } && test -s conftest${ac_exeext} && (./conftest; exit) 2>/dev/null
 then
   found=yes
 else
@@ -14888,12 +15122,12 @@ EOF
   unset found
   
   echo $ac_n "checking for socketpair""... $ac_c" 1>&6
-echo "configure:14892: checking for socketpair" >&5
+echo "configure:15131: checking for socketpair" >&5
 if eval "test \"`echo '$''{'ac_cv_func_socketpair'+set}'`\" = set"; then
   echo $ac_n "(cached) $ac_c" 1>&6
 else
   cat > conftest.$ac_ext <<EOF
-#line 14897 "configure"
+#line 15136 "configure"
 #include "confdefs.h"
 /* System header to define __stub macros and hopefully few prototypes,
     which can conflict with char socketpair(); below.  */
@@ -14916,7 +15150,7 @@ socketpair();
 
 ; return 0; }
 EOF
-if { (eval echo configure:14920: \"$ac_link\") 1>&5; (eval $ac_link) 2>&5; } && test -s conftest${ac_exeext}; then
+if { (eval echo configure:15159: \"$ac_link\") 1>&5; (eval $ac_link) 2>&5; } && test -s conftest${ac_exeext}; then
   rm -rf conftest*
   eval "ac_cv_func_socketpair=yes"
 else
@@ -14934,12 +15168,12 @@ if eval "test \"`echo '$ac_cv_func_'socketpair`\" = yes"; then
 else
   echo "$ac_t""no" 1>&6
  echo $ac_n "checking for __socketpair""... $ac_c" 1>&6
-echo "configure:14938: checking for __socketpair" >&5
+echo "configure:15177: checking for __socketpair" >&5
 if eval "test \"`echo '$''{'ac_cv_func___socketpair'+set}'`\" = set"; then
   echo $ac_n "(cached) $ac_c" 1>&6
 else
   cat > conftest.$ac_ext <<EOF
-#line 14943 "configure"
+#line 15182 "configure"
 #include "confdefs.h"
 /* System header to define __stub macros and hopefully few prototypes,
     which can conflict with char __socketpair(); below.  */
@@ -14962,7 +15196,7 @@ __socketpair();
 
 ; return 0; }
 EOF
-if { (eval echo configure:14966: \"$ac_link\") 1>&5; (eval $ac_link) 2>&5; } && test -s conftest${ac_exeext}; then
+if { (eval echo configure:15205: \"$ac_link\") 1>&5; (eval $ac_link) 2>&5; } && test -s conftest${ac_exeext}; then
   rm -rf conftest*
   eval "ac_cv_func___socketpair=yes"
 else
@@ -15000,7 +15234,7 @@ EOF
   unset ac_cv_lib_socket___socketpair
   unset found
   echo $ac_n "checking for socketpair in -lsocket""... $ac_c" 1>&6
-echo "configure:15004: checking for socketpair in -lsocket" >&5
+echo "configure:15243: checking for socketpair in -lsocket" >&5
 ac_lib_var=`echo socket'_'socketpair | sed 'y%./+-%__p_%'`
 if eval "test \"`echo '$''{'ac_cv_lib_$ac_lib_var'+set}'`\" = set"; then
   echo $ac_n "(cached) $ac_c" 1>&6
@@ -15008,7 +15242,7 @@ else
   ac_save_LIBS="$LIBS"
 LIBS="-lsocket  $LIBS"
 cat > conftest.$ac_ext <<EOF
-#line 15012 "configure"
+#line 15251 "configure"
 #include "confdefs.h"
 /* Override any gcc2 internal prototype to avoid an error.  */
 /* We use char because int might match the return type of a gcc2
@@ -15019,7 +15253,7 @@ int main() {
 socketpair()
 ; return 0; }
 EOF
-if { (eval echo configure:15023: \"$ac_link\") 1>&5; (eval $ac_link) 2>&5; } && test -s conftest${ac_exeext}; then
+if { (eval echo configure:15262: \"$ac_link\") 1>&5; (eval $ac_link) 2>&5; } && test -s conftest${ac_exeext}; then
   rm -rf conftest*
   eval "ac_cv_lib_$ac_lib_var=yes"
 else
@@ -15039,7 +15273,7 @@ else
   echo "$ac_t""no" 1>&6
 
     echo $ac_n "checking for __socketpair in -lsocket""... $ac_c" 1>&6
-echo "configure:15043: checking for __socketpair in -lsocket" >&5
+echo "configure:15282: checking for __socketpair in -lsocket" >&5
 ac_lib_var=`echo socket'_'__socketpair | sed 'y%./+-%__p_%'`
 if eval "test \"`echo '$''{'ac_cv_lib_$ac_lib_var'+set}'`\" = set"; then
   echo $ac_n "(cached) $ac_c" 1>&6
@@ -15047,7 +15281,7 @@ else
   ac_save_LIBS="$LIBS"
 LIBS="-lsocket  $LIBS"
 cat > conftest.$ac_ext <<EOF
-#line 15051 "configure"
+#line 15290 "configure"
 #include "confdefs.h"
 /* Override any gcc2 internal prototype to avoid an error.  */
 /* We use char because int might match the return type of a gcc2
@@ -15058,7 +15292,7 @@ int main() {
 __socketpair()
 ; return 0; }
 EOF
-if { (eval echo configure:15062: \"$ac_link\") 1>&5; (eval $ac_link) 2>&5; } && test -s conftest${ac_exeext}; then
+if { (eval echo configure:15301: \"$ac_link\") 1>&5; (eval $ac_link) 2>&5; } && test -s conftest${ac_exeext}; then
   rm -rf conftest*
   eval "ac_cv_lib_$ac_lib_var=yes"
 else
@@ -15090,11 +15324,11 @@ fi
   found=no
 else
   cat > conftest.$ac_ext <<EOF
-#line 15094 "configure"
+#line 15333 "configure"
 #include "confdefs.h"
 main() { return (0); }
 EOF
-if { (eval echo configure:15098: \"$ac_link\") 1>&5; (eval $ac_link) 2>&5; } && test -s conftest${ac_exeext} && (./conftest; exit) 2>/dev/null
+if { (eval echo configure:15337: \"$ac_link\") 1>&5; (eval $ac_link) 2>&5; } && test -s conftest${ac_exeext} && (./conftest; exit) 2>/dev/null
 then
   found=yes
 else
@@ -15145,12 +15379,12 @@ EOF
   unset found
   
   echo $ac_n "checking for htonl""... $ac_c" 1>&6
-echo "configure:15149: checking for htonl" >&5
+echo "configure:15388: checking for htonl" >&5
 if eval "test \"`echo '$''{'ac_cv_func_htonl'+set}'`\" = set"; then
   echo $ac_n "(cached) $ac_c" 1>&6
 else
   cat > conftest.$ac_ext <<EOF
-#line 15154 "configure"
+#line 15393 "configure"
 #include "confdefs.h"
 /* System header to define __stub macros and hopefully few prototypes,
     which can conflict with char htonl(); below.  */
@@ -15173,7 +15407,7 @@ htonl();
 
 ; return 0; }
 EOF
-if { (eval echo configure:15177: \"$ac_link\") 1>&5; (eval $ac_link) 2>&5; } && test -s conftest${ac_exeext}; then
+if { (eval echo configure:15416: \"$ac_link\") 1>&5; (eval $ac_link) 2>&5; } && test -s conftest${ac_exeext}; then
   rm -rf conftest*
   eval "ac_cv_func_htonl=yes"
 else
@@ -15191,12 +15425,12 @@ if eval "test \"`echo '$ac_cv_func_'htonl`\" = yes"; then
 else
   echo "$ac_t""no" 1>&6
  echo $ac_n "checking for __htonl""... $ac_c" 1>&6
-echo "configure:15195: checking for __htonl" >&5
+echo "configure:15434: checking for __htonl" >&5
 if eval "test \"`echo '$''{'ac_cv_func___htonl'+set}'`\" = set"; then
   echo $ac_n "(cached) $ac_c" 1>&6
 else
   cat > conftest.$ac_ext <<EOF
-#line 15200 "configure"
+#line 15439 "configure"
 #include "confdefs.h"
 /* System header to define __stub macros and hopefully few prototypes,
     which can conflict with char __htonl(); below.  */
@@ -15219,7 +15453,7 @@ __htonl();
 
 ; return 0; }
 EOF
-if { (eval echo configure:15223: \"$ac_link\") 1>&5; (eval $ac_link) 2>&5; } && test -s conftest${ac_exeext}; then
+if { (eval echo configure:15462: \"$ac_link\") 1>&5; (eval $ac_link) 2>&5; } && test -s conftest${ac_exeext}; then
   rm -rf conftest*
   eval "ac_cv_func___htonl=yes"
 else
@@ -15257,7 +15491,7 @@ EOF
   unset ac_cv_lib_socket___htonl
   unset found
   echo $ac_n "checking for htonl in -lsocket""... $ac_c" 1>&6
-echo "configure:15261: checking for htonl in -lsocket" >&5
+echo "configure:15500: checking for htonl in -lsocket" >&5
 ac_lib_var=`echo socket'_'htonl | sed 'y%./+-%__p_%'`
 if eval "test \"`echo '$''{'ac_cv_lib_$ac_lib_var'+set}'`\" = set"; then
   echo $ac_n "(cached) $ac_c" 1>&6
@@ -15265,7 +15499,7 @@ else
   ac_save_LIBS="$LIBS"
 LIBS="-lsocket  $LIBS"
 cat > conftest.$ac_ext <<EOF
-#line 15269 "configure"
+#line 15508 "configure"
 #include "confdefs.h"
 /* Override any gcc2 internal prototype to avoid an error.  */
 /* We use char because int might match the return type of a gcc2
@@ -15276,7 +15510,7 @@ int main() {
 htonl()
 ; return 0; }
 EOF
-if { (eval echo configure:15280: \"$ac_link\") 1>&5; (eval $ac_link) 2>&5; } && test -s conftest${ac_exeext}; then
+if { (eval echo configure:15519: \"$ac_link\") 1>&5; (eval $ac_link) 2>&5; } && test -s conftest${ac_exeext}; then
   rm -rf conftest*
   eval "ac_cv_lib_$ac_lib_var=yes"
 else
@@ -15296,7 +15530,7 @@ else
   echo "$ac_t""no" 1>&6
 
     echo $ac_n "checking for __htonl in -lsocket""... $ac_c" 1>&6
-echo "configure:15300: checking for __htonl in -lsocket" >&5
+echo "configure:15539: checking for __htonl in -lsocket" >&5
 ac_lib_var=`echo socket'_'__htonl | sed 'y%./+-%__p_%'`
 if eval "test \"`echo '$''{'ac_cv_lib_$ac_lib_var'+set}'`\" = set"; then
   echo $ac_n "(cached) $ac_c" 1>&6
@@ -15304,7 +15538,7 @@ else
   ac_save_LIBS="$LIBS"
 LIBS="-lsocket  $LIBS"
 cat > conftest.$ac_ext <<EOF
-#line 15308 "configure"
+#line 15547 "configure"
 #include "confdefs.h"
 /* Override any gcc2 internal prototype to avoid an error.  */
 /* We use char because int might match the return type of a gcc2
@@ -15315,7 +15549,7 @@ int main() {
 __htonl()
 ; return 0; }
 EOF
-if { (eval echo configure:15319: \"$ac_link\") 1>&5; (eval $ac_link) 2>&5; } && test -s conftest${ac_exeext}; then
+if { (eval echo configure:15558: \"$ac_link\") 1>&5; (eval $ac_link) 2>&5; } && test -s conftest${ac_exeext}; then
   rm -rf conftest*
   eval "ac_cv_lib_$ac_lib_var=yes"
 else
@@ -15347,11 +15581,11 @@ fi
   found=no
 else
   cat > conftest.$ac_ext <<EOF
-#line 15351 "configure"
+#line 15590 "configure"
 #include "confdefs.h"
 main() { return (0); }
 EOF
-if { (eval echo configure:15355: \"$ac_link\") 1>&5; (eval $ac_link) 2>&5; } && test -s conftest${ac_exeext} && (./conftest; exit) 2>/dev/null
+if { (eval echo configure:15594: \"$ac_link\") 1>&5; (eval $ac_link) 2>&5; } && test -s conftest${ac_exeext} && (./conftest; exit) 2>/dev/null
 then
   found=yes
 else
@@ -15402,12 +15636,12 @@ EOF
   unset found
   
   echo $ac_n "checking for gethostname""... $ac_c" 1>&6
-echo "configure:15406: checking for gethostname" >&5
+echo "configure:15645: checking for gethostname" >&5
 if eval "test \"`echo '$''{'ac_cv_func_gethostname'+set}'`\" = set"; then
   echo $ac_n "(cached) $ac_c" 1>&6
 else
   cat > conftest.$ac_ext <<EOF
-#line 15411 "configure"
+#line 15650 "configure"
 #include "confdefs.h"
 /* System header to define __stub macros and hopefully few prototypes,
     which can conflict with char gethostname(); below.  */
@@ -15430,7 +15664,7 @@ gethostname();
 
 ; return 0; }
 EOF
-if { (eval echo configure:15434: \"$ac_link\") 1>&5; (eval $ac_link) 2>&5; } && test -s conftest${ac_exeext}; then
+if { (eval echo configure:15673: \"$ac_link\") 1>&5; (eval $ac_link) 2>&5; } && test -s conftest${ac_exeext}; then
   rm -rf conftest*
   eval "ac_cv_func_gethostname=yes"
 else
@@ -15448,12 +15682,12 @@ if eval "test \"`echo '$ac_cv_func_'gethostname`\" = yes"; then
 else
   echo "$ac_t""no" 1>&6
  echo $ac_n "checking for __gethostname""... $ac_c" 1>&6
-echo "configure:15452: checking for __gethostname" >&5
+echo "configure:15691: checking for __gethostname" >&5
 if eval "test \"`echo '$''{'ac_cv_func___gethostname'+set}'`\" = set"; then
   echo $ac_n "(cached) $ac_c" 1>&6
 else
   cat > conftest.$ac_ext <<EOF
-#line 15457 "configure"
+#line 15696 "configure"
 #include "confdefs.h"
 /* System header to define __stub macros and hopefully few prototypes,
     which can conflict with char __gethostname(); below.  */
@@ -15476,7 +15710,7 @@ __gethostname();
 
 ; return 0; }
 EOF
-if { (eval echo configure:15480: \"$ac_link\") 1>&5; (eval $ac_link) 2>&5; } && test -s conftest${ac_exeext}; then
+if { (eval echo configure:15719: \"$ac_link\") 1>&5; (eval $ac_link) 2>&5; } && test -s conftest${ac_exeext}; then
   rm -rf conftest*
   eval "ac_cv_func___gethostname=yes"
 else
@@ -15514,7 +15748,7 @@ EOF
   unset ac_cv_lib_nsl___gethostname
   unset found
   echo $ac_n "checking for gethostname in -lnsl""... $ac_c" 1>&6
-echo "configure:15518: checking for gethostname in -lnsl" >&5
+echo "configure:15757: checking for gethostname in -lnsl" >&5
 ac_lib_var=`echo nsl'_'gethostname | sed 'y%./+-%__p_%'`
 if eval "test \"`echo '$''{'ac_cv_lib_$ac_lib_var'+set}'`\" = set"; then
   echo $ac_n "(cached) $ac_c" 1>&6
@@ -15522,7 +15756,7 @@ else
   ac_save_LIBS="$LIBS"
 LIBS="-lnsl  $LIBS"
 cat > conftest.$ac_ext <<EOF
-#line 15526 "configure"
+#line 15765 "configure"
 #include "confdefs.h"
 /* Override any gcc2 internal prototype to avoid an error.  */
 /* We use char because int might match the return type of a gcc2
@@ -15533,7 +15767,7 @@ int main() {
 gethostname()
 ; return 0; }
 EOF
-if { (eval echo configure:15537: \"$ac_link\") 1>&5; (eval $ac_link) 2>&5; } && test -s conftest${ac_exeext}; then
+if { (eval echo configure:15776: \"$ac_link\") 1>&5; (eval $ac_link) 2>&5; } && test -s conftest${ac_exeext}; then
   rm -rf conftest*
   eval "ac_cv_lib_$ac_lib_var=yes"
 else
@@ -15553,7 +15787,7 @@ else
   echo "$ac_t""no" 1>&6
 
     echo $ac_n "checking for __gethostname in -lnsl""... $ac_c" 1>&6
-echo "configure:15557: checking for __gethostname in -lnsl" >&5
+echo "configure:15796: checking for __gethostname in -lnsl" >&5
 ac_lib_var=`echo nsl'_'__gethostname | sed 'y%./+-%__p_%'`
 if eval "test \"`echo '$''{'ac_cv_lib_$ac_lib_var'+set}'`\" = set"; then
   echo $ac_n "(cached) $ac_c" 1>&6
@@ -15561,7 +15795,7 @@ else
   ac_save_LIBS="$LIBS"
 LIBS="-lnsl  $LIBS"
 cat > conftest.$ac_ext <<EOF
-#line 15565 "configure"
+#line 15804 "configure"
 #include "confdefs.h"
 /* Override any gcc2 internal prototype to avoid an error.  */
 /* We use char because int might match the return type of a gcc2
@@ -15572,7 +15806,7 @@ int main() {
 __gethostname()
 ; return 0; }
 EOF
-if { (eval echo configure:15576: \"$ac_link\") 1>&5; (eval $ac_link) 2>&5; } && test -s conftest${ac_exeext}; then
+if { (eval echo configure:15815: \"$ac_link\") 1>&5; (eval $ac_link) 2>&5; } && test -s conftest${ac_exeext}; then
   rm -rf conftest*
   eval "ac_cv_lib_$ac_lib_var=yes"
 else
@@ -15604,11 +15838,11 @@ fi
   found=no
 else
   cat > conftest.$ac_ext <<EOF
-#line 15608 "configure"
+#line 15847 "configure"
 #include "confdefs.h"
 main() { return (0); }
 EOF
-if { (eval echo configure:15612: \"$ac_link\") 1>&5; (eval $ac_link) 2>&5; } && test -s conftest${ac_exeext} && (./conftest; exit) 2>/dev/null
+if { (eval echo configure:15851: \"$ac_link\") 1>&5; (eval $ac_link) 2>&5; } && test -s conftest${ac_exeext} && (./conftest; exit) 2>/dev/null
 then
   found=yes
 else
@@ -15659,12 +15893,12 @@ EOF
   unset found
   
   echo $ac_n "checking for gethostbyaddr""... $ac_c" 1>&6
-echo "configure:15663: checking for gethostbyaddr" >&5
+echo "configure:15902: checking for gethostbyaddr" >&5
 if eval "test \"`echo '$''{'ac_cv_func_gethostbyaddr'+set}'`\" = set"; then
   echo $ac_n "(cached) $ac_c" 1>&6
 else
   cat > conftest.$ac_ext <<EOF
-#line 15668 "configure"
+#line 15907 "configure"
 #include "confdefs.h"
 /* System header to define __stub macros and hopefully few prototypes,
     which can conflict with char gethostbyaddr(); below.  */
@@ -15687,7 +15921,7 @@ gethostbyaddr();
 
 ; return 0; }
 EOF
-if { (eval echo configure:15691: \"$ac_link\") 1>&5; (eval $ac_link) 2>&5; } && test -s conftest${ac_exeext}; then
+if { (eval echo configure:15930: \"$ac_link\") 1>&5; (eval $ac_link) 2>&5; } && test -s conftest${ac_exeext}; then
   rm -rf conftest*
   eval "ac_cv_func_gethostbyaddr=yes"
 else
@@ -15705,12 +15939,12 @@ if eval "test \"`echo '$ac_cv_func_'gethostbyaddr`\" = yes"; then
 else
   echo "$ac_t""no" 1>&6
  echo $ac_n "checking for __gethostbyaddr""... $ac_c" 1>&6
-echo "configure:15709: checking for __gethostbyaddr" >&5
+echo "configure:15948: checking for __gethostbyaddr" >&5
 if eval "test \"`echo '$''{'ac_cv_func___gethostbyaddr'+set}'`\" = set"; then
   echo $ac_n "(cached) $ac_c" 1>&6
 else
   cat > conftest.$ac_ext <<EOF
-#line 15714 "configure"
+#line 15953 "configure"
 #include "confdefs.h"
 /* System header to define __stub macros and hopefully few prototypes,
     which can conflict with char __gethostbyaddr(); below.  */
@@ -15733,7 +15967,7 @@ __gethostbyaddr();
 
 ; return 0; }
 EOF
-if { (eval echo configure:15737: \"$ac_link\") 1>&5; (eval $ac_link) 2>&5; } && test -s conftest${ac_exeext}; then
+if { (eval echo configure:15976: \"$ac_link\") 1>&5; (eval $ac_link) 2>&5; } && test -s conftest${ac_exeext}; then
   rm -rf conftest*
   eval "ac_cv_func___gethostbyaddr=yes"
 else
@@ -15771,7 +16005,7 @@ EOF
   unset ac_cv_lib_nsl___gethostbyaddr
   unset found
   echo $ac_n "checking for gethostbyaddr in -lnsl""... $ac_c" 1>&6
-echo "configure:15775: checking for gethostbyaddr in -lnsl" >&5
+echo "configure:16014: checking for gethostbyaddr in -lnsl" >&5
 ac_lib_var=`echo nsl'_'gethostbyaddr | sed 'y%./+-%__p_%'`
 if eval "test \"`echo '$''{'ac_cv_lib_$ac_lib_var'+set}'`\" = set"; then
   echo $ac_n "(cached) $ac_c" 1>&6
@@ -15779,7 +16013,7 @@ else
   ac_save_LIBS="$LIBS"
 LIBS="-lnsl  $LIBS"
 cat > conftest.$ac_ext <<EOF
-#line 15783 "configure"
+#line 16022 "configure"
 #include "confdefs.h"
 /* Override any gcc2 internal prototype to avoid an error.  */
 /* We use char because int might match the return type of a gcc2
@@ -15790,7 +16024,7 @@ int main() {
 gethostbyaddr()
 ; return 0; }
 EOF
-if { (eval echo configure:15794: \"$ac_link\") 1>&5; (eval $ac_link) 2>&5; } && test -s conftest${ac_exeext}; then
+if { (eval echo configure:16033: \"$ac_link\") 1>&5; (eval $ac_link) 2>&5; } && test -s conftest${ac_exeext}; then
   rm -rf conftest*
   eval "ac_cv_lib_$ac_lib_var=yes"
 else
@@ -15810,7 +16044,7 @@ else
   echo "$ac_t""no" 1>&6
 
     echo $ac_n "checking for __gethostbyaddr in -lnsl""... $ac_c" 1>&6
-echo "configure:15814: checking for __gethostbyaddr in -lnsl" >&5
+echo "configure:16053: checking for __gethostbyaddr in -lnsl" >&5
 ac_lib_var=`echo nsl'_'__gethostbyaddr | sed 'y%./+-%__p_%'`
 if eval "test \"`echo '$''{'ac_cv_lib_$ac_lib_var'+set}'`\" = set"; then
   echo $ac_n "(cached) $ac_c" 1>&6
@@ -15818,7 +16052,7 @@ else
   ac_save_LIBS="$LIBS"
 LIBS="-lnsl  $LIBS"
 cat > conftest.$ac_ext <<EOF
-#line 15822 "configure"
+#line 16061 "configure"
 #include "confdefs.h"
 /* Override any gcc2 internal prototype to avoid an error.  */
 /* We use char because int might match the return type of a gcc2
@@ -15829,7 +16063,7 @@ int main() {
 __gethostbyaddr()
 ; return 0; }
 EOF
-if { (eval echo configure:15833: \"$ac_link\") 1>&5; (eval $ac_link) 2>&5; } && test -s conftest${ac_exeext}; then
+if { (eval echo configure:16072: \"$ac_link\") 1>&5; (eval $ac_link) 2>&5; } && test -s conftest${ac_exeext}; then
   rm -rf conftest*
   eval "ac_cv_lib_$ac_lib_var=yes"
 else
@@ -15861,11 +16095,11 @@ fi
   found=no
 else
   cat > conftest.$ac_ext <<EOF
-#line 15865 "configure"
+#line 16104 "configure"
 #include "confdefs.h"
 main() { return (0); }
 EOF
-if { (eval echo configure:15869: \"$ac_link\") 1>&5; (eval $ac_link) 2>&5; } && test -s conftest${ac_exeext} && (./conftest; exit) 2>/dev/null
+if { (eval echo configure:16108: \"$ac_link\") 1>&5; (eval $ac_link) 2>&5; } && test -s conftest${ac_exeext} && (./conftest; exit) 2>/dev/null
 then
   found=yes
 else
@@ -15916,12 +16150,12 @@ EOF
   unset found
   
   echo $ac_n "checking for yp_get_default_domain""... $ac_c" 1>&6
-echo "configure:15920: checking for yp_get_default_domain" >&5
+echo "configure:16159: checking for yp_get_default_domain" >&5
 if eval "test \"`echo '$''{'ac_cv_func_yp_get_default_domain'+set}'`\" = set"; then
   echo $ac_n "(cached) $ac_c" 1>&6
 else
   cat > conftest.$ac_ext <<EOF
-#line 15925 "configure"
+#line 16164 "configure"
 #include "confdefs.h"
 /* System header to define __stub macros and hopefully few prototypes,
     which can conflict with char yp_get_default_domain(); below.  */
@@ -15944,7 +16178,7 @@ yp_get_default_domain();
 
 ; return 0; }
 EOF
-if { (eval echo configure:15948: \"$ac_link\") 1>&5; (eval $ac_link) 2>&5; } && test -s conftest${ac_exeext}; then
+if { (eval echo configure:16187: \"$ac_link\") 1>&5; (eval $ac_link) 2>&5; } && test -s conftest${ac_exeext}; then
   rm -rf conftest*
   eval "ac_cv_func_yp_get_default_domain=yes"
 else
@@ -15962,12 +16196,12 @@ if eval "test \"`echo '$ac_cv_func_'yp_get_default_domain`\" = yes"; then
 else
   echo "$ac_t""no" 1>&6
  echo $ac_n "checking for __yp_get_default_domain""... $ac_c" 1>&6
-echo "configure:15966: checking for __yp_get_default_domain" >&5
+echo "configure:16205: checking for __yp_get_default_domain" >&5
 if eval "test \"`echo '$''{'ac_cv_func___yp_get_default_domain'+set}'`\" = set"; then
   echo $ac_n "(cached) $ac_c" 1>&6
 else
   cat > conftest.$ac_ext <<EOF
-#line 15971 "configure"
+#line 16210 "configure"
 #include "confdefs.h"
 /* System header to define __stub macros and hopefully few prototypes,
     which can conflict with char __yp_get_default_domain(); below.  */
@@ -15990,7 +16224,7 @@ __yp_get_default_domain();
 
 ; return 0; }
 EOF
-if { (eval echo configure:15994: \"$ac_link\") 1>&5; (eval $ac_link) 2>&5; } && test -s conftest${ac_exeext}; then
+if { (eval echo configure:16233: \"$ac_link\") 1>&5; (eval $ac_link) 2>&5; } && test -s conftest${ac_exeext}; then
   rm -rf conftest*
   eval "ac_cv_func___yp_get_default_domain=yes"
 else
@@ -16028,7 +16262,7 @@ EOF
   unset ac_cv_lib_nsl___yp_get_default_domain
   unset found
   echo $ac_n "checking for yp_get_default_domain in -lnsl""... $ac_c" 1>&6
-echo "configure:16032: checking for yp_get_default_domain in -lnsl" >&5
+echo "configure:16271: checking for yp_get_default_domain in -lnsl" >&5
 ac_lib_var=`echo nsl'_'yp_get_default_domain | sed 'y%./+-%__p_%'`
 if eval "test \"`echo '$''{'ac_cv_lib_$ac_lib_var'+set}'`\" = set"; then
   echo $ac_n "(cached) $ac_c" 1>&6
@@ -16036,7 +16270,7 @@ else
   ac_save_LIBS="$LIBS"
 LIBS="-lnsl  $LIBS"
 cat > conftest.$ac_ext <<EOF
-#line 16040 "configure"
+#line 16279 "configure"
 #include "confdefs.h"
 /* Override any gcc2 internal prototype to avoid an error.  */
 /* We use char because int might match the return type of a gcc2
@@ -16047,7 +16281,7 @@ int main() {
 yp_get_default_domain()
 ; return 0; }
 EOF
-if { (eval echo configure:16051: \"$ac_link\") 1>&5; (eval $ac_link) 2>&5; } && test -s conftest${ac_exeext}; then
+if { (eval echo configure:16290: \"$ac_link\") 1>&5; (eval $ac_link) 2>&5; } && test -s conftest${ac_exeext}; then
   rm -rf conftest*
   eval "ac_cv_lib_$ac_lib_var=yes"
 else
@@ -16067,7 +16301,7 @@ else
   echo "$ac_t""no" 1>&6
 
     echo $ac_n "checking for __yp_get_default_domain in -lnsl""... $ac_c" 1>&6
-echo "configure:16071: checking for __yp_get_default_domain in -lnsl" >&5
+echo "configure:16310: checking for __yp_get_default_domain in -lnsl" >&5
 ac_lib_var=`echo nsl'_'__yp_get_default_domain | sed 'y%./+-%__p_%'`
 if eval "test \"`echo '$''{'ac_cv_lib_$ac_lib_var'+set}'`\" = set"; then
   echo $ac_n "(cached) $ac_c" 1>&6
@@ -16075,7 +16309,7 @@ else
   ac_save_LIBS="$LIBS"
 LIBS="-lnsl  $LIBS"
 cat > conftest.$ac_ext <<EOF
-#line 16079 "configure"
+#line 16318 "configure"
 #include "confdefs.h"
 /* Override any gcc2 internal prototype to avoid an error.  */
 /* We use char because int might match the return type of a gcc2
@@ -16086,7 +16320,7 @@ int main() {
 __yp_get_default_domain()
 ; return 0; }
 EOF
-if { (eval echo configure:16090: \"$ac_link\") 1>&5; (eval $ac_link) 2>&5; } && test -s conftest${ac_exeext}; then
+if { (eval echo configure:16329: \"$ac_link\") 1>&5; (eval $ac_link) 2>&5; } && test -s conftest${ac_exeext}; then
   rm -rf conftest*
   eval "ac_cv_lib_$ac_lib_var=yes"
 else
@@ -16118,11 +16352,11 @@ fi
   found=no
 else
   cat > conftest.$ac_ext <<EOF
-#line 16122 "configure"
+#line 16361 "configure"
 #include "confdefs.h"
 main() { return (0); }
 EOF
-if { (eval echo configure:16126: \"$ac_link\") 1>&5; (eval $ac_link) 2>&5; } && test -s conftest${ac_exeext} && (./conftest; exit) 2>/dev/null
+if { (eval echo configure:16365: \"$ac_link\") 1>&5; (eval $ac_link) 2>&5; } && test -s conftest${ac_exeext} && (./conftest; exit) 2>/dev/null
 then
   found=yes
 else
@@ -16174,12 +16408,12 @@ EOF
   unset found
   
   echo $ac_n "checking for dlopen""... $ac_c" 1>&6
-echo "configure:16178: checking for dlopen" >&5
+echo "configure:16417: checking for dlopen" >&5
 if eval "test \"`echo '$''{'ac_cv_func_dlopen'+set}'`\" = set"; then
   echo $ac_n "(cached) $ac_c" 1>&6
 else
   cat > conftest.$ac_ext <<EOF
-#line 16183 "configure"
+#line 16422 "configure"
 #include "confdefs.h"
 /* System header to define __stub macros and hopefully few prototypes,
     which can conflict with char dlopen(); below.  */
@@ -16202,7 +16436,7 @@ dlopen();
 
 ; return 0; }
 EOF
-if { (eval echo configure:16206: \"$ac_link\") 1>&5; (eval $ac_link) 2>&5; } && test -s conftest${ac_exeext}; then
+if { (eval echo configure:16445: \"$ac_link\") 1>&5; (eval $ac_link) 2>&5; } && test -s conftest${ac_exeext}; then
   rm -rf conftest*
   eval "ac_cv_func_dlopen=yes"
 else
@@ -16220,12 +16454,12 @@ if eval "test \"`echo '$ac_cv_func_'dlopen`\" = yes"; then
 else
   echo "$ac_t""no" 1>&6
  echo $ac_n "checking for __dlopen""... $ac_c" 1>&6
-echo "configure:16224: checking for __dlopen" >&5
+echo "configure:16463: checking for __dlopen" >&5
 if eval "test \"`echo '$''{'ac_cv_func___dlopen'+set}'`\" = set"; then
   echo $ac_n "(cached) $ac_c" 1>&6
 else
   cat > conftest.$ac_ext <<EOF
-#line 16229 "configure"
+#line 16468 "configure"
 #include "confdefs.h"
 /* System header to define __stub macros and hopefully few prototypes,
     which can conflict with char __dlopen(); below.  */
@@ -16248,7 +16482,7 @@ __dlopen();
 
 ; return 0; }
 EOF
-if { (eval echo configure:16252: \"$ac_link\") 1>&5; (eval $ac_link) 2>&5; } && test -s conftest${ac_exeext}; then
+if { (eval echo configure:16491: \"$ac_link\") 1>&5; (eval $ac_link) 2>&5; } && test -s conftest${ac_exeext}; then
   rm -rf conftest*
   eval "ac_cv_func___dlopen=yes"
 else
@@ -16286,7 +16520,7 @@ EOF
   unset ac_cv_lib_dl___dlopen
   unset found
   echo $ac_n "checking for dlopen in -ldl""... $ac_c" 1>&6
-echo "configure:16290: checking for dlopen in -ldl" >&5
+echo "configure:16529: checking for dlopen in -ldl" >&5
 ac_lib_var=`echo dl'_'dlopen | sed 'y%./+-%__p_%'`
 if eval "test \"`echo '$''{'ac_cv_lib_$ac_lib_var'+set}'`\" = set"; then
   echo $ac_n "(cached) $ac_c" 1>&6
@@ -16294,7 +16528,7 @@ else
   ac_save_LIBS="$LIBS"
 LIBS="-ldl  $LIBS"
 cat > conftest.$ac_ext <<EOF
-#line 16298 "configure"
+#line 16537 "configure"
 #include "confdefs.h"
 /* Override any gcc2 internal prototype to avoid an error.  */
 /* We use char because int might match the return type of a gcc2
@@ -16305,7 +16539,7 @@ int main() {
 dlopen()
 ; return 0; }
 EOF
-if { (eval echo configure:16309: \"$ac_link\") 1>&5; (eval $ac_link) 2>&5; } && test -s conftest${ac_exeext}; then
+if { (eval echo configure:16548: \"$ac_link\") 1>&5; (eval $ac_link) 2>&5; } && test -s conftest${ac_exeext}; then
   rm -rf conftest*
   eval "ac_cv_lib_$ac_lib_var=yes"
 else
@@ -16325,7 +16559,7 @@ else
   echo "$ac_t""no" 1>&6
 
     echo $ac_n "checking for __dlopen in -ldl""... $ac_c" 1>&6
-echo "configure:16329: checking for __dlopen in -ldl" >&5
+echo "configure:16568: checking for __dlopen in -ldl" >&5
 ac_lib_var=`echo dl'_'__dlopen | sed 'y%./+-%__p_%'`
 if eval "test \"`echo '$''{'ac_cv_lib_$ac_lib_var'+set}'`\" = set"; then
   echo $ac_n "(cached) $ac_c" 1>&6
@@ -16333,7 +16567,7 @@ else
   ac_save_LIBS="$LIBS"
 LIBS="-ldl  $LIBS"
 cat > conftest.$ac_ext <<EOF
-#line 16337 "configure"
+#line 16576 "configure"
 #include "confdefs.h"
 /* Override any gcc2 internal prototype to avoid an error.  */
 /* We use char because int might match the return type of a gcc2
@@ -16344,7 +16578,7 @@ int main() {
 __dlopen()
 ; return 0; }
 EOF
-if { (eval echo configure:16348: \"$ac_link\") 1>&5; (eval $ac_link) 2>&5; } && test -s conftest${ac_exeext}; then
+if { (eval echo configure:16587: \"$ac_link\") 1>&5; (eval $ac_link) 2>&5; } && test -s conftest${ac_exeext}; then
   rm -rf conftest*
   eval "ac_cv_lib_$ac_lib_var=yes"
 else
@@ -16376,11 +16610,11 @@ fi
   found=no
 else
   cat > conftest.$ac_ext <<EOF
-#line 16380 "configure"
+#line 16619 "configure"
 #include "confdefs.h"
 main() { return (0); }
 EOF
-if { (eval echo configure:16384: \"$ac_link\") 1>&5; (eval $ac_link) 2>&5; } && test -s conftest${ac_exeext} && (./conftest; exit) 2>/dev/null
+if { (eval echo configure:16623: \"$ac_link\") 1>&5; (eval $ac_link) 2>&5; } && test -s conftest${ac_exeext} && (./conftest; exit) 2>/dev/null
 then
   found=yes
 else
@@ -16432,7 +16666,7 @@ EOF
 
 fi
 echo $ac_n "checking for sin in -lm""... $ac_c" 1>&6
-echo "configure:16436: checking for sin in -lm" >&5
+echo "configure:16675: checking for sin in -lm" >&5
 ac_lib_var=`echo m'_'sin | sed 'y%./+-%__p_%'`
 if eval "test \"`echo '$''{'ac_cv_lib_$ac_lib_var'+set}'`\" = set"; then
   echo $ac_n "(cached) $ac_c" 1>&6
@@ -16440,7 +16674,7 @@ else
   ac_save_LIBS="$LIBS"
 LIBS="-lm  $LIBS"
 cat > conftest.$ac_ext <<EOF
-#line 16444 "configure"
+#line 16683 "configure"
 #include "confdefs.h"
 /* Override any gcc2 internal prototype to avoid an error.  */
 /* We use char because int might match the return type of a gcc2
@@ -16451,7 +16685,7 @@ int main() {
 sin()
 ; return 0; }
 EOF
-if { (eval echo configure:16455: \"$ac_link\") 1>&5; (eval $ac_link) 2>&5; } && test -s conftest${ac_exeext}; then
+if { (eval echo configure:16694: \"$ac_link\") 1>&5; (eval $ac_link) 2>&5; } && test -s conftest${ac_exeext}; then
   rm -rf conftest*
   eval "ac_cv_lib_$ac_lib_var=yes"
 else
@@ -16485,12 +16719,12 @@ fi
   unset found
   
   echo $ac_n "checking for inet_aton""... $ac_c" 1>&6
-echo "configure:16489: checking for inet_aton" >&5
+echo "configure:16728: checking for inet_aton" >&5
 if eval "test \"`echo '$''{'ac_cv_func_inet_aton'+set}'`\" = set"; then
   echo $ac_n "(cached) $ac_c" 1>&6
 else
   cat > conftest.$ac_ext <<EOF
-#line 16494 "configure"
+#line 16733 "configure"
 #include "confdefs.h"
 /* System header to define __stub macros and hopefully few prototypes,
     which can conflict with char inet_aton(); below.  */
@@ -16513,7 +16747,7 @@ inet_aton();
 
 ; return 0; }
 EOF
-if { (eval echo configure:16517: \"$ac_link\") 1>&5; (eval $ac_link) 2>&5; } && test -s conftest${ac_exeext}; then
+if { (eval echo configure:16756: \"$ac_link\") 1>&5; (eval $ac_link) 2>&5; } && test -s conftest${ac_exeext}; then
   rm -rf conftest*
   eval "ac_cv_func_inet_aton=yes"
 else
@@ -16531,12 +16765,12 @@ if eval "test \"`echo '$ac_cv_func_'inet_aton`\" = yes"; then
 else
   echo "$ac_t""no" 1>&6
  echo $ac_n "checking for __inet_aton""... $ac_c" 1>&6
-echo "configure:16535: checking for __inet_aton" >&5
+echo "configure:16774: checking for __inet_aton" >&5
 if eval "test \"`echo '$''{'ac_cv_func___inet_aton'+set}'`\" = set"; then
   echo $ac_n "(cached) $ac_c" 1>&6
 else
   cat > conftest.$ac_ext <<EOF
-#line 16540 "configure"
+#line 16779 "configure"
 #include "confdefs.h"
 /* System header to define __stub macros and hopefully few prototypes,
     which can conflict with char __inet_aton(); below.  */
@@ -16559,7 +16793,7 @@ __inet_aton();
 
 ; return 0; }
 EOF
-if { (eval echo configure:16563: \"$ac_link\") 1>&5; (eval $ac_link) 2>&5; } && test -s conftest${ac_exeext}; then
+if { (eval echo configure:16802: \"$ac_link\") 1>&5; (eval $ac_link) 2>&5; } && test -s conftest${ac_exeext}; then
   rm -rf conftest*
   eval "ac_cv_func___inet_aton=yes"
 else
@@ -16597,7 +16831,7 @@ EOF
   unset ac_cv_lib_resolv___inet_aton
   unset found
   echo $ac_n "checking for inet_aton in -lresolv""... $ac_c" 1>&6
-echo "configure:16601: checking for inet_aton in -lresolv" >&5
+echo "configure:16840: checking for inet_aton in -lresolv" >&5
 ac_lib_var=`echo resolv'_'inet_aton | sed 'y%./+-%__p_%'`
 if eval "test \"`echo '$''{'ac_cv_lib_$ac_lib_var'+set}'`\" = set"; then
   echo $ac_n "(cached) $ac_c" 1>&6
@@ -16605,7 +16839,7 @@ else
   ac_save_LIBS="$LIBS"
 LIBS="-lresolv  $LIBS"
 cat > conftest.$ac_ext <<EOF
-#line 16609 "configure"
+#line 16848 "configure"
 #include "confdefs.h"
 /* Override any gcc2 internal prototype to avoid an error.  */
 /* We use char because int might match the return type of a gcc2
@@ -16616,7 +16850,7 @@ int main() {
 inet_aton()
 ; return 0; }
 EOF
-if { (eval echo configure:16620: \"$ac_link\") 1>&5; (eval $ac_link) 2>&5; } && test -s conftest${ac_exeext}; then
+if { (eval echo configure:16859: \"$ac_link\") 1>&5; (eval $ac_link) 2>&5; } && test -s conftest${ac_exeext}; then
   rm -rf conftest*
   eval "ac_cv_lib_$ac_lib_var=yes"
 else
@@ -16636,7 +16870,7 @@ else
   echo "$ac_t""no" 1>&6
 
     echo $ac_n "checking for __inet_aton in -lresolv""... $ac_c" 1>&6
-echo "configure:16640: checking for __inet_aton in -lresolv" >&5
+echo "configure:16879: checking for __inet_aton in -lresolv" >&5
 ac_lib_var=`echo resolv'_'__inet_aton | sed 'y%./+-%__p_%'`
 if eval "test \"`echo '$''{'ac_cv_lib_$ac_lib_var'+set}'`\" = set"; then
   echo $ac_n "(cached) $ac_c" 1>&6
@@ -16644,7 +16878,7 @@ else
   ac_save_LIBS="$LIBS"
 LIBS="-lresolv  $LIBS"
 cat > conftest.$ac_ext <<EOF
-#line 16648 "configure"
+#line 16887 "configure"
 #include "confdefs.h"
 /* Override any gcc2 internal prototype to avoid an error.  */
 /* We use char because int might match the return type of a gcc2
@@ -16655,7 +16889,7 @@ int main() {
 __inet_aton()
 ; return 0; }
 EOF
-if { (eval echo configure:16659: \"$ac_link\") 1>&5; (eval $ac_link) 2>&5; } && test -s conftest${ac_exeext}; then
+if { (eval echo configure:16898: \"$ac_link\") 1>&5; (eval $ac_link) 2>&5; } && test -s conftest${ac_exeext}; then
   rm -rf conftest*
   eval "ac_cv_lib_$ac_lib_var=yes"
 else
@@ -16687,11 +16921,11 @@ fi
   found=no
 else
   cat > conftest.$ac_ext <<EOF
-#line 16691 "configure"
+#line 16930 "configure"
 #include "confdefs.h"
 main() { return (0); }
 EOF
-if { (eval echo configure:16695: \"$ac_link\") 1>&5; (eval $ac_link) 2>&5; } && test -s conftest${ac_exeext} && (./conftest; exit) 2>/dev/null
+if { (eval echo configure:16934: \"$ac_link\") 1>&5; (eval $ac_link) 2>&5; } && test -s conftest${ac_exeext} && (./conftest; exit) 2>/dev/null
 then
   found=yes
 else
@@ -16733,7 +16967,7 @@ EOF
   unset ac_cv_lib_bind___inet_aton
   unset found
   echo $ac_n "checking for inet_aton in -lbind""... $ac_c" 1>&6
-echo "configure:16737: checking for inet_aton in -lbind" >&5
+echo "configure:16976: checking for inet_aton in -lbind" >&5
 ac_lib_var=`echo bind'_'inet_aton | sed 'y%./+-%__p_%'`
 if eval "test \"`echo '$''{'ac_cv_lib_$ac_lib_var'+set}'`\" = set"; then
   echo $ac_n "(cached) $ac_c" 1>&6
@@ -16741,7 +16975,7 @@ else
   ac_save_LIBS="$LIBS"
 LIBS="-lbind  $LIBS"
 cat > conftest.$ac_ext <<EOF
-#line 16745 "configure"
+#line 16984 "configure"
 #include "confdefs.h"
 /* Override any gcc2 internal prototype to avoid an error.  */
 /* We use char because int might match the return type of a gcc2
@@ -16752,7 +16986,7 @@ int main() {
 inet_aton()
 ; return 0; }
 EOF
-if { (eval echo configure:16756: \"$ac_link\") 1>&5; (eval $ac_link) 2>&5; } && test -s conftest${ac_exeext}; then
+if { (eval echo configure:16995: \"$ac_link\") 1>&5; (eval $ac_link) 2>&5; } && test -s conftest${ac_exeext}; then
   rm -rf conftest*
   eval "ac_cv_lib_$ac_lib_var=yes"
 else
@@ -16772,7 +17006,7 @@ else
   echo "$ac_t""no" 1>&6
 
     echo $ac_n "checking for __inet_aton in -lbind""... $ac_c" 1>&6
-echo "configure:16776: checking for __inet_aton in -lbind" >&5
+echo "configure:17015: checking for __inet_aton in -lbind" >&5
 ac_lib_var=`echo bind'_'__inet_aton | sed 'y%./+-%__p_%'`
 if eval "test \"`echo '$''{'ac_cv_lib_$ac_lib_var'+set}'`\" = set"; then
   echo $ac_n "(cached) $ac_c" 1>&6
@@ -16780,7 +17014,7 @@ else
   ac_save_LIBS="$LIBS"
 LIBS="-lbind  $LIBS"
 cat > conftest.$ac_ext <<EOF
-#line 16784 "configure"
+#line 17023 "configure"
 #include "confdefs.h"
 /* Override any gcc2 internal prototype to avoid an error.  */
 /* We use char because int might match the return type of a gcc2
@@ -16791,7 +17025,7 @@ int main() {
 __inet_aton()
 ; return 0; }
 EOF
-if { (eval echo configure:16795: \"$ac_link\") 1>&5; (eval $ac_link) 2>&5; } && test -s conftest${ac_exeext}; then
+if { (eval echo configure:17034: \"$ac_link\") 1>&5; (eval $ac_link) 2>&5; } && test -s conftest${ac_exeext}; then
   rm -rf conftest*
   eval "ac_cv_lib_$ac_lib_var=yes"
 else
@@ -16823,11 +17057,11 @@ fi
   found=no
 else
   cat > conftest.$ac_ext <<EOF
-#line 16827 "configure"
+#line 17066 "configure"
 #include "confdefs.h"
 main() { return (0); }
 EOF
-if { (eval echo configure:16831: \"$ac_link\") 1>&5; (eval $ac_link) 2>&5; } && test -s conftest${ac_exeext} && (./conftest; exit) 2>/dev/null
+if { (eval echo configure:17070: \"$ac_link\") 1>&5; (eval $ac_link) 2>&5; } && test -s conftest${ac_exeext} && (./conftest; exit) 2>/dev/null
 then
   found=yes
 else
@@ -16878,12 +17112,12 @@ EOF
 
 
 echo $ac_n "checking for ANSI C header files""... $ac_c" 1>&6
-echo "configure:16882: checking for ANSI C header files" >&5
+echo "configure:17121: checking for ANSI C header files" >&5
 if eval "test \"`echo '$''{'ac_cv_header_stdc'+set}'`\" = set"; then
   echo $ac_n "(cached) $ac_c" 1>&6
 else
   cat > conftest.$ac_ext <<EOF
-#line 16887 "configure"
+#line 17126 "configure"
 #include "confdefs.h"
 #include <stdlib.h>
 #include <stdarg.h>
@@ -16891,7 +17125,7 @@ else
 #include <float.h>
 EOF
 ac_try="$ac_cpp conftest.$ac_ext >/dev/null 2>conftest.out"
-{ (eval echo configure:16895: \"$ac_try\") 1>&5; (eval $ac_try) 2>&5; }
+{ (eval echo configure:17134: \"$ac_try\") 1>&5; (eval $ac_try) 2>&5; }
 ac_err=`grep -v '^ *+' conftest.out | grep -v "^conftest.${ac_ext}\$"`
 if test -z "$ac_err"; then
   rm -rf conftest*
@@ -16908,7 +17142,7 @@ rm -f conftest*
 if test $ac_cv_header_stdc = yes; then
   # SunOS 4.x string.h does not declare mem*, contrary to ANSI.
 cat > conftest.$ac_ext <<EOF
-#line 16912 "configure"
+#line 17151 "configure"
 #include "confdefs.h"
 #include <string.h>
 EOF
@@ -16926,7 +17160,7 @@ fi
 if test $ac_cv_header_stdc = yes; then
   # ISC 2.0.2 stdlib.h does not declare free, contrary to ANSI.
 cat > conftest.$ac_ext <<EOF
-#line 16930 "configure"
+#line 17169 "configure"
 #include "confdefs.h"
 #include <stdlib.h>
 EOF
@@ -16947,7 +17181,7 @@ if test "$cross_compiling" = yes; then
   :
 else
   cat > conftest.$ac_ext <<EOF
-#line 16951 "configure"
+#line 17190 "configure"
 #include "confdefs.h"
 #include <ctype.h>
 #define ISLOWER(c) ('a' <= (c) && (c) <= 'z')
@@ -16958,7 +17192,7 @@ if (XOR (islower (i), ISLOWER (i)) || toupper (i) != TOUPPER (i)) exit(2);
 exit (0); }
 
 EOF
-if { (eval echo configure:16962: \"$ac_link\") 1>&5; (eval $ac_link) 2>&5; } && test -s conftest${ac_exeext} && (./conftest; exit) 2>/dev/null
+if { (eval echo configure:17201: \"$ac_link\") 1>&5; (eval $ac_link) 2>&5; } && test -s conftest${ac_exeext} && (./conftest; exit) 2>/dev/null
 then
   :
 else
@@ -16986,12 +17220,12 @@ for ac_hdr in dirent.h sys/ndir.h sys/dir.h ndir.h
 do
 ac_safe=`echo "$ac_hdr" | sed 'y%./+-%__p_%'`
 echo $ac_n "checking for $ac_hdr that defines DIR""... $ac_c" 1>&6
-echo "configure:16990: checking for $ac_hdr that defines DIR" >&5
+echo "configure:17229: checking for $ac_hdr that defines DIR" >&5
 if eval "test \"`echo '$''{'ac_cv_header_dirent_$ac_safe'+set}'`\" = set"; then
   echo $ac_n "(cached) $ac_c" 1>&6
 else
   cat > conftest.$ac_ext <<EOF
-#line 16995 "configure"
+#line 17234 "configure"
 #include "confdefs.h"
 #include <sys/types.h>
 #include <$ac_hdr>
@@ -16999,7 +17233,7 @@ int main() {
 DIR *dirp = 0;
 ; return 0; }
 EOF
-if { (eval echo configure:17003: \"$ac_compile\") 1>&5; (eval $ac_compile) 2>&5; }; then
+if { (eval echo configure:17242: \"$ac_compile\") 1>&5; (eval $ac_compile) 2>&5; }; then
   rm -rf conftest*
   eval "ac_cv_header_dirent_$ac_safe=yes"
 else
@@ -17024,7 +17258,7 @@ done
 # Two versions of opendir et al. are in -ldir and -lx on SCO Xenix.
 if test $ac_header_dirent = dirent.h; then
 echo $ac_n "checking for opendir in -ldir""... $ac_c" 1>&6
-echo "configure:17028: checking for opendir in -ldir" >&5
+echo "configure:17267: checking for opendir in -ldir" >&5
 ac_lib_var=`echo dir'_'opendir | sed 'y%./+-%__p_%'`
 if eval "test \"`echo '$''{'ac_cv_lib_$ac_lib_var'+set}'`\" = set"; then
   echo $ac_n "(cached) $ac_c" 1>&6
@@ -17032,7 +17266,7 @@ else
   ac_save_LIBS="$LIBS"
 LIBS="-ldir  $LIBS"
 cat > conftest.$ac_ext <<EOF
-#line 17036 "configure"
+#line 17275 "configure"
 #include "confdefs.h"
 /* Override any gcc2 internal prototype to avoid an error.  */
 /* We use char because int might match the return type of a gcc2
@@ -17043,7 +17277,7 @@ int main() {
 opendir()
 ; return 0; }
 EOF
-if { (eval echo configure:17047: \"$ac_link\") 1>&5; (eval $ac_link) 2>&5; } && test -s conftest${ac_exeext}; then
+if { (eval echo configure:17286: \"$ac_link\") 1>&5; (eval $ac_link) 2>&5; } && test -s conftest${ac_exeext}; then
   rm -rf conftest*
   eval "ac_cv_lib_$ac_lib_var=yes"
 else
@@ -17065,7 +17299,7 @@ fi
 
 else
 echo $ac_n "checking for opendir in -lx""... $ac_c" 1>&6
-echo "configure:17069: checking for opendir in -lx" >&5
+echo "configure:17308: checking for opendir in -lx" >&5
 ac_lib_var=`echo x'_'opendir | sed 'y%./+-%__p_%'`
 if eval "test \"`echo '$''{'ac_cv_lib_$ac_lib_var'+set}'`\" = set"; then
   echo $ac_n "(cached) $ac_c" 1>&6
@@ -17073,7 +17307,7 @@ else
   ac_save_LIBS="$LIBS"
 LIBS="-lx  $LIBS"
 cat > conftest.$ac_ext <<EOF
-#line 17077 "configure"
+#line 17316 "configure"
 #include "confdefs.h"
 /* Override any gcc2 internal prototype to avoid an error.  */
 /* We use char because int might match the return type of a gcc2
@@ -17084,7 +17318,7 @@ int main() {
 opendir()
 ; return 0; }
 EOF
-if { (eval echo configure:17088: \"$ac_link\") 1>&5; (eval $ac_link) 2>&5; } && test -s conftest${ac_exeext}; then
+if { (eval echo configure:17327: \"$ac_link\") 1>&5; (eval $ac_link) 2>&5; } && test -s conftest${ac_exeext}; then
   rm -rf conftest*
   eval "ac_cv_lib_$ac_lib_var=yes"
 else
@@ -17166,17 +17400,17 @@ assert.h
 do
 ac_safe=`echo "$ac_hdr" | sed 'y%./+-%__p_%'`
 echo $ac_n "checking for $ac_hdr""... $ac_c" 1>&6
-echo "configure:17170: checking for $ac_hdr" >&5
+echo "configure:17409: checking for $ac_hdr" >&5
 if eval "test \"`echo '$''{'ac_cv_header_$ac_safe'+set}'`\" = set"; then
   echo $ac_n "(cached) $ac_c" 1>&6
 else
   cat > conftest.$ac_ext <<EOF
-#line 17175 "configure"
+#line 17414 "configure"
 #include "confdefs.h"
 #include <$ac_hdr>
 EOF
 ac_try="$ac_cpp conftest.$ac_ext >/dev/null 2>conftest.out"
-{ (eval echo configure:17180: \"$ac_try\") 1>&5; (eval $ac_try) 2>&5; }
+{ (eval echo configure:17419: \"$ac_try\") 1>&5; (eval $ac_try) 2>&5; }
 ac_err=`grep -v '^ *+' conftest.out | grep -v "^conftest.${ac_ext}\$"`
 if test -z "$ac_err"; then
   rm -rf conftest*
@@ -17205,12 +17439,12 @@ done
 
 
   echo $ac_n "checking for fopencookie""... $ac_c" 1>&6
-echo "configure:17209: checking for fopencookie" >&5
+echo "configure:17448: checking for fopencookie" >&5
 if eval "test \"`echo '$''{'ac_cv_func_fopencookie'+set}'`\" = set"; then
   echo $ac_n "(cached) $ac_c" 1>&6
 else
   cat > conftest.$ac_ext <<EOF
-#line 17214 "configure"
+#line 17453 "configure"
 #include "confdefs.h"
 /* System header to define __stub macros and hopefully few prototypes,
     which can conflict with char fopencookie(); below.  */
@@ -17233,7 +17467,7 @@ fopencookie();
 
 ; return 0; }
 EOF
-if { (eval echo configure:17237: \"$ac_link\") 1>&5; (eval $ac_link) 2>&5; } && test -s conftest${ac_exeext}; then
+if { (eval echo configure:17476: \"$ac_link\") 1>&5; (eval $ac_link) 2>&5; } && test -s conftest${ac_exeext}; then
   rm -rf conftest*
   eval "ac_cv_func_fopencookie=yes"
 else
@@ -17255,7 +17489,7 @@ fi
 
   if test "$have_glibc_fopencookie" = "yes"; then
 cat > conftest.$ac_ext <<EOF
-#line 17259 "configure"
+#line 17498 "configure"
 #include "confdefs.h"
 
 #define _GNU_SOURCE
@@ -17265,7 +17499,7 @@ int main() {
 cookie_io_functions_t cookie;
 ; return 0; }
 EOF
-if { (eval echo configure:17269: \"$ac_compile\") 1>&5; (eval $ac_compile) 2>&5; }; then
+if { (eval echo configure:17508: \"$ac_compile\") 1>&5; (eval $ac_compile) 2>&5; }; then
   rm -rf conftest*
   have_cookie_io_functions_t=yes
 else
@@ -17284,7 +17518,7 @@ if test "$cross_compiling" = yes; then
 
 else
   cat > conftest.$ac_ext <<EOF
-#line 17288 "configure"
+#line 17527 "configure"
 #include "confdefs.h"
 
 #define _GNU_SOURCE
@@ -17316,7 +17550,7 @@ main() {
 
 
 EOF
-if { (eval echo configure:17320: \"$ac_link\") 1>&5; (eval $ac_link) 2>&5; } && test -s conftest${ac_exeext} && (./conftest; exit) 2>/dev/null
+if { (eval echo configure:17559: \"$ac_link\") 1>&5; (eval $ac_link) 2>&5; } && test -s conftest${ac_exeext} && (./conftest; exit) 2>/dev/null
 then
   
   cookie_io_functions_use_off64_t=yes
@@ -17336,7 +17570,7 @@ fi
     else
 
 cat > conftest.$ac_ext <<EOF
-#line 17340 "configure"
+#line 17579 "configure"
 #include "confdefs.h"
 
 #define _GNU_SOURCE
@@ -17346,7 +17580,7 @@ int main() {
  _IO_cookie_io_functions_t cookie; 
 ; return 0; }
 EOF
-if { (eval echo configure:17350: \"$ac_compile\") 1>&5; (eval $ac_compile) 2>&5; }; then
+if { (eval echo configure:17589: \"$ac_compile\") 1>&5; (eval $ac_compile) 2>&5; }; then
   rm -rf conftest*
   have_IO_cookie_io_functions_t=yes
 else
@@ -17380,7 +17614,7 @@ EOF
 
 
   echo $ac_n "checking for broken getcwd""... $ac_c" 1>&6
-echo "configure:17384: checking for broken getcwd" >&5
+echo "configure:17623: checking for broken getcwd" >&5
   os=`uname -sr 2>/dev/null`
   case $os in
     SunOS*)
@@ -17395,14 +17629,14 @@ EOF
 
 
   echo $ac_n "checking for broken libc stdio""... $ac_c" 1>&6
-echo "configure:17399: checking for broken libc stdio" >&5
+echo "configure:17638: checking for broken libc stdio" >&5
   if eval "test \"`echo '$''{'_cv_have_broken_glibc_fopen_append'+set}'`\" = set"; then
   echo $ac_n "(cached) $ac_c" 1>&6
 else
   
   if test "$cross_compiling" = yes; then
   cat > conftest.$ac_ext <<EOF
-#line 17406 "configure"
+#line 17645 "configure"
 #include "confdefs.h"
 
 #include <features.h>
@@ -17415,7 +17649,7 @@ choke me
 
 ; return 0; }
 EOF
-if { (eval echo configure:17419: \"$ac_compile\") 1>&5; (eval $ac_compile) 2>&5; }; then
+if { (eval echo configure:17658: \"$ac_compile\") 1>&5; (eval $ac_compile) 2>&5; }; then
   rm -rf conftest*
   _cv_have_broken_glibc_fopen_append=yes
 else
@@ -17428,7 +17662,7 @@ rm -f conftest*
 
 else
   cat > conftest.$ac_ext <<EOF
-#line 17432 "configure"
+#line 17671 "configure"
 #include "confdefs.h"
 
 #include <stdio.h>
@@ -17456,7 +17690,7 @@ int main(int argc, char *argv[])
 }
 
 EOF
-if { (eval echo configure:17460: \"$ac_link\") 1>&5; (eval $ac_link) 2>&5; } && test -s conftest${ac_exeext} && (./conftest; exit) 2>/dev/null
+if { (eval echo configure:17699: \"$ac_link\") 1>&5; (eval $ac_link) 2>&5; } && test -s conftest${ac_exeext} && (./conftest; exit) 2>/dev/null
 then
   _cv_have_broken_glibc_fopen_append=no
 else
@@ -17484,12 +17718,12 @@ EOF
 
 
 echo $ac_n "checking whether struct tm is in sys/time.h or time.h""... $ac_c" 1>&6
-echo "configure:17488: checking whether struct tm is in sys/time.h or time.h" >&5
+echo "configure:17727: checking whether struct tm is in sys/time.h or time.h" >&5
 if eval "test \"`echo '$''{'ac_cv_struct_tm'+set}'`\" = set"; then
   echo $ac_n "(cached) $ac_c" 1>&6
 else
   cat > conftest.$ac_ext <<EOF
-#line 17493 "configure"
+#line 17732 "configure"
 #include "confdefs.h"
 #include <sys/types.h>
 #include <time.h>
@@ -17497,7 +17731,7 @@ int main() {
 struct tm *tp; tp->tm_sec;
 ; return 0; }
 EOF
-if { (eval echo configure:17501: \"$ac_compile\") 1>&5; (eval $ac_compile) 2>&5; }; then
+if { (eval echo configure:17740: \"$ac_compile\") 1>&5; (eval $ac_compile) 2>&5; }; then
   rm -rf conftest*
   ac_cv_struct_tm=time.h
 else
@@ -17518,12 +17752,12 @@ EOF
 fi
 
 echo $ac_n "checking for tm_zone in struct tm""... $ac_c" 1>&6
-echo "configure:17522: checking for tm_zone in struct tm" >&5
+echo "configure:17761: checking for tm_zone in struct tm" >&5
 if eval "test \"`echo '$''{'ac_cv_struct_tm_zone'+set}'`\" = set"; then
   echo $ac_n "(cached) $ac_c" 1>&6
 else
   cat > conftest.$ac_ext <<EOF
-#line 17527 "configure"
+#line 17766 "configure"
 #include "confdefs.h"
 #include <sys/types.h>
 #include <$ac_cv_struct_tm>
@@ -17531,7 +17765,7 @@ int main() {
 struct tm tm; tm.tm_zone;
 ; return 0; }
 EOF
-if { (eval echo configure:17535: \"$ac_compile\") 1>&5; (eval $ac_compile) 2>&5; }; then
+if { (eval echo configure:17774: \"$ac_compile\") 1>&5; (eval $ac_compile) 2>&5; }; then
   rm -rf conftest*
   ac_cv_struct_tm_zone=yes
 else
@@ -17551,12 +17785,12 @@ EOF
 
 else
   echo $ac_n "checking for tzname""... $ac_c" 1>&6
-echo "configure:17555: checking for tzname" >&5
+echo "configure:17794: checking for tzname" >&5
 if eval "test \"`echo '$''{'ac_cv_var_tzname'+set}'`\" = set"; then
   echo $ac_n "(cached) $ac_c" 1>&6
 else
   cat > conftest.$ac_ext <<EOF
-#line 17560 "configure"
+#line 17799 "configure"
 #include "confdefs.h"
 #include <time.h>
 #ifndef tzname /* For SGI.  */
@@ -17566,7 +17800,7 @@ int main() {
 atoi(*tzname);
 ; return 0; }
 EOF
-if { (eval echo configure:17570: \"$ac_link\") 1>&5; (eval $ac_link) 2>&5; } && test -s conftest${ac_exeext}; then
+if { (eval echo configure:17809: \"$ac_link\") 1>&5; (eval $ac_link) 2>&5; } && test -s conftest${ac_exeext}; then
   rm -rf conftest*
   ac_cv_var_tzname=yes
 else
@@ -17590,16 +17824,16 @@ fi
 
 
   echo $ac_n "checking for missing declarations of reentrant functions""... $ac_c" 1>&6
-echo "configure:17594: checking for missing declarations of reentrant functions" >&5
+echo "configure:17833: checking for missing declarations of reentrant functions" >&5
   cat > conftest.$ac_ext <<EOF
-#line 17596 "configure"
+#line 17835 "configure"
 #include "confdefs.h"
 #include <time.h>
 int main() {
 struct tm *(*func)() = localtime_r
 ; return 0; }
 EOF
-if { (eval echo configure:17603: \"$ac_compile\") 1>&5; (eval $ac_compile) 2>&5; }; then
+if { (eval echo configure:17842: \"$ac_compile\") 1>&5; (eval $ac_compile) 2>&5; }; then
   rm -rf conftest*
   
     :
@@ -17617,14 +17851,14 @@ EOF
 fi
 rm -f conftest*
   cat > conftest.$ac_ext <<EOF
-#line 17621 "configure"
+#line 17860 "configure"
 #include "confdefs.h"
 #include <time.h>
 int main() {
 struct tm *(*func)() = gmtime_r
 ; return 0; }
 EOF
-if { (eval echo configure:17628: \"$ac_compile\") 1>&5; (eval $ac_compile) 2>&5; }; then
+if { (eval echo configure:17867: \"$ac_compile\") 1>&5; (eval $ac_compile) 2>&5; }; then
   rm -rf conftest*
   
     :
@@ -17642,14 +17876,14 @@ EOF
 fi
 rm -f conftest*
   cat > conftest.$ac_ext <<EOF
-#line 17646 "configure"
+#line 17885 "configure"
 #include "confdefs.h"
 #include <time.h>
 int main() {
 char *(*func)() = asctime_r
 ; return 0; }
 EOF
-if { (eval echo configure:17653: \"$ac_compile\") 1>&5; (eval $ac_compile) 2>&5; }; then
+if { (eval echo configure:17892: \"$ac_compile\") 1>&5; (eval $ac_compile) 2>&5; }; then
   rm -rf conftest*
   
     :
@@ -17667,14 +17901,14 @@ EOF
 fi
 rm -f conftest*
   cat > conftest.$ac_ext <<EOF
-#line 17671 "configure"
+#line 17910 "configure"
 #include "confdefs.h"
 #include <time.h>
 int main() {
 char *(*func)() = ctime_r
 ; return 0; }
 EOF
-if { (eval echo configure:17678: \"$ac_compile\") 1>&5; (eval $ac_compile) 2>&5; }; then
+if { (eval echo configure:17917: \"$ac_compile\") 1>&5; (eval $ac_compile) 2>&5; }; then
   rm -rf conftest*
   
     :
@@ -17692,14 +17926,14 @@ EOF
 fi
 rm -f conftest*
   cat > conftest.$ac_ext <<EOF
-#line 17696 "configure"
+#line 17935 "configure"
 #include "confdefs.h"
 #include <string.h>
 int main() {
 char *(*func)() = strtok_r
 ; return 0; }
 EOF
-if { (eval echo configure:17703: \"$ac_compile\") 1>&5; (eval $ac_compile) 2>&5; }; then
+if { (eval echo configure:17942: \"$ac_compile\") 1>&5; (eval $ac_compile) 2>&5; }; then
   rm -rf conftest*
   
     :
@@ -17720,16 +17954,16 @@ rm -f conftest*
 
 
   echo $ac_n "checking for fclose declaration""... $ac_c" 1>&6
-echo "configure:17724: checking for fclose declaration" >&5
+echo "configure:17963: checking for fclose declaration" >&5
   cat > conftest.$ac_ext <<EOF
-#line 17726 "configure"
+#line 17965 "configure"
 #include "confdefs.h"
 #include <stdio.h>
 int main() {
 int (*func)() = fclose
 ; return 0; }
 EOF
-if { (eval echo configure:17733: \"$ac_compile\") 1>&5; (eval $ac_compile) 2>&5; }; then
+if { (eval echo configure:17972: \"$ac_compile\") 1>&5; (eval $ac_compile) 2>&5; }; then
   rm -rf conftest*
   
     cat >> confdefs.h <<\EOF
@@ -17755,12 +17989,12 @@ rm -f conftest*
 
 
 echo $ac_n "checking for tm_gmtoff in struct tm""... $ac_c" 1>&6
-echo "configure:17759: checking for tm_gmtoff in struct tm" >&5
+echo "configure:17998: checking for tm_gmtoff in struct tm" >&5
 if eval "test \"`echo '$''{'ac_cv_struct_tm_gmtoff'+set}'`\" = set"; then
   echo $ac_n "(cached) $ac_c" 1>&6
 else
   cat > conftest.$ac_ext <<EOF
-#line 17764 "configure"
+#line 18003 "configure"
 #include "confdefs.h"
 #include <sys/types.h>
 #include <$ac_cv_struct_tm>
@@ -17768,7 +18002,7 @@ int main() {
 struct tm tm; tm.tm_gmtoff;
 ; return 0; }
 EOF
-if { (eval echo configure:17772: \"$ac_compile\") 1>&5; (eval $ac_compile) 2>&5; }; then
+if { (eval echo configure:18011: \"$ac_compile\") 1>&5; (eval $ac_compile) 2>&5; }; then
   rm -rf conftest*
   ac_cv_struct_tm_gmtoff=yes
 else
@@ -17791,12 +18025,12 @@ fi
 
 
 echo $ac_n "checking for struct flock""... $ac_c" 1>&6
-echo "configure:17795: checking for struct flock" >&5
+echo "configure:18034: checking for struct flock" >&5
 if eval "test \"`echo '$''{'ac_cv_struct_flock'+set}'`\" = set"; then
   echo $ac_n "(cached) $ac_c" 1>&6
 else
   cat > conftest.$ac_ext <<EOF
-#line 17800 "configure"
+#line 18039 "configure"
 #include "confdefs.h"
 
 #include <unistd.h>
@@ -17806,7 +18040,7 @@ int main() {
 struct flock x;
 ; return 0; }
 EOF
-if { (eval echo configure:17810: \"$ac_compile\") 1>&5; (eval $ac_compile) 2>&5; }; then
+if { (eval echo configure:18049: \"$ac_compile\") 1>&5; (eval $ac_compile) 2>&5; }; then
   rm -rf conftest*
   
           ac_cv_struct_flock=yes
@@ -17833,12 +18067,12 @@ fi
 
 
 echo $ac_n "checking for socklen_t""... $ac_c" 1>&6
-echo "configure:17837: checking for socklen_t" >&5
+echo "configure:18076: checking for socklen_t" >&5
 if eval "test \"`echo '$''{'ac_cv_socklen_t'+set}'`\" = set"; then
   echo $ac_n "(cached) $ac_c" 1>&6
 else
   cat > conftest.$ac_ext <<EOF
-#line 17842 "configure"
+#line 18081 "configure"
 #include "confdefs.h"
 
 #include <sys/types.h>
@@ -17850,7 +18084,7 @@ socklen_t x;
 
 ; return 0; }
 EOF
-if { (eval echo configure:17854: \"$ac_compile\") 1>&5; (eval $ac_compile) 2>&5; }; then
+if { (eval echo configure:18093: \"$ac_compile\") 1>&5; (eval $ac_compile) 2>&5; }; then
   rm -rf conftest*
   
   ac_cv_socklen_t=yes
@@ -17876,7 +18110,7 @@ fi
 
 
 echo $ac_n "checking size of size_t""... $ac_c" 1>&6
-echo "configure:17880: checking size of size_t" >&5
+echo "configure:18119: checking size of size_t" >&5
 if eval "test \"`echo '$''{'ac_cv_sizeof_size_t'+set}'`\" = set"; then
   echo $ac_n "(cached) $ac_c" 1>&6
 else
@@ -17884,7 +18118,7 @@ else
   ac_cv_sizeof_size_t=8
 else
   cat > conftest.$ac_ext <<EOF
-#line 17888 "configure"
+#line 18127 "configure"
 #include "confdefs.h"
 #include <stdio.h>
 main()
@@ -17895,7 +18129,7 @@ main()
   exit(0);
 }
 EOF
-if { (eval echo configure:17899: \"$ac_link\") 1>&5; (eval $ac_link) 2>&5; } && test -s conftest${ac_exeext} && (./conftest; exit) 2>/dev/null
+if { (eval echo configure:18138: \"$ac_link\") 1>&5; (eval $ac_link) 2>&5; } && test -s conftest${ac_exeext} && (./conftest; exit) 2>/dev/null
 then
   ac_cv_sizeof_size_t=`cat conftestval`
 else
@@ -17915,7 +18149,7 @@ EOF
 
 
 echo $ac_n "checking size of long long""... $ac_c" 1>&6
-echo "configure:17919: checking size of long long" >&5
+echo "configure:18158: checking size of long long" >&5
 if eval "test \"`echo '$''{'ac_cv_sizeof_long_long'+set}'`\" = set"; then
   echo $ac_n "(cached) $ac_c" 1>&6
 else
@@ -17923,7 +18157,7 @@ else
   ac_cv_sizeof_long_long=8
 else
   cat > conftest.$ac_ext <<EOF
-#line 17927 "configure"
+#line 18166 "configure"
 #include "confdefs.h"
 #include <stdio.h>
 main()
@@ -17934,7 +18168,7 @@ main()
   exit(0);
 }
 EOF
-if { (eval echo configure:17938: \"$ac_link\") 1>&5; (eval $ac_link) 2>&5; } && test -s conftest${ac_exeext} && (./conftest; exit) 2>/dev/null
+if { (eval echo configure:18177: \"$ac_link\") 1>&5; (eval $ac_link) 2>&5; } && test -s conftest${ac_exeext} && (./conftest; exit) 2>/dev/null
 then
   ac_cv_sizeof_long_long=`cat conftestval`
 else
@@ -17954,7 +18188,7 @@ EOF
 
 
 echo $ac_n "checking size of long long int""... $ac_c" 1>&6
-echo "configure:17958: checking size of long long int" >&5
+echo "configure:18197: checking size of long long int" >&5
 if eval "test \"`echo '$''{'ac_cv_sizeof_long_long_int'+set}'`\" = set"; then
   echo $ac_n "(cached) $ac_c" 1>&6
 else
@@ -17962,7 +18196,7 @@ else
   ac_cv_sizeof_long_long_int=8
 else
   cat > conftest.$ac_ext <<EOF
-#line 17966 "configure"
+#line 18205 "configure"
 #include "confdefs.h"
 #include <stdio.h>
 main()
@@ -17973,7 +18207,7 @@ main()
   exit(0);
 }
 EOF
-if { (eval echo configure:17977: \"$ac_link\") 1>&5; (eval $ac_link) 2>&5; } && test -s conftest${ac_exeext} && (./conftest; exit) 2>/dev/null
+if { (eval echo configure:18216: \"$ac_link\") 1>&5; (eval $ac_link) 2>&5; } && test -s conftest${ac_exeext} && (./conftest; exit) 2>/dev/null
 then
   ac_cv_sizeof_long_long_int=`cat conftestval`
 else
@@ -17993,7 +18227,7 @@ EOF
 
 
 echo $ac_n "checking size of long""... $ac_c" 1>&6
-echo "configure:17997: checking size of long" >&5
+echo "configure:18236: checking size of long" >&5
 if eval "test \"`echo '$''{'ac_cv_sizeof_long'+set}'`\" = set"; then
   echo $ac_n "(cached) $ac_c" 1>&6
 else
@@ -18001,7 +18235,7 @@ else
   ac_cv_sizeof_long=8
 else
   cat > conftest.$ac_ext <<EOF
-#line 18005 "configure"
+#line 18244 "configure"
 #include "confdefs.h"
 #include <stdio.h>
 main()
@@ -18012,7 +18246,7 @@ main()
   exit(0);
 }
 EOF
-if { (eval echo configure:18016: \"$ac_link\") 1>&5; (eval $ac_link) 2>&5; } && test -s conftest${ac_exeext} && (./conftest; exit) 2>/dev/null
+if { (eval echo configure:18255: \"$ac_link\") 1>&5; (eval $ac_link) 2>&5; } && test -s conftest${ac_exeext} && (./conftest; exit) 2>/dev/null
 then
   ac_cv_sizeof_long=`cat conftestval`
 else
@@ -18032,7 +18266,7 @@ EOF
 
 
 echo $ac_n "checking size of int""... $ac_c" 1>&6
-echo "configure:18036: checking size of int" >&5
+echo "configure:18275: checking size of int" >&5
 if eval "test \"`echo '$''{'ac_cv_sizeof_int'+set}'`\" = set"; then
   echo $ac_n "(cached) $ac_c" 1>&6
 else
@@ -18040,7 +18274,7 @@ else
   ac_cv_sizeof_int=4
 else
   cat > conftest.$ac_ext <<EOF
-#line 18044 "configure"
+#line 18283 "configure"
 #include "confdefs.h"
 #include <stdio.h>
 main()
@@ -18051,7 +18285,7 @@ main()
   exit(0);
 }
 EOF
-if { (eval echo configure:18055: \"$ac_link\") 1>&5; (eval $ac_link) 2>&5; } && test -s conftest${ac_exeext} && (./conftest; exit) 2>/dev/null
+if { (eval echo configure:18294: \"$ac_link\") 1>&5; (eval $ac_link) 2>&5; } && test -s conftest${ac_exeext} && (./conftest; exit) 2>/dev/null
 then
   ac_cv_sizeof_int=`cat conftestval`
 else
@@ -18073,7 +18307,7 @@ EOF
 
 
   echo $ac_n "checking size of intmax_t""... $ac_c" 1>&6
-echo "configure:18077: checking size of intmax_t" >&5
+echo "configure:18316: checking size of intmax_t" >&5
   
   php_cache_value=php_cv_sizeof_intmax_t
   if eval "test \"`echo '$''{'php_cv_sizeof_intmax_t'+set}'`\" = set"; then
@@ -18090,7 +18324,7 @@ else
 
 else
   cat > conftest.$ac_ext <<EOF
-#line 18094 "configure"
+#line 18333 "configure"
 #include "confdefs.h"
 #include <stdio.h>
 #if STDC_HEADERS
@@ -18114,7 +18348,7 @@ int main()
 }
   
 EOF
-if { (eval echo configure:18118: \"$ac_link\") 1>&5; (eval $ac_link) 2>&5; } && test -s conftest${ac_exeext} && (./conftest; exit) 2>/dev/null
+if { (eval echo configure:18357: \"$ac_link\") 1>&5; (eval $ac_link) 2>&5; } && test -s conftest${ac_exeext} && (./conftest; exit) 2>/dev/null
 then
   
     eval $php_cache_value=`cat conftestval`
@@ -18153,7 +18387,7 @@ EOF
 
 
   echo $ac_n "checking size of ssize_t""... $ac_c" 1>&6
-echo "configure:18157: checking size of ssize_t" >&5
+echo "configure:18396: checking size of ssize_t" >&5
   
   php_cache_value=php_cv_sizeof_ssize_t
   if eval "test \"`echo '$''{'php_cv_sizeof_ssize_t'+set}'`\" = set"; then
@@ -18170,7 +18404,7 @@ else
 
 else
   cat > conftest.$ac_ext <<EOF
-#line 18174 "configure"
+#line 18413 "configure"
 #include "confdefs.h"
 #include <stdio.h>
 #if STDC_HEADERS
@@ -18194,7 +18428,7 @@ int main()
 }
   
 EOF
-if { (eval echo configure:18198: \"$ac_link\") 1>&5; (eval $ac_link) 2>&5; } && test -s conftest${ac_exeext} && (./conftest; exit) 2>/dev/null
+if { (eval echo configure:18437: \"$ac_link\") 1>&5; (eval $ac_link) 2>&5; } && test -s conftest${ac_exeext} && (./conftest; exit) 2>/dev/null
 then
   
     eval $php_cache_value=`cat conftestval`
@@ -18233,7 +18467,7 @@ EOF
 
 
   echo $ac_n "checking size of ptrdiff_t""... $ac_c" 1>&6
-echo "configure:18237: checking size of ptrdiff_t" >&5
+echo "configure:18476: checking size of ptrdiff_t" >&5
   
   php_cache_value=php_cv_sizeof_ptrdiff_t
   if eval "test \"`echo '$''{'php_cv_sizeof_ptrdiff_t'+set}'`\" = set"; then
@@ -18250,7 +18484,7 @@ else
 
 else
   cat > conftest.$ac_ext <<EOF
-#line 18254 "configure"
+#line 18493 "configure"
 #include "confdefs.h"
 #include <stdio.h>
 #if STDC_HEADERS
@@ -18274,7 +18508,7 @@ int main()
 }
   
 EOF
-if { (eval echo configure:18278: \"$ac_link\") 1>&5; (eval $ac_link) 2>&5; } && test -s conftest${ac_exeext} && (./conftest; exit) 2>/dev/null
+if { (eval echo configure:18517: \"$ac_link\") 1>&5; (eval $ac_link) 2>&5; } && test -s conftest${ac_exeext} && (./conftest; exit) 2>/dev/null
 then
   
     eval $php_cache_value=`cat conftestval`
@@ -18313,12 +18547,12 @@ EOF
 
 
 echo $ac_n "checking for st_blksize in struct stat""... $ac_c" 1>&6
-echo "configure:18317: checking for st_blksize in struct stat" >&5
+echo "configure:18556: checking for st_blksize in struct stat" >&5
 if eval "test \"`echo '$''{'ac_cv_struct_st_blksize'+set}'`\" = set"; then
   echo $ac_n "(cached) $ac_c" 1>&6
 else
   cat > conftest.$ac_ext <<EOF
-#line 18322 "configure"
+#line 18561 "configure"
 #include "confdefs.h"
 #include <sys/types.h>
 #include <sys/stat.h>
@@ -18326,7 +18560,7 @@ int main() {
 struct stat s; s.st_blksize;
 ; return 0; }
 EOF
-if { (eval echo configure:18330: \"$ac_compile\") 1>&5; (eval $ac_compile) 2>&5; }; then
+if { (eval echo configure:18569: \"$ac_compile\") 1>&5; (eval $ac_compile) 2>&5; }; then
   rm -rf conftest*
   ac_cv_struct_st_blksize=yes
 else
@@ -18348,12 +18582,12 @@ fi
 
 if test "`uname -s 2>/dev/null`" != "QNX"; then
   echo $ac_n "checking for st_blocks in struct stat""... $ac_c" 1>&6
-echo "configure:18352: checking for st_blocks in struct stat" >&5
+echo "configure:18591: checking for st_blocks in struct stat" >&5
 if eval "test \"`echo '$''{'ac_cv_struct_st_blocks'+set}'`\" = set"; then
   echo $ac_n "(cached) $ac_c" 1>&6
 else
   cat > conftest.$ac_ext <<EOF
-#line 18357 "configure"
+#line 18596 "configure"
 #include "confdefs.h"
 #include <sys/types.h>
 #include <sys/stat.h>
@@ -18361,7 +18595,7 @@ int main() {
 struct stat s; s.st_blocks;
 ; return 0; }
 EOF
-if { (eval echo configure:18365: \"$ac_compile\") 1>&5; (eval $ac_compile) 2>&5; }; then
+if { (eval echo configure:18604: \"$ac_compile\") 1>&5; (eval $ac_compile) 2>&5; }; then
   rm -rf conftest*
   ac_cv_struct_st_blocks=yes
 else
@@ -18388,12 +18622,12 @@ else
   WARNING_LEVEL=0
 fi
 echo $ac_n "checking for st_rdev in struct stat""... $ac_c" 1>&6
-echo "configure:18392: checking for st_rdev in struct stat" >&5
+echo "configure:18631: checking for st_rdev in struct stat" >&5
 if eval "test \"`echo '$''{'ac_cv_struct_st_rdev'+set}'`\" = set"; then
   echo $ac_n "(cached) $ac_c" 1>&6
 else
   cat > conftest.$ac_ext <<EOF
-#line 18397 "configure"
+#line 18636 "configure"
 #include "confdefs.h"
 #include <sys/types.h>
 #include <sys/stat.h>
@@ -18401,7 +18635,7 @@ int main() {
 struct stat s; s.st_rdev;
 ; return 0; }
 EOF
-if { (eval echo configure:18405: \"$ac_compile\") 1>&5; (eval $ac_compile) 2>&5; }; then
+if { (eval echo configure:18644: \"$ac_compile\") 1>&5; (eval $ac_compile) 2>&5; }; then
   rm -rf conftest*
   ac_cv_struct_st_rdev=yes
 else
@@ -18423,12 +18657,12 @@ fi
 
 
 echo $ac_n "checking for size_t""... $ac_c" 1>&6
-echo "configure:18427: checking for size_t" >&5
+echo "configure:18666: checking for size_t" >&5
 if eval "test \"`echo '$''{'ac_cv_type_size_t'+set}'`\" = set"; then
   echo $ac_n "(cached) $ac_c" 1>&6
 else
   cat > conftest.$ac_ext <<EOF
-#line 18432 "configure"
+#line 18671 "configure"
 #include "confdefs.h"
 #include <sys/types.h>
 #if STDC_HEADERS
@@ -18456,12 +18690,12 @@ EOF
 fi
 
 echo $ac_n "checking for uid_t in sys/types.h""... $ac_c" 1>&6
-echo "configure:18460: checking for uid_t in sys/types.h" >&5
+echo "configure:18699: checking for uid_t in sys/types.h" >&5
 if eval "test \"`echo '$''{'ac_cv_type_uid_t'+set}'`\" = set"; then
   echo $ac_n "(cached) $ac_c" 1>&6
 else
   cat > conftest.$ac_ext <<EOF
-#line 18465 "configure"
+#line 18704 "configure"
 #include "confdefs.h"
 #include <sys/types.h>
 EOF
@@ -18492,12 +18726,12 @@ fi
 
 
     echo $ac_n "checking for struct sockaddr_storage""... $ac_c" 1>&6
-echo "configure:18496: checking for struct sockaddr_storage" >&5
+echo "configure:18735: checking for struct sockaddr_storage" >&5
 if eval "test \"`echo '$''{'ac_cv_sockaddr_storage'+set}'`\" = set"; then
   echo $ac_n "(cached) $ac_c" 1>&6
 else
   cat > conftest.$ac_ext <<EOF
-#line 18501 "configure"
+#line 18740 "configure"
 #include "confdefs.h"
 #include <sys/types.h>
 #include <sys/socket.h>
@@ -18505,7 +18739,7 @@ int main() {
 struct sockaddr_storage s; s
 ; return 0; }
 EOF
-if { (eval echo configure:18509: \"$ac_compile\") 1>&5; (eval $ac_compile) 2>&5; }; then
+if { (eval echo configure:18748: \"$ac_compile\") 1>&5; (eval $ac_compile) 2>&5; }; then
   rm -rf conftest*
   ac_cv_sockaddr_storage=yes
 else
@@ -18526,13 +18760,13 @@ EOF
 
   fi
     echo $ac_n "checking for field sa_len in struct sockaddr""... $ac_c" 1>&6
-echo "configure:18530: checking for field sa_len in struct sockaddr" >&5
+echo "configure:18769: checking for field sa_len in struct sockaddr" >&5
 if eval "test \"`echo '$''{'ac_cv_sockaddr_sa_len'+set}'`\" = set"; then
   echo $ac_n "(cached) $ac_c" 1>&6
 else
   
     cat > conftest.$ac_ext <<EOF
-#line 18536 "configure"
+#line 18775 "configure"
 #include "confdefs.h"
 #include <sys/types.h>
 #include <sys/socket.h>
@@ -18540,7 +18774,7 @@ int main() {
 static struct sockaddr sa; int n = (int) sa.sa_len; return n;
 ; return 0; }
 EOF
-if { (eval echo configure:18544: \"$ac_compile\") 1>&5; (eval $ac_compile) 2>&5; }; then
+if { (eval echo configure:18783: \"$ac_compile\") 1>&5; (eval $ac_compile) 2>&5; }; then
   rm -rf conftest*
   ac_cv_sockaddr_sa_len=yes
 else
@@ -18563,12 +18797,12 @@ EOF
 
 
 echo $ac_n "checking for IPv6 support""... $ac_c" 1>&6
-echo "configure:18567: checking for IPv6 support" >&5
+echo "configure:18806: checking for IPv6 support" >&5
 if eval "test \"`echo '$''{'ac_cv_ipv6_support'+set}'`\" = set"; then
   echo $ac_n "(cached) $ac_c" 1>&6
 else
   cat > conftest.$ac_ext <<EOF
-#line 18572 "configure"
+#line 18811 "configure"
 #include "confdefs.h"
  #include <sys/types.h>
 #include <sys/socket.h>
@@ -18577,7 +18811,7 @@ int main() {
 struct sockaddr_in6 s; struct in6_addr t=in6addr_any; int i=AF_INET6; s; t.s6_addr[0] = 0;
 ; return 0; }
 EOF
-if { (eval echo configure:18581: \"$ac_link\") 1>&5; (eval $ac_link) 2>&5; } && test -s conftest${ac_exeext}; then
+if { (eval echo configure:18820: \"$ac_link\") 1>&5; (eval $ac_link) 2>&5; } && test -s conftest${ac_exeext}; then
   rm -rf conftest*
   ac_cv_ipv6_support=yes
 else
@@ -18593,12 +18827,12 @@ echo "$ac_t""$ac_cv_ipv6_support" 1>&6
 
 
 echo $ac_n "checking for vprintf""... $ac_c" 1>&6
-echo "configure:18597: checking for vprintf" >&5
+echo "configure:18836: checking for vprintf" >&5
 if eval "test \"`echo '$''{'ac_cv_func_vprintf'+set}'`\" = set"; then
   echo $ac_n "(cached) $ac_c" 1>&6
 else
   cat > conftest.$ac_ext <<EOF
-#line 18602 "configure"
+#line 18841 "configure"
 #include "confdefs.h"
 /* System header to define __stub macros and hopefully few prototypes,
     which can conflict with char vprintf(); below.  */
@@ -18621,7 +18855,7 @@ vprintf();
 
 ; return 0; }
 EOF
-if { (eval echo configure:18625: \"$ac_link\") 1>&5; (eval $ac_link) 2>&5; } && test -s conftest${ac_exeext}; then
+if { (eval echo configure:18864: \"$ac_link\") 1>&5; (eval $ac_link) 2>&5; } && test -s conftest${ac_exeext}; then
   rm -rf conftest*
   eval "ac_cv_func_vprintf=yes"
 else
@@ -18645,12 +18879,12 @@ fi
 
 if test "$ac_cv_func_vprintf" != yes; then
 echo $ac_n "checking for _doprnt""... $ac_c" 1>&6
-echo "configure:18649: checking for _doprnt" >&5
+echo "configure:18888: checking for _doprnt" >&5
 if eval "test \"`echo '$''{'ac_cv_func__doprnt'+set}'`\" = set"; then
   echo $ac_n "(cached) $ac_c" 1>&6
 else
   cat > conftest.$ac_ext <<EOF
-#line 18654 "configure"
+#line 18893 "configure"
 #include "confdefs.h"
 /* System header to define __stub macros and hopefully few prototypes,
     which can conflict with char _doprnt(); below.  */
@@ -18673,7 +18907,7 @@ _doprnt();
 
 ; return 0; }
 EOF
-if { (eval echo configure:18677: \"$ac_link\") 1>&5; (eval $ac_link) 2>&5; } && test -s conftest${ac_exeext}; then
+if { (eval echo configure:18916: \"$ac_link\") 1>&5; (eval $ac_link) 2>&5; } && test -s conftest${ac_exeext}; then
   rm -rf conftest*
   eval "ac_cv_func__doprnt=yes"
 else
@@ -18782,12 +19016,12 @@ nanosleep \
 
 do
 echo $ac_n "checking for $ac_func""... $ac_c" 1>&6
-echo "configure:18786: checking for $ac_func" >&5
+echo "configure:19025: checking for $ac_func" >&5
 if eval "test \"`echo '$''{'ac_cv_func_$ac_func'+set}'`\" = set"; then
   echo $ac_n "(cached) $ac_c" 1>&6
 else
   cat > conftest.$ac_ext <<EOF
-#line 18791 "configure"
+#line 19030 "configure"
 #include "confdefs.h"
 /* System header to define __stub macros and hopefully few prototypes,
     which can conflict with char $ac_func(); below.  */
@@ -18810,7 +19044,7 @@ $ac_func();
 
 ; return 0; }
 EOF
-if { (eval echo configure:18814: \"$ac_link\") 1>&5; (eval $ac_link) 2>&5; } && test -s conftest${ac_exeext}; then
+if { (eval echo configure:19053: \"$ac_link\") 1>&5; (eval $ac_link) 2>&5; } && test -s conftest${ac_exeext}; then
   rm -rf conftest*
   eval "ac_cv_func_$ac_func=yes"
 else
@@ -18841,7 +19075,7 @@ done
   unset ac_cv_lib_rt___nanosleep
   unset found
   echo $ac_n "checking for nanosleep in -lrt""... $ac_c" 1>&6
-echo "configure:18845: checking for nanosleep in -lrt" >&5
+echo "configure:19084: checking for nanosleep in -lrt" >&5
 ac_lib_var=`echo rt'_'nanosleep | sed 'y%./+-%__p_%'`
 if eval "test \"`echo '$''{'ac_cv_lib_$ac_lib_var'+set}'`\" = set"; then
   echo $ac_n "(cached) $ac_c" 1>&6
@@ -18849,7 +19083,7 @@ else
   ac_save_LIBS="$LIBS"
 LIBS="-lrt  $LIBS"
 cat > conftest.$ac_ext <<EOF
-#line 18853 "configure"
+#line 19092 "configure"
 #include "confdefs.h"
 /* Override any gcc2 internal prototype to avoid an error.  */
 /* We use char because int might match the return type of a gcc2
@@ -18860,7 +19094,7 @@ int main() {
 nanosleep()
 ; return 0; }
 EOF
-if { (eval echo configure:18864: \"$ac_link\") 1>&5; (eval $ac_link) 2>&5; } && test -s conftest${ac_exeext}; then
+if { (eval echo configure:19103: \"$ac_link\") 1>&5; (eval $ac_link) 2>&5; } && test -s conftest${ac_exeext}; then
   rm -rf conftest*
   eval "ac_cv_lib_$ac_lib_var=yes"
 else
@@ -18880,7 +19114,7 @@ else
   echo "$ac_t""no" 1>&6
 
     echo $ac_n "checking for __nanosleep in -lrt""... $ac_c" 1>&6
-echo "configure:18884: checking for __nanosleep in -lrt" >&5
+echo "configure:19123: checking for __nanosleep in -lrt" >&5
 ac_lib_var=`echo rt'_'__nanosleep | sed 'y%./+-%__p_%'`
 if eval "test \"`echo '$''{'ac_cv_lib_$ac_lib_var'+set}'`\" = set"; then
   echo $ac_n "(cached) $ac_c" 1>&6
@@ -18888,7 +19122,7 @@ else
   ac_save_LIBS="$LIBS"
 LIBS="-lrt  $LIBS"
 cat > conftest.$ac_ext <<EOF
-#line 18892 "configure"
+#line 19131 "configure"
 #include "confdefs.h"
 /* Override any gcc2 internal prototype to avoid an error.  */
 /* We use char because int might match the return type of a gcc2
@@ -18899,7 +19133,7 @@ int main() {
 __nanosleep()
 ; return 0; }
 EOF
-if { (eval echo configure:18903: \"$ac_link\") 1>&5; (eval $ac_link) 2>&5; } && test -s conftest${ac_exeext}; then
+if { (eval echo configure:19142: \"$ac_link\") 1>&5; (eval $ac_link) 2>&5; } && test -s conftest${ac_exeext}; then
   rm -rf conftest*
   eval "ac_cv_lib_$ac_lib_var=yes"
 else
@@ -18931,11 +19165,11 @@ fi
   found=no
 else
   cat > conftest.$ac_ext <<EOF
-#line 18935 "configure"
+#line 19174 "configure"
 #include "confdefs.h"
 main() { return (0); }
 EOF
-if { (eval echo configure:18939: \"$ac_link\") 1>&5; (eval $ac_link) 2>&5; } && test -s conftest${ac_exeext} && (./conftest; exit) 2>/dev/null
+if { (eval echo configure:19178: \"$ac_link\") 1>&5; (eval $ac_link) 2>&5; } && test -s conftest${ac_exeext} && (./conftest; exit) 2>/dev/null
 then
   found=yes
 else
@@ -18979,25 +19213,25 @@ EOF
 
 
 echo $ac_n "checking for getaddrinfo""... $ac_c" 1>&6
-echo "configure:18983: checking for getaddrinfo" >&5
+echo "configure:19222: checking for getaddrinfo" >&5
 if eval "test \"`echo '$''{'ac_cv_func_getaddrinfo'+set}'`\" = set"; then
   echo $ac_n "(cached) $ac_c" 1>&6
 else
   cat > conftest.$ac_ext <<EOF
-#line 18988 "configure"
+#line 19227 "configure"
 #include "confdefs.h"
 #include <netdb.h>
 int main() {
 struct addrinfo *g,h;g=&h;getaddrinfo("","",g,&g);
 ; return 0; }
 EOF
-if { (eval echo configure:18995: \"$ac_link\") 1>&5; (eval $ac_link) 2>&5; } && test -s conftest${ac_exeext}; then
+if { (eval echo configure:19234: \"$ac_link\") 1>&5; (eval $ac_link) 2>&5; } && test -s conftest${ac_exeext}; then
   rm -rf conftest*
   if test "$cross_compiling" = yes; then
   ac_cv_func_getaddrinfo=no
 else
   cat > conftest.$ac_ext <<EOF
-#line 19001 "configure"
+#line 19240 "configure"
 #include "confdefs.h"
 
 #include <netdb.h>
@@ -19037,7 +19271,7 @@ int main(void) {
 }
   
 EOF
-if { (eval echo configure:19041: \"$ac_link\") 1>&5; (eval $ac_link) 2>&5; } && test -s conftest${ac_exeext} && (./conftest; exit) 2>/dev/null
+if { (eval echo configure:19280: \"$ac_link\") 1>&5; (eval $ac_link) 2>&5; } && test -s conftest${ac_exeext} && (./conftest; exit) 2>/dev/null
 then
   ac_cv_func_getaddrinfo=yes
 else
@@ -19067,19 +19301,19 @@ EOF
 fi
 
 echo $ac_n "checking for __sync_fetch_and_add""... $ac_c" 1>&6
-echo "configure:19071: checking for __sync_fetch_and_add" >&5
+echo "configure:19310: checking for __sync_fetch_and_add" >&5
 if eval "test \"`echo '$''{'ac_cv_func_sync_fetch_and_add'+set}'`\" = set"; then
   echo $ac_n "(cached) $ac_c" 1>&6
 else
   cat > conftest.$ac_ext <<EOF
-#line 19076 "configure"
+#line 19315 "configure"
 #include "confdefs.h"
 
 int main() {
 int x;__sync_fetch_and_add(&x,1);
 ; return 0; }
 EOF
-if { (eval echo configure:19083: \"$ac_link\") 1>&5; (eval $ac_link) 2>&5; } && test -s conftest${ac_exeext}; then
+if { (eval echo configure:19322: \"$ac_link\") 1>&5; (eval $ac_link) 2>&5; } && test -s conftest${ac_exeext}; then
   rm -rf conftest*
   ac_cv_func_sync_fetch_and_add=yes
 else
@@ -19102,12 +19336,12 @@ fi
 for ac_func in strlcat strlcpy getopt
 do
 echo $ac_n "checking for $ac_func""... $ac_c" 1>&6
-echo "configure:19106: checking for $ac_func" >&5
+echo "configure:19345: checking for $ac_func" >&5
 if eval "test \"`echo '$''{'ac_cv_func_$ac_func'+set}'`\" = set"; then
   echo $ac_n "(cached) $ac_c" 1>&6
 else
   cat > conftest.$ac_ext <<EOF
-#line 19111 "configure"
+#line 19350 "configure"
 #include "confdefs.h"
 /* System header to define __stub macros and hopefully few prototypes,
     which can conflict with char $ac_func(); below.  */
@@ -19130,7 +19364,7 @@ $ac_func();
 
 ; return 0; }
 EOF
-if { (eval echo configure:19134: \"$ac_link\") 1>&5; (eval $ac_link) 2>&5; } && test -s conftest${ac_exeext}; then
+if { (eval echo configure:19373: \"$ac_link\") 1>&5; (eval $ac_link) 2>&5; } && test -s conftest${ac_exeext}; then
   rm -rf conftest*
   eval "ac_cv_func_$ac_func=yes"
 else
@@ -19157,7 +19391,7 @@ done
 
 
 echo $ac_n "checking whether utime accepts a null argument""... $ac_c" 1>&6
-echo "configure:19161: checking whether utime accepts a null argument" >&5
+echo "configure:19400: checking whether utime accepts a null argument" >&5
 if eval "test \"`echo '$''{'ac_cv_func_utime_null'+set}'`\" = set"; then
   echo $ac_n "(cached) $ac_c" 1>&6
 else
@@ -19167,7 +19401,7 @@ if test "$cross_compiling" = yes; then
   ac_cv_func_utime_null=no
 else
   cat > conftest.$ac_ext <<EOF
-#line 19171 "configure"
+#line 19410 "configure"
 #include "confdefs.h"
 #include <sys/types.h>
 #include <sys/stat.h>
@@ -19178,7 +19412,7 @@ exit(!(stat ("conftestdata", &s) == 0 && utime("conftestdata", (long *)0) == 0
 && t.st_mtime - s.st_mtime < 120));
 }
 EOF
-if { (eval echo configure:19182: \"$ac_link\") 1>&5; (eval $ac_link) 2>&5; } && test -s conftest${ac_exeext} && (./conftest; exit) 2>/dev/null
+if { (eval echo configure:19421: \"$ac_link\") 1>&5; (eval $ac_link) 2>&5; } && test -s conftest${ac_exeext} && (./conftest; exit) 2>/dev/null
 then
   ac_cv_func_utime_null=yes
 else
@@ -19204,19 +19438,19 @@ fi
 # The Ultrix 4.2 mips builtin alloca declared by alloca.h only works
 # for constant arguments.  Useless!
 echo $ac_n "checking for working alloca.h""... $ac_c" 1>&6
-echo "configure:19208: checking for working alloca.h" >&5
+echo "configure:19447: checking for working alloca.h" >&5
 if eval "test \"`echo '$''{'ac_cv_header_alloca_h'+set}'`\" = set"; then
   echo $ac_n "(cached) $ac_c" 1>&6
 else
   cat > conftest.$ac_ext <<EOF
-#line 19213 "configure"
+#line 19452 "configure"
 #include "confdefs.h"
 #include <alloca.h>
 int main() {
 char *p = alloca(2 * sizeof(int));
 ; return 0; }
 EOF
-if { (eval echo configure:19220: \"$ac_link\") 1>&5; (eval $ac_link) 2>&5; } && test -s conftest${ac_exeext}; then
+if { (eval echo configure:19459: \"$ac_link\") 1>&5; (eval $ac_link) 2>&5; } && test -s conftest${ac_exeext}; then
   rm -rf conftest*
   ac_cv_header_alloca_h=yes
 else
@@ -19237,12 +19471,12 @@ EOF
 fi
 
 echo $ac_n "checking for alloca""... $ac_c" 1>&6
-echo "configure:19241: checking for alloca" >&5
+echo "configure:19480: checking for alloca" >&5
 if eval "test \"`echo '$''{'ac_cv_func_alloca_works'+set}'`\" = set"; then
   echo $ac_n "(cached) $ac_c" 1>&6
 else
   cat > conftest.$ac_ext <<EOF
-#line 19246 "configure"
+#line 19485 "configure"
 #include "confdefs.h"
 
 #ifdef __GNUC__
@@ -19270,7 +19504,7 @@ int main() {
 char *p = (char *) alloca(1);
 ; return 0; }
 EOF
-if { (eval echo configure:19274: \"$ac_link\") 1>&5; (eval $ac_link) 2>&5; } && test -s conftest${ac_exeext}; then
+if { (eval echo configure:19513: \"$ac_link\") 1>&5; (eval $ac_link) 2>&5; } && test -s conftest${ac_exeext}; then
   rm -rf conftest*
   ac_cv_func_alloca_works=yes
 else
@@ -19302,12 +19536,12 @@ EOF
 
 
 echo $ac_n "checking whether alloca needs Cray hooks""... $ac_c" 1>&6
-echo "configure:19306: checking whether alloca needs Cray hooks" >&5
+echo "configure:19545: checking whether alloca needs Cray hooks" >&5
 if eval "test \"`echo '$''{'ac_cv_os_cray'+set}'`\" = set"; then
   echo $ac_n "(cached) $ac_c" 1>&6
 else
   cat > conftest.$ac_ext <<EOF
-#line 19311 "configure"
+#line 19550 "configure"
 #include "confdefs.h"
 #if defined(CRAY) && ! defined(CRAY2)
 webecray
@@ -19332,12 +19566,12 @@ echo "$ac_t""$ac_cv_os_cray" 1>&6
 if test $ac_cv_os_cray = yes; then
 for ac_func in _getb67 GETB67 getb67; do
   echo $ac_n "checking for $ac_func""... $ac_c" 1>&6
-echo "configure:19336: checking for $ac_func" >&5
+echo "configure:19575: checking for $ac_func" >&5
 if eval "test \"`echo '$''{'ac_cv_func_$ac_func'+set}'`\" = set"; then
   echo $ac_n "(cached) $ac_c" 1>&6
 else
   cat > conftest.$ac_ext <<EOF
-#line 19341 "configure"
+#line 19580 "configure"
 #include "confdefs.h"
 /* System header to define __stub macros and hopefully few prototypes,
     which can conflict with char $ac_func(); below.  */
@@ -19360,7 +19594,7 @@ $ac_func();
 
 ; return 0; }
 EOF
-if { (eval echo configure:19364: \"$ac_link\") 1>&5; (eval $ac_link) 2>&5; } && test -s conftest${ac_exeext}; then
+if { (eval echo configure:19603: \"$ac_link\") 1>&5; (eval $ac_link) 2>&5; } && test -s conftest${ac_exeext}; then
   rm -rf conftest*
   eval "ac_cv_func_$ac_func=yes"
 else
@@ -19387,7 +19621,7 @@ done
 fi
 
 echo $ac_n "checking stack direction for C alloca""... $ac_c" 1>&6
-echo "configure:19391: checking stack direction for C alloca" >&5
+echo "configure:19630: checking stack direction for C alloca" >&5
 if eval "test \"`echo '$''{'ac_cv_c_stack_direction'+set}'`\" = set"; then
   echo $ac_n "(cached) $ac_c" 1>&6
 else
@@ -19395,7 +19629,7 @@ else
   ac_cv_c_stack_direction=0
 else
   cat > conftest.$ac_ext <<EOF
-#line 19399 "configure"
+#line 19638 "configure"
 #include "confdefs.h"
 find_stack_direction ()
 {
@@ -19414,7 +19648,7 @@ main ()
   exit (find_stack_direction() < 0);
 }
 EOF
-if { (eval echo configure:19418: \"$ac_link\") 1>&5; (eval $ac_link) 2>&5; } && test -s conftest${ac_exeext} && (./conftest; exit) 2>/dev/null
+if { (eval echo configure:19657: \"$ac_link\") 1>&5; (eval $ac_link) 2>&5; } && test -s conftest${ac_exeext} && (./conftest; exit) 2>/dev/null
 then
   ac_cv_c_stack_direction=1
 else
@@ -19437,13 +19671,13 @@ fi
 
 
   echo $ac_n "checking for declared timezone""... $ac_c" 1>&6
-echo "configure:19441: checking for declared timezone" >&5
+echo "configure:19680: checking for declared timezone" >&5
 if eval "test \"`echo '$''{'ac_cv_declared_timezone'+set}'`\" = set"; then
   echo $ac_n "(cached) $ac_c" 1>&6
 else
   
     cat > conftest.$ac_ext <<EOF
-#line 19447 "configure"
+#line 19686 "configure"
 #include "confdefs.h"
 
 #include <sys/types.h>
@@ -19458,7 +19692,7 @@ int main() {
 
 ; return 0; }
 EOF
-if { (eval echo configure:19462: \"$ac_compile\") 1>&5; (eval $ac_compile) 2>&5; }; then
+if { (eval echo configure:19701: \"$ac_compile\") 1>&5; (eval $ac_compile) 2>&5; }; then
   rm -rf conftest*
   
   ac_cv_declared_timezone=yes
@@ -19484,7 +19718,7 @@ EOF
 
 
 echo $ac_n "checking for type of reentrant time-related functions""... $ac_c" 1>&6
-echo "configure:19488: checking for type of reentrant time-related functions" >&5
+echo "configure:19727: checking for type of reentrant time-related functions" >&5
 if eval "test \"`echo '$''{'ac_cv_time_r_type'+set}'`\" = set"; then
   echo $ac_n "(cached) $ac_c" 1>&6
 else
@@ -19495,7 +19729,7 @@ if test "$cross_compiling" = yes; then
 
 else
   cat > conftest.$ac_ext <<EOF
-#line 19499 "configure"
+#line 19738 "configure"
 #include "confdefs.h"
 
 #include <time.h>
@@ -19513,7 +19747,7 @@ return (1);
 }
 
 EOF
-if { (eval echo configure:19517: \"$ac_link\") 1>&5; (eval $ac_link) 2>&5; } && test -s conftest${ac_exeext} && (./conftest; exit) 2>/dev/null
+if { (eval echo configure:19756: \"$ac_link\") 1>&5; (eval $ac_link) 2>&5; } && test -s conftest${ac_exeext} && (./conftest; exit) 2>/dev/null
 then
   
   ac_cv_time_r_type=hpux
@@ -19529,7 +19763,7 @@ else
   
 else
   cat > conftest.$ac_ext <<EOF
-#line 19533 "configure"
+#line 19772 "configure"
 #include "confdefs.h"
 
 #include <time.h>
@@ -19545,7 +19779,7 @@ main() {
 }
   
 EOF
-if { (eval echo configure:19549: \"$ac_link\") 1>&5; (eval $ac_link) 2>&5; } && test -s conftest${ac_exeext} && (./conftest; exit) 2>/dev/null
+if { (eval echo configure:19788: \"$ac_link\") 1>&5; (eval $ac_link) 2>&5; } && test -s conftest${ac_exeext} && (./conftest; exit) 2>/dev/null
 then
   
     ac_cv_time_r_type=irix
@@ -19583,12 +19817,12 @@ EOF
 
 
     echo $ac_n "checking for readdir_r""... $ac_c" 1>&6
-echo "configure:19587: checking for readdir_r" >&5
+echo "configure:19826: checking for readdir_r" >&5
 if eval "test \"`echo '$''{'ac_cv_func_readdir_r'+set}'`\" = set"; then
   echo $ac_n "(cached) $ac_c" 1>&6
 else
   cat > conftest.$ac_ext <<EOF
-#line 19592 "configure"
+#line 19831 "configure"
 #include "confdefs.h"
 /* System header to define __stub macros and hopefully few prototypes,
     which can conflict with char readdir_r(); below.  */
@@ -19611,7 +19845,7 @@ readdir_r();
 
 ; return 0; }
 EOF
-if { (eval echo configure:19615: \"$ac_link\") 1>&5; (eval $ac_link) 2>&5; } && test -s conftest${ac_exeext}; then
+if { (eval echo configure:19854: \"$ac_link\") 1>&5; (eval $ac_link) 2>&5; } && test -s conftest${ac_exeext}; then
   rm -rf conftest*
   eval "ac_cv_func_readdir_r=yes"
 else
@@ -19633,7 +19867,7 @@ fi
 
   if test "$ac_cv_func_readdir_r" = "yes"; then
   echo $ac_n "checking for type of readdir_r""... $ac_c" 1>&6
-echo "configure:19637: checking for type of readdir_r" >&5
+echo "configure:19876: checking for type of readdir_r" >&5
 if eval "test \"`echo '$''{'ac_cv_what_readdir_r'+set}'`\" = set"; then
   echo $ac_n "(cached) $ac_c" 1>&6
 else
@@ -19644,7 +19878,7 @@ else
    
 else
   cat > conftest.$ac_ext <<EOF
-#line 19648 "configure"
+#line 19887 "configure"
 #include "confdefs.h"
 
 #define _REENTRANT
@@ -19669,7 +19903,7 @@ main() {
 }
     
 EOF
-if { (eval echo configure:19673: \"$ac_link\") 1>&5; (eval $ac_link) 2>&5; } && test -s conftest${ac_exeext} && (./conftest; exit) 2>/dev/null
+if { (eval echo configure:19912: \"$ac_link\") 1>&5; (eval $ac_link) 2>&5; } && test -s conftest${ac_exeext} && (./conftest; exit) 2>/dev/null
 then
   
       ac_cv_what_readdir_r=POSIX
@@ -19680,7 +19914,7 @@ else
   rm -fr conftest*
   
       cat > conftest.$ac_ext <<EOF
-#line 19684 "configure"
+#line 19923 "configure"
 #include "confdefs.h"
 
 #define _REENTRANT
@@ -19690,7 +19924,7 @@ int readdir_r(DIR *, struct dirent *);
         
 EOF
 ac_try="$ac_cpp conftest.$ac_ext >/dev/null 2>conftest.out"
-{ (eval echo configure:19694: \"$ac_try\") 1>&5; (eval $ac_try) 2>&5; }
+{ (eval echo configure:19933: \"$ac_try\") 1>&5; (eval $ac_try) 2>&5; }
 ac_err=`grep -v '^ *+' conftest.out | grep -v "^conftest.${ac_ext}\$"`
 if test -z "$ac_err"; then
   rm -rf conftest*
@@ -19732,12 +19966,12 @@ EOF
 
 
 echo $ac_n "checking for in_addr_t""... $ac_c" 1>&6
-echo "configure:19736: checking for in_addr_t" >&5
+echo "configure:19975: checking for in_addr_t" >&5
 if eval "test \"`echo '$''{'ac_cv_type_in_addr_t'+set}'`\" = set"; then
   echo $ac_n "(cached) $ac_c" 1>&6
 else
   cat > conftest.$ac_ext <<EOF
-#line 19741 "configure"
+#line 19980 "configure"
 #include "confdefs.h"
 #include <sys/types.h>
 #if STDC_HEADERS
@@ -19771,12 +20005,12 @@ fi
 for ac_func in crypt_r
 do
 echo $ac_n "checking for $ac_func""... $ac_c" 1>&6
-echo "configure:19775: checking for $ac_func" >&5
+echo "configure:20014: checking for $ac_func" >&5
 if eval "test \"`echo '$''{'ac_cv_func_$ac_func'+set}'`\" = set"; then
   echo $ac_n "(cached) $ac_c" 1>&6
 else
   cat > conftest.$ac_ext <<EOF
-#line 19780 "configure"
+#line 20019 "configure"
 #include "confdefs.h"
 /* System header to define __stub macros and hopefully few prototypes,
     which can conflict with char $ac_func(); below.  */
@@ -19799,7 +20033,7 @@ $ac_func();
 
 ; return 0; }
 EOF
-if { (eval echo configure:19803: \"$ac_link\") 1>&5; (eval $ac_link) 2>&5; } && test -s conftest${ac_exeext}; then
+if { (eval echo configure:20042: \"$ac_link\") 1>&5; (eval $ac_link) 2>&5; } && test -s conftest${ac_exeext}; then
   rm -rf conftest*
   eval "ac_cv_func_$ac_func=yes"
 else
@@ -19827,14 +20061,14 @@ done
 if test "x$php_crypt_r" = "x1"; then
   
   echo $ac_n "checking which data struct is used by crypt_r""... $ac_c" 1>&6
-echo "configure:19831: checking which data struct is used by crypt_r" >&5
+echo "configure:20070: checking which data struct is used by crypt_r" >&5
 if eval "test \"`echo '$''{'php_cv_crypt_r_style'+set}'`\" = set"; then
   echo $ac_n "(cached) $ac_c" 1>&6
 else
   
     php_cv_crypt_r_style=none
     cat > conftest.$ac_ext <<EOF
-#line 19838 "configure"
+#line 20077 "configure"
 #include "confdefs.h"
 
 #define _REENTRANT 1
@@ -19847,7 +20081,7 @@ crypt_r("passwd", "hash", &buffer);
 
 ; return 0; }
 EOF
-if { (eval echo configure:19851: \"$ac_compile\") 1>&5; (eval $ac_compile) 2>&5; }; then
+if { (eval echo configure:20090: \"$ac_compile\") 1>&5; (eval $ac_compile) 2>&5; }; then
   rm -rf conftest*
   php_cv_crypt_r_style=cryptd
 else
@@ -19858,7 +20092,7 @@ rm -f conftest*
 
     if test "$php_cv_crypt_r_style" = "none"; then
       cat > conftest.$ac_ext <<EOF
-#line 19862 "configure"
+#line 20101 "configure"
 #include "confdefs.h"
 
 #define _REENTRANT 1
@@ -19871,7 +20105,7 @@ crypt_r("passwd", "hash", &buffer);
 
 ; return 0; }
 EOF
-if { (eval echo configure:19875: \"$ac_compile\") 1>&5; (eval $ac_compile) 2>&5; }; then
+if { (eval echo configure:20114: \"$ac_compile\") 1>&5; (eval $ac_compile) 2>&5; }; then
   rm -rf conftest*
   php_cv_crypt_r_style=struct_crypt_data
 else
@@ -19883,7 +20117,7 @@ rm -f conftest*
 
     if test "$php_cv_crypt_r_style" = "none"; then
       cat > conftest.$ac_ext <<EOF
-#line 19887 "configure"
+#line 20126 "configure"
 #include "confdefs.h"
 
 #define _REENTRANT 1
@@ -19897,7 +20131,7 @@ crypt_r("passwd", "hash", &buffer);
 
 ; return 0; }
 EOF
-if { (eval echo configure:19901: \"$ac_compile\") 1>&5; (eval $ac_compile) 2>&5; }; then
+if { (eval echo configure:20140: \"$ac_compile\") 1>&5; (eval $ac_compile) 2>&5; }; then
   rm -rf conftest*
   php_cv_crypt_r_style=struct_crypt_data_gnu_source
 else
@@ -19956,7 +20190,7 @@ fi
 php_enable_gcov=no
 
 echo $ac_n "checking whether to include gcov symbols""... $ac_c" 1>&6
-echo "configure:19960: checking whether to include gcov symbols" >&5
+echo "configure:20199: checking whether to include gcov symbols" >&5
 # Check whether --enable-gcov or --disable-gcov was given.
 if test "${enable_gcov+set}" = set; then
   enableval="$enable_gcov"
@@ -19995,7 +20229,7 @@ if test "$PHP_GCOV" = "yes"; then
   # Extract the first word of "lcov", so it can be a program name with args.
 set dummy lcov; ac_word=$2
 echo $ac_n "checking for $ac_word""... $ac_c" 1>&6
-echo "configure:19999: checking for $ac_word" >&5
+echo "configure:20238: checking for $ac_word" >&5
 if eval "test \"`echo '$''{'ac_cv_prog_LTP'+set}'`\" = set"; then
   echo $ac_n "(cached) $ac_c" 1>&6
 else
@@ -20024,7 +20258,7 @@ fi
   # Extract the first word of "genhtml", so it can be a program name with args.
 set dummy genhtml; ac_word=$2
 echo $ac_n "checking for $ac_word""... $ac_c" 1>&6
-echo "configure:20028: checking for $ac_word" >&5
+echo "configure:20267: checking for $ac_word" >&5
 if eval "test \"`echo '$''{'ac_cv_prog_LTP_GENHTML'+set}'`\" = set"; then
   echo $ac_n "(cached) $ac_c" 1>&6
 else
@@ -20059,7 +20293,7 @@ fi
 
   if test "$LTP"; then
     echo $ac_n "checking for ltp version""... $ac_c" 1>&6
-echo "configure:20063: checking for ltp version" >&5
+echo "configure:20302: checking for ltp version" >&5
 if eval "test \"`echo '$''{'php_cv_ltp_version'+set}'`\" = set"; then
   echo $ac_n "(cached) $ac_c" 1>&6
 else
@@ -20116,7 +20350,7 @@ fi
 php_enable_debug=no
 
 echo $ac_n "checking whether to include debugging symbols""... $ac_c" 1>&6
-echo "configure:20120: checking whether to include debugging symbols" >&5
+echo "configure:20359: checking whether to include debugging symbols" >&5
 # Check whether --enable-debug or --disable-debug was given.
 if test "${enable_debug+set}" = set; then
   enableval="$enable_debug"
@@ -20164,7 +20398,7 @@ fi
 php_with_layout=PHP
 
 echo $ac_n "checking layout of installed files""... $ac_c" 1>&6
-echo "configure:20168: checking layout of installed files" >&5
+echo "configure:20407: checking layout of installed files" >&5
 # Check whether --with-layout or --without-layout was given.
 if test "${with_layout+set}" = set; then
   withval="$with_layout"
@@ -20196,7 +20430,7 @@ esac
 php_with_config_file_path=DEFAULT
 
 echo $ac_n "checking path to configuration file""... $ac_c" 1>&6
-echo "configure:20200: checking path to configuration file" >&5
+echo "configure:20439: checking path to configuration file" >&5
 # Check whether --with-config-file-path or --without-config-file-path was given.
 if test "${with_config_file_path+set}" = set; then
   withval="$with_config_file_path"
@@ -20227,7 +20461,7 @@ if test "$PHP_CONFIG_FILE_PATH" = "DEFAULT"; then
 fi
 
 echo $ac_n "checking where to scan for configuration files""... $ac_c" 1>&6
-echo "configure:20231: checking where to scan for configuration files" >&5
+echo "configure:20470: checking where to scan for configuration files" >&5
 
 php_with_config_file_scan_dir=DEFAULT
 
@@ -20260,7 +20494,7 @@ test -n "$DEBUG_CFLAGS" && CFLAGS="$CFLAGS $DEBUG_CFLAGS"
 php_enable_safe_mode=no
 
 echo $ac_n "checking whether to enable safe mode by default""... $ac_c" 1>&6
-echo "configure:20264: checking whether to enable safe mode by default" >&5
+echo "configure:20503: checking whether to enable safe mode by default" >&5
 # Check whether --enable-safe-mode or --disable-safe-mode was given.
 if test "${enable_safe_mode+set}" = set; then
   enableval="$enable_safe_mode"
@@ -20292,7 +20526,7 @@ EOF
 fi
 
 echo $ac_n "checking for safe mode exec dir""... $ac_c" 1>&6
-echo "configure:20296: checking for safe mode exec dir" >&5
+echo "configure:20535: checking for safe mode exec dir" >&5
 
 php_with_exec_dir=no
 
@@ -20333,7 +20567,7 @@ fi
 php_enable_sigchild=no
 
 echo $ac_n "checking whether to enable PHP's own SIGCHLD handler""... $ac_c" 1>&6
-echo "configure:20337: checking whether to enable PHP's own SIGCHLD handler" >&5
+echo "configure:20576: checking whether to enable PHP's own SIGCHLD handler" >&5
 # Check whether --enable-sigchild or --disable-sigchild was given.
 if test "${enable_sigchild+set}" = set; then
   enableval="$enable_sigchild"
@@ -20368,7 +20602,7 @@ fi
 php_enable_magic_quotes=no
 
 echo $ac_n "checking whether to enable magic quotes by default""... $ac_c" 1>&6
-echo "configure:20372: checking whether to enable magic quotes by default" >&5
+echo "configure:20611: checking whether to enable magic quotes by default" >&5
 # Check whether --enable-magic-quotes or --disable-magic-quotes was given.
 if test "${enable_magic_quotes+set}" = set; then
   enableval="$enable_magic_quotes"
@@ -20403,7 +20637,7 @@ fi
 php_enable_libgcc=no
 
 echo $ac_n "checking whether to explicitly link against libgcc""... $ac_c" 1>&6
-echo "configure:20407: checking whether to explicitly link against libgcc" >&5
+echo "configure:20646: checking whether to explicitly link against libgcc" >&5
 # Check whether --enable-libgcc or --disable-libgcc was given.
 if test "${enable_libgcc+set}" = set; then
   enableval="$enable_libgcc"
@@ -20480,7 +20714,7 @@ fi
 php_enable_short_tags=yes
 
 echo $ac_n "checking whether to enable short tags by default""... $ac_c" 1>&6
-echo "configure:20484: checking whether to enable short tags by default" >&5
+echo "configure:20723: checking whether to enable short tags by default" >&5
 # Check whether --enable-short-tags or --disable-short-tags was given.
 if test "${enable_short_tags+set}" = set; then
   enableval="$enable_short_tags"
@@ -20515,7 +20749,7 @@ fi
 php_enable_dmalloc=no
 
 echo $ac_n "checking whether to enable dmalloc""... $ac_c" 1>&6
-echo "configure:20519: checking whether to enable dmalloc" >&5
+echo "configure:20758: checking whether to enable dmalloc" >&5
 # Check whether --enable-dmalloc or --disable-dmalloc was given.
 if test "${enable_dmalloc+set}" = set; then
   enableval="$enable_dmalloc"
@@ -20536,7 +20770,7 @@ echo "$ac_t""$ext_output" 1>&6
 
 if test "$PHP_DMALLOC" = "yes"; then
   echo $ac_n "checking for dmalloc_error in -ldmalloc""... $ac_c" 1>&6
-echo "configure:20540: checking for dmalloc_error in -ldmalloc" >&5
+echo "configure:20779: checking for dmalloc_error in -ldmalloc" >&5
 ac_lib_var=`echo dmalloc'_'dmalloc_error | sed 'y%./+-%__p_%'`
 if eval "test \"`echo '$''{'ac_cv_lib_$ac_lib_var'+set}'`\" = set"; then
   echo $ac_n "(cached) $ac_c" 1>&6
@@ -20544,7 +20778,7 @@ else
   ac_save_LIBS="$LIBS"
 LIBS="-ldmalloc  $LIBS"
 cat > conftest.$ac_ext <<EOF
-#line 20548 "configure"
+#line 20787 "configure"
 #include "confdefs.h"
 /* Override any gcc2 internal prototype to avoid an error.  */
 /* We use char because int might match the return type of a gcc2
@@ -20555,7 +20789,7 @@ int main() {
 dmalloc_error()
 ; return 0; }
 EOF
-if { (eval echo configure:20559: \"$ac_link\") 1>&5; (eval $ac_link) 2>&5; } && test -s conftest${ac_exeext}; then
+if { (eval echo configure:20798: \"$ac_link\") 1>&5; (eval $ac_link) 2>&5; } && test -s conftest${ac_exeext}; then
   rm -rf conftest*
   eval "ac_cv_lib_$ac_lib_var=yes"
 else
@@ -20600,7 +20834,7 @@ fi
 php_enable_ipv6=yes
 
 echo $ac_n "checking whether to enable IPv6 support""... $ac_c" 1>&6
-echo "configure:20604: checking whether to enable IPv6 support" >&5
+echo "configure:20843: checking whether to enable IPv6 support" >&5
 # Check whether --enable-ipv6 or --disable-ipv6 was given.
 if test "${enable_ipv6+set}" = set; then
   enableval="$enable_ipv6"
@@ -20627,7 +20861,7 @@ EOF
 fi
 
 echo $ac_n "checking how big to make fd sets""... $ac_c" 1>&6
-echo "configure:20631: checking how big to make fd sets" >&5
+echo "configure:20870: checking how big to make fd sets" >&5
 
 php_enable_fd_setsize=no
 
@@ -20695,7 +20929,7 @@ fi
 
 
 echo $ac_n "checking size of long""... $ac_c" 1>&6
-echo "configure:20699: checking size of long" >&5
+echo "configure:20938: checking size of long" >&5
 if eval "test \"`echo '$''{'ac_cv_sizeof_long'+set}'`\" = set"; then
   echo $ac_n "(cached) $ac_c" 1>&6
 else
@@ -20703,7 +20937,7 @@ else
   ac_cv_sizeof_long=8
 else
   cat > conftest.$ac_ext <<EOF
-#line 20707 "configure"
+#line 20946 "configure"
 #include "confdefs.h"
 #include <stdio.h>
 main()
@@ -20714,7 +20948,7 @@ main()
   exit(0);
 }
 EOF
-if { (eval echo configure:20718: \"$ac_link\") 1>&5; (eval $ac_link) 2>&5; } && test -s conftest${ac_exeext} && (./conftest; exit) 2>/dev/null
+if { (eval echo configure:20957: \"$ac_link\") 1>&5; (eval $ac_link) 2>&5; } && test -s conftest${ac_exeext} && (./conftest; exit) 2>/dev/null
 then
   ac_cv_sizeof_long=`cat conftestval`
 else
@@ -20734,7 +20968,7 @@ EOF
 
 
 echo $ac_n "checking size of int""... $ac_c" 1>&6
-echo "configure:20738: checking size of int" >&5
+echo "configure:20977: checking size of int" >&5
 if eval "test \"`echo '$''{'ac_cv_sizeof_int'+set}'`\" = set"; then
   echo $ac_n "(cached) $ac_c" 1>&6
 else
@@ -20742,7 +20976,7 @@ else
   ac_cv_sizeof_int=4
 else
   cat > conftest.$ac_ext <<EOF
-#line 20746 "configure"
+#line 20985 "configure"
 #include "confdefs.h"
 #include <stdio.h>
 main()
@@ -20753,7 +20987,7 @@ main()
   exit(0);
 }
 EOF
-if { (eval echo configure:20757: \"$ac_link\") 1>&5; (eval $ac_link) 2>&5; } && test -s conftest${ac_exeext} && (./conftest; exit) 2>/dev/null
+if { (eval echo configure:20996: \"$ac_link\") 1>&5; (eval $ac_link) 2>&5; } && test -s conftest${ac_exeext} && (./conftest; exit) 2>/dev/null
 then
   ac_cv_sizeof_int=`cat conftestval`
 else
@@ -20774,13 +21008,13 @@ EOF
 
 
 echo $ac_n "checking for int32_t""... $ac_c" 1>&6
-echo "configure:20778: checking for int32_t" >&5
+echo "configure:21017: checking for int32_t" >&5
 if eval "test \"`echo '$''{'ac_cv_int_type_int32_t'+set}'`\" = set"; then
   echo $ac_n "(cached) $ac_c" 1>&6
 else
   
 cat > conftest.$ac_ext <<EOF
-#line 20784 "configure"
+#line 21023 "configure"
 #include "confdefs.h"
 
 #if HAVE_SYS_TYPES_H
@@ -20799,7 +21033,7 @@ if (sizeof (int32_t))
 
 ; return 0; }
 EOF
-if { (eval echo configure:20803: \"$ac_compile\") 1>&5; (eval $ac_compile) 2>&5; }; then
+if { (eval echo configure:21042: \"$ac_compile\") 1>&5; (eval $ac_compile) 2>&5; }; then
   rm -rf conftest*
   ac_cv_int_type_int32_t=yes
 else
@@ -20822,13 +21056,13 @@ fi
 
 
 echo $ac_n "checking for uint32_t""... $ac_c" 1>&6
-echo "configure:20826: checking for uint32_t" >&5
+echo "configure:21065: checking for uint32_t" >&5
 if eval "test \"`echo '$''{'ac_cv_int_type_uint32_t'+set}'`\" = set"; then
   echo $ac_n "(cached) $ac_c" 1>&6
 else
   
 cat > conftest.$ac_ext <<EOF
-#line 20832 "configure"
+#line 21071 "configure"
 #include "confdefs.h"
 
 #if HAVE_SYS_TYPES_H
@@ -20847,7 +21081,7 @@ if (sizeof (uint32_t))
 
 ; return 0; }
 EOF
-if { (eval echo configure:20851: \"$ac_compile\") 1>&5; (eval $ac_compile) 2>&5; }; then
+if { (eval echo configure:21090: \"$ac_compile\") 1>&5; (eval $ac_compile) 2>&5; }; then
   rm -rf conftest*
   ac_cv_int_type_uint32_t=yes
 else
@@ -20879,17 +21113,17 @@ stdlib.h
 do
 ac_safe=`echo "$ac_hdr" | sed 'y%./+-%__p_%'`
 echo $ac_n "checking for $ac_hdr""... $ac_c" 1>&6
-echo "configure:20883: checking for $ac_hdr" >&5
+echo "configure:21122: checking for $ac_hdr" >&5
 if eval "test \"`echo '$''{'ac_cv_header_$ac_safe'+set}'`\" = set"; then
   echo $ac_n "(cached) $ac_c" 1>&6
 else
   cat > conftest.$ac_ext <<EOF
-#line 20888 "configure"
+#line 21127 "configure"
 #include "confdefs.h"
 #include <$ac_hdr>
 EOF
 ac_try="$ac_cpp conftest.$ac_ext >/dev/null 2>conftest.out"
-{ (eval echo configure:20893: \"$ac_try\") 1>&5; (eval $ac_try) 2>&5; }
+{ (eval echo configure:21132: \"$ac_try\") 1>&5; (eval $ac_try) 2>&5; }
 ac_err=`grep -v '^ *+' conftest.out | grep -v "^conftest.${ac_ext}\$"`
 if test -z "$ac_err"; then
   rm -rf conftest*
@@ -20919,12 +21153,12 @@ done
 for ac_func in strtoll atoll strftime
 do
 echo $ac_n "checking for $ac_func""... $ac_c" 1>&6
-echo "configure:20923: checking for $ac_func" >&5
+echo "configure:21162: checking for $ac_func" >&5
 if eval "test \"`echo '$''{'ac_cv_func_$ac_func'+set}'`\" = set"; then
   echo $ac_n "(cached) $ac_c" 1>&6
 else
   cat > conftest.$ac_ext <<EOF
-#line 20928 "configure"
+#line 21167 "configure"
 #include "confdefs.h"
 /* System header to define __stub macros and hopefully few prototypes,
     which can conflict with char $ac_func(); below.  */
@@ -20947,7 +21181,7 @@ $ac_func();
 
 ; return 0; }
 EOF
-if { (eval echo configure:20951: \"$ac_link\") 1>&5; (eval $ac_link) 2>&5; } && test -s conftest${ac_exeext}; then
+if { (eval echo configure:21190: \"$ac_link\") 1>&5; (eval $ac_link) 2>&5; } && test -s conftest${ac_exeext}; then
   rm -rf conftest*
   eval "ac_cv_func_$ac_func=yes"
 else
@@ -21237,7 +21471,7 @@ EOF
       ac_obj=$1
       IFS=$old_IFS
       
-      PHP_CLI_OBJS="$PHP_CLI_OBJS $ac_bdir$ac_obj.lo"
+      PHP_GLOBAL_OBJS="$PHP_GLOBAL_OBJS $ac_bdir$ac_obj.lo"
 
       case $ac_src in
         *.c) ac_comp="$b_c_pre $ac_extra $ac_inc $b_c_meta -c $ac_srcdir$ac_src -o $ac_bdir$ac_obj.$b_lo $b_c_post" ;;
@@ -21409,7 +21643,7 @@ case $PHP_REGEX in
 esac
 
 echo $ac_n "checking which regex library to use""... $ac_c" 1>&6
-echo "configure:21413: checking which regex library to use" >&5
+echo "configure:21652: checking which regex library to use" >&5
 echo "$ac_t""$REGEX_TYPE" 1>&6
 
 if test "$REGEX_TYPE" = "php"; then
@@ -21677,7 +21911,7 @@ EOF
       ac_obj=$1
       IFS=$old_IFS
       
-      PHP_CLI_OBJS="$PHP_CLI_OBJS $ac_bdir$ac_obj.lo"
+      PHP_GLOBAL_OBJS="$PHP_GLOBAL_OBJS $ac_bdir$ac_obj.lo"
 
       case $ac_src in
         *.c) ac_comp="$b_c_pre $ac_extra $ac_inc $b_c_meta -c $ac_srcdir$ac_src -o $ac_bdir$ac_obj.$b_lo $b_c_post" ;;
@@ -21786,13 +22020,13 @@ elif test "$REGEX_TYPE" = "system"; then
 EOF
 
     echo $ac_n "checking whether field re_magic exists in struct regex_t""... $ac_c" 1>&6
-echo "configure:21790: checking whether field re_magic exists in struct regex_t" >&5
+echo "configure:22029: checking whether field re_magic exists in struct regex_t" >&5
 if eval "test \"`echo '$''{'ac_cv_regex_t_re_magic'+set}'`\" = set"; then
   echo $ac_n "(cached) $ac_c" 1>&6
 else
   
   cat > conftest.$ac_ext <<EOF
-#line 21796 "configure"
+#line 22035 "configure"
 #include "confdefs.h"
 #include <sys/types.h>
 #include <regex.h>
@@ -21800,7 +22034,7 @@ int main() {
 regex_t rt; rt.re_magic;
 ; return 0; }
 EOF
-if { (eval echo configure:21804: \"$ac_compile\") 1>&5; (eval $ac_compile) 2>&5; }; then
+if { (eval echo configure:22043: \"$ac_compile\") 1>&5; (eval $ac_compile) 2>&5; }; then
   rm -rf conftest*
   ac_cv_regex_t_re_magic=yes
 else
@@ -21826,7 +22060,7 @@ fi
 php_enable_libxml=yes
 
 echo $ac_n "checking whether to enable LIBXML support""... $ac_c" 1>&6
-echo "configure:21830: checking whether to enable LIBXML support" >&5
+echo "configure:22069: checking whether to enable LIBXML support" >&5
 # Check whether --enable-libxml or --disable-libxml was given.
 if test "${enable_libxml+set}" = set; then
   enableval="$enable_libxml"
@@ -21871,7 +22105,7 @@ if test -z "$PHP_LIBXML_DIR"; then
 php_with_libxml_dir=no
 
 echo $ac_n "checking libxml2 install dir""... $ac_c" 1>&6
-echo "configure:21875: checking libxml2 install dir" >&5
+echo "configure:22114: checking libxml2 install dir" >&5
 # Check whether --with-libxml-dir or --without-libxml-dir was given.
 if test "${with_libxml_dir+set}" = set; then
   withval="$with_libxml_dir"
@@ -21897,7 +22131,7 @@ if test "$PHP_LIBXML" != "no"; then
 
   
 echo $ac_n "checking for xml2-config path""... $ac_c" 1>&6
-echo "configure:21901: checking for xml2-config path" >&5
+echo "configure:22140: checking for xml2-config path" >&5
 if eval "test \"`echo '$''{'ac_cv_php_xml2_config_path'+set}'`\" = set"; then
   echo $ac_n "(cached) $ac_c" 1>&6
 else
@@ -22055,7 +22289,7 @@ echo "$ac_t""$ac_cv_php_xml2_config_path" 1>&6
 
 
             echo $ac_n "checking whether libxml build works""... $ac_c" 1>&6
-echo "configure:22059: checking whether libxml build works" >&5
+echo "configure:22298: checking whether libxml build works" >&5
 if eval "test \"`echo '$''{'php_cv_libxml_build_works'+set}'`\" = set"; then
   echo $ac_n "(cached) $ac_c" 1>&6
 else
@@ -22071,7 +22305,7 @@ else
   
 else
   cat > conftest.$ac_ext <<EOF
-#line 22075 "configure"
+#line 22314 "configure"
 #include "confdefs.h"
 
     
@@ -22082,7 +22316,7 @@ else
     }
   
 EOF
-if { (eval echo configure:22086: \"$ac_link\") 1>&5; (eval $ac_link) 2>&5; } && test -s conftest${ac_exeext} && (./conftest; exit) 2>/dev/null
+if { (eval echo configure:22325: \"$ac_link\") 1>&5; (eval $ac_link) 2>&5; } && test -s conftest${ac_exeext} && (./conftest; exit) 2>/dev/null
 then
   
     LIBS=$old_LIBS
@@ -22380,7 +22614,7 @@ EOF
       ac_obj=$1
       IFS=$old_IFS
       
-      PHP_CLI_OBJS="$PHP_CLI_OBJS $ac_bdir$ac_obj.lo"
+      PHP_GLOBAL_OBJS="$PHP_GLOBAL_OBJS $ac_bdir$ac_obj.lo"
 
       case $ac_src in
         *.c) ac_comp="$b_c_pre $ac_extra $ac_inc $b_c_meta -c $ac_srcdir$ac_src -o $ac_bdir$ac_obj.$b_lo $b_c_post" ;;
@@ -22447,7 +22681,7 @@ fi
 php_with_openssl=no
 
 echo $ac_n "checking for OpenSSL support""... $ac_c" 1>&6
-echo "configure:22451: checking for OpenSSL support" >&5
+echo "configure:22690: checking for OpenSSL support" >&5
 # Check whether --with-openssl or --without-openssl was given.
 if test "${with_openssl+set}" = set; then
   withval="$with_openssl"
@@ -22491,7 +22725,7 @@ echo "$ac_t""$ext_output" 1>&6
 php_with_kerberos=no
 
 echo $ac_n "checking for Kerberos support""... $ac_c" 1>&6
-echo "configure:22495: checking for Kerberos support" >&5
+echo "configure:22734: checking for Kerberos support" >&5
 # Check whether --with-kerberos or --without-kerberos was given.
 if test "${with_kerberos+set}" = set; then
   withval="$with_kerberos"
@@ -22770,7 +23004,7 @@ EOF
       ac_obj=$1
       IFS=$old_IFS
       
-      PHP_CLI_OBJS="$PHP_CLI_OBJS $ac_bdir$ac_obj.lo"
+      PHP_GLOBAL_OBJS="$PHP_GLOBAL_OBJS $ac_bdir$ac_obj.lo"
 
       case $ac_src in
         *.c) ac_comp="$b_c_pre $ac_extra $ac_inc $b_c_meta -c $ac_srcdir$ac_src -o $ac_bdir$ac_obj.$b_lo $b_c_post" ;;
@@ -22817,7 +23051,7 @@ EOF
     # Extract the first word of "krb5-config", so it can be a program name with args.
 set dummy krb5-config; ac_word=$2
 echo $ac_n "checking for $ac_word""... $ac_c" 1>&6
-echo "configure:22821: checking for $ac_word" >&5
+echo "configure:23060: checking for $ac_word" >&5
 if eval "test \"`echo '$''{'ac_cv_path_KRB5_CONFIG'+set}'`\" = set"; then
   echo $ac_n "(cached) $ac_c" 1>&6
 else
@@ -23177,7 +23411,7 @@ fi
   fi
 
   echo $ac_n "checking for DSA_get_default_method in -lssl""... $ac_c" 1>&6
-echo "configure:23181: checking for DSA_get_default_method in -lssl" >&5
+echo "configure:23420: checking for DSA_get_default_method in -lssl" >&5
 ac_lib_var=`echo ssl'_'DSA_get_default_method | sed 'y%./+-%__p_%'`
 if eval "test \"`echo '$''{'ac_cv_lib_$ac_lib_var'+set}'`\" = set"; then
   echo $ac_n "(cached) $ac_c" 1>&6
@@ -23185,7 +23419,7 @@ else
   ac_save_LIBS="$LIBS"
 LIBS="-lssl  $LIBS"
 cat > conftest.$ac_ext <<EOF
-#line 23189 "configure"
+#line 23428 "configure"
 #include "confdefs.h"
 /* Override any gcc2 internal prototype to avoid an error.  */
 /* We use char because int might match the return type of a gcc2
@@ -23196,7 +23430,7 @@ int main() {
 DSA_get_default_method()
 ; return 0; }
 EOF
-if { (eval echo configure:23200: \"$ac_link\") 1>&5; (eval $ac_link) 2>&5; } && test -s conftest${ac_exeext}; then
+if { (eval echo configure:23439: \"$ac_link\") 1>&5; (eval $ac_link) 2>&5; } && test -s conftest${ac_exeext}; then
   rm -rf conftest*
   eval "ac_cv_lib_$ac_lib_var=yes"
 else
@@ -23220,7 +23454,7 @@ else
 fi
 
   echo $ac_n "checking for X509_free in -lcrypto""... $ac_c" 1>&6
-echo "configure:23224: checking for X509_free in -lcrypto" >&5
+echo "configure:23463: checking for X509_free in -lcrypto" >&5
 ac_lib_var=`echo crypto'_'X509_free | sed 'y%./+-%__p_%'`
 if eval "test \"`echo '$''{'ac_cv_lib_$ac_lib_var'+set}'`\" = set"; then
   echo $ac_n "(cached) $ac_c" 1>&6
@@ -23228,7 +23462,7 @@ else
   ac_save_LIBS="$LIBS"
 LIBS="-lcrypto  $LIBS"
 cat > conftest.$ac_ext <<EOF
-#line 23232 "configure"
+#line 23471 "configure"
 #include "confdefs.h"
 /* Override any gcc2 internal prototype to avoid an error.  */
 /* We use char because int might match the return type of a gcc2
@@ -23239,7 +23473,7 @@ int main() {
 X509_free()
 ; return 0; }
 EOF
-if { (eval echo configure:23243: \"$ac_link\") 1>&5; (eval $ac_link) 2>&5; } && test -s conftest${ac_exeext}; then
+if { (eval echo configure:23482: \"$ac_link\") 1>&5; (eval $ac_link) 2>&5; } && test -s conftest${ac_exeext}; then
   rm -rf conftest*
   eval "ac_cv_lib_$ac_lib_var=yes"
 else
@@ -23281,7 +23515,7 @@ fi
     # Extract the first word of "pkg-config", so it can be a program name with args.
 set dummy pkg-config; ac_word=$2
 echo $ac_n "checking for $ac_word""... $ac_c" 1>&6
-echo "configure:23285: checking for $ac_word" >&5
+echo "configure:23524: checking for $ac_word" >&5
 if eval "test \"`echo '$''{'ac_cv_path_PKG_CONFIG'+set}'`\" = set"; then
   echo $ac_n "(cached) $ac_c" 1>&6
 else
@@ -23486,9 +23720,9 @@ fi
     old_CPPFLAGS=$CPPFLAGS
     CPPFLAGS=-I$OPENSSL_INCDIR
     echo $ac_n "checking for OpenSSL version""... $ac_c" 1>&6
-echo "configure:23490: checking for OpenSSL version" >&5
+echo "configure:23729: checking for OpenSSL version" >&5
     cat > conftest.$ac_ext <<EOF
-#line 23492 "configure"
+#line 23731 "configure"
 #include "confdefs.h"
 
 #include <openssl/opensslv.h>
@@ -23643,7 +23877,7 @@ rm -f conftest*
   done
 
   echo $ac_n "checking for CRYPTO_free in -lcrypto""... $ac_c" 1>&6
-echo "configure:23647: checking for CRYPTO_free in -lcrypto" >&5
+echo "configure:23886: checking for CRYPTO_free in -lcrypto" >&5
 ac_lib_var=`echo crypto'_'CRYPTO_free | sed 'y%./+-%__p_%'`
 if eval "test \"`echo '$''{'ac_cv_lib_$ac_lib_var'+set}'`\" = set"; then
   echo $ac_n "(cached) $ac_c" 1>&6
@@ -23651,7 +23885,7 @@ else
   ac_save_LIBS="$LIBS"
 LIBS="-lcrypto  $LIBS"
 cat > conftest.$ac_ext <<EOF
-#line 23655 "configure"
+#line 23894 "configure"
 #include "confdefs.h"
 /* Override any gcc2 internal prototype to avoid an error.  */
 /* We use char because int might match the return type of a gcc2
@@ -23662,7 +23896,7 @@ int main() {
 CRYPTO_free()
 ; return 0; }
 EOF
-if { (eval echo configure:23666: \"$ac_link\") 1>&5; (eval $ac_link) 2>&5; } && test -s conftest${ac_exeext}; then
+if { (eval echo configure:23905: \"$ac_link\") 1>&5; (eval $ac_link) 2>&5; } && test -s conftest${ac_exeext}; then
   rm -rf conftest*
   eval "ac_cv_lib_$ac_lib_var=yes"
 else
@@ -23819,7 +24053,7 @@ fi
   done
 
   echo $ac_n "checking for SSL_CTX_set_ssl_version in -lssl""... $ac_c" 1>&6
-echo "configure:23823: checking for SSL_CTX_set_ssl_version in -lssl" >&5
+echo "configure:24062: checking for SSL_CTX_set_ssl_version in -lssl" >&5
 ac_lib_var=`echo ssl'_'SSL_CTX_set_ssl_version | sed 'y%./+-%__p_%'`
 if eval "test \"`echo '$''{'ac_cv_lib_$ac_lib_var'+set}'`\" = set"; then
   echo $ac_n "(cached) $ac_c" 1>&6
@@ -23827,7 +24061,7 @@ else
   ac_save_LIBS="$LIBS"
 LIBS="-lssl  $LIBS"
 cat > conftest.$ac_ext <<EOF
-#line 23831 "configure"
+#line 24070 "configure"
 #include "confdefs.h"
 /* Override any gcc2 internal prototype to avoid an error.  */
 /* We use char because int might match the return type of a gcc2
@@ -23838,7 +24072,7 @@ int main() {
 SSL_CTX_set_ssl_version()
 ; return 0; }
 EOF
-if { (eval echo configure:23842: \"$ac_link\") 1>&5; (eval $ac_link) 2>&5; } && test -s conftest${ac_exeext}; then
+if { (eval echo configure:24081: \"$ac_link\") 1>&5; (eval $ac_link) 2>&5; } && test -s conftest${ac_exeext}; then
   rm -rf conftest*
   eval "ac_cv_lib_$ac_lib_var=yes"
 else
@@ -24003,7 +24237,7 @@ ext_output=$PHP_PCRE_REGEX
 
   if test "$PHP_PCRE_REGEX" != "yes" && test "$PHP_PCRE_REGEX" != "no"; then
     echo $ac_n "checking for PCRE headers location""... $ac_c" 1>&6
-echo "configure:24007: checking for PCRE headers location" >&5
+echo "configure:24246: checking for PCRE headers location" >&5
     for i in $PHP_PCRE_REGEX $PHP_PCRE_REGEX/include $PHP_PCRE_REGEX/include/pcre $PHP_PCRE_REGEX/local/include; do
       test -f $i/pcre.h && PCRE_INCDIR=$i
     done
@@ -24014,7 +24248,7 @@ echo "configure:24007: checking for PCRE headers location" >&5
     echo "$ac_t""$PCRE_INCDIR" 1>&6
 
     echo $ac_n "checking for PCRE library location""... $ac_c" 1>&6
-echo "configure:24018: checking for PCRE library location" >&5
+echo "configure:24257: checking for PCRE library location" >&5
     for j in $PHP_PCRE_REGEX $PHP_PCRE_REGEX/$PHP_LIBDIR; do
       test -f $j/libpcre.a || test -f $j/libpcre.$SHLIB_SUFFIX_NAME && PCRE_LIBDIR=$j
     done
@@ -24381,7 +24615,7 @@ EOF
       ac_obj=$1
       IFS=$old_IFS
       
-      PHP_CLI_OBJS="$PHP_CLI_OBJS $ac_bdir$ac_obj.lo"
+      PHP_GLOBAL_OBJS="$PHP_GLOBAL_OBJS $ac_bdir$ac_obj.lo"
 
       case $ac_src in
         *.c) ac_comp="$b_c_pre $ac_extra $ac_inc $b_c_meta -c $ac_srcdir$ac_src -o $ac_bdir$ac_obj.$b_lo $b_c_post" ;;
@@ -24436,7 +24670,7 @@ EOF
 
   else
     echo $ac_n "checking for PCRE library to use""... $ac_c" 1>&6
-echo "configure:24440: checking for PCRE library to use" >&5
+echo "configure:24679: checking for PCRE library to use" >&5
     echo "$ac_t""bundled" 1>&6
     pcrelib_sources="pcrelib/pcre_chartables.c pcrelib/pcre_ucd.c \
     				 pcrelib/pcre_compile.c pcrelib/pcre_config.c pcrelib/pcre_exec.c \
@@ -24705,7 +24939,7 @@ EOF
       ac_obj=$1
       IFS=$old_IFS
       
-      PHP_CLI_OBJS="$PHP_CLI_OBJS $ac_bdir$ac_obj.lo"
+      PHP_GLOBAL_OBJS="$PHP_GLOBAL_OBJS $ac_bdir$ac_obj.lo"
 
       case $ac_src in
         *.c) ac_comp="$b_c_pre $ac_extra $ac_inc $b_c_meta -c $ac_srcdir$ac_src -o $ac_bdir$ac_obj.$b_lo $b_c_post" ;;
@@ -24774,7 +25008,7 @@ EOF
 php_with_sqlite3=yes
 
 echo $ac_n "checking whether to enable the SQLite3 extension""... $ac_c" 1>&6
-echo "configure:24778: checking whether to enable the SQLite3 extension" >&5
+echo "configure:25017: checking whether to enable the SQLite3 extension" >&5
 # Check whether --with-sqlite3 or --without-sqlite3 was given.
 if test "${with_sqlite3+set}" = set; then
   withval="$with_sqlite3"
@@ -24829,7 +25063,7 @@ if test $PHP_SQLITE3 != "no"; then
 
   if test $PHP_SQLITE3 != "yes"; then
     echo $ac_n "checking for sqlite3 files in default path""... $ac_c" 1>&6
-echo "configure:24833: checking for sqlite3 files in default path" >&5
+echo "configure:25072: checking for sqlite3 files in default path" >&5
     for i in $PHP_SQLITE3 /usr/local /usr; do
       if test -r $i/include/sqlite3.h; then
         SQLITE3_DIR=$i
@@ -24844,7 +25078,7 @@ echo "configure:24833: checking for sqlite3 files in default path" >&5
     fi
 
     echo $ac_n "checking for SQLite 3.3.9+""... $ac_c" 1>&6
-echo "configure:24848: checking for SQLite 3.3.9+" >&5
+echo "configure:25087: checking for SQLite 3.3.9+" >&5
     
   save_old_LDFLAGS=$LDFLAGS
   ac_stuff="
@@ -24943,7 +25177,7 @@ echo "configure:24848: checking for SQLite 3.3.9+" >&5
   done
 
   echo $ac_n "checking for sqlite3_prepare_v2 in -lsqlite3""... $ac_c" 1>&6
-echo "configure:24947: checking for sqlite3_prepare_v2 in -lsqlite3" >&5
+echo "configure:25186: checking for sqlite3_prepare_v2 in -lsqlite3" >&5
 ac_lib_var=`echo sqlite3'_'sqlite3_prepare_v2 | sed 'y%./+-%__p_%'`
 if eval "test \"`echo '$''{'ac_cv_lib_$ac_lib_var'+set}'`\" = set"; then
   echo $ac_n "(cached) $ac_c" 1>&6
@@ -24951,7 +25185,7 @@ else
   ac_save_LIBS="$LIBS"
 LIBS="-lsqlite3  $LIBS"
 cat > conftest.$ac_ext <<EOF
-#line 24955 "configure"
+#line 25194 "configure"
 #include "confdefs.h"
 /* Override any gcc2 internal prototype to avoid an error.  */
 /* We use char because int might match the return type of a gcc2
@@ -24962,7 +25196,7 @@ int main() {
 sqlite3_prepare_v2()
 ; return 0; }
 EOF
-if { (eval echo configure:24966: \"$ac_link\") 1>&5; (eval $ac_link) 2>&5; } && test -s conftest${ac_exeext}; then
+if { (eval echo configure:25205: \"$ac_link\") 1>&5; (eval $ac_link) 2>&5; } && test -s conftest${ac_exeext}; then
   rm -rf conftest*
   eval "ac_cv_lib_$ac_lib_var=yes"
 else
@@ -25222,7 +25456,7 @@ fi
   done
 
   echo $ac_n "checking for sqlite3_key in -lsqlite3""... $ac_c" 1>&6
-echo "configure:25226: checking for sqlite3_key in -lsqlite3" >&5
+echo "configure:25465: checking for sqlite3_key in -lsqlite3" >&5
 ac_lib_var=`echo sqlite3'_'sqlite3_key | sed 'y%./+-%__p_%'`
 if eval "test \"`echo '$''{'ac_cv_lib_$ac_lib_var'+set}'`\" = set"; then
   echo $ac_n "(cached) $ac_c" 1>&6
@@ -25230,7 +25464,7 @@ else
   ac_save_LIBS="$LIBS"
 LIBS="-lsqlite3  $LIBS"
 cat > conftest.$ac_ext <<EOF
-#line 25234 "configure"
+#line 25473 "configure"
 #include "confdefs.h"
 /* Override any gcc2 internal prototype to avoid an error.  */
 /* We use char because int might match the return type of a gcc2
@@ -25241,7 +25475,7 @@ int main() {
 sqlite3_key()
 ; return 0; }
 EOF
-if { (eval echo configure:25245: \"$ac_link\") 1>&5; (eval $ac_link) 2>&5; } && test -s conftest${ac_exeext}; then
+if { (eval echo configure:25484: \"$ac_link\") 1>&5; (eval $ac_link) 2>&5; } && test -s conftest${ac_exeext}; then
   rm -rf conftest*
   eval "ac_cv_lib_$ac_lib_var=yes"
 else
@@ -25373,7 +25607,7 @@ fi
   done
 
   echo $ac_n "checking for sqlite3_load_extension in -lsqlite3""... $ac_c" 1>&6
-echo "configure:25377: checking for sqlite3_load_extension in -lsqlite3" >&5
+echo "configure:25616: checking for sqlite3_load_extension in -lsqlite3" >&5
 ac_lib_var=`echo sqlite3'_'sqlite3_load_extension | sed 'y%./+-%__p_%'`
 if eval "test \"`echo '$''{'ac_cv_lib_$ac_lib_var'+set}'`\" = set"; then
   echo $ac_n "(cached) $ac_c" 1>&6
@@ -25381,7 +25615,7 @@ else
   ac_save_LIBS="$LIBS"
 LIBS="-lsqlite3  $LIBS"
 cat > conftest.$ac_ext <<EOF
-#line 25385 "configure"
+#line 25624 "configure"
 #include "confdefs.h"
 /* Override any gcc2 internal prototype to avoid an error.  */
 /* We use char because int might match the return type of a gcc2
@@ -25392,7 +25626,7 @@ int main() {
 sqlite3_load_extension()
 ; return 0; }
 EOF
-if { (eval echo configure:25396: \"$ac_link\") 1>&5; (eval $ac_link) 2>&5; } && test -s conftest${ac_exeext}; then
+if { (eval echo configure:25635: \"$ac_link\") 1>&5; (eval $ac_link) 2>&5; } && test -s conftest${ac_exeext}; then
   rm -rf conftest*
   eval "ac_cv_lib_$ac_lib_var=yes"
 else
@@ -25429,7 +25663,7 @@ fi
 
   else
     echo $ac_n "checking bundled sqlite3 library""... $ac_c" 1>&6
-echo "configure:25433: checking bundled sqlite3 library" >&5
+echo "configure:25672: checking bundled sqlite3 library" >&5
     echo "$ac_t""yes" 1>&6
 
     sqlite3_extra_sources="libsqlite/sqlite3.c"
@@ -25737,7 +25971,7 @@ EOF
       ac_obj=$1
       IFS=$old_IFS
       
-      PHP_CLI_OBJS="$PHP_CLI_OBJS $ac_bdir$ac_obj.lo"
+      PHP_GLOBAL_OBJS="$PHP_GLOBAL_OBJS $ac_bdir$ac_obj.lo"
 
       case $ac_src in
         *.c) ac_comp="$b_c_pre $ac_extra $ac_inc $b_c_meta -c $ac_srcdir$ac_src -o $ac_bdir$ac_obj.$b_lo $b_c_post" ;;
@@ -25785,7 +26019,7 @@ fi
 php_with_zlib=no
 
 echo $ac_n "checking for ZLIB support""... $ac_c" 1>&6
-echo "configure:25789: checking for ZLIB support" >&5
+echo "configure:26028: checking for ZLIB support" >&5
 # Check whether --with-zlib or --without-zlib was given.
 if test "${with_zlib+set}" = set; then
   withval="$with_zlib"
@@ -25829,7 +26063,7 @@ echo "$ac_t""$ext_output" 1>&6
 php_with_zlib_dir=no
 
 echo $ac_n "checking if the location of ZLIB install directory is defined""... $ac_c" 1>&6
-echo "configure:25833: checking if the location of ZLIB install directory is defined" >&5
+echo "configure:26072: checking if the location of ZLIB install directory is defined" >&5
 # Check whether --with-zlib-dir or --without-zlib-dir was given.
 if test "${with_zlib_dir+set}" = set; then
   withval="$with_zlib_dir"
@@ -26108,7 +26342,7 @@ EOF
       ac_obj=$1
       IFS=$old_IFS
       
-      PHP_CLI_OBJS="$PHP_CLI_OBJS $ac_bdir$ac_obj.lo"
+      PHP_GLOBAL_OBJS="$PHP_GLOBAL_OBJS $ac_bdir$ac_obj.lo"
 
       case $ac_src in
         *.c) ac_comp="$b_c_pre $ac_extra $ac_inc $b_c_meta -c $ac_srcdir$ac_src -o $ac_bdir$ac_obj.$b_lo $b_c_post" ;;
@@ -26272,7 +26506,7 @@ EOF
   done
 
   echo $ac_n "checking for gzgets in -lz""... $ac_c" 1>&6
-echo "configure:26276: checking for gzgets in -lz" >&5
+echo "configure:26515: checking for gzgets in -lz" >&5
 ac_lib_var=`echo z'_'gzgets | sed 'y%./+-%__p_%'`
 if eval "test \"`echo '$''{'ac_cv_lib_$ac_lib_var'+set}'`\" = set"; then
   echo $ac_n "(cached) $ac_c" 1>&6
@@ -26280,7 +26514,7 @@ else
   ac_save_LIBS="$LIBS"
 LIBS="-lz  $LIBS"
 cat > conftest.$ac_ext <<EOF
-#line 26284 "configure"
+#line 26523 "configure"
 #include "confdefs.h"
 /* Override any gcc2 internal prototype to avoid an error.  */
 /* We use char because int might match the return type of a gcc2
@@ -26291,7 +26525,7 @@ int main() {
 gzgets()
 ; return 0; }
 EOF
-if { (eval echo configure:26295: \"$ac_link\") 1>&5; (eval $ac_link) 2>&5; } && test -s conftest${ac_exeext}; then
+if { (eval echo configure:26534: \"$ac_link\") 1>&5; (eval $ac_link) 2>&5; } && test -s conftest${ac_exeext}; then
   rm -rf conftest*
   eval "ac_cv_lib_$ac_lib_var=yes"
 else
@@ -26431,7 +26665,7 @@ fi
 php_enable_bcmath=no
 
 echo $ac_n "checking whether to enable bc style precision math functions""... $ac_c" 1>&6
-echo "configure:26435: checking whether to enable bc style precision math functions" >&5
+echo "configure:26674: checking whether to enable bc style precision math functions" >&5
 # Check whether --enable-bcmath or --disable-bcmath was given.
 if test "${enable_bcmath+set}" = set; then
   enableval="$enable_bcmath"
@@ -26747,7 +26981,7 @@ libbcmath/src/rmzero.c libbcmath/src/str2num.c; do
       ac_obj=$1
       IFS=$old_IFS
       
-      PHP_CLI_OBJS="$PHP_CLI_OBJS $ac_bdir$ac_obj.lo"
+      PHP_GLOBAL_OBJS="$PHP_GLOBAL_OBJS $ac_bdir$ac_obj.lo"
 
       case $ac_src in
         *.c) ac_comp="$b_c_pre $ac_extra $ac_inc $b_c_meta -c $ac_srcdir$ac_src -o $ac_bdir$ac_obj.$b_lo $b_c_post" ;;
@@ -26796,7 +27030,7 @@ fi
 php_with_bz2=no
 
 echo $ac_n "checking for BZip2 support""... $ac_c" 1>&6
-echo "configure:26800: checking for BZip2 support" >&5
+echo "configure:27039: checking for BZip2 support" >&5
 # Check whether --with-bz2 or --without-bz2 was given.
 if test "${with_bz2+set}" = set; then
   withval="$with_bz2"
@@ -26841,7 +27075,7 @@ if test "$PHP_BZ2" != "no"; then
     BZIP_DIR=$PHP_BZ2
   else
     echo $ac_n "checking for BZip2 in default path""... $ac_c" 1>&6
-echo "configure:26845: checking for BZip2 in default path" >&5
+echo "configure:27084: checking for BZip2 in default path" >&5
     for i in /usr/local /usr; do
       if test -r $i/include/bzlib.h; then
         BZIP_DIR=$i
@@ -26954,7 +27188,7 @@ echo "configure:26845: checking for BZip2 in default path" >&5
   done
 
   echo $ac_n "checking for BZ2_bzerror in -lbz2""... $ac_c" 1>&6
-echo "configure:26958: checking for BZ2_bzerror in -lbz2" >&5
+echo "configure:27197: checking for BZ2_bzerror in -lbz2" >&5
 ac_lib_var=`echo bz2'_'BZ2_bzerror | sed 'y%./+-%__p_%'`
 if eval "test \"`echo '$''{'ac_cv_lib_$ac_lib_var'+set}'`\" = set"; then
   echo $ac_n "(cached) $ac_c" 1>&6
@@ -26962,7 +27196,7 @@ else
   ac_save_LIBS="$LIBS"
 LIBS="-lbz2  $LIBS"
 cat > conftest.$ac_ext <<EOF
-#line 26966 "configure"
+#line 27205 "configure"
 #include "confdefs.h"
 /* Override any gcc2 internal prototype to avoid an error.  */
 /* We use char because int might match the return type of a gcc2
@@ -26973,7 +27207,7 @@ int main() {
 BZ2_bzerror()
 ; return 0; }
 EOF
-if { (eval echo configure:26977: \"$ac_link\") 1>&5; (eval $ac_link) 2>&5; } && test -s conftest${ac_exeext}; then
+if { (eval echo configure:27216: \"$ac_link\") 1>&5; (eval $ac_link) 2>&5; } && test -s conftest${ac_exeext}; then
   rm -rf conftest*
   eval "ac_cv_lib_$ac_lib_var=yes"
 else
@@ -27398,7 +27632,7 @@ EOF
       ac_obj=$1
       IFS=$old_IFS
       
-      PHP_CLI_OBJS="$PHP_CLI_OBJS $ac_bdir$ac_obj.lo"
+      PHP_GLOBAL_OBJS="$PHP_GLOBAL_OBJS $ac_bdir$ac_obj.lo"
 
       case $ac_src in
         *.c) ac_comp="$b_c_pre $ac_extra $ac_inc $b_c_meta -c $ac_srcdir$ac_src -o $ac_bdir$ac_obj.$b_lo $b_c_post" ;;
@@ -27441,7 +27675,7 @@ fi
 php_enable_calendar=no
 
 echo $ac_n "checking whether to enable calendar conversion support""... $ac_c" 1>&6
-echo "configure:27445: checking whether to enable calendar conversion support" >&5
+echo "configure:27684: checking whether to enable calendar conversion support" >&5
 # Check whether --enable-calendar or --disable-calendar was given.
 if test "${enable_calendar+set}" = set; then
   enableval="$enable_calendar"
@@ -27745,7 +27979,7 @@ EOF
       ac_obj=$1
       IFS=$old_IFS
       
-      PHP_CLI_OBJS="$PHP_CLI_OBJS $ac_bdir$ac_obj.lo"
+      PHP_GLOBAL_OBJS="$PHP_GLOBAL_OBJS $ac_bdir$ac_obj.lo"
 
       case $ac_src in
         *.c) ac_comp="$b_c_pre $ac_extra $ac_inc $b_c_meta -c $ac_srcdir$ac_src -o $ac_bdir$ac_obj.$b_lo $b_c_post" ;;
@@ -27785,7 +28019,7 @@ fi
 php_enable_ctype=yes
 
 echo $ac_n "checking whether to enable ctype functions""... $ac_c" 1>&6
-echo "configure:27789: checking whether to enable ctype functions" >&5
+echo "configure:28028: checking whether to enable ctype functions" >&5
 # Check whether --enable-ctype or --disable-ctype was given.
 if test "${enable_ctype+set}" = set; then
   enableval="$enable_ctype"
@@ -28089,7 +28323,7 @@ EOF
       ac_obj=$1
       IFS=$old_IFS
       
-      PHP_CLI_OBJS="$PHP_CLI_OBJS $ac_bdir$ac_obj.lo"
+      PHP_GLOBAL_OBJS="$PHP_GLOBAL_OBJS $ac_bdir$ac_obj.lo"
 
       case $ac_src in
         *.c) ac_comp="$b_c_pre $ac_extra $ac_inc $b_c_meta -c $ac_srcdir$ac_src -o $ac_bdir$ac_obj.$b_lo $b_c_post" ;;
@@ -28129,7 +28363,7 @@ fi
 php_with_curl=no
 
 echo $ac_n "checking for cURL support""... $ac_c" 1>&6
-echo "configure:28133: checking for cURL support" >&5
+echo "configure:28372: checking for cURL support" >&5
 # Check whether --with-curl or --without-curl was given.
 if test "${with_curl+set}" = set; then
   withval="$with_curl"
@@ -28173,7 +28407,7 @@ echo "$ac_t""$ext_output" 1>&6
 php_with_curlwrappers=no
 
 echo $ac_n "checking if we should use cURL for url streams""... $ac_c" 1>&6
-echo "configure:28177: checking if we should use cURL for url streams" >&5
+echo "configure:28416: checking if we should use cURL for url streams" >&5
 # Check whether --with-curlwrappers or --without-curlwrappers was given.
 if test "${with_curlwrappers+set}" = set; then
   withval="$with_curlwrappers"
@@ -28197,7 +28431,7 @@ if test "$PHP_CURL" != "no"; then
     CURL_DIR=$PHP_CURL
   else
     echo $ac_n "checking for cURL in default path""... $ac_c" 1>&6
-echo "configure:28201: checking for cURL in default path" >&5
+echo "configure:28440: checking for cURL in default path" >&5
     for i in /usr/local /usr; do
       if test -r $i/include/curl/easy.h; then
         CURL_DIR=$i
@@ -28215,7 +28449,7 @@ echo "configure:28201: checking for cURL in default path" >&5
 
   CURL_CONFIG="curl-config"
   echo $ac_n "checking for cURL 7.10.5 or greater""... $ac_c" 1>&6
-echo "configure:28219: checking for cURL 7.10.5 or greater" >&5
+echo "configure:28458: checking for cURL 7.10.5 or greater" >&5
 
   if ${CURL_DIR}/bin/curl-config --libs > /dev/null 2>&1; then
     CURL_CONFIG=${CURL_DIR}/bin/curl-config
@@ -28453,7 +28687,7 @@ echo "configure:28219: checking for cURL 7.10.5 or greater" >&5
 
   
   echo $ac_n "checking for SSL support in libcurl""... $ac_c" 1>&6
-echo "configure:28457: checking for SSL support in libcurl" >&5
+echo "configure:28696: checking for SSL support in libcurl" >&5
   CURL_SSL=`$CURL_CONFIG --feature | $EGREP SSL`
   if test "$CURL_SSL" = "SSL"; then
     echo "$ac_t""yes" 1>&6
@@ -28466,7 +28700,7 @@ EOF
     CFLAGS="`$CURL_CONFIG --cflags`"
    
     echo $ac_n "checking how to run the C preprocessor""... $ac_c" 1>&6
-echo "configure:28470: checking how to run the C preprocessor" >&5
+echo "configure:28709: checking how to run the C preprocessor" >&5
 # On Suns, sometimes $CPP names a directory.
 if test -n "$CPP" && test -d "$CPP"; then
   CPP=
@@ -28481,13 +28715,13 @@ else
   # On the NeXT, cc -E runs the code through the compiler's parser,
   # not just through cpp.
   cat > conftest.$ac_ext <<EOF
-#line 28485 "configure"
+#line 28724 "configure"
 #include "confdefs.h"
 #include <assert.h>
 Syntax Error
 EOF
 ac_try="$ac_cpp conftest.$ac_ext >/dev/null 2>conftest.out"
-{ (eval echo configure:28491: \"$ac_try\") 1>&5; (eval $ac_try) 2>&5; }
+{ (eval echo configure:28730: \"$ac_try\") 1>&5; (eval $ac_try) 2>&5; }
 ac_err=`grep -v '^ *+' conftest.out | grep -v "^conftest.${ac_ext}\$"`
 if test -z "$ac_err"; then
   :
@@ -28498,13 +28732,13 @@ else
   rm -rf conftest*
   CPP="${CC-cc} -E -traditional-cpp"
   cat > conftest.$ac_ext <<EOF
-#line 28502 "configure"
+#line 28741 "configure"
 #include "confdefs.h"
 #include <assert.h>
 Syntax Error
 EOF
 ac_try="$ac_cpp conftest.$ac_ext >/dev/null 2>conftest.out"
-{ (eval echo configure:28508: \"$ac_try\") 1>&5; (eval $ac_try) 2>&5; }
+{ (eval echo configure:28747: \"$ac_try\") 1>&5; (eval $ac_try) 2>&5; }
 ac_err=`grep -v '^ *+' conftest.out | grep -v "^conftest.${ac_ext}\$"`
 if test -z "$ac_err"; then
   :
@@ -28515,13 +28749,13 @@ else
   rm -rf conftest*
   CPP="${CC-cc} -nologo -E"
   cat > conftest.$ac_ext <<EOF
-#line 28519 "configure"
+#line 28758 "configure"
 #include "confdefs.h"
 #include <assert.h>
 Syntax Error
 EOF
 ac_try="$ac_cpp conftest.$ac_ext >/dev/null 2>conftest.out"
-{ (eval echo configure:28525: \"$ac_try\") 1>&5; (eval $ac_try) 2>&5; }
+{ (eval echo configure:28764: \"$ac_try\") 1>&5; (eval $ac_try) 2>&5; }
 ac_err=`grep -v '^ *+' conftest.out | grep -v "^conftest.${ac_ext}\$"`
 if test -z "$ac_err"; then
   :
@@ -28546,14 +28780,14 @@ fi
 echo "$ac_t""$CPP" 1>&6
 
     echo $ac_n "checking for openssl support in libcurl""... $ac_c" 1>&6
-echo "configure:28550: checking for openssl support in libcurl" >&5
+echo "configure:28789: checking for openssl support in libcurl" >&5
     if test "$cross_compiling" = yes; then
   
       echo "$ac_t""no" 1>&6
     
 else
   cat > conftest.$ac_ext <<EOF
-#line 28557 "configure"
+#line 28796 "configure"
 #include "confdefs.h"
 
 #include <curl/curl.h>
@@ -28572,7 +28806,7 @@ int main(int argc, char *argv[])
 }
     
 EOF
-if { (eval echo configure:28576: \"$ac_link\") 1>&5; (eval $ac_link) 2>&5; } && test -s conftest${ac_exeext} && (./conftest; exit) 2>/dev/null
+if { (eval echo configure:28815: \"$ac_link\") 1>&5; (eval $ac_link) 2>&5; } && test -s conftest${ac_exeext} && (./conftest; exit) 2>/dev/null
 then
   
       echo "$ac_t""yes" 1>&6
@@ -28580,17 +28814,17 @@ then
 do
 ac_safe=`echo "$ac_hdr" | sed 'y%./+-%__p_%'`
 echo $ac_n "checking for $ac_hdr""... $ac_c" 1>&6
-echo "configure:28584: checking for $ac_hdr" >&5
+echo "configure:28823: checking for $ac_hdr" >&5
 if eval "test \"`echo '$''{'ac_cv_header_$ac_safe'+set}'`\" = set"; then
   echo $ac_n "(cached) $ac_c" 1>&6
 else
   cat > conftest.$ac_ext <<EOF
-#line 28589 "configure"
+#line 28828 "configure"
 #include "confdefs.h"
 #include <$ac_hdr>
 EOF
 ac_try="$ac_cpp conftest.$ac_ext >/dev/null 2>conftest.out"
-{ (eval echo configure:28594: \"$ac_try\") 1>&5; (eval $ac_try) 2>&5; }
+{ (eval echo configure:28833: \"$ac_try\") 1>&5; (eval $ac_try) 2>&5; }
 ac_err=`grep -v '^ *+' conftest.out | grep -v "^conftest.${ac_ext}\$"`
 if test -z "$ac_err"; then
   rm -rf conftest*
@@ -28635,14 +28869,14 @@ fi
 
    
     echo $ac_n "checking for gnutls support in libcurl""... $ac_c" 1>&6
-echo "configure:28639: checking for gnutls support in libcurl" >&5
+echo "configure:28878: checking for gnutls support in libcurl" >&5
     if test "$cross_compiling" = yes; then
   
       echo "$ac_t""no" 1>&6
     
 else
   cat > conftest.$ac_ext <<EOF
-#line 28646 "configure"
+#line 28885 "configure"
 #include "confdefs.h"
 
 #include <curl/curl.h>
@@ -28661,23 +28895,23 @@ int main(int argc, char *argv[])
 }
 
 EOF
-if { (eval echo configure:28665: \"$ac_link\") 1>&5; (eval $ac_link) 2>&5; } && test -s conftest${ac_exeext} && (./conftest; exit) 2>/dev/null
+if { (eval echo configure:28904: \"$ac_link\") 1>&5; (eval $ac_link) 2>&5; } && test -s conftest${ac_exeext} && (./conftest; exit) 2>/dev/null
 then
   
       echo "$ac_t""yes" 1>&6
       ac_safe=`echo "gcrypt.h" | sed 'y%./+-%__p_%'`
 echo $ac_n "checking for gcrypt.h""... $ac_c" 1>&6
-echo "configure:28671: checking for gcrypt.h" >&5
+echo "configure:28910: checking for gcrypt.h" >&5
 if eval "test \"`echo '$''{'ac_cv_header_$ac_safe'+set}'`\" = set"; then
   echo $ac_n "(cached) $ac_c" 1>&6
 else
   cat > conftest.$ac_ext <<EOF
-#line 28676 "configure"
+#line 28915 "configure"
 #include "confdefs.h"
 #include <gcrypt.h>
 EOF
 ac_try="$ac_cpp conftest.$ac_ext >/dev/null 2>conftest.out"
-{ (eval echo configure:28681: \"$ac_try\") 1>&5; (eval $ac_try) 2>&5; }
+{ (eval echo configure:28920: \"$ac_try\") 1>&5; (eval $ac_try) 2>&5; }
 ac_err=`grep -v '^ *+' conftest.out | grep -v "^conftest.${ac_ext}\$"`
 if test -z "$ac_err"; then
   rm -rf conftest*
@@ -28819,7 +29053,7 @@ fi
   done
 
   echo $ac_n "checking for curl_easy_perform in -lcurl""... $ac_c" 1>&6
-echo "configure:28823: checking for curl_easy_perform in -lcurl" >&5
+echo "configure:29062: checking for curl_easy_perform in -lcurl" >&5
 ac_lib_var=`echo curl'_'curl_easy_perform | sed 'y%./+-%__p_%'`
 if eval "test \"`echo '$''{'ac_cv_lib_$ac_lib_var'+set}'`\" = set"; then
   echo $ac_n "(cached) $ac_c" 1>&6
@@ -28827,7 +29061,7 @@ else
   ac_save_LIBS="$LIBS"
 LIBS="-lcurl  $LIBS"
 cat > conftest.$ac_ext <<EOF
-#line 28831 "configure"
+#line 29070 "configure"
 #include "confdefs.h"
 /* Override any gcc2 internal prototype to avoid an error.  */
 /* We use char because int might match the return type of a gcc2
@@ -28838,7 +29072,7 @@ int main() {
 curl_easy_perform()
 ; return 0; }
 EOF
-if { (eval echo configure:28842: \"$ac_link\") 1>&5; (eval $ac_link) 2>&5; } && test -s conftest${ac_exeext}; then
+if { (eval echo configure:29081: \"$ac_link\") 1>&5; (eval $ac_link) 2>&5; } && test -s conftest${ac_exeext}; then
   rm -rf conftest*
   eval "ac_cv_lib_$ac_lib_var=yes"
 else
@@ -28974,7 +29208,7 @@ fi
   done
 
   echo $ac_n "checking for curl_version_info in -lcurl""... $ac_c" 1>&6
-echo "configure:28978: checking for curl_version_info in -lcurl" >&5
+echo "configure:29217: checking for curl_version_info in -lcurl" >&5
 ac_lib_var=`echo curl'_'curl_version_info | sed 'y%./+-%__p_%'`
 if eval "test \"`echo '$''{'ac_cv_lib_$ac_lib_var'+set}'`\" = set"; then
   echo $ac_n "(cached) $ac_c" 1>&6
@@ -28982,7 +29216,7 @@ else
   ac_save_LIBS="$LIBS"
 LIBS="-lcurl  $LIBS"
 cat > conftest.$ac_ext <<EOF
-#line 28986 "configure"
+#line 29225 "configure"
 #include "confdefs.h"
 /* Override any gcc2 internal prototype to avoid an error.  */
 /* We use char because int might match the return type of a gcc2
@@ -28993,7 +29227,7 @@ int main() {
 curl_version_info()
 ; return 0; }
 EOF
-if { (eval echo configure:28997: \"$ac_link\") 1>&5; (eval $ac_link) 2>&5; } && test -s conftest${ac_exeext}; then
+if { (eval echo configure:29236: \"$ac_link\") 1>&5; (eval $ac_link) 2>&5; } && test -s conftest${ac_exeext}; then
   rm -rf conftest*
   eval "ac_cv_lib_$ac_lib_var=yes"
 else
@@ -29127,7 +29361,7 @@ fi
   done
 
   echo $ac_n "checking for curl_easy_strerror in -lcurl""... $ac_c" 1>&6
-echo "configure:29131: checking for curl_easy_strerror in -lcurl" >&5
+echo "configure:29370: checking for curl_easy_strerror in -lcurl" >&5
 ac_lib_var=`echo curl'_'curl_easy_strerror | sed 'y%./+-%__p_%'`
 if eval "test \"`echo '$''{'ac_cv_lib_$ac_lib_var'+set}'`\" = set"; then
   echo $ac_n "(cached) $ac_c" 1>&6
@@ -29135,7 +29369,7 @@ else
   ac_save_LIBS="$LIBS"
 LIBS="-lcurl  $LIBS"
 cat > conftest.$ac_ext <<EOF
-#line 29139 "configure"
+#line 29378 "configure"
 #include "confdefs.h"
 /* Override any gcc2 internal prototype to avoid an error.  */
 /* We use char because int might match the return type of a gcc2
@@ -29146,7 +29380,7 @@ int main() {
 curl_easy_strerror()
 ; return 0; }
 EOF
-if { (eval echo configure:29150: \"$ac_link\") 1>&5; (eval $ac_link) 2>&5; } && test -s conftest${ac_exeext}; then
+if { (eval echo configure:29389: \"$ac_link\") 1>&5; (eval $ac_link) 2>&5; } && test -s conftest${ac_exeext}; then
   rm -rf conftest*
   eval "ac_cv_lib_$ac_lib_var=yes"
 else
@@ -29280,7 +29514,7 @@ fi
   done
 
   echo $ac_n "checking for curl_multi_strerror in -lcurl""... $ac_c" 1>&6
-echo "configure:29284: checking for curl_multi_strerror in -lcurl" >&5
+echo "configure:29523: checking for curl_multi_strerror in -lcurl" >&5
 ac_lib_var=`echo curl'_'curl_multi_strerror | sed 'y%./+-%__p_%'`
 if eval "test \"`echo '$''{'ac_cv_lib_$ac_lib_var'+set}'`\" = set"; then
   echo $ac_n "(cached) $ac_c" 1>&6
@@ -29288,7 +29522,7 @@ else
   ac_save_LIBS="$LIBS"
 LIBS="-lcurl  $LIBS"
 cat > conftest.$ac_ext <<EOF
-#line 29292 "configure"
+#line 29531 "configure"
 #include "confdefs.h"
 /* Override any gcc2 internal prototype to avoid an error.  */
 /* We use char because int might match the return type of a gcc2
@@ -29299,7 +29533,7 @@ int main() {
 curl_multi_strerror()
 ; return 0; }
 EOF
-if { (eval echo configure:29303: \"$ac_link\") 1>&5; (eval $ac_link) 2>&5; } && test -s conftest${ac_exeext}; then
+if { (eval echo configure:29542: \"$ac_link\") 1>&5; (eval $ac_link) 2>&5; } && test -s conftest${ac_exeext}; then
   rm -rf conftest*
   eval "ac_cv_lib_$ac_lib_var=yes"
 else
@@ -29601,7 +29835,7 @@ EOF
       ac_obj=$1
       IFS=$old_IFS
       
-      PHP_CLI_OBJS="$PHP_CLI_OBJS $ac_bdir$ac_obj.lo"
+      PHP_GLOBAL_OBJS="$PHP_GLOBAL_OBJS $ac_bdir$ac_obj.lo"
 
       case $ac_src in
         *.c) ac_comp="$b_c_pre $ac_extra $ac_inc $b_c_meta -c $ac_srcdir$ac_src -o $ac_bdir$ac_obj.$b_lo $b_c_post" ;;
@@ -29985,7 +30219,7 @@ if test "$PHP_QDBM" != "no"; then
   done
 
   echo $ac_n "checking for dpopen in -l$LIB""... $ac_c" 1>&6
-echo "configure:29989: checking for dpopen in -l$LIB" >&5
+echo "configure:30228: checking for dpopen in -l$LIB" >&5
 ac_lib_var=`echo $LIB'_'dpopen | sed 'y%./+-%__p_%'`
 if eval "test \"`echo '$''{'ac_cv_lib_$ac_lib_var'+set}'`\" = set"; then
   echo $ac_n "(cached) $ac_c" 1>&6
@@ -29993,7 +30227,7 @@ else
   ac_save_LIBS="$LIBS"
 LIBS="-l$LIB  $LIBS"
 cat > conftest.$ac_ext <<EOF
-#line 29997 "configure"
+#line 30236 "configure"
 #include "confdefs.h"
 /* Override any gcc2 internal prototype to avoid an error.  */
 /* We use char because int might match the return type of a gcc2
@@ -30004,7 +30238,7 @@ int main() {
 dpopen()
 ; return 0; }
 EOF
-if { (eval echo configure:30008: \"$ac_link\") 1>&5; (eval $ac_link) 2>&5; } && test -s conftest${ac_exeext}; then
+if { (eval echo configure:30247: \"$ac_link\") 1>&5; (eval $ac_link) 2>&5; } && test -s conftest${ac_exeext}; then
   rm -rf conftest*
   eval "ac_cv_lib_$ac_lib_var=yes"
 else
@@ -30173,7 +30407,7 @@ fi
     THIS_FULL_NAME="$THIS_NAME"
   fi
   echo $ac_n "checking for $THIS_FULL_NAME support""... $ac_c" 1>&6
-echo "configure:30177: checking for $THIS_FULL_NAME support" >&5
+echo "configure:30416: checking for $THIS_FULL_NAME support" >&5
   if test -n ""; then
     { echo "configure: error: " 1>&2; exit 1; }
   fi
@@ -30201,7 +30435,7 @@ if test "$PHP_GDBM" != "no"; then
     THIS_FULL_NAME="$THIS_NAME"
   fi
   echo $ac_n "checking for $THIS_FULL_NAME support""... $ac_c" 1>&6
-echo "configure:30205: checking for $THIS_FULL_NAME support" >&5
+echo "configure:30444: checking for $THIS_FULL_NAME support" >&5
   if test -n "You cannot combine --with-gdbm with --with-qdbm"; then
     { echo "configure: error: You cannot combine --with-gdbm with --with-qdbm" 1>&2; exit 1; }
   fi
@@ -30320,7 +30554,7 @@ echo "configure:30205: checking for $THIS_FULL_NAME support" >&5
   done
 
   echo $ac_n "checking for gdbm_open in -lgdbm""... $ac_c" 1>&6
-echo "configure:30324: checking for gdbm_open in -lgdbm" >&5
+echo "configure:30563: checking for gdbm_open in -lgdbm" >&5
 ac_lib_var=`echo gdbm'_'gdbm_open | sed 'y%./+-%__p_%'`
 if eval "test \"`echo '$''{'ac_cv_lib_$ac_lib_var'+set}'`\" = set"; then
   echo $ac_n "(cached) $ac_c" 1>&6
@@ -30328,7 +30562,7 @@ else
   ac_save_LIBS="$LIBS"
 LIBS="-lgdbm  $LIBS"
 cat > conftest.$ac_ext <<EOF
-#line 30332 "configure"
+#line 30571 "configure"
 #include "confdefs.h"
 /* Override any gcc2 internal prototype to avoid an error.  */
 /* We use char because int might match the return type of a gcc2
@@ -30339,7 +30573,7 @@ int main() {
 gdbm_open()
 ; return 0; }
 EOF
-if { (eval echo configure:30343: \"$ac_link\") 1>&5; (eval $ac_link) 2>&5; } && test -s conftest${ac_exeext}; then
+if { (eval echo configure:30582: \"$ac_link\") 1>&5; (eval $ac_link) 2>&5; } && test -s conftest${ac_exeext}; then
   rm -rf conftest*
   eval "ac_cv_lib_$ac_lib_var=yes"
 else
@@ -30504,7 +30738,7 @@ fi
     THIS_FULL_NAME="$THIS_NAME"
   fi
   echo $ac_n "checking for $THIS_FULL_NAME support""... $ac_c" 1>&6
-echo "configure:30508: checking for $THIS_FULL_NAME support" >&5
+echo "configure:30747: checking for $THIS_FULL_NAME support" >&5
   if test -n ""; then
     { echo "configure: error: " 1>&2; exit 1; }
   fi
@@ -30633,7 +30867,7 @@ if test "$PHP_NDBM" != "no"; then
   done
 
   echo $ac_n "checking for dbm_open in -l$LIB""... $ac_c" 1>&6
-echo "configure:30637: checking for dbm_open in -l$LIB" >&5
+echo "configure:30876: checking for dbm_open in -l$LIB" >&5
 ac_lib_var=`echo $LIB'_'dbm_open | sed 'y%./+-%__p_%'`
 if eval "test \"`echo '$''{'ac_cv_lib_$ac_lib_var'+set}'`\" = set"; then
   echo $ac_n "(cached) $ac_c" 1>&6
@@ -30641,7 +30875,7 @@ else
   ac_save_LIBS="$LIBS"
 LIBS="-l$LIB  $LIBS"
 cat > conftest.$ac_ext <<EOF
-#line 30645 "configure"
+#line 30884 "configure"
 #include "confdefs.h"
 /* Override any gcc2 internal prototype to avoid an error.  */
 /* We use char because int might match the return type of a gcc2
@@ -30652,7 +30886,7 @@ int main() {
 dbm_open()
 ; return 0; }
 EOF
-if { (eval echo configure:30656: \"$ac_link\") 1>&5; (eval $ac_link) 2>&5; } && test -s conftest${ac_exeext}; then
+if { (eval echo configure:30895: \"$ac_link\") 1>&5; (eval $ac_link) 2>&5; } && test -s conftest${ac_exeext}; then
   rm -rf conftest*
   eval "ac_cv_lib_$ac_lib_var=yes"
 else
@@ -30821,7 +31055,7 @@ fi
     THIS_FULL_NAME="$THIS_NAME"
   fi
   echo $ac_n "checking for $THIS_FULL_NAME support""... $ac_c" 1>&6
-echo "configure:30825: checking for $THIS_FULL_NAME support" >&5
+echo "configure:31064: checking for $THIS_FULL_NAME support" >&5
   if test -n ""; then
     { echo "configure: error: " 1>&2; exit 1; }
   fi
@@ -30909,7 +31143,7 @@ if test "$PHP_DB4" != "no"; then
   LIBS="-l$LIB $LIBS"
   
         cat > conftest.$ac_ext <<EOF
-#line 30913 "configure"
+#line 31152 "configure"
 #include "confdefs.h"
 
 #include "$THIS_INCLUDE"
@@ -30920,11 +31154,11 @@ int main() {
         
 ; return 0; }
 EOF
-if { (eval echo configure:30924: \"$ac_link\") 1>&5; (eval $ac_link) 2>&5; } && test -s conftest${ac_exeext}; then
+if { (eval echo configure:31163: \"$ac_link\") 1>&5; (eval $ac_link) 2>&5; } && test -s conftest${ac_exeext}; then
   rm -rf conftest*
   
           cat > conftest.$ac_ext <<EOF
-#line 30928 "configure"
+#line 31167 "configure"
 #include "confdefs.h"
 
 #include "$THIS_INCLUDE"
@@ -30961,14 +31195,14 @@ rm -f conftest*
   done
   if test -z "$THIS_LIBS"; then
     echo $ac_n "checking for DB4 major version""... $ac_c" 1>&6
-echo "configure:30965: checking for DB4 major version" >&5
+echo "configure:31204: checking for DB4 major version" >&5
     { echo "configure: error: Header contains different version" 1>&2; exit 1; }
   fi
   if test "4" = "4"; then
     echo $ac_n "checking for DB4 minor version and patch level""... $ac_c" 1>&6
-echo "configure:30970: checking for DB4 minor version and patch level" >&5
+echo "configure:31209: checking for DB4 minor version and patch level" >&5
     cat > conftest.$ac_ext <<EOF
-#line 30972 "configure"
+#line 31211 "configure"
 #include "confdefs.h"
 
 #include "$THIS_INCLUDE"
@@ -30994,9 +31228,9 @@ rm -f conftest*
   fi
   if test "$ext_shared" = "yes"; then
     echo $ac_n "checking if dba can be used as shared extension""... $ac_c" 1>&6
-echo "configure:30998: checking if dba can be used as shared extension" >&5
+echo "configure:31237: checking if dba can be used as shared extension" >&5
     cat > conftest.$ac_ext <<EOF
-#line 31000 "configure"
+#line 31239 "configure"
 #include "confdefs.h"
 
 #include "$THIS_INCLUDE"
@@ -31153,7 +31387,7 @@ fi
     THIS_FULL_NAME="$THIS_NAME"
   fi
   echo $ac_n "checking for $THIS_FULL_NAME support""... $ac_c" 1>&6
-echo "configure:31157: checking for $THIS_FULL_NAME support" >&5
+echo "configure:31396: checking for $THIS_FULL_NAME support" >&5
   if test -n ""; then
     { echo "configure: error: " 1>&2; exit 1; }
   fi
@@ -31181,7 +31415,7 @@ if test "$PHP_DB3" != "no"; then
     THIS_FULL_NAME="$THIS_NAME"
   fi
   echo $ac_n "checking for $THIS_FULL_NAME support""... $ac_c" 1>&6
-echo "configure:31185: checking for $THIS_FULL_NAME support" >&5
+echo "configure:31424: checking for $THIS_FULL_NAME support" >&5
   if test -n "You cannot combine --with-db3 with --with-db4"; then
     { echo "configure: error: You cannot combine --with-db3 with --with-db4" 1>&2; exit 1; }
   fi
@@ -31232,7 +31466,7 @@ echo "configure:31185: checking for $THIS_FULL_NAME support" >&5
   LIBS="-l$LIB $LIBS"
   
         cat > conftest.$ac_ext <<EOF
-#line 31236 "configure"
+#line 31475 "configure"
 #include "confdefs.h"
 
 #include "$THIS_INCLUDE"
@@ -31243,11 +31477,11 @@ int main() {
         
 ; return 0; }
 EOF
-if { (eval echo configure:31247: \"$ac_link\") 1>&5; (eval $ac_link) 2>&5; } && test -s conftest${ac_exeext}; then
+if { (eval echo configure:31486: \"$ac_link\") 1>&5; (eval $ac_link) 2>&5; } && test -s conftest${ac_exeext}; then
   rm -rf conftest*
   
           cat > conftest.$ac_ext <<EOF
-#line 31251 "configure"
+#line 31490 "configure"
 #include "confdefs.h"
 
 #include "$THIS_INCLUDE"
@@ -31284,14 +31518,14 @@ rm -f conftest*
   done
   if test -z "$THIS_LIBS"; then
     echo $ac_n "checking for DB3 major version""... $ac_c" 1>&6
-echo "configure:31288: checking for DB3 major version" >&5
+echo "configure:31527: checking for DB3 major version" >&5
     { echo "configure: error: Header contains different version" 1>&2; exit 1; }
   fi
   if test "3" = "4"; then
     echo $ac_n "checking for DB4 minor version and patch level""... $ac_c" 1>&6
-echo "configure:31293: checking for DB4 minor version and patch level" >&5
+echo "configure:31532: checking for DB4 minor version and patch level" >&5
     cat > conftest.$ac_ext <<EOF
-#line 31295 "configure"
+#line 31534 "configure"
 #include "confdefs.h"
 
 #include "$THIS_INCLUDE"
@@ -31317,9 +31551,9 @@ rm -f conftest*
   fi
   if test "$ext_shared" = "yes"; then
     echo $ac_n "checking if dba can be used as shared extension""... $ac_c" 1>&6
-echo "configure:31321: checking if dba can be used as shared extension" >&5
+echo "configure:31560: checking if dba can be used as shared extension" >&5
     cat > conftest.$ac_ext <<EOF
-#line 31323 "configure"
+#line 31562 "configure"
 #include "confdefs.h"
 
 #include "$THIS_INCLUDE"
@@ -31476,7 +31710,7 @@ fi
     THIS_FULL_NAME="$THIS_NAME"
   fi
   echo $ac_n "checking for $THIS_FULL_NAME support""... $ac_c" 1>&6
-echo "configure:31480: checking for $THIS_FULL_NAME support" >&5
+echo "configure:31719: checking for $THIS_FULL_NAME support" >&5
   if test -n ""; then
     { echo "configure: error: " 1>&2; exit 1; }
   fi
@@ -31504,7 +31738,7 @@ if test "$PHP_DB2" != "no"; then
     THIS_FULL_NAME="$THIS_NAME"
   fi
   echo $ac_n "checking for $THIS_FULL_NAME support""... $ac_c" 1>&6
-echo "configure:31508: checking for $THIS_FULL_NAME support" >&5
+echo "configure:31747: checking for $THIS_FULL_NAME support" >&5
   if test -n "You cannot combine --with-db2 with --with-db3 or --with-db4"; then
     { echo "configure: error: You cannot combine --with-db2 with --with-db3 or --with-db4" 1>&2; exit 1; }
   fi
@@ -31555,7 +31789,7 @@ echo "configure:31508: checking for $THIS_FULL_NAME support" >&5
   LIBS="-l$LIB $LIBS"
   
         cat > conftest.$ac_ext <<EOF
-#line 31559 "configure"
+#line 31798 "configure"
 #include "confdefs.h"
 
 #include "$THIS_INCLUDE"
@@ -31566,11 +31800,11 @@ int main() {
         
 ; return 0; }
 EOF
-if { (eval echo configure:31570: \"$ac_link\") 1>&5; (eval $ac_link) 2>&5; } && test -s conftest${ac_exeext}; then
+if { (eval echo configure:31809: \"$ac_link\") 1>&5; (eval $ac_link) 2>&5; } && test -s conftest${ac_exeext}; then
   rm -rf conftest*
   
           cat > conftest.$ac_ext <<EOF
-#line 31574 "configure"
+#line 31813 "configure"
 #include "confdefs.h"
 
 #include "$THIS_INCLUDE"
@@ -31607,14 +31841,14 @@ rm -f conftest*
   done
   if test -z "$THIS_LIBS"; then
     echo $ac_n "checking for DB2 major version""... $ac_c" 1>&6
-echo "configure:31611: checking for DB2 major version" >&5
+echo "configure:31850: checking for DB2 major version" >&5
     { echo "configure: error: Header contains different version" 1>&2; exit 1; }
   fi
   if test "2" = "4"; then
     echo $ac_n "checking for DB4 minor version and patch level""... $ac_c" 1>&6
-echo "configure:31616: checking for DB4 minor version and patch level" >&5
+echo "configure:31855: checking for DB4 minor version and patch level" >&5
     cat > conftest.$ac_ext <<EOF
-#line 31618 "configure"
+#line 31857 "configure"
 #include "confdefs.h"
 
 #include "$THIS_INCLUDE"
@@ -31640,9 +31874,9 @@ rm -f conftest*
   fi
   if test "$ext_shared" = "yes"; then
     echo $ac_n "checking if dba can be used as shared extension""... $ac_c" 1>&6
-echo "configure:31644: checking if dba can be used as shared extension" >&5
+echo "configure:31883: checking if dba can be used as shared extension" >&5
     cat > conftest.$ac_ext <<EOF
-#line 31646 "configure"
+#line 31885 "configure"
 #include "confdefs.h"
 
 #include "$THIS_INCLUDE"
@@ -31799,7 +32033,7 @@ fi
     THIS_FULL_NAME="$THIS_NAME"
   fi
   echo $ac_n "checking for $THIS_FULL_NAME support""... $ac_c" 1>&6
-echo "configure:31803: checking for $THIS_FULL_NAME support" >&5
+echo "configure:32042: checking for $THIS_FULL_NAME support" >&5
   if test -n ""; then
     { echo "configure: error: " 1>&2; exit 1; }
   fi
@@ -31819,7 +32053,7 @@ if test "$PHP_DB1" != "no"; then
   unset THIS_INCLUDE THIS_LIBS THIS_LFLAGS THIS_PREFIX THIS_RESULT
 
   echo $ac_n "checking for DB1 in library""... $ac_c" 1>&6
-echo "configure:31823: checking for DB1 in library" >&5
+echo "configure:32062: checking for DB1 in library" >&5
   if test "$HAVE_DB4" = "1"; then
     THIS_VERSION=4
     THIS_LIBS=$DB4_LIBS
@@ -31867,7 +32101,7 @@ EOF
   fi
   echo "$ac_t""$THIS_LIBS" 1>&6
   echo $ac_n "checking for DB1 in header""... $ac_c" 1>&6
-echo "configure:31871: checking for DB1 in header" >&5
+echo "configure:32110: checking for DB1 in header" >&5
   echo "$ac_t""$THIS_INCLUDE" 1>&6
   if test -n "$THIS_INCLUDE"; then
     
@@ -31877,7 +32111,7 @@ echo "configure:31871: checking for DB1 in header" >&5
   LIBS="-l$THIS_LIBS $LIBS"
   
       cat > conftest.$ac_ext <<EOF
-#line 31881 "configure"
+#line 32120 "configure"
 #include "confdefs.h"
 
 #include "$THIS_INCLUDE"
@@ -31888,7 +32122,7 @@ int main() {
       
 ; return 0; }
 EOF
-if { (eval echo configure:31892: \"$ac_link\") 1>&5; (eval $ac_link) 2>&5; } && test -s conftest${ac_exeext}; then
+if { (eval echo configure:32131: \"$ac_link\") 1>&5; (eval $ac_link) 2>&5; } && test -s conftest${ac_exeext}; then
   rm -rf conftest*
   
         cat >> confdefs.h <<EOF
@@ -32038,7 +32272,7 @@ fi
     THIS_FULL_NAME="$THIS_NAME"
   fi
   echo $ac_n "checking for $THIS_FULL_NAME support""... $ac_c" 1>&6
-echo "configure:32042: checking for $THIS_FULL_NAME support" >&5
+echo "configure:32281: checking for $THIS_FULL_NAME support" >&5
   if test -n ""; then
     { echo "configure: error: " 1>&2; exit 1; }
   fi
@@ -32066,7 +32300,7 @@ if test "$PHP_DBM" != "no"; then
     THIS_FULL_NAME="$THIS_NAME"
   fi
   echo $ac_n "checking for $THIS_FULL_NAME support""... $ac_c" 1>&6
-echo "configure:32070: checking for $THIS_FULL_NAME support" >&5
+echo "configure:32309: checking for $THIS_FULL_NAME support" >&5
   if test -n "You cannot combine --with-dbm with --with-qdbm"; then
     { echo "configure: error: You cannot combine --with-dbm with --with-qdbm" 1>&2; exit 1; }
   fi
@@ -32190,7 +32424,7 @@ echo "configure:32070: checking for $THIS_FULL_NAME support" >&5
   done
 
   echo $ac_n "checking for dbminit in -l$LIB""... $ac_c" 1>&6
-echo "configure:32194: checking for dbminit in -l$LIB" >&5
+echo "configure:32433: checking for dbminit in -l$LIB" >&5
 ac_lib_var=`echo $LIB'_'dbminit | sed 'y%./+-%__p_%'`
 if eval "test \"`echo '$''{'ac_cv_lib_$ac_lib_var'+set}'`\" = set"; then
   echo $ac_n "(cached) $ac_c" 1>&6
@@ -32198,7 +32432,7 @@ else
   ac_save_LIBS="$LIBS"
 LIBS="-l$LIB  $LIBS"
 cat > conftest.$ac_ext <<EOF
-#line 32202 "configure"
+#line 32441 "configure"
 #include "confdefs.h"
 /* Override any gcc2 internal prototype to avoid an error.  */
 /* We use char because int might match the return type of a gcc2
@@ -32209,7 +32443,7 @@ int main() {
 dbminit()
 ; return 0; }
 EOF
-if { (eval echo configure:32213: \"$ac_link\") 1>&5; (eval $ac_link) 2>&5; } && test -s conftest${ac_exeext}; then
+if { (eval echo configure:32452: \"$ac_link\") 1>&5; (eval $ac_link) 2>&5; } && test -s conftest${ac_exeext}; then
   rm -rf conftest*
   eval "ac_cv_lib_$ac_lib_var=yes"
 else
@@ -32229,7 +32463,7 @@ if eval "test \"`echo '$ac_cv_lib_'$ac_lib_var`\" = yes"; then
     ext_shared=$save_ext_shared
     
         echo $ac_n "checking for DBM using GDBM""... $ac_c" 1>&6
-echo "configure:32233: checking for DBM using GDBM" >&5
+echo "configure:32472: checking for DBM using GDBM" >&5
         cat >> confdefs.h <<EOF
 #define DBM_INCLUDE_FILE "$THIS_INCLUDE"
 EOF
@@ -32393,7 +32627,7 @@ fi
     THIS_FULL_NAME="$THIS_NAME"
   fi
   echo $ac_n "checking for $THIS_FULL_NAME support""... $ac_c" 1>&6
-echo "configure:32397: checking for $THIS_FULL_NAME support" >&5
+echo "configure:32636: checking for $THIS_FULL_NAME support" >&5
   if test -n ""; then
     { echo "configure: error: " 1>&2; exit 1; }
   fi
@@ -32605,7 +32839,7 @@ elif test "$PHP_CDB" != "no"; then
   done
 
   echo $ac_n "checking for cdb_read in -l$LIB""... $ac_c" 1>&6
-echo "configure:32609: checking for cdb_read in -l$LIB" >&5
+echo "configure:32848: checking for cdb_read in -l$LIB" >&5
 ac_lib_var=`echo $LIB'_'cdb_read | sed 'y%./+-%__p_%'`
 if eval "test \"`echo '$''{'ac_cv_lib_$ac_lib_var'+set}'`\" = set"; then
   echo $ac_n "(cached) $ac_c" 1>&6
@@ -32613,7 +32847,7 @@ else
   ac_save_LIBS="$LIBS"
 LIBS="-l$LIB  $LIBS"
 cat > conftest.$ac_ext <<EOF
-#line 32617 "configure"
+#line 32856 "configure"
 #include "confdefs.h"
 /* Override any gcc2 internal prototype to avoid an error.  */
 /* We use char because int might match the return type of a gcc2
@@ -32624,7 +32858,7 @@ int main() {
 cdb_read()
 ; return 0; }
 EOF
-if { (eval echo configure:32628: \"$ac_link\") 1>&5; (eval $ac_link) 2>&5; } && test -s conftest${ac_exeext}; then
+if { (eval echo configure:32867: \"$ac_link\") 1>&5; (eval $ac_link) 2>&5; } && test -s conftest${ac_exeext}; then
   rm -rf conftest*
   eval "ac_cv_lib_$ac_lib_var=yes"
 else
@@ -32793,7 +33027,7 @@ fi
     THIS_FULL_NAME="$THIS_NAME"
   fi
   echo $ac_n "checking for $THIS_FULL_NAME support""... $ac_c" 1>&6
-echo "configure:32797: checking for $THIS_FULL_NAME support" >&5
+echo "configure:33036: checking for $THIS_FULL_NAME support" >&5
   if test -n ""; then
     { echo "configure: error: " 1>&2; exit 1; }
   fi
@@ -32824,7 +33058,7 @@ fi
     THIS_FULL_NAME="$THIS_NAME"
   fi
   echo $ac_n "checking for $THIS_FULL_NAME support""... $ac_c" 1>&6
-echo "configure:32828: checking for $THIS_FULL_NAME support" >&5
+echo "configure:33067: checking for $THIS_FULL_NAME support" >&5
   if test -n ""; then
     { echo "configure: error: " 1>&2; exit 1; }
   fi
@@ -32855,7 +33089,7 @@ fi
     THIS_FULL_NAME="$THIS_NAME"
   fi
   echo $ac_n "checking for $THIS_FULL_NAME support""... $ac_c" 1>&6
-echo "configure:32859: checking for $THIS_FULL_NAME support" >&5
+echo "configure:33098: checking for $THIS_FULL_NAME support" >&5
   if test -n ""; then
     { echo "configure: error: " 1>&2; exit 1; }
   fi
@@ -32870,7 +33104,7 @@ echo "configure:32859: checking for $THIS_FULL_NAME support" >&5
 
 
 echo $ac_n "checking whether to enable DBA interface""... $ac_c" 1>&6
-echo "configure:32874: checking whether to enable DBA interface" >&5
+echo "configure:33113: checking whether to enable DBA interface" >&5
 if test "$HAVE_DBA" = "1"; then
   if test "$ext_shared" = "yes"; then
     echo "$ac_t""yes, shared" 1>&6
@@ -33140,7 +33374,7 @@ EOF
       ac_obj=$1
       IFS=$old_IFS
       
-      PHP_CLI_OBJS="$PHP_CLI_OBJS $ac_bdir$ac_obj.lo"
+      PHP_GLOBAL_OBJS="$PHP_GLOBAL_OBJS $ac_bdir$ac_obj.lo"
 
       case $ac_src in
         *.c) ac_comp="$b_c_pre $ac_extra $ac_inc $b_c_meta -c $ac_srcdir$ac_src -o $ac_bdir$ac_obj.$b_lo $b_c_post" ;;
@@ -33200,7 +33434,7 @@ fi
 php_enable_dom=yes
 
 echo $ac_n "checking whether to enable DOM support""... $ac_c" 1>&6
-echo "configure:33204: checking whether to enable DOM support" >&5
+echo "configure:33443: checking whether to enable DOM support" >&5
 # Check whether --enable-dom or --disable-dom was given.
 if test "${enable_dom+set}" = set; then
   enableval="$enable_dom"
@@ -33245,7 +33479,7 @@ if test -z "$PHP_LIBXML_DIR"; then
 php_with_libxml_dir=no
 
 echo $ac_n "checking libxml2 install dir""... $ac_c" 1>&6
-echo "configure:33249: checking libxml2 install dir" >&5
+echo "configure:33488: checking libxml2 install dir" >&5
 # Check whether --with-libxml-dir or --without-libxml-dir was given.
 if test "${with_libxml_dir+set}" = set; then
   withval="$with_libxml_dir"
@@ -33273,7 +33507,7 @@ if test "$PHP_DOM" != "no"; then
 
   
 echo $ac_n "checking for xml2-config path""... $ac_c" 1>&6
-echo "configure:33277: checking for xml2-config path" >&5
+echo "configure:33516: checking for xml2-config path" >&5
 if eval "test \"`echo '$''{'ac_cv_php_xml2_config_path'+set}'`\" = set"; then
   echo $ac_n "(cached) $ac_c" 1>&6
 else
@@ -33431,7 +33665,7 @@ echo "$ac_t""$ac_cv_php_xml2_config_path" 1>&6
 
 
             echo $ac_n "checking whether libxml build works""... $ac_c" 1>&6
-echo "configure:33435: checking whether libxml build works" >&5
+echo "configure:33674: checking whether libxml build works" >&5
 if eval "test \"`echo '$''{'php_cv_libxml_build_works'+set}'`\" = set"; then
   echo $ac_n "(cached) $ac_c" 1>&6
 else
@@ -33447,7 +33681,7 @@ else
   
 else
   cat > conftest.$ac_ext <<EOF
-#line 33451 "configure"
+#line 33690 "configure"
 #include "confdefs.h"
 
     
@@ -33458,7 +33692,7 @@ else
     }
   
 EOF
-if { (eval echo configure:33462: \"$ac_link\") 1>&5; (eval $ac_link) 2>&5; } && test -s conftest${ac_exeext} && (./conftest; exit) 2>/dev/null
+if { (eval echo configure:33701: \"$ac_link\") 1>&5; (eval $ac_link) 2>&5; } && test -s conftest${ac_exeext} && (./conftest; exit) 2>/dev/null
 then
   
     LIBS=$old_LIBS
@@ -33792,7 +34026,7 @@ EOF
       ac_obj=$1
       IFS=$old_IFS
       
-      PHP_CLI_OBJS="$PHP_CLI_OBJS $ac_bdir$ac_obj.lo"
+      PHP_GLOBAL_OBJS="$PHP_GLOBAL_OBJS $ac_bdir$ac_obj.lo"
 
       case $ac_src in
         *.c) ac_comp="$b_c_pre $ac_extra $ac_inc $b_c_meta -c $ac_srcdir$ac_src -o $ac_bdir$ac_obj.$b_lo $b_c_post" ;;
@@ -33881,7 +34115,7 @@ fi
 php_with_enchant=no
 
 echo $ac_n "checking for ENCHANT support""... $ac_c" 1>&6
-echo "configure:33885: checking for ENCHANT support" >&5
+echo "configure:34124: checking for ENCHANT support" >&5
 # Check whether --with-enchant or --without-enchant was given.
 if test "${with_enchant+set}" = set; then
   withval="$with_enchant"
@@ -34181,7 +34415,7 @@ EOF
       ac_obj=$1
       IFS=$old_IFS
       
-      PHP_CLI_OBJS="$PHP_CLI_OBJS $ac_bdir$ac_obj.lo"
+      PHP_GLOBAL_OBJS="$PHP_GLOBAL_OBJS $ac_bdir$ac_obj.lo"
 
       case $ac_src in
         *.c) ac_comp="$b_c_pre $ac_extra $ac_inc $b_c_meta -c $ac_srcdir$ac_src -o $ac_bdir$ac_obj.$b_lo $b_c_post" ;;
@@ -34466,7 +34700,7 @@ EOF
   done
 
   echo $ac_n "checking for enchant_broker_set_param in -lenchant""... $ac_c" 1>&6
-echo "configure:34470: checking for enchant_broker_set_param in -lenchant" >&5
+echo "configure:34709: checking for enchant_broker_set_param in -lenchant" >&5
 ac_lib_var=`echo enchant'_'enchant_broker_set_param | sed 'y%./+-%__p_%'`
 if eval "test \"`echo '$''{'ac_cv_lib_$ac_lib_var'+set}'`\" = set"; then
   echo $ac_n "(cached) $ac_c" 1>&6
@@ -34474,7 +34708,7 @@ else
   ac_save_LIBS="$LIBS"
 LIBS="-lenchant  $LIBS"
 cat > conftest.$ac_ext <<EOF
-#line 34478 "configure"
+#line 34717 "configure"
 #include "confdefs.h"
 /* Override any gcc2 internal prototype to avoid an error.  */
 /* We use char because int might match the return type of a gcc2
@@ -34485,7 +34719,7 @@ int main() {
 enchant_broker_set_param()
 ; return 0; }
 EOF
-if { (eval echo configure:34489: \"$ac_link\") 1>&5; (eval $ac_link) 2>&5; } && test -s conftest${ac_exeext}; then
+if { (eval echo configure:34728: \"$ac_link\") 1>&5; (eval $ac_link) 2>&5; } && test -s conftest${ac_exeext}; then
   rm -rf conftest*
   eval "ac_cv_lib_$ac_lib_var=yes"
 else
@@ -34532,7 +34766,7 @@ fi
 php_enable_exif=no
 
 echo $ac_n "checking whether to enable EXIF (metadata from images) support""... $ac_c" 1>&6
-echo "configure:34536: checking whether to enable EXIF (metadata from images) support" >&5
+echo "configure:34775: checking whether to enable EXIF (metadata from images) support" >&5
 # Check whether --enable-exif or --disable-exif was given.
 if test "${enable_exif+set}" = set; then
   enableval="$enable_exif"
@@ -34836,7 +35070,7 @@ EOF
       ac_obj=$1
       IFS=$old_IFS
       
-      PHP_CLI_OBJS="$PHP_CLI_OBJS $ac_bdir$ac_obj.lo"
+      PHP_GLOBAL_OBJS="$PHP_GLOBAL_OBJS $ac_bdir$ac_obj.lo"
 
       case $ac_src in
         *.c) ac_comp="$b_c_pre $ac_extra $ac_inc $b_c_meta -c $ac_srcdir$ac_src -o $ac_bdir$ac_obj.$b_lo $b_c_post" ;;
@@ -34876,7 +35110,7 @@ fi
 php_enable_fileinfo=yes
 
 echo $ac_n "checking for fileinfo support""... $ac_c" 1>&6
-echo "configure:34880: checking for fileinfo support" >&5
+echo "configure:35119: checking for fileinfo support" >&5
 # Check whether --enable-fileinfo or --disable-fileinfo was given.
 if test "${enable_fileinfo+set}" = set; then
   enableval="$enable_fileinfo"
@@ -35184,7 +35418,7 @@ EOF
       ac_obj=$1
       IFS=$old_IFS
       
-      PHP_CLI_OBJS="$PHP_CLI_OBJS $ac_bdir$ac_obj.lo"
+      PHP_GLOBAL_OBJS="$PHP_GLOBAL_OBJS $ac_bdir$ac_obj.lo"
 
       case $ac_src in
         *.c) ac_comp="$b_c_pre $ac_extra $ac_inc $b_c_meta -c $ac_srcdir$ac_src -o $ac_bdir$ac_obj.$b_lo $b_c_post" ;;
@@ -35226,12 +35460,12 @@ EOF
   for ac_func in utimes strndup
 do
 echo $ac_n "checking for $ac_func""... $ac_c" 1>&6
-echo "configure:35230: checking for $ac_func" >&5
+echo "configure:35469: checking for $ac_func" >&5
 if eval "test \"`echo '$''{'ac_cv_func_$ac_func'+set}'`\" = set"; then
   echo $ac_n "(cached) $ac_c" 1>&6
 else
   cat > conftest.$ac_ext <<EOF
-#line 35235 "configure"
+#line 35474 "configure"
 #include "confdefs.h"
 /* System header to define __stub macros and hopefully few prototypes,
     which can conflict with char $ac_func(); below.  */
@@ -35254,7 +35488,7 @@ $ac_func();
 
 ; return 0; }
 EOF
-if { (eval echo configure:35258: \"$ac_link\") 1>&5; (eval $ac_link) 2>&5; } && test -s conftest${ac_exeext}; then
+if { (eval echo configure:35497: \"$ac_link\") 1>&5; (eval $ac_link) 2>&5; } && test -s conftest${ac_exeext}; then
   rm -rf conftest*
   eval "ac_cv_func_$ac_func=yes"
 else
@@ -35292,7 +35526,7 @@ fi
 php_enable_filter=yes
 
 echo $ac_n "checking whether to enable input filter support""... $ac_c" 1>&6
-echo "configure:35296: checking whether to enable input filter support" >&5
+echo "configure:35535: checking whether to enable input filter support" >&5
 # Check whether --enable-filter or --disable-filter was given.
 if test "${enable_filter+set}" = set; then
   enableval="$enable_filter"
@@ -35336,7 +35570,7 @@ echo "$ac_t""$ext_output" 1>&6
 php_with_pcre_dir=no
 
 echo $ac_n "checking pcre install prefix""... $ac_c" 1>&6
-echo "configure:35340: checking pcre install prefix" >&5
+echo "configure:35579: checking pcre install prefix" >&5
 # Check whether --with-pcre-dir or --without-pcre-dir was given.
 if test "${with_pcre_dir+set}" = set; then
   withval="$with_pcre_dir"
@@ -35363,7 +35597,7 @@ if test "$PHP_FILTER" != "no"; then
         old_CPPFLAGS=$CPPFLAGS
     CPPFLAGS=$INCLUDES
     cat > conftest.$ac_ext <<EOF
-#line 35367 "configure"
+#line 35606 "configure"
 #include "confdefs.h"
 
 #include <main/php_config.h>
@@ -35382,7 +35616,7 @@ else
   rm -rf conftest*
   
       cat > conftest.$ac_ext <<EOF
-#line 35386 "configure"
+#line 35625 "configure"
 #include "confdefs.h"
 
 #include <main/php_config.h>
@@ -35671,7 +35905,7 @@ EOF
       ac_obj=$1
       IFS=$old_IFS
       
-      PHP_CLI_OBJS="$PHP_CLI_OBJS $ac_bdir$ac_obj.lo"
+      PHP_GLOBAL_OBJS="$PHP_GLOBAL_OBJS $ac_bdir$ac_obj.lo"
 
       case $ac_src in
         *.c) ac_comp="$b_c_pre $ac_extra $ac_inc $b_c_meta -c $ac_srcdir$ac_src -o $ac_bdir$ac_obj.$b_lo $b_c_post" ;;
@@ -35752,7 +35986,7 @@ fi
 php_enable_ftp=no
 
 echo $ac_n "checking whether to enable FTP support""... $ac_c" 1>&6
-echo "configure:35756: checking whether to enable FTP support" >&5
+echo "configure:35995: checking whether to enable FTP support" >&5
 # Check whether --enable-ftp or --disable-ftp was given.
 if test "${enable_ftp+set}" = set; then
   enableval="$enable_ftp"
@@ -35796,7 +36030,7 @@ echo "$ac_t""$ext_output" 1>&6
 php_with_openssl_dir=no
 
 echo $ac_n "checking OpenSSL dir for FTP""... $ac_c" 1>&6
-echo "configure:35800: checking OpenSSL dir for FTP" >&5
+echo "configure:36039: checking OpenSSL dir for FTP" >&5
 # Check whether --with-openssl-dir or --without-openssl-dir was given.
 if test "${with_openssl_dir+set}" = set; then
   withval="$with_openssl_dir"
@@ -36079,7 +36313,7 @@ EOF
       ac_obj=$1
       IFS=$old_IFS
       
-      PHP_CLI_OBJS="$PHP_CLI_OBJS $ac_bdir$ac_obj.lo"
+      PHP_GLOBAL_OBJS="$PHP_GLOBAL_OBJS $ac_bdir$ac_obj.lo"
 
       case $ac_src in
         *.c) ac_comp="$b_c_pre $ac_extra $ac_inc $b_c_meta -c $ac_srcdir$ac_src -o $ac_bdir$ac_obj.$b_lo $b_c_post" ;;
@@ -36134,7 +36368,7 @@ EOF
     # Extract the first word of "pkg-config", so it can be a program name with args.
 set dummy pkg-config; ac_word=$2
 echo $ac_n "checking for $ac_word""... $ac_c" 1>&6
-echo "configure:36138: checking for $ac_word" >&5
+echo "configure:36377: checking for $ac_word" >&5
 if eval "test \"`echo '$''{'ac_cv_path_PKG_CONFIG'+set}'`\" = set"; then
   echo $ac_n "(cached) $ac_c" 1>&6
 else
@@ -36339,9 +36573,9 @@ fi
     old_CPPFLAGS=$CPPFLAGS
     CPPFLAGS=-I$OPENSSL_INCDIR
     echo $ac_n "checking for OpenSSL version""... $ac_c" 1>&6
-echo "configure:36343: checking for OpenSSL version" >&5
+echo "configure:36582: checking for OpenSSL version" >&5
     cat > conftest.$ac_ext <<EOF
-#line 36345 "configure"
+#line 36584 "configure"
 #include "confdefs.h"
 
 #include <openssl/opensslv.h>
@@ -36496,7 +36730,7 @@ rm -f conftest*
   done
 
   echo $ac_n "checking for CRYPTO_free in -lcrypto""... $ac_c" 1>&6
-echo "configure:36500: checking for CRYPTO_free in -lcrypto" >&5
+echo "configure:36739: checking for CRYPTO_free in -lcrypto" >&5
 ac_lib_var=`echo crypto'_'CRYPTO_free | sed 'y%./+-%__p_%'`
 if eval "test \"`echo '$''{'ac_cv_lib_$ac_lib_var'+set}'`\" = set"; then
   echo $ac_n "(cached) $ac_c" 1>&6
@@ -36504,7 +36738,7 @@ else
   ac_save_LIBS="$LIBS"
 LIBS="-lcrypto  $LIBS"
 cat > conftest.$ac_ext <<EOF
-#line 36508 "configure"
+#line 36747 "configure"
 #include "confdefs.h"
 /* Override any gcc2 internal prototype to avoid an error.  */
 /* We use char because int might match the return type of a gcc2
@@ -36515,7 +36749,7 @@ int main() {
 CRYPTO_free()
 ; return 0; }
 EOF
-if { (eval echo configure:36519: \"$ac_link\") 1>&5; (eval $ac_link) 2>&5; } && test -s conftest${ac_exeext}; then
+if { (eval echo configure:36758: \"$ac_link\") 1>&5; (eval $ac_link) 2>&5; } && test -s conftest${ac_exeext}; then
   rm -rf conftest*
   eval "ac_cv_lib_$ac_lib_var=yes"
 else
@@ -36672,7 +36906,7 @@ fi
   done
 
   echo $ac_n "checking for SSL_CTX_set_ssl_version in -lssl""... $ac_c" 1>&6
-echo "configure:36676: checking for SSL_CTX_set_ssl_version in -lssl" >&5
+echo "configure:36915: checking for SSL_CTX_set_ssl_version in -lssl" >&5
 ac_lib_var=`echo ssl'_'SSL_CTX_set_ssl_version | sed 'y%./+-%__p_%'`
 if eval "test \"`echo '$''{'ac_cv_lib_$ac_lib_var'+set}'`\" = set"; then
   echo $ac_n "(cached) $ac_c" 1>&6
@@ -36680,7 +36914,7 @@ else
   ac_save_LIBS="$LIBS"
 LIBS="-lssl  $LIBS"
 cat > conftest.$ac_ext <<EOF
-#line 36684 "configure"
+#line 36923 "configure"
 #include "confdefs.h"
 /* Override any gcc2 internal prototype to avoid an error.  */
 /* We use char because int might match the return type of a gcc2
@@ -36691,7 +36925,7 @@ int main() {
 SSL_CTX_set_ssl_version()
 ; return 0; }
 EOF
-if { (eval echo configure:36695: \"$ac_link\") 1>&5; (eval $ac_link) 2>&5; } && test -s conftest${ac_exeext}; then
+if { (eval echo configure:36934: \"$ac_link\") 1>&5; (eval $ac_link) 2>&5; } && test -s conftest${ac_exeext}; then
   rm -rf conftest*
   eval "ac_cv_lib_$ac_lib_var=yes"
 else
@@ -36833,7 +37067,7 @@ fi
 php_with_gd=no
 
 echo $ac_n "checking for GD support""... $ac_c" 1>&6
-echo "configure:36837: checking for GD support" >&5
+echo "configure:37076: checking for GD support" >&5
 # Check whether --with-gd or --without-gd was given.
 if test "${with_gd+set}" = set; then
   withval="$with_gd"
@@ -36878,7 +37112,7 @@ if test -z "$PHP_JPEG_DIR"; then
 php_with_jpeg_dir=no
 
 echo $ac_n "checking for the location of libjpeg""... $ac_c" 1>&6
-echo "configure:36882: checking for the location of libjpeg" >&5
+echo "configure:37121: checking for the location of libjpeg" >&5
 # Check whether --with-jpeg-dir or --without-jpeg-dir was given.
 if test "${with_jpeg_dir+set}" = set; then
   withval="$with_jpeg_dir"
@@ -36903,7 +37137,7 @@ if test -z "$PHP_PNG_DIR"; then
 php_with_png_dir=no
 
 echo $ac_n "checking for the location of libpng""... $ac_c" 1>&6
-echo "configure:36907: checking for the location of libpng" >&5
+echo "configure:37146: checking for the location of libpng" >&5
 # Check whether --with-png-dir or --without-png-dir was given.
 if test "${with_png_dir+set}" = set; then
   withval="$with_png_dir"
@@ -36928,7 +37162,7 @@ if test -z "$PHP_ZLIB_DIR"; then
 php_with_zlib_dir=no
 
 echo $ac_n "checking for the location of libz""... $ac_c" 1>&6
-echo "configure:36932: checking for the location of libz" >&5
+echo "configure:37171: checking for the location of libz" >&5
 # Check whether --with-zlib-dir or --without-zlib-dir was given.
 if test "${with_zlib_dir+set}" = set; then
   withval="$with_zlib_dir"
@@ -36952,7 +37186,7 @@ fi
 php_with_xpm_dir=no
 
 echo $ac_n "checking for the location of libXpm""... $ac_c" 1>&6
-echo "configure:36956: checking for the location of libXpm" >&5
+echo "configure:37195: checking for the location of libXpm" >&5
 # Check whether --with-xpm-dir or --without-xpm-dir was given.
 if test "${with_xpm_dir+set}" = set; then
   withval="$with_xpm_dir"
@@ -36975,7 +37209,7 @@ echo "$ac_t""$ext_output" 1>&6
 php_with_freetype_dir=no
 
 echo $ac_n "checking for FreeType 2""... $ac_c" 1>&6
-echo "configure:36979: checking for FreeType 2" >&5
+echo "configure:37218: checking for FreeType 2" >&5
 # Check whether --with-freetype-dir or --without-freetype-dir was given.
 if test "${with_freetype_dir+set}" = set; then
   withval="$with_freetype_dir"
@@ -36998,7 +37232,7 @@ echo "$ac_t""$ext_output" 1>&6
 php_with_t1lib=no
 
 echo $ac_n "checking for T1lib support""... $ac_c" 1>&6
-echo "configure:37002: checking for T1lib support" >&5
+echo "configure:37241: checking for T1lib support" >&5
 # Check whether --with-t1lib or --without-t1lib was given.
 if test "${with_t1lib+set}" = set; then
   withval="$with_t1lib"
@@ -37021,7 +37255,7 @@ echo "$ac_t""$ext_output" 1>&6
 php_enable_gd_native_ttf=no
 
 echo $ac_n "checking whether to enable truetype string function in GD""... $ac_c" 1>&6
-echo "configure:37025: checking whether to enable truetype string function in GD" >&5
+echo "configure:37264: checking whether to enable truetype string function in GD" >&5
 # Check whether --enable-gd-native-ttf or --disable-gd-native-ttf was given.
 if test "${enable_gd_native_ttf+set}" = set; then
   enableval="$enable_gd_native_ttf"
@@ -37044,7 +37278,7 @@ echo "$ac_t""$ext_output" 1>&6
 php_enable_gd_jis_conv=no
 
 echo $ac_n "checking whether to enable JIS-mapped Japanese font support in GD""... $ac_c" 1>&6
-echo "configure:37048: checking whether to enable JIS-mapped Japanese font support in GD" >&5
+echo "configure:37287: checking whether to enable JIS-mapped Japanese font support in GD" >&5
 # Check whether --enable-gd-jis-conv or --disable-gd-jis-conv was given.
 if test "${enable_gd_jis_conv+set}" = set; then
   enableval="$enable_gd_jis_conv"
@@ -37096,12 +37330,12 @@ if test "$PHP_GD" = "yes"; then
   for ac_func in fabsf floorf
 do
 echo $ac_n "checking for $ac_func""... $ac_c" 1>&6
-echo "configure:37100: checking for $ac_func" >&5
+echo "configure:37339: checking for $ac_func" >&5
 if eval "test \"`echo '$''{'ac_cv_func_$ac_func'+set}'`\" = set"; then
   echo $ac_n "(cached) $ac_c" 1>&6
 else
   cat > conftest.$ac_ext <<EOF
-#line 37105 "configure"
+#line 37344 "configure"
 #include "confdefs.h"
 /* System header to define __stub macros and hopefully few prototypes,
     which can conflict with char $ac_func(); below.  */
@@ -37124,7 +37358,7 @@ $ac_func();
 
 ; return 0; }
 EOF
-if { (eval echo configure:37128: \"$ac_link\") 1>&5; (eval $ac_link) 2>&5; } && test -s conftest${ac_exeext}; then
+if { (eval echo configure:37367: \"$ac_link\") 1>&5; (eval $ac_link) 2>&5; } && test -s conftest${ac_exeext}; then
   rm -rf conftest*
   eval "ac_cv_func_$ac_func=yes"
 else
@@ -37296,7 +37530,7 @@ EOF
   done
 
   echo $ac_n "checking for jpeg_read_header in -ljpeg""... $ac_c" 1>&6
-echo "configure:37300: checking for jpeg_read_header in -ljpeg" >&5
+echo "configure:37539: checking for jpeg_read_header in -ljpeg" >&5
 ac_lib_var=`echo jpeg'_'jpeg_read_header | sed 'y%./+-%__p_%'`
 if eval "test \"`echo '$''{'ac_cv_lib_$ac_lib_var'+set}'`\" = set"; then
   echo $ac_n "(cached) $ac_c" 1>&6
@@ -37304,7 +37538,7 @@ else
   ac_save_LIBS="$LIBS"
 LIBS="-ljpeg  $LIBS"
 cat > conftest.$ac_ext <<EOF
-#line 37308 "configure"
+#line 37547 "configure"
 #include "confdefs.h"
 /* Override any gcc2 internal prototype to avoid an error.  */
 /* We use char because int might match the return type of a gcc2
@@ -37315,7 +37549,7 @@ int main() {
 jpeg_read_header()
 ; return 0; }
 EOF
-if { (eval echo configure:37319: \"$ac_link\") 1>&5; (eval $ac_link) 2>&5; } && test -s conftest${ac_exeext}; then
+if { (eval echo configure:37558: \"$ac_link\") 1>&5; (eval $ac_link) 2>&5; } && test -s conftest${ac_exeext}; then
   rm -rf conftest*
   eval "ac_cv_lib_$ac_lib_var=yes"
 else
@@ -37593,7 +37827,7 @@ fi
   done
 
   echo $ac_n "checking for png_write_image in -lpng""... $ac_c" 1>&6
-echo "configure:37597: checking for png_write_image in -lpng" >&5
+echo "configure:37836: checking for png_write_image in -lpng" >&5
 ac_lib_var=`echo png'_'png_write_image | sed 'y%./+-%__p_%'`
 if eval "test \"`echo '$''{'ac_cv_lib_$ac_lib_var'+set}'`\" = set"; then
   echo $ac_n "(cached) $ac_c" 1>&6
@@ -37601,7 +37835,7 @@ else
   ac_save_LIBS="$LIBS"
 LIBS="-lpng  $LIBS"
 cat > conftest.$ac_ext <<EOF
-#line 37605 "configure"
+#line 37844 "configure"
 #include "confdefs.h"
 /* Override any gcc2 internal prototype to avoid an error.  */
 /* We use char because int might match the return type of a gcc2
@@ -37612,7 +37846,7 @@ int main() {
 png_write_image()
 ; return 0; }
 EOF
-if { (eval echo configure:37616: \"$ac_link\") 1>&5; (eval $ac_link) 2>&5; } && test -s conftest${ac_exeext}; then
+if { (eval echo configure:37855: \"$ac_link\") 1>&5; (eval $ac_link) 2>&5; } && test -s conftest${ac_exeext}; then
   rm -rf conftest*
   eval "ac_cv_lib_$ac_lib_var=yes"
 else
@@ -37992,7 +38226,7 @@ fi
   done
 
   echo $ac_n "checking for XpmFreeXpmImage in -lXpm""... $ac_c" 1>&6
-echo "configure:37996: checking for XpmFreeXpmImage in -lXpm" >&5
+echo "configure:38235: checking for XpmFreeXpmImage in -lXpm" >&5
 ac_lib_var=`echo Xpm'_'XpmFreeXpmImage | sed 'y%./+-%__p_%'`
 if eval "test \"`echo '$''{'ac_cv_lib_$ac_lib_var'+set}'`\" = set"; then
   echo $ac_n "(cached) $ac_c" 1>&6
@@ -38000,7 +38234,7 @@ else
   ac_save_LIBS="$LIBS"
 LIBS="-lXpm  $LIBS"
 cat > conftest.$ac_ext <<EOF
-#line 38004 "configure"
+#line 38243 "configure"
 #include "confdefs.h"
 /* Override any gcc2 internal prototype to avoid an error.  */
 /* We use char because int might match the return type of a gcc2
@@ -38011,7 +38245,7 @@ int main() {
 XpmFreeXpmImage()
 ; return 0; }
 EOF
-if { (eval echo configure:38015: \"$ac_link\") 1>&5; (eval $ac_link) 2>&5; } && test -s conftest${ac_exeext}; then
+if { (eval echo configure:38254: \"$ac_link\") 1>&5; (eval $ac_link) 2>&5; } && test -s conftest${ac_exeext}; then
   rm -rf conftest*
   eval "ac_cv_lib_$ac_lib_var=yes"
 else
@@ -38386,7 +38620,7 @@ fi
   done
 
   echo $ac_n "checking for FT_New_Face in -lfreetype""... $ac_c" 1>&6
-echo "configure:38390: checking for FT_New_Face in -lfreetype" >&5
+echo "configure:38629: checking for FT_New_Face in -lfreetype" >&5
 ac_lib_var=`echo freetype'_'FT_New_Face | sed 'y%./+-%__p_%'`
 if eval "test \"`echo '$''{'ac_cv_lib_$ac_lib_var'+set}'`\" = set"; then
   echo $ac_n "(cached) $ac_c" 1>&6
@@ -38394,7 +38628,7 @@ else
   ac_save_LIBS="$LIBS"
 LIBS="-lfreetype  $LIBS"
 cat > conftest.$ac_ext <<EOF
-#line 38398 "configure"
+#line 38637 "configure"
 #include "confdefs.h"
 /* Override any gcc2 internal prototype to avoid an error.  */
 /* We use char because int might match the return type of a gcc2
@@ -38405,7 +38639,7 @@ int main() {
 FT_New_Face()
 ; return 0; }
 EOF
-if { (eval echo configure:38409: \"$ac_link\") 1>&5; (eval $ac_link) 2>&5; } && test -s conftest${ac_exeext}; then
+if { (eval echo configure:38648: \"$ac_link\") 1>&5; (eval $ac_link) 2>&5; } && test -s conftest${ac_exeext}; then
   rm -rf conftest*
   eval "ac_cv_lib_$ac_lib_var=yes"
 else
@@ -38722,7 +38956,7 @@ fi
   done
 
   echo $ac_n "checking for T1_StrError in -lt1""... $ac_c" 1>&6
-echo "configure:38726: checking for T1_StrError in -lt1" >&5
+echo "configure:38965: checking for T1_StrError in -lt1" >&5
 ac_lib_var=`echo t1'_'T1_StrError | sed 'y%./+-%__p_%'`
 if eval "test \"`echo '$''{'ac_cv_lib_$ac_lib_var'+set}'`\" = set"; then
   echo $ac_n "(cached) $ac_c" 1>&6
@@ -38730,7 +38964,7 @@ else
   ac_save_LIBS="$LIBS"
 LIBS="-lt1  $LIBS"
 cat > conftest.$ac_ext <<EOF
-#line 38734 "configure"
+#line 38973 "configure"
 #include "confdefs.h"
 /* Override any gcc2 internal prototype to avoid an error.  */
 /* We use char because int might match the return type of a gcc2
@@ -38741,7 +38975,7 @@ int main() {
 T1_StrError()
 ; return 0; }
 EOF
-if { (eval echo configure:38745: \"$ac_link\") 1>&5; (eval $ac_link) 2>&5; } && test -s conftest${ac_exeext}; then
+if { (eval echo configure:38984: \"$ac_link\") 1>&5; (eval $ac_link) 2>&5; } && test -s conftest${ac_exeext}; then
   rm -rf conftest*
   eval "ac_cv_lib_$ac_lib_var=yes"
 else
@@ -39184,7 +39418,7 @@ EOF
   done
 
   echo $ac_n "checking for jpeg_read_header in -ljpeg""... $ac_c" 1>&6
-echo "configure:39188: checking for jpeg_read_header in -ljpeg" >&5
+echo "configure:39427: checking for jpeg_read_header in -ljpeg" >&5
 ac_lib_var=`echo jpeg'_'jpeg_read_header | sed 'y%./+-%__p_%'`
 if eval "test \"`echo '$''{'ac_cv_lib_$ac_lib_var'+set}'`\" = set"; then
   echo $ac_n "(cached) $ac_c" 1>&6
@@ -39192,7 +39426,7 @@ else
   ac_save_LIBS="$LIBS"
 LIBS="-ljpeg  $LIBS"
 cat > conftest.$ac_ext <<EOF
-#line 39196 "configure"
+#line 39435 "configure"
 #include "confdefs.h"
 /* Override any gcc2 internal prototype to avoid an error.  */
 /* We use char because int might match the return type of a gcc2
@@ -39203,7 +39437,7 @@ int main() {
 jpeg_read_header()
 ; return 0; }
 EOF
-if { (eval echo configure:39207: \"$ac_link\") 1>&5; (eval $ac_link) 2>&5; } && test -s conftest${ac_exeext}; then
+if { (eval echo configure:39446: \"$ac_link\") 1>&5; (eval $ac_link) 2>&5; } && test -s conftest${ac_exeext}; then
   rm -rf conftest*
   eval "ac_cv_lib_$ac_lib_var=yes"
 else
@@ -39481,7 +39715,7 @@ fi
   done
 
   echo $ac_n "checking for png_write_image in -lpng""... $ac_c" 1>&6
-echo "configure:39485: checking for png_write_image in -lpng" >&5
+echo "configure:39724: checking for png_write_image in -lpng" >&5
 ac_lib_var=`echo png'_'png_write_image | sed 'y%./+-%__p_%'`
 if eval "test \"`echo '$''{'ac_cv_lib_$ac_lib_var'+set}'`\" = set"; then
   echo $ac_n "(cached) $ac_c" 1>&6
@@ -39489,7 +39723,7 @@ else
   ac_save_LIBS="$LIBS"
 LIBS="-lpng  $LIBS"
 cat > conftest.$ac_ext <<EOF
-#line 39493 "configure"
+#line 39732 "configure"
 #include "confdefs.h"
 /* Override any gcc2 internal prototype to avoid an error.  */
 /* We use char because int might match the return type of a gcc2
@@ -39500,7 +39734,7 @@ int main() {
 png_write_image()
 ; return 0; }
 EOF
-if { (eval echo configure:39504: \"$ac_link\") 1>&5; (eval $ac_link) 2>&5; } && test -s conftest${ac_exeext}; then
+if { (eval echo configure:39743: \"$ac_link\") 1>&5; (eval $ac_link) 2>&5; } && test -s conftest${ac_exeext}; then
   rm -rf conftest*
   eval "ac_cv_lib_$ac_lib_var=yes"
 else
@@ -39880,7 +40114,7 @@ fi
   done
 
   echo $ac_n "checking for XpmFreeXpmImage in -lXpm""... $ac_c" 1>&6
-echo "configure:39884: checking for XpmFreeXpmImage in -lXpm" >&5
+echo "configure:40123: checking for XpmFreeXpmImage in -lXpm" >&5
 ac_lib_var=`echo Xpm'_'XpmFreeXpmImage | sed 'y%./+-%__p_%'`
 if eval "test \"`echo '$''{'ac_cv_lib_$ac_lib_var'+set}'`\" = set"; then
   echo $ac_n "(cached) $ac_c" 1>&6
@@ -39888,7 +40122,7 @@ else
   ac_save_LIBS="$LIBS"
 LIBS="-lXpm  $LIBS"
 cat > conftest.$ac_ext <<EOF
-#line 39892 "configure"
+#line 40131 "configure"
 #include "confdefs.h"
 /* Override any gcc2 internal prototype to avoid an error.  */
 /* We use char because int might match the return type of a gcc2
@@ -39899,7 +40133,7 @@ int main() {
 XpmFreeXpmImage()
 ; return 0; }
 EOF
-if { (eval echo configure:39903: \"$ac_link\") 1>&5; (eval $ac_link) 2>&5; } && test -s conftest${ac_exeext}; then
+if { (eval echo configure:40142: \"$ac_link\") 1>&5; (eval $ac_link) 2>&5; } && test -s conftest${ac_exeext}; then
   rm -rf conftest*
   eval "ac_cv_lib_$ac_lib_var=yes"
 else
@@ -40274,7 +40508,7 @@ fi
   done
 
   echo $ac_n "checking for FT_New_Face in -lfreetype""... $ac_c" 1>&6
-echo "configure:40278: checking for FT_New_Face in -lfreetype" >&5
+echo "configure:40517: checking for FT_New_Face in -lfreetype" >&5
 ac_lib_var=`echo freetype'_'FT_New_Face | sed 'y%./+-%__p_%'`
 if eval "test \"`echo '$''{'ac_cv_lib_$ac_lib_var'+set}'`\" = set"; then
   echo $ac_n "(cached) $ac_c" 1>&6
@@ -40282,7 +40516,7 @@ else
   ac_save_LIBS="$LIBS"
 LIBS="-lfreetype  $LIBS"
 cat > conftest.$ac_ext <<EOF
-#line 40286 "configure"
+#line 40525 "configure"
 #include "confdefs.h"
 /* Override any gcc2 internal prototype to avoid an error.  */
 /* We use char because int might match the return type of a gcc2
@@ -40293,7 +40527,7 @@ int main() {
 FT_New_Face()
 ; return 0; }
 EOF
-if { (eval echo configure:40297: \"$ac_link\") 1>&5; (eval $ac_link) 2>&5; } && test -s conftest${ac_exeext}; then
+if { (eval echo configure:40536: \"$ac_link\") 1>&5; (eval $ac_link) 2>&5; } && test -s conftest${ac_exeext}; then
   rm -rf conftest*
   eval "ac_cv_lib_$ac_lib_var=yes"
 else
@@ -40610,7 +40844,7 @@ fi
   done
 
   echo $ac_n "checking for T1_StrError in -lt1""... $ac_c" 1>&6
-echo "configure:40614: checking for T1_StrError in -lt1" >&5
+echo "configure:40853: checking for T1_StrError in -lt1" >&5
 ac_lib_var=`echo t1'_'T1_StrError | sed 'y%./+-%__p_%'`
 if eval "test \"`echo '$''{'ac_cv_lib_$ac_lib_var'+set}'`\" = set"; then
   echo $ac_n "(cached) $ac_c" 1>&6
@@ -40618,7 +40852,7 @@ else
   ac_save_LIBS="$LIBS"
 LIBS="-lt1  $LIBS"
 cat > conftest.$ac_ext <<EOF
-#line 40622 "configure"
+#line 40861 "configure"
 #include "confdefs.h"
 /* Override any gcc2 internal prototype to avoid an error.  */
 /* We use char because int might match the return type of a gcc2
@@ -40629,7 +40863,7 @@ int main() {
 T1_StrError()
 ; return 0; }
 EOF
-if { (eval echo configure:40633: \"$ac_link\") 1>&5; (eval $ac_link) 2>&5; } && test -s conftest${ac_exeext}; then
+if { (eval echo configure:40872: \"$ac_link\") 1>&5; (eval $ac_link) 2>&5; } && test -s conftest${ac_exeext}; then
   rm -rf conftest*
   eval "ac_cv_lib_$ac_lib_var=yes"
 else
@@ -41004,7 +41238,7 @@ EOF
   done
 
   echo $ac_n "checking for gdImageString16 in -lgd""... $ac_c" 1>&6
-echo "configure:41008: checking for gdImageString16 in -lgd" >&5
+echo "configure:41247: checking for gdImageString16 in -lgd" >&5
 ac_lib_var=`echo gd'_'gdImageString16 | sed 'y%./+-%__p_%'`
 if eval "test \"`echo '$''{'ac_cv_lib_$ac_lib_var'+set}'`\" = set"; then
   echo $ac_n "(cached) $ac_c" 1>&6
@@ -41012,7 +41246,7 @@ else
   ac_save_LIBS="$LIBS"
 LIBS="-lgd  $LIBS"
 cat > conftest.$ac_ext <<EOF
-#line 41016 "configure"
+#line 41255 "configure"
 #include "confdefs.h"
 /* Override any gcc2 internal prototype to avoid an error.  */
 /* We use char because int might match the return type of a gcc2
@@ -41023,7 +41257,7 @@ int main() {
 gdImageString16()
 ; return 0; }
 EOF
-if { (eval echo configure:41027: \"$ac_link\") 1>&5; (eval $ac_link) 2>&5; } && test -s conftest${ac_exeext}; then
+if { (eval echo configure:41266: \"$ac_link\") 1>&5; (eval $ac_link) 2>&5; } && test -s conftest${ac_exeext}; then
   rm -rf conftest*
   eval "ac_cv_lib_$ac_lib_var=yes"
 else
@@ -41152,7 +41386,7 @@ fi
   done
 
   echo $ac_n "checking for gdImagePaletteCopy in -lgd""... $ac_c" 1>&6
-echo "configure:41156: checking for gdImagePaletteCopy in -lgd" >&5
+echo "configure:41395: checking for gdImagePaletteCopy in -lgd" >&5
 ac_lib_var=`echo gd'_'gdImagePaletteCopy | sed 'y%./+-%__p_%'`
 if eval "test \"`echo '$''{'ac_cv_lib_$ac_lib_var'+set}'`\" = set"; then
   echo $ac_n "(cached) $ac_c" 1>&6
@@ -41160,7 +41394,7 @@ else
   ac_save_LIBS="$LIBS"
 LIBS="-lgd  $LIBS"
 cat > conftest.$ac_ext <<EOF
-#line 41164 "configure"
+#line 41403 "configure"
 #include "confdefs.h"
 /* Override any gcc2 internal prototype to avoid an error.  */
 /* We use char because int might match the return type of a gcc2
@@ -41171,7 +41405,7 @@ int main() {
 gdImagePaletteCopy()
 ; return 0; }
 EOF
-if { (eval echo configure:41175: \"$ac_link\") 1>&5; (eval $ac_link) 2>&5; } && test -s conftest${ac_exeext}; then
+if { (eval echo configure:41414: \"$ac_link\") 1>&5; (eval $ac_link) 2>&5; } && test -s conftest${ac_exeext}; then
   rm -rf conftest*
   eval "ac_cv_lib_$ac_lib_var=yes"
 else
@@ -41300,7 +41534,7 @@ fi
   done
 
   echo $ac_n "checking for gdImageCreateFromPng in -lgd""... $ac_c" 1>&6
-echo "configure:41304: checking for gdImageCreateFromPng in -lgd" >&5
+echo "configure:41543: checking for gdImageCreateFromPng in -lgd" >&5
 ac_lib_var=`echo gd'_'gdImageCreateFromPng | sed 'y%./+-%__p_%'`
 if eval "test \"`echo '$''{'ac_cv_lib_$ac_lib_var'+set}'`\" = set"; then
   echo $ac_n "(cached) $ac_c" 1>&6
@@ -41308,7 +41542,7 @@ else
   ac_save_LIBS="$LIBS"
 LIBS="-lgd  $LIBS"
 cat > conftest.$ac_ext <<EOF
-#line 41312 "configure"
+#line 41551 "configure"
 #include "confdefs.h"
 /* Override any gcc2 internal prototype to avoid an error.  */
 /* We use char because int might match the return type of a gcc2
@@ -41319,7 +41553,7 @@ int main() {
 gdImageCreateFromPng()
 ; return 0; }
 EOF
-if { (eval echo configure:41323: \"$ac_link\") 1>&5; (eval $ac_link) 2>&5; } && test -s conftest${ac_exeext}; then
+if { (eval echo configure:41562: \"$ac_link\") 1>&5; (eval $ac_link) 2>&5; } && test -s conftest${ac_exeext}; then
   rm -rf conftest*
   eval "ac_cv_lib_$ac_lib_var=yes"
 else
@@ -41448,7 +41682,7 @@ fi
   done
 
   echo $ac_n "checking for gdImageCreateFromGif in -lgd""... $ac_c" 1>&6
-echo "configure:41452: checking for gdImageCreateFromGif in -lgd" >&5
+echo "configure:41691: checking for gdImageCreateFromGif in -lgd" >&5
 ac_lib_var=`echo gd'_'gdImageCreateFromGif | sed 'y%./+-%__p_%'`
 if eval "test \"`echo '$''{'ac_cv_lib_$ac_lib_var'+set}'`\" = set"; then
   echo $ac_n "(cached) $ac_c" 1>&6
@@ -41456,7 +41690,7 @@ else
   ac_save_LIBS="$LIBS"
 LIBS="-lgd  $LIBS"
 cat > conftest.$ac_ext <<EOF
-#line 41460 "configure"
+#line 41699 "configure"
 #include "confdefs.h"
 /* Override any gcc2 internal prototype to avoid an error.  */
 /* We use char because int might match the return type of a gcc2
@@ -41467,7 +41701,7 @@ int main() {
 gdImageCreateFromGif()
 ; return 0; }
 EOF
-if { (eval echo configure:41471: \"$ac_link\") 1>&5; (eval $ac_link) 2>&5; } && test -s conftest${ac_exeext}; then
+if { (eval echo configure:41710: \"$ac_link\") 1>&5; (eval $ac_link) 2>&5; } && test -s conftest${ac_exeext}; then
   rm -rf conftest*
   eval "ac_cv_lib_$ac_lib_var=yes"
 else
@@ -41596,7 +41830,7 @@ fi
   done
 
   echo $ac_n "checking for gdImageGif in -lgd""... $ac_c" 1>&6
-echo "configure:41600: checking for gdImageGif in -lgd" >&5
+echo "configure:41839: checking for gdImageGif in -lgd" >&5
 ac_lib_var=`echo gd'_'gdImageGif | sed 'y%./+-%__p_%'`
 if eval "test \"`echo '$''{'ac_cv_lib_$ac_lib_var'+set}'`\" = set"; then
   echo $ac_n "(cached) $ac_c" 1>&6
@@ -41604,7 +41838,7 @@ else
   ac_save_LIBS="$LIBS"
 LIBS="-lgd  $LIBS"
 cat > conftest.$ac_ext <<EOF
-#line 41608 "configure"
+#line 41847 "configure"
 #include "confdefs.h"
 /* Override any gcc2 internal prototype to avoid an error.  */
 /* We use char because int might match the return type of a gcc2
@@ -41615,7 +41849,7 @@ int main() {
 gdImageGif()
 ; return 0; }
 EOF
-if { (eval echo configure:41619: \"$ac_link\") 1>&5; (eval $ac_link) 2>&5; } && test -s conftest${ac_exeext}; then
+if { (eval echo configure:41858: \"$ac_link\") 1>&5; (eval $ac_link) 2>&5; } && test -s conftest${ac_exeext}; then
   rm -rf conftest*
   eval "ac_cv_lib_$ac_lib_var=yes"
 else
@@ -41744,7 +41978,7 @@ fi
   done
 
   echo $ac_n "checking for gdImageWBMP in -lgd""... $ac_c" 1>&6
-echo "configure:41748: checking for gdImageWBMP in -lgd" >&5
+echo "configure:41987: checking for gdImageWBMP in -lgd" >&5
 ac_lib_var=`echo gd'_'gdImageWBMP | sed 'y%./+-%__p_%'`
 if eval "test \"`echo '$''{'ac_cv_lib_$ac_lib_var'+set}'`\" = set"; then
   echo $ac_n "(cached) $ac_c" 1>&6
@@ -41752,7 +41986,7 @@ else
   ac_save_LIBS="$LIBS"
 LIBS="-lgd  $LIBS"
 cat > conftest.$ac_ext <<EOF
-#line 41756 "configure"
+#line 41995 "configure"
 #include "confdefs.h"
 /* Override any gcc2 internal prototype to avoid an error.  */
 /* We use char because int might match the return type of a gcc2
@@ -41763,7 +41997,7 @@ int main() {
 gdImageWBMP()
 ; return 0; }
 EOF
-if { (eval echo configure:41767: \"$ac_link\") 1>&5; (eval $ac_link) 2>&5; } && test -s conftest${ac_exeext}; then
+if { (eval echo configure:42006: \"$ac_link\") 1>&5; (eval $ac_link) 2>&5; } && test -s conftest${ac_exeext}; then
   rm -rf conftest*
   eval "ac_cv_lib_$ac_lib_var=yes"
 else
@@ -41892,7 +42126,7 @@ fi
   done
 
   echo $ac_n "checking for gdImageCreateFromJpeg in -lgd""... $ac_c" 1>&6
-echo "configure:41896: checking for gdImageCreateFromJpeg in -lgd" >&5
+echo "configure:42135: checking for gdImageCreateFromJpeg in -lgd" >&5
 ac_lib_var=`echo gd'_'gdImageCreateFromJpeg | sed 'y%./+-%__p_%'`
 if eval "test \"`echo '$''{'ac_cv_lib_$ac_lib_var'+set}'`\" = set"; then
   echo $ac_n "(cached) $ac_c" 1>&6
@@ -41900,7 +42134,7 @@ else
   ac_save_LIBS="$LIBS"
 LIBS="-lgd  $LIBS"
 cat > conftest.$ac_ext <<EOF
-#line 41904 "configure"
+#line 42143 "configure"
 #include "confdefs.h"
 /* Override any gcc2 internal prototype to avoid an error.  */
 /* We use char because int might match the return type of a gcc2
@@ -41911,7 +42145,7 @@ int main() {
 gdImageCreateFromJpeg()
 ; return 0; }
 EOF
-if { (eval echo configure:41915: \"$ac_link\") 1>&5; (eval $ac_link) 2>&5; } && test -s conftest${ac_exeext}; then
+if { (eval echo configure:42154: \"$ac_link\") 1>&5; (eval $ac_link) 2>&5; } && test -s conftest${ac_exeext}; then
   rm -rf conftest*
   eval "ac_cv_lib_$ac_lib_var=yes"
 else
@@ -42040,7 +42274,7 @@ fi
   done
 
   echo $ac_n "checking for gdImageCreateFromXpm in -lgd""... $ac_c" 1>&6
-echo "configure:42044: checking for gdImageCreateFromXpm in -lgd" >&5
+echo "configure:42283: checking for gdImageCreateFromXpm in -lgd" >&5
 ac_lib_var=`echo gd'_'gdImageCreateFromXpm | sed 'y%./+-%__p_%'`
 if eval "test \"`echo '$''{'ac_cv_lib_$ac_lib_var'+set}'`\" = set"; then
   echo $ac_n "(cached) $ac_c" 1>&6
@@ -42048,7 +42282,7 @@ else
   ac_save_LIBS="$LIBS"
 LIBS="-lgd  $LIBS"
 cat > conftest.$ac_ext <<EOF
-#line 42052 "configure"
+#line 42291 "configure"
 #include "confdefs.h"
 /* Override any gcc2 internal prototype to avoid an error.  */
 /* We use char because int might match the return type of a gcc2
@@ -42059,7 +42293,7 @@ int main() {
 gdImageCreateFromXpm()
 ; return 0; }
 EOF
-if { (eval echo configure:42063: \"$ac_link\") 1>&5; (eval $ac_link) 2>&5; } && test -s conftest${ac_exeext}; then
+if { (eval echo configure:42302: \"$ac_link\") 1>&5; (eval $ac_link) 2>&5; } && test -s conftest${ac_exeext}; then
   rm -rf conftest*
   eval "ac_cv_lib_$ac_lib_var=yes"
 else
@@ -42188,7 +42422,7 @@ fi
   done
 
   echo $ac_n "checking for gdImageCreateFromGd2 in -lgd""... $ac_c" 1>&6
-echo "configure:42192: checking for gdImageCreateFromGd2 in -lgd" >&5
+echo "configure:42431: checking for gdImageCreateFromGd2 in -lgd" >&5
 ac_lib_var=`echo gd'_'gdImageCreateFromGd2 | sed 'y%./+-%__p_%'`
 if eval "test \"`echo '$''{'ac_cv_lib_$ac_lib_var'+set}'`\" = set"; then
   echo $ac_n "(cached) $ac_c" 1>&6
@@ -42196,7 +42430,7 @@ else
   ac_save_LIBS="$LIBS"
 LIBS="-lgd  $LIBS"
 cat > conftest.$ac_ext <<EOF
-#line 42200 "configure"
+#line 42439 "configure"
 #include "confdefs.h"
 /* Override any gcc2 internal prototype to avoid an error.  */
 /* We use char because int might match the return type of a gcc2
@@ -42207,7 +42441,7 @@ int main() {
 gdImageCreateFromGd2()
 ; return 0; }
 EOF
-if { (eval echo configure:42211: \"$ac_link\") 1>&5; (eval $ac_link) 2>&5; } && test -s conftest${ac_exeext}; then
+if { (eval echo configure:42450: \"$ac_link\") 1>&5; (eval $ac_link) 2>&5; } && test -s conftest${ac_exeext}; then
   rm -rf conftest*
   eval "ac_cv_lib_$ac_lib_var=yes"
 else
@@ -42336,7 +42570,7 @@ fi
   done
 
   echo $ac_n "checking for gdImageCreateTrueColor in -lgd""... $ac_c" 1>&6
-echo "configure:42340: checking for gdImageCreateTrueColor in -lgd" >&5
+echo "configure:42579: checking for gdImageCreateTrueColor in -lgd" >&5
 ac_lib_var=`echo gd'_'gdImageCreateTrueColor | sed 'y%./+-%__p_%'`
 if eval "test \"`echo '$''{'ac_cv_lib_$ac_lib_var'+set}'`\" = set"; then
   echo $ac_n "(cached) $ac_c" 1>&6
@@ -42344,7 +42578,7 @@ else
   ac_save_LIBS="$LIBS"
 LIBS="-lgd  $LIBS"
 cat > conftest.$ac_ext <<EOF
-#line 42348 "configure"
+#line 42587 "configure"
 #include "confdefs.h"
 /* Override any gcc2 internal prototype to avoid an error.  */
 /* We use char because int might match the return type of a gcc2
@@ -42355,7 +42589,7 @@ int main() {
 gdImageCreateTrueColor()
 ; return 0; }
 EOF
-if { (eval echo configure:42359: \"$ac_link\") 1>&5; (eval $ac_link) 2>&5; } && test -s conftest${ac_exeext}; then
+if { (eval echo configure:42598: \"$ac_link\") 1>&5; (eval $ac_link) 2>&5; } && test -s conftest${ac_exeext}; then
   rm -rf conftest*
   eval "ac_cv_lib_$ac_lib_var=yes"
 else
@@ -42484,7 +42718,7 @@ fi
   done
 
   echo $ac_n "checking for gdImageSetTile in -lgd""... $ac_c" 1>&6
-echo "configure:42488: checking for gdImageSetTile in -lgd" >&5
+echo "configure:42727: checking for gdImageSetTile in -lgd" >&5
 ac_lib_var=`echo gd'_'gdImageSetTile | sed 'y%./+-%__p_%'`
 if eval "test \"`echo '$''{'ac_cv_lib_$ac_lib_var'+set}'`\" = set"; then
   echo $ac_n "(cached) $ac_c" 1>&6
@@ -42492,7 +42726,7 @@ else
   ac_save_LIBS="$LIBS"
 LIBS="-lgd  $LIBS"
 cat > conftest.$ac_ext <<EOF
-#line 42496 "configure"
+#line 42735 "configure"
 #include "confdefs.h"
 /* Override any gcc2 internal prototype to avoid an error.  */
 /* We use char because int might match the return type of a gcc2
@@ -42503,7 +42737,7 @@ int main() {
 gdImageSetTile()
 ; return 0; }
 EOF
-if { (eval echo configure:42507: \"$ac_link\") 1>&5; (eval $ac_link) 2>&5; } && test -s conftest${ac_exeext}; then
+if { (eval echo configure:42746: \"$ac_link\") 1>&5; (eval $ac_link) 2>&5; } && test -s conftest${ac_exeext}; then
   rm -rf conftest*
   eval "ac_cv_lib_$ac_lib_var=yes"
 else
@@ -42632,7 +42866,7 @@ fi
   done
 
   echo $ac_n "checking for gdImageEllipse in -lgd""... $ac_c" 1>&6
-echo "configure:42636: checking for gdImageEllipse in -lgd" >&5
+echo "configure:42875: checking for gdImageEllipse in -lgd" >&5
 ac_lib_var=`echo gd'_'gdImageEllipse | sed 'y%./+-%__p_%'`
 if eval "test \"`echo '$''{'ac_cv_lib_$ac_lib_var'+set}'`\" = set"; then
   echo $ac_n "(cached) $ac_c" 1>&6
@@ -42640,7 +42874,7 @@ else
   ac_save_LIBS="$LIBS"
 LIBS="-lgd  $LIBS"
 cat > conftest.$ac_ext <<EOF
-#line 42644 "configure"
+#line 42883 "configure"
 #include "confdefs.h"
 /* Override any gcc2 internal prototype to avoid an error.  */
 /* We use char because int might match the return type of a gcc2
@@ -42651,7 +42885,7 @@ int main() {
 gdImageEllipse()
 ; return 0; }
 EOF
-if { (eval echo configure:42655: \"$ac_link\") 1>&5; (eval $ac_link) 2>&5; } && test -s conftest${ac_exeext}; then
+if { (eval echo configure:42894: \"$ac_link\") 1>&5; (eval $ac_link) 2>&5; } && test -s conftest${ac_exeext}; then
   rm -rf conftest*
   eval "ac_cv_lib_$ac_lib_var=yes"
 else
@@ -42780,7 +43014,7 @@ fi
   done
 
   echo $ac_n "checking for gdImageSetBrush in -lgd""... $ac_c" 1>&6
-echo "configure:42784: checking for gdImageSetBrush in -lgd" >&5
+echo "configure:43023: checking for gdImageSetBrush in -lgd" >&5
 ac_lib_var=`echo gd'_'gdImageSetBrush | sed 'y%./+-%__p_%'`
 if eval "test \"`echo '$''{'ac_cv_lib_$ac_lib_var'+set}'`\" = set"; then
   echo $ac_n "(cached) $ac_c" 1>&6
@@ -42788,7 +43022,7 @@ else
   ac_save_LIBS="$LIBS"
 LIBS="-lgd  $LIBS"
 cat > conftest.$ac_ext <<EOF
-#line 42792 "configure"
+#line 43031 "configure"
 #include "confdefs.h"
 /* Override any gcc2 internal prototype to avoid an error.  */
 /* We use char because int might match the return type of a gcc2
@@ -42799,7 +43033,7 @@ int main() {
 gdImageSetBrush()
 ; return 0; }
 EOF
-if { (eval echo configure:42803: \"$ac_link\") 1>&5; (eval $ac_link) 2>&5; } && test -s conftest${ac_exeext}; then
+if { (eval echo configure:43042: \"$ac_link\") 1>&5; (eval $ac_link) 2>&5; } && test -s conftest${ac_exeext}; then
   rm -rf conftest*
   eval "ac_cv_lib_$ac_lib_var=yes"
 else
@@ -42928,7 +43162,7 @@ fi
   done
 
   echo $ac_n "checking for gdImageStringTTF in -lgd""... $ac_c" 1>&6
-echo "configure:42932: checking for gdImageStringTTF in -lgd" >&5
+echo "configure:43171: checking for gdImageStringTTF in -lgd" >&5
 ac_lib_var=`echo gd'_'gdImageStringTTF | sed 'y%./+-%__p_%'`
 if eval "test \"`echo '$''{'ac_cv_lib_$ac_lib_var'+set}'`\" = set"; then
   echo $ac_n "(cached) $ac_c" 1>&6
@@ -42936,7 +43170,7 @@ else
   ac_save_LIBS="$LIBS"
 LIBS="-lgd  $LIBS"
 cat > conftest.$ac_ext <<EOF
-#line 42940 "configure"
+#line 43179 "configure"
 #include "confdefs.h"
 /* Override any gcc2 internal prototype to avoid an error.  */
 /* We use char because int might match the return type of a gcc2
@@ -42947,7 +43181,7 @@ int main() {
 gdImageStringTTF()
 ; return 0; }
 EOF
-if { (eval echo configure:42951: \"$ac_link\") 1>&5; (eval $ac_link) 2>&5; } && test -s conftest${ac_exeext}; then
+if { (eval echo configure:43190: \"$ac_link\") 1>&5; (eval $ac_link) 2>&5; } && test -s conftest${ac_exeext}; then
   rm -rf conftest*
   eval "ac_cv_lib_$ac_lib_var=yes"
 else
@@ -43076,7 +43310,7 @@ fi
   done
 
   echo $ac_n "checking for gdImageStringFT in -lgd""... $ac_c" 1>&6
-echo "configure:43080: checking for gdImageStringFT in -lgd" >&5
+echo "configure:43319: checking for gdImageStringFT in -lgd" >&5
 ac_lib_var=`echo gd'_'gdImageStringFT | sed 'y%./+-%__p_%'`
 if eval "test \"`echo '$''{'ac_cv_lib_$ac_lib_var'+set}'`\" = set"; then
   echo $ac_n "(cached) $ac_c" 1>&6
@@ -43084,7 +43318,7 @@ else
   ac_save_LIBS="$LIBS"
 LIBS="-lgd  $LIBS"
 cat > conftest.$ac_ext <<EOF
-#line 43088 "configure"
+#line 43327 "configure"
 #include "confdefs.h"
 /* Override any gcc2 internal prototype to avoid an error.  */
 /* We use char because int might match the return type of a gcc2
@@ -43095,7 +43329,7 @@ int main() {
 gdImageStringFT()
 ; return 0; }
 EOF
-if { (eval echo configure:43099: \"$ac_link\") 1>&5; (eval $ac_link) 2>&5; } && test -s conftest${ac_exeext}; then
+if { (eval echo configure:43338: \"$ac_link\") 1>&5; (eval $ac_link) 2>&5; } && test -s conftest${ac_exeext}; then
   rm -rf conftest*
   eval "ac_cv_lib_$ac_lib_var=yes"
 else
@@ -43224,7 +43458,7 @@ fi
   done
 
   echo $ac_n "checking for gdImageStringFTEx in -lgd""... $ac_c" 1>&6
-echo "configure:43228: checking for gdImageStringFTEx in -lgd" >&5
+echo "configure:43467: checking for gdImageStringFTEx in -lgd" >&5
 ac_lib_var=`echo gd'_'gdImageStringFTEx | sed 'y%./+-%__p_%'`
 if eval "test \"`echo '$''{'ac_cv_lib_$ac_lib_var'+set}'`\" = set"; then
   echo $ac_n "(cached) $ac_c" 1>&6
@@ -43232,7 +43466,7 @@ else
   ac_save_LIBS="$LIBS"
 LIBS="-lgd  $LIBS"
 cat > conftest.$ac_ext <<EOF
-#line 43236 "configure"
+#line 43475 "configure"
 #include "confdefs.h"
 /* Override any gcc2 internal prototype to avoid an error.  */
 /* We use char because int might match the return type of a gcc2
@@ -43243,7 +43477,7 @@ int main() {
 gdImageStringFTEx()
 ; return 0; }
 EOF
-if { (eval echo configure:43247: \"$ac_link\") 1>&5; (eval $ac_link) 2>&5; } && test -s conftest${ac_exeext}; then
+if { (eval echo configure:43486: \"$ac_link\") 1>&5; (eval $ac_link) 2>&5; } && test -s conftest${ac_exeext}; then
   rm -rf conftest*
   eval "ac_cv_lib_$ac_lib_var=yes"
 else
@@ -43372,7 +43606,7 @@ fi
   done
 
   echo $ac_n "checking for gdImageColorClosestHWB in -lgd""... $ac_c" 1>&6
-echo "configure:43376: checking for gdImageColorClosestHWB in -lgd" >&5
+echo "configure:43615: checking for gdImageColorClosestHWB in -lgd" >&5
 ac_lib_var=`echo gd'_'gdImageColorClosestHWB | sed 'y%./+-%__p_%'`
 if eval "test \"`echo '$''{'ac_cv_lib_$ac_lib_var'+set}'`\" = set"; then
   echo $ac_n "(cached) $ac_c" 1>&6
@@ -43380,7 +43614,7 @@ else
   ac_save_LIBS="$LIBS"
 LIBS="-lgd  $LIBS"
 cat > conftest.$ac_ext <<EOF
-#line 43384 "configure"
+#line 43623 "configure"
 #include "confdefs.h"
 /* Override any gcc2 internal prototype to avoid an error.  */
 /* We use char because int might match the return type of a gcc2
@@ -43391,7 +43625,7 @@ int main() {
 gdImageColorClosestHWB()
 ; return 0; }
 EOF
-if { (eval echo configure:43395: \"$ac_link\") 1>&5; (eval $ac_link) 2>&5; } && test -s conftest${ac_exeext}; then
+if { (eval echo configure:43634: \"$ac_link\") 1>&5; (eval $ac_link) 2>&5; } && test -s conftest${ac_exeext}; then
   rm -rf conftest*
   eval "ac_cv_lib_$ac_lib_var=yes"
 else
@@ -43520,7 +43754,7 @@ fi
   done
 
   echo $ac_n "checking for gdImageColorResolve in -lgd""... $ac_c" 1>&6
-echo "configure:43524: checking for gdImageColorResolve in -lgd" >&5
+echo "configure:43763: checking for gdImageColorResolve in -lgd" >&5
 ac_lib_var=`echo gd'_'gdImageColorResolve | sed 'y%./+-%__p_%'`
 if eval "test \"`echo '$''{'ac_cv_lib_$ac_lib_var'+set}'`\" = set"; then
   echo $ac_n "(cached) $ac_c" 1>&6
@@ -43528,7 +43762,7 @@ else
   ac_save_LIBS="$LIBS"
 LIBS="-lgd  $LIBS"
 cat > conftest.$ac_ext <<EOF
-#line 43532 "configure"
+#line 43771 "configure"
 #include "confdefs.h"
 /* Override any gcc2 internal prototype to avoid an error.  */
 /* We use char because int might match the return type of a gcc2
@@ -43539,7 +43773,7 @@ int main() {
 gdImageColorResolve()
 ; return 0; }
 EOF
-if { (eval echo configure:43543: \"$ac_link\") 1>&5; (eval $ac_link) 2>&5; } && test -s conftest${ac_exeext}; then
+if { (eval echo configure:43782: \"$ac_link\") 1>&5; (eval $ac_link) 2>&5; } && test -s conftest${ac_exeext}; then
   rm -rf conftest*
   eval "ac_cv_lib_$ac_lib_var=yes"
 else
@@ -43668,7 +43902,7 @@ fi
   done
 
   echo $ac_n "checking for gdImageGifCtx in -lgd""... $ac_c" 1>&6
-echo "configure:43672: checking for gdImageGifCtx in -lgd" >&5
+echo "configure:43911: checking for gdImageGifCtx in -lgd" >&5
 ac_lib_var=`echo gd'_'gdImageGifCtx | sed 'y%./+-%__p_%'`
 if eval "test \"`echo '$''{'ac_cv_lib_$ac_lib_var'+set}'`\" = set"; then
   echo $ac_n "(cached) $ac_c" 1>&6
@@ -43676,7 +43910,7 @@ else
   ac_save_LIBS="$LIBS"
 LIBS="-lgd  $LIBS"
 cat > conftest.$ac_ext <<EOF
-#line 43680 "configure"
+#line 43919 "configure"
 #include "confdefs.h"
 /* Override any gcc2 internal prototype to avoid an error.  */
 /* We use char because int might match the return type of a gcc2
@@ -43687,7 +43921,7 @@ int main() {
 gdImageGifCtx()
 ; return 0; }
 EOF
-if { (eval echo configure:43691: \"$ac_link\") 1>&5; (eval $ac_link) 2>&5; } && test -s conftest${ac_exeext}; then
+if { (eval echo configure:43930: \"$ac_link\") 1>&5; (eval $ac_link) 2>&5; } && test -s conftest${ac_exeext}; then
   rm -rf conftest*
   eval "ac_cv_lib_$ac_lib_var=yes"
 else
@@ -43816,7 +44050,7 @@ fi
   done
 
   echo $ac_n "checking for gdCacheCreate in -lgd""... $ac_c" 1>&6
-echo "configure:43820: checking for gdCacheCreate in -lgd" >&5
+echo "configure:44059: checking for gdCacheCreate in -lgd" >&5
 ac_lib_var=`echo gd'_'gdCacheCreate | sed 'y%./+-%__p_%'`
 if eval "test \"`echo '$''{'ac_cv_lib_$ac_lib_var'+set}'`\" = set"; then
   echo $ac_n "(cached) $ac_c" 1>&6
@@ -43824,7 +44058,7 @@ else
   ac_save_LIBS="$LIBS"
 LIBS="-lgd  $LIBS"
 cat > conftest.$ac_ext <<EOF
-#line 43828 "configure"
+#line 44067 "configure"
 #include "confdefs.h"
 /* Override any gcc2 internal prototype to avoid an error.  */
 /* We use char because int might match the return type of a gcc2
@@ -43835,7 +44069,7 @@ int main() {
 gdCacheCreate()
 ; return 0; }
 EOF
-if { (eval echo configure:43839: \"$ac_link\") 1>&5; (eval $ac_link) 2>&5; } && test -s conftest${ac_exeext}; then
+if { (eval echo configure:44078: \"$ac_link\") 1>&5; (eval $ac_link) 2>&5; } && test -s conftest${ac_exeext}; then
   rm -rf conftest*
   eval "ac_cv_lib_$ac_lib_var=yes"
 else
@@ -43964,7 +44198,7 @@ fi
   done
 
   echo $ac_n "checking for gdFontCacheShutdown in -lgd""... $ac_c" 1>&6
-echo "configure:43968: checking for gdFontCacheShutdown in -lgd" >&5
+echo "configure:44207: checking for gdFontCacheShutdown in -lgd" >&5
 ac_lib_var=`echo gd'_'gdFontCacheShutdown | sed 'y%./+-%__p_%'`
 if eval "test \"`echo '$''{'ac_cv_lib_$ac_lib_var'+set}'`\" = set"; then
   echo $ac_n "(cached) $ac_c" 1>&6
@@ -43972,7 +44206,7 @@ else
   ac_save_LIBS="$LIBS"
 LIBS="-lgd  $LIBS"
 cat > conftest.$ac_ext <<EOF
-#line 43976 "configure"
+#line 44215 "configure"
 #include "confdefs.h"
 /* Override any gcc2 internal prototype to avoid an error.  */
 /* We use char because int might match the return type of a gcc2
@@ -43983,7 +44217,7 @@ int main() {
 gdFontCacheShutdown()
 ; return 0; }
 EOF
-if { (eval echo configure:43987: \"$ac_link\") 1>&5; (eval $ac_link) 2>&5; } && test -s conftest${ac_exeext}; then
+if { (eval echo configure:44226: \"$ac_link\") 1>&5; (eval $ac_link) 2>&5; } && test -s conftest${ac_exeext}; then
   rm -rf conftest*
   eval "ac_cv_lib_$ac_lib_var=yes"
 else
@@ -44112,7 +44346,7 @@ fi
   done
 
   echo $ac_n "checking for gdFreeFontCache in -lgd""... $ac_c" 1>&6
-echo "configure:44116: checking for gdFreeFontCache in -lgd" >&5
+echo "configure:44355: checking for gdFreeFontCache in -lgd" >&5
 ac_lib_var=`echo gd'_'gdFreeFontCache | sed 'y%./+-%__p_%'`
 if eval "test \"`echo '$''{'ac_cv_lib_$ac_lib_var'+set}'`\" = set"; then
   echo $ac_n "(cached) $ac_c" 1>&6
@@ -44120,7 +44354,7 @@ else
   ac_save_LIBS="$LIBS"
 LIBS="-lgd  $LIBS"
 cat > conftest.$ac_ext <<EOF
-#line 44124 "configure"
+#line 44363 "configure"
 #include "confdefs.h"
 /* Override any gcc2 internal prototype to avoid an error.  */
 /* We use char because int might match the return type of a gcc2
@@ -44131,7 +44365,7 @@ int main() {
 gdFreeFontCache()
 ; return 0; }
 EOF
-if { (eval echo configure:44135: \"$ac_link\") 1>&5; (eval $ac_link) 2>&5; } && test -s conftest${ac_exeext}; then
+if { (eval echo configure:44374: \"$ac_link\") 1>&5; (eval $ac_link) 2>&5; } && test -s conftest${ac_exeext}; then
   rm -rf conftest*
   eval "ac_cv_lib_$ac_lib_var=yes"
 else
@@ -44260,7 +44494,7 @@ fi
   done
 
   echo $ac_n "checking for gdFontCacheMutexSetup in -lgd""... $ac_c" 1>&6
-echo "configure:44264: checking for gdFontCacheMutexSetup in -lgd" >&5
+echo "configure:44503: checking for gdFontCacheMutexSetup in -lgd" >&5
 ac_lib_var=`echo gd'_'gdFontCacheMutexSetup | sed 'y%./+-%__p_%'`
 if eval "test \"`echo '$''{'ac_cv_lib_$ac_lib_var'+set}'`\" = set"; then
   echo $ac_n "(cached) $ac_c" 1>&6
@@ -44268,7 +44502,7 @@ else
   ac_save_LIBS="$LIBS"
 LIBS="-lgd  $LIBS"
 cat > conftest.$ac_ext <<EOF
-#line 44272 "configure"
+#line 44511 "configure"
 #include "confdefs.h"
 /* Override any gcc2 internal prototype to avoid an error.  */
 /* We use char because int might match the return type of a gcc2
@@ -44279,7 +44513,7 @@ int main() {
 gdFontCacheMutexSetup()
 ; return 0; }
 EOF
-if { (eval echo configure:44283: \"$ac_link\") 1>&5; (eval $ac_link) 2>&5; } && test -s conftest${ac_exeext}; then
+if { (eval echo configure:44522: \"$ac_link\") 1>&5; (eval $ac_link) 2>&5; } && test -s conftest${ac_exeext}; then
   rm -rf conftest*
   eval "ac_cv_lib_$ac_lib_var=yes"
 else
@@ -44408,7 +44642,7 @@ fi
   done
 
   echo $ac_n "checking for gdNewDynamicCtxEx in -lgd""... $ac_c" 1>&6
-echo "configure:44412: checking for gdNewDynamicCtxEx in -lgd" >&5
+echo "configure:44651: checking for gdNewDynamicCtxEx in -lgd" >&5
 ac_lib_var=`echo gd'_'gdNewDynamicCtxEx | sed 'y%./+-%__p_%'`
 if eval "test \"`echo '$''{'ac_cv_lib_$ac_lib_var'+set}'`\" = set"; then
   echo $ac_n "(cached) $ac_c" 1>&6
@@ -44416,7 +44650,7 @@ else
   ac_save_LIBS="$LIBS"
 LIBS="-lgd  $LIBS"
 cat > conftest.$ac_ext <<EOF
-#line 44420 "configure"
+#line 44659 "configure"
 #include "confdefs.h"
 /* Override any gcc2 internal prototype to avoid an error.  */
 /* We use char because int might match the return type of a gcc2
@@ -44427,7 +44661,7 @@ int main() {
 gdNewDynamicCtxEx()
 ; return 0; }
 EOF
-if { (eval echo configure:44431: \"$ac_link\") 1>&5; (eval $ac_link) 2>&5; } && test -s conftest${ac_exeext}; then
+if { (eval echo configure:44670: \"$ac_link\") 1>&5; (eval $ac_link) 2>&5; } && test -s conftest${ac_exeext}; then
   rm -rf conftest*
   eval "ac_cv_lib_$ac_lib_var=yes"
 else
@@ -44556,7 +44790,7 @@ fi
   done
 
   echo $ac_n "checking for gdImageConvolution in -lgd""... $ac_c" 1>&6
-echo "configure:44560: checking for gdImageConvolution in -lgd" >&5
+echo "configure:44799: checking for gdImageConvolution in -lgd" >&5
 ac_lib_var=`echo gd'_'gdImageConvolution | sed 'y%./+-%__p_%'`
 if eval "test \"`echo '$''{'ac_cv_lib_$ac_lib_var'+set}'`\" = set"; then
   echo $ac_n "(cached) $ac_c" 1>&6
@@ -44564,7 +44798,7 @@ else
   ac_save_LIBS="$LIBS"
 LIBS="-lgd  $LIBS"
 cat > conftest.$ac_ext <<EOF
-#line 44568 "configure"
+#line 44807 "configure"
 #include "confdefs.h"
 /* Override any gcc2 internal prototype to avoid an error.  */
 /* We use char because int might match the return type of a gcc2
@@ -44575,7 +44809,7 @@ int main() {
 gdImageConvolution()
 ; return 0; }
 EOF
-if { (eval echo configure:44579: \"$ac_link\") 1>&5; (eval $ac_link) 2>&5; } && test -s conftest${ac_exeext}; then
+if { (eval echo configure:44818: \"$ac_link\") 1>&5; (eval $ac_link) 2>&5; } && test -s conftest${ac_exeext}; then
   rm -rf conftest*
   eval "ac_cv_lib_$ac_lib_var=yes"
 else
@@ -44704,7 +44938,7 @@ fi
   done
 
   echo $ac_n "checking for gdImagePixelate in -lgd""... $ac_c" 1>&6
-echo "configure:44708: checking for gdImagePixelate in -lgd" >&5
+echo "configure:44947: checking for gdImagePixelate in -lgd" >&5
 ac_lib_var=`echo gd'_'gdImagePixelate | sed 'y%./+-%__p_%'`
 if eval "test \"`echo '$''{'ac_cv_lib_$ac_lib_var'+set}'`\" = set"; then
   echo $ac_n "(cached) $ac_c" 1>&6
@@ -44712,7 +44946,7 @@ else
   ac_save_LIBS="$LIBS"
 LIBS="-lgd  $LIBS"
 cat > conftest.$ac_ext <<EOF
-#line 44716 "configure"
+#line 44955 "configure"
 #include "confdefs.h"
 /* Override any gcc2 internal prototype to avoid an error.  */
 /* We use char because int might match the return type of a gcc2
@@ -44723,7 +44957,7 @@ int main() {
 gdImagePixelate()
 ; return 0; }
 EOF
-if { (eval echo configure:44727: \"$ac_link\") 1>&5; (eval $ac_link) 2>&5; } && test -s conftest${ac_exeext}; then
+if { (eval echo configure:44966: \"$ac_link\") 1>&5; (eval $ac_link) 2>&5; } && test -s conftest${ac_exeext}; then
   rm -rf conftest*
   eval "ac_cv_lib_$ac_lib_var=yes"
 else
@@ -44778,7 +45012,7 @@ fi
         old_CPPFLAGS=$CPPFLAGS
   CPPFLAGS=-I$GD_INCLUDE
   cat > conftest.$ac_ext <<EOF
-#line 44782 "configure"
+#line 45021 "configure"
 #include "confdefs.h"
 
 #include <gd.h>
@@ -44792,7 +45026,7 @@ ctx->gd_free = 1;
   
 ; return 0; }
 EOF
-if { (eval echo configure:44796: \"$ac_compile\") 1>&5; (eval $ac_compile) 2>&5; }; then
+if { (eval echo configure:45035: \"$ac_compile\") 1>&5; (eval $ac_compile) 2>&5; }; then
   rm -rf conftest*
   
     cat >> confdefs.h <<\EOF
@@ -45070,7 +45304,7 @@ EOF
       ac_obj=$1
       IFS=$old_IFS
       
-      PHP_CLI_OBJS="$PHP_CLI_OBJS $ac_bdir$ac_obj.lo"
+      PHP_GLOBAL_OBJS="$PHP_GLOBAL_OBJS $ac_bdir$ac_obj.lo"
 
       case $ac_src in
         *.c) ac_comp="$b_c_pre $ac_extra $ac_inc $b_c_meta -c $ac_srcdir$ac_src -o $ac_bdir$ac_obj.$b_lo $b_c_post" ;;
@@ -45123,7 +45357,7 @@ EOF
   
 else
   cat > conftest.$ac_ext <<EOF
-#line 45127 "configure"
+#line 45366 "configure"
 #include "confdefs.h"
 
     char foobar () {}
@@ -45134,7 +45368,7 @@ else
     }
   
 EOF
-if { (eval echo configure:45138: \"$ac_link\") 1>&5; (eval $ac_link) 2>&5; } && test -s conftest${ac_exeext} && (./conftest; exit) 2>/dev/null
+if { (eval echo configure:45377: \"$ac_link\") 1>&5; (eval $ac_link) 2>&5; } && test -s conftest${ac_exeext} && (./conftest; exit) 2>/dev/null
 then
   
     LIBS=$old_LIBS
@@ -45285,7 +45519,7 @@ fi
   done
 
   echo $ac_n "checking for gdImageCreate in -lgd""... $ac_c" 1>&6
-echo "configure:45289: checking for gdImageCreate in -lgd" >&5
+echo "configure:45528: checking for gdImageCreate in -lgd" >&5
 ac_lib_var=`echo gd'_'gdImageCreate | sed 'y%./+-%__p_%'`
 if eval "test \"`echo '$''{'ac_cv_lib_$ac_lib_var'+set}'`\" = set"; then
   echo $ac_n "(cached) $ac_c" 1>&6
@@ -45293,7 +45527,7 @@ else
   ac_save_LIBS="$LIBS"
 LIBS="-lgd  $LIBS"
 cat > conftest.$ac_ext <<EOF
-#line 45297 "configure"
+#line 45536 "configure"
 #include "confdefs.h"
 /* Override any gcc2 internal prototype to avoid an error.  */
 /* We use char because int might match the return type of a gcc2
@@ -45304,7 +45538,7 @@ int main() {
 gdImageCreate()
 ; return 0; }
 EOF
-if { (eval echo configure:45308: \"$ac_link\") 1>&5; (eval $ac_link) 2>&5; } && test -s conftest${ac_exeext}; then
+if { (eval echo configure:45547: \"$ac_link\") 1>&5; (eval $ac_link) 2>&5; } && test -s conftest${ac_exeext}; then
   rm -rf conftest*
   eval "ac_cv_lib_$ac_lib_var=yes"
 else
@@ -45369,7 +45603,7 @@ fi
 php_with_gettext=no
 
 echo $ac_n "checking for GNU gettext support""... $ac_c" 1>&6
-echo "configure:45373: checking for GNU gettext support" >&5
+echo "configure:45612: checking for GNU gettext support" >&5
 # Check whether --with-gettext or --without-gettext was given.
 if test "${with_gettext+set}" = set; then
   withval="$with_gettext"
@@ -45424,7 +45658,7 @@ if test "$PHP_GETTEXT" != "no"; then
   O_LDFLAGS=$LDFLAGS
   LDFLAGS="$LDFLAGS -L$GETTEXT_LIBDIR"
   echo $ac_n "checking for bindtextdomain in -lintl""... $ac_c" 1>&6
-echo "configure:45428: checking for bindtextdomain in -lintl" >&5
+echo "configure:45667: checking for bindtextdomain in -lintl" >&5
 ac_lib_var=`echo intl'_'bindtextdomain | sed 'y%./+-%__p_%'`
 if eval "test \"`echo '$''{'ac_cv_lib_$ac_lib_var'+set}'`\" = set"; then
   echo $ac_n "(cached) $ac_c" 1>&6
@@ -45432,7 +45666,7 @@ else
   ac_save_LIBS="$LIBS"
 LIBS="-lintl  $LIBS"
 cat > conftest.$ac_ext <<EOF
-#line 45436 "configure"
+#line 45675 "configure"
 #include "confdefs.h"
 /* Override any gcc2 internal prototype to avoid an error.  */
 /* We use char because int might match the return type of a gcc2
@@ -45443,7 +45677,7 @@ int main() {
 bindtextdomain()
 ; return 0; }
 EOF
-if { (eval echo configure:45447: \"$ac_link\") 1>&5; (eval $ac_link) 2>&5; } && test -s conftest${ac_exeext}; then
+if { (eval echo configure:45686: \"$ac_link\") 1>&5; (eval $ac_link) 2>&5; } && test -s conftest${ac_exeext}; then
   rm -rf conftest*
   eval "ac_cv_lib_$ac_lib_var=yes"
 else
@@ -45465,7 +45699,7 @@ if eval "test \"`echo '$ac_cv_lib_'$ac_lib_var`\" = yes"; then
 else
   echo "$ac_t""no" 1>&6
 echo $ac_n "checking for bindtextdomain in -lc""... $ac_c" 1>&6
-echo "configure:45469: checking for bindtextdomain in -lc" >&5
+echo "configure:45708: checking for bindtextdomain in -lc" >&5
 ac_lib_var=`echo c'_'bindtextdomain | sed 'y%./+-%__p_%'`
 if eval "test \"`echo '$''{'ac_cv_lib_$ac_lib_var'+set}'`\" = set"; then
   echo $ac_n "(cached) $ac_c" 1>&6
@@ -45473,7 +45707,7 @@ else
   ac_save_LIBS="$LIBS"
 LIBS="-lc  $LIBS"
 cat > conftest.$ac_ext <<EOF
-#line 45477 "configure"
+#line 45716 "configure"
 #include "confdefs.h"
 /* Override any gcc2 internal prototype to avoid an error.  */
 /* We use char because int might match the return type of a gcc2
@@ -45484,7 +45718,7 @@ int main() {
 bindtextdomain()
 ; return 0; }
 EOF
-if { (eval echo configure:45488: \"$ac_link\") 1>&5; (eval $ac_link) 2>&5; } && test -s conftest${ac_exeext}; then
+if { (eval echo configure:45727: \"$ac_link\") 1>&5; (eval $ac_link) 2>&5; } && test -s conftest${ac_exeext}; then
   rm -rf conftest*
   eval "ac_cv_lib_$ac_lib_var=yes"
 else
@@ -45778,7 +46012,7 @@ EOF
       ac_obj=$1
       IFS=$old_IFS
       
-      PHP_CLI_OBJS="$PHP_CLI_OBJS $ac_bdir$ac_obj.lo"
+      PHP_GLOBAL_OBJS="$PHP_GLOBAL_OBJS $ac_bdir$ac_obj.lo"
 
       case $ac_src in
         *.c) ac_comp="$b_c_pre $ac_extra $ac_inc $b_c_meta -c $ac_srcdir$ac_src -o $ac_bdir$ac_obj.$b_lo $b_c_post" ;;
@@ -45948,7 +46182,7 @@ EOF
 
 
   echo $ac_n "checking for ngettext in -l$GETTEXT_CHECK_IN_LIB""... $ac_c" 1>&6
-echo "configure:45952: checking for ngettext in -l$GETTEXT_CHECK_IN_LIB" >&5
+echo "configure:46191: checking for ngettext in -l$GETTEXT_CHECK_IN_LIB" >&5
 ac_lib_var=`echo $GETTEXT_CHECK_IN_LIB'_'ngettext | sed 'y%./+-%__p_%'`
 if eval "test \"`echo '$''{'ac_cv_lib_$ac_lib_var'+set}'`\" = set"; then
   echo $ac_n "(cached) $ac_c" 1>&6
@@ -45956,7 +46190,7 @@ else
   ac_save_LIBS="$LIBS"
 LIBS="-l$GETTEXT_CHECK_IN_LIB  $LIBS"
 cat > conftest.$ac_ext <<EOF
-#line 45960 "configure"
+#line 46199 "configure"
 #include "confdefs.h"
 /* Override any gcc2 internal prototype to avoid an error.  */
 /* We use char because int might match the return type of a gcc2
@@ -45967,7 +46201,7 @@ int main() {
 ngettext()
 ; return 0; }
 EOF
-if { (eval echo configure:45971: \"$ac_link\") 1>&5; (eval $ac_link) 2>&5; } && test -s conftest${ac_exeext}; then
+if { (eval echo configure:46210: \"$ac_link\") 1>&5; (eval $ac_link) 2>&5; } && test -s conftest${ac_exeext}; then
   rm -rf conftest*
   eval "ac_cv_lib_$ac_lib_var=yes"
 else
@@ -45991,7 +46225,7 @@ else
 fi
 
   echo $ac_n "checking for dngettext in -l$GETTEXT_CHECK_IN_LIB""... $ac_c" 1>&6
-echo "configure:45995: checking for dngettext in -l$GETTEXT_CHECK_IN_LIB" >&5
+echo "configure:46234: checking for dngettext in -l$GETTEXT_CHECK_IN_LIB" >&5
 ac_lib_var=`echo $GETTEXT_CHECK_IN_LIB'_'dngettext | sed 'y%./+-%__p_%'`
 if eval "test \"`echo '$''{'ac_cv_lib_$ac_lib_var'+set}'`\" = set"; then
   echo $ac_n "(cached) $ac_c" 1>&6
@@ -45999,7 +46233,7 @@ else
   ac_save_LIBS="$LIBS"
 LIBS="-l$GETTEXT_CHECK_IN_LIB  $LIBS"
 cat > conftest.$ac_ext <<EOF
-#line 46003 "configure"
+#line 46242 "configure"
 #include "confdefs.h"
 /* Override any gcc2 internal prototype to avoid an error.  */
 /* We use char because int might match the return type of a gcc2
@@ -46010,7 +46244,7 @@ int main() {
 dngettext()
 ; return 0; }
 EOF
-if { (eval echo configure:46014: \"$ac_link\") 1>&5; (eval $ac_link) 2>&5; } && test -s conftest${ac_exeext}; then
+if { (eval echo configure:46253: \"$ac_link\") 1>&5; (eval $ac_link) 2>&5; } && test -s conftest${ac_exeext}; then
   rm -rf conftest*
   eval "ac_cv_lib_$ac_lib_var=yes"
 else
@@ -46034,7 +46268,7 @@ else
 fi
 
   echo $ac_n "checking for dcngettext in -l$GETTEXT_CHECK_IN_LIB""... $ac_c" 1>&6
-echo "configure:46038: checking for dcngettext in -l$GETTEXT_CHECK_IN_LIB" >&5
+echo "configure:46277: checking for dcngettext in -l$GETTEXT_CHECK_IN_LIB" >&5
 ac_lib_var=`echo $GETTEXT_CHECK_IN_LIB'_'dcngettext | sed 'y%./+-%__p_%'`
 if eval "test \"`echo '$''{'ac_cv_lib_$ac_lib_var'+set}'`\" = set"; then
   echo $ac_n "(cached) $ac_c" 1>&6
@@ -46042,7 +46276,7 @@ else
   ac_save_LIBS="$LIBS"
 LIBS="-l$GETTEXT_CHECK_IN_LIB  $LIBS"
 cat > conftest.$ac_ext <<EOF
-#line 46046 "configure"
+#line 46285 "configure"
 #include "confdefs.h"
 /* Override any gcc2 internal prototype to avoid an error.  */
 /* We use char because int might match the return type of a gcc2
@@ -46053,7 +46287,7 @@ int main() {
 dcngettext()
 ; return 0; }
 EOF
-if { (eval echo configure:46057: \"$ac_link\") 1>&5; (eval $ac_link) 2>&5; } && test -s conftest${ac_exeext}; then
+if { (eval echo configure:46296: \"$ac_link\") 1>&5; (eval $ac_link) 2>&5; } && test -s conftest${ac_exeext}; then
   rm -rf conftest*
   eval "ac_cv_lib_$ac_lib_var=yes"
 else
@@ -46077,7 +46311,7 @@ else
 fi
 
   echo $ac_n "checking for bind_textdomain_codeset in -l$GETTEXT_CHECK_IN_LIB""... $ac_c" 1>&6
-echo "configure:46081: checking for bind_textdomain_codeset in -l$GETTEXT_CHECK_IN_LIB" >&5
+echo "configure:46320: checking for bind_textdomain_codeset in -l$GETTEXT_CHECK_IN_LIB" >&5
 ac_lib_var=`echo $GETTEXT_CHECK_IN_LIB'_'bind_textdomain_codeset | sed 'y%./+-%__p_%'`
 if eval "test \"`echo '$''{'ac_cv_lib_$ac_lib_var'+set}'`\" = set"; then
   echo $ac_n "(cached) $ac_c" 1>&6
@@ -46085,7 +46319,7 @@ else
   ac_save_LIBS="$LIBS"
 LIBS="-l$GETTEXT_CHECK_IN_LIB  $LIBS"
 cat > conftest.$ac_ext <<EOF
-#line 46089 "configure"
+#line 46328 "configure"
 #include "confdefs.h"
 /* Override any gcc2 internal prototype to avoid an error.  */
 /* We use char because int might match the return type of a gcc2
@@ -46096,7 +46330,7 @@ int main() {
 bind_textdomain_codeset()
 ; return 0; }
 EOF
-if { (eval echo configure:46100: \"$ac_link\") 1>&5; (eval $ac_link) 2>&5; } && test -s conftest${ac_exeext}; then
+if { (eval echo configure:46339: \"$ac_link\") 1>&5; (eval $ac_link) 2>&5; } && test -s conftest${ac_exeext}; then
   rm -rf conftest*
   eval "ac_cv_lib_$ac_lib_var=yes"
 else
@@ -46127,7 +46361,7 @@ fi
 php_with_gmp=no
 
 echo $ac_n "checking for GNU MP support""... $ac_c" 1>&6
-echo "configure:46131: checking for GNU MP support" >&5
+echo "configure:46370: checking for GNU MP support" >&5
 # Check whether --with-gmp or --without-gmp was given.
 if test "${with_gmp+set}" = set; then
   withval="$with_gmp"
@@ -46275,7 +46509,7 @@ if test "$PHP_GMP" != "no"; then
   done
 
   echo $ac_n "checking for __gmp_randinit_lc_2exp_size in -lgmp""... $ac_c" 1>&6
-echo "configure:46279: checking for __gmp_randinit_lc_2exp_size in -lgmp" >&5
+echo "configure:46518: checking for __gmp_randinit_lc_2exp_size in -lgmp" >&5
 ac_lib_var=`echo gmp'_'__gmp_randinit_lc_2exp_size | sed 'y%./+-%__p_%'`
 if eval "test \"`echo '$''{'ac_cv_lib_$ac_lib_var'+set}'`\" = set"; then
   echo $ac_n "(cached) $ac_c" 1>&6
@@ -46283,7 +46517,7 @@ else
   ac_save_LIBS="$LIBS"
 LIBS="-lgmp  $LIBS"
 cat > conftest.$ac_ext <<EOF
-#line 46287 "configure"
+#line 46526 "configure"
 #include "confdefs.h"
 /* Override any gcc2 internal prototype to avoid an error.  */
 /* We use char because int might match the return type of a gcc2
@@ -46294,7 +46528,7 @@ int main() {
 __gmp_randinit_lc_2exp_size()
 ; return 0; }
 EOF
-if { (eval echo configure:46298: \"$ac_link\") 1>&5; (eval $ac_link) 2>&5; } && test -s conftest${ac_exeext}; then
+if { (eval echo configure:46537: \"$ac_link\") 1>&5; (eval $ac_link) 2>&5; } && test -s conftest${ac_exeext}; then
   rm -rf conftest*
   eval "ac_cv_lib_$ac_lib_var=yes"
 else
@@ -46419,7 +46653,7 @@ else
   done
 
   echo $ac_n "checking for gmp_randinit_lc_2exp_size in -lgmp""... $ac_c" 1>&6
-echo "configure:46423: checking for gmp_randinit_lc_2exp_size in -lgmp" >&5
+echo "configure:46662: checking for gmp_randinit_lc_2exp_size in -lgmp" >&5
 ac_lib_var=`echo gmp'_'gmp_randinit_lc_2exp_size | sed 'y%./+-%__p_%'`
 if eval "test \"`echo '$''{'ac_cv_lib_$ac_lib_var'+set}'`\" = set"; then
   echo $ac_n "(cached) $ac_c" 1>&6
@@ -46427,7 +46661,7 @@ else
   ac_save_LIBS="$LIBS"
 LIBS="-lgmp  $LIBS"
 cat > conftest.$ac_ext <<EOF
-#line 46431 "configure"
+#line 46670 "configure"
 #include "confdefs.h"
 /* Override any gcc2 internal prototype to avoid an error.  */
 /* We use char because int might match the return type of a gcc2
@@ -46438,7 +46672,7 @@ int main() {
 gmp_randinit_lc_2exp_size()
 ; return 0; }
 EOF
-if { (eval echo configure:46442: \"$ac_link\") 1>&5; (eval $ac_link) 2>&5; } && test -s conftest${ac_exeext}; then
+if { (eval echo configure:46681: \"$ac_link\") 1>&5; (eval $ac_link) 2>&5; } && test -s conftest${ac_exeext}; then
   rm -rf conftest*
   eval "ac_cv_lib_$ac_lib_var=yes"
 else
@@ -46863,7 +47097,7 @@ EOF
       ac_obj=$1
       IFS=$old_IFS
       
-      PHP_CLI_OBJS="$PHP_CLI_OBJS $ac_bdir$ac_obj.lo"
+      PHP_GLOBAL_OBJS="$PHP_GLOBAL_OBJS $ac_bdir$ac_obj.lo"
 
       case $ac_src in
         *.c) ac_comp="$b_c_pre $ac_extra $ac_inc $b_c_meta -c $ac_srcdir$ac_src -o $ac_bdir$ac_obj.$b_lo $b_c_post" ;;
@@ -46910,7 +47144,7 @@ fi
 php_with_mhash=no
 
 echo $ac_n "checking for mhash support""... $ac_c" 1>&6
-echo "configure:46914: checking for mhash support" >&5
+echo "configure:47153: checking for mhash support" >&5
 # Check whether --with-mhash or --without-mhash was given.
 if test "${with_mhash+set}" = set; then
   withval="$with_mhash"
@@ -46954,7 +47188,7 @@ echo "$ac_t""$ext_output" 1>&6
 php_enable_hash=yes
 
 echo $ac_n "checking whether to enable hash support""... $ac_c" 1>&6
-echo "configure:46958: checking whether to enable hash support" >&5
+echo "configure:47197: checking whether to enable hash support" >&5
 # Check whether --enable-hash or --disable-hash was given.
 if test "${enable_hash+set}" = set; then
   enableval="$enable_hash"
@@ -47012,7 +47246,7 @@ EOF
 
 
   echo $ac_n "checking whether byte ordering is bigendian""... $ac_c" 1>&6
-echo "configure:47016: checking whether byte ordering is bigendian" >&5
+echo "configure:47255: checking whether byte ordering is bigendian" >&5
 if eval "test \"`echo '$''{'ac_cv_c_bigendian_php'+set}'`\" = set"; then
   echo $ac_n "(cached) $ac_c" 1>&6
 else
@@ -47022,7 +47256,7 @@ else
   ac_cv_c_bigendian_php=unknown
 else
   cat > conftest.$ac_ext <<EOF
-#line 47026 "configure"
+#line 47265 "configure"
 #include "confdefs.h"
 
 int main(void)
@@ -47038,7 +47272,7 @@ int main(void)
 }
   
 EOF
-if { (eval echo configure:47042: \"$ac_link\") 1>&5; (eval $ac_link) 2>&5; } && test -s conftest${ac_exeext} && (./conftest; exit) 2>/dev/null
+if { (eval echo configure:47281: \"$ac_link\") 1>&5; (eval $ac_link) 2>&5; } && test -s conftest${ac_exeext} && (./conftest; exit) 2>/dev/null
 then
   ac_cv_c_bigendian_php=yes
 else
@@ -47063,7 +47297,7 @@ EOF
 
 
   echo $ac_n "checking size of short""... $ac_c" 1>&6
-echo "configure:47067: checking size of short" >&5
+echo "configure:47306: checking size of short" >&5
 if eval "test \"`echo '$''{'ac_cv_sizeof_short'+set}'`\" = set"; then
   echo $ac_n "(cached) $ac_c" 1>&6
 else
@@ -47071,7 +47305,7 @@ else
   ac_cv_sizeof_short=2
 else
   cat > conftest.$ac_ext <<EOF
-#line 47075 "configure"
+#line 47314 "configure"
 #include "confdefs.h"
 #include <stdio.h>
 main()
@@ -47082,7 +47316,7 @@ main()
   exit(0);
 }
 EOF
-if { (eval echo configure:47086: \"$ac_link\") 1>&5; (eval $ac_link) 2>&5; } && test -s conftest${ac_exeext} && (./conftest; exit) 2>/dev/null
+if { (eval echo configure:47325: \"$ac_link\") 1>&5; (eval $ac_link) 2>&5; } && test -s conftest${ac_exeext} && (./conftest; exit) 2>/dev/null
 then
   ac_cv_sizeof_short=`cat conftestval`
 else
@@ -47102,7 +47336,7 @@ EOF
 
 
   echo $ac_n "checking size of int""... $ac_c" 1>&6
-echo "configure:47106: checking size of int" >&5
+echo "configure:47345: checking size of int" >&5
 if eval "test \"`echo '$''{'ac_cv_sizeof_int'+set}'`\" = set"; then
   echo $ac_n "(cached) $ac_c" 1>&6
 else
@@ -47110,7 +47344,7 @@ else
   ac_cv_sizeof_int=4
 else
   cat > conftest.$ac_ext <<EOF
-#line 47114 "configure"
+#line 47353 "configure"
 #include "confdefs.h"
 #include <stdio.h>
 main()
@@ -47121,7 +47355,7 @@ main()
   exit(0);
 }
 EOF
-if { (eval echo configure:47125: \"$ac_link\") 1>&5; (eval $ac_link) 2>&5; } && test -s conftest${ac_exeext} && (./conftest; exit) 2>/dev/null
+if { (eval echo configure:47364: \"$ac_link\") 1>&5; (eval $ac_link) 2>&5; } && test -s conftest${ac_exeext} && (./conftest; exit) 2>/dev/null
 then
   ac_cv_sizeof_int=`cat conftestval`
 else
@@ -47141,7 +47375,7 @@ EOF
 
 
   echo $ac_n "checking size of long""... $ac_c" 1>&6
-echo "configure:47145: checking size of long" >&5
+echo "configure:47384: checking size of long" >&5
 if eval "test \"`echo '$''{'ac_cv_sizeof_long'+set}'`\" = set"; then
   echo $ac_n "(cached) $ac_c" 1>&6
 else
@@ -47149,7 +47383,7 @@ else
   ac_cv_sizeof_long=4
 else
   cat > conftest.$ac_ext <<EOF
-#line 47153 "configure"
+#line 47392 "configure"
 #include "confdefs.h"
 #include <stdio.h>
 main()
@@ -47160,7 +47394,7 @@ main()
   exit(0);
 }
 EOF
-if { (eval echo configure:47164: \"$ac_link\") 1>&5; (eval $ac_link) 2>&5; } && test -s conftest${ac_exeext} && (./conftest; exit) 2>/dev/null
+if { (eval echo configure:47403: \"$ac_link\") 1>&5; (eval $ac_link) 2>&5; } && test -s conftest${ac_exeext} && (./conftest; exit) 2>/dev/null
 then
   ac_cv_sizeof_long=`cat conftestval`
 else
@@ -47180,7 +47414,7 @@ EOF
 
 
   echo $ac_n "checking size of long long""... $ac_c" 1>&6
-echo "configure:47184: checking size of long long" >&5
+echo "configure:47423: checking size of long long" >&5
 if eval "test \"`echo '$''{'ac_cv_sizeof_long_long'+set}'`\" = set"; then
   echo $ac_n "(cached) $ac_c" 1>&6
 else
@@ -47188,7 +47422,7 @@ else
   ac_cv_sizeof_long_long=8
 else
   cat > conftest.$ac_ext <<EOF
-#line 47192 "configure"
+#line 47431 "configure"
 #include "confdefs.h"
 #include <stdio.h>
 main()
@@ -47199,7 +47433,7 @@ main()
   exit(0);
 }
 EOF
-if { (eval echo configure:47203: \"$ac_link\") 1>&5; (eval $ac_link) 2>&5; } && test -s conftest${ac_exeext} && (./conftest; exit) 2>/dev/null
+if { (eval echo configure:47442: \"$ac_link\") 1>&5; (eval $ac_link) 2>&5; } && test -s conftest${ac_exeext} && (./conftest; exit) 2>/dev/null
 then
   ac_cv_sizeof_long_long=`cat conftestval`
 else
@@ -47486,7 +47720,7 @@ EOF
       ac_obj=$1
       IFS=$old_IFS
       
-      PHP_CLI_OBJS="$PHP_CLI_OBJS $ac_bdir$ac_obj.lo"
+      PHP_GLOBAL_OBJS="$PHP_GLOBAL_OBJS $ac_bdir$ac_obj.lo"
 
       case $ac_src in
         *.c) ac_comp="$b_c_pre $ac_extra $ac_inc $b_c_meta -c $ac_srcdir$ac_src -o $ac_bdir$ac_obj.$b_lo $b_c_post" ;;
@@ -47548,7 +47782,7 @@ fi
 php_with_iconv=yes
 
 echo $ac_n "checking for iconv support""... $ac_c" 1>&6
-echo "configure:47552: checking for iconv support" >&5
+echo "configure:47791: checking for iconv support" >&5
 # Check whether --with-iconv or --without-iconv was given.
 if test "${with_iconv+set}" = set; then
   withval="$with_iconv"
@@ -47612,12 +47846,12 @@ if test "$PHP_ICONV" != "no"; then
             LIBS_save="$LIBS"
     LIBS=
     echo $ac_n "checking for iconv""... $ac_c" 1>&6
-echo "configure:47616: checking for iconv" >&5
+echo "configure:47855: checking for iconv" >&5
 if eval "test \"`echo '$''{'ac_cv_func_iconv'+set}'`\" = set"; then
   echo $ac_n "(cached) $ac_c" 1>&6
 else
   cat > conftest.$ac_ext <<EOF
-#line 47621 "configure"
+#line 47860 "configure"
 #include "confdefs.h"
 /* System header to define __stub macros and hopefully few prototypes,
     which can conflict with char iconv(); below.  */
@@ -47640,7 +47874,7 @@ iconv();
 
 ; return 0; }
 EOF
-if { (eval echo configure:47644: \"$ac_link\") 1>&5; (eval $ac_link) 2>&5; } && test -s conftest${ac_exeext}; then
+if { (eval echo configure:47883: \"$ac_link\") 1>&5; (eval $ac_link) 2>&5; } && test -s conftest${ac_exeext}; then
   rm -rf conftest*
   eval "ac_cv_func_iconv=yes"
 else
@@ -47661,12 +47895,12 @@ else
   echo "$ac_t""no" 1>&6
 
       echo $ac_n "checking for libiconv""... $ac_c" 1>&6
-echo "configure:47665: checking for libiconv" >&5
+echo "configure:47904: checking for libiconv" >&5
 if eval "test \"`echo '$''{'ac_cv_func_libiconv'+set}'`\" = set"; then
   echo $ac_n "(cached) $ac_c" 1>&6
 else
   cat > conftest.$ac_ext <<EOF
-#line 47670 "configure"
+#line 47909 "configure"
 #include "confdefs.h"
 /* System header to define __stub macros and hopefully few prototypes,
     which can conflict with char libiconv(); below.  */
@@ -47689,7 +47923,7 @@ libiconv();
 
 ; return 0; }
 EOF
-if { (eval echo configure:47693: \"$ac_link\") 1>&5; (eval $ac_link) 2>&5; } && test -s conftest${ac_exeext}; then
+if { (eval echo configure:47932: \"$ac_link\") 1>&5; (eval $ac_link) 2>&5; } && test -s conftest${ac_exeext}; then
   rm -rf conftest*
   eval "ac_cv_func_libiconv=yes"
 else
@@ -47846,7 +48080,7 @@ EOF
   done
 
   echo $ac_n "checking for libiconv in -l$iconv_lib_name""... $ac_c" 1>&6
-echo "configure:47850: checking for libiconv in -l$iconv_lib_name" >&5
+echo "configure:48089: checking for libiconv in -l$iconv_lib_name" >&5
 ac_lib_var=`echo $iconv_lib_name'_'libiconv | sed 'y%./+-%__p_%'`
 if eval "test \"`echo '$''{'ac_cv_lib_$ac_lib_var'+set}'`\" = set"; then
   echo $ac_n "(cached) $ac_c" 1>&6
@@ -47854,7 +48088,7 @@ else
   ac_save_LIBS="$LIBS"
 LIBS="-l$iconv_lib_name  $LIBS"
 cat > conftest.$ac_ext <<EOF
-#line 47858 "configure"
+#line 48097 "configure"
 #include "confdefs.h"
 /* Override any gcc2 internal prototype to avoid an error.  */
 /* We use char because int might match the return type of a gcc2
@@ -47865,7 +48099,7 @@ int main() {
 libiconv()
 ; return 0; }
 EOF
-if { (eval echo configure:47869: \"$ac_link\") 1>&5; (eval $ac_link) 2>&5; } && test -s conftest${ac_exeext}; then
+if { (eval echo configure:48108: \"$ac_link\") 1>&5; (eval $ac_link) 2>&5; } && test -s conftest${ac_exeext}; then
   rm -rf conftest*
   eval "ac_cv_lib_$ac_lib_var=yes"
 else
@@ -48006,7 +48240,7 @@ else
   done
 
   echo $ac_n "checking for iconv in -l$iconv_lib_name""... $ac_c" 1>&6
-echo "configure:48010: checking for iconv in -l$iconv_lib_name" >&5
+echo "configure:48249: checking for iconv in -l$iconv_lib_name" >&5
 ac_lib_var=`echo $iconv_lib_name'_'iconv | sed 'y%./+-%__p_%'`
 if eval "test \"`echo '$''{'ac_cv_lib_$ac_lib_var'+set}'`\" = set"; then
   echo $ac_n "(cached) $ac_c" 1>&6
@@ -48014,7 +48248,7 @@ else
   ac_save_LIBS="$LIBS"
 LIBS="-l$iconv_lib_name  $LIBS"
 cat > conftest.$ac_ext <<EOF
-#line 48018 "configure"
+#line 48257 "configure"
 #include "confdefs.h"
 /* Override any gcc2 internal prototype to avoid an error.  */
 /* We use char because int might match the return type of a gcc2
@@ -48025,7 +48259,7 @@ int main() {
 iconv()
 ; return 0; }
 EOF
-if { (eval echo configure:48029: \"$ac_link\") 1>&5; (eval $ac_link) 2>&5; } && test -s conftest${ac_exeext}; then
+if { (eval echo configure:48268: \"$ac_link\") 1>&5; (eval $ac_link) 2>&5; } && test -s conftest${ac_exeext}; then
   rm -rf conftest*
   eval "ac_cv_lib_$ac_lib_var=yes"
 else
@@ -48236,16 +48470,16 @@ else
     fi 
 
     echo $ac_n "checking if iconv is glibc's""... $ac_c" 1>&6
-echo "configure:48240: checking if iconv is glibc's" >&5
+echo "configure:48479: checking if iconv is glibc's" >&5
     cat > conftest.$ac_ext <<EOF
-#line 48242 "configure"
+#line 48481 "configure"
 #include "confdefs.h"
 #include <gnu/libc-version.h>
 int main() {
 gnu_get_libc_version();
 ; return 0; }
 EOF
-if { (eval echo configure:48249: \"$ac_link\") 1>&5; (eval $ac_link) 2>&5; } && test -s conftest${ac_exeext}; then
+if { (eval echo configure:48488: \"$ac_link\") 1>&5; (eval $ac_link) 2>&5; } && test -s conftest${ac_exeext}; then
   rm -rf conftest*
   
       echo "$ac_t""yes" 1>&6
@@ -48263,7 +48497,7 @@ rm -f conftest*
 
     if test -z "$iconv_impl_name"; then
       echo $ac_n "checking if using GNU libiconv""... $ac_c" 1>&6
-echo "configure:48267: checking if using GNU libiconv" >&5
+echo "configure:48506: checking if using GNU libiconv" >&5
       php_iconv_old_ld="$LDFLAGS"
       LDFLAGS="-liconv $LDFLAGS"
       if test "$cross_compiling" = yes; then
@@ -48273,7 +48507,7 @@ echo "configure:48267: checking if using GNU libiconv" >&5
       
 else
   cat > conftest.$ac_ext <<EOF
-#line 48277 "configure"
+#line 48516 "configure"
 #include "confdefs.h"
 
 #include <$PHP_ICONV_H_PATH>
@@ -48283,7 +48517,7 @@ int main() {
 }
       
 EOF
-if { (eval echo configure:48287: \"$ac_link\") 1>&5; (eval $ac_link) 2>&5; } && test -s conftest${ac_exeext} && (./conftest; exit) 2>/dev/null
+if { (eval echo configure:48526: \"$ac_link\") 1>&5; (eval $ac_link) 2>&5; } && test -s conftest${ac_exeext} && (./conftest; exit) 2>/dev/null
 then
   
         echo "$ac_t""yes" 1>&6
@@ -48305,16 +48539,16 @@ fi
 
     if test -z "$iconv_impl_name"; then
       echo $ac_n "checking if iconv is Konstantin Chuguev's""... $ac_c" 1>&6
-echo "configure:48309: checking if iconv is Konstantin Chuguev's" >&5
+echo "configure:48548: checking if iconv is Konstantin Chuguev's" >&5
       cat > conftest.$ac_ext <<EOF
-#line 48311 "configure"
+#line 48550 "configure"
 #include "confdefs.h"
 #include <iconv.h>
 int main() {
 iconv_ccs_init(NULL, NULL);
 ; return 0; }
 EOF
-if { (eval echo configure:48318: \"$ac_link\") 1>&5; (eval $ac_link) 2>&5; } && test -s conftest${ac_exeext}; then
+if { (eval echo configure:48557: \"$ac_link\") 1>&5; (eval $ac_link) 2>&5; } && test -s conftest${ac_exeext}; then
   rm -rf conftest*
   
         echo "$ac_t""yes" 1>&6
@@ -48333,18 +48567,18 @@ rm -f conftest*
 
     if test -z "$iconv_impl_name"; then
       echo $ac_n "checking if using IBM iconv""... $ac_c" 1>&6
-echo "configure:48337: checking if using IBM iconv" >&5
+echo "configure:48576: checking if using IBM iconv" >&5
       php_iconv_old_ld="$LDFLAGS"
       LDFLAGS="-liconv $LDFLAGS"
       cat > conftest.$ac_ext <<EOF
-#line 48341 "configure"
+#line 48580 "configure"
 #include "confdefs.h"
 #include <iconv.h>
 int main() {
 cstoccsid("");
 ; return 0; }
 EOF
-if { (eval echo configure:48348: \"$ac_link\") 1>&5; (eval $ac_link) 2>&5; } && test -s conftest${ac_exeext}; then
+if { (eval echo configure:48587: \"$ac_link\") 1>&5; (eval $ac_link) 2>&5; } && test -s conftest${ac_exeext}; then
   rm -rf conftest*
   
         echo "$ac_t""yes" 1>&6
@@ -48530,7 +48764,7 @@ EOF
     esac
 
     echo $ac_n "checking if iconv supports errno""... $ac_c" 1>&6
-echo "configure:48534: checking if iconv supports errno" >&5
+echo "configure:48773: checking if iconv supports errno" >&5
     if test "$cross_compiling" = yes; then
   
       echo "$ac_t""no" 1>&6
@@ -48544,7 +48778,7 @@ EOF
     
 else
   cat > conftest.$ac_ext <<EOF
-#line 48548 "configure"
+#line 48787 "configure"
 #include "confdefs.h"
 
 #include <$PHP_ICONV_H_PATH>
@@ -48565,7 +48799,7 @@ int main() {
 }
     
 EOF
-if { (eval echo configure:48569: \"$ac_link\") 1>&5; (eval $ac_link) 2>&5; } && test -s conftest${ac_exeext} && (./conftest; exit) 2>/dev/null
+if { (eval echo configure:48808: \"$ac_link\") 1>&5; (eval $ac_link) 2>&5; } && test -s conftest${ac_exeext} && (./conftest; exit) 2>/dev/null
 then
   
       echo "$ac_t""yes" 1>&6
@@ -48597,9 +48831,9 @@ fi
 
 
     echo $ac_n "checking if your cpp allows macro usage in include lines""... $ac_c" 1>&6
-echo "configure:48601: checking if your cpp allows macro usage in include lines" >&5
+echo "configure:48840: checking if your cpp allows macro usage in include lines" >&5
     cat > conftest.$ac_ext <<EOF
-#line 48603 "configure"
+#line 48842 "configure"
 #include "confdefs.h"
 
 #define FOO <$PHP_ICONV_H_PATH>
@@ -48609,7 +48843,7 @@ int main() {
 
 ; return 0; }
 EOF
-if { (eval echo configure:48613: \"$ac_compile\") 1>&5; (eval $ac_compile) 2>&5; }; then
+if { (eval echo configure:48852: \"$ac_compile\") 1>&5; (eval $ac_compile) 2>&5; }; then
   rm -rf conftest*
   
       echo "$ac_t""yes" 1>&6
@@ -48890,7 +49124,7 @@ EOF
       ac_obj=$1
       IFS=$old_IFS
       
-      PHP_CLI_OBJS="$PHP_CLI_OBJS $ac_bdir$ac_obj.lo"
+      PHP_GLOBAL_OBJS="$PHP_GLOBAL_OBJS $ac_bdir$ac_obj.lo"
 
       case $ac_src in
         *.c) ac_comp="$b_c_pre $ac_extra $ac_inc $b_c_meta -c $ac_srcdir$ac_src -o $ac_bdir$ac_obj.$b_lo $b_c_post" ;;
@@ -48965,7 +49199,7 @@ fi
 php_with_imap=no
 
 echo $ac_n "checking for IMAP support""... $ac_c" 1>&6
-echo "configure:48969: checking for IMAP support" >&5
+echo "configure:49208: checking for IMAP support" >&5
 # Check whether --with-imap or --without-imap was given.
 if test "${with_imap+set}" = set; then
   withval="$with_imap"
@@ -49009,7 +49243,7 @@ echo "$ac_t""$ext_output" 1>&6
 php_with_kerberos=no
 
 echo $ac_n "checking for IMAP Kerberos support""... $ac_c" 1>&6
-echo "configure:49013: checking for IMAP Kerberos support" >&5
+echo "configure:49252: checking for IMAP Kerberos support" >&5
 # Check whether --with-kerberos or --without-kerberos was given.
 if test "${with_kerberos+set}" = set; then
   withval="$with_kerberos"
@@ -49032,7 +49266,7 @@ echo "$ac_t""$ext_output" 1>&6
 php_with_imap_ssl=no
 
 echo $ac_n "checking for IMAP SSL support""... $ac_c" 1>&6
-echo "configure:49036: checking for IMAP SSL support" >&5
+echo "configure:49275: checking for IMAP SSL support" >&5
 # Check whether --with-imap-ssl or --without-imap-ssl was given.
 if test "${with_imap_ssl+set}" = set; then
   withval="$with_imap_ssl"
@@ -49315,7 +49549,7 @@ EOF
       ac_obj=$1
       IFS=$old_IFS
       
-      PHP_CLI_OBJS="$PHP_CLI_OBJS $ac_bdir$ac_obj.lo"
+      PHP_GLOBAL_OBJS="$PHP_GLOBAL_OBJS $ac_bdir$ac_obj.lo"
 
       case $ac_src in
         *.c) ac_comp="$b_c_pre $ac_extra $ac_inc $b_c_meta -c $ac_srcdir$ac_src -o $ac_bdir$ac_obj.$b_lo $b_c_post" ;;
@@ -49436,7 +49670,7 @@ EOF
     done
 
         cat > conftest.$ac_ext <<EOF
-#line 49440 "configure"
+#line 49679 "configure"
 #include "confdefs.h"
 #include <$IMAP_INC_DIR/mail.h>
 EOF
@@ -49456,12 +49690,12 @@ rm -f conftest*
         old_CFLAGS=$CFLAGS
     CFLAGS="-I$IMAP_INC_DIR"
     echo $ac_n "checking for utf8_mime2text signature""... $ac_c" 1>&6
-echo "configure:49460: checking for utf8_mime2text signature" >&5
+echo "configure:49699: checking for utf8_mime2text signature" >&5
 if eval "test \"`echo '$''{'ac_cv_utf8_mime2text'+set}'`\" = set"; then
   echo $ac_n "(cached) $ac_c" 1>&6
 else
   cat > conftest.$ac_ext <<EOF
-#line 49465 "configure"
+#line 49704 "configure"
 #include "confdefs.h"
 
 #include <stdio.h>
@@ -49474,7 +49708,7 @@ int main() {
       
 ; return 0; }
 EOF
-if { (eval echo configure:49478: \"$ac_compile\") 1>&5; (eval $ac_compile) 2>&5; }; then
+if { (eval echo configure:49717: \"$ac_compile\") 1>&5; (eval $ac_compile) 2>&5; }; then
   rm -rf conftest*
   
         ac_cv_utf8_mime2text=old
@@ -49503,12 +49737,12 @@ EOF
     old_CFLAGS=$CFLAGS
     CFLAGS="-I$IMAP_INC_DIR"
     echo $ac_n "checking for U8T_DECOMPOSE""... $ac_c" 1>&6
-echo "configure:49507: checking for U8T_DECOMPOSE" >&5
+echo "configure:49746: checking for U8T_DECOMPOSE" >&5
 if eval "test \"`echo '$''{'ac_cv_u8t_canonical'+set}'`\" = set"; then
   echo $ac_n "(cached) $ac_c" 1>&6
 else
   cat > conftest.$ac_ext <<EOF
-#line 49512 "configure"
+#line 49751 "configure"
 #include "confdefs.h"
 
 #include <c-client.h>
@@ -49519,7 +49753,7 @@ int main() {
       
 ; return 0; }
 EOF
-if { (eval echo configure:49523: \"$ac_compile\") 1>&5; (eval $ac_compile) 2>&5; }; then
+if { (eval echo configure:49762: \"$ac_compile\") 1>&5; (eval $ac_compile) 2>&5; }; then
   rm -rf conftest*
   
          ac_cv_u8t_decompose=yes
@@ -49549,7 +49783,7 @@ echo "$ac_t""$ac_cv_u8t_canonical" 1>&6
         old_CPPFLAGS=$CPPFLAGS
     CPPFLAGS=-I$IMAP_INC_DIR
     cat > conftest.$ac_ext <<EOF
-#line 49553 "configure"
+#line 49792 "configure"
 #include "confdefs.h"
 
 #include "imap4r1.h"
@@ -49668,7 +49902,7 @@ rm -f conftest*
   done
 
   echo $ac_n "checking for pam_start in -lpam""... $ac_c" 1>&6
-echo "configure:49672: checking for pam_start in -lpam" >&5
+echo "configure:49911: checking for pam_start in -lpam" >&5
 ac_lib_var=`echo pam'_'pam_start | sed 'y%./+-%__p_%'`
 if eval "test \"`echo '$''{'ac_cv_lib_$ac_lib_var'+set}'`\" = set"; then
   echo $ac_n "(cached) $ac_c" 1>&6
@@ -49676,7 +49910,7 @@ else
   ac_save_LIBS="$LIBS"
 LIBS="-lpam  $LIBS"
 cat > conftest.$ac_ext <<EOF
-#line 49680 "configure"
+#line 49919 "configure"
 #include "confdefs.h"
 /* Override any gcc2 internal prototype to avoid an error.  */
 /* We use char because int might match the return type of a gcc2
@@ -49687,7 +49921,7 @@ int main() {
 pam_start()
 ; return 0; }
 EOF
-if { (eval echo configure:49691: \"$ac_link\") 1>&5; (eval $ac_link) 2>&5; } && test -s conftest${ac_exeext}; then
+if { (eval echo configure:49930: \"$ac_link\") 1>&5; (eval $ac_link) 2>&5; } && test -s conftest${ac_exeext}; then
   rm -rf conftest*
   eval "ac_cv_lib_$ac_lib_var=yes"
 else
@@ -49842,7 +50076,7 @@ fi
   done
 
   echo $ac_n "checking for crypt in -lcrypt""... $ac_c" 1>&6
-echo "configure:49846: checking for crypt in -lcrypt" >&5
+echo "configure:50085: checking for crypt in -lcrypt" >&5
 ac_lib_var=`echo crypt'_'crypt | sed 'y%./+-%__p_%'`
 if eval "test \"`echo '$''{'ac_cv_lib_$ac_lib_var'+set}'`\" = set"; then
   echo $ac_n "(cached) $ac_c" 1>&6
@@ -49850,7 +50084,7 @@ else
   ac_save_LIBS="$LIBS"
 LIBS="-lcrypt  $LIBS"
 cat > conftest.$ac_ext <<EOF
-#line 49854 "configure"
+#line 50093 "configure"
 #include "confdefs.h"
 /* Override any gcc2 internal prototype to avoid an error.  */
 /* We use char because int might match the return type of a gcc2
@@ -49861,7 +50095,7 @@ int main() {
 crypt()
 ; return 0; }
 EOF
-if { (eval echo configure:49865: \"$ac_link\") 1>&5; (eval $ac_link) 2>&5; } && test -s conftest${ac_exeext}; then
+if { (eval echo configure:50104: \"$ac_link\") 1>&5; (eval $ac_link) 2>&5; } && test -s conftest${ac_exeext}; then
   rm -rf conftest*
   eval "ac_cv_lib_$ac_lib_var=yes"
 else
@@ -50065,7 +50299,7 @@ fi
     # Extract the first word of "krb5-config", so it can be a program name with args.
 set dummy krb5-config; ac_word=$2
 echo $ac_n "checking for $ac_word""... $ac_c" 1>&6
-echo "configure:50069: checking for $ac_word" >&5
+echo "configure:50308: checking for $ac_word" >&5
 if eval "test \"`echo '$''{'ac_cv_path_KRB5_CONFIG'+set}'`\" = set"; then
   echo $ac_n "(cached) $ac_c" 1>&6
 else
@@ -50434,7 +50668,7 @@ else
 
   else
     cat > conftest.$ac_ext <<EOF
-#line 50438 "configure"
+#line 50677 "configure"
 #include "confdefs.h"
 #include <$IMAP_INC_DIR/linkage.h>
 EOF
@@ -50475,7 +50709,7 @@ rm -f conftest*
     # Extract the first word of "pkg-config", so it can be a program name with args.
 set dummy pkg-config; ac_word=$2
 echo $ac_n "checking for $ac_word""... $ac_c" 1>&6
-echo "configure:50479: checking for $ac_word" >&5
+echo "configure:50718: checking for $ac_word" >&5
 if eval "test \"`echo '$''{'ac_cv_path_PKG_CONFIG'+set}'`\" = set"; then
   echo $ac_n "(cached) $ac_c" 1>&6
 else
@@ -50680,9 +50914,9 @@ fi
     old_CPPFLAGS=$CPPFLAGS
     CPPFLAGS=-I$OPENSSL_INCDIR
     echo $ac_n "checking for OpenSSL version""... $ac_c" 1>&6
-echo "configure:50684: checking for OpenSSL version" >&5
+echo "configure:50923: checking for OpenSSL version" >&5
     cat > conftest.$ac_ext <<EOF
-#line 50686 "configure"
+#line 50925 "configure"
 #include "confdefs.h"
 
 #include <openssl/opensslv.h>
@@ -50837,7 +51071,7 @@ rm -f conftest*
   done
 
   echo $ac_n "checking for CRYPTO_free in -lcrypto""... $ac_c" 1>&6
-echo "configure:50841: checking for CRYPTO_free in -lcrypto" >&5
+echo "configure:51080: checking for CRYPTO_free in -lcrypto" >&5
 ac_lib_var=`echo crypto'_'CRYPTO_free | sed 'y%./+-%__p_%'`
 if eval "test \"`echo '$''{'ac_cv_lib_$ac_lib_var'+set}'`\" = set"; then
   echo $ac_n "(cached) $ac_c" 1>&6
@@ -50845,7 +51079,7 @@ else
   ac_save_LIBS="$LIBS"
 LIBS="-lcrypto  $LIBS"
 cat > conftest.$ac_ext <<EOF
-#line 50849 "configure"
+#line 51088 "configure"
 #include "confdefs.h"
 /* Override any gcc2 internal prototype to avoid an error.  */
 /* We use char because int might match the return type of a gcc2
@@ -50856,7 +51090,7 @@ int main() {
 CRYPTO_free()
 ; return 0; }
 EOF
-if { (eval echo configure:50860: \"$ac_link\") 1>&5; (eval $ac_link) 2>&5; } && test -s conftest${ac_exeext}; then
+if { (eval echo configure:51099: \"$ac_link\") 1>&5; (eval $ac_link) 2>&5; } && test -s conftest${ac_exeext}; then
   rm -rf conftest*
   eval "ac_cv_lib_$ac_lib_var=yes"
 else
@@ -51013,7 +51247,7 @@ fi
   done
 
   echo $ac_n "checking for SSL_CTX_set_ssl_version in -lssl""... $ac_c" 1>&6
-echo "configure:51017: checking for SSL_CTX_set_ssl_version in -lssl" >&5
+echo "configure:51256: checking for SSL_CTX_set_ssl_version in -lssl" >&5
 ac_lib_var=`echo ssl'_'SSL_CTX_set_ssl_version | sed 'y%./+-%__p_%'`
 if eval "test \"`echo '$''{'ac_cv_lib_$ac_lib_var'+set}'`\" = set"; then
   echo $ac_n "(cached) $ac_c" 1>&6
@@ -51021,7 +51255,7 @@ else
   ac_save_LIBS="$LIBS"
 LIBS="-lssl  $LIBS"
 cat > conftest.$ac_ext <<EOF
-#line 51025 "configure"
+#line 51264 "configure"
 #include "confdefs.h"
 /* Override any gcc2 internal prototype to avoid an error.  */
 /* We use char because int might match the return type of a gcc2
@@ -51032,7 +51266,7 @@ int main() {
 SSL_CTX_set_ssl_version()
 ; return 0; }
 EOF
-if { (eval echo configure:51036: \"$ac_link\") 1>&5; (eval $ac_link) 2>&5; } && test -s conftest${ac_exeext}; then
+if { (eval echo configure:51275: \"$ac_link\") 1>&5; (eval $ac_link) 2>&5; } && test -s conftest${ac_exeext}; then
   rm -rf conftest*
   eval "ac_cv_lib_$ac_lib_var=yes"
 else
@@ -51174,7 +51408,7 @@ else
 
   elif test -f "$IMAP_INC_DIR/linkage.c"; then
     cat > conftest.$ac_ext <<EOF
-#line 51178 "configure"
+#line 51417 "configure"
 #include "confdefs.h"
 #include <$IMAP_INC_DIR/linkage.c>
 EOF
@@ -51205,7 +51439,7 @@ rm -f conftest*
   
 else
   cat > conftest.$ac_ext <<EOF
-#line 51209 "configure"
+#line 51448 "configure"
 #include "confdefs.h"
 
     
@@ -51238,7 +51472,7 @@ else
     }
   
 EOF
-if { (eval echo configure:51242: \"$ac_link\") 1>&5; (eval $ac_link) 2>&5; } && test -s conftest${ac_exeext} && (./conftest; exit) 2>/dev/null
+if { (eval echo configure:51481: \"$ac_link\") 1>&5; (eval $ac_link) 2>&5; } && test -s conftest${ac_exeext} && (./conftest; exit) 2>/dev/null
 then
   
     LIBS=$old_LIBS
@@ -51274,7 +51508,7 @@ fi
   
 else
   cat > conftest.$ac_ext <<EOF
-#line 51278 "configure"
+#line 51517 "configure"
 #include "confdefs.h"
 
     
@@ -51307,7 +51541,7 @@ else
     }
   
 EOF
-if { (eval echo configure:51311: \"$ac_link\") 1>&5; (eval $ac_link) 2>&5; } && test -s conftest${ac_exeext} && (./conftest; exit) 2>/dev/null
+if { (eval echo configure:51550: \"$ac_link\") 1>&5; (eval $ac_link) 2>&5; } && test -s conftest${ac_exeext} && (./conftest; exit) 2>/dev/null
 then
   
     LIBS=$old_LIBS
@@ -51334,7 +51568,7 @@ fi
 
 
     echo $ac_n "checking whether rfc822_output_address_list function present""... $ac_c" 1>&6
-echo "configure:51338: checking whether rfc822_output_address_list function present" >&5
+echo "configure:51577: checking whether rfc822_output_address_list function present" >&5
     
   old_LIBS=$LIBS
   LIBS="
@@ -51346,7 +51580,7 @@ echo "configure:51338: checking whether rfc822_output_address_list function pres
   
 else
   cat > conftest.$ac_ext <<EOF
-#line 51350 "configure"
+#line 51589 "configure"
 #include "confdefs.h"
 
     
@@ -51382,7 +51616,7 @@ else
     }
   
 EOF
-if { (eval echo configure:51386: \"$ac_link\") 1>&5; (eval $ac_link) 2>&5; } && test -s conftest${ac_exeext} && (./conftest; exit) 2>/dev/null
+if { (eval echo configure:51625: \"$ac_link\") 1>&5; (eval $ac_link) 2>&5; } && test -s conftest${ac_exeext} && (./conftest; exit) 2>/dev/null
 then
   
     LIBS=$old_LIBS
@@ -51411,7 +51645,7 @@ fi
 
 
     echo $ac_n "checking whether build with IMAP works""... $ac_c" 1>&6
-echo "configure:51415: checking whether build with IMAP works" >&5
+echo "configure:51654: checking whether build with IMAP works" >&5
     
   
   old_LIBS=$LIBS
@@ -51422,7 +51656,7 @@ echo "configure:51415: checking whether build with IMAP works" >&5
   
 else
   cat > conftest.$ac_ext <<EOF
-#line 51426 "configure"
+#line 51665 "configure"
 #include "confdefs.h"
 
     
@@ -51455,7 +51689,7 @@ else
     }
   
 EOF
-if { (eval echo configure:51459: \"$ac_link\") 1>&5; (eval $ac_link) 2>&5; } && test -s conftest${ac_exeext} && (./conftest; exit) 2>/dev/null
+if { (eval echo configure:51698: \"$ac_link\") 1>&5; (eval $ac_link) 2>&5; } && test -s conftest${ac_exeext} && (./conftest; exit) 2>/dev/null
 then
   
     LIBS=$old_LIBS
@@ -51487,7 +51721,7 @@ fi
 php_with_interbase=no
 
 echo $ac_n "checking for InterBase support""... $ac_c" 1>&6
-echo "configure:51491: checking for InterBase support" >&5
+echo "configure:51730: checking for InterBase support" >&5
 # Check whether --with-interbase or --without-interbase was given.
 if test "${with_interbase+set}" = set; then
   withval="$with_interbase"
@@ -51634,7 +51868,7 @@ if test "$PHP_INTERBASE" != "no"; then
   done
 
   echo $ac_n "checking for isc_detach_database in -lfbclient""... $ac_c" 1>&6
-echo "configure:51638: checking for isc_detach_database in -lfbclient" >&5
+echo "configure:51877: checking for isc_detach_database in -lfbclient" >&5
 ac_lib_var=`echo fbclient'_'isc_detach_database | sed 'y%./+-%__p_%'`
 if eval "test \"`echo '$''{'ac_cv_lib_$ac_lib_var'+set}'`\" = set"; then
   echo $ac_n "(cached) $ac_c" 1>&6
@@ -51642,7 +51876,7 @@ else
   ac_save_LIBS="$LIBS"
 LIBS="-lfbclient  $LIBS"
 cat > conftest.$ac_ext <<EOF
-#line 51646 "configure"
+#line 51885 "configure"
 #include "confdefs.h"
 /* Override any gcc2 internal prototype to avoid an error.  */
 /* We use char because int might match the return type of a gcc2
@@ -51653,7 +51887,7 @@ int main() {
 isc_detach_database()
 ; return 0; }
 EOF
-if { (eval echo configure:51657: \"$ac_link\") 1>&5; (eval $ac_link) 2>&5; } && test -s conftest${ac_exeext}; then
+if { (eval echo configure:51896: \"$ac_link\") 1>&5; (eval $ac_link) 2>&5; } && test -s conftest${ac_exeext}; then
   rm -rf conftest*
   eval "ac_cv_lib_$ac_lib_var=yes"
 else
@@ -51780,7 +52014,7 @@ else
   done
 
   echo $ac_n "checking for isc_detach_database in -lgds""... $ac_c" 1>&6
-echo "configure:51784: checking for isc_detach_database in -lgds" >&5
+echo "configure:52023: checking for isc_detach_database in -lgds" >&5
 ac_lib_var=`echo gds'_'isc_detach_database | sed 'y%./+-%__p_%'`
 if eval "test \"`echo '$''{'ac_cv_lib_$ac_lib_var'+set}'`\" = set"; then
   echo $ac_n "(cached) $ac_c" 1>&6
@@ -51788,7 +52022,7 @@ else
   ac_save_LIBS="$LIBS"
 LIBS="-lgds  $LIBS"
 cat > conftest.$ac_ext <<EOF
-#line 51792 "configure"
+#line 52031 "configure"
 #include "confdefs.h"
 /* Override any gcc2 internal prototype to avoid an error.  */
 /* We use char because int might match the return type of a gcc2
@@ -51799,7 +52033,7 @@ int main() {
 isc_detach_database()
 ; return 0; }
 EOF
-if { (eval echo configure:51803: \"$ac_link\") 1>&5; (eval $ac_link) 2>&5; } && test -s conftest${ac_exeext}; then
+if { (eval echo configure:52042: \"$ac_link\") 1>&5; (eval $ac_link) 2>&5; } && test -s conftest${ac_exeext}; then
   rm -rf conftest*
   eval "ac_cv_lib_$ac_lib_var=yes"
 else
@@ -51926,7 +52160,7 @@ else
   done
 
   echo $ac_n "checking for isc_detach_database in -lib_util""... $ac_c" 1>&6
-echo "configure:51930: checking for isc_detach_database in -lib_util" >&5
+echo "configure:52169: checking for isc_detach_database in -lib_util" >&5
 ac_lib_var=`echo ib_util'_'isc_detach_database | sed 'y%./+-%__p_%'`
 if eval "test \"`echo '$''{'ac_cv_lib_$ac_lib_var'+set}'`\" = set"; then
   echo $ac_n "(cached) $ac_c" 1>&6
@@ -51934,7 +52168,7 @@ else
   ac_save_LIBS="$LIBS"
 LIBS="-lib_util  $LIBS"
 cat > conftest.$ac_ext <<EOF
-#line 51938 "configure"
+#line 52177 "configure"
 #include "confdefs.h"
 /* Override any gcc2 internal prototype to avoid an error.  */
 /* We use char because int might match the return type of a gcc2
@@ -51945,7 +52179,7 @@ int main() {
 isc_detach_database()
 ; return 0; }
 EOF
-if { (eval echo configure:51949: \"$ac_link\") 1>&5; (eval $ac_link) 2>&5; } && test -s conftest${ac_exeext}; then
+if { (eval echo configure:52188: \"$ac_link\") 1>&5; (eval $ac_link) 2>&5; } && test -s conftest${ac_exeext}; then
   rm -rf conftest*
   eval "ac_cv_lib_$ac_lib_var=yes"
 else
@@ -52379,7 +52613,7 @@ EOF
       ac_obj=$1
       IFS=$old_IFS
       
-      PHP_CLI_OBJS="$PHP_CLI_OBJS $ac_bdir$ac_obj.lo"
+      PHP_GLOBAL_OBJS="$PHP_GLOBAL_OBJS $ac_bdir$ac_obj.lo"
 
       case $ac_src in
         *.c) ac_comp="$b_c_pre $ac_extra $ac_inc $b_c_meta -c $ac_srcdir$ac_src -o $ac_bdir$ac_obj.$b_lo $b_c_post" ;;
@@ -52422,7 +52656,7 @@ fi
 php_enable_intl=no
 
 echo $ac_n "checking whether to enable internationalization support""... $ac_c" 1>&6
-echo "configure:52426: checking whether to enable internationalization support" >&5
+echo "configure:52665: checking whether to enable internationalization support" >&5
 # Check whether --enable-intl or --disable-intl was given.
 if test "${enable_intl+set}" = set; then
   enableval="$enable_intl"
@@ -52494,7 +52728,7 @@ ext_output=$PHP_ICU_DIR
         # Extract the first word of "icu-config", so it can be a program name with args.
 set dummy icu-config; ac_word=$2
 echo $ac_n "checking for $ac_word""... $ac_c" 1>&6
-echo "configure:52498: checking for $ac_word" >&5
+echo "configure:52737: checking for $ac_word" >&5
 if eval "test \"`echo '$''{'ac_cv_path_ICU_CONFIG'+set}'`\" = set"; then
   echo $ac_n "(cached) $ac_c" 1>&6
 else
@@ -52532,7 +52766,7 @@ fi
   fi
 
   echo $ac_n "checking for location of ICU headers and libraries""... $ac_c" 1>&6
-echo "configure:52536: checking for location of ICU headers and libraries" >&5
+echo "configure:52775: checking for location of ICU headers and libraries" >&5
 
     icu_install_prefix=`$ICU_CONFIG --prefix 2> /dev/null`
   if test "$?" != "0" || test -z "$icu_install_prefix"; then
@@ -52542,7 +52776,7 @@ echo "configure:52536: checking for location of ICU headers and libraries" >&5
     echo "$ac_t""$icu_install_prefix" 1>&6
 
         echo $ac_n "checking for ICU 3.4 or greater""... $ac_c" 1>&6
-echo "configure:52546: checking for ICU 3.4 or greater" >&5
+echo "configure:52785: checking for ICU 3.4 or greater" >&5
     icu_version_full=`$ICU_CONFIG --version`
     ac_IFS=$IFS
     IFS="."
@@ -52699,7 +52933,7 @@ do
 # Extract the first word of "$ac_prog", so it can be a program name with args.
 set dummy $ac_prog; ac_word=$2
 echo $ac_n "checking for $ac_word""... $ac_c" 1>&6
-echo "configure:52703: checking for $ac_word" >&5
+echo "configure:52942: checking for $ac_word" >&5
 if eval "test \"`echo '$''{'ac_cv_prog_CXX'+set}'`\" = set"; then
   echo $ac_n "(cached) $ac_c" 1>&6
 else
@@ -52731,7 +52965,7 @@ test -n "$CXX" || CXX="gcc"
 
 
 echo $ac_n "checking whether the C++ compiler ($CXX $CXXFLAGS $LDFLAGS) works""... $ac_c" 1>&6
-echo "configure:52735: checking whether the C++ compiler ($CXX $CXXFLAGS $LDFLAGS) works" >&5
+echo "configure:52974: checking whether the C++ compiler ($CXX $CXXFLAGS $LDFLAGS) works" >&5
 
 ac_ext=C
 # CXXFLAGS is not in ac_cpp because -g, -O, etc. are not valid cpp options.
@@ -52742,12 +52976,12 @@ cross_compiling=$ac_cv_prog_cxx_cross
 
 cat > conftest.$ac_ext << EOF
 
-#line 52746 "configure"
+#line 52985 "configure"
 #include "confdefs.h"
 
 int main(){return(0);}
 EOF
-if { (eval echo configure:52751: \"$ac_link\") 1>&5; (eval $ac_link) 2>&5; } && test -s conftest${ac_exeext}; then
+if { (eval echo configure:52990: \"$ac_link\") 1>&5; (eval $ac_link) 2>&5; } && test -s conftest${ac_exeext}; then
   ac_cv_prog_cxx_works=yes
   # If we can't run a trivial program, we are probably using a cross compiler.
   if (./conftest; exit) 2>/dev/null; then
@@ -52773,12 +53007,12 @@ if test $ac_cv_prog_cxx_works = no; then
   { echo "configure: error: installation or configuration problem: C++ compiler cannot create executables." 1>&2; exit 1; }
 fi
 echo $ac_n "checking whether the C++ compiler ($CXX $CXXFLAGS $LDFLAGS) is a cross-compiler""... $ac_c" 1>&6
-echo "configure:52777: checking whether the C++ compiler ($CXX $CXXFLAGS $LDFLAGS) is a cross-compiler" >&5
+echo "configure:53016: checking whether the C++ compiler ($CXX $CXXFLAGS $LDFLAGS) is a cross-compiler" >&5
 echo "$ac_t""$ac_cv_prog_cxx_cross" 1>&6
 cross_compiling=$ac_cv_prog_cxx_cross
 
 echo $ac_n "checking whether we are using GNU C++""... $ac_c" 1>&6
-echo "configure:52782: checking whether we are using GNU C++" >&5
+echo "configure:53021: checking whether we are using GNU C++" >&5
 if eval "test \"`echo '$''{'ac_cv_prog_gxx'+set}'`\" = set"; then
   echo $ac_n "(cached) $ac_c" 1>&6
 else
@@ -52787,7 +53021,7 @@ else
   yes;
 #endif
 EOF
-if { ac_try='${CXX-g++} -E conftest.C'; { (eval echo configure:52791: \"$ac_try\") 1>&5; (eval $ac_try) 2>&5; }; } | egrep yes >/dev/null 2>&1; then
+if { ac_try='${CXX-g++} -E conftest.C'; { (eval echo configure:53030: \"$ac_try\") 1>&5; (eval $ac_try) 2>&5; }; } | egrep yes >/dev/null 2>&1; then
   ac_cv_prog_gxx=yes
 else
   ac_cv_prog_gxx=no
@@ -52806,7 +53040,7 @@ ac_test_CXXFLAGS="${CXXFLAGS+set}"
 ac_save_CXXFLAGS="$CXXFLAGS"
 CXXFLAGS=
 echo $ac_n "checking whether ${CXX-g++} accepts -g""... $ac_c" 1>&6
-echo "configure:52810: checking whether ${CXX-g++} accepts -g" >&5
+echo "configure:53049: checking whether ${CXX-g++} accepts -g" >&5
 if eval "test \"`echo '$''{'ac_cv_prog_cxx_g'+set}'`\" = set"; then
   echo $ac_n "(cached) $ac_c" 1>&6
 else
@@ -52838,7 +53072,7 @@ else
 fi
 
     echo $ac_n "checking how to run the C++ preprocessor""... $ac_c" 1>&6
-echo "configure:52842: checking how to run the C++ preprocessor" >&5
+echo "configure:53081: checking how to run the C++ preprocessor" >&5
 if test -z "$CXXCPP"; then
 if eval "test \"`echo '$''{'ac_cv_prog_CXXCPP'+set}'`\" = set"; then
   echo $ac_n "(cached) $ac_c" 1>&6
@@ -52851,12 +53085,12 @@ ac_link='${CXX-g++} -o conftest${ac_exeext} $CXXFLAGS $CPPFLAGS $LDFLAGS conftes
 cross_compiling=$ac_cv_prog_cxx_cross
   CXXCPP="${CXX-g++} -E"
   cat > conftest.$ac_ext <<EOF
-#line 52855 "configure"
+#line 53094 "configure"
 #include "confdefs.h"
 #include <stdlib.h>
 EOF
 ac_try="$ac_cpp conftest.$ac_ext >/dev/null 2>conftest.out"
-{ (eval echo configure:52860: \"$ac_try\") 1>&5; (eval $ac_try) 2>&5; }
+{ (eval echo configure:53099: \"$ac_try\") 1>&5; (eval $ac_try) 2>&5; }
 ac_err=`grep -v '^ *+' conftest.out | grep -v "^conftest.${ac_ext}\$"`
 if test -z "$ac_err"; then
   :
@@ -53323,7 +53557,7 @@ EOF
       ac_obj=$1
       IFS=$old_IFS
       
-      PHP_CLI_OBJS="$PHP_CLI_OBJS $ac_bdir$ac_obj.lo"
+      PHP_GLOBAL_OBJS="$PHP_GLOBAL_OBJS $ac_bdir$ac_obj.lo"
 
       case $ac_src in
         *.c) ac_comp="$b_c_pre $ac_extra $ac_inc $b_c_meta -c $ac_srcdir$ac_src -o $ac_bdir$ac_obj.$b_lo $b_c_post" ;;
@@ -53414,7 +53648,7 @@ fi
 php_enable_json=yes
 
 echo $ac_n "checking whether to enable JavaScript Object Serialization support""... $ac_c" 1>&6
-echo "configure:53418: checking whether to enable JavaScript Object Serialization support" >&5
+echo "configure:53657: checking whether to enable JavaScript Object Serialization support" >&5
 # Check whether --enable-json or --disable-json was given.
 if test "${enable_json+set}" = set; then
   enableval="$enable_json"
@@ -53460,12 +53694,12 @@ if test "$PHP_JSON" != "no"; then
 EOF
 
   echo $ac_n "checking for ANSI C header files""... $ac_c" 1>&6
-echo "configure:53464: checking for ANSI C header files" >&5
+echo "configure:53703: checking for ANSI C header files" >&5
 if eval "test \"`echo '$''{'ac_cv_header_stdc'+set}'`\" = set"; then
   echo $ac_n "(cached) $ac_c" 1>&6
 else
   cat > conftest.$ac_ext <<EOF
-#line 53469 "configure"
+#line 53708 "configure"
 #include "confdefs.h"
 #include <stdlib.h>
 #include <stdarg.h>
@@ -53473,7 +53707,7 @@ else
 #include <float.h>
 EOF
 ac_try="$ac_cpp conftest.$ac_ext >/dev/null 2>conftest.out"
-{ (eval echo configure:53477: \"$ac_try\") 1>&5; (eval $ac_try) 2>&5; }
+{ (eval echo configure:53716: \"$ac_try\") 1>&5; (eval $ac_try) 2>&5; }
 ac_err=`grep -v '^ *+' conftest.out | grep -v "^conftest.${ac_ext}\$"`
 if test -z "$ac_err"; then
   rm -rf conftest*
@@ -53490,7 +53724,7 @@ rm -f conftest*
 if test $ac_cv_header_stdc = yes; then
   # SunOS 4.x string.h does not declare mem*, contrary to ANSI.
 cat > conftest.$ac_ext <<EOF
-#line 53494 "configure"
+#line 53733 "configure"
 #include "confdefs.h"
 #include <string.h>
 EOF
@@ -53508,7 +53742,7 @@ fi
 if test $ac_cv_header_stdc = yes; then
   # ISC 2.0.2 stdlib.h does not declare free, contrary to ANSI.
 cat > conftest.$ac_ext <<EOF
-#line 53512 "configure"
+#line 53751 "configure"
 #include "confdefs.h"
 #include <stdlib.h>
 EOF
@@ -53529,7 +53763,7 @@ if test "$cross_compiling" = yes; then
   :
 else
   cat > conftest.$ac_ext <<EOF
-#line 53533 "configure"
+#line 53772 "configure"
 #include "confdefs.h"
 #include <ctype.h>
 #define ISLOWER(c) ('a' <= (c) && (c) <= 'z')
@@ -53540,7 +53774,7 @@ if (XOR (islower (i), ISLOWER (i)) || toupper (i) != TOUPPER (i)) exit(2);
 exit (0); }
 
 EOF
-if { (eval echo configure:53544: \"$ac_link\") 1>&5; (eval $ac_link) 2>&5; } && test -s conftest${ac_exeext} && (./conftest; exit) 2>/dev/null
+if { (eval echo configure:53783: \"$ac_link\") 1>&5; (eval $ac_link) 2>&5; } && test -s conftest${ac_exeext} && (./conftest; exit) 2>/dev/null
 then
   :
 else
@@ -53823,7 +54057,7 @@ EOF
       ac_obj=$1
       IFS=$old_IFS
       
-      PHP_CLI_OBJS="$PHP_CLI_OBJS $ac_bdir$ac_obj.lo"
+      PHP_GLOBAL_OBJS="$PHP_GLOBAL_OBJS $ac_bdir$ac_obj.lo"
 
       case $ac_src in
         *.c) ac_comp="$b_c_pre $ac_extra $ac_inc $b_c_meta -c $ac_srcdir$ac_src -o $ac_bdir$ac_obj.$b_lo $b_c_post" ;;
@@ -53890,7 +54124,7 @@ fi
 php_with_ldap=no
 
 echo $ac_n "checking for LDAP support""... $ac_c" 1>&6
-echo "configure:53894: checking for LDAP support" >&5
+echo "configure:54133: checking for LDAP support" >&5
 # Check whether --with-ldap or --without-ldap was given.
 if test "${with_ldap+set}" = set; then
   withval="$with_ldap"
@@ -53934,7 +54168,7 @@ echo "$ac_t""$ext_output" 1>&6
 php_with_ldap_sasl=no
 
 echo $ac_n "checking for LDAP Cyrus SASL support""... $ac_c" 1>&6
-echo "configure:53938: checking for LDAP Cyrus SASL support" >&5
+echo "configure:54177: checking for LDAP Cyrus SASL support" >&5
 # Check whether --with-ldap-sasl or --without-ldap-sasl was given.
 if test "${with_ldap_sasl+set}" = set; then
   withval="$with_ldap_sasl"
@@ -54214,7 +54448,7 @@ EOF
       ac_obj=$1
       IFS=$old_IFS
       
-      PHP_CLI_OBJS="$PHP_CLI_OBJS $ac_bdir$ac_obj.lo"
+      PHP_GLOBAL_OBJS="$PHP_GLOBAL_OBJS $ac_bdir$ac_obj.lo"
 
       case $ac_src in
         *.c) ac_comp="$b_c_pre $ac_extra $ac_inc $b_c_meta -c $ac_srcdir$ac_src -o $ac_bdir$ac_obj.$b_lo $b_c_post" ;;
@@ -56276,19 +56510,19 @@ EOF
   LIBS="$LIBS $LDAP_SHARED_LIBADD"
 
     echo $ac_n "checking for 3 arg ldap_set_rebind_proc""... $ac_c" 1>&6
-echo "configure:56280: checking for 3 arg ldap_set_rebind_proc" >&5
+echo "configure:56519: checking for 3 arg ldap_set_rebind_proc" >&5
 if eval "test \"`echo '$''{'ac_cv_3arg_setrebindproc'+set}'`\" = set"; then
   echo $ac_n "(cached) $ac_c" 1>&6
 else
   cat > conftest.$ac_ext <<EOF
-#line 56285 "configure"
+#line 56524 "configure"
 #include "confdefs.h"
 #include <ldap.h>
 int main() {
 ldap_set_rebind_proc(0,0,0)
 ; return 0; }
 EOF
-if { (eval echo configure:56292: \"$ac_compile\") 1>&5; (eval $ac_compile) 2>&5; }; then
+if { (eval echo configure:56531: \"$ac_compile\") 1>&5; (eval $ac_compile) 2>&5; }; then
   rm -rf conftest*
   ac_cv_3arg_setrebindproc=yes
 else
@@ -56311,12 +56545,12 @@ EOF
       for ac_func in ldap_parse_result ldap_parse_reference ldap_start_tls_s
 do
 echo $ac_n "checking for $ac_func""... $ac_c" 1>&6
-echo "configure:56315: checking for $ac_func" >&5
+echo "configure:56554: checking for $ac_func" >&5
 if eval "test \"`echo '$''{'ac_cv_func_$ac_func'+set}'`\" = set"; then
   echo $ac_n "(cached) $ac_c" 1>&6
 else
   cat > conftest.$ac_ext <<EOF
-#line 56320 "configure"
+#line 56559 "configure"
 #include "confdefs.h"
 /* System header to define __stub macros and hopefully few prototypes,
     which can conflict with char $ac_func(); below.  */
@@ -56339,7 +56573,7 @@ $ac_func();
 
 ; return 0; }
 EOF
-if { (eval echo configure:56343: \"$ac_link\") 1>&5; (eval $ac_link) 2>&5; } && test -s conftest${ac_exeext}; then
+if { (eval echo configure:56582: \"$ac_link\") 1>&5; (eval $ac_link) 2>&5; } && test -s conftest${ac_exeext}; then
   rm -rf conftest*
   eval "ac_cv_func_$ac_func=yes"
 else
@@ -56501,7 +56735,7 @@ EOF
   done
 
   echo $ac_n "checking for sasl_version in -lsasl2""... $ac_c" 1>&6
-echo "configure:56505: checking for sasl_version in -lsasl2" >&5
+echo "configure:56744: checking for sasl_version in -lsasl2" >&5
 ac_lib_var=`echo sasl2'_'sasl_version | sed 'y%./+-%__p_%'`
 if eval "test \"`echo '$''{'ac_cv_lib_$ac_lib_var'+set}'`\" = set"; then
   echo $ac_n "(cached) $ac_c" 1>&6
@@ -56509,7 +56743,7 @@ else
   ac_save_LIBS="$LIBS"
 LIBS="-lsasl2  $LIBS"
 cat > conftest.$ac_ext <<EOF
-#line 56513 "configure"
+#line 56752 "configure"
 #include "confdefs.h"
 /* Override any gcc2 internal prototype to avoid an error.  */
 /* We use char because int might match the return type of a gcc2
@@ -56520,7 +56754,7 @@ int main() {
 sasl_version()
 ; return 0; }
 EOF
-if { (eval echo configure:56524: \"$ac_link\") 1>&5; (eval $ac_link) 2>&5; } && test -s conftest${ac_exeext}; then
+if { (eval echo configure:56763: \"$ac_link\") 1>&5; (eval $ac_link) 2>&5; } && test -s conftest${ac_exeext}; then
   rm -rf conftest*
   eval "ac_cv_lib_$ac_lib_var=yes"
 else
@@ -56689,12 +56923,12 @@ fi
   fi
 
         echo $ac_n "checking for ldap_bind_s""... $ac_c" 1>&6
-echo "configure:56693: checking for ldap_bind_s" >&5
+echo "configure:56932: checking for ldap_bind_s" >&5
 if eval "test \"`echo '$''{'ac_cv_func_ldap_bind_s'+set}'`\" = set"; then
   echo $ac_n "(cached) $ac_c" 1>&6
 else
   cat > conftest.$ac_ext <<EOF
-#line 56698 "configure"
+#line 56937 "configure"
 #include "confdefs.h"
 /* System header to define __stub macros and hopefully few prototypes,
     which can conflict with char ldap_bind_s(); below.  */
@@ -56717,7 +56951,7 @@ ldap_bind_s();
 
 ; return 0; }
 EOF
-if { (eval echo configure:56721: \"$ac_link\") 1>&5; (eval $ac_link) 2>&5; } && test -s conftest${ac_exeext}; then
+if { (eval echo configure:56960: \"$ac_link\") 1>&5; (eval $ac_link) 2>&5; } && test -s conftest${ac_exeext}; then
   rm -rf conftest*
   eval "ac_cv_func_ldap_bind_s=yes"
 else
@@ -56770,7 +57004,7 @@ fi
 php_enable_mbstring=no
 
 echo $ac_n "checking whether to enable multibyte string support""... $ac_c" 1>&6
-echo "configure:56774: checking whether to enable multibyte string support" >&5
+echo "configure:57013: checking whether to enable multibyte string support" >&5
 # Check whether --enable-mbstring or --disable-mbstring was given.
 if test "${enable_mbstring+set}" = set; then
   enableval="$enable_mbstring"
@@ -56814,7 +57048,7 @@ echo "$ac_t""$ext_output" 1>&6
 php_enable_mbregex=yes
 
 echo $ac_n "checking whether to enable multibyte regex support""... $ac_c" 1>&6
-echo "configure:56818: checking whether to enable multibyte regex support" >&5
+echo "configure:57057: checking whether to enable multibyte regex support" >&5
 # Check whether --enable-mbregex or --disable-mbregex was given.
 if test "${enable_mbregex+set}" = set; then
   enableval="$enable_mbregex"
@@ -56837,7 +57071,7 @@ echo "$ac_t""$ext_output" 1>&6
 php_enable_mbregex_backtrack=yes
 
 echo $ac_n "checking whether to check multibyte regex backtrack""... $ac_c" 1>&6
-echo "configure:56841: checking whether to check multibyte regex backtrack" >&5
+echo "configure:57080: checking whether to check multibyte regex backtrack" >&5
 # Check whether --enable-mbregex_backtrack or --disable-mbregex_backtrack was given.
 if test "${enable_mbregex_backtrack+set}" = set; then
   enableval="$enable_mbregex_backtrack"
@@ -56860,7 +57094,7 @@ echo "$ac_t""$ext_output" 1>&6
 php_with_libmbfl=no
 
 echo $ac_n "checking for external libmbfl""... $ac_c" 1>&6
-echo "configure:56864: checking for external libmbfl" >&5
+echo "configure:57103: checking for external libmbfl" >&5
 # Check whether --with-libmbfl or --without-libmbfl was given.
 if test "${with_libmbfl+set}" = set; then
   withval="$with_libmbfl"
@@ -56883,7 +57117,7 @@ echo "$ac_t""$ext_output" 1>&6
 php_with_onig=no
 
 echo $ac_n "checking for external oniguruma""... $ac_c" 1>&6
-echo "configure:56887: checking for external oniguruma" >&5
+echo "configure:57126: checking for external oniguruma" >&5
 # Check whether --with-onig or --without-onig was given.
 if test "${with_onig+set}" = set; then
   withval="$with_onig"
@@ -56924,7 +57158,7 @@ EOF
       fi
 
       echo $ac_n "checking for variable length prototypes and stdarg.h""... $ac_c" 1>&6
-echo "configure:56928: checking for variable length prototypes and stdarg.h" >&5
+echo "configure:57167: checking for variable length prototypes and stdarg.h" >&5
 if eval "test \"`echo '$''{'php_cv_mbstring_stdarg'+set}'`\" = set"; then
   echo $ac_n "(cached) $ac_c" 1>&6
 else
@@ -56942,7 +57176,7 @@ else
         
 else
   cat > conftest.$ac_ext <<EOF
-#line 56946 "configure"
+#line 57185 "configure"
 #include "confdefs.h"
 
 #include <stdarg.h>
@@ -56957,7 +57191,7 @@ int foo(int x, ...) {
 int main() { return foo(10, "", 3.14); }
         
 EOF
-if { (eval echo configure:56961: \"$ac_link\") 1>&5; (eval $ac_link) 2>&5; } && test -s conftest${ac_exeext} && (./conftest; exit) 2>/dev/null
+if { (eval echo configure:57200: \"$ac_link\") 1>&5; (eval $ac_link) 2>&5; } && test -s conftest${ac_exeext} && (./conftest; exit) 2>/dev/null
 then
   php_cv_mbstring_stdarg=yes
 else
@@ -56978,17 +57212,17 @@ echo "$ac_t""$php_cv_mbstring_stdarg" 1>&6
 do
 ac_safe=`echo "$ac_hdr" | sed 'y%./+-%__p_%'`
 echo $ac_n "checking for $ac_hdr""... $ac_c" 1>&6
-echo "configure:56982: checking for $ac_hdr" >&5
+echo "configure:57221: checking for $ac_hdr" >&5
 if eval "test \"`echo '$''{'ac_cv_header_$ac_safe'+set}'`\" = set"; then
   echo $ac_n "(cached) $ac_c" 1>&6
 else
   cat > conftest.$ac_ext <<EOF
-#line 56987 "configure"
+#line 57226 "configure"
 #include "confdefs.h"
 #include <$ac_hdr>
 EOF
 ac_try="$ac_cpp conftest.$ac_ext >/dev/null 2>conftest.out"
-{ (eval echo configure:56992: \"$ac_try\") 1>&5; (eval $ac_try) 2>&5; }
+{ (eval echo configure:57231: \"$ac_try\") 1>&5; (eval $ac_try) 2>&5; }
 ac_err=`grep -v '^ *+' conftest.out | grep -v "^conftest.${ac_ext}\$"`
 if test -z "$ac_err"; then
   rm -rf conftest*
@@ -57015,7 +57249,7 @@ fi
 done
 
       echo $ac_n "checking size of int""... $ac_c" 1>&6
-echo "configure:57019: checking size of int" >&5
+echo "configure:57258: checking size of int" >&5
 if eval "test \"`echo '$''{'ac_cv_sizeof_int'+set}'`\" = set"; then
   echo $ac_n "(cached) $ac_c" 1>&6
 else
@@ -57023,7 +57257,7 @@ else
   ac_cv_sizeof_int=4
 else
   cat > conftest.$ac_ext <<EOF
-#line 57027 "configure"
+#line 57266 "configure"
 #include "confdefs.h"
 #include <stdio.h>
 main()
@@ -57034,7 +57268,7 @@ main()
   exit(0);
 }
 EOF
-if { (eval echo configure:57038: \"$ac_link\") 1>&5; (eval $ac_link) 2>&5; } && test -s conftest${ac_exeext} && (./conftest; exit) 2>/dev/null
+if { (eval echo configure:57277: \"$ac_link\") 1>&5; (eval $ac_link) 2>&5; } && test -s conftest${ac_exeext} && (./conftest; exit) 2>/dev/null
 then
   ac_cv_sizeof_int=`cat conftestval`
 else
@@ -57054,7 +57288,7 @@ EOF
 
 
       echo $ac_n "checking size of short""... $ac_c" 1>&6
-echo "configure:57058: checking size of short" >&5
+echo "configure:57297: checking size of short" >&5
 if eval "test \"`echo '$''{'ac_cv_sizeof_short'+set}'`\" = set"; then
   echo $ac_n "(cached) $ac_c" 1>&6
 else
@@ -57062,7 +57296,7 @@ else
   ac_cv_sizeof_short=2
 else
   cat > conftest.$ac_ext <<EOF
-#line 57066 "configure"
+#line 57305 "configure"
 #include "confdefs.h"
 #include <stdio.h>
 main()
@@ -57073,7 +57307,7 @@ main()
   exit(0);
 }
 EOF
-if { (eval echo configure:57077: \"$ac_link\") 1>&5; (eval $ac_link) 2>&5; } && test -s conftest${ac_exeext} && (./conftest; exit) 2>/dev/null
+if { (eval echo configure:57316: \"$ac_link\") 1>&5; (eval $ac_link) 2>&5; } && test -s conftest${ac_exeext} && (./conftest; exit) 2>/dev/null
 then
   ac_cv_sizeof_short=`cat conftestval`
 else
@@ -57093,7 +57327,7 @@ EOF
 
 
       echo $ac_n "checking size of long""... $ac_c" 1>&6
-echo "configure:57097: checking size of long" >&5
+echo "configure:57336: checking size of long" >&5
 if eval "test \"`echo '$''{'ac_cv_sizeof_long'+set}'`\" = set"; then
   echo $ac_n "(cached) $ac_c" 1>&6
 else
@@ -57101,7 +57335,7 @@ else
   ac_cv_sizeof_long=4
 else
   cat > conftest.$ac_ext <<EOF
-#line 57105 "configure"
+#line 57344 "configure"
 #include "confdefs.h"
 #include <stdio.h>
 main()
@@ -57112,7 +57346,7 @@ main()
   exit(0);
 }
 EOF
-if { (eval echo configure:57116: \"$ac_link\") 1>&5; (eval $ac_link) 2>&5; } && test -s conftest${ac_exeext} && (./conftest; exit) 2>/dev/null
+if { (eval echo configure:57355: \"$ac_link\") 1>&5; (eval $ac_link) 2>&5; } && test -s conftest${ac_exeext} && (./conftest; exit) 2>/dev/null
 then
   ac_cv_sizeof_long=`cat conftestval`
 else
@@ -57132,12 +57366,12 @@ EOF
 
 
       echo $ac_n "checking for working const""... $ac_c" 1>&6
-echo "configure:57136: checking for working const" >&5
+echo "configure:57375: checking for working const" >&5
 if eval "test \"`echo '$''{'ac_cv_c_const'+set}'`\" = set"; then
   echo $ac_n "(cached) $ac_c" 1>&6
 else
   cat > conftest.$ac_ext <<EOF
-#line 57141 "configure"
+#line 57380 "configure"
 #include "confdefs.h"
 
 int main() {
@@ -57186,7 +57420,7 @@ ccp = (char const *const *) p;
 
 ; return 0; }
 EOF
-if { (eval echo configure:57190: \"$ac_compile\") 1>&5; (eval $ac_compile) 2>&5; }; then
+if { (eval echo configure:57429: \"$ac_compile\") 1>&5; (eval $ac_compile) 2>&5; }; then
   rm -rf conftest*
   ac_cv_c_const=yes
 else
@@ -57207,12 +57441,12 @@ EOF
 fi
 
       echo $ac_n "checking whether time.h and sys/time.h may both be included""... $ac_c" 1>&6
-echo "configure:57211: checking whether time.h and sys/time.h may both be included" >&5
+echo "configure:57450: checking whether time.h and sys/time.h may both be included" >&5
 if eval "test \"`echo '$''{'ac_cv_header_time'+set}'`\" = set"; then
   echo $ac_n "(cached) $ac_c" 1>&6
 else
   cat > conftest.$ac_ext <<EOF
-#line 57216 "configure"
+#line 57455 "configure"
 #include "confdefs.h"
 #include <sys/types.h>
 #include <sys/time.h>
@@ -57221,7 +57455,7 @@ int main() {
 struct tm *tp;
 ; return 0; }
 EOF
-if { (eval echo configure:57225: \"$ac_compile\") 1>&5; (eval $ac_compile) 2>&5; }; then
+if { (eval echo configure:57464: \"$ac_compile\") 1>&5; (eval $ac_compile) 2>&5; }; then
   rm -rf conftest*
   ac_cv_header_time=yes
 else
@@ -57244,19 +57478,19 @@ fi
       # The Ultrix 4.2 mips builtin alloca declared by alloca.h only works
 # for constant arguments.  Useless!
 echo $ac_n "checking for working alloca.h""... $ac_c" 1>&6
-echo "configure:57248: checking for working alloca.h" >&5
+echo "configure:57487: checking for working alloca.h" >&5
 if eval "test \"`echo '$''{'ac_cv_header_alloca_h'+set}'`\" = set"; then
   echo $ac_n "(cached) $ac_c" 1>&6
 else
   cat > conftest.$ac_ext <<EOF
-#line 57253 "configure"
+#line 57492 "configure"
 #include "confdefs.h"
 #include <alloca.h>
 int main() {
 char *p = alloca(2 * sizeof(int));
 ; return 0; }
 EOF
-if { (eval echo configure:57260: \"$ac_link\") 1>&5; (eval $ac_link) 2>&5; } && test -s conftest${ac_exeext}; then
+if { (eval echo configure:57499: \"$ac_link\") 1>&5; (eval $ac_link) 2>&5; } && test -s conftest${ac_exeext}; then
   rm -rf conftest*
   ac_cv_header_alloca_h=yes
 else
@@ -57277,12 +57511,12 @@ EOF
 fi
 
 echo $ac_n "checking for alloca""... $ac_c" 1>&6
-echo "configure:57281: checking for alloca" >&5
+echo "configure:57520: checking for alloca" >&5
 if eval "test \"`echo '$''{'ac_cv_func_alloca_works'+set}'`\" = set"; then
   echo $ac_n "(cached) $ac_c" 1>&6
 else
   cat > conftest.$ac_ext <<EOF
-#line 57286 "configure"
+#line 57525 "configure"
 #include "confdefs.h"
 
 #ifdef __GNUC__
@@ -57310,7 +57544,7 @@ int main() {
 char *p = (char *) alloca(1);
 ; return 0; }
 EOF
-if { (eval echo configure:57314: \"$ac_link\") 1>&5; (eval $ac_link) 2>&5; } && test -s conftest${ac_exeext}; then
+if { (eval echo configure:57553: \"$ac_link\") 1>&5; (eval $ac_link) 2>&5; } && test -s conftest${ac_exeext}; then
   rm -rf conftest*
   ac_cv_func_alloca_works=yes
 else
@@ -57342,12 +57576,12 @@ EOF
 
 
 echo $ac_n "checking whether alloca needs Cray hooks""... $ac_c" 1>&6
-echo "configure:57346: checking whether alloca needs Cray hooks" >&5
+echo "configure:57585: checking whether alloca needs Cray hooks" >&5
 if eval "test \"`echo '$''{'ac_cv_os_cray'+set}'`\" = set"; then
   echo $ac_n "(cached) $ac_c" 1>&6
 else
   cat > conftest.$ac_ext <<EOF
-#line 57351 "configure"
+#line 57590 "configure"
 #include "confdefs.h"
 #if defined(CRAY) && ! defined(CRAY2)
 webecray
@@ -57372,12 +57606,12 @@ echo "$ac_t""$ac_cv_os_cray" 1>&6
 if test $ac_cv_os_cray = yes; then
 for ac_func in _getb67 GETB67 getb67; do
   echo $ac_n "checking for $ac_func""... $ac_c" 1>&6
-echo "configure:57376: checking for $ac_func" >&5
+echo "configure:57615: checking for $ac_func" >&5
 if eval "test \"`echo '$''{'ac_cv_func_$ac_func'+set}'`\" = set"; then
   echo $ac_n "(cached) $ac_c" 1>&6
 else
   cat > conftest.$ac_ext <<EOF
-#line 57381 "configure"
+#line 57620 "configure"
 #include "confdefs.h"
 /* System header to define __stub macros and hopefully few prototypes,
     which can conflict with char $ac_func(); below.  */
@@ -57400,7 +57634,7 @@ $ac_func();
 
 ; return 0; }
 EOF
-if { (eval echo configure:57404: \"$ac_link\") 1>&5; (eval $ac_link) 2>&5; } && test -s conftest${ac_exeext}; then
+if { (eval echo configure:57643: \"$ac_link\") 1>&5; (eval $ac_link) 2>&5; } && test -s conftest${ac_exeext}; then
   rm -rf conftest*
   eval "ac_cv_func_$ac_func=yes"
 else
@@ -57427,7 +57661,7 @@ done
 fi
 
 echo $ac_n "checking stack direction for C alloca""... $ac_c" 1>&6
-echo "configure:57431: checking stack direction for C alloca" >&5
+echo "configure:57670: checking stack direction for C alloca" >&5
 if eval "test \"`echo '$''{'ac_cv_c_stack_direction'+set}'`\" = set"; then
   echo $ac_n "(cached) $ac_c" 1>&6
 else
@@ -57435,7 +57669,7 @@ else
   ac_cv_c_stack_direction=0
 else
   cat > conftest.$ac_ext <<EOF
-#line 57439 "configure"
+#line 57678 "configure"
 #include "confdefs.h"
 find_stack_direction ()
 {
@@ -57454,7 +57688,7 @@ main ()
   exit (find_stack_direction() < 0);
 }
 EOF
-if { (eval echo configure:57458: \"$ac_link\") 1>&5; (eval $ac_link) 2>&5; } && test -s conftest${ac_exeext} && (./conftest; exit) 2>/dev/null
+if { (eval echo configure:57697: \"$ac_link\") 1>&5; (eval $ac_link) 2>&5; } && test -s conftest${ac_exeext} && (./conftest; exit) 2>/dev/null
 then
   ac_cv_c_stack_direction=1
 else
@@ -57476,7 +57710,7 @@ EOF
 fi
 
       echo $ac_n "checking for 8-bit clean memcmp""... $ac_c" 1>&6
-echo "configure:57480: checking for 8-bit clean memcmp" >&5
+echo "configure:57719: checking for 8-bit clean memcmp" >&5
 if eval "test \"`echo '$''{'ac_cv_func_memcmp_clean'+set}'`\" = set"; then
   echo $ac_n "(cached) $ac_c" 1>&6
 else
@@ -57484,7 +57718,7 @@ else
   ac_cv_func_memcmp_clean=no
 else
   cat > conftest.$ac_ext <<EOF
-#line 57488 "configure"
+#line 57727 "configure"
 #include "confdefs.h"
 
 main()
@@ -57494,7 +57728,7 @@ main()
 }
 
 EOF
-if { (eval echo configure:57498: \"$ac_link\") 1>&5; (eval $ac_link) 2>&5; } && test -s conftest${ac_exeext} && (./conftest; exit) 2>/dev/null
+if { (eval echo configure:57737: \"$ac_link\") 1>&5; (eval $ac_link) 2>&5; } && test -s conftest${ac_exeext} && (./conftest; exit) 2>/dev/null
 then
   ac_cv_func_memcmp_clean=yes
 else
@@ -57513,17 +57747,17 @@ test $ac_cv_func_memcmp_clean = no && LIBOBJS="$LIBOBJS memcmp.${ac_objext}"
 
       ac_safe=`echo "stdarg.h" | sed 'y%./+-%__p_%'`
 echo $ac_n "checking for stdarg.h""... $ac_c" 1>&6
-echo "configure:57517: checking for stdarg.h" >&5
+echo "configure:57756: checking for stdarg.h" >&5
 if eval "test \"`echo '$''{'ac_cv_header_$ac_safe'+set}'`\" = set"; then
   echo $ac_n "(cached) $ac_c" 1>&6
 else
   cat > conftest.$ac_ext <<EOF
-#line 57522 "configure"
+#line 57761 "configure"
 #include "confdefs.h"
 #include <stdarg.h>
 EOF
 ac_try="$ac_cpp conftest.$ac_ext >/dev/null 2>conftest.out"
-{ (eval echo configure:57527: \"$ac_try\") 1>&5; (eval $ac_try) 2>&5; }
+{ (eval echo configure:57766: \"$ac_try\") 1>&5; (eval $ac_try) 2>&5; }
 ac_err=`grep -v '^ *+' conftest.out | grep -v "^conftest.${ac_ext}\$"`
 if test -z "$ac_err"; then
   rm -rf conftest*
@@ -57753,7 +57987,7 @@ EOF
   done
 
   echo $ac_n "checking for onig_init in -lonig""... $ac_c" 1>&6
-echo "configure:57757: checking for onig_init in -lonig" >&5
+echo "configure:57996: checking for onig_init in -lonig" >&5
 ac_lib_var=`echo onig'_'onig_init | sed 'y%./+-%__p_%'`
 if eval "test \"`echo '$''{'ac_cv_lib_$ac_lib_var'+set}'`\" = set"; then
   echo $ac_n "(cached) $ac_c" 1>&6
@@ -57761,7 +57995,7 @@ else
   ac_save_LIBS="$LIBS"
 LIBS="-lonig  $LIBS"
 cat > conftest.$ac_ext <<EOF
-#line 57765 "configure"
+#line 58004 "configure"
 #include "confdefs.h"
 /* Override any gcc2 internal prototype to avoid an error.  */
 /* We use char because int might match the return type of a gcc2
@@ -57772,7 +58006,7 @@ int main() {
 onig_init()
 ; return 0; }
 EOF
-if { (eval echo configure:57776: \"$ac_link\") 1>&5; (eval $ac_link) 2>&5; } && test -s conftest${ac_exeext}; then
+if { (eval echo configure:58015: \"$ac_link\") 1>&5; (eval $ac_link) 2>&5; } && test -s conftest${ac_exeext}; then
   rm -rf conftest*
   eval "ac_cv_lib_$ac_lib_var=yes"
 else
@@ -57998,9 +58232,9 @@ fi
   done
 
       echo $ac_n "checking if oniguruma has an invalid entry for KOI8 encoding""... $ac_c" 1>&6
-echo "configure:58002: checking if oniguruma has an invalid entry for KOI8 encoding" >&5
+echo "configure:58241: checking if oniguruma has an invalid entry for KOI8 encoding" >&5
       cat > conftest.$ac_ext <<EOF
-#line 58004 "configure"
+#line 58243 "configure"
 #include "confdefs.h"
 
 #include <oniguruma.h>
@@ -58011,7 +58245,7 @@ return (int)(ONIG_ENCODING_KOI8 + 1);
       
 ; return 0; }
 EOF
-if { (eval echo configure:58015: \"$ac_link\") 1>&5; (eval $ac_link) 2>&5; } && test -s conftest${ac_exeext}; then
+if { (eval echo configure:58254: \"$ac_link\") 1>&5; (eval $ac_link) 2>&5; } && test -s conftest${ac_exeext}; then
   rm -rf conftest*
   
         echo "$ac_t""no" 1>&6
@@ -58309,7 +58543,7 @@ EOF
   done
 
   echo $ac_n "checking for mbfl_buffer_converter_new in -lmbfl""... $ac_c" 1>&6
-echo "configure:58313: checking for mbfl_buffer_converter_new in -lmbfl" >&5
+echo "configure:58552: checking for mbfl_buffer_converter_new in -lmbfl" >&5
 ac_lib_var=`echo mbfl'_'mbfl_buffer_converter_new | sed 'y%./+-%__p_%'`
 if eval "test \"`echo '$''{'ac_cv_lib_$ac_lib_var'+set}'`\" = set"; then
   echo $ac_n "(cached) $ac_c" 1>&6
@@ -58317,7 +58551,7 @@ else
   ac_save_LIBS="$LIBS"
 LIBS="-lmbfl  $LIBS"
 cat > conftest.$ac_ext <<EOF
-#line 58321 "configure"
+#line 58560 "configure"
 #include "confdefs.h"
 /* Override any gcc2 internal prototype to avoid an error.  */
 /* We use char because int might match the return type of a gcc2
@@ -58328,7 +58562,7 @@ int main() {
 mbfl_buffer_converter_new()
 ; return 0; }
 EOF
-if { (eval echo configure:58332: \"$ac_link\") 1>&5; (eval $ac_link) 2>&5; } && test -s conftest${ac_exeext}; then
+if { (eval echo configure:58571: \"$ac_link\") 1>&5; (eval $ac_link) 2>&5; } && test -s conftest${ac_exeext}; then
   rm -rf conftest*
   eval "ac_cv_lib_$ac_lib_var=yes"
 else
@@ -58720,7 +58954,7 @@ EOF
       ac_obj=$1
       IFS=$old_IFS
       
-      PHP_CLI_OBJS="$PHP_CLI_OBJS $ac_bdir$ac_obj.lo"
+      PHP_GLOBAL_OBJS="$PHP_GLOBAL_OBJS $ac_bdir$ac_obj.lo"
 
       case $ac_src in
         *.c) ac_comp="$b_c_pre $ac_extra $ac_inc $b_c_meta -c $ac_srcdir$ac_src -o $ac_bdir$ac_obj.$b_lo $b_c_post" ;;
@@ -58965,7 +59199,7 @@ fi
 php_with_mcrypt=no
 
 echo $ac_n "checking for mcrypt support""... $ac_c" 1>&6
-echo "configure:58969: checking for mcrypt support" >&5
+echo "configure:59208: checking for mcrypt support" >&5
 # Check whether --with-mcrypt or --without-mcrypt was given.
 if test "${with_mcrypt+set}" = set; then
   withval="$with_mcrypt"
@@ -59018,9 +59252,9 @@ if test "$PHP_MCRYPT" != "no"; then
   old_CPPFLAGS=$CPPFLAGS
   CPPFLAGS=-I$MCRYPT_DIR/include
   echo $ac_n "checking for libmcrypt version""... $ac_c" 1>&6
-echo "configure:59022: checking for libmcrypt version" >&5
+echo "configure:59261: checking for libmcrypt version" >&5
   cat > conftest.$ac_ext <<EOF
-#line 59024 "configure"
+#line 59263 "configure"
 #include "confdefs.h"
 
 #include <mcrypt.h>
@@ -59144,7 +59378,7 @@ rm -f conftest*
   done
 
   echo $ac_n "checking for mcrypt_module_open in -lmcrypt""... $ac_c" 1>&6
-echo "configure:59148: checking for mcrypt_module_open in -lmcrypt" >&5
+echo "configure:59387: checking for mcrypt_module_open in -lmcrypt" >&5
 ac_lib_var=`echo mcrypt'_'mcrypt_module_open | sed 'y%./+-%__p_%'`
 if eval "test \"`echo '$''{'ac_cv_lib_$ac_lib_var'+set}'`\" = set"; then
   echo $ac_n "(cached) $ac_c" 1>&6
@@ -59152,7 +59386,7 @@ else
   ac_save_LIBS="$LIBS"
 LIBS="-lmcrypt  $LIBS"
 cat > conftest.$ac_ext <<EOF
-#line 59156 "configure"
+#line 59395 "configure"
 #include "confdefs.h"
 /* Override any gcc2 internal prototype to avoid an error.  */
 /* We use char because int might match the return type of a gcc2
@@ -59163,7 +59397,7 @@ int main() {
 mcrypt_module_open()
 ; return 0; }
 EOF
-if { (eval echo configure:59167: \"$ac_link\") 1>&5; (eval $ac_link) 2>&5; } && test -s conftest${ac_exeext}; then
+if { (eval echo configure:59406: \"$ac_link\") 1>&5; (eval $ac_link) 2>&5; } && test -s conftest${ac_exeext}; then
   rm -rf conftest*
   eval "ac_cv_lib_$ac_lib_var=yes"
 else
@@ -59316,7 +59550,7 @@ else
   done
 
   echo $ac_n "checking for mcrypt_module_open in -lmcrypt""... $ac_c" 1>&6
-echo "configure:59320: checking for mcrypt_module_open in -lmcrypt" >&5
+echo "configure:59559: checking for mcrypt_module_open in -lmcrypt" >&5
 ac_lib_var=`echo mcrypt'_'mcrypt_module_open | sed 'y%./+-%__p_%'`
 if eval "test \"`echo '$''{'ac_cv_lib_$ac_lib_var'+set}'`\" = set"; then
   echo $ac_n "(cached) $ac_c" 1>&6
@@ -59324,7 +59558,7 @@ else
   ac_save_LIBS="$LIBS"
 LIBS="-lmcrypt  $LIBS"
 cat > conftest.$ac_ext <<EOF
-#line 59328 "configure"
+#line 59567 "configure"
 #include "confdefs.h"
 /* Override any gcc2 internal prototype to avoid an error.  */
 /* We use char because int might match the return type of a gcc2
@@ -59335,7 +59569,7 @@ int main() {
 mcrypt_module_open()
 ; return 0; }
 EOF
-if { (eval echo configure:59339: \"$ac_link\") 1>&5; (eval $ac_link) 2>&5; } && test -s conftest${ac_exeext}; then
+if { (eval echo configure:59578: \"$ac_link\") 1>&5; (eval $ac_link) 2>&5; } && test -s conftest${ac_exeext}; then
   rm -rf conftest*
   eval "ac_cv_lib_$ac_lib_var=yes"
 else
@@ -59768,7 +60002,7 @@ EOF
       ac_obj=$1
       IFS=$old_IFS
       
-      PHP_CLI_OBJS="$PHP_CLI_OBJS $ac_bdir$ac_obj.lo"
+      PHP_GLOBAL_OBJS="$PHP_GLOBAL_OBJS $ac_bdir$ac_obj.lo"
 
       case $ac_src in
         *.c) ac_comp="$b_c_pre $ac_extra $ac_inc $b_c_meta -c $ac_srcdir$ac_src -o $ac_bdir$ac_obj.$b_lo $b_c_post" ;;
@@ -59808,7 +60042,7 @@ fi
 php_with_mssql=no
 
 echo $ac_n "checking for MSSQL support via FreeTDS""... $ac_c" 1>&6
-echo "configure:59812: checking for MSSQL support via FreeTDS" >&5
+echo "configure:60051: checking for MSSQL support via FreeTDS" >&5
 # Check whether --with-mssql or --without-mssql was given.
 if test "${with_mssql+set}" = set; then
   withval="$with_mssql"
@@ -60272,7 +60506,7 @@ EOF
       ac_obj=$1
       IFS=$old_IFS
       
-      PHP_CLI_OBJS="$PHP_CLI_OBJS $ac_bdir$ac_obj.lo"
+      PHP_GLOBAL_OBJS="$PHP_GLOBAL_OBJS $ac_bdir$ac_obj.lo"
 
       case $ac_src in
         *.c) ac_comp="$b_c_pre $ac_extra $ac_inc $b_c_meta -c $ac_srcdir$ac_src -o $ac_bdir$ac_obj.$b_lo $b_c_post" ;;
@@ -60306,7 +60540,7 @@ EOF
   fi
 
   echo $ac_n "checking for dnet_addr in -ldnet_stub""... $ac_c" 1>&6
-echo "configure:60310: checking for dnet_addr in -ldnet_stub" >&5
+echo "configure:60549: checking for dnet_addr in -ldnet_stub" >&5
 ac_lib_var=`echo dnet_stub'_'dnet_addr | sed 'y%./+-%__p_%'`
 if eval "test \"`echo '$''{'ac_cv_lib_$ac_lib_var'+set}'`\" = set"; then
   echo $ac_n "(cached) $ac_c" 1>&6
@@ -60314,7 +60548,7 @@ else
   ac_save_LIBS="$LIBS"
 LIBS="-ldnet_stub  $LIBS"
 cat > conftest.$ac_ext <<EOF
-#line 60318 "configure"
+#line 60557 "configure"
 #include "confdefs.h"
 /* Override any gcc2 internal prototype to avoid an error.  */
 /* We use char because int might match the return type of a gcc2
@@ -60325,7 +60559,7 @@ int main() {
 dnet_addr()
 ; return 0; }
 EOF
-if { (eval echo configure:60329: \"$ac_link\") 1>&5; (eval $ac_link) 2>&5; } && test -s conftest${ac_exeext}; then
+if { (eval echo configure:60568: \"$ac_link\") 1>&5; (eval $ac_link) 2>&5; } && test -s conftest${ac_exeext}; then
   rm -rf conftest*
   eval "ac_cv_lib_$ac_lib_var=yes"
 else
@@ -60469,7 +60703,7 @@ fi
 php_with_mysql=no
 
 echo $ac_n "checking for MySQL support""... $ac_c" 1>&6
-echo "configure:60473: checking for MySQL support" >&5
+echo "configure:60712: checking for MySQL support" >&5
 # Check whether --with-mysql or --without-mysql was given.
 if test "${with_mysql+set}" = set; then
   withval="$with_mysql"
@@ -60513,7 +60747,7 @@ echo "$ac_t""$ext_output" 1>&6
 php_with_mysql_sock=no
 
 echo $ac_n "checking for specified location of the MySQL UNIX socket""... $ac_c" 1>&6
-echo "configure:60517: checking for specified location of the MySQL UNIX socket" >&5
+echo "configure:60756: checking for specified location of the MySQL UNIX socket" >&5
 # Check whether --with-mysql-sock or --without-mysql-sock was given.
 if test "${with_mysql_sock+set}" = set; then
   withval="$with_mysql_sock"
@@ -60537,7 +60771,7 @@ if test -z "$PHP_ZLIB_DIR"; then
 php_with_zlib_dir=no
 
 echo $ac_n "checking for the location of libz""... $ac_c" 1>&6
-echo "configure:60541: checking for the location of libz" >&5
+echo "configure:60780: checking for the location of libz" >&5
 # Check whether --with-zlib-dir or --without-zlib-dir was given.
 if test "${with_zlib_dir+set}" = set; then
   withval="$with_zlib_dir"
@@ -60734,7 +60968,7 @@ Note that the MySQL client library is not bundled anymore!" 1>&2; exit 1; }
   done
 
   echo $ac_n "checking for mysql_close in -l$MYSQL_LIBNAME""... $ac_c" 1>&6
-echo "configure:60738: checking for mysql_close in -l$MYSQL_LIBNAME" >&5
+echo "configure:60977: checking for mysql_close in -l$MYSQL_LIBNAME" >&5
 ac_lib_var=`echo $MYSQL_LIBNAME'_'mysql_close | sed 'y%./+-%__p_%'`
 if eval "test \"`echo '$''{'ac_cv_lib_$ac_lib_var'+set}'`\" = set"; then
   echo $ac_n "(cached) $ac_c" 1>&6
@@ -60742,7 +60976,7 @@ else
   ac_save_LIBS="$LIBS"
 LIBS="-l$MYSQL_LIBNAME  $LIBS"
 cat > conftest.$ac_ext <<EOF
-#line 60746 "configure"
+#line 60985 "configure"
 #include "confdefs.h"
 /* Override any gcc2 internal prototype to avoid an error.  */
 /* We use char because int might match the return type of a gcc2
@@ -60753,7 +60987,7 @@ int main() {
 mysql_close()
 ; return 0; }
 EOF
-if { (eval echo configure:60757: \"$ac_link\") 1>&5; (eval $ac_link) 2>&5; } && test -s conftest${ac_exeext}; then
+if { (eval echo configure:60996: \"$ac_link\") 1>&5; (eval $ac_link) 2>&5; } && test -s conftest${ac_exeext}; then
   rm -rf conftest*
   eval "ac_cv_lib_$ac_lib_var=yes"
 else
@@ -60976,7 +61210,7 @@ else
   done
 
   echo $ac_n "checking for mysql_error in -l$MYSQL_LIBNAME""... $ac_c" 1>&6
-echo "configure:60980: checking for mysql_error in -l$MYSQL_LIBNAME" >&5
+echo "configure:61219: checking for mysql_error in -l$MYSQL_LIBNAME" >&5
 ac_lib_var=`echo $MYSQL_LIBNAME'_'mysql_error | sed 'y%./+-%__p_%'`
 if eval "test \"`echo '$''{'ac_cv_lib_$ac_lib_var'+set}'`\" = set"; then
   echo $ac_n "(cached) $ac_c" 1>&6
@@ -60984,7 +61218,7 @@ else
   ac_save_LIBS="$LIBS"
 LIBS="-l$MYSQL_LIBNAME  $LIBS"
 cat > conftest.$ac_ext <<EOF
-#line 60988 "configure"
+#line 61227 "configure"
 #include "confdefs.h"
 /* Override any gcc2 internal prototype to avoid an error.  */
 /* We use char because int might match the return type of a gcc2
@@ -60995,7 +61229,7 @@ int main() {
 mysql_error()
 ; return 0; }
 EOF
-if { (eval echo configure:60999: \"$ac_link\") 1>&5; (eval $ac_link) 2>&5; } && test -s conftest${ac_exeext}; then
+if { (eval echo configure:61238: \"$ac_link\") 1>&5; (eval $ac_link) 2>&5; } && test -s conftest${ac_exeext}; then
   rm -rf conftest*
   eval "ac_cv_lib_$ac_lib_var=yes"
 else
@@ -61150,7 +61384,7 @@ fi
   done
 
   echo $ac_n "checking for mysql_errno in -l$MYSQL_LIBNAME""... $ac_c" 1>&6
-echo "configure:61154: checking for mysql_errno in -l$MYSQL_LIBNAME" >&5
+echo "configure:61393: checking for mysql_errno in -l$MYSQL_LIBNAME" >&5
 ac_lib_var=`echo $MYSQL_LIBNAME'_'mysql_errno | sed 'y%./+-%__p_%'`
 if eval "test \"`echo '$''{'ac_cv_lib_$ac_lib_var'+set}'`\" = set"; then
   echo $ac_n "(cached) $ac_c" 1>&6
@@ -61158,7 +61392,7 @@ else
   ac_save_LIBS="$LIBS"
 LIBS="-l$MYSQL_LIBNAME  $LIBS"
 cat > conftest.$ac_ext <<EOF
-#line 61162 "configure"
+#line 61401 "configure"
 #include "confdefs.h"
 /* Override any gcc2 internal prototype to avoid an error.  */
 /* We use char because int might match the return type of a gcc2
@@ -61169,7 +61403,7 @@ int main() {
 mysql_errno()
 ; return 0; }
 EOF
-if { (eval echo configure:61173: \"$ac_link\") 1>&5; (eval $ac_link) 2>&5; } && test -s conftest${ac_exeext}; then
+if { (eval echo configure:61412: \"$ac_link\") 1>&5; (eval $ac_link) 2>&5; } && test -s conftest${ac_exeext}; then
   rm -rf conftest*
   eval "ac_cv_lib_$ac_lib_var=yes"
 else
@@ -61363,7 +61597,7 @@ fi
 
 if test "$PHP_MYSQL" != "no"; then
   echo $ac_n "checking for MySQL UNIX socket location""... $ac_c" 1>&6
-echo "configure:61367: checking for MySQL UNIX socket location" >&5
+echo "configure:61606: checking for MySQL UNIX socket location" >&5
   if test "$PHP_MYSQL_SOCK" != "no" && test "$PHP_MYSQL_SOCK" != "yes"; then
     MYSQL_SOCK=$PHP_MYSQL_SOCK
     cat >> confdefs.h <<EOF
@@ -61667,7 +61901,7 @@ EOF
       ac_obj=$1
       IFS=$old_IFS
       
-      PHP_CLI_OBJS="$PHP_CLI_OBJS $ac_bdir$ac_obj.lo"
+      PHP_GLOBAL_OBJS="$PHP_GLOBAL_OBJS $ac_bdir$ac_obj.lo"
 
       case $ac_src in
         *.c) ac_comp="$b_c_pre $ac_extra $ac_inc $b_c_meta -c $ac_srcdir$ac_src -o $ac_bdir$ac_obj.$b_lo $b_c_post" ;;
@@ -61736,7 +61970,7 @@ fi
 php_with_mysqli=no
 
 echo $ac_n "checking for MySQLi support""... $ac_c" 1>&6
-echo "configure:61740: checking for MySQLi support" >&5
+echo "configure:61979: checking for MySQLi support" >&5
 # Check whether --with-mysqli or --without-mysqli was given.
 if test "${with_mysqli+set}" = set; then
   withval="$with_mysqli"
@@ -61780,7 +62014,7 @@ echo "$ac_t""$ext_output" 1>&6
 php_enable_embedded_mysqli=no
 
 echo $ac_n "checking whether to enable embedded MySQLi support""... $ac_c" 1>&6
-echo "configure:61784: checking whether to enable embedded MySQLi support" >&5
+echo "configure:62023: checking whether to enable embedded MySQLi support" >&5
 # Check whether --enable-embedded_mysqli or --disable-embedded_mysqli was given.
 if test "${enable_embedded_mysqli+set}" = set; then
   enableval="$enable_embedded_mysqli"
@@ -61931,7 +62165,7 @@ EOF
   done
 
   echo $ac_n "checking for mysql_set_server_option in -l$MYSQL_LIB_NAME""... $ac_c" 1>&6
-echo "configure:61935: checking for mysql_set_server_option in -l$MYSQL_LIB_NAME" >&5
+echo "configure:62174: checking for mysql_set_server_option in -l$MYSQL_LIB_NAME" >&5
 ac_lib_var=`echo $MYSQL_LIB_NAME'_'mysql_set_server_option | sed 'y%./+-%__p_%'`
 if eval "test \"`echo '$''{'ac_cv_lib_$ac_lib_var'+set}'`\" = set"; then
   echo $ac_n "(cached) $ac_c" 1>&6
@@ -61939,7 +62173,7 @@ else
   ac_save_LIBS="$LIBS"
 LIBS="-l$MYSQL_LIB_NAME  $LIBS"
 cat > conftest.$ac_ext <<EOF
-#line 61943 "configure"
+#line 62182 "configure"
 #include "confdefs.h"
 /* Override any gcc2 internal prototype to avoid an error.  */
 /* We use char because int might match the return type of a gcc2
@@ -61950,7 +62184,7 @@ int main() {
 mysql_set_server_option()
 ; return 0; }
 EOF
-if { (eval echo configure:61954: \"$ac_link\") 1>&5; (eval $ac_link) 2>&5; } && test -s conftest${ac_exeext}; then
+if { (eval echo configure:62193: \"$ac_link\") 1>&5; (eval $ac_link) 2>&5; } && test -s conftest${ac_exeext}; then
   rm -rf conftest*
   eval "ac_cv_lib_$ac_lib_var=yes"
 else
@@ -62198,7 +62432,7 @@ EOF
   done
 
   echo $ac_n "checking for mysql_set_character_set in -l$MYSQL_LIB_NAME""... $ac_c" 1>&6
-echo "configure:62202: checking for mysql_set_character_set in -l$MYSQL_LIB_NAME" >&5
+echo "configure:62441: checking for mysql_set_character_set in -l$MYSQL_LIB_NAME" >&5
 ac_lib_var=`echo $MYSQL_LIB_NAME'_'mysql_set_character_set | sed 'y%./+-%__p_%'`
 if eval "test \"`echo '$''{'ac_cv_lib_$ac_lib_var'+set}'`\" = set"; then
   echo $ac_n "(cached) $ac_c" 1>&6
@@ -62206,7 +62440,7 @@ else
   ac_save_LIBS="$LIBS"
 LIBS="-l$MYSQL_LIB_NAME  $LIBS"
 cat > conftest.$ac_ext <<EOF
-#line 62210 "configure"
+#line 62449 "configure"
 #include "confdefs.h"
 /* Override any gcc2 internal prototype to avoid an error.  */
 /* We use char because int might match the return type of a gcc2
@@ -62217,7 +62451,7 @@ int main() {
 mysql_set_character_set()
 ; return 0; }
 EOF
-if { (eval echo configure:62221: \"$ac_link\") 1>&5; (eval $ac_link) 2>&5; } && test -s conftest${ac_exeext}; then
+if { (eval echo configure:62460: \"$ac_link\") 1>&5; (eval $ac_link) 2>&5; } && test -s conftest${ac_exeext}; then
   rm -rf conftest*
   eval "ac_cv_lib_$ac_lib_var=yes"
 else
@@ -62361,7 +62595,7 @@ fi
   done
 
   echo $ac_n "checking for mysql_stmt_next_result in -l$MYSQL_LIB_NAME""... $ac_c" 1>&6
-echo "configure:62365: checking for mysql_stmt_next_result in -l$MYSQL_LIB_NAME" >&5
+echo "configure:62604: checking for mysql_stmt_next_result in -l$MYSQL_LIB_NAME" >&5
 ac_lib_var=`echo $MYSQL_LIB_NAME'_'mysql_stmt_next_result | sed 'y%./+-%__p_%'`
 if eval "test \"`echo '$''{'ac_cv_lib_$ac_lib_var'+set}'`\" = set"; then
   echo $ac_n "(cached) $ac_c" 1>&6
@@ -62369,7 +62603,7 @@ else
   ac_save_LIBS="$LIBS"
 LIBS="-l$MYSQL_LIB_NAME  $LIBS"
 cat > conftest.$ac_ext <<EOF
-#line 62373 "configure"
+#line 62612 "configure"
 #include "confdefs.h"
 /* Override any gcc2 internal prototype to avoid an error.  */
 /* We use char because int might match the return type of a gcc2
@@ -62380,7 +62614,7 @@ int main() {
 mysql_stmt_next_result()
 ; return 0; }
 EOF
-if { (eval echo configure:62384: \"$ac_link\") 1>&5; (eval $ac_link) 2>&5; } && test -s conftest${ac_exeext}; then
+if { (eval echo configure:62623: \"$ac_link\") 1>&5; (eval $ac_link) 2>&5; } && test -s conftest${ac_exeext}; then
   rm -rf conftest*
   eval "ac_cv_lib_$ac_lib_var=yes"
 else
@@ -62681,7 +62915,7 @@ EOF
       ac_obj=$1
       IFS=$old_IFS
       
-      PHP_CLI_OBJS="$PHP_CLI_OBJS $ac_bdir$ac_obj.lo"
+      PHP_GLOBAL_OBJS="$PHP_GLOBAL_OBJS $ac_bdir$ac_obj.lo"
 
       case $ac_src in
         *.c) ac_comp="$b_c_pre $ac_extra $ac_inc $b_c_meta -c $ac_srcdir$ac_src -o $ac_bdir$ac_obj.$b_lo $b_c_post" ;;
@@ -62790,7 +63024,7 @@ fi
 php_with_oci8=no
 
 echo $ac_n "checking for Oracle Database OCI8 support""... $ac_c" 1>&6
-echo "configure:62794: checking for Oracle Database OCI8 support" >&5
+echo "configure:63033: checking for Oracle Database OCI8 support" >&5
 # Check whether --with-oci8 or --without-oci8 was given.
 if test "${with_oci8+set}" = set; then
   withval="$with_oci8"
@@ -62838,7 +63072,7 @@ if test "$PHP_OCI8" != "no"; then
 
   
   echo $ac_n "checking PHP version""... $ac_c" 1>&6
-echo "configure:62842: checking PHP version" >&5
+echo "configure:63081: checking PHP version" >&5
 
   tmp_version=$PHP_VERSION
   if test -z "$tmp_version"; then
@@ -62870,7 +63104,7 @@ echo "configure:62842: checking PHP version" >&5
 
   
   echo $ac_n "checking size of long int""... $ac_c" 1>&6
-echo "configure:62874: checking size of long int" >&5
+echo "configure:63113: checking size of long int" >&5
 if eval "test \"`echo '$''{'ac_cv_sizeof_long_int'+set}'`\" = set"; then
   echo $ac_n "(cached) $ac_c" 1>&6
 else
@@ -62878,7 +63112,7 @@ else
   ac_cv_sizeof_long_int=4
 else
   cat > conftest.$ac_ext <<EOF
-#line 62882 "configure"
+#line 63121 "configure"
 #include "confdefs.h"
 #include <stdio.h>
 main()
@@ -62889,7 +63123,7 @@ main()
   exit(0);
 }
 EOF
-if { (eval echo configure:62893: \"$ac_link\") 1>&5; (eval $ac_link) 2>&5; } && test -s conftest${ac_exeext} && (./conftest; exit) 2>/dev/null
+if { (eval echo configure:63132: \"$ac_link\") 1>&5; (eval $ac_link) 2>&5; } && test -s conftest${ac_exeext} && (./conftest; exit) 2>/dev/null
 then
   ac_cv_sizeof_long_int=`cat conftestval`
 else
@@ -62909,7 +63143,7 @@ EOF
 
 
   echo $ac_n "checking checking if we're on a 64-bit platform""... $ac_c" 1>&6
-echo "configure:62913: checking checking if we're on a 64-bit platform" >&5
+echo "configure:63152: checking checking if we're on a 64-bit platform" >&5
   if test "$ac_cv_sizeof_long_int" = "4"; then
     echo "$ac_t""no" 1>&6
     PHP_OCI8_OH_LIBDIR=lib32 
@@ -62953,7 +63187,7 @@ echo "configure:62913: checking checking if we're on a 64-bit platform" >&5
   if test "$PHP_OCI8_INSTANT_CLIENT" = "no"; then
             
     echo $ac_n "checking Oracle ORACLE_HOME install directory""... $ac_c" 1>&6
-echo "configure:62957: checking Oracle ORACLE_HOME install directory" >&5
+echo "configure:63196: checking Oracle ORACLE_HOME install directory" >&5
 
     if test "$PHP_OCI8" = "yes"; then
       OCI8_DIR=$ORACLE_HOME
@@ -62964,7 +63198,7 @@ echo "configure:62957: checking Oracle ORACLE_HOME install directory" >&5
 
     
   echo $ac_n "checking ORACLE_HOME library validity""... $ac_c" 1>&6
-echo "configure:62968: checking ORACLE_HOME library validity" >&5
+echo "configure:63207: checking ORACLE_HOME library validity" >&5
   if test ! -d "$OCI8_DIR"; then
     { echo "configure: error: ${OCI8_DIR} is not a directory" 1>&2; exit 1; }
   fi
@@ -63305,7 +63539,7 @@ echo "configure:62968: checking ORACLE_HOME library validity" >&5
 
     
   echo $ac_n "checking Oracle library version compatibility""... $ac_c" 1>&6
-echo "configure:63309: checking Oracle library version compatibility" >&5
+echo "configure:63548: checking Oracle library version compatibility" >&5
   OCI8_LCS_BASE=$OCI8_DIR/$OCI8_LIB_DIR/libclntsh.$SHLIB_SUFFIX_NAME
   OCI8_LCS=`ls $OCI8_LCS_BASE.*.1 2> /dev/null | $PHP_OCI8_TAIL1`  # Oracle 10g, 11g etc
   if test -s "$OCI8_DIR/orainst/unix.rgs"; then
@@ -63435,7 +63669,7 @@ echo "configure:63309: checking Oracle library version compatibility" >&5
   done
 
   echo $ac_n "checking for OCIEnvNlsCreate in -lclntsh""... $ac_c" 1>&6
-echo "configure:63439: checking for OCIEnvNlsCreate in -lclntsh" >&5
+echo "configure:63678: checking for OCIEnvNlsCreate in -lclntsh" >&5
 ac_lib_var=`echo clntsh'_'OCIEnvNlsCreate | sed 'y%./+-%__p_%'`
 if eval "test \"`echo '$''{'ac_cv_lib_$ac_lib_var'+set}'`\" = set"; then
   echo $ac_n "(cached) $ac_c" 1>&6
@@ -63443,7 +63677,7 @@ else
   ac_save_LIBS="$LIBS"
 LIBS="-lclntsh  $LIBS"
 cat > conftest.$ac_ext <<EOF
-#line 63447 "configure"
+#line 63686 "configure"
 #include "confdefs.h"
 /* Override any gcc2 internal prototype to avoid an error.  */
 /* We use char because int might match the return type of a gcc2
@@ -63454,7 +63688,7 @@ int main() {
 OCIEnvNlsCreate()
 ; return 0; }
 EOF
-if { (eval echo configure:63458: \"$ac_link\") 1>&5; (eval $ac_link) 2>&5; } && test -s conftest${ac_exeext}; then
+if { (eval echo configure:63697: \"$ac_link\") 1>&5; (eval $ac_link) 2>&5; } && test -s conftest${ac_exeext}; then
   rm -rf conftest*
   eval "ac_cv_lib_$ac_lib_var=yes"
 else
@@ -63818,7 +64052,7 @@ EOF
       ac_obj=$1
       IFS=$old_IFS
       
-      PHP_CLI_OBJS="$PHP_CLI_OBJS $ac_bdir$ac_obj.lo"
+      PHP_GLOBAL_OBJS="$PHP_GLOBAL_OBJS $ac_bdir$ac_obj.lo"
 
       case $ac_src in
         *.c) ac_comp="$b_c_pre $ac_extra $ac_inc $b_c_meta -c $ac_srcdir$ac_src -o $ac_bdir$ac_obj.$b_lo $b_c_post" ;;
@@ -63887,7 +64121,7 @@ EOF
   else
             
     echo $ac_n "checking Oracle Instant Client directory""... $ac_c" 1>&6
-echo "configure:63891: checking Oracle Instant Client directory" >&5
+echo "configure:64130: checking Oracle Instant Client directory" >&5
 
     if test "$PHP_OCI8_INSTANT_CLIENT" = "yes"; then
                                     PHP_OCI8_INSTANT_CLIENT=`ls -d /usr/lib/oracle/*/client${PHP_OCI8_IC_LIBDIR_SUFFIX}/lib/libclntsh.* 2> /dev/null | $PHP_OCI8_TAIL1 | $PHP_OCI8_SED -e 's#/libclntsh[^/]*##'`
@@ -63900,7 +64134,7 @@ echo "configure:63891: checking Oracle Instant Client directory" >&5
     OCI8_DIR=$PHP_OCI8_INSTANT_CLIENT
 
     echo $ac_n "checking Oracle Instant Client SDK header directory""... $ac_c" 1>&6
-echo "configure:63904: checking Oracle Instant Client SDK header directory" >&5
+echo "configure:64143: checking Oracle Instant Client SDK header directory" >&5
 
         OCISDKRPMINC=`echo "$PHP_OCI8_INSTANT_CLIENT" | $PHP_OCI8_SED -e 's!^/usr/lib/oracle/\(.*\)/client\('${PHP_OCI8_IC_LIBDIR_SUFFIX}'\)*/lib/*$!/usr/include/oracle/\1/client\2!'`
 
@@ -64109,7 +64343,7 @@ echo "configure:63904: checking Oracle Instant Client SDK header directory" >&5
 
     
   echo $ac_n "checking Oracle Instant Client library version compatibility""... $ac_c" 1>&6
-echo "configure:64113: checking Oracle Instant Client library version compatibility" >&5
+echo "configure:64352: checking Oracle Instant Client library version compatibility" >&5
   OCI8_LCS_BASE=$PHP_OCI8_INSTANT_CLIENT/libclntsh.$SHLIB_SUFFIX_NAME
   OCI8_LCS=`ls $OCI8_LCS_BASE.*.1 2> /dev/null | $PHP_OCI8_TAIL1`  # Oracle 10g, 11g etc
   OCI8_NNZ=`ls $PHP_OCI8_INSTANT_CLIENT/libnnz*.$SHLIB_SUFFIX_NAME 2> /dev/null | $PHP_OCI8_TAIL1`
@@ -64453,7 +64687,7 @@ EOF
       ac_obj=$1
       IFS=$old_IFS
       
-      PHP_CLI_OBJS="$PHP_CLI_OBJS $ac_bdir$ac_obj.lo"
+      PHP_GLOBAL_OBJS="$PHP_GLOBAL_OBJS $ac_bdir$ac_obj.lo"
 
       case $ac_src in
         *.c) ac_comp="$b_c_pre $ac_extra $ac_inc $b_c_meta -c $ac_srcdir$ac_src -o $ac_bdir$ac_obj.$b_lo $b_c_post" ;;
@@ -64570,7 +64804,7 @@ esac
 
   if test "$PHP_ADABAS" != "no"; then
     echo $ac_n "checking for Adabas support""... $ac_c" 1>&6
-echo "configure:64574: checking for Adabas support" >&5
+echo "configure:64813: checking for Adabas support" >&5
     if test "$PHP_ADABAS" = "yes"; then
       PHP_ADABAS=/usr/local
     fi
@@ -64773,7 +65007,7 @@ esac
 
   if test "$PHP_SAPDB" != "no"; then
     echo $ac_n "checking for SAP DB support""... $ac_c" 1>&6
-echo "configure:64777: checking for SAP DB support" >&5
+echo "configure:65016: checking for SAP DB support" >&5
     if test "$PHP_SAPDB" = "yes"; then
       PHP_SAPDB=/usr/local
     fi
@@ -64906,7 +65140,7 @@ esac
 
   if test "$PHP_SOLID" != "no"; then
     echo $ac_n "checking for Solid support""... $ac_c" 1>&6
-echo "configure:64910: checking for Solid support" >&5
+echo "configure:65149: checking for Solid support" >&5
     if test "$PHP_SOLID" = "yes"; then
       PHP_SOLID=/usr/local/solid
     fi
@@ -64933,7 +65167,7 @@ EOF
     echo "$ac_t""$ext_output" 1>&6
     
   echo $ac_n "checking Solid library file""... $ac_c" 1>&6
-echo "configure:64937: checking Solid library file" >&5  
+echo "configure:65176: checking Solid library file" >&5  
   ac_solid_uname_r=`uname -r 2>/dev/null`
   ac_solid_uname_s=`uname -s 2>/dev/null`
   case $ac_solid_uname_s in
@@ -65054,7 +65288,7 @@ esac
 
   if test "$PHP_IBM_DB2" != "no"; then
     echo $ac_n "checking for IBM DB2 support""... $ac_c" 1>&6
-echo "configure:65058: checking for IBM DB2 support" >&5
+echo "configure:65297: checking for IBM DB2 support" >&5
     if test "$PHP_IBM_DB2" = "yes"; then
       ODBC_INCDIR=/home/db2inst1/sqllib/include
       ODBC_LIBDIR=/home/db2inst1/sqllib/lib
@@ -65085,7 +65319,7 @@ fi
   
 else
   cat > conftest.$ac_ext <<EOF
-#line 65089 "configure"
+#line 65328 "configure"
 #include "confdefs.h"
 
     
@@ -65096,7 +65330,7 @@ else
     }
   
 EOF
-if { (eval echo configure:65100: \"$ac_link\") 1>&5; (eval $ac_link) 2>&5; } && test -s conftest${ac_exeext} && (./conftest; exit) 2>/dev/null
+if { (eval echo configure:65339: \"$ac_link\") 1>&5; (eval $ac_link) 2>&5; } && test -s conftest${ac_exeext} && (./conftest; exit) 2>/dev/null
 then
   
     LIBS=$old_LIBS
@@ -65177,7 +65411,7 @@ esac
 
   if test "$PHP_ODBCROUTER" != "no"; then
     echo $ac_n "checking for ODBCRouter.com support""... $ac_c" 1>&6
-echo "configure:65181: checking for ODBCRouter.com support" >&5
+echo "configure:65420: checking for ODBCRouter.com support" >&5
     if test "$PHP_ODBCROUTER" = "yes"; then
       PHP_ODBCROUTER=/usr
     fi
@@ -65241,7 +65475,7 @@ esac
 
   if test "$PHP_EMPRESS" != "no"; then
     echo $ac_n "checking for Empress support""... $ac_c" 1>&6
-echo "configure:65245: checking for Empress support" >&5
+echo "configure:65484: checking for Empress support" >&5
     if test "$PHP_EMPRESS" = "yes"; then
       ODBC_INCDIR=$EMPRESSPATH/include/odbc
       ODBC_LIBDIR=$EMPRESSPATH/shlib
@@ -65259,7 +65493,7 @@ EOF
     echo "$ac_t""$ext_output" 1>&6
     
   echo $ac_n "checking Empress library file""... $ac_c" 1>&6
-echo "configure:65263: checking Empress library file" >&5
+echo "configure:65502: checking Empress library file" >&5
   ODBC_LIBS=`echo $ODBC_LIBDIR/libempodbccl.so | cut -d' ' -f1`
   if test ! -f $ODBC_LIBS; then
     ODBC_LIBS=`echo $ODBC_LIBDIR/libempodbccl.so | cut -d' ' -f1`
@@ -65315,7 +65549,7 @@ esac
 
   if test "$PHP_EMPRESS_BCS" != "no"; then
     echo $ac_n "checking for Empress local access support""... $ac_c" 1>&6
-echo "configure:65319: checking for Empress local access support" >&5
+echo "configure:65558: checking for Empress local access support" >&5
     if test "$PHP_EMPRESS_BCS" = "yes"; then
       ODBC_INCDIR=$EMPRESSPATH/include/odbc
       ODBC_LIBDIR=$EMPRESSPATH/shlib
@@ -65349,7 +65583,7 @@ EOF
     echo "$ac_t""$ext_output" 1>&6
     
   echo $ac_n "checking Empress local access library file""... $ac_c" 1>&6
-echo "configure:65353: checking Empress local access library file" >&5
+echo "configure:65592: checking Empress local access library file" >&5
   ODBCBCS_LIBS=`echo $ODBC_LIBDIR/libempodbcbcs.a | cut -d' ' -f1`
   if test ! -f $ODBCBCS_LIBS; then
     ODBCBCS_LIBS=`echo $ODBC_LIBDIR/libempodbcbcs.a | cut -d' ' -f1`
@@ -65405,7 +65639,7 @@ esac
   
   if test "$PHP_BIRDSTEP" != "no"; then
     echo $ac_n "checking for Birdstep support""... $ac_c" 1>&6
-echo "configure:65409: checking for Birdstep support" >&5
+echo "configure:65648: checking for Birdstep support" >&5
     if test "$PHP_BIRDSTEP" = "yes"; then
         ODBC_INCDIR=/usr/local/birdstep/include
         ODBC_LIBDIR=/usr/local/birdstep/lib
@@ -65517,7 +65751,7 @@ esac
 
   if test "$PHP_CUSTOM_ODBC" != "no"; then
     echo $ac_n "checking for a custom ODBC support""... $ac_c" 1>&6
-echo "configure:65521: checking for a custom ODBC support" >&5
+echo "configure:65760: checking for a custom ODBC support" >&5
     if test "$PHP_CUSTOM_ODBC" = "yes"; then
       PHP_CUSTOM_ODBC=/usr/local
     fi
@@ -65581,7 +65815,7 @@ esac
 
   if test "$PHP_IODBC" != "no"; then
     echo $ac_n "checking for iODBC support""... $ac_c" 1>&6
-echo "configure:65585: checking for iODBC support" >&5
+echo "configure:65824: checking for iODBC support" >&5
     if test "$PHP_IODBC" = "yes"; then
       PHP_IODBC=/usr/local
     fi
@@ -65727,7 +65961,7 @@ esac
 
   if test "$PHP_ESOOB" != "no"; then
     echo $ac_n "checking for Easysoft ODBC-ODBC Bridge support""... $ac_c" 1>&6
-echo "configure:65731: checking for Easysoft ODBC-ODBC Bridge support" >&5
+echo "configure:65970: checking for Easysoft ODBC-ODBC Bridge support" >&5
     if test "$PHP_ESOOB" = "yes"; then
       PHP_ESOOB=/usr/local/easysoft/oob/client
     fi
@@ -65791,7 +66025,7 @@ esac
 
   if test "$PHP_UNIXODBC" != "no"; then
     echo $ac_n "checking for unixODBC support""... $ac_c" 1>&6
-echo "configure:65795: checking for unixODBC support" >&5
+echo "configure:66034: checking for unixODBC support" >&5
     if test "$PHP_UNIXODBC" = "yes"; then
       PHP_UNIXODBC=/usr/local
     fi
@@ -65860,7 +66094,7 @@ esac
 
   if test "$PHP_DBMAKER" != "no"; then
     echo $ac_n "checking for DBMaker support""... $ac_c" 1>&6
-echo "configure:65864: checking for DBMaker support" >&5
+echo "configure:66103: checking for DBMaker support" >&5
     if test "$PHP_DBMAKER" = "yes"; then
       # find dbmaker's home directory
       DBMAKER_HOME=`grep "^dbmaker:" /etc/passwd | $AWK -F: '{print $6}'`
@@ -66382,7 +66616,7 @@ EOF
       ac_obj=$1
       IFS=$old_IFS
       
-      PHP_CLI_OBJS="$PHP_CLI_OBJS $ac_bdir$ac_obj.lo"
+      PHP_GLOBAL_OBJS="$PHP_GLOBAL_OBJS $ac_bdir$ac_obj.lo"
 
       case $ac_src in
         *.c) ac_comp="$b_c_pre $ac_extra $ac_inc $b_c_meta -c $ac_srcdir$ac_src -o $ac_bdir$ac_obj.$b_lo $b_c_post" ;;
@@ -66422,7 +66656,7 @@ fi
 php_enable_pcntl=no
 
 echo $ac_n "checking whether to enable pcntl support""... $ac_c" 1>&6
-echo "configure:66426: checking whether to enable pcntl support" >&5
+echo "configure:66665: checking whether to enable pcntl support" >&5
 # Check whether --enable-pcntl or --disable-pcntl was given.
 if test "${enable_pcntl+set}" = set; then
   enableval="$enable_pcntl"
@@ -66466,12 +66700,12 @@ if test "$PHP_PCNTL" != "no"; then
   for ac_func in fork
 do
 echo $ac_n "checking for $ac_func""... $ac_c" 1>&6
-echo "configure:66470: checking for $ac_func" >&5
+echo "configure:66709: checking for $ac_func" >&5
 if eval "test \"`echo '$''{'ac_cv_func_$ac_func'+set}'`\" = set"; then
   echo $ac_n "(cached) $ac_c" 1>&6
 else
   cat > conftest.$ac_ext <<EOF
-#line 66475 "configure"
+#line 66714 "configure"
 #include "confdefs.h"
 /* System header to define __stub macros and hopefully few prototypes,
     which can conflict with char $ac_func(); below.  */
@@ -66494,7 +66728,7 @@ $ac_func();
 
 ; return 0; }
 EOF
-if { (eval echo configure:66498: \"$ac_link\") 1>&5; (eval $ac_link) 2>&5; } && test -s conftest${ac_exeext}; then
+if { (eval echo configure:66737: \"$ac_link\") 1>&5; (eval $ac_link) 2>&5; } && test -s conftest${ac_exeext}; then
   rm -rf conftest*
   eval "ac_cv_func_$ac_func=yes"
 else
@@ -66525,12 +66759,12 @@ done
   for ac_func in waitpid
 do
 echo $ac_n "checking for $ac_func""... $ac_c" 1>&6
-echo "configure:66529: checking for $ac_func" >&5
+echo "configure:66768: checking for $ac_func" >&5
 if eval "test \"`echo '$''{'ac_cv_func_$ac_func'+set}'`\" = set"; then
   echo $ac_n "(cached) $ac_c" 1>&6
 else
   cat > conftest.$ac_ext <<EOF
-#line 66534 "configure"
+#line 66773 "configure"
 #include "confdefs.h"
 /* System header to define __stub macros and hopefully few prototypes,
     which can conflict with char $ac_func(); below.  */
@@ -66553,7 +66787,7 @@ $ac_func();
 
 ; return 0; }
 EOF
-if { (eval echo configure:66557: \"$ac_link\") 1>&5; (eval $ac_link) 2>&5; } && test -s conftest${ac_exeext}; then
+if { (eval echo configure:66796: \"$ac_link\") 1>&5; (eval $ac_link) 2>&5; } && test -s conftest${ac_exeext}; then
   rm -rf conftest*
   eval "ac_cv_func_$ac_func=yes"
 else
@@ -66584,12 +66818,12 @@ done
   for ac_func in sigaction
 do
 echo $ac_n "checking for $ac_func""... $ac_c" 1>&6
-echo "configure:66588: checking for $ac_func" >&5
+echo "configure:66827: checking for $ac_func" >&5
 if eval "test \"`echo '$''{'ac_cv_func_$ac_func'+set}'`\" = set"; then
   echo $ac_n "(cached) $ac_c" 1>&6
 else
   cat > conftest.$ac_ext <<EOF
-#line 66593 "configure"
+#line 66832 "configure"
 #include "confdefs.h"
 /* System header to define __stub macros and hopefully few prototypes,
     which can conflict with char $ac_func(); below.  */
@@ -66612,7 +66846,7 @@ $ac_func();
 
 ; return 0; }
 EOF
-if { (eval echo configure:66616: \"$ac_link\") 1>&5; (eval $ac_link) 2>&5; } && test -s conftest${ac_exeext}; then
+if { (eval echo configure:66855: \"$ac_link\") 1>&5; (eval $ac_link) 2>&5; } && test -s conftest${ac_exeext}; then
   rm -rf conftest*
   eval "ac_cv_func_$ac_func=yes"
 else
@@ -66643,12 +66877,12 @@ done
   for ac_func in getpriority setpriority wait3 sigprocmask sigwaitinfo sigtimedwait
 do
 echo $ac_n "checking for $ac_func""... $ac_c" 1>&6
-echo "configure:66647: checking for $ac_func" >&5
+echo "configure:66886: checking for $ac_func" >&5
 if eval "test \"`echo '$''{'ac_cv_func_$ac_func'+set}'`\" = set"; then
   echo $ac_n "(cached) $ac_c" 1>&6
 else
   cat > conftest.$ac_ext <<EOF
-#line 66652 "configure"
+#line 66891 "configure"
 #include "confdefs.h"
 /* System header to define __stub macros and hopefully few prototypes,
     which can conflict with char $ac_func(); below.  */
@@ -66671,7 +66905,7 @@ $ac_func();
 
 ; return 0; }
 EOF
-if { (eval echo configure:66675: \"$ac_link\") 1>&5; (eval $ac_link) 2>&5; } && test -s conftest${ac_exeext}; then
+if { (eval echo configure:66914: \"$ac_link\") 1>&5; (eval $ac_link) 2>&5; } && test -s conftest${ac_exeext}; then
   rm -rf conftest*
   eval "ac_cv_func_$ac_func=yes"
 else
@@ -66954,7 +67188,7 @@ EOF
       ac_obj=$1
       IFS=$old_IFS
       
-      PHP_CLI_OBJS="$PHP_CLI_OBJS $ac_bdir$ac_obj.lo"
+      PHP_GLOBAL_OBJS="$PHP_GLOBAL_OBJS $ac_bdir$ac_obj.lo"
 
       case $ac_src in
         *.c) ac_comp="$b_c_pre $ac_extra $ac_inc $b_c_meta -c $ac_srcdir$ac_src -o $ac_bdir$ac_obj.$b_lo $b_c_post" ;;
@@ -66996,7 +67230,7 @@ fi
 php_enable_pdo=yes
 
 echo $ac_n "checking whether to enable PDO support""... $ac_c" 1>&6
-echo "configure:67000: checking whether to enable PDO support" >&5
+echo "configure:67239: checking whether to enable PDO support" >&5
 # Check whether --enable-pdo or --disable-pdo was given.
 if test "${enable_pdo+set}" = set; then
   enableval="$enable_pdo"
@@ -67338,7 +67572,7 @@ EOF
       ac_obj=$1
       IFS=$old_IFS
       
-      PHP_CLI_OBJS="$PHP_CLI_OBJS $ac_bdir$ac_obj.lo"
+      PHP_GLOBAL_OBJS="$PHP_GLOBAL_OBJS $ac_bdir$ac_obj.lo"
 
       case $ac_src in
         *.c) ac_comp="$b_c_pre $ac_extra $ac_inc $b_c_meta -c $ac_srcdir$ac_src -o $ac_bdir$ac_obj.$b_lo $b_c_post" ;;
@@ -67430,7 +67664,7 @@ fi
 php_with_pdo_dblib=no
 
 echo $ac_n "checking for PDO_DBLIB support via FreeTDS""... $ac_c" 1>&6
-echo "configure:67434: checking for PDO_DBLIB support via FreeTDS" >&5
+echo "configure:67673: checking for PDO_DBLIB support via FreeTDS" >&5
 # Check whether --with-pdo-dblib or --without-pdo-dblib was given.
 if test "${with_pdo_dblib+set}" = set; then
   withval="$with_pdo_dblib"
@@ -67647,13 +67881,13 @@ if test "$PHP_PDO_DBLIB" != "no"; then
   
     
   echo $ac_n "checking for PDO includes""... $ac_c" 1>&6
-echo "configure:67651: checking for PDO includes" >&5
+echo "configure:67890: checking for PDO includes" >&5
 if eval "test \"`echo '$''{'pdo_inc_path'+set}'`\" = set"; then
   echo $ac_n "(cached) $ac_c" 1>&6
 else
   
     echo $ac_n "checking for PDO includes""... $ac_c" 1>&6
-echo "configure:67657: checking for PDO includes" >&5
+echo "configure:67896: checking for PDO includes" >&5
     if test -f $abs_srcdir/include/php/ext/pdo/php_pdo_driver.h; then
       pdo_inc_path=$abs_srcdir/ext
     elif test -f $abs_srcdir/ext/pdo/php_pdo_driver.h; then
@@ -67933,7 +68167,7 @@ EOF
       ac_obj=$1
       IFS=$old_IFS
       
-      PHP_CLI_OBJS="$PHP_CLI_OBJS $ac_bdir$ac_obj.lo"
+      PHP_GLOBAL_OBJS="$PHP_GLOBAL_OBJS $ac_bdir$ac_obj.lo"
 
       case $ac_src in
         *.c) ac_comp="$b_c_pre $ac_extra $ac_inc $b_c_meta -c $ac_srcdir$ac_src -o $ac_bdir$ac_obj.$b_lo $b_c_post" ;;
@@ -67967,7 +68201,7 @@ EOF
   fi
 
   echo $ac_n "checking for dnet_addr in -ldnet_stub""... $ac_c" 1>&6
-echo "configure:67971: checking for dnet_addr in -ldnet_stub" >&5
+echo "configure:68210: checking for dnet_addr in -ldnet_stub" >&5
 ac_lib_var=`echo dnet_stub'_'dnet_addr | sed 'y%./+-%__p_%'`
 if eval "test \"`echo '$''{'ac_cv_lib_$ac_lib_var'+set}'`\" = set"; then
   echo $ac_n "(cached) $ac_c" 1>&6
@@ -67975,7 +68209,7 @@ else
   ac_save_LIBS="$LIBS"
 LIBS="-ldnet_stub  $LIBS"
 cat > conftest.$ac_ext <<EOF
-#line 67979 "configure"
+#line 68218 "configure"
 #include "confdefs.h"
 /* Override any gcc2 internal prototype to avoid an error.  */
 /* We use char because int might match the return type of a gcc2
@@ -67986,7 +68220,7 @@ int main() {
 dnet_addr()
 ; return 0; }
 EOF
-if { (eval echo configure:67990: \"$ac_link\") 1>&5; (eval $ac_link) 2>&5; } && test -s conftest${ac_exeext}; then
+if { (eval echo configure:68229: \"$ac_link\") 1>&5; (eval $ac_link) 2>&5; } && test -s conftest${ac_exeext}; then
   rm -rf conftest*
   eval "ac_cv_lib_$ac_lib_var=yes"
 else
@@ -68147,7 +68381,7 @@ fi
 php_with_pdo_firebird=no
 
 echo $ac_n "checking for Firebird support for PDO""... $ac_c" 1>&6
-echo "configure:68151: checking for Firebird support for PDO" >&5
+echo "configure:68390: checking for Firebird support for PDO" >&5
 # Check whether --with-pdo-firebird or --without-pdo-firebird was given.
 if test "${with_pdo_firebird+set}" = set; then
   withval="$with_pdo_firebird"
@@ -68301,7 +68535,7 @@ if test "$PHP_PDO_FIREBIRD" != "no"; then
   done
 
   echo $ac_n "checking for isc_detach_database in -lfbclient""... $ac_c" 1>&6
-echo "configure:68305: checking for isc_detach_database in -lfbclient" >&5
+echo "configure:68544: checking for isc_detach_database in -lfbclient" >&5
 ac_lib_var=`echo fbclient'_'isc_detach_database | sed 'y%./+-%__p_%'`
 if eval "test \"`echo '$''{'ac_cv_lib_$ac_lib_var'+set}'`\" = set"; then
   echo $ac_n "(cached) $ac_c" 1>&6
@@ -68309,7 +68543,7 @@ else
   ac_save_LIBS="$LIBS"
 LIBS="-lfbclient  $LIBS"
 cat > conftest.$ac_ext <<EOF
-#line 68313 "configure"
+#line 68552 "configure"
 #include "confdefs.h"
 /* Override any gcc2 internal prototype to avoid an error.  */
 /* We use char because int might match the return type of a gcc2
@@ -68320,7 +68554,7 @@ int main() {
 isc_detach_database()
 ; return 0; }
 EOF
-if { (eval echo configure:68324: \"$ac_link\") 1>&5; (eval $ac_link) 2>&5; } && test -s conftest${ac_exeext}; then
+if { (eval echo configure:68563: \"$ac_link\") 1>&5; (eval $ac_link) 2>&5; } && test -s conftest${ac_exeext}; then
   rm -rf conftest*
   eval "ac_cv_lib_$ac_lib_var=yes"
 else
@@ -68447,7 +68681,7 @@ else
   done
 
   echo $ac_n "checking for isc_detach_database in -lgds""... $ac_c" 1>&6
-echo "configure:68451: checking for isc_detach_database in -lgds" >&5
+echo "configure:68690: checking for isc_detach_database in -lgds" >&5
 ac_lib_var=`echo gds'_'isc_detach_database | sed 'y%./+-%__p_%'`
 if eval "test \"`echo '$''{'ac_cv_lib_$ac_lib_var'+set}'`\" = set"; then
   echo $ac_n "(cached) $ac_c" 1>&6
@@ -68455,7 +68689,7 @@ else
   ac_save_LIBS="$LIBS"
 LIBS="-lgds  $LIBS"
 cat > conftest.$ac_ext <<EOF
-#line 68459 "configure"
+#line 68698 "configure"
 #include "confdefs.h"
 /* Override any gcc2 internal prototype to avoid an error.  */
 /* We use char because int might match the return type of a gcc2
@@ -68466,7 +68700,7 @@ int main() {
 isc_detach_database()
 ; return 0; }
 EOF
-if { (eval echo configure:68470: \"$ac_link\") 1>&5; (eval $ac_link) 2>&5; } && test -s conftest${ac_exeext}; then
+if { (eval echo configure:68709: \"$ac_link\") 1>&5; (eval $ac_link) 2>&5; } && test -s conftest${ac_exeext}; then
   rm -rf conftest*
   eval "ac_cv_lib_$ac_lib_var=yes"
 else
@@ -68593,7 +68827,7 @@ else
   done
 
   echo $ac_n "checking for isc_detach_database in -lib_util""... $ac_c" 1>&6
-echo "configure:68597: checking for isc_detach_database in -lib_util" >&5
+echo "configure:68836: checking for isc_detach_database in -lib_util" >&5
 ac_lib_var=`echo ib_util'_'isc_detach_database | sed 'y%./+-%__p_%'`
 if eval "test \"`echo '$''{'ac_cv_lib_$ac_lib_var'+set}'`\" = set"; then
   echo $ac_n "(cached) $ac_c" 1>&6
@@ -68601,7 +68835,7 @@ else
   ac_save_LIBS="$LIBS"
 LIBS="-lib_util  $LIBS"
 cat > conftest.$ac_ext <<EOF
-#line 68605 "configure"
+#line 68844 "configure"
 #include "confdefs.h"
 /* Override any gcc2 internal prototype to avoid an error.  */
 /* We use char because int might match the return type of a gcc2
@@ -68612,7 +68846,7 @@ int main() {
 isc_detach_database()
 ; return 0; }
 EOF
-if { (eval echo configure:68616: \"$ac_link\") 1>&5; (eval $ac_link) 2>&5; } && test -s conftest${ac_exeext}; then
+if { (eval echo configure:68855: \"$ac_link\") 1>&5; (eval $ac_link) 2>&5; } && test -s conftest${ac_exeext}; then
   rm -rf conftest*
   eval "ac_cv_lib_$ac_lib_var=yes"
 else
@@ -68657,13 +68891,13 @@ fi
  
   
   echo $ac_n "checking for PDO includes""... $ac_c" 1>&6
-echo "configure:68661: checking for PDO includes" >&5
+echo "configure:68900: checking for PDO includes" >&5
 if eval "test \"`echo '$''{'pdo_inc_path'+set}'`\" = set"; then
   echo $ac_n "(cached) $ac_c" 1>&6
 else
   
     echo $ac_n "checking for PDO includes""... $ac_c" 1>&6
-echo "configure:68667: checking for PDO includes" >&5
+echo "configure:68906: checking for PDO includes" >&5
     if test -f $abs_srcdir/include/php/ext/pdo/php_pdo_driver.h; then
       pdo_inc_path=$abs_srcdir/ext
     elif test -f $abs_srcdir/ext/pdo/php_pdo_driver.h; then
@@ -69073,7 +69307,7 @@ EOF
       ac_obj=$1
       IFS=$old_IFS
       
-      PHP_CLI_OBJS="$PHP_CLI_OBJS $ac_bdir$ac_obj.lo"
+      PHP_GLOBAL_OBJS="$PHP_GLOBAL_OBJS $ac_bdir$ac_obj.lo"
 
       case $ac_src in
         *.c) ac_comp="$b_c_pre $ac_extra $ac_inc $b_c_meta -c $ac_srcdir$ac_src -o $ac_bdir$ac_obj.$b_lo $b_c_post" ;;
@@ -69135,7 +69369,7 @@ fi
 php_with_pdo_mysql=no
 
 echo $ac_n "checking for MySQL support for PDO""... $ac_c" 1>&6
-echo "configure:69139: checking for MySQL support for PDO" >&5
+echo "configure:69378: checking for MySQL support for PDO" >&5
 # Check whether --with-pdo-mysql or --without-pdo-mysql was given.
 if test "${with_pdo_mysql+set}" = set; then
   withval="$with_pdo_mysql"
@@ -69180,7 +69414,7 @@ if test -z "$PHP_ZLIB_DIR"; then
 php_with_zlib_dir=no
 
 echo $ac_n "checking for the location of libz""... $ac_c" 1>&6
-echo "configure:69184: checking for the location of libz" >&5
+echo "configure:69423: checking for the location of libz" >&5
 # Check whether --with-zlib-dir or --without-zlib-dir was given.
 if test "${with_zlib_dir+set}" = set; then
   withval="$with_zlib_dir"
@@ -69244,14 +69478,14 @@ EOF
 
 
     echo $ac_n "checking for mysql_config""... $ac_c" 1>&6
-echo "configure:69248: checking for mysql_config" >&5
+echo "configure:69487: checking for mysql_config" >&5
     if test -n "$PDO_MYSQL_CONFIG"; then
       echo "$ac_t""$PDO_MYSQL_CONFIG" 1>&6
       if test "x$SED" = "x"; then
         # Extract the first word of "sed", so it can be a program name with args.
 set dummy sed; ac_word=$2
 echo $ac_n "checking for $ac_word""... $ac_c" 1>&6
-echo "configure:69255: checking for $ac_word" >&5
+echo "configure:69494: checking for $ac_word" >&5
 if eval "test \"`echo '$''{'ac_cv_path_SED'+set}'`\" = set"; then
   echo $ac_n "(cached) $ac_c" 1>&6
 else
@@ -69295,7 +69529,7 @@ fi
     elif test -n "$PDO_MYSQL_DIR"; then
       echo "$ac_t""not found" 1>&6
       echo $ac_n "checking for mysql install under $PDO_MYSQL_DIR""... $ac_c" 1>&6
-echo "configure:69299: checking for mysql install under $PDO_MYSQL_DIR" >&5
+echo "configure:69538: checking for mysql install under $PDO_MYSQL_DIR" >&5
       if test -r $PDO_MYSQL_DIR/include/mysql; then
         PDO_MYSQL_INC_DIR=$PDO_MYSQL_DIR/include/mysql
       else
@@ -69449,7 +69683,7 @@ echo "configure:69299: checking for mysql install under $PDO_MYSQL_DIR" >&5
   done
 
   echo $ac_n "checking for mysql_query in -l$PDO_MYSQL_LIBNAME""... $ac_c" 1>&6
-echo "configure:69453: checking for mysql_query in -l$PDO_MYSQL_LIBNAME" >&5
+echo "configure:69692: checking for mysql_query in -l$PDO_MYSQL_LIBNAME" >&5
 ac_lib_var=`echo $PDO_MYSQL_LIBNAME'_'mysql_query | sed 'y%./+-%__p_%'`
 if eval "test \"`echo '$''{'ac_cv_lib_$ac_lib_var'+set}'`\" = set"; then
   echo $ac_n "(cached) $ac_c" 1>&6
@@ -69457,7 +69691,7 @@ else
   ac_save_LIBS="$LIBS"
 LIBS="-l$PDO_MYSQL_LIBNAME  $LIBS"
 cat > conftest.$ac_ext <<EOF
-#line 69461 "configure"
+#line 69700 "configure"
 #include "confdefs.h"
 /* Override any gcc2 internal prototype to avoid an error.  */
 /* We use char because int might match the return type of a gcc2
@@ -69468,7 +69702,7 @@ int main() {
 mysql_query()
 ; return 0; }
 EOF
-if { (eval echo configure:69472: \"$ac_link\") 1>&5; (eval $ac_link) 2>&5; } && test -s conftest${ac_exeext}; then
+if { (eval echo configure:69711: \"$ac_link\") 1>&5; (eval $ac_link) 2>&5; } && test -s conftest${ac_exeext}; then
   rm -rf conftest*
   eval "ac_cv_lib_$ac_lib_var=yes"
 else
@@ -69821,7 +70055,7 @@ else
   done
 
   echo $ac_n "checking for mysql_query in -l$PDO_MYSQL_LIBNAME""... $ac_c" 1>&6
-echo "configure:69825: checking for mysql_query in -l$PDO_MYSQL_LIBNAME" >&5
+echo "configure:70064: checking for mysql_query in -l$PDO_MYSQL_LIBNAME" >&5
 ac_lib_var=`echo $PDO_MYSQL_LIBNAME'_'mysql_query | sed 'y%./+-%__p_%'`
 if eval "test \"`echo '$''{'ac_cv_lib_$ac_lib_var'+set}'`\" = set"; then
   echo $ac_n "(cached) $ac_c" 1>&6
@@ -69829,7 +70063,7 @@ else
   ac_save_LIBS="$LIBS"
 LIBS="-l$PDO_MYSQL_LIBNAME  $LIBS"
 cat > conftest.$ac_ext <<EOF
-#line 69833 "configure"
+#line 70072 "configure"
 #include "confdefs.h"
 /* Override any gcc2 internal prototype to avoid an error.  */
 /* We use char because int might match the return type of a gcc2
@@ -69840,7 +70074,7 @@ int main() {
 mysql_query()
 ; return 0; }
 EOF
-if { (eval echo configure:69844: \"$ac_link\") 1>&5; (eval $ac_link) 2>&5; } && test -s conftest${ac_exeext}; then
+if { (eval echo configure:70083: \"$ac_link\") 1>&5; (eval $ac_link) 2>&5; } && test -s conftest${ac_exeext}; then
   rm -rf conftest*
   eval "ac_cv_lib_$ac_lib_var=yes"
 else
@@ -69995,7 +70229,7 @@ fi
   done
 
   echo $ac_n "checking for mysql_query in -l$PDO_MYSQL_LIBNAME""... $ac_c" 1>&6
-echo "configure:69999: checking for mysql_query in -l$PDO_MYSQL_LIBNAME" >&5
+echo "configure:70238: checking for mysql_query in -l$PDO_MYSQL_LIBNAME" >&5
 ac_lib_var=`echo $PDO_MYSQL_LIBNAME'_'mysql_query | sed 'y%./+-%__p_%'`
 if eval "test \"`echo '$''{'ac_cv_lib_$ac_lib_var'+set}'`\" = set"; then
   echo $ac_n "(cached) $ac_c" 1>&6
@@ -70003,7 +70237,7 @@ else
   ac_save_LIBS="$LIBS"
 LIBS="-l$PDO_MYSQL_LIBNAME  $LIBS"
 cat > conftest.$ac_ext <<EOF
-#line 70007 "configure"
+#line 70246 "configure"
 #include "confdefs.h"
 /* Override any gcc2 internal prototype to avoid an error.  */
 /* We use char because int might match the return type of a gcc2
@@ -70014,7 +70248,7 @@ int main() {
 mysql_query()
 ; return 0; }
 EOF
-if { (eval echo configure:70018: \"$ac_link\") 1>&5; (eval $ac_link) 2>&5; } && test -s conftest${ac_exeext}; then
+if { (eval echo configure:70257: \"$ac_link\") 1>&5; (eval $ac_link) 2>&5; } && test -s conftest${ac_exeext}; then
   rm -rf conftest*
   eval "ac_cv_lib_$ac_lib_var=yes"
 else
@@ -70188,12 +70422,12 @@ fi
     for ac_func in mysql_commit mysql_stmt_prepare mysql_next_result mysql_sqlstate
 do
 echo $ac_n "checking for $ac_func""... $ac_c" 1>&6
-echo "configure:70192: checking for $ac_func" >&5
+echo "configure:70431: checking for $ac_func" >&5
 if eval "test \"`echo '$''{'ac_cv_func_$ac_func'+set}'`\" = set"; then
   echo $ac_n "(cached) $ac_c" 1>&6
 else
   cat > conftest.$ac_ext <<EOF
-#line 70197 "configure"
+#line 70436 "configure"
 #include "confdefs.h"
 /* System header to define __stub macros and hopefully few prototypes,
     which can conflict with char $ac_func(); below.  */
@@ -70216,7 +70450,7 @@ $ac_func();
 
 ; return 0; }
 EOF
-if { (eval echo configure:70220: \"$ac_link\") 1>&5; (eval $ac_link) 2>&5; } && test -s conftest${ac_exeext}; then
+if { (eval echo configure:70459: \"$ac_link\") 1>&5; (eval $ac_link) 2>&5; } && test -s conftest${ac_exeext}; then
   rm -rf conftest*
   eval "ac_cv_func_$ac_func=yes"
 else
@@ -70246,13 +70480,13 @@ done
   
     
   echo $ac_n "checking for PDO includes""... $ac_c" 1>&6
-echo "configure:70250: checking for PDO includes" >&5
+echo "configure:70489: checking for PDO includes" >&5
 if eval "test \"`echo '$''{'pdo_inc_path'+set}'`\" = set"; then
   echo $ac_n "(cached) $ac_c" 1>&6
 else
   
     echo $ac_n "checking for PDO includes""... $ac_c" 1>&6
-echo "configure:70256: checking for PDO includes" >&5
+echo "configure:70495: checking for PDO includes" >&5
     if test -f $abs_srcdir/include/php/ext/pdo/php_pdo_driver.h; then
       pdo_inc_path=$abs_srcdir/ext
     elif test -f $abs_srcdir/ext/pdo/php_pdo_driver.h; then
@@ -70539,7 +70773,7 @@ EOF
       ac_obj=$1
       IFS=$old_IFS
       
-      PHP_CLI_OBJS="$PHP_CLI_OBJS $ac_bdir$ac_obj.lo"
+      PHP_GLOBAL_OBJS="$PHP_GLOBAL_OBJS $ac_bdir$ac_obj.lo"
 
       case $ac_src in
         *.c) ac_comp="$b_c_pre $ac_extra $ac_inc $b_c_meta -c $ac_srcdir$ac_src -o $ac_bdir$ac_obj.$b_lo $b_c_post" ;;
@@ -70637,7 +70871,7 @@ SUPPORTED_LIB_VERS="9.0 10.1 11.1"  # This caters for all Oracle 9.x, 10.x and 1
 php_with_pdo_oci=no
 
 echo $ac_n "checking Oracle OCI support for PDO""... $ac_c" 1>&6
-echo "configure:70641: checking Oracle OCI support for PDO" >&5
+echo "configure:70880: checking Oracle OCI support for PDO" >&5
 # Check whether --with-pdo-oci or --without-pdo-oci was given.
 if test "${with_pdo_oci+set}" = set; then
   withval="$with_pdo_oci"
@@ -70684,7 +70918,7 @@ if test "$PHP_PDO_OCI" != "no"; then
   fi
 
   echo $ac_n "checking Oracle Install-Dir""... $ac_c" 1>&6
-echo "configure:70688: checking Oracle Install-Dir" >&5
+echo "configure:70927: checking Oracle Install-Dir" >&5
   if test "$PHP_PDO_OCI" = "yes" || test -z "$PHP_PDO_OCI"; then
     PDO_OCI_DIR=$ORACLE_HOME
   else
@@ -70693,7 +70927,7 @@ echo "configure:70688: checking Oracle Install-Dir" >&5
   echo "$ac_t""$PHP_PDO_OCI" 1>&6
 
   echo $ac_n "checking if that is sane""... $ac_c" 1>&6
-echo "configure:70697: checking if that is sane" >&5
+echo "configure:70936: checking if that is sane" >&5
   if test -z "$PDO_OCI_DIR"; then
     { echo "configure: error: 
 You need to tell me where to find your Oracle Instant Client SDK, or set ORACLE_HOME.
@@ -70704,7 +70938,7 @@ You need to tell me where to find your Oracle Instant Client SDK, or set ORACLE_
 
   if test "instantclient" = "`echo $PDO_OCI_DIR | cut -d, -f1`" ; then
     echo $ac_n "checking size of long int""... $ac_c" 1>&6
-echo "configure:70708: checking size of long int" >&5
+echo "configure:70947: checking size of long int" >&5
 if eval "test \"`echo '$''{'ac_cv_sizeof_long_int'+set}'`\" = set"; then
   echo $ac_n "(cached) $ac_c" 1>&6
 else
@@ -70712,7 +70946,7 @@ else
   ac_cv_sizeof_long_int=4
 else
   cat > conftest.$ac_ext <<EOF
-#line 70716 "configure"
+#line 70955 "configure"
 #include "confdefs.h"
 #include <stdio.h>
 main()
@@ -70723,7 +70957,7 @@ main()
   exit(0);
 }
 EOF
-if { (eval echo configure:70727: \"$ac_link\") 1>&5; (eval $ac_link) 2>&5; } && test -s conftest${ac_exeext} && (./conftest; exit) 2>/dev/null
+if { (eval echo configure:70966: \"$ac_link\") 1>&5; (eval $ac_link) 2>&5; } && test -s conftest${ac_exeext} && (./conftest; exit) 2>/dev/null
 then
   ac_cv_sizeof_long_int=`cat conftestval`
 else
@@ -70757,7 +70991,7 @@ EOF
       fi
     fi
     echo $ac_n "checking for oci.h""... $ac_c" 1>&6
-echo "configure:70761: checking for oci.h" >&5
+echo "configure:71000: checking for oci.h" >&5
     if test -f $PDO_OCI_IC_PREFIX/include/oracle/$PDO_OCI_IC_VERS/$PDO_OCI_CLIENT_DIR/oci.h ; then
       
   if test "$PDO_OCI_IC_PREFIX/include/oracle/$PDO_OCI_IC_VERS/$PDO_OCI_CLIENT_DIR" != "/usr/include"; then
@@ -70906,7 +71140,7 @@ echo "configure:70761: checking for oci.h" >&5
   else
     
   echo $ac_n "checking size of long int""... $ac_c" 1>&6
-echo "configure:70910: checking size of long int" >&5
+echo "configure:71149: checking size of long int" >&5
 if eval "test \"`echo '$''{'ac_cv_sizeof_long_int'+set}'`\" = set"; then
   echo $ac_n "(cached) $ac_c" 1>&6
 else
@@ -70914,7 +71148,7 @@ else
   ac_cv_sizeof_long_int=4
 else
   cat > conftest.$ac_ext <<EOF
-#line 70918 "configure"
+#line 71157 "configure"
 #include "confdefs.h"
 #include <stdio.h>
 main()
@@ -70925,7 +71159,7 @@ main()
   exit(0);
 }
 EOF
-if { (eval echo configure:70929: \"$ac_link\") 1>&5; (eval $ac_link) 2>&5; } && test -s conftest${ac_exeext} && (./conftest; exit) 2>/dev/null
+if { (eval echo configure:71168: \"$ac_link\") 1>&5; (eval $ac_link) 2>&5; } && test -s conftest${ac_exeext} && (./conftest; exit) 2>/dev/null
 then
   ac_cv_sizeof_long_int=`cat conftestval`
 else
@@ -70945,7 +71179,7 @@ EOF
 
 
   echo $ac_n "checking if we're on a 64-bit platform""... $ac_c" 1>&6
-echo "configure:70949: checking if we're on a 64-bit platform" >&5
+echo "configure:71188: checking if we're on a 64-bit platform" >&5
   if test "$ac_cv_sizeof_long_int" = "4" ; then
     echo "$ac_t""no" 1>&6
     TMP_PDO_OCI_LIB_DIR="$PDO_OCI_DIR/lib32"
@@ -70955,7 +71189,7 @@ echo "configure:70949: checking if we're on a 64-bit platform" >&5
   fi
 
   echo $ac_n "checking OCI8 libraries dir""... $ac_c" 1>&6
-echo "configure:70959: checking OCI8 libraries dir" >&5
+echo "configure:71198: checking OCI8 libraries dir" >&5
   if test -d "$PDO_OCI_DIR/lib" && test ! -d "$PDO_OCI_DIR/lib32"; then
     PDO_OCI_LIB_DIR="$PDO_OCI_DIR/lib"
   elif test ! -d "$PDO_OCI_DIR/lib" && test -d "$PDO_OCI_DIR/lib32"; then
@@ -71322,7 +71556,7 @@ echo "configure:70959: checking OCI8 libraries dir" >&5
     fi
     
   echo $ac_n "checking Oracle version""... $ac_c" 1>&6
-echo "configure:71326: checking Oracle version" >&5
+echo "configure:71565: checking Oracle version" >&5
   for OCI_VER in $SUPPORTED_LIB_VERS; do
     if test -f $PDO_OCI_DIR/lib/libclntsh.$SHLIB_SUFFIX_NAME.$OCI_VER; then
       PDO_OCI_VERSION="$OCI_VER"
@@ -71504,7 +71738,7 @@ echo "configure:71326: checking Oracle version" >&5
   done
 
   echo $ac_n "checking for OCIEnvCreate in -lclntsh""... $ac_c" 1>&6
-echo "configure:71508: checking for OCIEnvCreate in -lclntsh" >&5
+echo "configure:71747: checking for OCIEnvCreate in -lclntsh" >&5
 ac_lib_var=`echo clntsh'_'OCIEnvCreate | sed 'y%./+-%__p_%'`
 if eval "test \"`echo '$''{'ac_cv_lib_$ac_lib_var'+set}'`\" = set"; then
   echo $ac_n "(cached) $ac_c" 1>&6
@@ -71512,7 +71746,7 @@ else
   ac_save_LIBS="$LIBS"
 LIBS="-lclntsh  $LIBS"
 cat > conftest.$ac_ext <<EOF
-#line 71516 "configure"
+#line 71755 "configure"
 #include "confdefs.h"
 /* Override any gcc2 internal prototype to avoid an error.  */
 /* We use char because int might match the return type of a gcc2
@@ -71523,7 +71757,7 @@ int main() {
 OCIEnvCreate()
 ; return 0; }
 EOF
-if { (eval echo configure:71527: \"$ac_link\") 1>&5; (eval $ac_link) 2>&5; } && test -s conftest${ac_exeext}; then
+if { (eval echo configure:71766: \"$ac_link\") 1>&5; (eval $ac_link) 2>&5; } && test -s conftest${ac_exeext}; then
   rm -rf conftest*
   eval "ac_cv_lib_$ac_lib_var=yes"
 else
@@ -71657,7 +71891,7 @@ fi
   done
 
   echo $ac_n "checking for OCIEnvNlsCreate in -lclntsh""... $ac_c" 1>&6
-echo "configure:71661: checking for OCIEnvNlsCreate in -lclntsh" >&5
+echo "configure:71900: checking for OCIEnvNlsCreate in -lclntsh" >&5
 ac_lib_var=`echo clntsh'_'OCIEnvNlsCreate | sed 'y%./+-%__p_%'`
 if eval "test \"`echo '$''{'ac_cv_lib_$ac_lib_var'+set}'`\" = set"; then
   echo $ac_n "(cached) $ac_c" 1>&6
@@ -71665,7 +71899,7 @@ else
   ac_save_LIBS="$LIBS"
 LIBS="-lclntsh  $LIBS"
 cat > conftest.$ac_ext <<EOF
-#line 71669 "configure"
+#line 71908 "configure"
 #include "confdefs.h"
 /* Override any gcc2 internal prototype to avoid an error.  */
 /* We use char because int might match the return type of a gcc2
@@ -71676,7 +71910,7 @@ int main() {
 OCIEnvNlsCreate()
 ; return 0; }
 EOF
-if { (eval echo configure:71680: \"$ac_link\") 1>&5; (eval $ac_link) 2>&5; } && test -s conftest${ac_exeext}; then
+if { (eval echo configure:71919: \"$ac_link\") 1>&5; (eval $ac_link) 2>&5; } && test -s conftest${ac_exeext}; then
   rm -rf conftest*
   eval "ac_cv_lib_$ac_lib_var=yes"
 else
@@ -71810,7 +72044,7 @@ fi
   done
 
   echo $ac_n "checking for OCILobIsTemporary in -lclntsh""... $ac_c" 1>&6
-echo "configure:71814: checking for OCILobIsTemporary in -lclntsh" >&5
+echo "configure:72053: checking for OCILobIsTemporary in -lclntsh" >&5
 ac_lib_var=`echo clntsh'_'OCILobIsTemporary | sed 'y%./+-%__p_%'`
 if eval "test \"`echo '$''{'ac_cv_lib_$ac_lib_var'+set}'`\" = set"; then
   echo $ac_n "(cached) $ac_c" 1>&6
@@ -71818,7 +72052,7 @@ else
   ac_save_LIBS="$LIBS"
 LIBS="-lclntsh  $LIBS"
 cat > conftest.$ac_ext <<EOF
-#line 71822 "configure"
+#line 72061 "configure"
 #include "confdefs.h"
 /* Override any gcc2 internal prototype to avoid an error.  */
 /* We use char because int might match the return type of a gcc2
@@ -71829,7 +72063,7 @@ int main() {
 OCILobIsTemporary()
 ; return 0; }
 EOF
-if { (eval echo configure:71833: \"$ac_link\") 1>&5; (eval $ac_link) 2>&5; } && test -s conftest${ac_exeext}; then
+if { (eval echo configure:72072: \"$ac_link\") 1>&5; (eval $ac_link) 2>&5; } && test -s conftest${ac_exeext}; then
   rm -rf conftest*
   eval "ac_cv_lib_$ac_lib_var=yes"
 else
@@ -71959,7 +72193,7 @@ else
   done
 
   echo $ac_n "checking for OCILobIsTemporary in -locijdbc8""... $ac_c" 1>&6
-echo "configure:71963: checking for OCILobIsTemporary in -locijdbc8" >&5
+echo "configure:72202: checking for OCILobIsTemporary in -locijdbc8" >&5
 ac_lib_var=`echo ocijdbc8'_'OCILobIsTemporary | sed 'y%./+-%__p_%'`
 if eval "test \"`echo '$''{'ac_cv_lib_$ac_lib_var'+set}'`\" = set"; then
   echo $ac_n "(cached) $ac_c" 1>&6
@@ -71967,7 +72201,7 @@ else
   ac_save_LIBS="$LIBS"
 LIBS="-locijdbc8  $LIBS"
 cat > conftest.$ac_ext <<EOF
-#line 71971 "configure"
+#line 72210 "configure"
 #include "confdefs.h"
 /* Override any gcc2 internal prototype to avoid an error.  */
 /* We use char because int might match the return type of a gcc2
@@ -71978,7 +72212,7 @@ int main() {
 OCILobIsTemporary()
 ; return 0; }
 EOF
-if { (eval echo configure:71982: \"$ac_link\") 1>&5; (eval $ac_link) 2>&5; } && test -s conftest${ac_exeext}; then
+if { (eval echo configure:72221: \"$ac_link\") 1>&5; (eval $ac_link) 2>&5; } && test -s conftest${ac_exeext}; then
   rm -rf conftest*
   eval "ac_cv_lib_$ac_lib_var=yes"
 else
@@ -72139,7 +72373,7 @@ fi
   done
 
   echo $ac_n "checking for OCICollAssign in -lclntsh""... $ac_c" 1>&6
-echo "configure:72143: checking for OCICollAssign in -lclntsh" >&5
+echo "configure:72382: checking for OCICollAssign in -lclntsh" >&5
 ac_lib_var=`echo clntsh'_'OCICollAssign | sed 'y%./+-%__p_%'`
 if eval "test \"`echo '$''{'ac_cv_lib_$ac_lib_var'+set}'`\" = set"; then
   echo $ac_n "(cached) $ac_c" 1>&6
@@ -72147,7 +72381,7 @@ else
   ac_save_LIBS="$LIBS"
 LIBS="-lclntsh  $LIBS"
 cat > conftest.$ac_ext <<EOF
-#line 72151 "configure"
+#line 72390 "configure"
 #include "confdefs.h"
 /* Override any gcc2 internal prototype to avoid an error.  */
 /* We use char because int might match the return type of a gcc2
@@ -72158,7 +72392,7 @@ int main() {
 OCICollAssign()
 ; return 0; }
 EOF
-if { (eval echo configure:72162: \"$ac_link\") 1>&5; (eval $ac_link) 2>&5; } && test -s conftest${ac_exeext}; then
+if { (eval echo configure:72401: \"$ac_link\") 1>&5; (eval $ac_link) 2>&5; } && test -s conftest${ac_exeext}; then
   rm -rf conftest*
   eval "ac_cv_lib_$ac_lib_var=yes"
 else
@@ -72292,7 +72526,7 @@ fi
   done
 
   echo $ac_n "checking for OCIStmtFetch2 in -lclntsh""... $ac_c" 1>&6
-echo "configure:72296: checking for OCIStmtFetch2 in -lclntsh" >&5
+echo "configure:72535: checking for OCIStmtFetch2 in -lclntsh" >&5
 ac_lib_var=`echo clntsh'_'OCIStmtFetch2 | sed 'y%./+-%__p_%'`
 if eval "test \"`echo '$''{'ac_cv_lib_$ac_lib_var'+set}'`\" = set"; then
   echo $ac_n "(cached) $ac_c" 1>&6
@@ -72300,7 +72534,7 @@ else
   ac_save_LIBS="$LIBS"
 LIBS="-lclntsh  $LIBS"
 cat > conftest.$ac_ext <<EOF
-#line 72304 "configure"
+#line 72543 "configure"
 #include "confdefs.h"
 /* Override any gcc2 internal prototype to avoid an error.  */
 /* We use char because int might match the return type of a gcc2
@@ -72311,7 +72545,7 @@ int main() {
 OCIStmtFetch2()
 ; return 0; }
 EOF
-if { (eval echo configure:72315: \"$ac_link\") 1>&5; (eval $ac_link) 2>&5; } && test -s conftest${ac_exeext}; then
+if { (eval echo configure:72554: \"$ac_link\") 1>&5; (eval $ac_link) 2>&5; } && test -s conftest${ac_exeext}; then
   rm -rf conftest*
   eval "ac_cv_lib_$ac_lib_var=yes"
 else
@@ -72350,13 +72584,13 @@ fi
   
     
   echo $ac_n "checking for PDO includes""... $ac_c" 1>&6
-echo "configure:72354: checking for PDO includes" >&5
+echo "configure:72593: checking for PDO includes" >&5
 if eval "test \"`echo '$''{'pdo_inc_path'+set}'`\" = set"; then
   echo $ac_n "(cached) $ac_c" 1>&6
 else
   
     echo $ac_n "checking for PDO includes""... $ac_c" 1>&6
-echo "configure:72360: checking for PDO includes" >&5
+echo "configure:72599: checking for PDO includes" >&5
     if test -f $abs_srcdir/include/php/ext/pdo/php_pdo_driver.h; then
       pdo_inc_path=$abs_srcdir/ext
     elif test -f $abs_srcdir/ext/pdo/php_pdo_driver.h; then
@@ -72635,7 +72869,7 @@ EOF
       ac_obj=$1
       IFS=$old_IFS
       
-      PHP_CLI_OBJS="$PHP_CLI_OBJS $ac_bdir$ac_obj.lo"
+      PHP_GLOBAL_OBJS="$PHP_GLOBAL_OBJS $ac_bdir$ac_obj.lo"
 
       case $ac_src in
         *.c) ac_comp="$b_c_pre $ac_extra $ac_inc $b_c_meta -c $ac_srcdir$ac_src -o $ac_bdir$ac_obj.$b_lo $b_c_post" ;;
@@ -72724,7 +72958,7 @@ fi
 php_with_pdo_odbc=no
 
 echo $ac_n "checking for ODBC v3 support for PDO""... $ac_c" 1>&6
-echo "configure:72728: checking for ODBC v3 support for PDO" >&5
+echo "configure:72967: checking for ODBC v3 support for PDO" >&5
 # Check whether --with-pdo-odbc or --without-pdo-odbc was given.
 if test "${with_pdo_odbc+set}" = set; then
   withval="$with_pdo_odbc"
@@ -72776,13 +73010,13 @@ if test "$PHP_PDO_ODBC" != "no"; then
   
     
   echo $ac_n "checking for PDO includes""... $ac_c" 1>&6
-echo "configure:72780: checking for PDO includes" >&5
+echo "configure:73019: checking for PDO includes" >&5
 if eval "test \"`echo '$''{'pdo_inc_path'+set}'`\" = set"; then
   echo $ac_n "(cached) $ac_c" 1>&6
 else
   
     echo $ac_n "checking for PDO includes""... $ac_c" 1>&6
-echo "configure:72786: checking for PDO includes" >&5
+echo "configure:73025: checking for PDO includes" >&5
     if test -f $abs_srcdir/include/php/ext/pdo/php_pdo_driver.h; then
       pdo_inc_path=$abs_srcdir/ext
     elif test -f $abs_srcdir/ext/pdo/php_pdo_driver.h; then
@@ -72803,7 +73037,7 @@ echo "$ac_t""$pdo_inc_path" 1>&6
   
 
   echo $ac_n "checking for selected PDO ODBC flavour""... $ac_c" 1>&6
-echo "configure:72807: checking for selected PDO ODBC flavour" >&5
+echo "configure:73046: checking for selected PDO ODBC flavour" >&5
 
   pdo_odbc_flavour="`echo $PHP_PDO_ODBC | cut -d, -f1`"
   pdo_odbc_dir="`echo $PHP_PDO_ODBC | cut -d, -f2`"
@@ -72882,7 +73116,7 @@ echo "configure:72807: checking for selected PDO ODBC flavour" >&5
 
   
   echo $ac_n "checking for odbc.h in $PDO_ODBC_INCDIR""... $ac_c" 1>&6
-echo "configure:72886: checking for odbc.h in $PDO_ODBC_INCDIR" >&5
+echo "configure:73125: checking for odbc.h in $PDO_ODBC_INCDIR" >&5
   if test -f "$PDO_ODBC_INCDIR/odbc.h"; then
     php_pdo_have_header=yes
     cat >> confdefs.h <<\EOF
@@ -72896,7 +73130,7 @@ EOF
 
   
   echo $ac_n "checking for odbcsdk.h in $PDO_ODBC_INCDIR""... $ac_c" 1>&6
-echo "configure:72900: checking for odbcsdk.h in $PDO_ODBC_INCDIR" >&5
+echo "configure:73139: checking for odbcsdk.h in $PDO_ODBC_INCDIR" >&5
   if test -f "$PDO_ODBC_INCDIR/odbcsdk.h"; then
     php_pdo_have_header=yes
     cat >> confdefs.h <<\EOF
@@ -72910,7 +73144,7 @@ EOF
 
   
   echo $ac_n "checking for iodbc.h in $PDO_ODBC_INCDIR""... $ac_c" 1>&6
-echo "configure:72914: checking for iodbc.h in $PDO_ODBC_INCDIR" >&5
+echo "configure:73153: checking for iodbc.h in $PDO_ODBC_INCDIR" >&5
   if test -f "$PDO_ODBC_INCDIR/iodbc.h"; then
     php_pdo_have_header=yes
     cat >> confdefs.h <<\EOF
@@ -72924,7 +73158,7 @@ EOF
 
   
   echo $ac_n "checking for sqlunix.h in $PDO_ODBC_INCDIR""... $ac_c" 1>&6
-echo "configure:72928: checking for sqlunix.h in $PDO_ODBC_INCDIR" >&5
+echo "configure:73167: checking for sqlunix.h in $PDO_ODBC_INCDIR" >&5
   if test -f "$PDO_ODBC_INCDIR/sqlunix.h"; then
     php_pdo_have_header=yes
     cat >> confdefs.h <<\EOF
@@ -72938,7 +73172,7 @@ EOF
 
   
   echo $ac_n "checking for sqltypes.h in $PDO_ODBC_INCDIR""... $ac_c" 1>&6
-echo "configure:72942: checking for sqltypes.h in $PDO_ODBC_INCDIR" >&5
+echo "configure:73181: checking for sqltypes.h in $PDO_ODBC_INCDIR" >&5
   if test -f "$PDO_ODBC_INCDIR/sqltypes.h"; then
     php_pdo_have_header=yes
     cat >> confdefs.h <<\EOF
@@ -72952,7 +73186,7 @@ EOF
 
   
   echo $ac_n "checking for sqlucode.h in $PDO_ODBC_INCDIR""... $ac_c" 1>&6
-echo "configure:72956: checking for sqlucode.h in $PDO_ODBC_INCDIR" >&5
+echo "configure:73195: checking for sqlucode.h in $PDO_ODBC_INCDIR" >&5
   if test -f "$PDO_ODBC_INCDIR/sqlucode.h"; then
     php_pdo_have_header=yes
     cat >> confdefs.h <<\EOF
@@ -72966,7 +73200,7 @@ EOF
 
   
   echo $ac_n "checking for sql.h in $PDO_ODBC_INCDIR""... $ac_c" 1>&6
-echo "configure:72970: checking for sql.h in $PDO_ODBC_INCDIR" >&5
+echo "configure:73209: checking for sql.h in $PDO_ODBC_INCDIR" >&5
   if test -f "$PDO_ODBC_INCDIR/sql.h"; then
     php_pdo_have_header=yes
     cat >> confdefs.h <<\EOF
@@ -72980,7 +73214,7 @@ EOF
 
   
   echo $ac_n "checking for isql.h in $PDO_ODBC_INCDIR""... $ac_c" 1>&6
-echo "configure:72984: checking for isql.h in $PDO_ODBC_INCDIR" >&5
+echo "configure:73223: checking for isql.h in $PDO_ODBC_INCDIR" >&5
   if test -f "$PDO_ODBC_INCDIR/isql.h"; then
     php_pdo_have_header=yes
     cat >> confdefs.h <<\EOF
@@ -72994,7 +73228,7 @@ EOF
 
   
   echo $ac_n "checking for sqlext.h in $PDO_ODBC_INCDIR""... $ac_c" 1>&6
-echo "configure:72998: checking for sqlext.h in $PDO_ODBC_INCDIR" >&5
+echo "configure:73237: checking for sqlext.h in $PDO_ODBC_INCDIR" >&5
   if test -f "$PDO_ODBC_INCDIR/sqlext.h"; then
     php_pdo_have_header=yes
     cat >> confdefs.h <<\EOF
@@ -73008,7 +73242,7 @@ EOF
 
   
   echo $ac_n "checking for isqlext.h in $PDO_ODBC_INCDIR""... $ac_c" 1>&6
-echo "configure:73012: checking for isqlext.h in $PDO_ODBC_INCDIR" >&5
+echo "configure:73251: checking for isqlext.h in $PDO_ODBC_INCDIR" >&5
   if test -f "$PDO_ODBC_INCDIR/isqlext.h"; then
     php_pdo_have_header=yes
     cat >> confdefs.h <<\EOF
@@ -73022,7 +73256,7 @@ EOF
 
   
   echo $ac_n "checking for udbcext.h in $PDO_ODBC_INCDIR""... $ac_c" 1>&6
-echo "configure:73026: checking for udbcext.h in $PDO_ODBC_INCDIR" >&5
+echo "configure:73265: checking for udbcext.h in $PDO_ODBC_INCDIR" >&5
   if test -f "$PDO_ODBC_INCDIR/udbcext.h"; then
     php_pdo_have_header=yes
     cat >> confdefs.h <<\EOF
@@ -73036,7 +73270,7 @@ EOF
 
   
   echo $ac_n "checking for sqlcli1.h in $PDO_ODBC_INCDIR""... $ac_c" 1>&6
-echo "configure:73040: checking for sqlcli1.h in $PDO_ODBC_INCDIR" >&5
+echo "configure:73279: checking for sqlcli1.h in $PDO_ODBC_INCDIR" >&5
   if test -f "$PDO_ODBC_INCDIR/sqlcli1.h"; then
     php_pdo_have_header=yes
     cat >> confdefs.h <<\EOF
@@ -73050,7 +73284,7 @@ EOF
 
   
   echo $ac_n "checking for LibraryManager.h in $PDO_ODBC_INCDIR""... $ac_c" 1>&6
-echo "configure:73054: checking for LibraryManager.h in $PDO_ODBC_INCDIR" >&5
+echo "configure:73293: checking for LibraryManager.h in $PDO_ODBC_INCDIR" >&5
   if test -f "$PDO_ODBC_INCDIR/LibraryManager.h"; then
     php_pdo_have_header=yes
     cat >> confdefs.h <<\EOF
@@ -73064,7 +73298,7 @@ EOF
 
   
   echo $ac_n "checking for cli0core.h in $PDO_ODBC_INCDIR""... $ac_c" 1>&6
-echo "configure:73068: checking for cli0core.h in $PDO_ODBC_INCDIR" >&5
+echo "configure:73307: checking for cli0core.h in $PDO_ODBC_INCDIR" >&5
   if test -f "$PDO_ODBC_INCDIR/cli0core.h"; then
     php_pdo_have_header=yes
     cat >> confdefs.h <<\EOF
@@ -73078,7 +73312,7 @@ EOF
 
   
   echo $ac_n "checking for cli0ext.h in $PDO_ODBC_INCDIR""... $ac_c" 1>&6
-echo "configure:73082: checking for cli0ext.h in $PDO_ODBC_INCDIR" >&5
+echo "configure:73321: checking for cli0ext.h in $PDO_ODBC_INCDIR" >&5
   if test -f "$PDO_ODBC_INCDIR/cli0ext.h"; then
     php_pdo_have_header=yes
     cat >> confdefs.h <<\EOF
@@ -73092,7 +73326,7 @@ EOF
 
   
   echo $ac_n "checking for cli0cli.h in $PDO_ODBC_INCDIR""... $ac_c" 1>&6
-echo "configure:73096: checking for cli0cli.h in $PDO_ODBC_INCDIR" >&5
+echo "configure:73335: checking for cli0cli.h in $PDO_ODBC_INCDIR" >&5
   if test -f "$PDO_ODBC_INCDIR/cli0cli.h"; then
     php_pdo_have_header=yes
     cat >> confdefs.h <<\EOF
@@ -73106,7 +73340,7 @@ EOF
 
   
   echo $ac_n "checking for cli0defs.h in $PDO_ODBC_INCDIR""... $ac_c" 1>&6
-echo "configure:73110: checking for cli0defs.h in $PDO_ODBC_INCDIR" >&5
+echo "configure:73349: checking for cli0defs.h in $PDO_ODBC_INCDIR" >&5
   if test -f "$PDO_ODBC_INCDIR/cli0defs.h"; then
     php_pdo_have_header=yes
     cat >> confdefs.h <<\EOF
@@ -73120,7 +73354,7 @@ EOF
 
   
   echo $ac_n "checking for cli0env.h in $PDO_ODBC_INCDIR""... $ac_c" 1>&6
-echo "configure:73124: checking for cli0env.h in $PDO_ODBC_INCDIR" >&5
+echo "configure:73363: checking for cli0env.h in $PDO_ODBC_INCDIR" >&5
   if test -f "$PDO_ODBC_INCDIR/cli0env.h"; then
     php_pdo_have_header=yes
     cat >> confdefs.h <<\EOF
@@ -73326,7 +73560,7 @@ EOF
   done
 
   echo $ac_n "checking for SQLBindCol in -l$pdo_odbc_def_lib""... $ac_c" 1>&6
-echo "configure:73330: checking for SQLBindCol in -l$pdo_odbc_def_lib" >&5
+echo "configure:73569: checking for SQLBindCol in -l$pdo_odbc_def_lib" >&5
 ac_lib_var=`echo $pdo_odbc_def_lib'_'SQLBindCol | sed 'y%./+-%__p_%'`
 if eval "test \"`echo '$''{'ac_cv_lib_$ac_lib_var'+set}'`\" = set"; then
   echo $ac_n "(cached) $ac_c" 1>&6
@@ -73334,7 +73568,7 @@ else
   ac_save_LIBS="$LIBS"
 LIBS="-l$pdo_odbc_def_lib  $LIBS"
 cat > conftest.$ac_ext <<EOF
-#line 73338 "configure"
+#line 73577 "configure"
 #include "confdefs.h"
 /* Override any gcc2 internal prototype to avoid an error.  */
 /* We use char because int might match the return type of a gcc2
@@ -73345,7 +73579,7 @@ int main() {
 SQLBindCol()
 ; return 0; }
 EOF
-if { (eval echo configure:73349: \"$ac_link\") 1>&5; (eval $ac_link) 2>&5; } && test -s conftest${ac_exeext}; then
+if { (eval echo configure:73588: \"$ac_link\") 1>&5; (eval $ac_link) 2>&5; } && test -s conftest${ac_exeext}; then
   rm -rf conftest*
   eval "ac_cv_lib_$ac_lib_var=yes"
 else
@@ -73460,7 +73694,7 @@ if eval "test \"`echo '$ac_cv_lib_'$ac_lib_var`\" = yes"; then
   done
 
   echo $ac_n "checking for SQLAllocHandle in -l$pdo_odbc_def_lib""... $ac_c" 1>&6
-echo "configure:73464: checking for SQLAllocHandle in -l$pdo_odbc_def_lib" >&5
+echo "configure:73703: checking for SQLAllocHandle in -l$pdo_odbc_def_lib" >&5
 ac_lib_var=`echo $pdo_odbc_def_lib'_'SQLAllocHandle | sed 'y%./+-%__p_%'`
 if eval "test \"`echo '$''{'ac_cv_lib_$ac_lib_var'+set}'`\" = set"; then
   echo $ac_n "(cached) $ac_c" 1>&6
@@ -73468,7 +73702,7 @@ else
   ac_save_LIBS="$LIBS"
 LIBS="-l$pdo_odbc_def_lib  $LIBS"
 cat > conftest.$ac_ext <<EOF
-#line 73472 "configure"
+#line 73711 "configure"
 #include "confdefs.h"
 /* Override any gcc2 internal prototype to avoid an error.  */
 /* We use char because int might match the return type of a gcc2
@@ -73479,7 +73713,7 @@ int main() {
 SQLAllocHandle()
 ; return 0; }
 EOF
-if { (eval echo configure:73483: \"$ac_link\") 1>&5; (eval $ac_link) 2>&5; } && test -s conftest${ac_exeext}; then
+if { (eval echo configure:73722: \"$ac_link\") 1>&5; (eval $ac_link) 2>&5; } && test -s conftest${ac_exeext}; then
   rm -rf conftest*
   eval "ac_cv_lib_$ac_lib_var=yes"
 else
@@ -73789,7 +74023,7 @@ EOF
       ac_obj=$1
       IFS=$old_IFS
       
-      PHP_CLI_OBJS="$PHP_CLI_OBJS $ac_bdir$ac_obj.lo"
+      PHP_GLOBAL_OBJS="$PHP_GLOBAL_OBJS $ac_bdir$ac_obj.lo"
 
       case $ac_src in
         *.c) ac_comp="$b_c_pre $ac_extra $ac_inc $b_c_meta -c $ac_srcdir$ac_src -o $ac_bdir$ac_obj.$b_lo $b_c_post" ;;
@@ -73853,7 +74087,7 @@ fi
 php_with_pdo_pgsql=no
 
 echo $ac_n "checking for PostgreSQL support for PDO""... $ac_c" 1>&6
-echo "configure:73857: checking for PostgreSQL support for PDO" >&5
+echo "configure:74096: checking for PostgreSQL support for PDO" >&5
 # Check whether --with-pdo-pgsql or --without-pdo-pgsql was given.
 if test "${with_pdo_pgsql+set}" = set; then
   withval="$with_pdo_pgsql"
@@ -73912,7 +74146,7 @@ if test "$PHP_PDO_PGSQL" != "no"; then
 
 
   echo $ac_n "checking for pg_config""... $ac_c" 1>&6
-echo "configure:73916: checking for pg_config" >&5
+echo "configure:74155: checking for pg_config" >&5
   for i in $PHP_PDO_PGSQL $PHP_PDO_PGSQL/bin /usr/local/pgsql/bin /usr/local/bin /usr/bin ""; do
     if test -x $i/pg_config; then
       PG_CONFIG="$i/pg_config"
@@ -73976,14 +74210,14 @@ EOF
 
 
   echo $ac_n "checking for openssl dependencies""... $ac_c" 1>&6
-echo "configure:73980: checking for openssl dependencies" >&5
+echo "configure:74219: checking for openssl dependencies" >&5
   grep openssl $PGSQL_INCLUDE/libpq-fe.h >/dev/null 2>&1
   if test $? -eq 0 ; then
     echo "$ac_t""yes" 1>&6
         # Extract the first word of "pkg-config", so it can be a program name with args.
 set dummy pkg-config; ac_word=$2
 echo $ac_n "checking for $ac_word""... $ac_c" 1>&6
-echo "configure:73987: checking for $ac_word" >&5
+echo "configure:74226: checking for $ac_word" >&5
 if eval "test \"`echo '$''{'ac_cv_path_PKG_CONFIG'+set}'`\" = set"; then
   echo $ac_n "(cached) $ac_c" 1>&6
 else
@@ -74027,7 +74261,7 @@ fi
   old_LDFLAGS=$LDFLAGS
   LDFLAGS="-L$PGSQL_LIBDIR $LDFLAGS"
   echo $ac_n "checking for PQparameterStatus in -lpq""... $ac_c" 1>&6
-echo "configure:74031: checking for PQparameterStatus in -lpq" >&5
+echo "configure:74270: checking for PQparameterStatus in -lpq" >&5
 ac_lib_var=`echo pq'_'PQparameterStatus | sed 'y%./+-%__p_%'`
 if eval "test \"`echo '$''{'ac_cv_lib_$ac_lib_var'+set}'`\" = set"; then
   echo $ac_n "(cached) $ac_c" 1>&6
@@ -74035,7 +74269,7 @@ else
   ac_save_LIBS="$LIBS"
 LIBS="-lpq  $LIBS"
 cat > conftest.$ac_ext <<EOF
-#line 74039 "configure"
+#line 74278 "configure"
 #include "confdefs.h"
 /* Override any gcc2 internal prototype to avoid an error.  */
 /* We use char because int might match the return type of a gcc2
@@ -74046,7 +74280,7 @@ int main() {
 PQparameterStatus()
 ; return 0; }
 EOF
-if { (eval echo configure:74050: \"$ac_link\") 1>&5; (eval $ac_link) 2>&5; } && test -s conftest${ac_exeext}; then
+if { (eval echo configure:74289: \"$ac_link\") 1>&5; (eval $ac_link) 2>&5; } && test -s conftest${ac_exeext}; then
   rm -rf conftest*
   eval "ac_cv_lib_$ac_lib_var=yes"
 else
@@ -74075,7 +74309,7 @@ fi
 
 
   echo $ac_n "checking for PQprepare in -lpq""... $ac_c" 1>&6
-echo "configure:74079: checking for PQprepare in -lpq" >&5
+echo "configure:74318: checking for PQprepare in -lpq" >&5
 ac_lib_var=`echo pq'_'PQprepare | sed 'y%./+-%__p_%'`
 if eval "test \"`echo '$''{'ac_cv_lib_$ac_lib_var'+set}'`\" = set"; then
   echo $ac_n "(cached) $ac_c" 1>&6
@@ -74083,7 +74317,7 @@ else
   ac_save_LIBS="$LIBS"
 LIBS="-lpq  $LIBS"
 cat > conftest.$ac_ext <<EOF
-#line 74087 "configure"
+#line 74326 "configure"
 #include "confdefs.h"
 /* Override any gcc2 internal prototype to avoid an error.  */
 /* We use char because int might match the return type of a gcc2
@@ -74094,7 +74328,7 @@ int main() {
 PQprepare()
 ; return 0; }
 EOF
-if { (eval echo configure:74098: \"$ac_link\") 1>&5; (eval $ac_link) 2>&5; } && test -s conftest${ac_exeext}; then
+if { (eval echo configure:74337: \"$ac_link\") 1>&5; (eval $ac_link) 2>&5; } && test -s conftest${ac_exeext}; then
   rm -rf conftest*
   eval "ac_cv_lib_$ac_lib_var=yes"
 else
@@ -74118,7 +74352,7 @@ else
 fi
 
   echo $ac_n "checking for PQescapeStringConn in -lpq""... $ac_c" 1>&6
-echo "configure:74122: checking for PQescapeStringConn in -lpq" >&5
+echo "configure:74361: checking for PQescapeStringConn in -lpq" >&5
 ac_lib_var=`echo pq'_'PQescapeStringConn | sed 'y%./+-%__p_%'`
 if eval "test \"`echo '$''{'ac_cv_lib_$ac_lib_var'+set}'`\" = set"; then
   echo $ac_n "(cached) $ac_c" 1>&6
@@ -74126,7 +74360,7 @@ else
   ac_save_LIBS="$LIBS"
 LIBS="-lpq  $LIBS"
 cat > conftest.$ac_ext <<EOF
-#line 74130 "configure"
+#line 74369 "configure"
 #include "confdefs.h"
 /* Override any gcc2 internal prototype to avoid an error.  */
 /* We use char because int might match the return type of a gcc2
@@ -74137,7 +74371,7 @@ int main() {
 PQescapeStringConn()
 ; return 0; }
 EOF
-if { (eval echo configure:74141: \"$ac_link\") 1>&5; (eval $ac_link) 2>&5; } && test -s conftest${ac_exeext}; then
+if { (eval echo configure:74380: \"$ac_link\") 1>&5; (eval $ac_link) 2>&5; } && test -s conftest${ac_exeext}; then
   rm -rf conftest*
   eval "ac_cv_lib_$ac_lib_var=yes"
 else
@@ -74161,7 +74395,7 @@ else
 fi
 
   echo $ac_n "checking for PQescapeByteaConn in -lpq""... $ac_c" 1>&6
-echo "configure:74165: checking for PQescapeByteaConn in -lpq" >&5
+echo "configure:74404: checking for PQescapeByteaConn in -lpq" >&5
 ac_lib_var=`echo pq'_'PQescapeByteaConn | sed 'y%./+-%__p_%'`
 if eval "test \"`echo '$''{'ac_cv_lib_$ac_lib_var'+set}'`\" = set"; then
   echo $ac_n "(cached) $ac_c" 1>&6
@@ -74169,7 +74403,7 @@ else
   ac_save_LIBS="$LIBS"
 LIBS="-lpq  $LIBS"
 cat > conftest.$ac_ext <<EOF
-#line 74173 "configure"
+#line 74412 "configure"
 #include "confdefs.h"
 /* Override any gcc2 internal prototype to avoid an error.  */
 /* We use char because int might match the return type of a gcc2
@@ -74180,7 +74414,7 @@ int main() {
 PQescapeByteaConn()
 ; return 0; }
 EOF
-if { (eval echo configure:74184: \"$ac_link\") 1>&5; (eval $ac_link) 2>&5; } && test -s conftest${ac_exeext}; then
+if { (eval echo configure:74423: \"$ac_link\") 1>&5; (eval $ac_link) 2>&5; } && test -s conftest${ac_exeext}; then
   rm -rf conftest*
   eval "ac_cv_lib_$ac_lib_var=yes"
 else
@@ -74205,7 +74439,7 @@ fi
 
 
   echo $ac_n "checking for pg_encoding_to_char in -lpq""... $ac_c" 1>&6
-echo "configure:74209: checking for pg_encoding_to_char in -lpq" >&5
+echo "configure:74448: checking for pg_encoding_to_char in -lpq" >&5
 ac_lib_var=`echo pq'_'pg_encoding_to_char | sed 'y%./+-%__p_%'`
 if eval "test \"`echo '$''{'ac_cv_lib_$ac_lib_var'+set}'`\" = set"; then
   echo $ac_n "(cached) $ac_c" 1>&6
@@ -74213,7 +74447,7 @@ else
   ac_save_LIBS="$LIBS"
 LIBS="-lpq  $LIBS"
 cat > conftest.$ac_ext <<EOF
-#line 74217 "configure"
+#line 74456 "configure"
 #include "confdefs.h"
 /* Override any gcc2 internal prototype to avoid an error.  */
 /* We use char because int might match the return type of a gcc2
@@ -74224,7 +74458,7 @@ int main() {
 pg_encoding_to_char()
 ; return 0; }
 EOF
-if { (eval echo configure:74228: \"$ac_link\") 1>&5; (eval $ac_link) 2>&5; } && test -s conftest${ac_exeext}; then
+if { (eval echo configure:74467: \"$ac_link\") 1>&5; (eval $ac_link) 2>&5; } && test -s conftest${ac_exeext}; then
   rm -rf conftest*
   eval "ac_cv_lib_$ac_lib_var=yes"
 else
@@ -74388,13 +74622,13 @@ fi
   
     
   echo $ac_n "checking for PDO includes""... $ac_c" 1>&6
-echo "configure:74392: checking for PDO includes" >&5
+echo "configure:74631: checking for PDO includes" >&5
 if eval "test \"`echo '$''{'pdo_inc_path'+set}'`\" = set"; then
   echo $ac_n "(cached) $ac_c" 1>&6
 else
   
     echo $ac_n "checking for PDO includes""... $ac_c" 1>&6
-echo "configure:74398: checking for PDO includes" >&5
+echo "configure:74637: checking for PDO includes" >&5
     if test -f $abs_srcdir/include/php/ext/pdo/php_pdo_driver.h; then
       pdo_inc_path=$abs_srcdir/ext
     elif test -f $abs_srcdir/ext/pdo/php_pdo_driver.h; then
@@ -74673,7 +74907,7 @@ EOF
       ac_obj=$1
       IFS=$old_IFS
       
-      PHP_CLI_OBJS="$PHP_CLI_OBJS $ac_bdir$ac_obj.lo"
+      PHP_GLOBAL_OBJS="$PHP_GLOBAL_OBJS $ac_bdir$ac_obj.lo"
 
       case $ac_src in
         *.c) ac_comp="$b_c_pre $ac_extra $ac_inc $b_c_meta -c $ac_srcdir$ac_src -o $ac_bdir$ac_obj.$b_lo $b_c_post" ;;
@@ -74734,7 +74968,7 @@ fi
 php_with_pdo_sqlite=$PHP_PDO
 
 echo $ac_n "checking for sqlite 3 support for PDO""... $ac_c" 1>&6
-echo "configure:74738: checking for sqlite 3 support for PDO" >&5
+echo "configure:74977: checking for sqlite 3 support for PDO" >&5
 # Check whether --with-pdo-sqlite or --without-pdo-sqlite was given.
 if test "${with_pdo_sqlite+set}" = set; then
   withval="$with_pdo_sqlite"
@@ -74783,13 +75017,13 @@ if test "$PHP_PDO_SQLITE" != "no"; then
   
     
   echo $ac_n "checking for PDO includes""... $ac_c" 1>&6
-echo "configure:74787: checking for PDO includes" >&5
+echo "configure:75026: checking for PDO includes" >&5
 if eval "test \"`echo '$''{'pdo_inc_path'+set}'`\" = set"; then
   echo $ac_n "(cached) $ac_c" 1>&6
 else
   
     echo $ac_n "checking for PDO includes""... $ac_c" 1>&6
-echo "configure:74793: checking for PDO includes" >&5
+echo "configure:75032: checking for PDO includes" >&5
     if test -f $abs_srcdir/include/php/ext/pdo/php_pdo_driver.h; then
       pdo_inc_path=$abs_srcdir/ext
     elif test -f $abs_srcdir/ext/pdo/php_pdo_driver.h; then
@@ -74818,7 +75052,7 @@ echo "$ac_t""$pdo_inc_path" 1>&6
       PDO_SQLITE_DIR=$PHP_PDO_SQLITE
     else # search default path list
       echo $ac_n "checking for sqlite3 files in default path""... $ac_c" 1>&6
-echo "configure:74822: checking for sqlite3 files in default path" >&5
+echo "configure:75061: checking for sqlite3 files in default path" >&5
       for i in $SEARCH_PATH ; do
         if test -r $i/$SEARCH_FOR; then
           PDO_SQLITE_DIR=$i
@@ -74964,7 +75198,7 @@ echo "configure:74822: checking for sqlite3 files in default path" >&5
   done
 
   echo $ac_n "checking for $LIBSYMBOL in -l$LIBNAME""... $ac_c" 1>&6
-echo "configure:74968: checking for $LIBSYMBOL in -l$LIBNAME" >&5
+echo "configure:75207: checking for $LIBSYMBOL in -l$LIBNAME" >&5
 ac_lib_var=`echo $LIBNAME'_'$LIBSYMBOL | sed 'y%./+-%__p_%'`
 if eval "test \"`echo '$''{'ac_cv_lib_$ac_lib_var'+set}'`\" = set"; then
   echo $ac_n "(cached) $ac_c" 1>&6
@@ -74972,7 +75206,7 @@ else
   ac_save_LIBS="$LIBS"
 LIBS="-l$LIBNAME  $LIBS"
 cat > conftest.$ac_ext <<EOF
-#line 74976 "configure"
+#line 75215 "configure"
 #include "confdefs.h"
 /* Override any gcc2 internal prototype to avoid an error.  */
 /* We use char because int might match the return type of a gcc2
@@ -74983,7 +75217,7 @@ int main() {
 $LIBSYMBOL()
 ; return 0; }
 EOF
-if { (eval echo configure:74987: \"$ac_link\") 1>&5; (eval $ac_link) 2>&5; } && test -s conftest${ac_exeext}; then
+if { (eval echo configure:75226: \"$ac_link\") 1>&5; (eval $ac_link) 2>&5; } && test -s conftest${ac_exeext}; then
   rm -rf conftest*
   eval "ac_cv_lib_$ac_lib_var=yes"
 else
@@ -75213,7 +75447,7 @@ fi
   done
 
   echo $ac_n "checking for sqlite3_key in -lsqlite3""... $ac_c" 1>&6
-echo "configure:75217: checking for sqlite3_key in -lsqlite3" >&5
+echo "configure:75456: checking for sqlite3_key in -lsqlite3" >&5
 ac_lib_var=`echo sqlite3'_'sqlite3_key | sed 'y%./+-%__p_%'`
 if eval "test \"`echo '$''{'ac_cv_lib_$ac_lib_var'+set}'`\" = set"; then
   echo $ac_n "(cached) $ac_c" 1>&6
@@ -75221,7 +75455,7 @@ else
   ac_save_LIBS="$LIBS"
 LIBS="-lsqlite3  $LIBS"
 cat > conftest.$ac_ext <<EOF
-#line 75225 "configure"
+#line 75464 "configure"
 #include "confdefs.h"
 /* Override any gcc2 internal prototype to avoid an error.  */
 /* We use char because int might match the return type of a gcc2
@@ -75232,7 +75466,7 @@ int main() {
 sqlite3_key()
 ; return 0; }
 EOF
-if { (eval echo configure:75236: \"$ac_link\") 1>&5; (eval $ac_link) 2>&5; } && test -s conftest${ac_exeext}; then
+if { (eval echo configure:75475: \"$ac_link\") 1>&5; (eval $ac_link) 2>&5; } && test -s conftest${ac_exeext}; then
   rm -rf conftest*
   eval "ac_cv_lib_$ac_lib_var=yes"
 else
@@ -75530,7 +75764,7 @@ EOF
       ac_obj=$1
       IFS=$old_IFS
       
-      PHP_CLI_OBJS="$PHP_CLI_OBJS $ac_bdir$ac_obj.lo"
+      PHP_GLOBAL_OBJS="$PHP_GLOBAL_OBJS $ac_bdir$ac_obj.lo"
 
       case $ac_src in
         *.c) ac_comp="$b_c_pre $ac_extra $ac_inc $b_c_meta -c $ac_srcdir$ac_src -o $ac_bdir$ac_obj.$b_lo $b_c_post" ;;
@@ -75882,7 +76116,7 @@ EOF
       ac_obj=$1
       IFS=$old_IFS
       
-      PHP_CLI_OBJS="$PHP_CLI_OBJS $ac_bdir$ac_obj.lo"
+      PHP_GLOBAL_OBJS="$PHP_GLOBAL_OBJS $ac_bdir$ac_obj.lo"
 
       case $ac_src in
         *.c) ac_comp="$b_c_pre $ac_extra $ac_inc $b_c_meta -c $ac_srcdir$ac_src -o $ac_bdir$ac_obj.$b_lo $b_c_post" ;;
@@ -75973,12 +76207,12 @@ but you've either not enabled sqlite3, or have disabled it.
       for ac_func in usleep nanosleep
 do
 echo $ac_n "checking for $ac_func""... $ac_c" 1>&6
-echo "configure:75977: checking for $ac_func" >&5
+echo "configure:76216: checking for $ac_func" >&5
 if eval "test \"`echo '$''{'ac_cv_func_$ac_func'+set}'`\" = set"; then
   echo $ac_n "(cached) $ac_c" 1>&6
 else
   cat > conftest.$ac_ext <<EOF
-#line 75982 "configure"
+#line 76221 "configure"
 #include "confdefs.h"
 /* System header to define __stub macros and hopefully few prototypes,
     which can conflict with char $ac_func(); below.  */
@@ -76001,7 +76235,7 @@ $ac_func();
 
 ; return 0; }
 EOF
-if { (eval echo configure:76005: \"$ac_link\") 1>&5; (eval $ac_link) 2>&5; } && test -s conftest${ac_exeext}; then
+if { (eval echo configure:76244: \"$ac_link\") 1>&5; (eval $ac_link) 2>&5; } && test -s conftest${ac_exeext}; then
   rm -rf conftest*
   eval "ac_cv_func_$ac_func=yes"
 else
@@ -76029,17 +76263,17 @@ done
 do
 ac_safe=`echo "$ac_hdr" | sed 'y%./+-%__p_%'`
 echo $ac_n "checking for $ac_hdr""... $ac_c" 1>&6
-echo "configure:76033: checking for $ac_hdr" >&5
+echo "configure:76272: checking for $ac_hdr" >&5
 if eval "test \"`echo '$''{'ac_cv_header_$ac_safe'+set}'`\" = set"; then
   echo $ac_n "(cached) $ac_c" 1>&6
 else
   cat > conftest.$ac_ext <<EOF
-#line 76038 "configure"
+#line 76277 "configure"
 #include "confdefs.h"
 #include <$ac_hdr>
 EOF
 ac_try="$ac_cpp conftest.$ac_ext >/dev/null 2>conftest.out"
-{ (eval echo configure:76043: \"$ac_try\") 1>&5; (eval $ac_try) 2>&5; }
+{ (eval echo configure:76282: \"$ac_try\") 1>&5; (eval $ac_try) 2>&5; }
 ac_err=`grep -v '^ *+' conftest.out | grep -v "^conftest.${ac_ext}\$"`
 if test -z "$ac_err"; then
   rm -rf conftest*
@@ -76163,7 +76397,7 @@ done
   done
 
   echo $ac_n "checking for fdatasync in -lrt""... $ac_c" 1>&6
-echo "configure:76167: checking for fdatasync in -lrt" >&5
+echo "configure:76406: checking for fdatasync in -lrt" >&5
 ac_lib_var=`echo rt'_'fdatasync | sed 'y%./+-%__p_%'`
 if eval "test \"`echo '$''{'ac_cv_lib_$ac_lib_var'+set}'`\" = set"; then
   echo $ac_n "(cached) $ac_c" 1>&6
@@ -76171,7 +76405,7 @@ else
   ac_save_LIBS="$LIBS"
 LIBS="-lrt  $LIBS"
 cat > conftest.$ac_ext <<EOF
-#line 76175 "configure"
+#line 76414 "configure"
 #include "confdefs.h"
 /* Override any gcc2 internal prototype to avoid an error.  */
 /* We use char because int might match the return type of a gcc2
@@ -76182,7 +76416,7 @@ int main() {
 fdatasync()
 ; return 0; }
 EOF
-if { (eval echo configure:76186: \"$ac_link\") 1>&5; (eval $ac_link) 2>&5; } && test -s conftest${ac_exeext}; then
+if { (eval echo configure:76425: \"$ac_link\") 1>&5; (eval $ac_link) 2>&5; } && test -s conftest${ac_exeext}; then
   rm -rf conftest*
   eval "ac_cv_lib_$ac_lib_var=yes"
 else
@@ -76263,7 +76497,7 @@ fi
 php_with_pgsql=no
 
 echo $ac_n "checking for PostgreSQL support""... $ac_c" 1>&6
-echo "configure:76267: checking for PostgreSQL support" >&5
+echo "configure:76506: checking for PostgreSQL support" >&5
 # Check whether --with-pgsql or --without-pgsql was given.
 if test "${with_pgsql+set}" = set; then
   withval="$with_pgsql"
@@ -76317,7 +76551,7 @@ if test "$PHP_PGSQL" != "no"; then
 
 
   echo $ac_n "checking for pg_config""... $ac_c" 1>&6
-echo "configure:76321: checking for pg_config" >&5
+echo "configure:76560: checking for pg_config" >&5
   for i in $PHP_PGSQL $PHP_PGSQL/bin /usr/local/pgsql/bin /usr/local/bin /usr/bin ""; do
 	if test -x $i/pg_config; then
       PG_CONFIG="$i/pg_config"
@@ -76385,7 +76619,7 @@ EOF
   old_LDFLAGS=$LDFLAGS
   LDFLAGS="-L$PGSQL_LIBDIR $LDFLAGS"
   echo $ac_n "checking for PQescapeString in -lpq""... $ac_c" 1>&6
-echo "configure:76389: checking for PQescapeString in -lpq" >&5
+echo "configure:76628: checking for PQescapeString in -lpq" >&5
 ac_lib_var=`echo pq'_'PQescapeString | sed 'y%./+-%__p_%'`
 if eval "test \"`echo '$''{'ac_cv_lib_$ac_lib_var'+set}'`\" = set"; then
   echo $ac_n "(cached) $ac_c" 1>&6
@@ -76393,7 +76627,7 @@ else
   ac_save_LIBS="$LIBS"
 LIBS="-lpq  $LIBS"
 cat > conftest.$ac_ext <<EOF
-#line 76397 "configure"
+#line 76636 "configure"
 #include "confdefs.h"
 /* Override any gcc2 internal prototype to avoid an error.  */
 /* We use char because int might match the return type of a gcc2
@@ -76404,7 +76638,7 @@ int main() {
 PQescapeString()
 ; return 0; }
 EOF
-if { (eval echo configure:76408: \"$ac_link\") 1>&5; (eval $ac_link) 2>&5; } && test -s conftest${ac_exeext}; then
+if { (eval echo configure:76647: \"$ac_link\") 1>&5; (eval $ac_link) 2>&5; } && test -s conftest${ac_exeext}; then
   rm -rf conftest*
   eval "ac_cv_lib_$ac_lib_var=yes"
 else
@@ -76428,7 +76662,7 @@ else
 fi
 
   echo $ac_n "checking for PQunescapeBytea in -lpq""... $ac_c" 1>&6
-echo "configure:76432: checking for PQunescapeBytea in -lpq" >&5
+echo "configure:76671: checking for PQunescapeBytea in -lpq" >&5
 ac_lib_var=`echo pq'_'PQunescapeBytea | sed 'y%./+-%__p_%'`
 if eval "test \"`echo '$''{'ac_cv_lib_$ac_lib_var'+set}'`\" = set"; then
   echo $ac_n "(cached) $ac_c" 1>&6
@@ -76436,7 +76670,7 @@ else
   ac_save_LIBS="$LIBS"
 LIBS="-lpq  $LIBS"
 cat > conftest.$ac_ext <<EOF
-#line 76440 "configure"
+#line 76679 "configure"
 #include "confdefs.h"
 /* Override any gcc2 internal prototype to avoid an error.  */
 /* We use char because int might match the return type of a gcc2
@@ -76447,7 +76681,7 @@ int main() {
 PQunescapeBytea()
 ; return 0; }
 EOF
-if { (eval echo configure:76451: \"$ac_link\") 1>&5; (eval $ac_link) 2>&5; } && test -s conftest${ac_exeext}; then
+if { (eval echo configure:76690: \"$ac_link\") 1>&5; (eval $ac_link) 2>&5; } && test -s conftest${ac_exeext}; then
   rm -rf conftest*
   eval "ac_cv_lib_$ac_lib_var=yes"
 else
@@ -76471,7 +76705,7 @@ else
 fi
 
   echo $ac_n "checking for PQsetnonblocking in -lpq""... $ac_c" 1>&6
-echo "configure:76475: checking for PQsetnonblocking in -lpq" >&5
+echo "configure:76714: checking for PQsetnonblocking in -lpq" >&5
 ac_lib_var=`echo pq'_'PQsetnonblocking | sed 'y%./+-%__p_%'`
 if eval "test \"`echo '$''{'ac_cv_lib_$ac_lib_var'+set}'`\" = set"; then
   echo $ac_n "(cached) $ac_c" 1>&6
@@ -76479,7 +76713,7 @@ else
   ac_save_LIBS="$LIBS"
 LIBS="-lpq  $LIBS"
 cat > conftest.$ac_ext <<EOF
-#line 76483 "configure"
+#line 76722 "configure"
 #include "confdefs.h"
 /* Override any gcc2 internal prototype to avoid an error.  */
 /* We use char because int might match the return type of a gcc2
@@ -76490,7 +76724,7 @@ int main() {
 PQsetnonblocking()
 ; return 0; }
 EOF
-if { (eval echo configure:76494: \"$ac_link\") 1>&5; (eval $ac_link) 2>&5; } && test -s conftest${ac_exeext}; then
+if { (eval echo configure:76733: \"$ac_link\") 1>&5; (eval $ac_link) 2>&5; } && test -s conftest${ac_exeext}; then
   rm -rf conftest*
   eval "ac_cv_lib_$ac_lib_var=yes"
 else
@@ -76514,7 +76748,7 @@ else
 fi
 
   echo $ac_n "checking for PQcmdTuples in -lpq""... $ac_c" 1>&6
-echo "configure:76518: checking for PQcmdTuples in -lpq" >&5
+echo "configure:76757: checking for PQcmdTuples in -lpq" >&5
 ac_lib_var=`echo pq'_'PQcmdTuples | sed 'y%./+-%__p_%'`
 if eval "test \"`echo '$''{'ac_cv_lib_$ac_lib_var'+set}'`\" = set"; then
   echo $ac_n "(cached) $ac_c" 1>&6
@@ -76522,7 +76756,7 @@ else
   ac_save_LIBS="$LIBS"
 LIBS="-lpq  $LIBS"
 cat > conftest.$ac_ext <<EOF
-#line 76526 "configure"
+#line 76765 "configure"
 #include "confdefs.h"
 /* Override any gcc2 internal prototype to avoid an error.  */
 /* We use char because int might match the return type of a gcc2
@@ -76533,7 +76767,7 @@ int main() {
 PQcmdTuples()
 ; return 0; }
 EOF
-if { (eval echo configure:76537: \"$ac_link\") 1>&5; (eval $ac_link) 2>&5; } && test -s conftest${ac_exeext}; then
+if { (eval echo configure:76776: \"$ac_link\") 1>&5; (eval $ac_link) 2>&5; } && test -s conftest${ac_exeext}; then
   rm -rf conftest*
   eval "ac_cv_lib_$ac_lib_var=yes"
 else
@@ -76557,7 +76791,7 @@ else
 fi
 
   echo $ac_n "checking for PQoidValue in -lpq""... $ac_c" 1>&6
-echo "configure:76561: checking for PQoidValue in -lpq" >&5
+echo "configure:76800: checking for PQoidValue in -lpq" >&5
 ac_lib_var=`echo pq'_'PQoidValue | sed 'y%./+-%__p_%'`
 if eval "test \"`echo '$''{'ac_cv_lib_$ac_lib_var'+set}'`\" = set"; then
   echo $ac_n "(cached) $ac_c" 1>&6
@@ -76565,7 +76799,7 @@ else
   ac_save_LIBS="$LIBS"
 LIBS="-lpq  $LIBS"
 cat > conftest.$ac_ext <<EOF
-#line 76569 "configure"
+#line 76808 "configure"
 #include "confdefs.h"
 /* Override any gcc2 internal prototype to avoid an error.  */
 /* We use char because int might match the return type of a gcc2
@@ -76576,7 +76810,7 @@ int main() {
 PQoidValue()
 ; return 0; }
 EOF
-if { (eval echo configure:76580: \"$ac_link\") 1>&5; (eval $ac_link) 2>&5; } && test -s conftest${ac_exeext}; then
+if { (eval echo configure:76819: \"$ac_link\") 1>&5; (eval $ac_link) 2>&5; } && test -s conftest${ac_exeext}; then
   rm -rf conftest*
   eval "ac_cv_lib_$ac_lib_var=yes"
 else
@@ -76600,7 +76834,7 @@ else
 fi
 
   echo $ac_n "checking for PQclientEncoding in -lpq""... $ac_c" 1>&6
-echo "configure:76604: checking for PQclientEncoding in -lpq" >&5
+echo "configure:76843: checking for PQclientEncoding in -lpq" >&5
 ac_lib_var=`echo pq'_'PQclientEncoding | sed 'y%./+-%__p_%'`
 if eval "test \"`echo '$''{'ac_cv_lib_$ac_lib_var'+set}'`\" = set"; then
   echo $ac_n "(cached) $ac_c" 1>&6
@@ -76608,7 +76842,7 @@ else
   ac_save_LIBS="$LIBS"
 LIBS="-lpq  $LIBS"
 cat > conftest.$ac_ext <<EOF
-#line 76612 "configure"
+#line 76851 "configure"
 #include "confdefs.h"
 /* Override any gcc2 internal prototype to avoid an error.  */
 /* We use char because int might match the return type of a gcc2
@@ -76619,7 +76853,7 @@ int main() {
 PQclientEncoding()
 ; return 0; }
 EOF
-if { (eval echo configure:76623: \"$ac_link\") 1>&5; (eval $ac_link) 2>&5; } && test -s conftest${ac_exeext}; then
+if { (eval echo configure:76862: \"$ac_link\") 1>&5; (eval $ac_link) 2>&5; } && test -s conftest${ac_exeext}; then
   rm -rf conftest*
   eval "ac_cv_lib_$ac_lib_var=yes"
 else
@@ -76643,7 +76877,7 @@ else
 fi
 
   echo $ac_n "checking for PQparameterStatus in -lpq""... $ac_c" 1>&6
-echo "configure:76647: checking for PQparameterStatus in -lpq" >&5
+echo "configure:76886: checking for PQparameterStatus in -lpq" >&5
 ac_lib_var=`echo pq'_'PQparameterStatus | sed 'y%./+-%__p_%'`
 if eval "test \"`echo '$''{'ac_cv_lib_$ac_lib_var'+set}'`\" = set"; then
   echo $ac_n "(cached) $ac_c" 1>&6
@@ -76651,7 +76885,7 @@ else
   ac_save_LIBS="$LIBS"
 LIBS="-lpq  $LIBS"
 cat > conftest.$ac_ext <<EOF
-#line 76655 "configure"
+#line 76894 "configure"
 #include "confdefs.h"
 /* Override any gcc2 internal prototype to avoid an error.  */
 /* We use char because int might match the return type of a gcc2
@@ -76662,7 +76896,7 @@ int main() {
 PQparameterStatus()
 ; return 0; }
 EOF
-if { (eval echo configure:76666: \"$ac_link\") 1>&5; (eval $ac_link) 2>&5; } && test -s conftest${ac_exeext}; then
+if { (eval echo configure:76905: \"$ac_link\") 1>&5; (eval $ac_link) 2>&5; } && test -s conftest${ac_exeext}; then
   rm -rf conftest*
   eval "ac_cv_lib_$ac_lib_var=yes"
 else
@@ -76686,7 +76920,7 @@ else
 fi
 
   echo $ac_n "checking for PQprotocolVersion in -lpq""... $ac_c" 1>&6
-echo "configure:76690: checking for PQprotocolVersion in -lpq" >&5
+echo "configure:76929: checking for PQprotocolVersion in -lpq" >&5
 ac_lib_var=`echo pq'_'PQprotocolVersion | sed 'y%./+-%__p_%'`
 if eval "test \"`echo '$''{'ac_cv_lib_$ac_lib_var'+set}'`\" = set"; then
   echo $ac_n "(cached) $ac_c" 1>&6
@@ -76694,7 +76928,7 @@ else
   ac_save_LIBS="$LIBS"
 LIBS="-lpq  $LIBS"
 cat > conftest.$ac_ext <<EOF
-#line 76698 "configure"
+#line 76937 "configure"
 #include "confdefs.h"
 /* Override any gcc2 internal prototype to avoid an error.  */
 /* We use char because int might match the return type of a gcc2
@@ -76705,7 +76939,7 @@ int main() {
 PQprotocolVersion()
 ; return 0; }
 EOF
-if { (eval echo configure:76709: \"$ac_link\") 1>&5; (eval $ac_link) 2>&5; } && test -s conftest${ac_exeext}; then
+if { (eval echo configure:76948: \"$ac_link\") 1>&5; (eval $ac_link) 2>&5; } && test -s conftest${ac_exeext}; then
   rm -rf conftest*
   eval "ac_cv_lib_$ac_lib_var=yes"
 else
@@ -76729,7 +76963,7 @@ else
 fi
 
   echo $ac_n "checking for PQtransactionStatus in -lpq""... $ac_c" 1>&6
-echo "configure:76733: checking for PQtransactionStatus in -lpq" >&5
+echo "configure:76972: checking for PQtransactionStatus in -lpq" >&5
 ac_lib_var=`echo pq'_'PQtransactionStatus | sed 'y%./+-%__p_%'`
 if eval "test \"`echo '$''{'ac_cv_lib_$ac_lib_var'+set}'`\" = set"; then
   echo $ac_n "(cached) $ac_c" 1>&6
@@ -76737,7 +76971,7 @@ else
   ac_save_LIBS="$LIBS"
 LIBS="-lpq  $LIBS"
 cat > conftest.$ac_ext <<EOF
-#line 76741 "configure"
+#line 76980 "configure"
 #include "confdefs.h"
 /* Override any gcc2 internal prototype to avoid an error.  */
 /* We use char because int might match the return type of a gcc2
@@ -76748,7 +76982,7 @@ int main() {
 PQtransactionStatus()
 ; return 0; }
 EOF
-if { (eval echo configure:76752: \"$ac_link\") 1>&5; (eval $ac_link) 2>&5; } && test -s conftest${ac_exeext}; then
+if { (eval echo configure:76991: \"$ac_link\") 1>&5; (eval $ac_link) 2>&5; } && test -s conftest${ac_exeext}; then
   rm -rf conftest*
   eval "ac_cv_lib_$ac_lib_var=yes"
 else
@@ -76772,7 +77006,7 @@ else
 fi
 
   echo $ac_n "checking for PQexecParams in -lpq""... $ac_c" 1>&6
-echo "configure:76776: checking for PQexecParams in -lpq" >&5
+echo "configure:77015: checking for PQexecParams in -lpq" >&5
 ac_lib_var=`echo pq'_'PQexecParams | sed 'y%./+-%__p_%'`
 if eval "test \"`echo '$''{'ac_cv_lib_$ac_lib_var'+set}'`\" = set"; then
   echo $ac_n "(cached) $ac_c" 1>&6
@@ -76780,7 +77014,7 @@ else
   ac_save_LIBS="$LIBS"
 LIBS="-lpq  $LIBS"
 cat > conftest.$ac_ext <<EOF
-#line 76784 "configure"
+#line 77023 "configure"
 #include "confdefs.h"
 /* Override any gcc2 internal prototype to avoid an error.  */
 /* We use char because int might match the return type of a gcc2
@@ -76791,7 +77025,7 @@ int main() {
 PQexecParams()
 ; return 0; }
 EOF
-if { (eval echo configure:76795: \"$ac_link\") 1>&5; (eval $ac_link) 2>&5; } && test -s conftest${ac_exeext}; then
+if { (eval echo configure:77034: \"$ac_link\") 1>&5; (eval $ac_link) 2>&5; } && test -s conftest${ac_exeext}; then
   rm -rf conftest*
   eval "ac_cv_lib_$ac_lib_var=yes"
 else
@@ -76815,7 +77049,7 @@ else
 fi
 
   echo $ac_n "checking for PQprepare in -lpq""... $ac_c" 1>&6
-echo "configure:76819: checking for PQprepare in -lpq" >&5
+echo "configure:77058: checking for PQprepare in -lpq" >&5
 ac_lib_var=`echo pq'_'PQprepare | sed 'y%./+-%__p_%'`
 if eval "test \"`echo '$''{'ac_cv_lib_$ac_lib_var'+set}'`\" = set"; then
   echo $ac_n "(cached) $ac_c" 1>&6
@@ -76823,7 +77057,7 @@ else
   ac_save_LIBS="$LIBS"
 LIBS="-lpq  $LIBS"
 cat > conftest.$ac_ext <<EOF
-#line 76827 "configure"
+#line 77066 "configure"
 #include "confdefs.h"
 /* Override any gcc2 internal prototype to avoid an error.  */
 /* We use char because int might match the return type of a gcc2
@@ -76834,7 +77068,7 @@ int main() {
 PQprepare()
 ; return 0; }
 EOF
-if { (eval echo configure:76838: \"$ac_link\") 1>&5; (eval $ac_link) 2>&5; } && test -s conftest${ac_exeext}; then
+if { (eval echo configure:77077: \"$ac_link\") 1>&5; (eval $ac_link) 2>&5; } && test -s conftest${ac_exeext}; then
   rm -rf conftest*
   eval "ac_cv_lib_$ac_lib_var=yes"
 else
@@ -76858,7 +77092,7 @@ else
 fi
 
   echo $ac_n "checking for PQexecPrepared in -lpq""... $ac_c" 1>&6
-echo "configure:76862: checking for PQexecPrepared in -lpq" >&5
+echo "configure:77101: checking for PQexecPrepared in -lpq" >&5
 ac_lib_var=`echo pq'_'PQexecPrepared | sed 'y%./+-%__p_%'`
 if eval "test \"`echo '$''{'ac_cv_lib_$ac_lib_var'+set}'`\" = set"; then
   echo $ac_n "(cached) $ac_c" 1>&6
@@ -76866,7 +77100,7 @@ else
   ac_save_LIBS="$LIBS"
 LIBS="-lpq  $LIBS"
 cat > conftest.$ac_ext <<EOF
-#line 76870 "configure"
+#line 77109 "configure"
 #include "confdefs.h"
 /* Override any gcc2 internal prototype to avoid an error.  */
 /* We use char because int might match the return type of a gcc2
@@ -76877,7 +77111,7 @@ int main() {
 PQexecPrepared()
 ; return 0; }
 EOF
-if { (eval echo configure:76881: \"$ac_link\") 1>&5; (eval $ac_link) 2>&5; } && test -s conftest${ac_exeext}; then
+if { (eval echo configure:77120: \"$ac_link\") 1>&5; (eval $ac_link) 2>&5; } && test -s conftest${ac_exeext}; then
   rm -rf conftest*
   eval "ac_cv_lib_$ac_lib_var=yes"
 else
@@ -76901,7 +77135,7 @@ else
 fi
 
   echo $ac_n "checking for PQresultErrorField in -lpq""... $ac_c" 1>&6
-echo "configure:76905: checking for PQresultErrorField in -lpq" >&5
+echo "configure:77144: checking for PQresultErrorField in -lpq" >&5
 ac_lib_var=`echo pq'_'PQresultErrorField | sed 'y%./+-%__p_%'`
 if eval "test \"`echo '$''{'ac_cv_lib_$ac_lib_var'+set}'`\" = set"; then
   echo $ac_n "(cached) $ac_c" 1>&6
@@ -76909,7 +77143,7 @@ else
   ac_save_LIBS="$LIBS"
 LIBS="-lpq  $LIBS"
 cat > conftest.$ac_ext <<EOF
-#line 76913 "configure"
+#line 77152 "configure"
 #include "confdefs.h"
 /* Override any gcc2 internal prototype to avoid an error.  */
 /* We use char because int might match the return type of a gcc2
@@ -76920,7 +77154,7 @@ int main() {
 PQresultErrorField()
 ; return 0; }
 EOF
-if { (eval echo configure:76924: \"$ac_link\") 1>&5; (eval $ac_link) 2>&5; } && test -s conftest${ac_exeext}; then
+if { (eval echo configure:77163: \"$ac_link\") 1>&5; (eval $ac_link) 2>&5; } && test -s conftest${ac_exeext}; then
   rm -rf conftest*
   eval "ac_cv_lib_$ac_lib_var=yes"
 else
@@ -76944,7 +77178,7 @@ else
 fi
 
   echo $ac_n "checking for PQsendQueryParams in -lpq""... $ac_c" 1>&6
-echo "configure:76948: checking for PQsendQueryParams in -lpq" >&5
+echo "configure:77187: checking for PQsendQueryParams in -lpq" >&5
 ac_lib_var=`echo pq'_'PQsendQueryParams | sed 'y%./+-%__p_%'`
 if eval "test \"`echo '$''{'ac_cv_lib_$ac_lib_var'+set}'`\" = set"; then
   echo $ac_n "(cached) $ac_c" 1>&6
@@ -76952,7 +77186,7 @@ else
   ac_save_LIBS="$LIBS"
 LIBS="-lpq  $LIBS"
 cat > conftest.$ac_ext <<EOF
-#line 76956 "configure"
+#line 77195 "configure"
 #include "confdefs.h"
 /* Override any gcc2 internal prototype to avoid an error.  */
 /* We use char because int might match the return type of a gcc2
@@ -76963,7 +77197,7 @@ int main() {
 PQsendQueryParams()
 ; return 0; }
 EOF
-if { (eval echo configure:76967: \"$ac_link\") 1>&5; (eval $ac_link) 2>&5; } && test -s conftest${ac_exeext}; then
+if { (eval echo configure:77206: \"$ac_link\") 1>&5; (eval $ac_link) 2>&5; } && test -s conftest${ac_exeext}; then
   rm -rf conftest*
   eval "ac_cv_lib_$ac_lib_var=yes"
 else
@@ -76987,7 +77221,7 @@ else
 fi
 
   echo $ac_n "checking for PQsendPrepare in -lpq""... $ac_c" 1>&6
-echo "configure:76991: checking for PQsendPrepare in -lpq" >&5
+echo "configure:77230: checking for PQsendPrepare in -lpq" >&5
 ac_lib_var=`echo pq'_'PQsendPrepare | sed 'y%./+-%__p_%'`
 if eval "test \"`echo '$''{'ac_cv_lib_$ac_lib_var'+set}'`\" = set"; then
   echo $ac_n "(cached) $ac_c" 1>&6
@@ -76995,7 +77229,7 @@ else
   ac_save_LIBS="$LIBS"
 LIBS="-lpq  $LIBS"
 cat > conftest.$ac_ext <<EOF
-#line 76999 "configure"
+#line 77238 "configure"
 #include "confdefs.h"
 /* Override any gcc2 internal prototype to avoid an error.  */
 /* We use char because int might match the return type of a gcc2
@@ -77006,7 +77240,7 @@ int main() {
 PQsendPrepare()
 ; return 0; }
 EOF
-if { (eval echo configure:77010: \"$ac_link\") 1>&5; (eval $ac_link) 2>&5; } && test -s conftest${ac_exeext}; then
+if { (eval echo configure:77249: \"$ac_link\") 1>&5; (eval $ac_link) 2>&5; } && test -s conftest${ac_exeext}; then
   rm -rf conftest*
   eval "ac_cv_lib_$ac_lib_var=yes"
 else
@@ -77030,7 +77264,7 @@ else
 fi
 
   echo $ac_n "checking for PQsendQueryPrepared in -lpq""... $ac_c" 1>&6
-echo "configure:77034: checking for PQsendQueryPrepared in -lpq" >&5
+echo "configure:77273: checking for PQsendQueryPrepared in -lpq" >&5
 ac_lib_var=`echo pq'_'PQsendQueryPrepared | sed 'y%./+-%__p_%'`
 if eval "test \"`echo '$''{'ac_cv_lib_$ac_lib_var'+set}'`\" = set"; then
   echo $ac_n "(cached) $ac_c" 1>&6
@@ -77038,7 +77272,7 @@ else
   ac_save_LIBS="$LIBS"
 LIBS="-lpq  $LIBS"
 cat > conftest.$ac_ext <<EOF
-#line 77042 "configure"
+#line 77281 "configure"
 #include "confdefs.h"
 /* Override any gcc2 internal prototype to avoid an error.  */
 /* We use char because int might match the return type of a gcc2
@@ -77049,7 +77283,7 @@ int main() {
 PQsendQueryPrepared()
 ; return 0; }
 EOF
-if { (eval echo configure:77053: \"$ac_link\") 1>&5; (eval $ac_link) 2>&5; } && test -s conftest${ac_exeext}; then
+if { (eval echo configure:77292: \"$ac_link\") 1>&5; (eval $ac_link) 2>&5; } && test -s conftest${ac_exeext}; then
   rm -rf conftest*
   eval "ac_cv_lib_$ac_lib_var=yes"
 else
@@ -77073,7 +77307,7 @@ else
 fi
 
   echo $ac_n "checking for PQputCopyData in -lpq""... $ac_c" 1>&6
-echo "configure:77077: checking for PQputCopyData in -lpq" >&5
+echo "configure:77316: checking for PQputCopyData in -lpq" >&5
 ac_lib_var=`echo pq'_'PQputCopyData | sed 'y%./+-%__p_%'`
 if eval "test \"`echo '$''{'ac_cv_lib_$ac_lib_var'+set}'`\" = set"; then
   echo $ac_n "(cached) $ac_c" 1>&6
@@ -77081,7 +77315,7 @@ else
   ac_save_LIBS="$LIBS"
 LIBS="-lpq  $LIBS"
 cat > conftest.$ac_ext <<EOF
-#line 77085 "configure"
+#line 77324 "configure"
 #include "confdefs.h"
 /* Override any gcc2 internal prototype to avoid an error.  */
 /* We use char because int might match the return type of a gcc2
@@ -77092,7 +77326,7 @@ int main() {
 PQputCopyData()
 ; return 0; }
 EOF
-if { (eval echo configure:77096: \"$ac_link\") 1>&5; (eval $ac_link) 2>&5; } && test -s conftest${ac_exeext}; then
+if { (eval echo configure:77335: \"$ac_link\") 1>&5; (eval $ac_link) 2>&5; } && test -s conftest${ac_exeext}; then
   rm -rf conftest*
   eval "ac_cv_lib_$ac_lib_var=yes"
 else
@@ -77116,7 +77350,7 @@ else
 fi
 
   echo $ac_n "checking for PQputCopyEnd in -lpq""... $ac_c" 1>&6
-echo "configure:77120: checking for PQputCopyEnd in -lpq" >&5
+echo "configure:77359: checking for PQputCopyEnd in -lpq" >&5
 ac_lib_var=`echo pq'_'PQputCopyEnd | sed 'y%./+-%__p_%'`
 if eval "test \"`echo '$''{'ac_cv_lib_$ac_lib_var'+set}'`\" = set"; then
   echo $ac_n "(cached) $ac_c" 1>&6
@@ -77124,7 +77358,7 @@ else
   ac_save_LIBS="$LIBS"
 LIBS="-lpq  $LIBS"
 cat > conftest.$ac_ext <<EOF
-#line 77128 "configure"
+#line 77367 "configure"
 #include "confdefs.h"
 /* Override any gcc2 internal prototype to avoid an error.  */
 /* We use char because int might match the return type of a gcc2
@@ -77135,7 +77369,7 @@ int main() {
 PQputCopyEnd()
 ; return 0; }
 EOF
-if { (eval echo configure:77139: \"$ac_link\") 1>&5; (eval $ac_link) 2>&5; } && test -s conftest${ac_exeext}; then
+if { (eval echo configure:77378: \"$ac_link\") 1>&5; (eval $ac_link) 2>&5; } && test -s conftest${ac_exeext}; then
   rm -rf conftest*
   eval "ac_cv_lib_$ac_lib_var=yes"
 else
@@ -77159,7 +77393,7 @@ else
 fi
 
   echo $ac_n "checking for PQgetCopyData in -lpq""... $ac_c" 1>&6
-echo "configure:77163: checking for PQgetCopyData in -lpq" >&5
+echo "configure:77402: checking for PQgetCopyData in -lpq" >&5
 ac_lib_var=`echo pq'_'PQgetCopyData | sed 'y%./+-%__p_%'`
 if eval "test \"`echo '$''{'ac_cv_lib_$ac_lib_var'+set}'`\" = set"; then
   echo $ac_n "(cached) $ac_c" 1>&6
@@ -77167,7 +77401,7 @@ else
   ac_save_LIBS="$LIBS"
 LIBS="-lpq  $LIBS"
 cat > conftest.$ac_ext <<EOF
-#line 77171 "configure"
+#line 77410 "configure"
 #include "confdefs.h"
 /* Override any gcc2 internal prototype to avoid an error.  */
 /* We use char because int might match the return type of a gcc2
@@ -77178,7 +77412,7 @@ int main() {
 PQgetCopyData()
 ; return 0; }
 EOF
-if { (eval echo configure:77182: \"$ac_link\") 1>&5; (eval $ac_link) 2>&5; } && test -s conftest${ac_exeext}; then
+if { (eval echo configure:77421: \"$ac_link\") 1>&5; (eval $ac_link) 2>&5; } && test -s conftest${ac_exeext}; then
   rm -rf conftest*
   eval "ac_cv_lib_$ac_lib_var=yes"
 else
@@ -77202,7 +77436,7 @@ else
 fi
 
   echo $ac_n "checking for PQfreemem in -lpq""... $ac_c" 1>&6
-echo "configure:77206: checking for PQfreemem in -lpq" >&5
+echo "configure:77445: checking for PQfreemem in -lpq" >&5
 ac_lib_var=`echo pq'_'PQfreemem | sed 'y%./+-%__p_%'`
 if eval "test \"`echo '$''{'ac_cv_lib_$ac_lib_var'+set}'`\" = set"; then
   echo $ac_n "(cached) $ac_c" 1>&6
@@ -77210,7 +77444,7 @@ else
   ac_save_LIBS="$LIBS"
 LIBS="-lpq  $LIBS"
 cat > conftest.$ac_ext <<EOF
-#line 77214 "configure"
+#line 77453 "configure"
 #include "confdefs.h"
 /* Override any gcc2 internal prototype to avoid an error.  */
 /* We use char because int might match the return type of a gcc2
@@ -77221,7 +77455,7 @@ int main() {
 PQfreemem()
 ; return 0; }
 EOF
-if { (eval echo configure:77225: \"$ac_link\") 1>&5; (eval $ac_link) 2>&5; } && test -s conftest${ac_exeext}; then
+if { (eval echo configure:77464: \"$ac_link\") 1>&5; (eval $ac_link) 2>&5; } && test -s conftest${ac_exeext}; then
   rm -rf conftest*
   eval "ac_cv_lib_$ac_lib_var=yes"
 else
@@ -77245,7 +77479,7 @@ else
 fi
 
   echo $ac_n "checking for PQsetErrorVerbosity in -lpq""... $ac_c" 1>&6
-echo "configure:77249: checking for PQsetErrorVerbosity in -lpq" >&5
+echo "configure:77488: checking for PQsetErrorVerbosity in -lpq" >&5
 ac_lib_var=`echo pq'_'PQsetErrorVerbosity | sed 'y%./+-%__p_%'`
 if eval "test \"`echo '$''{'ac_cv_lib_$ac_lib_var'+set}'`\" = set"; then
   echo $ac_n "(cached) $ac_c" 1>&6
@@ -77253,7 +77487,7 @@ else
   ac_save_LIBS="$LIBS"
 LIBS="-lpq  $LIBS"
 cat > conftest.$ac_ext <<EOF
-#line 77257 "configure"
+#line 77496 "configure"
 #include "confdefs.h"
 /* Override any gcc2 internal prototype to avoid an error.  */
 /* We use char because int might match the return type of a gcc2
@@ -77264,7 +77498,7 @@ int main() {
 PQsetErrorVerbosity()
 ; return 0; }
 EOF
-if { (eval echo configure:77268: \"$ac_link\") 1>&5; (eval $ac_link) 2>&5; } && test -s conftest${ac_exeext}; then
+if { (eval echo configure:77507: \"$ac_link\") 1>&5; (eval $ac_link) 2>&5; } && test -s conftest${ac_exeext}; then
   rm -rf conftest*
   eval "ac_cv_lib_$ac_lib_var=yes"
 else
@@ -77288,7 +77522,7 @@ else
 fi
 
   echo $ac_n "checking for PQftable in -lpq""... $ac_c" 1>&6
-echo "configure:77292: checking for PQftable in -lpq" >&5
+echo "configure:77531: checking for PQftable in -lpq" >&5
 ac_lib_var=`echo pq'_'PQftable | sed 'y%./+-%__p_%'`
 if eval "test \"`echo '$''{'ac_cv_lib_$ac_lib_var'+set}'`\" = set"; then
   echo $ac_n "(cached) $ac_c" 1>&6
@@ -77296,7 +77530,7 @@ else
   ac_save_LIBS="$LIBS"
 LIBS="-lpq  $LIBS"
 cat > conftest.$ac_ext <<EOF
-#line 77300 "configure"
+#line 77539 "configure"
 #include "confdefs.h"
 /* Override any gcc2 internal prototype to avoid an error.  */
 /* We use char because int might match the return type of a gcc2
@@ -77307,7 +77541,7 @@ int main() {
 PQftable()
 ; return 0; }
 EOF
-if { (eval echo configure:77311: \"$ac_link\") 1>&5; (eval $ac_link) 2>&5; } && test -s conftest${ac_exeext}; then
+if { (eval echo configure:77550: \"$ac_link\") 1>&5; (eval $ac_link) 2>&5; } && test -s conftest${ac_exeext}; then
   rm -rf conftest*
   eval "ac_cv_lib_$ac_lib_var=yes"
 else
@@ -77331,7 +77565,7 @@ else
 fi
 
   echo $ac_n "checking for PQescapeStringConn in -lpq""... $ac_c" 1>&6
-echo "configure:77335: checking for PQescapeStringConn in -lpq" >&5
+echo "configure:77574: checking for PQescapeStringConn in -lpq" >&5
 ac_lib_var=`echo pq'_'PQescapeStringConn | sed 'y%./+-%__p_%'`
 if eval "test \"`echo '$''{'ac_cv_lib_$ac_lib_var'+set}'`\" = set"; then
   echo $ac_n "(cached) $ac_c" 1>&6
@@ -77339,7 +77573,7 @@ else
   ac_save_LIBS="$LIBS"
 LIBS="-lpq  $LIBS"
 cat > conftest.$ac_ext <<EOF
-#line 77343 "configure"
+#line 77582 "configure"
 #include "confdefs.h"
 /* Override any gcc2 internal prototype to avoid an error.  */
 /* We use char because int might match the return type of a gcc2
@@ -77350,7 +77584,7 @@ int main() {
 PQescapeStringConn()
 ; return 0; }
 EOF
-if { (eval echo configure:77354: \"$ac_link\") 1>&5; (eval $ac_link) 2>&5; } && test -s conftest${ac_exeext}; then
+if { (eval echo configure:77593: \"$ac_link\") 1>&5; (eval $ac_link) 2>&5; } && test -s conftest${ac_exeext}; then
   rm -rf conftest*
   eval "ac_cv_lib_$ac_lib_var=yes"
 else
@@ -77374,7 +77608,7 @@ else
 fi
 
   echo $ac_n "checking for PQescapeByteaConn in -lpq""... $ac_c" 1>&6
-echo "configure:77378: checking for PQescapeByteaConn in -lpq" >&5
+echo "configure:77617: checking for PQescapeByteaConn in -lpq" >&5
 ac_lib_var=`echo pq'_'PQescapeByteaConn | sed 'y%./+-%__p_%'`
 if eval "test \"`echo '$''{'ac_cv_lib_$ac_lib_var'+set}'`\" = set"; then
   echo $ac_n "(cached) $ac_c" 1>&6
@@ -77382,7 +77616,7 @@ else
   ac_save_LIBS="$LIBS"
 LIBS="-lpq  $LIBS"
 cat > conftest.$ac_ext <<EOF
-#line 77386 "configure"
+#line 77625 "configure"
 #include "confdefs.h"
 /* Override any gcc2 internal prototype to avoid an error.  */
 /* We use char because int might match the return type of a gcc2
@@ -77393,7 +77627,7 @@ int main() {
 PQescapeByteaConn()
 ; return 0; }
 EOF
-if { (eval echo configure:77397: \"$ac_link\") 1>&5; (eval $ac_link) 2>&5; } && test -s conftest${ac_exeext}; then
+if { (eval echo configure:77636: \"$ac_link\") 1>&5; (eval $ac_link) 2>&5; } && test -s conftest${ac_exeext}; then
   rm -rf conftest*
   eval "ac_cv_lib_$ac_lib_var=yes"
 else
@@ -77417,7 +77651,7 @@ else
 fi
 
   echo $ac_n "checking for pg_encoding_to_char in -lpq""... $ac_c" 1>&6
-echo "configure:77421: checking for pg_encoding_to_char in -lpq" >&5
+echo "configure:77660: checking for pg_encoding_to_char in -lpq" >&5
 ac_lib_var=`echo pq'_'pg_encoding_to_char | sed 'y%./+-%__p_%'`
 if eval "test \"`echo '$''{'ac_cv_lib_$ac_lib_var'+set}'`\" = set"; then
   echo $ac_n "(cached) $ac_c" 1>&6
@@ -77425,7 +77659,7 @@ else
   ac_save_LIBS="$LIBS"
 LIBS="-lpq  $LIBS"
 cat > conftest.$ac_ext <<EOF
-#line 77429 "configure"
+#line 77668 "configure"
 #include "confdefs.h"
 /* Override any gcc2 internal prototype to avoid an error.  */
 /* We use char because int might match the return type of a gcc2
@@ -77436,7 +77670,7 @@ int main() {
 pg_encoding_to_char()
 ; return 0; }
 EOF
-if { (eval echo configure:77440: \"$ac_link\") 1>&5; (eval $ac_link) 2>&5; } && test -s conftest${ac_exeext}; then
+if { (eval echo configure:77679: \"$ac_link\") 1>&5; (eval $ac_link) 2>&5; } && test -s conftest${ac_exeext}; then
   rm -rf conftest*
   eval "ac_cv_lib_$ac_lib_var=yes"
 else
@@ -77460,7 +77694,7 @@ else
 fi
 
   echo $ac_n "checking for lo_create in -lpq""... $ac_c" 1>&6
-echo "configure:77464: checking for lo_create in -lpq" >&5
+echo "configure:77703: checking for lo_create in -lpq" >&5
 ac_lib_var=`echo pq'_'lo_create | sed 'y%./+-%__p_%'`
 if eval "test \"`echo '$''{'ac_cv_lib_$ac_lib_var'+set}'`\" = set"; then
   echo $ac_n "(cached) $ac_c" 1>&6
@@ -77468,7 +77702,7 @@ else
   ac_save_LIBS="$LIBS"
 LIBS="-lpq  $LIBS"
 cat > conftest.$ac_ext <<EOF
-#line 77472 "configure"
+#line 77711 "configure"
 #include "confdefs.h"
 /* Override any gcc2 internal prototype to avoid an error.  */
 /* We use char because int might match the return type of a gcc2
@@ -77479,7 +77713,7 @@ int main() {
 lo_create()
 ; return 0; }
 EOF
-if { (eval echo configure:77483: \"$ac_link\") 1>&5; (eval $ac_link) 2>&5; } && test -s conftest${ac_exeext}; then
+if { (eval echo configure:77722: \"$ac_link\") 1>&5; (eval $ac_link) 2>&5; } && test -s conftest${ac_exeext}; then
   rm -rf conftest*
   eval "ac_cv_lib_$ac_lib_var=yes"
 else
@@ -77503,7 +77737,7 @@ else
 fi
 
   echo $ac_n "checking for lo_import_with_oid in -lpq""... $ac_c" 1>&6
-echo "configure:77507: checking for lo_import_with_oid in -lpq" >&5
+echo "configure:77746: checking for lo_import_with_oid in -lpq" >&5
 ac_lib_var=`echo pq'_'lo_import_with_oid | sed 'y%./+-%__p_%'`
 if eval "test \"`echo '$''{'ac_cv_lib_$ac_lib_var'+set}'`\" = set"; then
   echo $ac_n "(cached) $ac_c" 1>&6
@@ -77511,7 +77745,7 @@ else
   ac_save_LIBS="$LIBS"
 LIBS="-lpq  $LIBS"
 cat > conftest.$ac_ext <<EOF
-#line 77515 "configure"
+#line 77754 "configure"
 #include "confdefs.h"
 /* Override any gcc2 internal prototype to avoid an error.  */
 /* We use char because int might match the return type of a gcc2
@@ -77522,7 +77756,7 @@ int main() {
 lo_import_with_oid()
 ; return 0; }
 EOF
-if { (eval echo configure:77526: \"$ac_link\") 1>&5; (eval $ac_link) 2>&5; } && test -s conftest${ac_exeext}; then
+if { (eval echo configure:77765: \"$ac_link\") 1>&5; (eval $ac_link) 2>&5; } && test -s conftest${ac_exeext}; then
   rm -rf conftest*
   eval "ac_cv_lib_$ac_lib_var=yes"
 else
@@ -77940,7 +78174,7 @@ EOF
       ac_obj=$1
       IFS=$old_IFS
       
-      PHP_CLI_OBJS="$PHP_CLI_OBJS $ac_bdir$ac_obj.lo"
+      PHP_GLOBAL_OBJS="$PHP_GLOBAL_OBJS $ac_bdir$ac_obj.lo"
 
       case $ac_src in
         *.c) ac_comp="$b_c_pre $ac_extra $ac_inc $b_c_meta -c $ac_srcdir$ac_src -o $ac_bdir$ac_obj.$b_lo $b_c_post" ;;
@@ -77982,7 +78216,7 @@ fi
 php_enable_phar=yes
 
 echo $ac_n "checking for phar archive support""... $ac_c" 1>&6
-echo "configure:77986: checking for phar archive support" >&5
+echo "configure:78225: checking for phar archive support" >&5
 # Check whether --enable-phar or --disable-phar was given.
 if test "${enable_phar+set}" = set; then
   enableval="$enable_phar"
@@ -78282,7 +78516,7 @@ EOF
       ac_obj=$1
       IFS=$old_IFS
       
-      PHP_CLI_OBJS="$PHP_CLI_OBJS $ac_bdir$ac_obj.lo"
+      PHP_GLOBAL_OBJS="$PHP_GLOBAL_OBJS $ac_bdir$ac_obj.lo"
 
       case $ac_src in
         *.c) ac_comp="$b_c_pre $ac_extra $ac_inc $b_c_meta -c $ac_srcdir$ac_src -o $ac_bdir$ac_obj.$b_lo $b_c_post" ;;
@@ -78316,7 +78550,7 @@ EOF
   fi
 
   echo $ac_n "checking for phar openssl support""... $ac_c" 1>&6
-echo "configure:78320: checking for phar openssl support" >&5
+echo "configure:78559: checking for phar openssl support" >&5
   if test "$PHP_HASH_SHARED" != "yes"; then
     if test "$PHP_HASH" != "no"; then
       cat >> confdefs.h <<\EOF
@@ -78391,7 +78625,7 @@ fi
 php_enable_posix=yes
 
 echo $ac_n "checking whether to enable POSIX-like functions""... $ac_c" 1>&6
-echo "configure:78395: checking whether to enable POSIX-like functions" >&5
+echo "configure:78634: checking whether to enable POSIX-like functions" >&5
 # Check whether --enable-posix or --disable-posix was given.
 if test "${enable_posix+set}" = set; then
   enableval="$enable_posix"
@@ -78695,7 +78929,7 @@ EOF
       ac_obj=$1
       IFS=$old_IFS
       
-      PHP_CLI_OBJS="$PHP_CLI_OBJS $ac_bdir$ac_obj.lo"
+      PHP_GLOBAL_OBJS="$PHP_GLOBAL_OBJS $ac_bdir$ac_obj.lo"
 
       case $ac_src in
         *.c) ac_comp="$b_c_pre $ac_extra $ac_inc $b_c_meta -c $ac_srcdir$ac_src -o $ac_bdir$ac_obj.$b_lo $b_c_post" ;;
@@ -78733,17 +78967,17 @@ EOF
 do
 ac_safe=`echo "$ac_hdr" | sed 'y%./+-%__p_%'`
 echo $ac_n "checking for $ac_hdr""... $ac_c" 1>&6
-echo "configure:78737: checking for $ac_hdr" >&5
+echo "configure:78976: checking for $ac_hdr" >&5
 if eval "test \"`echo '$''{'ac_cv_header_$ac_safe'+set}'`\" = set"; then
   echo $ac_n "(cached) $ac_c" 1>&6
 else
   cat > conftest.$ac_ext <<EOF
-#line 78742 "configure"
+#line 78981 "configure"
 #include "confdefs.h"
 #include <$ac_hdr>
 EOF
 ac_try="$ac_cpp conftest.$ac_ext >/dev/null 2>conftest.out"
-{ (eval echo configure:78747: \"$ac_try\") 1>&5; (eval $ac_try) 2>&5; }
+{ (eval echo configure:78986: \"$ac_try\") 1>&5; (eval $ac_try) 2>&5; }
 ac_err=`grep -v '^ *+' conftest.out | grep -v "^conftest.${ac_ext}\$"`
 if test -z "$ac_err"; then
   rm -rf conftest*
@@ -78773,12 +79007,12 @@ done
   for ac_func in seteuid setegid setsid getsid setpgid getpgid ctermid mkfifo mknod getrlimit getlogin getgroups makedev initgroups getpwuid_r getgrgid_r
 do
 echo $ac_n "checking for $ac_func""... $ac_c" 1>&6
-echo "configure:78777: checking for $ac_func" >&5
+echo "configure:79016: checking for $ac_func" >&5
 if eval "test \"`echo '$''{'ac_cv_func_$ac_func'+set}'`\" = set"; then
   echo $ac_n "(cached) $ac_c" 1>&6
 else
   cat > conftest.$ac_ext <<EOF
-#line 78782 "configure"
+#line 79021 "configure"
 #include "confdefs.h"
 /* System header to define __stub macros and hopefully few prototypes,
     which can conflict with char $ac_func(); below.  */
@@ -78801,7 +79035,7 @@ $ac_func();
 
 ; return 0; }
 EOF
-if { (eval echo configure:78805: \"$ac_link\") 1>&5; (eval $ac_link) 2>&5; } && test -s conftest${ac_exeext}; then
+if { (eval echo configure:79044: \"$ac_link\") 1>&5; (eval $ac_link) 2>&5; } && test -s conftest${ac_exeext}; then
   rm -rf conftest*
   eval "ac_cv_func_$ac_func=yes"
 else
@@ -78827,14 +79061,14 @@ done
 
 
   echo $ac_n "checking for working ttyname_r() implementation""... $ac_c" 1>&6
-echo "configure:78831: checking for working ttyname_r() implementation" >&5
+echo "configure:79070: checking for working ttyname_r() implementation" >&5
   if test "$cross_compiling" = yes; then
   
     echo "$ac_t""no, cannot detect working ttyname_r() when cross compiling. posix_ttyname() will be thread-unsafe" 1>&6
   
 else
   cat > conftest.$ac_ext <<EOF
-#line 78838 "configure"
+#line 79077 "configure"
 #include "confdefs.h"
 
 #include <unistd.h>
@@ -78847,7 +79081,7 @@ int main(int argc, char *argv[])
 }
   
 EOF
-if { (eval echo configure:78851: \"$ac_link\") 1>&5; (eval $ac_link) 2>&5; } && test -s conftest${ac_exeext} && (./conftest; exit) 2>/dev/null
+if { (eval echo configure:79090: \"$ac_link\") 1>&5; (eval $ac_link) 2>&5; } && test -s conftest${ac_exeext} && (./conftest; exit) 2>/dev/null
 then
   
     echo "$ac_t""yes" 1>&6
@@ -78869,13 +79103,13 @@ fi
 
 
   echo $ac_n "checking for utsname.domainname""... $ac_c" 1>&6
-echo "configure:78873: checking for utsname.domainname" >&5
+echo "configure:79112: checking for utsname.domainname" >&5
 if eval "test \"`echo '$''{'ac_cv_have_utsname_domainname'+set}'`\" = set"; then
   echo $ac_n "(cached) $ac_c" 1>&6
 else
   
     cat > conftest.$ac_ext <<EOF
-#line 78879 "configure"
+#line 79118 "configure"
 #include "confdefs.h"
 
       #define _GNU_SOURCE
@@ -78887,7 +79121,7 @@ int main() {
     
 ; return 0; }
 EOF
-if { (eval echo configure:78891: \"$ac_compile\") 1>&5; (eval $ac_compile) 2>&5; }; then
+if { (eval echo configure:79130: \"$ac_compile\") 1>&5; (eval $ac_compile) 2>&5; }; then
   rm -rf conftest*
   
       ac_cv_have_utsname_domainname=yes
@@ -78918,7 +79152,7 @@ fi
 php_with_pspell=no
 
 echo $ac_n "checking for PSPELL support""... $ac_c" 1>&6
-echo "configure:78922: checking for PSPELL support" >&5
+echo "configure:79161: checking for PSPELL support" >&5
 # Check whether --with-pspell or --without-pspell was given.
 if test "${with_pspell+set}" = set; then
   withval="$with_pspell"
@@ -79218,7 +79452,7 @@ EOF
       ac_obj=$1
       IFS=$old_IFS
       
-      PHP_CLI_OBJS="$PHP_CLI_OBJS $ac_bdir$ac_obj.lo"
+      PHP_GLOBAL_OBJS="$PHP_GLOBAL_OBJS $ac_bdir$ac_obj.lo"
 
       case $ac_src in
         *.c) ac_comp="$b_c_pre $ac_extra $ac_inc $b_c_meta -c $ac_srcdir$ac_src -o $ac_bdir$ac_obj.$b_lo $b_c_post" ;;
@@ -79468,7 +79702,7 @@ EOF
   done
 
   echo $ac_n "checking for new_aspell_config in -laspell""... $ac_c" 1>&6
-echo "configure:79472: checking for new_aspell_config in -laspell" >&5
+echo "configure:79711: checking for new_aspell_config in -laspell" >&5
 ac_lib_var=`echo aspell'_'new_aspell_config | sed 'y%./+-%__p_%'`
 if eval "test \"`echo '$''{'ac_cv_lib_$ac_lib_var'+set}'`\" = set"; then
   echo $ac_n "(cached) $ac_c" 1>&6
@@ -79476,7 +79710,7 @@ else
   ac_save_LIBS="$LIBS"
 LIBS="-laspell  $LIBS"
 cat > conftest.$ac_ext <<EOF
-#line 79480 "configure"
+#line 79719 "configure"
 #include "confdefs.h"
 /* Override any gcc2 internal prototype to avoid an error.  */
 /* We use char because int might match the return type of a gcc2
@@ -79487,7 +79721,7 @@ int main() {
 new_aspell_config()
 ; return 0; }
 EOF
-if { (eval echo configure:79491: \"$ac_link\") 1>&5; (eval $ac_link) 2>&5; } && test -s conftest${ac_exeext}; then
+if { (eval echo configure:79730: \"$ac_link\") 1>&5; (eval $ac_link) 2>&5; } && test -s conftest${ac_exeext}; then
   rm -rf conftest*
   eval "ac_cv_lib_$ac_lib_var=yes"
 else
@@ -79661,7 +79895,7 @@ fi
 php_with_libedit=no
 
 echo $ac_n "checking for libedit readline replacement""... $ac_c" 1>&6
-echo "configure:79665: checking for libedit readline replacement" >&5
+echo "configure:79904: checking for libedit readline replacement" >&5
 # Check whether --with-libedit or --without-libedit was given.
 if test "${with_libedit+set}" = set; then
   withval="$with_libedit"
@@ -79706,7 +79940,7 @@ if test "$PHP_LIBEDIT" = "no"; then
 php_with_readline=no
 
 echo $ac_n "checking for readline support""... $ac_c" 1>&6
-echo "configure:79710: checking for readline support" >&5
+echo "configure:79949: checking for readline support" >&5
 # Check whether --with-readline or --without-readline was given.
 if test "${with_readline+set}" = set; then
   withval="$with_readline"
@@ -79792,7 +80026,7 @@ if test "$PHP_READLINE" && test "$PHP_READLINE" != "no"; then
 
   PHP_READLINE_LIBS=""
   echo $ac_n "checking for tgetent in -lncurses""... $ac_c" 1>&6
-echo "configure:79796: checking for tgetent in -lncurses" >&5
+echo "configure:80035: checking for tgetent in -lncurses" >&5
 ac_lib_var=`echo ncurses'_'tgetent | sed 'y%./+-%__p_%'`
 if eval "test \"`echo '$''{'ac_cv_lib_$ac_lib_var'+set}'`\" = set"; then
   echo $ac_n "(cached) $ac_c" 1>&6
@@ -79800,7 +80034,7 @@ else
   ac_save_LIBS="$LIBS"
 LIBS="-lncurses  $LIBS"
 cat > conftest.$ac_ext <<EOF
-#line 79804 "configure"
+#line 80043 "configure"
 #include "confdefs.h"
 /* Override any gcc2 internal prototype to avoid an error.  */
 /* We use char because int might match the return type of a gcc2
@@ -79811,7 +80045,7 @@ int main() {
 tgetent()
 ; return 0; }
 EOF
-if { (eval echo configure:79815: \"$ac_link\") 1>&5; (eval $ac_link) 2>&5; } && test -s conftest${ac_exeext}; then
+if { (eval echo configure:80054: \"$ac_link\") 1>&5; (eval $ac_link) 2>&5; } && test -s conftest${ac_exeext}; then
   rm -rf conftest*
   eval "ac_cv_lib_$ac_lib_var=yes"
 else
@@ -79856,7 +80090,7 @@ else
   echo "$ac_t""no" 1>&6
 
     echo $ac_n "checking for tgetent in -ltermcap""... $ac_c" 1>&6
-echo "configure:79860: checking for tgetent in -ltermcap" >&5
+echo "configure:80099: checking for tgetent in -ltermcap" >&5
 ac_lib_var=`echo termcap'_'tgetent | sed 'y%./+-%__p_%'`
 if eval "test \"`echo '$''{'ac_cv_lib_$ac_lib_var'+set}'`\" = set"; then
   echo $ac_n "(cached) $ac_c" 1>&6
@@ -79864,7 +80098,7 @@ else
   ac_save_LIBS="$LIBS"
 LIBS="-ltermcap  $LIBS"
 cat > conftest.$ac_ext <<EOF
-#line 79868 "configure"
+#line 80107 "configure"
 #include "confdefs.h"
 /* Override any gcc2 internal prototype to avoid an error.  */
 /* We use char because int might match the return type of a gcc2
@@ -79875,7 +80109,7 @@ int main() {
 tgetent()
 ; return 0; }
 EOF
-if { (eval echo configure:79879: \"$ac_link\") 1>&5; (eval $ac_link) 2>&5; } && test -s conftest${ac_exeext}; then
+if { (eval echo configure:80118: \"$ac_link\") 1>&5; (eval $ac_link) 2>&5; } && test -s conftest${ac_exeext}; then
   rm -rf conftest*
   eval "ac_cv_lib_$ac_lib_var=yes"
 else
@@ -80022,7 +80256,7 @@ fi
   done
 
   echo $ac_n "checking for readline in -lreadline""... $ac_c" 1>&6
-echo "configure:80026: checking for readline in -lreadline" >&5
+echo "configure:80265: checking for readline in -lreadline" >&5
 ac_lib_var=`echo readline'_'readline | sed 'y%./+-%__p_%'`
 if eval "test \"`echo '$''{'ac_cv_lib_$ac_lib_var'+set}'`\" = set"; then
   echo $ac_n "(cached) $ac_c" 1>&6
@@ -80030,7 +80264,7 @@ else
   ac_save_LIBS="$LIBS"
 LIBS="-lreadline  $LIBS"
 cat > conftest.$ac_ext <<EOF
-#line 80034 "configure"
+#line 80273 "configure"
 #include "confdefs.h"
 /* Override any gcc2 internal prototype to avoid an error.  */
 /* We use char because int might match the return type of a gcc2
@@ -80041,7 +80275,7 @@ int main() {
 readline()
 ; return 0; }
 EOF
-if { (eval echo configure:80045: \"$ac_link\") 1>&5; (eval $ac_link) 2>&5; } && test -s conftest${ac_exeext}; then
+if { (eval echo configure:80284: \"$ac_link\") 1>&5; (eval $ac_link) 2>&5; } && test -s conftest${ac_exeext}; then
   rm -rf conftest*
   eval "ac_cv_lib_$ac_lib_var=yes"
 else
@@ -80270,7 +80504,7 @@ fi
   done
 
   echo $ac_n "checking for rl_pending_input in -lreadline""... $ac_c" 1>&6
-echo "configure:80274: checking for rl_pending_input in -lreadline" >&5
+echo "configure:80513: checking for rl_pending_input in -lreadline" >&5
 ac_lib_var=`echo readline'_'rl_pending_input | sed 'y%./+-%__p_%'`
 if eval "test \"`echo '$''{'ac_cv_lib_$ac_lib_var'+set}'`\" = set"; then
   echo $ac_n "(cached) $ac_c" 1>&6
@@ -80278,7 +80512,7 @@ else
   ac_save_LIBS="$LIBS"
 LIBS="-lreadline  $LIBS"
 cat > conftest.$ac_ext <<EOF
-#line 80282 "configure"
+#line 80521 "configure"
 #include "confdefs.h"
 /* Override any gcc2 internal prototype to avoid an error.  */
 /* We use char because int might match the return type of a gcc2
@@ -80289,7 +80523,7 @@ int main() {
 rl_pending_input()
 ; return 0; }
 EOF
-if { (eval echo configure:80293: \"$ac_link\") 1>&5; (eval $ac_link) 2>&5; } && test -s conftest${ac_exeext}; then
+if { (eval echo configure:80532: \"$ac_link\") 1>&5; (eval $ac_link) 2>&5; } && test -s conftest${ac_exeext}; then
   rm -rf conftest*
   eval "ac_cv_lib_$ac_lib_var=yes"
 else
@@ -80420,7 +80654,7 @@ fi
   done
 
   echo $ac_n "checking for rl_callback_read_char in -lreadline""... $ac_c" 1>&6
-echo "configure:80424: checking for rl_callback_read_char in -lreadline" >&5
+echo "configure:80663: checking for rl_callback_read_char in -lreadline" >&5
 ac_lib_var=`echo readline'_'rl_callback_read_char | sed 'y%./+-%__p_%'`
 if eval "test \"`echo '$''{'ac_cv_lib_$ac_lib_var'+set}'`\" = set"; then
   echo $ac_n "(cached) $ac_c" 1>&6
@@ -80428,7 +80662,7 @@ else
   ac_save_LIBS="$LIBS"
 LIBS="-lreadline  $LIBS"
 cat > conftest.$ac_ext <<EOF
-#line 80432 "configure"
+#line 80671 "configure"
 #include "confdefs.h"
 /* Override any gcc2 internal prototype to avoid an error.  */
 /* We use char because int might match the return type of a gcc2
@@ -80439,7 +80673,7 @@ int main() {
 rl_callback_read_char()
 ; return 0; }
 EOF
-if { (eval echo configure:80443: \"$ac_link\") 1>&5; (eval $ac_link) 2>&5; } && test -s conftest${ac_exeext}; then
+if { (eval echo configure:80682: \"$ac_link\") 1>&5; (eval $ac_link) 2>&5; } && test -s conftest${ac_exeext}; then
   rm -rf conftest*
   eval "ac_cv_lib_$ac_lib_var=yes"
 else
@@ -80523,7 +80757,7 @@ elif test "$PHP_LIBEDIT" != "no"; then
 
 
   echo $ac_n "checking for tgetent in -lncurses""... $ac_c" 1>&6
-echo "configure:80527: checking for tgetent in -lncurses" >&5
+echo "configure:80766: checking for tgetent in -lncurses" >&5
 ac_lib_var=`echo ncurses'_'tgetent | sed 'y%./+-%__p_%'`
 if eval "test \"`echo '$''{'ac_cv_lib_$ac_lib_var'+set}'`\" = set"; then
   echo $ac_n "(cached) $ac_c" 1>&6
@@ -80531,7 +80765,7 @@ else
   ac_save_LIBS="$LIBS"
 LIBS="-lncurses  $LIBS"
 cat > conftest.$ac_ext <<EOF
-#line 80535 "configure"
+#line 80774 "configure"
 #include "confdefs.h"
 /* Override any gcc2 internal prototype to avoid an error.  */
 /* We use char because int might match the return type of a gcc2
@@ -80542,7 +80776,7 @@ int main() {
 tgetent()
 ; return 0; }
 EOF
-if { (eval echo configure:80546: \"$ac_link\") 1>&5; (eval $ac_link) 2>&5; } && test -s conftest${ac_exeext}; then
+if { (eval echo configure:80785: \"$ac_link\") 1>&5; (eval $ac_link) 2>&5; } && test -s conftest${ac_exeext}; then
   rm -rf conftest*
   eval "ac_cv_lib_$ac_lib_var=yes"
 else
@@ -80586,7 +80820,7 @@ else
   echo "$ac_t""no" 1>&6
 
     echo $ac_n "checking for tgetent in -ltermcap""... $ac_c" 1>&6
-echo "configure:80590: checking for tgetent in -ltermcap" >&5
+echo "configure:80829: checking for tgetent in -ltermcap" >&5
 ac_lib_var=`echo termcap'_'tgetent | sed 'y%./+-%__p_%'`
 if eval "test \"`echo '$''{'ac_cv_lib_$ac_lib_var'+set}'`\" = set"; then
   echo $ac_n "(cached) $ac_c" 1>&6
@@ -80594,7 +80828,7 @@ else
   ac_save_LIBS="$LIBS"
 LIBS="-ltermcap  $LIBS"
 cat > conftest.$ac_ext <<EOF
-#line 80598 "configure"
+#line 80837 "configure"
 #include "confdefs.h"
 /* Override any gcc2 internal prototype to avoid an error.  */
 /* We use char because int might match the return type of a gcc2
@@ -80605,7 +80839,7 @@ int main() {
 tgetent()
 ; return 0; }
 EOF
-if { (eval echo configure:80609: \"$ac_link\") 1>&5; (eval $ac_link) 2>&5; } && test -s conftest${ac_exeext}; then
+if { (eval echo configure:80848: \"$ac_link\") 1>&5; (eval $ac_link) 2>&5; } && test -s conftest${ac_exeext}; then
   rm -rf conftest*
   eval "ac_cv_lib_$ac_lib_var=yes"
 else
@@ -80751,7 +80985,7 @@ fi
   done
 
   echo $ac_n "checking for readline in -ledit""... $ac_c" 1>&6
-echo "configure:80755: checking for readline in -ledit" >&5
+echo "configure:80994: checking for readline in -ledit" >&5
 ac_lib_var=`echo edit'_'readline | sed 'y%./+-%__p_%'`
 if eval "test \"`echo '$''{'ac_cv_lib_$ac_lib_var'+set}'`\" = set"; then
   echo $ac_n "(cached) $ac_c" 1>&6
@@ -80759,7 +80993,7 @@ else
   ac_save_LIBS="$LIBS"
 LIBS="-ledit  $LIBS"
 cat > conftest.$ac_ext <<EOF
-#line 80763 "configure"
+#line 81002 "configure"
 #include "confdefs.h"
 /* Override any gcc2 internal prototype to avoid an error.  */
 /* We use char because int might match the return type of a gcc2
@@ -80770,7 +81004,7 @@ int main() {
 readline()
 ; return 0; }
 EOF
-if { (eval echo configure:80774: \"$ac_link\") 1>&5; (eval $ac_link) 2>&5; } && test -s conftest${ac_exeext}; then
+if { (eval echo configure:81013: \"$ac_link\") 1>&5; (eval $ac_link) 2>&5; } && test -s conftest${ac_exeext}; then
   rm -rf conftest*
   eval "ac_cv_lib_$ac_lib_var=yes"
 else
@@ -80911,12 +81145,12 @@ if test "$PHP_READLINE" != "no" || test "$PHP_LIBEDIT" != "no"; then
   for ac_func in rl_completion_matches
 do
 echo $ac_n "checking for $ac_func""... $ac_c" 1>&6
-echo "configure:80915: checking for $ac_func" >&5
+echo "configure:81154: checking for $ac_func" >&5
 if eval "test \"`echo '$''{'ac_cv_func_$ac_func'+set}'`\" = set"; then
   echo $ac_n "(cached) $ac_c" 1>&6
 else
   cat > conftest.$ac_ext <<EOF
-#line 80920 "configure"
+#line 81159 "configure"
 #include "confdefs.h"
 /* System header to define __stub macros and hopefully few prototypes,
     which can conflict with char $ac_func(); below.  */
@@ -80939,7 +81173,7 @@ $ac_func();
 
 ; return 0; }
 EOF
-if { (eval echo configure:80943: \"$ac_link\") 1>&5; (eval $ac_link) 2>&5; } && test -s conftest${ac_exeext}; then
+if { (eval echo configure:81182: \"$ac_link\") 1>&5; (eval $ac_link) 2>&5; } && test -s conftest${ac_exeext}; then
   rm -rf conftest*
   eval "ac_cv_func_$ac_func=yes"
 else
@@ -81222,7 +81456,7 @@ EOF
       ac_obj=$1
       IFS=$old_IFS
       
-      PHP_CLI_OBJS="$PHP_CLI_OBJS $ac_bdir$ac_obj.lo"
+      PHP_GLOBAL_OBJS="$PHP_GLOBAL_OBJS $ac_bdir$ac_obj.lo"
 
       case $ac_src in
         *.c) ac_comp="$b_c_pre $ac_extra $ac_inc $b_c_meta -c $ac_srcdir$ac_src -o $ac_bdir$ac_obj.$b_lo $b_c_post" ;;
@@ -81265,7 +81499,7 @@ fi
 php_with_recode=no
 
 echo $ac_n "checking for recode support""... $ac_c" 1>&6
-echo "configure:81269: checking for recode support" >&5
+echo "configure:81508: checking for recode support" >&5
 # Check whether --with-recode or --without-recode was given.
 if test "${with_recode+set}" = set; then
   withval="$with_recode"
@@ -81429,7 +81663,7 @@ if test "$PHP_RECODE" != "no"; then
   done
 
   echo $ac_n "checking for recode_format_table in -lrecode""... $ac_c" 1>&6
-echo "configure:81433: checking for recode_format_table in -lrecode" >&5
+echo "configure:81672: checking for recode_format_table in -lrecode" >&5
 ac_lib_var=`echo recode'_'recode_format_table | sed 'y%./+-%__p_%'`
 if eval "test \"`echo '$''{'ac_cv_lib_$ac_lib_var'+set}'`\" = set"; then
   echo $ac_n "(cached) $ac_c" 1>&6
@@ -81437,7 +81671,7 @@ else
   ac_save_LIBS="$LIBS"
 LIBS="-lrecode  $LIBS"
 cat > conftest.$ac_ext <<EOF
-#line 81441 "configure"
+#line 81680 "configure"
 #include "confdefs.h"
 /* Override any gcc2 internal prototype to avoid an error.  */
 /* We use char because int might match the return type of a gcc2
@@ -81448,7 +81682,7 @@ int main() {
 recode_format_table()
 ; return 0; }
 EOF
-if { (eval echo configure:81452: \"$ac_link\") 1>&5; (eval $ac_link) 2>&5; } && test -s conftest${ac_exeext}; then
+if { (eval echo configure:81691: \"$ac_link\") 1>&5; (eval $ac_link) 2>&5; } && test -s conftest${ac_exeext}; then
   rm -rf conftest*
   eval "ac_cv_lib_$ac_lib_var=yes"
 else
@@ -81578,7 +81812,7 @@ else
     LDFLAGS="$LDFLAGS -L$RECODE_DIR/$RECODE_LIB"
     LIBS="$LIBS -lrecode"
     cat > conftest.$ac_ext <<EOF
-#line 81582 "configure"
+#line 81821 "configure"
 #include "confdefs.h"
 
 char *program_name;
@@ -81589,7 +81823,7 @@ recode_format_table();
     
 ; return 0; }
 EOF
-if { (eval echo configure:81593: \"$ac_link\") 1>&5; (eval $ac_link) 2>&5; } && test -s conftest${ac_exeext}; then
+if { (eval echo configure:81832: \"$ac_link\") 1>&5; (eval $ac_link) 2>&5; } && test -s conftest${ac_exeext}; then
   rm -rf conftest*
   
       
@@ -81752,17 +81986,17 @@ EOF
 do
 ac_safe=`echo "$ac_hdr" | sed 'y%./+-%__p_%'`
 echo $ac_n "checking for $ac_hdr""... $ac_c" 1>&6
-echo "configure:81756: checking for $ac_hdr" >&5
+echo "configure:81995: checking for $ac_hdr" >&5
 if eval "test \"`echo '$''{'ac_cv_header_$ac_safe'+set}'`\" = set"; then
   echo $ac_n "(cached) $ac_c" 1>&6
 else
   cat > conftest.$ac_ext <<EOF
-#line 81761 "configure"
+#line 82000 "configure"
 #include "confdefs.h"
 #include <$ac_hdr>
 EOF
 ac_try="$ac_cpp conftest.$ac_ext >/dev/null 2>conftest.out"
-{ (eval echo configure:81766: \"$ac_try\") 1>&5; (eval $ac_try) 2>&5; }
+{ (eval echo configure:82005: \"$ac_try\") 1>&5; (eval $ac_try) 2>&5; }
 ac_err=`grep -v '^ *+' conftest.out | grep -v "^conftest.${ac_ext}\$"`
 if test -z "$ac_err"; then
   rm -rf conftest*
@@ -82047,7 +82281,7 @@ EOF
       ac_obj=$1
       IFS=$old_IFS
       
-      PHP_CLI_OBJS="$PHP_CLI_OBJS $ac_bdir$ac_obj.lo"
+      PHP_GLOBAL_OBJS="$PHP_GLOBAL_OBJS $ac_bdir$ac_obj.lo"
 
       case $ac_src in
         *.c) ac_comp="$b_c_pre $ac_extra $ac_inc $b_c_meta -c $ac_srcdir$ac_src -o $ac_bdir$ac_obj.$b_lo $b_c_post" ;;
@@ -82346,7 +82580,7 @@ EOF
       ac_obj=$1
       IFS=$old_IFS
       
-      PHP_CLI_OBJS="$PHP_CLI_OBJS $ac_bdir$ac_obj.lo"
+      PHP_GLOBAL_OBJS="$PHP_GLOBAL_OBJS $ac_bdir$ac_obj.lo"
 
       case $ac_src in
         *.c) ac_comp="$b_c_pre $ac_extra $ac_inc $b_c_meta -c $ac_srcdir$ac_src -o $ac_bdir$ac_obj.$b_lo $b_c_post" ;;
@@ -82385,7 +82619,7 @@ EOF
 php_enable_session=yes
 
 echo $ac_n "checking whether to enable PHP sessions""... $ac_c" 1>&6
-echo "configure:82389: checking whether to enable PHP sessions" >&5
+echo "configure:82628: checking whether to enable PHP sessions" >&5
 # Check whether --enable-session or --disable-session was given.
 if test "${enable_session+set}" = set; then
   enableval="$enable_session"
@@ -82429,7 +82663,7 @@ echo "$ac_t""$ext_output" 1>&6
 php_with_mm=no
 
 echo $ac_n "checking for mm support""... $ac_c" 1>&6
-echo "configure:82433: checking for mm support" >&5
+echo "configure:82672: checking for mm support" >&5
 # Check whether --with-mm or --without-mm was given.
 if test "${with_mm+set}" = set; then
   withval="$with_mm"
@@ -82451,7 +82685,7 @@ echo "$ac_t""$ext_output" 1>&6
 if test "$PHP_SESSION" != "no"; then
   
   echo $ac_n "checking whether pwrite works""... $ac_c" 1>&6
-echo "configure:82455: checking whether pwrite works" >&5
+echo "configure:82694: checking whether pwrite works" >&5
 if eval "test \"`echo '$''{'ac_cv_pwrite'+set}'`\" = set"; then
   echo $ac_n "(cached) $ac_c" 1>&6
 else
@@ -82463,7 +82697,7 @@ else
   
 else
   cat > conftest.$ac_ext <<EOF
-#line 82467 "configure"
+#line 82706 "configure"
 #include "confdefs.h"
 
 #include <sys/types.h>
@@ -82484,7 +82718,7 @@ else
 
   
 EOF
-if { (eval echo configure:82488: \"$ac_link\") 1>&5; (eval $ac_link) 2>&5; } && test -s conftest${ac_exeext} && (./conftest; exit) 2>/dev/null
+if { (eval echo configure:82727: \"$ac_link\") 1>&5; (eval $ac_link) 2>&5; } && test -s conftest${ac_exeext} && (./conftest; exit) 2>/dev/null
 then
   
     ac_cv_pwrite=yes
@@ -82509,7 +82743,7 @@ fi
   
 else
   cat > conftest.$ac_ext <<EOF
-#line 82513 "configure"
+#line 82752 "configure"
 #include "confdefs.h"
 
 #include <sys/types.h>
@@ -82530,7 +82764,7 @@ ssize_t pwrite(int, void *, size_t, off64_t);
 
   
 EOF
-if { (eval echo configure:82534: \"$ac_link\") 1>&5; (eval $ac_link) 2>&5; } && test -s conftest${ac_exeext} && (./conftest; exit) 2>/dev/null
+if { (eval echo configure:82773: \"$ac_link\") 1>&5; (eval $ac_link) 2>&5; } && test -s conftest${ac_exeext} && (./conftest; exit) 2>/dev/null
 then
   
     ac_cv_pwrite=yes
@@ -82571,7 +82805,7 @@ EOF
 
   
   echo $ac_n "checking whether pread works""... $ac_c" 1>&6
-echo "configure:82575: checking whether pread works" >&5
+echo "configure:82814: checking whether pread works" >&5
 if eval "test \"`echo '$''{'ac_cv_pread'+set}'`\" = set"; then
   echo $ac_n "(cached) $ac_c" 1>&6
 else
@@ -82584,7 +82818,7 @@ else
   
 else
   cat > conftest.$ac_ext <<EOF
-#line 82588 "configure"
+#line 82827 "configure"
 #include "confdefs.h"
 
 #include <sys/types.h>
@@ -82604,7 +82838,7 @@ else
     }
   
 EOF
-if { (eval echo configure:82608: \"$ac_link\") 1>&5; (eval $ac_link) 2>&5; } && test -s conftest${ac_exeext} && (./conftest; exit) 2>/dev/null
+if { (eval echo configure:82847: \"$ac_link\") 1>&5; (eval $ac_link) 2>&5; } && test -s conftest${ac_exeext} && (./conftest; exit) 2>/dev/null
 then
   
     ac_cv_pread=yes
@@ -82631,7 +82865,7 @@ fi
   
 else
   cat > conftest.$ac_ext <<EOF
-#line 82635 "configure"
+#line 82874 "configure"
 #include "confdefs.h"
 
 #include <sys/types.h>
@@ -82651,7 +82885,7 @@ ssize_t pread(int, void *, size_t, off64_t);
     }
   
 EOF
-if { (eval echo configure:82655: \"$ac_link\") 1>&5; (eval $ac_link) 2>&5; } && test -s conftest${ac_exeext} && (./conftest; exit) 2>/dev/null
+if { (eval echo configure:82894: \"$ac_link\") 1>&5; (eval $ac_link) 2>&5; } && test -s conftest${ac_exeext} && (./conftest; exit) 2>/dev/null
 then
   
     ac_cv_pread=yes
@@ -82950,7 +83184,7 @@ EOF
       ac_obj=$1
       IFS=$old_IFS
       
-      PHP_CLI_OBJS="$PHP_CLI_OBJS $ac_bdir$ac_obj.lo"
+      PHP_GLOBAL_OBJS="$PHP_GLOBAL_OBJS $ac_bdir$ac_obj.lo"
 
       case $ac_src in
         *.c) ac_comp="$b_c_pre $ac_extra $ac_inc $b_c_meta -c $ac_srcdir$ac_src -o $ac_bdir$ac_obj.$b_lo $b_c_post" ;;
@@ -83215,7 +83449,7 @@ fi
 php_enable_shmop=no
 
 echo $ac_n "checking whether to enable shmop support""... $ac_c" 1>&6
-echo "configure:83219: checking whether to enable shmop support" >&5
+echo "configure:83458: checking whether to enable shmop support" >&5
 # Check whether --enable-shmop or --disable-shmop was given.
 if test "${enable_shmop+set}" = set; then
   enableval="$enable_shmop"
@@ -83519,7 +83753,7 @@ EOF
       ac_obj=$1
       IFS=$old_IFS
       
-      PHP_CLI_OBJS="$PHP_CLI_OBJS $ac_bdir$ac_obj.lo"
+      PHP_GLOBAL_OBJS="$PHP_GLOBAL_OBJS $ac_bdir$ac_obj.lo"
 
       case $ac_src in
         *.c) ac_comp="$b_c_pre $ac_extra $ac_inc $b_c_meta -c $ac_srcdir$ac_src -o $ac_bdir$ac_obj.$b_lo $b_c_post" ;;
@@ -83559,7 +83793,7 @@ fi
 php_enable_simplexml=yes
 
 echo $ac_n "checking whether to enable SimpleXML support""... $ac_c" 1>&6
-echo "configure:83563: checking whether to enable SimpleXML support" >&5
+echo "configure:83802: checking whether to enable SimpleXML support" >&5
 # Check whether --enable-simplexml or --disable-simplexml was given.
 if test "${enable_simplexml+set}" = set; then
   enableval="$enable_simplexml"
@@ -83604,7 +83838,7 @@ if test -z "$PHP_LIBXML_DIR"; then
 php_with_libxml_dir=no
 
 echo $ac_n "checking libxml2 install dir""... $ac_c" 1>&6
-echo "configure:83608: checking libxml2 install dir" >&5
+echo "configure:83847: checking libxml2 install dir" >&5
 # Check whether --with-libxml-dir or --without-libxml-dir was given.
 if test "${with_libxml_dir+set}" = set; then
   withval="$with_libxml_dir"
@@ -83632,7 +83866,7 @@ if test "$PHP_SIMPLEXML" != "no"; then
 
   
 echo $ac_n "checking for xml2-config path""... $ac_c" 1>&6
-echo "configure:83636: checking for xml2-config path" >&5
+echo "configure:83875: checking for xml2-config path" >&5
 if eval "test \"`echo '$''{'ac_cv_php_xml2_config_path'+set}'`\" = set"; then
   echo $ac_n "(cached) $ac_c" 1>&6
 else
@@ -83790,7 +84024,7 @@ echo "$ac_t""$ac_cv_php_xml2_config_path" 1>&6
 
 
             echo $ac_n "checking whether libxml build works""... $ac_c" 1>&6
-echo "configure:83794: checking whether libxml build works" >&5
+echo "configure:84033: checking whether libxml build works" >&5
 if eval "test \"`echo '$''{'php_cv_libxml_build_works'+set}'`\" = set"; then
   echo $ac_n "(cached) $ac_c" 1>&6
 else
@@ -83806,7 +84040,7 @@ else
   
 else
   cat > conftest.$ac_ext <<EOF
-#line 83810 "configure"
+#line 84049 "configure"
 #include "confdefs.h"
 
     
@@ -83817,7 +84051,7 @@ else
     }
   
 EOF
-if { (eval echo configure:83821: \"$ac_link\") 1>&5; (eval $ac_link) 2>&5; } && test -s conftest${ac_exeext} && (./conftest; exit) 2>/dev/null
+if { (eval echo configure:84060: \"$ac_link\") 1>&5; (eval $ac_link) 2>&5; } && test -s conftest${ac_exeext} && (./conftest; exit) 2>/dev/null
 then
   
     LIBS=$old_LIBS
@@ -84115,7 +84349,7 @@ EOF
       ac_obj=$1
       IFS=$old_IFS
       
-      PHP_CLI_OBJS="$PHP_CLI_OBJS $ac_bdir$ac_obj.lo"
+      PHP_GLOBAL_OBJS="$PHP_GLOBAL_OBJS $ac_bdir$ac_obj.lo"
 
       case $ac_src in
         *.c) ac_comp="$b_c_pre $ac_extra $ac_inc $b_c_meta -c $ac_srcdir$ac_src -o $ac_bdir$ac_obj.$b_lo $b_c_post" ;;
@@ -84205,7 +84439,7 @@ fi
 php_with_snmp=no
 
 echo $ac_n "checking for SNMP support""... $ac_c" 1>&6
-echo "configure:84209: checking for SNMP support" >&5
+echo "configure:84448: checking for SNMP support" >&5
 # Check whether --with-snmp or --without-snmp was given.
 if test "${with_snmp+set}" = set; then
   withval="$with_snmp"
@@ -84249,7 +84483,7 @@ echo "$ac_t""$ext_output" 1>&6
 php_with_openssl_dir=no
 
 echo $ac_n "checking OpenSSL dir for SNMP""... $ac_c" 1>&6
-echo "configure:84253: checking OpenSSL dir for SNMP" >&5
+echo "configure:84492: checking OpenSSL dir for SNMP" >&5
 # Check whether --with-openssl-dir or --without-openssl-dir was given.
 if test "${with_openssl_dir+set}" = set; then
   withval="$with_openssl_dir"
@@ -84272,7 +84506,7 @@ echo "$ac_t""$ext_output" 1>&6
 php_enable_ucd_snmp_hack=no
 
 echo $ac_n "checking whether to enable UCD SNMP hack""... $ac_c" 1>&6
-echo "configure:84276: checking whether to enable UCD SNMP hack" >&5
+echo "configure:84515: checking whether to enable UCD SNMP hack" >&5
 # Check whether --enable-ucd-snmp-hack or --disable-ucd-snmp-hack was given.
 if test "${enable_ucd_snmp_hack+set}" = set; then
   enableval="$enable_ucd_snmp_hack"
@@ -84297,7 +84531,7 @@ if test "$PHP_SNMP" != "no"; then
     # Extract the first word of "net-snmp-config", so it can be a program name with args.
 set dummy net-snmp-config; ac_word=$2
 echo $ac_n "checking for $ac_word""... $ac_c" 1>&6
-echo "configure:84301: checking for $ac_word" >&5
+echo "configure:84540: checking for $ac_word" >&5
 if eval "test \"`echo '$''{'ac_cv_path_SNMP_CONFIG'+set}'`\" = set"; then
   echo $ac_n "(cached) $ac_c" 1>&6
 else
@@ -84498,17 +84732,17 @@ EOF
 do
 ac_safe=`echo "$ac_hdr" | sed 'y%./+-%__p_%'`
 echo $ac_n "checking for $ac_hdr""... $ac_c" 1>&6
-echo "configure:84502: checking for $ac_hdr" >&5
+echo "configure:84741: checking for $ac_hdr" >&5
 if eval "test \"`echo '$''{'ac_cv_header_$ac_safe'+set}'`\" = set"; then
   echo $ac_n "(cached) $ac_c" 1>&6
 else
   cat > conftest.$ac_ext <<EOF
-#line 84507 "configure"
+#line 84746 "configure"
 #include "confdefs.h"
 #include <$ac_hdr>
 EOF
 ac_try="$ac_cpp conftest.$ac_ext >/dev/null 2>conftest.out"
-{ (eval echo configure:84512: \"$ac_try\") 1>&5; (eval $ac_try) 2>&5; }
+{ (eval echo configure:84751: \"$ac_try\") 1>&5; (eval $ac_try) 2>&5; }
 ac_err=`grep -v '^ *+' conftest.out | grep -v "^conftest.${ac_ext}\$"`
 if test -z "$ac_err"; then
   rm -rf conftest*
@@ -84536,9 +84770,9 @@ done
 
     if test "$ac_cv_header_default_store_h" = "yes"; then
       echo $ac_n "checking for OpenSSL support in SNMP libraries""... $ac_c" 1>&6
-echo "configure:84540: checking for OpenSSL support in SNMP libraries" >&5
+echo "configure:84779: checking for OpenSSL support in SNMP libraries" >&5
       cat > conftest.$ac_ext <<EOF
-#line 84542 "configure"
+#line 84781 "configure"
 #include "confdefs.h"
 
 #include <ucd-snmp-config.h>
@@ -84593,7 +84827,7 @@ rm -f conftest*
     # Extract the first word of "pkg-config", so it can be a program name with args.
 set dummy pkg-config; ac_word=$2
 echo $ac_n "checking for $ac_word""... $ac_c" 1>&6
-echo "configure:84597: checking for $ac_word" >&5
+echo "configure:84836: checking for $ac_word" >&5
 if eval "test \"`echo '$''{'ac_cv_path_PKG_CONFIG'+set}'`\" = set"; then
   echo $ac_n "(cached) $ac_c" 1>&6
 else
@@ -84798,9 +85032,9 @@ fi
     old_CPPFLAGS=$CPPFLAGS
     CPPFLAGS=-I$OPENSSL_INCDIR
     echo $ac_n "checking for OpenSSL version""... $ac_c" 1>&6
-echo "configure:84802: checking for OpenSSL version" >&5
+echo "configure:85041: checking for OpenSSL version" >&5
     cat > conftest.$ac_ext <<EOF
-#line 84804 "configure"
+#line 85043 "configure"
 #include "confdefs.h"
 
 #include <openssl/opensslv.h>
@@ -84955,7 +85189,7 @@ rm -f conftest*
   done
 
   echo $ac_n "checking for CRYPTO_free in -lcrypto""... $ac_c" 1>&6
-echo "configure:84959: checking for CRYPTO_free in -lcrypto" >&5
+echo "configure:85198: checking for CRYPTO_free in -lcrypto" >&5
 ac_lib_var=`echo crypto'_'CRYPTO_free | sed 'y%./+-%__p_%'`
 if eval "test \"`echo '$''{'ac_cv_lib_$ac_lib_var'+set}'`\" = set"; then
   echo $ac_n "(cached) $ac_c" 1>&6
@@ -84963,7 +85197,7 @@ else
   ac_save_LIBS="$LIBS"
 LIBS="-lcrypto  $LIBS"
 cat > conftest.$ac_ext <<EOF
-#line 84967 "configure"
+#line 85206 "configure"
 #include "confdefs.h"
 /* Override any gcc2 internal prototype to avoid an error.  */
 /* We use char because int might match the return type of a gcc2
@@ -84974,7 +85208,7 @@ int main() {
 CRYPTO_free()
 ; return 0; }
 EOF
-if { (eval echo configure:84978: \"$ac_link\") 1>&5; (eval $ac_link) 2>&5; } && test -s conftest${ac_exeext}; then
+if { (eval echo configure:85217: \"$ac_link\") 1>&5; (eval $ac_link) 2>&5; } && test -s conftest${ac_exeext}; then
   rm -rf conftest*
   eval "ac_cv_lib_$ac_lib_var=yes"
 else
@@ -85131,7 +85365,7 @@ fi
   done
 
   echo $ac_n "checking for SSL_CTX_set_ssl_version in -lssl""... $ac_c" 1>&6
-echo "configure:85135: checking for SSL_CTX_set_ssl_version in -lssl" >&5
+echo "configure:85374: checking for SSL_CTX_set_ssl_version in -lssl" >&5
 ac_lib_var=`echo ssl'_'SSL_CTX_set_ssl_version | sed 'y%./+-%__p_%'`
 if eval "test \"`echo '$''{'ac_cv_lib_$ac_lib_var'+set}'`\" = set"; then
   echo $ac_n "(cached) $ac_c" 1>&6
@@ -85139,7 +85373,7 @@ else
   ac_save_LIBS="$LIBS"
 LIBS="-lssl  $LIBS"
 cat > conftest.$ac_ext <<EOF
-#line 85143 "configure"
+#line 85382 "configure"
 #include "confdefs.h"
 /* Override any gcc2 internal prototype to avoid an error.  */
 /* We use char because int might match the return type of a gcc2
@@ -85150,7 +85384,7 @@ int main() {
 SSL_CTX_set_ssl_version()
 ; return 0; }
 EOF
-if { (eval echo configure:85154: \"$ac_link\") 1>&5; (eval $ac_link) 2>&5; } && test -s conftest${ac_exeext}; then
+if { (eval echo configure:85393: \"$ac_link\") 1>&5; (eval $ac_link) 2>&5; } && test -s conftest${ac_exeext}; then
   rm -rf conftest*
   eval "ac_cv_lib_$ac_lib_var=yes"
 else
@@ -85286,7 +85520,7 @@ else
     fi
 
     echo $ac_n "checking for kstat_read in -lkstat""... $ac_c" 1>&6
-echo "configure:85290: checking for kstat_read in -lkstat" >&5
+echo "configure:85529: checking for kstat_read in -lkstat" >&5
 ac_lib_var=`echo kstat'_'kstat_read | sed 'y%./+-%__p_%'`
 if eval "test \"`echo '$''{'ac_cv_lib_$ac_lib_var'+set}'`\" = set"; then
   echo $ac_n "(cached) $ac_c" 1>&6
@@ -85294,7 +85528,7 @@ else
   ac_save_LIBS="$LIBS"
 LIBS="-lkstat  $LIBS"
 cat > conftest.$ac_ext <<EOF
-#line 85298 "configure"
+#line 85537 "configure"
 #include "confdefs.h"
 /* Override any gcc2 internal prototype to avoid an error.  */
 /* We use char because int might match the return type of a gcc2
@@ -85305,7 +85539,7 @@ int main() {
 kstat_read()
 ; return 0; }
 EOF
-if { (eval echo configure:85309: \"$ac_link\") 1>&5; (eval $ac_link) 2>&5; } && test -s conftest${ac_exeext}; then
+if { (eval echo configure:85548: \"$ac_link\") 1>&5; (eval $ac_link) 2>&5; } && test -s conftest${ac_exeext}; then
   rm -rf conftest*
   eval "ac_cv_lib_$ac_lib_var=yes"
 else
@@ -85576,7 +85810,7 @@ fi
   done
 
   echo $ac_n "checking for snmp_parse_oid in -l$SNMP_LIBNAME""... $ac_c" 1>&6
-echo "configure:85580: checking for snmp_parse_oid in -l$SNMP_LIBNAME" >&5
+echo "configure:85819: checking for snmp_parse_oid in -l$SNMP_LIBNAME" >&5
 ac_lib_var=`echo $SNMP_LIBNAME'_'snmp_parse_oid | sed 'y%./+-%__p_%'`
 if eval "test \"`echo '$''{'ac_cv_lib_$ac_lib_var'+set}'`\" = set"; then
   echo $ac_n "(cached) $ac_c" 1>&6
@@ -85584,7 +85818,7 @@ else
   ac_save_LIBS="$LIBS"
 LIBS="-l$SNMP_LIBNAME  $LIBS"
 cat > conftest.$ac_ext <<EOF
-#line 85588 "configure"
+#line 85827 "configure"
 #include "confdefs.h"
 /* Override any gcc2 internal prototype to avoid an error.  */
 /* We use char because int might match the return type of a gcc2
@@ -85595,7 +85829,7 @@ int main() {
 snmp_parse_oid()
 ; return 0; }
 EOF
-if { (eval echo configure:85599: \"$ac_link\") 1>&5; (eval $ac_link) 2>&5; } && test -s conftest${ac_exeext}; then
+if { (eval echo configure:85838: \"$ac_link\") 1>&5; (eval $ac_link) 2>&5; } && test -s conftest${ac_exeext}; then
   rm -rf conftest*
   eval "ac_cv_lib_$ac_lib_var=yes"
 else
@@ -85729,7 +85963,7 @@ fi
   done
 
   echo $ac_n "checking for init_snmp in -l$SNMP_LIBNAME""... $ac_c" 1>&6
-echo "configure:85733: checking for init_snmp in -l$SNMP_LIBNAME" >&5
+echo "configure:85972: checking for init_snmp in -l$SNMP_LIBNAME" >&5
 ac_lib_var=`echo $SNMP_LIBNAME'_'init_snmp | sed 'y%./+-%__p_%'`
 if eval "test \"`echo '$''{'ac_cv_lib_$ac_lib_var'+set}'`\" = set"; then
   echo $ac_n "(cached) $ac_c" 1>&6
@@ -85737,7 +85971,7 @@ else
   ac_save_LIBS="$LIBS"
 LIBS="-l$SNMP_LIBNAME  $LIBS"
 cat > conftest.$ac_ext <<EOF
-#line 85741 "configure"
+#line 85980 "configure"
 #include "confdefs.h"
 /* Override any gcc2 internal prototype to avoid an error.  */
 /* We use char because int might match the return type of a gcc2
@@ -85748,7 +85982,7 @@ int main() {
 init_snmp()
 ; return 0; }
 EOF
-if { (eval echo configure:85752: \"$ac_link\") 1>&5; (eval $ac_link) 2>&5; } && test -s conftest${ac_exeext}; then
+if { (eval echo configure:85991: \"$ac_link\") 1>&5; (eval $ac_link) 2>&5; } && test -s conftest${ac_exeext}; then
   rm -rf conftest*
   eval "ac_cv_lib_$ac_lib_var=yes"
 else
@@ -86052,7 +86286,7 @@ EOF
       ac_obj=$1
       IFS=$old_IFS
       
-      PHP_CLI_OBJS="$PHP_CLI_OBJS $ac_bdir$ac_obj.lo"
+      PHP_GLOBAL_OBJS="$PHP_GLOBAL_OBJS $ac_bdir$ac_obj.lo"
 
       case $ac_src in
         *.c) ac_comp="$b_c_pre $ac_extra $ac_inc $b_c_meta -c $ac_srcdir$ac_src -o $ac_bdir$ac_obj.$b_lo $b_c_post" ;;
@@ -86095,7 +86329,7 @@ fi
 php_enable_soap=no
 
 echo $ac_n "checking whether to enable SOAP support""... $ac_c" 1>&6
-echo "configure:86099: checking whether to enable SOAP support" >&5
+echo "configure:86338: checking whether to enable SOAP support" >&5
 # Check whether --enable-soap or --disable-soap was given.
 if test "${enable_soap+set}" = set; then
   enableval="$enable_soap"
@@ -86140,7 +86374,7 @@ if test -z "$PHP_LIBXML_DIR"; then
 php_with_libxml_dir=no
 
 echo $ac_n "checking libxml2 install dir""... $ac_c" 1>&6
-echo "configure:86144: checking libxml2 install dir" >&5
+echo "configure:86383: checking libxml2 install dir" >&5
 # Check whether --with-libxml-dir or --without-libxml-dir was given.
 if test "${with_libxml_dir+set}" = set; then
   withval="$with_libxml_dir"
@@ -86168,7 +86402,7 @@ if test "$PHP_SOAP" != "no"; then
 
   
 echo $ac_n "checking for xml2-config path""... $ac_c" 1>&6
-echo "configure:86172: checking for xml2-config path" >&5
+echo "configure:86411: checking for xml2-config path" >&5
 if eval "test \"`echo '$''{'ac_cv_php_xml2_config_path'+set}'`\" = set"; then
   echo $ac_n "(cached) $ac_c" 1>&6
 else
@@ -86326,7 +86560,7 @@ echo "$ac_t""$ac_cv_php_xml2_config_path" 1>&6
 
 
             echo $ac_n "checking whether libxml build works""... $ac_c" 1>&6
-echo "configure:86330: checking whether libxml build works" >&5
+echo "configure:86569: checking whether libxml build works" >&5
 if eval "test \"`echo '$''{'php_cv_libxml_build_works'+set}'`\" = set"; then
   echo $ac_n "(cached) $ac_c" 1>&6
 else
@@ -86342,7 +86576,7 @@ else
   
 else
   cat > conftest.$ac_ext <<EOF
-#line 86346 "configure"
+#line 86585 "configure"
 #include "confdefs.h"
 
     
@@ -86353,7 +86587,7 @@ else
     }
   
 EOF
-if { (eval echo configure:86357: \"$ac_link\") 1>&5; (eval $ac_link) 2>&5; } && test -s conftest${ac_exeext} && (./conftest; exit) 2>/dev/null
+if { (eval echo configure:86596: \"$ac_link\") 1>&5; (eval $ac_link) 2>&5; } && test -s conftest${ac_exeext} && (./conftest; exit) 2>/dev/null
 then
   
     LIBS=$old_LIBS
@@ -86651,7 +86885,7 @@ EOF
       ac_obj=$1
       IFS=$old_IFS
       
-      PHP_CLI_OBJS="$PHP_CLI_OBJS $ac_bdir$ac_obj.lo"
+      PHP_GLOBAL_OBJS="$PHP_GLOBAL_OBJS $ac_bdir$ac_obj.lo"
 
       case $ac_src in
         *.c) ac_comp="$b_c_pre $ac_extra $ac_inc $b_c_meta -c $ac_srcdir$ac_src -o $ac_bdir$ac_obj.$b_lo $b_c_post" ;;
@@ -86703,7 +86937,7 @@ fi
 php_enable_sockets=no
 
 echo $ac_n "checking whether to enable sockets support""... $ac_c" 1>&6
-echo "configure:86707: checking whether to enable sockets support" >&5
+echo "configure:86946: checking whether to enable sockets support" >&5
 # Check whether --enable-sockets or --disable-sockets was given.
 if test "${enable_sockets+set}" = set; then
   enableval="$enable_sockets"
@@ -86745,13 +86979,13 @@ echo "$ac_t""$ext_output" 1>&6
 
 if test "$PHP_SOCKETS" != "no"; then
     echo $ac_n "checking for struct cmsghdr""... $ac_c" 1>&6
-echo "configure:86749: checking for struct cmsghdr" >&5
+echo "configure:86988: checking for struct cmsghdr" >&5
 if eval "test \"`echo '$''{'ac_cv_cmsghdr'+set}'`\" = set"; then
   echo $ac_n "(cached) $ac_c" 1>&6
 else
   
     cat > conftest.$ac_ext <<EOF
-#line 86755 "configure"
+#line 86994 "configure"
 #include "confdefs.h"
 
 #include <sys/types.h>
@@ -86760,7 +86994,7 @@ int main() {
 struct cmsghdr s; s
 ; return 0; }
 EOF
-if { (eval echo configure:86764: \"$ac_compile\") 1>&5; (eval $ac_compile) 2>&5; }; then
+if { (eval echo configure:87003: \"$ac_compile\") 1>&5; (eval $ac_compile) 2>&5; }; then
   rm -rf conftest*
   ac_cv_cmsghdr=yes
 else
@@ -86785,12 +87019,12 @@ EOF
   for ac_func in hstrerror socketpair
 do
 echo $ac_n "checking for $ac_func""... $ac_c" 1>&6
-echo "configure:86789: checking for $ac_func" >&5
+echo "configure:87028: checking for $ac_func" >&5
 if eval "test \"`echo '$''{'ac_cv_func_$ac_func'+set}'`\" = set"; then
   echo $ac_n "(cached) $ac_c" 1>&6
 else
   cat > conftest.$ac_ext <<EOF
-#line 86794 "configure"
+#line 87033 "configure"
 #include "confdefs.h"
 /* System header to define __stub macros and hopefully few prototypes,
     which can conflict with char $ac_func(); below.  */
@@ -86813,7 +87047,7 @@ $ac_func();
 
 ; return 0; }
 EOF
-if { (eval echo configure:86817: \"$ac_link\") 1>&5; (eval $ac_link) 2>&5; } && test -s conftest${ac_exeext}; then
+if { (eval echo configure:87056: \"$ac_link\") 1>&5; (eval $ac_link) 2>&5; } && test -s conftest${ac_exeext}; then
   rm -rf conftest*
   eval "ac_cv_func_$ac_func=yes"
 else
@@ -86841,17 +87075,17 @@ done
 do
 ac_safe=`echo "$ac_hdr" | sed 'y%./+-%__p_%'`
 echo $ac_n "checking for $ac_hdr""... $ac_c" 1>&6
-echo "configure:86845: checking for $ac_hdr" >&5
+echo "configure:87084: checking for $ac_hdr" >&5
 if eval "test \"`echo '$''{'ac_cv_header_$ac_safe'+set}'`\" = set"; then
   echo $ac_n "(cached) $ac_c" 1>&6
 else
   cat > conftest.$ac_ext <<EOF
-#line 86850 "configure"
+#line 87089 "configure"
 #include "confdefs.h"
 #include <$ac_hdr>
 EOF
 ac_try="$ac_cpp conftest.$ac_ext >/dev/null 2>conftest.out"
-{ (eval echo configure:86855: \"$ac_try\") 1>&5; (eval $ac_try) 2>&5; }
+{ (eval echo configure:87094: \"$ac_try\") 1>&5; (eval $ac_try) 2>&5; }
 ac_err=`grep -v '^ *+' conftest.out | grep -v "^conftest.${ac_ext}\$"`
 if test -z "$ac_err"; then
   rm -rf conftest*
@@ -86878,7 +87112,7 @@ fi
 done
 
   cat > conftest.$ac_ext <<EOF
-#line 86882 "configure"
+#line 87121 "configure"
 #include "confdefs.h"
 
 #include <sys/types.h>
@@ -86888,7 +87122,7 @@ int main() {
 static struct msghdr tp; int n = (int) tp.msg_flags; return n
 ; return 0; }
 EOF
-if { (eval echo configure:86892: \"$ac_compile\") 1>&5; (eval $ac_compile) 2>&5; }; then
+if { (eval echo configure:87131: \"$ac_compile\") 1>&5; (eval $ac_compile) 2>&5; }; then
   :
 else
   echo "configure: failed program was:" >&5
@@ -87165,7 +87399,7 @@ EOF
       ac_obj=$1
       IFS=$old_IFS
       
-      PHP_CLI_OBJS="$PHP_CLI_OBJS $ac_bdir$ac_obj.lo"
+      PHP_GLOBAL_OBJS="$PHP_GLOBAL_OBJS $ac_bdir$ac_obj.lo"
 
       case $ac_src in
         *.c) ac_comp="$b_c_pre $ac_extra $ac_inc $b_c_meta -c $ac_srcdir$ac_src -o $ac_bdir$ac_obj.$b_lo $b_c_post" ;;
@@ -87222,7 +87456,7 @@ fi
 
 
   echo $ac_n "checking whether zend_object_value is packed""... $ac_c" 1>&6
-echo "configure:87226: checking whether zend_object_value is packed" >&5
+echo "configure:87465: checking whether zend_object_value is packed" >&5
   old_CPPFLAGS=$CPPFLAGS
   CPPFLAGS="$INCLUDES -I$abs_srcdir $CPPFLAGS"
   if test "$cross_compiling" = yes; then
@@ -87232,7 +87466,7 @@ echo "configure:87226: checking whether zend_object_value is packed" >&5
   
 else
   cat > conftest.$ac_ext <<EOF
-#line 87236 "configure"
+#line 87475 "configure"
 #include "confdefs.h"
 
 #include "Zend/zend_types.h"
@@ -87241,7 +87475,7 @@ int main(int argc, char **argv) {
 }
   
 EOF
-if { (eval echo configure:87245: \"$ac_link\") 1>&5; (eval $ac_link) 2>&5; } && test -s conftest${ac_exeext} && (./conftest; exit) 2>/dev/null
+if { (eval echo configure:87484: \"$ac_link\") 1>&5; (eval $ac_link) 2>&5; } && test -s conftest${ac_exeext} && (./conftest; exit) 2>/dev/null
 then
   
     ac_result=1
@@ -87527,7 +87761,7 @@ EOF
       ac_obj=$1
       IFS=$old_IFS
       
-      PHP_CLI_OBJS="$PHP_CLI_OBJS $ac_bdir$ac_obj.lo"
+      PHP_GLOBAL_OBJS="$PHP_GLOBAL_OBJS $ac_bdir$ac_obj.lo"
 
       case $ac_src in
         *.c) ac_comp="$b_c_pre $ac_extra $ac_inc $b_c_meta -c $ac_srcdir$ac_src -o $ac_bdir$ac_obj.$b_lo $b_c_post" ;;
@@ -87605,7 +87839,7 @@ but you've either not enabled pcre, or have disabled it.
 php_with_sqlite=yes
 
 echo $ac_n "checking for sqlite support""... $ac_c" 1>&6
-echo "configure:87609: checking for sqlite support" >&5
+echo "configure:87848: checking for sqlite support" >&5
 # Check whether --with-sqlite or --without-sqlite was given.
 if test "${with_sqlite+set}" = set; then
   withval="$with_sqlite"
@@ -87649,7 +87883,7 @@ echo "$ac_t""$ext_output" 1>&6
 php_enable_sqlite_utf8=no
 
 echo $ac_n "checking whether to enable UTF-8 support in sqlite (default: ISO-8859-1)""... $ac_c" 1>&6
-echo "configure:87653: checking whether to enable UTF-8 support in sqlite (default: ISO-8859-1)" >&5
+echo "configure:87892: checking whether to enable UTF-8 support in sqlite (default: ISO-8859-1)" >&5
 # Check whether --enable-sqlite-utf8 or --disable-sqlite-utf8 was given.
 if test "${enable_sqlite_utf8+set}" = set; then
   enableval="$enable_sqlite_utf8"
@@ -87677,13 +87911,13 @@ if test "$PHP_SQLITE" != "no"; then
   if test "$PHP_PDO" != "no"; then
     
   echo $ac_n "checking for PDO includes""... $ac_c" 1>&6
-echo "configure:87681: checking for PDO includes" >&5
+echo "configure:87920: checking for PDO includes" >&5
 if eval "test \"`echo '$''{'pdo_inc_path'+set}'`\" = set"; then
   echo $ac_n "(cached) $ac_c" 1>&6
 else
   
     echo $ac_n "checking for PDO includes""... $ac_c" 1>&6
-echo "configure:87687: checking for PDO includes" >&5
+echo "configure:87926: checking for PDO includes" >&5
     if test -f $abs_srcdir/include/php/ext/pdo/php_pdo_driver.h; then
       pdo_inc_path=$abs_srcdir/ext
     elif test -f $abs_srcdir/ext/pdo/php_pdo_driver.h; then
@@ -87717,7 +87951,7 @@ EOF
       SQLITE_DIR=$PHP_SQLITE
     else # search default path list
       echo $ac_n "checking for sqlite files in default path""... $ac_c" 1>&6
-echo "configure:87721: checking for sqlite files in default path" >&5
+echo "configure:87960: checking for sqlite files in default path" >&5
       for i in $SEARCH_PATH ; do
         if test -r $i/$SEARCH_FOR; then
           SQLITE_DIR=$i
@@ -87829,7 +88063,7 @@ echo "configure:87721: checking for sqlite files in default path" >&5
   done
 
   echo $ac_n "checking for sqlite_open in -lsqlite""... $ac_c" 1>&6
-echo "configure:87833: checking for sqlite_open in -lsqlite" >&5
+echo "configure:88072: checking for sqlite_open in -lsqlite" >&5
 ac_lib_var=`echo sqlite'_'sqlite_open | sed 'y%./+-%__p_%'`
 if eval "test \"`echo '$''{'ac_cv_lib_$ac_lib_var'+set}'`\" = set"; then
   echo $ac_n "(cached) $ac_c" 1>&6
@@ -87837,7 +88071,7 @@ else
   ac_save_LIBS="$LIBS"
 LIBS="-lsqlite  $LIBS"
 cat > conftest.$ac_ext <<EOF
-#line 87841 "configure"
+#line 88080 "configure"
 #include "confdefs.h"
 /* Override any gcc2 internal prototype to avoid an error.  */
 /* We use char because int might match the return type of a gcc2
@@ -87848,7 +88082,7 @@ int main() {
 sqlite_open()
 ; return 0; }
 EOF
-if { (eval echo configure:87852: \"$ac_link\") 1>&5; (eval $ac_link) 2>&5; } && test -s conftest${ac_exeext}; then
+if { (eval echo configure:88091: \"$ac_link\") 1>&5; (eval $ac_link) 2>&5; } && test -s conftest${ac_exeext}; then
   rm -rf conftest*
   eval "ac_cv_lib_$ac_lib_var=yes"
 else
@@ -88021,7 +88255,7 @@ fi
   # Extract the first word of "lemon", so it can be a program name with args.
 set dummy lemon; ac_word=$2
 echo $ac_n "checking for $ac_word""... $ac_c" 1>&6
-echo "configure:88025: checking for $ac_word" >&5
+echo "configure:88264: checking for $ac_word" >&5
 if eval "test \"`echo '$''{'ac_cv_prog_LEMON'+set}'`\" = set"; then
   echo $ac_n "(cached) $ac_c" 1>&6
 else
@@ -88049,7 +88283,7 @@ fi
 
   if test "$LEMON"; then
     echo $ac_n "checking for lemon version""... $ac_c" 1>&6
-echo "configure:88053: checking for lemon version" >&5
+echo "configure:88292: checking for lemon version" >&5
 if eval "test \"`echo '$''{'php_cv_lemon_version'+set}'`\" = set"; then
   echo $ac_n "(cached) $ac_c" 1>&6
 else
@@ -88355,7 +88589,7 @@ EOF
       ac_obj=$1
       IFS=$old_IFS
       
-      PHP_CLI_OBJS="$PHP_CLI_OBJS $ac_bdir$ac_obj.lo"
+      PHP_GLOBAL_OBJS="$PHP_GLOBAL_OBJS $ac_bdir$ac_obj.lo"
 
       case $ac_src in
         *.c) ac_comp="$b_c_pre $ac_extra $ac_inc $b_c_meta -c $ac_srcdir$ac_src -o $ac_bdir$ac_obj.$b_lo $b_c_post" ;;
@@ -88462,7 +88696,7 @@ but you've either not enabled pdo, or have disabled it.
   
 
     echo $ac_n "checking size of char *""... $ac_c" 1>&6
-echo "configure:88466: checking size of char *" >&5
+echo "configure:88705: checking size of char *" >&5
 if eval "test \"`echo '$''{'ac_cv_sizeof_char_p'+set}'`\" = set"; then
   echo $ac_n "(cached) $ac_c" 1>&6
 else
@@ -88470,7 +88704,7 @@ else
   ac_cv_sizeof_char_p=4
 else
   cat > conftest.$ac_ext <<EOF
-#line 88474 "configure"
+#line 88713 "configure"
 #include "confdefs.h"
 #include <stdio.h>
 main()
@@ -88481,7 +88715,7 @@ main()
   exit(0);
 }
 EOF
-if { (eval echo configure:88485: \"$ac_link\") 1>&5; (eval $ac_link) 2>&5; } && test -s conftest${ac_exeext} && (./conftest; exit) 2>/dev/null
+if { (eval echo configure:88724: \"$ac_link\") 1>&5; (eval $ac_link) 2>&5; } && test -s conftest${ac_exeext} && (./conftest; exit) 2>/dev/null
 then
   ac_cv_sizeof_char_p=`cat conftestval`
 else
@@ -88543,12 +88777,12 @@ EOF
   for ac_func in usleep nanosleep
 do
 echo $ac_n "checking for $ac_func""... $ac_c" 1>&6
-echo "configure:88547: checking for $ac_func" >&5
+echo "configure:88786: checking for $ac_func" >&5
 if eval "test \"`echo '$''{'ac_cv_func_$ac_func'+set}'`\" = set"; then
   echo $ac_n "(cached) $ac_c" 1>&6
 else
   cat > conftest.$ac_ext <<EOF
-#line 88552 "configure"
+#line 88791 "configure"
 #include "confdefs.h"
 /* System header to define __stub macros and hopefully few prototypes,
     which can conflict with char $ac_func(); below.  */
@@ -88571,7 +88805,7 @@ $ac_func();
 
 ; return 0; }
 EOF
-if { (eval echo configure:88575: \"$ac_link\") 1>&5; (eval $ac_link) 2>&5; } && test -s conftest${ac_exeext}; then
+if { (eval echo configure:88814: \"$ac_link\") 1>&5; (eval $ac_link) 2>&5; } && test -s conftest${ac_exeext}; then
   rm -rf conftest*
   eval "ac_cv_func_$ac_func=yes"
 else
@@ -88599,17 +88833,17 @@ done
 do
 ac_safe=`echo "$ac_hdr" | sed 'y%./+-%__p_%'`
 echo $ac_n "checking for $ac_hdr""... $ac_c" 1>&6
-echo "configure:88603: checking for $ac_hdr" >&5
+echo "configure:88842: checking for $ac_hdr" >&5
 if eval "test \"`echo '$''{'ac_cv_header_$ac_safe'+set}'`\" = set"; then
   echo $ac_n "(cached) $ac_c" 1>&6
 else
   cat > conftest.$ac_ext <<EOF
-#line 88608 "configure"
+#line 88847 "configure"
 #include "confdefs.h"
 #include <$ac_hdr>
 EOF
 ac_try="$ac_cpp conftest.$ac_ext >/dev/null 2>conftest.out"
-{ (eval echo configure:88613: \"$ac_try\") 1>&5; (eval $ac_try) 2>&5; }
+{ (eval echo configure:88852: \"$ac_try\") 1>&5; (eval $ac_try) 2>&5; }
 ac_err=`grep -v '^ *+' conftest.out | grep -v "^conftest.${ac_ext}\$"`
 if test -z "$ac_err"; then
   rm -rf conftest*
@@ -88640,7 +88874,7 @@ fi
 
 
 echo $ac_n "checking whether flush should be called explicitly after a buffered io""... $ac_c" 1>&6
-echo "configure:88644: checking whether flush should be called explicitly after a buffered io" >&5
+echo "configure:88883: checking whether flush should be called explicitly after a buffered io" >&5
 if eval "test \"`echo '$''{'ac_cv_flush_io'+set}'`\" = set"; then
   echo $ac_n "(cached) $ac_c" 1>&6
 else
@@ -88651,7 +88885,7 @@ if test "$cross_compiling" = yes; then
 
 else
   cat > conftest.$ac_ext <<EOF
-#line 88655 "configure"
+#line 88894 "configure"
 #include "confdefs.h"
 
 #include <stdio.h>
@@ -88689,7 +88923,7 @@ int main(int argc, char **argv)
 }
 
 EOF
-if { (eval echo configure:88693: \"$ac_link\") 1>&5; (eval $ac_link) 2>&5; } && test -s conftest${ac_exeext} && (./conftest; exit) 2>/dev/null
+if { (eval echo configure:88932: \"$ac_link\") 1>&5; (eval $ac_link) 2>&5; } && test -s conftest${ac_exeext} && (./conftest; exit) 2>/dev/null
 then
   
   ac_cv_flush_io=no
@@ -88717,7 +88951,7 @@ fi
 
 if test "$ac_cv_func_crypt" = "no"; then
   echo $ac_n "checking for crypt in -lcrypt""... $ac_c" 1>&6
-echo "configure:88721: checking for crypt in -lcrypt" >&5
+echo "configure:88960: checking for crypt in -lcrypt" >&5
 ac_lib_var=`echo crypt'_'crypt | sed 'y%./+-%__p_%'`
 if eval "test \"`echo '$''{'ac_cv_lib_$ac_lib_var'+set}'`\" = set"; then
   echo $ac_n "(cached) $ac_c" 1>&6
@@ -88725,7 +88959,7 @@ else
   ac_save_LIBS="$LIBS"
 LIBS="-lcrypt  $LIBS"
 cat > conftest.$ac_ext <<EOF
-#line 88729 "configure"
+#line 88968 "configure"
 #include "confdefs.h"
 /* Override any gcc2 internal prototype to avoid an error.  */
 /* We use char because int might match the return type of a gcc2
@@ -88736,7 +88970,7 @@ int main() {
 crypt()
 ; return 0; }
 EOF
-if { (eval echo configure:88740: \"$ac_link\") 1>&5; (eval $ac_link) 2>&5; } && test -s conftest${ac_exeext}; then
+if { (eval echo configure:88979: \"$ac_link\") 1>&5; (eval $ac_link) 2>&5; } && test -s conftest${ac_exeext}; then
   rm -rf conftest*
   eval "ac_cv_lib_$ac_lib_var=yes"
 else
@@ -88765,7 +88999,7 @@ fi
 fi
   
 echo $ac_n "checking for standard DES crypt""... $ac_c" 1>&6
-echo "configure:88769: checking for standard DES crypt" >&5
+echo "configure:89008: checking for standard DES crypt" >&5
 if eval "test \"`echo '$''{'ac_cv_crypt_des'+set}'`\" = set"; then
   echo $ac_n "(cached) $ac_c" 1>&6
 else
@@ -88776,7 +89010,7 @@ else
 
 else
   cat > conftest.$ac_ext <<EOF
-#line 88780 "configure"
+#line 89019 "configure"
 #include "confdefs.h"
 
 #if HAVE_UNISTD_H
@@ -88795,7 +89029,7 @@ main() {
 #endif
 }
 EOF
-if { (eval echo configure:88799: \"$ac_link\") 1>&5; (eval $ac_link) 2>&5; } && test -s conftest${ac_exeext} && (./conftest; exit) 2>/dev/null
+if { (eval echo configure:89038: \"$ac_link\") 1>&5; (eval $ac_link) 2>&5; } && test -s conftest${ac_exeext} && (./conftest; exit) 2>/dev/null
 then
   
   ac_cv_crypt_des=yes
@@ -88816,7 +89050,7 @@ fi
 echo "$ac_t""$ac_cv_crypt_des" 1>&6
 
 echo $ac_n "checking for extended DES crypt""... $ac_c" 1>&6
-echo "configure:88820: checking for extended DES crypt" >&5
+echo "configure:89059: checking for extended DES crypt" >&5
 if eval "test \"`echo '$''{'ac_cv_crypt_ext_des'+set}'`\" = set"; then
   echo $ac_n "(cached) $ac_c" 1>&6
 else
@@ -88827,7 +89061,7 @@ else
 
 else
   cat > conftest.$ac_ext <<EOF
-#line 88831 "configure"
+#line 89070 "configure"
 #include "confdefs.h"
 
 #if HAVE_UNISTD_H
@@ -88846,7 +89080,7 @@ main() {
 #endif
 }
 EOF
-if { (eval echo configure:88850: \"$ac_link\") 1>&5; (eval $ac_link) 2>&5; } && test -s conftest${ac_exeext} && (./conftest; exit) 2>/dev/null
+if { (eval echo configure:89089: \"$ac_link\") 1>&5; (eval $ac_link) 2>&5; } && test -s conftest${ac_exeext} && (./conftest; exit) 2>/dev/null
 then
   
   ac_cv_crypt_ext_des=yes
@@ -88867,7 +89101,7 @@ fi
 echo "$ac_t""$ac_cv_crypt_ext_des" 1>&6
 
 echo $ac_n "checking for MD5 crypt""... $ac_c" 1>&6
-echo "configure:88871: checking for MD5 crypt" >&5
+echo "configure:89110: checking for MD5 crypt" >&5
 if eval "test \"`echo '$''{'ac_cv_crypt_md5'+set}'`\" = set"; then
   echo $ac_n "(cached) $ac_c" 1>&6
 else
@@ -88878,7 +89112,7 @@ if test "$cross_compiling" = yes; then
 
 else
   cat > conftest.$ac_ext <<EOF
-#line 88882 "configure"
+#line 89121 "configure"
 #include "confdefs.h"
 
 #if HAVE_UNISTD_H
@@ -88906,7 +89140,7 @@ main() {
 #endif
 }
 EOF
-if { (eval echo configure:88910: \"$ac_link\") 1>&5; (eval $ac_link) 2>&5; } && test -s conftest${ac_exeext} && (./conftest; exit) 2>/dev/null
+if { (eval echo configure:89149: \"$ac_link\") 1>&5; (eval $ac_link) 2>&5; } && test -s conftest${ac_exeext} && (./conftest; exit) 2>/dev/null
 then
   
   ac_cv_crypt_md5=yes
@@ -88927,7 +89161,7 @@ fi
 echo "$ac_t""$ac_cv_crypt_md5" 1>&6
 
 echo $ac_n "checking for Blowfish crypt""... $ac_c" 1>&6
-echo "configure:88931: checking for Blowfish crypt" >&5
+echo "configure:89170: checking for Blowfish crypt" >&5
 if eval "test \"`echo '$''{'ac_cv_crypt_blowfish'+set}'`\" = set"; then
   echo $ac_n "(cached) $ac_c" 1>&6
 else
@@ -88938,7 +89172,7 @@ if test "$cross_compiling" = yes; then
 
 else
   cat > conftest.$ac_ext <<EOF
-#line 88942 "configure"
+#line 89181 "configure"
 #include "confdefs.h"
 
 #if HAVE_UNISTD_H
@@ -88963,7 +89197,7 @@ main() {
 #endif
 }
 EOF
-if { (eval echo configure:88967: \"$ac_link\") 1>&5; (eval $ac_link) 2>&5; } && test -s conftest${ac_exeext} && (./conftest; exit) 2>/dev/null
+if { (eval echo configure:89206: \"$ac_link\") 1>&5; (eval $ac_link) 2>&5; } && test -s conftest${ac_exeext} && (./conftest; exit) 2>/dev/null
 then
   
   ac_cv_crypt_blowfish=yes
@@ -88984,7 +89218,7 @@ fi
 echo "$ac_t""$ac_cv_crypt_blowfish" 1>&6
 
 echo $ac_n "checking for SHA512 crypt""... $ac_c" 1>&6
-echo "configure:88988: checking for SHA512 crypt" >&5
+echo "configure:89227: checking for SHA512 crypt" >&5
 if eval "test \"`echo '$''{'ac_cv_crypt_SHA512'+set}'`\" = set"; then
   echo $ac_n "(cached) $ac_c" 1>&6
 else
@@ -88995,7 +89229,7 @@ if test "$cross_compiling" = yes; then
 
 else
   cat > conftest.$ac_ext <<EOF
-#line 88999 "configure"
+#line 89238 "configure"
 #include "confdefs.h"
 
 #if HAVE_UNISTD_H
@@ -89019,7 +89253,7 @@ main() {
 #endif
 }
 EOF
-if { (eval echo configure:89023: \"$ac_link\") 1>&5; (eval $ac_link) 2>&5; } && test -s conftest${ac_exeext} && (./conftest; exit) 2>/dev/null
+if { (eval echo configure:89262: \"$ac_link\") 1>&5; (eval $ac_link) 2>&5; } && test -s conftest${ac_exeext} && (./conftest; exit) 2>/dev/null
 then
   
   ac_cv_crypt_SHA512=yes
@@ -89040,7 +89274,7 @@ fi
 echo "$ac_t""$ac_cv_crypt_SHA512" 1>&6
 
 echo $ac_n "checking for SHA256 crypt""... $ac_c" 1>&6
-echo "configure:89044: checking for SHA256 crypt" >&5
+echo "configure:89283: checking for SHA256 crypt" >&5
 if eval "test \"`echo '$''{'ac_cv_crypt_SHA256'+set}'`\" = set"; then
   echo $ac_n "(cached) $ac_c" 1>&6
 else
@@ -89051,7 +89285,7 @@ if test "$cross_compiling" = yes; then
 
 else
   cat > conftest.$ac_ext <<EOF
-#line 89055 "configure"
+#line 89294 "configure"
 #include "confdefs.h"
 
 #if HAVE_UNISTD_H
@@ -89075,7 +89309,7 @@ main() {
 #endif
 }
 EOF
-if { (eval echo configure:89079: \"$ac_link\") 1>&5; (eval $ac_link) 2>&5; } && test -s conftest${ac_exeext} && (./conftest; exit) 2>/dev/null
+if { (eval echo configure:89318: \"$ac_link\") 1>&5; (eval $ac_link) 2>&5; } && test -s conftest${ac_exeext} && (./conftest; exit) 2>/dev/null
 then
   
   ac_cv_crypt_SHA256=yes
@@ -89099,13 +89333,13 @@ echo "$ac_t""$ac_cv_crypt_SHA256" 1>&6
 if test "$ac_cv_crypt_blowfish" = "no" || test "$ac_cv_crypt_des" = "no" || test "$ac_cv_crypt_ext_des" = "no" || test "x$php_crypt_r" = "x0"; then
 
         echo $ac_n "checking whether the compiler supports __alignof__""... $ac_c" 1>&6
-echo "configure:89103: checking whether the compiler supports __alignof__" >&5
+echo "configure:89342: checking whether the compiler supports __alignof__" >&5
 if eval "test \"`echo '$''{'ac_cv_alignof_exists'+set}'`\" = set"; then
   echo $ac_n "(cached) $ac_c" 1>&6
 else
   
   cat > conftest.$ac_ext <<EOF
-#line 89109 "configure"
+#line 89348 "configure"
 #include "confdefs.h"
 
   
@@ -89115,7 +89349,7 @@ int main() {
   
 ; return 0; }
 EOF
-if { (eval echo configure:89119: \"$ac_compile\") 1>&5; (eval $ac_compile) 2>&5; }; then
+if { (eval echo configure:89358: \"$ac_compile\") 1>&5; (eval $ac_compile) 2>&5; }; then
   rm -rf conftest*
   
     ac_cv_alignof_exists=yes
@@ -89140,13 +89374,13 @@ EOF
   fi
 
         echo $ac_n "checking whether the compiler supports aligned attribute""... $ac_c" 1>&6
-echo "configure:89144: checking whether the compiler supports aligned attribute" >&5
+echo "configure:89383: checking whether the compiler supports aligned attribute" >&5
 if eval "test \"`echo '$''{'ac_cv_attribute_aligned'+set}'`\" = set"; then
   echo $ac_n "(cached) $ac_c" 1>&6
 else
   
   cat > conftest.$ac_ext <<EOF
-#line 89150 "configure"
+#line 89389 "configure"
 #include "confdefs.h"
 
   
@@ -89156,7 +89390,7 @@ int main() {
   
 ; return 0; }
 EOF
-if { (eval echo configure:89160: \"$ac_compile\") 1>&5; (eval $ac_compile) 2>&5; }; then
+if { (eval echo configure:89399: \"$ac_compile\") 1>&5; (eval $ac_compile) 2>&5; }; then
   rm -rf conftest*
   
     ac_cv_attribute_aligned=yes
@@ -89335,12 +89569,12 @@ fi
 for ac_func in getcwd getwd asinh acosh atanh log1p hypot glob strfmon nice fpclass isinf isnan mempcpy strpncpy
 do
 echo $ac_n "checking for $ac_func""... $ac_c" 1>&6
-echo "configure:89339: checking for $ac_func" >&5
+echo "configure:89578: checking for $ac_func" >&5
 if eval "test \"`echo '$''{'ac_cv_func_$ac_func'+set}'`\" = set"; then
   echo $ac_n "(cached) $ac_c" 1>&6
 else
   cat > conftest.$ac_ext <<EOF
-#line 89344 "configure"
+#line 89583 "configure"
 #include "confdefs.h"
 /* System header to define __stub macros and hopefully few prototypes,
     which can conflict with char $ac_func(); below.  */
@@ -89363,7 +89597,7 @@ $ac_func();
 
 ; return 0; }
 EOF
-if { (eval echo configure:89367: \"$ac_link\") 1>&5; (eval $ac_link) 2>&5; } && test -s conftest${ac_exeext}; then
+if { (eval echo configure:89606: \"$ac_link\") 1>&5; (eval $ac_link) 2>&5; } && test -s conftest${ac_exeext}; then
   rm -rf conftest*
   eval "ac_cv_func_$ac_func=yes"
 else
@@ -89388,7 +89622,7 @@ fi
 done
 
 echo $ac_n "checking for working fnmatch""... $ac_c" 1>&6
-echo "configure:89392: checking for working fnmatch" >&5
+echo "configure:89631: checking for working fnmatch" >&5
 if eval "test \"`echo '$''{'ac_cv_func_fnmatch_works'+set}'`\" = set"; then
   echo $ac_n "(cached) $ac_c" 1>&6
 else
@@ -89399,11 +89633,11 @@ if test "$cross_compiling" = yes; then
   ac_cv_func_fnmatch_works=no
 else
   cat > conftest.$ac_ext <<EOF
-#line 89403 "configure"
+#line 89642 "configure"
 #include "confdefs.h"
 main() { exit (fnmatch ("a*", "abc", 0) != 0); }
 EOF
-if { (eval echo configure:89407: \"$ac_link\") 1>&5; (eval $ac_link) 2>&5; } && test -s conftest${ac_exeext} && (./conftest; exit) 2>/dev/null
+if { (eval echo configure:89646: \"$ac_link\") 1>&5; (eval $ac_link) 2>&5; } && test -s conftest${ac_exeext} && (./conftest; exit) 2>/dev/null
 then
   ac_cv_func_fnmatch_works=yes
 else
@@ -89430,12 +89664,12 @@ fi
 for ac_func in fork CreateProcess
 do
 echo $ac_n "checking for $ac_func""... $ac_c" 1>&6
-echo "configure:89434: checking for $ac_func" >&5
+echo "configure:89673: checking for $ac_func" >&5
 if eval "test \"`echo '$''{'ac_cv_func_$ac_func'+set}'`\" = set"; then
   echo $ac_n "(cached) $ac_c" 1>&6
 else
   cat > conftest.$ac_ext <<EOF
-#line 89439 "configure"
+#line 89678 "configure"
 #include "confdefs.h"
 /* System header to define __stub macros and hopefully few prototypes,
     which can conflict with char $ac_func(); below.  */
@@ -89458,7 +89692,7 @@ $ac_func();
 
 ; return 0; }
 EOF
-if { (eval echo configure:89462: \"$ac_link\") 1>&5; (eval $ac_link) 2>&5; } && test -s conftest${ac_exeext}; then
+if { (eval echo configure:89701: \"$ac_link\") 1>&5; (eval $ac_link) 2>&5; } && test -s conftest${ac_exeext}; then
   rm -rf conftest*
   eval "ac_cv_func_$ac_func=yes"
 else
@@ -89488,7 +89722,7 @@ fi
 done
 
 echo $ac_n "checking if your OS can spawn processes with inherited handles""... $ac_c" 1>&6
-echo "configure:89492: checking if your OS can spawn processes with inherited handles" >&5
+echo "configure:89731: checking if your OS can spawn processes with inherited handles" >&5
 if test "$php_can_support_proc_open" = "yes"; then
   echo "$ac_t""yes" 1>&6
   cat >> confdefs.h <<\EOF
@@ -89512,12 +89746,12 @@ fi
   unset found
   
   echo $ac_n "checking for res_nsearch""... $ac_c" 1>&6
-echo "configure:89516: checking for res_nsearch" >&5
+echo "configure:89755: checking for res_nsearch" >&5
 if eval "test \"`echo '$''{'ac_cv_func_res_nsearch'+set}'`\" = set"; then
   echo $ac_n "(cached) $ac_c" 1>&6
 else
   cat > conftest.$ac_ext <<EOF
-#line 89521 "configure"
+#line 89760 "configure"
 #include "confdefs.h"
 /* System header to define __stub macros and hopefully few prototypes,
     which can conflict with char res_nsearch(); below.  */
@@ -89540,7 +89774,7 @@ res_nsearch();
 
 ; return 0; }
 EOF
-if { (eval echo configure:89544: \"$ac_link\") 1>&5; (eval $ac_link) 2>&5; } && test -s conftest${ac_exeext}; then
+if { (eval echo configure:89783: \"$ac_link\") 1>&5; (eval $ac_link) 2>&5; } && test -s conftest${ac_exeext}; then
   rm -rf conftest*
   eval "ac_cv_func_res_nsearch=yes"
 else
@@ -89558,12 +89792,12 @@ if eval "test \"`echo '$ac_cv_func_'res_nsearch`\" = yes"; then
 else
   echo "$ac_t""no" 1>&6
  echo $ac_n "checking for __res_nsearch""... $ac_c" 1>&6
-echo "configure:89562: checking for __res_nsearch" >&5
+echo "configure:89801: checking for __res_nsearch" >&5
 if eval "test \"`echo '$''{'ac_cv_func___res_nsearch'+set}'`\" = set"; then
   echo $ac_n "(cached) $ac_c" 1>&6
 else
   cat > conftest.$ac_ext <<EOF
-#line 89567 "configure"
+#line 89806 "configure"
 #include "confdefs.h"
 /* System header to define __stub macros and hopefully few prototypes,
     which can conflict with char __res_nsearch(); below.  */
@@ -89586,7 +89820,7 @@ __res_nsearch();
 
 ; return 0; }
 EOF
-if { (eval echo configure:89590: \"$ac_link\") 1>&5; (eval $ac_link) 2>&5; } && test -s conftest${ac_exeext}; then
+if { (eval echo configure:89829: \"$ac_link\") 1>&5; (eval $ac_link) 2>&5; } && test -s conftest${ac_exeext}; then
   rm -rf conftest*
   eval "ac_cv_func___res_nsearch=yes"
 else
@@ -89624,7 +89858,7 @@ EOF
   unset ac_cv_lib_resolv___res_nsearch
   unset found
   echo $ac_n "checking for res_nsearch in -lresolv""... $ac_c" 1>&6
-echo "configure:89628: checking for res_nsearch in -lresolv" >&5
+echo "configure:89867: checking for res_nsearch in -lresolv" >&5
 ac_lib_var=`echo resolv'_'res_nsearch | sed 'y%./+-%__p_%'`
 if eval "test \"`echo '$''{'ac_cv_lib_$ac_lib_var'+set}'`\" = set"; then
   echo $ac_n "(cached) $ac_c" 1>&6
@@ -89632,7 +89866,7 @@ else
   ac_save_LIBS="$LIBS"
 LIBS="-lresolv  $LIBS"
 cat > conftest.$ac_ext <<EOF
-#line 89636 "configure"
+#line 89875 "configure"
 #include "confdefs.h"
 /* Override any gcc2 internal prototype to avoid an error.  */
 /* We use char because int might match the return type of a gcc2
@@ -89643,7 +89877,7 @@ int main() {
 res_nsearch()
 ; return 0; }
 EOF
-if { (eval echo configure:89647: \"$ac_link\") 1>&5; (eval $ac_link) 2>&5; } && test -s conftest${ac_exeext}; then
+if { (eval echo configure:89886: \"$ac_link\") 1>&5; (eval $ac_link) 2>&5; } && test -s conftest${ac_exeext}; then
   rm -rf conftest*
   eval "ac_cv_lib_$ac_lib_var=yes"
 else
@@ -89663,7 +89897,7 @@ else
   echo "$ac_t""no" 1>&6
 
     echo $ac_n "checking for __res_nsearch in -lresolv""... $ac_c" 1>&6
-echo "configure:89667: checking for __res_nsearch in -lresolv" >&5
+echo "configure:89906: checking for __res_nsearch in -lresolv" >&5
 ac_lib_var=`echo resolv'_'__res_nsearch | sed 'y%./+-%__p_%'`
 if eval "test \"`echo '$''{'ac_cv_lib_$ac_lib_var'+set}'`\" = set"; then
   echo $ac_n "(cached) $ac_c" 1>&6
@@ -89671,7 +89905,7 @@ else
   ac_save_LIBS="$LIBS"
 LIBS="-lresolv  $LIBS"
 cat > conftest.$ac_ext <<EOF
-#line 89675 "configure"
+#line 89914 "configure"
 #include "confdefs.h"
 /* Override any gcc2 internal prototype to avoid an error.  */
 /* We use char because int might match the return type of a gcc2
@@ -89682,7 +89916,7 @@ int main() {
 __res_nsearch()
 ; return 0; }
 EOF
-if { (eval echo configure:89686: \"$ac_link\") 1>&5; (eval $ac_link) 2>&5; } && test -s conftest${ac_exeext}; then
+if { (eval echo configure:89925: \"$ac_link\") 1>&5; (eval $ac_link) 2>&5; } && test -s conftest${ac_exeext}; then
   rm -rf conftest*
   eval "ac_cv_lib_$ac_lib_var=yes"
 else
@@ -89714,11 +89948,11 @@ fi
   found=no
 else
   cat > conftest.$ac_ext <<EOF
-#line 89718 "configure"
+#line 89957 "configure"
 #include "confdefs.h"
 main() { return (0); }
 EOF
-if { (eval echo configure:89722: \"$ac_link\") 1>&5; (eval $ac_link) 2>&5; } && test -s conftest${ac_exeext} && (./conftest; exit) 2>/dev/null
+if { (eval echo configure:89961: \"$ac_link\") 1>&5; (eval $ac_link) 2>&5; } && test -s conftest${ac_exeext} && (./conftest; exit) 2>/dev/null
 then
   found=yes
 else
@@ -89760,7 +89994,7 @@ EOF
   unset ac_cv_lib_bind___res_nsearch
   unset found
   echo $ac_n "checking for res_nsearch in -lbind""... $ac_c" 1>&6
-echo "configure:89764: checking for res_nsearch in -lbind" >&5
+echo "configure:90003: checking for res_nsearch in -lbind" >&5
 ac_lib_var=`echo bind'_'res_nsearch | sed 'y%./+-%__p_%'`
 if eval "test \"`echo '$''{'ac_cv_lib_$ac_lib_var'+set}'`\" = set"; then
   echo $ac_n "(cached) $ac_c" 1>&6
@@ -89768,7 +90002,7 @@ else
   ac_save_LIBS="$LIBS"
 LIBS="-lbind  $LIBS"
 cat > conftest.$ac_ext <<EOF
-#line 89772 "configure"
+#line 90011 "configure"
 #include "confdefs.h"
 /* Override any gcc2 internal prototype to avoid an error.  */
 /* We use char because int might match the return type of a gcc2
@@ -89779,7 +90013,7 @@ int main() {
 res_nsearch()
 ; return 0; }
 EOF
-if { (eval echo configure:89783: \"$ac_link\") 1>&5; (eval $ac_link) 2>&5; } && test -s conftest${ac_exeext}; then
+if { (eval echo configure:90022: \"$ac_link\") 1>&5; (eval $ac_link) 2>&5; } && test -s conftest${ac_exeext}; then
   rm -rf conftest*
   eval "ac_cv_lib_$ac_lib_var=yes"
 else
@@ -89799,7 +90033,7 @@ else
   echo "$ac_t""no" 1>&6
 
     echo $ac_n "checking for __res_nsearch in -lbind""... $ac_c" 1>&6
-echo "configure:89803: checking for __res_nsearch in -lbind" >&5
+echo "configure:90042: checking for __res_nsearch in -lbind" >&5
 ac_lib_var=`echo bind'_'__res_nsearch | sed 'y%./+-%__p_%'`
 if eval "test \"`echo '$''{'ac_cv_lib_$ac_lib_var'+set}'`\" = set"; then
   echo $ac_n "(cached) $ac_c" 1>&6
@@ -89807,7 +90041,7 @@ else
   ac_save_LIBS="$LIBS"
 LIBS="-lbind  $LIBS"
 cat > conftest.$ac_ext <<EOF
-#line 89811 "configure"
+#line 90050 "configure"
 #include "confdefs.h"
 /* Override any gcc2 internal prototype to avoid an error.  */
 /* We use char because int might match the return type of a gcc2
@@ -89818,7 +90052,7 @@ int main() {
 __res_nsearch()
 ; return 0; }
 EOF
-if { (eval echo configure:89822: \"$ac_link\") 1>&5; (eval $ac_link) 2>&5; } && test -s conftest${ac_exeext}; then
+if { (eval echo configure:90061: \"$ac_link\") 1>&5; (eval $ac_link) 2>&5; } && test -s conftest${ac_exeext}; then
   rm -rf conftest*
   eval "ac_cv_lib_$ac_lib_var=yes"
 else
@@ -89850,11 +90084,11 @@ fi
   found=no
 else
   cat > conftest.$ac_ext <<EOF
-#line 89854 "configure"
+#line 90093 "configure"
 #include "confdefs.h"
 main() { return (0); }
 EOF
-if { (eval echo configure:89858: \"$ac_link\") 1>&5; (eval $ac_link) 2>&5; } && test -s conftest${ac_exeext} && (./conftest; exit) 2>/dev/null
+if { (eval echo configure:90097: \"$ac_link\") 1>&5; (eval $ac_link) 2>&5; } && test -s conftest${ac_exeext} && (./conftest; exit) 2>/dev/null
 then
   found=yes
 else
@@ -89896,7 +90130,7 @@ EOF
   unset ac_cv_lib_socket___res_nsearch
   unset found
   echo $ac_n "checking for res_nsearch in -lsocket""... $ac_c" 1>&6
-echo "configure:89900: checking for res_nsearch in -lsocket" >&5
+echo "configure:90139: checking for res_nsearch in -lsocket" >&5
 ac_lib_var=`echo socket'_'res_nsearch | sed 'y%./+-%__p_%'`
 if eval "test \"`echo '$''{'ac_cv_lib_$ac_lib_var'+set}'`\" = set"; then
   echo $ac_n "(cached) $ac_c" 1>&6
@@ -89904,7 +90138,7 @@ else
   ac_save_LIBS="$LIBS"
 LIBS="-lsocket  $LIBS"
 cat > conftest.$ac_ext <<EOF
-#line 89908 "configure"
+#line 90147 "configure"
 #include "confdefs.h"
 /* Override any gcc2 internal prototype to avoid an error.  */
 /* We use char because int might match the return type of a gcc2
@@ -89915,7 +90149,7 @@ int main() {
 res_nsearch()
 ; return 0; }
 EOF
-if { (eval echo configure:89919: \"$ac_link\") 1>&5; (eval $ac_link) 2>&5; } && test -s conftest${ac_exeext}; then
+if { (eval echo configure:90158: \"$ac_link\") 1>&5; (eval $ac_link) 2>&5; } && test -s conftest${ac_exeext}; then
   rm -rf conftest*
   eval "ac_cv_lib_$ac_lib_var=yes"
 else
@@ -89935,7 +90169,7 @@ else
   echo "$ac_t""no" 1>&6
 
     echo $ac_n "checking for __res_nsearch in -lsocket""... $ac_c" 1>&6
-echo "configure:89939: checking for __res_nsearch in -lsocket" >&5
+echo "configure:90178: checking for __res_nsearch in -lsocket" >&5
 ac_lib_var=`echo socket'_'__res_nsearch | sed 'y%./+-%__p_%'`
 if eval "test \"`echo '$''{'ac_cv_lib_$ac_lib_var'+set}'`\" = set"; then
   echo $ac_n "(cached) $ac_c" 1>&6
@@ -89943,7 +90177,7 @@ else
   ac_save_LIBS="$LIBS"
 LIBS="-lsocket  $LIBS"
 cat > conftest.$ac_ext <<EOF
-#line 89947 "configure"
+#line 90186 "configure"
 #include "confdefs.h"
 /* Override any gcc2 internal prototype to avoid an error.  */
 /* We use char because int might match the return type of a gcc2
@@ -89954,7 +90188,7 @@ int main() {
 __res_nsearch()
 ; return 0; }
 EOF
-if { (eval echo configure:89958: \"$ac_link\") 1>&5; (eval $ac_link) 2>&5; } && test -s conftest${ac_exeext}; then
+if { (eval echo configure:90197: \"$ac_link\") 1>&5; (eval $ac_link) 2>&5; } && test -s conftest${ac_exeext}; then
   rm -rf conftest*
   eval "ac_cv_lib_$ac_lib_var=yes"
 else
@@ -89986,11 +90220,11 @@ fi
   found=no
 else
   cat > conftest.$ac_ext <<EOF
-#line 89990 "configure"
+#line 90229 "configure"
 #include "confdefs.h"
 main() { return (0); }
 EOF
-if { (eval echo configure:89994: \"$ac_link\") 1>&5; (eval $ac_link) 2>&5; } && test -s conftest${ac_exeext} && (./conftest; exit) 2>/dev/null
+if { (eval echo configure:90233: \"$ac_link\") 1>&5; (eval $ac_link) 2>&5; } && test -s conftest${ac_exeext} && (./conftest; exit) 2>/dev/null
 then
   found=yes
 else
@@ -90047,12 +90281,12 @@ EOF
   unset found
   
   echo $ac_n "checking for dns_search""... $ac_c" 1>&6
-echo "configure:90051: checking for dns_search" >&5
+echo "configure:90290: checking for dns_search" >&5
 if eval "test \"`echo '$''{'ac_cv_func_dns_search'+set}'`\" = set"; then
   echo $ac_n "(cached) $ac_c" 1>&6
 else
   cat > conftest.$ac_ext <<EOF
-#line 90056 "configure"
+#line 90295 "configure"
 #include "confdefs.h"
 /* System header to define __stub macros and hopefully few prototypes,
     which can conflict with char dns_search(); below.  */
@@ -90075,7 +90309,7 @@ dns_search();
 
 ; return 0; }
 EOF
-if { (eval echo configure:90079: \"$ac_link\") 1>&5; (eval $ac_link) 2>&5; } && test -s conftest${ac_exeext}; then
+if { (eval echo configure:90318: \"$ac_link\") 1>&5; (eval $ac_link) 2>&5; } && test -s conftest${ac_exeext}; then
   rm -rf conftest*
   eval "ac_cv_func_dns_search=yes"
 else
@@ -90093,12 +90327,12 @@ if eval "test \"`echo '$ac_cv_func_'dns_search`\" = yes"; then
 else
   echo "$ac_t""no" 1>&6
  echo $ac_n "checking for __dns_search""... $ac_c" 1>&6
-echo "configure:90097: checking for __dns_search" >&5
+echo "configure:90336: checking for __dns_search" >&5
 if eval "test \"`echo '$''{'ac_cv_func___dns_search'+set}'`\" = set"; then
   echo $ac_n "(cached) $ac_c" 1>&6
 else
   cat > conftest.$ac_ext <<EOF
-#line 90102 "configure"
+#line 90341 "configure"
 #include "confdefs.h"
 /* System header to define __stub macros and hopefully few prototypes,
     which can conflict with char __dns_search(); below.  */
@@ -90121,7 +90355,7 @@ __dns_search();
 
 ; return 0; }
 EOF
-if { (eval echo configure:90125: \"$ac_link\") 1>&5; (eval $ac_link) 2>&5; } && test -s conftest${ac_exeext}; then
+if { (eval echo configure:90364: \"$ac_link\") 1>&5; (eval $ac_link) 2>&5; } && test -s conftest${ac_exeext}; then
   rm -rf conftest*
   eval "ac_cv_func___dns_search=yes"
 else
@@ -90159,7 +90393,7 @@ EOF
   unset ac_cv_lib_resolv___dns_search
   unset found
   echo $ac_n "checking for dns_search in -lresolv""... $ac_c" 1>&6
-echo "configure:90163: checking for dns_search in -lresolv" >&5
+echo "configure:90402: checking for dns_search in -lresolv" >&5
 ac_lib_var=`echo resolv'_'dns_search | sed 'y%./+-%__p_%'`
 if eval "test \"`echo '$''{'ac_cv_lib_$ac_lib_var'+set}'`\" = set"; then
   echo $ac_n "(cached) $ac_c" 1>&6
@@ -90167,7 +90401,7 @@ else
   ac_save_LIBS="$LIBS"
 LIBS="-lresolv  $LIBS"
 cat > conftest.$ac_ext <<EOF
-#line 90171 "configure"
+#line 90410 "configure"
 #include "confdefs.h"
 /* Override any gcc2 internal prototype to avoid an error.  */
 /* We use char because int might match the return type of a gcc2
@@ -90178,7 +90412,7 @@ int main() {
 dns_search()
 ; return 0; }
 EOF
-if { (eval echo configure:90182: \"$ac_link\") 1>&5; (eval $ac_link) 2>&5; } && test -s conftest${ac_exeext}; then
+if { (eval echo configure:90421: \"$ac_link\") 1>&5; (eval $ac_link) 2>&5; } && test -s conftest${ac_exeext}; then
   rm -rf conftest*
   eval "ac_cv_lib_$ac_lib_var=yes"
 else
@@ -90198,7 +90432,7 @@ else
   echo "$ac_t""no" 1>&6
 
     echo $ac_n "checking for __dns_search in -lresolv""... $ac_c" 1>&6
-echo "configure:90202: checking for __dns_search in -lresolv" >&5
+echo "configure:90441: checking for __dns_search in -lresolv" >&5
 ac_lib_var=`echo resolv'_'__dns_search | sed 'y%./+-%__p_%'`
 if eval "test \"`echo '$''{'ac_cv_lib_$ac_lib_var'+set}'`\" = set"; then
   echo $ac_n "(cached) $ac_c" 1>&6
@@ -90206,7 +90440,7 @@ else
   ac_save_LIBS="$LIBS"
 LIBS="-lresolv  $LIBS"
 cat > conftest.$ac_ext <<EOF
-#line 90210 "configure"
+#line 90449 "configure"
 #include "confdefs.h"
 /* Override any gcc2 internal prototype to avoid an error.  */
 /* We use char because int might match the return type of a gcc2
@@ -90217,7 +90451,7 @@ int main() {
 __dns_search()
 ; return 0; }
 EOF
-if { (eval echo configure:90221: \"$ac_link\") 1>&5; (eval $ac_link) 2>&5; } && test -s conftest${ac_exeext}; then
+if { (eval echo configure:90460: \"$ac_link\") 1>&5; (eval $ac_link) 2>&5; } && test -s conftest${ac_exeext}; then
   rm -rf conftest*
   eval "ac_cv_lib_$ac_lib_var=yes"
 else
@@ -90249,11 +90483,11 @@ fi
   found=no
 else
   cat > conftest.$ac_ext <<EOF
-#line 90253 "configure"
+#line 90492 "configure"
 #include "confdefs.h"
 main() { return (0); }
 EOF
-if { (eval echo configure:90257: \"$ac_link\") 1>&5; (eval $ac_link) 2>&5; } && test -s conftest${ac_exeext} && (./conftest; exit) 2>/dev/null
+if { (eval echo configure:90496: \"$ac_link\") 1>&5; (eval $ac_link) 2>&5; } && test -s conftest${ac_exeext} && (./conftest; exit) 2>/dev/null
 then
   found=yes
 else
@@ -90295,7 +90529,7 @@ EOF
   unset ac_cv_lib_bind___dns_search
   unset found
   echo $ac_n "checking for dns_search in -lbind""... $ac_c" 1>&6
-echo "configure:90299: checking for dns_search in -lbind" >&5
+echo "configure:90538: checking for dns_search in -lbind" >&5
 ac_lib_var=`echo bind'_'dns_search | sed 'y%./+-%__p_%'`
 if eval "test \"`echo '$''{'ac_cv_lib_$ac_lib_var'+set}'`\" = set"; then
   echo $ac_n "(cached) $ac_c" 1>&6
@@ -90303,7 +90537,7 @@ else
   ac_save_LIBS="$LIBS"
 LIBS="-lbind  $LIBS"
 cat > conftest.$ac_ext <<EOF
-#line 90307 "configure"
+#line 90546 "configure"
 #include "confdefs.h"
 /* Override any gcc2 internal prototype to avoid an error.  */
 /* We use char because int might match the return type of a gcc2
@@ -90314,7 +90548,7 @@ int main() {
 dns_search()
 ; return 0; }
 EOF
-if { (eval echo configure:90318: \"$ac_link\") 1>&5; (eval $ac_link) 2>&5; } && test -s conftest${ac_exeext}; then
+if { (eval echo configure:90557: \"$ac_link\") 1>&5; (eval $ac_link) 2>&5; } && test -s conftest${ac_exeext}; then
   rm -rf conftest*
   eval "ac_cv_lib_$ac_lib_var=yes"
 else
@@ -90334,7 +90568,7 @@ else
   echo "$ac_t""no" 1>&6
 
     echo $ac_n "checking for __dns_search in -lbind""... $ac_c" 1>&6
-echo "configure:90338: checking for __dns_search in -lbind" >&5
+echo "configure:90577: checking for __dns_search in -lbind" >&5
 ac_lib_var=`echo bind'_'__dns_search | sed 'y%./+-%__p_%'`
 if eval "test \"`echo '$''{'ac_cv_lib_$ac_lib_var'+set}'`\" = set"; then
   echo $ac_n "(cached) $ac_c" 1>&6
@@ -90342,7 +90576,7 @@ else
   ac_save_LIBS="$LIBS"
 LIBS="-lbind  $LIBS"
 cat > conftest.$ac_ext <<EOF
-#line 90346 "configure"
+#line 90585 "configure"
 #include "confdefs.h"
 /* Override any gcc2 internal prototype to avoid an error.  */
 /* We use char because int might match the return type of a gcc2
@@ -90353,7 +90587,7 @@ int main() {
 __dns_search()
 ; return 0; }
 EOF
-if { (eval echo configure:90357: \"$ac_link\") 1>&5; (eval $ac_link) 2>&5; } && test -s conftest${ac_exeext}; then
+if { (eval echo configure:90596: \"$ac_link\") 1>&5; (eval $ac_link) 2>&5; } && test -s conftest${ac_exeext}; then
   rm -rf conftest*
   eval "ac_cv_lib_$ac_lib_var=yes"
 else
@@ -90385,11 +90619,11 @@ fi
   found=no
 else
   cat > conftest.$ac_ext <<EOF
-#line 90389 "configure"
+#line 90628 "configure"
 #include "confdefs.h"
 main() { return (0); }
 EOF
-if { (eval echo configure:90393: \"$ac_link\") 1>&5; (eval $ac_link) 2>&5; } && test -s conftest${ac_exeext} && (./conftest; exit) 2>/dev/null
+if { (eval echo configure:90632: \"$ac_link\") 1>&5; (eval $ac_link) 2>&5; } && test -s conftest${ac_exeext} && (./conftest; exit) 2>/dev/null
 then
   found=yes
 else
@@ -90431,7 +90665,7 @@ EOF
   unset ac_cv_lib_socket___dns_search
   unset found
   echo $ac_n "checking for dns_search in -lsocket""... $ac_c" 1>&6
-echo "configure:90435: checking for dns_search in -lsocket" >&5
+echo "configure:90674: checking for dns_search in -lsocket" >&5
 ac_lib_var=`echo socket'_'dns_search | sed 'y%./+-%__p_%'`
 if eval "test \"`echo '$''{'ac_cv_lib_$ac_lib_var'+set}'`\" = set"; then
   echo $ac_n "(cached) $ac_c" 1>&6
@@ -90439,7 +90673,7 @@ else
   ac_save_LIBS="$LIBS"
 LIBS="-lsocket  $LIBS"
 cat > conftest.$ac_ext <<EOF
-#line 90443 "configure"
+#line 90682 "configure"
 #include "confdefs.h"
 /* Override any gcc2 internal prototype to avoid an error.  */
 /* We use char because int might match the return type of a gcc2
@@ -90450,7 +90684,7 @@ int main() {
 dns_search()
 ; return 0; }
 EOF
-if { (eval echo configure:90454: \"$ac_link\") 1>&5; (eval $ac_link) 2>&5; } && test -s conftest${ac_exeext}; then
+if { (eval echo configure:90693: \"$ac_link\") 1>&5; (eval $ac_link) 2>&5; } && test -s conftest${ac_exeext}; then
   rm -rf conftest*
   eval "ac_cv_lib_$ac_lib_var=yes"
 else
@@ -90470,7 +90704,7 @@ else
   echo "$ac_t""no" 1>&6
 
     echo $ac_n "checking for __dns_search in -lsocket""... $ac_c" 1>&6
-echo "configure:90474: checking for __dns_search in -lsocket" >&5
+echo "configure:90713: checking for __dns_search in -lsocket" >&5
 ac_lib_var=`echo socket'_'__dns_search | sed 'y%./+-%__p_%'`
 if eval "test \"`echo '$''{'ac_cv_lib_$ac_lib_var'+set}'`\" = set"; then
   echo $ac_n "(cached) $ac_c" 1>&6
@@ -90478,7 +90712,7 @@ else
   ac_save_LIBS="$LIBS"
 LIBS="-lsocket  $LIBS"
 cat > conftest.$ac_ext <<EOF
-#line 90482 "configure"
+#line 90721 "configure"
 #include "confdefs.h"
 /* Override any gcc2 internal prototype to avoid an error.  */
 /* We use char because int might match the return type of a gcc2
@@ -90489,7 +90723,7 @@ int main() {
 __dns_search()
 ; return 0; }
 EOF
-if { (eval echo configure:90493: \"$ac_link\") 1>&5; (eval $ac_link) 2>&5; } && test -s conftest${ac_exeext}; then
+if { (eval echo configure:90732: \"$ac_link\") 1>&5; (eval $ac_link) 2>&5; } && test -s conftest${ac_exeext}; then
   rm -rf conftest*
   eval "ac_cv_lib_$ac_lib_var=yes"
 else
@@ -90521,11 +90755,11 @@ fi
   found=no
 else
   cat > conftest.$ac_ext <<EOF
-#line 90525 "configure"
+#line 90764 "configure"
 #include "confdefs.h"
 main() { return (0); }
 EOF
-if { (eval echo configure:90529: \"$ac_link\") 1>&5; (eval $ac_link) 2>&5; } && test -s conftest${ac_exeext} && (./conftest; exit) 2>/dev/null
+if { (eval echo configure:90768: \"$ac_link\") 1>&5; (eval $ac_link) 2>&5; } && test -s conftest${ac_exeext} && (./conftest; exit) 2>/dev/null
 then
   found=yes
 else
@@ -90582,12 +90816,12 @@ EOF
   unset found
   
   echo $ac_n "checking for dn_expand""... $ac_c" 1>&6
-echo "configure:90586: checking for dn_expand" >&5
+echo "configure:90825: checking for dn_expand" >&5
 if eval "test \"`echo '$''{'ac_cv_func_dn_expand'+set}'`\" = set"; then
   echo $ac_n "(cached) $ac_c" 1>&6
 else
   cat > conftest.$ac_ext <<EOF
-#line 90591 "configure"
+#line 90830 "configure"
 #include "confdefs.h"
 /* System header to define __stub macros and hopefully few prototypes,
     which can conflict with char dn_expand(); below.  */
@@ -90610,7 +90844,7 @@ dn_expand();
 
 ; return 0; }
 EOF
-if { (eval echo configure:90614: \"$ac_link\") 1>&5; (eval $ac_link) 2>&5; } && test -s conftest${ac_exeext}; then
+if { (eval echo configure:90853: \"$ac_link\") 1>&5; (eval $ac_link) 2>&5; } && test -s conftest${ac_exeext}; then
   rm -rf conftest*
   eval "ac_cv_func_dn_expand=yes"
 else
@@ -90628,12 +90862,12 @@ if eval "test \"`echo '$ac_cv_func_'dn_expand`\" = yes"; then
 else
   echo "$ac_t""no" 1>&6
  echo $ac_n "checking for __dn_expand""... $ac_c" 1>&6
-echo "configure:90632: checking for __dn_expand" >&5
+echo "configure:90871: checking for __dn_expand" >&5
 if eval "test \"`echo '$''{'ac_cv_func___dn_expand'+set}'`\" = set"; then
   echo $ac_n "(cached) $ac_c" 1>&6
 else
   cat > conftest.$ac_ext <<EOF
-#line 90637 "configure"
+#line 90876 "configure"
 #include "confdefs.h"
 /* System header to define __stub macros and hopefully few prototypes,
     which can conflict with char __dn_expand(); below.  */
@@ -90656,7 +90890,7 @@ __dn_expand();
 
 ; return 0; }
 EOF
-if { (eval echo configure:90660: \"$ac_link\") 1>&5; (eval $ac_link) 2>&5; } && test -s conftest${ac_exeext}; then
+if { (eval echo configure:90899: \"$ac_link\") 1>&5; (eval $ac_link) 2>&5; } && test -s conftest${ac_exeext}; then
   rm -rf conftest*
   eval "ac_cv_func___dn_expand=yes"
 else
@@ -90694,7 +90928,7 @@ EOF
   unset ac_cv_lib_resolv___dn_expand
   unset found
   echo $ac_n "checking for dn_expand in -lresolv""... $ac_c" 1>&6
-echo "configure:90698: checking for dn_expand in -lresolv" >&5
+echo "configure:90937: checking for dn_expand in -lresolv" >&5
 ac_lib_var=`echo resolv'_'dn_expand | sed 'y%./+-%__p_%'`
 if eval "test \"`echo '$''{'ac_cv_lib_$ac_lib_var'+set}'`\" = set"; then
   echo $ac_n "(cached) $ac_c" 1>&6
@@ -90702,7 +90936,7 @@ else
   ac_save_LIBS="$LIBS"
 LIBS="-lresolv  $LIBS"
 cat > conftest.$ac_ext <<EOF
-#line 90706 "configure"
+#line 90945 "configure"
 #include "confdefs.h"
 /* Override any gcc2 internal prototype to avoid an error.  */
 /* We use char because int might match the return type of a gcc2
@@ -90713,7 +90947,7 @@ int main() {
 dn_expand()
 ; return 0; }
 EOF
-if { (eval echo configure:90717: \"$ac_link\") 1>&5; (eval $ac_link) 2>&5; } && test -s conftest${ac_exeext}; then
+if { (eval echo configure:90956: \"$ac_link\") 1>&5; (eval $ac_link) 2>&5; } && test -s conftest${ac_exeext}; then
   rm -rf conftest*
   eval "ac_cv_lib_$ac_lib_var=yes"
 else
@@ -90733,7 +90967,7 @@ else
   echo "$ac_t""no" 1>&6
 
     echo $ac_n "checking for __dn_expand in -lresolv""... $ac_c" 1>&6
-echo "configure:90737: checking for __dn_expand in -lresolv" >&5
+echo "configure:90976: checking for __dn_expand in -lresolv" >&5
 ac_lib_var=`echo resolv'_'__dn_expand | sed 'y%./+-%__p_%'`
 if eval "test \"`echo '$''{'ac_cv_lib_$ac_lib_var'+set}'`\" = set"; then
   echo $ac_n "(cached) $ac_c" 1>&6
@@ -90741,7 +90975,7 @@ else
   ac_save_LIBS="$LIBS"
 LIBS="-lresolv  $LIBS"
 cat > conftest.$ac_ext <<EOF
-#line 90745 "configure"
+#line 90984 "configure"
 #include "confdefs.h"
 /* Override any gcc2 internal prototype to avoid an error.  */
 /* We use char because int might match the return type of a gcc2
@@ -90752,7 +90986,7 @@ int main() {
 __dn_expand()
 ; return 0; }
 EOF
-if { (eval echo configure:90756: \"$ac_link\") 1>&5; (eval $ac_link) 2>&5; } && test -s conftest${ac_exeext}; then
+if { (eval echo configure:90995: \"$ac_link\") 1>&5; (eval $ac_link) 2>&5; } && test -s conftest${ac_exeext}; then
   rm -rf conftest*
   eval "ac_cv_lib_$ac_lib_var=yes"
 else
@@ -90784,11 +91018,11 @@ fi
   found=no
 else
   cat > conftest.$ac_ext <<EOF
-#line 90788 "configure"
+#line 91027 "configure"
 #include "confdefs.h"
 main() { return (0); }
 EOF
-if { (eval echo configure:90792: \"$ac_link\") 1>&5; (eval $ac_link) 2>&5; } && test -s conftest${ac_exeext} && (./conftest; exit) 2>/dev/null
+if { (eval echo configure:91031: \"$ac_link\") 1>&5; (eval $ac_link) 2>&5; } && test -s conftest${ac_exeext} && (./conftest; exit) 2>/dev/null
 then
   found=yes
 else
@@ -90830,7 +91064,7 @@ EOF
   unset ac_cv_lib_bind___dn_expand
   unset found
   echo $ac_n "checking for dn_expand in -lbind""... $ac_c" 1>&6
-echo "configure:90834: checking for dn_expand in -lbind" >&5
+echo "configure:91073: checking for dn_expand in -lbind" >&5
 ac_lib_var=`echo bind'_'dn_expand | sed 'y%./+-%__p_%'`
 if eval "test \"`echo '$''{'ac_cv_lib_$ac_lib_var'+set}'`\" = set"; then
   echo $ac_n "(cached) $ac_c" 1>&6
@@ -90838,7 +91072,7 @@ else
   ac_save_LIBS="$LIBS"
 LIBS="-lbind  $LIBS"
 cat > conftest.$ac_ext <<EOF
-#line 90842 "configure"
+#line 91081 "configure"
 #include "confdefs.h"
 /* Override any gcc2 internal prototype to avoid an error.  */
 /* We use char because int might match the return type of a gcc2
@@ -90849,7 +91083,7 @@ int main() {
 dn_expand()
 ; return 0; }
 EOF
-if { (eval echo configure:90853: \"$ac_link\") 1>&5; (eval $ac_link) 2>&5; } && test -s conftest${ac_exeext}; then
+if { (eval echo configure:91092: \"$ac_link\") 1>&5; (eval $ac_link) 2>&5; } && test -s conftest${ac_exeext}; then
   rm -rf conftest*
   eval "ac_cv_lib_$ac_lib_var=yes"
 else
@@ -90869,7 +91103,7 @@ else
   echo "$ac_t""no" 1>&6
 
     echo $ac_n "checking for __dn_expand in -lbind""... $ac_c" 1>&6
-echo "configure:90873: checking for __dn_expand in -lbind" >&5
+echo "configure:91112: checking for __dn_expand in -lbind" >&5
 ac_lib_var=`echo bind'_'__dn_expand | sed 'y%./+-%__p_%'`
 if eval "test \"`echo '$''{'ac_cv_lib_$ac_lib_var'+set}'`\" = set"; then
   echo $ac_n "(cached) $ac_c" 1>&6
@@ -90877,7 +91111,7 @@ else
   ac_save_LIBS="$LIBS"
 LIBS="-lbind  $LIBS"
 cat > conftest.$ac_ext <<EOF
-#line 90881 "configure"
+#line 91120 "configure"
 #include "confdefs.h"
 /* Override any gcc2 internal prototype to avoid an error.  */
 /* We use char because int might match the return type of a gcc2
@@ -90888,7 +91122,7 @@ int main() {
 __dn_expand()
 ; return 0; }
 EOF
-if { (eval echo configure:90892: \"$ac_link\") 1>&5; (eval $ac_link) 2>&5; } && test -s conftest${ac_exeext}; then
+if { (eval echo configure:91131: \"$ac_link\") 1>&5; (eval $ac_link) 2>&5; } && test -s conftest${ac_exeext}; then
   rm -rf conftest*
   eval "ac_cv_lib_$ac_lib_var=yes"
 else
@@ -90920,11 +91154,11 @@ fi
   found=no
 else
   cat > conftest.$ac_ext <<EOF
-#line 90924 "configure"
+#line 91163 "configure"
 #include "confdefs.h"
 main() { return (0); }
 EOF
-if { (eval echo configure:90928: \"$ac_link\") 1>&5; (eval $ac_link) 2>&5; } && test -s conftest${ac_exeext} && (./conftest; exit) 2>/dev/null
+if { (eval echo configure:91167: \"$ac_link\") 1>&5; (eval $ac_link) 2>&5; } && test -s conftest${ac_exeext} && (./conftest; exit) 2>/dev/null
 then
   found=yes
 else
@@ -90966,7 +91200,7 @@ EOF
   unset ac_cv_lib_socket___dn_expand
   unset found
   echo $ac_n "checking for dn_expand in -lsocket""... $ac_c" 1>&6
-echo "configure:90970: checking for dn_expand in -lsocket" >&5
+echo "configure:91209: checking for dn_expand in -lsocket" >&5
 ac_lib_var=`echo socket'_'dn_expand | sed 'y%./+-%__p_%'`
 if eval "test \"`echo '$''{'ac_cv_lib_$ac_lib_var'+set}'`\" = set"; then
   echo $ac_n "(cached) $ac_c" 1>&6
@@ -90974,7 +91208,7 @@ else
   ac_save_LIBS="$LIBS"
 LIBS="-lsocket  $LIBS"
 cat > conftest.$ac_ext <<EOF
-#line 90978 "configure"
+#line 91217 "configure"
 #include "confdefs.h"
 /* Override any gcc2 internal prototype to avoid an error.  */
 /* We use char because int might match the return type of a gcc2
@@ -90985,7 +91219,7 @@ int main() {
 dn_expand()
 ; return 0; }
 EOF
-if { (eval echo configure:90989: \"$ac_link\") 1>&5; (eval $ac_link) 2>&5; } && test -s conftest${ac_exeext}; then
+if { (eval echo configure:91228: \"$ac_link\") 1>&5; (eval $ac_link) 2>&5; } && test -s conftest${ac_exeext}; then
   rm -rf conftest*
   eval "ac_cv_lib_$ac_lib_var=yes"
 else
@@ -91005,7 +91239,7 @@ else
   echo "$ac_t""no" 1>&6
 
     echo $ac_n "checking for __dn_expand in -lsocket""... $ac_c" 1>&6
-echo "configure:91009: checking for __dn_expand in -lsocket" >&5
+echo "configure:91248: checking for __dn_expand in -lsocket" >&5
 ac_lib_var=`echo socket'_'__dn_expand | sed 'y%./+-%__p_%'`
 if eval "test \"`echo '$''{'ac_cv_lib_$ac_lib_var'+set}'`\" = set"; then
   echo $ac_n "(cached) $ac_c" 1>&6
@@ -91013,7 +91247,7 @@ else
   ac_save_LIBS="$LIBS"
 LIBS="-lsocket  $LIBS"
 cat > conftest.$ac_ext <<EOF
-#line 91017 "configure"
+#line 91256 "configure"
 #include "confdefs.h"
 /* Override any gcc2 internal prototype to avoid an error.  */
 /* We use char because int might match the return type of a gcc2
@@ -91024,7 +91258,7 @@ int main() {
 __dn_expand()
 ; return 0; }
 EOF
-if { (eval echo configure:91028: \"$ac_link\") 1>&5; (eval $ac_link) 2>&5; } && test -s conftest${ac_exeext}; then
+if { (eval echo configure:91267: \"$ac_link\") 1>&5; (eval $ac_link) 2>&5; } && test -s conftest${ac_exeext}; then
   rm -rf conftest*
   eval "ac_cv_lib_$ac_lib_var=yes"
 else
@@ -91056,11 +91290,11 @@ fi
   found=no
 else
   cat > conftest.$ac_ext <<EOF
-#line 91060 "configure"
+#line 91299 "configure"
 #include "confdefs.h"
 main() { return (0); }
 EOF
-if { (eval echo configure:91064: \"$ac_link\") 1>&5; (eval $ac_link) 2>&5; } && test -s conftest${ac_exeext} && (./conftest; exit) 2>/dev/null
+if { (eval echo configure:91303: \"$ac_link\") 1>&5; (eval $ac_link) 2>&5; } && test -s conftest${ac_exeext} && (./conftest; exit) 2>/dev/null
 then
   found=yes
 else
@@ -91117,12 +91351,12 @@ EOF
   unset found
   
   echo $ac_n "checking for dn_skipname""... $ac_c" 1>&6
-echo "configure:91121: checking for dn_skipname" >&5
+echo "configure:91360: checking for dn_skipname" >&5
 if eval "test \"`echo '$''{'ac_cv_func_dn_skipname'+set}'`\" = set"; then
   echo $ac_n "(cached) $ac_c" 1>&6
 else
   cat > conftest.$ac_ext <<EOF
-#line 91126 "configure"
+#line 91365 "configure"
 #include "confdefs.h"
 /* System header to define __stub macros and hopefully few prototypes,
     which can conflict with char dn_skipname(); below.  */
@@ -91145,7 +91379,7 @@ dn_skipname();
 
 ; return 0; }
 EOF
-if { (eval echo configure:91149: \"$ac_link\") 1>&5; (eval $ac_link) 2>&5; } && test -s conftest${ac_exeext}; then
+if { (eval echo configure:91388: \"$ac_link\") 1>&5; (eval $ac_link) 2>&5; } && test -s conftest${ac_exeext}; then
   rm -rf conftest*
   eval "ac_cv_func_dn_skipname=yes"
 else
@@ -91163,12 +91397,12 @@ if eval "test \"`echo '$ac_cv_func_'dn_skipname`\" = yes"; then
 else
   echo "$ac_t""no" 1>&6
  echo $ac_n "checking for __dn_skipname""... $ac_c" 1>&6
-echo "configure:91167: checking for __dn_skipname" >&5
+echo "configure:91406: checking for __dn_skipname" >&5
 if eval "test \"`echo '$''{'ac_cv_func___dn_skipname'+set}'`\" = set"; then
   echo $ac_n "(cached) $ac_c" 1>&6
 else
   cat > conftest.$ac_ext <<EOF
-#line 91172 "configure"
+#line 91411 "configure"
 #include "confdefs.h"
 /* System header to define __stub macros and hopefully few prototypes,
     which can conflict with char __dn_skipname(); below.  */
@@ -91191,7 +91425,7 @@ __dn_skipname();
 
 ; return 0; }
 EOF
-if { (eval echo configure:91195: \"$ac_link\") 1>&5; (eval $ac_link) 2>&5; } && test -s conftest${ac_exeext}; then
+if { (eval echo configure:91434: \"$ac_link\") 1>&5; (eval $ac_link) 2>&5; } && test -s conftest${ac_exeext}; then
   rm -rf conftest*
   eval "ac_cv_func___dn_skipname=yes"
 else
@@ -91229,7 +91463,7 @@ EOF
   unset ac_cv_lib_resolv___dn_skipname
   unset found
   echo $ac_n "checking for dn_skipname in -lresolv""... $ac_c" 1>&6
-echo "configure:91233: checking for dn_skipname in -lresolv" >&5
+echo "configure:91472: checking for dn_skipname in -lresolv" >&5
 ac_lib_var=`echo resolv'_'dn_skipname | sed 'y%./+-%__p_%'`
 if eval "test \"`echo '$''{'ac_cv_lib_$ac_lib_var'+set}'`\" = set"; then
   echo $ac_n "(cached) $ac_c" 1>&6
@@ -91237,7 +91471,7 @@ else
   ac_save_LIBS="$LIBS"
 LIBS="-lresolv  $LIBS"
 cat > conftest.$ac_ext <<EOF
-#line 91241 "configure"
+#line 91480 "configure"
 #include "confdefs.h"
 /* Override any gcc2 internal prototype to avoid an error.  */
 /* We use char because int might match the return type of a gcc2
@@ -91248,7 +91482,7 @@ int main() {
 dn_skipname()
 ; return 0; }
 EOF
-if { (eval echo configure:91252: \"$ac_link\") 1>&5; (eval $ac_link) 2>&5; } && test -s conftest${ac_exeext}; then
+if { (eval echo configure:91491: \"$ac_link\") 1>&5; (eval $ac_link) 2>&5; } && test -s conftest${ac_exeext}; then
   rm -rf conftest*
   eval "ac_cv_lib_$ac_lib_var=yes"
 else
@@ -91268,7 +91502,7 @@ else
   echo "$ac_t""no" 1>&6
 
     echo $ac_n "checking for __dn_skipname in -lresolv""... $ac_c" 1>&6
-echo "configure:91272: checking for __dn_skipname in -lresolv" >&5
+echo "configure:91511: checking for __dn_skipname in -lresolv" >&5
 ac_lib_var=`echo resolv'_'__dn_skipname | sed 'y%./+-%__p_%'`
 if eval "test \"`echo '$''{'ac_cv_lib_$ac_lib_var'+set}'`\" = set"; then
   echo $ac_n "(cached) $ac_c" 1>&6
@@ -91276,7 +91510,7 @@ else
   ac_save_LIBS="$LIBS"
 LIBS="-lresolv  $LIBS"
 cat > conftest.$ac_ext <<EOF
-#line 91280 "configure"
+#line 91519 "configure"
 #include "confdefs.h"
 /* Override any gcc2 internal prototype to avoid an error.  */
 /* We use char because int might match the return type of a gcc2
@@ -91287,7 +91521,7 @@ int main() {
 __dn_skipname()
 ; return 0; }
 EOF
-if { (eval echo configure:91291: \"$ac_link\") 1>&5; (eval $ac_link) 2>&5; } && test -s conftest${ac_exeext}; then
+if { (eval echo configure:91530: \"$ac_link\") 1>&5; (eval $ac_link) 2>&5; } && test -s conftest${ac_exeext}; then
   rm -rf conftest*
   eval "ac_cv_lib_$ac_lib_var=yes"
 else
@@ -91319,11 +91553,11 @@ fi
   found=no
 else
   cat > conftest.$ac_ext <<EOF
-#line 91323 "configure"
+#line 91562 "configure"
 #include "confdefs.h"
 main() { return (0); }
 EOF
-if { (eval echo configure:91327: \"$ac_link\") 1>&5; (eval $ac_link) 2>&5; } && test -s conftest${ac_exeext} && (./conftest; exit) 2>/dev/null
+if { (eval echo configure:91566: \"$ac_link\") 1>&5; (eval $ac_link) 2>&5; } && test -s conftest${ac_exeext} && (./conftest; exit) 2>/dev/null
 then
   found=yes
 else
@@ -91365,7 +91599,7 @@ EOF
   unset ac_cv_lib_bind___dn_skipname
   unset found
   echo $ac_n "checking for dn_skipname in -lbind""... $ac_c" 1>&6
-echo "configure:91369: checking for dn_skipname in -lbind" >&5
+echo "configure:91608: checking for dn_skipname in -lbind" >&5
 ac_lib_var=`echo bind'_'dn_skipname | sed 'y%./+-%__p_%'`
 if eval "test \"`echo '$''{'ac_cv_lib_$ac_lib_var'+set}'`\" = set"; then
   echo $ac_n "(cached) $ac_c" 1>&6
@@ -91373,7 +91607,7 @@ else
   ac_save_LIBS="$LIBS"
 LIBS="-lbind  $LIBS"
 cat > conftest.$ac_ext <<EOF
-#line 91377 "configure"
+#line 91616 "configure"
 #include "confdefs.h"
 /* Override any gcc2 internal prototype to avoid an error.  */
 /* We use char because int might match the return type of a gcc2
@@ -91384,7 +91618,7 @@ int main() {
 dn_skipname()
 ; return 0; }
 EOF
-if { (eval echo configure:91388: \"$ac_link\") 1>&5; (eval $ac_link) 2>&5; } && test -s conftest${ac_exeext}; then
+if { (eval echo configure:91627: \"$ac_link\") 1>&5; (eval $ac_link) 2>&5; } && test -s conftest${ac_exeext}; then
   rm -rf conftest*
   eval "ac_cv_lib_$ac_lib_var=yes"
 else
@@ -91404,7 +91638,7 @@ else
   echo "$ac_t""no" 1>&6
 
     echo $ac_n "checking for __dn_skipname in -lbind""... $ac_c" 1>&6
-echo "configure:91408: checking for __dn_skipname in -lbind" >&5
+echo "configure:91647: checking for __dn_skipname in -lbind" >&5
 ac_lib_var=`echo bind'_'__dn_skipname | sed 'y%./+-%__p_%'`
 if eval "test \"`echo '$''{'ac_cv_lib_$ac_lib_var'+set}'`\" = set"; then
   echo $ac_n "(cached) $ac_c" 1>&6
@@ -91412,7 +91646,7 @@ else
   ac_save_LIBS="$LIBS"
 LIBS="-lbind  $LIBS"
 cat > conftest.$ac_ext <<EOF
-#line 91416 "configure"
+#line 91655 "configure"
 #include "confdefs.h"
 /* Override any gcc2 internal prototype to avoid an error.  */
 /* We use char because int might match the return type of a gcc2
@@ -91423,7 +91657,7 @@ int main() {
 __dn_skipname()
 ; return 0; }
 EOF
-if { (eval echo configure:91427: \"$ac_link\") 1>&5; (eval $ac_link) 2>&5; } && test -s conftest${ac_exeext}; then
+if { (eval echo configure:91666: \"$ac_link\") 1>&5; (eval $ac_link) 2>&5; } && test -s conftest${ac_exeext}; then
   rm -rf conftest*
   eval "ac_cv_lib_$ac_lib_var=yes"
 else
@@ -91455,11 +91689,11 @@ fi
   found=no
 else
   cat > conftest.$ac_ext <<EOF
-#line 91459 "configure"
+#line 91698 "configure"
 #include "confdefs.h"
 main() { return (0); }
 EOF
-if { (eval echo configure:91463: \"$ac_link\") 1>&5; (eval $ac_link) 2>&5; } && test -s conftest${ac_exeext} && (./conftest; exit) 2>/dev/null
+if { (eval echo configure:91702: \"$ac_link\") 1>&5; (eval $ac_link) 2>&5; } && test -s conftest${ac_exeext} && (./conftest; exit) 2>/dev/null
 then
   found=yes
 else
@@ -91501,7 +91735,7 @@ EOF
   unset ac_cv_lib_socket___dn_skipname
   unset found
   echo $ac_n "checking for dn_skipname in -lsocket""... $ac_c" 1>&6
-echo "configure:91505: checking for dn_skipname in -lsocket" >&5
+echo "configure:91744: checking for dn_skipname in -lsocket" >&5
 ac_lib_var=`echo socket'_'dn_skipname | sed 'y%./+-%__p_%'`
 if eval "test \"`echo '$''{'ac_cv_lib_$ac_lib_var'+set}'`\" = set"; then
   echo $ac_n "(cached) $ac_c" 1>&6
@@ -91509,7 +91743,7 @@ else
   ac_save_LIBS="$LIBS"
 LIBS="-lsocket  $LIBS"
 cat > conftest.$ac_ext <<EOF
-#line 91513 "configure"
+#line 91752 "configure"
 #include "confdefs.h"
 /* Override any gcc2 internal prototype to avoid an error.  */
 /* We use char because int might match the return type of a gcc2
@@ -91520,7 +91754,7 @@ int main() {
 dn_skipname()
 ; return 0; }
 EOF
-if { (eval echo configure:91524: \"$ac_link\") 1>&5; (eval $ac_link) 2>&5; } && test -s conftest${ac_exeext}; then
+if { (eval echo configure:91763: \"$ac_link\") 1>&5; (eval $ac_link) 2>&5; } && test -s conftest${ac_exeext}; then
   rm -rf conftest*
   eval "ac_cv_lib_$ac_lib_var=yes"
 else
@@ -91540,7 +91774,7 @@ else
   echo "$ac_t""no" 1>&6
 
     echo $ac_n "checking for __dn_skipname in -lsocket""... $ac_c" 1>&6
-echo "configure:91544: checking for __dn_skipname in -lsocket" >&5
+echo "configure:91783: checking for __dn_skipname in -lsocket" >&5
 ac_lib_var=`echo socket'_'__dn_skipname | sed 'y%./+-%__p_%'`
 if eval "test \"`echo '$''{'ac_cv_lib_$ac_lib_var'+set}'`\" = set"; then
   echo $ac_n "(cached) $ac_c" 1>&6
@@ -91548,7 +91782,7 @@ else
   ac_save_LIBS="$LIBS"
 LIBS="-lsocket  $LIBS"
 cat > conftest.$ac_ext <<EOF
-#line 91552 "configure"
+#line 91791 "configure"
 #include "confdefs.h"
 /* Override any gcc2 internal prototype to avoid an error.  */
 /* We use char because int might match the return type of a gcc2
@@ -91559,7 +91793,7 @@ int main() {
 __dn_skipname()
 ; return 0; }
 EOF
-if { (eval echo configure:91563: \"$ac_link\") 1>&5; (eval $ac_link) 2>&5; } && test -s conftest${ac_exeext}; then
+if { (eval echo configure:91802: \"$ac_link\") 1>&5; (eval $ac_link) 2>&5; } && test -s conftest${ac_exeext}; then
   rm -rf conftest*
   eval "ac_cv_lib_$ac_lib_var=yes"
 else
@@ -91591,11 +91825,11 @@ fi
   found=no
 else
   cat > conftest.$ac_ext <<EOF
-#line 91595 "configure"
+#line 91834 "configure"
 #include "confdefs.h"
 main() { return (0); }
 EOF
-if { (eval echo configure:91599: \"$ac_link\") 1>&5; (eval $ac_link) 2>&5; } && test -s conftest${ac_exeext} && (./conftest; exit) 2>/dev/null
+if { (eval echo configure:91838: \"$ac_link\") 1>&5; (eval $ac_link) 2>&5; } && test -s conftest${ac_exeext} && (./conftest; exit) 2>/dev/null
 then
   found=yes
 else
@@ -91654,12 +91888,12 @@ EOF
   unset found
   
   echo $ac_n "checking for res_search""... $ac_c" 1>&6
-echo "configure:91658: checking for res_search" >&5
+echo "configure:91897: checking for res_search" >&5
 if eval "test \"`echo '$''{'ac_cv_func_res_search'+set}'`\" = set"; then
   echo $ac_n "(cached) $ac_c" 1>&6
 else
   cat > conftest.$ac_ext <<EOF
-#line 91663 "configure"
+#line 91902 "configure"
 #include "confdefs.h"
 /* System header to define __stub macros and hopefully few prototypes,
     which can conflict with char res_search(); below.  */
@@ -91682,7 +91916,7 @@ res_search();
 
 ; return 0; }
 EOF
-if { (eval echo configure:91686: \"$ac_link\") 1>&5; (eval $ac_link) 2>&5; } && test -s conftest${ac_exeext}; then
+if { (eval echo configure:91925: \"$ac_link\") 1>&5; (eval $ac_link) 2>&5; } && test -s conftest${ac_exeext}; then
   rm -rf conftest*
   eval "ac_cv_func_res_search=yes"
 else
@@ -91700,12 +91934,12 @@ if eval "test \"`echo '$ac_cv_func_'res_search`\" = yes"; then
 else
   echo "$ac_t""no" 1>&6
  echo $ac_n "checking for __res_search""... $ac_c" 1>&6
-echo "configure:91704: checking for __res_search" >&5
+echo "configure:91943: checking for __res_search" >&5
 if eval "test \"`echo '$''{'ac_cv_func___res_search'+set}'`\" = set"; then
   echo $ac_n "(cached) $ac_c" 1>&6
 else
   cat > conftest.$ac_ext <<EOF
-#line 91709 "configure"
+#line 91948 "configure"
 #include "confdefs.h"
 /* System header to define __stub macros and hopefully few prototypes,
     which can conflict with char __res_search(); below.  */
@@ -91728,7 +91962,7 @@ __res_search();
 
 ; return 0; }
 EOF
-if { (eval echo configure:91732: \"$ac_link\") 1>&5; (eval $ac_link) 2>&5; } && test -s conftest${ac_exeext}; then
+if { (eval echo configure:91971: \"$ac_link\") 1>&5; (eval $ac_link) 2>&5; } && test -s conftest${ac_exeext}; then
   rm -rf conftest*
   eval "ac_cv_func___res_search=yes"
 else
@@ -91766,7 +92000,7 @@ EOF
   unset ac_cv_lib_resolv___res_search
   unset found
   echo $ac_n "checking for res_search in -lresolv""... $ac_c" 1>&6
-echo "configure:91770: checking for res_search in -lresolv" >&5
+echo "configure:92009: checking for res_search in -lresolv" >&5
 ac_lib_var=`echo resolv'_'res_search | sed 'y%./+-%__p_%'`
 if eval "test \"`echo '$''{'ac_cv_lib_$ac_lib_var'+set}'`\" = set"; then
   echo $ac_n "(cached) $ac_c" 1>&6
@@ -91774,7 +92008,7 @@ else
   ac_save_LIBS="$LIBS"
 LIBS="-lresolv  $LIBS"
 cat > conftest.$ac_ext <<EOF
-#line 91778 "configure"
+#line 92017 "configure"
 #include "confdefs.h"
 /* Override any gcc2 internal prototype to avoid an error.  */
 /* We use char because int might match the return type of a gcc2
@@ -91785,7 +92019,7 @@ int main() {
 res_search()
 ; return 0; }
 EOF
-if { (eval echo configure:91789: \"$ac_link\") 1>&5; (eval $ac_link) 2>&5; } && test -s conftest${ac_exeext}; then
+if { (eval echo configure:92028: \"$ac_link\") 1>&5; (eval $ac_link) 2>&5; } && test -s conftest${ac_exeext}; then
   rm -rf conftest*
   eval "ac_cv_lib_$ac_lib_var=yes"
 else
@@ -91805,7 +92039,7 @@ else
   echo "$ac_t""no" 1>&6
 
     echo $ac_n "checking for __res_search in -lresolv""... $ac_c" 1>&6
-echo "configure:91809: checking for __res_search in -lresolv" >&5
+echo "configure:92048: checking for __res_search in -lresolv" >&5
 ac_lib_var=`echo resolv'_'__res_search | sed 'y%./+-%__p_%'`
 if eval "test \"`echo '$''{'ac_cv_lib_$ac_lib_var'+set}'`\" = set"; then
   echo $ac_n "(cached) $ac_c" 1>&6
@@ -91813,7 +92047,7 @@ else
   ac_save_LIBS="$LIBS"
 LIBS="-lresolv  $LIBS"
 cat > conftest.$ac_ext <<EOF
-#line 91817 "configure"
+#line 92056 "configure"
 #include "confdefs.h"
 /* Override any gcc2 internal prototype to avoid an error.  */
 /* We use char because int might match the return type of a gcc2
@@ -91824,7 +92058,7 @@ int main() {
 __res_search()
 ; return 0; }
 EOF
-if { (eval echo configure:91828: \"$ac_link\") 1>&5; (eval $ac_link) 2>&5; } && test -s conftest${ac_exeext}; then
+if { (eval echo configure:92067: \"$ac_link\") 1>&5; (eval $ac_link) 2>&5; } && test -s conftest${ac_exeext}; then
   rm -rf conftest*
   eval "ac_cv_lib_$ac_lib_var=yes"
 else
@@ -91856,11 +92090,11 @@ fi
   found=no
 else
   cat > conftest.$ac_ext <<EOF
-#line 91860 "configure"
+#line 92099 "configure"
 #include "confdefs.h"
 main() { return (0); }
 EOF
-if { (eval echo configure:91864: \"$ac_link\") 1>&5; (eval $ac_link) 2>&5; } && test -s conftest${ac_exeext} && (./conftest; exit) 2>/dev/null
+if { (eval echo configure:92103: \"$ac_link\") 1>&5; (eval $ac_link) 2>&5; } && test -s conftest${ac_exeext} && (./conftest; exit) 2>/dev/null
 then
   found=yes
 else
@@ -91902,7 +92136,7 @@ EOF
   unset ac_cv_lib_bind___res_search
   unset found
   echo $ac_n "checking for res_search in -lbind""... $ac_c" 1>&6
-echo "configure:91906: checking for res_search in -lbind" >&5
+echo "configure:92145: checking for res_search in -lbind" >&5
 ac_lib_var=`echo bind'_'res_search | sed 'y%./+-%__p_%'`
 if eval "test \"`echo '$''{'ac_cv_lib_$ac_lib_var'+set}'`\" = set"; then
   echo $ac_n "(cached) $ac_c" 1>&6
@@ -91910,7 +92144,7 @@ else
   ac_save_LIBS="$LIBS"
 LIBS="-lbind  $LIBS"
 cat > conftest.$ac_ext <<EOF
-#line 91914 "configure"
+#line 92153 "configure"
 #include "confdefs.h"
 /* Override any gcc2 internal prototype to avoid an error.  */
 /* We use char because int might match the return type of a gcc2
@@ -91921,7 +92155,7 @@ int main() {
 res_search()
 ; return 0; }
 EOF
-if { (eval echo configure:91925: \"$ac_link\") 1>&5; (eval $ac_link) 2>&5; } && test -s conftest${ac_exeext}; then
+if { (eval echo configure:92164: \"$ac_link\") 1>&5; (eval $ac_link) 2>&5; } && test -s conftest${ac_exeext}; then
   rm -rf conftest*
   eval "ac_cv_lib_$ac_lib_var=yes"
 else
@@ -91941,7 +92175,7 @@ else
   echo "$ac_t""no" 1>&6
 
     echo $ac_n "checking for __res_search in -lbind""... $ac_c" 1>&6
-echo "configure:91945: checking for __res_search in -lbind" >&5
+echo "configure:92184: checking for __res_search in -lbind" >&5
 ac_lib_var=`echo bind'_'__res_search | sed 'y%./+-%__p_%'`
 if eval "test \"`echo '$''{'ac_cv_lib_$ac_lib_var'+set}'`\" = set"; then
   echo $ac_n "(cached) $ac_c" 1>&6
@@ -91949,7 +92183,7 @@ else
   ac_save_LIBS="$LIBS"
 LIBS="-lbind  $LIBS"
 cat > conftest.$ac_ext <<EOF
-#line 91953 "configure"
+#line 92192 "configure"
 #include "confdefs.h"
 /* Override any gcc2 internal prototype to avoid an error.  */
 /* We use char because int might match the return type of a gcc2
@@ -91960,7 +92194,7 @@ int main() {
 __res_search()
 ; return 0; }
 EOF
-if { (eval echo configure:91964: \"$ac_link\") 1>&5; (eval $ac_link) 2>&5; } && test -s conftest${ac_exeext}; then
+if { (eval echo configure:92203: \"$ac_link\") 1>&5; (eval $ac_link) 2>&5; } && test -s conftest${ac_exeext}; then
   rm -rf conftest*
   eval "ac_cv_lib_$ac_lib_var=yes"
 else
@@ -91992,11 +92226,11 @@ fi
   found=no
 else
   cat > conftest.$ac_ext <<EOF
-#line 91996 "configure"
+#line 92235 "configure"
 #include "confdefs.h"
 main() { return (0); }
 EOF
-if { (eval echo configure:92000: \"$ac_link\") 1>&5; (eval $ac_link) 2>&5; } && test -s conftest${ac_exeext} && (./conftest; exit) 2>/dev/null
+if { (eval echo configure:92239: \"$ac_link\") 1>&5; (eval $ac_link) 2>&5; } && test -s conftest${ac_exeext} && (./conftest; exit) 2>/dev/null
 then
   found=yes
 else
@@ -92038,7 +92272,7 @@ EOF
   unset ac_cv_lib_socket___res_search
   unset found
   echo $ac_n "checking for res_search in -lsocket""... $ac_c" 1>&6
-echo "configure:92042: checking for res_search in -lsocket" >&5
+echo "configure:92281: checking for res_search in -lsocket" >&5
 ac_lib_var=`echo socket'_'res_search | sed 'y%./+-%__p_%'`
 if eval "test \"`echo '$''{'ac_cv_lib_$ac_lib_var'+set}'`\" = set"; then
   echo $ac_n "(cached) $ac_c" 1>&6
@@ -92046,7 +92280,7 @@ else
   ac_save_LIBS="$LIBS"
 LIBS="-lsocket  $LIBS"
 cat > conftest.$ac_ext <<EOF
-#line 92050 "configure"
+#line 92289 "configure"
 #include "confdefs.h"
 /* Override any gcc2 internal prototype to avoid an error.  */
 /* We use char because int might match the return type of a gcc2
@@ -92057,7 +92291,7 @@ int main() {
 res_search()
 ; return 0; }
 EOF
-if { (eval echo configure:92061: \"$ac_link\") 1>&5; (eval $ac_link) 2>&5; } && test -s conftest${ac_exeext}; then
+if { (eval echo configure:92300: \"$ac_link\") 1>&5; (eval $ac_link) 2>&5; } && test -s conftest${ac_exeext}; then
   rm -rf conftest*
   eval "ac_cv_lib_$ac_lib_var=yes"
 else
@@ -92077,7 +92311,7 @@ else
   echo "$ac_t""no" 1>&6
 
     echo $ac_n "checking for __res_search in -lsocket""... $ac_c" 1>&6
-echo "configure:92081: checking for __res_search in -lsocket" >&5
+echo "configure:92320: checking for __res_search in -lsocket" >&5
 ac_lib_var=`echo socket'_'__res_search | sed 'y%./+-%__p_%'`
 if eval "test \"`echo '$''{'ac_cv_lib_$ac_lib_var'+set}'`\" = set"; then
   echo $ac_n "(cached) $ac_c" 1>&6
@@ -92085,7 +92319,7 @@ else
   ac_save_LIBS="$LIBS"
 LIBS="-lsocket  $LIBS"
 cat > conftest.$ac_ext <<EOF
-#line 92089 "configure"
+#line 92328 "configure"
 #include "confdefs.h"
 /* Override any gcc2 internal prototype to avoid an error.  */
 /* We use char because int might match the return type of a gcc2
@@ -92096,7 +92330,7 @@ int main() {
 __res_search()
 ; return 0; }
 EOF
-if { (eval echo configure:92100: \"$ac_link\") 1>&5; (eval $ac_link) 2>&5; } && test -s conftest${ac_exeext}; then
+if { (eval echo configure:92339: \"$ac_link\") 1>&5; (eval $ac_link) 2>&5; } && test -s conftest${ac_exeext}; then
   rm -rf conftest*
   eval "ac_cv_lib_$ac_lib_var=yes"
 else
@@ -92128,11 +92362,11 @@ fi
   found=no
 else
   cat > conftest.$ac_ext <<EOF
-#line 92132 "configure"
+#line 92371 "configure"
 #include "confdefs.h"
 main() { return (0); }
 EOF
-if { (eval echo configure:92136: \"$ac_link\") 1>&5; (eval $ac_link) 2>&5; } && test -s conftest${ac_exeext} && (./conftest; exit) 2>/dev/null
+if { (eval echo configure:92375: \"$ac_link\") 1>&5; (eval $ac_link) 2>&5; } && test -s conftest${ac_exeext} && (./conftest; exit) 2>/dev/null
 then
   found=yes
 else
@@ -92185,7 +92419,7 @@ EOF
 
 
 echo $ac_n "checking whether atof() accepts NAN""... $ac_c" 1>&6
-echo "configure:92189: checking whether atof() accepts NAN" >&5
+echo "configure:92428: checking whether atof() accepts NAN" >&5
 if eval "test \"`echo '$''{'ac_cv_atof_accept_nan'+set}'`\" = set"; then
   echo $ac_n "(cached) $ac_c" 1>&6
 else
@@ -92196,7 +92430,7 @@ if test "$cross_compiling" = yes; then
 
 else
   cat > conftest.$ac_ext <<EOF
-#line 92200 "configure"
+#line 92439 "configure"
 #include "confdefs.h"
 
 #include <math.h>
@@ -92216,7 +92450,7 @@ int main(int argc, char** argv)
 }
 
 EOF
-if { (eval echo configure:92220: \"$ac_link\") 1>&5; (eval $ac_link) 2>&5; } && test -s conftest${ac_exeext} && (./conftest; exit) 2>/dev/null
+if { (eval echo configure:92459: \"$ac_link\") 1>&5; (eval $ac_link) 2>&5; } && test -s conftest${ac_exeext} && (./conftest; exit) 2>/dev/null
 then
   
   ac_cv_atof_accept_nan=yes
@@ -92243,7 +92477,7 @@ EOF
 fi
 
 echo $ac_n "checking whether atof() accepts INF""... $ac_c" 1>&6
-echo "configure:92247: checking whether atof() accepts INF" >&5
+echo "configure:92486: checking whether atof() accepts INF" >&5
 if eval "test \"`echo '$''{'ac_cv_atof_accept_inf'+set}'`\" = set"; then
   echo $ac_n "(cached) $ac_c" 1>&6
 else
@@ -92254,7 +92488,7 @@ if test "$cross_compiling" = yes; then
 
 else
   cat > conftest.$ac_ext <<EOF
-#line 92258 "configure"
+#line 92497 "configure"
 #include "confdefs.h"
 
 #include <math.h>
@@ -92277,7 +92511,7 @@ int main(int argc, char** argv)
 }
 
 EOF
-if { (eval echo configure:92281: \"$ac_link\") 1>&5; (eval $ac_link) 2>&5; } && test -s conftest${ac_exeext} && (./conftest; exit) 2>/dev/null
+if { (eval echo configure:92520: \"$ac_link\") 1>&5; (eval $ac_link) 2>&5; } && test -s conftest${ac_exeext} && (./conftest; exit) 2>/dev/null
 then
   
   ac_cv_atof_accept_inf=yes
@@ -92304,7 +92538,7 @@ EOF
 fi
 
 echo $ac_n "checking whether HUGE_VAL == INF""... $ac_c" 1>&6
-echo "configure:92308: checking whether HUGE_VAL == INF" >&5
+echo "configure:92547: checking whether HUGE_VAL == INF" >&5
 if eval "test \"`echo '$''{'ac_cv_huge_val_inf'+set}'`\" = set"; then
   echo $ac_n "(cached) $ac_c" 1>&6
 else
@@ -92315,7 +92549,7 @@ if test "$cross_compiling" = yes; then
 
 else
   cat > conftest.$ac_ext <<EOF
-#line 92319 "configure"
+#line 92558 "configure"
 #include "confdefs.h"
 
 #include <math.h>
@@ -92338,7 +92572,7 @@ int main(int argc, char** argv)
 }
 
 EOF
-if { (eval echo configure:92342: \"$ac_link\") 1>&5; (eval $ac_link) 2>&5; } && test -s conftest${ac_exeext} && (./conftest; exit) 2>/dev/null
+if { (eval echo configure:92581: \"$ac_link\") 1>&5; (eval $ac_link) 2>&5; } && test -s conftest${ac_exeext} && (./conftest; exit) 2>/dev/null
 then
   
   ac_cv_huge_val_inf=yes
@@ -92365,7 +92599,7 @@ EOF
 fi
 
 echo $ac_n "checking whether HUGE_VAL + -HUGEVAL == NAN""... $ac_c" 1>&6
-echo "configure:92369: checking whether HUGE_VAL + -HUGEVAL == NAN" >&5
+echo "configure:92608: checking whether HUGE_VAL + -HUGEVAL == NAN" >&5
 if eval "test \"`echo '$''{'ac_cv_huge_val_nan'+set}'`\" = set"; then
   echo $ac_n "(cached) $ac_c" 1>&6
 else
@@ -92376,7 +92610,7 @@ if test "$cross_compiling" = yes; then
 
 else
   cat > conftest.$ac_ext <<EOF
-#line 92380 "configure"
+#line 92619 "configure"
 #include "confdefs.h"
 
 #include <math.h>
@@ -92401,7 +92635,7 @@ int main(int argc, char** argv)
 }
 
 EOF
-if { (eval echo configure:92405: \"$ac_link\") 1>&5; (eval $ac_link) 2>&5; } && test -s conftest${ac_exeext} && (./conftest; exit) 2>/dev/null
+if { (eval echo configure:92644: \"$ac_link\") 1>&5; (eval $ac_link) 2>&5; } && test -s conftest${ac_exeext} && (./conftest; exit) 2>/dev/null
 then
   
   ac_cv_huge_val_nan=yes
@@ -92428,13 +92662,13 @@ EOF
 fi
 
 echo $ac_n "checking whether strptime() declaration fails""... $ac_c" 1>&6
-echo "configure:92432: checking whether strptime() declaration fails" >&5
+echo "configure:92671: checking whether strptime() declaration fails" >&5
 if eval "test \"`echo '$''{'ac_cv_strptime_decl_fails'+set}'`\" = set"; then
   echo $ac_n "(cached) $ac_c" 1>&6
 else
   
 cat > conftest.$ac_ext <<EOF
-#line 92438 "configure"
+#line 92677 "configure"
 #include "confdefs.h"
 
 #include <time.h>
@@ -92450,7 +92684,7 @@ int strptime(const char *s, const char *format, struct tm *tm);
 
 ; return 0; }
 EOF
-if { (eval echo configure:92454: \"$ac_compile\") 1>&5; (eval $ac_compile) 2>&5; }; then
+if { (eval echo configure:92693: \"$ac_compile\") 1>&5; (eval $ac_compile) 2>&5; }; then
   rm -rf conftest*
   
   ac_cv_strptime_decl_fails=no
@@ -92478,17 +92712,17 @@ for ac_hdr in wchar.h
 do
 ac_safe=`echo "$ac_hdr" | sed 'y%./+-%__p_%'`
 echo $ac_n "checking for $ac_hdr""... $ac_c" 1>&6
-echo "configure:92482: checking for $ac_hdr" >&5
+echo "configure:92721: checking for $ac_hdr" >&5
 if eval "test \"`echo '$''{'ac_cv_header_$ac_safe'+set}'`\" = set"; then
   echo $ac_n "(cached) $ac_c" 1>&6
 else
   cat > conftest.$ac_ext <<EOF
-#line 92487 "configure"
+#line 92726 "configure"
 #include "confdefs.h"
 #include <$ac_hdr>
 EOF
 ac_try="$ac_cpp conftest.$ac_ext >/dev/null 2>conftest.out"
-{ (eval echo configure:92492: \"$ac_try\") 1>&5; (eval $ac_try) 2>&5; }
+{ (eval echo configure:92731: \"$ac_try\") 1>&5; (eval $ac_try) 2>&5; }
 ac_err=`grep -v '^ *+' conftest.out | grep -v "^conftest.${ac_ext}\$"`
 if test -z "$ac_err"; then
   rm -rf conftest*
@@ -92517,12 +92751,12 @@ done
 for ac_func in mblen
 do
 echo $ac_n "checking for $ac_func""... $ac_c" 1>&6
-echo "configure:92521: checking for $ac_func" >&5
+echo "configure:92760: checking for $ac_func" >&5
 if eval "test \"`echo '$''{'ac_cv_func_$ac_func'+set}'`\" = set"; then
   echo $ac_n "(cached) $ac_c" 1>&6
 else
   cat > conftest.$ac_ext <<EOF
-#line 92526 "configure"
+#line 92765 "configure"
 #include "confdefs.h"
 /* System header to define __stub macros and hopefully few prototypes,
     which can conflict with char $ac_func(); below.  */
@@ -92545,7 +92779,7 @@ $ac_func();
 
 ; return 0; }
 EOF
-if { (eval echo configure:92549: \"$ac_link\") 1>&5; (eval $ac_link) 2>&5; } && test -s conftest${ac_exeext}; then
+if { (eval echo configure:92788: \"$ac_link\") 1>&5; (eval $ac_link) 2>&5; } && test -s conftest${ac_exeext}; then
   rm -rf conftest*
   eval "ac_cv_func_$ac_func=yes"
 else
@@ -92572,12 +92806,12 @@ done
 for ac_func in mbrlen mbsinit
 do
 echo $ac_n "checking for $ac_func""... $ac_c" 1>&6
-echo "configure:92576: checking for $ac_func" >&5
+echo "configure:92815: checking for $ac_func" >&5
 if eval "test \"`echo '$''{'ac_cv_func_$ac_func'+set}'`\" = set"; then
   echo $ac_n "(cached) $ac_c" 1>&6
 else
   cat > conftest.$ac_ext <<EOF
-#line 92581 "configure"
+#line 92820 "configure"
 #include "confdefs.h"
 /* System header to define __stub macros and hopefully few prototypes,
     which can conflict with char $ac_func(); below.  */
@@ -92600,7 +92834,7 @@ $ac_func();
 
 ; return 0; }
 EOF
-if { (eval echo configure:92604: \"$ac_link\") 1>&5; (eval $ac_link) 2>&5; } && test -s conftest${ac_exeext}; then
+if { (eval echo configure:92843: \"$ac_link\") 1>&5; (eval $ac_link) 2>&5; } && test -s conftest${ac_exeext}; then
   rm -rf conftest*
   eval "ac_cv_func_$ac_func=yes"
 else
@@ -92625,13 +92859,13 @@ fi
 done
 
 echo $ac_n "checking for mbstate_t""... $ac_c" 1>&6
-echo "configure:92629: checking for mbstate_t" >&5
+echo "configure:92868: checking for mbstate_t" >&5
 if eval "test \"`echo '$''{'ac_cv_type_mbstate_t'+set}'`\" = set"; then
   echo $ac_n "(cached) $ac_c" 1>&6
 else
   
 cat > conftest.$ac_ext <<EOF
-#line 92635 "configure"
+#line 92874 "configure"
 #include "confdefs.h"
 
 #ifdef HAVE_WCHAR_H
@@ -92644,7 +92878,7 @@ int __tmp__() { mbstate_t a; }
 
 ; return 0; }
 EOF
-if { (eval echo configure:92648: \"$ac_compile\") 1>&5; (eval $ac_compile) 2>&5; }; then
+if { (eval echo configure:92887: \"$ac_compile\") 1>&5; (eval $ac_compile) 2>&5; }; then
   rm -rf conftest*
   
   ac_cv_type_mbstate_t=yes
@@ -92672,17 +92906,17 @@ for ac_hdr in atomic.h
 do
 ac_safe=`echo "$ac_hdr" | sed 'y%./+-%__p_%'`
 echo $ac_n "checking for $ac_hdr""... $ac_c" 1>&6
-echo "configure:92676: checking for $ac_hdr" >&5
+echo "configure:92915: checking for $ac_hdr" >&5
 if eval "test \"`echo '$''{'ac_cv_header_$ac_safe'+set}'`\" = set"; then
   echo $ac_n "(cached) $ac_c" 1>&6
 else
   cat > conftest.$ac_ext <<EOF
-#line 92681 "configure"
+#line 92920 "configure"
 #include "confdefs.h"
 #include <$ac_hdr>
 EOF
 ac_try="$ac_cpp conftest.$ac_ext >/dev/null 2>conftest.out"
-{ (eval echo configure:92686: \"$ac_try\") 1>&5; (eval $ac_try) 2>&5; }
+{ (eval echo configure:92925: \"$ac_try\") 1>&5; (eval $ac_try) 2>&5; }
 ac_err=`grep -v '^ *+' conftest.out | grep -v "^conftest.${ac_ext}\$"`
 if test -z "$ac_err"; then
   rm -rf conftest*
@@ -93008,7 +93242,7 @@ EOF
       ac_obj=$1
       IFS=$old_IFS
       
-      PHP_CLI_OBJS="$PHP_CLI_OBJS $ac_bdir$ac_obj.lo"
+      PHP_GLOBAL_OBJS="$PHP_GLOBAL_OBJS $ac_bdir$ac_obj.lo"
 
       case $ac_src in
         *.c) ac_comp="$b_c_pre $ac_extra $ac_inc $b_c_meta -c $ac_srcdir$ac_src -o $ac_bdir$ac_obj.$b_lo $b_c_post" ;;
@@ -93072,7 +93306,7 @@ EOF
 php_with_sybase_ct=no
 
 echo $ac_n "checking for Sybase-CT support""... $ac_c" 1>&6
-echo "configure:93076: checking for Sybase-CT support" >&5
+echo "configure:93315: checking for Sybase-CT support" >&5
 # Check whether --with-sybase-ct or --without-sybase-ct was given.
 if test "${with_sybase_ct+set}" = set; then
   withval="$with_sybase_ct"
@@ -93381,7 +93615,7 @@ EOF
       ac_obj=$1
       IFS=$old_IFS
       
-      PHP_CLI_OBJS="$PHP_CLI_OBJS $ac_bdir$ac_obj.lo"
+      PHP_GLOBAL_OBJS="$PHP_GLOBAL_OBJS $ac_bdir$ac_obj.lo"
 
       case $ac_src in
         *.c) ac_comp="$b_c_pre $ac_extra $ac_inc $b_c_meta -c $ac_srcdir$ac_src -o $ac_bdir$ac_obj.$b_lo $b_c_post" ;;
@@ -93427,7 +93661,7 @@ EOF
   fi
 
     echo $ac_n "checking size of long int""... $ac_c" 1>&6
-echo "configure:93431: checking size of long int" >&5
+echo "configure:93670: checking size of long int" >&5
 if eval "test \"`echo '$''{'ac_cv_sizeof_long_int'+set}'`\" = set"; then
   echo $ac_n "(cached) $ac_c" 1>&6
 else
@@ -93435,7 +93669,7 @@ else
   ac_cv_sizeof_long_int=4
 else
   cat > conftest.$ac_ext <<EOF
-#line 93439 "configure"
+#line 93678 "configure"
 #include "confdefs.h"
 #include <stdio.h>
 main()
@@ -93446,7 +93680,7 @@ main()
   exit(0);
 }
 EOF
-if { (eval echo configure:93450: \"$ac_link\") 1>&5; (eval $ac_link) 2>&5; } && test -s conftest${ac_exeext} && (./conftest; exit) 2>/dev/null
+if { (eval echo configure:93689: \"$ac_link\") 1>&5; (eval $ac_link) 2>&5; } && test -s conftest${ac_exeext} && (./conftest; exit) 2>/dev/null
 then
   ac_cv_sizeof_long_int=`cat conftestval`
 else
@@ -93466,7 +93700,7 @@ EOF
 
 
   echo $ac_n "checking checking if we're on a 64-bit platform""... $ac_c" 1>&6
-echo "configure:93470: checking checking if we're on a 64-bit platform" >&5
+echo "configure:93709: checking checking if we're on a 64-bit platform" >&5
   if test "$ac_cv_sizeof_long_int" = "4"; then
     echo "$ac_t""no" 1>&6
     PHP_SYBASE_64=no
@@ -93477,7 +93711,7 @@ echo "configure:93470: checking checking if we're on a 64-bit platform" >&5
 
 
   echo $ac_n "checking Checking for ctpublic.h""... $ac_c" 1>&6
-echo "configure:93481: checking Checking for ctpublic.h" >&5
+echo "configure:93720: checking Checking for ctpublic.h" >&5
   if test -f $SYBASE_CT_INCDIR/ctpublic.h; then
     echo "$ac_t""found in $SYBASE_CT_INCDIR" 1>&6
     
@@ -93516,11 +93750,11 @@ echo "configure:93481: checking Checking for ctpublic.h" >&5
   fi
  
   echo $ac_n "checking Checking Sybase libdir""... $ac_c" 1>&6
-echo "configure:93520: checking Checking Sybase libdir" >&5
+echo "configure:93759: checking Checking Sybase libdir" >&5
   echo "$ac_t""Have $SYBASE_CT_LIBDIR" 1>&6
  
   echo $ac_n "checking Checking for Sybase platform libraries""... $ac_c" 1>&6
-echo "configure:93524: checking Checking for Sybase platform libraries" >&5
+echo "configure:93763: checking Checking for Sybase platform libraries" >&5
 
   
   if test "$SYBASE_CT_LIBDIR" != "/usr/$PHP_LIBDIR" && test "$SYBASE_CT_LIBDIR" != "/usr/lib"; then
@@ -93786,7 +94020,7 @@ echo "configure:93524: checking Checking for Sybase platform libraries" >&5
   done
 
   echo $ac_n "checking for netg_errstr in -lsybtcl64""... $ac_c" 1>&6
-echo "configure:93790: checking for netg_errstr in -lsybtcl64" >&5
+echo "configure:94029: checking for netg_errstr in -lsybtcl64" >&5
 ac_lib_var=`echo sybtcl64'_'netg_errstr | sed 'y%./+-%__p_%'`
 if eval "test \"`echo '$''{'ac_cv_lib_$ac_lib_var'+set}'`\" = set"; then
   echo $ac_n "(cached) $ac_c" 1>&6
@@ -93794,7 +94028,7 @@ else
   ac_save_LIBS="$LIBS"
 LIBS="-lsybtcl64  $LIBS"
 cat > conftest.$ac_ext <<EOF
-#line 93798 "configure"
+#line 94037 "configure"
 #include "confdefs.h"
 /* Override any gcc2 internal prototype to avoid an error.  */
 /* We use char because int might match the return type of a gcc2
@@ -93805,7 +94039,7 @@ int main() {
 netg_errstr()
 ; return 0; }
 EOF
-if { (eval echo configure:93809: \"$ac_link\") 1>&5; (eval $ac_link) 2>&5; } && test -s conftest${ac_exeext}; then
+if { (eval echo configure:94048: \"$ac_link\") 1>&5; (eval $ac_link) 2>&5; } && test -s conftest${ac_exeext}; then
   rm -rf conftest*
   eval "ac_cv_lib_$ac_lib_var=yes"
 else
@@ -93980,7 +94214,7 @@ fi
   done
 
   echo $ac_n "checking for insck__getVdate in -linsck64""... $ac_c" 1>&6
-echo "configure:93984: checking for insck__getVdate in -linsck64" >&5
+echo "configure:94223: checking for insck__getVdate in -linsck64" >&5
 ac_lib_var=`echo insck64'_'insck__getVdate | sed 'y%./+-%__p_%'`
 if eval "test \"`echo '$''{'ac_cv_lib_$ac_lib_var'+set}'`\" = set"; then
   echo $ac_n "(cached) $ac_c" 1>&6
@@ -93988,7 +94222,7 @@ else
   ac_save_LIBS="$LIBS"
 LIBS="-linsck64  $LIBS"
 cat > conftest.$ac_ext <<EOF
-#line 93992 "configure"
+#line 94231 "configure"
 #include "confdefs.h"
 /* Override any gcc2 internal prototype to avoid an error.  */
 /* We use char because int might match the return type of a gcc2
@@ -93999,7 +94233,7 @@ int main() {
 insck__getVdate()
 ; return 0; }
 EOF
-if { (eval echo configure:94003: \"$ac_link\") 1>&5; (eval $ac_link) 2>&5; } && test -s conftest${ac_exeext}; then
+if { (eval echo configure:94242: \"$ac_link\") 1>&5; (eval $ac_link) 2>&5; } && test -s conftest${ac_exeext}; then
   rm -rf conftest*
   eval "ac_cv_lib_$ac_lib_var=yes"
 else
@@ -94147,7 +94381,7 @@ fi
   done
 
   echo $ac_n "checking for bsd_tcp in -linsck64""... $ac_c" 1>&6
-echo "configure:94151: checking for bsd_tcp in -linsck64" >&5
+echo "configure:94390: checking for bsd_tcp in -linsck64" >&5
 ac_lib_var=`echo insck64'_'bsd_tcp | sed 'y%./+-%__p_%'`
 if eval "test \"`echo '$''{'ac_cv_lib_$ac_lib_var'+set}'`\" = set"; then
   echo $ac_n "(cached) $ac_c" 1>&6
@@ -94155,7 +94389,7 @@ else
   ac_save_LIBS="$LIBS"
 LIBS="-linsck64  $LIBS"
 cat > conftest.$ac_ext <<EOF
-#line 94159 "configure"
+#line 94398 "configure"
 #include "confdefs.h"
 /* Override any gcc2 internal prototype to avoid an error.  */
 /* We use char because int might match the return type of a gcc2
@@ -94166,7 +94400,7 @@ int main() {
 bsd_tcp()
 ; return 0; }
 EOF
-if { (eval echo configure:94170: \"$ac_link\") 1>&5; (eval $ac_link) 2>&5; } && test -s conftest${ac_exeext}; then
+if { (eval echo configure:94409: \"$ac_link\") 1>&5; (eval $ac_link) 2>&5; } && test -s conftest${ac_exeext}; then
   rm -rf conftest*
   eval "ac_cv_lib_$ac_lib_var=yes"
 else
@@ -94413,7 +94647,7 @@ fi
   done
 
   echo $ac_n "checking for netg_errstr in -lsybtcl""... $ac_c" 1>&6
-echo "configure:94417: checking for netg_errstr in -lsybtcl" >&5
+echo "configure:94656: checking for netg_errstr in -lsybtcl" >&5
 ac_lib_var=`echo sybtcl'_'netg_errstr | sed 'y%./+-%__p_%'`
 if eval "test \"`echo '$''{'ac_cv_lib_$ac_lib_var'+set}'`\" = set"; then
   echo $ac_n "(cached) $ac_c" 1>&6
@@ -94421,7 +94655,7 @@ else
   ac_save_LIBS="$LIBS"
 LIBS="-lsybtcl  $LIBS"
 cat > conftest.$ac_ext <<EOF
-#line 94425 "configure"
+#line 94664 "configure"
 #include "confdefs.h"
 /* Override any gcc2 internal prototype to avoid an error.  */
 /* We use char because int might match the return type of a gcc2
@@ -94432,7 +94666,7 @@ int main() {
 netg_errstr()
 ; return 0; }
 EOF
-if { (eval echo configure:94436: \"$ac_link\") 1>&5; (eval $ac_link) 2>&5; } && test -s conftest${ac_exeext}; then
+if { (eval echo configure:94675: \"$ac_link\") 1>&5; (eval $ac_link) 2>&5; } && test -s conftest${ac_exeext}; then
   rm -rf conftest*
   eval "ac_cv_lib_$ac_lib_var=yes"
 else
@@ -94607,7 +94841,7 @@ fi
   done
 
   echo $ac_n "checking for insck__getVdate in -linsck""... $ac_c" 1>&6
-echo "configure:94611: checking for insck__getVdate in -linsck" >&5
+echo "configure:94850: checking for insck__getVdate in -linsck" >&5
 ac_lib_var=`echo insck'_'insck__getVdate | sed 'y%./+-%__p_%'`
 if eval "test \"`echo '$''{'ac_cv_lib_$ac_lib_var'+set}'`\" = set"; then
   echo $ac_n "(cached) $ac_c" 1>&6
@@ -94615,7 +94849,7 @@ else
   ac_save_LIBS="$LIBS"
 LIBS="-linsck  $LIBS"
 cat > conftest.$ac_ext <<EOF
-#line 94619 "configure"
+#line 94858 "configure"
 #include "confdefs.h"
 /* Override any gcc2 internal prototype to avoid an error.  */
 /* We use char because int might match the return type of a gcc2
@@ -94626,7 +94860,7 @@ int main() {
 insck__getVdate()
 ; return 0; }
 EOF
-if { (eval echo configure:94630: \"$ac_link\") 1>&5; (eval $ac_link) 2>&5; } && test -s conftest${ac_exeext}; then
+if { (eval echo configure:94869: \"$ac_link\") 1>&5; (eval $ac_link) 2>&5; } && test -s conftest${ac_exeext}; then
   rm -rf conftest*
   eval "ac_cv_lib_$ac_lib_var=yes"
 else
@@ -94774,7 +95008,7 @@ fi
   done
 
   echo $ac_n "checking for bsd_tcp in -linsck""... $ac_c" 1>&6
-echo "configure:94778: checking for bsd_tcp in -linsck" >&5
+echo "configure:95017: checking for bsd_tcp in -linsck" >&5
 ac_lib_var=`echo insck'_'bsd_tcp | sed 'y%./+-%__p_%'`
 if eval "test \"`echo '$''{'ac_cv_lib_$ac_lib_var'+set}'`\" = set"; then
   echo $ac_n "(cached) $ac_c" 1>&6
@@ -94782,7 +95016,7 @@ else
   ac_save_LIBS="$LIBS"
 LIBS="-linsck  $LIBS"
 cat > conftest.$ac_ext <<EOF
-#line 94786 "configure"
+#line 95025 "configure"
 #include "confdefs.h"
 /* Override any gcc2 internal prototype to avoid an error.  */
 /* We use char because int might match the return type of a gcc2
@@ -94793,7 +95027,7 @@ int main() {
 bsd_tcp()
 ; return 0; }
 EOF
-if { (eval echo configure:94797: \"$ac_link\") 1>&5; (eval $ac_link) 2>&5; } && test -s conftest${ac_exeext}; then
+if { (eval echo configure:95036: \"$ac_link\") 1>&5; (eval $ac_link) 2>&5; } && test -s conftest${ac_exeext}; then
   rm -rf conftest*
   eval "ac_cv_lib_$ac_lib_var=yes"
 else
@@ -95040,7 +95274,7 @@ fi
   done
 
   echo $ac_n "checking for netg_errstr in -ltcl""... $ac_c" 1>&6
-echo "configure:95044: checking for netg_errstr in -ltcl" >&5
+echo "configure:95283: checking for netg_errstr in -ltcl" >&5
 ac_lib_var=`echo tcl'_'netg_errstr | sed 'y%./+-%__p_%'`
 if eval "test \"`echo '$''{'ac_cv_lib_$ac_lib_var'+set}'`\" = set"; then
   echo $ac_n "(cached) $ac_c" 1>&6
@@ -95048,7 +95282,7 @@ else
   ac_save_LIBS="$LIBS"
 LIBS="-ltcl  $LIBS"
 cat > conftest.$ac_ext <<EOF
-#line 95052 "configure"
+#line 95291 "configure"
 #include "confdefs.h"
 /* Override any gcc2 internal prototype to avoid an error.  */
 /* We use char because int might match the return type of a gcc2
@@ -95059,7 +95293,7 @@ int main() {
 netg_errstr()
 ; return 0; }
 EOF
-if { (eval echo configure:95063: \"$ac_link\") 1>&5; (eval $ac_link) 2>&5; } && test -s conftest${ac_exeext}; then
+if { (eval echo configure:95302: \"$ac_link\") 1>&5; (eval $ac_link) 2>&5; } && test -s conftest${ac_exeext}; then
   rm -rf conftest*
   eval "ac_cv_lib_$ac_lib_var=yes"
 else
@@ -95234,7 +95468,7 @@ fi
   done
 
   echo $ac_n "checking for insck__getVdate in -linsck""... $ac_c" 1>&6
-echo "configure:95238: checking for insck__getVdate in -linsck" >&5
+echo "configure:95477: checking for insck__getVdate in -linsck" >&5
 ac_lib_var=`echo insck'_'insck__getVdate | sed 'y%./+-%__p_%'`
 if eval "test \"`echo '$''{'ac_cv_lib_$ac_lib_var'+set}'`\" = set"; then
   echo $ac_n "(cached) $ac_c" 1>&6
@@ -95242,7 +95476,7 @@ else
   ac_save_LIBS="$LIBS"
 LIBS="-linsck  $LIBS"
 cat > conftest.$ac_ext <<EOF
-#line 95246 "configure"
+#line 95485 "configure"
 #include "confdefs.h"
 /* Override any gcc2 internal prototype to avoid an error.  */
 /* We use char because int might match the return type of a gcc2
@@ -95253,7 +95487,7 @@ int main() {
 insck__getVdate()
 ; return 0; }
 EOF
-if { (eval echo configure:95257: \"$ac_link\") 1>&5; (eval $ac_link) 2>&5; } && test -s conftest${ac_exeext}; then
+if { (eval echo configure:95496: \"$ac_link\") 1>&5; (eval $ac_link) 2>&5; } && test -s conftest${ac_exeext}; then
   rm -rf conftest*
   eval "ac_cv_lib_$ac_lib_var=yes"
 else
@@ -95401,7 +95635,7 @@ fi
   done
 
   echo $ac_n "checking for bsd_tcp in -linsck""... $ac_c" 1>&6
-echo "configure:95405: checking for bsd_tcp in -linsck" >&5
+echo "configure:95644: checking for bsd_tcp in -linsck" >&5
 ac_lib_var=`echo insck'_'bsd_tcp | sed 'y%./+-%__p_%'`
 if eval "test \"`echo '$''{'ac_cv_lib_$ac_lib_var'+set}'`\" = set"; then
   echo $ac_n "(cached) $ac_c" 1>&6
@@ -95409,7 +95643,7 @@ else
   ac_save_LIBS="$LIBS"
 LIBS="-linsck  $LIBS"
 cat > conftest.$ac_ext <<EOF
-#line 95413 "configure"
+#line 95652 "configure"
 #include "confdefs.h"
 /* Override any gcc2 internal prototype to avoid an error.  */
 /* We use char because int might match the return type of a gcc2
@@ -95420,7 +95654,7 @@ int main() {
 bsd_tcp()
 ; return 0; }
 EOF
-if { (eval echo configure:95424: \"$ac_link\") 1>&5; (eval $ac_link) 2>&5; } && test -s conftest${ac_exeext}; then
+if { (eval echo configure:95663: \"$ac_link\") 1>&5; (eval $ac_link) 2>&5; } && test -s conftest${ac_exeext}; then
   rm -rf conftest*
   eval "ac_cv_lib_$ac_lib_var=yes"
 else
@@ -95480,7 +95714,7 @@ fi
 php_enable_sysvmsg=no
 
 echo $ac_n "checking whether to enable System V IPC support""... $ac_c" 1>&6
-echo "configure:95484: checking whether to enable System V IPC support" >&5
+echo "configure:95723: checking whether to enable System V IPC support" >&5
 # Check whether --enable-sysvmsg or --disable-sysvmsg was given.
 if test "${enable_sysvmsg+set}" = set; then
   enableval="$enable_sysvmsg"
@@ -95523,17 +95757,17 @@ echo "$ac_t""$ext_output" 1>&6
 if test "$PHP_SYSVMSG" != "no"; then
   ac_safe=`echo "sys/msg.h" | sed 'y%./+-%__p_%'`
 echo $ac_n "checking for sys/msg.h""... $ac_c" 1>&6
-echo "configure:95527: checking for sys/msg.h" >&5
+echo "configure:95766: checking for sys/msg.h" >&5
 if eval "test \"`echo '$''{'ac_cv_header_$ac_safe'+set}'`\" = set"; then
   echo $ac_n "(cached) $ac_c" 1>&6
 else
   cat > conftest.$ac_ext <<EOF
-#line 95532 "configure"
+#line 95771 "configure"
 #include "confdefs.h"
 #include <sys/msg.h>
 EOF
 ac_try="$ac_cpp conftest.$ac_ext >/dev/null 2>conftest.out"
-{ (eval echo configure:95537: \"$ac_try\") 1>&5; (eval $ac_try) 2>&5; }
+{ (eval echo configure:95776: \"$ac_try\") 1>&5; (eval $ac_try) 2>&5; }
 ac_err=`grep -v '^ *+' conftest.out | grep -v "^conftest.${ac_ext}\$"`
 if test -z "$ac_err"; then
   rm -rf conftest*
@@ -95820,7 +96054,7 @@ EOF
       ac_obj=$1
       IFS=$old_IFS
       
-      PHP_CLI_OBJS="$PHP_CLI_OBJS $ac_bdir$ac_obj.lo"
+      PHP_GLOBAL_OBJS="$PHP_GLOBAL_OBJS $ac_bdir$ac_obj.lo"
 
       case $ac_src in
         *.c) ac_comp="$b_c_pre $ac_extra $ac_inc $b_c_meta -c $ac_srcdir$ac_src -o $ac_bdir$ac_obj.$b_lo $b_c_post" ;;
@@ -95860,7 +96094,7 @@ fi
 php_enable_sysvsem=no
 
 echo $ac_n "checking whether to enable System V semaphore support""... $ac_c" 1>&6
-echo "configure:95864: checking whether to enable System V semaphore support" >&5
+echo "configure:96103: checking whether to enable System V semaphore support" >&5
 # Check whether --enable-sysvsem or --disable-sysvsem was given.
 if test "${enable_sysvsem+set}" = set; then
   enableval="$enable_sysvsem"
@@ -96160,7 +96394,7 @@ EOF
       ac_obj=$1
       IFS=$old_IFS
       
-      PHP_CLI_OBJS="$PHP_CLI_OBJS $ac_bdir$ac_obj.lo"
+      PHP_GLOBAL_OBJS="$PHP_GLOBAL_OBJS $ac_bdir$ac_obj.lo"
 
       case $ac_src in
         *.c) ac_comp="$b_c_pre $ac_extra $ac_inc $b_c_meta -c $ac_srcdir$ac_src -o $ac_bdir$ac_obj.$b_lo $b_c_post" ;;
@@ -96198,12 +96432,12 @@ EOF
 EOF
 
  echo $ac_n "checking for union semun""... $ac_c" 1>&6
-echo "configure:96202: checking for union semun" >&5
+echo "configure:96441: checking for union semun" >&5
 if eval "test \"`echo '$''{'php_cv_semun'+set}'`\" = set"; then
   echo $ac_n "(cached) $ac_c" 1>&6
 else
   cat > conftest.$ac_ext <<EOF
-#line 96207 "configure"
+#line 96446 "configure"
 #include "confdefs.h"
 
 #include <sys/types.h>
@@ -96214,7 +96448,7 @@ int main() {
 union semun x;
 ; return 0; }
 EOF
-if { (eval echo configure:96218: \"$ac_compile\") 1>&5; (eval $ac_compile) 2>&5; }; then
+if { (eval echo configure:96457: \"$ac_compile\") 1>&5; (eval $ac_compile) 2>&5; }; then
   rm -rf conftest*
   
      php_cv_semun=yes
@@ -96250,7 +96484,7 @@ fi
 php_enable_sysvshm=no
 
 echo $ac_n "checking whether to enable System V shared memory support""... $ac_c" 1>&6
-echo "configure:96254: checking whether to enable System V shared memory support" >&5
+echo "configure:96493: checking whether to enable System V shared memory support" >&5
 # Check whether --enable-sysvshm or --disable-sysvshm was given.
 if test "${enable_sysvshm+set}" = set; then
   enableval="$enable_sysvshm"
@@ -96554,7 +96788,7 @@ EOF
       ac_obj=$1
       IFS=$old_IFS
       
-      PHP_CLI_OBJS="$PHP_CLI_OBJS $ac_bdir$ac_obj.lo"
+      PHP_GLOBAL_OBJS="$PHP_GLOBAL_OBJS $ac_bdir$ac_obj.lo"
 
       case $ac_src in
         *.c) ac_comp="$b_c_pre $ac_extra $ac_inc $b_c_meta -c $ac_srcdir$ac_src -o $ac_bdir$ac_obj.$b_lo $b_c_post" ;;
@@ -96594,7 +96828,7 @@ fi
 php_with_tidy=no
 
 echo $ac_n "checking for TIDY support""... $ac_c" 1>&6
-echo "configure:96598: checking for TIDY support" >&5
+echo "configure:96837: checking for TIDY support" >&5
 # Check whether --with-tidy or --without-tidy was given.
 if test "${with_tidy+set}" = set; then
   withval="$with_tidy"
@@ -96883,7 +97117,7 @@ if test "$PHP_TIDY" != "no"; then
   done
 
   echo $ac_n "checking for tidyOptGetDoc in -ltidy""... $ac_c" 1>&6
-echo "configure:96887: checking for tidyOptGetDoc in -ltidy" >&5
+echo "configure:97126: checking for tidyOptGetDoc in -ltidy" >&5
 ac_lib_var=`echo tidy'_'tidyOptGetDoc | sed 'y%./+-%__p_%'`
 if eval "test \"`echo '$''{'ac_cv_lib_$ac_lib_var'+set}'`\" = set"; then
   echo $ac_n "(cached) $ac_c" 1>&6
@@ -96891,7 +97125,7 @@ else
   ac_save_LIBS="$LIBS"
 LIBS="-ltidy  $LIBS"
 cat > conftest.$ac_ext <<EOF
-#line 96895 "configure"
+#line 97134 "configure"
 #include "confdefs.h"
 /* Override any gcc2 internal prototype to avoid an error.  */
 /* We use char because int might match the return type of a gcc2
@@ -96902,7 +97136,7 @@ int main() {
 tidyOptGetDoc()
 ; return 0; }
 EOF
-if { (eval echo configure:96906: \"$ac_link\") 1>&5; (eval $ac_link) 2>&5; } && test -s conftest${ac_exeext}; then
+if { (eval echo configure:97145: \"$ac_link\") 1>&5; (eval $ac_link) 2>&5; } && test -s conftest${ac_exeext}; then
   rm -rf conftest*
   eval "ac_cv_lib_$ac_lib_var=yes"
 else
@@ -97198,7 +97432,7 @@ EOF
       ac_obj=$1
       IFS=$old_IFS
       
-      PHP_CLI_OBJS="$PHP_CLI_OBJS $ac_bdir$ac_obj.lo"
+      PHP_GLOBAL_OBJS="$PHP_GLOBAL_OBJS $ac_bdir$ac_obj.lo"
 
       case $ac_src in
         *.c) ac_comp="$b_c_pre $ac_extra $ac_inc $b_c_meta -c $ac_srcdir$ac_src -o $ac_bdir$ac_obj.$b_lo $b_c_post" ;;
@@ -97246,7 +97480,7 @@ fi
 php_enable_tokenizer=yes
 
 echo $ac_n "checking whether to enable tokenizer support""... $ac_c" 1>&6
-echo "configure:97250: checking whether to enable tokenizer support" >&5
+echo "configure:97489: checking whether to enable tokenizer support" >&5
 # Check whether --enable-tokenizer or --disable-tokenizer was given.
 if test "${enable_tokenizer+set}" = set; then
   enableval="$enable_tokenizer"
@@ -97546,7 +97780,7 @@ EOF
       ac_obj=$1
       IFS=$old_IFS
       
-      PHP_CLI_OBJS="$PHP_CLI_OBJS $ac_bdir$ac_obj.lo"
+      PHP_GLOBAL_OBJS="$PHP_GLOBAL_OBJS $ac_bdir$ac_obj.lo"
 
       case $ac_src in
         *.c) ac_comp="$b_c_pre $ac_extra $ac_inc $b_c_meta -c $ac_srcdir$ac_src -o $ac_bdir$ac_obj.$b_lo $b_c_post" ;;
@@ -97592,7 +97826,7 @@ fi
 php_enable_wddx=no
 
 echo $ac_n "checking whether to enable WDDX support""... $ac_c" 1>&6
-echo "configure:97596: checking whether to enable WDDX support" >&5
+echo "configure:97835: checking whether to enable WDDX support" >&5
 # Check whether --enable-wddx or --disable-wddx was given.
 if test "${enable_wddx+set}" = set; then
   enableval="$enable_wddx"
@@ -97637,7 +97871,7 @@ if test -z "$PHP_LIBXML_DIR"; then
 php_with_libxml_dir=no
 
 echo $ac_n "checking libxml2 install dir""... $ac_c" 1>&6
-echo "configure:97641: checking libxml2 install dir" >&5
+echo "configure:97880: checking libxml2 install dir" >&5
 # Check whether --with-libxml-dir or --without-libxml-dir was given.
 if test "${with_libxml_dir+set}" = set; then
   withval="$with_libxml_dir"
@@ -97661,7 +97895,7 @@ fi
 php_with_libexpat_dir=no
 
 echo $ac_n "checking libexpat dir for WDDX""... $ac_c" 1>&6
-echo "configure:97665: checking libexpat dir for WDDX" >&5
+echo "configure:97904: checking libexpat dir for WDDX" >&5
 # Check whether --with-libexpat-dir or --without-libexpat-dir was given.
 if test "${with_libexpat_dir+set}" = set; then
   withval="$with_libexpat_dir"
@@ -97689,7 +97923,7 @@ if test "$PHP_WDDX" != "no"; then
 
     
 echo $ac_n "checking for xml2-config path""... $ac_c" 1>&6
-echo "configure:97693: checking for xml2-config path" >&5
+echo "configure:97932: checking for xml2-config path" >&5
 if eval "test \"`echo '$''{'ac_cv_php_xml2_config_path'+set}'`\" = set"; then
   echo $ac_n "(cached) $ac_c" 1>&6
 else
@@ -97847,7 +98081,7 @@ echo "$ac_t""$ac_cv_php_xml2_config_path" 1>&6
 
 
             echo $ac_n "checking whether libxml build works""... $ac_c" 1>&6
-echo "configure:97851: checking whether libxml build works" >&5
+echo "configure:98090: checking whether libxml build works" >&5
 if eval "test \"`echo '$''{'php_cv_libxml_build_works'+set}'`\" = set"; then
   echo $ac_n "(cached) $ac_c" 1>&6
 else
@@ -97863,7 +98097,7 @@ else
   
 else
   cat > conftest.$ac_ext <<EOF
-#line 97867 "configure"
+#line 98106 "configure"
 #include "confdefs.h"
 
     
@@ -97874,7 +98108,7 @@ else
     }
   
 EOF
-if { (eval echo configure:97878: \"$ac_link\") 1>&5; (eval $ac_link) 2>&5; } && test -s conftest${ac_exeext} && (./conftest; exit) 2>/dev/null
+if { (eval echo configure:98117: \"$ac_link\") 1>&5; (eval $ac_link) 2>&5; } && test -s conftest${ac_exeext} && (./conftest; exit) 2>/dev/null
 then
   
     LIBS=$old_LIBS
@@ -98379,7 +98613,7 @@ EOF
       ac_obj=$1
       IFS=$old_IFS
       
-      PHP_CLI_OBJS="$PHP_CLI_OBJS $ac_bdir$ac_obj.lo"
+      PHP_GLOBAL_OBJS="$PHP_GLOBAL_OBJS $ac_bdir$ac_obj.lo"
 
       case $ac_src in
         *.c) ac_comp="$b_c_pre $ac_extra $ac_inc $b_c_meta -c $ac_srcdir$ac_src -o $ac_bdir$ac_obj.$b_lo $b_c_post" ;;
@@ -98441,7 +98675,7 @@ fi
 php_enable_xml=yes
 
 echo $ac_n "checking whether to enable XML support""... $ac_c" 1>&6
-echo "configure:98445: checking whether to enable XML support" >&5
+echo "configure:98684: checking whether to enable XML support" >&5
 # Check whether --enable-xml or --disable-xml was given.
 if test "${enable_xml+set}" = set; then
   enableval="$enable_xml"
@@ -98486,7 +98720,7 @@ if test -z "$PHP_LIBXML_DIR"; then
 php_with_libxml_dir=no
 
 echo $ac_n "checking libxml2 install dir""... $ac_c" 1>&6
-echo "configure:98490: checking libxml2 install dir" >&5
+echo "configure:98729: checking libxml2 install dir" >&5
 # Check whether --with-libxml-dir or --without-libxml-dir was given.
 if test "${with_libxml_dir+set}" = set; then
   withval="$with_libxml_dir"
@@ -98510,7 +98744,7 @@ fi
 php_with_libexpat_dir=no
 
 echo $ac_n "checking libexpat install dir""... $ac_c" 1>&6
-echo "configure:98514: checking libexpat install dir" >&5
+echo "configure:98753: checking libexpat install dir" >&5
 # Check whether --with-libexpat-dir or --without-libexpat-dir was given.
 if test "${with_libexpat_dir+set}" = set; then
   withval="$with_libexpat_dir"
@@ -98539,7 +98773,7 @@ if test "$PHP_XML" != "no"; then
 
     
 echo $ac_n "checking for xml2-config path""... $ac_c" 1>&6
-echo "configure:98543: checking for xml2-config path" >&5
+echo "configure:98782: checking for xml2-config path" >&5
 if eval "test \"`echo '$''{'ac_cv_php_xml2_config_path'+set}'`\" = set"; then
   echo $ac_n "(cached) $ac_c" 1>&6
 else
@@ -98697,7 +98931,7 @@ echo "$ac_t""$ac_cv_php_xml2_config_path" 1>&6
 
 
             echo $ac_n "checking whether libxml build works""... $ac_c" 1>&6
-echo "configure:98701: checking whether libxml build works" >&5
+echo "configure:98940: checking whether libxml build works" >&5
 if eval "test \"`echo '$''{'php_cv_libxml_build_works'+set}'`\" = set"; then
   echo $ac_n "(cached) $ac_c" 1>&6
 else
@@ -98713,7 +98947,7 @@ else
   
 else
   cat > conftest.$ac_ext <<EOF
-#line 98717 "configure"
+#line 98956 "configure"
 #include "confdefs.h"
 
     
@@ -98724,7 +98958,7 @@ else
     }
   
 EOF
-if { (eval echo configure:98728: \"$ac_link\") 1>&5; (eval $ac_link) 2>&5; } && test -s conftest${ac_exeext} && (./conftest; exit) 2>/dev/null
+if { (eval echo configure:98967: \"$ac_link\") 1>&5; (eval $ac_link) 2>&5; } && test -s conftest${ac_exeext} && (./conftest; exit) 2>/dev/null
 then
   
     LIBS=$old_LIBS
@@ -99195,7 +99429,7 @@ EOF
       ac_obj=$1
       IFS=$old_IFS
       
-      PHP_CLI_OBJS="$PHP_CLI_OBJS $ac_bdir$ac_obj.lo"
+      PHP_GLOBAL_OBJS="$PHP_GLOBAL_OBJS $ac_bdir$ac_obj.lo"
 
       case $ac_src in
         *.c) ac_comp="$b_c_pre $ac_extra $ac_inc $b_c_meta -c $ac_srcdir$ac_src -o $ac_bdir$ac_obj.$b_lo $b_c_post" ;;
@@ -99260,7 +99494,7 @@ fi
 php_enable_xmlreader=yes
 
 echo $ac_n "checking whether to enable XMLReader support""... $ac_c" 1>&6
-echo "configure:99264: checking whether to enable XMLReader support" >&5
+echo "configure:99503: checking whether to enable XMLReader support" >&5
 # Check whether --enable-xmlreader or --disable-xmlreader was given.
 if test "${enable_xmlreader+set}" = set; then
   enableval="$enable_xmlreader"
@@ -99305,7 +99539,7 @@ if test -z "$PHP_LIBXML_DIR"; then
 php_with_libxml_dir=no
 
 echo $ac_n "checking libxml2 install dir""... $ac_c" 1>&6
-echo "configure:99309: checking libxml2 install dir" >&5
+echo "configure:99548: checking libxml2 install dir" >&5
 # Check whether --with-libxml-dir or --without-libxml-dir was given.
 if test "${with_libxml_dir+set}" = set; then
   withval="$with_libxml_dir"
@@ -99333,7 +99567,7 @@ if test "$PHP_XMLREADER" != "no"; then
 
   
 echo $ac_n "checking for xml2-config path""... $ac_c" 1>&6
-echo "configure:99337: checking for xml2-config path" >&5
+echo "configure:99576: checking for xml2-config path" >&5
 if eval "test \"`echo '$''{'ac_cv_php_xml2_config_path'+set}'`\" = set"; then
   echo $ac_n "(cached) $ac_c" 1>&6
 else
@@ -99491,7 +99725,7 @@ echo "$ac_t""$ac_cv_php_xml2_config_path" 1>&6
 
 
             echo $ac_n "checking whether libxml build works""... $ac_c" 1>&6
-echo "configure:99495: checking whether libxml build works" >&5
+echo "configure:99734: checking whether libxml build works" >&5
 if eval "test \"`echo '$''{'php_cv_libxml_build_works'+set}'`\" = set"; then
   echo $ac_n "(cached) $ac_c" 1>&6
 else
@@ -99507,7 +99741,7 @@ else
   
 else
   cat > conftest.$ac_ext <<EOF
-#line 99511 "configure"
+#line 99750 "configure"
 #include "confdefs.h"
 
     
@@ -99518,7 +99752,7 @@ else
     }
   
 EOF
-if { (eval echo configure:99522: \"$ac_link\") 1>&5; (eval $ac_link) 2>&5; } && test -s conftest${ac_exeext} && (./conftest; exit) 2>/dev/null
+if { (eval echo configure:99761: \"$ac_link\") 1>&5; (eval $ac_link) 2>&5; } && test -s conftest${ac_exeext} && (./conftest; exit) 2>/dev/null
 then
   
     LIBS=$old_LIBS
@@ -99816,7 +100050,7 @@ EOF
       ac_obj=$1
       IFS=$old_IFS
       
-      PHP_CLI_OBJS="$PHP_CLI_OBJS $ac_bdir$ac_obj.lo"
+      PHP_GLOBAL_OBJS="$PHP_GLOBAL_OBJS $ac_bdir$ac_obj.lo"
 
       case $ac_src in
         *.c) ac_comp="$b_c_pre $ac_extra $ac_inc $b_c_meta -c $ac_srcdir$ac_src -o $ac_bdir$ac_obj.$b_lo $b_c_post" ;;
@@ -99900,7 +100134,7 @@ fi
 php_with_xmlrpc=no
 
 echo $ac_n "checking for XMLRPC-EPI support""... $ac_c" 1>&6
-echo "configure:99904: checking for XMLRPC-EPI support" >&5
+echo "configure:100143: checking for XMLRPC-EPI support" >&5
 # Check whether --with-xmlrpc or --without-xmlrpc was given.
 if test "${with_xmlrpc+set}" = set; then
   withval="$with_xmlrpc"
@@ -99945,7 +100179,7 @@ if test -z "$PHP_LIBXML_DIR"; then
 php_with_libxml_dir=no
 
 echo $ac_n "checking libxml2 install dir""... $ac_c" 1>&6
-echo "configure:99949: checking libxml2 install dir" >&5
+echo "configure:100188: checking libxml2 install dir" >&5
 # Check whether --with-libxml-dir or --without-libxml-dir was given.
 if test "${with_libxml_dir+set}" = set; then
   withval="$with_libxml_dir"
@@ -99969,7 +100203,7 @@ fi
 php_with_libexpat_dir=no
 
 echo $ac_n "checking libexpat dir for XMLRPC-EPI""... $ac_c" 1>&6
-echo "configure:99973: checking libexpat dir for XMLRPC-EPI" >&5
+echo "configure:100212: checking libexpat dir for XMLRPC-EPI" >&5
 # Check whether --with-libexpat-dir or --without-libexpat-dir was given.
 if test "${with_libexpat_dir+set}" = set; then
   withval="$with_libexpat_dir"
@@ -99992,7 +100226,7 @@ echo "$ac_t""$ext_output" 1>&6
 php_with_iconv_dir=no
 
 echo $ac_n "checking iconv dir for XMLRPC-EPI""... $ac_c" 1>&6
-echo "configure:99996: checking iconv dir for XMLRPC-EPI" >&5
+echo "configure:100235: checking iconv dir for XMLRPC-EPI" >&5
 # Check whether --with-iconv-dir or --without-iconv-dir was given.
 if test "${with_iconv_dir+set}" = set; then
   withval="$with_iconv_dir"
@@ -100048,7 +100282,7 @@ EOF
 
     
 echo $ac_n "checking for xml2-config path""... $ac_c" 1>&6
-echo "configure:100052: checking for xml2-config path" >&5
+echo "configure:100291: checking for xml2-config path" >&5
 if eval "test \"`echo '$''{'ac_cv_php_xml2_config_path'+set}'`\" = set"; then
   echo $ac_n "(cached) $ac_c" 1>&6
 else
@@ -100206,7 +100440,7 @@ echo "$ac_t""$ac_cv_php_xml2_config_path" 1>&6
 
 
             echo $ac_n "checking whether libxml build works""... $ac_c" 1>&6
-echo "configure:100210: checking whether libxml build works" >&5
+echo "configure:100449: checking whether libxml build works" >&5
 if eval "test \"`echo '$''{'php_cv_libxml_build_works'+set}'`\" = set"; then
   echo $ac_n "(cached) $ac_c" 1>&6
 else
@@ -100222,7 +100456,7 @@ else
   
 else
   cat > conftest.$ac_ext <<EOF
-#line 100226 "configure"
+#line 100465 "configure"
 #include "confdefs.h"
 
     
@@ -100233,7 +100467,7 @@ else
     }
   
 EOF
-if { (eval echo configure:100237: \"$ac_link\") 1>&5; (eval $ac_link) 2>&5; } && test -s conftest${ac_exeext} && (./conftest; exit) 2>/dev/null
+if { (eval echo configure:100476: \"$ac_link\") 1>&5; (eval $ac_link) 2>&5; } && test -s conftest${ac_exeext} && (./conftest; exit) 2>/dev/null
 then
   
     LIBS=$old_LIBS
@@ -100505,12 +100739,12 @@ EOF
             LIBS_save="$LIBS"
     LIBS=
     echo $ac_n "checking for iconv""... $ac_c" 1>&6
-echo "configure:100509: checking for iconv" >&5
+echo "configure:100748: checking for iconv" >&5
 if eval "test \"`echo '$''{'ac_cv_func_iconv'+set}'`\" = set"; then
   echo $ac_n "(cached) $ac_c" 1>&6
 else
   cat > conftest.$ac_ext <<EOF
-#line 100514 "configure"
+#line 100753 "configure"
 #include "confdefs.h"
 /* System header to define __stub macros and hopefully few prototypes,
     which can conflict with char iconv(); below.  */
@@ -100533,7 +100767,7 @@ iconv();
 
 ; return 0; }
 EOF
-if { (eval echo configure:100537: \"$ac_link\") 1>&5; (eval $ac_link) 2>&5; } && test -s conftest${ac_exeext}; then
+if { (eval echo configure:100776: \"$ac_link\") 1>&5; (eval $ac_link) 2>&5; } && test -s conftest${ac_exeext}; then
   rm -rf conftest*
   eval "ac_cv_func_iconv=yes"
 else
@@ -100554,12 +100788,12 @@ else
   echo "$ac_t""no" 1>&6
 
       echo $ac_n "checking for libiconv""... $ac_c" 1>&6
-echo "configure:100558: checking for libiconv" >&5
+echo "configure:100797: checking for libiconv" >&5
 if eval "test \"`echo '$''{'ac_cv_func_libiconv'+set}'`\" = set"; then
   echo $ac_n "(cached) $ac_c" 1>&6
 else
   cat > conftest.$ac_ext <<EOF
-#line 100563 "configure"
+#line 100802 "configure"
 #include "confdefs.h"
 /* System header to define __stub macros and hopefully few prototypes,
     which can conflict with char libiconv(); below.  */
@@ -100582,7 +100816,7 @@ libiconv();
 
 ; return 0; }
 EOF
-if { (eval echo configure:100586: \"$ac_link\") 1>&5; (eval $ac_link) 2>&5; } && test -s conftest${ac_exeext}; then
+if { (eval echo configure:100825: \"$ac_link\") 1>&5; (eval $ac_link) 2>&5; } && test -s conftest${ac_exeext}; then
   rm -rf conftest*
   eval "ac_cv_func_libiconv=yes"
 else
@@ -100739,7 +100973,7 @@ EOF
   done
 
   echo $ac_n "checking for libiconv in -l$iconv_lib_name""... $ac_c" 1>&6
-echo "configure:100743: checking for libiconv in -l$iconv_lib_name" >&5
+echo "configure:100982: checking for libiconv in -l$iconv_lib_name" >&5
 ac_lib_var=`echo $iconv_lib_name'_'libiconv | sed 'y%./+-%__p_%'`
 if eval "test \"`echo '$''{'ac_cv_lib_$ac_lib_var'+set}'`\" = set"; then
   echo $ac_n "(cached) $ac_c" 1>&6
@@ -100747,7 +100981,7 @@ else
   ac_save_LIBS="$LIBS"
 LIBS="-l$iconv_lib_name  $LIBS"
 cat > conftest.$ac_ext <<EOF
-#line 100751 "configure"
+#line 100990 "configure"
 #include "confdefs.h"
 /* Override any gcc2 internal prototype to avoid an error.  */
 /* We use char because int might match the return type of a gcc2
@@ -100758,7 +100992,7 @@ int main() {
 libiconv()
 ; return 0; }
 EOF
-if { (eval echo configure:100762: \"$ac_link\") 1>&5; (eval $ac_link) 2>&5; } && test -s conftest${ac_exeext}; then
+if { (eval echo configure:101001: \"$ac_link\") 1>&5; (eval $ac_link) 2>&5; } && test -s conftest${ac_exeext}; then
   rm -rf conftest*
   eval "ac_cv_lib_$ac_lib_var=yes"
 else
@@ -100899,7 +101133,7 @@ else
   done
 
   echo $ac_n "checking for iconv in -l$iconv_lib_name""... $ac_c" 1>&6
-echo "configure:100903: checking for iconv in -l$iconv_lib_name" >&5
+echo "configure:101142: checking for iconv in -l$iconv_lib_name" >&5
 ac_lib_var=`echo $iconv_lib_name'_'iconv | sed 'y%./+-%__p_%'`
 if eval "test \"`echo '$''{'ac_cv_lib_$ac_lib_var'+set}'`\" = set"; then
   echo $ac_n "(cached) $ac_c" 1>&6
@@ -100907,7 +101141,7 @@ else
   ac_save_LIBS="$LIBS"
 LIBS="-l$iconv_lib_name  $LIBS"
 cat > conftest.$ac_ext <<EOF
-#line 100911 "configure"
+#line 101150 "configure"
 #include "confdefs.h"
 /* Override any gcc2 internal prototype to avoid an error.  */
 /* We use char because int might match the return type of a gcc2
@@ -100918,7 +101152,7 @@ int main() {
 iconv()
 ; return 0; }
 EOF
-if { (eval echo configure:100922: \"$ac_link\") 1>&5; (eval $ac_link) 2>&5; } && test -s conftest${ac_exeext}; then
+if { (eval echo configure:101161: \"$ac_link\") 1>&5; (eval $ac_link) 2>&5; } && test -s conftest${ac_exeext}; then
   rm -rf conftest*
   eval "ac_cv_lib_$ac_lib_var=yes"
 else
@@ -101108,7 +101342,7 @@ if test "$PHP_XMLRPC" = "yes"; then
   # Extract the first word of "ranlib", so it can be a program name with args.
 set dummy ranlib; ac_word=$2
 echo $ac_n "checking for $ac_word""... $ac_c" 1>&6
-echo "configure:101112: checking for $ac_word" >&5
+echo "configure:101351: checking for $ac_word" >&5
 if eval "test \"`echo '$''{'ac_cv_prog_RANLIB'+set}'`\" = set"; then
   echo $ac_n "(cached) $ac_c" 1>&6
 else
@@ -101136,21 +101370,21 @@ else
 fi
 
 echo $ac_n "checking for inline""... $ac_c" 1>&6
-echo "configure:101140: checking for inline" >&5
+echo "configure:101379: checking for inline" >&5
 if eval "test \"`echo '$''{'ac_cv_c_inline'+set}'`\" = set"; then
   echo $ac_n "(cached) $ac_c" 1>&6
 else
   ac_cv_c_inline=no
 for ac_kw in inline __inline__ __inline; do
   cat > conftest.$ac_ext <<EOF
-#line 101147 "configure"
+#line 101386 "configure"
 #include "confdefs.h"
 
 int main() {
 } $ac_kw foo() {
 ; return 0; }
 EOF
-if { (eval echo configure:101154: \"$ac_compile\") 1>&5; (eval $ac_compile) 2>&5; }; then
+if { (eval echo configure:101393: \"$ac_compile\") 1>&5; (eval $ac_compile) 2>&5; }; then
   rm -rf conftest*
   ac_cv_c_inline=$ac_kw; break
 else
@@ -101188,12 +101422,12 @@ EOF
 
 
 echo $ac_n "checking for ANSI C header files""... $ac_c" 1>&6
-echo "configure:101192: checking for ANSI C header files" >&5
+echo "configure:101431: checking for ANSI C header files" >&5
 if eval "test \"`echo '$''{'ac_cv_header_stdc'+set}'`\" = set"; then
   echo $ac_n "(cached) $ac_c" 1>&6
 else
   cat > conftest.$ac_ext <<EOF
-#line 101197 "configure"
+#line 101436 "configure"
 #include "confdefs.h"
 #include <stdlib.h>
 #include <stdarg.h>
@@ -101201,7 +101435,7 @@ else
 #include <float.h>
 EOF
 ac_try="$ac_cpp conftest.$ac_ext >/dev/null 2>conftest.out"
-{ (eval echo configure:101205: \"$ac_try\") 1>&5; (eval $ac_try) 2>&5; }
+{ (eval echo configure:101444: \"$ac_try\") 1>&5; (eval $ac_try) 2>&5; }
 ac_err=`grep -v '^ *+' conftest.out | grep -v "^conftest.${ac_ext}\$"`
 if test -z "$ac_err"; then
   rm -rf conftest*
@@ -101218,7 +101452,7 @@ rm -f conftest*
 if test $ac_cv_header_stdc = yes; then
   # SunOS 4.x string.h does not declare mem*, contrary to ANSI.
 cat > conftest.$ac_ext <<EOF
-#line 101222 "configure"
+#line 101461 "configure"
 #include "confdefs.h"
 #include <string.h>
 EOF
@@ -101236,7 +101470,7 @@ fi
 if test $ac_cv_header_stdc = yes; then
   # ISC 2.0.2 stdlib.h does not declare free, contrary to ANSI.
 cat > conftest.$ac_ext <<EOF
-#line 101240 "configure"
+#line 101479 "configure"
 #include "confdefs.h"
 #include <stdlib.h>
 EOF
@@ -101257,7 +101491,7 @@ if test "$cross_compiling" = yes; then
   :
 else
   cat > conftest.$ac_ext <<EOF
-#line 101261 "configure"
+#line 101500 "configure"
 #include "confdefs.h"
 #include <ctype.h>
 #define ISLOWER(c) ('a' <= (c) && (c) <= 'z')
@@ -101268,7 +101502,7 @@ if (XOR (islower (i), ISLOWER (i)) || toupper (i) != TOUPPER (i)) exit(2);
 exit (0); }
 
 EOF
-if { (eval echo configure:101272: \"$ac_link\") 1>&5; (eval $ac_link) 2>&5; } && test -s conftest${ac_exeext} && (./conftest; exit) 2>/dev/null
+if { (eval echo configure:101511: \"$ac_link\") 1>&5; (eval $ac_link) 2>&5; } && test -s conftest${ac_exeext} && (./conftest; exit) 2>/dev/null
 then
   :
 else
@@ -101295,17 +101529,17 @@ for ac_hdr in xmlparse.h xmltok.h stdlib.h strings.h string.h
 do
 ac_safe=`echo "$ac_hdr" | sed 'y%./+-%__p_%'`
 echo $ac_n "checking for $ac_hdr""... $ac_c" 1>&6
-echo "configure:101299: checking for $ac_hdr" >&5
+echo "configure:101538: checking for $ac_hdr" >&5
 if eval "test \"`echo '$''{'ac_cv_header_$ac_safe'+set}'`\" = set"; then
   echo $ac_n "(cached) $ac_c" 1>&6
 else
   cat > conftest.$ac_ext <<EOF
-#line 101304 "configure"
+#line 101543 "configure"
 #include "confdefs.h"
 #include <$ac_hdr>
 EOF
 ac_try="$ac_cpp conftest.$ac_ext >/dev/null 2>conftest.out"
-{ (eval echo configure:101309: \"$ac_try\") 1>&5; (eval $ac_try) 2>&5; }
+{ (eval echo configure:101548: \"$ac_try\") 1>&5; (eval $ac_try) 2>&5; }
 ac_err=`grep -v '^ *+' conftest.out | grep -v "^conftest.${ac_ext}\$"`
 if test -z "$ac_err"; then
   rm -rf conftest*
@@ -101337,7 +101571,7 @@ done
 
 
 echo $ac_n "checking size of char""... $ac_c" 1>&6
-echo "configure:101341: checking size of char" >&5
+echo "configure:101580: checking size of char" >&5
 if eval "test \"`echo '$''{'ac_cv_sizeof_char'+set}'`\" = set"; then
   echo $ac_n "(cached) $ac_c" 1>&6
 else
@@ -101345,7 +101579,7 @@ else
   ac_cv_sizeof_char=1
 else
   cat > conftest.$ac_ext <<EOF
-#line 101349 "configure"
+#line 101588 "configure"
 #include "confdefs.h"
 #include <stdio.h>
 main()
@@ -101356,7 +101590,7 @@ main()
   exit(0);
 }
 EOF
-if { (eval echo configure:101360: \"$ac_link\") 1>&5; (eval $ac_link) 2>&5; } && test -s conftest${ac_exeext} && (./conftest; exit) 2>/dev/null
+if { (eval echo configure:101599: \"$ac_link\") 1>&5; (eval $ac_link) 2>&5; } && test -s conftest${ac_exeext} && (./conftest; exit) 2>/dev/null
 then
   ac_cv_sizeof_char=`cat conftestval`
 else
@@ -101377,7 +101611,7 @@ EOF
 
 
 echo $ac_n "checking size of int""... $ac_c" 1>&6
-echo "configure:101381: checking size of int" >&5
+echo "configure:101620: checking size of int" >&5
 if eval "test \"`echo '$''{'ac_cv_sizeof_int'+set}'`\" = set"; then
   echo $ac_n "(cached) $ac_c" 1>&6
 else
@@ -101385,7 +101619,7 @@ else
   ac_cv_sizeof_int=4
 else
   cat > conftest.$ac_ext <<EOF
-#line 101389 "configure"
+#line 101628 "configure"
 #include "confdefs.h"
 #include <stdio.h>
 main()
@@ -101396,7 +101630,7 @@ main()
   exit(0);
 }
 EOF
-if { (eval echo configure:101400: \"$ac_link\") 1>&5; (eval $ac_link) 2>&5; } && test -s conftest${ac_exeext} && (./conftest; exit) 2>/dev/null
+if { (eval echo configure:101639: \"$ac_link\") 1>&5; (eval $ac_link) 2>&5; } && test -s conftest${ac_exeext} && (./conftest; exit) 2>/dev/null
 then
   ac_cv_sizeof_int=`cat conftestval`
 else
@@ -101416,7 +101650,7 @@ EOF
 
 
 echo $ac_n "checking size of long""... $ac_c" 1>&6
-echo "configure:101420: checking size of long" >&5
+echo "configure:101659: checking size of long" >&5
 if eval "test \"`echo '$''{'ac_cv_sizeof_long'+set}'`\" = set"; then
   echo $ac_n "(cached) $ac_c" 1>&6
 else
@@ -101424,7 +101658,7 @@ else
   ac_cv_sizeof_long=4
 else
   cat > conftest.$ac_ext <<EOF
-#line 101428 "configure"
+#line 101667 "configure"
 #include "confdefs.h"
 #include <stdio.h>
 main()
@@ -101435,7 +101669,7 @@ main()
   exit(0);
 }
 EOF
-if { (eval echo configure:101439: \"$ac_link\") 1>&5; (eval $ac_link) 2>&5; } && test -s conftest${ac_exeext} && (./conftest; exit) 2>/dev/null
+if { (eval echo configure:101678: \"$ac_link\") 1>&5; (eval $ac_link) 2>&5; } && test -s conftest${ac_exeext} && (./conftest; exit) 2>/dev/null
 then
   ac_cv_sizeof_long=`cat conftestval`
 else
@@ -101455,7 +101689,7 @@ EOF
 
 
 echo $ac_n "checking size of long long""... $ac_c" 1>&6
-echo "configure:101459: checking size of long long" >&5
+echo "configure:101698: checking size of long long" >&5
 if eval "test \"`echo '$''{'ac_cv_sizeof_long_long'+set}'`\" = set"; then
   echo $ac_n "(cached) $ac_c" 1>&6
 else
@@ -101463,7 +101697,7 @@ else
   ac_cv_sizeof_long_long=8
 else
   cat > conftest.$ac_ext <<EOF
-#line 101467 "configure"
+#line 101706 "configure"
 #include "confdefs.h"
 #include <stdio.h>
 main()
@@ -101474,7 +101708,7 @@ main()
   exit(0);
 }
 EOF
-if { (eval echo configure:101478: \"$ac_link\") 1>&5; (eval $ac_link) 2>&5; } && test -s conftest${ac_exeext} && (./conftest; exit) 2>/dev/null
+if { (eval echo configure:101717: \"$ac_link\") 1>&5; (eval $ac_link) 2>&5; } && test -s conftest${ac_exeext} && (./conftest; exit) 2>/dev/null
 then
   ac_cv_sizeof_long_long=`cat conftestval`
 else
@@ -101494,12 +101728,12 @@ EOF
 
 
 echo $ac_n "checking for size_t""... $ac_c" 1>&6
-echo "configure:101498: checking for size_t" >&5
+echo "configure:101737: checking for size_t" >&5
 if eval "test \"`echo '$''{'ac_cv_type_size_t'+set}'`\" = set"; then
   echo $ac_n "(cached) $ac_c" 1>&6
 else
   cat > conftest.$ac_ext <<EOF
-#line 101503 "configure"
+#line 101742 "configure"
 #include "confdefs.h"
 #include <sys/types.h>
 #if STDC_HEADERS
@@ -101527,12 +101761,12 @@ EOF
 fi
 
 echo $ac_n "checking whether time.h and sys/time.h may both be included""... $ac_c" 1>&6
-echo "configure:101531: checking whether time.h and sys/time.h may both be included" >&5
+echo "configure:101770: checking whether time.h and sys/time.h may both be included" >&5
 if eval "test \"`echo '$''{'ac_cv_header_time'+set}'`\" = set"; then
   echo $ac_n "(cached) $ac_c" 1>&6
 else
   cat > conftest.$ac_ext <<EOF
-#line 101536 "configure"
+#line 101775 "configure"
 #include "confdefs.h"
 #include <sys/types.h>
 #include <sys/time.h>
@@ -101541,7 +101775,7 @@ int main() {
 struct tm *tp;
 ; return 0; }
 EOF
-if { (eval echo configure:101545: \"$ac_compile\") 1>&5; (eval $ac_compile) 2>&5; }; then
+if { (eval echo configure:101784: \"$ac_compile\") 1>&5; (eval $ac_compile) 2>&5; }; then
   rm -rf conftest*
   ac_cv_header_time=yes
 else
@@ -101562,12 +101796,12 @@ EOF
 fi
 
 echo $ac_n "checking for uid_t in sys/types.h""... $ac_c" 1>&6
-echo "configure:101566: checking for uid_t in sys/types.h" >&5
+echo "configure:101805: checking for uid_t in sys/types.h" >&5
 if eval "test \"`echo '$''{'ac_cv_type_uid_t'+set}'`\" = set"; then
   echo $ac_n "(cached) $ac_c" 1>&6
 else
   cat > conftest.$ac_ext <<EOF
-#line 101571 "configure"
+#line 101810 "configure"
 #include "confdefs.h"
 #include <sys/types.h>
 EOF
@@ -101607,12 +101841,12 @@ for ac_func in \
  memcpy memmove
 do
 echo $ac_n "checking for $ac_func""... $ac_c" 1>&6
-echo "configure:101611: checking for $ac_func" >&5
+echo "configure:101850: checking for $ac_func" >&5
 if eval "test \"`echo '$''{'ac_cv_func_$ac_func'+set}'`\" = set"; then
   echo $ac_n "(cached) $ac_c" 1>&6
 else
   cat > conftest.$ac_ext <<EOF
-#line 101616 "configure"
+#line 101855 "configure"
 #include "confdefs.h"
 /* System header to define __stub macros and hopefully few prototypes,
     which can conflict with char $ac_func(); below.  */
@@ -101635,7 +101869,7 @@ $ac_func();
 
 ; return 0; }
 EOF
-if { (eval echo configure:101639: \"$ac_link\") 1>&5; (eval $ac_link) 2>&5; } && test -s conftest${ac_exeext}; then
+if { (eval echo configure:101878: \"$ac_link\") 1>&5; (eval $ac_link) 2>&5; } && test -s conftest${ac_exeext}; then
   rm -rf conftest*
   eval "ac_cv_func_$ac_func=yes"
 else
@@ -101941,7 +102175,7 @@ EOF
       ac_obj=$1
       IFS=$old_IFS
       
-      PHP_CLI_OBJS="$PHP_CLI_OBJS $ac_bdir$ac_obj.lo"
+      PHP_GLOBAL_OBJS="$PHP_GLOBAL_OBJS $ac_bdir$ac_obj.lo"
 
       case $ac_src in
         *.c) ac_comp="$b_c_pre $ac_extra $ac_inc $b_c_meta -c $ac_srcdir$ac_src -o $ac_bdir$ac_obj.$b_lo $b_c_post" ;;
@@ -101989,7 +102223,7 @@ elif test "$PHP_XMLRPC" != "no"; then
     XMLRPC_DIR=$PHP_XMLRPC/include/xmlrpc-epi
   else
     echo $ac_n "checking for XMLRPC-EPI in default path""... $ac_c" 1>&6
-echo "configure:101993: checking for XMLRPC-EPI in default path" >&5
+echo "configure:102232: checking for XMLRPC-EPI in default path" >&5
     for i in /usr/local /usr; do
       if test -r $i/include/xmlrpc.h; then
         XMLRPC_DIR=$i/include
@@ -102391,7 +102625,7 @@ EOF
       ac_obj=$1
       IFS=$old_IFS
       
-      PHP_CLI_OBJS="$PHP_CLI_OBJS $ac_bdir$ac_obj.lo"
+      PHP_GLOBAL_OBJS="$PHP_GLOBAL_OBJS $ac_bdir$ac_obj.lo"
 
       case $ac_src in
         *.c) ac_comp="$b_c_pre $ac_extra $ac_inc $b_c_meta -c $ac_srcdir$ac_src -o $ac_bdir$ac_obj.$b_lo $b_c_post" ;;
@@ -102432,7 +102666,7 @@ fi
 php_enable_xmlwriter=yes
 
 echo $ac_n "checking whether to enable XMLWriter support""... $ac_c" 1>&6
-echo "configure:102436: checking whether to enable XMLWriter support" >&5
+echo "configure:102675: checking whether to enable XMLWriter support" >&5
 # Check whether --enable-xmlwriter or --disable-xmlwriter was given.
 if test "${enable_xmlwriter+set}" = set; then
   enableval="$enable_xmlwriter"
@@ -102477,7 +102711,7 @@ if test -z "$PHP_LIBXML_DIR"; then
 php_with_libxml_dir=no
 
 echo $ac_n "checking libxml2 install dir""... $ac_c" 1>&6
-echo "configure:102481: checking libxml2 install dir" >&5
+echo "configure:102720: checking libxml2 install dir" >&5
 # Check whether --with-libxml-dir or --without-libxml-dir was given.
 if test "${with_libxml_dir+set}" = set; then
   withval="$with_libxml_dir"
@@ -102505,7 +102739,7 @@ if test "$PHP_XMLWRITER" != "no"; then
 
   
 echo $ac_n "checking for xml2-config path""... $ac_c" 1>&6
-echo "configure:102509: checking for xml2-config path" >&5
+echo "configure:102748: checking for xml2-config path" >&5
 if eval "test \"`echo '$''{'ac_cv_php_xml2_config_path'+set}'`\" = set"; then
   echo $ac_n "(cached) $ac_c" 1>&6
 else
@@ -102663,7 +102897,7 @@ echo "$ac_t""$ac_cv_php_xml2_config_path" 1>&6
 
 
             echo $ac_n "checking whether libxml build works""... $ac_c" 1>&6
-echo "configure:102667: checking whether libxml build works" >&5
+echo "configure:102906: checking whether libxml build works" >&5
 if eval "test \"`echo '$''{'php_cv_libxml_build_works'+set}'`\" = set"; then
   echo $ac_n "(cached) $ac_c" 1>&6
 else
@@ -102679,7 +102913,7 @@ else
   
 else
   cat > conftest.$ac_ext <<EOF
-#line 102683 "configure"
+#line 102922 "configure"
 #include "confdefs.h"
 
     
@@ -102690,7 +102924,7 @@ else
     }
   
 EOF
-if { (eval echo configure:102694: \"$ac_link\") 1>&5; (eval $ac_link) 2>&5; } && test -s conftest${ac_exeext} && (./conftest; exit) 2>/dev/null
+if { (eval echo configure:102933: \"$ac_link\") 1>&5; (eval $ac_link) 2>&5; } && test -s conftest${ac_exeext} && (./conftest; exit) 2>/dev/null
 then
   
     LIBS=$old_LIBS
@@ -102988,7 +103222,7 @@ EOF
       ac_obj=$1
       IFS=$old_IFS
       
-      PHP_CLI_OBJS="$PHP_CLI_OBJS $ac_bdir$ac_obj.lo"
+      PHP_GLOBAL_OBJS="$PHP_GLOBAL_OBJS $ac_bdir$ac_obj.lo"
 
       case $ac_src in
         *.c) ac_comp="$b_c_pre $ac_extra $ac_inc $b_c_meta -c $ac_srcdir$ac_src -o $ac_bdir$ac_obj.$b_lo $b_c_post" ;;
@@ -103040,7 +103274,7 @@ fi
 php_with_xsl=no
 
 echo $ac_n "checking for XSL support""... $ac_c" 1>&6
-echo "configure:103044: checking for XSL support" >&5
+echo "configure:103283: checking for XSL support" >&5
 # Check whether --with-xsl or --without-xsl was given.
 if test "${with_xsl+set}" = set; then
   withval="$with_xsl"
@@ -103240,7 +103474,7 @@ if test "$PHP_XSL" != "no"; then
 
       
       echo $ac_n "checking for EXSLT support""... $ac_c" 1>&6
-echo "configure:103244: checking for EXSLT support" >&5
+echo "configure:103483: checking for EXSLT support" >&5
       for i in $PHP_XSL /usr/local /usr; do
         if test -r "$i/include/libexslt/exslt.h"; then
           PHP_XSL_EXSL_DIR=$i
@@ -103654,7 +103888,7 @@ EOF
       ac_obj=$1
       IFS=$old_IFS
       
-      PHP_CLI_OBJS="$PHP_CLI_OBJS $ac_bdir$ac_obj.lo"
+      PHP_GLOBAL_OBJS="$PHP_GLOBAL_OBJS $ac_bdir$ac_obj.lo"
 
       case $ac_src in
         *.c) ac_comp="$b_c_pre $ac_extra $ac_inc $b_c_meta -c $ac_srcdir$ac_src -o $ac_bdir$ac_obj.$b_lo $b_c_post" ;;
@@ -103716,7 +103950,7 @@ fi
 php_enable_zip=no
 
 echo $ac_n "checking for zip archive read/writesupport""... $ac_c" 1>&6
-echo "configure:103720: checking for zip archive read/writesupport" >&5
+echo "configure:103959: checking for zip archive read/writesupport" >&5
 # Check whether --enable-zip or --disable-zip was given.
 if test "${enable_zip+set}" = set; then
   enableval="$enable_zip"
@@ -103761,7 +103995,7 @@ if test -z "$PHP_ZLIB_DIR"; then
 php_with_zlib_dir=no
 
 echo $ac_n "checking for the location of libz""... $ac_c" 1>&6
-echo "configure:103765: checking for the location of libz" >&5
+echo "configure:104004: checking for the location of libz" >&5
 # Check whether --with-zlib-dir or --without-zlib-dir was given.
 if test "${with_zlib_dir+set}" = set; then
   withval="$with_zlib_dir"
@@ -103785,7 +104019,7 @@ fi
 php_with_pcre_dir=no
 
 echo $ac_n "checking pcre install prefix""... $ac_c" 1>&6
-echo "configure:103789: checking pcre install prefix" >&5
+echo "configure:104028: checking pcre install prefix" >&5
 # Check whether --with-pcre-dir or --without-pcre-dir was given.
 if test "${with_pcre_dir+set}" = set; then
   withval="$with_pcre_dir"
@@ -103829,7 +104063,7 @@ if test "$PHP_ZIP" != "no"; then
   fi
 
     echo $ac_n "checking for the location of zlib""... $ac_c" 1>&6
-echo "configure:103833: checking for the location of zlib" >&5
+echo "configure:104072: checking for the location of zlib" >&5
   if test "$PHP_ZLIB_DIR" = "no"; then
     { echo "configure: error: zip support requires ZLIB. Use --with-zlib-dir=<DIR> to specify prefix where ZLIB include and library are located" 1>&2; exit 1; }
   else
@@ -103967,7 +104201,7 @@ echo "configure:103833: checking for the location of zlib" >&5
     old_CPPFLAGS=$CPPFLAGS
   CPPFLAGS=$INCLUDES
   cat > conftest.$ac_ext <<EOF
-#line 103971 "configure"
+#line 104210 "configure"
 #include "confdefs.h"
 
 #include <main/php_config.h>
@@ -103986,7 +104220,7 @@ else
   rm -rf conftest*
   
     cat > conftest.$ac_ext <<EOF
-#line 103990 "configure"
+#line 104229 "configure"
 #include "confdefs.h"
 
 #include <main/php_config.h>
@@ -104305,7 +104539,7 @@ EOF
       ac_obj=$1
       IFS=$old_IFS
       
-      PHP_CLI_OBJS="$PHP_CLI_OBJS $ac_bdir$ac_obj.lo"
+      PHP_GLOBAL_OBJS="$PHP_GLOBAL_OBJS $ac_bdir$ac_obj.lo"
 
       case $ac_src in
         *.c) ac_comp="$b_c_pre $ac_extra $ac_inc $b_c_meta -c $ac_srcdir$ac_src -o $ac_bdir$ac_obj.$b_lo $b_c_post" ;;
@@ -104360,7 +104594,7 @@ fi
 php_enable_mysqlnd=no
 
 echo $ac_n "checking whether to enable mysqlnd""... $ac_c" 1>&6
-echo "configure:104364: checking whether to enable mysqlnd" >&5
+echo "configure:104603: checking whether to enable mysqlnd" >&5
 # Check whether --enable-mysqlnd or --disable-mysqlnd was given.
 if test "${enable_mysqlnd+set}" = set; then
   enableval="$enable_mysqlnd"
@@ -104404,7 +104638,7 @@ echo "$ac_t""$ext_output" 1>&6
 php_enable_mysqlnd_compression_support=yes
 
 echo $ac_n "checking whether to disable compressed protocol support in mysqlnd""... $ac_c" 1>&6
-echo "configure:104408: checking whether to disable compressed protocol support in mysqlnd" >&5
+echo "configure:104647: checking whether to disable compressed protocol support in mysqlnd" >&5
 # Check whether --enable-mysqlnd_compression_support or --disable-mysqlnd_compression_support was given.
 if test "${enable_mysqlnd_compression_support+set}" = set; then
   enableval="$enable_mysqlnd_compression_support"
@@ -104428,7 +104662,7 @@ if test -z "$PHP_ZLIB_DIR"; then
 php_with_zlib_dir=no
 
 echo $ac_n "checking for the location of libz""... $ac_c" 1>&6
-echo "configure:104432: checking for the location of libz" >&5
+echo "configure:104671: checking for the location of libz" >&5
 # Check whether --with-zlib-dir or --without-zlib-dir was given.
 if test "${with_zlib_dir+set}" = set; then
   withval="$with_zlib_dir"
@@ -104727,7 +104961,7 @@ EOF
       ac_obj=$1
       IFS=$old_IFS
       
-      PHP_CLI_OBJS="$PHP_CLI_OBJS $ac_bdir$ac_obj.lo"
+      PHP_GLOBAL_OBJS="$PHP_GLOBAL_OBJS $ac_bdir$ac_obj.lo"
 
       case $ac_src in
         *.c) ac_comp="$b_c_pre $ac_extra $ac_inc $b_c_meta -c $ac_srcdir$ac_src -o $ac_bdir$ac_obj.$b_lo $b_c_post" ;;
@@ -104795,7 +105029,7 @@ if test "$PHP_MYSQLND" != "no" || test "$PHP_MYSQLND_ENABLED" = "yes" || test "$
     
   for php_typename in int8 uint8 int16 uint16 int32 uint32 uchar ulong int8_t uint8_t int16_t uint16_t int32_t uint32_t int64_t uint64_t; do
     echo $ac_n "checking whether $php_typename exists""... $ac_c" 1>&6
-echo "configure:104799: checking whether $php_typename exists" >&5
+echo "configure:105038: checking whether $php_typename exists" >&5
     
   php_cache_value=php_cv_sizeof_$php_typename
   if eval "test \"`echo '$''{'php_cv_sizeof_$php_typename'+set}'`\" = set"; then
@@ -104812,7 +105046,7 @@ else
 
 else
   cat > conftest.$ac_ext <<EOF
-#line 104816 "configure"
+#line 105055 "configure"
 #include "confdefs.h"
 #include <stdio.h>
 #if STDC_HEADERS
@@ -104842,7 +105076,7 @@ int main()
 }
   
 EOF
-if { (eval echo configure:104846: \"$ac_link\") 1>&5; (eval $ac_link) 2>&5; } && test -s conftest${ac_exeext} && (./conftest; exit) 2>/dev/null
+if { (eval echo configure:105085: \"$ac_link\") 1>&5; (eval $ac_link) 2>&5; } && test -s conftest${ac_exeext} && (./conftest; exit) 2>/dev/null
 then
   
     eval $php_cache_value=`cat conftestval`
@@ -104982,7 +105216,7 @@ if test "$PHP_RECODE" != "no"; then
   done
 
   echo $ac_n "checking for hash_insert in -l$MYSQL_LIBNAME""... $ac_c" 1>&6
-echo "configure:104986: checking for hash_insert in -l$MYSQL_LIBNAME" >&5
+echo "configure:105225: checking for hash_insert in -l$MYSQL_LIBNAME" >&5
 ac_lib_var=`echo $MYSQL_LIBNAME'_'hash_insert | sed 'y%./+-%__p_%'`
 if eval "test \"`echo '$''{'ac_cv_lib_$ac_lib_var'+set}'`\" = set"; then
   echo $ac_n "(cached) $ac_c" 1>&6
@@ -104990,7 +105224,7 @@ else
   ac_save_LIBS="$LIBS"
 LIBS="-l$MYSQL_LIBNAME  $LIBS"
 cat > conftest.$ac_ext <<EOF
-#line 104994 "configure"
+#line 105233 "configure"
 #include "confdefs.h"
 /* Override any gcc2 internal prototype to avoid an error.  */
 /* We use char because int might match the return type of a gcc2
@@ -105001,7 +105235,7 @@ int main() {
 hash_insert()
 ; return 0; }
 EOF
-if { (eval echo configure:105005: \"$ac_link\") 1>&5; (eval $ac_link) 2>&5; } && test -s conftest${ac_exeext}; then
+if { (eval echo configure:105244: \"$ac_link\") 1>&5; (eval $ac_link) 2>&5; } && test -s conftest${ac_exeext}; then
   rm -rf conftest*
   eval "ac_cv_lib_$ac_lib_var=yes"
 else
@@ -105046,13 +105280,7 @@ fi
 enable_shared=yes
 enable_static=yes
 
-case $php_build_target in
-  program|static)
-    standard_libtool_flag='-prefer-non-pic -static'
-    if test -z "$PHP_MODULES" && test -z "$PHP_ZEND_EX"; then
-        enable_shared=no
-    fi
-    ;;
+case $php_sapi_module in
   shared)
     enable_static=no
     case $with_pic in
@@ -105065,6 +105293,12 @@ case $php_build_target in
     esac
     EXTRA_LDFLAGS="$EXTRA_LDFLAGS -avoid-version -module"
     ;;
+  *)
+    standard_libtool_flag='-prefer-non-pic -static'
+    if test -z "$PHP_MODULES" && test -z "$PHP_ZEND_EX"; then
+      enable_shared=no
+    fi
+    ;;
 esac
 
 EXTRA_LIBS="$EXTRA_LIBS $DLIBS $LIBS"
@@ -105103,7 +105337,7 @@ fi
 php_with_pear=DEFAULT
 
 echo $ac_n "checking whether to install PEAR""... $ac_c" 1>&6
-echo "configure:105107: checking whether to install PEAR" >&5
+echo "configure:105346: checking whether to install PEAR" >&5
 # Check whether --with-pear or --without-pear was given.
 if test "${with_pear+set}" = set; then
   withval="$with_pear"
@@ -105205,7 +105439,7 @@ fi
   bison_version=none
   if test "$YACC"; then
     echo $ac_n "checking for bison version""... $ac_c" 1>&6
-echo "configure:105209: checking for bison version" >&5
+echo "configure:105448: checking for bison version" >&5
 if eval "test \"`echo '$''{'php_cv_bison_version'+set}'`\" = set"; then
   echo $ac_n "(cached) $ac_c" 1>&6
 else
@@ -105215,12 +105449,7 @@ else
       if test -n "$bison_version_vars"; then
         set $bison_version_vars
         bison_version="${1}.${2}"
-        for bison_check_version in $bison_version_list; do
-          if test "$bison_version" = "$bison_check_version"; then
-            php_cv_bison_version="$bison_check_version (ok)"
-            break
-          fi
-        done
+        php_cv_bison_version="$bison_version (ok)"
       fi
     
 fi
@@ -105261,17 +105490,17 @@ dlfcn.h
 do
 ac_safe=`echo "$ac_hdr" | sed 'y%./+-%__p_%'`
 echo $ac_n "checking for $ac_hdr""... $ac_c" 1>&6
-echo "configure:105265: checking for $ac_hdr" >&5
+echo "configure:105504: checking for $ac_hdr" >&5
 if eval "test \"`echo '$''{'ac_cv_header_$ac_safe'+set}'`\" = set"; then
   echo $ac_n "(cached) $ac_c" 1>&6
 else
   cat > conftest.$ac_ext <<EOF
-#line 105270 "configure"
+#line 105509 "configure"
 #include "confdefs.h"
 #include <$ac_hdr>
 EOF
 ac_try="$ac_cpp conftest.$ac_ext >/dev/null 2>conftest.out"
-{ (eval echo configure:105275: \"$ac_try\") 1>&5; (eval $ac_try) 2>&5; }
+{ (eval echo configure:105514: \"$ac_try\") 1>&5; (eval $ac_try) 2>&5; }
 ac_err=`grep -v '^ *+' conftest.out | grep -v "^conftest.${ac_ext}\$"`
 if test -z "$ac_err"; then
   rm -rf conftest*
@@ -105299,12 +105528,12 @@ done
 
 
 echo $ac_n "checking for size_t""... $ac_c" 1>&6
-echo "configure:105303: checking for size_t" >&5
+echo "configure:105542: checking for size_t" >&5
 if eval "test \"`echo '$''{'ac_cv_type_size_t'+set}'`\" = set"; then
   echo $ac_n "(cached) $ac_c" 1>&6
 else
   cat > conftest.$ac_ext <<EOF
-#line 105308 "configure"
+#line 105547 "configure"
 #include "confdefs.h"
 #include <sys/types.h>
 #if STDC_HEADERS
@@ -105332,12 +105561,12 @@ EOF
 fi
 
 echo $ac_n "checking return type of signal handlers""... $ac_c" 1>&6
-echo "configure:105336: checking return type of signal handlers" >&5
+echo "configure:105575: checking return type of signal handlers" >&5
 if eval "test \"`echo '$''{'ac_cv_type_signal'+set}'`\" = set"; then
   echo $ac_n "(cached) $ac_c" 1>&6
 else
   cat > conftest.$ac_ext <<EOF
-#line 105341 "configure"
+#line 105580 "configure"
 #include "confdefs.h"
 #include <sys/types.h>
 #include <signal.h>
@@ -105354,7 +105583,7 @@ int main() {
 int i;
 ; return 0; }
 EOF
-if { (eval echo configure:105358: \"$ac_compile\") 1>&5; (eval $ac_compile) 2>&5; }; then
+if { (eval echo configure:105597: \"$ac_compile\") 1>&5; (eval $ac_compile) 2>&5; }; then
   rm -rf conftest*
   ac_cv_type_signal=void
 else
@@ -105378,12 +105607,12 @@ EOF
 
 
 echo $ac_n "checking for uint""... $ac_c" 1>&6
-echo "configure:105382: checking for uint" >&5
+echo "configure:105621: checking for uint" >&5
 if eval "test \"`echo '$''{'ac_cv_type_uint'+set}'`\" = set"; then
   echo $ac_n "(cached) $ac_c" 1>&6
 else
   cat > conftest.$ac_ext <<EOF
-#line 105387 "configure"
+#line 105626 "configure"
 #include "confdefs.h"
 #include <sys/types.h>
 #if STDC_HEADERS
@@ -105411,12 +105640,12 @@ EOF
 fi
 
 echo $ac_n "checking for ulong""... $ac_c" 1>&6
-echo "configure:105415: checking for ulong" >&5
+echo "configure:105654: checking for ulong" >&5
 if eval "test \"`echo '$''{'ac_cv_type_ulong'+set}'`\" = set"; then
   echo $ac_n "(cached) $ac_c" 1>&6
 else
   cat > conftest.$ac_ext <<EOF
-#line 105420 "configure"
+#line 105659 "configure"
 #include "confdefs.h"
 #include <sys/types.h>
 #if STDC_HEADERS
@@ -105446,9 +105675,9 @@ fi
 
 
 echo $ac_n "checking for int32_t""... $ac_c" 1>&6
-echo "configure:105450: checking for int32_t" >&5
+echo "configure:105689: checking for int32_t" >&5
 cat > conftest.$ac_ext <<EOF
-#line 105452 "configure"
+#line 105691 "configure"
 #include "confdefs.h"
 
 #if HAVE_SYS_TYPES_H  
@@ -105467,7 +105696,7 @@ if (sizeof (int32_t))
 
 ; return 0; }
 EOF
-if { (eval echo configure:105471: \"$ac_compile\") 1>&5; (eval $ac_compile) 2>&5; }; then
+if { (eval echo configure:105710: \"$ac_compile\") 1>&5; (eval $ac_compile) 2>&5; }; then
   rm -rf conftest*
   
   cat >> confdefs.h <<EOF
@@ -105486,9 +105715,9 @@ fi
 rm -f conftest*
 
 echo $ac_n "checking for uint32_t""... $ac_c" 1>&6
-echo "configure:105490: checking for uint32_t" >&5
+echo "configure:105729: checking for uint32_t" >&5
 cat > conftest.$ac_ext <<EOF
-#line 105492 "configure"
+#line 105731 "configure"
 #include "confdefs.h"
 
 #if HAVE_SYS_TYPES_H  
@@ -105507,7 +105736,7 @@ if (sizeof (uint32_t))
 
 ; return 0; }
 EOF
-if { (eval echo configure:105511: \"$ac_compile\") 1>&5; (eval $ac_compile) 2>&5; }; then
+if { (eval echo configure:105750: \"$ac_compile\") 1>&5; (eval $ac_compile) 2>&5; }; then
   rm -rf conftest*
   
   cat >> confdefs.h <<EOF
@@ -105526,12 +105755,12 @@ fi
 rm -f conftest*
 
 echo $ac_n "checking for vprintf""... $ac_c" 1>&6
-echo "configure:105530: checking for vprintf" >&5
+echo "configure:105769: checking for vprintf" >&5
 if eval "test \"`echo '$''{'ac_cv_func_vprintf'+set}'`\" = set"; then
   echo $ac_n "(cached) $ac_c" 1>&6
 else
   cat > conftest.$ac_ext <<EOF
-#line 105535 "configure"
+#line 105774 "configure"
 #include "confdefs.h"
 /* System header to define __stub macros and hopefully few prototypes,
     which can conflict with char vprintf(); below.  */
@@ -105554,7 +105783,7 @@ vprintf();
 
 ; return 0; }
 EOF
-if { (eval echo configure:105558: \"$ac_link\") 1>&5; (eval $ac_link) 2>&5; } && test -s conftest${ac_exeext}; then
+if { (eval echo configure:105797: \"$ac_link\") 1>&5; (eval $ac_link) 2>&5; } && test -s conftest${ac_exeext}; then
   rm -rf conftest*
   eval "ac_cv_func_vprintf=yes"
 else
@@ -105578,12 +105807,12 @@ fi
 
 if test "$ac_cv_func_vprintf" != yes; then
 echo $ac_n "checking for _doprnt""... $ac_c" 1>&6
-echo "configure:105582: checking for _doprnt" >&5
+echo "configure:105821: checking for _doprnt" >&5
 if eval "test \"`echo '$''{'ac_cv_func__doprnt'+set}'`\" = set"; then
   echo $ac_n "(cached) $ac_c" 1>&6
 else
   cat > conftest.$ac_ext <<EOF
-#line 105587 "configure"
+#line 105826 "configure"
 #include "confdefs.h"
 /* System header to define __stub macros and hopefully few prototypes,
     which can conflict with char _doprnt(); below.  */
@@ -105606,7 +105835,7 @@ _doprnt();
 
 ; return 0; }
 EOF
-if { (eval echo configure:105610: \"$ac_link\") 1>&5; (eval $ac_link) 2>&5; } && test -s conftest${ac_exeext}; then
+if { (eval echo configure:105849: \"$ac_link\") 1>&5; (eval $ac_link) 2>&5; } && test -s conftest${ac_exeext}; then
   rm -rf conftest*
   eval "ac_cv_func__doprnt=yes"
 else
@@ -105631,7 +105860,7 @@ fi
 fi
 
 echo $ac_n "checking for 8-bit clean memcmp""... $ac_c" 1>&6
-echo "configure:105635: checking for 8-bit clean memcmp" >&5
+echo "configure:105874: checking for 8-bit clean memcmp" >&5
 if eval "test \"`echo '$''{'ac_cv_func_memcmp_clean'+set}'`\" = set"; then
   echo $ac_n "(cached) $ac_c" 1>&6
 else
@@ -105639,7 +105868,7 @@ else
   ac_cv_func_memcmp_clean=no
 else
   cat > conftest.$ac_ext <<EOF
-#line 105643 "configure"
+#line 105882 "configure"
 #include "confdefs.h"
 
 main()
@@ -105649,7 +105878,7 @@ main()
 }
 
 EOF
-if { (eval echo configure:105653: \"$ac_link\") 1>&5; (eval $ac_link) 2>&5; } && test -s conftest${ac_exeext} && (./conftest; exit) 2>/dev/null
+if { (eval echo configure:105892: \"$ac_link\") 1>&5; (eval $ac_link) 2>&5; } && test -s conftest${ac_exeext} && (./conftest; exit) 2>/dev/null
 then
   ac_cv_func_memcmp_clean=yes
 else
@@ -105669,19 +105898,19 @@ test $ac_cv_func_memcmp_clean = no && LIBOBJS="$LIBOBJS memcmp.${ac_objext}"
 # The Ultrix 4.2 mips builtin alloca declared by alloca.h only works
 # for constant arguments.  Useless!
 echo $ac_n "checking for working alloca.h""... $ac_c" 1>&6
-echo "configure:105673: checking for working alloca.h" >&5
+echo "configure:105912: checking for working alloca.h" >&5
 if eval "test \"`echo '$''{'ac_cv_header_alloca_h'+set}'`\" = set"; then
   echo $ac_n "(cached) $ac_c" 1>&6
 else
   cat > conftest.$ac_ext <<EOF
-#line 105678 "configure"
+#line 105917 "configure"
 #include "confdefs.h"
 #include <alloca.h>
 int main() {
 char *p = alloca(2 * sizeof(int));
 ; return 0; }
 EOF
-if { (eval echo configure:105685: \"$ac_link\") 1>&5; (eval $ac_link) 2>&5; } && test -s conftest${ac_exeext}; then
+if { (eval echo configure:105924: \"$ac_link\") 1>&5; (eval $ac_link) 2>&5; } && test -s conftest${ac_exeext}; then
   rm -rf conftest*
   ac_cv_header_alloca_h=yes
 else
@@ -105702,12 +105931,12 @@ EOF
 fi
 
 echo $ac_n "checking for alloca""... $ac_c" 1>&6
-echo "configure:105706: checking for alloca" >&5
+echo "configure:105945: checking for alloca" >&5
 if eval "test \"`echo '$''{'ac_cv_func_alloca_works'+set}'`\" = set"; then
   echo $ac_n "(cached) $ac_c" 1>&6
 else
   cat > conftest.$ac_ext <<EOF
-#line 105711 "configure"
+#line 105950 "configure"
 #include "confdefs.h"
 
 #ifdef __GNUC__
@@ -105735,7 +105964,7 @@ int main() {
 char *p = (char *) alloca(1);
 ; return 0; }
 EOF
-if { (eval echo configure:105739: \"$ac_link\") 1>&5; (eval $ac_link) 2>&5; } && test -s conftest${ac_exeext}; then
+if { (eval echo configure:105978: \"$ac_link\") 1>&5; (eval $ac_link) 2>&5; } && test -s conftest${ac_exeext}; then
   rm -rf conftest*
   ac_cv_func_alloca_works=yes
 else
@@ -105767,12 +105996,12 @@ EOF
 
 
 echo $ac_n "checking whether alloca needs Cray hooks""... $ac_c" 1>&6
-echo "configure:105771: checking whether alloca needs Cray hooks" >&5
+echo "configure:106010: checking whether alloca needs Cray hooks" >&5
 if eval "test \"`echo '$''{'ac_cv_os_cray'+set}'`\" = set"; then
   echo $ac_n "(cached) $ac_c" 1>&6
 else
   cat > conftest.$ac_ext <<EOF
-#line 105776 "configure"
+#line 106015 "configure"
 #include "confdefs.h"
 #if defined(CRAY) && ! defined(CRAY2)
 webecray
@@ -105797,12 +106026,12 @@ echo "$ac_t""$ac_cv_os_cray" 1>&6
 if test $ac_cv_os_cray = yes; then
 for ac_func in _getb67 GETB67 getb67; do
   echo $ac_n "checking for $ac_func""... $ac_c" 1>&6
-echo "configure:105801: checking for $ac_func" >&5
+echo "configure:106040: checking for $ac_func" >&5
 if eval "test \"`echo '$''{'ac_cv_func_$ac_func'+set}'`\" = set"; then
   echo $ac_n "(cached) $ac_c" 1>&6
 else
   cat > conftest.$ac_ext <<EOF
-#line 105806 "configure"
+#line 106045 "configure"
 #include "confdefs.h"
 /* System header to define __stub macros and hopefully few prototypes,
     which can conflict with char $ac_func(); below.  */
@@ -105825,7 +106054,7 @@ $ac_func();
 
 ; return 0; }
 EOF
-if { (eval echo configure:105829: \"$ac_link\") 1>&5; (eval $ac_link) 2>&5; } && test -s conftest${ac_exeext}; then
+if { (eval echo configure:106068: \"$ac_link\") 1>&5; (eval $ac_link) 2>&5; } && test -s conftest${ac_exeext}; then
   rm -rf conftest*
   eval "ac_cv_func_$ac_func=yes"
 else
@@ -105852,7 +106081,7 @@ done
 fi
 
 echo $ac_n "checking stack direction for C alloca""... $ac_c" 1>&6
-echo "configure:105856: checking stack direction for C alloca" >&5
+echo "configure:106095: checking stack direction for C alloca" >&5
 if eval "test \"`echo '$''{'ac_cv_c_stack_direction'+set}'`\" = set"; then
   echo $ac_n "(cached) $ac_c" 1>&6
 else
@@ -105860,7 +106089,7 @@ else
   ac_cv_c_stack_direction=0
 else
   cat > conftest.$ac_ext <<EOF
-#line 105864 "configure"
+#line 106103 "configure"
 #include "confdefs.h"
 find_stack_direction ()
 {
@@ -105879,7 +106108,7 @@ main ()
   exit (find_stack_direction() < 0);
 }
 EOF
-if { (eval echo configure:105883: \"$ac_link\") 1>&5; (eval $ac_link) 2>&5; } && test -s conftest${ac_exeext} && (./conftest; exit) 2>/dev/null
+if { (eval echo configure:106122: \"$ac_link\") 1>&5; (eval $ac_link) 2>&5; } && test -s conftest${ac_exeext} && (./conftest; exit) 2>/dev/null
 then
   ac_cv_c_stack_direction=1
 else
@@ -105903,12 +106132,12 @@ fi
 for ac_func in memcpy strdup getpid kill strtod strtol finite fpclass sigsetjmp
 do
 echo $ac_n "checking for $ac_func""... $ac_c" 1>&6
-echo "configure:105907: checking for $ac_func" >&5
+echo "configure:106146: checking for $ac_func" >&5
 if eval "test \"`echo '$''{'ac_cv_func_$ac_func'+set}'`\" = set"; then
   echo $ac_n "(cached) $ac_c" 1>&6
 else
   cat > conftest.$ac_ext <<EOF
-#line 105912 "configure"
+#line 106151 "configure"
 #include "confdefs.h"
 /* System header to define __stub macros and hopefully few prototypes,
     which can conflict with char $ac_func(); below.  */
@@ -105931,7 +106160,7 @@ $ac_func();
 
 ; return 0; }
 EOF
-if { (eval echo configure:105935: \"$ac_link\") 1>&5; (eval $ac_link) 2>&5; } && test -s conftest${ac_exeext}; then
+if { (eval echo configure:106174: \"$ac_link\") 1>&5; (eval $ac_link) 2>&5; } && test -s conftest${ac_exeext}; then
   rm -rf conftest*
   eval "ac_cv_func_$ac_func=yes"
 else
@@ -105957,7 +106186,7 @@ done
 
 
   echo $ac_n "checking whether sprintf is broken""... $ac_c" 1>&6
-echo "configure:105961: checking whether sprintf is broken" >&5
+echo "configure:106200: checking whether sprintf is broken" >&5
 if eval "test \"`echo '$''{'ac_cv_broken_sprintf'+set}'`\" = set"; then
   echo $ac_n "(cached) $ac_c" 1>&6
 else
@@ -105968,11 +106197,11 @@ else
     
 else
   cat > conftest.$ac_ext <<EOF
-#line 105972 "configure"
+#line 106211 "configure"
 #include "confdefs.h"
 main() {char buf[20];exit(sprintf(buf,"testing 123")!=11); }
 EOF
-if { (eval echo configure:105976: \"$ac_link\") 1>&5; (eval $ac_link) 2>&5; } && test -s conftest${ac_exeext} && (./conftest; exit) 2>/dev/null
+if { (eval echo configure:106215: \"$ac_link\") 1>&5; (eval $ac_link) 2>&5; } && test -s conftest${ac_exeext} && (./conftest; exit) 2>/dev/null
 then
   
       ac_cv_broken_sprintf=no
@@ -106006,12 +106235,12 @@ EOF
 for ac_func in finite isfinite isinf isnan
 do
 echo $ac_n "checking for $ac_func""... $ac_c" 1>&6
-echo "configure:106010: checking for $ac_func" >&5
+echo "configure:106249: checking for $ac_func" >&5
 if eval "test \"`echo '$''{'ac_cv_func_$ac_func'+set}'`\" = set"; then
   echo $ac_n "(cached) $ac_c" 1>&6
 else
   cat > conftest.$ac_ext <<EOF
-#line 106015 "configure"
+#line 106254 "configure"
 #include "confdefs.h"
 /* System header to define __stub macros and hopefully few prototypes,
     which can conflict with char $ac_func(); below.  */
@@ -106034,7 +106263,7 @@ $ac_func();
 
 ; return 0; }
 EOF
-if { (eval echo configure:106038: \"$ac_link\") 1>&5; (eval $ac_link) 2>&5; } && test -s conftest${ac_exeext}; then
+if { (eval echo configure:106277: \"$ac_link\") 1>&5; (eval $ac_link) 2>&5; } && test -s conftest${ac_exeext}; then
   rm -rf conftest*
   eval "ac_cv_func_$ac_func=yes"
 else
@@ -106061,13 +106290,13 @@ done
 
 
   echo $ac_n "checking whether fp_except is defined""... $ac_c" 1>&6
-echo "configure:106065: checking whether fp_except is defined" >&5
+echo "configure:106304: checking whether fp_except is defined" >&5
 if eval "test \"`echo '$''{'ac_cv_type_fp_except'+set}'`\" = set"; then
   echo $ac_n "(cached) $ac_c" 1>&6
 else
   
     cat > conftest.$ac_ext <<EOF
-#line 106071 "configure"
+#line 106310 "configure"
 #include "confdefs.h"
 
 #include <floatingpoint.h>
@@ -106078,7 +106307,7 @@ fp_except x = (fp_except) 0;
 
 ; return 0; }
 EOF
-if { (eval echo configure:106082: \"$ac_compile\") 1>&5; (eval $ac_compile) 2>&5; }; then
+if { (eval echo configure:106321: \"$ac_compile\") 1>&5; (eval $ac_compile) 2>&5; }; then
   rm -rf conftest*
   
      ac_cv_type_fp_except=yes
@@ -106105,9 +106334,9 @@ EOF
 
 
   echo $ac_n "checking for usable _FPU_SETCW""... $ac_c" 1>&6
-echo "configure:106109: checking for usable _FPU_SETCW" >&5
+echo "configure:106348: checking for usable _FPU_SETCW" >&5
   cat > conftest.$ac_ext <<EOF
-#line 106111 "configure"
+#line 106350 "configure"
 #include "confdefs.h"
 
     #include <fpu_control.h>
@@ -106127,7 +106356,7 @@ int main() {
   
 ; return 0; }
 EOF
-if { (eval echo configure:106131: \"$ac_link\") 1>&5; (eval $ac_link) 2>&5; } && test -s conftest${ac_exeext}; then
+if { (eval echo configure:106370: \"$ac_link\") 1>&5; (eval $ac_link) 2>&5; } && test -s conftest${ac_exeext}; then
   rm -rf conftest*
   ac_cfp_have__fpu_setcw=yes
 else
@@ -106148,9 +106377,9 @@ EOF
   fi
   
   echo $ac_n "checking for usable fpsetprec""... $ac_c" 1>&6
-echo "configure:106152: checking for usable fpsetprec" >&5
+echo "configure:106391: checking for usable fpsetprec" >&5
   cat > conftest.$ac_ext <<EOF
-#line 106154 "configure"
+#line 106393 "configure"
 #include "confdefs.h"
 
     #include <machine/ieeefp.h>
@@ -106169,7 +106398,7 @@ int main() {
   
 ; return 0; }
 EOF
-if { (eval echo configure:106173: \"$ac_link\") 1>&5; (eval $ac_link) 2>&5; } && test -s conftest${ac_exeext}; then
+if { (eval echo configure:106412: \"$ac_link\") 1>&5; (eval $ac_link) 2>&5; } && test -s conftest${ac_exeext}; then
   rm -rf conftest*
   ac_cfp_have_fpsetprec=yes
 else
@@ -106190,9 +106419,9 @@ EOF
   fi
 
   echo $ac_n "checking for usable _controlfp""... $ac_c" 1>&6
-echo "configure:106194: checking for usable _controlfp" >&5
+echo "configure:106433: checking for usable _controlfp" >&5
   cat > conftest.$ac_ext <<EOF
-#line 106196 "configure"
+#line 106435 "configure"
 #include "confdefs.h"
 
     #include <float.h>
@@ -106211,7 +106440,7 @@ int main() {
   
 ; return 0; }
 EOF
-if { (eval echo configure:106215: \"$ac_link\") 1>&5; (eval $ac_link) 2>&5; } && test -s conftest${ac_exeext}; then
+if { (eval echo configure:106454: \"$ac_link\") 1>&5; (eval $ac_link) 2>&5; } && test -s conftest${ac_exeext}; then
   rm -rf conftest*
   ac_cfp_have__controlfp=yes
 else
@@ -106232,9 +106461,9 @@ EOF
   fi
 
   echo $ac_n "checking for usable _controlfp_s""... $ac_c" 1>&6
-echo "configure:106236: checking for usable _controlfp_s" >&5
+echo "configure:106475: checking for usable _controlfp_s" >&5
   cat > conftest.$ac_ext <<EOF
-#line 106238 "configure"
+#line 106477 "configure"
 #include "confdefs.h"
 
    #include <float.h>
@@ -106254,7 +106483,7 @@ int main() {
   
 ; return 0; }
 EOF
-if { (eval echo configure:106258: \"$ac_link\") 1>&5; (eval $ac_link) 2>&5; } && test -s conftest${ac_exeext}; then
+if { (eval echo configure:106497: \"$ac_link\") 1>&5; (eval $ac_link) 2>&5; } && test -s conftest${ac_exeext}; then
   rm -rf conftest*
   ac_cfp_have__controlfp_s=yes
 else
@@ -106275,9 +106504,9 @@ EOF
   fi
 
   echo $ac_n "checking whether FPU control word can be manipulated by inline assembler""... $ac_c" 1>&6
-echo "configure:106279: checking whether FPU control word can be manipulated by inline assembler" >&5
+echo "configure:106518: checking whether FPU control word can be manipulated by inline assembler" >&5
   cat > conftest.$ac_ext <<EOF
-#line 106281 "configure"
+#line 106520 "configure"
 #include "confdefs.h"
 
     /* nothing */
@@ -106299,7 +106528,7 @@ int main() {
   
 ; return 0; }
 EOF
-if { (eval echo configure:106303: \"$ac_link\") 1>&5; (eval $ac_link) 2>&5; } && test -s conftest${ac_exeext}; then
+if { (eval echo configure:106542: \"$ac_link\") 1>&5; (eval $ac_link) 2>&5; } && test -s conftest${ac_exeext}; then
   rm -rf conftest*
   ac_cfp_have_fpu_inline_asm_x86=yes
 else
@@ -106321,7 +106550,7 @@ EOF
 
 
 echo $ac_n "checking whether double cast to long preserves least significant bits""... $ac_c" 1>&6
-echo "configure:106325: checking whether double cast to long preserves least significant bits" >&5
+echo "configure:106564: checking whether double cast to long preserves least significant bits" >&5
 
 if test "$cross_compiling" = yes; then
   
@@ -106329,7 +106558,7 @@ if test "$cross_compiling" = yes; then
 
 else
   cat > conftest.$ac_ext <<EOF
-#line 106333 "configure"
+#line 106572 "configure"
 #include "confdefs.h"
 
 #include <limits.h>
@@ -106353,7 +106582,7 @@ int main()
 }
 
 EOF
-if { (eval echo configure:106357: \"$ac_link\") 1>&5; (eval $ac_link) 2>&5; } && test -s conftest${ac_exeext} && (./conftest; exit) 2>/dev/null
+if { (eval echo configure:106596: \"$ac_link\") 1>&5; (eval $ac_link) 2>&5; } && test -s conftest${ac_exeext} && (./conftest; exit) 2>/dev/null
 then
   
   cat >> confdefs.h <<\EOF
@@ -106379,17 +106608,17 @@ for ac_hdr in dlfcn.h
 do
 ac_safe=`echo "$ac_hdr" | sed 'y%./+-%__p_%'`
 echo $ac_n "checking for $ac_hdr""... $ac_c" 1>&6
-echo "configure:106383: checking for $ac_hdr" >&5
+echo "configure:106622: checking for $ac_hdr" >&5
 if eval "test \"`echo '$''{'ac_cv_header_$ac_safe'+set}'`\" = set"; then
   echo $ac_n "(cached) $ac_c" 1>&6
 else
   cat > conftest.$ac_ext <<EOF
-#line 106388 "configure"
+#line 106627 "configure"
 #include "confdefs.h"
 #include <$ac_hdr>
 EOF
 ac_try="$ac_cpp conftest.$ac_ext >/dev/null 2>conftest.out"
-{ (eval echo configure:106393: \"$ac_try\") 1>&5; (eval $ac_try) 2>&5; }
+{ (eval echo configure:106632: \"$ac_try\") 1>&5; (eval $ac_try) 2>&5; }
 ac_err=`grep -v '^ *+' conftest.out | grep -v "^conftest.${ac_ext}\$"`
 if test -z "$ac_err"; then
   rm -rf conftest*
@@ -106417,14 +106646,14 @@ done
 
 
 echo $ac_n "checking whether dlsym() requires a leading underscore in symbol names""... $ac_c" 1>&6
-echo "configure:106421: checking whether dlsym() requires a leading underscore in symbol names" >&5
+echo "configure:106660: checking whether dlsym() requires a leading underscore in symbol names" >&5
 if test "$cross_compiling" = yes; then :
   
 else
   lt_dlunknown=0; lt_dlno_uscore=1; lt_dlneed_uscore=2
   lt_status=$lt_dlunknown
   cat > conftest.$ac_ext <<EOF
-#line 106428 "configure"
+#line 106667 "configure"
 #include "confdefs.h"
 
 #if HAVE_DLFCN_H
@@ -106487,7 +106716,7 @@ int main ()
     exit (status);
 }
 EOF
-  if { (eval echo configure:106491: \"$ac_link\") 1>&5; (eval $ac_link) 2>&5; } && test -s conftest${ac_exeext} 2>/dev/null; then
+  if { (eval echo configure:106730: \"$ac_link\") 1>&5; (eval $ac_link) 2>&5; } && test -s conftest${ac_exeext} 2>/dev/null; then
     (./conftest; exit; ) >&5 2>/dev/null
     lt_status=$?
     case x$lt_status in
@@ -106570,23 +106799,23 @@ fi
 
 
 echo $ac_n "checking virtual machine dispatch method""... $ac_c" 1>&6
-echo "configure:106574: checking virtual machine dispatch method" >&5
+echo "configure:106813: checking virtual machine dispatch method" >&5
 echo "$ac_t""$PHP_ZEND_VM" 1>&6
 
 echo $ac_n "checking whether to enable thread-safety""... $ac_c" 1>&6
-echo "configure:106578: checking whether to enable thread-safety" >&5
+echo "configure:106817: checking whether to enable thread-safety" >&5
 echo "$ac_t""$ZEND_MAINTAINER_ZTS" 1>&6
 
 echo $ac_n "checking whether to enable inline optimization for GCC""... $ac_c" 1>&6
-echo "configure:106582: checking whether to enable inline optimization for GCC" >&5
+echo "configure:106821: checking whether to enable inline optimization for GCC" >&5
 echo "$ac_t""$ZEND_INLINE_OPTIMIZATION" 1>&6
 
 echo $ac_n "checking whether to enable Zend debugging""... $ac_c" 1>&6
-echo "configure:106586: checking whether to enable Zend debugging" >&5
+echo "configure:106825: checking whether to enable Zend debugging" >&5
 echo "$ac_t""$ZEND_DEBUG" 1>&6
 
 echo $ac_n "checking whether to enable Zend multibyte""... $ac_c" 1>&6
-echo "configure:106590: checking whether to enable Zend multibyte" >&5
+echo "configure:106829: checking whether to enable Zend multibyte" >&5
 echo "$ac_t""$ZEND_MULTIBYTE" 1>&6
 
 case $PHP_ZEND_VM in
@@ -106659,21 +106888,21 @@ fi
 
 
 echo $ac_n "checking for inline""... $ac_c" 1>&6
-echo "configure:106663: checking for inline" >&5
+echo "configure:106902: checking for inline" >&5
 if eval "test \"`echo '$''{'ac_cv_c_inline'+set}'`\" = set"; then
   echo $ac_n "(cached) $ac_c" 1>&6
 else
   ac_cv_c_inline=no
 for ac_kw in inline __inline__ __inline; do
   cat > conftest.$ac_ext <<EOF
-#line 106670 "configure"
+#line 106909 "configure"
 #include "confdefs.h"
 
 int main() {
 } $ac_kw foo() {
 ; return 0; }
 EOF
-if { (eval echo configure:106677: \"$ac_compile\") 1>&5; (eval $ac_compile) 2>&5; }; then
+if { (eval echo configure:106916: \"$ac_compile\") 1>&5; (eval $ac_compile) 2>&5; }; then
   rm -rf conftest*
   ac_cv_c_inline=$ac_kw; break
 else
@@ -106702,7 +106931,7 @@ esac
 
 
 echo $ac_n "checking target system is Darwin""... $ac_c" 1>&6
-echo "configure:106706: checking target system is Darwin" >&5
+echo "configure:106945: checking target system is Darwin" >&5
 if echo "$target" | grep "darwin" > /dev/null; then
   cat >> confdefs.h <<\EOF
 #define DARWIN 1
@@ -106714,7 +106943,7 @@ else
 fi
 
 echo $ac_n "checking for MM alignment and log values""... $ac_c" 1>&6
-echo "configure:106718: checking for MM alignment and log values" >&5
+echo "configure:106957: checking for MM alignment and log values" >&5
 
 if test "$cross_compiling" = yes; then
   
@@ -106722,7 +106951,7 @@ if test "$cross_compiling" = yes; then
 
 else
   cat > conftest.$ac_ext <<EOF
-#line 106726 "configure"
+#line 106965 "configure"
 #include "confdefs.h"
 
 #include <stdio.h>
@@ -106758,7 +106987,7 @@ int main()
 }
 
 EOF
-if { (eval echo configure:106762: \"$ac_link\") 1>&5; (eval $ac_link) 2>&5; } && test -s conftest${ac_exeext} && (./conftest; exit) 2>/dev/null
+if { (eval echo configure:107001: \"$ac_link\") 1>&5; (eval $ac_link) 2>&5; } && test -s conftest${ac_exeext} && (./conftest; exit) 2>/dev/null
 then
   
   LIBZEND_MM_ALIGN=`cat conftest.zend | cut -d ' ' -f 1`
@@ -106783,7 +107012,7 @@ fi
 echo "$ac_t""done" 1>&6
 
 echo $ac_n "checking for memory allocation using mmap(MAP_ANON)""... $ac_c" 1>&6
-echo "configure:106787: checking for memory allocation using mmap(MAP_ANON)" >&5
+echo "configure:107026: checking for memory allocation using mmap(MAP_ANON)" >&5
 
 if test "$cross_compiling" = yes; then
   
@@ -106791,7 +107020,7 @@ if test "$cross_compiling" = yes; then
 
 else
   cat > conftest.$ac_ext <<EOF
-#line 106795 "configure"
+#line 107034 "configure"
 #include "confdefs.h"
 
 #include <sys/types.h>
@@ -106827,7 +107056,7 @@ int main()
 }
 
 EOF
-if { (eval echo configure:106831: \"$ac_link\") 1>&5; (eval $ac_link) 2>&5; } && test -s conftest${ac_exeext} && (./conftest; exit) 2>/dev/null
+if { (eval echo configure:107070: \"$ac_link\") 1>&5; (eval $ac_link) 2>&5; } && test -s conftest${ac_exeext} && (./conftest; exit) 2>/dev/null
 then
   
   cat >> confdefs.h <<\EOF
@@ -106849,7 +107078,7 @@ fi
 
 
 echo $ac_n "checking for memory allocation using mmap("/dev/zero")""... $ac_c" 1>&6
-echo "configure:106853: checking for memory allocation using mmap("/dev/zero")" >&5
+echo "configure:107092: checking for memory allocation using mmap("/dev/zero")" >&5
 
 if test "$cross_compiling" = yes; then
   
@@ -106857,7 +107086,7 @@ if test "$cross_compiling" = yes; then
 
 else
   cat > conftest.$ac_ext <<EOF
-#line 106861 "configure"
+#line 107100 "configure"
 #include "confdefs.h"
 
 #include <sys/types.h>
@@ -106903,7 +107132,7 @@ int main()
 }
 
 EOF
-if { (eval echo configure:106907: \"$ac_link\") 1>&5; (eval $ac_link) 2>&5; } && test -s conftest${ac_exeext} && (./conftest; exit) 2>/dev/null
+if { (eval echo configure:107146: \"$ac_link\") 1>&5; (eval $ac_link) 2>&5; } && test -s conftest${ac_exeext} && (./conftest; exit) 2>/dev/null
 then
   
   cat >> confdefs.h <<\EOF
@@ -106927,12 +107156,12 @@ fi
 for ac_func in mremap
 do
 echo $ac_n "checking for $ac_func""... $ac_c" 1>&6
-echo "configure:106931: checking for $ac_func" >&5
+echo "configure:107170: checking for $ac_func" >&5
 if eval "test \"`echo '$''{'ac_cv_func_$ac_func'+set}'`\" = set"; then
   echo $ac_n "(cached) $ac_c" 1>&6
 else
   cat > conftest.$ac_ext <<EOF
-#line 106936 "configure"
+#line 107175 "configure"
 #include "confdefs.h"
 /* System header to define __stub macros and hopefully few prototypes,
     which can conflict with char $ac_func(); below.  */
@@ -106955,7 +107184,7 @@ $ac_func();
 
 ; return 0; }
 EOF
-if { (eval echo configure:106959: \"$ac_link\") 1>&5; (eval $ac_link) 2>&5; } && test -s conftest${ac_exeext}; then
+if { (eval echo configure:107198: \"$ac_link\") 1>&5; (eval $ac_link) 2>&5; } && test -s conftest${ac_exeext}; then
   rm -rf conftest*
   eval "ac_cv_func_$ac_func=yes"
 else
@@ -107022,17 +107251,17 @@ for ac_hdr in stdarg.h
 do
 ac_safe=`echo "$ac_hdr" | sed 'y%./+-%__p_%'`
 echo $ac_n "checking for $ac_hdr""... $ac_c" 1>&6
-echo "configure:107026: checking for $ac_hdr" >&5
+echo "configure:107265: checking for $ac_hdr" >&5
 if eval "test \"`echo '$''{'ac_cv_header_$ac_safe'+set}'`\" = set"; then
   echo $ac_n "(cached) $ac_c" 1>&6
 else
   cat > conftest.$ac_ext <<EOF
-#line 107031 "configure"
+#line 107270 "configure"
 #include "confdefs.h"
 #include <$ac_hdr>
 EOF
 ac_try="$ac_cpp conftest.$ac_ext >/dev/null 2>conftest.out"
-{ (eval echo configure:107036: \"$ac_try\") 1>&5; (eval $ac_try) 2>&5; }
+{ (eval echo configure:107275: \"$ac_try\") 1>&5; (eval $ac_try) 2>&5; }
 ac_err=`grep -v '^ *+' conftest.out | grep -v "^conftest.${ac_ext}\$"`
 if test -z "$ac_err"; then
   rm -rf conftest*
@@ -107109,7 +107338,7 @@ if test "$TSRM_PTH" != "no"; then
   
 
 echo $ac_n "checking for GNU Pth""... $ac_c" 1>&6
-echo "configure:107113: checking for GNU Pth" >&5
+echo "configure:107352: checking for GNU Pth" >&5
 PTH_PREFIX="`$TSRM_PTH --prefix`"
 if test -z "$PTH_PREFIX"; then
   echo "$ac_t""Please check your Pth installation" 1>&6
@@ -107139,17 +107368,17 @@ elif test "$TSRM_ST" != "no"; then
 do
 ac_safe=`echo "$ac_hdr" | sed 'y%./+-%__p_%'`
 echo $ac_n "checking for $ac_hdr""... $ac_c" 1>&6
-echo "configure:107143: checking for $ac_hdr" >&5
+echo "configure:107382: checking for $ac_hdr" >&5
 if eval "test \"`echo '$''{'ac_cv_header_$ac_safe'+set}'`\" = set"; then
   echo $ac_n "(cached) $ac_c" 1>&6
 else
   cat > conftest.$ac_ext <<EOF
-#line 107148 "configure"
+#line 107387 "configure"
 #include "confdefs.h"
 #include <$ac_hdr>
 EOF
 ac_try="$ac_cpp conftest.$ac_ext >/dev/null 2>conftest.out"
-{ (eval echo configure:107153: \"$ac_try\") 1>&5; (eval $ac_try) 2>&5; }
+{ (eval echo configure:107392: \"$ac_try\") 1>&5; (eval $ac_try) 2>&5; }
 ac_err=`grep -v '^ *+' conftest.out | grep -v "^conftest.${ac_ext}\$"`
 if test -z "$ac_err"; then
   rm -rf conftest*
@@ -107179,7 +107408,7 @@ done
 
   LIBS="$LIBS -lst"
   echo $ac_n "checking for SGI's State Threads""... $ac_c" 1>&6
-echo "configure:107183: checking for SGI's State Threads" >&5
+echo "configure:107422: checking for SGI's State Threads" >&5
   echo "$ac_t""yes" 1>&6
   cat >> confdefs.h <<\EOF
 #define TSRM_ST 1
@@ -107218,7 +107447,7 @@ if test "$cross_compiling" = yes; then
 
 else
   cat > conftest.$ac_ext <<EOF
-#line 107222 "configure"
+#line 107461 "configure"
 #include "confdefs.h"
 
 #include <pthread.h>
@@ -107236,7 +107465,7 @@ int main() {
     return pthread_create(&thd, NULL, thread_routine, &data);
 } 
 EOF
-if { (eval echo configure:107240: \"$ac_link\") 1>&5; (eval $ac_link) 2>&5; } && test -s conftest${ac_exeext} && (./conftest; exit) 2>/dev/null
+if { (eval echo configure:107479: \"$ac_link\") 1>&5; (eval $ac_link) 2>&5; } && test -s conftest${ac_exeext} && (./conftest; exit) 2>/dev/null
 then
   
   pthreads_working=yes
@@ -107256,7 +107485,7 @@ fi
   CFLAGS=$save_CFLAGS
 
   echo $ac_n "checking for pthreads_cflags""... $ac_c" 1>&6
-echo "configure:107260: checking for pthreads_cflags" >&5
+echo "configure:107499: checking for pthreads_cflags" >&5
 if eval "test \"`echo '$''{'ac_cv_pthreads_cflags'+set}'`\" = set"; then
   echo $ac_n "(cached) $ac_c" 1>&6
 else
@@ -107278,7 +107507,7 @@ if test "$cross_compiling" = yes; then
 
 else
   cat > conftest.$ac_ext <<EOF
-#line 107282 "configure"
+#line 107521 "configure"
 #include "confdefs.h"
 
 #include <pthread.h>
@@ -107296,7 +107525,7 @@ int main() {
     return pthread_create(&thd, NULL, thread_routine, &data);
 } 
 EOF
-if { (eval echo configure:107300: \"$ac_link\") 1>&5; (eval $ac_link) 2>&5; } && test -s conftest${ac_exeext} && (./conftest; exit) 2>/dev/null
+if { (eval echo configure:107539: \"$ac_link\") 1>&5; (eval $ac_link) 2>&5; } && test -s conftest${ac_exeext} && (./conftest; exit) 2>/dev/null
 then
   
   pthreads_working=yes
@@ -107326,7 +107555,7 @@ fi
 echo "$ac_t""$ac_cv_pthreads_cflags" 1>&6
 
 echo $ac_n "checking for pthreads_lib""... $ac_c" 1>&6
-echo "configure:107330: checking for pthreads_lib" >&5
+echo "configure:107569: checking for pthreads_lib" >&5
 if eval "test \"`echo '$''{'ac_cv_pthreads_lib'+set}'`\" = set"; then
   echo $ac_n "(cached) $ac_c" 1>&6
 else
@@ -107348,7 +107577,7 @@ if test "$cross_compiling" = yes; then
 
 else
   cat > conftest.$ac_ext <<EOF
-#line 107352 "configure"
+#line 107591 "configure"
 #include "confdefs.h"
 
 #include <pthread.h>
@@ -107366,7 +107595,7 @@ int main() {
     return pthread_create(&thd, NULL, thread_routine, &data);
 } 
 EOF
-if { (eval echo configure:107370: \"$ac_link\") 1>&5; (eval $ac_link) 2>&5; } && test -s conftest${ac_exeext} && (./conftest; exit) 2>/dev/null
+if { (eval echo configure:107609: \"$ac_link\") 1>&5; (eval $ac_link) 2>&5; } && test -s conftest${ac_exeext} && (./conftest; exit) 2>/dev/null
 then
   
   pthreads_working=yes
@@ -107417,7 +107646,7 @@ EOF
 
 
   echo $ac_n "checking for POSIX threads""... $ac_c" 1>&6
-echo "configure:107421: checking for POSIX threads" >&5
+echo "configure:107660: checking for POSIX threads" >&5
   echo "$ac_t""yes" 1>&6
 fi
 
@@ -107606,58 +107835,6 @@ EOF
   ;;
 esac
 
-if test "$PHP_CLI" != "no"; then
-  PHP_CLI_TARGET="\$(SAPI_CLI_PATH)"
-  PHP_INSTALL_CLI_TARGET="install-cli"
-  
-  
-  case sapi/cli in
-  "") ac_srcdir="$abs_srcdir/"; unset ac_bdir; ac_inc="-I. -I$abs_srcdir" ;;
-  /*) ac_srcdir=`echo "sapi/cli"|cut -c 2-`"/"; ac_bdir=$ac_srcdir; ac_inc="-I$ac_bdir -I$abs_srcdir/$ac_bdir" ;;
-  *) ac_srcdir="$abs_srcdir/sapi/cli/"; ac_bdir="sapi/cli/"; ac_inc="-I$ac_bdir -I$ac_srcdir" ;;
-  esac
-  
-  
-
-  b_c_pre=$php_c_pre
-  b_cxx_pre=$php_cxx_pre
-  b_c_meta=$php_c_meta
-  b_cxx_meta=$php_cxx_meta
-  b_c_post=$php_c_post
-  b_cxx_post=$php_cxx_post
-  b_lo=$php_lo
-
-
-  old_IFS=$IFS
-  for ac_src in php_cli.c php_cli_readline.c; do
-  
-      IFS=.
-      set $ac_src
-      ac_obj=$1
-      IFS=$old_IFS
-      
-      PHP_CLI_OBJS="$PHP_CLI_OBJS $ac_bdir$ac_obj.lo"
-
-      case $ac_src in
-        *.c) ac_comp="$b_c_pre  $ac_inc $b_c_meta -c $ac_srcdir$ac_src -o $ac_bdir$ac_obj.$b_lo $b_c_post" ;;
-        *.s) ac_comp="$b_c_pre  $ac_inc $b_c_meta -c $ac_srcdir$ac_src -o $ac_bdir$ac_obj.$b_lo $b_c_post" ;;
-        *.S) ac_comp="$b_c_pre  $ac_inc $b_c_meta -c $ac_srcdir$ac_src -o $ac_bdir$ac_obj.$b_lo $b_c_post" ;;
-        *.cpp|*.cc|*.cxx) ac_comp="$b_cxx_pre  $ac_inc $b_cxx_meta -c $ac_srcdir$ac_src -o $ac_bdir$ac_obj.$b_lo $b_cxx_post" ;;
-      esac
-
-    cat >>Makefile.objects<<EOF
-$ac_bdir$ac_obj.lo: $ac_srcdir$ac_src
-	$ac_comp
-EOF
-  done
-
-
-  PHP_INSTALLED_SAPIS="cli $PHP_SAPI"
-  PHP_EXECUTABLE="\$(top_builddir)/\$(SAPI_CLI_PATH)"
-else
-  PHP_INSTALLED_SAPIS="$PHP_SAPI"
-fi
-
 
   
   PHP_VAR_SUBST="$PHP_VAR_SUBST PHP_INSTALLED_SAPIS"
@@ -107669,19 +107846,20 @@ fi
   PHP_VAR_SUBST="$PHP_VAR_SUBST PHP_EXECUTABLE"
 
 
-  PHP_VAR_SUBST="$PHP_VAR_SUBST PHP_CLI_TARGET"
-
 
   PHP_VAR_SUBST="$PHP_VAR_SUBST PHP_SAPI_OBJS"
 
 
-  PHP_VAR_SUBST="$PHP_VAR_SUBST PHP_CLI_OBJS"
+  PHP_VAR_SUBST="$PHP_VAR_SUBST PHP_BINARY_OBJS"
 
 
   PHP_VAR_SUBST="$PHP_VAR_SUBST PHP_GLOBAL_OBJS"
 
 
 
+  PHP_VAR_SUBST="$PHP_VAR_SUBST PHP_BINARIES"
+
+
   PHP_VAR_SUBST="$PHP_VAR_SUBST PHP_MODULES"
 
 
@@ -107971,7 +108149,7 @@ fi
 LDFLAGS="$LDFLAGS $PHP_AIX_LDFLAGS"
 
 case $host_alias in
-*darwin9*|*darwin10*|*darwin11*|*darwin12*)
+*darwin9*|*darwin10*|*darwin11*|*darwin12*|*darwin13*|*darwin14*|*darwin15*|*darwin16*)
   ac_cv_exeext=
   ;;
 esac
@@ -108053,7 +108231,7 @@ fi
 
 
 echo $ac_n "checking build system type""... $ac_c" 1>&6
-echo "configure:108057: checking build system type" >&5
+echo "configure:108245: checking build system type" >&5
 
 build_alias=$build
 case "$build_alias" in
@@ -108082,7 +108260,7 @@ ac_prog=ld
 if test "$GCC" = yes; then
   # Check if gcc -print-prog-name=ld gives a path.
   echo $ac_n "checking for ld used by $CC""... $ac_c" 1>&6
-echo "configure:108086: checking for ld used by $CC" >&5
+echo "configure:108274: checking for ld used by $CC" >&5
   case $host in
   *-*-mingw*)
     # gcc leaves a trailing carriage return which upsets mingw
@@ -108112,10 +108290,10 @@ echo "configure:108086: checking for ld used by $CC" >&5
   esac
 elif test "$with_gnu_ld" = yes; then
   echo $ac_n "checking for GNU ld""... $ac_c" 1>&6
-echo "configure:108116: checking for GNU ld" >&5
+echo "configure:108304: checking for GNU ld" >&5
 else
   echo $ac_n "checking for non-GNU ld""... $ac_c" 1>&6
-echo "configure:108119: checking for non-GNU ld" >&5
+echo "configure:108307: checking for non-GNU ld" >&5
 fi
 if eval "test \"`echo '$''{'lt_cv_path_LD'+set}'`\" = set"; then
   echo $ac_n "(cached) $ac_c" 1>&6
@@ -108154,7 +108332,7 @@ else
 fi
 test -z "$LD" && { echo "configure: error: no acceptable ld found in \$PATH" 1>&2; exit 1; }
 echo $ac_n "checking if the linker ($LD) is GNU ld""... $ac_c" 1>&6
-echo "configure:108158: checking if the linker ($LD) is GNU ld" >&5
+echo "configure:108346: checking if the linker ($LD) is GNU ld" >&5
 if eval "test \"`echo '$''{'lt_cv_prog_gnu_ld'+set}'`\" = set"; then
   echo $ac_n "(cached) $ac_c" 1>&6
 else
@@ -108174,7 +108352,7 @@ with_gnu_ld=$lt_cv_prog_gnu_ld
 
 
 echo $ac_n "checking for $LD option to reload object files""... $ac_c" 1>&6
-echo "configure:108178: checking for $LD option to reload object files" >&5
+echo "configure:108366: checking for $LD option to reload object files" >&5
 if eval "test \"`echo '$''{'lt_cv_ld_reload_flag'+set}'`\" = set"; then
   echo $ac_n "(cached) $ac_c" 1>&6
 else
@@ -108199,7 +108377,7 @@ case $host_os in
 esac
 
 echo $ac_n "checking for BSD-compatible nm""... $ac_c" 1>&6
-echo "configure:108203: checking for BSD-compatible nm" >&5
+echo "configure:108391: checking for BSD-compatible nm" >&5
 if eval "test \"`echo '$''{'lt_cv_path_NM'+set}'`\" = set"; then
   echo $ac_n "(cached) $ac_c" 1>&6
 else
@@ -108252,7 +108430,7 @@ echo "$ac_t""$lt_cv_path_NM" 1>&6
 NM="$lt_cv_path_NM"
 
 echo $ac_n "checking how to recognize dependent libraries""... $ac_c" 1>&6
-echo "configure:108256: checking how to recognize dependent libraries" >&5
+echo "configure:108444: checking how to recognize dependent libraries" >&5
 if eval "test \"`echo '$''{'lt_cv_deplibs_check_method'+set}'`\" = set"; then
   echo $ac_n "(cached) $ac_c" 1>&6
 else
@@ -108444,13 +108622,13 @@ deplibs_check_method=$lt_cv_deplibs_check_method
 test -z "$deplibs_check_method" && deplibs_check_method=unknown
 
 echo $ac_n "checking for object suffix""... $ac_c" 1>&6
-echo "configure:108448: checking for object suffix" >&5
+echo "configure:108636: checking for object suffix" >&5
 if eval "test \"`echo '$''{'ac_cv_objext'+set}'`\" = set"; then
   echo $ac_n "(cached) $ac_c" 1>&6
 else
   rm -f conftest*
 echo 'int i = 1;' > conftest.$ac_ext
-if { (eval echo configure:108454: \"$ac_compile\") 1>&5; (eval $ac_compile) 2>&5; }; then
+if { (eval echo configure:108642: \"$ac_compile\") 1>&5; (eval $ac_compile) 2>&5; }; then
   for ac_file in conftest.*; do
     case $ac_file in
     *.c) ;;
@@ -108470,7 +108648,7 @@ ac_objext=$ac_cv_objext
 
 
 echo $ac_n "checking for executable suffix""... $ac_c" 1>&6
-echo "configure:108474: checking for executable suffix" >&5
+echo "configure:108662: checking for executable suffix" >&5
 if eval "test \"`echo '$''{'ac_cv_exeext'+set}'`\" = set"; then
   echo $ac_n "(cached) $ac_c" 1>&6
 else
@@ -108480,7 +108658,7 @@ else
   rm -f conftest*
   echo 'int main () { return 0; }' > conftest.$ac_ext
   ac_cv_exeext=
-  if { (eval echo configure:108484: \"$ac_link\") 1>&5; (eval $ac_link) 2>&5; }; then
+  if { (eval echo configure:108672: \"$ac_link\") 1>&5; (eval $ac_link) 2>&5; }; then
     for file in conftest.*; do
       case $file in
       *.c | *.o | *.obj) ;;
@@ -108526,7 +108704,7 @@ case $host in
 ia64-*-hpux*)
   # Find out which ABI we are using.
   echo 'int i;' > conftest.$ac_ext
-  if { (eval echo configure:108530: \"$ac_compile\") 1>&5; (eval $ac_compile) 2>&5; }; then
+  if { (eval echo configure:108718: \"$ac_compile\") 1>&5; (eval $ac_compile) 2>&5; }; then
     case `/usr/bin/file conftest.$ac_objext` in
     *ELF-32*)
       HPUX_IA64_MODE="32"
@@ -108540,8 +108718,8 @@ ia64-*-hpux*)
   ;;
 *-*-irix6*)
   # Find out which ABI we are using.
-  echo '#line 108544 "configure"' > conftest.$ac_ext
-  if { (eval echo configure:108545: \"$ac_compile\") 1>&5; (eval $ac_compile) 2>&5; }; then
+  echo '#line 108732 "configure"' > conftest.$ac_ext
+  if { (eval echo configure:108733: \"$ac_compile\") 1>&5; (eval $ac_compile) 2>&5; }; then
    if test "$lt_cv_prog_gnu_ld" = yes; then
     case `/usr/bin/file conftest.$ac_objext` in
     *32-bit*)
@@ -108575,7 +108753,7 @@ x86_64-*kfreebsd*-gnu|x86_64-*linux*|ppc*-*linux*|powerpc*-*linux*| \
 s390*-*linux*|sparc*-*linux*)
   # Find out which ABI we are using.
   echo 'int i;' > conftest.$ac_ext
-  if { (eval echo configure:108579: \"$ac_compile\") 1>&5; (eval $ac_compile) 2>&5; }; then
+  if { (eval echo configure:108767: \"$ac_compile\") 1>&5; (eval $ac_compile) 2>&5; }; then
     case `/usr/bin/file conftest.o` in
     *32-bit*)
       case $host in
@@ -108625,7 +108803,7 @@ s390*-*linux*|sparc*-*linux*)
   SAVE_CFLAGS="$CFLAGS"
   CFLAGS="$CFLAGS -belf"
   echo $ac_n "checking whether the C compiler needs -belf""... $ac_c" 1>&6
-echo "configure:108629: checking whether the C compiler needs -belf" >&5
+echo "configure:108817: checking whether the C compiler needs -belf" >&5
 if eval "test \"`echo '$''{'lt_cv_cc_needs_belf'+set}'`\" = set"; then
   echo $ac_n "(cached) $ac_c" 1>&6
 else
@@ -108638,14 +108816,14 @@ ac_link='${CC-cc} -o conftest${ac_exeext} $CFLAGS $CPPFLAGS $LDFLAGS conftest.$a
 cross_compiling=$ac_cv_prog_cc_cross
 
      cat > conftest.$ac_ext <<EOF
-#line 108642 "configure"
+#line 108830 "configure"
 #include "confdefs.h"
 
 int main() {
 
 ; return 0; }
 EOF
-if { (eval echo configure:108649: \"$ac_link\") 1>&5; (eval $ac_link) 2>&5; } && test -s conftest${ac_exeext}; then
+if { (eval echo configure:108837: \"$ac_link\") 1>&5; (eval $ac_link) 2>&5; } && test -s conftest${ac_exeext}; then
   rm -rf conftest*
   lt_cv_cc_needs_belf=yes
 else
@@ -108673,7 +108851,7 @@ echo "$ac_t""$lt_cv_cc_needs_belf" 1>&6
 sparc*-*solaris*)
   # Find out which ABI we are using.
   echo 'int i;' > conftest.$ac_ext
-  if { (eval echo configure:108677: \"$ac_compile\") 1>&5; (eval $ac_compile) 2>&5; }; then
+  if { (eval echo configure:108865: \"$ac_compile\") 1>&5; (eval $ac_compile) 2>&5; }; then
     case `/usr/bin/file conftest.o` in
     *64-bit*)
       case $lt_cv_prog_gnu_ld in
@@ -108702,7 +108880,7 @@ if test -n "$CXX" && ( test "X$CXX" != "Xno" &&
     ( (test "X$CXX" = "Xg++" && `g++ -v >/dev/null 2>&1` ) ||
     (test "X$CXX" != "Xg++"))) ; then
   echo $ac_n "checking how to run the C++ preprocessor""... $ac_c" 1>&6
-echo "configure:108706: checking how to run the C++ preprocessor" >&5
+echo "configure:108894: checking how to run the C++ preprocessor" >&5
 if test -z "$CXXCPP"; then
 if eval "test \"`echo '$''{'ac_cv_prog_CXXCPP'+set}'`\" = set"; then
   echo $ac_n "(cached) $ac_c" 1>&6
@@ -108715,12 +108893,12 @@ ac_link='${CXX-g++} -o conftest${ac_exeext} $CXXFLAGS $CPPFLAGS $LDFLAGS conftes
 cross_compiling=$ac_cv_prog_cxx_cross
   CXXCPP="${CXX-g++} -E"
   cat > conftest.$ac_ext <<EOF
-#line 108719 "configure"
+#line 108907 "configure"
 #include "confdefs.h"
 #include <stdlib.h>
 EOF
 ac_try="$ac_cpp conftest.$ac_ext >/dev/null 2>conftest.out"
-{ (eval echo configure:108724: \"$ac_try\") 1>&5; (eval $ac_try) 2>&5; }
+{ (eval echo configure:108912: \"$ac_try\") 1>&5; (eval $ac_try) 2>&5; }
 ac_err=`grep -v '^ *+' conftest.out | grep -v "^conftest.${ac_ext}\$"`
 if test -z "$ac_err"; then
   :
@@ -108750,7 +108928,7 @@ fi
 # Autoconf 2.13's AC_OBJEXT and AC_EXEEXT macros only works for C compilers!
 # find the maximum length of command line arguments
 echo $ac_n "checking the maximum length of command line arguments""... $ac_c" 1>&6
-echo "configure:108754: checking the maximum length of command line arguments" >&5
+echo "configure:108942: checking the maximum length of command line arguments" >&5
 if eval "test \"`echo '$''{'lt_cv_sys_max_cmd_len'+set}'`\" = set"; then
   echo $ac_n "(cached) $ac_c" 1>&6
 else
@@ -108872,7 +109050,7 @@ fi
 
 # Check for command to grab the raw symbol name followed by C symbol from nm.
 echo $ac_n "checking command to parse $NM output from $compiler object""... $ac_c" 1>&6
-echo "configure:108876: checking command to parse $NM output from $compiler object" >&5
+echo "configure:109064: checking command to parse $NM output from $compiler object" >&5
 if eval "test \"`echo '$''{'lt_cv_sys_global_symbol_pipe'+set}'`\" = set"; then
   echo $ac_n "(cached) $ac_c" 1>&6
 else
@@ -108976,10 +109154,10 @@ void nm_test_func(){}
 int main(){nm_test_var='a';nm_test_func();return(0);}
 EOF
 
-  if { (eval echo configure:108980: \"$ac_compile\") 1>&5; (eval $ac_compile) 2>&5; }; then
+  if { (eval echo configure:109168: \"$ac_compile\") 1>&5; (eval $ac_compile) 2>&5; }; then
     # Now try to grab the symbols.
     nlist=conftest.nm
-    if { (eval echo configure:108983: \"$NM conftest.$ac_objext \| $lt_cv_sys_global_symbol_pipe \> $nlist\") 1>&5; (eval $NM conftest.$ac_objext \| $lt_cv_sys_global_symbol_pipe \> $nlist) 2>&5; } && test -s "$nlist"; then
+    if { (eval echo configure:109171: \"$NM conftest.$ac_objext \| $lt_cv_sys_global_symbol_pipe \> $nlist\") 1>&5; (eval $NM conftest.$ac_objext \| $lt_cv_sys_global_symbol_pipe \> $nlist) 2>&5; } && test -s "$nlist"; then
       # Try sorting and uniquifying the output.
       if sort "$nlist" | uniq > "$nlist"T; then
 	mv -f "$nlist"T "$nlist"
@@ -109030,7 +109208,7 @@ EOF
 	  lt_save_CFLAGS="$CFLAGS"
 	  LIBS="conftstm.$ac_objext"
 	  CFLAGS="$CFLAGS$lt_prog_compiler_no_builtin_flag"
-	  if { (eval echo configure:109034: \"$ac_link\") 1>&5; (eval $ac_link) 2>&5; } && test -s conftest${ac_exeext}; then
+	  if { (eval echo configure:109222: \"$ac_link\") 1>&5; (eval $ac_link) 2>&5; } && test -s conftest${ac_exeext}; then
 	    pipe_works=yes
 	  fi
 	  LIBS="$lt_save_LIBS"
@@ -109070,7 +109248,7 @@ else
 fi
 
 echo $ac_n "checking for objdir""... $ac_c" 1>&6
-echo "configure:109074: checking for objdir" >&5
+echo "configure:109262: checking for objdir" >&5
 if eval "test \"`echo '$''{'lt_cv_objdir'+set}'`\" = set"; then
   echo $ac_n "(cached) $ac_c" 1>&6
 else
@@ -109136,7 +109314,7 @@ with_gnu_ld="$lt_cv_prog_gnu_ld"
 # Extract the first word of "${ac_tool_prefix}ar", so it can be a program name with args.
 set dummy ${ac_tool_prefix}ar; ac_word=$2
 echo $ac_n "checking for $ac_word""... $ac_c" 1>&6
-echo "configure:109140: checking for $ac_word" >&5
+echo "configure:109328: checking for $ac_word" >&5
 if eval "test \"`echo '$''{'ac_cv_prog_AR'+set}'`\" = set"; then
   echo $ac_n "(cached) $ac_c" 1>&6
 else
@@ -109168,7 +109346,7 @@ if test -n "$ac_tool_prefix"; then
   # Extract the first word of "ar", so it can be a program name with args.
 set dummy ar; ac_word=$2
 echo $ac_n "checking for $ac_word""... $ac_c" 1>&6
-echo "configure:109172: checking for $ac_word" >&5
+echo "configure:109360: checking for $ac_word" >&5
 if eval "test \"`echo '$''{'ac_cv_prog_AR'+set}'`\" = set"; then
   echo $ac_n "(cached) $ac_c" 1>&6
 else
@@ -109203,7 +109381,7 @@ fi
 # Extract the first word of "${ac_tool_prefix}ranlib", so it can be a program name with args.
 set dummy ${ac_tool_prefix}ranlib; ac_word=$2
 echo $ac_n "checking for $ac_word""... $ac_c" 1>&6
-echo "configure:109207: checking for $ac_word" >&5
+echo "configure:109395: checking for $ac_word" >&5
 if eval "test \"`echo '$''{'ac_cv_prog_RANLIB'+set}'`\" = set"; then
   echo $ac_n "(cached) $ac_c" 1>&6
 else
@@ -109235,7 +109413,7 @@ if test -n "$ac_tool_prefix"; then
   # Extract the first word of "ranlib", so it can be a program name with args.
 set dummy ranlib; ac_word=$2
 echo $ac_n "checking for $ac_word""... $ac_c" 1>&6
-echo "configure:109239: checking for $ac_word" >&5
+echo "configure:109427: checking for $ac_word" >&5
 if eval "test \"`echo '$''{'ac_cv_prog_RANLIB'+set}'`\" = set"; then
   echo $ac_n "(cached) $ac_c" 1>&6
 else
@@ -109270,7 +109448,7 @@ fi
 # Extract the first word of "${ac_tool_prefix}strip", so it can be a program name with args.
 set dummy ${ac_tool_prefix}strip; ac_word=$2
 echo $ac_n "checking for $ac_word""... $ac_c" 1>&6
-echo "configure:109274: checking for $ac_word" >&5
+echo "configure:109462: checking for $ac_word" >&5
 if eval "test \"`echo '$''{'ac_cv_prog_STRIP'+set}'`\" = set"; then
   echo $ac_n "(cached) $ac_c" 1>&6
 else
@@ -109302,7 +109480,7 @@ if test -n "$ac_tool_prefix"; then
   # Extract the first word of "strip", so it can be a program name with args.
 set dummy strip; ac_word=$2
 echo $ac_n "checking for $ac_word""... $ac_c" 1>&6
-echo "configure:109306: checking for $ac_word" >&5
+echo "configure:109494: checking for $ac_word" >&5
 if eval "test \"`echo '$''{'ac_cv_prog_STRIP'+set}'`\" = set"; then
   echo $ac_n "(cached) $ac_c" 1>&6
 else
@@ -109389,7 +109567,7 @@ case $deplibs_check_method in
 file_magic*)
   if test "$file_magic_cmd" = '$MAGIC_CMD'; then
     echo $ac_n "checking for ${ac_tool_prefix}file""... $ac_c" 1>&6
-echo "configure:109393: checking for ${ac_tool_prefix}file" >&5
+echo "configure:109581: checking for ${ac_tool_prefix}file" >&5
 if eval "test \"`echo '$''{'lt_cv_path_MAGIC_CMD'+set}'`\" = set"; then
   echo $ac_n "(cached) $ac_c" 1>&6
 else
@@ -109449,7 +109627,7 @@ fi
 if test -z "$lt_cv_path_MAGIC_CMD"; then
   if test -n "$ac_tool_prefix"; then
     echo $ac_n "checking for file""... $ac_c" 1>&6
-echo "configure:109453: checking for file" >&5
+echo "configure:109641: checking for file" >&5
 if eval "test \"`echo '$''{'lt_cv_path_MAGIC_CMD'+set}'`\" = set"; then
   echo $ac_n "(cached) $ac_c" 1>&6
 else
@@ -109521,7 +109699,7 @@ esac
     # Extract the first word of "${ac_tool_prefix}dsymutil", so it can be a program name with args.
 set dummy ${ac_tool_prefix}dsymutil; ac_word=$2
 echo $ac_n "checking for $ac_word""... $ac_c" 1>&6
-echo "configure:109525: checking for $ac_word" >&5
+echo "configure:109713: checking for $ac_word" >&5
 if eval "test \"`echo '$''{'ac_cv_prog_DSYMUTIL'+set}'`\" = set"; then
   echo $ac_n "(cached) $ac_c" 1>&6
 else
@@ -109553,7 +109731,7 @@ if test -n "$ac_tool_prefix"; then
   # Extract the first word of "dsymutil", so it can be a program name with args.
 set dummy dsymutil; ac_word=$2
 echo $ac_n "checking for $ac_word""... $ac_c" 1>&6
-echo "configure:109557: checking for $ac_word" >&5
+echo "configure:109745: checking for $ac_word" >&5
 if eval "test \"`echo '$''{'ac_cv_prog_DSYMUTIL'+set}'`\" = set"; then
   echo $ac_n "(cached) $ac_c" 1>&6
 else
@@ -109588,7 +109766,7 @@ fi
     # Extract the first word of "${ac_tool_prefix}nmedit", so it can be a program name with args.
 set dummy ${ac_tool_prefix}nmedit; ac_word=$2
 echo $ac_n "checking for $ac_word""... $ac_c" 1>&6
-echo "configure:109592: checking for $ac_word" >&5
+echo "configure:109780: checking for $ac_word" >&5
 if eval "test \"`echo '$''{'ac_cv_prog_NMEDIT'+set}'`\" = set"; then
   echo $ac_n "(cached) $ac_c" 1>&6
 else
@@ -109620,7 +109798,7 @@ if test -n "$ac_tool_prefix"; then
   # Extract the first word of "nmedit", so it can be a program name with args.
 set dummy nmedit; ac_word=$2
 echo $ac_n "checking for $ac_word""... $ac_c" 1>&6
-echo "configure:109624: checking for $ac_word" >&5
+echo "configure:109812: checking for $ac_word" >&5
 if eval "test \"`echo '$''{'ac_cv_prog_NMEDIT'+set}'`\" = set"; then
   echo $ac_n "(cached) $ac_c" 1>&6
 else
@@ -109654,7 +109832,7 @@ fi
 
 
     echo $ac_n "checking for -single_module linker flag""... $ac_c" 1>&6
-echo "configure:109658: checking for -single_module linker flag" >&5
+echo "configure:109846: checking for -single_module linker flag" >&5
 if eval "test \"`echo '$''{'lt_cv_apple_cc_single_mod'+set}'`\" = set"; then
   echo $ac_n "(cached) $ac_c" 1>&6
 else
@@ -109677,7 +109855,7 @@ fi
 
 echo "$ac_t""$lt_cv_apple_cc_single_mod" 1>&6
     echo $ac_n "checking for -exported_symbols_list linker flag""... $ac_c" 1>&6
-echo "configure:109681: checking for -exported_symbols_list linker flag" >&5
+echo "configure:109869: checking for -exported_symbols_list linker flag" >&5
 if eval "test \"`echo '$''{'lt_cv_ld_exported_symbols_list'+set}'`\" = set"; then
   echo $ac_n "(cached) $ac_c" 1>&6
 else
@@ -109687,12 +109865,12 @@ else
       LDFLAGS="$LDFLAGS -Wl,-exported_symbols_list,conftest.sym"
       
 cat > conftest.$ac_ext <<EOF
-#line 109691 "configure"
+#line 109879 "configure"
 #include "confdefs.h"
 int main() {
 ; return 0; }
 EOF
-if { (eval echo configure:109696: \"$ac_link\") 1>&5; (eval $ac_link) 2>&5; } && test -s conftest${ac_exeext}; then
+if { (eval echo configure:109884: \"$ac_link\") 1>&5; (eval $ac_link) 2>&5; } && test -s conftest${ac_exeext}; then
  lt_cv_ld_exported_symbols_list=yes
   rm -rf conftest*
 else
@@ -109826,7 +110004,7 @@ if test "$GCC" = yes; then
 
   
 echo $ac_n "checking if $compiler supports -fno-rtti -fno-exceptions""... $ac_c" 1>&6
-echo "configure:109830: checking if $compiler supports -fno-rtti -fno-exceptions" >&5
+echo "configure:110018: checking if $compiler supports -fno-rtti -fno-exceptions" >&5
 if eval "test \"`echo '$''{'lt_cv_prog_compiler_rtti_exceptions'+set}'`\" = set"; then
   echo $ac_n "(cached) $ac_c" 1>&6
 else
@@ -109843,11 +110021,11 @@ else
    -e 's:.*FLAGS}\{0,1\} :&$lt_compiler_flag :; t' \
    -e 's: [^ ]*conftest\.: $lt_compiler_flag&:; t' \
    -e 's:$: $lt_compiler_flag:'`
-   (eval echo "\"configure:109847: $lt_compile\"" >&5)
+   (eval echo "\"configure:110035: $lt_compile\"" >&5)
    (eval "$lt_compile" 2>conftest.err)
    ac_status=$?
    cat conftest.err >&5
-   echo "configure:109851: \$? = $ac_status" >&5
+   echo "configure:110039: \$? = $ac_status" >&5
    if (exit $ac_status) && test -s "$ac_outfile"; then
      # The compiler can only warn and ignore the option if not recognized
      # So say no if there are warnings other than the usual output.
@@ -109876,7 +110054,7 @@ lt_prog_compiler_pic=
 lt_prog_compiler_static=
 
 echo $ac_n "checking for $compiler option to produce PIC""... $ac_c" 1>&6
-echo "configure:109880: checking for $compiler option to produce PIC" >&5
+echo "configure:110068: checking for $compiler option to produce PIC" >&5
  
   if test "$GCC" = yes; then
     lt_prog_compiler_wl='-Wl,'
@@ -110123,7 +110301,7 @@ echo "$ac_t""$lt_prog_compiler_pic" 1>&6
 if test -n "$lt_prog_compiler_pic"; then
   
 echo $ac_n "checking if $compiler PIC flag $lt_prog_compiler_pic works""... $ac_c" 1>&6
-echo "configure:110127: checking if $compiler PIC flag $lt_prog_compiler_pic works" >&5
+echo "configure:110315: checking if $compiler PIC flag $lt_prog_compiler_pic works" >&5
 if eval "test \"`echo '$''{'lt_cv_prog_compiler_pic_works'+set}'`\" = set"; then
   echo $ac_n "(cached) $ac_c" 1>&6
 else
@@ -110140,11 +110318,11 @@ else
    -e 's:.*FLAGS}\{0,1\} :&$lt_compiler_flag :; t' \
    -e 's: [^ ]*conftest\.: $lt_compiler_flag&:; t' \
    -e 's:$: $lt_compiler_flag:'`
-   (eval echo "\"configure:110144: $lt_compile\"" >&5)
+   (eval echo "\"configure:110332: $lt_compile\"" >&5)
    (eval "$lt_compile" 2>conftest.err)
    ac_status=$?
    cat conftest.err >&5
-   echo "configure:110148: \$? = $ac_status" >&5
+   echo "configure:110336: \$? = $ac_status" >&5
    if (exit $ac_status) && test -s "$ac_outfile"; then
      # The compiler can only warn and ignore the option if not recognized
      # So say no if there are warnings other than the usual output.
@@ -110186,7 +110364,7 @@ esac
 #
 wl=$lt_prog_compiler_wl eval lt_tmp_static_flag=\"$lt_prog_compiler_static\"
 echo $ac_n "checking if $compiler static flag $lt_tmp_static_flag works""... $ac_c" 1>&6
-echo "configure:110190: checking if $compiler static flag $lt_tmp_static_flag works" >&5
+echo "configure:110378: checking if $compiler static flag $lt_tmp_static_flag works" >&5
 if eval "test \"`echo '$''{'lt_cv_prog_compiler_static_works'+set}'`\" = set"; then
   echo $ac_n "(cached) $ac_c" 1>&6
 else
@@ -110224,7 +110402,7 @@ fi
 
 
 echo $ac_n "checking if $compiler supports -c -o file.$ac_objext""... $ac_c" 1>&6
-echo "configure:110228: checking if $compiler supports -c -o file.$ac_objext" >&5
+echo "configure:110416: checking if $compiler supports -c -o file.$ac_objext" >&5
 if eval "test \"`echo '$''{'lt_cv_prog_compiler_c_o'+set}'`\" = set"; then
   echo $ac_n "(cached) $ac_c" 1>&6
 else
@@ -110244,11 +110422,11 @@ else
    -e 's:.*FLAGS}\{0,1\} :&$lt_compiler_flag :; t' \
    -e 's: [^ ]*conftest\.: $lt_compiler_flag&:; t' \
    -e 's:$: $lt_compiler_flag:'`
-   (eval echo "\"configure:110248: $lt_compile\"" >&5)
+   (eval echo "\"configure:110436: $lt_compile\"" >&5)
    (eval "$lt_compile" 2>out/conftest.err)
    ac_status=$?
    cat out/conftest.err >&5
-   echo "configure:110252: \$? = $ac_status" >&5
+   echo "configure:110440: \$? = $ac_status" >&5
    if (exit $ac_status) && test -s out/conftest2.$ac_objext
    then
      # The compiler can only warn and ignore the option if not recognized
@@ -110278,7 +110456,7 @@ hard_links="nottested"
 if test "$lt_cv_prog_compiler_c_o" = no && test "$need_locks" != no; then
   # do not overwrite the value of need_locks provided by the user
   echo $ac_n "checking if we can lock with hard links""... $ac_c" 1>&6
-echo "configure:110282: checking if we can lock with hard links" >&5
+echo "configure:110470: checking if we can lock with hard links" >&5
   hard_links=yes
   $rm conftest*
   ln conftest.a conftest.b 2>/dev/null && hard_links=no
@@ -110295,7 +110473,7 @@ else
 fi
 
 echo $ac_n "checking whether the $compiler linker ($LD) supports shared libraries""... $ac_c" 1>&6
-echo "configure:110299: checking whether the $compiler linker ($LD) supports shared libraries" >&5
+echo "configure:110487: checking whether the $compiler linker ($LD) supports shared libraries" >&5
 
   runpath_var=
   allow_undefined_flag=
@@ -110706,12 +110884,12 @@ _LT_EOF
        # Determine the default libpath from the value encoded in an empty executable.
        
 cat > conftest.$ac_ext <<EOF
-#line 110710 "configure"
+#line 110898 "configure"
 #include "confdefs.h"
 int main() {
 ; return 0; }
 EOF
-if { (eval echo configure:110715: \"$ac_link\") 1>&5; (eval $ac_link) 2>&5; } && test -s conftest${ac_exeext}; then
+if { (eval echo configure:110903: \"$ac_link\") 1>&5; (eval $ac_link) 2>&5; } && test -s conftest${ac_exeext}; then
  
 lt_aix_libpath_sed='
     /Import File Strings/,/^$/ {
@@ -110744,12 +110922,12 @@ if test -z "$aix_libpath"; then aix_libpath="/usr/lib:/lib"; fi
 	 # Determine the default libpath from the value encoded in an empty executable.
 	 
 cat > conftest.$ac_ext <<EOF
-#line 110748 "configure"
+#line 110936 "configure"
 #include "confdefs.h"
 int main() {
 ; return 0; }
 EOF
-if { (eval echo configure:110753: \"$ac_link\") 1>&5; (eval $ac_link) 2>&5; } && test -s conftest${ac_exeext}; then
+if { (eval echo configure:110941: \"$ac_link\") 1>&5; (eval $ac_link) 2>&5; } && test -s conftest${ac_exeext}; then
  
 lt_aix_libpath_sed='
     /Import File Strings/,/^$/ {
@@ -111239,11 +111417,11 @@ x|xyes)
       # systems, -lgcc has to come before -lc. If gcc already passes -lc
       # to ld, don't add -lc before -lgcc.
       echo $ac_n "checking whether -lc should be explicitly linked in""... $ac_c" 1>&6
-echo "configure:111243: checking whether -lc should be explicitly linked in" >&5
+echo "configure:111431: checking whether -lc should be explicitly linked in" >&5
       $rm conftest*
       echo "$lt_simple_compile_test_code" > conftest.$ac_ext
 
-      if { (eval echo configure:111247: \"$ac_compile\") 1>&5; (eval $ac_compile) 2>&5; } 2>conftest.err; then
+      if { (eval echo configure:111435: \"$ac_compile\") 1>&5; (eval $ac_compile) 2>&5; } 2>conftest.err; then
         soname=conftest
         lib=conftest
         libobjs=conftest.$ac_objext
@@ -111257,7 +111435,7 @@ echo "configure:111243: checking whether -lc should be explicitly linked in" >&5
         libname=conftest
         lt_save_allow_undefined_flag=$allow_undefined_flag
         allow_undefined_flag=
-        if { (eval echo configure:111261: \"$archive_cmds 2\>\&1 \| grep \" -lc \" \>/dev/null 2\>\&1\") 1>&5; (eval $archive_cmds 2\>\&1 \| grep \" -lc \" \>/dev/null 2\>\&1) 2>&5; }
+        if { (eval echo configure:111449: \"$archive_cmds 2\>\&1 \| grep \" -lc \" \>/dev/null 2\>\&1\") 1>&5; (eval $archive_cmds 2\>\&1 \| grep \" -lc \" \>/dev/null 2\>\&1) 2>&5; }
         then
 	  archive_cmds_need_lc=no
         else
@@ -111276,7 +111454,7 @@ echo "configure:111243: checking whether -lc should be explicitly linked in" >&5
 esac
 
 echo $ac_n "checking dynamic linker characteristics""... $ac_c" 1>&6
-echo "configure:111280: checking dynamic linker characteristics" >&5
+echo "configure:111468: checking dynamic linker characteristics" >&5
 library_names_spec=
 libname_spec='lib$name'
 soname_spec=
@@ -111901,7 +112079,7 @@ if test "$GCC" = yes; then
 fi
 
 echo $ac_n "checking how to hardcode library paths into programs""... $ac_c" 1>&6
-echo "configure:111905: checking how to hardcode library paths into programs" >&5
+echo "configure:112093: checking how to hardcode library paths into programs" >&5
 hardcode_action=
 if test -n "$hardcode_libdir_flag_spec" || \
    test -n "$runpath_var" || \
@@ -111939,7 +112117,7 @@ fi
 striplib=
 old_striplib=
 echo $ac_n "checking whether stripping libraries is possible""... $ac_c" 1>&6
-echo "configure:111943: checking whether stripping libraries is possible" >&5
+echo "configure:112131: checking whether stripping libraries is possible" >&5
 if test -n "$STRIP" && $STRIP -V 2>&1 | grep "GNU strip" >/dev/null; then
   test -z "$old_striplib" && old_striplib="$STRIP --strip-debug"
   test -z "$striplib" && striplib="$STRIP --strip-unneeded"
@@ -111990,7 +112168,7 @@ else
   darwin*)
   # if libdl is installed we need to link against it
     echo $ac_n "checking for dlopen in -ldl""... $ac_c" 1>&6
-echo "configure:111994: checking for dlopen in -ldl" >&5
+echo "configure:112182: checking for dlopen in -ldl" >&5
 ac_lib_var=`echo dl'_'dlopen | sed 'y%./+-%__p_%'`
 if eval "test \"`echo '$''{'ac_cv_lib_$ac_lib_var'+set}'`\" = set"; then
   echo $ac_n "(cached) $ac_c" 1>&6
@@ -111998,7 +112176,7 @@ else
   ac_save_LIBS="$LIBS"
 LIBS="-ldl  $LIBS"
 cat > conftest.$ac_ext <<EOF
-#line 112002 "configure"
+#line 112190 "configure"
 #include "confdefs.h"
 /* Override any gcc2 internal prototype to avoid an error.  */
 /* We use char because int might match the return type of a gcc2
@@ -112009,7 +112187,7 @@ int main() {
 dlopen()
 ; return 0; }
 EOF
-if { (eval echo configure:112013: \"$ac_link\") 1>&5; (eval $ac_link) 2>&5; } && test -s conftest${ac_exeext}; then
+if { (eval echo configure:112201: \"$ac_link\") 1>&5; (eval $ac_link) 2>&5; } && test -s conftest${ac_exeext}; then
   rm -rf conftest*
   eval "ac_cv_lib_$ac_lib_var=yes"
 else
@@ -112038,12 +112216,12 @@ fi
 
   *)
     echo $ac_n "checking for shl_load""... $ac_c" 1>&6
-echo "configure:112042: checking for shl_load" >&5
+echo "configure:112230: checking for shl_load" >&5
 if eval "test \"`echo '$''{'ac_cv_func_shl_load'+set}'`\" = set"; then
   echo $ac_n "(cached) $ac_c" 1>&6
 else
   cat > conftest.$ac_ext <<EOF
-#line 112047 "configure"
+#line 112235 "configure"
 #include "confdefs.h"
 /* System header to define __stub macros and hopefully few prototypes,
     which can conflict with char shl_load(); below.  */
@@ -112066,7 +112244,7 @@ shl_load();
 
 ; return 0; }
 EOF
-if { (eval echo configure:112070: \"$ac_link\") 1>&5; (eval $ac_link) 2>&5; } && test -s conftest${ac_exeext}; then
+if { (eval echo configure:112258: \"$ac_link\") 1>&5; (eval $ac_link) 2>&5; } && test -s conftest${ac_exeext}; then
   rm -rf conftest*
   eval "ac_cv_func_shl_load=yes"
 else
@@ -112084,7 +112262,7 @@ if eval "test \"`echo '$ac_cv_func_'shl_load`\" = yes"; then
 else
   echo "$ac_t""no" 1>&6
 echo $ac_n "checking for shl_load in -ldld""... $ac_c" 1>&6
-echo "configure:112088: checking for shl_load in -ldld" >&5
+echo "configure:112276: checking for shl_load in -ldld" >&5
 ac_lib_var=`echo dld'_'shl_load | sed 'y%./+-%__p_%'`
 if eval "test \"`echo '$''{'ac_cv_lib_$ac_lib_var'+set}'`\" = set"; then
   echo $ac_n "(cached) $ac_c" 1>&6
@@ -112092,7 +112270,7 @@ else
   ac_save_LIBS="$LIBS"
 LIBS="-ldld  $LIBS"
 cat > conftest.$ac_ext <<EOF
-#line 112096 "configure"
+#line 112284 "configure"
 #include "confdefs.h"
 /* Override any gcc2 internal prototype to avoid an error.  */
 /* We use char because int might match the return type of a gcc2
@@ -112103,7 +112281,7 @@ int main() {
 shl_load()
 ; return 0; }
 EOF
-if { (eval echo configure:112107: \"$ac_link\") 1>&5; (eval $ac_link) 2>&5; } && test -s conftest${ac_exeext}; then
+if { (eval echo configure:112295: \"$ac_link\") 1>&5; (eval $ac_link) 2>&5; } && test -s conftest${ac_exeext}; then
   rm -rf conftest*
   eval "ac_cv_lib_$ac_lib_var=yes"
 else
@@ -112122,12 +112300,12 @@ if eval "test \"`echo '$ac_cv_lib_'$ac_lib_var`\" = yes"; then
 else
   echo "$ac_t""no" 1>&6
 echo $ac_n "checking for dlopen""... $ac_c" 1>&6
-echo "configure:112126: checking for dlopen" >&5
+echo "configure:112314: checking for dlopen" >&5
 if eval "test \"`echo '$''{'ac_cv_func_dlopen'+set}'`\" = set"; then
   echo $ac_n "(cached) $ac_c" 1>&6
 else
   cat > conftest.$ac_ext <<EOF
-#line 112131 "configure"
+#line 112319 "configure"
 #include "confdefs.h"
 /* System header to define __stub macros and hopefully few prototypes,
     which can conflict with char dlopen(); below.  */
@@ -112150,7 +112328,7 @@ dlopen();
 
 ; return 0; }
 EOF
-if { (eval echo configure:112154: \"$ac_link\") 1>&5; (eval $ac_link) 2>&5; } && test -s conftest${ac_exeext}; then
+if { (eval echo configure:112342: \"$ac_link\") 1>&5; (eval $ac_link) 2>&5; } && test -s conftest${ac_exeext}; then
   rm -rf conftest*
   eval "ac_cv_func_dlopen=yes"
 else
@@ -112168,7 +112346,7 @@ if eval "test \"`echo '$ac_cv_func_'dlopen`\" = yes"; then
 else
   echo "$ac_t""no" 1>&6
 echo $ac_n "checking for dlopen in -ldl""... $ac_c" 1>&6
-echo "configure:112172: checking for dlopen in -ldl" >&5
+echo "configure:112360: checking for dlopen in -ldl" >&5
 ac_lib_var=`echo dl'_'dlopen | sed 'y%./+-%__p_%'`
 if eval "test \"`echo '$''{'ac_cv_lib_$ac_lib_var'+set}'`\" = set"; then
   echo $ac_n "(cached) $ac_c" 1>&6
@@ -112176,7 +112354,7 @@ else
   ac_save_LIBS="$LIBS"
 LIBS="-ldl  $LIBS"
 cat > conftest.$ac_ext <<EOF
-#line 112180 "configure"
+#line 112368 "configure"
 #include "confdefs.h"
 /* Override any gcc2 internal prototype to avoid an error.  */
 /* We use char because int might match the return type of a gcc2
@@ -112187,7 +112365,7 @@ int main() {
 dlopen()
 ; return 0; }
 EOF
-if { (eval echo configure:112191: \"$ac_link\") 1>&5; (eval $ac_link) 2>&5; } && test -s conftest${ac_exeext}; then
+if { (eval echo configure:112379: \"$ac_link\") 1>&5; (eval $ac_link) 2>&5; } && test -s conftest${ac_exeext}; then
   rm -rf conftest*
   eval "ac_cv_lib_$ac_lib_var=yes"
 else
@@ -112206,7 +112384,7 @@ if eval "test \"`echo '$ac_cv_lib_'$ac_lib_var`\" = yes"; then
 else
   echo "$ac_t""no" 1>&6
 echo $ac_n "checking for dlopen in -lsvld""... $ac_c" 1>&6
-echo "configure:112210: checking for dlopen in -lsvld" >&5
+echo "configure:112398: checking for dlopen in -lsvld" >&5
 ac_lib_var=`echo svld'_'dlopen | sed 'y%./+-%__p_%'`
 if eval "test \"`echo '$''{'ac_cv_lib_$ac_lib_var'+set}'`\" = set"; then
   echo $ac_n "(cached) $ac_c" 1>&6
@@ -112214,7 +112392,7 @@ else
   ac_save_LIBS="$LIBS"
 LIBS="-lsvld  $LIBS"
 cat > conftest.$ac_ext <<EOF
-#line 112218 "configure"
+#line 112406 "configure"
 #include "confdefs.h"
 /* Override any gcc2 internal prototype to avoid an error.  */
 /* We use char because int might match the return type of a gcc2
@@ -112225,7 +112403,7 @@ int main() {
 dlopen()
 ; return 0; }
 EOF
-if { (eval echo configure:112229: \"$ac_link\") 1>&5; (eval $ac_link) 2>&5; } && test -s conftest${ac_exeext}; then
+if { (eval echo configure:112417: \"$ac_link\") 1>&5; (eval $ac_link) 2>&5; } && test -s conftest${ac_exeext}; then
   rm -rf conftest*
   eval "ac_cv_lib_$ac_lib_var=yes"
 else
@@ -112244,7 +112422,7 @@ if eval "test \"`echo '$ac_cv_lib_'$ac_lib_var`\" = yes"; then
 else
   echo "$ac_t""no" 1>&6
 echo $ac_n "checking for dld_link in -ldld""... $ac_c" 1>&6
-echo "configure:112248: checking for dld_link in -ldld" >&5
+echo "configure:112436: checking for dld_link in -ldld" >&5
 ac_lib_var=`echo dld'_'dld_link | sed 'y%./+-%__p_%'`
 if eval "test \"`echo '$''{'ac_cv_lib_$ac_lib_var'+set}'`\" = set"; then
   echo $ac_n "(cached) $ac_c" 1>&6
@@ -112252,7 +112430,7 @@ else
   ac_save_LIBS="$LIBS"
 LIBS="-ldld  $LIBS"
 cat > conftest.$ac_ext <<EOF
-#line 112256 "configure"
+#line 112444 "configure"
 #include "confdefs.h"
 /* Override any gcc2 internal prototype to avoid an error.  */
 /* We use char because int might match the return type of a gcc2
@@ -112263,7 +112441,7 @@ int main() {
 dld_link()
 ; return 0; }
 EOF
-if { (eval echo configure:112267: \"$ac_link\") 1>&5; (eval $ac_link) 2>&5; } && test -s conftest${ac_exeext}; then
+if { (eval echo configure:112455: \"$ac_link\") 1>&5; (eval $ac_link) 2>&5; } && test -s conftest${ac_exeext}; then
   rm -rf conftest*
   eval "ac_cv_lib_$ac_lib_var=yes"
 else
@@ -112319,7 +112497,7 @@ fi
     LIBS="$lt_cv_dlopen_libs $LIBS"
 
     echo $ac_n "checking whether a program can dlopen itself""... $ac_c" 1>&6
-echo "configure:112323: checking whether a program can dlopen itself" >&5
+echo "configure:112511: checking whether a program can dlopen itself" >&5
 if eval "test \"`echo '$''{'lt_cv_dlopen_self'+set}'`\" = set"; then
   echo $ac_n "(cached) $ac_c" 1>&6
 else
@@ -112329,7 +112507,7 @@ else
   lt_dlunknown=0; lt_dlno_uscore=1; lt_dlneed_uscore=2
   lt_status=$lt_dlunknown
   cat > conftest.$ac_ext <<EOF
-#line 112333 "configure"
+#line 112521 "configure"
 #include "confdefs.h"
 
 #if HAVE_DLFCN_H
@@ -112392,7 +112570,7 @@ int main ()
     exit (status);
 }
 EOF
-  if { (eval echo configure:112396: \"$ac_link\") 1>&5; (eval $ac_link) 2>&5; } && test -s conftest${ac_exeext} 2>/dev/null; then
+  if { (eval echo configure:112584: \"$ac_link\") 1>&5; (eval $ac_link) 2>&5; } && test -s conftest${ac_exeext} 2>/dev/null; then
     (./conftest; exit; ) >&5 2>/dev/null
     lt_status=$?
     case x$lt_status in
@@ -112415,7 +112593,7 @@ echo "$ac_t""$lt_cv_dlopen_self" 1>&6
     if test "x$lt_cv_dlopen_self" = xyes; then
       wl=$lt_prog_compiler_wl eval LDFLAGS=\"\$LDFLAGS $lt_prog_compiler_static\"
       echo $ac_n "checking whether a statically linked program can dlopen itself""... $ac_c" 1>&6
-echo "configure:112419: checking whether a statically linked program can dlopen itself" >&5
+echo "configure:112607: checking whether a statically linked program can dlopen itself" >&5
 if eval "test \"`echo '$''{'lt_cv_dlopen_self_static'+set}'`\" = set"; then
   echo $ac_n "(cached) $ac_c" 1>&6
 else
@@ -112425,7 +112603,7 @@ else
   lt_dlunknown=0; lt_dlno_uscore=1; lt_dlneed_uscore=2
   lt_status=$lt_dlunknown
   cat > conftest.$ac_ext <<EOF
-#line 112429 "configure"
+#line 112617 "configure"
 #include "confdefs.h"
 
 #if HAVE_DLFCN_H
@@ -112488,7 +112666,7 @@ int main ()
     exit (status);
 }
 EOF
-  if { (eval echo configure:112492: \"$ac_link\") 1>&5; (eval $ac_link) 2>&5; } && test -s conftest${ac_exeext} 2>/dev/null; then
+  if { (eval echo configure:112680: \"$ac_link\") 1>&5; (eval $ac_link) 2>&5; } && test -s conftest${ac_exeext} 2>/dev/null; then
     (./conftest; exit; ) >&5 2>/dev/null
     lt_status=$?
     case x$lt_status in
@@ -112529,11 +112707,11 @@ fi
 
 # Report which library types will actually be built
 echo $ac_n "checking if libtool supports shared libraries""... $ac_c" 1>&6
-echo "configure:112533: checking if libtool supports shared libraries" >&5
+echo "configure:112721: checking if libtool supports shared libraries" >&5
 echo "$ac_t""$can_build_shared" 1>&6
 
 echo $ac_n "checking whether to build shared libraries""... $ac_c" 1>&6
-echo "configure:112537: checking whether to build shared libraries" >&5
+echo "configure:112725: checking whether to build shared libraries" >&5
 test "$can_build_shared" = "no" && enable_shared=no
 
 # On AIX, shared libraries and static libraries use the same namespace, and
@@ -112556,7 +112734,7 @@ esac
 echo "$ac_t""$enable_shared" 1>&6
 
 echo $ac_n "checking whether to build static libraries""... $ac_c" 1>&6
-echo "configure:112560: checking whether to build static libraries" >&5
+echo "configure:112748: checking whether to build static libraries" >&5
 # Make sure either enable_shared or enable_static is yes.
 test "$enable_shared" = yes || enable_static=yes
 echo "$ac_t""$enable_static" 1>&6
@@ -113242,7 +113420,7 @@ ac_prog=ld
 if test "$GCC" = yes; then
   # Check if gcc -print-prog-name=ld gives a path.
   echo $ac_n "checking for ld used by $CC""... $ac_c" 1>&6
-echo "configure:113246: checking for ld used by $CC" >&5
+echo "configure:113434: checking for ld used by $CC" >&5
   case $host in
   *-*-mingw*)
     # gcc leaves a trailing carriage return which upsets mingw
@@ -113272,10 +113450,10 @@ echo "configure:113246: checking for ld used by $CC" >&5
   esac
 elif test "$with_gnu_ld" = yes; then
   echo $ac_n "checking for GNU ld""... $ac_c" 1>&6
-echo "configure:113276: checking for GNU ld" >&5
+echo "configure:113464: checking for GNU ld" >&5
 else
   echo $ac_n "checking for non-GNU ld""... $ac_c" 1>&6
-echo "configure:113279: checking for non-GNU ld" >&5
+echo "configure:113467: checking for non-GNU ld" >&5
 fi
 if eval "test \"`echo '$''{'lt_cv_path_LD'+set}'`\" = set"; then
   echo $ac_n "(cached) $ac_c" 1>&6
@@ -113314,7 +113492,7 @@ else
 fi
 test -z "$LD" && { echo "configure: error: no acceptable ld found in \$PATH" 1>&2; exit 1; }
 echo $ac_n "checking if the linker ($LD) is GNU ld""... $ac_c" 1>&6
-echo "configure:113318: checking if the linker ($LD) is GNU ld" >&5
+echo "configure:113506: checking if the linker ($LD) is GNU ld" >&5
 if eval "test \"`echo '$''{'lt_cv_prog_gnu_ld'+set}'`\" = set"; then
   echo $ac_n "(cached) $ac_c" 1>&6
 else
@@ -113380,7 +113558,7 @@ fi
 
 # PORTME: fill in a description of your system's C++ link characteristics
 echo $ac_n "checking whether the $compiler linker ($LD) supports shared libraries""... $ac_c" 1>&6
-echo "configure:113384: checking whether the $compiler linker ($LD) supports shared libraries" >&5
+echo "configure:113572: checking whether the $compiler linker ($LD) supports shared libraries" >&5
 ld_shlibs_CXX=yes
 case $host_os in
   aix3*)
@@ -113478,12 +113656,12 @@ case $host_os in
       # Determine the default libpath from the value encoded in an empty executable.
       
 cat > conftest.$ac_ext <<EOF
-#line 113482 "configure"
+#line 113670 "configure"
 #include "confdefs.h"
 int main() {
 ; return 0; }
 EOF
-if { (eval echo configure:113487: \"$ac_link\") 1>&5; (eval $ac_link) 2>&5; } && test -s conftest${ac_exeext}; then
+if { (eval echo configure:113675: \"$ac_link\") 1>&5; (eval $ac_link) 2>&5; } && test -s conftest${ac_exeext}; then
  
 lt_aix_libpath_sed='
     /Import File Strings/,/^$/ {
@@ -113517,12 +113695,12 @@ if test -z "$aix_libpath"; then aix_libpath="/usr/lib:/lib"; fi
 	# Determine the default libpath from the value encoded in an empty executable.
 	
 cat > conftest.$ac_ext <<EOF
-#line 113521 "configure"
+#line 113709 "configure"
 #include "confdefs.h"
 int main() {
 ; return 0; }
 EOF
-if { (eval echo configure:113526: \"$ac_link\") 1>&5; (eval $ac_link) 2>&5; } && test -s conftest${ac_exeext}; then
+if { (eval echo configure:113714: \"$ac_link\") 1>&5; (eval $ac_link) 2>&5; } && test -s conftest${ac_exeext}; then
  
 lt_aix_libpath_sed='
     /Import File Strings/,/^$/ {
@@ -114289,7 +114467,7 @@ private:
 };
 EOF
 
-if { (eval echo configure:114293: \"$ac_compile\") 1>&5; (eval $ac_compile) 2>&5; }; then
+if { (eval echo configure:114481: \"$ac_compile\") 1>&5; (eval $ac_compile) 2>&5; }; then
   # Parse the compiler output and extract the necessary
   # objects, libraries and library flags.
 
@@ -114445,7 +114623,7 @@ lt_prog_compiler_pic_CXX=
 lt_prog_compiler_static_CXX=
 
 echo $ac_n "checking for $compiler option to produce PIC""... $ac_c" 1>&6
-echo "configure:114449: checking for $compiler option to produce PIC" >&5
+echo "configure:114637: checking for $compiler option to produce PIC" >&5
  
   # C++ specific cases for pic, static, wl, etc.
   if test "$GXX" = yes; then
@@ -114742,7 +114920,7 @@ echo "$ac_t""$lt_prog_compiler_pic_CXX" 1>&6
 if test -n "$lt_prog_compiler_pic_CXX"; then
   
 echo $ac_n "checking if $compiler PIC flag $lt_prog_compiler_pic_CXX works""... $ac_c" 1>&6
-echo "configure:114746: checking if $compiler PIC flag $lt_prog_compiler_pic_CXX works" >&5
+echo "configure:114934: checking if $compiler PIC flag $lt_prog_compiler_pic_CXX works" >&5
 if eval "test \"`echo '$''{'lt_cv_prog_compiler_pic_works_CXX'+set}'`\" = set"; then
   echo $ac_n "(cached) $ac_c" 1>&6
 else
@@ -114759,11 +114937,11 @@ else
    -e 's:.*FLAGS}\{0,1\} :&$lt_compiler_flag :; t' \
    -e 's: [^ ]*conftest\.: $lt_compiler_flag&:; t' \
    -e 's:$: $lt_compiler_flag:'`
-   (eval echo "\"configure:114763: $lt_compile\"" >&5)
+   (eval echo "\"configure:114951: $lt_compile\"" >&5)
    (eval "$lt_compile" 2>conftest.err)
    ac_status=$?
    cat conftest.err >&5
-   echo "configure:114767: \$? = $ac_status" >&5
+   echo "configure:114955: \$? = $ac_status" >&5
    if (exit $ac_status) && test -s "$ac_outfile"; then
      # The compiler can only warn and ignore the option if not recognized
      # So say no if there are warnings other than the usual output.
@@ -114805,7 +114983,7 @@ esac
 #
 wl=$lt_prog_compiler_wl_CXX eval lt_tmp_static_flag=\"$lt_prog_compiler_static_CXX\"
 echo $ac_n "checking if $compiler static flag $lt_tmp_static_flag works""... $ac_c" 1>&6
-echo "configure:114809: checking if $compiler static flag $lt_tmp_static_flag works" >&5
+echo "configure:114997: checking if $compiler static flag $lt_tmp_static_flag works" >&5
 if eval "test \"`echo '$''{'lt_cv_prog_compiler_static_works_CXX'+set}'`\" = set"; then
   echo $ac_n "(cached) $ac_c" 1>&6
 else
@@ -114843,7 +115021,7 @@ fi
 
 
 echo $ac_n "checking if $compiler supports -c -o file.$ac_objext""... $ac_c" 1>&6
-echo "configure:114847: checking if $compiler supports -c -o file.$ac_objext" >&5
+echo "configure:115035: checking if $compiler supports -c -o file.$ac_objext" >&5
 if eval "test \"`echo '$''{'lt_cv_prog_compiler_c_o_CXX'+set}'`\" = set"; then
   echo $ac_n "(cached) $ac_c" 1>&6
 else
@@ -114863,11 +115041,11 @@ else
    -e 's:.*FLAGS}\{0,1\} :&$lt_compiler_flag :; t' \
    -e 's: [^ ]*conftest\.: $lt_compiler_flag&:; t' \
    -e 's:$: $lt_compiler_flag:'`
-   (eval echo "\"configure:114867: $lt_compile\"" >&5)
+   (eval echo "\"configure:115055: $lt_compile\"" >&5)
    (eval "$lt_compile" 2>out/conftest.err)
    ac_status=$?
    cat out/conftest.err >&5
-   echo "configure:114871: \$? = $ac_status" >&5
+   echo "configure:115059: \$? = $ac_status" >&5
    if (exit $ac_status) && test -s out/conftest2.$ac_objext
    then
      # The compiler can only warn and ignore the option if not recognized
@@ -114897,7 +115075,7 @@ hard_links="nottested"
 if test "$lt_cv_prog_compiler_c_o_CXX" = no && test "$need_locks" != no; then
   # do not overwrite the value of need_locks provided by the user
   echo $ac_n "checking if we can lock with hard links""... $ac_c" 1>&6
-echo "configure:114901: checking if we can lock with hard links" >&5
+echo "configure:115089: checking if we can lock with hard links" >&5
   hard_links=yes
   $rm conftest*
   ln conftest.a conftest.b 2>/dev/null && hard_links=no
@@ -114914,7 +115092,7 @@ else
 fi
 
 echo $ac_n "checking whether the $compiler linker ($LD) supports shared libraries""... $ac_c" 1>&6
-echo "configure:114918: checking whether the $compiler linker ($LD) supports shared libraries" >&5
+echo "configure:115106: checking whether the $compiler linker ($LD) supports shared libraries" >&5
 
   export_symbols_cmds_CXX='$NM $libobjs $convenience | $global_symbol_pipe | $SED '\''s/.* //'\'' | sort | uniq > $export_symbols'
   case $host_os in
@@ -114960,11 +115138,11 @@ x|xyes)
       # systems, -lgcc has to come before -lc. If gcc already passes -lc
       # to ld, don't add -lc before -lgcc.
       echo $ac_n "checking whether -lc should be explicitly linked in""... $ac_c" 1>&6
-echo "configure:114964: checking whether -lc should be explicitly linked in" >&5
+echo "configure:115152: checking whether -lc should be explicitly linked in" >&5
       $rm conftest*
       echo "$lt_simple_compile_test_code" > conftest.$ac_ext
 
-      if { (eval echo configure:114968: \"$ac_compile\") 1>&5; (eval $ac_compile) 2>&5; } 2>conftest.err; then
+      if { (eval echo configure:115156: \"$ac_compile\") 1>&5; (eval $ac_compile) 2>&5; } 2>conftest.err; then
         soname=conftest
         lib=conftest
         libobjs=conftest.$ac_objext
@@ -114978,7 +115156,7 @@ echo "configure:114964: checking whether -lc should be explicitly linked in" >&5
         libname=conftest
         lt_save_allow_undefined_flag=$allow_undefined_flag_CXX
         allow_undefined_flag_CXX=
-        if { (eval echo configure:114982: \"$archive_cmds_CXX 2\>\&1 \| grep \" -lc \" \>/dev/null 2\>\&1\") 1>&5; (eval $archive_cmds_CXX 2\>\&1 \| grep \" -lc \" \>/dev/null 2\>\&1) 2>&5; }
+        if { (eval echo configure:115170: \"$archive_cmds_CXX 2\>\&1 \| grep \" -lc \" \>/dev/null 2\>\&1\") 1>&5; (eval $archive_cmds_CXX 2\>\&1 \| grep \" -lc \" \>/dev/null 2\>\&1) 2>&5; }
         then
 	  archive_cmds_need_lc_CXX=no
         else
@@ -114997,7 +115175,7 @@ echo "configure:114964: checking whether -lc should be explicitly linked in" >&5
 esac
 
 echo $ac_n "checking dynamic linker characteristics""... $ac_c" 1>&6
-echo "configure:115001: checking dynamic linker characteristics" >&5
+echo "configure:115189: checking dynamic linker characteristics" >&5
 library_names_spec=
 libname_spec='lib$name'
 soname_spec=
@@ -115570,7 +115748,7 @@ if test "$GCC" = yes; then
 fi
 
 echo $ac_n "checking how to hardcode library paths into programs""... $ac_c" 1>&6
-echo "configure:115574: checking how to hardcode library paths into programs" >&5
+echo "configure:115762: checking how to hardcode library paths into programs" >&5
 hardcode_action_CXX=
 if test -n "$hardcode_libdir_flag_spec_CXX" || \
    test -n "$runpath_var_CXX" || \
@@ -116129,17 +116307,8 @@ else
   pharcmd_install=
 fi;
 
-all_targets="$lcov_target \$(OVERALL_TARGET) \$(PHP_MODULES) \$(PHP_ZEND_EX) \$(PHP_CLI_TARGET) $pharcmd"
-install_targets="$install_modules install-build install-headers install-programs $install_pear $pharcmd_install"
-
-case $PHP_SAPI in
-  cli)
-    install_targets="$PHP_INSTALL_CLI_TARGET $install_targets"
-    ;;
-  *)
-    install_targets="install-sapi $PHP_INSTALL_CLI_TARGET $install_targets"
-    ;;
-esac
+all_targets="$lcov_target \$(OVERALL_TARGET) \$(PHP_MODULES) \$(PHP_ZEND_EX) \$(PHP_BINARIES) $pharcmd"
+install_targets="$install_sapi $install_modules $install_binaries install-build install-headers install-programs $install_pear $pharcmd_install"
 
 
   PHP_VAR_SUBST="$PHP_VAR_SUBST all_targets"
@@ -116148,6 +116317,9 @@ esac
   PHP_VAR_SUBST="$PHP_VAR_SUBST install_targets"
 
 
+  PHP_VAR_SUBST="$PHP_VAR_SUBST install_binary_targets"
+
+
 
   
     for header_file in Zend/ TSRM/ include/ main/ main/streams/; do
@@ -116365,7 +116537,6 @@ case $host_alias in
     ;;
   *)
     
-  
   case /main in
   "") ac_srcdir="$abs_srcdir/"; unset ac_bdir; ac_inc="-I. -I$abs_srcdir" ;;
   /*) ac_srcdir=`echo "/main"|cut -c 2-`"/"; ac_bdir=$ac_srcdir; ac_inc="-I$ac_bdir -I$abs_srcdir/$ac_bdir" ;;
@@ -116391,7 +116562,7 @@ case $host_alias in
       ac_obj=$1
       IFS=$old_IFS
       
-      PHP_CLI_OBJS="$PHP_CLI_OBJS $ac_bdir$ac_obj.lo"
+      PHP_BINARY_OBJS="$PHP_BINARY_OBJS $ac_bdir$ac_obj.lo"
 
       case $ac_src in
         *.c) ac_comp="$b_c_pre  $ac_inc $b_c_meta -c $ac_srcdir$ac_src -o $ac_bdir$ac_obj.$b_lo $b_c_post" ;;
@@ -116406,7 +116577,6 @@ $ac_bdir$ac_obj.lo: $ac_srcdir$ac_src
 EOF
   done
 
-
     ;;
 esac
 
@@ -116565,11 +116735,6 @@ EOF
 
 
   
-    BUILD_DIR="$BUILD_DIR sapi/$PHP_SAPI sapi/cli"
-  
-
-
-  
     BUILD_DIR="$BUILD_DIR TSRM"
   
 
diff --git a/configure.in b/configure.in
index 183c3e3..f675baf 100644
--- a/configure.in
+++ b/configure.in
@@ -304,8 +304,8 @@ dnl -------------------------------------------------------------------------
 PTHREADS_CHECK
 PHP_HELP_SEPARATOR([SAPI modules:])
 PHP_SHLIB_SUFFIX_NAMES
-PHP_SAPI=default
 PHP_BUILD_PROGRAM
+PHP_SAPI=none
 
 
 dnl SAPI configuration.
@@ -324,6 +324,20 @@ dnl Show which main SAPI was selected
 AC_MSG_CHECKING([for chosen SAPI module])
 AC_MSG_RESULT([$PHP_SAPI])
 
+dnl Show which binaries were selected
+AC_MSG_CHECKING([for executable SAPI binaries])
+if test "$PHP_BINARIES"; then
+  AC_MSG_RESULT([$PHP_BINARIES])
+else
+  AC_MSG_RESULT([none])
+fi
+
+dnl Exit early
+if test -z "$PHP_INSTALLED_SAPIS"; then
+  AC_MSG_ERROR([Nothing to build.])
+fi
+
+dnl force ZTS
 if test "$enable_maintainer_zts" = "yes"; then
   PTHREADS_ASSIGN_VARS
   PTHREADS_FLAGS
@@ -965,14 +979,8 @@ dnl -------------------------------------------------------------------------
 enable_shared=yes
 enable_static=yes
 
-case $php_build_target in
-  program|static)
-    standard_libtool_flag='-prefer-non-pic -static'
-    if test -z "$PHP_MODULES" && test -z "$PHP_ZEND_EX"; then
-        enable_shared=no
-    fi
-    ;;
-  shared)
+case $php_sapi_module in
+  shared[)]
     enable_static=no
     case $with_pic in
       yes)
@@ -984,6 +992,12 @@ case $php_build_target in
     esac
     EXTRA_LDFLAGS="$EXTRA_LDFLAGS -avoid-version -module"
     ;;
+  *[)]
+    standard_libtool_flag='-prefer-non-pic -static'
+    if test -z "$PHP_MODULES" && test -z "$PHP_ZEND_EX"; then
+      enable_shared=no
+    fi
+    ;;
 esac
 
 EXTRA_LIBS="$EXTRA_LIBS $DLIBS $LIBS"
@@ -1221,24 +1235,15 @@ case $host_alias in
   ;;
 esac
 
-if test "$PHP_CLI" != "no"; then
-  PHP_CLI_TARGET="\$(SAPI_CLI_PATH)"
-  PHP_INSTALL_CLI_TARGET="install-cli"
-  PHP_ADD_SOURCES(sapi/cli, php_cli.c php_cli_readline.c,, cli)
-  PHP_INSTALLED_SAPIS="cli $PHP_SAPI"
-  PHP_EXECUTABLE="\$(top_builddir)/\$(SAPI_CLI_PATH)"
-else
-  PHP_INSTALLED_SAPIS="$PHP_SAPI"
-fi
-
 PHP_SUBST_OLD(PHP_INSTALLED_SAPIS)
 
 PHP_SUBST(PHP_EXECUTABLE)
-PHP_SUBST(PHP_CLI_TARGET)
+
 PHP_SUBST(PHP_SAPI_OBJS)
-PHP_SUBST(PHP_CLI_OBJS)
+PHP_SUBST(PHP_BINARY_OBJS)
 PHP_SUBST(PHP_GLOBAL_OBJS)
 
+PHP_SUBST(PHP_BINARIES)
 PHP_SUBST(PHP_MODULES)
 PHP_SUBST(PHP_ZEND_EX)
 
@@ -1338,7 +1343,7 @@ LDFLAGS="$LDFLAGS $PHP_AIX_LDFLAGS"
 dnl Autoconf 2.13's libtool checks go slightly nuts on Mac OS X 10.5, 10.6, 10.7 and 10.8.
 dnl This hack works around it. Ugly.
 case $host_alias in
-*darwin9*|*darwin10*|*darwin11*|*darwin12*)
+*darwin9*|*darwin10*|*darwin11*|*darwin12*|*darwin13*|*darwin14*|*darwin15*|*darwin16*)
   ac_cv_exeext=
   ;;
 esac
@@ -1382,20 +1387,12 @@ else
   pharcmd_install=
 fi;
 
-all_targets="$lcov_target \$(OVERALL_TARGET) \$(PHP_MODULES) \$(PHP_ZEND_EX) \$(PHP_CLI_TARGET) $pharcmd"
-install_targets="$install_modules install-build install-headers install-programs $install_pear $pharcmd_install"
-
-case $PHP_SAPI in
-  cli)
-    install_targets="$PHP_INSTALL_CLI_TARGET $install_targets"
-    ;;
-  *)
-    install_targets="install-sapi $PHP_INSTALL_CLI_TARGET $install_targets"
-    ;;
-esac
+all_targets="$lcov_target \$(OVERALL_TARGET) \$(PHP_MODULES) \$(PHP_ZEND_EX) \$(PHP_BINARIES) $pharcmd"
+install_targets="$install_sapi $install_modules $install_binaries install-build install-headers install-programs $install_pear $pharcmd_install"
 
 PHP_SUBST(all_targets)
 PHP_SUBST(install_targets)
+PHP_SUBST(install_binary_targets)
 
 PHP_INSTALL_HEADERS([Zend/ TSRM/ include/ main/ main/streams/])
 
@@ -1420,7 +1417,7 @@ case $host_alias in
     PHP_ADD_BUILD_DIR(netware)
     ;;
   *)
-    PHP_ADD_SOURCES(/main, internal_functions_cli.c,, cli)
+    PHP_ADD_SOURCES_X(/main, internal_functions_cli.c,, PHP_BINARY_OBJS)
     ;;
 esac
 
@@ -1451,7 +1448,6 @@ fi
 PHP_ADD_SOURCES_X(Zend, zend_execute.c,,PHP_GLOBAL_OBJS,,$flag)
 
 PHP_ADD_BUILD_DIR(main main/streams)
-PHP_ADD_BUILD_DIR(sapi/$PHP_SAPI sapi/cli)
 PHP_ADD_BUILD_DIR(TSRM)
 PHP_ADD_BUILD_DIR(Zend)
 
diff --git a/generated_lists b/generated_lists
index 2716217..f618fb3 100644
--- a/generated_lists
+++ b/generated_lists
@@ -1,3 +1,3 @@
 makefile_am_files = Zend/Makefile.am TSRM/Makefile.am
 config_h_files = Zend/acconfig.h TSRM/acconfig.h
-config_m4_files = Zend/Zend.m4 TSRM/tsrm.m4 TSRM/threads.m4 Zend/acinclude.m4 ext/bcmath/config.m4 ext/bz2/config.m4 ext/calendar/config.m4 ext/ctype/config.m4 ext/curl/config.m4 ext/date/config0.m4 ext/dba/config.m4 ext/dom/config.m4 ext/enchant/config.m4 ext/ereg/config0.m4 ext/exif/config.m4 ext/fileinfo/config.m4 ext/filter/config.m4 ext/ftp/config.m4 ext/gd/config.m4 ext/gettext/config.m4 ext/gmp/config.m4 ext/hash/config.m4 ext/iconv/config.m4 ext/imap/config.m4 ext/interbase/config.m4 ext/intl/config.m4 ext/json/config.m4 ext/ldap/config.m4 ext/libxml/config0.m4 ext/mbstring/config.m4 ext/mcrypt/config.m4 ext/mssql/config.m4 ext/mysql/config.m4 ext/mysqli/config.m4 ext/mysqlnd/config9.m4 ext/oci8/config.m4 ext/odbc/config.m4 ext/openssl/config0.m4 ext/pcntl/config.m4 ext/pcre/config0.m4 ext/pdo/config.m4 ext/pdo_dblib/config.m4 ext/pdo_firebird/config.m4 ext/pdo_mysql/config.m4 ext/pdo_oci/config.m4 ext/pdo_odbc/config.m4 ext/pdo_pgsql/config.m4 ext/pdo_sqlite/config.m4 ext/pgsql/config.m4 ext/phar/config.m4 ext/posix/config.m4 ext/pspell/config.m4 ext/readline/config.m4 ext/recode/config9.m4 ext/recode/config.m4 ext/reflection/config.m4 ext/session/config.m4 ext/shmop/config.m4 ext/simplexml/config.m4 ext/snmp/config.m4 ext/soap/config.m4 ext/sockets/config.m4 ext/spl/config.m4 ext/sqlite3/config0.m4 ext/sqlite/config.m4 ext/standard/config.m4 ext/sybase_ct/config.m4 ext/sysvmsg/config.m4 ext/sysvsem/config.m4 ext/sysvshm/config.m4 ext/tidy/config.m4 ext/tokenizer/config.m4 ext/wddx/config.m4 ext/xml/config.m4 ext/xmlreader/config.m4 ext/xmlrpc/config.m4 ext/xmlwriter/config.m4 ext/xsl/config.m4 ext/zip/config.m4 ext/zlib/config0.m4 sapi/aolserver/config.m4 sapi/apache2filter/config.m4 sapi/apache2handler/config.m4 sapi/apache/config.m4 sapi/apache_hooks/config.m4 sapi/caudium/config.m4 sapi/cli/config.m4 sapi/continuity/config.m4 sapi/embed/config.m4 sapi/fpm/config.m4 sapi/isapi/config.m4 sapi/litespeed/config.m4 sapi/milter/config.m4 sapi/nsapi/config.m4 sapi/phttpd/config.m4 sapi/pi3web/config.m4 sapi/roxen/config.m4 sapi/thttpd/config.m4 sapi/tux/config.m4 sapi/webjames/config.m4
+config_m4_files = Zend/Zend.m4 TSRM/tsrm.m4 TSRM/threads.m4 Zend/acinclude.m4 ext/bcmath/config.m4 ext/bz2/config.m4 ext/calendar/config.m4 ext/ctype/config.m4 ext/curl/config.m4 ext/date/config0.m4 ext/dba/config.m4 ext/dom/config.m4 ext/enchant/config.m4 ext/ereg/config0.m4 ext/exif/config.m4 ext/fileinfo/config.m4 ext/filter/config.m4 ext/ftp/config.m4 ext/gd/config.m4 ext/gettext/config.m4 ext/gmp/config.m4 ext/hash/config.m4 ext/iconv/config.m4 ext/imap/config.m4 ext/interbase/config.m4 ext/intl/config.m4 ext/json/config.m4 ext/ldap/config.m4 ext/libxml/config0.m4 ext/mbstring/config.m4 ext/mcrypt/config.m4 ext/mssql/config.m4 ext/mysql/config.m4 ext/mysqli/config.m4 ext/mysqlnd/config9.m4 ext/oci8/config.m4 ext/odbc/config.m4 ext/openssl/config0.m4 ext/pcntl/config.m4 ext/pcre/config0.m4 ext/pdo/config.m4 ext/pdo_dblib/config.m4 ext/pdo_firebird/config.m4 ext/pdo_mysql/config.m4 ext/pdo_oci/config.m4 ext/pdo_odbc/config.m4 ext/pdo_pgsql/config.m4 ext/pdo_sqlite/config.m4 ext/pgsql/config.m4 ext/phar/config.m4 ext/posix/config.m4 ext/pspell/config.m4 ext/readline/config.m4 ext/recode/config.m4 ext/recode/config9.m4 ext/reflection/config.m4 ext/session/config.m4 ext/shmop/config.m4 ext/simplexml/config.m4 ext/snmp/config.m4 ext/soap/config.m4 ext/sockets/config.m4 ext/spl/config.m4 ext/sqlite/config.m4 ext/sqlite3/config0.m4 ext/standard/config.m4 ext/sybase_ct/config.m4 ext/sysvmsg/config.m4 ext/sysvsem/config.m4 ext/sysvshm/config.m4 ext/tidy/config.m4 ext/tokenizer/config.m4 ext/wddx/config.m4 ext/xml/config.m4 ext/xmlreader/config.m4 ext/xmlrpc/config.m4 ext/xmlwriter/config.m4 ext/xsl/config.m4 ext/zip/config.m4 ext/zlib/config0.m4 sapi/aolserver/config.m4 sapi/apache/config.m4 sapi/apache2filter/config.m4 sapi/apache2handler/config.m4 sapi/apache_hooks/config.m4 sapi/caudium/config.m4 sapi/cli/config.m4 sapi/continuity/config.m4 sapi/embed/config.m4 sapi/fpm/config.m4 sapi/isapi/config.m4 sapi/litespeed/config.m4 sapi/milter/config.m4 sapi/nsapi/config.m4 sapi/phttpd/config.m4 sapi/pi3web/config.m4 sapi/roxen/config.m4 sapi/thttpd/config.m4 sapi/tux/config.m4 sapi/webjames/config.m4
diff --git a/main/php_config.h.in b/main/php_config.h.in
index b666ac6..182965c 100644
--- a/main/php_config.h.in
+++ b/main/php_config.h.in
@@ -1,4 +1,4 @@
-/* main/php_config.h.in.  Generated from configure.in by autoheader.  */
+/* main/php_config.h.in.  Generated automatically from configure.in by autoheader.  */
 /* Leave this file alone */
 /*
    +----------------------------------------------------------------------+
@@ -32,2424 +32,2925 @@
 #define ZEND_DLIMPORT
 
 
+/* Define if on AIX 3.
+   System headers sometimes define this.
+   We just want to avoid a redefinition error message.  */
+#ifndef _ALL_SOURCE
+#undef _ALL_SOURCE
+#endif
+
+/* Define if using alloca.c.  */
+#undef C_ALLOCA
+
+/* Define to empty if the keyword does not work.  */
+#undef const
+
+/* Define to one of _getb67, GETB67, getb67 for Cray-2 and Cray-YMP systems.
+   This function is required for alloca.c support on those systems.  */
+#undef CRAY_STACKSEG_END
+
+/* Define to `int' if <sys/types.h> doesn't define.  */
+#undef gid_t
+
+/* Define if you have alloca, as a function or macro.  */
+#undef HAVE_ALLOCA
+
+/* Define if you have <alloca.h> and it should be used (not on Ultrix).  */
+#undef HAVE_ALLOCA_H
+
+/* Define if you don't have vprintf but do have _doprnt.  */
+#undef HAVE_DOPRNT
+
+/* Define if your system has a working fnmatch function.  */
+#undef HAVE_FNMATCH
+
+/* Define if your struct stat has st_blksize.  */
+#undef HAVE_ST_BLKSIZE
+
+/* Define if your struct stat has st_blocks.  */
+#undef HAVE_ST_BLOCKS
+
+/* Define if your struct stat has st_rdev.  */
+#undef HAVE_ST_RDEV
+
+/* Define if your struct tm has tm_zone.  */
+#undef HAVE_TM_ZONE
+
+/* Define if you don't have tm_zone but do have the external array
+   tzname.  */
+#undef HAVE_TZNAME
+
+/* Define if utime(file, NULL) sets file's timestamp to the present.  */
+#undef HAVE_UTIME_NULL
+
+/* Define if you have the vprintf function.  */
+#undef HAVE_VPRINTF
+
+/* Define as __inline if that's what the C compiler calls it.  */
+#undef inline
+
+/* Define if your C compiler doesn't accept -c and -o together.  */
+#undef NO_MINUS_C_MINUS_O
+
+/* Define as the return type of signal handlers (int or void).  */
+#undef RETSIGTYPE
+
+/* Define to `unsigned' if <sys/types.h> doesn't define.  */
+#undef size_t
+
+/* If using the C implementation of alloca, define if you know the
+   direction of stack growth for your system; otherwise it will be
+   automatically deduced at run-time.
+ STACK_DIRECTION > 0 => grows toward higher addresses
+ STACK_DIRECTION < 0 => grows toward lower addresses
+ STACK_DIRECTION = 0 => direction of growth unknown
+ */
+#undef STACK_DIRECTION
+
+/* Define if you have the ANSI C header files.  */
+#undef STDC_HEADERS
+
+/* Define if you can safely include both <sys/time.h> and <time.h>.  */
+#undef TIME_WITH_SYS_TIME
+
+/* Define if your <sys/time.h> declares struct tm.  */
+#undef TM_IN_SYS_TIME
+
+/* Define to `int' if <sys/types.h> doesn't define.  */
+#undef uid_t
+
 #undef uint
 #undef ulong
 
-/* Define if you want to enable memory limit support */
-#define MEMORY_LIMIT 0
+/* The number of bytes in a char.  */
+#undef SIZEOF_CHAR
 
+/* The number of bytes in a char *.  */
+#undef SIZEOF_CHAR_P
 
-/* */
-#undef AIX
+/* The number of bytes in a int.  */
+#undef SIZEOF_INT
 
-/* Whether to use native BeOS threads */
-#undef BETHREADS
+/* The number of bytes in a long.  */
+#undef SIZEOF_LONG
 
-/* */
-#undef CDB_INCLUDE_FILE
+/* The number of bytes in a long int.  */
+#undef SIZEOF_LONG_INT
 
-/* Define if system uses EBCDIC */
-#undef CHARSET_EBCDIC
+/* The number of bytes in a long long.  */
+#undef SIZEOF_LONG_LONG
 
-/* Whether to build bcmath as dynamic module */
-#undef COMPILE_DL_BCMATH
+/* The number of bytes in a long long int.  */
+#undef SIZEOF_LONG_LONG_INT
 
-/* Whether to build bz2 as dynamic module */
-#undef COMPILE_DL_BZ2
+/* The number of bytes in a short.  */
+#undef SIZEOF_SHORT
 
-/* Whether to build calendar as dynamic module */
-#undef COMPILE_DL_CALENDAR
+/* The number of bytes in a size_t.  */
+#undef SIZEOF_SIZE_T
 
-/* Whether to build ctype as dynamic module */
-#undef COMPILE_DL_CTYPE
+/* Define if you have the CreateProcess function.  */
+#undef HAVE_CREATEPROCESS
 
-/* Whether to build curl as dynamic module */
-#undef COMPILE_DL_CURL
+/* Define if you have the acosh function.  */
+#undef HAVE_ACOSH
 
-/* Whether to build date as dynamic module */
-#undef COMPILE_DL_DATE
+/* Define if you have the alphasort function.  */
+#undef HAVE_ALPHASORT
 
-/* Whether to build dba as dynamic module */
-#undef COMPILE_DL_DBA
+/* Define if you have the asctime_r function.  */
+#undef HAVE_ASCTIME_R
 
-/* Whether to build dom as dynamic module */
-#undef COMPILE_DL_DOM
+/* Define if you have the asinh function.  */
+#undef HAVE_ASINH
 
-/* Whether to build enchant as dynamic module */
-#undef COMPILE_DL_ENCHANT
+/* Define if you have the asprintf function.  */
+#undef HAVE_ASPRINTF
 
-/* Whether to build ereg as dynamic module */
-#undef COMPILE_DL_EREG
+/* Define if you have the atanh function.  */
+#undef HAVE_ATANH
 
-/* Whether to build exif as dynamic module */
-#undef COMPILE_DL_EXIF
+/* Define if you have the atoll function.  */
+#undef HAVE_ATOLL
 
-/* Whether to build fileinfo as dynamic module */
-#undef COMPILE_DL_FILEINFO
+/* Define if you have the chroot function.  */
+#undef HAVE_CHROOT
 
-/* Whether to build filter as dynamic module */
-#undef COMPILE_DL_FILTER
+/* Define if you have the clearenv function.  */
+#undef HAVE_CLEARENV
 
-/* Whether to build ftp as dynamic module */
-#undef COMPILE_DL_FTP
+/* Define if you have the crypt function.  */
+#undef HAVE_CRYPT
 
-/* Whether to build gd as dynamic module */
-#undef COMPILE_DL_GD
+/* Define if you have the crypt_r function.  */
+#undef HAVE_CRYPT_R
 
-/* Whether to build gettext as dynamic module */
-#undef COMPILE_DL_GETTEXT
+/* Define if you have the ctermid function.  */
+#undef HAVE_CTERMID
 
-/* Whether to build gmp as dynamic module */
-#undef COMPILE_DL_GMP
+/* Define if you have the ctime_r function.  */
+#undef HAVE_CTIME_R
+
+/* Define if you have the cuserid function.  */
+#undef HAVE_CUSERID
+
+/* Define if you have the fabsf function.  */
+#undef HAVE_FABSF
+
+/* Define if you have the finite function.  */
+#undef HAVE_FINITE
+
+/* Define if you have the flock function.  */
+#undef HAVE_FLOCK
+
+/* Define if you have the floorf function.  */
+#undef HAVE_FLOORF
+
+/* Define if you have the fork function.  */
+#undef HAVE_FORK
+
+/* Define if you have the fpclass function.  */
+#undef HAVE_FPCLASS
+
+/* Define if you have the ftok function.  */
+#undef HAVE_FTOK
+
+/* Define if you have the funopen function.  */
+#undef HAVE_FUNOPEN
+
+/* Define if you have the gai_strerror function.  */
+#undef HAVE_GAI_STRERROR
+
+/* Define if you have the gcvt function.  */
+#undef HAVE_GCVT
+
+/* Define if you have the getcwd function.  */
+#undef HAVE_GETCWD
+
+/* Define if you have the getgrgid_r function.  */
+#undef HAVE_GETGRGID_R
+
+/* Define if you have the getgrnam_r function.  */
+#undef HAVE_GETGRNAM_R
+
+/* Define if you have the getgroups function.  */
+#undef HAVE_GETGROUPS
+
+/* Define if you have the gethostname function.  */
+#undef HAVE_GETHOSTNAME
+
+/* Define if you have the getloadavg function.  */
+#undef HAVE_GETLOADAVG
+
+/* Define if you have the getlogin function.  */
+#undef HAVE_GETLOGIN
+
+/* Define if you have the getopt function.  */
+#undef HAVE_GETOPT
+
+/* Define if you have the getpgid function.  */
+#undef HAVE_GETPGID
+
+/* Define if you have the getpid function.  */
+#undef HAVE_GETPID
+
+/* Define if you have the getpriority function.  */
+#undef HAVE_GETPRIORITY
+
+/* Define if you have the getprotobyname function.  */
+#undef HAVE_GETPROTOBYNAME
+
+/* Define if you have the getprotobynumber function.  */
+#undef HAVE_GETPROTOBYNUMBER
+
+/* Define if you have the getpwnam_r function.  */
+#undef HAVE_GETPWNAM_R
+
+/* Define if you have the getpwuid_r function.  */
+#undef HAVE_GETPWUID_R
+
+/* Define if you have the getrlimit function.  */
+#undef HAVE_GETRLIMIT
+
+/* Define if you have the getrusage function.  */
+#undef HAVE_GETRUSAGE
+
+/* Define if you have the getservbyname function.  */
+#undef HAVE_GETSERVBYNAME
+
+/* Define if you have the getservbyport function.  */
+#undef HAVE_GETSERVBYPORT
+
+/* Define if you have the getsid function.  */
+#undef HAVE_GETSID
+
+/* Define if you have the gettimeofday function.  */
+#undef HAVE_GETTIMEOFDAY
+
+/* Define if you have the getwd function.  */
+#undef HAVE_GETWD
+
+/* Define if you have the glob function.  */
+#undef HAVE_GLOB
+
+/* Define if you have the gmtime_r function.  */
+#undef HAVE_GMTIME_R
+
+/* Define if you have the grantpt function.  */
+#undef HAVE_GRANTPT
+
+/* Define if you have the hstrerror function.  */
+#undef HAVE_HSTRERROR
+
+/* Define if you have the hypot function.  */
+#undef HAVE_HYPOT
+
+/* Define if you have the inet_ntoa function.  */
+#undef HAVE_INET_NTOA
+
+/* Define if you have the inet_ntop function.  */
+#undef HAVE_INET_NTOP
+
+/* Define if you have the inet_pton function.  */
+#undef HAVE_INET_PTON
+
+/* Define if you have the initgroups function.  */
+#undef HAVE_INITGROUPS
+
+/* Define if you have the isascii function.  */
+#undef HAVE_ISASCII
+
+/* Define if you have the isfinite function.  */
+#undef HAVE_ISFINITE
+
+/* Define if you have the isinf function.  */
+#undef HAVE_ISINF
+
+/* Define if you have the isnan function.  */
+#undef HAVE_ISNAN
+
+/* Define if you have the kill function.  */
+#undef HAVE_KILL
+
+/* Define if you have the lchown function.  */
+#undef HAVE_LCHOWN
+
+/* Define if you have the ldap_parse_reference function.  */
+#undef HAVE_LDAP_PARSE_REFERENCE
+
+/* Define if you have the ldap_parse_result function.  */
+#undef HAVE_LDAP_PARSE_RESULT
+
+/* Define if you have the ldap_start_tls_s function.  */
+#undef HAVE_LDAP_START_TLS_S
+
+/* Define if you have the link function.  */
+#undef HAVE_LINK
+
+/* Define if you have the localeconv function.  */
+#undef HAVE_LOCALECONV
+
+/* Define if you have the localtime_r function.  */
+#undef HAVE_LOCALTIME_R
+
+/* Define if you have the lockf function.  */
+#undef HAVE_LOCKF
+
+/* Define if you have the log1p function.  */
+#undef HAVE_LOG1P
+
+/* Define if you have the lrand48 function.  */
+#undef HAVE_LRAND48
+
+/* Define if you have the makedev function.  */
+#undef HAVE_MAKEDEV
+
+/* Define if you have the mblen function.  */
+#undef HAVE_MBLEN
+
+/* Define if you have the mbrlen function.  */
+#undef HAVE_MBRLEN
+
+/* Define if you have the mbsinit function.  */
+#undef HAVE_MBSINIT
+
+/* Define if you have the memcpy function.  */
+#undef HAVE_MEMCPY
+
+/* Define if you have the memmove function.  */
+#undef HAVE_MEMMOVE
+
+/* Define if you have the mempcpy function.  */
+#undef HAVE_MEMPCPY
+
+/* Define if you have the mkfifo function.  */
+#undef HAVE_MKFIFO
+
+/* Define if you have the mknod function.  */
+#undef HAVE_MKNOD
+
+/* Define if you have the mkstemp function.  */
+#undef HAVE_MKSTEMP
+
+/* Define if you have the mmap function.  */
+#undef HAVE_MMAP
+
+/* Define if you have the mremap function.  */
+#undef HAVE_MREMAP
+
+/* Define if you have the mysql_commit function.  */
+#undef HAVE_MYSQL_COMMIT
+
+/* Define if you have the mysql_next_result function.  */
+#undef HAVE_MYSQL_NEXT_RESULT
+
+/* Define if you have the mysql_sqlstate function.  */
+#undef HAVE_MYSQL_SQLSTATE
+
+/* Define if you have the mysql_stmt_prepare function.  */
+#undef HAVE_MYSQL_STMT_PREPARE
+
+/* Define if you have the nanosleep function.  */
+#undef HAVE_NANOSLEEP
+
+/* Define if you have the nice function.  */
+#undef HAVE_NICE
+
+/* Define if you have the nl_langinfo function.  */
+#undef HAVE_NL_LANGINFO
+
+/* Define if you have the perror function.  */
+#undef HAVE_PERROR
+
+/* Define if you have the poll function.  */
+#undef HAVE_POLL
+
+/* Define if you have the ptsname function.  */
+#undef HAVE_PTSNAME
+
+/* Define if you have the putenv function.  */
+#undef HAVE_PUTENV
+
+/* Define if you have the rand_r function.  */
+#undef HAVE_RAND_R
+
+/* Define if you have the random function.  */
+#undef HAVE_RANDOM
+
+/* Define if you have the realpath function.  */
+#undef HAVE_REALPATH
+
+/* Define if you have the rl_completion_matches function.  */
+#undef HAVE_RL_COMPLETION_MATCHES
+
+/* Define if you have the scandir function.  */
+#undef HAVE_SCANDIR
+
+/* Define if you have the setegid function.  */
+#undef HAVE_SETEGID
+
+/* Define if you have the setenv function.  */
+#undef HAVE_SETENV
+
+/* Define if you have the seteuid function.  */
+#undef HAVE_SETEUID
+
+/* Define if you have the setitimer function.  */
+#undef HAVE_SETITIMER
+
+/* Define if you have the setlocale function.  */
+#undef HAVE_SETLOCALE
+
+/* Define if you have the setpgid function.  */
+#undef HAVE_SETPGID
+
+/* Define if you have the setpriority function.  */
+#undef HAVE_SETPRIORITY
+
+/* Define if you have the setproctitle function.  */
+#undef HAVE_SETPROCTITLE
+
+/* Define if you have the setsid function.  */
+#undef HAVE_SETSID
+
+/* Define if you have the setsockopt function.  */
+#undef HAVE_SETSOCKOPT
+
+/* Define if you have the setvbuf function.  */
+#undef HAVE_SETVBUF
+
+/* Define if you have the shutdown function.  */
+#undef HAVE_SHUTDOWN
+
+/* Define if you have the sigaction function.  */
+#undef HAVE_SIGACTION
+
+/* Define if you have the sigprocmask function.  */
+#undef HAVE_SIGPROCMASK
+
+/* Define if you have the sigsetjmp function.  */
+#undef HAVE_SIGSETJMP
+
+/* Define if you have the sigtimedwait function.  */
+#undef HAVE_SIGTIMEDWAIT
+
+/* Define if you have the sigwaitinfo function.  */
+#undef HAVE_SIGWAITINFO
+
+/* Define if you have the sin function.  */
+#undef HAVE_SIN
+
+/* Define if you have the snprintf function.  */
+#undef HAVE_SNPRINTF
+
+/* Define if you have the socketpair function.  */
+#undef HAVE_SOCKETPAIR
+
+/* Define if you have the srand48 function.  */
+#undef HAVE_SRAND48
+
+/* Define if you have the srandom function.  */
+#undef HAVE_SRANDOM
+
+/* Define if you have the statfs function.  */
+#undef HAVE_STATFS
+
+/* Define if you have the statvfs function.  */
+#undef HAVE_STATVFS
+
+/* Define if you have the std_syslog function.  */
+#undef HAVE_STD_SYSLOG
+
+/* Define if you have the strcasecmp function.  */
+#undef HAVE_STRCASECMP
+
+/* Define if you have the strcoll function.  */
+#undef HAVE_STRCOLL
+
+/* Define if you have the strdup function.  */
+#undef HAVE_STRDUP
+
+/* Define if you have the strerror function.  */
+#undef HAVE_STRERROR
+
+/* Define if you have the strfmon function.  */
+#undef HAVE_STRFMON
+
+/* Define if you have the strftime function.  */
+#undef HAVE_STRFTIME
+
+/* Define if you have the strlcat function.  */
+#undef HAVE_STRLCAT
+
+/* Define if you have the strlcpy function.  */
+#undef HAVE_STRLCPY
+
+/* Define if you have the strndup function.  */
+#undef HAVE_STRNDUP
+
+/* Define if you have the strnlen function.  */
+#undef HAVE_STRNLEN
+
+/* Define if you have the strpbrk function.  */
+#undef HAVE_STRPBRK
+
+/* Define if you have the strpncpy function.  */
+#undef HAVE_STRPNCPY
+
+/* Define if you have the strptime function.  */
+#undef HAVE_STRPTIME
+
+/* Define if you have the strstr function.  */
+#undef HAVE_STRSTR
+
+/* Define if you have the strtod function.  */
+#undef HAVE_STRTOD
+
+/* Define if you have the strtok_r function.  */
+#undef HAVE_STRTOK_R
+
+/* Define if you have the strtol function.  */
+#undef HAVE_STRTOL
+
+/* Define if you have the strtoll function.  */
+#undef HAVE_STRTOLL
+
+/* Define if you have the strtoul function.  */
+#undef HAVE_STRTOUL
+
+/* Define if you have the strtoull function.  */
+#undef HAVE_STRTOULL
+
+/* Define if you have the symlink function.  */
+#undef HAVE_SYMLINK
+
+/* Define if you have the tempnam function.  */
+#undef HAVE_TEMPNAM
+
+/* Define if you have the tzset function.  */
+#undef HAVE_TZSET
+
+/* Define if you have the unlockpt function.  */
+#undef HAVE_UNLOCKPT
+
+/* Define if you have the unsetenv function.  */
+#undef HAVE_UNSETENV
+
+/* Define if you have the usleep function.  */
+#undef HAVE_USLEEP
+
+/* Define if you have the utime function.  */
+#undef HAVE_UTIME
+
+/* Define if you have the utimes function.  */
+#undef HAVE_UTIMES
+
+/* Define if you have the vasprintf function.  */
+#undef HAVE_VASPRINTF
+
+/* Define if you have the vsnprintf function.  */
+#undef HAVE_VSNPRINTF
+
+/* Define if you have the wait3 function.  */
+#undef HAVE_WAIT3
+
+/* Define if you have the waitpid function.  */
+#undef HAVE_WAITPID
 
-/* Whether to build hash as dynamic module */
-#undef COMPILE_DL_HASH
+/* Define if you have the </nsapi.h> header file.  */
+#undef HAVE__NSAPI_H
 
-/* Whether to build iconv as dynamic module */
-#undef COMPILE_DL_ICONV
+/* Define if you have the <ApplicationServices/ApplicationServices.h> header file.  */
+#undef HAVE_APPLICATIONSERVICES_APPLICATIONSERVICES_H
 
-/* Whether to build imap as dynamic module */
-#undef COMPILE_DL_IMAP
+/* Define if you have the <alloca.h> header file.  */
+#undef HAVE_ALLOCA_H
 
-/* Whether to build interbase as dynamic module */
-#undef COMPILE_DL_INTERBASE
+/* Define if you have the <arpa/inet.h> header file.  */
+#undef HAVE_ARPA_INET_H
 
-/* Whether to build intl as dynamic module */
-#undef COMPILE_DL_INTL
+/* Define if you have the <arpa/nameser.h> header file.  */
+#undef HAVE_ARPA_NAMESER_H
 
-/* Whether to build json as dynamic module */
-#undef COMPILE_DL_JSON
+/* Define if you have the <assert.h> header file.  */
+#undef HAVE_ASSERT_H
 
-/* Whether to build ldap as dynamic module */
-#undef COMPILE_DL_LDAP
+/* Define if you have the <atomic.h> header file.  */
+#undef HAVE_ATOMIC_H
 
-/* Whether to build libxml as dynamic module */
-#undef COMPILE_DL_LIBXML
+/* Define if you have the <crypt.h> header file.  */
+#undef HAVE_CRYPT_H
 
-/* Whether to build mbstring as dynamic module */
-#undef COMPILE_DL_MBSTRING
+/* Define if you have the <default_store.h> header file.  */
+#undef HAVE_DEFAULT_STORE_H
 
-/* Whether to build mcrypt as dynamic module */
-#undef COMPILE_DL_MCRYPT
+/* Define if you have the <dirent.h> header file.  */
+#undef HAVE_DIRENT_H
 
-/* Whether to build mssql as dynamic module */
-#undef COMPILE_DL_MSSQL
+/* Define if you have the <dlfcn.h> header file.  */
+#undef HAVE_DLFCN_H
 
-/* Whether to build mysql as dynamic module */
-#undef COMPILE_DL_MYSQL
+/* Define if you have the <dns.h> header file.  */
+#undef HAVE_DNS_H
 
-/* Whether to build mysqli as dynamic module */
-#undef COMPILE_DL_MYSQLI
+/* Define if you have the <errno.h> header file.  */
+#undef HAVE_ERRNO_H
 
-/* Whether to build mysqlnd as dynamic module */
-#undef COMPILE_DL_MYSQLND
+/* Define if you have the <fcntl.h> header file.  */
+#undef HAVE_FCNTL_H
 
-/* Whether to build oci8 as dynamic module */
-#undef COMPILE_DL_OCI8
+/* Define if you have the <grp.h> header file.  */
+#undef HAVE_GRP_H
 
-/* Whether to build odbc as dynamic module */
-#undef COMPILE_DL_ODBC
+/* Define if you have the <ieeefp.h> header file.  */
+#undef HAVE_IEEEFP_H
 
-/* Whether to build openssl as dynamic module */
-#undef COMPILE_DL_OPENSSL
+/* Define if you have the <inttypes.h> header file.  */
+#undef HAVE_INTTYPES_H
 
-/* Whether to build pcntl as dynamic module */
-#undef COMPILE_DL_PCNTL
+/* Define if you have the <langinfo.h> header file.  */
+#undef HAVE_LANGINFO_H
 
-/* Whether to build pcre as dynamic module */
-#undef COMPILE_DL_PCRE
+/* Define if you have the <limits.h> header file.  */
+#undef HAVE_LIMITS_H
 
-/* Whether to build pdo as dynamic module */
-#undef COMPILE_DL_PDO
+/* Define if you have the <locale.h> header file.  */
+#undef HAVE_LOCALE_H
 
-/* Whether to build pdo_dblib as dynamic module */
-#undef COMPILE_DL_PDO_DBLIB
+/* Define if you have the <malloc.h> header file.  */
+#undef HAVE_MALLOC_H
 
-/* Whether to build pdo_firebird as dynamic module */
-#undef COMPILE_DL_PDO_FIREBIRD
+/* Define if you have the <monetary.h> header file.  */
+#undef HAVE_MONETARY_H
 
-/* Whether to build pdo_mysql as dynamic module */
-#undef COMPILE_DL_PDO_MYSQL
+/* Define if you have the <ndir.h> header file.  */
+#undef HAVE_NDIR_H
 
-/* Whether to build pdo_oci as dynamic module */
-#undef COMPILE_DL_PDO_OCI
+/* Define if you have the <netdb.h> header file.  */
+#undef HAVE_NETDB_H
 
-/* Whether to build pdo_odbc as dynamic module */
-#undef COMPILE_DL_PDO_ODBC
+/* Define if you have the <netinet/in.h> header file.  */
+#undef HAVE_NETINET_IN_H
 
-/* Whether to build pdo_pgsql as dynamic module */
-#undef COMPILE_DL_PDO_PGSQL
+/* Define if you have the <netinet/tcp.h> header file.  */
+#undef HAVE_NETINET_TCP_H
 
-/* Whether to build pdo_sqlite as dynamic module */
-#undef COMPILE_DL_PDO_SQLITE
+/* Define if you have the <openssl/crypto.h> header file.  */
+#undef HAVE_OPENSSL_CRYPTO_H
 
-/* Whether to build pgsql as dynamic module */
-#undef COMPILE_DL_PGSQL
+/* Define if you have the <pwd.h> header file.  */
+#undef HAVE_PWD_H
 
-/* Whether to build phar as dynamic module */
-#undef COMPILE_DL_PHAR
+/* Define if you have the <resolv.h> header file.  */
+#undef HAVE_RESOLV_H
 
-/* Whether to build posix as dynamic module */
-#undef COMPILE_DL_POSIX
+/* Define if you have the <signal.h> header file.  */
+#undef HAVE_SIGNAL_H
 
-/* Whether to build pspell as dynamic module */
-#undef COMPILE_DL_PSPELL
+/* Define if you have the <st.h> header file.  */
+#undef HAVE_ST_H
 
-/* Whether to build readline as dynamic module */
-#undef COMPILE_DL_READLINE
+/* Define if you have the <stdarg.h> header file.  */
+#undef HAVE_STDARG_H
 
-/* Whether to build recode as dynamic module */
-#undef COMPILE_DL_RECODE
+/* Define if you have the <stdbool.h> header file.  */
+#undef HAVE_STDBOOL_H
 
-/* Whether to build reflection as dynamic module */
-#undef COMPILE_DL_REFLECTION
+/* Define if you have the <stdint.h> header file.  */
+#undef HAVE_STDINT_H
 
-/* Whether to build session as dynamic module */
-#undef COMPILE_DL_SESSION
+/* Define if you have the <stdio.h> header file.  */
+#undef HAVE_STDIO_H
 
-/* Whether to build shmop as dynamic module */
-#undef COMPILE_DL_SHMOP
+/* Define if you have the <stdlib.h> header file.  */
+#undef HAVE_STDLIB_H
 
-/* Whether to build simplexml as dynamic module */
-#undef COMPILE_DL_SIMPLEXML
+/* Define if you have the <string.h> header file.  */
+#undef HAVE_STRING_H
 
-/* Whether to build snmp as dynamic module */
-#undef COMPILE_DL_SNMP
+/* Define if you have the <strings.h> header file.  */
+#undef HAVE_STRINGS_H
 
-/* Whether to build soap as dynamic module */
-#undef COMPILE_DL_SOAP
+/* Define if you have the <sys/dir.h> header file.  */
+#undef HAVE_SYS_DIR_H
 
-/* Whether to build sockets as dynamic module */
-#undef COMPILE_DL_SOCKETS
+/* Define if you have the <sys/file.h> header file.  */
+#undef HAVE_SYS_FILE_H
 
-/* Whether to build spl as dynamic module */
-#undef COMPILE_DL_SPL
+/* Define if you have the <sys/ioctl.h> header file.  */
+#undef HAVE_SYS_IOCTL_H
 
-/* Whether to build sqlite as dynamic module */
-#undef COMPILE_DL_SQLITE
+/* Define if you have the <sys/ipc.h> header file.  */
+#undef HAVE_SYS_IPC_H
 
-/* Whether to build sqlite3 as dynamic module */
-#undef COMPILE_DL_SQLITE3
+/* Define if you have the <sys/loadavg.h> header file.  */
+#undef HAVE_SYS_LOADAVG_H
 
-/* Whether to build standard as dynamic module */
-#undef COMPILE_DL_STANDARD
+/* Define if you have the <sys/mkdev.h> header file.  */
+#undef HAVE_SYS_MKDEV_H
 
-/* Whether to build sybase_ct as dynamic module */
-#undef COMPILE_DL_SYBASE_CT
+/* Define if you have the <sys/mman.h> header file.  */
+#undef HAVE_SYS_MMAN_H
 
-/* Whether to build sysvmsg as dynamic module */
-#undef COMPILE_DL_SYSVMSG
+/* Define if you have the <sys/mount.h> header file.  */
+#undef HAVE_SYS_MOUNT_H
 
-/* Whether to build sysvsem as dynamic module */
-#undef COMPILE_DL_SYSVSEM
+/* Define if you have the <sys/ndir.h> header file.  */
+#undef HAVE_SYS_NDIR_H
 
-/* Whether to build sysvshm as dynamic module */
-#undef COMPILE_DL_SYSVSHM
+/* Define if you have the <sys/param.h> header file.  */
+#undef HAVE_SYS_PARAM_H
 
-/* Whether to build tidy as dynamic module */
-#undef COMPILE_DL_TIDY
+/* Define if you have the <sys/poll.h> header file.  */
+#undef HAVE_SYS_POLL_H
 
-/* Whether to build tokenizer as dynamic module */
-#undef COMPILE_DL_TOKENIZER
+/* Define if you have the <sys/resource.h> header file.  */
+#undef HAVE_SYS_RESOURCE_H
 
-/* Whether to build wddx as dynamic module */
-#undef COMPILE_DL_WDDX
+/* Define if you have the <sys/select.h> header file.  */
+#undef HAVE_SYS_SELECT_H
 
-/* Whether to build xml as dynamic module */
-#undef COMPILE_DL_XML
+/* Define if you have the <sys/socket.h> header file.  */
+#undef HAVE_SYS_SOCKET_H
 
-/* Whether to build xmlreader as dynamic module */
-#undef COMPILE_DL_XMLREADER
+/* Define if you have the <sys/stat.h> header file.  */
+#undef HAVE_SYS_STAT_H
 
-/* Whether to build xmlrpc as dynamic module */
-#undef COMPILE_DL_XMLRPC
+/* Define if you have the <sys/statfs.h> header file.  */
+#undef HAVE_SYS_STATFS_H
 
-/* Whether to build xmlwriter as dynamic module */
-#undef COMPILE_DL_XMLWRITER
+/* Define if you have the <sys/statvfs.h> header file.  */
+#undef HAVE_SYS_STATVFS_H
 
-/* Whether to build xsl as dynamic module */
-#undef COMPILE_DL_XSL
+/* Define if you have the <sys/sysexits.h> header file.  */
+#undef HAVE_SYS_SYSEXITS_H
 
-/* Whether to build zip as dynamic module */
-#undef COMPILE_DL_ZIP
+/* Define if you have the <sys/time.h> header file.  */
+#undef HAVE_SYS_TIME_H
 
-/* Whether to build zlib as dynamic module */
-#undef COMPILE_DL_ZLIB
+/* Define if you have the <sys/times.h> header file.  */
+#undef HAVE_SYS_TIMES_H
 
-/* */
-#undef COOKIE_IO_FUNCTIONS_T
+/* Define if you have the <sys/types.h> header file.  */
+#undef HAVE_SYS_TYPES_H
 
-/* */
-#undef COOKIE_SEEKER_USES_OFF64_T
+/* Define if you have the <sys/uio.h> header file.  */
+#undef HAVE_SYS_UIO_H
 
-/* Define to one of `_getb67', `GETB67', `getb67' for Cray-2 and Cray-YMP
-   systems. This function is required for `alloca.c' support on those systems.
-   */
-#undef CRAY_STACKSEG_END
+/* Define if you have the <sys/un.h> header file.  */
+#undef HAVE_SYS_UN_H
 
-/* Define if crypt_r has uses CRYPTD */
-#undef CRYPT_R_CRYPTD
+/* Define if you have the <sys/utsname.h> header file.  */
+#undef HAVE_SYS_UTSNAME_H
 
-/* Define if struct crypt_data requires _GNU_SOURCE */
-#undef CRYPT_R_GNU_SOURCE
+/* Define if you have the <sys/varargs.h> header file.  */
+#undef HAVE_SYS_VARARGS_H
 
-/* Define if crypt_r uses struct crypt_data */
-#undef CRYPT_R_STRUCT_CRYPT_DATA
+/* Define if you have the <sys/vfs.h> header file.  */
+#undef HAVE_SYS_VFS_H
 
-/* Define to 1 if using `alloca.c'. */
-#undef C_ALLOCA
+/* Define if you have the <sys/wait.h> header file.  */
+#undef HAVE_SYS_WAIT_H
 
-/* Define if the target system is darwin */
-#undef DARWIN
+/* Define if you have the <sysexits.h> header file.  */
+#undef HAVE_SYSEXITS_H
 
-/* */
-#undef DB1_INCLUDE_FILE
+/* Define if you have the <syslog.h> header file.  */
+#undef HAVE_SYSLOG_H
 
-/* */
-#undef DB1_VERSION
+/* Define if you have the <termios.h> header file.  */
+#undef HAVE_TERMIOS_H
 
-/* */
-#undef DB2_INCLUDE_FILE
+/* Define if you have the <time.h> header file.  */
+#undef HAVE_TIME_H
 
-/* */
-#undef DB3_INCLUDE_FILE
+/* Define if you have the <tuxmodule.h> header file.  */
+#undef HAVE_TUXMODULE_H
 
-/* */
-#undef DB4_INCLUDE_FILE
+/* Define if you have the <unistd.h> header file.  */
+#undef HAVE_UNISTD_H
 
-/* */
-#undef DBA_CDB
+/* Define if you have the <unix.h> header file.  */
+#undef HAVE_UNIX_H
 
-/* */
-#undef DBA_CDB_BUILTIN
+/* Define if you have the <utime.h> header file.  */
+#undef HAVE_UTIME_H
 
-/* */
-#undef DBA_CDB_MAKE
+/* Define if you have the <wchar.h> header file.  */
+#undef HAVE_WCHAR_H
 
-/* */
-#undef DBA_DB1
+/* Define if you have the <xmlparse.h> header file.  */
+#undef HAVE_XMLPARSE_H
 
-/* */
-#undef DBA_DB2
+/* Define if you have the <xmltok.h> header file.  */
+#undef HAVE_XMLTOK_H
 
-/* */
-#undef DBA_DB3
+/* Define if you have the m library (-lm).  */
+#undef HAVE_LIBM
 
-/* */
-#undef DBA_DB4
+/* Define if the target system has /dev/urandom device */
+#undef HAVE_DEV_URANDOM
 
-/* */
-#undef DBA_DBM
+/* Whether you have AOLserver */
+#undef HAVE_AOLSERVER
 
-/* */
-#undef DBA_FLATFILE
+/*   */
+#undef HAVE_AP_CONFIG_H
 
-/* */
-#undef DBA_GDBM
+/*   */
+#undef HAVE_AP_COMPAT_H
 
-/* */
-#undef DBA_INIFILE
+/*   */
+#undef HAVE_APACHE
 
-/* */
-#undef DBA_NDBM
+/*   */
+#undef HAVE_APACHE
 
-/* */
-#undef DBA_QDBM
+/*   */
+#undef HAVE_AP_CONFIG_H
 
-/* */
-#undef DBM_INCLUDE_FILE
+/*   */
+#undef HAVE_AP_CONFIG_H
 
-/* */
-#undef DBM_VERSION
+/*   */
+#undef HAVE_AP_COMPAT_H
 
-/* */
-#undef DEFAULT_SHORT_OPEN_TAG
+/*   */
+#undef HAVE_OLD_COMPAT_H
 
-/* Define if dlsym() requires a leading underscore in symbol names. */
-#undef DLSYM_NEEDS_UNDERSCORE
+/*   */
+#undef HAVE_AP_CONFIG_H
 
-/* Whether to enable chroot() function */
-#undef ENABLE_CHROOT_FUNC
+/*   */
+#undef HAVE_AP_COMPAT_H
 
-/* */
-#undef ENABLE_GD_TTF
+/*   */
+#undef HAVE_OLD_COMPAT_H
 
-/* */
-#undef ENCHANT_VERSION_STRING
+/*   */
+#undef HAVE_AP_CONFIG_H
 
-/* */
-#undef GDBM_INCLUDE_FILE
+/*   */
+#undef HAVE_AP_COMPAT_H
 
-/* Whether you use GNU Pth */
-#undef GNUPTH
+/*   */
+#undef HAVE_OLD_COMPAT_H
 
-/* Whether 3 arg set_rebind_proc() */
-#undef HAVE_3ARG_SETREBINDPROC
+/*   */
+#undef USE_TRANSFER_TABLES
 
-/* Define to 1 if you have the `acosh' function. */
-#undef HAVE_ACOSH
+/*   */
+#undef PHP_APACHE_HAVE_CLIENT_FD
 
-/* */
-#undef HAVE_ADABAS
+/*   */
+#undef HAVE_AP_CONFIG_H
 
-/* whether the compiler supports __alignof__ */
-#undef HAVE_ALIGNOF
+/*   */
+#undef HAVE_AP_COMPAT_H
 
-/* Define to 1 if you have `alloca', as a function or macro. */
-#undef HAVE_ALLOCA
+/*   */
+#undef HAVE_APACHE_HOOKS
 
-/* Define to 1 if you have <alloca.h> and it should be used (not on Ultrix).
-   */
-#undef HAVE_ALLOCA_H
+/*   */
+#undef HAVE_APACHE
 
-/* Define to 1 if you have the `alphasort' function. */
-#undef HAVE_ALPHASORT
+/*   */
+#undef HAVE_AP_CONFIG_H
 
-/* Whether you have AOLserver */
-#undef HAVE_AOLSERVER
+/*   */
+#undef HAVE_AP_CONFIG_H
 
-/* */
-#undef HAVE_APACHE
+/*   */
+#undef HAVE_AP_COMPAT_H
 
-/* */
-#undef HAVE_APACHE_HOOKS
+/*   */
+#undef HAVE_OLD_COMPAT_H
 
-/* Define to 1 if you have the <ApplicationServices/ApplicationServices.h>
-   header file. */
-#undef HAVE_APPLICATIONSERVICES_APPLICATIONSERVICES_H
+/*   */
+#undef HAVE_AP_CONFIG_H
 
-/* */
+/*   */
 #undef HAVE_AP_COMPAT_H
 
-/* */
+/*   */
+#undef HAVE_OLD_COMPAT_H
+
+/*   */
 #undef HAVE_AP_CONFIG_H
 
-/* Define to 1 if you have the <arpa/inet.h> header file. */
-#undef HAVE_ARPA_INET_H
+/*   */
+#undef HAVE_AP_COMPAT_H
 
-/* Define to 1 if you have the <arpa/nameser.h> header file. */
-#undef HAVE_ARPA_NAMESER_H
+/*   */
+#undef HAVE_OLD_COMPAT_H
 
-/* Define to 1 if you have the `asctime_r' function. */
-#undef HAVE_ASCTIME_R
+/*   */
+#undef USE_TRANSFER_TABLES
 
-/* Define to 1 if you have the `asinh' function. */
-#undef HAVE_ASINH
+/*   */
+#undef PHP_APACHE_HAVE_CLIENT_FD
 
-/* Define to 1 if you have the `asprintf' function. */
-#undef HAVE_ASPRINTF
+/* Whether to compile with Caudium support */
+#undef HAVE_CAUDIUM
 
-/* Define to 1 if you have the <assert.h> header file. */
-#undef HAVE_ASSERT_H
+/* Whether you have a Continuity Server */
+#undef HAVE_CONTINUITY
 
-/* Define to 1 if you have the `atanh' function. */
-#undef HAVE_ATANH
+/* do we have prctl? */
+#undef HAVE_PRCTL
 
-/* whether atof() accepts INF */
-#undef HAVE_ATOF_ACCEPTS_INF
+/* do we have clock_gettime? */
+#undef HAVE_CLOCK_GETTIME
 
-/* whether atof() accepts NAN */
-#undef HAVE_ATOF_ACCEPTS_NAN
+/* do we have clock_get_time? */
+#undef HAVE_CLOCK_GET_TIME
 
-/* Define to 1 if you have the `atoll' function. */
-#undef HAVE_ATOLL
+/* do we have ptrace? */
+#undef HAVE_PTRACE
 
-/* Define to 1 if you have the <atomic.h> header file. */
-#undef HAVE_ATOMIC_H
+/* do we have mach_vm_read? */
+#undef HAVE_MACH_VM_READ
 
-/* whether the compiler supports __attribute__ ((__aligned__)) */
-#undef HAVE_ATTRIBUTE_ALIGNED
+/* /proc/pid/mem interface */
+#undef PROC_MEM_FILE
 
-/* Whether you have bcmath */
-#undef HAVE_BCMATH
+/* Define to 1 if gcc supports __sync_bool_compare_and_swap() a.o. */
+#undef HAVE_BUILTIN_ATOMIC
 
-/* */
-#undef HAVE_BIND_TEXTDOMAIN_CODESET
+/* do we have TCP_INFO? */
+#undef HAVE_LQ_TCP_INFO
 
-/* */
-#undef HAVE_BIRDSTEP
+/* do we have SO_LISTENQxxx? */
+#undef HAVE_LQ_SO_LISTENQ
 
-/* Define if system has broken getcwd */
-#undef HAVE_BROKEN_GETCWD
+/* do we have sysconf? */
+#undef HAVE_SYSCONF
 
-/* Define if your glibc borks on fopen with mode a+ */
-#undef HAVE_BROKEN_GLIBC_FOPEN_APPEND
+/* do we have times? */
+#undef HAVE_TIMES
 
-/* Whether we have librecode 3.5 */
-#undef HAVE_BROKEN_RECODE
+/* do we have kqueue? */
+#undef HAVE_KQUEUE
 
-/* Konstantin Chuguev's iconv implementation */
-#undef HAVE_BSD_ICONV
+/* do we have port framework? */
+#undef HAVE_PORT
 
-/* */
-#undef HAVE_BUILD_DEFS_H
+/* do we have /dev/poll? */
+#undef HAVE_DEVPOLL
 
-/* Define to 1 if gcc supports __sync_bool_compare_and_swap() a.o. */
-#undef HAVE_BUILTIN_ATOMIC
+/* do we have epoll? */
+#undef HAVE_EPOLL
 
-/* */
-#undef HAVE_BUNDLED_PCRE
+/* do we have poll? */
+#undef HAVE_POLL
 
-/* */
-#undef HAVE_BZ2
+/* do we have select? */
+#undef HAVE_SELECT
 
-/* */
-#undef HAVE_CALENDAR
+/* fpm user name */
+#undef PHP_FPM_USER
 
-/* Whether to compile with Caudium support */
-#undef HAVE_CAUDIUM
+/* fpm group name */
+#undef PHP_FPM_GROUP
 
-/* Define to 1 if you have the `chroot' function. */
-#undef HAVE_CHROOT
+/*   */
+#undef WITH_ZEUS
 
-/* Define to 1 if you have the `clearenv' function. */
-#undef HAVE_CLEARENV
+/* Whether you have a Netscape/iPlanet/Sun Webserver */
+#undef HAVE_NSAPI
 
-/* */
-#undef HAVE_CLI0CLI_H
+/* Whether you have phttpd */
+#undef HAVE_PHTTPD
 
-/* */
-#undef HAVE_CLI0CORE_H
+/* whether you want Pi3Web support */
+#undef WITH_PI3WEB
 
-/* */
-#undef HAVE_CLI0DEFS_H
+/* Whether you use Roxen */
+#undef HAVE_ROXEN
 
-/* */
-#undef HAVE_CLI0ENV_H
+/* Whether to use Roxen in ZTS mode */
+#undef ROXEN_USE_ZTS
 
-/* */
-#undef HAVE_CLI0EXT_H
+/* Define if the socklen_t typedef is in sys/socket.h */
+#undef HAVE_SOCKLEN_T
 
-/* do we have clock_gettime? */
-#undef HAVE_CLOCK_GETTIME
+/* Define if sockaddr_un in sys/un.h contains a sun_len component */
+#undef HAVE_SOCKADDR_UN_SUN_LEN
 
-/* do we have clock_get_time? */
-#undef HAVE_CLOCK_GET_TIME
+/* Define if cross-process locking is required by accept() */
+#undef USE_LOCKING
 
-/* Whether you have struct cmsghdr */
-#undef HAVE_CMSGHDR
+/* Define if system uses EBCDIC */
+#undef CHARSET_EBCDIC
 
-/* */
-#undef HAVE_CODBC
+/* Define if processor uses big-endian word */
+#undef WORDS_BIGENDIAN
 
-/* */
-#undef HAVE_COLORCLOSESTHWB
+/* whether write(2) works */
+#undef PHP_WRITE_STDOUT
 
-/* Whether you have a Continuity Server */
-#undef HAVE_CONTINUITY
+/*   */
+#undef HAVE_SOCKET
 
-/* Define to 1 if you have the `CreateProcess' function. */
-#undef HAVE_CREATEPROCESS
+/*   */
+#undef HAVE_SOCKET
 
-/* */
-#undef HAVE_CRYPT
+/*   */
+#undef HAVE_LIBSOCKET
 
-/* Define to 1 if you have the <crypt.h> header file. */
-#undef HAVE_CRYPT_H
+/*   */
+#undef HAVE_SOCKETPAIR
 
-/* Define to 1 if you have the `crypt_r' function. */
-#undef HAVE_CRYPT_R
+/*   */
+#undef HAVE_SOCKETPAIR
 
-/* Define to 1 if you have the `ctermid' function. */
-#undef HAVE_CTERMID
+/*   */
+#undef HAVE_LIBSOCKET
 
-/* Define to 1 if you have the `ctime_r' function. */
-#undef HAVE_CTIME_R
+/*   */
+#undef HAVE_HTONL
 
-/* */
-#undef HAVE_CTYPE
+/*   */
+#undef HAVE_HTONL
 
-/* */
-#undef HAVE_CURL
+/*   */
+#undef HAVE_LIBSOCKET
 
-/* */
-#undef HAVE_CURL_EASY_STRERROR
+/*   */
+#undef HAVE_GETHOSTNAME
 
-/* Have cURL with GnuTLS support */
-#undef HAVE_CURL_GNUTLS
+/*   */
+#undef HAVE_GETHOSTNAME
 
-/* */
-#undef HAVE_CURL_MULTI_STRERROR
+/*   */
+#undef HAVE_LIBNSL
 
-/* Have cURL with OpenSSL support */
-#undef HAVE_CURL_OPENSSL
+/*   */
+#undef HAVE_GETHOSTBYADDR
 
-/* Have cURL with SSL support */
-#undef HAVE_CURL_SSL
+/*   */
+#undef HAVE_GETHOSTBYADDR
 
-/* */
-#undef HAVE_CURL_VERSION_INFO
+/*   */
+#undef HAVE_LIBNSL
 
-/* Define to 1 if you have the `cuserid' function. */
-#undef HAVE_CUSERID
+/*   */
+#undef HAVE_YP_GET_DEFAULT_DOMAIN
 
-/* */
-#undef HAVE_DBA
+/*   */
+#undef HAVE_YP_GET_DEFAULT_DOMAIN
 
-/* Whether you want DBMaker */
-#undef HAVE_DBMAKER
+/*   */
+#undef HAVE_LIBNSL
 
-/* */
-#undef HAVE_DCNGETTEXT
+/*   */
+#undef HAVE_DLOPEN
 
-/* Whether system headers declare timezone */
-#undef HAVE_DECLARED_TIMEZONE
+/*   */
+#undef HAVE_DLOPEN
 
-/* Define to 1 if you have the declaration of `tzname', and to 0 if you don't.
-   */
-#undef HAVE_DECL_TZNAME
+/*   */
+#undef HAVE_LIBDL
 
-/* Define to 1 if you have the <default_store.h> header file. */
-#undef HAVE_DEFAULT_STORE_H
+/*   */
+#undef HAVE_LIBDL
 
-/* do we have /dev/poll? */
-#undef HAVE_DEVPOLL
+/*   */
+#undef HAVE_INET_ATON
 
-/* Define if the target system has /dev/urandom device */
-#undef HAVE_DEV_URANDOM
+/*   */
+#undef HAVE_INET_ATON
 
-/* Define to 1 if you have the <dirent.h> header file. */
-#undef HAVE_DIRENT_H
+/*   */
+#undef HAVE_LIBRESOLV
 
-/* Define to 1 if you have the <dlfcn.h> header file. */
-#undef HAVE_DLFCN_H
+/*   */
+#undef HAVE_INET_ATON
 
-/* */
-#undef HAVE_DLOPEN
+/*   */
+#undef HAVE_LIBBIND
 
-/* Whether you have dmalloc */
-#undef HAVE_DMALLOC
+/*   */
+#undef HAVE_FOPENCOOKIE
 
-/* */
-#undef HAVE_DNGETTEXT
+/*   */
+#undef COOKIE_IO_FUNCTIONS_T
 
-/* Define to 1 if you have the <dns.h> header file. */
-#undef HAVE_DNS_H
+/*   */
+#undef COOKIE_SEEKER_USES_OFF64_T
 
-/* */
-#undef HAVE_DNS_SEARCH
+/* Define if system has broken getcwd */
+#undef HAVE_BROKEN_GETCWD
 
-/* */
-#undef HAVE_DN_EXPAND
+/* Define if your glibc borks on fopen with mode a+ */
+#undef HAVE_BROKEN_GLIBC_FOPEN_APPEND
 
-/* */
-#undef HAVE_DN_SKIPNAME
+/* Whether localtime_r is declared */
+#undef MISSING_LOCALTIME_R_DECL
 
-/* */
-#undef HAVE_DOM
+/* Whether gmtime_r is declared */
+#undef MISSING_GMTIME_R_DECL
 
-/* Define to 1 if you don't have `vprintf' but do have `_doprnt.' */
-#undef HAVE_DOPRNT
+/* Whether asctime_r is declared */
+#undef MISSING_ASCTIME_R_DECL
 
-/* OpenSSL 0.9.7 or later */
-#undef HAVE_DSA_DEFAULT_METHOD
+/* Whether ctime_r is declared */
+#undef MISSING_CTIME_R_DECL
 
-/* embedded MySQL support enabled */
-#undef HAVE_EMBEDDED_MYSQLI
+/* Whether strtok_r is declared */
+#undef MISSING_STRTOK_R_DECL
 
-/* */
-#undef HAVE_EMPRESS
+/*   */
+#undef MISSING_FCLOSE_DECL
 
-/* */
-#undef HAVE_ENCHANT
+/*   */
+#undef MISSING_FCLOSE_DECL
 
-/* */
-#undef HAVE_ENCHANT_BROKER_SET_PARAM
+/* whether you have tm_gmtoff in struct tm */
+#undef HAVE_TM_GMTOFF
 
-/* do we have epoll? */
-#undef HAVE_EPOLL
+/* whether you have struct flock */
+#undef HAVE_STRUCT_FLOCK
 
-/* Define to 1 if you have the <errno.h> header file. */
-#undef HAVE_ERRNO_H
+/* Whether you have socklen_t */
+#undef HAVE_SOCKLEN_T
 
-/* */
-#undef HAVE_ESOOB
+/* Size of intmax_t */
+#undef SIZEOF_INTMAX_T
 
-/* Whether you want EXIF (metadata from images) support */
-#undef HAVE_EXIF
+/* Whether intmax_t is available */
+#undef HAVE_INTMAX_T
 
-/* Define to 1 if you have the `fabsf' function. */
-#undef HAVE_FABSF
+/* Size of ssize_t */
+#undef SIZEOF_SSIZE_T
+
+/* Whether ssize_t is available */
+#undef HAVE_SSIZE_T
 
-/* Define to 1 if you have the <fcntl.h> header file. */
-#undef HAVE_FCNTL_H
+/* Size of ptrdiff_t */
+#undef SIZEOF_PTRDIFF_T
 
-/* Define to 1 if you have the `finite' function. */
-#undef HAVE_FINITE
+/* Whether ptrdiff_t is available */
+#undef HAVE_PTRDIFF_T
 
-/* Define to 1 if you have the `flock' function. */
-#undef HAVE_FLOCK
+/* Whether you have struct sockaddr_storage */
+#undef HAVE_SOCKADDR_STORAGE
 
-/* Define to 1 if you have the `floorf' function. */
-#undef HAVE_FLOORF
+/* Whether struct sockaddr has field sa_len */
+#undef HAVE_SOCKADDR_SA_LEN
 
-/* Define if flush should be called explicitly after a buffered io. */
-#undef HAVE_FLUSHIO
+/*   */
+#undef HAVE_NANOSLEEP
 
-/* Define to 1 if your system has a working POSIX `fnmatch' function. */
-#undef HAVE_FNMATCH
+/*   */
+#undef HAVE_LIBRT
 
-/* */
-#undef HAVE_FOPENCOOKIE
+/* Define if you have the getaddrinfo function */
+#undef HAVE_GETADDRINFO
 
-/* Define to 1 if you have the `fork' function. */
-#undef HAVE_FORK
+/* Define if you have the __sync_fetch_and_add function */
+#undef HAVE_SYNC_FETCH_AND_ADD
 
-/* Define to 1 if you have the `fpclass' function. */
-#undef HAVE_FPCLASS
+/* Whether system headers declare timezone */
+#undef HAVE_DECLARED_TIMEZONE
 
-/* whether fpsetprec is present and usable */
-#undef HAVE_FPSETPREC
+/* Whether you have HP-UX 10.x */
+#undef PHP_HPUX_TIME_R
 
-/* whether FPU control word can be manipulated by inline assembler */
-#undef HAVE_FPU_INLINE_ASM_X86
+/* Whether you have IRIX-style functions */
+#undef PHP_IRIX_TIME_R
 
-/* whether floatingpoint.h defines fp_except */
-#undef HAVE_FP_EXCEPT
+/* whether you have POSIX readdir_r */
+#undef HAVE_POSIX_READDIR_R
 
-/* */
-#undef HAVE_FREETDS
+/* whether you have old-style readdir_r */
+#undef HAVE_OLD_READDIR_R
 
-/* Define to 1 if you have the `ftok' function. */
-#undef HAVE_FTOK
+/*   */
+#undef in_addr_t
 
-/* Whether you want FTP support */
-#undef HAVE_FTP
+/* Define if crypt_r has uses CRYPTD */
+#undef CRYPT_R_CRYPTD
 
-/* Define to 1 if you have the `funopen' function. */
-#undef HAVE_FUNOPEN
+/* Define if crypt_r uses struct crypt_data */
+#undef CRYPT_R_STRUCT_CRYPT_DATA
 
-/* Define to 1 if you have the `gai_strerror' function. */
-#undef HAVE_GAI_STRERROR
+/* Define if struct crypt_data requires _GNU_SOURCE */
+#undef CRYPT_R_GNU_SOURCE
 
 /* Whether you have gcov */
 #undef HAVE_GCOV
 
-/* Define to 1 if you have the `gcvt' function. */
-#undef HAVE_GCVT
+/*   */
+#undef PHP_SAFE_MODE
 
-/* */
-#undef HAVE_GDIMAGECOLORRESOLVE
+/*   */
+#undef PHP_SAFE_MODE
 
-/* */
-#undef HAVE_GD_BUNDLED
+/*   */
+#undef PHP_SAFE_MODE_EXEC_DIR
 
-/* */
-#undef HAVE_GD_CACHE_CREATE
+/*   */
+#undef PHP_SAFE_MODE_EXEC_DIR
 
-/* */
-#undef HAVE_GD_DYNAMIC_CTX_EX
+/*   */
+#undef PHP_SIGCHILD
 
-/* */
-#undef HAVE_GD_FONTCACHESHUTDOWN
+/*   */
+#undef PHP_SIGCHILD
 
-/* */
-#undef HAVE_GD_FONTMUTEX
+/*   */
+#undef MAGIC_QUOTES
 
-/* */
-#undef HAVE_GD_FREEFONTCACHE
+/*   */
+#undef MAGIC_QUOTES
 
-/* */
-#undef HAVE_GD_GD2
+/*   */
+#undef DEFAULT_SHORT_OPEN_TAG
 
-/* */
-#undef HAVE_GD_GIF_CREATE
+/*   */
+#undef DEFAULT_SHORT_OPEN_TAG
 
-/* */
-#undef HAVE_GD_GIF_CTX
+/* Whether you have dmalloc */
+#undef HAVE_DMALLOC
 
-/* */
-#undef HAVE_GD_GIF_READ
+/* Whether to enable IPv6 support */
+#undef HAVE_IPV6
 
-/* */
-#undef HAVE_GD_IMAGEELLIPSE
+/*  Define if int32_t type is present.  */
+#undef HAVE_INT32_T
 
-/* */
-#undef HAVE_GD_IMAGESETBRUSH
+/*  Define if uint32_t type is present.  */
+#undef HAVE_UINT32_T
 
-/* */
-#undef HAVE_GD_IMAGESETTILE
+/* Whether to build date as dynamic module */
+#undef COMPILE_DL_DATE
 
-/* */
-#undef HAVE_GD_IMAGE_CONVOLUTION
+/* Whether to build ereg as dynamic module */
+#undef COMPILE_DL_EREG
 
-/* */
-#undef HAVE_GD_IMAGE_PIXELATE
+/*   */
+#undef HAVE_REGEX_T_RE_MAGIC
 
-/* */
-#undef HAVE_GD_JPG
+/*   */
+#undef HSREGEX
 
-/* */
-#undef HAVE_GD_PNG
+/*   */
+#undef REGEX
 
-/* */
-#undef HAVE_GD_STRINGFT
+/*   */
+#undef REGEX
 
-/* */
-#undef HAVE_GD_STRINGFTEX
+/* 1 */
+#undef HAVE_REGEX_T_RE_MAGIC
 
-/* */
-#undef HAVE_GD_STRINGTTF
+/*   */
+#undef HAVE_LIBXML
 
-/* */
-#undef HAVE_GD_WBMP
+/*   */
+#undef HAVE_LIBXML
 
-/* */
-#undef HAVE_GD_XBM
+/* Whether to build libxml as dynamic module */
+#undef COMPILE_DL_LIBXML
 
-/* */
-#undef HAVE_GD_XPM
+/* Whether to build openssl as dynamic module */
+#undef COMPILE_DL_OPENSSL
 
-/* Define if you have the getaddrinfo function */
-#undef HAVE_GETADDRINFO
+/* OpenSSL 0.9.7 or later */
+#undef HAVE_DSA_DEFAULT_METHOD
 
-/* Define to 1 if you have the `getcwd' function. */
-#undef HAVE_GETCWD
+/* OpenSSL 0.9.7 or later */
+#undef HAVE_DSA_DEFAULT_METHOD
 
-/* Define to 1 if you have the `getgrgid_r' function. */
-#undef HAVE_GETGRGID_R
+/*   */
+#undef HAVE_OPENSSL_EXT
 
-/* Define to 1 if you have the `getgrnam_r' function. */
-#undef HAVE_GETGRNAM_R
+/*   */
+#undef HAVE_PCRE
 
-/* Define to 1 if you have the `getgroups' function. */
-#undef HAVE_GETGROUPS
+/* Whether to build pcre as dynamic module */
+#undef COMPILE_DL_PCRE
 
-/* */
-#undef HAVE_GETHOSTBYADDR
+/* Whether to build pcre as dynamic module */
+#undef COMPILE_DL_PCRE
 
-/* Define to 1 if you have the `gethostname' function. */
-#undef HAVE_GETHOSTNAME
+/*   */
+#undef HAVE_BUNDLED_PCRE
 
-/* Define to 1 if you have the `getloadavg' function. */
-#undef HAVE_GETLOADAVG
+/* have commercial sqlite3 with crypto support */
+#undef HAVE_SQLITE3_KEY
 
-/* Define to 1 if you have the `getlogin' function. */
-#undef HAVE_GETLOGIN
+/* have sqlite3 with extension support */
+#undef SQLITE_OMIT_LOAD_EXTENSION
 
-/* Define to 1 if you have the `getopt' function. */
-#undef HAVE_GETOPT
+/*   */
+#undef HAVE_SQLITE3
 
-/* Define to 1 if you have the `getpgid' function. */
-#undef HAVE_GETPGID
+/* Whether to build sqlite3 as dynamic module */
+#undef COMPILE_DL_SQLITE3
 
-/* Define to 1 if you have the `getpid' function. */
-#undef HAVE_GETPID
+/* Whether to build zlib as dynamic module */
+#undef COMPILE_DL_ZLIB
 
-/* Define to 1 if you have the `getpriority' function. */
-#undef HAVE_GETPRIORITY
+/*   */
+#undef HAVE_ZLIB
 
-/* Define to 1 if you have the `getprotobyname' function. */
-#undef HAVE_GETPROTOBYNAME
+/* Whether to build bcmath as dynamic module */
+#undef COMPILE_DL_BCMATH
 
-/* Define to 1 if you have the `getprotobynumber' function. */
-#undef HAVE_GETPROTOBYNUMBER
+/* Whether you have bcmath */
+#undef HAVE_BCMATH
 
-/* Define to 1 if you have the `getpwnam_r' function. */
-#undef HAVE_GETPWNAM_R
+/*   */
+#undef HAVE_BZ2
 
-/* Define to 1 if you have the `getpwuid_r' function. */
-#undef HAVE_GETPWUID_R
+/* Whether to build bz2 as dynamic module */
+#undef COMPILE_DL_BZ2
 
-/* Define to 1 if you have the `getrlimit' function. */
-#undef HAVE_GETRLIMIT
+/*   */
+#undef HAVE_CALENDAR
 
-/* Define to 1 if you have the `getrusage' function. */
-#undef HAVE_GETRUSAGE
+/* Whether to build calendar as dynamic module */
+#undef COMPILE_DL_CALENDAR
 
-/* Define to 1 if you have the `getservbyname' function. */
-#undef HAVE_GETSERVBYNAME
+/*   */
+#undef HAVE_CTYPE
 
-/* Define to 1 if you have the `getservbyport' function. */
-#undef HAVE_GETSERVBYPORT
+/* Whether to build ctype as dynamic module */
+#undef COMPILE_DL_CTYPE
 
-/* Define to 1 if you have the `getsid' function. */
-#undef HAVE_GETSID
+/* Have cURL with  SSL support */
+#undef HAVE_CURL_SSL
 
-/* Define to 1 if you have the `gettimeofday' function. */
-#undef HAVE_GETTIMEOFDAY
+/* Have cURL with OpenSSL support */
+#undef HAVE_CURL_OPENSSL
 
-/* Define to 1 if you have the `getwd' function. */
-#undef HAVE_GETWD
+/* Have cURL with GnuTLS support */
+#undef HAVE_CURL_GNUTLS
 
-/* */
-#undef HAVE_GICONV_H
+/*   */
+#undef HAVE_CURL
 
-/* glibc's iconv implementation */
-#undef HAVE_GLIBC_ICONV
+/*   */
+#undef HAVE_CURL_VERSION_INFO
 
-/* Define to 1 if you have the `glob' function. */
-#undef HAVE_GLOB
+/*   */
+#undef HAVE_CURL_EASY_STRERROR
 
-/* */
-#undef HAVE_GMP
+/*   */
+#undef HAVE_CURL_MULTI_STRERROR
 
-/* Define to 1 if you have the `gmtime_r' function. */
-#undef HAVE_GMTIME_R
+/*   */
+#undef PHP_CURL_URL_WRAPPERS
 
-/* Define to 1 if you have the `grantpt' function. */
-#undef HAVE_GRANTPT
+/* Whether to build curl as dynamic module */
+#undef COMPILE_DL_CURL
 
-/* Define to 1 if you have the <grp.h> header file. */
-#undef HAVE_GRP_H
+/*   */
+#undef QDBM_INCLUDE_FILE
 
-/* Have HASH Extension */
-#undef HAVE_HASH_EXT
+/*   */
+#undef DBA_QDBM
 
-/* Define to 1 if you have the `hstrerror' function. */
-#undef HAVE_HSTRERROR
+/*   */
+#undef GDBM_INCLUDE_FILE
 
-/* */
-#undef HAVE_HTONL
+/*   */
+#undef DBA_GDBM
 
-/* whether HUGE_VAL == INF */
-#undef HAVE_HUGE_VAL_INF
+/*   */
+#undef NDBM_INCLUDE_FILE
 
-/* whether HUGE_VAL + -HUGEVAL == NAN */
-#undef HAVE_HUGE_VAL_NAN
+/*   */
+#undef DBA_NDBM
 
-/* Define to 1 if you have the `hypot' function. */
-#undef HAVE_HYPOT
+/*   */
+#undef DBA_DB4
 
-/* */
-#undef HAVE_IBASE
+/*   */
+#undef DB4_INCLUDE_FILE
 
-/* */
-#undef HAVE_IBMDB2
+/*   */
+#undef DBA_DB3
 
-/* IBM iconv implementation */
-#undef HAVE_IBM_ICONV
+/*   */
+#undef DB3_INCLUDE_FILE
 
-/* */
-#undef HAVE_ICONV
+/*   */
+#undef DBA_DB2
 
-/* Define to 1 if you have the <ieeefp.h> header file. */
-#undef HAVE_IEEEFP_H
+/*   */
+#undef DB2_INCLUDE_FILE
 
-/* */
-#undef HAVE_IMAP
+/*   */
+#undef DB1_VERSION
 
-/* */
-#undef HAVE_IMAP2000
+/*   */
+#undef DB1_VERSION
 
-/* */
-#undef HAVE_IMAP2001
+/*   */
+#undef DB1_INCLUDE_FILE
 
-/* */
-#undef HAVE_IMAP2004
+/*   */
+#undef DBA_DB1
 
-/* */
-#undef HAVE_IMAP_AUTH_GSS
+/*   */
+#undef DBM_INCLUDE_FILE
 
-/* */
-#undef HAVE_IMAP_KRB
+/*   */
+#undef DBM_VERSION
 
-/* */
-#undef HAVE_IMAP_MUTF7
+/*   */
+#undef DBM_VERSION
 
-/* */
-#undef HAVE_IMAP_SSL
+/*   */
+#undef DBA_DBM
 
-/* */
-#undef HAVE_INET_ATON
+/*   */
+#undef DBA_CDB_BUILTIN
 
-/* Define to 1 if you have the `inet_ntoa' function. */
-#undef HAVE_INET_NTOA
+/*   */
+#undef DBA_CDB_MAKE
 
-/* Define to 1 if you have the `inet_ntop' function. */
-#undef HAVE_INET_NTOP
+/*   */
+#undef DBA_CDB
 
-/* Define to 1 if you have the `inet_pton' function. */
-#undef HAVE_INET_PTON
+/*   */
+#undef CDB_INCLUDE_FILE
 
-/* Define to 1 if you have the `initgroups' function. */
-#undef HAVE_INITGROUPS
+/*   */
+#undef DBA_CDB
 
-/* Define if int32_t type is present. */
-#undef HAVE_INT32_T
+/*   */
+#undef DBA_INIFILE
 
-/* Whether intmax_t is available */
-#undef HAVE_INTMAX_T
+/*   */
+#undef DBA_FLATFILE
+
+/*   */
+#undef HAVE_DBA
+
+/* Whether to build dba as dynamic module */
+#undef COMPILE_DL_DBA
+
+/*   */
+#undef HAVE_LIBXML
+
+/*   */
+#undef HAVE_DOM
 
-/* Define to 1 if you have the <inttypes.h> header file. */
-#undef HAVE_INTTYPES_H
+/* Whether to build dom as dynamic module */
+#undef COMPILE_DL_DOM
 
-/* */
-#undef HAVE_IODBC
+/* Whether to build enchant as dynamic module */
+#undef COMPILE_DL_ENCHANT
 
-/* */
-#undef HAVE_IODBC_H
+/*   */
+#undef HAVE_ENCHANT
 
-/* Whether to enable IPv6 support */
-#undef HAVE_IPV6
+/*   */
+#undef HAVE_ENCHANT_BROKER_SET_PARAM
 
-/* Define to 1 if you have the `isascii' function. */
-#undef HAVE_ISASCII
+/*   */
+#undef ENCHANT_VERSION_STRING
 
-/* Define to 1 if you have the `isfinite' function. */
-#undef HAVE_ISFINITE
+/* Whether you want EXIF (metadata from images) support */
+#undef HAVE_EXIF
 
-/* Define to 1 if you have the `isinf' function. */
-#undef HAVE_ISINF
+/* Whether to build exif as dynamic module */
+#undef COMPILE_DL_EXIF
 
-/* Define to 1 if you have the `isnan' function. */
-#undef HAVE_ISNAN
+/* Whether to build fileinfo as dynamic module */
+#undef COMPILE_DL_FILEINFO
 
-/* */
-#undef HAVE_ISQLEXT_H
+/* Whether to build filter as dynamic module */
+#undef COMPILE_DL_FILTER
 
-/* */
-#undef HAVE_ISQL_H
+/* Whether you want FTP support */
+#undef HAVE_FTP
 
-/* whether to enable JavaScript Object Serialization support */
-#undef HAVE_JSON
+/* Whether to build ftp as dynamic module */
+#undef COMPILE_DL_FTP
 
-/* Define to 1 if you have the `kill' function. */
-#undef HAVE_KILL
+/*   */
+#undef USE_GD_IMGSTRTTF
 
-/* do we have kqueue? */
-#undef HAVE_KQUEUE
+/*   */
+#undef USE_GD_IMGSTRTTF
 
-/* Define to 1 if you have the <langinfo.h> header file. */
-#undef HAVE_LANGINFO_H
+/*   */
+#undef HAVE_LIBFREETYPE
 
-/* Define to 1 if you have the `lchown' function. */
-#undef HAVE_LCHOWN
+/*   */
+#undef ENABLE_GD_TTF
 
-/* */
-#undef HAVE_LDAP
+/*   */
+#undef HAVE_LIBT1
 
-/* Define to 1 if you have the `ldap_parse_reference' function. */
-#undef HAVE_LDAP_PARSE_REFERENCE
+/*   */
+#undef HAVE_LIBGD
 
-/* Define to 1 if you have the `ldap_parse_result' function. */
-#undef HAVE_LDAP_PARSE_RESULT
+/*   */
+#undef HAVE_LIBGD13
 
-/* LDAP SASL support */
-#undef HAVE_LDAP_SASL
+/*   */
+#undef HAVE_LIBGD15
 
-/* */
-#undef HAVE_LDAP_SASL_H
+/*   */
+#undef HAVE_LIBGD20
 
-/* */
-#undef HAVE_LDAP_SASL_SASL_H
+/*   */
+#undef HAVE_LIBGD204
 
-/* Define to 1 if you have the `ldap_start_tls_s' function. */
-#undef HAVE_LDAP_START_TLS_S
+/*   */
+#undef HAVE_GD_IMAGESETTILE
 
-/* */
-#undef HAVE_LIBBIND
+/*   */
+#undef HAVE_GD_IMAGESETBRUSH
 
-/* */
-#undef HAVE_LIBCRYPT
+/*   */
+#undef HAVE_GDIMAGECOLORRESOLVE
 
-/* */
-#undef HAVE_LIBDL
+/*   */
+#undef HAVE_COLORCLOSESTHWB
 
-/* */
-#undef HAVE_LIBDNET_STUB
+/*   */
+#undef HAVE_GD_WBMP
 
-/* */
-#undef HAVE_LIBEDIT
+/*   */
+#undef HAVE_GD_GD2
 
-/* */
-#undef HAVE_LIBEXPAT
+/*   */
+#undef HAVE_GD_PNG
 
-/* */
-#undef HAVE_LIBFREETYPE
+/*   */
+#undef HAVE_GD_XBM
 
-/* */
-#undef HAVE_LIBGD
+/*   */
+#undef HAVE_GD_BUNDLED
 
-/* */
-#undef HAVE_LIBGD13
+/*   */
+#undef HAVE_GD_GIF_READ
 
-/* */
-#undef HAVE_LIBGD15
+/*   */
+#undef HAVE_GD_GIF_CREATE
 
-/* */
-#undef HAVE_LIBGD20
+/*   */
+#undef HAVE_GD_IMAGEELLIPSE
 
-/* */
-#undef HAVE_LIBGD204
+/*   */
+#undef HAVE_GD_FONTCACHESHUTDOWN
 
-/* */
-#undef HAVE_LIBICONV
+/*   */
+#undef HAVE_GD_FONTMUTEX
 
-/* */
-#undef HAVE_LIBINTL
+/*   */
+#undef HAVE_GD_DYNAMIC_CTX_EX
 
-/* Define to 1 if you have the `m' library (-lm). */
-#undef HAVE_LIBM
+/*   */
+#undef HAVE_GD_GIF_CTX
 
-/* */
-#undef HAVE_LIBMCRYPT
+/*   */
+#undef HAVE_GD_JPG
 
-/* Whether you have libmm */
-#undef HAVE_LIBMM
+/*   */
+#undef HAVE_GD_XPM
 
-/* */
-#undef HAVE_LIBNSL
+/*   */
+#undef HAVE_GD_STRINGFT
 
-/* */
-#undef HAVE_LIBPAM
+/*   */
+#undef HAVE_GD_STRINGFTEX
 
-/* */
-#undef HAVE_LIBRARYMANAGER_H
+/*   */
+#undef ENABLE_GD_TTF
 
-/* */
-#undef HAVE_LIBREADLINE
+/*   */
+#undef USE_GD_JISX0208
 
-/* Whether we have librecode 3.5 or higher */
-#undef HAVE_LIBRECODE
+/*   */
+#undef USE_GD_IMGSTRTTF
 
-/* */
-#undef HAVE_LIBRESOLV
+/*   */
+#undef USE_GD_IMGSTRTTF
 
-/* */
-#undef HAVE_LIBRT
+/*   */
+#undef HAVE_LIBFREETYPE
 
-/* */
-#undef HAVE_LIBSOCKET
+/*   */
+#undef ENABLE_GD_TTF
 
-/* */
+/*   */
 #undef HAVE_LIBT1
 
-/* */
-#undef HAVE_LIBXML
+/*   */
+#undef HAVE_LIBGD
 
-/* Define to 1 if you have the <limits.h> header file. */
-#undef HAVE_LIMITS_H
+/*   */
+#undef HAVE_LIBGD13
 
EOP
            )->strip(1)
        );
    }
}
