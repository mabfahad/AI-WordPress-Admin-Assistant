<?php

namespace AIWordPressAssistant\Settings;

defined( 'ABSPATH' ) || exit;

class Settings {

    private const OPTION_NAME = 'ai_wp_assistant_settings';

    private const DEFAULTS = [
        'provider'              => 'openai',
        'openai_api_key'        => '',
        'openai_model'          => 'gpt-4.1-mini',
        'anthropic_api_key'     => '',
        'anthropic_model'       => 'claude-sonnet-4-20250514',
        'gemini_api_key'        => '',
        'gemini_model'          => 'gemini-2.5-flash',
        'temperature'           => 0.2,
    ];

    /**
     * Get all settings.
     *
     * @return array
     */
    public function all(): array {
        $settings = get_option(
            self::OPTION_NAME,
            []
        );

        return wp_parse_args(
            is_array( $settings ) ? $settings : [],
            self::DEFAULTS
        );
    }

    /**
     * Get a setting.
     *
     * @param string $key     Setting key.
     * @param mixed  $default Default value.
     *
     * @return mixed
     */
    public function get(
        string $key,
        mixed $default = null
    ): mixed {
        $settings = $this->all();

        return $settings[ $key ] ?? $default;
    }

    /**
     * Update settings.
     *
     * @param array $settings Settings.
     *
     * @return bool
     */
    public function update( array $settings ): bool {
        $current = $this->all();

        $settings = array_merge(
            $current,
            $settings
        );

        return update_option(
            self::OPTION_NAME,
            $settings
        );
    }

    /**
     * Get available providers.
     *
     * @return array
     */
    public function get_providers(): array {
        return [
            'openai' => 'OpenAI',
            'anthropic' => 'Anthropic',
            'gemini' => 'Google Gemini',
        ];
    }
}