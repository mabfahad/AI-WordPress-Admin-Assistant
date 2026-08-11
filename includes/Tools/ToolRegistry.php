<?php

namespace AIWordPressAssistant\Tools;

defined( 'ABSPATH' ) || exit;

class ToolRegistry {

    /**
     * Registered tools.
     *
     * @var array<string, ToolInterface>
     */
    private array $tools = [];

    public function __construct() {
        $this->register(
            new GetPostsTool()
        );
    }

    /**
     * Register a tool.
     */
    public function register(
        ToolInterface $tool
    ): void {
        $this->tools[ $tool->get_name() ] = $tool;
    }

    /**
     * Check whether a tool exists.
     */
    public function has(
        string $name
    ): bool {
        return isset( $this->tools[ $name ] );
    }

    /**
     * Get a tool.
     */
    public function get(
        string $name
    ): ?ToolInterface {
        return $this->tools[ $name ] ?? null;
    }

    /**
     * Execute a tool.
     */
    public function execute(
        string $name,
        array $arguments = []
    ): array {

        $tool = $this->get( $name );

        if ( ! $tool ) {
            throw new \RuntimeException(
                sprintf(
                    'Unknown AI tool: %s',
                    $name
                )
            );
        }

        return $tool->execute( $arguments );
    }

    public function get_definitions(): array {

        $definitions = [];

        foreach ( $this->tools as $tool ) {
            $definitions[] = [
                'type'        => 'function',
                'name'        => $tool->get_name(),
                'description' => $tool->get_description(),
                'parameters'  => $tool->get_parameters(),
            ];
        }

        return $definitions;
    }

    /**
     * Get all registered tools.
     *
     * @return array<string, ToolInterface>
     */
    public function all(): array {
        return $this->tools;
    }
}