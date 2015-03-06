<?php

class Lexer {
	
	const WHITESPACE = "WHITESPACE";
	const OPENING_BRACE = "OPENING_BRACE";
	const CLOSING_BRACE = "CLOSING_BRACE";
	const OPENING_BRACKET = "OPENING_BRACKET";
	const CLOSING_BRACKET = "CLOSING_BRACKET";
	const OPENING_PARENTHESIS = "OPENING_PARENTHESIS";
	const CLOSING_PARENTHESIS = "CLOSING_PARENTHESIS";
	const EQUALS = "EQUALS";
	const COMMA = "COMMA";
	const SEMICOLON = "SEMICOLON";
	const NUMBER = "NUMBER";
	const BOOLEAN = "BOOLEAN";
	const DOUBLE_QUOTED_STRING = "DOUBLE_QUOTED_STRING";
	const SINGLE_QUOTED_STRING = "SINGLE_QUOTED_STRING";
	const SINGLE_LINE_COMMENT = "SINGLE_LINE_COMMENT";
	const MULTIPLE_LINE_COMMENT = "MULTIPLE_LINE_COMMENT";
	const KEYWORD = "KEYWORD";
	const IDENTIFIER = "IDENTIFIER";
	
	protected $patterns;
	protected $text;
	protected $offset;
	
	public function __construct($text) {
		$this->patterns = array(
			self::WHITESPACE => "/(\s+)/",
			self::OPENING_BRACE => "/(\{)/",
			self::CLOSING_BRACE => "/(\})/",
			self::OPENING_BRACKET => "/(\[)/",
			self::CLOSING_BRACKET => "/(\])/",
			self::OPENING_PARENTHESIS => "/(\()/",
			self::CLOSING_PARENTHESIS => "/(\))/",
			self::EQUALS => "/(\=)/",
			self::COMMA => "/(\,)/",
			self::SEMICOLON => "/(\;)/",
			self::NUMBER => "/([0-9]+(\.[0-9]*)?)/",
			self::BOOLEAN => "/(true|false)/",
			self::DOUBLE_QUOTED_STRING => "/(\"([^\"\\\\]|\\\\.)*\")/",
			self::SINGLE_QUOTED_STRING => "/(\'([^\'\\\\]|\\\\.)*\')/",
			self::SINGLE_LINE_COMMENT => "/(\/\/.*)/",
			self::MULTIPLE_LINE_COMMENT => "/(\/\*.*\*\/)/s",
			self::KEYWORD => "/(import|public|package|message|enum|required|optional|repeated|double|float|int32|int64|uint32|uint64|sint32|sint64|fixed32|fixed64|sfixed32|sfixed64|bool|string|bytes|option|default|packed|deprecated|service|rpc|returns|group|extensions|to|max|extend|oneof)/",
			self::IDENTIFIER => "/([^\s\{\}\[\]\(\)\=\,\;]+)/",
		);
		$this->text = $text;
		$this->offset = 0; 
	}
	
	public function getNextToken() {
		foreach ($this->patterns as $type => $pattern) {
			if (preg_match($pattern, $this->text, $matches, PREG_OFFSET_CAPTURE, $this->offset)) {
				$offset = $matches[0][1];
				if ($offset === $this->offset) {
					$text = $matches[1][0];
					$this->offset += strlen($text);
					return new Token($type, $text);
				}
			}
		}
		return NULL;
	}
	
	public function tokenize() {
		$tokens = array();
		while ($token = $this->getNextToken()) {
			switch ($token->getType()) {
				case self::WHITESPACE:
				case self::SINGLE_LINE_COMMENT:
				case self::MULTIPLE_LINE_COMMENT:
					break;
				default:
					array_push($tokens, $token);
			}
		}
		return $tokens;
	}
	
}
