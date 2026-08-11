<?php

namespace AIWordPressAssistant\REST;

use WP_REST_Request;
use WP_REST_Response;
use WP_Error;

defined( 'ABSPATH' ) || exit;

class AssistantController {

    /**
     * REST API namespace.
     */
    private const NAMESPACE = 'ai-assistant/v1';

    /**
     * Register REST routes.
     *
     * @return void
     */
    public function register_routes(): void {
        register_rest_route(
            self::NAMESPACE,
            '/chat',
            [
                'methods'             => 'POST',
                'callback'            => [ $this, 'chat' ],
                'permission_callback' => [ $this, 'permission_check' ],
                'args'                => [
                    'message' => [
                        'required'          => true,
                        'type'              => 'string',
                        'sanitize_callback' => 'sanitize_textarea_field',
                        'validate_callback' => function ( $value ) {
                            return is_string( $value ) && trim( $value ) !== '';
                        },
                    ],
                ],
            ]
        );
    }

    /**
     * Check user permissions.
     *
     * @return bool
     */
    public function permission_check(): bool {
        return current_user_can( 'manage_options' );
    }

    /**
     * Handle chat request.
     *
     * @param WP_REST_Request $request REST request.
     *
     * @return WP_REST_Response|WP_Error
     */
    public function chat( WP_REST_Request $request ) {
        $message = $request->get_param( 'message' );

        if ( empty( $message ) ) {
            return new WP_Error(
                'empty_message',
                __( 'Message cannot be empty.', 'ai-wordpress-admin-assistant' ),
                [ 'status' => 400 ]
            );
        }

        return new WP_REST_Response(
            [
                'success' => true,
                'message' => sprintf(
                    /* translators: %s: user message. */
                    __( 'I received your message: "%s"', 'ai-wordpress-admin-assistant' ),
                    $message
                ),
            ],
            200
        );
    }
}