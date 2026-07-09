<?php
defined('BASEPATH') OR exit('No direct script access allowed');

require_once APPPATH . 'third_party/ci3_ai/autoload.php';

use CiAi\Agent\Agent;
use CiAi\Agent\ToolRegistry;
use CiAi\Chat\Message;
use CiAi\Contracts\ProviderInterface;
use CiAi\Exceptions\AiException;
use CiAi\Mcp\McpClient;
use CiAi\Mcp\McpServer;

/**
 * CI3 AI - Fachada CodeIgniter para o pacote CiAi.
 *
 * Carregamento:
 *   $this->load->library('ai');
 *
 * Exemplos:
 *   $texto = $this->ai->ask('Resuma este texto: ...');
 *   $resposta = $this->ai->provider('gemini')->chat(array(Message::user('Olá')));
 *   $agente = $this->ai->agent()->setSystemPrompt('...')->addTool($tool);
 *
 * @package CiAi
 * @author  Mário Lucas <mariolucasdev@gmail.com>
 * @license MIT
 */
class Ai
{
	/** @var array Configuração de config/ai.php */
	protected $config;

	/** @var ProviderInterface[] Instâncias por nome */
	protected $providers = array();

	/**
	 * @param array $params Sobrescreve chaves da configuração
	 */
	public function __construct($params = array())
	{
		$CI =& get_instance();
		$CI->config->load('ai', true);

		$this->config = array_merge($CI->config->item('ai', 'ai'), $params);
	}

	/**
	 * Retorna (e memoriza) uma instância de provedor.
	 *
	 * @param string|null $name openai | gemini | deepseek | null (padrão)
	 * @return ProviderInterface
	 * @throws AiException
	 */
	public function provider($name = null)
	{
		if ($name === null) {
			$name = $this->config['default_provider'];
		}

		if (isset($this->providers[$name])) {
			return $this->providers[$name];
		}

		if (!isset($this->config['providers'][$name])) {
			throw new AiException('Provedor não configurado: ' . $name);
		}

		$providerConfig = $this->config['providers'][$name];
		$providerConfig['timeout'] = isset($this->config['timeout']) ? $this->config['timeout'] : 60;

		$class = $providerConfig['class'];
		if (!class_exists($class)) {
			throw new AiException('Classe de provedor não encontrada: ' . $class);
		}

		return $this->providers[$name] = new $class($providerConfig);
	}

	/**
	 * Atalho: pergunta simples com resposta em texto.
	 *
	 * @param string $prompt
	 * @param array $options model, temperature, max_tokens...
	 * @param string|null $provider
	 * @return string
	 */
	public function ask($prompt, array $options = array(), $provider = null)
	{
		$response = $this->provider($provider)->chat(array(Message::user($prompt)), $options);

		return (string) $response->content;
	}

	/**
	 * Conversa completa com controle das mensagens.
	 *
	 * @param \CiAi\Chat\Message[] $messages
	 * @param array $options
	 * @param string|null $provider
	 * @return \CiAi\Chat\ChatResponse
	 */
	public function chat(array $messages, array $options = array(), $provider = null)
	{
		return $this->provider($provider)->chat($messages, $options);
	}

	/**
	 * Cria um agente com loop de tool-calling.
	 *
	 * @param string|null $provider
	 * @return Agent
	 */
	public function agent($provider = null)
	{
		return new Agent($this->provider($provider));
	}

	/**
	 * Cria um registro de tools (para agentes ou servidor MCP).
	 *
	 * @return ToolRegistry
	 */
	public function toolRegistry()
	{
		return new ToolRegistry();
	}

	/**
	 * Cliente para consumir um servidor MCP remoto.
	 *
	 * @param string $url
	 * @param array $headers
	 * @return McpClient
	 */
	public function mcpClient($url, array $headers = array())
	{
		$timeout = isset($this->config['timeout']) ? $this->config['timeout'] : 60;

		return new McpClient($url, $headers, $timeout);
	}

	/**
	 * Servidor MCP para expor tools desta aplicação.
	 *
	 * @param ToolRegistry $tools
	 * @param string $name
	 * @return McpServer
	 */
	public function mcpServer(ToolRegistry $tools, $name = 'ci3-ai')
	{
		return new McpServer($tools, $name);
	}
}
