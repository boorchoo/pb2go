<?php

abstract class BaseGenerator {
	
	protected static $phpTypeMapping = array(
		'double' => 'float',
		'float' => 'float',
		'int32' => 'integer',
		'int64' => 'integer',
		'uint32' => 'integer',
		'uint64' => 'integer',
		'sint32' => 'integer',
		'sint64' => 'integer',
		'fixed32' => 'integer',
		'fixed64' => 'integer',
		'sfixed32' => 'integer',
		'sfixed64' => 'integer',
		'bool' => 'boolean',
		'string' => 'string',
		'bytes' => 'string',
	);
	
	public function __construct() {
	}
	
	public function toCamelCase($string) {
		return ucfirst(preg_replace_callback('/_([a-zA-Z])/', create_function('$c', 'return strtoupper($c[1]);'), $string));
	}
	
	public function getPHPType($protocolBuffersType) {
		return isset(self::$phpTypeMapping[$protocolBuffersType]) ? self::$phpTypeMapping[$protocolBuffersType] : NULL;
	}

}
