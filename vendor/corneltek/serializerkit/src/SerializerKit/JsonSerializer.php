<?php
namespace SerializerKit;

class JsonSerializer
{
    function encode($data) 
    {
        return json_encode($data); 
    }

    function decode($data) 
    {
        return json_decode($data, true); 
    }
}

