= Error notes: =

#1. Ada error di sini:

    Lokasi: include/class/class-login-admin.php
    Detail error: Saat fitur diaktifkan, lalu ditambahkan custom role, lalu dinonaktifkan, custom role jadi tidak mau tampil semua. Setelah diaktifkan kembali, custom role tetap tidak aktif.
    Solusi: Tambahkan proper sanitation before data submission.
    Status: Temporary fixed.

    ```
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

            $sanitized_data = array_map('sanitize_text_field', $option_risbl_login_redirect);

            update_option('_risbl_login_redirect', $sanitized_data);

            // Trigger a flag for displaying the admin notice
            $this->set_admin_notice(__('Updated!', 'risbl-default-page-after-user-login'));

        endif;

    }
    ```