<?php

class JavaScriptGenerator extends AbstractGenerator {
	
	public function __construct($proto) {
		parent::__construct($proto);
	}
	
	public function generate($path) {
		echo "Generating JavaScript files...\n";
		
		$source = <<<SOURCE
/*** DO NOT MANUALLY EDIT THIS FILE ***/

SOURCE;
		
		$source .= $this->generateJSONRPCSource();
		
		foreach ($this->proto['enums'] as $enum) {
			$source .= $this->generateEnumSource($enum);
		}
		
		foreach ($this->proto['messages'] as $message) {
			$source .= $this->generateMessageSource($message);
		}
		
		foreach ($this->proto['services'] as $service) {
			$source .= $this->generateServiceSource($service);
		}
		
		$filepath = "{$path}/public/js/output.js";
		$res = $this->output($filepath, $source);
		if ($res) {
			echo "{$filepath}\n";
		}
		
		$source = $this->generateHTMLSource('output');
		$filepath = "{$path}/public/output.html";
		$res = $this->output($filepath, $source);
		if ($res) {
			echo "{$filepath}\n";
		}
	}
	
	public function generateJSONRPCSource() {
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

	public function generateEnumSource($enum) {
		$source = <<<SOURCE

{$enum['type']} = {

SOURCE;
		foreach ($enum['values'] as $name => $value) {
			$source .= "	{$name}: {$value},\n";
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
				$source = Registry::isEnumType($field['type']) ? "{$type['name']}.{$field['options']['default']}" : 'null';
				break;
		}
		return $source;
	}
	
	public function generateMessageSource($message) {
		$source = <<<SOURCE

{$message['type']} = function() {

SOURCE;
		foreach ($message['fields'] as $name => $field) {
			$source .= "	var {$name} = " . ($field['rule'] == 'repeated' ? '[]' : 'null') . ";\n";
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
					$source .= isset($field['options']['default']) ? "		return this.has{$methodName}() ? {$name} : "
						. $this->getFieldDefaultValueSource($message, $field) . ";\n" : "		return {$name};\n";
					$source .= <<<SOURCE
	};

	this.set{$methodName} = function(value) {
		{$name} = value;
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
					$source .= "			value.{$name} = " . (Registry::isMessageType($field['type']) ?
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
					$source .= "				value.{$name}.push(this.get{$methodName}(index)" . (Registry::isMessageType($field['type']) ?
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
					$source .= "		object.set{$methodName}(" . (Registry::isMessageType($field['type']) ?
						"{$type['name']}.fromObject(value.{$name})" : "value.{$name}") . ");";
					break;
				case 'repeated':
					$source .= <<<SOURCE

	if ((typeof value.{$name} !== 'undefined') && (value.{$name} instanceof Array)) {

SOURCE;
					$source .= <<<SOURCE
		for (var index in value.{$name}) {

SOURCE;
					$source .= "			object.add{$methodName}(" . (Registry::isMessageType($field['type']) ?
						"{$type['name']}.fromObject(value.{$name}[index])" : "value.{$name}[index]") . ");";
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
		return $source;
	}

	public function generateServiceSource($service) {
		$source = <<<SOURCE

{$service['service']} = function() {
	var id = 0;

	getId = function() {
		return ++id;
	};

	this.getLastId = function() {
		return id;
	};


SOURCE;
		foreach ($service['rpcs'] as $rpcName => $rpc) {
			$returnsType = Registry::getType($rpc['returns']);
			$source .= <<<SOURCE
	this.{$rpcName} = function(params, resultHandler, errorHandler) {
		var request = new JSONRPC.Request();
		request.setMethod('$rpcName');
		request.setParams(params.toObject());
		request.setId(getId());
		JSONRPC.send('/{$service['service']}.php', request.serialize(),
			function(result) {
				if (typeof resultHandler === 'undefined') {
					return;
				}
				resultHandler({$returnsType['name']}.fromObject(result));
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

	public function generateHTMLSource($filename) {
		$source = <<<SOURCE
<!DOCTYPE html>
<!-- DO NOT MANUALLY EDIT THIS FILE -->
<html>
  <head>
    <meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
    <title>{$filename}</title>
    <script type="text/javascript" src="js/{$filename}.js"></script>
  </head>
  <body>
  </body>
</html>
SOURCE;
		return $source;
	}

}
