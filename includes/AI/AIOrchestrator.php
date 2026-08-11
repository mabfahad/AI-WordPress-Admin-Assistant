<?php

namespace AIWordPressAssistant\AI;

defined( 'ABSPATH' ) || exit;

class AIOrchestrator {

    /**
     * AI provider.
     */
    private AIProviderInterface $provider;

    /**
     * Constructor.
     *
     * @param AIProviderInterface $provider AI provider.
     */
    public function __construct(
        AIProviderInterface $provider
    ) {
        $this->provider = $provider;
    }

    /**
     * Generate an assistant response.
     *
     * @param string $message User message.
     *
     * @return AIResponse
     */
    public function respond( string $message ): AIResponse {
        $messages = [
            [
                'role'    => 'system',
                'content' => $this->get_system_prompt(),
            ],
            [
                'role'    => 'user',
                'content' => $message,
            ],
        ];

        return $this->provider->chat(
            $messages,
            [
                'model' => 'gpt-4.1-mini',
            ]
        );
    }

    /**
     * Get the assistant system prompt.
     *
     * @return string
     */
    private function get_system_prompt(): string {
        return <<<PROMPT
You are an AI assistant for WordPress administrators.

Your job is to help administrators understand and manage
their WordPress websites.

Be concise, accurate, and practical.

Do not claim to have performed an action unless the system
has actually performed that action.

At this stage, you do not have access to WordPress site data.
PROMPT;
    }
}
