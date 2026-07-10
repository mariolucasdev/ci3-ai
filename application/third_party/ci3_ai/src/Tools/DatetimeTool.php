<?php
/**
 * CI3 AI - Pacote de IA para CodeIgniter 3.x
 *
 * @package CiAi
 * @author  Mário Lucas <mariolucasdev@gmail.com>
 * @license MIT
 */
namespace CiAi\Tools;

/**
 * Tool de exemplo: retorna data e hora atuais do servidor.
 * Serve de referência para criar novas tools.
 */
class DatetimeTool extends AbstractTool
{
	protected $name = 'get_current_datetime';

	protected $description = 'Retorna a data e hora atuais do servidor, em um timezone opcional.';

	protected $parameters = [
		'type' => 'object',
		'properties' => [
			'timezone' => [
				'type' => 'string',
				'description' => 'Timezone IANA, ex.: America/Sao_Paulo. Padrão: timezone do servidor.',
			],
		],
	];

	public function execute(array $arguments)
	{
		$timezone = isset($arguments['timezone']) ? $arguments['timezone'] : date_default_timezone_get();

		try {
			$date = new \DateTime('now', new \DateTimeZone($timezone));
		} catch (\Exception $e) {
			return ['error' => 'Timezone inválido: ' . $timezone];
		}

		return [
			'datetime' => $date->format('Y-m-d H:i:s'),
			'timezone' => $timezone,
		];
	}
}
