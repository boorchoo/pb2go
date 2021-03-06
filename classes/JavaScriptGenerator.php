<?php

class JavaScriptGenerator extends AbstractGenerator {
	
	public function __construct($fileName, $proto) {
		parent::__construct($fileName, $proto);
	}
	
	public function generate($path) {
		echo "Generating JavaScript files...\n";
		
		$source = <<<SOURCE
/*** DO NOT MANUALLY EDIT THIS FILE ***/

SOURCE;
		
		$source .= $this->generateJSONRPCSource();
		
		$packages = array();
		foreach ($this->proto['messages'] as $message) {
			if (!in_array($message['package'], $packages)) {
				array_push($packages, $message['package']);
			}
		}
		foreach ($this->proto['enums'] as $enum) {
			if (!in_array($enum['package'], $packages)) {
				array_push($packages, $enum['package']);
			}
		}
		foreach ($this->proto['services'] as $service) {
			if (!in_array($service['package'], $packages)) {
				array_push($packages, $service['package']);
			}
		}
		foreach ($packages as $package) {
			if (!empty($package)) {
				$parts = explode('.', $package);
				$module = array_pop($parts);
				if (!empty($parts)) {
					$source .= <<<SOURCE

var {$parts[0]} = (function(\$this) {
	return \$this;
}({$parts[0]} || {}));


SOURCE;
				} else {
					$source .= <<<SOURCE
				
var 
SOURCE;
				}
				$source .= <<<SOURCE
{$package} = (function(\$this) {

SOURCE;
			}
			$_source = '';
			foreach ($this->proto['messages'] as $message) {
				if ($message['package'] === $package) {
					$_source .= $this->generateMessageSource($message);
					foreach ($message['oneofs'] as $oneof) {
						$_source .= $this->generateOneofEnumSource($message, $oneof);
					}
				}
			}
			
			foreach ($this->proto['enums'] as $enum) {
				if ($enum['package'] === $package) {
					$_source .= $this->generateEnumSource($enum);
				}
			}
			
			foreach ($this->proto['services'] as $service) {
				if ($service['package'] === $package) {
					$_source .= $this->generateServiceClientSource($service);
				}
			}
			if (!empty($package)) {
				$source .= $_source;
				$source .= <<<SOURCE

	return \$this;
}({$package} || {}));

SOURCE;
			} else {
				$lines = explode("\n", $_source);
				foreach ($lines as &$line) {
					$line = substr($line, 1);
				}
				$source .= implode("\n", $lines);
			}
		}
		
		$filepath = "{$path}/public/js/{$this->fileName}.js";
		$res = $this->output($filepath, $source);
		if ($res) {
			echo "{$filepath}\n";
		}
		
		$source = $this->generateHTMLSource();
		$filepath = "{$path}/public/{$this->fileName}.html";
		$res = $this->output($filepath, $source);
		if ($res) {
			echo "{$filepath}\n";
		}
	}
	
	public function generateJSONRPCSource() {
		$source = <<<'SOURCE'

var JSONRPC = (function($this) {

	var Request = function() {

		var jsonrpc = '2.0';
		var method = null;
		var params = null;
		var id = null;
		var hasId = false;

		this.getJsonrpc = function() {
			return jsonrpc;
		};

		this.setJsonrpc = function(_jsonrpc) {
			jsonrpc = _jsonrpc;
		};

		this.getMethod = function() {
			return method;
		};

		this.setMethod = function(_method) {
			method = _method;
		};

		this.hasParams = function() {
			return null !== params;
		};

		this.getParams = function() {
			return params;
		};

		this.setParams = function(_params) {
			params = _params;
		};

		this.hasId = function() {
			return hasId;
		};

		this.getId = function() {
			return id;
		};

		this.setId = function(_id) {
			id = _id;
			hasId = true;
		};

		this.clearId = function() {
			id = null;
			hasId = false;
		}

		this.toObject = function() {
			var object = new Object();
			object.jsonrpc = this.getJsonrpc();
			object.method = this.getMethod();
			if (this.hasParams()) {
				object.params = this.getParams();
			}
			if (this.hasId()) {
				object.id = this.getId();
			}
			return object;
		};

	};

	Request.fromObject = function(object) {
		var request = new Request();
		if (typeof object.jsonrpc === 'undefined' || object.jsonrpc !== '2.0') {
			throw 'Invalid Request';
		}
		request.setJsonrpc(object.jsonrpc);
		if (typeof object.method === 'undefined') {
			throw 'Invalid Request';
		}
		request.setMethod(object.method);
		if (typeof object.params !== 'undefined') {
			request.setParams(object.params);
		}
		if (typeof object.id !== 'undefined') {
			request.setId(object.id);
		}
		return request;
	};

	$this.Request = Request;

	var Response = function() {

		var jsonrpc = '2.0';
		var result = null;
		var error = null;
		var id = null;

		this.getJsonrpc = function() {
			return jsonrpc;
		};

		this.setJsonrpc = function(_jsonrpc) {
			jsonrpc = _jsonrpc;
		};

		this.hasResult = function() {
			return null !== result;
		};

		this.getResult = function() {
			return result;
		};

		this.setResult = function(_result) {
			result = _result;
		};

		this.hasError = function() {
			return null !== error;
		};

		this.getError = function() {
			return error;
		};

		this.setError = function(_error) {
			error = _error;
		};

		this.getId = function() {
			return id;
		};

		this.setId = function(_id) {
			id = _id;
		};

		this.toObject = function() {
			var object = new Object();
			object.jsonrpc = this.getJsonrpc();
			if (this.hasError()) {
				object.error = this.getError().toObject();
			} else {
				object.result = this.getResult();
			}
			object.id = this.getId();
			return object;
		};

	};

	Response.fromObject = function(object) {
		var response = new Response();
		if (typeof object.jsonrpc !== 'undefined') {
			response.setJsonrpc(object.jsonrpc);
		}
		if (typeof object.result !== 'undefined') {
			response.setResult(object.result);
		}
		if (typeof object.error !== 'undefined') {
			response.setError(Response.Error.fromObject(object.error));
		}
		if (typeof object.id !== 'undefined') {
			response.setId(object.id);
		}
		return response;
	};

	$this.Response = Response;

	Response.Error = function() {

		var code = null;
		var message = null;
		var data = null;

		this.getCode = function() {
			return code;
		};

		this.setCode = function(_code) {
			code = _code;
		};

		this.getMessage = function() {
			return message;
		};

		this.setMessage = function(_message) {
			message = _message;
		};

		this.hasData = function() {
			return null !== data;
		};

		this.getData = function() {
			return data;
		};

		this.setData = function(_data) {
			data = _data;
		};

		this.toObject = function() {
			var object = new Object();
			object.code = this.getCode();
			object.message = this.getMessage();
			if (this.hasData()) {
				object.data = this.getData();
			}
			return object;
		};

	};

	Response.Error.fromObject = function(object) {
		var error = new Response.Error();
		if (typeof object.code !== 'undefined') {
			error.setCode(object.code);
		}
		if (typeof object.message !== 'undefined') {
			error.setMessage(object.message);
		}
		if (typeof object.data !== 'undefined') {
			error.setData(object.data);
		}
		return error;
	};

	var ServiceClient = function(_url) {

		var url = _url;
		var requestHeaders = [];
		var id = 0;

		this.getURL = function() {
			return url;
		}

		this.hasRequestHeader = function(header) {
			return typeof requestHeaders[header] === 'undefined' ? false : true;
		}

		this.getRequestHeader = function(header) {
			return typeof requestHeaders[header] === 'undefined' ? null : requestHeaders[header];
		}

		this.setRequestHeader = function(header, value) {
			if (typeof value === 'undefined' || value === null) {
				clearRequestHeader(header);
			} else {
				requestHeaders[header] = value;
			}
		}

		this.clearRequestHeader = function(header) {
			if (hasRequestHeader(header)) {
				delete requestHeaders[header];
			}
		}

		this.getId = function() {
			return ++id;
		};

		this.getLastId = function() {
			return id;
		};

		this.send = function(request, handler) {
			var xhr = new XMLHttpRequest();
			xhr.open('POST', url, true);
			for (var header in requestHeaders) {
				xhr.setRequestHeader(header, requestHeaders[header]);
			}
			xhr.onreadystatechange = function () {
				if (xhr.readyState === 4) {
					if (xhr.status === 200) {
						handler(JSON.parse(xhr.responseText));
					}
				}
			};
			xhr.send(JSON.stringify(request));
		}

		this.invoke = function(method, params, resultHandler, errorHandler) {
			if (typeof params === 'undefined' || params === null) {
				throw 'Invalid params';
			}
			if (!params.isInitialized()) {
				throw 'Uninitialized message';
			}
			var request = new Request();
			request.setMethod(method);
			request.setParams(params.toObject());
			request.setId(this.getId());
			this.send(request.toObject(), function(object) {
				var response = Response.fromObject(object);
				if (response.hasError()) {
					errorHandler(response.getError());
				} else {
					resultHandler(response.getResult());
				}
			});
		};

	};

	$this.ServiceClient = ServiceClient;

	ServiceClient.Batch = function(_serviceClient) {

		var serviceClient = _serviceClient;
		var request = [];
		var result = [];
		var error = [];

		this.getServiceClient = function() {
			return serviceClient;
		};

		this.addRequest = function(method, params, responseClass) {
			if (typeof params === 'undefined' || params === null) {
				throw 'Invalid params';
			}
			if (!params.isInitialized()) {
				throw 'Uninitialized message';
			}
			var _request = new Request();
			_request.setMethod(method);
			_request.setParams(params.toObject());
			_request.setId(serviceClient.getId());
			request[serviceClient.getLastId()] = {
				request: _request,
				responseClass: responseClass
			};
			return serviceClient.getLastId();
		};

		this.clearRequest = function() {
			request = [];
		};

		this.getResultCount = function() {
			return result.length;
		};

		this.hasResult = function(id) {
			return typeof result[id] === 'undefined' ? false : true;
		};

		this.getResult = function(id) {
			return typeof result[id] === 'undefined' ? null : result[id];
		};

		this.getResultArray = function() {
			return result;
		};

		this.setResultFromObject = function(id, object) {
			result[id] = request[id].responseClass.fromObject(object);
		};

		this.clearResult = function() {
			result = [];
		};

		this.getErrorCount = function() {
			return error.length;
		};

		this.hasError = function(id) {
			return typeof error[id] === 'undefined' ? false : true;
		};

		this.getError = function(id) {
			return typeof error[id] === 'undefined' ? null : error[id];
		};

		this.getErrorArray = function() {
			return error;
		};

		this.setError = function(id, _error) {
			error[id] = _error;
		};

		this.clearError = function() {
			error = [];
		};

		this.send = function(handler) {
			var requests = [];
			for (var index in request) {
				requests.push(request[index].request.toObject());
			}
			var $this = this;
			serviceClient.send(requests, function(objects) {
				$this.clearResult();
				$this.clearError();
				for (var index in objects) {
					var response = Response.fromObject(objects[index]);
					if (response.hasError()) {
						$this.setError(response.getId(), response.getError());
					} else {
						$this.setResultFromObject(response.getId(), response.getResult());
					}
				}
				$this.clearRequest();
				handler($this);
			});
		};

	};

	return $this;
}(JSONRPC || {}));

SOURCE;
		return $source;
	}

	public function generateEnumSource($enum) {
		$var = strpos($enum['type'], '.') === FALSE ? 'var ' : '';
		$source = <<<SOURCE

	{$var}{$enum['type']} = {

SOURCE;
		foreach ($enum['values'] as $name => $value) {
			$source .= "		{$name}: {$value},\n";
		}
		$source = substr($source, 0, strlen($source) - 2);
		$source .= <<<SOURCE

	};

SOURCE;
		if (!empty($enum['package']) && strpos($enum['type'], '.') === FALSE) {
			$source .= <<<SOURCE
		
	\$this.{$enum['type']} = {$enum['type']};
		
SOURCE;
		}
		return $source;
	}
	
	public function generateOneofEnumSource($message, $oneof) {
		$type = $this->toCamelCase($oneof['oneof']) . "Case";
		$oneofCaseNotSet = strtoupper($oneof['oneof']) . '_NOT_SET';
		$source = <<<SOURCE

	{$message['type']}.{$type} = {

SOURCE;
		$source .= "		{$oneofCaseNotSet}: 0,\n";
		foreach ($oneof['fields'] as $field) {
			$oneofCase = strtoupper($field['field']);
			$source .= "		{$oneofCase}: {$field['tag']},\n";
		}
		$source = substr($source, 0, strlen($source) - 2);
		$source .= <<<SOURCE

	};

SOURCE;
		return $source;
	}
	
	protected function getFieldDefaultValueSource($message, $field) {
		if (!isset($field['options']['default'])) {
			return 'null';
		}
		$type = Registry::getType($field['type']);
		switch ($type['name']) {
			case 'int32':
				$source = "{$field['options']['default']}";
				break;
			case 'string':
				$source = "{$field['options']['default']}";
				break;
			default:
				$source = Registry::isEnumType($field['type']) ? ($type['package'] === $message['package'] ? '' : "{$type['package']}.") . "{$type['name']}.{$field['options']['default']}" : 'null';
				break;
		}
		return $source;
	}
	
	public function generateMessageSource($message) {
		$var = strpos($message['type'], '.') === FALSE ? 'var ' : ''; 
		$source = <<<SOURCE

	{$var}{$message['type']} = function() {


SOURCE;
		foreach ($message['fields'] as $name => $field) {
			$source .= "		var {$name} = " . ($field['rule'] == 'repeated' ? '[]' : 'null') . ";\n";
		}
		$source .= "\n";
		foreach ($message['fields'] as $name => $field) {
			$methodName = $this->toCamelCase($name);
			switch ($field['rule']) {
				case 'required':
				case 'optional':
					$source .= <<<SOURCE
		this.has{$methodName} = function() {
			return null !== {$name};
		};

		this.get{$methodName} = function() {

SOURCE;
					$source .= isset($field['options']['default']) ? "			return this.has{$methodName}() ? {$name} : "
						. $this->getFieldDefaultValueSource($message, $field) . ";\n" : "			return {$name};\n";
					$source .= <<<SOURCE
		};

		this.set{$methodName} = function(value) {

SOURCE;
					if (isset($field['oneof'])) {
						$oneofMethodName = $this->toCamelCase($field['oneof']);
						$source .= <<<SOURCE
			this.clear{$oneofMethodName}();

SOURCE;
					}
					$source .= <<<SOURCE
			{$name} = value;
		};

		this.clear{$methodName} = function() {
			{$name} = null;
		};


SOURCE;
					break;
				case 'repeated':
					$source .= <<<SOURCE
		this.get{$methodName}Count = function() {
			return {$name}.length;
		};

		this.get{$methodName} = function(index) {
			return typeof {$name}[index] === 'undefined' ? null : {$name}[index];
		};

		this.get{$methodName}Array = function() {
			return {$name};
		};

		this.set{$methodName} = function(index, value) {
			{$name}[index] = value;
		};

		this.add{$methodName} = function(value) {
			{$name}.push(value);
		};

		this.addAll{$methodName} = function(values) {
			for (var index in values) {
				this.add{$methodName}(values[index]);
			}
		};

		this.clear{$methodName} = function() {
			{$name} = [];
		};


SOURCE;
					break;
				default:
					break;
			}
		}
		foreach ($message['oneofs'] as $oneof) {
			$methodName = $this->toCamelCase($oneof['oneof']);
			$oneofCaseClass = "{$message['type']}.{$methodName}Case";
			$oneofCaseNotSet = strtoupper($oneof['oneof']) . '_NOT_SET';
			$source .= <<<SOURCE
		this.get{$methodName}Case = function() {

SOURCE;
			foreach ($oneof['fields'] as $field) {
				$fieldMethodName = $this->toCamelCase($field['field']);
				$oneofCase = strtoupper($field['field']);
				$source .= <<<SOURCE
			if (this.has{$fieldMethodName}()) {
				return {$oneofCaseClass}.{$oneofCase};
			}

SOURCE;
			}
			$source .= <<<SOURCE
			return {$oneofCaseClass}.{$oneofCaseNotSet};
		};

		this.clear{$methodName} = function() {

SOURCE;
			foreach ($oneof['fields'] as $field) {
				$fieldMethodName = $this->toCamelCase($field['field']);
				$source .= <<<SOURCE
			this.clear{$fieldMethodName}();

SOURCE;
			}
			$source .= <<<SOURCE
		};


SOURCE;
		}
		$source .= <<<SOURCE
		this.isInitialized = function() {

SOURCE;
		foreach ($message['fields'] as $name => $field) {
			if ($field['rule'] == 'required') {
				$methodName = $this->toCamelCase($name);
				$source .= <<<SOURCE
			if (!this.has{$methodName}()) {
				return false;
			}

SOURCE;
			}
		}
		$source .= <<<SOURCE
			return true;
		};

		this.toObject = function(includeAllFields) {
			if (typeof includeAllFields === 'undefined') {
				includeAllFields = false;
			}

			var value = new Object();
SOURCE;
		foreach ($message['fields'] as $name => $field) {
			$methodName = $this->toCamelCase($name);
			$type = Registry::getType($field['type']);
			switch ($field['rule']) {
				case 'required':
				case 'optional':
					$source .= <<<SOURCE
	
			if (includeAllFields || this.has{$methodName}()) {
	
SOURCE;
					$source .= "				value.{$name} = " . (Registry::isMessageType($field['type']) ?
						"this.get{$methodName}() === null ? null : this.get{$methodName}().toObject(includeAllFields)" : "this.get{$methodName}()") . ";";
					break;
				case 'repeated':
					$source .= <<<SOURCE

			if (includeAllFields || this.get{$methodName}Count() > 0) {

SOURCE;
					$source .= <<<SOURCE
				value.{$name} = [];
				for (var index in this.get{$methodName}Array()) {

SOURCE;
					$source .= "					value.{$name}.push(this.get{$methodName}(index)" . (Registry::isMessageType($field['type']) ?
						'.toObject(includeAllFields)' : '') . ");";
					$source .= <<<SOURCE

				}
SOURCE;
					break;
				default:
					break;
			}
			$source .= <<<SOURCE

			}
SOURCE;
		}
		$source .= <<<SOURCE

			return value;
		};

		this.serialize = function() {
			return JSON.stringify(this.toObject());
		};

	};

	{$message['type']}.fromObject = function(value) {
		var object = new {$message['type']}();
SOURCE;
		foreach ($message['fields'] as $name => $field) {
			$methodName = $this->toCamelCase($name);
			$type = Registry::getType($field['type']);
			switch ($field['rule']) {
				case 'required':
				case 'optional':
					$source .= <<<SOURCE

		if (typeof value.{$name} !== 'undefined') {

SOURCE;
					$source .= "			object.set{$methodName}(" . (Registry::isMessageType($field['type']) ?
						($type['package'] === $message['package'] ? '' : "{$type['package']}.") . "{$type['name']}.fromObject(value.{$name})" : "value.{$name}") . ");";
					break;
				case 'repeated':
					$source .= <<<SOURCE

		if ((typeof value.{$name} !== 'undefined') && (value.{$name} instanceof Array)) {

SOURCE;
					$source .= <<<SOURCE
			for (var index in value.{$name}) {

SOURCE;
					$source .= "				object.add{$methodName}(" . (Registry::isMessageType($field['type']) ?
						($type['package'] === $message['package'] ? '' : "{$type['package']}.") . "{$type['name']}.fromObject(value.{$name}[index])" : "value.{$name}[index]") . ");";
					$source .= <<<SOURCE
	
			}
SOURCE;
					break;
				default:
					break;
			}
			$source .= <<<SOURCE

		}
SOURCE;
		}
		$source .= <<<SOURCE

		return object;
	};

	{$message['type']}.parse = function(value) {
		return {$message['type']}.fromObject(JSON.parse(value));
	};

SOURCE;
		if (!empty($message['package']) && strpos($message['type'], '.') === FALSE) {
			$source .= <<<SOURCE

	\$this.{$message['type']} = {$message['type']};

SOURCE;
		}
		return $source;
	}

	public function generateServiceClientSource($service) {
		$source = <<<SOURCE

	var {$service['service']}Client = function(_url) {

		this.base = JSONRPC.ServiceClient;
		this.base(_url);

		this.newBatch = function() {
			return new {$service['service']}Client.Batch(this);
		};


SOURCE;
		foreach ($service['rpcs'] as $rpcName => $rpc) {
			$returnsType = Registry::getType($rpc['returns']);
			$returns = ($returnsType['package'] === $service['package'] ? '' : "{$returnsType['package']}.") . $returnsType['name'];
			$source .= <<<SOURCE
		this.{$rpcName} = function(params, resultHandler, errorHandler) {
			this.invoke('$rpcName', params,
				function(result) {
					if (typeof resultHandler === 'undefined') {
						return;
					}
					resultHandler({$returns}.fromObject(result));
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
		$source .= <<<SOURCE
	};
	{$service['service']}Client.prototype = new JSONRPC.ServiceClient;

SOURCE;
		if (!empty($service['package'])) {
			$source .= <<<SOURCE

	\$this.{$service['service']}Client = {$service['service']}Client;

SOURCE;
		}
		$source .= <<<SOURCE

	{$service['service']}Client.Batch = function(_serviceClient) {

		this.base = JSONRPC.ServiceClient.Batch;
		this.base(_serviceClient);


SOURCE;
		foreach ($service['rpcs'] as $rpcName => $rpc) {
			$returnsType = Registry::getType($rpc['returns']);
			$returns = ($returnsType['package'] === $service['package'] ? '' : "{$returnsType['package']}.") . $returnsType['name'];
			$source .= <<<SOURCE
		this.{$rpcName} = function(params) {
			return this.addRequest('$rpcName', params, {$returns});
		};


SOURCE;
		}
		$source .= <<<SOURCE
	};
	{$service['service']}Client.Batch.prototype = new JSONRPC.ServiceClient.Batch;

SOURCE;
		return $source;
	}

	public function generateHTMLSource() {
		$source = <<<SOURCE
<!DOCTYPE html>
<!-- DO NOT MANUALLY EDIT THIS FILE -->
<html>
  <head>
    <meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
    <title>{$this->fileName}</title>
    <script type="text/javascript" src="js/{$this->fileName}.js"></script>
  </head>
  <body>
  </body>
</html>
SOURCE;
		return $source;
	}

}
