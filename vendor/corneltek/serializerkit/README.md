SerializerKit
==============

    $data = array( ..... );

    $serializer = new SerializerKit\Serializer('xml');
    $string = $serializer->encode($data);
    $data2 = $serializer->decode($string);


