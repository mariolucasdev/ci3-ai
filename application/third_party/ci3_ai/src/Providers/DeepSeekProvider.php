<?php
/**
 * CI3 AI - Pacote de IA para CodeIgniter 3.x
 *
 * @package CiAi
 * @author  Mário Lucas <mariolucasdev@gmail.com>
 * @license MIT
 */
namespace CiAi\Providers;

/**
 * Provedor DeepSeek. A API é compatível com o formato OpenAI,
 * mudando apenas base_url e modelos (deepseek-chat, deepseek-reasoner).
 */
class DeepSeekProvider extends OpenAiProvider
{
    public function name()
    {
        return 'deepseek';
    }
}
