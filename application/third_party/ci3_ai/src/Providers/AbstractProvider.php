<?php
/**
 * CI3 AI - Pacote de IA para CodeIgniter 3.x
 *
 * @package CiAi
 * @author  Mário Lucas <mariolucasdev@gmail.com>
 * @license MIT
 */
namespace CiAi\Providers;

use CiAi\Contracts\ProviderInterface;
use CiAi\Exceptions\ProviderException;
use CiAi\Support\Http;

abstract class AbstractProvider implements ProviderInterface
{
	/** @var array Configuração: api_key, base_url, model, timeout */
	protected $config;

	/** @var Http */
	protected $http;

	/**
	 * @param array $config
	 */
	public function __construct(array $config)
	{
		if (empty($config['api_key'])) {
			throw new ProviderException(
				'API key ausente para o provedor "' . $this->name() . '". '
				. 'Defina a variável de ambiente correspondente (ver config/ai.php).'
			);
		}

		$this->config = $config;
		$timeout = isset($config['timeout']) ? (int) $config['timeout'] : 60;
		$this->http = new Http($timeout);
	}

	/**
	 * Modelo efetivo: opção da chamada > configuração.
	 *
	 * @param array $options
	 * @return string
	 */
	protected function resolveModel(array $options)
	{
		if (!empty($options['model'])) {
			return $options['model'];
		}

		return $this->config['model'];
	}
}
