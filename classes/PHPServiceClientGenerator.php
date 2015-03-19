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
		
		foreach ($this->proto['services'] as $service) {
			$source = $this->generateClassSource($service);
			$filepath = "{$path}/classes/" . (empty($service['package']) ? '' : (str_replace('\\', '/', $this->getNamespace($service['package'])) . '/')) . "{$service['service']}Client.php";
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
	protected $requests;
	protected $id;

	protected function __construct($url) {
		$this->url = $url;
		$this->requestHeaders = array();
		$this->requests = array();
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

	protected function addRequest($method, $params, $responseClassName) {
		if (!empty($params) && !$params->isInitialized()) {
			throw new UninitializedMessageException();
		}
		$request = new Request();
		$request->setMethod($method);
		$request->setParams(empty($params) ? NULL : $params->toStdClass());
		$request->setId($this->getId());
		$this->requests[$this->getLastId()] = array(
			'request' => $request,
			'responseClassName' => $responseClassName,
		);
		return $this->getLastId();
	}

	protected function getRequestResponseClassName($id) {
		return isset($this->requests[$id]['responseClassName']) ? $this->requests[$id]['responseClassName'] : NULL; 
	}

	public function clearRequests() {
		$this->requests = array();
	}

	protected function getId() {
		return ++$this->id;
	}

	public function getLastId() {
		return $this->id;
	}

	protected function invoke($method = NULL, $params = NULL, $responseClassName = NULL) {
		if (empty($method)) {
			$requests = array();
			foreach ($this->requests as $request) {
				array_push($requests, $request['request']->toStdClass());
			}
			$data = json_encode($requests);
		} else {
			if (!empty($params) && !$params->isInitialized()) {
				throw new UninitializedMessageException();
			}
			$request = new Request();
			$request->setMethod($method);
			$request->setParams(empty($params) ? NULL : $params->toStdClass());
			$request->setId($this->getId());
			$data = json_encode($request->toStdClass());
		}

		$ch = curl_init();
		curl_setopt($ch, CURLOPT_URL, $this->url);
		curl_setopt($ch, CURLOPT_POST, 1);
		curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
		$requestHeaders = array();
		foreach ($this->requestHeaders as $header => $value) {
			array_push($requestHeaders, "{$header}: {$value}");
		}
		curl_setopt($ch, CURLOPT_HTTPHEADER, $requestHeaders);
		$output = json_decode(curl_exec($ch));
		curl_close ($ch);

		if (is_array($output)) {
			$results = array();
			foreach ($output as $object) {
				$response = Response::fromStdClass($object);
				if ($response->hasError()) {
					$error = $response->getError();
					continue;
				}
				$responseClassName = $this->getRequestResponseClassName($response->getId());
				$results[$response->getId()] = empty($responseClassName) ? $response->getResult() : $responseClassName::fromStdClass($response->getResult());
			}
			$this->clearRequests();
			return $results;
		}

		$response = Response::fromStdClass($output);
		if ($response->hasError()) {
			$error = $response->getError();
			throw new \Exception($error->getMessage(), $error->getCode(), NULL);
		}
		return empty($responseClassName) ? $response->getResult() : $responseClassName::fromStdClass($response->getResult());
	}

	public function invokeAll() {
		return $this->invoke();
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

SOURCE;
		if (!empty($service['rpcs'])) {
			foreach ($service['rpcs'] as $rpcName => $rpc) {
				$returnsType = Registry::getType($rpc['returns']);
				$returns = (empty($returnsType['package']) ? '' : '\\' . $this->getNamespace($returnsType['package'])) . '\\' . str_replace('.', '_', $returnsType['name']);
				$source .= <<<SOURCE

	public function {$rpcName}(\$params) {
		return \$this->invoke('{$rpcName}', \$params, '{$returns}');
	}

	public function add{$rpcName}Request(\$params) {
		return \$this->addRequest('{$rpcName}', \$params, '{$returns}');
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
