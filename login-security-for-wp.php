<?php
/**
 * @link              https://christianzimpel.de
 * @since             1.0.0
 * @package           
 *
 * @wordpress-plugin
 * Plugin Name:       Login Security for WP
 * Plugin URI:        https://wp-support-blog.com/
 * Description:       Secure your login, Hide the Login and use a custom URL  
 * Version:           1.0.0
 * Author:            Christian Zimpel
 * Author URI:        https://christianzimpel.de
 * License:           GPLv3
 * License URI:       http://www.gnu.org/licenses/gpl-3.0.html
 * Text Domain:       login-security-for-wp
 * Domain Path:       /languages
*/

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) exit;

// Load textdomain for the plugin
function lsfw_load_my_plugin_textdomain() {
    $domain = 'login-security-for-wp';
    $locale = apply_filters( 'plugin_locale', get_locale(), $domain );
    load_textdomain( $domain, WP_LANG_DIR . '/plugins/' . $domain . '-' . $locale . '.mo' );
    load_plugin_textdomain( $domain, false, dirname( plugin_basename( __FILE__ ) ) . '/languages' );
}
add_action( 'plugins_loaded', 'lsfw_load_my_plugin_textdomain' );

// Add custom login rewrite rule
function lsfw_custom_login_rewrite_rules() {
    global $custom_login;
    $custom_login = get_option('wplogsec_url');
    add_rewrite_rule($custom_login . '/?$', 'index.php?lsfw_custom_login=1', 'top');
    flush_rewrite_rules(); 
}

add_action('init', 'lsfw_custom_login_rewrite_rules');

// Add custom login query variable
function lsfw_query_vars($vars) {
    $vars[] = 'lsfw_custom_login';
    return $vars;
}
add_filter('query_vars', 'lsfw_query_vars');

// Redirect to the custom login template
function lsfw_custom_login_template_redirect() {
    $is_lsfw_custom_login = intval(get_query_var('lsfw_custom_login'));
    if ($is_lsfw_custom_login) {
        require_once(ABSPATH . 'wp-login.php');
        exit;
    }
}
add_action('template_redirect', 'lsfw_custom_login_template_redirect');

// Flush rewrite rules on plugin activation and deactivation
function lsfw_flush_rewrite_rules() {
    lsfw_custom_login_rewrite_rules(); // Add custom login rewrite rule
    flush_rewrite_rules();
}

// Flush rewrite rules and update permalink structure on plugin activation
function lsfw_plugin_activation() {
    // Save the current permalink structure
    $current_permalink_structure = get_option('permalink_structure');
    update_option('lsfw_current_permalink_structure', $current_permalink_structure);

    // Set new permalink structure
    global $wp_rewrite;
    $wp_rewrite->set_permalink_structure('/%postname%/');
    $wp_rewrite->flush_rules();

    lsfw_flush_rewrite_rules();
	

}
register_activation_hook(__FILE__, 'lsfw_plugin_activation');

// Restore the original permalink structure on plugin deactivation
function lsfw_plugin_deactivation() {
    // Retrieve the original permalink structure
    $original_permalink_structure = get_option('lsfw_current_permalink_structure');
    
    // Restore the original permalink structure
    global $wp_rewrite;
    $wp_rewrite->set_permalink_structure($original_permalink_structure);
    $wp_rewrite->flush_rules();

    lsfw_flush_rewrite_rules();
	

}
register_deactivation_hook(__FILE__, 'lsfw_plugin_deactivation');

// Modify the login URL
add_filter( 'login_url', 'lsfw_my_login_page', 10, 3 );

// Custom login page function
function lsfw_my_login_page( $login_url, $redirect, $force_reauth ) {
	
	$login_url = esc_url($login_url);
    $redirect = esc_url($redirect);
    $force_reauth = (bool)$force_reauth;
	
	global $pagenow;	
	global $url;
	
	// If not logged in and on wp-login.php, show 404 or handle logout
	if (!is_user_logged_in() && ($pagenow == 'wp-login.php')){
		
		$action = isset($_GET['action']) ? sanitize_text_field($_GET['action']) : '';

		// Verify if the 'action' value is valid
		$valid_actions = array('logout');
		if (in_array($action, $valid_actions)) {
			if ($action == 'logout') {
				wp_logout();
				wp_redirect(home_url());
			}
		} else {
			http_response_code(404);
		}

		exit;
	}

	
	// If not logged in and trying to access wp-admin, show 404
	if (!is_user_logged_in() && (strpos(esc_url($redirect), 'wp-admin') !== false)) {

		http_response_code(404);
		exit;
	} else {
        return home_url( 'wp-login.php' );
	}
}


// Add an admin submenu for the plugin
function lsfw_wplogsec_admin_page() {
    add_submenu_page( 'options-general.php',
        __( 'Login Security for WP', 'login-security-for-WP', 'login-security-for-wp' ),
        'WP Login Security',
        'manage_options',
        'wplogsec-page',
        'lsfw_wplogsec_menu_page',
        7
    );
}
add_action( 'admin_menu', 'lsfw_wplogsec_admin_page' );

// Display the admin page from the /admin/ folder
function lsfw_wplogsec_menu_page() {
    ob_start();
    require_once ( plugin_dir_path( __FILE__ ) . '/admin/wplogsec-admin-page.php');
    echo ob_get_clean();
}


// Register the plugin settings
function lsfw_wplogsec_register_settings() {
    register_setting( 'wplogsec_settings_group', 'wplogsec_url', 'lsfw_wplogsec_sanitize_and_save' );
}

// Sanitize and save the custom login URL, then update permalinks
function lsfw_wplogsec_sanitize_and_save($input) {
    $sanitized_input = sanitize_text_field($input);

    // Check if the option was not previously set
    if (get_option('wplogsec_url') === false) {
        // Update the permalink structure
        flush_rewrite_rules(false);
    }

    return $sanitized_input;
}


add_action( 'admin_init', 'lsfw_wplogsec_register_settings' );

// Link within the Pluginpage
function lsfw_add_settings_link($links) {
    $settings_link = '<a href="' . esc_url( admin_url( 'options-general.php?page=wplogsec-page' ) ) . '">' . __('Settings', 'login-security-for-wp') . '</a>';
    array_push($links, $settings_link);
    return $links;
}

add_filter('plugin_action_links_' . plugin_basename(__FILE__), 'lsfw_add_settings_link');







// Modify the site URL for the custom login

function lsfw_custom_login_site_url($url, $path, $scheme, $blog_id) {
    global $custom_login;

    $custom_login = sanitize_key( $custom_login ); // Sanitize user input

    if ($path === 'wp-login.php' && $scheme !== 'admin') {
        $url = home_url($custom_login, $scheme);
    }

    return $url;
}
add_filter('site_url', 'lsfw_custom_login_site_url', 10, 4);


