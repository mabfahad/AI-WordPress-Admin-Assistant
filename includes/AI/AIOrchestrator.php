<?php

namespace AIWordPressAssistant\AI;

use AIWordPressAssistant\Tools\ToolRegistry;

defined( 'ABSPATH' ) || exit;

class AIOrchestrator {

    /**
     * AI provider.
     */
    private AIProviderInterface $provider;

    /**
     * WordPress tool registry.
     */
    private ToolRegistry $tools;

    /**
     * Constructor.
     *
     * @param AIProviderInterface $provider AI provider.
     * @param ToolRegistry         $tools    WordPress tools.
     */
    public function __construct(
        AIProviderInterface $provider,
        ToolRegistry $tools
    ) {
        $this->provider = $provider;
        $this->tools    = $tools;
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
                'tools' => $this->tools->get_definitions(),
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

You have access to WordPress tools that can retrieve
information from the current WordPress site.

When a user's question requires information about the
WordPress site, use the appropriate tool instead of
guessing or providing generic instructions.

Be concise, accurate, and practical.

Never claim that you performed an action unless the system
actually performed that action.

Never invent WordPress site data.
PROMPT;
    }
}