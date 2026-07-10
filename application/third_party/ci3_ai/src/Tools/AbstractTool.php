<?php
/**
 * CI3 AI - Pacote de IA para CodeIgniter 3.x
 *
 * @package CiAi
 * @author  Mário Lucas <mariolucasdev@gmail.com>
 * @license MIT
 */
namespace CiAi\Tools;

use CiAi\Contracts\ToolInterface;

/**
 * Base opcional para tools: defina as propriedades e implemente execute().
 */
abstract class AbstractTool implements ToolInterface
{
    /** @var string */
    protected $name = '';

    /** @var string */
    protected $description = '';

    /** @var array JSON Schema dos parâmetros */
    protected $parameters = [
        'type' => 'object',
        'properties' => [],
    ];

    public function getName()
    {
        return $this->name;
    }

    public function getDescription()
    {
        return $this->description;
    }

    public function getParameters()
    {
        return $this->parameters;
    }
}
