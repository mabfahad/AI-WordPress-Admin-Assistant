<?php

namespace AIWordPressAssistant\Tools;

defined( 'ABSPATH' ) || exit;

interface ToolInterface {

    /**
     * Get the unique tool name.
     */
    public function get_name(): string;

    /**
     * Get the tool description shown to the AI.
     */
    public function get_description(): string;

    /**
     * Get the JSON schema for tool arguments.
     */
    public function get_parameters(): array;

    /**
     * Execute the tool.
     *
     * @param array $arguments Tool arguments.
     *
     * @return array
     */
    public function execute( array $arguments = [] ): array;
}