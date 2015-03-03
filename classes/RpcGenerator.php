<?php

class RpcGenerator extends BaseGenerator {
	
	protected $package;
	protected $name;
	protected $rpc;

	public function __construct($package, $name, $rpc) {
		parent::__construct();
		$this->package = $package;
		$this->name = $name;
		$this->rpc = $rpc;
	}
	
	public function generatePHPClassSource() {
		$namespace = $this->getPHPNamespace($this->package);
		$source = <<<SOURCE
<?php

namespace {$namespace};

class {$this->name} extends \JSONRPC\Method {

	public function __construct() {
		parent::__construct();
	}
	
	public function authorize(\$client, \$params) {
		return TRUE;
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
