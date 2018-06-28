<?php

use SerializerKit\XmlSerializer;

class XmlSerializerTest extends PHPUnit_Framework_TestCase
{
    function test()
    {
        $xmls = new XmlSerializer;
        $string = $xmls->encode(array(
            'title' => 'War and Peace',
            'isbn' => 123123123,
            'authors' => array(
                array( 'name' => 'First', 'email' => 'Email-1' ),
                array( 'name' => 'Second', 'email' => 'Email-2' ),
            ),
        ));

        ok( $string ); 

        $data = $xmls->decode( $string );
        ok( $data['title'] );
        ok( $data['isbn'] );
        ok( is_array($data['authors']) );
        ok( $data['authors'][0] );
        ok( $data['authors'][1] );
    }
}

