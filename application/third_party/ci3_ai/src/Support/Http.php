<?php
/**
 * CI3 AI - Pacote de IA para CodeIgniter 3.x
 *
 * @package CiAi
 * @author  Mário Lucas <mariolucasdev@gmail.com>
 * @license MIT
 */
namespace CiAi\Support;

use CiAi\Exceptions\ProviderException;

/**
 * Cliente HTTP mínimo baseado em cURL (sem dependências externas).
 */
class Http
{
	/** @var int */
	protected $timeout;

	/**
	 * @param int $timeout Segundos
	 */
	public function __construct($timeout = 60)
	{
		$this->timeout = $timeout;
	}

	/**
	 * Envia um POST com corpo JSON e retorna a resposta decodificada.
	 *
	 * @param string $url
	 * @param array $payload
	 * @param array $headers  Lista no formato "Nome: valor"
	 * @return array
	 * @throws ProviderException
	 */
	public function postJson($url, array $payload, array $headers = [])
	{
		$headers[] = 'Content-Type: application/json';
		$headers[] = 'Accept: application/json';

		$ch = curl_init($url);
		curl_setopt_array($ch, [
			CURLOPT_POST => true,
			CURLOPT_POSTFIELDS => json_encode($payload),
			CURLOPT_HTTPHEADER => $headers,
			CURLOPT_RETURNTRANSFER => true,
			CURLOPT_TIMEOUT => $this->timeout,
			CURLOPT_CONNECTTIMEOUT => 10,
		]);

		$body = curl_exec($ch);
		$errno = curl_errno($ch);
		$error = curl_error($ch);
		$status = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
		curl_close($ch);

		if ($errno !== 0) {
			throw new ProviderException('Erro de rede: ' . $error, 0);
		}

		$decoded = json_decode($body, true);

		if ($status >= 400) {
			$message = $this->extractErrorMessage($decoded, $body, $status);
			throw new ProviderException($message, $status, $decoded !== null ? $decoded : $body);
		}

		if (!is_array($decoded)) {
			throw new ProviderException('Resposta não é um JSON válido', $status, $body);
		}

		return $decoded;
	}

	/**
	 * @param mixed $decoded
	 * @param string $body
	 * @param int $status
	 * @return string
	 */
	protected function extractErrorMessage($decoded, $body, $status)
	{
		if (is_array($decoded)) {
			// Formato OpenAI/DeepSeek: {"error": {"message": "..."}}
			if (isset($decoded['error']['message'])) {
				return $decoded['error']['message'];
			}
			// Formato Gemini: {"error": {"message": "...", "status": "..."}}
			if (isset($decoded['error']) && is_string($decoded['error'])) {
				return $decoded['error'];
			}
		}

		return 'Erro HTTP ' . $status . ': ' . substr((string) $body, 0, 500);
	}
}
