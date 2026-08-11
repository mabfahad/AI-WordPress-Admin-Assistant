<?php

namespace AIWordPressAssistant\AI;

defined( 'ABSPATH' ) || exit;

/**
 * Represents a tool call requested by an AI provider.
 *
 * The AI provider creates this object when the model
 * decides that it needs to execute a registered WordPress
 * tool.
 */
class AIToolCall {

    /**
     * Constructor.
     *
     * @param string $id        Tool call ID.
     * @param string $name      Tool name.
     * @param array  $arguments Tool arguments.
     */
    public function __construct(
        private readonly string $id,
        private readonly string $name,
        private readonly array $arguments
    ) {
    }

    /**
     * Get the tool call ID.
     *
     * @return string
     */
    public function get_id(): string {
        return $this->id;
    }

    /**
     * Get the tool name.
     *
     * @return string
     */
    public function get_name(): string {
        return $this->name;
    }

    /**
     * Get the tool arguments.
     *
     * @return array
     */
    public function get_arguments(): array {
        return $this->arguments;
    }
}