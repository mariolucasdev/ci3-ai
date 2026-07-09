<?php
/**
 * CI3 AI - Autoloader PSR-4 simples (compatível com PHP 7.2).
 *
 * Mapeia o namespace CiAi\ para o diretório src/.
 *
 * @package CiAi
 * @author  Mário Lucas <mariolucasdev@gmail.com>
 * @license MIT
 */
spl_autoload_register(function ($class) {
	$prefix = 'CiAi\\';
	$len = strlen($prefix);

	if (strncmp($class, $prefix, $len) !== 0) {
		return;
	}

	$relative = substr($class, $len);
	$file = __DIR__ . '/src/' . str_replace('\\', '/', $relative) . '.php';

	if (is_file($file)) {
		require $file;
	}
});
