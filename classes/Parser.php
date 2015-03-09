<?php

class Parser {

	protected $lexer;
	protected $proto;
	
	protected $currentType;

	public function __construct($text) {
		$this->lexer = new Lexer($text);
		$this->proto = array(
			'package' => NULL,
			'enums' => array(),
			'messages' => array(),
			'services' => array(),
			'options' => array(),
		);
		
		$this->currentType = array();
	}
	
	protected function getType($type) {
		if (in_array($type, array('double', 'float', 'int32', 'int64', 'uint32', 'uint64', 'sint32', 'sint64', 'fixed32', 'fixed64', 'sfixed32', 'sfixed64', 'bool', 'string', 'bytes'))) {
			return $type;
		}
	
		$_currentType = $this->currentType;
		$_type = empty($_currentType) ? $type : (implode('.', $_currentType) . ".{$type}");
		do {
			if (isset($this->proto['messages'][$_type]) || isset($this->proto['enums'][$_type])) {
				return $_type;
			}
			if (empty($_currentType)) {
				throw new Exception("Found undefined type {$type}");
			}
			array_pop($_currentType);
			$_type = empty($_currentType) ? $type : (implode('.', $_currentType) . ".{$type}");
		} while (1);
	}

	protected function getNextToken($type = NULL) {
		while ($token = $this->lexer->getNextToken()) {
			if ($token->getType() === Lexer::WHITESPACE || $token->getType() === Lexer::SINGLE_LINE_COMMENT || $token->getType() === Lexer::MULTIPLE_LINE_COMMENT) {
				continue;
			}
			if ($type !== NULL && $token->getType() !== $type) {
				throw new Exception("[{$token->getLine()} : {$token->getColumn()}] Expected {$type} but found {$token->getType()} => {$token->getText()}");
			}
			return $token;
		}
		if ($type !== NULL) {
			throw new Exception("Expected {$type} but found EOF");
		}
		return NULL;
	}

	public function parse() {
		while ($token = $this->getNextToken()) {
			if ($token->getType() !== Lexer::KEYWORD) {
				throw new Exception("[{$token->getLine()} : {$token->getColumn()}] Expected " . Lexer::KEYWORD . " but found {$token->getType()} => {$token->getText()}");
			}
			switch ($token->getText()) {
				case 'import':
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
					throw new Exception("[{$token->getLine()} : {$token->getColumn()}] Unexpected {$token->getType()} => {$token->getText()}");
			}
		}
		return $this->proto;
	}

	protected function parseImport() {
		$token = $this->getNextToken();
		if (empty($token)) {
			throw new Exception('Unexpected EOF');
		}
		$public = FALSE;
		if ($token->getType() === Lexer::KEYWORD && $token->getText() === 'public') {
			$public = TRUE;
			$token = $this->getNextToken();
			if (empty($token)) {
				throw new Exception('Unexpected EOF');
			}
		}
		if ($token->getType() !== Lexer::SINGLE_QUOTED_STRING && $token->getType() !== Lexer::DOUBLE_QUOTED_STRING) {
			throw new Exception("Expected " . Lexer::SINGLE_QUOTED_STRING . " or " . Lexer::DOUBLE_QUOTED_STRING . " but found {$token->getType()} => {$token->getText()}");
		}
		$path = $token->getText();
		$this->getNextToken(Lexer::SEMICOLON);
		//TODO: Parse $path
	}

	protected function parsePackage() {
		$token = $this->getNextToken(Lexer::IDENTIFIER);
		$package = $token->getText();
		//TODO: Check if $package is valid identifier
		$this->getNextToken(Lexer::SEMICOLON);
		$this->proto['package'] = $package;
	}

	protected function parseMessage() {
		$token = $this->getNextToken(Lexer::IDENTIFIER);
		$_type = $token->getText();
		array_push($this->currentType, $_type);
		$type = implode('.', $this->currentType);
		//TODO: Check if $type is valid identifier
		$this->getNextToken(Lexer::OPENING_BRACE);
		$this->proto['messages'][$type] = array(
			'fields' => array(),
			'options' => array(),
		);
		while ($token = $this->getNextToken()) {
			if ($token->getType() === Lexer::CLOSING_BRACE) {
				array_pop($this->currentType);
				return;
			}
			if ($token->getType() !== Lexer::KEYWORD) {
				throw new Exception("[{$token->getLine()} : {$token->getColumn()}] Expected " . Lexer::KEYWORD . " but found {$token->getType()} => {$token->getText()}");
			}
			switch ($token->getText()) {
				case 'required':
				case 'optional':
				case 'repeated':
					$this->parseField($token->getText());
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
				default:
					throw new Exception("[{$token->getLine()} : {$token->getColumn()}] Unexpected {$token->getType()} => {$token->getText()}");
			}
		}
		throw new Exception(empty($token) ? 'Unexpected EOF' : "[{$token->getLine()} : {$token->getColumn()}] Unexpected {$token->getType()} => {$token->getText()}");
	}

	protected function parseField($rule) {
		$token = $this->getNextToken();
		if (empty($token)) {
			throw new Exception('Unexpected EOF');
		}
		$type = $this->getType($token->getText());
		$token = $this->getNextToken(Lexer::IDENTIFIER);
		$field = $token->getText();
		$token = $this->getNextToken();
		if (empty($token)) {
			throw new Exception('Unexpected EOF');
		}
		if ($token->getType() !== Lexer::EQUALS && $token->getType() !== Lexer::OPENING_BRACKET && $token->getType() !== Lexer::SEMICOLON) {
			throw new Exception("[{$token->getLine()} : {$token->getColumn()}] Expected " . Lexer::EQUALS . " or " . Lexer::OPENING_BRACKET
				. " or " . Lexer::SEMICOLON . " but found {$token->getType()} => {$token->getText()}");
		}
		if ($token->getType() === Lexer::EQUALS) {
			$token = $this->getNextToken(Lexer::NUMBER);
			$tag = $token->getText();
			$token = $this->getNextToken();
			if (empty($token)) {
				throw new Exception('Unexpected EOF');
			}
		}
		if ($token->getType() !== Lexer::OPENING_BRACKET && $token->getType() !== Lexer::SEMICOLON) {
			throw new Exception("[{$token->getLine()} : {$token->getColumn()}] Expected " . Lexer::OPENING_BRACKET . " or "
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
				throw new Exception("[{$token->getLine()} : {$token->getColumn()}] Unexpected {$token->getType()} => {$token->getText()}");
			}
			if (empty($token)) {
				throw new Exception('Unexpected EOF');
			}
		}
		$this->proto['messages'][implode('.', $this->currentType)]['fields'][$field] = array(
			'rule' => $rule,
			'type' => $type,
			'tag' => isset($tag) ? $tag : NULL,
			'options' => $options,
		);
	}

	protected function parseEnum() {
		$token = $this->getNextToken(Lexer::IDENTIFIER);
		$_type = $token->getText();
		array_push($this->currentType, $_type);
		$type = implode('.', $this->currentType);
		//TODO: Check if $type is valid identifier
		$this->getNextToken(Lexer::OPENING_BRACE);
		$this->proto['enums'][$type] = array(
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
		throw new Exception(empty($token) ? 'Unexpected EOF' : "[{$token->getLine()} : {$token->getColumn()}] Unexpected {$token->getType()} => {$token->getText()}");
	}

	protected function parseService() {
		$token = $this->getNextToken(Lexer::IDENTIFIER);
		$service = $token->getText();
		//TODO: Check if $service is valid identifier
		$this->getNextToken(Lexer::OPENING_BRACE);
		$this->proto['services'][$service] = array(
			'rpcs' => array(),
			'options' => array(),
		);
		while ($token = $this->getNextToken()) {
			if ($token->getType() === Lexer::CLOSING_BRACE) {
				return;
			}
			if ($token->getType() !== Lexer::KEYWORD) {
				throw new Exception("[{$token->getLine()} : {$token->getColumn()}] Expected " . Lexer::KEYWORD . " but found {$token->getType()} => {$token->getText()}");
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
					$this->proto['services'][$service]['options'][$option] = $value;
					break;
				default:
					throw new Exception("[{$token->getLine()} : {$token->getColumn()}] Unexpected {$token->getType()} => {$token->getText()}");
			}
		}
		throw new Exception(empty($token) ? 'Unexpected EOF' : "[{$token->getLine()} : {$token->getColumn()}] Unexpected {$token->getType()} => {$token->getText()}");
	}
	
	protected function parseRpc($service) {
		$token = $this->getNextToken(Lexer::IDENTIFIER);
		$rpc = $token->getText();
		//TODO: Check if $rpc is valid identifier
		$this->getNextToken(Lexer::OPENING_PARENTHESIS);
		$token = $this->getNextToken();
		if (empty($token)) {
			throw new Exception('Unexpected EOF');
		}
		$type = $this->getType($token->getText());
		$this->getNextToken(Lexer::CLOSING_PARENTHESIS);
		$token = $this->getNextToken(Lexer::KEYWORD);
		if ($token->getText() !== 'returns') {
			throw new Exception("[{$token->getLine()} : {$token->getColumn()}] Unexpected {$token->getType()} => {$token->getText()}");
		}
		$this->getNextToken(Lexer::OPENING_PARENTHESIS);
		$token = $this->getNextToken();
		if (empty($token)) {
			throw new Exception('Unexpected EOF');
		}
		$returns = $this->getType($token->getText());
		$this->getNextToken(Lexer::CLOSING_PARENTHESIS);
		$token = $this->getNextToken();
		if (empty($token)) {
			throw new Exception('Unexpected EOF');
		}
		if ($token->getType() !== Lexer::SEMICOLON && $token->getType() !== Lexer::OPENING_BRACE) {
			throw new Exception("[{$token->getLine()} : {$token->getColumn()}] Expected " . Lexer::SEMICOLON . " or " . Lexer::OPENING_BRACE . " but found {$token->getType()} => {$token->getText()}");
		}
		$options = array();
		if ($token->getType() === Lexer::OPENING_BRACE) {
			while ($token = $this->getNextToken()) {
				if ($token->getType() === Lexer::CLOSING_BRACE) {
					break;
				}
				if ($token->getType() !== Lexer::KEYWORD || $token->getText() !== 'option') {
					throw new Exception("[{$token->getLine()} : {$token->getColumn()}] Expected " . Lexer::CLOSING_BRACE . " or " . Lexer::KEYWORD . " => option but found {$token->getType()} => {$token->getText()}");
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
				throw new Exception('Unexpected EOF');
			}
		}
		$this->proto['services'][$service]['rpcs'][$rpc] = array(
			'type' => $type,
			'returns' => $returns,
			'options' => $options,
		);
	}

	protected function parseExtend() {
		$token = $this->getNextToken(Lexer::IDENTIFIER);
		$type = $this->getType($token->getText());
		$currentType = $this->currentType;
		$this->currentType = explode('.', $type);
		$this->getNextToken(Lexer::OPENING_BRACE);
		while ($token = $this->getNextToken()) {
			if ($token->getType() === Lexer::CLOSING_BRACE) {
				$this->currentType = $currentType;
				return;
			}
			if ($token->getType() !== Lexer::KEYWORD) {
				throw new Exception("[{$token->getLine()} : {$token->getColumn()}] Expected " . Lexer::KEYWORD . " but found {$token->getType()} => {$token->getText()}");
			}
			switch ($token->getText()) {
				case 'required':
				case 'optional':
				case 'repeated':
					$this->parseField($token->getText());
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
				default:
					throw new Exception("[{$token->getLine()} : {$token->getColumn()}] Unexpected {$token->getType()} => {$token->getText()}");
			}
		}
		throw new Exception(empty($token) ? 'Unexpected EOF' : "[{$token->getLine()} : {$token->getColumn()}] Unexpected {$token->getType()} => {$token->getText()}");
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

}
