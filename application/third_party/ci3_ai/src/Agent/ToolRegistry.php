<?php
/**
 * CI3 AI - Pacote de IA para CodeIgniter 3.x
 *
 * @package CiAi
 * @author  Mário Lucas <mariolucasdev@gmail.com>
 * @license MIT
 */
namespace CiAi\Agent;

use CiAi\Contracts\ToolInterface;
use CiAi\Exceptions\AiException;

/**
 * Registro de tools disponíveis para um agente ou servidor MCP.
 */
class ToolRegistry
{
	/** @var ToolInterface[] Indexadas por nome */
	protected $tools = [];

	/**
	 * @param ToolInterface $tool
	 * @return $this
	 */
	public function register(ToolInterface $tool)
	{
		$this->tools[$tool->getName()] = $tool;
		return $this;
	}

	/**
	 * @param string $name
	 * @return bool
	 */
	public function has($name)
	{
		return isset($this->tools[$name]);
	}

	/**
	 * @return ToolInterface[]
	 */
	public function all()
	{
		return $this->tools;
	}

	/**
	 * @return int
	 */
	public function count()
	{
		return count($this->tools);
	}

	/**
	 * Schemas neutros consumidos pelos provedores.
	 *
	 * @return array Lista de ['name' => ..., 'description' => ..., 'parameters' => ...]
	 */
	public function schemas()
	{
		$schemas = [];

		foreach ($this->tools as $tool) {
			$schemas[] = [
				'name' => $tool->getName(),
				'description' => $tool->getDescription(),
				'parameters' => $tool->getParameters(),
			];
		}

		return $schemas;
	}

	/**
	 * Executa uma tool registrada.
	 *
	 * @param string $name
	 * @param array $arguments
	 * @return mixed
	 * @throws AiException
	 */
	public function execute($name, array $arguments)
	{
		if (!isset($this->tools[$name])) {
			throw new AiException('Tool não registrada: ' . $name);
		}

		return $this->tools[$name]->execute($arguments);
	}
}
