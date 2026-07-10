<?php
/**
 * CI3 AI - Pacote de IA para CodeIgniter 3.x
 *
 * @package CiAi
 * @author  Mário Lucas <mariolucasdev@gmail.com>
 * @license MIT
 */
namespace CiAi\Mcp;

use CiAi\Exceptions\AiException;
use CiAi\Support\Http;

/**
 * Cliente MCP (Model Context Protocol) via transporte HTTP (JSON-RPC 2.0).
 *
 * Uso:
 *   $mcp = new McpClient('https://servidor/mcp');
 *   $mcp->initialize();
 *   $tools = $mcp->listTools();
 *   $result = $mcp->callTool('nome_da_tool', ['arg' => 'valor']);
 *
 * Para usar tools remotas em um agente:
 *   foreach ($mcp->tools() as $tool) { $agent->addTool($tool); }
 */
class McpClient
{
	const PROTOCOL_VERSION = '2024-11-05';

	/** @var string */
	protected $url;

	/** @var array Headers extras, ex.: ['Authorization: Bearer x'] */
	protected $headers;

	/** @var Http */
	protected $http;

	/** @var int */
	protected $requestId = 0;

	/** @var bool */
	protected $initialized = false;

	/**
	 * @param string $url URL do endpoint MCP
	 * @param array $headers
	 * @param int $timeout
	 */
	public function __construct($url, array $headers = [], $timeout = 60)
	{
		$this->url = $url;
		$this->headers = $headers;
		$this->http = new Http($timeout);
	}

	/**
	 * Handshake inicial com o servidor.
	 *
	 * @return array Capacidades do servidor
	 */
	public function initialize()
	{
		$result = $this->request('initialize', [
			'protocolVersion' => self::PROTOCOL_VERSION,
			'capabilities' => new \stdClass(),
			'clientInfo' => [
				'name' => 'ci3-ai',
				'version' => '0.1.0',
			],
		]);

		$this->notify('notifications/initialized');
		$this->initialized = true;

		return $result;
	}

	/**
	 * Lista as tools expostas pelo servidor.
	 *
	 * @return array Cada item: name, description, inputSchema
	 */
	public function listTools()
	{
		$this->ensureInitialized();
		$result = $this->request('tools/list');

		return isset($result['tools']) ? $result['tools'] : [];
	}

	/**
	 * Executa uma tool remota.
	 *
	 * @param string $name
	 * @param array $arguments
	 * @return array Resultado MCP (content, isError)
	 */
	public function callTool($name, array $arguments = [])
	{
		$this->ensureInitialized();

		return $this->request('tools/call', [
			'name' => $name,
			'arguments' => count($arguments) > 0 ? $arguments : new \stdClass(),
		]);
	}

	/**
	 * Tools remotas adaptadas para uso direto em um Agent.
	 *
	 * @return McpToolAdapter[]
	 */
	public function tools()
	{
		$adapters = [];

		foreach ($this->listTools() as $definition) {
			$adapters[] = new McpToolAdapter($this, $definition);
		}

		return $adapters;
	}

	protected function ensureInitialized()
	{
		if (!$this->initialized) {
			$this->initialize();
		}
	}

	/**
	 * @param string $method
	 * @param mixed $params
	 * @return array result do JSON-RPC
	 * @throws AiException
	 */
	protected function request($method, $params = null)
	{
		$payload = [
			'jsonrpc' => '2.0',
			'id' => ++$this->requestId,
			'method' => $method,
		];
		if ($params !== null) {
			$payload['params'] = $params;
		}

		$response = $this->http->postJson($this->url, $payload, $this->headers);

		if (isset($response['error'])) {
			$message = isset($response['error']['message']) ? $response['error']['message'] : 'Erro MCP';
			throw new AiException('MCP [' . $method . ']: ' . $message);
		}

		return isset($response['result']) ? $response['result'] : [];
	}

	/**
	 * Notificação JSON-RPC (sem id, sem resposta esperada).
	 *
	 * @param string $method
	 */
	protected function notify($method)
	{
		try {
			$this->http->postJson($this->url, [
				'jsonrpc' => '2.0',
				'method' => $method,
			], $this->headers);
		} catch (\Exception $e) {
			// Alguns servidores respondem 202/vazio a notificações; ignorar falhas de parse.
		}
	}
}
