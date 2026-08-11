<?php

namespace AIWordPressAssistant\AI;

defined( 'ABSPATH' ) || exit;

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

        if ( empty( $this->api_key ) ) {
            throw new \RuntimeException(
                'Gemini API key is not configured.'
            );
        }

        $model = $options['model']
            ?? 'gemini-3.6-flash';

        $system_instruction = '';

        $input = [];

        foreach ( $messages as $message ) {

            if ( 'system' === $message['role'] ) {
                $system_instruction = $message['content'];
                continue;
            }

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
         * For a single user request, we can simplify the input.
         * This also keeps the provider ready for multi-turn
         * conversations later.
         */
        if ( count( $input ) === 1 ) {
            $input = $input[0]['content'][0]['text'];
        }

        $body = [
            'model' => $model,
            'input' => $input,
            'store' => false,
        ];

        if ( ! empty( $system_instruction ) ) {
            $body['system_instruction'] = $system_instruction;
        }

        if ( isset( $options['temperature'] ) ) {
            $body['generation_config'] = [
                'temperature' => (float) $options['temperature'],
            ];
        }

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

        if ( is_wp_error( $response ) ) {
            throw new \RuntimeException(
                $response->get_error_message()
            );
        }

        $status_code = wp_remote_retrieve_response_code(
            $response
        );

        $response_body = wp_remote_retrieve_body(
            $response
        );

        $data = json_decode(
            $response_body,
            true
        );

        if (
            $status_code < 200 ||
            $status_code >= 300
        ) {
            throw new \RuntimeException(
                $data['error']['message']
                    ?? 'Gemini API request failed.'
            );
        }

        $content = $this->extract_text(
            $data
        );

        if ( null === $content ) {
            throw new \RuntimeException(
                'Gemini returned an invalid response.'
            );
        }

        return new AIResponse(
            content: $content,
            provider: 'gemini',
            model: $data['model'] ?? $model,
            rawResponse: $data
        );
    }

    /**
     * Extract text from an Interactions API response.
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

            if (
                'model_output' !== ( $step['type'] ?? '' )
            ) {
                continue;
            }

            foreach ( $step['content'] ?? [] as $content ) {

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

    private function extract_tool_calls(
        array $data
    ): array {

        $tool_calls = [];

        foreach ( $data['steps'] ?? [] as $step ) {

            if (
                'function_call' !== ( $step['type'] ?? '' )
            ) {
                continue;
            }

            $tool_calls[] = new AIToolCall(
                id: (string) ( $step['id'] ?? '' ),
                name: (string) ( $step['name'] ?? '' ),
                arguments: is_array( $step['arguments'] ?? null )
                    ? $step['arguments']
                    : []
            );
        }

        return $tool_calls;
    }
}