<?php
/**
 * CI3 AI - Pacote de IA para CodeIgniter 3.x
 *
 * @package CiAi
 * @author  Mário Lucas <mariolucasdev@gmail.com>
 * @license MIT
 */
namespace CiAi\Contracts;

use CiAi\Chat\ChatResponse;

interface ProviderInterface
{
	/**
	 * Nome do provedor (openai, gemini, deepseek...).
	 *
	 * @return string
	 */
	public function name();

	/**
	 * Envia uma conversa ao provedor e retorna a resposta.
	 *
	 * @param \CiAi\Chat\Message[] $messages
	 * @param array $options  Opções: model, temperature, max_tokens,
	 *                        tools (array de schemas neutros — ver ToolRegistry::schemas())
	 * @return ChatResponse
	 * @throws \CiAi\Exceptions\ProviderException
	 */
	public function chat(array $messages, array $options = array());
}
