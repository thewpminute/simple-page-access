<?php
/**
 * Plugin Name: Simple Page Access
 * Plugin URI: https://thewpminute.com/
 * Description: Control access to pages and posts for logged-in users with specific roles.
 * Version: 1.0.0
 * Author: WP Minute
 * Author URI: https://thewpminute.com/
 * License: GPL v2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: simple-page-access
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Main plugin class
 */
class Simple_Page_Access {

    /**
     * Constructor
     */
    public function __construct() {
        // Register meta fields
        add_action('init', array($this, 'register_meta_fields'));

        // Enqueue block editor assets
        add_action('enqueue_block_editor_assets', array($this, 'enqueue_editor_assets'));

        // Check access on page load
        add_action('template_redirect', array($this, 'check_page_access'));
    }

    /**
     * Register post meta fields
     */
    public function register_meta_fields() {
        // Register meta for restriction enabled
        register_post_meta('post', 'spa_restrict_access', array(
            'show_in_rest' => true,
            'single' => true,
            'type' => 'boolean',
            'default' => false,
            'auth_callback' => function() {
                return current_user_can('edit_posts');
            }
        ));

        register_post_meta('page', 'spa_restrict_access', array(
            'show_in_rest' => true,
            'single' => true,
            'type' => 'boolean',
            'default' => false,
            'auth_callback' => function() {
                return current_user_can('edit_posts');
            }
        ));

        // Register meta for allowed roles
        register_post_meta('post', 'spa_allowed_roles', array(
            'show_in_rest' => array(
                'schema' => array(
                    'type' => 'array',
                    'items' => array(
                        'type' => 'string'
                    )
                )
            ),
            'single' => true,
            'type' => 'array',
            'default' => array(),
            'auth_callback' => function() {
                return current_user_can('edit_posts');
            }
        ));

        register_post_meta('page', 'spa_allowed_roles', array(
            'show_in_rest' => array(
                'schema' => array(
                    'type' => 'array',
                    'items' => array(
                        'type' => 'string'
                    )
                )
            ),
            'single' => true,
            'type' => 'array',
            'default' => array(),
            'auth_callback' => function() {
                return current_user_can('edit_posts');
            }
        ));
    }

    /**
     * Enqueue block editor assets
     */
    public function enqueue_editor_assets() {
        global $post;

        // Only load on posts and pages
        if (!in_array($post->post_type, array('post', 'page'))) {
            return;
        }

        wp_enqueue_script(
            'simple-page-access-editor',
            plugin_dir_url(__FILE__) . 'js/editor.js',
            array(
                'wp-plugins',
                'wp-edit-post',
                'wp-element',
                'wp-components',
                'wp-data',
                'wp-i18n'
            ),
            '1.0.0',
            true
        );

        // Pass available roles to JavaScript
        wp_localize_script('simple-page-access-editor', 'spaData', array(
            'roles' => $this->get_available_roles()
        ));
    }

    /**
     * Get available WordPress roles
     */
    private function get_available_roles() {
        $roles = wp_roles()->roles;
        $available_roles = array();

        foreach ($roles as $role_key => $role) {
            $available_roles[] = array(
                'value' => $role_key,
                'label' => $role['name']
            );
        }

        return $available_roles;
    }

    /**
     * Check page access and redirect if necessary
     */
    public function check_page_access() {
        // Only check on singular posts and pages
        if (!is_singular(array('post', 'page'))) {
            return;
        }

        $post_id = get_the_ID();

        // Check if restriction is enabled
        $restrict_access = get_post_meta($post_id, 'spa_restrict_access', true);

        if (!$restrict_access) {
            return;
        }

        // Admins always have access
        if (current_user_can('administrator')) {
            return;
        }

        // Check if user is logged in
        if (!is_user_logged_in()) {
            $this->trigger_404();
            return;
        }

        // Get allowed roles and validate them
        $allowed_roles = get_post_meta($post_id, 'spa_allowed_roles', true);

        // If no roles are selected, any logged-in user can access
        if (empty($allowed_roles) || !is_array($allowed_roles)) {
            return;
        }

        // Validate that the stored roles actually exist in WordPress
        $allowed_roles = $this->validate_roles($allowed_roles);

        // If no valid roles remain after validation, allow any logged-in user
        if (empty($allowed_roles)) {
            return;
        }

        // Check if current user has any of the allowed roles
        $current_user = wp_get_current_user();
        $user_roles = $current_user->roles;

        $has_access = false;
        foreach ($user_roles as $user_role) {
            if (in_array($user_role, $allowed_roles)) {
                $has_access = true;
                break;
            }
        }

        // Show 404 if user doesn't have access
        if (!$has_access) {
            $this->trigger_404();
        }
    }

    /**
     * Validate that roles exist in WordPress
     *
     * @param array $roles Array of role slugs to validate
     * @return array Filtered array containing only valid roles
     */
    private function validate_roles($roles) {
        if (!is_array($roles)) {
            return array();
        }

        $valid_roles = array_keys(wp_roles()->roles);

        // Filter out any roles that don't exist in WordPress
        return array_intersect($roles, $valid_roles);
    }

    /**
     * Properly trigger a 404 error
     */
    private function trigger_404() {
        global $wp_query;

        // Set the query as 404
        $wp_query->set_404();

        // Clear any existing post data to prevent information leakage
        $wp_query->post = null;
        $wp_query->posts = array();
        $wp_query->post_count = 0;
        $wp_query->current_post = -1;
        $wp_query->found_posts = 0;

        // Set the proper HTTP status
        status_header(404);
        nocache_headers();
    }
}

// Initialize the plugin
new Simple_Page_Access();
