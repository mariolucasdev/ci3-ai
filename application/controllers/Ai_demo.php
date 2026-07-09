<?php
defined('BASEPATH') OR exit('No direct script access allowed');

use CiAi\Tools\DatetimeTool;

/**
 * Controller de demonstração do pacote CI3 AI.
 *
 * Rotas (com index.php):
 *   GET/POST index.php/ai_demo/ask?q=...      Pergunta simples
 *   GET/POST index.php/ai_demo/agent?q=...    Agente com tool de exemplo
 *   POST     index.php/ai_demo/mcp            Endpoint servidor MCP
 *
 * Requer variável de ambiente com a API key do provedor (ver config/ai.php).
 *
 * @package CiAi
 * @author  Mário Lucas <mariolucasdev@gmail.com>
 * @license MIT
 */
class Ai_demo extends CI_Controller
{
	public function __construct()
	{
		parent::__construct();
		$this->load->library('ai');
	}

	/**
	 * Pergunta simples: index.php/ai_demo/ask?q=Olá&provider=openai
	 */
	public function ask()
	{
		$question = $this->input->get_post('q');
		$provider = $this->input->get_post('provider'); // null = padrão

		if (empty($question)) {
			return $this->json(array('error' => 'Informe o parâmetro q'), 400);
		}

		try {
			$answer = $this->ai->ask($question, array(), $provider);
			$this->json(array('answer' => $answer));
		} catch (Exception $e) {
			$this->json(array('error' => $e->getMessage()), 500);
		}
	}

	/**
	 * Agente com tool-calling: index.php/ai_demo/agent?q=Que horas são em Lisboa?
	 */
	public function agent()
	{
		$question = $this->input->get_post('q');
		$provider = $this->input->get_post('provider');

		if (empty($question)) {
			return $this->json(array('error' => 'Informe o parâmetro q'), 400);
		}

		try {
			$agent = $this->ai->agent($provider)
				->setSystemPrompt('Você é um assistente útil. Use as tools disponíveis quando necessário.')
				->addTool(new DatetimeTool());

			$response = $agent->run($question);

			$this->json(array(
				'answer' => $response->content,
				'usage' => $response->usage,
			));
		} catch (Exception $e) {
			$this->json(array('error' => $e->getMessage()), 500);
		}
	}

	/**
	 * Servidor MCP: expõe as tools desta aplicação via POST index.php/ai_demo/mcp
	 */
	public function mcp()
	{
		$registry = $this->ai->toolRegistry()->register(new DatetimeTool());

		$this->ai->mcpServer($registry, 'ci3-ai-demo')->respond();
	}

	/**
	 * @param array $data
	 * @param int $status
	 */
	protected function json(array $data, $status = 200)
	{
		$this->output
			->set_status_header($status)
			->set_content_type('application/json')
			->set_output(json_encode($data, JSON_UNESCAPED_UNICODE));
	}
}
