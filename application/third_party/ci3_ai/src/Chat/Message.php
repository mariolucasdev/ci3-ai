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
 * Mensagem de conversa em formato neutro (independente de provedor).
 * Cada provedor converte para o seu formato nativo.
 */
class Message
{
    const ROLE_SYSTEM = 'system';
    const ROLE_USER = 'user';
    const ROLE_ASSISTANT = 'assistant';
    const ROLE_TOOL = 'tool';

    /** @var string */
    public $role;

    /** @var string|null */
    public $content;

    /** @var ToolCall[] Chamadas de tool feitas pelo assistente */
    public $toolCalls = [];

    /** @var string|null ID da chamada respondida (mensagens role=tool) */
    public $toolCallId;

    /** @var string|null Nome da tool respondida (mensagens role=tool) */
    public $name;

    /**
     * @param string $role
     * @param string|null $content
     */
    public function __construct($role, $content = null)
    {
        $this->role = $role;
        $this->content = $content;
    }

    /**
     * @param string $content
     * @return Message
     */
    public static function system($content)
    {
        return new self(self::ROLE_SYSTEM, $content);
    }

    /**
     * @param string $content
     * @return Message
     */
    public static function user($content)
    {
        return new self(self::ROLE_USER, $content);
    }

    /**
     * @param string|null $content
     * @param ToolCall[] $toolCalls
     * @return Message
     */
    public static function assistant($content, array $toolCalls = [])
    {
        $message = new self(self::ROLE_ASSISTANT, $content);
        $message->toolCalls = $toolCalls;
        return $message;
    }

    /**
     * Resultado da execução de uma tool, devolvido ao modelo.
     *
     * @param string $content
     * @param string|null $toolCallId
     * @param string|null $name
     * @return Message
     */
    public static function tool($content, $toolCallId = null, $name = null)
    {
        $message = new self(self::ROLE_TOOL, $content);
        $message->toolCallId = $toolCallId;
        $message->name = $name;
        return $message;
    }
}
