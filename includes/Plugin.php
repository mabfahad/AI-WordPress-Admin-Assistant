<?php

namespace AIWordPressAssistant;

use AIWordPressAssistant\Admin\AdminMenu;
use AIWordPressAssistant\REST\AssistantController;
use AIWordPressAssistant\AI\AIOrchestrator;
use AIWordPressAssistant\AI\OpenAIProvider;
use AIWordPressAssistant\AI\AIProviderFactory;
use AIWordPressAssistant\Settings\Settings;
use AIWordPressAssistant\Settings\SettingsPage;
use AIWordPressAssistant\Tools\ToolRegistry;

defined( 'ABSPATH' ) || exit;

class Plugin {

    /**
     * Initialize the plugin.
     *
     * @return void
     */

    private AIOrchestrator $ai_orchestrator;
    private Settings $settings;
    private ToolRegistry $tool_registry;
    public function init(): void {
        $this->settings = new Settings();
        $this->initialize_ai();
        $this->register_admin();
        $this->register_settings();
        $this->register_rest_api();
    }

    /**
     * Register admin functionality.
     *
     * @return void
     */
    private function register_admin(): void {
        $admin_menu = new AdminMenu();

        add_action(
            'admin_menu',
            [ $admin_menu, 'register' ]
        );

        add_action(
            'admin_enqueue_scripts',
            [ $this, 'enqueue_admin_assets' ]
        );
    }

    /**
     * Register REST API.
     *
     * @return void
     */
    private function register_rest_api(): void {
        $controller = new AssistantController($this->ai_orchestrator);

        add_action(
            'rest_api_init',
            [ $controller, 'register_routes' ]
        );
    }

    /**
     * Enqueue admin assets.
     *
     * @param string $hook_suffix Current admin page.
     *
     * @return void
     */
    public function enqueue_admin_assets( string $hook_suffix ): void {
        if ( 'toplevel_page_ai-wordpress-admin-assistant' !== $hook_suffix ) {
            return;
        }

        wp_enqueue_style(
            'ai-wp-assistant-admin',
            AI_WP_ASSISTANT_URL . 'assets/css/admin.css',
            [],
            AI_WP_ASSISTANT_VERSION
        );

        wp_enqueue_script(
            'ai-wp-assistant-admin',
            AI_WP_ASSISTANT_URL . 'assets/js/admin.js',
            [],
            AI_WP_ASSISTANT_VERSION,
            true
        );

        wp_localize_script(
            'ai-wp-assistant-admin',
            'AIWPAssistant',
            [
                'restUrl' => esc_url_raw(
                    rest_url( 'ai-assistant/v1/chat' )
                ),
                'nonce'   => wp_create_nonce( 'wp_rest' ),
                'i18n'    => [
                    'error' => __(
                        'Something went wrong. Please try again.',
                        'ai-wordpress-admin-assistant'
                    ),
                ],
            ]
        );
    }

    private function initialize_ai(): void {

        $this->tool_registry = new ToolRegistry();

        $factory = new AIProviderFactory(
            $this->settings
        );

        $provider = $factory->make();

        $this->ai_orchestrator = new AIOrchestrator(
            $provider,
            $this->tool_registry
        );
    }

    private function register_settings(): void {
        $settings_page = new SettingsPage(
            $this->settings
        );

        add_action(
            'admin_init',
            [ $settings_page, 'register' ]
        );

        add_action(
            'admin_menu',
            function () use ( $settings_page ) {
                add_submenu_page(
                    'ai-wordpress-admin-assistant',
                    __( 'Settings', 'ai-wordpress-admin-assistant' ),
                    __( 'Settings', 'ai-wordpress-admin-assistant' ),
                    'manage_options',
                    'ai-wordpress-admin-assistant-settings',
                    [ $settings_page, 'render' ]
                );
            }
        );
    }
}