<?php

namespace AIWordPressAssistant\AI;

defined( 'ABSPATH' ) || exit;

class AIResponse {

    /**
     * @param string $content      Response content.
     * @param string $provider     Provider name.
     * @param string|null $model   Model name.
     * @param array  $rawResponse  Raw provider response.
     */
    public function __construct(
        private readonly string $content,
        private readonly string $provider,
        private readonly ?string $model = null,
        private readonly array $rawResponse = []
    ) {
    }

    /**
     * Get response content.
     */
    public function getContent(): string {
        return $this->content;
    }

    /**
     * Get provider.
     */
    public function getProvider(): string {
        return $this->provider;
    }

    /**
     * Get model.
     */
    public function getModel(): ?string {
        return $this->model;
    }

    /**
     * Get raw response.
     */
    public function getRawResponse(): array {
        return $this->rawResponse;
    }
}
