<?php
/**
 * CI3 AI - Configuração do PHP-CS-Fixer.
 *
 * Aplica-se apenas ao código do pacote (third_party/ci3_ai, library, config
 * e controllers próprios) — nunca ao core do CodeIgniter (system/).
 *
 * O código-alvo deve permanecer compatível com PHP 7.2: não habilitar
 * regras que introduzam sintaxe 7.4+ (arrow functions, typed properties...).
 *
 * @author  Mário Lucas <mariolucasdev@gmail.com>
 * @license MIT
 */

$finder = PhpCsFixer\Finder::create()
	->in(__DIR__ . '/application/third_party/ci3_ai')
	->append([
		__DIR__ . '/application/libraries/Ai.php',
		__DIR__ . '/application/controllers/Ai_demo.php',
		__DIR__ . '/application/config/ai.php',
	]);

return (new PhpCsFixer\Config())
	->setRiskyAllowed(false)
	->setIndent("\t")
	->setLineEnding("\n")
	->setRules([
		// O motivo deste fixer: arrays sempre com []
		'array_syntax' => ['syntax' => 'short'],

		// Higiene geral (segura para PHP 7.2)
		'no_unused_imports' => true,
		'ordered_imports' => ['sort_algorithm' => 'alpha'],
		'single_quote' => true,
		'no_trailing_whitespace' => true,
		'no_trailing_whitespace_in_comment' => true,
		'no_whitespace_in_blank_line' => true,
		'single_blank_line_at_eof' => true,
		'blank_line_after_namespace' => true,
		'elseif' => true,
		'lowercase_keywords' => true,
		'constant_case' => ['case' => 'lower'],
		'trailing_comma_in_multiline' => ['elements' => ['arrays']],
		'whitespace_after_comma_in_array' => true,
		'no_leading_import_slash' => true,
		'binary_operator_spaces' => ['default' => 'single_space'],
	])
	->setFinder($finder);
