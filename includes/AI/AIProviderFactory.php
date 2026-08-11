<?php

namespace AIWordPressAssistant\AI;

use AIWordPressAssistant\Settings\Settings;

defined( 'ABSPATH' ) || exit;

class AIProviderFactory {

    private Settings $settings;

    public function __construct( Settings $settings ) {
        $this->settings = $settings;
    }

    /**
     * Create the configured AI provider.
     *
     * @return AIProviderInterface
     */
    public function make(): AIProviderInterface {
        $provider = $this->settings->get(
            'provider',
            'openai'
        );

        return match ( $provider ) {

            'anthropic' => new AnthropicProvider(
                $this->settings->get(
                    'anthropic_api_key'
                )
            ),

            'gemini' => new GeminiProvider(
                $this->settings->get(
                    'gemini_api_key'
                )
            ),

            default => new OpenAIProvider(
                $this->settings->get(
                    'openai_api_key'
                )
            ),
        };
    }
}