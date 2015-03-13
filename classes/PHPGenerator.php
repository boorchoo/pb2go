<?php

class PHPGenerator extends AbstractGenerator {
	
	public function __construct($fileName, $proto) {
		parent::__construct($fileName, $proto);
	}
	
	public function getNamespace($package, $prefix = FALSE) {
		$package_parts = explode('.', $package);
		foreach ($package_parts as &$package_part) {
			$package_part = $this->toCamelCase($package_part);
		}
		return ($prefix ? '\\' : '') . implode('\\', $package_parts);
	}
	
	public function generate($path) {
		echo "Generating PHP files...\n";
		
		$source = $this->generateRequestClassSource();
		$filepath = "{$path}/classes/JSONRPC/Request.php";
		$res = $this->output($filepath, $source);
		if ($res) {
			echo "{$filepath}\n";
		}
		
		$source = $this->generateResponseClassSource();
		$filepath = "{$path}/classes/JSONRPC/Response.php";
		$res = $this->output($filepath, $source);
		if ($res) {
			echo "{$filepath}\n";
		}
		
		$source = $this->generateResponse_ErrorClassSource();
		$filepath = "{$path}/classes/JSONRPC/Response_Error.php";
		$res = $this->output($filepath, $source);
		if ($res) {
			echo "{$filepath}\n";
		}
		
		$source = $this->generateParseErrorClassSource();
		$filepath = "{$path}/classes/JSONRPC/ParseError.php";
		$res = $this->output($filepath, $source);
		if ($res) {
			echo "{$filepath}\n";
		}
		
		$source = $this->generateInvalidRequestClassSource();
		$filepath = "{$path}/classes/JSONRPC/InvalidRequest.php";
		$res = $this->output($filepath, $source);
		if ($res) {
			echo "{$filepath}\n";
		}
		
		$source = $this->generateMethodNotFoundClassSource();
		$filepath = "{$path}/classes/JSONRPC/MethodNotFound.php";
		$res = $this->output($filepath, $source);
		if ($res) {
			echo "{$filepath}\n";
		}
		
		$source = $this->generateInvalidParamsClassSource();
		$filepath = "{$path}/classes/JSONRPC/InvalidParams.php";
		$res = $this->output($filepath, $source);
		if ($res) {
			echo "{$filepath}\n";
		}
		
		$source = $this->generateInternalErrorClassSource();
		$filepath = "{$path}/classes/JSONRPC/InternalError.php";
		$res = $this->output($filepath, $source);
		if ($res) {
			echo "{$filepath}\n";
		}
		
		$source = $this->generateServerErrorClassSource();
		$filepath = "{$path}/classes/JSONRPC/ServerError.php";
		$res = $this->output($filepath, $source);
		if ($res) {
			echo "{$filepath}\n";
		}
		
		$source = $this->generateInvalidProtocolBufferExceptionClassSource();
		$filepath = "{$path}/classes/JSONRPC/InvalidProtolBufferException.php";
		$res = $this->output($filepath, $source);
		if ($res) {
			echo "{$filepath}\n";
		}
		
		$source = $this->generateUninitializedMessageExceptionClassSource();
		$filepath = "{$path}/classes/JSONRPC/UninitializedMessageException.php";
		$res = $this->output($filepath, $source);
		if ($res) {
			echo "{$filepath}\n";
		}
		
		foreach ($this->proto['enums'] as $enum) {
			$source = $this->generateEnumClassSource($enum);
			$filepath = "{$path}/classes/" . str_replace('\\', '/', $this->getNamespace($enum['package'])) . '/' . str_replace('.', '_', $enum['type']) . ".php";
			$res = $this->output($filepath, $source);
			if ($res) {
				echo "{$filepath}\n";
			}
		}
		
		foreach ($this->proto['messages'] as $message) {
			$source = $this->generateMessageClassSource($message);
			$filepath = "{$path}/classes/" . str_replace('\\', '/', $this->getNamespace($message['package'])) . '/' . str_replace('.', '_', $message['type']) . ".php";
			$res = $this->output($filepath, $source);
			if ($res) {
				echo "{$filepath}\n";
			}
		}
	}

	public function generateRequestClassSource() {
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

	public function generateResponseClassSource() {
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

	public function generateResponse_ErrorClassSource() {
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

	public function generateParseErrorClassSource() {
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

	public function generateInvalidRequestClassSource() {
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

	public function generateMethodNotFoundClassSource() {
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

	public function generateInvalidParamsClassSource() {
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

	public function generateInternalErrorClassSource() {
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

	public function generateServerErrorClassSource() {
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

	public function generateInvalidProtocolBufferExceptionClassSource() {
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

	public function generateUninitializedMessageExceptionClassSource() {
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
	
	public function generateEnumClassSource($enum) {
		$namespace = $this->getNamespace($enum['package']);
		$class = str_replace('.', '_', $enum['type']);
		$source = <<<SOURCE
<?php

/*** DO NOT MANUALLY EDIT THIS FILE ***/

namespace {$namespace};

abstract class {$class} {


SOURCE;
		foreach ($enum['values'] as $name => $value) {
			$source .= "	const {$name} = {$value};\n";
		}
		$source .= <<<SOURCE

}

SOURCE;
		return $source;
	}
	
	protected function getFieldDefaultValueSource($message, $field) {
		if (!isset($field['options']['default'])) {
			return 'NULL';
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
				$source = Registry::isEnumType($field['type']) ? ($message['package'] === $type['package'] ? '' :
					(empty($type['package']) ? '' : '\\' . $this->getNamespace($type['package'])) . '\\')
					. str_replace('.', '_', $type['name']) . "::{$field['options']['default']}" : 'NULL';
				break;
		}
		return $source;
	}

	public function generateMessageClassSource($message) {
		$namespace = $this->getNamespace($message['package']);
		$class = str_replace('.', '_', $message['type']);
		$source = <<<SOURCE
<?php

/*** DO NOT MANUALLY EDIT THIS FILE ***/

namespace {$namespace};

class {$class} {


SOURCE;
		foreach ($message['fields'] as $name => $field) {
			$source .= "	protected \${$name} = " . ($field['rule'] == 'repeated' ? 'array()' : 'NULL') . ";\n";
		}
		$source .= "\n";
		$source .= <<<SOURCE
	public function __construct() {
	}

SOURCE;
		foreach ($message['fields'] as $name => $field) {
			$methodName = $this->toCamelCase($name);
			switch ($field['rule']) {
				case 'required':
				case 'optional':
					$source .= <<<SOURCE

	public function has{$methodName}() {
		return NULL !== \$this->{$name};
	}

	public function get{$methodName}() {

SOURCE;
					$source .= isset($field['options']['default']) ?
					"		return \$this->has{$methodName}() ? \$this->{$name} : " . $this->getFieldDefaultValueSource($message, $field) . ";\n" : "		return \$this->{$name};\n";
					$source .= <<<SOURCE
	}

	public function set{$methodName}(\$value) {
		\$this->{$name} = \$value;
	}

	public function clear{$methodName}() {
		\$this->{$name} = NULL;
	}

SOURCE;
					break;
				case 'repeated':
					$source .= <<<SOURCE

	public function get{$methodName}Count() {
		return count(\$this->{$name});
	}

	public function get{$methodName}(\$index) {
		return array_key_exists(\$index, \$this->{$name}) ? \$this->{$name}[\$index] : NULL;
	}

	public function get{$methodName}Array() {
		return \$this->{$name};
	}

	public function set{$methodName}(\$index, \$value) {
		\$this->{$name}[\$index] = \$value;
	}

	public function add{$methodName}(\$value) {
		array_push(\$this->{$name}, \$value);
	}

	public function addAll{$methodName}(\$values) {
		foreach (\$values as \$value) {
			\$this->add{$methodName}(\$value);
		}
	}

	public function clear{$methodName}() {
		\$this->{$name} = array();
	}

SOURCE;
					break;
				default:
					break;
			}
		}
		$source .= <<<SOURCE

	public function isInitialized() {

SOURCE;
		foreach ($message['fields'] as $name => $field) {
			if ($field['rule'] == 'required') {
				$methodName = $this->toCamelCase($name);
				$source .= <<<SOURCE
		if (!\$this->has{$methodName}()) {
			return FALSE;
		}

SOURCE;
			}
		}
		$source .= <<<SOURCE
		return TRUE;
	}

	public function toStdClass(\$includeAllFields = FALSE) {
		\$value = new \stdClass();
SOURCE;
		foreach ($message['fields'] as $name => $field) {
			$methodName = $this->toCamelCase($name);
			switch ($field['rule']) {
				case 'required':
				case 'optional':
					$source .= <<<SOURCE

		if (\$includeAllFields || \$this->has{$methodName}()) {

SOURCE;
					$source .= "			\$value->{$name} = " . (Registry::isMessageType($field['type']) ? "\$this->get{$methodName}() == NULL ? NULL : \$this->get{$methodName}()->toStdClass(\$includeAllFields)" : "\$this->get{$methodName}()") . ";";
					break;
				case 'repeated':
					$source .= <<<SOURCE

		if (\$includeAllFields || \$this->get{$methodName}Count()) {

SOURCE;
					$source .= <<<SOURCE
			\$value->{$name} = array();
			foreach (\$this->get{$methodName}Array() as \${$name}) {

SOURCE;
					$source .= "				array_push(\$value->{$name}, \${$name}" . (Registry::isMessageType($field['type']) ? '->toStdClass($includeAllFields)' : '') . ");";
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

		return \$value;
	}

	public function serialize() {
		return json_encode(\$this->toStdClass());
	}

	public static function fromStdClass(\$value) {
		\$object = new {$class}();
SOURCE;
		foreach ($message['fields'] as $name => $field) {
			$methodName = $this->toCamelCase($name);
			$typeType = Registry::getType($field['type']);
			$type = str_replace('.', '_', $typeType['name']);
			if ($message['package'] !== $typeType['package']) {
				$type = (empty($typeType['package']) ? '' : '\\' . $this->getNamespace($typeType['package'])) . '\\' . $type;
			}
			switch ($field['rule']) {
				case 'required':
				case 'optional':
					$source .= <<<SOURCE

		if (isset(\$value->{$name})) {

SOURCE;
					$source .= "			\$object->set{$methodName}(" . (Registry::isMessageType($field['type']) ? "{$type}::fromStdClass(\$value->{$name})" : "\$value->{$name}") . ");";
					break;
				case 'repeated':
					$source .= <<<SOURCE

		if (isset(\$value->{$name}) && is_array(\$value->{$name})) {

SOURCE;
					$source .= <<<SOURCE
			foreach (\$value->{$name} as \${$name}Value) {

SOURCE;
					$source .= "				\$object->add{$methodName}(" . (Registry::isMessageType($field['type']) ? "{$type}::fromStdClass(\${$name}Value)" : "\${$name}Value") . ");";
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

		return \$object;
	}

	public static function fromArray(\$value) {
		\$object = new {$class}();
SOURCE;
		foreach ($message['fields'] as $name => $field) {
			$methodName = $this->toCamelCase($name);
			$typeType = Registry::getType($field['type']);
			$type = str_replace('.', '_', $typeType['name']);
			if ($message['package'] !== $typeType['package']) {
				$type = (empty($typeType['package']) ? '' : '\\' . $this->getNamespace($typeType['package'])) . '\\' . $type;
			}
			switch ($field['rule']) {
				case 'required':
				case 'optional':
					$source .= <<<SOURCE

		if (isset(\$value['{$name}'])) {

SOURCE;
					$source .= "			\$object->set{$methodName}(" . (Registry::isMessageType($field['type']) ? "{$type}::fromArray(\$value['{$name}'])" : "\$value['{$name}']") . ");";
					break;
				case 'repeated':
					$source .= <<<SOURCE

		if (isset(\$value['{$name}']) && is_array(\$value['{$name}'])) {

SOURCE;
					$source .= <<<SOURCE
			foreach (\$value['{$name}'] as \${$name}Value) {

SOURCE;
					$source .= "				\$object->add{$methodName}(" . (Registry::isMessageType($field['type']) ? "{$type}::fromArray(\${$name}Value)" : "\${$name}Value") . ");";
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

		return \$object;
	}

	public static function parse(\$value) {
		return self::fromStdClass(json_decode(\$value));
	}

}

SOURCE;
		return $source;
	}

}
