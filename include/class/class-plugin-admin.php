<?php

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

class Risbl_Default_Page_After_User_Login_Plugin_Admin {

    /**
     * Constructor.
     * Hooks into the wp_loaded action.
     */
    public function __construct() {
        add_action('wp_loaded', array($this, 'on_wp_loaded'));
        add_action('admin_init', array($this, 'register_setting'));
        add_action('admin_enqueue_scripts', [$this, 'enqueue_admin_script']);
        add_action('init', [$this, 'form_processing']);
        add_action('admin_notices', array($this, 'display_admin_notice'));
    }

    /**
     * Method triggered by the wp_loaded action.
     * Loads the admin screen if the user is in the admin area.
     */
    public function on_wp_loaded() {
        if (is_admin()) {
            $this->load_admin_screen();
        }
    }

    /**
     * Enqueues the admin script.
     */
    public function enqueue_admin_script() {

        // Define the CSS file path relative to your plugin directory
        $css_url = RISBL_DEFAULT_PAGE_AFTER_USER_LOGIN_PLUGIN_URL . 'assets/style.css';

        // Enqueue the CSS file
        wp_enqueue_style('risbl-default-page-after-user-login-admin-styles', $css_url, [], '1.0.0', 'all');

    }

    /**
     * Verifies the nonce for security.
     *
     * @param string $nonce_key The nonce key.
     * @param string $action The action associated with the nonce.
     * @return bool True if the nonce is valid, false otherwise.
     */
    public function verify_nonce($nonce_key, $action) {
        if (isset($_POST[$nonce_key]) && wp_verify_nonce($_POST[$nonce_key], $action)) {
            return true;
        }
        return false;
    }

    /**
     * Retrieves a value from the POST request.
     *
     * @param string $key The key to retrieve from the POST request.
     * @param string $callback_val The default value to return if the key is not set.
     * @return mixed The value from the POST request or the default value.
     */
    public function post($key, $callback_val='') {
        $post = isset($_POST[$key]) ? true : false;
        if($post) {
            return $_POST[$key];
        }
        $val = !empty($callback_val) ? $callback_val : '';
        return $val;
    }

    /**
     * Checks if a key exists in the POST request.
     *
     * @param string $key The key to check in the POST request.
     * @return bool True if the key exists, false otherwise.
     */
    public function is_post($key) {
        return isset($_POST[$key]) ? true : false;
    }

    /**
     * Checks if the login redirect feature is active.
     *
     * @return bool True if the login redirect is active, false otherwise.
     */
    public function login_redirect_is_active() {
        $enabled = $this->get_option('_enable_risbl_login_redirect', false);
        return $enabled ? true : false ;
    }

    /**
     * Processes the form submissions.
     */
    public function form_processing() {

        $this->tab_general_form_processing();
        $this->tab_redirect_user_role_form_processing();
        $this->tab_redirect_user_byusername_form_processing();
        $this->tab_tool_form_processing();

    }

    /**
     * Processes the general tab form submissions.
     */
    public function tab_general_form_processing() {

        if( !$this->verify_nonce('_enable_risbl_login_redirect_nonce', 'safe_risbl_enable_risbl_login_redirect_toggle') ) {
            return; // Do nothing when nonce not verified.
        }

        $option_risbl_login_redirect = get_option('_risbl_login_redirect');
        
        if( $this->is_post('_risbl_login_redirect_action__enable_toggle') && ('yes' === $this->post('_risbl_login_redirect_action__enable_toggle')) ) : 

            $option_risbl_login_redirect['_enable_risbl_login_redirect'] = false;

            if( $this->is_post('_enable_risbl_login_redirect') ) {
                $option_risbl_login_redirect['_enable_risbl_login_redirect'] = $this->post('_enable_risbl_login_redirect');
            }

            // $sanitized_data = array_map('sanitize_text_field', $option_risbl_login_redirect);
            // Note: Need proper array sanitation in the future.
            $sanitized_data = $option_risbl_login_redirect;

            update_option('_risbl_login_redirect', $sanitized_data);

            // Trigger a flag for displaying the admin notice
            $this->set_admin_notice(__('Updated!', 'risbl-default-page-after-user-login'));

        endif;

    }

    /**
     * Updates the list of roles.
     */
    public function update_roles_list() {

        $option_risbl_login_redirect = get_option('_risbl_login_redirect');

        
        // Define an array of user roles
        $user_roles = risbl_default_page_after_user_login_supported_roles();

        $option_risbl_login_redirect['available_roles'] = array();
        // $role_label = '';

        // Loop through each role
        $available = array();
        foreach ($user_roles as $role) {

            $role_label = '';

            switch ($role) {
                case 'editor':
                    $role_label = __('Editor', 'risbl-default-page-after-user-login');
                    break;
                case 'author':
                    $role_label = __('Author', 'risbl-default-page-after-user-login');
                    break;
                case 'subscriber':
                    $role_label = __('Subscriber', 'risbl-default-page-after-user-login');
                    break;
                case 'contributor':
                    $role_label = __('Subscriber', 'risbl-default-page-after-user-login');
                    break;
                case 'shop_manager':
                    $role_label = __('Shop Manager', 'risbl-default-page-after-user-login');
                    break;
                case 'customer':
                    $role_label = __('Customer', 'risbl-default-page-after-user-login');
                    break;
                default:
                    $role_label = ''; 
                    break;
            }

            if ($this->is_post($role)) {
                $option_risbl_login_redirect[$role] = !empty($this->post($role)) ? sanitize_url($this->post($role)) : '';
            }

            $available[$role] = $role_label;

        } 

        $option_risbl_login_redirect['available_roles'] = (array) $available;

        // $sanitized_data = array_map('sanitize_text_field', $option_risbl_login_redirect);

        update_option('_risbl_login_redirect', $option_risbl_login_redirect);

    }

    /**
     * Inserts a new role into the list of available roles.
     */
    public function insert_new_role() {

        if( !$this->verify_nonce('_risbl_login_redirect_add_role_nonce', 'safe_risbl_login_redirect_add_role') ) {
            return; // Do nothing when nonce not verified.
        }

        if( !$this->post('_add_role_slug') || !$this->post('_add_role_label') ) :

            if( !$this->post('_add_role_slug') || ('' === $this->post('_add_role_slug')) ) {
                $this->set_admin_notice(__('Sorry, role slug is empty!', 'risbl-default-page-after-user-login'), 'error');               
                return;
            }

            if( !$this->post('_add_role_label') || ('' === $this->post('_add_role_label')) ) {
                $this->set_admin_notice(__('Sorry, role label is empty!', 'risbl-default-page-after-user-login'), 'error');              
                return;
            }

            // return; // Stop if either role slug or label is empty.

        endif;

        $option_risbl_login_redirect = get_option('_risbl_login_redirect');

        if( !array_key_exists('available_roles', $option_risbl_login_redirect) ) {
            $this->update_roles_list(); // Ensure roles list is updated.
        }

        $roles_list = isset($option_risbl_login_redirect['available_roles']) ? $option_risbl_login_redirect['available_roles'] : array();

        if( $this->post('_add_role_slug') && is_array($roles_list) && array_key_exists($this->post('_add_role_slug'), $roles_list) ) { 
            $this->set_admin_notice(__('Sorry, role slug already exists!', 'risbl-default-page-after-user-login'), 'error');
            return;
        }

        if( array_key_exists('available_roles', $option_risbl_login_redirect) && isset($option_risbl_login_redirect['available_roles']) ) {
            
            if( is_array($option_risbl_login_redirect['available_roles']) ) {
                if( $this->post('_add_role_slug') && $this->post('_add_role_label') ) {
                    $new_slug = sanitize_text_field($this->post('_add_role_slug')); 
                    $new_label = sanitize_text_field($this->post('_add_role_label'));
                    $option_risbl_login_redirect['available_roles'][$new_slug] = $new_label;
                }
            }

            // $sanitized_data = array_map('sanitize_text_field', $option_risbl_login_redirect);

            update_option('_risbl_login_redirect', $option_risbl_login_redirect);

            // Insert URL to new role
            if( $this->post('_add_role_redirect_url') ) {
                $new_slug = sanitize_text_field($this->post('_add_role_slug'));
                $option_risbl_login_redirect[$new_slug] = sanitize_url($this->post('_add_role_redirect_url'));
                if( $new_slug ) {
                    update_option('_risbl_login_redirect', $option_risbl_login_redirect);
                }
            }

            // Display message
            $this->set_admin_notice(__('New role added!', 'risbl-default-page-after-user-login'));
        }

    }

    /**
     * Deletes a role from the list of available roles.
     */
    public function delete_role() {
        if( !$this->verify_nonce('_risbl_role_to_delete_nonce', 'safe_risbl_role_to_delete_nonce') ) {
            return; // Do nothing when nonce not verified.
        }

        $slugs = $this->post('delete_this_role');

        $option_risbl_login_redirect = get_option('_risbl_login_redirect');
        $available_roles = $option_risbl_login_redirect['available_roles'];

        $new_roles_list = array();

        if( is_array($slugs) && (count($slugs) > 0) ) {
            $new_roles_list = array_diff_key($available_roles, $slugs);
        }

        $option_risbl_login_redirect['available_roles'] = $new_roles_list;

        if( count($new_roles_list) > 0 ) {
            update_option('_risbl_login_redirect', $option_risbl_login_redirect);
            $this->set_admin_notice(__('Roles list updated!', 'risbl-default-page-after-user-login'));
            return;
        }

        $this->set_admin_notice(__('Error. Maybe no role selected?', 'risbl-default-page-after-user-login'), 'error');

    }

    /**
     * Processes the redirect user role form submissions.
     */
    public function tab_redirect_user_role_form_processing() {

        if( !$this->verify_nonce('_enable_risbl_login_redirect_for_user_role_nonce', 'safe_risbl_enable_risbl_login_redirect_for_user_role') ) {
            return; // Do nothing when nonce not verified.
        }

        $this->update_roles_list();

        // Trigger a flag for displaying the admin notice
        $this->set_admin_notice(__('Updated!', 'risbl-default-page-after-user-login'));

    }

    /**
     * Processes the redirect user by username form submissions.
     */
    public function tab_redirect_user_byusername_form_processing() {

        if( !$this->verify_nonce('_enable_risbl_login_redirect_for_user_byusername_nonce', 'safe_risbl_enable_risbl_login_redirect_for_user_byusername') ) {
            return; // Do nothing when nonce not verified.
        } 

        $option_risbl_login_redirect = get_option('_risbl_login_redirect');

        // Get all users
        $users = get_users();

        // Extract usernames into an array
        $usernames = array();
        foreach ($users as $user) {
            $usernames[] = $user->user_login;
        }

        if( count($usernames) < 1 ) {
            return;
        }

        foreach ($usernames as $username) {
            $current_val = array_key_exists($username, $option_risbl_login_redirect) ? $option_risbl_login_redirect[$username] : '';
            $option_risbl_login_redirect[$username] = $this->is_post($username) ? sanitize_url($this->post($username)) : $current_val;
        }

        $sanitized_data = array_map('sanitize_text_field', $option_risbl_login_redirect);

        update_option('_risbl_login_redirect', $sanitized_data);

        // Trigger a flag for displaying the admin notice
        $this->set_admin_notice(__('Updated!', 'risbl-default-page-after-user-login'));

    }

    /**
     * Processes the tool tab form submissions.
     */
    public function tab_tool_form_processing() {

        $this->insert_new_role();
        $this->delete_role();

    }

    /**
     * Retrieves an option value.
     *
     * @param string $key The option key.
     * @param string $callback_val The default value to return if the key is not set.
     * @return mixed The option value or the default value.
     */
    public function get_option($key, $callback_val='') {
        $option_risbl_login_redirect = get_option('_risbl_login_redirect');
        if( is_array($option_risbl_login_redirect) && array_key_exists($key, $option_risbl_login_redirect) ) {
            return $option_risbl_login_redirect[$key];
        }
        return !empty($callback_val) ? $callback_val : '';
    }

    /**
     * Generates the fields for the general tab.
     *
     * @param object $setting_page The setting page object.
     * @return string The HTML output for the general tab fields.
     */
    public function fields_general($setting_page) {

        if( function_exists('Risbl_Admin_Field') ):

            $field = Risbl_Admin_Field();

            ob_start();

            $is_tab_general = isset($_GET['otab']) && ('general' === $_GET['otab']) ? true : false;

            if( !isset($_GET['otab']) ) {
                $is_tab_general = ('general' === $setting_page->tab_index) ? true : false;
            }

            $option = get_option('_risbl_login_redirect');

            $status = '';
            if( $this->login_redirect_is_active() ) {
                $status .= __('Status: ACTIVE', 'risbl-default-page-after-user-login');
            } else {
                $status .= __('Status: NOT ACTIVE', 'risbl-default-page-after-user-login');
            }

            echo $status;

            echo '<table class="form-table" role="presentation"><tbody>';
            // Checkbox
            echo $field->checkbox([
                'id' => '_enable_risbl_login_redirect',
                'row_title' => __('Enable?', 'risbl-default-page-after-user-login'),
                'label' => __('Check to enable custom login redirect.', 'risbl-default-page-after-user-login'),
                'default_value' => $this->get_option('_enable_risbl_login_redirect', false),
                'layout' => 'wp-admin-form-table',
                'part_of' => $is_tab_general,
            ]);

            echo '</tbody></table>';

            // Render a nonce field
            echo $field->nonce([
                'id' => '_enable_risbl_login_redirect_nonce',
                'action' => 'safe_risbl_enable_risbl_login_redirect_toggle',
                'part_of' => $is_tab_general,
            ]);


            echo '<input type="hidden" name="_risbl_login_redirect_action__enable_toggle" value="yes" />';

            // Create a submit field
            echo $field->submit([
                'id' => 'save-form',
                'label' => __('Save', 'risbl-default-page-after-user-login'),
                'class' => 'button button-primary',
                'part_of' => $is_tab_general,
            ]);

            return ob_get_clean();

        endif;

    }

    /**
     * Renders the tools section with add/delete role links.
     *
     * @param object $setting_page The admin settings page object
     * @return string HTML content for the tools section
     * @throws Exception If Risbl_Admin_Field function is missing
     */
    public function fields_tool($setting_page) {

        if( function_exists('Risbl_Admin_Field') ):

            $field = Risbl_Admin_Field();

            ob_start();

            $option_risbl_login_redirect = get_option('_risbl_login_redirect');

            echo '<div><p>'.__('Available tools:', 'risbl-default-page-after-user-login').'</p></div>';

            $add_role_link = add_query_arg(array(
                            $setting_page->tab_param => 'add-role',
                        ), $setting_page->admin_url);

            $delete_role_link = add_query_arg(array(
                            $setting_page->tab_param => 'delete-role',
                        ), $setting_page->admin_url);

            echo '<ul>';
            echo '<li>' . sprintf('&#9862;&nbsp;<a href="%1s">%2s</a>', $add_role_link, __('Add new role', 'risbl-default-page-after-user-login')) . '</li>';
            echo '<li>' . sprintf('&#9862;&nbsp;<a href="%1s">%2s</a>', $delete_role_link, __('Delete role', 'risbl-default-page-after-user-login')) . '</li>';
            echo '</ul>';


            return ob_get_clean();

        endif;

    }

    /**
     * Generates form fields for adding new user roles.
     *
     * @param object $setting_page The admin settings page object
     * @return string HTML form fields for role creation
     * @throws Exception If Risbl_Admin_Field function is missing
     */
    public function fields_add_role($setting_page) {

        if( function_exists('Risbl_Admin_Field') ):

            $field = Risbl_Admin_Field();

            ob_start();

            $is_page_add_role = isset($_GET['otab']) && ('add-role' === $_GET['otab']) ? true : false;

            echo '<table class="form-table" role="presentation"><tbody>';

            echo $field->input([
                'id' => '_add_role_slug',
                'type' => 'text',
                'label' => __('Slug', 'risbl-default-page-after-user-login'),
                'placeholder' => __('Enter slug', 'risbl-default-page-after-user-login'),
                'default_value' => '',
                'layout' => 'wp-admin-form-table',
                'class' => 'regular-text',
                'part_of' => $is_page_add_role,
            ]);
            echo $field->input([
                'id' => '_add_role_label',
                'type' => 'text',
                'label' => __('Label', 'risbl-default-page-after-user-login'),
                'placeholder' => __('Enter slug label', 'risbl-default-page-after-user-login'),
                'default_value' => '',
                'layout' => 'wp-admin-form-table',
                'class' => 'regular-text',
                'part_of' => $is_page_add_role,
            ]);
            echo $field->input([
                'id' => '_add_role_redirect_url',
                'type' => 'text',
                'label' => __('Redirect URL', 'risbl-default-page-after-user-login'),
                'placeholder' => __('Enter URL', 'risbl-default-page-after-user-login'),
                'default_value' => '',
                'layout' => 'wp-admin-form-table',
                'class' => 'regular-text',
                'part_of' => $is_page_add_role,
            ]);

            echo '</tbody></table>';

            // Render a nonce field
            echo $field->nonce([
                'id' => '_risbl_login_redirect_add_role_nonce',
                'action' => 'safe_risbl_login_redirect_add_role',
                'part_of' => $is_page_add_role,
            ]);


            echo '<input type="hidden" name="_risbl_login_redirect_add_role" value="yes" />';

            // Create a submit field
            echo $field->submit([
                'id' => 'save-form',
                'label' => __('Add role', 'risbl-default-page-after-user-login'),
                'class' => 'button button-primary',
                'part_of' => $is_page_add_role,
            ]);

            return ob_get_clean();

        endif;

    }

    /**
     * Configures and initializes the admin settings screen with tabs and pages.
     * Hooks into WordPress admin to create the settings interface.
     */
    public function load_admin_screen() {
        
        if( function_exists('Risbl_Admin') ) :

            $setting_page = Risbl_Admin();
    
            $setting_page->config([
                'page_title' => __('Login Redirect', 'risbl-default-page-after-user-login'),
                'menu_title' => __('Login Redirect', 'risbl-default-page-after-user-login'),
                'capability' => 'manage_options',
                'menu_slug' => 'risbl-login-redirect',
                'icon_url' => 'dashicons-admin-links',
                'position' => 29,
                'tabs'  => array(
                    'general' => __('General', 'risbl-default-page-after-user-login'),
                    'redirect' => __('Redirect', 'risbl-default-page-after-user-login'),
                    'tool' => __('Tool', 'risbl-default-page-after-user-login'),
                ),
                'tab_param' => 'otab',
                'tab_index' => 'general',
                'has_group' => 'yes',
                'group_index' => 'user-role',
                'form_wrap' => array('<form method="post" action="">', '</form>'),
            ])->add_setting_screen();
        
            $setting_page->add_page('general', array($this, 'page_general'));
            $setting_page->add_page('redirect', array($this, 'page_redirect'));
            $setting_page->add_page('tool', array($this, 'page_tool'));
            $setting_page->add_page('add-role', array($this, 'page_add_role'));
            $setting_page->add_page('delete-role', array($this, 'page_delete_role'));

        endif;

    }

    /**
     * Renders the general settings page content.
     *
     * @param object $setting_page The admin settings page object
     */
    public function page_general($setting_page) {
        echo $this->fields_general($setting_page);
    }

    /**
     * Handles the redirect settings tab content, including user role and specific user groups.
     *
     * @param object $setting_page The admin settings page object
     */
    public function page_redirect($setting_page) {

        /**
         * Create group within tab content
         */
        $group_args = array();
        $group_args[] = array(
            'slug'          => 'user-role',
            'label'         => __('User Role', 'risbl-default-page-after-user-login'),
            'parent_tab'    => 'redirect',
            'is_index'      => 'yes',  
        );
        $group_args[] = array(
            'slug'          => 'specific-user',
            'label'         => __('Specific User', 'risbl-default-page-after-user-login'),
            'parent_tab'    => 'redirect',  
        );

        $setting_page->add_group($group_args);

        if( $setting_page->is_group('user-role') ) :

            echo '<table class="form-table" role="presentation"><tbody>';

            $field = Risbl_Admin_Field();

            // Define an associative array of registered user roles
            $user_roles = array(
                // 'administrator' => __('Administrator', 'risbl-default-page-after-user-login'),
                'editor'        => __('Editor', 'risbl-default-page-after-user-login'),
                'author'        => __('Author', 'risbl-default-page-after-user-login'),
                'subscriber'    => __('Subscriber', 'risbl-default-page-after-user-login'),
                'contributor'   => __('Contributor', 'risbl-default-page-after-user-login')
            );

            if ( class_exists( 'WooCommerce' ) ) {
                $user_roles['shop_manager'] = __('Shop Manager', 'risbl-default-page-after-user-login');
                $user_roles['customer']     = __('Customer', 'risbl-default-page-after-user-login');
            }

            $option_risbl_login_redirect = get_option('_risbl_login_redirect');    
            if( array_key_exists('available_roles', $option_risbl_login_redirect) ) {
                $available_roles = $option_risbl_login_redirect['available_roles'];
                if(is_array($available_roles) && (count($available_roles) > 0)) {
                    $user_roles = $available_roles;

                    if( !class_exists('WooCommerce') && array_key_exists('shop_manager', $user_roles) && array_key_exists('customer', $user_roles) ) {
                        $wc_roles = array(
                            'shop_manager'  => __('Shop Manager', 'risbl-default-page-after-user-login'),
                            'customer'      => __('Customer', 'risbl-default-page-after-user-login'),
                        );
                        $user_roles = array_diff($available_roles, $wc_roles);
                    }

                }
            }

            // Loop through the associative array and display an input field for each role
            $extra_label = '';
            foreach ($user_roles as $key => $role) {
                switch ($key) {
                    case 'shop_manager':
                    case 'customer':
                        $extra_label = __('&nbsp;(WooCommerce)', 'risbl-default-page-after-user-login');
                        break;
                    
                    default:
                        $extra_label = '';
                        break;
                }
                echo $field->input([
                    'id' => $key,
                    'label' => $role . $extra_label,
                    'description' => sprintf(__('URL to open after login for %s user role.', 'risbl-default-page-after-user-login'), $role),
                    'type' => 'text',
                    'placeholder' => __('Enter URL', 'risbl-default-page-after-user-login'),
                    'default_value' => $this->get_option($key, ''),
                    'layout' => 'wp-admin-form-table',
                    'class' => 'regular-text',
                    'part_of' => $setting_page->is_group('user-role'),
                ]);
            }

            echo '</tbody></table>';

            // Render a nonce field
            echo $field->nonce([
                'id' => '_enable_risbl_login_redirect_for_user_role_nonce',
                'action' => 'safe_risbl_enable_risbl_login_redirect_for_user_role',
                'part_of' => $setting_page->is_group('user-role'),
            ]);

            // Create a submit field
            echo $field->submit([
                'id' => 'save-form',
                'label' => __('Save', 'risbl-default-page-after-user-login'),
                'class' => 'button button-primary',
                'part_of' => $setting_page->is_group('user-role'),
            ]);

        endif;

        if( $setting_page->is_group('specific-user') ) :
            echo $this->page_specific_user($setting_page);
        endif;

    }

    /**
     * Displays the tools page content.
     *
     * @param object $setting_page The admin settings page object
     */
    public function page_tool($setting_page) {
        echo $this->fields_tool($setting_page);
    }

    /**
     * Renders the add new role page with form fields.
     *
     * @param object $setting_page The admin settings page object
     */
    public function page_add_role($setting_page) {

        $go_back_url = add_query_arg(array(
            $setting_page->tab_param => 'tool',
        ), $setting_page->admin_url);

        echo '<div>' . sprintf('&larrhk;&nbsp;<a href="%1s">%2s</a>', $go_back_url, __('Back to Tool', 'risbl-default-page-after-user-login')) . '</div>';

        echo '<h2>'.__('Add new role', 'risbl-default-page-after-user-login').'</h2>';

        echo $this->fields_add_role($setting_page);

    }

    /**
     * Displays the role deletion interface with available custom roles.
     *
     * @param object $setting_page The admin settings page object
     */
    public function page_delete_role($setting_page) {

        $permanent_roles = risbl_default_page_after_user_login_permanent_roles();
        $available_roles = risbl_default_page_after_user_login_supported_roles();

        $roles_diff = array_diff($available_roles, $permanent_roles);

        $option_risbl_login_redirect = get_option('_risbl_login_redirect');

        $roles_list = array();

        if( is_array($roles_diff) && (count($roles_diff) > 0) && array_key_exists('available_roles', $option_risbl_login_redirect) ) {
            foreach ($roles_diff as $key => $value) {
                $current_roles = $option_risbl_login_redirect['available_roles'];
                if( isset($current_roles[$value]) ) {
                    $roles_list[$value] = isset($current_roles[$value]) ? $current_roles[$value] : '';
                }
            }
        }

        $go_back_url = add_query_arg(array(
            $setting_page->tab_param => 'tool',
        ), $setting_page->admin_url);

        echo '<div>' . sprintf('&larrhk;&nbsp;<a href="%1s">%2s</a>', $go_back_url, __('Back to Tool', 'risbl-default-page-after-user-login')) . '</div>';

        echo '<h2>'.__('Delete role', 'risbl-default-page-after-user-login').'</h2>';

        if( count($roles_list) < 1 ) {
            $add_role_link = add_query_arg(array(
                $setting_page->tab_param => 'add-role',
            ), $setting_page->admin_url);
            echo '<p>'.__('No custom role available.', 'risbl-default-page-after-user-login').'</p>';
            echo '<p>'.sprintf(__('Maybe want to <a href="%s">add some roles</a>?', 'risbl-default-page-after-user-login'), $add_role_link).'</p>';
        }

        $field = Risbl_Admin_Field();
        $is_page_delete_role = isset($_GET['otab']) && ('delete-role' === $_GET['otab']) ? true : false;

        if( count($roles_list) > 0 ) {
            echo '<table class="form-table" role="presentation">';
            echo '<tbody>';
            foreach ($roles_list as $key => $role) {
                $slug = $key;
                $label = $role;

                // Checkbox 
                echo $field->checkbox([ 
                    'id' => sprintf('delete_this_role[%s]', $slug),
                    'row_title' => $label,
                    'label' => sprintf(__('Check to delete %s', 'risbl-default-page-after-user-login'), $label),
                    'default_value' => false,
                    'layout' => 'wp-admin-form-table',
                    'part_of' => $is_page_delete_role,
                ]);

            }
            echo '</tbody>';
            echo '</table>';

            // Render a nonce field
            echo $field->nonce([
                'id' => '_risbl_role_to_delete_nonce',
                'action' => 'safe_risbl_role_to_delete_nonce',
                'part_of' => $is_page_delete_role,
            ]);

            $notice = sprintf('%1s<br />%2s', __('Once role deleted, it will be not linked to this login redirect mechanism.', 'risbl-default-page-after-user-login'), __('It will not affect to the current site user role system.', 'risbl-default-page-after-user-login'));
            echo '<div><p><em>'.$notice.'</em></p></div>';

            // Create a submit field
            echo $field->submit([
                'id' => 'save-form',
                'label' => __('Delete', 'risbl-default-page-after-user-login'),
                'class' => 'button button-primary',
                'part_of' => $is_page_delete_role,
            ]);

        }

    }

    /**
     * Renders the specific user settings page with paginated user list.
     *
     * @param object $setting_page The admin settings page object
     * @return string HTML content for specific user redirect settings
     */
    public function page_specific_user($setting_page) {

        ob_start();

        // Set the number of users to display per page
        $users_per_page = 5;

        // Get the current page number from the query string
        $paged = isset($_GET['paged']) ? $_GET['paged'] : 1;

        // Prepare the arguments for WP_User_Query
        $args = array(
            'number' => $users_per_page, // Number of users per page
            'paged'  => $paged,          // Current page number
            'role__not_in' => array('administrator'), // Exclude users with the 'administrator' role
        );

        // Create a new WP_User_Query instance
        $user_query = new WP_User_Query($args);

        $field = Risbl_Admin_Field();

        // Check if there are users found
        if (!empty($user_query->get_results())) {
            echo '<table class="form-table" role="presentation">';
            echo '<tbody>';

            // Loop through each user
            foreach ($user_query->get_results() as $user) {
                // Get the user ID
                $user_id = $user->ID;
                // Get the user's display name
                $user_name = $user->display_name;
                
                echo $field->input([
                    'id' => $user_name,
                    'label' => $user_name,
                    'description' => sprintf(__('URL to open after login for username: %s.', 'risbl-default-page-after-user-login'), $user_name),
                    'type' => 'text',
                    'placeholder' => __('Enter URL', 'risbl-default-page-after-user-login'),
                    'default_value' => $this->get_option($user_name, ''),
                    'layout' => 'wp-admin-form-table',
                    'class' => 'regular-text',
                    'part_of' => $setting_page->is_group('specific-user'),
                ]);

            }

            echo '</tbody>';
            echo '</table>';

            // Pagination
            $total_users = $user_query->get_total(); // Total number of users
            $total_pages = ceil($total_users / $users_per_page); // Total pages

            if ($total_pages > 1) {
                echo '<div class="pagination risbl-default-page-after-user-login__pagenav" style="margin-bottom:20px;">';
                echo paginate_links(array(
                    'base'      => add_query_arg('paged', '%#%'), // Use &paged=3 format
                    'format'    => '', // No format needed since base handles it
                    'current'   => max(1, $paged),
                    'total'     => $total_pages,
                    'prev_text' => __('&larr;&nbsp;&nbsp;', 'risbl-default-page-after-user-login'),
                    'next_text' => __('&nbsp;&nbsp;&rarr;', 'risbl-default-page-after-user-login'),
                ));
                echo '</div>';
            }
        } else {
            echo __('No users found.', 'risbl-default-page-after-user-login');
        }

        // Render a nonce field
        echo $field->nonce([
            'id' => '_enable_risbl_login_redirect_for_user_byusername_nonce',
            'action' => 'safe_risbl_enable_risbl_login_redirect_for_user_byusername',
            'part_of' => $setting_page->is_group('specific-user'),
        ]);

        // Create a submit field
        echo $field->submit([
            'id' => 'save-form',
            'label' => __('Save', 'risbl-default-page-after-user-login'),
            'class' => 'button button-primary',
            'part_of' => $setting_page->is_group('specific-user'),
        ]);

        return ob_get_clean();

    }

    /**
     * Registers the plugin setting with WordPress.
     * Uses array storage format with custom sanitization.
     */
    public function register_setting() {
        $args = array(
            'type'              => 'array', // Specify the type as array
            'sanitize_callback' => array($this, 'sanitize_array_val'), // Custom sanitization callback
            'default'           => array(), // Default value as an empty array
        );
        register_setting('_risbl_default_tab_after_user_login', '_risbl_login_redirect', $args);
    }

    /**
     * Sanitizes array values for the plugin settings.
     *
     * @param array $value Input values to sanitize
     * @return array Sanitized URL values
     */
    public function sanitize_array_val($value) {
        if (!is_array($value)) {
            return array(); // Ensure the value is an array
        }

        $sanitized_value = array();
        foreach ($value as $key => $url) {
            // Sanitize the URL using esc_url_raw
            $sanitized_value[$key] = esc_url_raw($url);
        }

        return $sanitized_value;
    }

    // Set a transient flag to show admin notice
    private function set_admin_notice($message, $type = 'success') {
        set_transient('risbl_login_redirect_updated_notice', array(
            'message' => $message,
            'type'    => $type,
        ), 30); // The notice will last for 30 seconds
    }

    // Display admin notice if transient exists
    public function display_admin_notice() {
        // Get the transient data
        $notice = get_transient('risbl_login_redirect_updated_notice');

        if ($notice) {
            $message = $notice['message'];
            $type    = $notice['type'];

            // Determine the notice class based on the type
            $notice_class = ($type === 'error') ? 'notice-error' : 'notice-success';

            // Output the notice
            echo '<div class="notice ' . esc_attr($notice_class) . ' is-dismissible" style="margin-left:0;margin-right:0;">
                    <p>' . esc_html($message) . '</p>
                </div>';

            // Delete the transient after it is displayed
            delete_transient('risbl_login_redirect_updated_notice');
        }
    }
    
}

// Instantiate the class
new Risbl_Default_Page_After_User_Login_Plugin_Admin();