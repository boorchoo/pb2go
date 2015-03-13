<?php

class PHPServiceClientGenerator extends PHPGenerator {

	public function __construct($fileName, $proto) {
		parent::__construct($fileName, $proto);
	}

	public function generate($path) {
		echo "Generating PHP service client files...\n";
		
		$source = $this->generateClientClassSource();
		$filepath = "{$path}/classes/JSONRPC/Client.php";
		$res = $this->output($filepath, $source);
		if ($res) {
			echo "{$filepath}\n";
		}
		
		foreach ($this->proto['services'] as $service) {
			$source = $this->generateServiceClientClassSource($service);
			$filepath = "{$path}/classes/" . str_replace('\\', '/', $this->getNamespace($service['package'])) . "/{$service['service']}Client.php";
			$res = $this->output($filepath, $source);
			if ($res) {
				echo "{$filepath}\n";
			} 
		}
	}
	
	public function generateClientClassSource() {
		$source = <<<'SOURCE'
<?php

/*** DO NOT MANUALLY EDIT THIS FILE ***/

namespace JSONRPC;

abstract class Client {

	protected $url;
	protected $requestHeaders;
	protected $id;

	protected function __construct($url) {
		$this->url = $url;
		$this->requestHeaders = array();
		$this->id = 0;
	}

	public function getURL() {
		return $this->url;
	}

	public function hasRequestHeader($header) {
		return isset($this->requestHeaders[$header]);
	}

	public function getRequestHeader($header) {
		return $this->hasRequestHeader($header) ? $this->requestHeaders[$header] : NULL;
	}

	public function setRequestHeader($header, $value = NULL) {
		if ($value === NULL) {
			$this->clearRequestHeader($header);
		} else {
			$this->requestHeaders[$header] = $value;
		}
	}

	public function clearRequestHeader($header) {
		if ($this->hasRequestHeader($header)) {
			unset($this->requestHeaders[$header]);
		}
	}

	public function getId() {
		return ++$this->id;
	}

	public function getLastId() {
		return $this->id;
	}

	protected function invoke($method, $params) {
		$request = new Request();
		$request->setMethod($method);
		$request->setParams($params);
		$request->setId($this->getId());
	
		$ch = curl_init();
		curl_setopt($ch, CURLOPT_URL, $this->url);
		curl_setopt($ch, CURLOPT_POST, 1);
		curl_setopt($ch, CURLOPT_POSTFIELDS, $request->serialize());
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
		$requestHeaders = array();
		foreach ($this->requestHeaders as $header => $value) {
			array_push($requestHeaders, "{$header}: {$value}");
		}
		curl_setopt($ch, CURLOPT_HTTPHEADER, $requestHeaders);
		$_response = curl_exec($ch);
		curl_close ($ch);
	
		$response = Response::parse($_response);
		if ($response->hasError()) {
			$error = $response->getError();
			throw new \Exception($error->getMessage(), $error->getCode(), NULL);
		}
		return $response->getResult();
	}

}

SOURCE;
		return $source;
	}

	public function generateServiceClientClassSource($service) {
		$namespace = $this->getNamespace($service['package']);
		$source = <<<SOURCE
<?php

/*** DO NOT MANUALLY EDIT THIS FILE ***/

namespace {$namespace};

class {$service['service']}Client extends \JSONRPC\Client {

	public function __construct(\$url) {
		parent::__construct(\$url);
	}

SOURCE;
		if (!empty($service['rpcs'])) {
			foreach ($service['rpcs'] as $rpcName => $rpc) {
				$returnsType = Registry::getType($rpc['returns']);
				$returns = str_replace('.', '_', $returnsType['name']);
				if ($service['package'] !== $returnsType['package']) {
					$returns = (empty($returnsType['package']) ? '' : '\\' . $this->getNamespace($returnsType['package'])) . '\\' . $returns;
				}
				$source .= <<<SOURCE

	public function {$rpcName}(\$params) {
		return {$returns}::fromStdClass(\$this->invoke('{$rpcName}', \$params->toStdClass()));
	}

SOURCE;
			}
		}
		$source .= <<<'SOURCE'

}

SOURCE;

		return $source;
	}

}
