<?php

class MessageGenerator extends BaseGenerator {
	
	protected $package;
	protected $type;
	protected $message;
	
	protected $className;
	
	public function __construct($package, $type, $message) {
		parent::__construct();
		$this->package = $package;
		$this->type = $type;
		$this->message = $message;
	}
	
	protected function isEnumType($type) {
		global $types;
		return in_array($type, array_keys($types['enums']));
	}
	
	protected function isMessageType($type) {
		global $types;
		return in_array($type, array_keys($types['messages']));
	}
	
	protected function getFieldDefaultValuePHPSource($field) {
		if (!isset($field['options']['default'])) {
			return 'NULL';
		}
		switch ($field['type']) {
			case 'int32':
				$source = "{$field['options']['default']}";
				break;
			case 'string':
				$source = "{$field['options']['default']}";
				break;
			default:
				//TODO: Default values for message types
				$source = $this->isEnumType($field['type']) ? str_replace('.', '_', $field['type']) . "::{$field['options']['default']}" : 'NULL';
				break;
		}
		return $source;
	}
	
	protected function getFieldDefaultValueJavaScriptSource($field) {
		if (!isset($field['options']['default'])) {
			return 'null';
		}
		switch ($field['type']) {
			case 'int32':
				$source = "{$field['options']['default']}";
				break;
			case 'string':
				$source = "{$field['options']['default']}";
				break;
			default:
				//TODO: Default values for message types
				$source = $this->isEnumType($field['type']) ? "{$field['type']}.{$field['options']['default']}" : 'null';
				break;
		}
		return $source;
	}

	public function generatePHPClassSource() {
		$namespace = $this->getPHPNamespace($this->package);
		$class = str_replace('.', '_', $this->type);
		$source = <<<SOURCE
<?php

/*** DO NOT MANUALLY EDIT THIS FILE ***/

namespace {$namespace};

class {$class} {


SOURCE;
		if (!empty($this->message['fields'])) {
			foreach ($this->message['fields'] as $name => $field) {
				$source .= "	protected \${$name} = " . ($field['rule'] == 'repeated' ? 'array()' : 'NULL') . ";\n";
			}
			$source .= "\n";
		}
		$source .= <<<SOURCE
	public function __construct() {
	}

SOURCE;
		foreach ($this->message['fields'] as $name => $field) {
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
						"		return \$this->has{$methodName}() ? \$this->{$name} : " . $this->getFieldDefaultValuePHPSource($field) . ";\n" : "		return \$this->{$name};\n";
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
		foreach ($this->message['fields'] as $name => $field) {
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
		foreach ($this->message['fields'] as $name => $field) {
			$methodName = $this->toCamelCase($name);
			switch ($field['rule']) {
				case 'required':
				case 'optional':
					$source .= <<<SOURCE

		if (\$includeAllFields || \$this->has{$methodName}()) {

SOURCE;
					$source .= "			\$value->{$name} = " . ($this->isMessageType($field['type']) ? "\$this->get{$methodName}() == NULL ? NULL : \$this->get{$methodName}()->toStdClass(\$includeAllFields)" : "\$this->get{$methodName}()") . ";";
					break;
				case 'repeated':
					$source .= <<<SOURCE

		if (\$includeAllFields || \$this->get{$methodName}Count()) {

SOURCE;
					$source .= <<<SOURCE
			\$value->{$name} = array();
			foreach (\$this->get{$methodName}Array() as \${$name}) {

SOURCE;
					$source .= "				array_push(\$value->{$name}, \${$name}" . ($this->isMessageType($field['type']) ? '->toStdClass($includeAllFields)' : '') . ");";
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
		foreach ($this->message['fields'] as $name => $field) {
			$methodName = $this->toCamelCase($name);
			switch ($field['rule']) {
				case 'required':
				case 'optional':
					$source .= <<<SOURCE

		if (isset(\$value->{$name})) {

SOURCE;
					$source .= "			\$object->set{$methodName}(" . ($this->isMessageType($field['type']) ? str_replace('.', '_', $field['type']) . "::fromStdClass(\$value->{$name})" : "\$value->{$name}") . ");";
					break;
				case 'repeated':
					$source .= <<<SOURCE

		if (isset(\$value->{$name}) && is_array(\$value->{$name})) {

SOURCE;
					$source .= <<<SOURCE
			foreach (\$value->{$name} as \${$name}Value) {

SOURCE;
					$source .= "				\$object->add{$methodName}(" . ($this->isMessageType($field['type']) ? str_replace('.', '_', $field['type']) . "::fromStdClass(\${$name}Value)" : "\${$name}Value") . ");";
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
		foreach ($this->message['fields'] as $name => $field) {
			$methodName = $this->toCamelCase($name);
			switch ($field['rule']) {
				case 'required':
				case 'optional':
					$source .= <<<SOURCE

		if (isset(\$value['{$name}'])) {

SOURCE;
					$source .= "			\$object->set{$methodName}(" . ($this->isMessageType($field['type']) ? str_replace('.', '_', $field['type']) . "::fromArray(\$value['{$name}'])" : "\$value['{$name}']") . ");";
					break;
				case 'repeated':
					$source .= <<<SOURCE

		if (isset(\$value['{$name}']) && is_array(\$value['{$name}'])) {

SOURCE;
					$source .= <<<SOURCE
			foreach (\$value['{$name}'] as \${$name}Value) {

SOURCE;
					$source .= "				\$object->add{$methodName}(" . ($this->isMessageType($field['type']) ? str_replace('.', '_', $field['type']) . "::fromArray(\${$name}Value)" : "\${$name}Value") . ");";
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
	
	public function generateJavaScriptClassSource() {
		$source = <<<SOURCE

{$this->type} = function() {

SOURCE;
		if (!empty($this->message['fields'])) {
			foreach ($this->message['fields'] as $name => $field) {
				$source .= "	var {$name} = " . ($field['rule'] == 'repeated' ? '[]' : 'null') . ";\n";
			}
			$source .= "\n";
		}
		foreach ($this->message['fields'] as $name => $field) {
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
					$source .= isset($field['options']['default']) ?
						"		return this.has{$methodName}() ? {$name} : " . $this->getFieldDefaultValueJavaScriptSource($field) . ";\n" : "		return {$name};\n";
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
		foreach ($this->message['fields'] as $name => $field) {
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
		foreach ($this->message['fields'] as $name => $field) {
			$methodName = $this->toCamelCase($name);
			switch ($field['rule']) {
				case 'required':
				case 'optional':
					$source .= <<<SOURCE

		if (includeAllFields || this.has{$methodName}()) {

SOURCE;
					$source .= "			value.{$name} = " . ($this->isMessageType($field['type']) ? "this.get{$methodName}() === null ? null : this.get{$methodName}().toObject(includeAllFields)" : "this.get{$methodName}()") . ";";
					break;
				case 'repeated':
					$source .= <<<SOURCE

		if (includeAllFields || this.get{$methodName}Count() > 0) {

SOURCE;
					$source .= <<<SOURCE
			value.{$name} = [];
			for (var index in this.get{$methodName}Array()) {

SOURCE;
					$source .= "				value.{$name}.push(this.get{$methodName}(index)" . ($this->isMessageType($field['type']) ? '.toObject(includeAllFields)' : '') . ");";
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

{$this->type}.fromObject = function(value) {
	var object = new {$this->type}();
SOURCE;
		foreach ($this->message['fields'] as $name => $field) {
			$methodName = $this->toCamelCase($name);
			switch ($field['rule']) {
				case 'required':
				case 'optional':
					$source .= <<<SOURCE

	if (typeof value.{$name} !== 'undefined') {

SOURCE;
					$source .= "		object.set{$methodName}(" . ($this->isMessageType($field['type']) ? "{$field['type']}.fromObject(value.{$name})" : "value.{$name}") . ");";
					break;
				case 'repeated':
					$source .= <<<SOURCE

	if ((typeof value.{$name} !== 'undefined') && (value.{$name} instanceof Array)) {

SOURCE;
					$source .= <<<SOURCE
		for (var index in value.{$name}) {

SOURCE;
					$source .= "			object.add{$methodName}(" . ($this->isMessageType($field['type']) ? "{$field['type']}.fromObject(value.{$name}[index])" : "value.{$name}[index]") . ");";
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

{$this->type}.parse = function(value) {
	return {$this->type}.fromObject(JSON.parse(value));
};

SOURCE;

		return $source;
	}
	
}