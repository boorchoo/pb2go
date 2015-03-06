<?php

class Token {

	public $type;
	public $text;

	public function __construct($type, $text) {
		$this->type = $type;
		$this->text = $text;
	}

	public function getType() {
		return $this->type;
	}

	public function getText() {
		return $this->text;
	}

}
