<?php

namespace AIWordPressAssistant\AI;

defined( 'ABSPATH' ) || exit;

/**
 * Represents the result returned by a WordPress tool.
 *
 * The provider-specific implementation converts this
 * into the format required by Gemini, OpenAI, Claude, etc.
 */
class AIToolResult {

    /**
     * Constructor.
     *
     * @param string $call_id ID of the original tool call.
     * @param string $name    Tool name.
     * @param array  $result  Tool execution result.
     */
    public function __construct(
        private readonly string $call_id,
        private readonly string $name,
        private readonly array $result
    ) {
    }

    /**
     * Get the tool call ID.
     *
     * @return string
     */
    public function get_call_id(): string {
        return $this->call_id;
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
     * Get the tool execution result.
     *
     * @return array
     */
    public function get_result(): array {
        return $this->result;
    }
}