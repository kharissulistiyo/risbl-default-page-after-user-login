<?php 

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

if( !function_exists('risbl_default_page_after_user_login_permanent_roles') ) :
    /**
     * Retrieves the list of permanent roles for user login redirection.
     *
     * This function returns a default set of roles that are always included in the login redirect process. 
     * If WooCommerce is active, additional roles related to WooCommerce are included.
     *
     * @return array The list of roles.
     */
    function risbl_default_page_after_user_login_permanent_roles() {

        $roles = array();
        $roles[] = 'editor';
        $roles[] = 'author';
        $roles[] = 'subscriber';
        $roles[] = 'contributor';

        if ( class_exists( 'WooCommerce' ) ) {
            $roles[] = 'shop_manager';
            $roles[] = 'customer';
        }

        return $roles;

    }
endif;

if( !function_exists('risbl_default_page_after_user_login_supported_roles') ) :
    /**
     * Retrieves the list of supported roles for user login redirection.
     *
     * This function fetches the permanent roles using `risbl_default_page_after_user_login_permanent_roles()`, 
     * and then checks if any roles have been explicitly defined in the plugin's settings. 
     * If available roles are set, it returns those instead.
     *
     * @return array The list of supported roles for login redirection.
     */
    function risbl_default_page_after_user_login_supported_roles() {

        $roles = risbl_default_page_after_user_login_permanent_roles();
        $get_roles = $roles;
        $option_risbl_login_redirect = get_option('_risbl_login_redirect');

        if( array_key_exists('available_roles', $option_risbl_login_redirect) ) {
            $available_roles = $option_risbl_login_redirect['available_roles'];
            if( is_array($available_roles) && (count($available_roles) > 0) ) {
                $get_roles = array_keys($available_roles);
            }
        }

        return $get_roles;
    }

endif;