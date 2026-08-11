<?php

namespace AIWordPressAssistant\Tools;

defined( 'ABSPATH' ) || exit;

class GetPostsTool implements ToolInterface {

    public function get_name(): string {
        return 'get_posts';
    }

    public function get_description(): string {
        return 'Get information about WordPress posts, including counts by status.';
    }

    public function get_parameters(): array {
        return [
            'type'       => 'object',
            'properties' => [
                'status' => [
                    'type'        => 'string',
                    'description' => 'Post status to count.',
                    'enum'        => [
                        'publish',
                        'draft',
                        'pending',
                        'future',
                        'private',
                    ],
                ],
            ],
            'required' => [],
        ];
    }

    public function execute( array $arguments = [] ): array {

        $counts = wp_count_posts( 'post' );

        $status = $arguments['status'] ?? null;

        if ( $status ) {
            return [
                'status' => $status,
                'count'  => isset( $counts->{$status} )
                    ? (int) $counts->{$status}
                    : 0,
            ];
        }

        return [
            'publish' => (int) $counts->publish,
            'draft'   => (int) $counts->draft,
            'pending' => (int) $counts->pending,
            'future'  => (int) $counts->future,
            'private' => (int) $counts->private,
        ];
    }
}