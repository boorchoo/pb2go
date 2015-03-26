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
		
		$source = $this->generateServiceClientErrorClassSource();
		$filepath = "{$path}/classes/JSONRPC/ServiceClientError.php";
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
		$_response = curl_exec($ch);
		if ($_response === FALSE) {
			throw new ServiceClientError(new Error('cURL error: ' . curl_error($ch), curl_errno($ch)));
		}
		$info = curl_getinfo($ch);
		if ($info['http_code'] !== 200) {
			throw new ServiceClientError(new Error('HTTP status code error', $info['http_code']));
		}
		if (empty($_response)) {
			return NULL;
		}
		$response = json_decode($_response);
		if ($response === NULL) {
			throw new ParseError(new Error(json_last_error_msg(), json_last_error()));
		}
		curl_close($ch);
		return $response;
	}

	protected function invoke($method, $params, $paramsRule, $paramsTypeName, $resultRule, $resultTypeName) {
		if (empty($paramsTypeName)) {
			if ($params !== NULL) {
				throw new InvalidParamsError();
			}
			$_params = NULL;
		} elseif ($paramsTypeName === 'float') {
			$_params = (float) $params;
		} elseif ($paramsTypeName === 'int') {
			$_params = (int) $params;
		} elseif ($paramsTypeName === 'bool') {
			$_params = (bool) $params;
		} elseif ($paramsTypeName === 'string') {
			$_params = (string) $params;
		} else {
			if (empty($params)) {
				throw new InvalidParamsError();
			}
			if (!$params->isInitialized()) {
				throw new InvalidParamsError(new UninitializedMessageError());
			}
			$_params = $params->toStdClass();
		}
		$request = new Request();
		$request->setMethod($method);
		$request->setParams($_params);
		$request->setId($this->getId());
		$object = $this->send($request->toStdClass());
		if ($object === NULL) {
			return NULL;
		}
		if (!is_object($object)) {
			throw new InvalidResultError();
		}
		$response = Response::fromStdClass($object);
		if ($response->hasError()) {
			throw $response->getError();
		}
		$result = $response->getResult();
		if (empty($resultTypeName)) {
			if ($result !== NULL) {
				throw new InvalidResultError();
			}
			return NULL;
		}
		if ($resultTypeName === 'float') {
			return (float) $result;
		}
		if ($resultTypeName === 'int') {
			return (int) $result;
		}
		if ($resultTypeName === 'bool') {
			return (bool) $result;
		}
		if ($resultTypeName === 'string') {
			return (string) $result;
		}
		if (empty($result)) {
			throw new InvalidResultError();
		}
		$_result = $resultTypeName::fromStdClass($result);
		if (!$_result->isInitialized()) {
			throw new InvalidResultError(new UninitializedMessageError());
		}
		return $_result;
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

	protected function addRequest($method, $params, $paramsRule, $paramsTypeName, $resultRule, $resultTypeName) {
		if (empty($paramsTypeName)) {
			if ($params !== NULL) {
				throw new InvalidParamsError();
			}
			$_params = NULL;
		} elseif ($paramsTypeName === 'float') {
			$_params = (float) $params;
		} elseif ($paramsTypeName === 'int') {
			$_params = (int) $params;
		} elseif ($paramsTypeName === 'bool') {
			$_params = (bool) $params;
		} elseif ($paramsTypeName === 'string') {
			$_params = (string) $params;
		} else {
			if (empty($params)) {
				throw new InvalidParamsError();
			}
			if (!$params->isInitialized()) {
				throw new InvalidParamsError(new UninitializedMessageError());
			}
			$_params = $params->toStdClass();
		}
		$request = new Request();
		$request->setMethod($method);
		$request->setParams($_params);
		$request->setId($this->serviceClient->getId());
		$this->request[$this->serviceClient->getLastId()] = array(
			'request' => $request,
			'resultRule' => $resultRule,
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

	protected function setResult($id, $result) {
		$resultRule = $this->request[$id]['resultRule'];
		$resultTypeName = $this->request[$id]['resultTypeName'];
		if (empty($resultTypeName)) {
			if ($result !== NULL) {
				$this->setError($id, new InvalidResultError());
				return;
			}
			$this->result[$id] = NULL;
			return;
		}
		if ($resultTypeName === 'float') {
			$this->result[$id] = (float) $result;
			return;
		}
		if ($resultTypeName === 'int') {
			$this->result[$id] = (int) $result;
			return;
		}
		if ($resultTypeName === 'bool') {
			$this->result[$id] = (bool) $result;
			return;
		}
		if ($resultTypeName === 'string') {
			$this->result[$id] = (string) $result;
			return;
		}
		if (empty($result)) {
			$this->setError($id, new InvalidResultError());
			return;
		}
		$_result = $resultTypeName::fromStdClass($result);
		if (!$_result->isInitialized()) {
			$this->setError($id, new InvalidResultError(new UninitializedMessageError()));
			return;
		}
		$this->result[$id] = $_result;
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

	protected function setError($id, $error) {
		$this->error[$id] = $error;
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
		if ($objects === NULL) {
			$this->clearRequest();
			return;
		}
		if (!is_array($objects)) {
			if (!is_object($objects)) {
				throw new InvalidResultError();
			}
			$response = Response::fromStdClass($objects);
			if ($response->hasError()) {
				throw $response->getError();
			}
			throw new InvalidResultError();
		}
		foreach ($objects as $object) {
			if (!is_object($object)) {
				$this->setError($response->getId(), new InvalidResultError());
				continue;
			}
			$response = Response::fromStdClass($object);
			if ($response->hasError()) {
				$this->setError($response->getId(), $response->getError());
				continue;
			}
			$this->setResult($response->getId(), $response->getResult());
		}
		$this->clearRequest();
	}

}

SOURCE;
		return $source;
	}
	
	public function generateServiceClientErrorClassSource() {
		$source = <<<'SOURCE'
<?php

/*** DO NOT MANUALLY EDIT THIS FILE ***/

namespace JSONRPC;

class ServiceClientError extends Error {

	const MESSAGE = 'Service client error';
	const CODE = -32003;

	public function __construct($data = NULL) {
		parent::__construct(self::MESSAGE, self::CODE, $data);
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
				if (empty($rpc['type'])) {
					$arg = '';
					$params = 'NULL';
					$type = 'NULL';
				} else {
					$typeType = Registry::getType($rpc['type']);
					if ($typeType['type'] === Registry::PRIMITIVE) {
						$_type = $this->getType($typeType['name']);
						$arg = '$params';
						$params = '$params';
						$type = $_type['type'];
					} elseif ($typeType['type'] === Registry::ENUM) {
						$arg = '$params';
						$params = '$params';
						$type = 'int';
					} else {
						$type = str_replace('.', '_', $typeType['name']);
						if ($service['package'] !== $typeType['package']) {
							$type = (empty($typeType['package']) ? '' : '\\' . $this->getNamespace($typeType['package'])) . '\\' . $type;
						}
						$arg = "{$type} \$params";
						$params = '$params';
					}
					if ($rpc['rule'] === 'optional') {
						$arg = "{$arg} = NULL";
					} elseif ($rpc['rule'] === 'repeated') {
						$arg = '$params';
					}
					$type = "'{$type}'";
				}
				if (empty($rpc['returns']['type'])) {
					$returns = 'NULL';
				} else {
					$returnsType = Registry::getType($rpc['returns']['type']);
					if ($returnsType['type'] === Registry::PRIMITIVE) {
						$_type = $this->getType($returnsType['name']);
						$returns = $_type['type'];
					} elseif ($returnsType['type'] === Registry::PRIMITIVE) {
						$returns = 'int';
					} else {
						$returns = str_replace('.', '_', $returnsType['name']);
						if ($service['package'] !== $returnsType['package']) {
							$returns = (empty($returnsType['package']) ? '' : '\\' . $this->getNamespace($returnsType['package'])) . '\\' . $returns;
						}
					}
					$returns = "'{$returns}'"; 
				}
				$source .= <<<SOURCE

	public function {$rpcName}({$arg}) {
		return \$this->invoke('{$rpcName}', {$params}, '{$rpc['rule']}', {$type}, '{$rpc['returns']['rule']}', {$returns});
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
				if (empty($rpc['type'])) {
					$arg = '';
					$params = 'NULL';
					$type = 'NULL';
				} else {
					$typeType = Registry::getType($rpc['type']);
					if ($typeType['type'] === Registry::PRIMITIVE) {
						$_type = $this->getType($typeType['name']);
						$arg = '$params';
						$params = '$params';
						$type = $_type['type'];
					} elseif ($typeType['type'] === Registry::ENUM) {
						$arg = '$params';
						$params = '$params';
						$type = 'int';
					} else {
						$type = str_replace('.', '_', $typeType['name']);
						if ($service['package'] !== $typeType['package']) {
							$type = (empty($typeType['package']) ? '' : '\\' . $this->getNamespace($typeType['package'])) . '\\' . $type;
						}
						$arg = "{$type} \$params";
						$params = '$params';
					}
					if ($rpc['rule'] === 'optional') {
						$arg = "{$arg} = NULL";
					} elseif ($rpc['rule'] === 'repeated') {
						$arg = '$params';
					}
					$type = "'$type'";
				}
				if (empty($rpc['returns']['type'])) {
					$returns = "NULL";
				} else {
					$returnsType = Registry::getType($rpc['returns']['type']);
					if ($returnsType['type'] === Registry::PRIMITIVE) {
						$_type = $this->getType($returnsType['name']);
						$returns = "{$_type['type']}";
						$returnEnd = ';';
					} elseif ($returnsType['type'] === Registry::PRIMITIVE) {
						$returns = "int";
					} else {
						$returns = (empty($returnsType['package']) ? '' : '\\' . $this->getNamespace($returnsType['package'])) . '\\' . str_replace('.', '_', $returnsType['name']);
					}
					$returns = "'{$returns}'";
				}
				$source .= <<<SOURCE

	public function {$rpcName}({$arg}) {
		return \$this->addRequest('{$rpcName}', {$params}, '{$rpc['rule']}', {$type}, '{$rpc['returns']['rule']}', {$returns});
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
