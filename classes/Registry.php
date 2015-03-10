<?php

class Registry {
	
	const PRIMITIVE = 'PRIMITIVE';
	const ENUM = 'ENUM';
	const MESSAGE = 'MESSAGE';
	
	protected static $types;
	
	protected function __construct() {
	}
	
	public static function init() {
		self::$types = array(
			'double' => array(
				'type' => self::PRIMITIVE,
				'package' => NULL,
				'name' => 'double',
			),
			'float' => array(
				'type' => self::PRIMITIVE,
				'package' => NULL,
				'name' => 'float',
			),
			'int32' => array(
				'type' => self::PRIMITIVE,
				'package' => NULL,
				'name' => 'int32',
			),
			'int64' => array(
				'type' => self::PRIMITIVE,
				'package' => NULL,
				'name' => 'int64',
			),
			'uint32' => array(
				'type' => self::PRIMITIVE,
				'package' => NULL,
				'name' => 'uint32',
			),
			'uint64' => array(
				'type' => self::PRIMITIVE,
				'package' => NULL,
				'name' => 'uint64',
			),
			'sint32' => array(
				'type' => self::PRIMITIVE,
				'package' => NULL,
				'name' => 'sint32',
			),
			'sint64' => array(
				'type' => self::PRIMITIVE,
				'package' => NULL,
				'name' => 'sint64',
			),
			'fixed32' => array(
				'type' => self::PRIMITIVE,
				'package' => NULL,
				'name' => 'fixed32',
			),
			'fixed64' => array(
				'type' => self::PRIMITIVE,
				'package' => NULL,
				'name' => 'fixed64',
			),
			'sfixed32' => array(
				'type' => self::PRIMITIVE,
				'package' => NULL,
				'name' => 'sfixed32',
			),
			'sfixed64' => array(
				'type' => self::PRIMITIVE,
				'package' => NULL,
				'name' => 'sfixed64',
			),
			'bool' => array(
				'type' => self::PRIMITIVE,
				'package' => NULL,
				'name' => 'bool',
			),
			'string' => array(
				'type' => self::PRIMITIVE,
				'package' => NULL,
				'name' => 'string',
			),
			'bytes' => array(
				'type' => self::PRIMITIVE,
				'package' => NULL,
				'name' => 'bytes',
			),
		);
	}
	
	public static function registerEnumType($package, $name) {
		self::$types[(empty($package) ? '' : "{$package}.") . $name] = array(
			'type' => self::ENUM,
			'package' => $package,
			'name' => $name,
		);
	}
	
	public static function registerMessageType($package, $name) {
		self::$types[(empty($package) ? '' : "{$package}.") . $name] = array(
				'type' => self::MESSAGE,
				'package' => $package,
				'name' => $name,
		);
	}
	
	public static function getType($type) {
		return isset(self::$types[$type]) ? self::$types[$type] : NULL; 
	}
	
	public static function isPrimitiveType($type) {
		if (!isset(self::$types[$type]['type'])) {
			return FALSE;
		}
		return self::$types[$type]['type'] === self::PRIMITIVE;
	}
	
	public static function isEnumType($type) {
		if (!isset(self::$types[$type]['type'])) {
			return FALSE;
		}
		return self::$types[$type]['type'] === self::ENUM;
	}
	
	public static function isMessageType($type) {
		if (!isset(self::$types[$type]['type'])) {
			return FALSE;
		}
		return self::$types[$type]['type'] === self::MESSAGE;
	}
	
}