<?php
/**
 * Plugin Name: AI WordPress Admin Assistant
 * Plugin URI: https://github.com/mabfahad/ai-wordpress-admin-assistant
 * Description: An AI-powered assistant for WordPress administration.
 * Version: 0.1.0
 * Author: Md Abdullah Al Fahad
 * Author URI: https://github.com/mabfahad
 * License: GPL-2.0-or-later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: ai-wordpress-admin-assistant
 */

defined( 'ABSPATH' ) || exit;

define( 'AI_WP_ASSISTANT_VERSION', '0.1.0' );
define( 'AI_WP_ASSISTANT_FILE', __FILE__ );
define( 'AI_WP_ASSISTANT_PATH', plugin_dir_path( __FILE__ ) );
define( 'AI_WP_ASSISTANT_URL', plugin_dir_url( __FILE__ ) );

$autoload = AI_WP_ASSISTANT_PATH . 'vendor/autoload.php';

if ( ! file_exists( $autoload ) ) {
    return;
}

require_once $autoload;

$plugin = new \AIWordPressAssistant\Plugin();
$plugin->init();