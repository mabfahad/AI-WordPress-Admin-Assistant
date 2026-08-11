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

    /**
     * Continue an AI interaction after executing tools.
     *
     * This allows the provider to receive the results of
     * WordPress tools and generate the final response.
     *
     * @param AIResponse $response     Previous AI response.
     * @param array      $tool_results Executed tool results.
     * @param array      $options      Provider options.
     *
     * @return AIResponse
     */
    public function continue_with_tool_results(
        AIResponse $response,
        array $tool_results,
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