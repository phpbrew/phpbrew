<?php
namespace SerializerKit;

class BsonSerializer
{
    public function encode($data) {
        return bson_encode($data);
    }

    public function decode($data) { 
        return bson_decode($data);
    }
}



