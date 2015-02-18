<?php

class Tokenizer {
	
	const BLANK = '/[ \t\n\r]+/';
	const COMMENT = '/\/\/[^\n\r]*[\n\r]/';
	const TOKEN = '/[a-zA-Z_][a-zA-Z0-9_\.]*|{|}|;|=|\[|\]|\(|\)|[0-9]+/';
	
	protected $text;
	protected $offset = 0;
	protected $line = 1;
	protected $column = 1;
	protected $lastLine = 0;
	protected $lastColumn = 0;

	public function __construct($text) {
		$this->text = $text;
	}
	
	public function getLine() {
		return $this->line;
	}
	
	public function getColumn() {
		return $this->column;
	}
	
	public function getLastLine() {
		return $this->lastLine;
	}
	
	public function getLastColumn() {
		return $this->lastColumn;
	}
	
	public function next($eof = FALSE) {
		while ($this->_next(self::BLANK) || $this->_next(self::COMMENT)) {
		}
		$value = $this->_next(self::TOKEN);
		if ($value === FALSE) {
			if ($this->offset == strlen($this->text) && $eof) {
				return FALSE;
			}
			if ($this->offset == strlen($this->text)) {
				throw new Exception("Unexpected end of file at line {$this->getLine()} column {$this->getColumn()}");
			}
			$limit = min(array_filter(array(
				strlen($this->text),
				strpos($this->text, " ", $this->offset),
				strpos($this->text, "\t", $this->offset),
				strpos($this->text, "\n", $this->offset),
				strpos($this->text, "\r", $this->offset),
			)));
			throw new Exception("Unexpected \"" . substr($this->text, $this->offset, $limit - $this->offset) . "\" at line {$this->getLine()} column {$this->getColumn()}");
		}
		return $value;
	}
	
	public function assertNext($_value) {
		$value = $this->next();
		if ($value !== $_value) {
			throw new Exception("Expected \"{$_value}\" but found \"{$value}\" at line {$this->getLine()} column {$this->getColumn()}");
		}
		return $value;
	}
	
	protected function _next($pattern) {
		$matches = NULL;
		if (preg_match($pattern, $this->text, $matches, PREG_OFFSET_CAPTURE, $this->offset)) {
			$offset = $matches[0][1];
			if ($offset === $this->offset) {
				$value = $matches[0][0];
				$this->offset += strlen($value);
				$newLines = substr_count($value, "\n");
				$this->lastLine = $this->line;
				$this->lastColumn = $this->column;
				if ($newLines > 0) {
					$this->line += $newLines;
					$this->column = strlen($value) - strrpos($value, "\n");
				} else {
					$this->column += strlen($value);
				}
				return $value;
			}
		}
		return FALSE;
	}

}
