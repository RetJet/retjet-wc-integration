<?php
// Function to generate a random token
function retjet_generate_token($length = 12, $special_chars = true, $extra_special_chars = false) {
    $chars = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789';
    if ($special_chars) {
        $chars .= '!@#$%^&*()';
    }
    if ($extra_special_chars) {
        $chars .= '-_ []{}<>~`+=,.;:/?|';
    }

    return substr(str_shuffle($chars), 0, $length);
}

// Function to create a WooCommerce API key
function retjet_create_woocommerce_api_key($api_key) {
    if (!class_exists('WooCommerce')) {
        return;
    }

    $user_id = get_current_user_id();
    $description = 'RetJet API Key';
    $permissions = 'read';

    $consumer_key = 'ck_' . wc_rand_hash();
    $consumer_secret = 'cs_' . wc_rand_hash();

    $data = array(
        'user_id'         => $user_id,
        'description'     => $description,
        'permissions'     => $permissions,
        'consumer_key'    => $consumer_key,
        'consumer_secret' => $consumer_secret,
        'truncated_key'   => substr($consumer_key, -7),
        'last_access'     => null,
    );

    $key_id = wc_api_dev_create_key($data);

    if ($key_id) {
        update_option('retjet_woocommerce_api_key', array(
            'consumer_key'    => $consumer_key,
            'consumer_secret' => $consumer_secret,
            'key_id'          => $key_id,
        ));
    }
}

// Function to delete WooCommerce API key
function retjet_delete_woocommerce_api_key() {
    $woocommerce_api_key = get_option('retjet_woocommerce_api_key');

    if ($woocommerce_api_key) {
        $cache_key = 'woocommerce_api_key_exists_' . $woocommerce_api_key['key_id'];
        wp_cache_delete($cache_key);

        global $wpdb;
        $table_name = $wpdb->prefix . 'woocommerce_api_keys';

        $wpdb->delete(
            $table_name,
            array('key_id' => $woocommerce_api_key['key_id']),
            array('%d')
        );

        delete_option('retjet_woocommerce_api_key');
    }
}

// Function to create WooCommerce API key (helper function)
function wc_api_dev_create_key($data) {
    global $wpdb;
    $table_name = $wpdb->prefix . 'woocommerce_api_keys';

    $result = $wpdb->insert(
        $table_name,
        array(
            'user_id'         => $data['user_id'],
            'description'     => $data['description'],
            'permissions'     => $data['permissions'],
            'consumer_key'    => wc_api_hash($data['consumer_key']),
            'consumer_secret' => $data['consumer_secret'],
            'truncated_key'   => $data['truncated_key'],
            'last_access'     => $data['last_access'],
        ),
        array(
            '%d',
            '%s',
            '%s',
            '%s',
            '%s',
            '%s',
            '%s',
        )
    );

    if ($result) {
        $insert_id = $wpdb->insert_id;

        $cache_key = 'woocommerce_api_key_exists_' . $insert_id;
        wp_cache_set($cache_key, true, '', HOUR_IN_SECONDS);

        return $insert_id;
    }

    return false;
}

// Function to generate RetJet integration URL
function get_integration_url($api_key, $api_secret) {
    $shop_domain = get_site_url();
    $parsed_url = wp_parse_url($shop_domain);
    $domain = isset($parsed_url['host']) ? $parsed_url['host'] : $shop_domain;

    // Remove trailing slashes
    $domain = rtrim($domain, '/');

    $form_name = $domain . ' Auto-Configuration';
    $form_label = $domain . ' Auto-Configuration by RetJet Module';

    $data = array(
        'form_name' => $form_name,
        'form_label' => $form_label,
        'form_channel_type' => 'woocommerce',
        'form_api_endpoint_url' => $shop_domain.'/wp-json/wc/v3/',
        'form_api_auth_type' => 'basic',
        'form_api_customer_key' => $api_key,
        'form_api_customer_secret' => $api_secret,
    );

    $json_data = wp_json_encode($data);
    $encoded_data = urlencode($json_data);

    $integration_base_url = 'https://app.retjet.com/panel/sales_channel/add?data=';
    return $integration_base_url . $encoded_data;
}