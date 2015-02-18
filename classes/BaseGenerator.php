<?php

abstract class BaseGenerator {
	
	public function __construct() {
	}
	
	public function toCamelCase($string) {
		return ucfirst(preg_replace_callback('/_([a-zA-Z])/', create_function('$c', 'return strtoupper($c[1]);'), $string));
	}

}
