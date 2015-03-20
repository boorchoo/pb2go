<?php

class PHPServiceClientGenerator extends PHPGenerator {

	public function __construct($fileName, $proto) {
		parent::__construct($fileName, $proto);
	}

	public function generate($path) {
		echo "Generating PHP service client files...\n";
		
		$source = $this->generateServiceClientClassSource();
		$filepath = "{$path}/classes/JSONRPC/ServiceClient.php";
		$res = $this->output($filepath, $source);
		if ($res) {
			echo "{$filepath}\n";
		}
		
		$source = $this->generateServiceClientBatchClassSource();
		$filepath = "{$path}/classes/JSONRPC/ServiceClient_Batch.php";
		$res = $this->output($filepath, $source);
		if ($res) {
			echo "{$filepath}\n";
		}
		
		foreach ($this->proto['services'] as $service) {
			$source = $this->generateClassSource($service);
			$filepath = "{$path}/classes/" . (empty($service['package']) ? '' : (str_replace('\\', '/', $this->getNamespace($service['package'])) . '/')) . "{$service['service']}Client.php";
			$res = $this->output($filepath, $source);
			if ($res) {
				echo "{$filepath}\n";
			}
			
			$source = $this->generateBatchClassSource($service);
			$filepath = "{$path}/classes/" . (empty($service['package']) ? '' : (str_replace('\\', '/', $this->getNamespace($service['package'])) . '/')) . "{$service['service']}Client_Batch.php";
			$res = $this->output($filepath, $source);
			if ($res) {
				echo "{$filepath}\n";
			}
		}
	}
	
	public function generateServiceClientClassSource() {
		$source = <<<'SOURCE'
<?php

/*** DO NOT MANUALLY EDIT THIS FILE ***/

namespace JSONRPC;

abstract class ServiceClient {

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

	public function send($request) {
		$ch = curl_init();
		curl_setopt($ch, CURLOPT_URL, $this->url);
		curl_setopt($ch, CURLOPT_POST, 1);
		curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($request));
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
		$requestHeaders = array();
		foreach ($this->requestHeaders as $header => $value) {
			array_push($requestHeaders, "{$header}: {$value}");
		}
		curl_setopt($ch, CURLOPT_HTTPHEADER, $requestHeaders);
		$response = json_decode(curl_exec($ch));
		curl_close($ch);
		return $response;
	}

	protected function invoke($method, $params) {
		if (empty($params)) {
			throw new InvalidParams();
		}
		if (!$params->isInitialized()) {
			throw new UninitializedMessageException();
		}
		$request = new Request();
		$request->setMethod($method);
		$request->setParams($params->toStdClass());
		$request->setId($this->getId());
		$response = Response::fromStdClass($this->send($request->toStdClass()));
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
	
	public function generateServiceClientBatchClassSource() {
		$source = <<<'SOURCE'
<?php

/*** DO NOT MANUALLY EDIT THIS FILE ***/

namespace JSONRPC;

class ServiceClient_Batch {

	protected $serviceClient;
	protected $requests;

	protected function __construct($serviceClient) {
		$this->serviceClient = $serviceClient;
		$this->requests = array();
	}

	public function getServiceClient() {
		return $this->serviceClient;
	}

	protected function add($method, $params, $responseClassName) {
		if (empty($params)) {
			throw new InvalidParams();
		}
		if (!$params->isInitialized()) {
			throw new UninitializedMessageException();
		}
		$request = new Request();
		$request->setMethod($method);
		$request->setParams($params->toStdClass());
		$request->setId($this->serviceClient->getId());
		$this->requests[$this->serviceClient->getLastId()] = array(
			'request' => $request,
			'responseClassName' => $responseClassName,
			'result' => NULL,
			'error' => NULL,
		);
		return $this->serviceClient->getLastId();
	}

	public function hasResult($id) {
		return isset($this->requests[$id]['result']);
	}

	public function getResult($id) {
		return isset($this->requests[$id]['result']) ? $this->requests[$id]['result'] : NULL;
	}

	public function hasError($id) {
		return isset($this->requests[$id]['error']);
	}

	public function getError($id) {
		return isset($this->requests[$id]['error']) ? $this->requests[$id]['error'] : NULL;
	}

	public function send() {
		$requests = array();
		foreach ($this->requests as $request) {
			array_push($requests, $request['request']->toStdClass());
		}
		$objects = $this->serviceClient->send($requests);
		foreach ($objects as $object) {
			$response = Response::fromStdClass($object);
			if ($response->hasError()) {
				$this->requests[$response->getId()]['error'] = $response->getError();
				continue;
			}
			$responseClassName = $this->requests[$response->getId()]['responseClassName'];
			$this->requests[$response->getId()]['result'] = $responseClassName::fromStdClass($response->getResult());
		}
	}

}

SOURCE;
		return $source;
	}

	public function generateClassSource($service) {
		$namespace = $this->getNamespace($service['package']);
		$source = <<<SOURCE
<?php

/*** DO NOT MANUALLY EDIT THIS FILE ***/


SOURCE;
		if (!empty($namespace)) {
			$source .= <<<SOURCE
namespace {$namespace};


SOURCE;
		}
		$source .= <<<SOURCE
class {$service['service']}Client extends \JSONRPC\ServiceClient {

	public function __construct(\$url) {
		parent::__construct(\$url);
	}

	public function newBatch() {
		return new {$service['service']}Client_Batch(\$this);
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
		return {$returns}::fromStdClass(\$this->invoke('{$rpcName}', \$params));
	}

SOURCE;
			}
		}
		$source .= <<<'SOURCE'

}

SOURCE;
		return $source;
	}
	
	public function generateBatchClassSource($service) {
		$namespace = $this->getNamespace($service['package']);
		$source = <<<SOURCE
<?php

/*** DO NOT MANUALLY EDIT THIS FILE ***/


SOURCE;
		if (!empty($namespace)) {
			$source .= <<<SOURCE
namespace {$namespace};


SOURCE;
		}
		$source .= <<<SOURCE
class {$service['service']}Client_Batch extends \JSONRPC\ServiceClient_Batch {

	public function __construct(\$serviceClient) {
		parent::__construct(\$serviceClient);
	}

SOURCE;
		if (!empty($service['rpcs'])) {
			foreach ($service['rpcs'] as $rpcName => $rpc) {
				$returnsType = Registry::getType($rpc['returns']);
				$returns = (empty($returnsType['package']) ? '' : '\\' . $this->getNamespace($returnsType['package'])) . '\\' . str_replace('.', '_', $returnsType['name']);
				$source .= <<<SOURCE

	public function {$rpcName}(\$params) {
		return \$this->add('{$rpcName}', \$params, '{$returns}');
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
