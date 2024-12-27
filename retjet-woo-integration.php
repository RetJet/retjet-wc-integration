<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly
}
/**
 * Plugin Name: RetJet Woo Integration
 * Plugin URI: https://github.com/RetJet/retjet-woo-integration
 * Description: Plugin that generates and manages an API key for integration with the RetJet platform.
 * Version: 1.0.0
 * Author: RetJet
 * License: GPLv2 or later
 * Author URI: https://retjet.com
 * GitHub Plugin URI: https://github.com/RetJet/retjet-woo-integration
 * GitHub Branch: main
 */

// Configuration array
$retjet_woo_integration_config = array(
    'dir' => plugin_dir_path(__FILE__),
    'url' => plugin_dir_url(__FILE__)
);

// Include necessary files
require_once $retjet_woo_integration_config['dir'] . 'includes/admin-page.php';
require_once $retjet_woo_integration_config['dir'] . 'includes/api-functions.php';


// Hook to run the function upon plugin activation
register_activation_hook(__FILE__, 'RETJET_WOO_INTEGRATION_activate');

function RETJET_WOO_INTEGRATION_activate() {
    // Check if WooCommerce is active
    if (!class_exists('WooCommerce')) {
        deactivate_plugins(plugin_basename(__FILE__));
        wp_die('This plugin requires WooCommerce to be installed and active.');
    }

    // Generate API key if it doesn't exist
    if (!get_option('retjet_api_key')) {
        $api_key = retjet_generate_token(32, false, false);
        add_option('retjet_api_key', $api_key);
        retjet_create_woocommerce_api_key($api_key);
    }
}

// Enqueue admin styles and scripts
add_action('admin_enqueue_scripts', 'RETJET_WOO_INTEGRATION_admin_assets');

function RETJET_WOO_INTEGRATION_admin_assets() {
    global $retjet_woo_integration_config;

    $version = filemtime($retjet_woo_integration_config['dir'] . 'assets/css/admin-style.css');
    wp_enqueue_style('retjet_admin_css', esc_url($retjet_woo_integration_config['url'] . 'assets/css/admin-style.css'), array(), $version);

    $version = filemtime($retjet_woo_integration_config['dir'] . 'assets/js/admin-script.js');
    wp_enqueue_script('retjet_admin_js', esc_url($retjet_woo_integration_config['url']. 'assets/js/admin-script.js'), array('jquery'), $version, true);

    wp_localize_script('retjet_admin_js', 'retjetIntegration', array(
        'copyIcon' => esc_url($retjet_woo_integration_config['url'] . 'assets/images/copy-icon.png'),
        'copiedIcon' => esc_url($retjet_woo_integration_config['url'] . 'assets/images/copied-icon.png'),
    ));
}

// Add settings link on plugin page
add_filter('plugin_action_links_' . plugin_basename(__FILE__), 'retjet_woo_integration_add_settings_link');

function retjet_woo_integration_add_settings_link($links) {
    $settings_link = '<a href="' . esc_url('admin.php?page=retjet-woo-integration') . '">' . esc_html__('Settings', 'retjet-woo-integration') . '</a>';
    array_unshift($links, $settings_link);
    return $links;
}
