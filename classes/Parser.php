<?php

class Parser {

	protected $tokenizer;
	protected $proto;
	protected $types;
	
	public function __construct() {
	}

	protected function findType($type) {
		if (in_array($type, array('double', 'float', 'int32', 'int64', 'uint32', 'uint64', 'sint32', 'sint64', 'fixed32', 'fixed64', 'sfixed32', 'sfixed64', 'bool', 'string', 'bytes'))) {
			return $type;
		}

		$_types = $this->types;
		$_type = empty($_types) ? $type : implode('.', $_types) . '.' . $type;
		do {
			if (isset($this->proto['messages'][$_type]) || isset($this->proto['enums'][$_type])) {
				return $_type;
			}
			if (empty($_types)) {
				throw new Exception("Undefined type \"{$type}\" at line {$this->tokenizer->getLastLine()} column {$this->tokenizer->getLastColumn()}");
			}
			array_pop($_types);
			$_type = empty($_types) ? $type : implode('.', $_types) . '.' . $type;
		} while (1);
	}
	
	protected function parsePackage() {
		$this->proto['package'] = $this->tokenizer->next();
		$this->tokenizer->assertNext(";");
	}
	
	protected function parseMessage() {
		$_type = $this->tokenizer->next();
		array_push($this->types, $_type);
		$type = implode('.', $this->types);
		$this->proto['messages'][$type] = array(
			'fields' => array(),
		);
		$this->tokenizer->assertNext("{");
		while (($token = $this->tokenizer->next()) !== "}") {
			switch ($token) {
				case 'required':
				case 'optional':
				case 'repeated':
					$this->parseField($token);
					break;
				case 'enum':
					$this->parseEnum();
					break;
				case 'message':
					$this->parseMessage();
					break;
				default:
					throw new Exception("Unexpected \"{$token}\" at line {$this->tokenizer->getLastLine()} column {$this->tokenizer->getLastColumn()}");
			}
		}
		array_pop($this->types);
	}

	protected function parseField($rule) {
		$type = $this->findType($this->tokenizer->next());
		$field = $this->tokenizer->next();
	
		$_type = implode('.', $this->types);
		$number = NULL;
		$default = NULL;
		
		$token = $this->tokenizer->next();
		switch ($token) {
			case ';':
				break;
			case '=':
				$number = $this->tokenizer->next();
				$token = $this->tokenizer->next();
				if ($token !== '[') {
					break;
				}
			case '[':
				$token = $this->tokenizer->next();
				switch ($token) {
					case ']':
						break;
					case 'default':
						$this->tokenizer->assertNext("=");
						$default = $this->tokenizer->next();
						$this->tokenizer->assertNext("]");
						$this->tokenizer->assertNext(";");
						break;
					default:
						throw new Exception("Unexpected \"{$token}\" at line {$this->tokenizer->getLastLine()} column {$this->tokenizer->getLastColumn()}");
				}
				break;
			default:
				throw new Exception("Unexpected \"{$token}\" at line {$this->tokenizer->getLastLine()} column {$this->tokenizer->getLastColumn()}");
		}
		
		$this->proto['messages'][$_type]['fields'][$field] = array(
				'type' => $type,
				'rule' => $rule,
				'number' => $number,
				'default' => $default,
		);
	}

	protected function parseEnum() {
		$_type = $this->tokenizer->next();
		array_push($this->types, $_type);
		$type = implode('.', $this->types);
		$this->proto['enums'][$type] = array();

		$this->tokenizer->assertNext("{");
		$token = $this->tokenizer->next();
		while ($token !== '}') {
			$name = $token;
			$this->tokenizer->assertNext("=");
			$this->proto['enums'][$type][$name] = $this->tokenizer->next();
			$this->tokenizer->assertNext(";");
			$token = $this->tokenizer->next();
		}
		array_pop($this->types);
	}
	
	protected function parseService() {
		$service = $this->tokenizer->next();
		$this->proto['services'][$service] = array();
		$this->tokenizer->assertNext("{");
		while (($token = $this->tokenizer->next()) !== "}") {
			switch ($token) {
				case "rpc":
					$this->parseRpc($service);
					break;
				default:
					throw new Exception("Unexpected \"{$token}\" at line {$this->tokenizer->getLastLine()} column {$this->tokenizer->getLastColumn()}");
			}
		}
	}

	protected function parseRpc($service) {
		$rpc = $this->tokenizer->next();
		$this->tokenizer->assertNext("(");
		$type = $this->findType($this->tokenizer->next());
		$this->tokenizer->assertNext(")");
		$this->tokenizer->assertNext("returns");
		$this->tokenizer->assertNext("(");
		$returns = $this->findType($this->tokenizer->next());
		$this->tokenizer->assertNext(")");
		$this->tokenizer->assertNext(";");

		$this->proto['services'][$service]['rpcs'][$rpc] = array(
			'type' => $type,
			'returns' => $returns,
		);
	}

	public function parse($path) {
		$pathInfo = pathinfo($path);
		$text = file_get_contents($path);
		
		$this->tokenizer = new Tokenizer($text);
		$this->proto = array(
			'package' => !empty($pathInfo['filename']) ? $pathInfo['filename'] : 'default',
			'enums' => array(),
			'messages' => array(),
			'services' => array(),
		);
		$this->types = array();

		while (($token = $this->tokenizer->next(TRUE)) !== FALSE) {
			switch ($token) {
				case 'package':
					$this->parsePackage();
					break;
				case 'enum':
					$this->parseEnum();
					break;
				case 'message':
					$this->parseMessage();
					break;
				case 'service':
					$this->parseService();
					break;
				default:
					throw new Exception("Unexpected \"{$token}\" at line {$this->tokenizer->getLastLine()} column {$this->tokenizer->getLastColumn()}");
			}
		}

		return $this->proto;
	}

}
