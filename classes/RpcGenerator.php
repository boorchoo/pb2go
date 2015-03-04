<?php

class RpcGenerator extends BaseGenerator {
	
	protected $package;
	protected $serviceName;
	protected $name;
	protected $rpc;

	public function __construct($package, $serviceName, $name, $rpc) {
		parent::__construct();
		$this->package = $package;
		$this->serviceName = $serviceName;
		$this->name = $name;
		$this->rpc = $rpc;
	}
	
	public function generatePHPClassSource() {
		$namespace = $this->getPHPNamespace($this->package);
		$source = <<<SOURCE
<?php

namespace {$namespace};

class {$this->serviceName}_{$this->name} extends \JSONRPC\Method {

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
