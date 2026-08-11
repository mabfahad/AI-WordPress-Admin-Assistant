<?php

namespace AIWordPressAssistant\AI;

defined( 'ABSPATH' ) || exit;

/**
 * Google Gemini AI provider.
 *
 * This implementation uses the Gemini Interactions API.
 *
 * The provider is responsible only for translating between
 * our internal AI interfaces and Gemini's API format.
 *
 * WordPress tool execution itself is handled by
 * AIOrchestrator and ToolRegistry.
 */
class GeminiProvider implements AIProviderInterface {

    /**
     * Gemini Interactions API endpoint.
     */
    private const API_URL =
        'https://generativelanguage.googleapis.com/v1/interactions';

    /**
     * Constructor.
     *
     * @param string $api_key Gemini API key.
     */
    public function __construct(
        private readonly string $api_key
    ) {
    }

    /**
     * Get provider identifier.
     *
     * @return string
     */
    public function get_id(): string {
        return 'gemini';
    }

    /**
     * Get provider name.
     *
     * @return string
     */
    public function get_name(): string {
        return 'Google Gemini';
    }

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
    ): AIResponse {

        /*
         * Make sure the Gemini API key has been configured.
         */
        if ( empty( $this->api_key ) ) {
            throw new \RuntimeException(
                'Gemini API key is not configured.'
            );
        }

        /*
         * Use the model supplied by the caller.
         *
         * Keeping the model configurable allows us to later
         * expose model selection in the WordPress dashboard.
         */
        $model = $options['model']
            ?? 'gemini-3.6-flash';

        /*
         * Separate the system instruction from the
         * normal conversation input.
         */
        $system_instruction = '';

        $input = [];

        foreach ( $messages as $message ) {

            /*
             * System messages use Gemini's dedicated
             * system_instruction property.
             */
            if ( 'system' === ( $message['role'] ?? '' ) ) {
                $system_instruction = $message['content'] ?? '';
                continue;
            }

            /*
             * Convert the conversation message into
             * the Interactions API format.
             */
            $input[] = [
                'role'    => $message['role'],
                'content' => [
                    [
                        'type' => 'text',
                        'text' => $message['content'],
                    ],
                ],
            ];
        }

        /*
         * For a single user request, we can simplify
         * the input to a plain string.
         *
         * The provider still supports multiple messages
         * for future conversation history.
         */
        if ( count( $input ) === 1 ) {
            $input = $input[0]['content'][0]['text'];
        }

        /*
         * Build the Gemini Interactions API request.
         *
         * IMPORTANT:
         *
         * We use store=true because our tool-calling flow
         * continues the interaction using
         * previous_interaction_id.
         */
        $body = [
            'model' => $model,
            'input' => $input,

            /*
             * Store the interaction so that Gemini can
             * continue it after WordPress tools execute.
             */
            'store' => true,
        ];

        /*
         * Add the system instruction when available.
         */
        if ( ! empty( $system_instruction ) ) {
            $body['system_instruction'] = $system_instruction;
        }

        /*
         * Pass our WordPress tools to Gemini.
         *
         * Gemini can then decide whether it needs to
         * call one of these tools.
         */
        if ( ! empty( $options['tools'] ) ) {
            $body['tools'] = $options['tools'];
        }

        /*
         * Add generation configuration when supplied.
         */
        if ( isset( $options['temperature'] ) ) {
            $body['generation_config'] = [
                'temperature' => (float) $options['temperature'],
            ];
        }

        /*
         * Send the request to Gemini.
         */
        $response = wp_remote_post(
            self::API_URL,
            [
                'timeout' => 60,
                'headers' => [
                    'x-goog-api-key' => $this->api_key,
                    'Content-Type'   => 'application/json',
                ],
                'body' => wp_json_encode( $body ),
            ]
        );

        /*
         * Handle WordPress HTTP errors.
         */
        if ( is_wp_error( $response ) ) {
            throw new \RuntimeException(
                $response->get_error_message()
            );
        }

        /*
         * Get the HTTP response status code.
         */
        $status_code = wp_remote_retrieve_response_code(
            $response
        );

        /*
         * Get the raw Gemini response body.
         */
        $response_body = wp_remote_retrieve_body(
            $response
        );

        /*
         * Decode Gemini's JSON response.
         */
        $data = json_decode(
            $response_body,
            true
        );

        /*
         * Log the raw Gemini response during development.
         *
         * This is useful when debugging provider responses
         * and function-calling behavior.
         */
        if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
            error_log(
                'AI WordPress Assistant - Gemini response: ' .
                print_r( $data, true )
            );
        }

        /*
         * Make sure Gemini returned valid JSON.
         */
        if ( ! is_array( $data ) ) {
            throw new \RuntimeException(
                'Gemini returned an invalid JSON response.'
            );
        }

        /*
         * Handle Gemini API errors.
         */
        if (
            $status_code < 200 ||
            $status_code >= 300
        ) {
            throw new \RuntimeException(
                $data['error']['message']
                    ?? 'Gemini API request failed.'
            );
        }

        /*
         * Gemini returns an interaction ID.
         *
         * We need this ID if the model requests a
         * WordPress tool and we need to continue the
         * interaction afterward.
         */
        $interaction_id = $data['id'] ?? null;

        /*
         * Extract any function calls requested
         * by Gemini.
         */
        $tool_calls = $this->extract_tool_calls(
            $data
        );

        /*
         * Extract normal text output.
         */
        $content = $this->extract_text(
            $data
        );

        /*
         * If Gemini requested a tool, an interaction ID
         * is mandatory because we need to continue the
         * same interaction after executing the tool.
         */
        if (
            ! empty( $tool_calls ) &&
            empty( $interaction_id )
        ) {
            throw new \RuntimeException(
                'Gemini returned tool calls without an interaction ID.'
            );
        }

        /*
         * Return a provider-independent response.
         */
        return new AIResponse(
            content: $content ?? '',
            provider: 'gemini',
            model: $data['model'] ?? $model,
            rawResponse: $data,
            tool_calls: $tool_calls,
            interaction_id: $interaction_id
        );
    }

    /**
     * Continue an existing Gemini interaction after
     * WordPress tools have been executed.
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
    ): AIResponse {

        /*
         * Make sure the Gemini API key has been configured.
         */
        if ( empty( $this->api_key ) ) {
            throw new \RuntimeException(
                'Gemini API key is not configured.'
            );
        }

        /*
         * Retrieve the interaction ID from the previous
         * Gemini response.
         */
        $interaction_id = $response->getInteractionId();

        if ( empty( $interaction_id ) ) {
            throw new \RuntimeException(
                'Gemini interaction ID is missing.'
            );
        }

        /*
         * Build the function_result input that will be
         * returned to Gemini.
         */
        $input = [];

        foreach ( $tool_results as $tool_result ) {

            /*
             * Only process our internal tool result objects.
             */
            if ( ! $tool_result instanceof AIToolResult ) {
                continue;
            }

            /*
             * Gemini uses call_id to associate the
             * function result with the original function call.
             */
            $input[] = [
                'type'    => 'function_result',
                'call_id' => $tool_result->get_call_id(),
                'name'    => $tool_result->get_name(),

                /*
                 * Keep the actual WordPress tool result
                 * as structured data.
                 */
                'result'  => $tool_result->get_result(),
            ];
        }

        /*
         * Make sure we actually have tool results.
         */
        if ( empty( $input ) ) {
            throw new \RuntimeException(
                'No valid Gemini tool results were provided.'
            );
        }

        /*
         * Build the continuation request.
         *
         * The previous interaction ID tells Gemini which
         * interaction contains the original function_call.
         */
        $body = [
            'model' => $response->getModel(),

            /*
             * Continue the existing interaction.
             */
            'previous_interaction_id' => $interaction_id,

            /*
             * Tools are interaction-scoped.
             *
             * Provide them again when continuing so Gemini
             * can request another tool if necessary.
             */
            'tools' => $options['tools'] ?? [],

            /*
             * Return the WordPress function results.
             */
            'input' => $input,

            /*
             * Keep storing the interaction because future
             * tool calls may require another continuation.
             */
            'store' => true,
        ];

        /*
         * Add generation configuration when supplied.
         */
        if ( isset( $options['temperature'] ) ) {
            $body['generation_config'] = [
                'temperature' => (float) $options['temperature'],
            ];
        }

        /*
         * Send the function results back to Gemini.
         */
        $api_response = wp_remote_post(
            self::API_URL,
            [
                'timeout' => 60,
                'headers' => [
                    'x-goog-api-key' => $this->api_key,
                    'Content-Type'   => 'application/json',
                ],
                'body' => wp_json_encode( $body ),
            ]
        );

        /*
         * Handle WordPress HTTP errors.
         */
        if ( is_wp_error( $api_response ) ) {
            throw new \RuntimeException(
                $api_response->get_error_message()
            );
        }

        /*
         * Get the HTTP status code.
         */
        $status_code = wp_remote_retrieve_response_code(
            $api_response
        );

        /*
         * Get the raw Gemini response body.
         */
        $response_body = wp_remote_retrieve_body(
            $api_response
        );

        /*
         * Decode Gemini's JSON response.
         */
        $data = json_decode(
            $response_body,
            true
        );

        /*
         * Log the continuation response during development.
         */
        if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
            error_log(
                'AI WordPress Assistant - Gemini tool response: ' .
                print_r( $data, true )
            );
        }

        /*
         * Make sure Gemini returned valid JSON.
         */
        if ( ! is_array( $data ) ) {
            throw new \RuntimeException(
                'Gemini returned an invalid JSON response.'
            );
        }

        /*
         * Handle Gemini API errors.
         */
        if (
            $status_code < 200 ||
            $status_code >= 300
        ) {
            throw new \RuntimeException(
                $data['error']['message']
                    ?? 'Gemini tool continuation failed.'
            );
        }

        /*
         * Gemini may request another tool after receiving
         * the previous tool result.
         *
         * Extract those calls so the orchestrator can
         * execute another iteration.
         */
        $tool_calls = $this->extract_tool_calls(
            $data
        );

        /*
         * Extract any text generated by Gemini.
         */
        $content = $this->extract_text(
            $data
        );

        /*
         * Get the new interaction ID.
         *
         * If Gemini doesn't return a new ID, keep using
         * the previous ID as a fallback.
         */
        $new_interaction_id = $data['id']
            ?? $interaction_id;

        /*
         * Return the updated provider-independent response.
         */
        return new AIResponse(
            content: $content ?? '',
            provider: 'gemini',
            model: $data['model'] ?? $response->getModel(),
            rawResponse: $data,
            tool_calls: $tool_calls,
            interaction_id: $new_interaction_id
        );
    }

    /**
     * Extract text from a Gemini Interactions API response.
     *
     * The Interactions API uses the "steps" response
     * structure.
     *
     * @param array $data API response.
     *
     * @return string|null
     */
    private function extract_text(
        array $data
    ): ?string {

        if ( empty( $data['steps'] ) ) {
            return null;
        }

        $text = '';

        foreach ( $data['steps'] as $step ) {

            /*
             * We only want model output steps.
             */
            if (
                'model_output' !== ( $step['type'] ?? '' )
            ) {
                continue;
            }

            /*
             * A model output step can contain multiple
             * content blocks.
             */
            foreach ( $step['content'] ?? [] as $content ) {

                /*
                 * Extract text content only.
                 */
                if (
                    'text' === ( $content['type'] ?? '' ) &&
                    isset( $content['text'] )
                ) {
                    $text .= $content['text'];
                }
            }
        }

        return '' !== $text ? $text : null;
    }

    /**
     * Extract function calls from a Gemini response.
     *
     * Gemini returns a "function_call" step when the model
     * wants the application to execute a registered tool.
     *
     * @param array $data API response.
     *
     * @return array
     */
    private function extract_tool_calls(
        array $data
    ): array {

        $tool_calls = [];

        foreach ( $data['steps'] ?? [] as $step ) {

            /*
             * Ignore normal model output and other step types.
             */
            if (
                'function_call' !== ( $step['type'] ?? '' )
            ) {
                continue;
            }

            /*
             * Gemini provides:
             *
             * - id        -> unique function call ID
             * - name      -> registered tool name
             * - arguments -> function arguments
             */
            $arguments = $step['arguments'] ?? [];

            /*
             * Some API versions or responses may return
             * arguments as a JSON string.
             *
             * Decode it safely if necessary.
             */
            if ( is_string( $arguments ) ) {

                $decoded_arguments = json_decode(
                    $arguments,
                    true
                );

                if ( is_array( $decoded_arguments ) ) {
                    $arguments = $decoded_arguments;
                } else {
                    /*
                     * Never pass malformed arguments to
                     * a WordPress tool.
                     */
                    $arguments = [];
                }
            }

            /*
             * Make sure arguments are always an array.
             */
            if ( ! is_array( $arguments ) ) {
                $arguments = [];
            }

            /*
             * Convert Gemini's function call into our
             * provider-independent AIToolCall object.
             */
            $tool_calls[] = new AIToolCall(
                id: (string) ( $step['id'] ?? '' ),
                name: (string) ( $step['name'] ?? '' ),
                arguments: $arguments
            );
        }

        return $tool_calls;
    }
}
