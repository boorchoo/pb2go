<?php

class EnumGenerator extends BaseGenerator {
	
	protected $type;
	protected $enum;
	
	public function __construct($type, $enum) {
		parent::__construct();
		$this->type = $type;
		$this->enum = $enum;
	}
	
	public function generatePHPClassSource() {
		$class = str_replace('.', '_', $this->type);
		$source = <<<SOURCE
<?php

/*** DO NOT MANUALLY EDIT THIS FILE ***/

abstract class {$class} {


SOURCE;
		foreach ($this->enum as $name => $value) {
			$source .= "	const {$name} = {$value};\n";
		} 
		$source .= <<<SOURCE

}

SOURCE;

		return $source;
	}
	
	public function generateJavaScriptClassSource() {
		$source = <<<SOURCE

{$this->type} = {

SOURCE;
		foreach ($this->enum as $name => $value) {
			$source .= "	{$name}: {$value},\n";
		}
		$source = substr($source, 0, strlen($source) - 2);
		$source .= <<<SOURCE

};

SOURCE;

		return $source;
	}
	
}