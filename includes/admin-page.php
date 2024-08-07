<?php
// Hook to add the menu page
add_action('admin_menu', 'retjet_woo_integration_menu');

function retjet_woo_integration_menu() {
    add_menu_page(
        'RetJet API Key Management',
        'RetJet API Key',
        'manage_options',
        'retjet-woo-integration',
        'retjet_woo_integration_page',
        'dashicons-admin-generic',
        81
    );
}

// Function to display the plugin page
function retjet_woo_integration_page() {
    global $retjet_woo_integration_config;

    if (!class_exists('WooCommerce')) {
        echo '<div class="wrap"><h1>RetJet API Key Management</h1><p>WooCommerce is not installed or active. Please install and activate WooCommerce to use this plugin.</p></div>';
        return;
    }

    // Check if the API keys exist in the WooCommerce database
    $woocommerce_api_key = get_option('retjet_woocommerce_api_key');
    if ($woocommerce_api_key) {
        global $wpdb;
        $key_id = $woocommerce_api_key['key_id'];
        $key_exists = $wpdb->get_var($wpdb->prepare("SELECT COUNT(*) FROM {$wpdb->prefix}woocommerce_api_keys WHERE key_id = %d", $key_id));
        if (!$key_exists) {
            delete_option('retjet_woocommerce_api_key');
            delete_option('retjet_api_key');
            $woocommerce_api_key = false;
        }
    }

    if (isset($_POST['generate_api_key'])) {
        $api_key = retjet_generate_token(32, false, false);
        update_option('retjet_api_key', $api_key);
        retjet_create_woocommerce_api_key($api_key);
    } elseif (isset($_POST['delete_api_key'])) {
        delete_option('retjet_api_key');
        retjet_delete_woocommerce_api_key();
    }

    $api_key = get_option('retjet_api_key');
    $woocommerce_api_key = get_option('retjet_woocommerce_api_key');
    $integration_url = $woocommerce_api_key ? get_integration_url($woocommerce_api_key['consumer_key'], $woocommerce_api_key['consumer_secret']) : '';
    ?>
    <div class="wrap retjet-admin-page">
        <h1>RetJet API Key Management</h1>
        <img src="<?php echo esc_url($retjet_woo_integration_config['url'] . 'assets/images/retjet-logo.png'); ?>" alt="RetJet Logo" class="retjet-logo">

        <?php if ($woocommerce_api_key): ?>
            <p>Please enter these keys in the appropriate fields when creating a Sales Channel in RetJet:</p>
            <label for="consumer_key"><strong>WooCommerce Consumer Key:</strong></label>
            <div class="input-copy-wrapper">
                <input type="text" id="consumer_key" value="<?php echo esc_attr($woocommerce_api_key['consumer_key']); ?>" readonly>
                <button class="button-copy" data-clipboard-target="#consumer_key">
                    <img src="<?php echo esc_url($retjet_woo_integration_config['url'] . 'assets/images/copy-icon.png'); ?>" alt="Copy">
                </button>
            </div>
            <label for="consumer_secret"><strong>WooCommerce Consumer Secret:</strong></label>
            <div class="input-copy-wrapper">
                <input type="text" id="consumer_secret" value="<?php echo esc_attr($woocommerce_api_key['consumer_secret']); ?>" readonly>
                <button class="button-copy" data-clipboard-target="#consumer_secret">
                    <img src="<?php echo esc_url($retjet_woo_integration_config['url'] . 'assets/images/copy-icon.png'); ?>" alt="Copy">
                </button>
            </div>
            <form method="post" action="" onsubmit="return confirm('Are you sure you want to delete the API key?');">
                <?php submit_button('Delete API Key', 'delete', 'delete_api_key'); ?>
            </form>

            <p><strong>Integrate Your Store with RetJet:</strong></p>
            <div class="integration-button-wrapper">
                <a href="<?php echo esc_url($integration_url); ?>" target="_blank">
                    <button type="button" class="button button-primary button-large">
                        <i class="dashicons dashicons-admin-links"></i> Start integration
                    </button>
                </a>
                <p class="description">Redirects to the RetJet panel and creates a Sales Channel.</p>
            </div>

        <?php else: ?>
            <p>No API key found. Generate a new API key to use the RetJet integration.</p>
            <form method="post" action="">
                <?php submit_button('Generate API Key', 'primary', 'generate_api_key'); ?>
            </form>
        <?php endif; ?>
    </div>
    <?php
}
