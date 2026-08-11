<?php

namespace AIWordPressAssistant\AI;

defined( 'ABSPATH' ) || exit;

class AIToolCall {

    public function __construct(
        private readonly string $id,
        private readonly string $name,
        private readonly array $arguments
    ) {
    }

    public function get_id(): string {
        return $this->id;
    }

    public function get_name(): string {
        return $this->name;
    }

    public function get_arguments(): array {
        return $this->arguments;
    }
}