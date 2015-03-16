<?php

class Parser {
	
	const MAX = 536870911;

	protected $proto;
	
	protected $files;
	protected $lexers;

	protected $currentPackage;
	protected $currentType;

	public function __construct() {
		$this->proto = array(
			'enums' => array(),
			'messages' => array(),
			'services' => array(),
			'options' => array(),
		);
		
		$this->files = array();
		$this->lexers = array();
		
		$this->currentPackage = NULL;
		$this->currentType = array();
	}
	
	protected function getFile() {
		return empty($this->files) ? NULL : end($this->files);
	}
	
	protected function getLexer() {
		return empty($this->lexers) ? NULL : end($this->lexers);
	}
	
	protected function getType($type) {
		$registeredType = Registry::getType($type);
		if (!empty($registeredType)) {
			return $registeredType;
		}
	
		$_currentType = $this->currentType;
		$_type = (empty($this->currentPackage) ? '' : "{$this->currentPackage}.") . (empty($_currentType) ? '' : (implode('.', $_currentType) . ".")) . $type;
		do {
			$registeredType = Registry::getType($_type);
			if (!empty($registeredType)) {
				return $registeredType;
			}
			if (empty($_currentType)) {
				throw new Exception("[{$this->getFile()}] Found undefined type {$type}");
			}
			array_pop($_currentType);
			$_type = (empty($this->currentPackage) ? '' : "{$this->currentPackage}.") . (empty($_currentType) ? '' : (implode('.', $_currentType) . ".")) . $type;
		} while (1);
	}

	protected function getNextToken($type = NULL) {
		while ($token = $this->getLexer()->getNextToken()) {
			if ($token->getType() === Lexer::WHITESPACE || $token->getType() === Lexer::SINGLE_LINE_COMMENT || $token->getType() === Lexer::MULTIPLE_LINE_COMMENT) {
				continue;
			}
			if ($type !== NULL && $token->getType() !== $type) {
				throw new Exception("[{$this->getFile()} : {$token->getLine()} : {$token->getColumn()}] Expected {$type} but found {$token->getType()} => {$token->getText()}");
			}
			return $token;
		}
		if ($type !== NULL) {
			throw new Exception("[{$this->getFile()}] Expected {$type} but found EOF");
		}
		return NULL;
	}

	public function parse($file) {
		$text = @file_get_contents($file);
		if ($text === FALSE) {
			throw new Exception("Failed to open file '{$file}'");
		}
		array_push($this->files, $file);
		array_push($this->lexers, new Lexer($text));
		$canImport = TRUE;
		
		while ($this->getLexer()) {
			while ($token = $this->getNextToken()) {
				if ($token->getType() !== Lexer::KEYWORD) {
					throw new Exception("[{$this->getFile()} : {$token->getLine()} : {$token->getColumn()}] Expected " . Lexer::KEYWORD . " but found {$token->getType()} => {$token->getText()}");
				}
				if ($token->getText() !== 'import') {
					$canImport = FALSE;
				}
				switch ($token->getText()) {
					case 'import':
						if (!$canImport) {
							throw new Exception("[{$this->getFile()} : {$token->getLine()} : {$token->getColumn()}] Unexpected {$token->getType()} => {$token->getText()}");
						}
						$this->parseImport();
						break;
					case 'package':
						$this->parsePackage();
						break;
					case 'message':
						$this->parseMessage();
						break;
					case 'enum':
						$this->parseEnum();
						break;
					case 'service':
						$this->parseService();
						break;
					case 'extend':
						$this->parseExtend();
						break;
					case 'option':
						$this->parseOption();
						break;
					default:
						throw new Exception("[{$this->getFile()} : {$token->getLine()} : {$token->getColumn()}] Unexpected {$token->getType()} => {$token->getText()}");
				}
			}
			array_pop($this->files);
			array_pop($this->lexers);
			$this->currentPackage = NULL;
			$this->currentType = array();
			$canImport = TRUE;
		}
		return $this->proto;
	}

	protected function parseImport() {
		$token = $this->getNextToken();
		if (empty($token)) {
			throw new Exception("[{$this->getFile()}] Unexpected EOF");
		}
		$public = FALSE;
		if ($token->getType() === Lexer::KEYWORD && $token->getText() === 'public') {
			$public = TRUE;
			$token = $this->getNextToken();
			if (empty($token)) {
				throw new Exception("[{$this->getFile()}] Unexpected EOF");
			}
		}
		if ($token->getType() !== Lexer::SINGLE_QUOTED_STRING && $token->getType() !== Lexer::DOUBLE_QUOTED_STRING) {
			throw new Exception("[{$this->getFile()} : {$token->getLine()} : {$token->getColumn()}] Expected " . Lexer::SINGLE_QUOTED_STRING . " or "
				. Lexer::DOUBLE_QUOTED_STRING . " but found {$token->getType()} => {$token->getText()}");
		}
		$file = $token->getText();
		$file = stripslashes(substr($file, 1, strlen($file) - 2));
		$this->getNextToken(Lexer::SEMICOLON);
		
		$text = @file_get_contents($file);
		if ($text === FALSE) {
			throw new Exception("[{$this->getFile()}] Failed to import '{$file}'");
		}
		if (in_array($file, $this->files)) {
			throw new Exception("[{$this->getFile()}] Found recursive import '{$file}'");
		}
		array_push($this->files, $file);
		array_push($this->lexers, new Lexer($text));
	}

	protected function parsePackage() {
		$token = $this->getNextToken(Lexer::IDENTIFIER);
		$package = $token->getText();
		//TODO: Check if $package is valid identifier
		$this->getNextToken(Lexer::SEMICOLON);
		$this->currentPackage = $package;
	}

	protected function parseMessage() {
		$token = $this->getNextToken(Lexer::IDENTIFIER);
		$_type = $token->getText();
		array_push($this->currentType, $_type);
		$type = (empty($this->currentPackage) ? '' : "{$this->currentPackage}.") . implode('.', $this->currentType);
		//TODO: Check if $type is valid identifier
		$this->getNextToken(Lexer::OPENING_BRACE);
		Registry::registerMessageType($this->currentPackage, implode('.', $this->currentType));
		$this->proto['messages'][$type] = array(
			'package' => $this->currentPackage,
			'type' => implode('.', $this->currentType),
			'fields' => array(),
			'extensions' => NULL,
			'options' => array(),
		);
		while ($token = $this->getNextToken()) {
			if ($token->getType() === Lexer::CLOSING_BRACE) {
				array_pop($this->currentType);
				return;
			}
			if ($token->getType() !== Lexer::KEYWORD) {
				throw new Exception("[{$this->getFile()} : {$token->getLine()} : {$token->getColumn()}] Expected " . Lexer::KEYWORD
					. " but found {$token->getType()} => {$token->getText()}");
			}
			switch ($token->getText()) {
				case 'required':
				case 'optional':
				case 'repeated':
					$this->parseField($token->getText());
					break;
				case 'oneof':
					$this->parseOneof();
					break;
				case 'message':
					$this->parseMessage();
					break;
				case 'enum':
					$this->parseEnum();
					break;
				case 'extend':
					$this->parseExtend();
					break;
				case 'option':
					$token = $this->getNextToken(Lexer::IDENTIFIER);
					$option = $token->getText();
					$this->getNextToken(Lexer::EQUALS);
					$token = $this->getNextToken();
					if (empty($token)) {
						break;
					}
					$value = $token->getText();
					//TODO: Check if $value is valid value for option
					$this->getNextToken(Lexer::SEMICOLON);
					$this->proto['messages'][$type]['options'][$option] = $value;
					break;
				case 'extensions':
					$this->parseExtensions();
					break;
				case 'group':
					throw new Exception("[{$this->getFile()} : {$token->getLine()} : {$token->getColumn()}] " . Lexer::KEYWORD . " => group is deprecated and "
						. "should not be used when creating new message types - use nested message types instead");
					break;
				default:
					throw new Exception("[{$this->getFile()} : {$token->getLine()} : {$token->getColumn()}] Unexpected {$token->getType()} => {$token->getText()}");
			}
		}
		throw new Exception(empty($token) ? "[{$this->getFile()}] Unexpected EOF" :
			"[{$this->getFile()} : {$token->getLine()} : {$token->getColumn()}] Unexpected {$token->getType()} => {$token->getText()}");
	}
	
	protected function parseOneof() {
		$token = $this->getNextToken(Lexer::IDENTIFIER);
		$name = $token->getText();
		$token = $this->getNextToken(Lexer::OPENING_BRACE);
		while ($token = $this->getNextToken()) {
			if ($token->getType() === Lexer::CLOSING_BRACE) { 
				return;
			}
			$type = $this->getType($token->getText());
			$token = $this->getNextToken(Lexer::IDENTIFIER);
			$field = $token->getText();
			$token = $this->getNextToken();
			if (empty($token)) {
				throw new Exception("[{$this->getFile()}] Unexpected EOF");
			}
			if ($token->getType() !== Lexer::EQUALS && $token->getType() !== Lexer::OPENING_BRACKET && $token->getType() !== Lexer::SEMICOLON) {
				throw new Exception("[{$this->getFile()} : {$token->getLine()} : {$token->getColumn()}] Expected " . Lexer::EQUALS . " or " . Lexer::OPENING_BRACKET
				. " or " . Lexer::SEMICOLON . " but found {$token->getType()} => {$token->getText()}");
			}
			if ($token->getType() === Lexer::EQUALS) {
				$token = $this->getNextToken(Lexer::NUMBER);
				$tag = $token->getText();
				$token = $this->getNextToken();
				if (empty($token)) {
					throw new Exception("[{$this->getFile()}] Unexpected EOF");
				}
			}
			if ($token->getType() !== Lexer::OPENING_BRACKET && $token->getType() !== Lexer::SEMICOLON) {
				throw new Exception("[{$this->getFile()} : {$token->getLine()} : {$token->getColumn()}] Expected " . Lexer::OPENING_BRACKET . " or "
						. Lexer::SEMICOLON . " but found {$token->getType()} => {$token->getText()}");
			}
			$options = array();
			if ($token->getType() === Lexer::OPENING_BRACKET) {
				while ($token = $this->getNextToken()) {
					if ($token->getType() === Lexer::CLOSING_BRACKET) {
						$token = $this->getNextToken(Lexer::SEMICOLON);
						break;
					}
					if ($token->getType() === Lexer::IDENTIFIER) {
						$option = $token->getText();
						$this->getNextToken(Lexer::EQUALS);
						$token = $this->getNextToken();
						if (empty($token)) {
							break;
						}
						$value = $token->getText();
						//TODO: Check if $value is valid value for option
						$options[$option] = $value;
						continue;
					}
					if ($token->getType() === Lexer::COMMA && !empty($options)) {
						continue;
					}
					throw new Exception("[{$this->getFile()} : {$token->getLine()} : {$token->getColumn()}] Unexpected {$token->getType()} => {$token->getText()}");
				}
				if (empty($token)) {
					throw new Exception("[{$this->getFile()}] Unexpected EOF");
				}
			}
			$this->proto['messages'][(empty($this->currentPackage) ? '' : "{$this->currentPackage}.") . implode('.', $this->currentType)]['fields'][$field] = array(
				'rule' => "optional",
				'type' => (empty($type['package']) ? '' : "{$type['package']}.") . $type['name'],
				'tag' => isset($tag) ? $tag : NULL,
				'oneof' => $name,
				'options' => $options,
			);
		}
		throw new Exception(empty($token) ? "[{$this->getFile()}] Unexpected EOF" :
			"[{$this->getFile()} : {$token->getLine()} : {$token->getColumn()}] Unexpected {$token->getType()} => {$token->getText()}");
	}

	protected function parseField($rule) {
		$token = $this->getNextToken();
		if (empty($token)) {
			throw new Exception("[{$this->getFile()}] Unexpected EOF");
		}
		$type = $this->getType($token->getText());
		$token = $this->getNextToken(Lexer::IDENTIFIER);
		$field = $token->getText();
		$token = $this->getNextToken();
		if (empty($token)) {
			throw new Exception("[{$this->getFile()}] Unexpected EOF");
		}
		if ($token->getType() !== Lexer::EQUALS && $token->getType() !== Lexer::OPENING_BRACKET && $token->getType() !== Lexer::SEMICOLON) {
			throw new Exception("[{$this->getFile()} : {$token->getLine()} : {$token->getColumn()}] Expected " . Lexer::EQUALS . " or " . Lexer::OPENING_BRACKET
				. " or " . Lexer::SEMICOLON . " but found {$token->getType()} => {$token->getText()}");
		}
		if ($token->getType() === Lexer::EQUALS) {
			$token = $this->getNextToken(Lexer::NUMBER);
			$tag = $token->getText();
			$token = $this->getNextToken();
			if (empty($token)) {
				throw new Exception("[{$this->getFile()}] Unexpected EOF");
			}
		}
		if ($token->getType() !== Lexer::OPENING_BRACKET && $token->getType() !== Lexer::SEMICOLON) {
			throw new Exception("[{$this->getFile()} : {$token->getLine()} : {$token->getColumn()}] Expected " . Lexer::OPENING_BRACKET . " or "
				. Lexer::SEMICOLON . " but found {$token->getType()} => {$token->getText()}");
		}
		$options = array();
		if ($token->getType() === Lexer::OPENING_BRACKET) {
			while ($token = $this->getNextToken()) {
				if ($token->getType() === Lexer::CLOSING_BRACKET) {
					$token = $this->getNextToken(Lexer::SEMICOLON);
					break;
				}
				if ($token->getType() === Lexer::IDENTIFIER) {
					$option = $token->getText();
					$this->getNextToken(Lexer::EQUALS);
					$token = $this->getNextToken();
					if (empty($token)) {
						break;
					}
					$value = $token->getText();
					//TODO: Check if $value is valid value for option
					$options[$option] = $value;
					continue;
				}
				if ($token->getType() === Lexer::COMMA && !empty($options)) {
					continue;
				}
				throw new Exception("[{$this->getFile()} : {$token->getLine()} : {$token->getColumn()}] Unexpected {$token->getType()} => {$token->getText()}");
			}
			if (empty($token)) {
				throw new Exception("[{$this->getFile()}] Unexpected EOF");
			}
		}
		$this->proto['messages'][(empty($this->currentPackage) ? '' : "{$this->currentPackage}.") . implode('.', $this->currentType)]['fields'][$field] = array(
			'rule' => $rule,
			'type' => (empty($type['package']) ? '' : "{$type['package']}.") . $type['name'],
			'tag' => isset($tag) ? $tag : NULL,
			'oneof' => NULL,
			'options' => $options,
		);
	}

	protected function parseEnum() {
		$token = $this->getNextToken(Lexer::IDENTIFIER);
		$_type = $token->getText();
		array_push($this->currentType, $_type);
		$type = (empty($this->currentPackage) ? '' : "{$this->currentPackage}.") . implode('.', $this->currentType);
		//TODO: Check if $type is valid identifier
		$this->getNextToken(Lexer::OPENING_BRACE);
		Registry::registerEnumType($this->currentPackage, implode('.', $this->currentType));
		$this->proto['enums'][$type] = array(
			'package' => $this->currentPackage,
			'type' => implode('.', $this->currentType),
			'values' => array(),
			'options' => array(),
		);
		while ($token = $this->getNextToken()) {
			if ($token->getType() === Lexer::CLOSING_BRACE) {
				array_pop($this->currentType);
				return;
			}
			if ($token->getType() === Lexer::IDENTIFIER) {
				$name = $token->getText();
				$this->getNextToken(Lexer::EQUALS);
				$token = $this->getNextToken(Lexer::NUMBER);
				$value = $token->getText();
				$this->getNextToken(Lexer::SEMICOLON);
				$this->proto['enums'][$type]['values'][$name] = $value;
				continue;
			}
			if ($token->getType() === Lexer::KEYWORD && $token->getText() === 'option') {
				$token = $this->getNextToken(Lexer::IDENTIFIER);
				$option = $token->getText();
				$this->getNextToken(Lexer::EQUALS);
				$token = $this->getNextToken();
				if (empty($token)) {
					break;
				}
				$value = $token->getText();
				//TODO: Check if $value is valid value for option
				$this->getNextToken(Lexer::SEMICOLON);
				$this->proto['enums'][$type]['options'][$option] = $value;
				continue;
			}
			break;
		}
		throw new Exception(empty($token) ? "[{$this->getFile()}] Unexpected EOF" :
			"[{$this->getFile()} : {$token->getLine()} : {$token->getColumn()}] Unexpected {$token->getType()} => {$token->getText()}");
	}

	protected function parseService() {
		$token = $this->getNextToken(Lexer::IDENTIFIER);
		$service = $token->getText();
		//TODO: Check if $service is valid identifier
		$this->getNextToken(Lexer::OPENING_BRACE);
		$this->proto['services'][(empty($this->currentPackage) ? '' : "{$this->currentPackage}.") . $service] = array(
			'package' => $this->currentPackage,
			'service' => $service,
			'rpcs' => array(),
			'options' => array(),
		);
		while ($token = $this->getNextToken()) {
			if ($token->getType() === Lexer::CLOSING_BRACE) {
				return;
			}
			if ($token->getType() !== Lexer::KEYWORD) {
				throw new Exception("[{$this->getFile()} : {$token->getLine()} : {$token->getColumn()}] Expected " . Lexer::KEYWORD
					. " but found {$token->getType()} => {$token->getText()}");
			}
			switch ($token->getText()) {
				case 'rpc':
					$this->parseRpc($service);
					break;
				case 'option':
					$token = $this->getNextToken(Lexer::IDENTIFIER);
					$option = $token->getText();
					$this->getNextToken(Lexer::EQUALS);
					$token = $this->getNextToken();
					if (empty($token)) {
						break;
					}
					$value = $token->getText();
					//TODO: Check if $value is valid value for option
					$this->getNextToken(Lexer::SEMICOLON);
					$this->proto['services'][(empty($this->currentPackage) ? '' : "{$this->currentPackage}.") . $service]['options'][$option] = $value;
					break;
				default:
					throw new Exception("[{$token->getLine()} : {$token->getColumn()}] Unexpected {$token->getType()} => {$token->getText()}");
			}
		}
		throw new Exception(empty($token) ? "[{$this->getFile()}] Unexpected EOF" :
			"[{$this->getFile()} : {$token->getLine()} : {$token->getColumn()}] Unexpected {$token->getType()} => {$token->getText()}");
	}
	
	protected function parseRpc($service) {
		$token = $this->getNextToken(Lexer::IDENTIFIER);
		$rpc = $token->getText();
		//TODO: Check if $rpc is valid identifier
		$this->getNextToken(Lexer::OPENING_PARENTHESIS);
		$token = $this->getNextToken();
		if (empty($token)) {
			throw new Exception("[{$this->getFile()}] Unexpected EOF");
		}
		$type = $this->getType($token->getText());
		$this->getNextToken(Lexer::CLOSING_PARENTHESIS);
		$token = $this->getNextToken(Lexer::KEYWORD);
		if ($token->getText() !== 'returns') {
			throw new Exception("[{$this->getFile()} : {$token->getLine()} : {$token->getColumn()}] Unexpected {$token->getType()} => {$token->getText()}");
		}
		$this->getNextToken(Lexer::OPENING_PARENTHESIS);
		$token = $this->getNextToken();
		if (empty($token)) {
			throw new Exception("[{$this->getFile()}] Unexpected EOF");
		}
		$returns = $this->getType($token->getText());
		$this->getNextToken(Lexer::CLOSING_PARENTHESIS);
		$token = $this->getNextToken();
		if (empty($token)) {
			throw new Exception("[{$this->getFile()}] Unexpected EOF");
		}
		if ($token->getType() !== Lexer::SEMICOLON && $token->getType() !== Lexer::OPENING_BRACE) {
			throw new Exception("[{$this->getFile()} : {$token->getLine()} : {$token->getColumn()}] Expected " . Lexer::SEMICOLON . " or "
				. Lexer::OPENING_BRACE . " but found {$token->getType()} => {$token->getText()}");
		}
		$options = array();
		if ($token->getType() === Lexer::OPENING_BRACE) {
			while ($token = $this->getNextToken()) {
				if ($token->getType() === Lexer::CLOSING_BRACE) {
					break;
				}
				if ($token->getType() !== Lexer::KEYWORD || $token->getText() !== 'option') {
					throw new Exception("[{$this->getFile()} : {$token->getLine()} : {$token->getColumn()}] Expected " . Lexer::CLOSING_BRACE . " or "
						. Lexer::KEYWORD . " => option but found {$token->getType()} => {$token->getText()}");
				}
				$token = $this->getNextToken(Lexer::IDENTIFIER);
				$option = $token->getText();
				$this->getNextToken(Lexer::EQUALS);
				$token = $this->getNextToken();
				if (empty($token)) {
					break;
				}
				$value = $token->getText();
				//TODO: Check if $value is valid value for option
				$this->getNextToken(Lexer::SEMICOLON);
				$options[$option] = $value;
			}
			if (empty($token)) {
				throw new Exception("[{$this->getFile()}] Unexpected EOF");
			}
		}
		$this->proto['services'][(empty($this->currentPackage) ? '' : "{$this->currentPackage}.") . $service]['rpcs'][$rpc] = array(
			'type' => (empty($type['package']) ? '' : "{$type['package']}.") . $type['name'],
			'returns' => (empty($returns['package']) ? '' : "{$returns['package']}.") . $returns['name'],
			'options' => $options,
		);
	}

	protected function parseExtend() {
		$token = $this->getNextToken(Lexer::IDENTIFIER);
		$_type = $token->getText();
		$type = $this->getType($_type);
		$currentPackage = $this->currentPackage;
		$currentType = $this->currentType;
		$this->currentPackage = $type['package'];
		$this->currentType = explode('.', $type['name']);
		$this->getNextToken(Lexer::OPENING_BRACE);
		while ($token = $this->getNextToken()) {
			if ($token->getType() === Lexer::CLOSING_BRACE) {
				$this->currentPackage = $currentPackage;
				$this->currentType = $currentType;
				return;
			}
			if ($token->getType() !== Lexer::KEYWORD) {
				throw new Exception("[{$this->getFile()} : {$token->getLine()} : {$token->getColumn()}] Expected " . Lexer::KEYWORD
					. " but found {$token->getType()} => {$token->getText()}");
			}
			switch ($token->getText()) {
				case 'required':
				case 'optional':
				case 'repeated':
					$this->parseField($token->getText());
					break;
				case 'oneof':
					$this->parseOneof();
					break;
				case 'message':
					$this->parseMessage();
					break;
				case 'enum':
					$this->parseEnum();
					break;
				case 'extend':
					$this->parseExtend();
					break;
				case 'option':
					$token = $this->getNextToken(Lexer::IDENTIFIER);
					$option = $token->getText();
					$this->getNextToken(Lexer::EQUALS);
					$token = $this->getNextToken();
					if (empty($token)) {
						break;
					}
					$value = $token->getText();
					//TODO: Check if $value is valid value for option
					$this->getNextToken(Lexer::SEMICOLON);
					$this->proto['messages'][(empty($type['package']) ? '' : "{$type['package']}.") . $type['name']]['options'][$option] = $value;
					break;
				default:
					throw new Exception("[{$this->getFile()} : {$token->getLine()} : {$token->getColumn()}] Unexpected {$token->getType()} => {$token->getText()}");
			}
		}
		throw new Exception(empty($token) ? "[{$this->getFile()}] Unexpected EOF" :
			"[{$this->getFile()} : {$token->getLine()} : {$token->getColumn()}] Unexpected {$token->getType()} => {$token->getText()}");
	}

	protected function parseOption() {
		$token = $this->getNextToken(Lexer::IDENTIFIER);
		$option = $token->getText();
		$this->getNextToken(Lexer::EQUALS);
		$token = $this->getNextToken();
		if (empty($token)) {
			break;
		}
		$value = $token->getText();
		//TODO: Check if $value is valid value for option
		$this->getNextToken(Lexer::SEMICOLON);
		$this->proto['options'][$option] = $value;
	}
	
	protected function parseExtensions() {
		$token = $this->getNextToken(Lexer::NUMBER);
		$from = intval($token->getText());
		$token = $this->getNextToken();
		if (empty($token)) {
			throw new Exception("[{$this->getFile()}] Unexpected EOF");
		}
		if ($token->getType() !== Lexer::KEYWORD || $token->getText() !== 'to') {
			throw new Exception("[{$this->getFile()} : {$token->getLine()} : {$token->getColumn()}] Expected " . Lexer::KEYWORD . " => to but found {$token->getType()} => {$token->getText()}");
		}
		$token = $this->getNextToken();
		if (empty($token)) {
			throw new Exception("[{$this->getFile()}] Unexpected EOF");
		}
		if ($token->getType() === Lexer::NUMBER) {
			$to = intval($token->getText());
		} elseif ($token->getType() === Lexer::KEYWORD && $token->getText() === 'max') {
			$to = self::MAX;
		} else {
			throw new Exception("[{$this->getFile()} : {$token->getLine()} : {$token->getColumn()}] Expected " . Lexer::NUMBER . " or " . Lexer::KEYWORD . " => max but found {$token->getType()} => {$token->getText()}");
		}
		$this->getNextToken(Lexer::SEMICOLON);
		$this->proto['messages'][(empty($this->currentPackage) ? '' : "{$this->currentPackage}.") . implode('.', $this->currentType)]['extensions'] = array($from, $to);
	}

}
