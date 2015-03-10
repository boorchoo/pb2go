<?php

require 'classes/Token.php';
require 'classes/Lexer.php';
require 'classes/Registry.php';
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

try {
	Registry::init();
	$parser = new Parser();
	$proto = $parser->parse($_file);
} catch (Exception $e) {
	echo "ERROR: {$e->getMessage()}\n";
	die();
}

$javaScriptSource = <<<SOURCE
/*** DO NOT MANUALLY EDIT THIS FILE ***/
		
SOURCE;

$serviceGenerator = new ServiceGenerator(NULL, NULL, NULL);
$javaScriptSource .= $serviceGenerator->generateJavaScriptJSONRPCSource();

foreach ($proto['messages'] as $message) {
	$messageGenerator = new MessageGenerator($message);
	if (empty($_mode) || $_mode == 'php-service' || $_mode == 'php-client') {
		output("{$path}/classes/" . str_replace('\\', '/', $messageGenerator->getPHPNamespace($message['package'])) . '/' . str_replace('.', '_', $message['type']) . ".php", $messageGenerator->generatePHPClassSource());
	}
	$javaScriptSource .= $messageGenerator->generateJavaScriptClassSource();
}

foreach ($proto['enums'] as $enum) {
	$enumGenerator = new EnumGenerator($enum);
	if (empty($_mode) || $_mode == 'php-service' || $_mode == 'php-client') {
		output("{$path}/classes/" . str_replace('\\', '/', $enumGenerator->getPHPNamespace($enum['package'])) . '/' . str_replace('.', '_', $enum['type']) . ".php", $enumGenerator->generatePHPClassSource());
	}
	$javaScriptSource .= $enumGenerator->generateJavaScriptClassSource();
}

if (empty($_mode) || $_mode == 'php-service' || $_mode == 'php-client') {
	output("{$path}/classes/JSONRPC/Request.php", $serviceGenerator->generatePHPRequestClassSource());

	output("{$path}/classes/JSONRPC/Response.php", $serviceGenerator->generatePHPResponseClassSource());

	output("{$path}/classes/JSONRPC/Response_Error.php", $serviceGenerator->generatePHPResponse_ErrorClassSource());

	output("{$path}/classes/JSONRPC/ParseError.php", $serviceGenerator->generatePHPParseErrorClassSource());

	output("{$path}/classes/JSONRPC/InvalidRequest.php", $serviceGenerator->generatePHPInvalidRequestClassSource());

	output("{$path}/classes/JSONRPC/MethodNotFound.php", $serviceGenerator->generatePHPMethodNotFoundClassSource());

	output("{$path}/classes/JSONRPC/InvalidParams.php", $serviceGenerator->generatePHPInvalidParamsClassSource());

	output("{$path}/classes/JSONRPC/InternalError.php", $serviceGenerator->generatePHPInternalErrorClassSource());

	output("{$path}/classes/JSONRPC/ServerError.php", $serviceGenerator->generatePHPServerErrorClassSource());

	output("{$path}/classes/JSONRPC/InvalidProtocolBufferException.php", $serviceGenerator->generatePHPInvalidProtocolBufferExceptionClassSource());

	output("{$path}/classes/JSONRPC/UninitializedMessageException.php", $serviceGenerator->generatePHPUninitializedMessageExceptionClassSource());
}

if (empty($_mode) || $_mode == 'php-service') {
	output("{$path}/classes/JSONRPC/Service.php", $serviceGenerator->generatePHPServiceClassSource());
	
	output("{$path}/classes/JSONRPC/Method.php", $serviceGenerator->generatePHPMethodClassSource());
	
	output("{$path}/classes/JSONRPC/Configuration.php", $serviceGenerator->generatePHPJSONRPCConfigurationClassSource());
	
	output("{$path}/classes/JSONRPC/Authentication.php", $serviceGenerator->generatePHPJSONRPCAuthenticationClassSource());
}

if (empty($_mode) || $_mode == 'php-client') {
	output("{$path}/classes/JSONRPC/Client.php", $serviceGenerator->generatePHPJSONRPCClientClassSource());
}

output("{$path}/public/output.html", $serviceGenerator->generateHTMLSource('output'));

foreach ($proto['services'] as $service) {
	$serviceGenerator = new ServiceGenerator($service);
	if (empty($_mode) || $_mode == 'php-service') {
		output("{$path}/classes/" . str_replace('\\', '/', $serviceGenerator->getPHPNamespace($service['package'])) . '/' . "{$service['service']}.php", $serviceGenerator->generatePHPClassSource());
		output("{$path}/classes/" . str_replace('\\', '/', $serviceGenerator->getPHPNamespace($service['package'])) . '/' . "{$service['service']}Configuration.php", $serviceGenerator->generatePHPConfigurationClassSource(), FALSE);
		output("{$path}/classes/" . str_replace('\\', '/', $serviceGenerator->getPHPNamespace($service['package'])) . '/' . "{$service['service']}Authentication.php", $serviceGenerator->generatePHPAuthenticationClassSource(), FALSE);
		output("{$path}/public/{$service['service']}.php", $serviceGenerator->generatePHPSource());
	}
	if (empty($_mode) || $_mode == 'php-client') {
		output("{$path}/classes/" . str_replace('\\', '/', $serviceGenerator->getPHPNamespace($service['package'])) . '/' . "{$service['service']}Client.php", $serviceGenerator->generatePHPClientClassSource());
	}
	foreach ($service['rpcs'] as $rpcName => $rpc) {
		$rpcGenerator = new RpcGenerator($service, $rpcName, $rpc);
		if (empty($_mode) || $_mode == 'php-service') {
			output("{$path}/classes/" . str_replace('\\', '/', $messageGenerator->getPHPNamespace($service['package'])) . '/' . "{$service['service']}_{$rpcName}.php", $rpcGenerator->generatePHPClassSource(), FALSE);
		}
	}
	$javaScriptSource .= $serviceGenerator->generateJavaScriptSource();
}

if (empty($_mode) || $_mode == 'js-client') {
	output("{$path}/public/js/output.js", $javaScriptSource);
}

function output($path, $contents, $update = TRUE) {
	$parts = explode('/', $path);
	$filename = array_pop($parts);
	$_path = '';
	foreach ($parts as $part) {
		$_path .= "/{$part}";
		if (file_exists($_path)) {
			if (is_dir($_path)) {
				continue;
			}
			echo "ERROR: Path {$_path} is not a directory\n";
		}
		$res = @mkdir($_path);
		if ($res === FALSE) {
			echo "ERROR: Failed to create {$_path}\n";
		}
	}
	if (file_exists($path)) {
		if ($update) {
			file_put_contents($path, $contents);
			echo "modified:\t{$path}\n";
		} else {
			echo "unchanged:\t{$path}\n";
		}
	} else {
		file_put_contents($path, $contents);
		echo "new file:\t{$path}\n";
	}
}
