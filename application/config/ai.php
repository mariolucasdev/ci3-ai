<?php
defined('BASEPATH') or exit('No direct script access allowed');

/*
|--------------------------------------------------------------------------
| CI3 AI - Configuração
|--------------------------------------------------------------------------
| Configuração dos provedores de IA. As chaves de API devem vir de
| variáveis de ambiente — nunca commitar chaves neste arquivo.
|
| @package CiAi
| @author  Mário Lucas <mariolucasdev@gmail.com>
| @license MIT
*/

$config['ai'] = [

	// Provedor usado quando nenhum é especificado
	'default_provider' => 'openai',

	// Timeout (segundos) das requisições HTTP aos provedores
	'timeout' => 60,

	'providers' => [

		'openai' => [
			'class' => 'CiAi\\Providers\\OpenAiProvider',
			'api_key' => getenv('OPENAI_API_KEY'),
			'base_url' => 'https://api.openai.com/v1',
			'model' => 'gpt-4o-mini',
		],

		'deepseek' => [
			'class' => 'CiAi\\Providers\\DeepSeekProvider',
			'api_key' => getenv('DEEPSEEK_API_KEY'),
			'base_url' => 'https://api.deepseek.com',
			'model' => 'deepseek-chat',
		],

		'gemini' => [
			'class' => 'CiAi\\Providers\\GeminiProvider',
			'api_key' => getenv('GEMINI_API_KEY'),
			'base_url' => 'https://generativelanguage.googleapis.com/v1beta',
			'model' => 'gemini-2.0-flash',
		],
	],
];
