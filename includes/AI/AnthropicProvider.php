<?php

namespace AIWordPressAssistant\AI;

defined( 'ABSPATH' ) || exit;

class AnthropicProvider implements AIProviderInterface {

    private const API_URL = 'https://api.anthropic.com/v1/messages';

    public function __construct(
        private readonly string $api_key
    ) {
    }

    public function get_id(): string {
        return 'anthropic';
    }

    public function get_name(): string {
        return 'Anthropic';
    }

    public function chat(
        array $messages,
        array $options = []
    ): AIResponse {

        if ( empty( $this->api_key ) ) {
            throw new \RuntimeException(
                'Anthropic API key is not configured.'
            );
        }

        $model = $options['model']
            ?? 'claude-sonnet-4-20250514';

        $system = '';

        $conversation = [];

        foreach ( $messages as $message ) {
            if ( 'system' === $message['role'] ) {
                $system = $message['content'];
                continue;
            }

            $conversation[] = [
                'role'    => $message['role'],
                'content' => $message['content'],
            ];
        }

        $body = [
            'model'      => $model,
            'max_tokens' => 2048,
            'messages'   => $conversation,
        ];

        if ( ! empty( $system ) ) {
            $body['system'] = $system;
        }

        $response = wp_remote_post(
            self::API_URL,
            [
                'timeout' => 60,
                'headers' => [
                    'x-api-key'         => $this->api_key,
                    'anthropic-version' => '2023-06-01',
                    'Content-Type'      => 'application/json',
                ],
                'body' => wp_json_encode( $body ),
            ]
        );

        if ( is_wp_error( $response ) ) {
            throw new \RuntimeException(
                $response->get_error_message()
            );
        }

        $status_code = wp_remote_retrieve_response_code( $response );
        $response_body = wp_remote_retrieve_body( $response );

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
                    ?? 'Anthropic API request failed.'
            );
        }

        $content = $data['content'][0]['text'] ?? null;

        if ( ! is_string( $content ) ) {
            throw new \RuntimeException(
                'Anthropic returned an invalid response.'
            );
        }

        return new AIResponse(
            content: $content,
            provider: 'anthropic',
            model: $data['model'] ?? $model,
            rawResponse: $data
        );
    }
}