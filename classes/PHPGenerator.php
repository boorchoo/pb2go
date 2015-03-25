<?php

class PHPGenerator extends AbstractGenerator {
	
	public static $type = array(
		'double' => array(
			'type' => 'float',
			'default' => '0.0',
		),
		'float' => array(
			'type' => 'float',
			'default' => '0.0',
		),
		'int32' => array(
			'type' => 'int',
			'default' => '0',
		),
		'int64' => array(
			'type' => 'int',
			'default' => '0',
		),
		'uint32' => array(
			'type' => 'int',
			'default' => '0',
		),
		'uint64' => array(
			'type' => 'int',
			'default' => '0',
		),
		'sint32' => array(
			'type' => 'int',
			'default' => '0',
		),
		'sint64' => array(
			'type' => 'int',
			'default' => '0',
		),
		'fixed32' => array(
			'type' => 'int',
			'default' => '0',
		),
		'fixed64' => array(
			'type' => 'int',
			'default' => '0',
		),
		'sfixed32' => array(
			'type' => 'int',
			'default' => '0',
		),
		'sfixed64' => array(
			'type' => 'int',
			'default' => '0',
		),
		'bool' => array(
			'type' => 'bool',
			'default' => 'FALSE',
		),
		'string' => array(
			'type' => 'string',
			'default' => "''",
		),
		'bytes' => array(
			'type' => 'string',
			'default' => "''",
		),
	);
	
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
	
	public function getClass($type) {
		$type_parts = explode('.', $type);
		foreach ($type_parts as &$type_part) {
			$type_part = $this->toCamelCase($type_part);
		}
		return implode('_', $type_parts);
	}
	
	public function getType($type) {
		return isset(self::$type[$type]) ? self::$type[$type] : NULL;
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
		
		$source = $this->generateErrorClassSource();
		$filepath = "{$path}/classes/JSONRPC/Error.php";
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
				
		$source = $this->generateInvalidParamsErrorClassSource();
		$filepath = "{$path}/classes/JSONRPC/InvalidParamsError.php";
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
		
		$source = $this->generateInvalidResultErrorClassSource();
		$filepath = "{$path}/classes/JSONRPC/InvalidResultError.php";
		$res = $this->output($filepath, $source);
		if ($res) {
			echo "{$filepath}\n";
		}
		
		$source = $this->generateUninitializedMessageErrorClassSource();
		$filepath = "{$path}/classes/JSONRPC/UninitializedMessageError.php";
		$res = $this->output($filepath, $source);
		if ($res) {
			echo "{$filepath}\n";
		}
		
		foreach ($this->proto['enums'] as $enum) {
			$source = $this->generateEnumClassSource($enum);
			$filepath = "{$path}/classes/" . (empty($enum['package']) ? '' : (str_replace('\\', '/', $this->getNamespace($enum['package'])) . '/')) . str_replace('.', '_', $enum['type']) . ".php";
			$res = $this->output($filepath, $source);
			if ($res) {
				echo "{$filepath}\n";
			}
		}
		
		foreach ($this->proto['messages'] as $message) {
			foreach ($message['oneofs'] as $oneof) {
				$source = $this->generateOneofEnumClassSource($message, $oneof);
				$filepath = "{$path}/classes/" . (empty($message['package']) ? '' : (str_replace('\\', '/', $this->getNamespace($message['package'])) . '/')) . $this->getClass("{$message['type']}.{$oneof['oneof']}Case") . ".php";
				$res = $this->output($filepath, $source);
				if ($res) {
					echo "{$filepath}\n";
				}
			}
			$source = $this->generateMessageClassSource($message);
			$filepath = "{$path}/classes/" . (empty($message['package']) ? '' : (str_replace('\\', '/', $this->getNamespace($message['package'])) . '/')) . str_replace('.', '_', $message['type']) . ".php";
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
	protected $hasId = FALSE;

	public function __construct() {
	}

	public function getJsonrpc() {
		return $this->jsonrpc;
	}

	public function setJsonrpc($jsonrpc) {
		$this->jsonrpc = $jsonrpc;
	}

	public function getMethod() {
		return $this->method;
	}

	public function setMethod($method) {
		$this->method = $method;
	}

	public function hasParams() {
		return NULL != $this->params;
	}

	public function getParams() {
		return $this->params;
	}

	public function setParams($params) {
		$this->params = $params;
	}

	public function hasId() {
		return $this->hasId;
	}

	public function getId() {
		return $this->id;
	}

	public function setId($id) {
		$this->id = $id;
		$this->hasId = TRUE;
	}

	public function clearId() {
		$this->id = NULL;
		$this->hasId = FALSE;
	}

	public function toStdClass() {
		$object = new \stdClass();
		$object->jsonrpc = $this->getJsonrpc();
		$object->method = $this->getMethod();
		if ($this->hasParams()) {
			$object->params = $this->getParams();
		}
		if ($this->hasId()) {
			$object->id = $this->getId();
		}
		return $object;
	}

	public static function fromStdClass($object) {
		$request = new Request();
		if (!isset($object->jsonrpc) || $object->jsonrpc !== '2.0') {
			throw new InvalidRequest();
		}
		$request->setJsonrpc($object->jsonrpc);
		if (!isset($object->method)) {
			throw new InvalidRequest();
		}
		$request->setMethod($object->method);
		if (isset($object->params)) {
			$request->setParams($object->params);
		}
		if (property_exists($object, 'id')) {
			$request->setId($object->id);
		}
		return $request;
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

	public function setJsonrpc($jsonrpc) {
		$this->jsonrpc = $jsonrpc;
	}

	public function hasResult() {
		return NULL !== $this->result;
	}

	public function getResult() {
		return $this->result;
	}

	public function setResult($result) {
		$this->result = $result;
	}

	public function hasError() {
		return NULL !== $this->error;
	}

	public function getError() {
		return $this->error;
	}

	public function setError($error) {
		$this->error = $error;
	}

	public function getId() {
		return $this->id;
	}

	public function setId($id) {
		$this->id = $id;
	}

	public function toStdClass() {
		$object = new \stdClass();
		$object->jsonrpc = $this->getJsonrpc();
		if ($this->hasError()) {
			$object->error = $this->getError()->toStdClass();
		} else {
			$object->result = $this->getResult();
		}
		$object->id = $this->getId();
		return $object;
	}

	public static function fromStdClass($object) {
		$response = new Response();
		if (isset($object->jsonrpc)) {
			$response->setJsonrpc($object->jsonrpc);
		}
		if (isset($object->result)) {
			$response->setResult($object->result);
		}
		if (isset($object->error)) {
			$response->setError(Error::fromStdClass($object->error));
		}
		if (isset($object->id)) {
			$response->setId($object->id);
		}
		return $response;
	}

	public static function fromException($e) {
		$response = new Response();
		$response->setError(Error::fromException($e));
		return $response;
	}

}

SOURCE;
		return $source;
	}

	public function generateErrorClassSource() {
		$source = <<<'SOURCE'
<?php

/*** DO NOT MANUALLY EDIT THIS FILE ***/

namespace JSONRPC;

class Error extends \Exception {

	protected $data = NULL;

	public function __construct($message = NULL, $code = NULL, $data = NULL) {
		parent::__construct($message, $code, is_a($data, '\\Exception') ? $data : NULL);
		if (is_a($data, '\\JSONRPC\\Error')) {
			$this->data = $data->toStdClass();
		} elseif (is_a($data, '\\Exception')) {
			$this->data = new \stdClass();
			$this->data->code = $data->getCode();
			$this->data->message = $data->getMessage();
			$this->data->file = $data->getFile();
			$this->data->line = $data->getLine();	
		} else {
			$this->data = $data;
		}
	}

	public function hasData() {
		return NULL !== $this->data;
	}

	public function getData() {
		return $this->data;
	}

	public function toStdClass() {
		$object = new \stdClass();
		$object->code = $this->getCode();
		$object->message = $this->getMessage();
		if ($this->hasData()) {
			$object->data = $this->getData();
		}
		return $object;
	}

	public static function fromStdClass($object) {
		$error = new Error(isset($object->message) ? $object->message : NULL, isset($object->code) ? $object->code : NULL, isset($object->data) ? $object->data : NULL);
		return $error;
	}

	public static function fromException($e) {
		$error = new Error($e->getMessage(), $e->getCode(), is_a($e, '\\JSONRPC\\Error') ? $e->getData() : $e->getPrevious());
		return $error;
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

class ParseError extends Error {

	const MESSAGE = 'Parse error';
	const CODE = -32700;

	public function __construct($data = NULL) {
		parent::__construct(self::MESSAGE, self::CODE, $data);
	}

}

SOURCE;
		return $source;
	}

	public function generateInvalidParamsErrorClassSource() {
		$source = <<<'SOURCE'
<?php

/*** DO NOT MANUALLY EDIT THIS FILE ***/

namespace JSONRPC;

class InvalidParamsError extends Error {

	const MESSAGE = 'Invalid params';
	const CODE = -32602;

	public function __construct($data = NULL) {
		parent::__construct(self::MESSAGE, self::CODE, $data);
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

class InternalError extends Error {

	const MESSAGE = 'Internal error';
	const CODE = -32603;

	public function __construct($data = NULL) {
		parent::__construct(self::MESSAGE, self::CODE, $data);
	}

}

SOURCE;
		return $source;
	}

	public function generateInvalidResultErrorClassSource() {
		$source = <<<'SOURCE'
<?php

/*** DO NOT MANUALLY EDIT THIS FILE ***/

namespace JSONRPC;

class InvalidResultError extends Error {

	const MESSAGE = 'Invalid result';
	const CODE = -32001;

	public function __construct($data = NULL) {
		parent::__construct(self::MESSAGE, self::CODE, $data);
	}

}

SOURCE;
		return $source;
	}

	public function generateUninitializedMessageErrorClassSource() {
		$source = <<<'SOURCE'
<?php

/*** DO NOT MANUALLY EDIT THIS FILE ***/

namespace JSONRPC;

class UninitializedMessageError extends Error {

	const MESSAGE = 'Uninitialized message';
	const CODE = -32002;

	public function __construct($missingFields = NULL) {
		parent::__construct(self::MESSAGE, self::CODE, $missingFields);
	}

	public function getMissingFields() {
		return $this->getData();
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


SOURCE;
		if (!empty($namespace)) {
			$source .= <<<SOURCE
namespace {$namespace};


SOURCE;
		}
		$source .= <<<SOURCE
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
	
	public function generateOneofEnumClassSource($message, $oneof) {
		$namespace = $this->getNamespace($message['package']);
		$class = $this->getClass("{$message['type']}.{$oneof['oneof']}Case");
		$oneofCaseNotSet = strtoupper($oneof['oneof']) . '_NOT_SET';
		$source = <<<SOURCE
<?php

/*** DO NOT MANUALLY EDIT THIS FILE ***/


SOURCE;
		if (!empty($namespace)) {
			$source .= <<<SOURCE
namespace {$namespace};


SOURCE;
		}
		$source .= <<<SOURCE
abstract class {$class} {


SOURCE;
		$source .= "	const {$oneofCaseNotSet} = 0;\n";
		foreach ($oneof['fields'] as $field) {
			$oneofCase = strtoupper($field['field']);
			$source .= "	const {$oneofCase} = {$field['tag']};\n";
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


SOURCE;
		if (!empty($namespace)) {
			$source .= <<<SOURCE
namespace {$namespace};


SOURCE;
		}
		$source .= <<<SOURCE
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

SOURCE;
					if (isset($field['oneof'])) {
						$oneofMethodName = $this->toCamelCase($field['oneof']);
						$source .= <<<SOURCE
		\$this->clear{$oneofMethodName}();

SOURCE;
					}
					$source .= <<<SOURCE
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
		foreach ($message['oneofs'] as $oneof) {
			$methodName = $this->toCamelCase($oneof['oneof']);
			$oneofCaseClass = $this->getClass("{$message['type']}.{$oneof['oneof']}Case");
			$oneofCaseNotSet = strtoupper($oneof['oneof']) . '_NOT_SET';
			$source .= <<<SOURCE

	public function get{$methodName}Case() {

SOURCE;
			foreach ($oneof['fields'] as $field) {
				$fieldMethodName = $this->toCamelCase($field['field']);
				$oneofCase = strtoupper($field['field']);
				$source .= <<<SOURCE
		if (\$this->has{$fieldMethodName}()) {
			return {$oneofCaseClass}::{$oneofCase};
		}

SOURCE;
			}
			$source .= <<<SOURCE
		return {$oneofCaseClass}::{$oneofCaseNotSet};
	}

	public function clear{$methodName}() {

SOURCE;
			foreach ($oneof['fields'] as $field) {
				$fieldMethodName = $this->toCamelCase($field['field']);
				$source .= <<<SOURCE
		\$this->clear{$fieldMethodName}();

SOURCE;
			}
			$source .= <<<SOURCE
	}

SOURCE;
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

}

SOURCE;
		return $source;
	}

}
