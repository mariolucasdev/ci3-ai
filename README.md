# CI3 AI

Pacote de IA para **CodeIgniter 3.1.11+** e **PHP 7.2+**: agentes, tools e MCP (Model Context Protocol) com suporte a **OpenAI**, **Gemini** e **DeepSeek** — sem nenhuma dependência externa (HTTP via cURL nativo).

## Instalação

1. Copie `application/third_party/ci3_ai/`, `application/libraries/Ai.php` e `application/config/ai.php` para sua aplicação CI3.
2. Defina as variáveis de ambiente das API keys que for usar:

```bash
export OPENAI_API_KEY=sk-...
export GEMINI_API_KEY=...
export DEEPSEEK_API_KEY=...
```

## Uso

### Pergunta simples

```php
$this->load->library('ai');

echo $this->ai->ask('Resuma este texto: ...');                          // provedor padrão
echo $this->ai->ask('Olá!', ['temperature' => 0.2], 'gemini');     // provedor específico
```

### Conversa com controle de mensagens

```php
use CiAi\Chat\Message;

$response = $this->ai->chat([
    Message::system('Você é um assistente de suporte.'),
    Message::user('Como redefinir minha senha?'),
], ['model' => 'gpt-4o'], 'openai');

echo $response->content;
print_r($response->usage);
```

### Agente com tools

```php
use CiAi\Tools\DatetimeTool;

$response = $this->ai->agent('deepseek')
    ->setSystemPrompt('Você é um assistente útil.')
    ->addTool(new DatetimeTool())
    ->run('Que horas são em Lisboa?');

echo $response->content;
```

Criando a sua tool:

```php
use CiAi\Tools\AbstractTool;

class BuscarPedidoTool extends AbstractTool
{
    protected $name = 'buscar_pedido';
    protected $description = 'Busca um pedido pelo número.';
    protected $parameters = [
        'type' => 'object',
        'properties' => [
            'numero' => ['type' => 'string', 'description' => 'Número do pedido'],
        ],
        'required' => ['numero'],
    ];

    public function execute(array $arguments)
    {
        // consulte seu model aqui
        return ['numero' => $arguments['numero'], 'status' => 'enviado'];
    }
}
```

### MCP — consumir um servidor remoto

```php
$mcp = $this->ai->mcpClient('https://exemplo.com/mcp', ['Authorization: Bearer token']);

// Tools remotas direto num agente:
$agent = $this->ai->agent();
foreach ($mcp->tools() as $tool) {
    $agent->addTool($tool);
}
echo $agent->run('Use as tools disponíveis para...')->content;
```

### MCP — expor suas tools como servidor

```php
public function mcp()
{
    $registry = $this->ai->toolRegistry()
        ->register(new DatetimeTool())
        ->register(new BuscarPedidoTool());

    $this->ai->mcpServer($registry, 'minha-app')->respond();
}
```

## Provedores e modelos padrão

| Provedor | Modelo padrão | Variável de ambiente |
|----------|---------------|----------------------|
| OpenAI   | `gpt-4o-mini` | `OPENAI_API_KEY` |
| DeepSeek | `deepseek-chat` | `DEEPSEEK_API_KEY` |
| Gemini   | `gemini-2.0-flash` | `GEMINI_API_KEY` |

Modelos e URLs são configuráveis em `application/config/ai.php`; o modelo também pode ser trocado por chamada via `['model' => '...']`.

## Demo

Com o servidor embutido do PHP:

```bash
OPENAI_API_KEY=sk-... php -S localhost:8000
curl "localhost:8000/index.php/ai_demo/ask?q=Olá"
curl "localhost:8000/index.php/ai_demo/agent?q=Que%20horas%20s%C3%A3o%3F"
```

## Licença

MIT (o CodeIgniter 3 tem licença própria — ver `license.txt`).
