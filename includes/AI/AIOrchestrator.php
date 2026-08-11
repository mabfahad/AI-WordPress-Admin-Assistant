<?php

namespace AIWordPressAssistant\AI;

use AIWordPressAssistant\Tools\ToolRegistry;

defined( 'ABSPATH' ) || exit;

/**
 * Coordinates communication between the AI provider
 * and the WordPress tool system.
 *
 * The orchestrator is intentionally provider-agnostic.
 *
 * It does not know how Gemini, Claude, OpenAI, etc.
 * implement tool calling internally.
 *
 * Its job is to:
 *
 * 1. Send the user's request to the AI provider.
 * 2. Detect requested WordPress tools.
 * 3. Execute those tools.
 * 4. Send the results back to the AI provider.
 * 5. Return the final AI response.
 */
class AIOrchestrator {

    /**
     * AI provider.
     */
    private AIProviderInterface $provider;

    /**
     * WordPress tool registry.
     */
    private ToolRegistry $tools;

    /**
     * Constructor.
     *
     * @param AIProviderInterface $provider AI provider.
     * @param ToolRegistry         $tools    WordPress tools.
     */
    public function __construct(
        AIProviderInterface $provider,
        ToolRegistry $tools
    ) {
        $this->provider = $provider;
        $this->tools    = $tools;
    }

    /**
     * Generate an assistant response.
     *
     * The AI may request one or more WordPress tools.
     * Those tools are executed and their results are sent
     * back to the provider until a final response is produced.
     *
     * @param string $message User message.
     *
     * @return AIResponse
     */
    public function respond( string $message ): AIResponse {

        /*
         * Build the initial conversation.
         */
        $messages = [
            [
                'role'    => 'system',
                'content' => $this->get_system_prompt(),
            ],
            [
                'role'    => 'user',
                'content' => $message,
            ],
        ];

        /*
         * Send the user's request to the AI provider.
         *
         * We also provide all registered WordPress tools
         * so the AI knows which operations are available.
         */
        $response = $this->provider->chat(
            $messages,
            [
                'tools' => $this->tools->get_definitions(),

                /*
                 * Use a deterministic temperature while
                 * developing the tool-calling system.
                 *
                 * This reduces the likelihood of malformed
                 * function arguments.
                 */
                'temperature' => 0,
            ]
        );

        /*
         * Limit the number of tool-calling iterations.
         *
         * This protects the plugin against an accidental
         * infinite loop where the AI continuously requests
         * additional tools.
         */
        $max_iterations = 5;

        for ( $i = 0; $i < $max_iterations; $i++ ) {

            /*
             * If the AI did not request any tools,
             * we already have the final response.
             */
            if ( ! $response->hasToolCalls() ) {
                return $response;
            }

            /*
             * Store the results of all requested tool calls.
             */
            $tool_results = [];

            foreach ( $response->getToolCalls() as $tool_call ) {

                /*
                 * Make sure the returned object is actually
                 * an AIToolCall instance.
                 */
                if ( ! $tool_call instanceof AIToolCall ) {
                    continue;
                }

                /*
                 * Get the tool name from the AI tool call.
                 */
                $tool_name = $tool_call->get_name();

                /*
                 * Never allow the AI to execute arbitrary
                 * WordPress functions.
                 *
                 * Only tools explicitly registered in
                 * ToolRegistry can be executed.
                 */
                if ( ! $this->tools->has( $tool_name ) ) {
                    throw new \RuntimeException(
                        sprintf(
                            'AI requested an unknown tool: %s',
                            $tool_name
                        )
                    );
                }

                /*
                 * Get the arguments supplied by the AI.
                 */
                $arguments = $tool_call->get_arguments();

                /*
                 * Execute the registered WordPress tool.
                 */
                $result = $this->tools->execute(
                    $tool_name,
                    $arguments
                );

                /*
                 * Convert the WordPress result into our
                 * provider-independent tool result object.
                 */
                $tool_results[] = new AIToolResult(
                    call_id: $tool_call->get_id(),
                    name: $tool_name,
                    result: $result
                );
            }

            /*
             * Send the tool results back to the AI provider.
             *
             * The provider is responsible for converting
             * these results into its own API-specific format.
             */
            $response = $this->provider
                ->continue_with_tool_results(
                    $response,
                    $tool_results,
                    [
                        'tools' => $this->tools->get_definitions(),

                        /*
                         * Keep the continuation deterministic
                         * while developing the agent.
                         */
                        'temperature' => 0,
                    ]
                );
        }

        /*
         * If the maximum number of iterations is reached,
         * stop rather than allowing an endless tool loop.
         */
        throw new \RuntimeException(
            'AI tool execution exceeded the maximum number of iterations.'
        );
    }

    /**
     * Get the assistant system prompt.
     *
     * @return string
     */
    private function get_system_prompt(): string {
        return <<<PROMPT
You are an AI assistant for WordPress administrators.

Your job is to help administrators understand and manage
their WordPress websites.

You have access to tools that can retrieve information
from the current WordPress site.

When a user's question requires information about the
WordPress site, use the appropriate tool instead of
guessing or providing generic instructions.

Use tools when actual WordPress site data is required.

Be concise, accurate, and practical.

Never claim that you performed an action unless the system
has actually performed that action.

Never invent WordPress site data.

If a tool returns no data, clearly say that no data was found.

PROMPT;
    }
}