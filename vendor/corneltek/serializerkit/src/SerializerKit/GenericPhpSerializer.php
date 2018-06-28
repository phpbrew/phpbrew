<?php
namespace SerializerKit;

/**
 * Use PHP Built-in serializer
 */
class GenericPhpSerializer
{

    public function encode($data)
    {
        return serialize($data);
    }

    public function decode($data)
    {
        return unserialize($data);
    }

}




