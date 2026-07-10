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
 * Pedido do modelo para executar uma tool.
 */
class ToolCall
{
    /** @var string|null ID da chamada (OpenAI/DeepSeek; Gemini não usa) */
    public $id;

    /** @var string */
    public $name;

    /** @var array Argumentos já decodificados */
    public $arguments = [];

    /**
     * @param string $name
     * @param array $arguments
     * @param string|null $id
     */
    public function __construct($name, array $arguments = [], $id = null)
    {
        $this->name = $name;
        $this->arguments = $arguments;
        $this->id = $id;
    }
}
