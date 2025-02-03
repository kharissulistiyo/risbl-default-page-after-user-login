<?php

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

class Risbl_Default_Page_After_User_Login_Redirect {

    /**
     * Constructor.
     * Hooks into the login_redirect filter.
     */
    public function __construct() {
        add_filter('login_redirect', array($this, 'redirect_after_login'), 10, 3);
    }

    /**
     * Retrieves the option for login redirect.
     *
     * @return array The options related to login redirect.
     */
    public function get_option() {
        $option_risbl_login_redirect = get_option('_risbl_login_redirect');
        return $option_risbl_login_redirect;
    }

    /**
     * Checks if the module for login redirect is active.
     *
     * @return bool True if the module is active, false otherwise.
     */
    public function is_module_active() {
        $option =  $this->get_option();
        $return = false;
        if( array_key_exists('_enable_risbl_login_redirect', $option) ) {
            $return = ('1' === $option['_enable_risbl_login_redirect']) ? true : false;
        }
        return $return;
    }

    /**
     * Retrieves the login redirect URL for a given key.
     *
     * @param string $key The key for the login redirect URL.
     * @return string|null The login redirect URL or null if not set.
     */
    public function get_login_redirect_url($key) {
        $option =  $this->get_option();
        if( array_key_exists($key, $option) ) {
            return $option[$key];
        } else {
            return null;
        }
    }

    /**
     * Redirects the user after login.
     *
     * @param string $redirect_to The redirect destination URL.
     * @param string $requested_redirect_to The requested redirect destination URL passed as a parameter.
     * @param WP_User|WP_Error $user WP_User object if login was successful, WP_Error object otherwise.
     * @return string The redirect URL.
     */
    public function redirect_after_login($redirect_to, $requested_redirect_to, $user) {

        if (is_wp_error($user)) {
            return $redirect_to;
        }

        if( $this->is_module_active() ) :
            $custom_redirect_url = $this->get_custom_redirect_url($user);

            // Determine the redirect URL based on custom meta, user role, or login form options
            if (!empty($custom_redirect_url)) {
                $redirect_to = $custom_redirect_url;
            } else {
                $redirect_to = $this->determine_redirect_url($user);
            }
        endif;

        return $redirect_to;

    }

    /**
     * Gets the custom redirect URL from user meta if it exists.
     *
     * @param int $user_id The user ID.
     * @return string|null The custom redirect URL or null if not set.
     */
    protected function get_custom_redirect_url($user) {

        if ($user) {
            $username = $user->user_login;
            $option =  $this->get_option();
            if( $this->is_module_active() && array_key_exists($username, $option) ) {
                return $this->get_login_redirect_url($username);
            }
        }

    }

    /**
     * Determines the redirect URL based on the user's role and user ID.
     *
     * @param string $user_role The user's role.
     * @param int $user_id The user's ID.
     * @return string The redirect URL.
     */
    protected function determine_redirect_url($user) {

        if ( in_array( 'administrator', $user->roles ) ) {
            return $this->get_admin_redirect_url();
        } else {

            $user_roles = risbl_default_page_after_user_login_supported_roles();

            foreach ($user_roles as $role) {
                // Check for a login form option override (e.g., stored in user meta or session)
                if ( in_array( $role, $user->roles ) ) {
                    $login_form_redirect = $this->get_login_redirect_url($role);
                    if (!empty($login_form_redirect)) {
                        return $login_form_redirect;
                    }
                    return $this->get_default_redirect_url();
                }
            }

        }
    }

    /**
     * Gets the default redirect URL.
     *
     * @return string The default redirect URL.
     */
    protected function get_default_redirect_url() {
        return $this->get_admin_redirect_url();
    }

    /**
     * Gets the admin redirect URL.
     *
     * @return string The admin redirect URL.
     */
    protected function get_admin_redirect_url() {
        return admin_url();
    }
}

// Instantiate the class
new Risbl_Default_Page_After_User_Login_Redirect();