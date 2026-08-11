<?php

namespace AIWordPressAssistant\AI;

defined( 'ABSPATH' ) || exit;

interface AIProviderInterface {

    /**
     * Send a chat request to the AI provider.
     *
     * @param array $messages Conversation messages.
     * @param array $options  Provider options.
     *
     * @return AIResponse
     */
    public function chat(
        array $messages,
        array $options = []
    ): AIResponse;
}
