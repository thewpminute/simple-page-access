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

        // Remove inaccessible posts/pages from query results (archives, search, feeds, embeds).
        add_filter('the_posts', array($this, 'filter_inaccessible_posts'), 10, 2);

        // Block restricted content in REST API responses.
        add_filter('rest_prepare_post', array($this, 'filter_rest_response'), 10, 3);
        add_filter('rest_prepare_page', array($this, 'filter_rest_response'), 10, 3);
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

        if ($post_id && !$this->user_can_access_post($post_id)) {
            $this->trigger_404();
        }
    }

    /**
     * Remove inaccessible posts/pages from query results.
     *
     * @param array    $posts Queried posts.
     * @param WP_Query $query WP Query object.
     * @return array
     */
    public function filter_inaccessible_posts($posts, $query) {
        if (empty($posts) || !is_array($posts)) {
            return $posts;
        }

        $filtered = array();
        foreach ($posts as $post) {
            if (!isset($post->ID, $post->post_type)) {
                $filtered[] = $post;
                continue;
            }

            if (!in_array($post->post_type, array('post', 'page'), true)) {
                $filtered[] = $post;
                continue;
            }

            if ($this->user_can_access_post((int) $post->ID)) {
                $filtered[] = $post;
            }
        }

        return $filtered;
    }

    /**
     * Restrict post/page REST API responses.
     *
     * @param WP_REST_Response $response Response object.
     * @param WP_Post          $post     Post object.
     * @param WP_REST_Request  $request  Request object.
     * @return WP_REST_Response|WP_Error
     */
    public function filter_rest_response($response, $post, $request) {
        if (!isset($post->ID)) {
            return $response;
        }

        if (!$this->user_can_access_post((int) $post->ID)) {
            return new WP_Error(
                'spa_rest_forbidden',
                __('Sorry, you are not allowed to access this content.', 'simple-page-access'),
                array('status' => 404)
            );
        }

        return $response;
    }

    /**
     * Determine whether the current user can access a post/page.
     *
     * @param int $post_id Post ID.
     * @return bool
     */
    private function user_can_access_post($post_id) {
        // Check if restriction is enabled.
        $restrict_access = get_post_meta($post_id, 'spa_restrict_access', true);
        if (!$restrict_access) {
            return true;
        }

        // Capability-based admin bypass.
        if (current_user_can('manage_options')) {
            return true;
        }

        if (!is_user_logged_in()) {
            return false;
        }

        $allowed_roles = get_post_meta($post_id, 'spa_allowed_roles', true);

        // Explicitly allow any logged-in user when no roles are configured.
        if (empty($allowed_roles)) {
            return true;
        }

        // Malformed role data should fail closed.
        if (!is_array($allowed_roles)) {
            return false;
        }

        $allowed_roles = $this->validate_roles($allowed_roles);

        // Invalid role configuration should fail closed.
        if (empty($allowed_roles)) {
            return false;
        }

        $current_user = wp_get_current_user();
        $user_roles = is_array($current_user->roles) ? $current_user->roles : array();

        foreach ($user_roles as $user_role) {
            if (in_array($user_role, $allowed_roles, true)) {
                return true;
            }
        }

        return false;
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

        $roles = array_map('sanitize_key', $roles);
        $valid_roles = array_keys(wp_roles()->roles);

        // Filter out any roles that don't exist in WordPress
        return array_values(array_intersect($roles, $valid_roles));
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
