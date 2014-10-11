<?php
use PhpBrew\PatchUtils;

class PatchUtilsTest extends PHPUnit_Framework_TestCase
{
    public function test()
    {
        $diff =<<<'PATCH'
--- tests/a.txt	2014-10-11 23:04:39.000000000 +0800
+++ tests/b.txt	2014-10-11 23:04:50.000000000 +0800
@@ -1 +1 @@
-aaa
+bbb

PATCH;
        ok(false !== file_put_contents('tests/a.txt', "aaa\n"));
        $ret = PatchUtils::applyFileStdin('tests/a.txt', $diff, $output);
        ok($ret == 0);
        unlink('tests/a.txt');
    }
}

