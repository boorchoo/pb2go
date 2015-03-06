<?php

class Token {

	protected $type;
	protected $text;
	protected $line;
	protected $column;

	public function __construct($type, $text) {
		$this->type = $type;
		$this->text = $text;
		$this->line = NULL;
		$this->column = NULL;
	}

	public function getType() {
		return $this->type;
	}

	public function getText() {
		return $this->text;
	}
	
	public function getLine() {
		return $this->line;
	}
	
	public function setLine($line) {
		$this->line = $line;
	}
	
	public function getColumn() {
		return $this->column;
	}
	
	public function setColumn($column) {
		$this->column = $column;
	}

}
