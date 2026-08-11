<?php

namespace AIWordPressAssistant\REST;

use WP_REST_Request;
use WP_REST_Response;
use WP_Error;
use AIWordPressAssistant\AI\AIOrchestrator;

defined( 'ABSPATH' ) || exit;

class AssistantController {

    /**
     * REST API namespace.
     */
    private const NAMESPACE = 'ai-assistant/v1';
    private AIOrchestrator $orchestrator;

    public function __construct(AIOrchestrator $orchestrator) {
        $this->orchestrator = $orchestrator;
    }

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

        try {
            $response = $this->orchestrator->respond( $message );

            return new WP_REST_Response(
                [
                    'success' => true,
                    'message' => $response->getContent(),
                    'provider' => $response->getProvider(),
                    'model'    => $response->getModel(),
                ],
                200
            );
        } catch ( \Throwable $exception ) {
            return new WP_Error(
                'ai_assistant_error',
                $exception->getMessage(),
                [
                    'status' => 500,
                ]
            );
        }
    }
}