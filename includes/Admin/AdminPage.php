<?php

namespace AIWordPressAssistant\Admin;

defined( 'ABSPATH' ) || exit;

class AdminPage {

    /**
     * Render the assistant page.
     *
     * @return void
     */
    public function render(): void {
        ?>
        <div class="wrap ai-wp-assistant">
            <div class="ai-wp-assistant__header">
                <h1>
                    <?php
                    esc_html_e(
                        'AI WordPress Assistant',
                        'ai-wordpress-admin-assistant'
                    );
                    ?>
                </h1>

                <p>
                    <?php
                    esc_html_e(
                        'Ask questions about your WordPress site.',
                        'ai-wordpress-admin-assistant'
                    );
                    ?>
                </p>
            </div>

            <div class="ai-wp-assistant__chat">
                <div
                    id="ai-wp-assistant-messages"
                    class="ai-wp-assistant__messages"
                >
                    <div class="ai-wp-assistant__message ai-wp-assistant__message--assistant">
                        <strong>Assistant:</strong>

                        <p>
                            <?php
                            esc_html_e(
                                'Hello! How can I help you with your WordPress site?',
                                'ai-wordpress-admin-assistant'
                            );
                            ?>
                        </p>
                    </div>
                </div>

                <form id="ai-wp-assistant-form" class="ai-wp-assistant__form">
                    <textarea
                        id="ai-wp-assistant-input"
                        name="message"
                        rows="3"
                        placeholder="<?php esc_attr_e( 'Ask something...', 'ai-wordpress-admin-assistant' ); ?>"
                    ></textarea>

                    <button
                        type="submit"
                        class="button button-primary"
                    >
                        <?php esc_html_e( 'Send', 'ai-wordpress-admin-assistant' ); ?>
                    </button>
                </form>
            </div>
        </div>
        <?php
    }
}