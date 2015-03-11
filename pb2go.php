<?php

require 'classes/Token.php';
require 'classes/Lexer.php';
require 'classes/Registry.php';
require 'classes/Parser.php';
require 'classes/AbstractGenerator.php';
require 'classes/PHPGenerator.php';
require 'classes/PHPServiceGenerator.php';
require 'classes/PHPServiceClientGenerator.php';
require 'classes/JavaScriptGenerator.php';

$modes = array(
	'php-service',
	'php-service-client',
	'javascript',
);

$_file = NULL;
$_mode = NULL;
$_path = NULL;
$_force = FALSE;

$arg = 1;
while (isset($argv[$arg])) {
	switch ($argv[$arg]) {
		case '-m':
		case '--mode':
			if (isset($argv[$arg + 1])) {
				$_mode = $argv[++$arg];
			}
			break;
		case '-p':
		case '--path':
			if (isset($argv[$arg + 1])) {
				$_path = $argv[++$arg];
			}
			break;
		case '-f':
		case '--force':
			$_force = TRUE;
			break;
		default:
			$_file = $argv[$arg];
			break;
	}
	$arg++;
}

echo "pb2go (https://github.com/boorchoo/pb2go)\n";
echo "Copyright (c) 2014-2015 Milan Živadinović (boorchoo@gmail.com)\n";
echo "\n";

if (empty($_file) || (!empty($_mode) && !in_array($_mode, $modes))) {
	echo "Usage: pb2go [options] <file>\n";
	echo "\n";
	echo "Options:\n";
	echo "  -m, --mode <mode>       \n";
	echo "  -p, --path <path>       Place the output files into <path>\n";
	echo "\n";
	echo "Modes:\n";
	echo "  php-service             Generate only PHP service files\n";
	echo "  php-service-client      Generate only PHP service client files\n";
	echo "  javascript              Generate only JavaScript files\n";
	echo "\n";
	die();
}
if (empty($_path)) {
	$_path = './output';
}

$file = realpath($_file);
if (!$file) {
	echo "ERROR: File {$_file} not found\n";
	die();
}

$path = realpath($_path);
if ($path) {
	if (!is_dir($path)) {
		echo "ERROR: Path {$path} is not a direcory\n";
		die();
	}
} else {
	mkdir($_path);
	$path = realpath($_path);
}

try {
	Registry::init();
	
	$parser = new Parser();
	$proto = $parser->parse($_file);

	if (empty($_mode) || $_mode == 'php-service' || $_mode == 'php-service-client') {
		$phpGenerator = new PHPGenerator($proto);
		$phpGenerator->generate($path);
	}
	if (empty($_mode) || $_mode == 'php-service') {
		$phpServiceGenerator = new PHPServiceGenerator($proto);
		$phpServiceGenerator->generate($path);
	}
	if (empty($_mode) || $_mode == 'php-service-client') {
		$phpServiceClientGenerator = new PHPServiceClientGenerator($proto);
		$phpServiceClientGenerator->generate($path);
	}
	if (empty($_mode) || $_mode == 'javascript') {
		$javaScriptGenerator = new JavaScriptGenerator($proto);
		$javaScriptGenerator->generate($path);
	}
} catch (Exception $e) {
	echo "ERROR: {$e->getMessage()}\n";
}
