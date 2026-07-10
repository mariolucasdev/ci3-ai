<?php
/**
 * CI3 AI - Pacote de IA para CodeIgniter 3.x
 *
 * @package CiAi
 * @author  Mário Lucas <mariolucasdev@gmail.com>
 * @license MIT
 */
namespace CiAi\Mcp;

use CiAi\Agent\ToolRegistry;

/**
 * Servidor MCP (Model Context Protocol) via transporte HTTP (JSON-RPC 2.0).
 * Expõe as tools de um ToolRegistry para qualquer cliente MCP.
 *
 * Uso em um controller CodeIgniter:
 *   $server = new McpServer($registry, 'meu-servidor');
 *   $server->respond(); // lê php://input e emite a resposta JSON
 */
class McpServer
{
    const PROTOCOL_VERSION = '2024-11-05';

    /** @var ToolRegistry */
    protected $tools;

    /** @var string */
    protected $serverName;

    /** @var string */
    protected $serverVersion;

    /**
     * @param ToolRegistry $tools
     * @param string $serverName
     * @param string $serverVersion
     */
    public function __construct(ToolRegistry $tools, $serverName = 'ci3-ai', $serverVersion = '0.1.0')
    {
        $this->tools = $tools;
        $this->serverName = $serverName;
        $this->serverVersion = $serverVersion;
    }

    /**
     * Lê a requisição de php://input, processa e emite a resposta JSON.
     * Conveniência para uso direto em controllers.
     */
    public function respond()
    {
        $body = file_get_contents('php://input');
        $request = json_decode($body, true);

        $response = $this->handle(is_array($request) ? $request : []);

        if ($response === null) {
            // Notificação: sem corpo de resposta
            http_response_code(202);
            return;
        }

        header('Content-Type: application/json');
        echo json_encode($response);
    }

    /**
     * Processa uma requisição JSON-RPC e retorna a resposta como array,
     * ou null para notificações.
     *
     * @param array $request
     * @return array|null
     */
    public function handle(array $request)
    {
        $method = isset($request['method']) ? $request['method'] : null;
        $params = isset($request['params']) ? $request['params'] : [];
        $id = isset($request['id']) ? $request['id'] : null;

        // Notificações não recebem resposta
        if ($id === null && strpos((string) $method, 'notifications/') === 0) {
            return null;
        }

        switch ($method) {
            case 'initialize':
                return $this->success($id, [
                    'protocolVersion' => self::PROTOCOL_VERSION,
                    'capabilities' => [
                        'tools' => new \stdClass(),
                    ],
                    'serverInfo' => [
                        'name' => $this->serverName,
                        'version' => $this->serverVersion,
                    ],
                ]);

            case 'ping':
                return $this->success($id, new \stdClass());

            case 'tools/list':
                return $this->success($id, ['tools' => $this->toolDefinitions()]);

            case 'tools/call':
                return $this->handleToolCall($id, $params);

            default:
                return $this->error($id, -32601, 'Método não suportado: ' . $method);
        }
    }

    /**
     * @return array
     */
    protected function toolDefinitions()
    {
        $definitions = [];

        foreach ($this->tools->all() as $tool) {
            $definitions[] = [
                'name' => $tool->getName(),
                'description' => $tool->getDescription(),
                'inputSchema' => $tool->getParameters(),
            ];
        }

        return $definitions;
    }

    /**
     * @param mixed $id
     * @param array $params
     * @return array
     */
    protected function handleToolCall($id, $params)
    {
        $name = isset($params['name']) ? $params['name'] : '';
        $arguments = isset($params['arguments']) && is_array($params['arguments'])
            ? $params['arguments']
            : [];

        if (!$this->tools->has($name)) {
            return $this->error($id, -32602, 'Tool desconhecida: ' . $name);
        }

        try {
            $result = $this->tools->execute($name, $arguments);
            $text = is_string($result) ? $result : json_encode($result);

            return $this->success($id, [
                'content' => [['type' => 'text', 'text' => $text]],
                'isError' => false,
            ]);
        } catch (\Exception $e) {
            return $this->success($id, [
                'content' => [['type' => 'text', 'text' => $e->getMessage()]],
                'isError' => true,
            ]);
        }
    }

    /**
     * @param mixed $id
     * @param mixed $result
     * @return array
     */
    protected function success($id, $result)
    {
        return [
            'jsonrpc' => '2.0',
            'id' => $id,
            'result' => $result,
        ];
    }

    /**
     * @param mixed $id
     * @param int $code
     * @param string $message
     * @return array
     */
    protected function error($id, $code, $message)
    {
        return [
            'jsonrpc' => '2.0',
            'id' => $id,
            'error' => ['code' => $code, 'message' => $message],
        ];
    }
}
