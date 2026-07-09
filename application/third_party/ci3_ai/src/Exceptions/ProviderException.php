<?php
/**
 * CI3 AI - Pacote de IA para CodeIgniter 3.x
 *
 * @package CiAi
 * @author  Mário Lucas <mariolucasdev@gmail.com>
 * @license MIT
 */
namespace CiAi\Exceptions;

class ProviderException extends AiException
{
	/** @var int Código HTTP retornado pelo provedor (0 = erro de rede) */
	public $statusCode = 0;

	/** @var mixed Corpo bruto da resposta do provedor */
	public $responseBody;

	/**
	 * @param string $message
	 * @param int $statusCode
	 * @param mixed $responseBody
	 */
	public function __construct($message, $statusCode = 0, $responseBody = null)
	{
		parent::__construct($message, $statusCode);
		$this->statusCode = $statusCode;
		$this->responseBody = $responseBody;
	}
}
