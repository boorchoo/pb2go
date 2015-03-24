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
		$request = new Request();
		$request->setMethod($method);
		$request->setParams($params);
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
	protected $request;
	protected $result;
	protected $error;

	protected function __construct($serviceClient) {
		$this->serviceClient = $serviceClient;
		$this->request = array();
		$this->result = array();
		$this->error = array();
	}

	public function getServiceClient() {
		return $this->serviceClient;
	}

	protected function addRequest($method, $params, $resultTypeName) {
		$request = new Request();
		$request->setMethod($method);
		$request->setParams($params);
		$request->setId($this->serviceClient->getId());
		$this->request[$this->serviceClient->getLastId()] = array(
			'request' => $request,
			'resultTypeName' => $resultTypeName,
		);
		return $this->serviceClient->getLastId();
	}

	protected function clearRequest() {
		$this->request = array();
	}

	public function getResultCount() {
		return count($this->result);
	}

	public function hasResult($id) {
		return isset($this->result[$id]);
	}

	public function getResult($id) {
		return isset($this->result[$id]) ? $this->result[$id] : NULL;
	}

	public function getResultArray() {
		return $this->result;
	}

	protected function clearResult() {
		$this->result = array();
	}

	public function getErrorCount() {
		return count($this->error);
	}

	public function hasError($id) {
		return isset($this->error[$id]);
	}

	public function getError($id) {
		return isset($this->error[$id]) ? $this->error[$id] : NULL;
	}

	public function getErrorArray() {
		return $this->error;
	}

	protected function clearError() {
		$this->error = array();
	}

	public function send() {
		$requests = array();
		foreach ($this->request as $request) {
			array_push($requests, $request['request']->toStdClass());
		}
		$objects = $this->serviceClient->send($requests);
		$this->clearResult();
		$this->clearError();
		foreach ($objects as $object) {
			$response = Response::fromStdClass($object);
			if ($response->hasError()) {
				$this->error[$response->getId()] = $response->getError();
				continue;
			}
			$resultTypeName = $this->request[$response->getId()]['resultTypeName'];
			if (empty($resultTypeName)) {
				$this->result[$response->getId()] = NULL;
				continue;
			}
			if ($resultTypeName === 'float') {
				$this->result[$response->getId()] = (float) $response->getResult();
				continue;
			}
			if ($resultTypeName === 'int') {
				$this->result[$response->getId()] = (int) $response->getResult();
				continue;
			}
			if ($resultTypeName === 'bool') {
				$this->result[$response->getId()] = (bool) $response->getResult();
				continue;
			}
			if ($resultTypeName === 'string') {
				$this->result[$response->getId()] = (string) $response->getResult();
				continue;
			}
			$this->result[$response->getId()] = $resultTypeName::fromStdClass($response->getResult());
		}
		$this->clearRequest();
		return $this;
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
				$check = '';
				if (empty($rpc['type'])) {
					$arg = '';
					$params = 'NULL';
				} else {
					$typeType = Registry::getType($rpc['type']);
					if ($typeType['type'] === Registry::PRIMITIVE) {
						$type = $this->getType($typeType['name']);
						$arg = "\$params";
						$params = "({$type['type']}) \$params";
					} elseif ($typeType['type'] === Registry::ENUM) {
						$arg = "\$params";
						$params = "(int) \$params";
					} else {
						$type = str_replace('.', '_', $typeType['name']);
						if ($service['package'] !== $typeType['package']) {
							$type = (empty($typeType['package']) ? '' : '\\' . $this->getNamespace($typeType['package'])) . '\\' . $type;
						}
						$check = <<<SOURCE

		if (!\$params->isInitialized()) {
			throw new UninitializedMessageException();
		}
SOURCE;
						$arg = "{$type} \$params";
						$params = '$params->toStdClass()';
					}
				}
				if (empty($rpc['returns'])) {
					$returnStart = '';
					$returnEnd = ";\n\t\treturn NULL;";
				} else {
					$returnsType = Registry::getType($rpc['returns']);
					if ($returnsType['type'] === Registry::PRIMITIVE) {
						$type = $this->getType($returnsType['name']);
						$returnStart = "return ({$type['type']}) ";
						$returnEnd = ';';
					} elseif ($returnsType['type'] === Registry::PRIMITIVE) {
						$returnStart = 'return (int) ';
						$returnEnd = ';';
					} else {
						$returns = str_replace('.', '_', $returnsType['name']);
						if ($service['package'] !== $returnsType['package']) {
							$returns = (empty($returnsType['package']) ? '' : '\\' . $this->getNamespace($returnsType['package'])) . '\\' . $returns;
						}
						$returnStart = "return {$returns}::fromStdClass(";
						$returnEnd = ');';
					}
				}
				$source .= <<<SOURCE

	public function {$rpcName}({$arg}) {{$check}
		{$returnStart}\$this->invoke('{$rpcName}', {$params}){$returnEnd}
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
				$check = '';
				if (empty($rpc['type'])) {
					$arg = '';
					$params = 'NULL';
				} else {
					$typeType = Registry::getType($rpc['type']);
					if ($typeType['type'] === Registry::PRIMITIVE) {
						$type = $this->getType($typeType['name']);
						$arg = "\$params";
						$params = "({$type['type']}) \$params";
					} elseif ($typeType['type'] === Registry::ENUM) {
						$arg = "\$params";
						$params = "(int) \$params";
					} else {
						$type = str_replace('.', '_', $typeType['name']);
						if ($service['package'] !== $typeType['package']) {
							$type = (empty($typeType['package']) ? '' : '\\' . $this->getNamespace($typeType['package'])) . '\\' . $type;
						}
						$check = <<<SOURCE
				
		if (!\$params->isInitialized()) {
			throw new UninitializedMessageException();
		}
SOURCE;
						$arg = "{$type} \$params";
						$params = '$params->toStdClass()';
					}
				}
				if (empty($rpc['returns'])) {
					$returns = "NULL";
				} else {
					$returnsType = Registry::getType($rpc['returns']);
					if ($returnsType['type'] === Registry::PRIMITIVE) {
						$type = $this->getType($returnsType['name']);
						$returns = "{$type['type']}";
						$returnEnd = ';';
					} elseif ($returnsType['type'] === Registry::PRIMITIVE) {
						$returns = "int";
					} else {
						$returns = (empty($returnsType['package']) ? '' : '\\' . $this->getNamespace($returnsType['package'])) . '\\' . str_replace('.', '_', $returnsType['name']);
					}
					$returns = "'{$returns}'";
				}
				$source .= <<<SOURCE

	public function {$rpcName}({$arg}) {{$check}
		return \$this->addRequest('{$rpcName}', {$params}, {$returns});
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
