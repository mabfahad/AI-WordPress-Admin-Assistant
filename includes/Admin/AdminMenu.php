<?php

namespace AIWordPressAssistant\Admin;

defined( 'ABSPATH' ) || exit;

class AdminMenu {

    /**
     * Register admin menu.
     *
     * @return void
     */
    public function register(): void {
        add_menu_page(
            __( 'AI Assistant', 'ai-wordpress-admin-assistant' ),
            __( 'AI Assistant', 'ai-wordpress-admin-assistant' ),
            'manage_options',
            'ai-wordpress-admin-assistant',
            [ $this, 'render' ],
            'dashicons-format-chat',
            30
        );
    }

    /**
     * Render admin page.
     *
     * @return void
     */
    public function render(): void {
        ( new AdminPage() )->render();
    }
}