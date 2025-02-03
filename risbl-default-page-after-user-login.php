<?php
/**
 * Plugin Name: Risbl Default Page after User Login 
 * Plugin URI: https://admin.risbl.com
 * Description: Set default page to open after user login.
 * Version: 0.0.1
 * Author: Kharis Sulistiyono
 * Author URI: https://kharis.risbl.com
 * Text Domain: risbl-default-page-after-user-login

License: GPL2
This WordPress Plugin is free software: you can redistribute it and/or modify
it under the terms of the GNU General Public License as published by
the Free Software Foundation, either version 2 of the License, or
any later version.

This free software is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
GNU General Public License for more details.

You should have received a copy of the GNU General Public License
along with this software. If not, see http://www.gnu.org/licenses/gpl-2.0.html.
*/

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

// Define plugin path constant.
define('RISBL_DEFAULT_PAGE_AFTER_USER_LOGIN_PLUGIN_PATH', plugin_dir_path(__FILE__));

define('RISBL_DEFAULT_PAGE_AFTER_USER_LOGIN_PLUGIN_URL', plugin_dir_url(__FILE__));


/**
 * Plugin activation hook.
 *
 * This function is triggered when the plugin is activated. It performs any necessary setup, 
 * such as flushing the rewrite rules to ensure the correct permalinks are set.
 */
function risbl_default_page_after_user_login_activate() {
    // Activation code here.
    flush_rewrite_rules();
}
register_activation_hook(__FILE__, 'risbl_default_page_after_user_login_activate');

/**
 * Plugin deactivation hook.
 *
 * This function is triggered when the plugin is deactivated. It performs any necessary cleanup, 
 * such as flushing the rewrite rules to ensure they are removed.
 */
function risbl_default_page_after_user_login_deactivate() {
    // Deactivation code here.
    flush_rewrite_rules();
}
register_deactivation_hook(__FILE__, 'risbl_default_page_after_user_login_deactivate');

/**
 * Loads the necessary plugin files.
 *
 * This function loads the core plugin files, including functions, the login redirect class, 
 * and the plugin admin class, by including them from the plugin’s 'include' directory.
 */
function risbl_default_page_after_user_login_plugin_loaded() {

      $files = array(
        'functions'                 => 'functions.php',
        'login-redirect'            => 'class/class-login-redirect.php',
        'plugin-admin'              => 'class/class-plugin-admin.php',
      );
      
      foreach ($files as $key => $file) {
        $path_file = RISBL_DEFAULT_PAGE_AFTER_USER_LOGIN_PLUGIN_PATH . 'include/' . $file;
          if (file_exists($path_file)) {
          require_once $path_file;
        }
      }

}
add_action('plugins_loaded', 'risbl_default_page_after_user_login_plugin_loaded');

/**
 * Loads the plugin textdomain for translations.
 *
 * This function loads the textdomain for the plugin to allow translation of strings 
 * into the desired language, based on the plugin's language files.
 */
function risbl_default_page_after_user_login_load_textdomain() {
    load_plugin_textdomain('risbl-default-page-after-user-login', false, dirname(plugin_basename(__FILE__)) . '/languages');
}
add_action('init', 'risbl_default_page_after_user_login_load_textdomain');