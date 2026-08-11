<?php

namespace AIWordPressAssistant\Tools;

defined( 'ABSPATH' ) || exit;

/**
 * Get information about installed WordPress plugins.
 *
 * This is a read-only tool.
 *
 * The AI can use this tool to answer questions about:
 *
 * - Installed plugins
 * - Active plugins
 * - Inactive plugins
 * - Plugin versions
 * - Plugin authors
 */
class GetPluginsTool implements ToolInterface {

    /**
     * Get the tool identifier.
     *
     * @return string
     */
    public function get_name(): string {
        return 'get_plugins';
    }

    /**
     * Get the tool description.
     *
     * This description is provided to the AI model so
     * it knows when this tool should be used.
     *
     * @return string
     */
    public function get_description(): string {
        return 'Get information about installed WordPress plugins, including their names, versions, authors, and whether they are active or inactive.';
    }

    /**
     * Get the JSON schema for tool arguments.
     *
     * This tool does not require arguments.
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
         * WordPress does not load the plugin administration
         * functions on every request.
         *
         * Make sure get_plugins() is available.
         */
        if ( ! function_exists( 'get_plugins' ) ) {
            require_once ABSPATH . 'wp-admin/includes/plugin.php';
        }

        /*
         * Get all installed plugins.
         *
         * The returned array is keyed by the plugin's
         * relative plugin file.
         */
        $plugins = get_plugins();

        /*
         * Get the list of currently active plugins.
         */
        $active_plugins = get_option(
            'active_plugins',
            []
        );

        /*
         * Normalize the active plugin list.
         */
        if ( ! is_array( $active_plugins ) ) {
            $active_plugins = [];
        }

        /*
         * Prepare the structured plugin list.
         */
        $plugin_list = [];

        foreach ( $plugins as $plugin_file => $plugin_data ) {

            /*
             * Determine whether this plugin is currently
             * active.
             */
            $is_active = in_array(
                $plugin_file,
                $active_plugins,
                true
            );

            /*
             * Return only the information that is useful
             * to the AI.
             */
            $plugin_list[] = [
                'file'        => $plugin_file,
                'name'        => $plugin_data['Name'] ?? '',
                'version'     => $plugin_data['Version'] ?? '',
                'description' => $plugin_data['Description'] ?? '',
                'author'      => $plugin_data['Author'] ?? '',
                'plugin_uri'  => $plugin_data['PluginURI'] ?? '',
                'active'      => $is_active,
            ];
        }

        /*
         * Count active and inactive plugins.
         */
        $active_count = 0;

        foreach ( $plugin_list as $plugin ) {

            if ( ! empty( $plugin['active'] ) ) {
                $active_count++;
            }
        }

        /*
         * Calculate the inactive plugin count.
         */
        $inactive_count = count( $plugin_list ) - $active_count;

        /*
         * Return structured data.
         *
         * The tool should not generate a human-readable
         * response. Gemini will decide how to present
         * this information to the user.
         */
        return [
            'total'    => count( $plugin_list ),
            'active'   => $active_count,
            'inactive' => $inactive_count,
            'plugins'  => $plugin_list,
        ];
    }
}
