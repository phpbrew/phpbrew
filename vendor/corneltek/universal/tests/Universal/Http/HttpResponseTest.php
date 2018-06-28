<?php

class HttpResponseTest extends PHPUnit_Framework_TestCase
{
    function test()
    {
        $response = new Universal\Http\HttpResponse(200);
        ok( $response );

        $response->noCache();
        $response->body('Hello World');

        $body = $response->finalize();
        is( 'Hello World', $body );
    }
}

