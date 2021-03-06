<?php

class PHPServiceGenerator extends PHPGenerator {

	public function __construct($fileName, $proto) {
		parent::__construct($fileName, $proto);
	}

	public function generate($path) {
		echo "Generating PHP service files...\n";
		
		$source = $this->generateServiceClassSource();
		$filepath = "{$path}/classes/JSONRPC/Service.php";
		$res = $this->output($filepath, $source);
		if ($res) {
			echo "{$filepath}\n";
		}
		
		$source = $this->generateMethodClassSource();
		$filepath = "{$path}/classes/JSONRPC/Method.php";
		$res = $this->output($filepath, $source);
		if ($res) {
			echo "{$filepath}\n";
		}
		
		$source = $this->generateAuthenticationClassSource();
		$filepath = "{$path}/classes/JSONRPC/Authentication.php";
		$res = $this->output($filepath, $source);
		if ($res) {
			echo "{$filepath}\n";
		}
		
		$source = $this->generateValuesClassSource();
		$filepath = "{$path}/classes/JSONRPC/Values.php";
		$res = $this->output($filepath, $source);
		if ($res) {
			echo "{$filepath}\n";
		}
		
		$source = $this->generateClientClassSource();
		$filepath = "{$path}/classes/JSONRPC/Client.php";
		$res = $this->output($filepath, $source);
		if ($res) {
			echo "{$filepath}\n";
		}
		
		foreach ($this->proto['services'] as $service) {
			$source = $this->generateClassSource($service);
			$filepath = "{$path}/classes/" . (empty($service['package']) ? '' : (str_replace('\\', '/', $this->getNamespace($service['package'])) . '/')) . "{$service['service']}.php";
			$res = $this->output($filepath, $source);
			if ($res) {
				echo "{$filepath}\n";
			}
			
			foreach ($service['rpcs'] as $rpcName => $rpc) {
				$source = $this->generateServiceMethodClassSource($service, $rpcName);
				$filepath = "{$path}/classes/" . (empty($service['package']) ? '' : (str_replace('\\', '/', $this->getNamespace($service['package'])) . '/')) . "{$service['service']}_{$rpcName}.php";
				$res = $this->output($filepath, $source, FALSE);
				if ($res) {
					echo "{$filepath}\n";
				}
			}
			
			$source = $this->generateServiceAuthenticationClassSource($service);
			$filepath = "{$path}/classes/" . (empty($service['package']) ? '' : (str_replace('\\', '/', $this->getNamespace($service['package'])) . '/')) . "{$service['service']}Authentication.php";
			$res = $this->output($filepath, $source, FALSE);
			if ($res) {
				echo "{$filepath}\n";
			}
			
			$source = $this->generateServiceConfigurationClassSource($service);
			$filepath = "{$path}/classes/" . (empty($service['package']) ? '' : (str_replace('\\', '/', $this->getNamespace($service['package'])) . '/')) . "{$service['service']}Configuration.php";
			$res = $this->output($filepath, $source, FALSE);
			if ($res) {
				echo "{$filepath}\n";
			}
			
			$source = $this->generateServiceSctiptSource($service);
			$filepath = "{$path}/public/{$service['service']}.php";
			$res = $this->output($filepath, $source);
			if ($res) {
				echo "{$filepath}\n";
			} 
		}
	}

	public function generateServiceClassSource() {
		$source = <<<'SOURCE'
<?php

/*** DO NOT MANUALLY EDIT THIS FILE ***/

namespace JSONRPC;

abstract class Service {

	protected $configurationClassName;
	protected $authenticationClassName;
	protected $methods;

	protected function __construct() {
		$this->configurationClassName = NULL;
		$this->authenticationClassName = NULL;
		$this->methods = array();
	}

	public function setConfigurationClass($configurationClassName) {
		$this->configurationClassName = $configurationClassName;
	}

	public function setAuthenticationClass($authenticationClassName) {
		$this->authenticationClassName = $authenticationClassName;
	}

	public function registerMethod($methodName, $methodClassName, $requestClassName, $responseClassName) {
		$this->methods[$methodName] = array(
			'methodClassName' => $methodClassName,
			'requestClassName' => $requestClassName,
			'responseClassName' => $responseClassName,
		);
		return TRUE;
	}

	public function run() {
		$jsonp = filter_input(INPUT_GET, 'jsonp');
		try {
			if (empty($this->configurationClassName)) {
				$config = new Values();
			} else {
				$config = new $this->configurationClassName();
			}
			$client = new Client();
			if (!empty($this->authenticationClassName)) {
				$authentication = new $this->authenticationClassName($config, $client);
				$authentication->authenticate();
			}
			$input = json_decode(file_get_contents("php://input"));
			if ($input === NULL) {
				throw new ParseError();
			}
			if (is_array($input)) {
				if (empty($input)) {
					throw new InvalidRequest();
				}
				$responses = array();
				foreach ($input as $value) {
					try {
						$request = Request::fromStdClass($value);
						if (!array_key_exists($request->getMethod(), $this->methods)) {
							throw new MethodNotFound();
						}
						$requestClassName = $this->methods[$request->getMethod()]['requestClassName'];
						$params = $requestClassName::fromStdClass($request->getParams());
						$methodClassName = $this->methods[$request->getMethod()]['methodClassName'];
						$method = new $methodClassName($config, $client);
						if (!$method->authorize($params)) {
							throw new InternalError(new \Exception("Not authorized"));
						}
						$response = new Response();
						$response->setResult($method->invoke($params)->toStdClass());
					} catch (\Exception $e) {
						$response = Response::fromException($e);
					}
					if (isset($request) && !$request->hasId()) {
						continue;
					}
					$response->setId(isset($request) ? $request->getId() : NULL);
					array_push($responses, $response->toStdClass());
				}
				if (empty($responses)) {
					die();
				}
				if (empty($jsonp)) {
					header('Content-Type: application/json');
					echo json_encode($responses);
				} else {
					header('Content-Type: application/javascript');
					echo "{$jsonp}(" . json_encode($responses) . ');';
				}
				die();
			} else {
				try {
					$request = Request::fromStdClass($input);
					if (!array_key_exists($request->getMethod(), $this->methods)) {
						throw new MethodNotFound();
					}
					$requestClassName = $this->methods[$request->getMethod()]['requestClassName'];
					$params = $requestClassName::fromStdClass($request->getParams());
					$methodClassName = $this->methods[$request->getMethod()]['methodClassName'];
					$method = new $methodClassName($config, $client);
					if (!$method->authorize($params)) {
						throw new InternalError(new \Exception("Not authorized"));
					}
					$response = new Response();
					$response->setResult($method->invoke($params)->toStdClass());
				} catch (\Exception $e) {
					$response = Response::fromException($e);
				}
				if (isset($request) && !$request->hasId()) {
					die();
				}
				$response->setId(isset($request) ? $request->getId() : NULL);
				if (empty($jsonp)) {
					header('Content-Type: application/json');
					echo json_encode($response->toStdClass());
				} else {
					header('Content-Type: application/javascript');
					echo "{$jsonp}(" . json_encode($response->toStdClass()) . ');';
				}
				die();
			}
		} catch (\Exception $e) {
			$response = Response::fromException($e);
			if (empty($jsonp)) {
				header('Content-Type: application/json');
				echo json_encode($response->toStdClass());
			} else {
				header('Content-Type: application/javascript');
				echo "{$jsonp}(" . json_encode($response->toStdClass()) . ');';
			}
			die();
		}
	}

}

SOURCE;
		return $source;
	}
	
	public function generateMethodClassSource() {
		$source = <<<'SOURCE'
<?php

/*** DO NOT MANUALLY EDIT THIS FILE ***/

namespace JSONRPC;

abstract class Method {

	protected $config;
	protected $client;

	public function __construct($config, $client) {
		$this->config = $config;
		$this->client = $client;
	}

	public abstract function authorize($params);

	public abstract function invoke($params);

}

SOURCE;
		return $source;
	}
	
	public function generateAuthenticationClassSource() {
		$source = <<<'SOURCE'
<?php

/*** DO NOT MANUALLY EDIT THIS FILE ***/

namespace JSONRPC;

abstract class Authentication {

	protected $config;
	protected $client;

	protected function __construct($config, $client) {
		$this->config = $config;
		$this->client = $client;
	}

	public abstract function authenticate();

	protected function hasRequestHeader($header) {
		$requestHeaders = getallheaders();
		return isset($requestHeaders[$header]);
	}

	protected function getRequestHeader($header) {
		$requestHeaders = getallheaders();
		return isset($requestHeaders[$header]) ? $requestHeaders[$header] : NULL;
	}

}

SOURCE;
		return $source;
	}
	
	public function generateServiceAuthenticationClassSource($service) {
		$namespace = $this->getNamespace($service['package']);
$source = <<<SOURCE
<?php


SOURCE;
		if (!empty($namespace)) {
			$source .= <<<SOURCE
namespace {$namespace};


SOURCE;
		}
		$source .= <<<SOURCE
class {$service['service']}Authentication extends \JSONRPC\Authentication {

	public function __construct(\$config, \$client) {
		parent::__construct(\$config, \$client);
	}

	public function authenticate() {
	}

}

SOURCE;
		return $source;
	}

	public function generateValuesClassSource() {
		$source = <<<'SOURCE'
<?php

/*** DO NOT MANUALLY EDIT THIS FILE ***/

namespace JSONRPC;

class Values {

	protected $values;

	public function __construct() {
		$this->values = array();
	}

	public function has($key) {
		return isset($this->values[$key]);
	}

	public function get($key) {
		return $this->has($key) ? $this->values[$key] : NULL;
	}

	public function getAll() {
		return $this->values;
	}

	public function set($key, $value = NULL) {
		if ($value === NULL) {
			$this->clear($key);
		} else {
			$this->values[$key] = $value;
		}
	}

	public function setAll($values) {
		foreach ($values as $key => $value) {
			$this->set($key, $value);
		}
	}

	public function clear($key) {
		if ($this->has($key)) {
			unset($this->values[$key]);
		}
	}

	public function clearAll() {
		$this->values = array();
	}

}

SOURCE;
		return $source;
	}

	public function generateServiceConfigurationClassSource($service) {
		$namespace = $this->getNamespace($service['package']);
		$source = <<<SOURCE
<?php


SOURCE;
		if (!empty($namespace)) {
			$source .= <<<SOURCE
namespace {$namespace};


SOURCE;
		}
		$source .= <<<SOURCE
class {$service['service']}Configuration extends \JSONRPC\Values {

	public function __construct() {
		parent::__construct();
	}

}

SOURCE;
		return $source;
	}
	
	public function generateClientClassSource() {
		$source = <<<'SOURCE'
<?php

/*** DO NOT MANUALLY EDIT THIS FILE ***/

namespace JSONRPC;

class Client extends Values {

	protected $authenticated;

	public function __construct() {
		parent::__construct();
		$this->authenticated = FALSE;
	}

	public function setAuthenticated($authenticated) {
		$this->authenticated = $authenticated;
	}

	public function isAuthenticated() {
		return $this->authenticated;
	}

}

SOURCE;
		return $source;
	}
	
	public function generateServiceMethodClassSource($service, $rpcName) {
		$namespace = $this->getNamespace($service['package']);
		$rpc = $service['rpcs'][$rpcName];
		$returnsType = Registry::getType($rpc['returns']);
		$returns = str_replace('.', '_', $returnsType['name']);
		if ($service['package'] !== $returnsType['package']) {
			$returns = (empty($returnsType['package']) ? '' : '\\' . $this->getNamespace($returnsType['package'])) . '\\' . $returns;
		}
		$source = <<<SOURCE
<?php


SOURCE;
		if (!empty($namespace)) {
			$source .= <<<SOURCE
namespace {$namespace};


SOURCE;
		}
		$source .= <<<SOURCE
class {$service['service']}_{$rpcName} extends \JSONRPC\Method {

	public function __construct(\$config, \$client) {
		parent::__construct(\$config, \$client);
	}

	public function authorize(\$params) {
		return TRUE;
	}

	public function invoke(\$params) {
		\$result = new {$returns}();
		return \$result;
	}

}

SOURCE;
		return $source;
	}

	public function generateClassSource($service) {
		$namespace = $this->getNamespace($service['package']);
		$source = <<<SOURCE
<?php

/*** DO NOT MANUALLY EDIT THIS FILE ***/


SOURCE;
		if (!empty($namespace)) {
			$source .= <<<SOURCE
namespace {$namespace};


SOURCE;
			$namespace .= '\\';
		}
		$source .= <<<SOURCE
class {$service['service']} extends \JSONRPC\Service {

	public function __construct() {
		parent::__construct();

		\$this->setConfigurationClass('\\{$namespace}{$service['service']}Configuration');
		\$this->setAuthenticationClass('\\{$namespace}{$service['service']}Authentication');

SOURCE;
		if (!empty($service['rpcs'])) {
			foreach ($service['rpcs'] as $rpcName => $rpc) {
				$typeType = Registry::getType($rpc['type']);
				$type = (empty($typeType['package']) ? '' : '\\' . $this->getNamespace($typeType['package']))
				. '\\' . str_replace('.', '_', $typeType['name']);
				$returnsType = Registry::getType($rpc['returns']);
				$returns = (empty($returnsType['package']) ? '' : '\\' . $this->getNamespace($returnsType['package']))
				. '\\' . str_replace('.', '_', $returnsType['name']);
				$source .= <<<SOURCE

		\$this->registerMethod('{$rpcName}', '\\{$namespace}{$service['service']}_{$rpcName}', '{$type}', '{$returns}');
SOURCE;
			}
			$source .= "\n";
		}
		$source .= <<<'SOURCE'
	}

}

SOURCE;
		return $source;
	}

	public function generateServiceSctiptSource($service) {
		$namespace = $this->getNamespace($service['package']);
		if (!empty($namespace)) {
			$namespace .= '\\';
		}
		$source = <<<SOURCE
<?php

/*** DO NOT MANUALLY EDIT THIS FILE ***/

spl_autoload_register(function (\$class) {
    include "../classes/" . str_replace('\\\\', '/', \$class) . ".php";
});

\$service = new {$namespace}{$service['service']}();
\$service->run();

SOURCE;
		return $source;
	}

}
