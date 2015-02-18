<?php

class RpcGenerator extends BaseGenerator {
	
	protected $name;
	protected $rpc;

	public function __construct($name, $rpc) {
		parent::__construct();
		$this->name = $name;
		$this->rpc = $rpc;
	}
	
	public function generatePHPClassSource() {
		$source = <<<SOURCE
<?php

class {$this->name} extends Method {

	public function __construct() {
		parent::__construct();
	}

	public function invoke(\$params) {
		\$result = new {$this->rpc['returns']}();
		return \$result;
	}

}

SOURCE;
		
		return $source;
	}
	
}
