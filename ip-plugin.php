<?php
/*
Plugin Name: User IP Display Plugin
Description: Display IP address of users in the admin area.
Version: 1.0
Author: TurkProgrammer
Author URI: https://github.com/turkprogrammer
*/

// Add a new column to the users table in the admin area
function ip_address_plugin_add_ip_column($columns) {
    $columns['ip'] = 'IP';
    return $columns;
}
add_filter('manage_users_columns', 'ip_address_plugin_add_ip_column');

// Populate the new column with the IP address of each user
function ip_address_plugin_display_ip($value, $column_name, $user_id) {
    if ($column_name === 'ip') {
        $user_ip = ip_address_plugin_get_user_ip($user_id);
        $value = $user_ip ? $user_ip : 'N/A';
    }
    return $value;
}
add_action('manage_users_custom_column', 'ip_address_plugin_display_ip', 10, 3);

// Function to get user's IP address
function ip_address_plugin_get_user_ip($user_id) {
    global $wpdb;
    $table_name = $wpdb->prefix . 'user_ips';

    $user_ip = $wpdb->get_var($wpdb->prepare("SELECT ip FROM $table_name WHERE user_id = %d ORDER BY id DESC LIMIT 1", $user_id));

    return $user_ip ? $user_ip : 'N/A';
}


// Create the user_ips table on plugin activation
function ip_address_plugin_create_table() {
    global $wpdb;
    $table_name = $wpdb->prefix . 'user_ips';

    if ($wpdb->get_var("SHOW TABLES LIKE '$table_name'") != $table_name) {
        $charset_collate = $wpdb->get_charset_collate();

        $sql = "CREATE TABLE $table_name (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            user_id bigint(20) NOT NULL,
            ip varchar(45) NOT NULL,
            timestamp datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY user_id (user_id)
        ) $charset_collate;";

        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql);
    }
}
register_activation_hook(__FILE__, 'ip_address_plugin_create_table');

// Save user's IP address in the user_ips table on login
function ip_address_plugin_save_user_ip($user_login, $user) {
    global $wpdb;
    $table_name = $wpdb->prefix . 'user_ips';

    $user_ip = ip_address_plugin_get_user_ip($user->ID);
    if ($user_ip !== 'N/A') {
        $wpdb->insert($table_name, array(
            'user_id' => $user->ID,
            'ip' => $user_ip
        ));
    }
}
add_action('wp_login', 'ip_address_plugin_save_user_ip', 10, 2);


