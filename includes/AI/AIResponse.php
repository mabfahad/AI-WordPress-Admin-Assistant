<?php

namespace AIWordPressAssistant\AI;

defined( 'ABSPATH' ) || exit;

class AIResponse {

    /**
     * Constructor.
     *
     * @param string      $content        Response content.
     * @param string      $provider       Provider identifier.
     * @param string|null $model          Model identifier.
     * @param array       $rawResponse    Raw provider response.
     * @param array       $tool_calls     Requested tool calls.
     * @param string|null $interaction_id Provider interaction ID.
     */
    public function __construct(
        private readonly string $content,
        private readonly string $provider,
        private readonly ?string $model = null,
        private readonly array $rawResponse = [],
        private readonly array $tool_calls = [],
        private readonly ?string $interaction_id = null
    ) {
    }

    /**
     * Get response content.
     *
     * @return string
     */
    public function getContent(): string {
        return $this->content;
    }

    /**
     * Get provider identifier.
     *
     * @return string
     */
    public function getProvider(): string {
        return $this->provider;
    }

    /**
     * Get model identifier.
     *
     * @return string|null
     */
    public function getModel(): ?string {
        return $this->model;
    }

    /**
     * Get raw provider response.
     *
     * @return array
     */
    public function getRawResponse(): array {
        return $this->rawResponse;
    }

    /**
     * Get requested tool calls.
     *
     * @return array
     */
    public function getToolCalls(): array {
        return $this->tool_calls;
    }

    /**
     * Determine whether the AI requested tool execution.
     *
     * @return bool
     */
    public function hasToolCalls(): bool {
        return ! empty( $this->tool_calls );
    }

    /**
     * Get the provider interaction ID.
     *
     * This is used by providers such as Gemini to continue
     * an existing interaction after tool execution.
     *
     * @return string|null
     */
    public function getInteractionId(): ?string {
        return $this->interaction_id;
    }
}