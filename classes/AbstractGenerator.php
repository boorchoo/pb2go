<?php

abstract class AbstractGenerator {
	
	protected $fileName;
	protected $proto;
	
	protected function __construct($fileName, $proto) {
		$this->fileName = $fileName;
		$this->proto = $proto;
	}
	
	public abstract function generate($path);
	
	public function toCamelCase($string) {
		return ucfirst(preg_replace_callback('/_([a-zA-Z])/', create_function('$c', 'return strtoupper($c[1]);'), $string));
	}
	
	protected function output($path, $contents, $force = TRUE) {
		$path_parts = explode('/', $path);
		$filename = array_pop($path_parts);
		
		$_path = '';
		foreach ($path_parts as $path_part) {
			$_path .= "/{$path_part}";
			if (file_exists($_path)) {
				if (is_dir($_path)) {
					continue;
				}
				throw new Exception("Path {$_path} is not a directory");
			}
			$res = @mkdir($_path);
			if ($res === FALSE) {
				throw new Exception("Failed to create {$_path}");
			}
		}
		
		$file_exists = file_exists($path);
		if (!$file_exists || ($file_exists && $force)) {
			$res = file_put_contents($path, $contents);
			if ($res === FALSE) {
				throw new Exception("Failed to write contents into {$_path}");
			}
			return TRUE;
		}
		return FALSE;
	}

}
