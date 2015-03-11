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
	'php-client',
	'js-client',
);

$_file = NULL;
$_mode = NULL;
$_path = NULL;
$_clean = FALSE;

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
		case '-c':
		case '--clean':
			$_clean = TRUE;
			break;
		default:
			$_file = $argv[$arg];
			break;
	}
	$arg++;
}

if (empty($_file) || (!empty($_mode) && !in_array($_mode, $modes))) {
	echo "Usage: pb2go [options] <file>\n";
	echo "Options:\n";
	echo "  -m, --mode <mode>   \n";
	echo "  -p, --path <path>   Place the output files into <path>\n";
	echo "  -c, --clean         Remove all existing files from <path>\n";
	echo "Modes:\n";
	echo "  php-service         Generate only PHP service files\n";
	echo "  php-client          Generate only PHP client files\n";
	echo "  js-client           Generate only JavaScript client files\n";
	echo "\n";
	die();
}
if (empty($_path)) {
	$_path = './output';
}

if (!empty($_mode)) {
	echo "Mode: {$_mode}\n";
}

$file = realpath($_file);
if (!$file) {
	echo "Error: {$_file} not found\n";
	die();
}

$path = realpath($_path);
if ($path) {
	if (!is_dir($path)) {
		echo "Error: {$path} is not a direcory\n";
		die();
	}
	if ($_clean) {
		$files = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($path, RecursiveDirectoryIterator::SKIP_DOTS), RecursiveIteratorIterator::CHILD_FIRST);
		foreach ($files as $fileinfo) {
			if ($fileinfo->isDir()) {
				rmdir($fileinfo->getRealPath());
			} else {
				unlink($fileinfo->getRealPath());
			}
		}
	}
} else {
	mkdir($_path);
	$path = realpath($_path);
}

try {
	Registry::init();
	$parser = new Parser();
	$proto = $parser->parse($_file);
} catch (Exception $e) {
	echo "ERROR: {$e->getMessage()}\n";
	die();
}

if (empty($_mode) || $_mode == 'php-service' || $_mode == 'php-client') {
	$phpGenerator = new PHPGenerator($proto);
	$phpGenerator->generate($path);
}
if (empty($_mode) || $_mode == 'php-service') {
	$phpServiceGenerator = new PHPServiceGenerator($proto);
	$phpServiceGenerator->generate($path);
}
if (empty($_mode) || $_mode == 'php-client') {
	$phpServiceClientGenerator = new PHPServiceClientGenerator($proto);
	$phpServiceClientGenerator->generate($path);
}
if (empty($_mode) || $_mode == 'js-client') {
	$javaScriptGenerator = new JavaScriptGenerator($proto);
	$javaScriptGenerator->generate($path);
}
