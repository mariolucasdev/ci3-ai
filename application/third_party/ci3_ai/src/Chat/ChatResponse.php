<?php
/**
 * CI3 AI - Pacote de IA para CodeIgniter 3.x
 *
 * @package CiAi
 * @author  Mário Lucas <mariolucasdev@gmail.com>
 * @license MIT
 */
namespace CiAi\Chat;

/**
 * Resposta de um provedor em formato neutro.
 */
class ChatResponse
{
    /** @var string|null Texto gerado pelo modelo */
    public $content;

    /** @var ToolCall[] */
    public $toolCalls = [];

    /** @var string|null stop, tool_calls, length... */
    public $finishReason;

    /** @var array Uso de tokens: prompt_tokens, completion_tokens, total_tokens */
    public $usage = [];

    /** @var array Resposta bruta do provedor (para depuração) */
    public $raw = [];

    /**
     * @return bool
     */
    public function hasToolCalls()
    {
        return count($this->toolCalls) > 0;
    }

    public function __toString()
    {
        return (string) $this->content;
    }
}
