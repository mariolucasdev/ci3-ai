<?php
/**
 * CI3 AI - Pacote de IA para CodeIgniter 3.x
 *
 * @package CiAi
 * @author  Mário Lucas <mariolucasdev@gmail.com>
 * @license MIT
 */
namespace CiAi\Contracts;

interface ToolInterface
{
	/**
	 * Nome único da tool (ex.: "get_weather").
	 *
	 * @return string
	 */
	public function getName();

	/**
	 * Descrição usada pelo modelo para decidir quando chamar a tool.
	 *
	 * @return string
	 */
	public function getDescription();

	/**
	 * JSON Schema dos parâmetros aceitos, como array associativo.
	 * Ex.: array('type' => 'object', 'properties' => array(...), 'required' => array(...))
	 *
	 * @return array
	 */
	public function getParameters();

	/**
	 * Executa a tool com os argumentos fornecidos pelo modelo.
	 *
	 * @param array $arguments
	 * @return mixed  String ou estrutura serializável em JSON
	 */
	public function execute(array $arguments);
}
