<?php

class ServiceGenerator extends BaseGenerator {
	
	protected $package;
	protected $name;
	protected $service;
	
	public function __construct($package, $name, $service) {
		parent::__construct();
		$this->package = $package;
		$this->name = $name;
		$this->service = $service;
	}

	public function generatePHPSource() {
		$namespace = $this->getPHPNamespace($this->package);
		$source = <<<SOURCE
<?php

/*** DO NOT MANUALLY EDIT THIS FILE ***/

spl_autoload_register(function (\$class) {
    include "../classes/" . str_replace('\\\\', '/', \$class) . ".php";
});

\$service = new {$namespace}\\{$this->name}();
\$service->run();

SOURCE;

		return $source;
	}
	
	public function generatePHPClientClassSource() {
		$namespace = $this->getPHPNamespace($this->package);
		$source = <<<SOURCE
<?php

/*** DO NOT MANUALLY EDIT THIS FILE ***/

namespace {$namespace};

class {$this->name}Client extends \JSONRPC\Client {

	public function __construct(\$url) {
		parent::__construct(\$url);
	}

SOURCE;
		if (!empty($this->service['rpcs'])) {
			foreach ($this->service['rpcs'] as $rpcName => $rpc) {
				$source .= <<<SOURCE

	public function {$rpcName}(\$params) {
		return {$rpc['returns']}::fromStdClass(\$this->invoke('{$rpcName}', \$params->toStdClass()));
	}

SOURCE;
			}
		}
		$source .= <<<'SOURCE'

}

SOURCE;
	
		return $source;
	}
	
	public function generatePHPClassSource() {
		$namespace = $this->getPHPNamespace($this->package);
		$source = <<<SOURCE
<?php

/*** DO NOT MANUALLY EDIT THIS FILE ***/

namespace {$namespace};

class {$this->name} extends \JSONRPC\Service {

	public function __construct() {
		parent::__construct();

		\$this->setConfigurationClass('{$namespace}\\{$this->name}Configuration');
		\$this->setAuthenticationClass('{$namespace}\\{$this->name}Authentication');

SOURCE;
		if (!empty($this->service['rpcs'])) {
			foreach ($this->service['rpcs'] as $rpcName => $rpc) {
				$source .= <<<SOURCE

		\$this->registerMethod('{$rpcName}', '{$namespace}\\{$this->name}_{$rpcName}', '{$namespace}\\{$rpc['type']}', '{$namespace}\\{$rpc['returns']}');
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

	public function generatePHPConfigurationClassSource() {
		$namespace = $this->getPHPNamespace($this->package);
		$source = <<<SOURCE
<?php

namespace {$namespace};

class {$this->name}Configuration extends \JSONRPC\Configuration {

	public function __construct() {
		parent::__construct();
	}

}

SOURCE;
	
		return $source;
	}

	public function generatePHPAuthenticationClassSource() {
		$namespace = $this->getPHPNamespace($this->package);
		$source = <<<SOURCE
<?php

namespace {$namespace};

class {$this->name}Authentication extends \JSONRPC\Authentication {

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

	public function generatePHPJSONRPCConfigurationClassSource() {
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

	public function generatePHPJSONRPCAuthenticationClassSource() {
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

	public function generatePHPJSONRPCClientClassSource() {
		$source = <<<'SOURCE'
<?php

/*** DO NOT MANUALLY EDIT THIS FILE ***/

namespace JSONRPC;

abstract class Client {

	protected $url;
	protected $requestHeaders;
	protected $id;

	protected function __construct($url) {
		$this->url = $url;
		$this->requestHeaders = array();
		$this->id = 0;
	}

	public function getURL() {
		return $this->url;
	}

	public function hasRequestHeader($header) {
		return isset($this->requestHeaders[$header]);
	}

	public function getRequestHeader($header) {
		return $this->hasRequestHeader($header) ? $this->requestHeaders[$header] : NULL; 
	}

	public function setRequestHeader($header, $value = NULL) {
		if ($value === NULL) {
			$this->clearRequestHeader($header);
		} else {
			$this->requestHeaders[$header] = $value;
		}
	}

	public function clearRequestHeader($header) {
		if ($this->hasRequestHeader($header)) {
			unset($this->requestHeaders[$header]);
		}
	}

	public function getId() {
		return ++$this->id;
	}

	public function getLastId() {
		return $this->id;
	}

	protected function invoke($method, $params) {
		$request = new Request();
		$request->setMethod($method);
		$request->setParams($params);
		$request->setId($this->getId());

		$ch = curl_init();
		curl_setopt($ch, CURLOPT_URL, $this->url);
		curl_setopt($ch, CURLOPT_POST, 1);
		curl_setopt($ch, CURLOPT_POSTFIELDS, $request->serialize());
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
		$requestHeaders = array();
		foreach ($this->requestHeaders as $header => $value) {
			array_push($requestHeaders, "{$header}: {$value}");
		}
		curl_setopt($ch, CURLOPT_HTTPHEADER, $requestHeaders);
		$_response = curl_exec($ch);
		curl_close ($ch);

		$response = Response::parse($_response);
		if ($response->hasError()) {
			$error = $response->getError();
			throw new \Exception($error->getMessage(), $error->getCode(), NULL);
		}
		return $response->getResult();
	}

}

SOURCE;
		
		return $source;
	}
	
	public function generatePHPServiceClassSource() {
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
	
	public function generatePHPMethodClassSource() {
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
	
	public function generatePHPRequestClassSource() {
		$source = <<<'SOURCE'
<?php

/*** DO NOT MANUALLY EDIT THIS FILE ***/

namespace JSONRPC;

class Request {

	protected $jsonrpc = '2.0';
	protected $method = NULL;
	protected $params = NULL;
	protected $id = NULL;

	public function __construct() {
	}

	public function getJsonrpc() {
		return $this->jsonrpc;
	}

	public function setJsonrpc($value) {
		$this->jsonrpc = $value;
	}

	public function getMethod() {
		return $this->method;
	}

	public function setMethod($value) {
		$this->method = $value;
	}

	public function hasParams() {
		return NULL != $this->params;
	}

	public function getParams() {
		return $this->params;
	}

	public function setParams($value) {
		$this->params = $value;
	}

	public function hasId() {
		return NULL != $this->id;
	}

	public function getId() {
		return $this->id;
	}

	public function setId($value) {
		$this->id = $value;
	}

	public function toStdClass() {
		$value = new \stdClass();
		$value->jsonrpc = $this->getJsonrpc();
		$value->method = $this->getMethod();
		if ($this->hasParams()) {
			$value->params = $this->getParams();
		}
		if ($this->hasId()) {
			$value->id = $this->getId();
		}
		return $value;
	}

	public function serialize() {
		return json_encode($this->toStdClass());
	}

	public static function fromStdClass($value) {
		$object = new Request();
		if (isset($value->jsonrpc)) {
			if ($value->jsonrpc === '2.0') {
				$object->setJsonrpc($value->jsonrpc);
			} else {
				throw new InvalidRequest();
			}
		} else {
			throw new InvalidRequest();
		}
		if (isset($value->method)) {
			$object->setMethod($value->method);
		} else {
			throw new InvalidRequest();
		}
		if (isset($value->params)) {
			$object->setParams($value->params);
		}
		if (isset($value->id)) {
			$object->setId($value->id);
		}
		return $object;
	}

	public static function parse($value) {
		if (empty($value)) {
			throw new ParseError();
		}
		$object = json_decode($value);
		if ($object === NULL) {
			throw new ParseError();
		}
		return self::fromStdClass($object);
	}

}

SOURCE;
		
		return $source;
	}
	
	public function generatePHPResponseClassSource() {
		$source = <<<'SOURCE'
<?php

/*** DO NOT MANUALLY EDIT THIS FILE ***/

namespace JSONRPC;

class Response {

	protected $jsonrpc = '2.0';
	protected $result = NULL;
	protected $error = NULL;
	protected $id = NULL;

	public function __construct() {
	}

	public function getJsonrpc() {
		return $this->jsonrpc;
	}

	public function setJsonrpc($value) {
		$this->jsonrpc = $value;
	}

	public function hasResult() {
		return NULL !== $this->result;
	}

	public function getResult() {
		return $this->result;
	}

	public function setResult($value) {
		$this->result = $value;
	}

	public function hasError() {
		return NULL !== $this->error;
	}

	public function getError() {
		return $this->error;
	}

	public function setError($value) {
		$this->error = $value;
	}

	public function getId() {
		return $this->id;
	}

	public function setId($value) {
		$this->id = $value;
	}

	public function toStdClass() {
		$value = new \stdClass();
		$value->jsonrpc = $this->getJsonrpc();
		if ($this->hasError()) {
			$value->error = $this->getError()->toStdClass();
		} else {
			$value->result = $this->getResult();
		}
		$value->id = $this->getId();
		return $value;
	}

	public function serialize() {
		return json_encode($this->toStdClass());
	}

	public static function fromStdClass($value) {
		$object = new Response();
		if (isset($value->jsonrpc)) {
			$object->setJsonrpc($value->jsonrpc);
		}
		if (isset($value->result)) {
			$object->setResult($value->result);
		}
		if (isset($value->error)) {
			$object->setError(Response_Error::fromStdClass($value->error));
		}
		if (isset($value->id)) {
			$object->setId($value->id);
		}
		return $object;
	}

	public static function parse($value) {
		return self::fromStdClass(json_decode($value));
	}

}

SOURCE;
		
		return $source;
	}
	
	public function generatePHPResponse_ErrorClassSource() {
		$source = <<<'SOURCE'
<?php

/*** DO NOT MANUALLY EDIT THIS FILE ***/

namespace JSONRPC;

class Response_Error {

	protected $code = NULL;
	protected $message = NULL;
	protected $data = NULL;

	public function __construct($code = NULL, $message = NULL, $data = NULL) {
		$this->code = $code;
		$this->message = $message;
		$this->data = $data;
	}

	public function getCode() {
		return $this->code;
	}

	public function setCode($value) {
		$this->code = $value;
	}

	public function getMessage() {
		return $this->message;
	}

	public function setMessage($value) {
		$this->message = $value;
	}

	public function hasData() {
		return NULL !== $this->data;
	}

	public function getData() {
		return $this->data;
	}

	public function setData($value) {
		$this->data = $value;
	}

	public function toStdClass() {
		$value = new \stdClass();
		$value->code = $this->getCode();
		$value->message = $this->getMessage();
		if ($this->hasData()) {
			$value->data = $this->getData();
		}
		return $value;
	}

	public function serialize() {
		return json_encode($this->toStdClass());
	}

	public static function fromStdClass($value) {
		$object = new Response_Error();
		if (isset($value->code)) {
			$object->setCode($value->code);
		}
		if (isset($value->message)) {
			$object->setMessage($value->message);
		}
		if (isset($value->data)) {
			$object->setData($value->data);
		}
		return $object;
	}

	public static function parse($value) {
		return self::fromStdClass(json_decode($value));
	}

}

SOURCE;

		return $source;
	}
	
	public function generatePHPParseErrorClassSource() {
		$source = <<<'SOURCE'
<?php

/*** DO NOT MANUALLY EDIT THIS FILE ***/

namespace JSONRPC;

class ParseError extends \Exception {

	public function __construct(\Exception $previous = NULL) {
		parent::__construct('Parse error', -32700, $previous);
	}

}

SOURCE;

		return $source;
	}
	
	public function generatePHPInvalidRequestClassSource() {
		$source = <<<'SOURCE'
<?php

/*** DO NOT MANUALLY EDIT THIS FILE ***/

namespace JSONRPC;

class InvalidRequest extends \Exception {

	public function __construct(\Exception $previous = NULL) {
		parent::__construct('Invalid Request', -32600, $previous);
	}

}

SOURCE;

		return $source;
	}
	
	public function generatePHPMethodNotFoundClassSource() {
		$source = <<<'SOURCE'
<?php

/*** DO NOT MANUALLY EDIT THIS FILE ***/

namespace JSONRPC;

class MethodNotFound extends \Exception {

	public function __construct(\Exception $previous = NULL) {
		parent::__construct('Method not found', -32601, $previous);
	}

}

SOURCE;

		return $source;
	}
	
	public function generatePHPInvalidParamsClassSource() {
		$source = <<<'SOURCE'
<?php

/*** DO NOT MANUALLY EDIT THIS FILE ***/

namespace JSONRPC;

class InvalidParams extends \Exception {

	public function __construct(\Exception $previous = NULL) {
		parent::__construct('Invalid params', -32602, $previous);
	}

}

SOURCE;

		return $source;
	}
	
	public function generatePHPInternalErrorClassSource() {
		$source = <<<'SOURCE'
<?php

/*** DO NOT MANUALLY EDIT THIS FILE ***/

namespace JSONRPC;

class InternalError extends \Exception {

	public function __construct(\Exception $previous = NULL) {
		parent::__construct('Internal error' . ($previous == NULL ? '' : " => {$previous->getMessage()}"), -32603, $previous);
	}

}

SOURCE;

		return $source;
	}
	
	public function generatePHPServerErrorClassSource() {
		$source = <<<'SOURCE'
<?php

/*** DO NOT MANUALLY EDIT THIS FILE ***/

namespace JSONRPC;

class ServerError extends \Exception {

	public function __construct($message = NULL, $code = NULL, \Exception $previous = NULL) {
		parent::__construct(is_null($message) ? 'Server error' : $message, is_null($code) ? -32000 : $code, $previous);
	}

}

SOURCE;

		return $source;
	}
	
	public function generatePHPInvalidProtocolBufferExceptionClassSource() {
		$source = <<<'SOURCE'
<?php

/*** DO NOT MANUALLY EDIT THIS FILE ***/

namespace JSONRPC;

class InvalidProtocolBufferException extends \Exception {

	const CODE = -32001;

	public function __construct(\Exception $previous = NULL) {
		parent::__construct('Invalid protocol buffer', self::CODE, $previous);
	}

}

SOURCE;

		return $source;
	}

	public function generatePHPUninitializedMessageExceptionClassSource() {
		$source = <<<'SOURCE'
<?php

/*** DO NOT MANUALLY EDIT THIS FILE ***/

namespace JSONRPC;

class UninitializedMessageException extends \Exception {

	const CODE = -32002;
	
	protected $missingFields = NULL;

	public function __construct($missingFields = NULL, \Exception $previous = NULL) {
		parent::__construct('Uninitialized message', self::CODE, $previous);
		$this->missingFields = $missingFields;
	}

	public function asInvalidProtocolBufferException() {
		return new InvalidProtocolBufferException($this->getPrevious());
	}

	public function getMissingFields() {
		return $this->missingFields;
	}

}

SOURCE;

		return $source;
	}

	public function generateJavaScriptSource() {
		$source = <<<SOURCE

{$this->name} = function() {
	var id = 0;

	getId = function() {
		return ++id;
	};

	this.getLastId = function() {
		return id;
	};


SOURCE;
		foreach ($this->service['rpcs'] as $rpcName => $rpc) {
			$source .= <<<SOURCE
	this.{$rpcName} = function(params, resultHandler, errorHandler) {
		var request = new JSONRPC.Request();
		request.setMethod('$rpcName');
		request.setParams(params.toObject());
		request.setId(getId());
		JSONRPC.send('/{$this->name}.php', request.serialize(),
			function(result) {
				if (typeof resultHandler === 'undefined') {
					return;
				}
				resultHandler({$rpc['returns']}.fromObject(result));
			},
			function(error) {
				if (typeof errorHandler === 'undefined') {
					return;
				}
				errorHandler(error);
			}
		);
	};

SOURCE;
		} 
		$source .= <<<'SOURCE'
};

SOURCE;

		return $source;
	}
	
	public function generateJavaScriptJSONRPCSource() {
		$source = <<<'SOURCE'

var JSONRPC = (function (JSONRPC) {
	Request = function() {
		var jsonrpc = '2.0';
		var method = null;
		var params = null;
		var id = null;

		this.getJsonrpc = function() {
			return jsonrpc;
		};

		this.setJsonrpc = function(value) {
			jsonrpc = value;
		};

		this.getMethod = function() {
			return method;
		};

		this.setMethod = function(value) {
			method = value;
		};

		this.hasParams = function() {
			return null !== params;
		};

		this.getParams = function() {
			return params;
		};

		this.setParams = function(value) {
			params = value;
		};

		this.hasId = function() {
			return null !== id;
		};

		this.getId = function() {
			return id;
		};

		this.setId = function(value) {
			id = value;
		};

		this.toObject = function() {
			var value = new Object();
			value.jsonrpc = this.getJsonrpc();
			value.method = this.getMethod();
			if (this.hasParams()) {
				value.params = this.getParams();
			}
			if (this.hasId()) {
				value.id = this.getId();
			}
			return value;
		};

		this.serialize = function() {
			return JSON.stringify(this.toObject());
		};
	};

	Request.fromObject = function(value) {
		var object = new Request();
		if (typeof value.jsonrpc !== 'undefined') {
			object.setJsonrpc(value.jsonrpc);
		}
		if (typeof value.method !== 'undefined') {
			object.setMethod(value.method);
		}
		if (typeof value.params !== 'undefined') {
			object.setParams(value.params);
		}
		if (typeof value.id !== 'undefined') {
			object.setId(value.id);
		}
		return object;
	};

	Request.parse = function(value) {
		return Request.fromObject(JSON.parse(value));
	};

	Response = function() {
		var jsonrpc = '2.0';
		var result = null;
		var error = null;
		var id = null;

		this.getJsonrpc = function() {
			return jsonrpc;
		};

		this.setJsonrpc = function(value) {
			jsonrpc = value;
		};

		this.hasResult = function() {
			return null !== result;
		};

		this.getResult = function() {
			return result;
		};

		this.setResult = function(value) {
			result = value;
		};

		this.hasError = function() {
			return null !== error;
		};

		this.getError = function() {
			return error;
		};

		this.setError = function(value) {
			error = value;
		};

		this.getId = function() {
			return id;
		};

		this.setId = function(value) {
			id = value;
		};

		this.toObject = function() {
			var value = new Object();
			value.jsonrpc = this.getJsonrpc();
			if (this.hasError()) {
				value.error = this.getError();
			} else {
				value.result = this.getResult();
			}
			value.id = this.getId();
			return value;
		};

		this.serialize = function() {
			return JSON.stringify(this.toObject());
		};
	};

	Response.fromObject = function(value) {
		var object = new Response();
		if (typeof value.jsonrpc !== 'undefined') {
			object.setJsonrpc(value.jsonrpc);
		}
		if (typeof value.result !== 'undefined') {
			object.setResult(value.result);
		}
		if (typeof value.error !== 'undefined') {
			object.setError(Response.Error.fromObject(value.error));
		}
		if (typeof value.id !== 'undefined') {
			object.setId(value.id);
		}
		return object;
	};

	Response.parse = function(value) {
		return Response.fromObject(JSON.parse(value));
	};

	Response.Error = function() {
		var code = null;
		var message = null;
		var data = null;

		this.getCode = function () {
			return code;
		};

		this.setCode = function(value) {
			code = value;
		};

		this.getMessage = function() {
			return message;
		};

		this.setMessage = function(value) {
			message = value;
		};

		this.hasData = function() {
			return null !== data;
		};

		this.getData = function() {
			return data;
		};

		this.setData = function(value) {
			data = value;
		};

		this.toObject = function() {
			var value = new Object();
			value.code = this.getCode();
			value.message = this.getMessage();
			if (this.hasData()) {
				value.data = this.getData();
			}
			return value;
		};

		this.serialize = function() {
			return JSON.stringify(this.toObject());
		};
	};

	Response.Error.fromObject = function(value) {
		var object = new Response.Error();
		if (typeof value.code !== 'undefined') {
			object.setCode(value.code);
		}
		if (typeof value.message !== 'undefined') {
			object.setMessage(value.message);
		}
		if (typeof value.data !== 'undefined') {
			object.setData(value.data);
		}
		return object;
	};

	Response.Error.parse = function(value) {
		return Response.Error.fromObject(JSON.parse(value));
	};

	JSONRPC.Request = Request;
	JSONRPC.Response = Response;

	JSONRPC.send = function(url, data, resultHandler, errorHandler) {
		console.log(data);
		var xhr = new XMLHttpRequest();
		xhr.open('POST', url, true);
		xhr.onreadystatechange = function () {
			if (xhr.readyState === 4) {
				if (xhr.status === 200) {
					console.log(xhr.responseText);
					var response = JSONRPC.Response.parse(xhr.responseText);
					if (response.hasError()) {
						errorHandler(response.getError());
					} else {
						resultHandler(response.getResult());
					}
				}
			}
		};
		xhr.send(data);
	};

	return JSONRPC;
}(JSONRPC || {}));

SOURCE;
		
		return $source;
	}
	
	public function generateHTMLSource($package = 'default') {
		$source = <<<SOURCE
<!DOCTYPE html>
<!-- DO NOT MANUALLY EDIT THIS FILE -->
<html>
  <head>
    <meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
    <title>{$package}</title>
    <script type="text/javascript" src="js/{$package}.js"></script>
  </head>
  <body>
  </body>
</html>
SOURCE;

		return $source;
	}
	
}