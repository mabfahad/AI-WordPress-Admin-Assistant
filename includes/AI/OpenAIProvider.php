<?php

namespace AIWordPressAssistant\AI;

use WP_Error;

defined( 'ABSPATH' ) || exit;

class OpenAIProvider implements AIProviderInterface {

    /**
     * OpenAI API endpoint.
     */
    private const API_URL = 'https://api.openai.com/v1/chat/completions';

    /**
     * API key.
     */
    private string $api_key;

    /**
     * Constructor.
     *
     * @param string $api_key OpenAI API key.
     */
    public function __construct( string $api_key ) {
        $this->api_key = trim( $api_key );
    }

    /**
     * Send a chat request.
     *
     * @param array $messages Conversation messages.
     * @param array $options  Provider options.
     *
     * @return AIResponse
     *
     * @throws \RuntimeException When the API request fails.
     */
    public function chat(
        array $messages,
        array $options = []
    ): AIResponse {

        if ( empty( $this->api_key ) ) {
            throw new \RuntimeException(
                'OpenAI API key is not configured.'
            );
        }

        $model = $options['model'] ?? 'gpt-4.1-mini';

        $body = [
            'model'    => $model,
            'messages' => $messages,
        ];

        if ( isset( $options['temperature'] ) ) {
            $body['temperature'] = (float) $options['temperature'];
        }

        $response = wp_remote_post(
            self::API_URL,
            [
                'timeout' => 60,
                'headers' => [
                    'Authorization' => 'Bearer ' . $this->api_key,
                    'Content-Type'  => 'application/json',
                ],
                'body'    => wp_json_encode( $body ),
            ]
        );

        if ( is_wp_error( $response ) ) {
            throw new \RuntimeException(
                $response->get_error_message()
            );
        }

        $status_code = wp_remote_retrieve_response_code( $response );
        $response_body = wp_remote_retrieve_body( $response );

        $data = json_decode( $response_body, true );

        if ( $status_code < 200 || $status_code >= 300 ) {
            $error_message = $data['error']['message']
                ?? 'OpenAI API request failed.';

            throw new \RuntimeException(
                $error_message
            );
        }

        $content = $data['choices'][0]['message']['content'] ?? null;

        if ( ! is_string( $content ) ) {
            throw new \RuntimeException(
                'OpenAI returned an invalid response.'
            );
        }

        return new AIResponse(
            content: $content,
            provider: 'openai',
            model: $data['model'] ?? $model,
            rawResponse: $data
        );
    }

    public function get_id(): string {
        return 'openai';
    }

    public function get_name(): string {
        return 'OpenAI';
    }
}
