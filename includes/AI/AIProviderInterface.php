<?php

namespace AIWordPressAssistant\AI;

defined( 'ABSPATH' ) || exit;

interface AIProviderInterface {

    /**
     * Send a chat request.
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

    /**
     * Get provider identifier.
     *
     * @return string
     */
    public function get_id(): string;

    /**
     * Get provider name.
     *
     * @return string
     */
    public function get_name(): string;
}