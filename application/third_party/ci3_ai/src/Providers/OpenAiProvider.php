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
 * Provedor OpenAI (Chat Completions API).
 * Também serve de base para qualquer API compatível com OpenAI (ex.: DeepSeek).
 */
class OpenAiProvider extends AbstractProvider
{
    public function name()
    {
        return 'openai';
    }

    public function chat(array $messages, array $options = [])
    {
        $payload = [
            'model' => $this->resolveModel($options),
            'messages' => $this->mapMessages($messages),
        ];

        if (isset($options['temperature'])) {
            $payload['temperature'] = $options['temperature'];
        }
        if (isset($options['max_tokens'])) {
            $payload['max_tokens'] = $options['max_tokens'];
        }
        if (!empty($options['tools'])) {
            $payload['tools'] = $this->mapTools($options['tools']);
        }

        $response = $this->http->postJson(
            rtrim($this->config['base_url'], '/') . '/chat/completions',
            $payload,
            ['Authorization: Bearer ' . $this->config['api_key']]
        );

        return $this->parseResponse($response);
    }

    /**
     * @param Message[] $messages
     * @return array
     */
    protected function mapMessages(array $messages)
    {
        $mapped = [];

        foreach ($messages as $message) {
            $item = ['role' => $message->role];

            if ($message->role === Message::ROLE_TOOL) {
                $item['tool_call_id'] = $message->toolCallId;
                $item['content'] = (string) $message->content;
            } elseif ($message->role === Message::ROLE_ASSISTANT && count($message->toolCalls) > 0) {
                $item['content'] = $message->content;
                $item['tool_calls'] = [];
                foreach ($message->toolCalls as $call) {
                    $item['tool_calls'][] = [
                        'id' => $call->id,
                        'type' => 'function',
                        'function' => [
                            'name' => $call->name,
                            'arguments' => json_encode($call->arguments),
                        ],
                    ];
                }
            } else {
                $item['content'] = (string) $message->content;
            }

            $mapped[] = $item;
        }

        return $mapped;
    }

    /**
     * Converte schemas neutros (ToolRegistry::schemas()) para o formato OpenAI.
     *
     * @param array $tools
     * @return array
     */
    protected function mapTools(array $tools)
    {
        $mapped = [];

        foreach ($tools as $tool) {
            $mapped[] = [
                'type' => 'function',
                'function' => [
                    'name' => $tool['name'],
                    'description' => $tool['description'],
                    'parameters' => $tool['parameters'],
                ],
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

        $choice = isset($response['choices'][0]) ? $response['choices'][0] : [];
        $message = isset($choice['message']) ? $choice['message'] : [];

        $result->content = isset($message['content']) ? $message['content'] : null;
        $result->finishReason = isset($choice['finish_reason']) ? $choice['finish_reason'] : null;

        if (!empty($message['tool_calls'])) {
            foreach ($message['tool_calls'] as $call) {
                $arguments = json_decode($call['function']['arguments'], true);
                $result->toolCalls[] = new ToolCall(
                    $call['function']['name'],
                    is_array($arguments) ? $arguments : [],
                    isset($call['id']) ? $call['id'] : null
                );
            }
        }

        if (isset($response['usage'])) {
            $result->usage = $response['usage'];
        }

        return $result;
    }
}
