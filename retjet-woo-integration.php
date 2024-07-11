<?php
/*
Plugin Name: RetJet Woo integration
Plugin URI: https://github.com/RetJet/retjet-woo-integration
Description: Plugin that generates and manages an API key for integration with the RetJet platform.
Version: 1.0
Author: RetJet
Author URI: https://retjet.com
GitHub Plugin URI: https://github.com/RetJet/retjet-woo-integration
GitHub Branch: main
*/

// Define constants
define('RETJET_WOO_INTEGRATION_DIR', plugin_dir_path(__FILE__));
define('RETJET_WOO_INTEGRATION_URL', plugin_dir_url(__FILE__));

// Include necessary files
require_once RETJET_WOO_INTEGRATION_DIR . 'includes/admin-page.php';
require_once RETJET_WOO_INTEGRATION_DIR . 'includes/api-functions.php';

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
    $version = filemtime(RETJET_WOO_INTEGRATION_DIR . 'assets/css/admin-style.css');
    wp_enqueue_style('retjet_admin_css', RETJET_WOO_INTEGRATION_URL . 'assets/css/admin-style.css', array(), $version);

    $version = filemtime(RETJET_WOO_INTEGRATION_DIR . 'assets/js/admin-script.js');
    wp_enqueue_script('retjet_admin_js', RETJET_WOO_INTEGRATION_URL . 'assets/js/admin-script.js', array('jquery'), $version, true);

    wp_localize_script('retjet_admin_js', 'retjetIntegration', array(
        'copyIcon' => RETJET_WOO_INTEGRATION_URL . 'assets/images/copy-icon.png',
        'copiedIcon' => RETJET_WOO_INTEGRATION_URL . 'assets/images/copied-icon.png',
    ));
}

// Add settings link on plugin page
add_filter('plugin_action_links_' . plugin_basename(__FILE__), 'retjet_woo_integration_add_settings_link');

function retjet_woo_integration_add_settings_link($links) {
    $settings_link = '<a href="admin.php?page=retjet-woo-integration">' . __('Settings') . '</a>';
    array_unshift($links, $settings_link);
    return $links;
}