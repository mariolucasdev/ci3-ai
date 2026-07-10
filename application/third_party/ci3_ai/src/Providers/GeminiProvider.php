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

    public function chat(array $messages, array $options = [])
    {
        $payload = [
            'contents' => [],
        ];

        $generationConfig = [];
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
                $payload['systemInstruction'] = [
                    'parts' => [['text' => (string) $message->content]],
                ];
                continue;
            }

            $payload['contents'][] = $this->mapMessage($message);
        }

        if (!empty($options['tools'])) {
            $declarations = [];
            foreach ($options['tools'] as $tool) {
                $declarations[] = [
                    'name' => $tool['name'],
                    'description' => $tool['description'],
                    'parameters' => $tool['parameters'],
                ];
            }
            $payload['tools'] = [['functionDeclarations' => $declarations]];
        }

        $model = $this->resolveModel($options);
        $url = rtrim($this->config['base_url'], '/') . '/models/' . $model . ':generateContent';

        $response = $this->http->postJson(
            $url,
            $payload,
            ['x-goog-api-key: ' . $this->config['api_key']]
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
            return [
                'role' => 'user',
                'parts' => [[
                    'functionResponse' => [
                        'name' => $message->name,
                        'response' => [
                            'result' => $response !== null ? $response : (string) $message->content,
                        ],
                    ],
                ]],
            ];
        }

        if ($message->role === Message::ROLE_ASSISTANT) {
            $parts = [];
            if ($message->content !== null && $message->content !== '') {
                $parts[] = ['text' => (string) $message->content];
            }
            foreach ($message->toolCalls as $call) {
                $parts[] = [
                    'functionCall' => [
                        'name' => $call->name,
                        'args' => count($call->arguments) > 0 ? $call->arguments : new \stdClass(),
                    ],
                ];
            }
            return ['role' => 'model', 'parts' => $parts];
        }

        return [
            'role' => 'user',
            'parts' => [['text' => (string) $message->content]],
        ];
    }

    /**
     * @param array $response
     * @return ChatResponse
     */
    protected function parseResponse(array $response)
    {
        $result = new ChatResponse();
        $result->raw = $response;

        $candidate = isset($response['candidates'][0]) ? $response['candidates'][0] : [];
        $parts = isset($candidate['content']['parts']) ? $candidate['content']['parts'] : [];

        $texts = [];
        foreach ($parts as $part) {
            if (isset($part['text'])) {
                $texts[] = $part['text'];
            }
            if (isset($part['functionCall'])) {
                $args = isset($part['functionCall']['args']) ? $part['functionCall']['args'] : [];
                $result->toolCalls[] = new ToolCall(
                    $part['functionCall']['name'],
                    is_array($args) ? $args : []
                );
            }
        }

        $result->content = count($texts) > 0 ? implode("\n", $texts) : null;
        $result->finishReason = isset($candidate['finishReason']) ? $candidate['finishReason'] : null;

        if (isset($response['usageMetadata'])) {
            $usage = $response['usageMetadata'];
            $result->usage = [
                'prompt_tokens' => isset($usage['promptTokenCount']) ? $usage['promptTokenCount'] : 0,
                'completion_tokens' => isset($usage['candidatesTokenCount']) ? $usage['candidatesTokenCount'] : 0,
                'total_tokens' => isset($usage['totalTokenCount']) ? $usage['totalTokenCount'] : 0,
            ];
        }

        return $result;
    }
}
