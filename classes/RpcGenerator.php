<?php

class RpcGenerator extends BaseGenerator {
	
	protected $service;
	protected $name;
	protected $rpc;

	public function __construct($service, $name, $rpc) {
		parent::__construct();
		$this->service = $service;
		$this->name = $name;
		$this->rpc = $rpc;
	}
	
	public function generatePHPClassSource() {
		$namespace = $this->getPHPNamespace($this->service['package']);
		$returnsType = Registry::getType($this->rpc['returns']);
		$returns = str_replace('.', '_', $returnsType['name']);
		if ($this->service['package'] !== $returnsType['package']) {
			$returns = (empty($returnsType['package']) ? '' : '\\' . $this->getPHPNamespace($returnsType['package'])) . '\\' . $returns;
		}
		$source = <<<SOURCE
<?php

namespace {$namespace};

class {$this->service['service']}_{$this->name} extends \JSONRPC\Method {

	public function __construct(\$config, \$client) {
		parent::__construct(\$config, \$client);
	}
	
	public function authorize(\$params) {
		return TRUE;
	}

	public function invoke(\$params) {
		\$result = new {$returns}();
		return \$result;
	}

}

SOURCE;
		
		return $source;
	}
	
}
