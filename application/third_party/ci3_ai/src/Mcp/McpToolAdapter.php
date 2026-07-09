<?php
/**
 * CI3 AI - Pacote de IA para CodeIgniter 3.x
 *
 * @package CiAi
 * @author  Mário Lucas <mariolucasdev@gmail.com>
 * @license MIT
 */
namespace CiAi\Mcp;

use CiAi\Contracts\ToolInterface;

/**
 * Adapta uma tool remota de um servidor MCP para a interface ToolInterface,
 * permitindo que agentes usem tools MCP de forma transparente.
 */
class McpToolAdapter implements ToolInterface
{
	/** @var McpClient */
	protected $client;

	/** @var array Definição vinda de tools/list: name, description, inputSchema */
	protected $definition;

	/**
	 * @param McpClient $client
	 * @param array $definition
	 */
	public function __construct(McpClient $client, array $definition)
	{
		$this->client = $client;
		$this->definition = $definition;
	}

	public function getName()
	{
		return $this->definition['name'];
	}

	public function getDescription()
	{
		return isset($this->definition['description']) ? $this->definition['description'] : '';
	}

	public function getParameters()
	{
		if (isset($this->definition['inputSchema'])) {
			return $this->definition['inputSchema'];
		}

		return array('type' => 'object', 'properties' => array());
	}

	public function execute(array $arguments)
	{
		$result = $this->client->callTool($this->getName(), $arguments);

		// Resultado MCP: {content: [{type: 'text', text: '...'}], isError: bool}
		if (isset($result['content']) && is_array($result['content'])) {
			$texts = array();
			foreach ($result['content'] as $part) {
				if (isset($part['text'])) {
					$texts[] = $part['text'];
				}
			}
			if (count($texts) > 0) {
				return implode("\n", $texts);
			}
		}

		return $result;
	}
}
