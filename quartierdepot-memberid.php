<?php
/**
 * Plugin Name: Quartierdepot Member ID
 * Plugin URI: https://github.com/quartier-depot/quartierdepot-memberid
 * Description: Displays membership ID in the WooCommerce account area
 * Version: 0.0.1
 * Author: Quartierdepot
 * Author URI: https://github.com/quartier-depot
 * Requires at least: 5.8
 * Requires PHP: 7.4
 */

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

class quartierdepot_memberid {
    /**
     * Constructor
     */
    public function __construct() {
        // Add endpoint
        add_action('init', array($this, 'add_endpoint'));
        
        // Add content
        add_action('woocommerce_account_memberid_endpoint', array($this, 'memberid_endpoint_content'));

        // Add AJAX handlers
        add_action('wp_ajax_delete_member_id', array($this, 'handle_delete_member_id'));
        add_action('wp_ajax_generate_member_id', array($this, 'handle_generate_member_id'));

        // Add script enqueue
        add_action('wp_enqueue_scripts', array($this, 'enqueue_scripts'));
    }

    /**
     * Add endpoint
     */
    public function add_endpoint() {
        add_rewrite_endpoint('memberid', EP_ROOT | EP_PAGES);
    }

    /**
     * Endpoint content
     */
    public function memberid_endpoint_content() {
        $user_id = get_current_user_id();
        $memberid = get_field('member_id', 'user_' . $user_id);
        
        wc_get_template(
            'memberid-screen.php',
            array(
                'memberid' => $memberid,
                'user_id' => $user_id
            ),
            'qd-memberid/',
            plugin_dir_path(__FILE__) . 'templates/'
        );
    }

    public function handle_delete_member_id() {
        // Verify nonce
        if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'delete_member_id')) {
            error_log('Nonce verification failed');
            wp_send_json_error('Invalid nonce');
        }

        // Get current user
        $user_id = get_current_user_id();
        if (!$user_id) {
            error_log('No user ID found');
            wp_send_json_error('User not logged in');
        }

        // Delete the member ID
        $result = update_field('member_id', null, 'user_' . $user_id);
        error_log('Update field result: ' . ($result ? 'success' : 'failed'));

        if ($result) {
            wp_send_json_success('Member ID deleted successfully');
        } else {
            wp_send_json_error('Failed to delete member ID');
        }
    }

    /**
     * Generate a unique member ID
     * Format: MXXXXXXXXXX where X is a random digit (10 digits)
     */
    private function generate_unique_member_id() {
        do {
            // Generate 10 random digits
            $number = str_pad(mt_rand(0, 9999999999), 10, '0', STR_PAD_LEFT);
            $member_id = 'M' . $number;
            
            // Check if this ID already exists
            $existing_users = get_users(array(
                'meta_key' => 'member_id',
                'meta_value' => $member_id,
                'fields' => 'ID'
            ));
        } while (!empty($existing_users));

        return $member_id;
    }

    /**
     * Handle member ID generation
     */
    public function handle_generate_member_id() {
        // Verify nonce
        if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'generate_member_id')) {
            error_log('Nonce verification failed for generate');
            wp_send_json_error('Invalid nonce');
        }

        // Get current user
        $user_id = get_current_user_id();
        if (!$user_id) {
            error_log('No user ID found for generate');
            wp_send_json_error('User not logged in');
        }

        // Check if user already has a member ID
        $existing_id = get_field('member_id', 'user_' . $user_id);
        if ($existing_id) {
            wp_send_json_error('User already has a member ID');
            return;
        }

        // Generate new member ID
        $member_id = $this->generate_unique_member_id();
        
        // Save the member ID
        $result = update_field('member_id', $member_id, 'user_' . $user_id);
        error_log('Generate member ID result: ' . ($result ? 'success' : 'failed'));

        if ($result) {
            wp_send_json_success(array(
                'message' => 'Member ID generated successfully',
                'member_id' => $member_id
            ));
        } else {
            wp_send_json_error('Failed to generate member ID');
        }
    }

    /**
     * Enqueue required scripts
     */
    public function enqueue_scripts() {
        // Only enqueue on the member ID page
        if (is_account_page() && isset($_GET['memberid'])) {
            wp_enqueue_script(
                'jsbarcode',
                plugins_url('js/JsBarcode.all.min.js', __FILE__),
                array('jquery'),
                '3.11.5',
                true
            );
        }
    }
}

// Initialize the plugin
function qd_memberid_init() {
    new quartierdepot_memberid();
}
add_action('plugins_loaded', 'qd_memberid_init');

// Activation hook
register_activation_hook(__FILE__, 'qd_memberid_activate');
function qd_memberid_activate() {
    add_rewrite_endpoint('memberid', EP_ROOT | EP_PAGES);
    flush_rewrite_rules();
}

// Deactivation hook
register_deactivation_hook(__FILE__, 'qd_memberid_deactivate');
function qd_memberid_deactivate() {
    flush_rewrite_rules();
}