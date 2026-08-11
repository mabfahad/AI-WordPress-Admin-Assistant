<?php

namespace AIWordPressAssistant\Settings;

defined( 'ABSPATH' ) || exit;

class SettingsPage {

    private Settings $settings;

    public function __construct( Settings $settings ) {
        $this->settings = $settings;
    }

    /**
     * Register settings.
     *
     * @return void
     */
    public function register(): void {
        register_setting(
            'ai_wp_assistant_settings',
            'ai_wp_assistant_settings',
            [
                'sanitize_callback' => [ $this, 'sanitize' ],
            ]
        );
    }

    /**
     * Sanitize settings.
     *
     * @param array $input Settings input.
     *
     * @return array
     */
    public function sanitize( array $input ): array {
        $current = $this->settings->all();

        $output = [
            'provider' => sanitize_key(
                $input['provider'] ?? 'openai'
            ),

            'openai_api_key' => $this->sanitize_api_key(
                $input['openai_api_key'] ?? $current['openai_api_key']
            ),

            'openai_model' => sanitize_text_field(
                $input['openai_model'] ?? 'gpt-4.1-mini'
            ),

            'anthropic_api_key' => $this->sanitize_api_key(
                $input['anthropic_api_key'] ?? $current['anthropic_api_key']
            ),

            'anthropic_model' => sanitize_text_field(
                $input['anthropic_model'] ?? 'claude-sonnet-4-20250514'
            ),

            'gemini_api_key' => $this->sanitize_api_key(
                $input['gemini_api_key'] ?? $current['gemini_api_key']
            ),

            'gemini_model' => sanitize_text_field(
                $input['gemini_model'] ?? 'gemini-2.5-flash'
            ),

            'temperature' => max(
                0,
                min(
                    2,
                    (float) ( $input['temperature'] ?? 0.2 )
                )
            ),
        ];

        if ( ! array_key_exists( $output['provider'], $this->settings->get_providers() ) ) {
            $output['provider'] = 'openai';
        }

        return $output;
    }

    /**
     * Sanitize API key.
     *
     * @param string $key API key.
     *
     * @return string
     */
    private function sanitize_api_key( string $key ): string {
        return trim( sanitize_text_field( $key ) );
    }

    /**
     * Render settings page.
     *
     * @return void
     */
    public function render(): void {
        $settings = $this->settings->all();
        ?>
        <div class="wrap">
            <h1>
                <?php
                esc_html_e(
                    'AI Assistant Settings',
                    'ai-wordpress-admin-assistant'
                );
                ?>
            </h1>

            <form method="post" action="options.php">
                <?php
                settings_fields( 'ai_wp_assistant_settings' );
                ?>

                <table class="form-table" role="presentation">

                    <tr>
                        <th scope="row">
                            <label for="ai-provider">
                                <?php esc_html_e( 'AI Provider', 'ai-wordpress-admin-assistant' ); ?>
                            </label>
                        </th>

                        <td>
                            <select
                                id="ai-provider"
                                name="ai_wp_assistant_settings[provider]"
                            >
                                <?php foreach ( $this->settings->get_providers() as $value => $label ) : ?>
                                    <option
                                        value="<?php echo esc_attr( $value ); ?>"
                                        <?php selected( $settings['provider'], $value ); ?>
                                    >
                                        <?php echo esc_html( $label ); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </td>
                    </tr>

                    <tr>
                        <th scope="row">
                            <?php esc_html_e( 'OpenAI API Key', 'ai-wordpress-admin-assistant' ); ?>
                        </th>

                        <td>
                            <input
                                type="password"
                                class="regular-text"
                                name="ai_wp_assistant_settings[openai_api_key]"
                                value="<?php echo esc_attr( $settings['openai_api_key'] ); ?>"
                                autocomplete="off"
                            />
                        </td>
                    </tr>

                    <tr>
                        <th scope="row">
                            <?php esc_html_e( 'OpenAI Model', 'ai-wordpress-admin-assistant' ); ?>
                        </th>

                        <td>
                            <input
                                type="text"
                                class="regular-text"
                                name="ai_wp_assistant_settings[openai_model]"
                                value="<?php echo esc_attr( $settings['openai_model'] ); ?>"
                            />
                        </td>
                    </tr>

                    <tr>
                        <th scope="row">
                            <?php esc_html_e( 'Anthropic API Key', 'ai-wordpress-admin-assistant' ); ?>
                        </th>

                        <td>
                            <input
                                type="password"
                                class="regular-text"
                                name="ai_wp_assistant_settings[anthropic_api_key]"
                                value="<?php echo esc_attr( $settings['anthropic_api_key'] ); ?>"
                                autocomplete="off"
                            />
                        </td>
                    </tr>

                    <tr>
                        <th scope="row">
                            <?php esc_html_e( 'Anthropic Model', 'ai-wordpress-admin-assistant' ); ?>
                        </th>

                        <td>
                            <input
                                type="text"
                                class="regular-text"
                                name="ai_wp_assistant_settings[anthropic_model]"
                                value="<?php echo esc_attr( $settings['anthropic_model'] ); ?>"
                            />
                        </td>
                    </tr>

                    <tr>
                        <th scope="row">
                            <?php esc_html_e( 'Gemini API Key', 'ai-wordpress-admin-assistant' ); ?>
                        </th>

                        <td>
                            <input
                                type="password"
                                class="regular-text"
                                name="ai_wp_assistant_settings[gemini_api_key]"
                                value="<?php echo esc_attr( $settings['gemini_api_key'] ); ?>"
                                autocomplete="off"
                            />
                        </td>
                    </tr>

                    <tr>
                        <th scope="row">
                            <?php esc_html_e( 'Gemini Model', 'ai-wordpress-admin-assistant' ); ?>
                        </th>

                        <td>
                            <input
                                type="text"
                                class="regular-text"
                                name="ai_wp_assistant_settings[gemini_model]"
                                value="<?php echo esc_attr( $settings['gemini_model'] ); ?>"
                            />
                        </td>
                    </tr>

                    <tr>
                        <th scope="row">
                            <label for="ai-temperature">
                                <?php esc_html_e( 'Temperature', 'ai-wordpress-admin-assistant' ); ?>
                            </label>
                        </th>

                        <td>
                            <input
                                id="ai-temperature"
                                type="number"
                                min="0"
                                max="2"
                                step="0.1"
                                name="ai_wp_assistant_settings[temperature]"
                                value="<?php echo esc_attr( $settings['temperature'] ); ?>"
                            />
                        </td>
                    </tr>

                </table>

                <?php submit_button(); ?>
            </form>
        </div>
        <?php
    }
}