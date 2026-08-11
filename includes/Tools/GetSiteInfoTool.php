<?php

namespace AIWordPressAssistant\Tools;

defined( 'ABSPATH' ) || exit;

/**
 * Get information about the current WordPress installation.
 *
 * This is a read-only tool.
 *
 * The AI can use this tool to answer questions about:
 *
 * - WordPress version
 * - PHP version
 * - Database version
 * - Site URL
 * - Home URL
 * - Multisite status
 * - Active theme
 * - Debug mode
 */
class GetSiteInfoTool implements ToolInterface {

    /**
     * Get the tool identifier.
     *
     * @return string
     */
    public function get_name(): string {
        return 'get_site_info';
    }

    /**
     * Get the tool description.
     *
     * This description is sent to the AI provider so the
     * model knows when this tool should be used.
     *
     * @return string
     */
    public function get_description(): string {
        return 'Get information about the current WordPress installation, including WordPress version, PHP version, database version, site URLs, multisite status, active theme, and debug mode.';
    }

    /**
     * Get the JSON schema for tool arguments.
     *
     * This tool does not require any arguments.
     *
     * @return array
     */
    public function get_parameters(): array {
        return [
            'type'       => 'object',
            'properties' => [],
        ];
    }

    /**
     * Execute the tool.
     *
     * @param array $arguments Tool arguments.
     *
     * @return array
     */
    public function execute( array $arguments = [] ): array {

        /*
         * Get the currently active theme.
         */
        $theme = wp_get_theme();

        /*
         * Get the database server version.
         *
         * WordPress exposes the database version through
         * the global wpdb object.
         */
        global $wpdb;

        $database_version = $wpdb->db_version();

        /*
         * Return structured data.
         *
         * Returning structured data instead of a formatted
         * sentence allows the AI provider to decide how
         * the information should be presented to the user.
         */
        return [
            'wordpress_version' => get_bloginfo( 'version' ),

            'php_version' => PHP_VERSION,

            'database_version' => $database_version,

            'site_url' => site_url(),

            'home_url' => home_url(),

            'is_multisite' => is_multisite(),

            'active_theme' => [
                'name'    => $theme->get( 'Name' ),
                'version' => $theme->get( 'Version' ),
                'author'  => $theme->get( 'Author' ),
            ],

            'debug_mode' => defined( 'WP_DEBUG' )
                && WP_DEBUG,
        ];
    }
}
