<?php

class PHPServiceGenerator extends PHPGenerator {

	public function __construct($proto) {
		parent::__construct($proto);
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
		
		$source = $this->generateConfigurationClassSource();
		$filepath = "{$path}/classes/JSONRPC/Configuration.php";
		$res = $this->output($filepath, $source);
		if ($res) {
			echo "{$filepath}\n";
		}
		
		foreach ($this->proto['services'] as $service) {
			$source = $this->generateClassSource($service);
			$filepath = "{$path}/classes/" . str_replace('\\', '/', $this->getNamespace($service['package'])) . "/{$service['service']}.php";
			$res = $this->output($filepath, $source);
			if ($res) {
				echo "{$filepath}\n";
			}
			
			foreach ($service['rpcs'] as $rpcName => $rpc) {
				$source = $this->generateServiceMethodClassSource($service, $rpcName);
				$filepath = "{$path}/classes/" . str_replace('\\', '/', $this->getNamespace($service['package'])) . "/{$service['service']}_{$rpcName}.php";
				$res = $this->output($filepath, $source, FALSE);
				if ($res) {
					echo "{$filepath}\n";
				}
			}
			
			$source = $this->generateServiceAuthenticationClassSource($service);
			$filepath = "{$path}/classes/" . str_replace('\\', '/', $this->getNamespace($service['package'])) . "/{$service['service']}Authentication.php";
			$res = $this->output($filepath, $source, FALSE);
			if ($res) {
				echo "{$filepath}\n";
			}
			
			$source = $this->generateServiceConfigurationClassSource($service);
			$filepath = "{$path}/classes/" . str_replace('\\', '/', $this->getNamespace($service['package'])) . "/{$service['service']}Configuration.php";
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
		try {
			if (empty($this->configurationClassName)) {
				$config = new Configuration();
			} else {
				$config = new $this->configurationClassName();
			}

			if (empty($this->authenticationClassName)) {
				$client = NULL;
			} else {
				$authentication = new $this->authenticationClassName($config);
				$client = $authentication->authenticate();
			}

			$request = Request::parse(file_get_contents("php://input"));
			//$isNotification = NULL === $request->getId();

			if (FALSE === array_key_exists($request->getMethod(), $this->methods)) {
				throw new MethodNotFound();
			}

			try {
				$requestClassName = $this->methods[$request->getMethod()]['requestClassName'];
				$params = $requestClassName::fromStdClass($request->getParams());
			} catch (\Exception $e) {
				throw new InvalidParams($e);
			}

			try {
				$methodClassName = $this->methods[$request->getMethod()]['methodClassName'];
				$method = new $methodClassName($config, $client);
				$authorized = $method->authorize($params);
			} catch (\Exception $e) {
				throw new InternalError($e);
			}

			if ($authorized) {
				try {
					$result = $method->invoke($params)->toStdClass();
				} catch (\Exception $e) {
					throw new InternalError($e);
				}
			} else {
				throw new InternalError(new \Exception("Not authorized"));
			}

			$response = new Response();
			$response->setResult($result);
			$response->setId($request->getId());
		} catch (\Exception $e) {
			$response = new Response();
			$response->setError(new Response_Error($e->getCode(), $e->getMessage(), NULL));
			$response->setId(isset($request) ? $request->getId() : NULL);
		}

		$jsonp = filter_input(INPUT_GET, 'jsonp');
		if (empty($jsonp)) {
			header('Content-Type: application/json');
			echo $response->serialize();
		} else {
			header('Content-Type: application/javascript');
			echo "{$jsonp}({$response->serialize()});";
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

	protected function __construct($config) {
		$this->config = $config;
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

namespace {$namespace};

class {$service['service']}Authentication extends \JSONRPC\Authentication {

	public function __construct(\$config) {
		parent::__construct(\$config);
	}

	public function authenticate() {
		\$client = NULL;
		return \$client;
	}

}

SOURCE;
		return $source;
	}

	public function generateConfigurationClassSource() {
		$source = <<<'SOURCE'
<?php

/*** DO NOT MANUALLY EDIT THIS FILE ***/

namespace JSONRPC;

class Configuration {

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

	public function set($key, $value = NULL) {
		if ($value === NULL) {
			$this->clear($key);
		} else {
			$this->values[$key] = $value;
		}
	}

	public function clear($key) {
		if ($this->has($key)) {
			unset($this->values[$key]);
		}
	}

}

SOURCE;
		return $source;
	}

	public function generateServiceConfigurationClassSource($service) {
		$namespace = $this->getNamespace($service['package']);
		$source = <<<SOURCE
<?php

namespace {$namespace};

class {$service['service']}Configuration extends \JSONRPC\Configuration {

	public function __construct() {
		parent::__construct();
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

namespace {$namespace};

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

namespace {$namespace};

class {$service['service']} extends \JSONRPC\Service {

	public function __construct() {
		parent::__construct();

		\$this->setConfigurationClass('\\{$namespace}\\{$service['service']}Configuration');
		\$this->setAuthenticationClass('\\{$namespace}\\{$service['service']}Authentication');

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

		\$this->registerMethod('{$rpcName}', '\\{$namespace}\\{$service['service']}_{$rpcName}', '{$type}', '{$returns}');
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
		$source = <<<SOURCE
<?php

/*** DO NOT MANUALLY EDIT THIS FILE ***/

spl_autoload_register(function (\$class) {
    include "../classes/" . str_replace('\\\\', '/', \$class) . ".php";
});

\$service = new {$namespace}\\{$service['service']}();
\$service->run();

SOURCE;
		return $source;
	}

}
