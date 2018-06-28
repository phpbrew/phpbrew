<?php
use SerializerKit\YamlSerializer;

class FooData {

    public $data;

    public function __construct($data)
    {
        $this->data = $data;
    }

    static function __set_state($vars) {
        return new FooData($vars['data']);
    }

}


class SerializerTest extends PHPUnit_Framework_TestCase
{

    function dataProvider()
    {
        return array(
            array(array(
                'foo' => 1,
                'string_test' => 'bar',
                'float' => 1.00001,
                'array' => array( 'subarray' => 1 )
            )),
        );
    }


    /**
     * @dataProvider dataProvider
     */
    function testYaml($data)
    {
        $bs = array();

        if ( extension_loaded('yaml') ) {
            $bs[] = YamlSerializer::yaml;
        }
        $bs[] = YamlSerializer::sfyaml;
        foreach( $bs as $b ) {
            $serializer = new SerializerKit\YamlSerializer( $b );
            $yaml = $serializer->encode( $data );
            $data = $serializer->decode( $yaml );
            ok( $data );
            ok( is_array( $data ));
        }
    }


    function test()
    {
        $data = array( 
            'foo' => 1,
            'string_test' => 'bar',
            'float' => 1.00001,
            'array' => array( 'subarray' => 1 ),
        );

        $formats = array( 'xml', 'json', 'bson', 'yaml' );
        foreach( $formats as $format ) {
            if( ! extension_loaded($format) )
                continue;

            ok( $format );

            $serializer = new SerializerKit\Serializer($format);
            ok( $serializer );

            $string = $serializer->encode($data);
            ok($string, $format );
            $data2 = $serializer->decode($string);

            foreach( $data as $k => $v ) {
                ok( $data2[ $k ] , $format );
                is( $v , $data2[ $k ] , $format );
            }
        }
    }

    function testPhpSerializer()
    {
        $data = array( 
            'float' => 1.1,
            'foo' => function() { return 123; }
        );

        $serializer = new SerializerKit\Serializer('php');
        $string = $serializer->encode($data);
        $data2 = $serializer->decode($string);

        foreach( $data as $k => $v ) {
            ok( $data2[ $k ] );
            is( $v , $data2[ $k ] );
        }
    }


    function testPhpSerializerWithObjectVarExport()
    {
        $foo = new FooData(array( 'foo' => 3 ));

        $serializer = new SerializerKit\Serializer('php');
        $string = $serializer->encode($foo);
        ok($string);

        $foo2 = $serializer->decode($string);
        ok($foo2);
        is(3,$foo2->data['foo']);

    }


}

