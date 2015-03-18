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

	$this.Request = Request;

	var Response = function() {

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

	$this.Response = Response;

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

		getId = function() {
			return ++id;
		};

		this.getLastId = function() {
			return id;
		};

		this.invoke = function(method, params, resultHandler, errorHandler) {
			var request = new JSONRPC.Request();
			request.setMethod(method);
			request.setParams(params.toObject());
			request.setId(getId());
			var xhr = new XMLHttpRequest();
			xhr.open('POST', url, true);
			for (var header in requestHeaders) {
				xhr.setRequestHeader(header, requestHeaders[header]);
			}
			xhr.onreadystatechange = function () {
				if (xhr.readyState === 4) {
					if (xhr.status === 200) {
						var response = Response.parse(xhr.responseText);
						if (response.hasError()) {
							errorHandler(response.getError());
						} else {
							resultHandler(response.getResult());
						}
					}
				}
			};
			xhr.send(request.serialize());
		};

	};

	$this.ServiceClient = ServiceClient;

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
