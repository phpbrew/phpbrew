<?php
namespace SerializerKit;

class MsgPackSerializer
{
	function encode($data) {
        return msgpack_serialize($data);
	}

	function decode($data) {
		return msgpack_unserialize($data);
	}
}

