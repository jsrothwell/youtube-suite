<?php
/**
 * Plugin Name: YouTube Suite
 * Plugin URI: https://github.com/jsrothwell/youtube-suite
 * Description: Complete YouTube integration suite - imports, galleries, comments, engagement, analytics, and UX features
 * Version: 2.0.0
 * Author: Jamieson Rothwell
 * License: GPL v2 or later
 * Text Domain: youtube-suite
 */

if (!defined('ABSPATH')) exit;

// Define plugin constants
define('YTS_VERSION', '2.0.0');
define('YTS_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('YTS_PLUGIN_URL', plugin_dir_url(__FILE__));

class YouTube_Suite {

    private static $instance = null;
    private $option_name = 'yts_settings';

    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function __construct() {
        $this->load_dependencies();
        $this->init_hooks();
    }

    private function load_dependencies() {
        // Core modules
        require_once YTS_PLUGIN_DIR . 'includes/class-yts-database.php';
        require_once YTS_PLUGIN_DIR . 'includes/class-yts-importer.php';
        require_once YTS_PLUGIN_DIR . 'includes/class-yts-comments.php';
        require_once YTS_PLUGIN_DIR . 'includes/class-yts-engagement.php';
        require_once YTS_PLUGIN_DIR . 'includes/class-yts-ux.php';
        require_once YTS_PLUGIN_DIR . 'includes/class-yts-gallery.php';
        require_once YTS_PLUGIN_DIR . 'includes/class-yts-admin.php';
        require_once YTS_PLUGIN_DIR . 'includes/class-yts-blocks.php';
        require_once YTS_PLUGIN_DIR . 'includes/class-yts-ajax.php';
    }

    private function init_hooks() {
        register_activation_hook(__FILE__, array($this, 'activate'));
        register_deactivation_hook(__FILE__, array($this, 'deactivate'));

        add_action('plugins_loaded', array($this, 'init'));
        add_action('wp_enqueue_scripts', array($this, 'enqueue_frontend_assets'));
    }

    public function init() {
        // Initialize all modules
        YTS_Database::get_instance();
        YTS_Importer::get_instance();
        YTS_Comments::get_instance();
        YTS_Engagement_Module::get_instance();
        YTS_UX::get_instance();
        YTS_Gallery::get_instance();
        YTS_Admin::get_instance();
        YTS_Blocks::get_instance();
        YTS_Ajax::get_instance();
    }

    public function activate() {
        // Create database tables
        YTS_Database::create_tables();

        // Set default options
        $defaults = array(
            // API Settings
            'api_key' => '',
            'channel_id' => '',

            // Import Settings
            'auto_import' => true,
            'import_frequency' => 'hourly',
            'post_status' => 'publish',
            'post_category' => '',
            'post_tags' => '',
            'embed_video' => true,
            'set_featured_image' => true,
            'update_existing' => false,

            // Gallery Settings
            'layout_type' => 'grid',
            'columns' => 3,
            'videos_per_page' => 12,
            'show_title' => true,
            'show_date' => true,
            'show_duration' => true,

            // Engagement Settings
            'enable_subscribe' => true,
            'enable_email_signup' => true,
            'enable_social_share' => true,
            'enable_analytics' => true,
            'email_double_optin' => false,
            'share_buttons' => array('facebook', 'twitter', 'linkedin'),

            // UX Settings
            'lazy_load' => true,
            'responsive_embeds' => true,
            'enable_search' => true,
            'enable_notification' => true,
            'enable_keyboard' => true,
            'notification_duration' => 7,

            // Comments Settings
            'enable_comments_sync' => false,
        );

        if (!get_option($this->option_name)) {
            add_option($this->option_name, $defaults);
        }

        // Schedule cron for auto-import
        if (!wp_next_scheduled('yts_auto_import')) {
            wp_schedule_event(time(), 'hourly', 'yts_auto_import');
        }

        flush_rewrite_rules();
    }

    public function deactivate() {
        $timestamp = wp_next_scheduled('yts_auto_import');
        if ($timestamp) {
            wp_unschedule_event($timestamp, 'yts_auto_import');
        }
        flush_rewrite_rules();
    }

    public function enqueue_frontend_assets() {
        // Combined CSS
        wp_enqueue_style(
            'yts-frontend',
            YTS_PLUGIN_URL . 'assets/css/frontend.css',
            array(),
            YTS_VERSION
        );

        // Combined JS
        wp_enqueue_script(
            'yts-frontend',
            YTS_PLUGIN_URL . 'assets/js/frontend.js',
            array('jquery'),
            YTS_VERSION,
            true
        );

        // Lazy loading library if enabled
        $settings = get_option($this->option_name, array());
        if (!empty($settings['lazy_load'])) {
            wp_enqueue_script(
                'lozad',
                'https://cdn.jsdelivr.net/npm/lozad@1.16.0/dist/lozad.min.js',
                array(),
                '1.16.0',
                true
            );
        }

        // Localize script
        wp_localize_script('yts-frontend', 'ytsData', array(
            'ajaxUrl' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('yts_nonce'),
            'settings' => $settings
        ));
    }

    public static function get_setting($key, $default = '') {
        $settings = get_option('yts_settings', array());
        return isset($settings[$key]) ? $settings[$key] : $default;
    }

    public static function update_setting($key, $value) {
        $settings = get_option('yts_settings', array());
        $settings[$key] = $value;
        return update_option('yts_settings', $settings);
    }
}

// Initialize the plugin
function yts_init() {
    return YouTube_Suite::get_instance();
}
add_action('plugins_loaded', 'yts_init');

// Utility function for getting YouTube video ID from URL
function yts_get_video_id($url) {
    preg_match('/(?:https?:\/\/)?(?:www\.)?(?:youtube\.com\/(?:[^\/\n\s]+\/\S+\/|(?:v|e(?:mbed)?)\/|\S*?[?&]v=)|youtu\.be\/)([a-zA-Z0-9_-]{11})/', $url, $matches);
    return isset($matches[1]) ? $matches[1] : null;
}

// Utility function for formatting duration
function yts_format_duration($duration) {
    preg_match_all('/(\d+)/', $duration, $parts);
    $time_parts = array_reverse($parts[0]);
    $formatted = '';
    if (isset($time_parts[2])) $formatted = $time_parts[2] . ':';
    $formatted .= isset($time_parts[1]) ? str_pad($time_parts[1], 2, '0', STR_PAD_LEFT) . ':' : '00:';
    $formatted .= isset($time_parts[0]) ? str_pad($time_parts[0], 2, '0', STR_PAD_LEFT) : '00';
    return $formatted;
}

if (class_exists('YTS_Admin')) YTS_Admin::get_instance();
