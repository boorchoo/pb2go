<?php

class ServiceGenerator extends BaseGenerator {
	
	protected $name;
	protected $service;
	
	public function __construct($name, $service) {
		parent::__construct();
		$this->name = $name;
		$this->service = $service;
	}
	
	public function generatePHPConfigSource() {
		$source = <<<'SOURCE'
<?php

SOURCE;
		return $source;
	}
	
	public function generatePHPSource() {
		$source = <<<SOURCE
<?php

/*** DO NOT MANUALLY EDIT THIS FILE ***/

include '../configs/{$this->name}.php';

spl_autoload_register(function (\$class) {
    include "../classes/{\$class}.php";
});

\$methods = array(
SOURCE;
		if (!empty($this->service['rpcs'])) {
			foreach ($this->service['rpcs'] as $rpcName => $rpc) {
				$source .= <<<SOURCE

	'{$rpcName}' => array(
		'methodClassName' => '{$rpcName}',
		'requestClassName' => '{$rpc['type']}',
		'responseClassName' => '{$rpc['returns']}',
	),
SOURCE;
			}
			$source .= "\n";
		}
		$source .= <<<'SOURCE'
);

try {
	$request = Request::parse(file_get_contents("php://input"));
	//$isNotification = NULL === $request->getId();

	if (FALSE === array_key_exists($request->getMethod(), $methods)) {
		throw new MethodNotFound();
	}
	
	try {
		$params = $methods[$request->getMethod()]['requestClassName']::fromStdClass($request->getParams());
	} catch (Exception $e) {
		throw new InvalidParams($e);
	}
	
	try {
		$method = new $methods[$request->getMethod()]['methodClassName']();
		$result = $method->invoke($params)->toStdClass();
	} catch (Exception $e) {
		throw new InternalError($e);
	}

	$response = new Response();
	$response->setResult($result);
	$response->setId($request->getId());
} catch (Exception $e) {
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

SOURCE;

		return $source;
	}
	
	public function generatePHPMethodClassSource() {
		$source = <<<'SOURCE'
<?php

/*** DO NOT MANUALLY EDIT THIS FILE ***/

abstract class Method {

	public function __construct() {
	}

	public abstract function invoke($params);

}

SOURCE;
		
		return $source;
	}
	
	public function generatePHPRequestClassSource() {
		$source = <<<'SOURCE'
<?php

/*** DO NOT MANUALLY EDIT THIS FILE ***/

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
		$value = new stdClass();
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
		$value = new stdClass();
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
		$value = new stdClass();
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

class ParseError extends Exception {

	public function __construct(Exception $previous = NULL) {
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

class InvalidRequest extends Exception {

	public function __construct(Exception $previous = NULL) {
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

class MethodNotFound extends Exception {

	public function __construct(Exception $previous = NULL) {
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

class InvalidParams extends Exception {

	public function __construct(Exception $previous = NULL) {
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

class InternalError extends Exception {

	public function __construct(Exception $previous = NULL) {
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

class ServerError extends Exception {

	public function __construct($message = NULL, $code = NULL, Exception $previous = NULL) {
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

class InvalidProtocolBufferException extends Exception {

	const CODE = -32001;

	public function __construct(Exception $previous = NULL) {
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

class UninitializedMessageException extends Exception {

	const CODE = -32002;
	
	protected $missingFields = NULL;

	public function __construct($missingFields = NULL, Exception $previous = NULL) {
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

	send = function(data, resultHandler, errorHandler) {
		console.log(data);
		var xhr = new XMLHttpRequest();
		xhr.open('POST', '/{$this->name}.php', true);
		xhr.onreadystatechange = function () {
			if (xhr.readyState === 4) {
				if (xhr.status === 200) {
					console.log(xhr.responseText);
					var response = Response.parse(xhr.responseText);
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


SOURCE;
		foreach ($this->service['rpcs'] as $rpcName => $rpc) {
			$source .= <<<SOURCE
	this.{$rpcName} = function(params, resultHandler, errorHandler) {
		var request = new Request();
		request.setMethod('$rpcName');
		request.setParams(params.toObject());
		request.setId(getId());
		send(request.serialize(), function(result) {
			resultHandler({$rpc['returns']}.fromObject(result));
		}, errorHandler);
	};

SOURCE;
		} 
		$source .= <<<'SOURCE'
};

SOURCE;

		return $source;
	}
	
	public function generateJavaScriptRequestClassSource() {
		$source = <<<'SOURCE'

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

SOURCE;
		
		return $source;
	}
	
	public function generateJavaScriptResponseClassSource() {
		$source = <<<'SOURCE'

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