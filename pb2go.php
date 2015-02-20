<?php

require 'classes/Tokenizer.php';
require 'classes/Parser.php';

require 'classes/BaseGenerator.php';
require 'classes/EnumGenerator.php';
require 'classes/MessageGenerator.php';
require 'classes/RpcGenerator.php';
require 'classes/ServiceGenerator.php';

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

if (empty($_mode) || $_mode == 'php-service' || $_mode == 'php-client') {
	if (!file_exists("{$path}/classes")) {
		mkdir("{$path}/classes");
	}
	if (!file_exists("{$path}/configs")) {
		mkdir("{$path}/configs");
	}
}
if (empty($_mode) || $_mode == 'php-service' || $_mode == 'js-client') {
	if (!file_exists("{$path}/public")) {
		mkdir("{$path}/public");
	}
}
if (empty($_mode) || $_mode == 'js-client') {
	if (!file_exists("{$path}/public/js")) {
		mkdir("{$path}/public/js");
	}
}

$parser = new Parser();
try {
	$proto = $parser->parse($_file);
} catch (Exception $e) {
	die($e->getMessage() . PHP_EOL);
}

$javaScriptSource = <<<SOURCE
/*** DO NOT MANUALLY EDIT THIS FILE ***/
		
SOURCE;

$types = array(
	'enums' => array(),
	'messages' => array(),
);
global $types;

foreach ($proto['enums'] as $type => $enum) {
	$types['enums'][$type] = array();
}

foreach ($proto['messages'] as $type => $message) {
	$types['messages'][$type] = array();
}

foreach ($proto['messages'] as $type => $message) {
	$messageGenerator = new MessageGenerator($type, $message);
	if (empty($_mode) || $_mode == 'php-service' || $_mode == 'php-client') {
		output("{$path}/classes/" . str_replace('.', '_', $type) . ".php", $messageGenerator->generatePHPClassSource());
	}
	$javaScriptSource .= $messageGenerator->generateJavaScriptClassSource();
}

foreach ($proto['enums'] as $type => $enum) {
	$enumGenerator = new EnumGenerator($type, $enum);
	if (empty($_mode) || $_mode == 'php-service' || $_mode == 'php-client') {
		output("{$path}/classes/" . str_replace('.', '_', $type) . ".php", $enumGenerator->generatePHPClassSource());
	}
	$javaScriptSource .= $enumGenerator->generateJavaScriptClassSource();
}

$serviceGenerator = new ServiceGenerator(NULL, NULL);
$javaScriptSource .= $serviceGenerator->generateJavaScriptRequestClassSource();
$javaScriptSource .= $serviceGenerator->generateJavaScriptResponseClassSource();

if (empty($_mode) || $_mode == 'php-service') {
	output("{$path}/classes/Method.php", $serviceGenerator->generatePHPMethodClassSource());

	output("{$path}/classes/Request.php", $serviceGenerator->generatePHPRequestClassSource());

	output("{$path}/classes/Response.php", $serviceGenerator->generatePHPResponseClassSource());

	output("{$path}/classes/Response_Error.php", $serviceGenerator->generatePHPResponse_ErrorClassSource());

	output("{$path}/classes/ParseError.php", $serviceGenerator->generatePHPParseErrorClassSource());

	output("{$path}/classes/InvalidRequest.php", $serviceGenerator->generatePHPInvalidRequestClassSource());

	output("{$path}/classes/MethodNotFound.php", $serviceGenerator->generatePHPMethodNotFoundClassSource());

	output("{$path}/classes/InvalidParams.php", $serviceGenerator->generatePHPInvalidParamsClassSource());

	output("{$path}/classes/InternalError.php", $serviceGenerator->generatePHPInternalErrorClassSource());

	output("{$path}/classes/ServerError.php", $serviceGenerator->generatePHPServerErrorClassSource());

	output("{$path}/classes/InvalidProtocolBufferException.php", $serviceGenerator->generatePHPInvalidProtocolBufferExceptionClassSource());

	output("{$path}/classes/UninitializedMessageException.php", $serviceGenerator->generatePHPUninitializedMessageExceptionClassSource());
}

//output("{$path}/public/{$proto['package']}.html", $serviceGenerator->generateHTMLSource($proto['package']));

foreach ($proto['services'] as $serviceName => $service) {
	$serviceGenerator = new ServiceGenerator($serviceName, $service);
	if (empty($_mode) || $_mode == 'php-service') {
		output("{$path}/configs/{$serviceName}.php", $serviceGenerator->generatePHPConfigSource(), FALSE);
		output("{$path}/public/{$serviceName}.php", $serviceGenerator->generatePHPSource());
	}
	foreach ($service['rpcs'] as $rpcName => $rpc) {
		$rpcGenerator = new RpcGenerator($rpcName, $rpc);
		if (empty($_mode) || $_mode == 'php-service') {
			output("{$path}/classes/{$rpcName}.php", $rpcGenerator->generatePHPClassSource(), FALSE);
		}
	}
	$javaScriptSource .= $serviceGenerator->generateJavaScriptSource();
}

if (empty($_mode) || $_mode == 'js-client') {
	output("{$path}/public/js/{$proto['package']}.js", $javaScriptSource);
}

function output($filePath, $contents, $update = TRUE) {
	if (file_exists($filePath)) {
		if ($update) {
			file_put_contents($filePath, $contents);
			echo "modified:\t{$filePath}\n";
		} else {
			echo "unchanged:\t{$filePath}\n";
		}
	} else {
		file_put_contents($filePath, $contents);
		echo "new file:\t{$filePath}\n";
	}
}
