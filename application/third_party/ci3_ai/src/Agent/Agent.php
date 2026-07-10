<?php
/**
 * CI3 AI - Pacote de IA para CodeIgniter 3.x
 *
 * @package CiAi
 * @author  Mário Lucas <mariolucasdev@gmail.com>
 * @license MIT
 */
namespace CiAi\Agent;

use CiAi\Chat\ChatResponse;
use CiAi\Chat\Message;
use CiAi\Contracts\ProviderInterface;
use CiAi\Contracts\ToolInterface;
use CiAi\Exceptions\AiException;

/**
 * Agente: executa um loop de tool-calling com o provedor até o modelo
 * produzir uma resposta final (ou atingir o limite de iterações).
 *
 * Uso:
 *   $agent = new Agent($provider);
 *   $agent->setSystemPrompt('Você é um assistente...')
 *         ->addTool(new MinhaTool())
 *         ->run('Pergunta do usuário');
 */
class Agent
{
    /** @var ProviderInterface */
    protected $provider;

    /** @var ToolRegistry */
    protected $tools;

    /** @var string|null */
    protected $systemPrompt;

    /** @var int Limite de idas e vindas de tools por execução */
    protected $maxIterations = 10;

    /** @var array Opções repassadas ao provedor (temperature, model...) */
    protected $options = [];

    /** @var Message[] Histórico completo da última execução */
    protected $messages = [];

    /**
     * @param ProviderInterface $provider
     * @param ToolRegistry|null $tools
     */
    public function __construct(ProviderInterface $provider, ?ToolRegistry $tools = null)
    {
        $this->provider = $provider;
        $this->tools = $tools !== null ? $tools : new ToolRegistry();
    }

    /**
     * @param string $prompt
     * @return $this
     */
    public function setSystemPrompt($prompt)
    {
        $this->systemPrompt = $prompt;
        return $this;
    }

    /**
     * @param ToolInterface $tool
     * @return $this
     */
    public function addTool(ToolInterface $tool)
    {
        $this->tools->register($tool);
        return $this;
    }

    /**
     * @param int $max
     * @return $this
     */
    public function setMaxIterations($max)
    {
        $this->maxIterations = (int) $max;
        return $this;
    }

    /**
     * Opções repassadas ao provedor em cada chamada (temperature, model...).
     *
     * @param array $options
     * @return $this
     */
    public function setOptions(array $options)
    {
        $this->options = $options;
        return $this;
    }

    /**
     * @return ToolRegistry
     */
    public function getTools()
    {
        return $this->tools;
    }

    /**
     * Histórico completo da última execução (útil para logs e continuação).
     *
     * @return Message[]
     */
    public function getMessages()
    {
        return $this->messages;
    }

    /**
     * Executa o agente até obter uma resposta final.
     *
     * @param string $userInput
     * @param Message[] $history Mensagens anteriores da conversa (sem system)
     * @return ChatResponse
     * @throws AiException
     */
    public function run($userInput, array $history = [])
    {
        $this->messages = [];

        if ($this->systemPrompt !== null) {
            $this->messages[] = Message::system($this->systemPrompt);
        }

        foreach ($history as $message) {
            $this->messages[] = $message;
        }

        $this->messages[] = Message::user($userInput);

        $options = $this->options;
        if ($this->tools->count() > 0) {
            $options['tools'] = $this->tools->schemas();
        }

        for ($i = 0; $i < $this->maxIterations; $i++) {
            $response = $this->provider->chat($this->messages, $options);

            if (!$response->hasToolCalls()) {
                return $response;
            }

            $this->messages[] = Message::assistant($response->content, $response->toolCalls);

            foreach ($response->toolCalls as $call) {
                try {
                    $result = $this->tools->execute($call->name, $call->arguments);
                } catch (\Exception $e) {
                    $result = ['error' => $e->getMessage()];
                }

                $content = is_string($result) ? $result : json_encode($result);
                $this->messages[] = Message::tool($content, $call->id, $call->name);
            }
        }

        throw new AiException(
            'Agente excedeu o limite de ' . $this->maxIterations . ' iterações sem resposta final.'
        );
    }
}
