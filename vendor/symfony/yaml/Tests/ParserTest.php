<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\Component\Yaml\Tests;

use Symfony\Component\Yaml\Yaml;
use Symfony\Component\Yaml\Parser;

class ParserTest extends \PHPUnit_Framework_TestCase
{
    protected $parser;

    protected function setUp()
    {
        $this->parser = new Parser();
    }

    protected function tearDown()
    {
        $this->parser = null;
    }

    /**
     * @dataProvider getDataFormSpecifications
     */
    public function testSpecifications($file, $expected, $yaml, $comment)
    {
        $this->assertEquals($expected, var_export($this->parser->parse($yaml), true), $comment);
    }

    public function getDataFormSpecifications()
    {
        $parser = new Parser();
        $path = __DIR__.'/Fixtures';

        $tests = array();
        $files = $parser->parse(file_get_contents($path.'/index.yml'));
        foreach ($files as $file) {
            $yamls = file_get_contents($path.'/'.$file.'.yml');

            // split YAMLs documents
            foreach (preg_split('/^---( %YAML\:1\.0)?/m', $yamls) as $yaml) {
                if (!$yaml) {
                    continue;
                }

                $test = $parser->parse($yaml);
                if (isset($test['todo']) && $test['todo']) {
                    // TODO
                } else {
                    eval('$expected = '.trim($test['php']).';');

                    $tests[] = array($file, var_export($expected, true), $test['yaml'], $test['test']);
                }
            }
        }

        return $tests;
    }

    public function testTabsInYaml()
    {
        // test tabs in YAML
        $yamls = array(
            "foo:\n	bar",
            "foo:\n 	bar",
            "foo:\n	 bar",
            "foo:\n 	 bar",
        );

        foreach ($yamls as $yaml) {
            try {
                $content = $this->parser->parse($yaml);

                $this->fail('YAML files must not contain tabs');
            } catch (\Exception $e) {
                $this->assertInstanceOf('\Exception', $e, 'YAML files must not contain tabs');
                $this->assertEquals('A YAML file cannot contain tabs as indentation at line 2 (near "'.strpbrk($yaml, "\t").'").', $e->getMessage(), 'YAML files must not contain tabs');
            }
        }
    }

    public function testEndOfTheDocumentMarker()
    {
        $yaml = <<<'EOF'
--- %YAML:1.0
foo
...
EOF;

        $this->assertEquals('foo', $this->parser->parse($yaml));
    }

    public function getBlockChompingTests()
    {
        $tests = array();

        $yaml = <<<'EOF'
foo: |-
    one
    two
bar: |-
    one
    two

EOF;
        $expected = array(
            'foo' => "one\ntwo",
            'bar' => "one\ntwo",
        );
        $tests['Literal block chomping strip with single trailing newline'] = array($expected, $yaml);

        $yaml = <<<'EOF'
foo: |-
    one
    two

bar: |-
    one
    two


EOF;
        $expected = array(
            'foo' => "one\ntwo",
            'bar' => "one\ntwo",
        );
        $tests['Literal block chomping strip with multiple trailing newlines'] = array($expected, $yaml);

        $yaml = <<<'EOF'
{}


EOF;
        $expected = array();
        $tests['Literal block chomping strip with multiple trailing newlines after a 1-liner'] = array($expected, $yaml);

        $yaml = <<<'EOF'
foo: |-
    one
    two
bar: |-
    one
    two
EOF;
        $expected = array(
            'foo' => "one\ntwo",
            'bar' => "one\ntwo",
        );
        $tests['Literal block chomping strip without trailing newline'] = array($expected, $yaml);

        $yaml = <<<'EOF'
foo: |
    one
    two
bar: |
    one
    two

EOF;
        $expected = array(
            'foo' => "one\ntwo\n",
            'bar' => "one\ntwo\n",
        );
        $tests['Literal block chomping clip with single trailing newline'] = array($expected, $yaml);

        $yaml = <<<'EOF'
foo: |
    one
    two

bar: |
    one
    two


EOF;
        $expected = array(
            'foo' => "one\ntwo\n",
            'bar' => "one\ntwo\n",
        );
        $tests['Literal block chomping clip with multiple trailing newlines'] = array($expected, $yaml);

        $yaml = <<<'EOF'
foo: |
    one
    two
bar: |
    one
    two
EOF;
        $expected = array(
            'foo' => "one\ntwo\n",
            'bar' => "one\ntwo",
        );
        $tests['Literal block chomping clip without trailing newline'] = array($expected, $yaml);

        $yaml = <<<'EOF'
foo: |+
    one
    two
bar: |+
    one
    two

EOF;
        $expected = array(
            'foo' => "one\ntwo\n",
            'bar' => "one\ntwo\n",
        );
        $tests['Literal block chomping keep with single trailing newline'] = array($expected, $yaml);

        $yaml = <<<'EOF'
foo: |+
    one
    two

bar: |+
    one
    two


EOF;
        $expected = array(
            'foo' => "one\ntwo\n\n",
            'bar' => "one\ntwo\n\n",
        );
        $tests['Literal block chomping keep with multiple trailing newlines'] = array($expected, $yaml);

        $yaml = <<<'EOF'
foo: |+
    one
    two
bar: |+
    one
    two
EOF;
        $expected = array(
            'foo' => "one\ntwo\n",
            'bar' => "one\ntwo",
        );
        $tests['Literal block chomping keep without trailing newline'] = array($expected, $yaml);

        $yaml = <<<'EOF'
foo: >-
    one
    two
bar: >-
    one
    two

EOF;
        $expected = array(
            'foo' => 'one two',
            'bar' => 'one two',
        );
        $tests['Folded block chomping strip with single trailing newline'] = array($expected, $yaml);

        $yaml = <<<'EOF'
foo: >-
    one
    two

bar: >-
    one
    two


EOF;
        $expected = array(
            'foo' => 'one two',
            'bar' => 'one two',
        );
        $tests['Folded block chomping strip with multiple trailing newlines'] = array($expected, $yaml);

        $yaml = <<<'EOF'
foo: >-
    one
    two
bar: >-
    one
    two
EOF;
        $expected = array(
            'foo' => 'one two',
            'bar' => 'one two',
        );
        $tests['Folded block chomping strip without trailing newline'] = array($expected, $yaml);

        $yaml = <<<'EOF'
foo: >
    one
    two
bar: >
    one
    two

EOF;
        $expected = array(
            'foo' => "one two\n",
            'bar' => "one two\n",
        );
        $tests['Folded block chomping clip with single trailing newline'] = array($expected, $yaml);

        $yaml = <<<'EOF'
foo: >
    one
    two

bar: >
    one
    two


EOF;
        $expected = array(
            'foo' => "one two\n",
            'bar' => "one two\n",
        );
        $tests['Folded block chomping clip with multiple trailing newlines'] = array($expected, $yaml);

        $yaml = <<<'EOF'
foo: >
    one
    two
bar: >
    one
    two
EOF;
        $expected = array(
            'foo' => "one two\n",
            'bar' => 'one two',
        );
        $tests['Folded block chomping clip without trailing newline'] = array($expected, $yaml);

        $yaml = <<<'EOF'
foo: >+
    one
    two
bar: >+
    one
    two

EOF;
        $expected = array(
            'foo' => "one two\n",
            'bar' => "one two\n",
        );
        $tests['Folded block chomping keep with single trailing newline'] = array($expected, $yaml);

        $yaml = <<<'EOF'
foo: >+
    one
    two

bar: >+
    one
    two


EOF;
        $expected = array(
            'foo' => "one two\n\n",
            'bar' => "one two\n\n",
        );
        $tests['Folded block chomping keep with multiple trailing newlines'] = array($expected, $yaml);

        $yaml = <<<'EOF'
foo: >+
    one
    two
bar: >+
    one
    two
EOF;
        $expected = array(
            'foo' => "one two\n",
            'bar' => 'one two',
        );
        $tests['Folded block chomping keep without trailing newline'] = array($expected, $yaml);

        return $tests;
    }

    /**
     * @dataProvider getBlockChompingTests
     */
    public function testBlockChomping($expected, $yaml)
    {
        $this->assertSame($expected, $this->parser->parse($yaml));
    }

    /**
     * Regression test for issue #7989.
     *
     * @see https://github.com/symfony/symfony/issues/7989
     */
    public function testBlockLiteralWithLeadingNewlines()
    {
        $yaml = <<<'EOF'
foo: |-


    bar

EOF;
        $expected = array(
            'foo' => "\n\nbar",
        );

        $this->assertSame($expected, $this->parser->parse($yaml));
    }

    public function testObjectSupportEnabled()
    {
        $input = <<<EOF
foo: !!php/object:O:30:"Symfony\Component\Yaml\Tests\B":1:{s:1:"b";s:3:"foo";}
bar: 1
EOF;
        $this->assertEquals(array('foo' => new B(), 'bar' => 1), $this->parser->parse($input, false, true), '->parse() is able to parse objects');

        $input = <<<EOF
foo: !php/object:O:30:"Symfony\Component\Yaml\Tests\B":1:{s:1:"b";s:3:"foo";}
bar: 1
EOF;
        $this->assertEquals(array('foo' => new B(), 'bar' => 1), $this->parser->parse($input, false, true), '->parse() is able to parse objects');
    }

    /**
     * @dataProvider invalidDumpedObjectProvider
     */
    public function testObjectSupportDisabledButNoExceptions($input)
    {
        $this->assertEquals(array('foo' => null, 'bar' => 1), $this->parser->parse($input), '->parse() does not parse objects');
    }

    /**
     * @dataProvider getObjectForMapTests
     */
    public function testObjectForMap($yaml, $expected)
    {
        $this->assertEquals($expected, $this->parser->parse($yaml, false, false, true));
    }

    public function getObjectForMapTests()
    {
        $tests = array();

        $yaml = <<<EOF
foo:
    fiz: [cat]
EOF;
        $expected = new \stdClass();
        $expected->foo = new \stdClass();
        $expected->foo->fiz = array('cat');
        $tests['mapping'] = array($yaml, $expected);

        $yaml = '{ "foo": "bar", "fiz": "cat" }';
        $expected = new \stdClass();
        $expected->foo = 'bar';
        $expected->fiz = 'cat';
        $tests['inline-mapping'] = array($yaml, $expected);

        $yaml = "foo: bar\nbaz: foobar";
        $expected = new \stdClass();
        $expected->foo = 'bar';
        $expected->baz = 'foobar';
        $tests['object-for-map-is-applied-after-parsing'] = array($yaml, $expected);

        $yaml = <<<EOT
array:
  - key: one
  - key: two
EOT;
        $expected = new \stdClass();
        $expected->array = array();
        $expected->array[0] = new \stdClass();
        $expected->array[0]->key = 'one';
        $expected->array[1] = new \stdClass();
        $expected->array[1]->key = 'two';
        $tests['nest-map-and-sequence'] = array($yaml, $expected);

        $yaml = <<<YAML
map:
  1: one
  2: two
YAML;
        $expected = new \stdClass();
        $expected->map = new \stdClass();
        $expected->map->{1} = 'one';
        $expected->map->{2} = 'two';
        $tests['numeric-keys'] = array($yaml, $expected);

        $yaml = <<<YAML
map:
  0: one
  1: two
YAML;
        $expected = new \stdClass();
        $expected->map = new \stdClass();
        $expected->map->{0} = 'one';
        $expected->map->{1} = 'two';
        $tests['zero-indexed-numeric-keys'] = array($yaml, $expected);

        return $tests;
    }

    /**
     * @dataProvider invalidDumpedObjectProvider
     * @expectedException \Symfony\Component\Yaml\Exception\ParseException
     */
    public function testObjectsSupportDisabledWithExceptions($yaml)
    {
        $this->parser->parse($yaml, true, false);
    }

    public function invalidDumpedObjectProvider()
    {
        $yamlTag = <<<EOF
foo: !!php/object:O:30:"Symfony\Tests\Component\Yaml\B":1:{s:1:"b";s:3:"foo";}
bar: 1
EOF;
        $localTag = <<<EOF
foo: !php/object:O:30:"Symfony\Tests\Component\Yaml\B":1:{s:1:"b";s:3:"foo";}
bar: 1
EOF;

        return array(
            'yaml-tag' => array($yamlTag),
            'local-tag' => array($localTag),
        );
    }

    /**
     * @requires extension iconv
     */
    public function testNonUtf8Exception()
    {
        $yamls = array(
            iconv('UTF-8', 'ISO-8859-1', "foo: 'äöüß'"),
            iconv('UTF-8', 'ISO-8859-15', "euro: '€'"),
            iconv('UTF-8', 'CP1252', "cp1252: '©ÉÇáñ'"),
        );

        foreach ($yamls as $yaml) {
            try {
                $this->parser->parse($yaml);

                $this->fail('charsets other than UTF-8 are rejected.');
            } catch (\Exception $e) {
                $this->assertInstanceOf('Symfony\Component\Yaml\Exception\ParseException', $e, 'charsets other than UTF-8 are rejected.');
            }
        }
    }

    /**
     * @expectedException \Symfony\Component\Yaml\Exception\ParseException
     */
    public function testUnindentedCollectionException()
    {
        $yaml = <<<'EOF'

collection:
-item1
-item2
-item3

EOF;

        $this->parser->parse($yaml);
    }

    /**
     * @expectedException \Symfony\Component\Yaml\Exception\ParseException
     */
    public function testShortcutKeyUnindentedCollectionException()
    {
        $yaml = <<<'EOF'

collection:
-  key: foo
  foo: bar

EOF;

        $this->parser->parse($yaml);
    }

    /**
     * @expectedException \Symfony\Component\Yaml\Exception\ParseException
     * @expectedExceptionMessage Multiple documents are not supported.
     */
    public function testMultipleDocumentsNotSupportedException()
    {
        Yaml::parse(<<<'EOL'
# Ranking of 1998 home runs
---
- Mark McGwire
- Sammy Sosa
- Ken Griffey

# Team ranking
---
- Chicago Cubs
- St Louis Cardinals
EOL
        );
    }

    /**
     * @expectedException \Symfony\Component\Yaml\Exception\ParseException
     */
    public function testSequenceInAMapping()
    {
        Yaml::parse(<<<'EOF'
yaml:
  hash: me
  - array stuff
EOF
        );
    }

    /**
     * @expectedException \Symfony\Component\Yaml\Exception\ParseException
     */
    public function testMappingInASequence()
    {
        Yaml::parse(<<<'EOF'
yaml:
  - array stuff
  hash: me
EOF
        );
    }

    /**
     * @expectedException \Symfony\Component\Yaml\Exception\ParseException
     * @expectedExceptionMessage missing colon
     */
    public function testScalarInSequence()
    {
        Yaml::parse(<<<EOF
foo:
    - bar
"missing colon"
    foo: bar
EOF
        );
    }

    /**
     * > It is an error for two equal keys to appear in the same mapping node.
     * > In such a case the YAML processor may continue, ignoring the second
     * > `key: value` pair and issuing an appropriate warning. This strategy
     * > preserves a consistent information model for one-pass and random access
     * > applications.
     *
     * @see http://yaml.org/spec/1.2/spec.html#id2759572
     * @see http://yaml.org/spec/1.1/#id932806
     */
    public function testMappingDuplicateKeyBlock()
    {
        $input = <<<EOD
parent:
    child: first
    child: duplicate
parent:
    child: duplicate
    child: duplicate
EOD;
        $expected = array(
            'parent' => array(
                'child' => 'first',
            ),
        );
        $this->assertSame($expected, Yaml::parse($input));
    }

    public function testMappingDuplicateKeyFlow()
    {
        $input = <<<EOD
parent: { child: first, child: duplicate }
parent: { child: duplicate, child: duplicate }
EOD;
        $expected = array(
            'parent' => array(
                'child' => 'first',
            ),
        );
        $this->assertSame($expected, Yaml::parse($input));
    }

    public function testEmptyValue()
    {
        $input = <<<'EOF'
hash:
EOF;

        $this->assertEquals(array('hash' => null), Yaml::parse($input));
    }

    public function testCommentAtTheRootIndent()
    {
        $this->assertEquals(array(
            'services' => array(
                'app.foo_service' => array(
                    'class' => 'Foo',
                ),
                'app/bar_service' => array(
                    'class' => 'Bar',
                ),
            ),
        ), Yaml::parse(<<<'EOF'
# comment 1
services:
# comment 2
    # comment 3
    app.foo_service:
        class: Foo
# comment 4
    # comment 5
    app/bar_service:
        class: Bar
EOF
        ));
    }

    public function testStringBlockWithComments()
    {
        $this->assertEquals(array('content' => <<<'EOT'
# comment 1
header

    # comment 2
    <body>
        <h1>title</h1>
    </body>

footer # comment3
EOT
        ), Yaml::parse(<<<'EOF'
content: |
    # comment 1
    header

        # comment 2
        <body>
            <h1>title</h1>
        </body>

    footer # comment3
EOF
        ));
    }

    public function testFoldedStringBlockWithComments()
    {
        $this->assertEquals(array(array('content' => <<<'EOT'
# comment 1
header

    # comment 2
    <body>
        <h1>title</h1>
    </body>

footer # comment3
EOT
        )), Yaml::parse(<<<'EOF'
-
    content: |
        # comment 1
        header

            # comment 2
            <body>
                <h1>title</h1>
            </body>

        footer # comment3
EOF
        ));
    }

    public function testNestedFoldedStringBlockWithComments()
    {
        $this->assertEquals(array(array(
            'title' => 'some title',
            'content' => <<<'EOT'
# comment 1
header

    # comment 2
    <body>
        <h1>title</h1>
    </body>

footer # comment3
EOT
        )), Yaml::parse(<<<'EOF'
-
    title: some title
    content: |
        # comment 1
        header

            # comment 2
            <body>
                <h1>title</h1>
            </body>

        footer # comment3
EOF
        ));
    }

    public function testReferenceResolvingInInlineStrings()
    {
        $this->assertEquals(array(
            'var' => 'var-value',
            'scalar' => 'var-value',
            'list' => array('var-value'),
            'list_in_list' => array(array('var-value')),
            'map_in_list' => array(array('key' => 'var-value')),
            'embedded_mapping' => array(array('key' => 'var-value')),
            'map' => array('key' => 'var-value'),
            'list_in_map' => array('key' => array('var-value')),
            'map_in_map' => array('foo' => array('bar' => 'var-value')),
        ), Yaml::parse(<<<'EOF'
var:  &var var-value
scalar: *var
list: [ *var ]
list_in_list: [[ *var ]]
map_in_list: [ { key: *var } ]
embedded_mapping: [ key: *var ]
map: { key: *var }
list_in_map: { key: [*var] }
map_in_map: { foo: { bar: *var } }
EOF
        ));
    }

    public function testYamlDirective()
    {
        $yaml = <<<'EOF'
%YAML 1.2
---
foo: 1
bar: 2
EOF;
        $this->assertEquals(array('foo' => 1, 'bar' => 2), $this->parser->parse($yaml));
    }

    public function testFloatKeys()
    {
        $yaml = <<<'EOF'
foo:
    1.2: "bar"
    1.3: "baz"
EOF;

        $expected = array(
            'foo' => array(
                '1.2' => 'bar',
                '1.3' => 'baz',
            ),
        );

        $this->assertEquals($expected, $this->parser->parse($yaml));
    }

    /**
     * @group legacy
     * throw ParseException in Symfony 3.0
     */
    public function testColonInMappingValueException()
    {
        $yaml = <<<EOF
foo: bar: baz
EOF;

        $deprecations = array();
        set_error_handler(function ($type, $msg) use (&$deprecations) {
            if (E_USER_DEPRECATED !== $type) {
                restore_error_handler();

                return call_user_func_array('PHPUnit_Util_ErrorHandler::handleError', func_get_args());
            }

            $deprecations[] = $msg;
        });

        $this->parser->parse($yaml);

        restore_error_handler();

        $this->assertCount(1, $deprecations);
        $this->assertContains('Using a colon in the unquoted mapping value "bar: baz" in line 1 is deprecated since Symfony 2.8 and will throw a ParseException in 3.0.', $deprecations[0]);
    }

    public function testColonInMappingValueExceptionNotTriggeredByColonInComment()
    {
        $yaml = <<<EOT
foo:
    bar: foobar # Note: a comment after a colon
EOT;

        $this->assertSame(array('foo' => array('bar' => 'foobar')), $this->parser->parse($yaml));
    }

    /**
     * @dataProvider getCommentLikeStringInScalarBlockData
     */
    public function testCommentLikeStringsAreNotStrippedInBlockScalars($yaml, $expectedParserResult)
    {
        $this->assertSame($expectedParserResult, $this->parser->parse($yaml));
    }

    public function getCommentLikeStringInScalarBlockData()
    {
        $tests = array();

        $yaml = <<<'EOT'
pages:
    -
        title: some title
        content: |
            # comment 1
            header

                # comment 2
                <body>
                    <h1>title</h1>
                </body>

            footer # comment3
EOT;
        $expected = array(
            'pages' => array(
                array(
                    'title' => 'some title',
                    'content' => <<<'EOT'
# comment 1
header

    # comment 2
    <body>
        <h1>title</h1>
    </body>

footer # comment3
EOT
                    ,
                ),
            ),
        );
        $tests[] = array($yaml, $expected);

        $yaml = <<<'EOT'
test: |
    foo
    # bar
    baz
collection:
    - one: |
        foo
        # bar
        baz
    - two: |
        foo
        # bar
        baz
EOT;
        $expected = array(
            'test' => <<<'EOT'
foo
# bar
baz

EOT
            ,
            'collection' => array(
                array(
                    'one' => <<<'EOT'
foo
# bar
baz
EOT
                    ,
                ),
                array(
                    'two' => <<<'EOT'
foo
# bar
baz
EOT
                    ,
                ),
            ),
        );
        $tests[] = array($yaml, $expected);

        $yaml = <<<EOT
foo:
  bar:
    scalar-block: >
      line1
      line2>
  baz:
# comment
    foobar: ~
EOT;
        $expected = array(
            'foo' => array(
                'bar' => array(
                    'scalar-block' => 'line1 line2>',
                ),
                'baz' => array(
                    'foobar' => null,
                ),
            ),
        );
        $tests[] = array($yaml, $expected);

        $yaml = <<<'EOT'
a:
    b: hello
#    c: |
#        first row
#        second row
    d: hello
EOT;
        $expected = array(
            'a' => array(
                'b' => 'hello',
                'd' => 'hello',
            ),
        );
        $tests[] = array($yaml, $expected);

        return $tests;
    }

    public function testBlankLinesAreParsedAsNewLinesInFoldedBlocks()
    {
        $yaml = <<<EOT
test: >
    <h2>A heading</h2>

    <ul>
    <li>a list</li>
    <li>may be a good example</li>
    </ul>
EOT;

        $this->assertSame(
            array(
                'test' => <<<EOT
<h2>A heading</h2>
<ul> <li>a list</li> <li>may be a good example</li> </ul>
EOT
                ,
            ),
            $this->parser->parse($yaml)
        );
    }

    public function testAdditionallyIndentedLinesAreParsedAsNewLinesInFoldedBlocks()
    {
        $yaml = <<<EOT
test: >
    <h2>A heading</h2>

    <ul>
      <li>a list</li>
      <li>may be a good example</li>
    </ul>
EOT;

        $this->assertSame(
            array(
                'test' => <<<EOT
<h2>A heading</h2>
<ul>
  <li>a list</li>
  <li>may be a good example</li>
</ul>
EOT
                ,
            ),
            $this->parser->parse($yaml)
        );
    }
}

class B
{
    public $b = 'foo';
}
