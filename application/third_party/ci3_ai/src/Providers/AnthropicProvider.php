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
 * Provedor Anthropic (Claude) via Messages API.
 *
 * Diferenças em relação ao formato OpenAI:
 * - max_tokens é obrigatório (padrão configurável em config/ai.php);
 * - o prompt de sistema vai no campo top-level "system";
 * - tool calls e resultados são blocos de conteúdo dentro das mensagens;
 * - resultados de tools paralelas devem ir em UMA única mensagem "user".
 */
class AnthropicProvider extends AbstractProvider
{
    const API_VERSION = '2023-06-01';

    const DEFAULT_MAX_TOKENS = 4096;

    public function name()
    {
        return 'anthropic';
    }

    public function chat(array $messages, array $options = [])
    {
        $payload = [
            'model' => $this->resolveModel($options),
            'max_tokens' => $this->resolveMaxTokens($options),
            'messages' => [],
        ];

        foreach ($messages as $message) {
            if ($message->role === Message::ROLE_SYSTEM) {
                $payload['system'] = (string) $message->content;
                continue;
            }

            $payload['messages'][] = $this->mapMessage($message);
        }

        $payload['messages'] = $this->mergeToolResults($payload['messages']);

        if (isset($options['temperature'])) {
            $payload['temperature'] = $options['temperature'];
        }
        if (!empty($options['tools'])) {
            $payload['tools'] = $this->mapTools($options['tools']);
        }

        $response = $this->http->postJson(
            rtrim($this->config['base_url'], '/') . '/messages',
            $payload,
            [
                'x-api-key: ' . $this->config['api_key'],
                'anthropic-version: ' . self::API_VERSION,
            ]
        );

        return $this->parseResponse($response);
    }

    /**
     * @param array $options
     * @return int
     */
    protected function resolveMaxTokens(array $options)
    {
        if (isset($options['max_tokens'])) {
            return (int) $options['max_tokens'];
        }
        if (isset($this->config['max_tokens'])) {
            return (int) $this->config['max_tokens'];
        }

        return self::DEFAULT_MAX_TOKENS;
    }

    /**
     * @param Message $message
     * @return array
     */
    protected function mapMessage(Message $message)
    {
        if ($message->role === Message::ROLE_TOOL) {
            return [
                'role' => 'user',
                'content' => [[
                    'type' => 'tool_result',
                    'tool_use_id' => $message->toolCallId,
                    'content' => (string) $message->content,
                ]],
            ];
        }

        if ($message->role === Message::ROLE_ASSISTANT && count($message->toolCalls) > 0) {
            $content = [];
            if ($message->content !== null && $message->content !== '') {
                $content[] = ['type' => 'text', 'text' => (string) $message->content];
            }
            foreach ($message->toolCalls as $call) {
                $content[] = [
                    'type' => 'tool_use',
                    'id' => $call->id,
                    'name' => $call->name,
                    'input' => count($call->arguments) > 0 ? $call->arguments : new \stdClass(),
                ];
            }

            return ['role' => 'assistant', 'content' => $content];
        }

        return [
            'role' => $message->role,
            'content' => (string) $message->content,
        ];
    }

    /**
     * A API exige que resultados de tool calls paralelas cheguem em uma
     * única mensagem "user" — mensagens tool_result consecutivas são unidas.
     *
     * @param array $messages
     * @return array
     */
    protected function mergeToolResults(array $messages)
    {
        $merged = [];

        foreach ($messages as $message) {
            $previous = count($merged) > 0 ? $merged[count($merged) - 1] : null;

            if ($previous !== null
                && $this->isToolResultMessage($message)
                && $this->isToolResultMessage($previous)
            ) {
                $merged[count($merged) - 1]['content'] = array_merge(
                    $previous['content'],
                    $message['content']
                );
                continue;
            }

            $merged[] = $message;
        }

        return $merged;
    }

    /**
     * @param array $message
     * @return bool
     */
    protected function isToolResultMessage(array $message)
    {
        return $message['role'] === 'user'
            && is_array($message['content'])
            && isset($message['content'][0]['type'])
            && $message['content'][0]['type'] === 'tool_result';
    }

    /**
     * Converte schemas neutros para o formato Anthropic (input_schema).
     *
     * @param array $tools
     * @return array
     */
    protected function mapTools(array $tools)
    {
        $mapped = [];

        foreach ($tools as $tool) {
            $mapped[] = [
                'name' => $tool['name'],
                'description' => $tool['description'],
                'input_schema' => $tool['parameters'],
            ];
        }

        return $mapped;
    }

    /**
     * @param array $response
     * @return ChatResponse
     */
    protected function parseResponse(array $response)
    {
        $result = new ChatResponse();
        $result->raw = $response;

        $texts = [];
        $blocks = isset($response['content']) ? $response['content'] : [];

        foreach ($blocks as $block) {
            if (isset($block['type']) && $block['type'] === 'text') {
                $texts[] = $block['text'];
            }
            if (isset($block['type']) && $block['type'] === 'tool_use') {
                $arguments = isset($block['input']) && is_array($block['input'])
                    ? $block['input']
                    : [];
                $result->toolCalls[] = new ToolCall($block['name'], $arguments, $block['id']);
            }
        }

        $result->content = count($texts) > 0 ? implode("\n", $texts) : null;
        $result->finishReason = isset($response['stop_reason']) ? $response['stop_reason'] : null;

        if (isset($response['usage'])) {
            $usage = $response['usage'];
            $input = isset($usage['input_tokens']) ? $usage['input_tokens'] : 0;
            $output = isset($usage['output_tokens']) ? $usage['output_tokens'] : 0;
            $result->usage = [
                'prompt_tokens' => $input,
                'completion_tokens' => $output,
                'total_tokens' => $input + $output,
            ];
        }

        return $result;
    }
}
