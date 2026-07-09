<?php
/**
 * CI3 AI - Pacote de IA para CodeIgniter 3.x
 *
 * @package CiAi
 * @author  Mário Lucas <mariolucasdev@gmail.com>
 * @license MIT
 */
namespace CiAi\Providers;

use CiAi\Chat\ChatResponse;
use CiAi\Chat\Message;
use CiAi\Chat\ToolCall;

/**
 * Provedor Google Gemini (generateContent API v1beta).
 */
class GeminiProvider extends AbstractProvider
{
	public function name()
	{
		return 'gemini';
	}

	public function chat(array $messages, array $options = array())
	{
		$payload = array(
			'contents' => array(),
		);

		$generationConfig = array();
		if (isset($options['temperature'])) {
			$generationConfig['temperature'] = $options['temperature'];
		}
		if (isset($options['max_tokens'])) {
			$generationConfig['maxOutputTokens'] = $options['max_tokens'];
		}
		if (count($generationConfig) > 0) {
			$payload['generationConfig'] = $generationConfig;
		}

		foreach ($messages as $message) {
			if ($message->role === Message::ROLE_SYSTEM) {
				$payload['systemInstruction'] = array(
					'parts' => array(array('text' => (string) $message->content)),
				);
				continue;
			}

			$payload['contents'][] = $this->mapMessage($message);
		}

		if (!empty($options['tools'])) {
			$declarations = array();
			foreach ($options['tools'] as $tool) {
				$declarations[] = array(
					'name' => $tool['name'],
					'description' => $tool['description'],
					'parameters' => $tool['parameters'],
				);
			}
			$payload['tools'] = array(array('functionDeclarations' => $declarations));
		}

		$model = $this->resolveModel($options);
		$url = rtrim($this->config['base_url'], '/') . '/models/' . $model . ':generateContent';

		$response = $this->http->postJson(
			$url,
			$payload,
			array('x-goog-api-key: ' . $this->config['api_key'])
		);

		return $this->parseResponse($response);
	}

	/**
	 * @param Message $message
	 * @return array
	 */
	protected function mapMessage(Message $message)
	{
		if ($message->role === Message::ROLE_TOOL) {
			$response = json_decode((string) $message->content, true);
			return array(
				'role' => 'user',
				'parts' => array(array(
					'functionResponse' => array(
						'name' => $message->name,
						'response' => array(
							'result' => $response !== null ? $response : (string) $message->content,
						),
					),
				)),
			);
		}

		if ($message->role === Message::ROLE_ASSISTANT) {
			$parts = array();
			if ($message->content !== null && $message->content !== '') {
				$parts[] = array('text' => (string) $message->content);
			}
			foreach ($message->toolCalls as $call) {
				$parts[] = array(
					'functionCall' => array(
						'name' => $call->name,
						'args' => count($call->arguments) > 0 ? $call->arguments : new \stdClass(),
					),
				);
			}
			return array('role' => 'model', 'parts' => $parts);
		}

		return array(
			'role' => 'user',
			'parts' => array(array('text' => (string) $message->content)),
		);
	}

	/**
	 * @param array $response
	 * @return ChatResponse
	 */
	protected function parseResponse(array $response)
	{
		$result = new ChatResponse();
		$result->raw = $response;

		$candidate = isset($response['candidates'][0]) ? $response['candidates'][0] : array();
		$parts = isset($candidate['content']['parts']) ? $candidate['content']['parts'] : array();

		$texts = array();
		foreach ($parts as $part) {
			if (isset($part['text'])) {
				$texts[] = $part['text'];
			}
			if (isset($part['functionCall'])) {
				$args = isset($part['functionCall']['args']) ? $part['functionCall']['args'] : array();
				$result->toolCalls[] = new ToolCall(
					$part['functionCall']['name'],
					is_array($args) ? $args : array()
				);
			}
		}

		$result->content = count($texts) > 0 ? implode("\n", $texts) : null;
		$result->finishReason = isset($candidate['finishReason']) ? $candidate['finishReason'] : null;

		if (isset($response['usageMetadata'])) {
			$usage = $response['usageMetadata'];
			$result->usage = array(
				'prompt_tokens' => isset($usage['promptTokenCount']) ? $usage['promptTokenCount'] : 0,
				'completion_tokens' => isset($usage['candidatesTokenCount']) ? $usage['candidatesTokenCount'] : 0,
				'total_tokens' => isset($usage['totalTokenCount']) ? $usage['totalTokenCount'] : 0,
			);
		}

		return $result;
	}
}
