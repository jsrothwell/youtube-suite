<?php
/**
 * Unified Admin Interface
 */

if (!defined('ABSPATH')) exit;
if ( ! class_exists( 'YTS_Admin' ) ) {
class YTS_Admin {

    private static $instance = null;
    private $option_name = 'yts_settings';

    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function __construct() {
        add_action('admin_menu', array($this, 'add_admin_menu'));
        add_action('admin_init', array($this, 'register_settings'));
        add_action('admin_enqueue_scripts', array($this, 'enqueue_admin_assets'));
        add_action('admin_notices', array($this, 'check_database_status'));
    }

    /**
     * Check database status and show notice if tables are missing
     */
    public function check_database_status() {
        $screen = get_current_screen();
        if (strpos($screen->id, 'youtube-suite') === false) {
            return;
        }

        $db = YTS_Database::get_instance();
        if (!$db->tables_exist()) {
            echo '<div class="notice notice-warning is-dismissible">';
            echo '<p><strong>YouTube Suite:</strong> Database tables are missing. ';
            echo '<a href="' . admin_url('admin.php?page=youtube-suite&action=repair_db') . '" class="button button-small">Repair Database</a>';
            echo '</p></div>';
        }
    }

    public function add_admin_menu() {
        // Main menu
        add_menu_page(
            __('YouTube Suite', 'youtube-suite'),
            __('YouTube Suite', 'youtube-suite'),
            'manage_options',
            'youtube-suite',
            array($this, 'render_dashboard'),
            'dashicons-youtube',
            30
        );

        // Dashboard
        add_submenu_page(
            'youtube-suite',
            __('Dashboard', 'youtube-suite'),
            __('Dashboard', 'youtube-suite'),
            'manage_options',
            'youtube-suite',
            array($this, 'render_dashboard')
        );

        // Settings
        add_submenu_page(
            'youtube-suite',
            __('Settings', 'youtube-suite'),
            __('Settings', 'youtube-suite'),
            'manage_options',
            'youtube-suite-settings',
            array($this, 'render_settings')
        );

        // Subscribers
        add_submenu_page(
            'youtube-suite',
            __('Subscribers', 'youtube-suite'),
            __('Subscribers', 'youtube-suite'),
            'manage_options',
            'youtube-suite-subscribers',
            array($this, 'render_subscribers')
        );

        // Analytics
        add_submenu_page(
            'youtube-suite',
            __('Analytics', 'youtube-suite'),
            __('Analytics', 'youtube-suite'),
            'manage_options',
            'youtube-suite-analytics',
            array($this, 'render_analytics')
        );
    }

    public function register_settings() {
        register_setting('yts_settings_group', $this->option_name);
    }

    public function enqueue_admin_assets($hook) {
        if (strpos($hook, 'youtube-suite') === false) {
            return;
        }

        wp_enqueue_style(
            'yts-admin',
            YTS_PLUGIN_URL . 'assets/css/admin.css',
            array(),
            YTS_VERSION
        );

        wp_enqueue_script(
            'yts-admin',
            YTS_PLUGIN_URL . 'assets/js/admin.js',
            array('jquery'),
            YTS_VERSION,
            true
        );

        wp_enqueue_style('wp-color-picker');
        wp_enqueue_script('wp-color-picker');
    }

    public function render_dashboard() {
        // Handle database repair
        if (isset($_GET['action']) && $_GET['action'] === 'repair_db' && current_user_can('manage_options')) {
            YTS_Database::create_tables();
            echo '<div class="notice notice-success is-dismissible"><p>' . __('Database tables created successfully!', 'youtube-suite') . '</p></div>';
        }

        $db = YTS_Database::get_instance();
        $active_subscribers = $db->get_subscriber_count('active');
        $total_videos = wp_count_posts('post')->publish;

        ?>
        <div class="wrap yts-admin">
            <h1><?php _e('YouTube Suite Dashboard', 'youtube-suite'); ?></h1>

            <div class="yts-dashboard-grid">
                <!-- Stats Cards -->
                <div class="yts-card yts-card-primary">
                    <div class="yts-card-icon">📹</div>
                    <h3><?php echo esc_html($total_videos); ?></h3>
                    <p><?php _e('Videos Imported', 'youtube-suite'); ?></p>
                </div>

                <div class="yts-card yts-card-success">
                    <div class="yts-card-icon">📧</div>
                    <h3><?php echo esc_html($active_subscribers); ?></h3>
                    <p><?php _e('Email Subscribers', 'youtube-suite'); ?></p>
                </div>

                <div class="yts-card yts-card-info">
                    <div class="yts-card-icon">👁️</div>
                    <h3><?php echo esc_html($db->get_analytics_count(null, date('Y-m-d', strtotime('-30 days')))); ?></h3>
                    <p><?php _e('Engagements (30d)', 'youtube-suite'); ?></p>
                </div>

                <div class="yts-card yts-card-warning">
                    <div class="yts-card-icon">🎯</div>
                    <h3><?php echo YouTube_Suite::get_setting('channel_id') ? '✓' : '✗'; ?></h3>
                    <p><?php _e('API Connected', 'youtube-suite'); ?></p>
                </div>
            </div>

            <!-- Quick Actions -->
            <div class="yts-section">
                <h2><?php _e('Quick Actions', 'youtube-suite'); ?></h2>
                <p class="description" style="margin-bottom: 15px;">
                    <?php _e('Import Videos: Fetches new videos from your YouTube channel. Refresh All: Updates ALL existing video posts with latest YouTube data (title, description, thumbnail).', 'youtube-suite'); ?>
                </p>
                <div class="yts-actions">
                    <form method="post" style="display: inline;">
                        <input type="hidden" name="yts_action" value="import_now">
                        <?php wp_nonce_field('yts_import_now'); ?>
                        <button type="submit" class="button button-primary">
                            🔄 <?php _e('Import Videos Now', 'youtube-suite'); ?>
                        </button>
                    </form>

                    <form method="post" style="display: inline;">
                        <input type="hidden" name="yts_action" value="refresh_all">
                        <?php wp_nonce_field('yts_refresh_all'); ?>
                        <button type="submit" class="button button-secondary">
                            ♻️ <?php _e('Refresh All Posts', 'youtube-suite'); ?>
                        </button>
                    </form>

                    <a href="<?php echo admin_url('admin.php?page=youtube-suite&action=repair_db'); ?>" class="button">
                        🔧 <?php _e('Repair Database', 'youtube-suite'); ?>
                    </a>

                    <a href="<?php echo admin_url('admin.php?page=youtube-suite-settings'); ?>" class="button">
                        ⚙️ <?php _e('Settings', 'youtube-suite'); ?>
                    </a>
                </div>
            </div>

            <!-- Shortcodes Guide -->
            <div class="yts-section">
                <h2><?php _e('Available Shortcodes', 'youtube-suite'); ?></h2>
                <div class="yts-shortcodes">
                    <div class="yts-shortcode-item">
                        <code>[youtube_gallery]</code>
                        <p><?php _e('Display video gallery with various layouts', 'youtube-suite'); ?></p>
                    </div>
                    <div class="yts-shortcode-item">
                        <code>[youtube_comments video_url="..."]</code>
                        <p><?php _e('Display YouTube comments for a video', 'youtube-suite'); ?></p>
                    </div>
                    <div class="yts-shortcode-item">
                        <code>[video_search]</code>
                        <p><?php _e('Add video search functionality', 'youtube-suite'); ?></p>
                    </div>
                    <div class="yts-shortcode-item">
                        <code>[latest_videos count="6"]</code>
                        <p><?php _e('Show latest imported videos', 'youtube-suite'); ?></p>
                    </div>
                </div>
            </div>
        </div>
        <?php

        // Handle actions
        if (isset($_POST['yts_action'])) {
            if ($_POST['yts_action'] === 'import_now' && check_admin_referer('yts_import_now')) {
                $result = YTS_Importer::get_instance()->import_videos();
                $message = sprintf(
                    __('Import completed! New videos: %d, Updated: %d', 'youtube-suite'),
                    $result['new'],
                    $result['updated']
                );
                echo '<div class="notice notice-success"><p>' . $message . '</p></div>';
            }
            if ($_POST['yts_action'] === 'refresh_all' && check_admin_referer('yts_refresh_all')) {
                $result = YTS_Importer::get_instance()->refresh_all_posts();
                $message = sprintf(
                    __('Refresh completed! %d posts updated with latest YouTube data.', 'youtube-suite'),
                    $result['updated']
                );
                echo '<div class="notice notice-success"><p>' . $message . '</p></div>';
            }
        }
    }

    public function render_settings() {
        if (isset($_POST['yts_save_settings'])) {
            check_admin_referer('yts_settings_save');

            $settings = array(
                // API
                'api_key' => sanitize_text_field($_POST['api_key']),
                'channel_id' => sanitize_text_field($_POST['channel_id']),

                // Import
                'auto_import' => isset($_POST['auto_import']),
                'import_frequency' => sanitize_text_field($_POST['import_frequency']),
                'post_status' => sanitize_text_field($_POST['post_status']),
                'embed_video' => isset($_POST['embed_video']),
                'set_featured_image' => isset($_POST['set_featured_image']),
                'update_existing' => isset($_POST['update_existing']),

                // Gallery
                'layout_type' => sanitize_text_field($_POST['layout_type']),
                'columns' => intval($_POST['columns']),
                'videos_per_page' => intval($_POST['videos_per_page']),
                'show_thumbnails' => isset($_POST['show_thumbnails']),
                'show_video_title' => isset($_POST['show_video_title']),
                'show_video_date' => isset($_POST['show_video_date']),
                
                // Single Post
                'single_video_size' => sanitize_text_field($_POST['single_video_size']),
                'single_video_position' => sanitize_text_field($_POST['single_video_position']),
                'show_video_details' => isset($_POST['show_video_details']),
                'show_video_description' => isset($_POST['show_video_description']),
                'show_related_videos' => isset($_POST['show_related_videos']),
                'related_videos_count' => intval($_POST['related_videos_count']),

                // Engagement
                'enable_subscribe' => isset($_POST['enable_subscribe']),
                'enable_email_signup' => isset($_POST['enable_email_signup']),
                'enable_social_share' => isset($_POST['enable_social_share']),
                'enable_analytics' => isset($_POST['enable_analytics']),
                'email_double_optin' => isset($_POST['email_double_optin']),
                
                // Social Sharing
                'share_button_position' => sanitize_text_field($_POST['share_button_position']),
                'share_button_style' => sanitize_text_field($_POST['share_button_style']),
                'show_share_counts' => isset($_POST['show_share_counts']),
                'enable_floating_share_bar' => isset($_POST['enable_floating_share_bar']),
                'floating_bar_position' => sanitize_text_field($_POST['floating_bar_position']),
                'share_networks' => isset($_POST['share_networks']) ? (array)$_POST['share_networks'] : array(),
                'twitter_username' => sanitize_text_field($_POST['twitter_username']),

                // UX
                'lazy_load' => isset($_POST['lazy_load']),
                'responsive_embeds' => isset($_POST['responsive_embeds']),
                'enable_search' => isset($_POST['enable_search']),
                'enable_notification' => isset($_POST['enable_notification']),
                'notification_duration' => intval($_POST['notification_duration']),

                // Comments
                'enable_comments_sync' => isset($_POST['enable_comments_sync']),
            );

            update_option($this->option_name, $settings);
            echo '<div class="notice notice-success"><p>' . __('Settings saved!', 'youtube-suite') . '</p></div>';
        }

        $settings = get_option($this->option_name, array());
        $g = function($key, $default = '') use ($settings) {
            return isset($settings[$key]) ? $settings[$key] : $default;
        };

        ?>
        <div class="wrap yts-admin">
            <h1><?php _e('YouTube Suite Settings', 'youtube-suite'); ?></h1>

            <form method="post" action="">
                <?php wp_nonce_field('yts_settings_save'); ?>

                <!-- Tabs -->
                <div class="yts-tabs">
                    <button type="button" class="yts-tab active" data-tab="api">🔑 API</button>
                    <button type="button" class="yts-tab" data-tab="import">📥 Import</button>
                    <button type="button" class="yts-tab" data-tab="gallery">🎨 Gallery</button>
                    <button type="button" class="yts-tab" data-tab="single">📺 Single Post</button>
                    <button type="button" class="yts-tab" data-tab="engagement">💬 Engagement</button>
                    <button type="button" class="yts-tab" data-tab="ux">✨ UX</button>
                    <button type="button" class="yts-tab" data-tab="comments">💭 Comments</button>
                </div>

                <!-- API Settings -->
                <div class="yts-tab-content active" data-tab="api">
                    <table class="form-table">
                        <tr>
                            <th><?php _e('YouTube API Key', 'youtube-suite'); ?> *</th>
                            <td>
                                <input type="text" name="api_key" value="<?php echo esc_attr($g('api_key')); ?>" class="regular-text" required>
                                <p class="description"><?php _e('Get from Google Developers Console', 'youtube-suite'); ?></p>
                            </td>
                        </tr>
                        <tr>
                            <th><?php _e('YouTube Channel ID', 'youtube-suite'); ?> *</th>
                            <td>
                                <input type="text" name="channel_id" value="<?php echo esc_attr($g('channel_id')); ?>" class="regular-text" required>
                                <p class="description"><?php _e('Find in YouTube Studio → Settings → Channel → Advanced', 'youtube-suite'); ?></p>
                            </td>
                        </tr>
                    </table>
                </div>

                <!-- Import Settings -->
                <div class="yts-tab-content" data-tab="import">
                    <table class="form-table">
                        <tr>
                            <th><?php _e('Auto Import', 'youtube-suite'); ?></th>
                            <td>
                                <label>
                                    <input type="checkbox" name="auto_import" value="1" <?php checked($g('auto_import'), 1); ?>>
                                    <?php _e('Automatically import new videos', 'youtube-suite'); ?>
                                </label>
                            </td>
                        </tr>
                        <tr>
                            <th><?php _e('Check Frequency', 'youtube-suite'); ?></th>
                            <td>
                                <select name="import_frequency">
                                    <option value="hourly" <?php selected($g('import_frequency', 'hourly'), 'hourly'); ?>><?php _e('Every Hour', 'youtube-suite'); ?></option>
                                    <option value="twicedaily" <?php selected($g('import_frequency', 'hourly'), 'twicedaily'); ?>><?php _e('Twice Daily', 'youtube-suite'); ?></option>
                                    <option value="daily" <?php selected($g('import_frequency', 'hourly'), 'daily'); ?>><?php _e('Once Daily', 'youtube-suite'); ?></option>
                                </select>
                            </td>
                        </tr>
                        <tr>
                            <th><?php _e('Post Status', 'youtube-suite'); ?></th>
                            <td>
                                <select name="post_status">
                                    <option value="publish" <?php selected($g('post_status', 'publish'), 'publish'); ?>><?php _e('Publish', 'youtube-suite'); ?></option>
                                    <option value="draft" <?php selected($g('post_status', 'publish'), 'draft'); ?>><?php _e('Draft', 'youtube-suite'); ?></option>
                                </select>
                            </td>
                        </tr>
                        <tr>
                            <th><?php _e('Options', 'youtube-suite'); ?></th>
                            <td>
                                <label><input type="checkbox" name="embed_video" value="1" <?php checked($g('embed_video'), 1); ?>> <?php _e('Embed video in post', 'youtube-suite'); ?></label><br>
                                <label><input type="checkbox" name="set_featured_image" value="1" <?php checked($g('set_featured_image'), 1); ?>> <?php _e('Set featured image', 'youtube-suite'); ?></label><br>
                                <label><input type="checkbox" name="update_existing" value="1" <?php checked($g('update_existing'), 1); ?>> <?php _e('Update existing posts when re-importing', 'youtube-suite'); ?></label>
                            </td>
                        </tr>
                    </table>
                </div>

                <!-- Gallery Settings -->
                <div class="yts-tab-content" data-tab="gallery">
                    <h3><?php _e('Gallery Display Settings', 'youtube-suite'); ?></h3>
                    <p class="description"><?php _e('Configure how video galleries appear on archive pages and when using the [youtube_gallery] shortcode.', 'youtube-suite'); ?></p>
                    <table class="form-table">
                        <tr>
                            <th><?php _e('Layout Type', 'youtube-suite'); ?></th>
                            <td>
                                <select name="layout_type">
                                    <option value="grid" <?php selected($g('layout_type', 'grid'), 'grid'); ?>><?php _e('Grid', 'youtube-suite'); ?></option>
                                    <option value="carousel" <?php selected($g('layout_type', 'grid'), 'carousel'); ?>><?php _e('Carousel', 'youtube-suite'); ?></option>
                                    <option value="list" <?php selected($g('layout_type', 'grid'), 'list'); ?>><?php _e('List', 'youtube-suite'); ?></option>
                                </select>
                                <p class="description"><?php _e('Choose how videos are displayed in galleries', 'youtube-suite'); ?></p>
                            </td>
                        </tr>
                        <tr>
                            <th><?php _e('Columns', 'youtube-suite'); ?></th>
                            <td>
                                <select name="columns">
                                    <option value="2" <?php selected($g('columns', 3), 2); ?>>2</option>
                                    <option value="3" <?php selected($g('columns', 3), 3); ?>>3</option>
                                    <option value="4" <?php selected($g('columns', 3), 4); ?>>4</option>
                                </select>
                                <p class="description"><?php _e('Number of columns in grid layout', 'youtube-suite'); ?></p>
                            </td>
                        </tr>
                        <tr>
                            <th><?php _e('Videos Per Page', 'youtube-suite'); ?></th>
                            <td>
                                <input type="number" name="videos_per_page" value="<?php echo esc_attr($g('videos_per_page', 12)); ?>" min="1" max="50">
                                <p class="description"><?php _e('Number of videos to display per page', 'youtube-suite'); ?></p>
                            </td>
                        </tr>
                        <tr>
                            <th><?php _e('Display Options', 'youtube-suite'); ?></th>
                            <td>
                                <label><input type="checkbox" name="show_thumbnails" value="1" <?php checked($g('show_thumbnails', true), 1); ?>> <?php _e('Show Thumbnails', 'youtube-suite'); ?></label><br>
                                <label><input type="checkbox" name="show_video_title" value="1" <?php checked($g('show_video_title', true), 1); ?>> <?php _e('Show Video Titles', 'youtube-suite'); ?></label><br>
                                <label><input type="checkbox" name="show_video_date" value="1" <?php checked($g('show_video_date', true), 1); ?>> <?php _e('Show Publish Date', 'youtube-suite'); ?></label>
                            </td>
                        </tr>
                    </table>
                </div>

                <!-- Single Post Settings -->
                <div class="yts-tab-content" data-tab="single">
                    <h3><?php _e('Single Post Video Display', 'youtube-suite'); ?></h3>
                    <p class="description"><?php _e('Configure how videos appear on individual post pages.', 'youtube-suite'); ?></p>
                    <table class="form-table">
                        <tr>
                            <th><?php _e('Video Size', 'youtube-suite'); ?></th>
                            <td>
                                <select name="single_video_size">
                                    <option value="small" <?php selected($g('single_video_size', 'large'), 'small'); ?>><?php _e('Small (560x315)', 'youtube-suite'); ?></option>
                                    <option value="medium" <?php selected($g('single_video_size', 'large'), 'medium'); ?>><?php _e('Medium (720x405)', 'youtube-suite'); ?></option>
                                    <option value="large" <?php selected($g('single_video_size', 'large'), 'large'); ?>><?php _e('Large (960x540)', 'youtube-suite'); ?></option>
                                    <option value="full" <?php selected($g('single_video_size', 'large'), 'full'); ?>><?php _e('Full Width (100%)', 'youtube-suite'); ?></option>
                                </select>
                                <p class="description"><?php _e('Choose the video player size for single posts', 'youtube-suite'); ?></p>
                            </td>
                        </tr>
                        <tr>
                            <th><?php _e('Video Position', 'youtube-suite'); ?></th>
                            <td>
                                <select name="single_video_position">
                                    <option value="top" <?php selected($g('single_video_position', 'top'), 'top'); ?>><?php _e('Top of Content', 'youtube-suite'); ?></option>
                                    <option value="bottom" <?php selected($g('single_video_position', 'top'), 'bottom'); ?>><?php _e('Bottom of Content', 'youtube-suite'); ?></option>
                                    <option value="replace" <?php selected($g('single_video_position', 'top'), 'replace'); ?>><?php _e('Replace Content (Video Only)', 'youtube-suite'); ?></option>
                                </select>
                                <p class="description"><?php _e('Where to display the video in relation to post content', 'youtube-suite'); ?></p>
                            </td>
                        </tr>
                        <tr>
                            <th><?php _e('Video Details', 'youtube-suite'); ?></th>
                            <td>
                                <label><input type="checkbox" name="show_video_details" value="1" <?php checked($g('show_video_details', true), 1); ?>> <?php _e('Show video metadata (duration, views, etc.)', 'youtube-suite'); ?></label>
                                <p class="description"><?php _e('Display additional information about the video', 'youtube-suite'); ?></p>
                            </td>
                        </tr>
                        <tr>
                            <th><?php _e('Video Description', 'youtube-suite'); ?></th>
                            <td>
                                <label><input type="checkbox" name="show_video_description" value="1" <?php checked($g('show_video_description', true), 1); ?>> <?php _e('Show YouTube description below video', 'youtube-suite'); ?></label>
                                <p class="description"><?php _e('Display the original YouTube video description', 'youtube-suite'); ?></p>
                            </td>
                        </tr>
                        <tr>
                            <th><?php _e('Related Videos', 'youtube-suite'); ?></th>
                            <td>
                                <label><input type="checkbox" name="show_related_videos" value="1" <?php checked($g('show_related_videos'), 1); ?>> <?php _e('Show related videos from your channel', 'youtube-suite'); ?></label><br>
                                <label style="margin-top: 10px; display: inline-block;">
                                    <?php _e('Number of related videos:', 'youtube-suite'); ?>
                                    <input type="number" name="related_videos_count" value="<?php echo esc_attr($g('related_videos_count', 3)); ?>" min="1" max="12" style="width: 60px;">
                                </label>
                                <p class="description"><?php _e('Display other videos from your channel at the bottom of the post', 'youtube-suite'); ?></p>
                            </td>
                        </tr>
                    </table>
                </div>

                <!-- Engagement Settings -->
                <div class="yts-tab-content" data-tab="engagement">
                    <h3><?php _e('Engagement Features', 'youtube-suite'); ?></h3>
                    <table class="form-table">
                        <tr>
                            <th><?php _e('Features', 'youtube-suite'); ?></th>
                            <td>
                                <label><input type="checkbox" name="enable_subscribe" value="1" <?php checked($g('enable_subscribe'), 1); ?>> <?php _e('Subscribe Button', 'youtube-suite'); ?></label><br>
                                <label><input type="checkbox" name="enable_email_signup" value="1" <?php checked($g('enable_email_signup'), 1); ?>> <?php _e('Email Signup Forms', 'youtube-suite'); ?></label><br>
                                <label><input type="checkbox" name="enable_social_share" value="1" <?php checked($g('enable_social_share'), 1); ?>> <?php _e('Social Share Buttons', 'youtube-suite'); ?></label><br>
                                <label><input type="checkbox" name="enable_analytics" value="1" <?php checked($g('enable_analytics'), 1); ?>> <?php _e('Analytics Tracking', 'youtube-suite'); ?></label>
                            </td>
                        </tr>
                        <tr>
                            <th><?php _e('Email Settings', 'youtube-suite'); ?></th>
                            <td>
                                <label><input type="checkbox" name="email_double_optin" value="1" <?php checked($g('email_double_optin'), 1); ?>> <?php _e('Require email confirmation', 'youtube-suite'); ?></label>
                            </td>
                        </tr>
                    </table>

                    <hr style="margin: 30px 0;">

                    <h3><?php _e('Social Sharing Configuration', 'youtube-suite'); ?></h3>
                    <p class="description"><?php _e('Advanced social sharing similar to Social Warfare - includes inline buttons, floating bars, and click-to-tweet functionality.', 'youtube-suite'); ?></p>
                    
                    <table class="form-table">
                        <tr>
                            <th><?php _e('Share Button Position', 'youtube-suite'); ?></th>
                            <td>
                                <select name="share_button_position">
                                    <option value="top" <?php selected($g('share_button_position', 'both'), 'top'); ?>><?php _e('Above Content', 'youtube-suite'); ?></option>
                                    <option value="bottom" <?php selected($g('share_button_position', 'both'), 'bottom'); ?>><?php _e('Below Content', 'youtube-suite'); ?></option>
                                    <option value="both" <?php selected($g('share_button_position', 'both'), 'both'); ?>><?php _e('Above & Below', 'youtube-suite'); ?></option>
                                    <option value="none" <?php selected($g('share_button_position', 'both'), 'none'); ?>><?php _e('None (Manual)', 'youtube-suite'); ?></option>
                                </select>
                                <p class="description"><?php _e('Where to display inline share buttons', 'youtube-suite'); ?></p>
                            </td>
                        </tr>
                        <tr>
                            <th><?php _e('Button Style', 'youtube-suite'); ?></th>
                            <td>
                                <select name="share_button_style">
                                    <option value="flat" <?php selected($g('share_button_style', 'flat'), 'flat'); ?>><?php _e('Flat (Solid Colors)', 'youtube-suite'); ?></option>
                                    <option value="gradient" <?php selected($g('share_button_style', 'flat'), 'gradient'); ?>><?php _e('Gradient', 'youtube-suite'); ?></option>
                                    <option value="outlined" <?php selected($g('share_button_style', 'flat'), 'outlined'); ?>><?php _e('Outlined', 'youtube-suite'); ?></option>
                                </select>
                                <p class="description"><?php _e('Visual style of share buttons', 'youtube-suite'); ?></p>
                            </td>
                        </tr>
                        <tr>
                            <th><?php _e('Share Networks', 'youtube-suite'); ?></th>
                            <td>
                                <?php 
                                $selected_networks = $g('share_networks', array('facebook', 'twitter', 'linkedin', 'pinterest'));
                                if (!is_array($selected_networks)) $selected_networks = array();
                                ?>
                                <label><input type="checkbox" name="share_networks[]" value="facebook" <?php checked(in_array('facebook', $selected_networks)); ?>> 📘 Facebook</label><br>
                                <label><input type="checkbox" name="share_networks[]" value="twitter" <?php checked(in_array('twitter', $selected_networks)); ?>> 🐦 Twitter</label><br>
                                <label><input type="checkbox" name="share_networks[]" value="linkedin" <?php checked(in_array('linkedin', $selected_networks)); ?>> 💼 LinkedIn</label><br>
                                <label><input type="checkbox" name="share_networks[]" value="pinterest" <?php checked(in_array('pinterest', $selected_networks)); ?>> 📌 Pinterest</label><br>
                                <label><input type="checkbox" name="share_networks[]" value="reddit" <?php checked(in_array('reddit', $selected_networks)); ?>> 🤖 Reddit</label><br>
                                <label><input type="checkbox" name="share_networks[]" value="whatsapp" <?php checked(in_array('whatsapp', $selected_networks)); ?>> 💬 WhatsApp</label><br>
                                <label><input type="checkbox" name="share_networks[]" value="email" <?php checked(in_array('email', $selected_networks)); ?>> ✉️ Email</label><br>
                                <label><input type="checkbox" name="share_networks[]" value="copy" <?php checked(in_array('copy', $selected_networks)); ?>> 🔗 Copy Link</label>
                                <p class="description"><?php _e('Select which social networks to display', 'youtube-suite'); ?></p>
                            </td>
                        </tr>
                        <tr>
                            <th><?php _e('Floating Share Bar', 'youtube-suite'); ?></th>
                            <td>
                                <label><input type="checkbox" name="enable_floating_share_bar" value="1" <?php checked($g('enable_floating_share_bar'), 1); ?>> <?php _e('Enable floating sidebar share buttons', 'youtube-suite'); ?></label><br>
                                <div style="margin-top: 10px;">
                                    <label><?php _e('Position:', 'youtube-suite'); ?></label>
                                    <select name="floating_bar_position">
                                        <option value="left" <?php selected($g('floating_bar_position', 'left'), 'left'); ?>><?php _e('Left Side', 'youtube-suite'); ?></option>
                                        <option value="right" <?php selected($g('floating_bar_position', 'left'), 'right'); ?>><?php _e('Right Side', 'youtube-suite'); ?></option>
                                    </select>
                                </div>
                                <p class="description"><?php _e('Sticky share bar that follows as users scroll (hidden on mobile)', 'youtube-suite'); ?></p>
                            </td>
                        </tr>
                        <tr>
                            <th><?php _e('Share Counts', 'youtube-suite'); ?></th>
                            <td>
                                <label><input type="checkbox" name="show_share_counts" value="1" <?php checked($g('show_share_counts', true), 1); ?>> <?php _e('Display share counts on buttons', 'youtube-suite'); ?></label>
                                <p class="description"><?php _e('Shows how many times content has been shared', 'youtube-suite'); ?></p>
                            </td>
                        </tr>
                        <tr>
                            <th><?php _e('Twitter Username', 'youtube-suite'); ?></th>
                            <td>
                                <input type="text" name="twitter_username" value="<?php echo esc_attr($g('twitter_username')); ?>" class="regular-text" placeholder="@yourusername">
                                <p class="description"><?php _e('Optional: Your Twitter username for "via @username" in tweets', 'youtube-suite'); ?></p>
                            </td>
                        </tr>
                    </table>

                    <div style="background: #e8f4f8; padding: 20px; border-left: 4px solid #1da1f2; margin-top: 20px;">
                        <h4 style="margin-top: 0;"><?php _e('💡 How to Use Social Sharing', 'youtube-suite'); ?></h4>
                        <p><strong><?php _e('Automatic:', 'youtube-suite'); ?></strong> <?php _e('Share buttons will automatically appear on video posts based on your position settings.', 'youtube-suite'); ?></p>
                        <p><strong><?php _e('Shortcodes:', 'youtube-suite'); ?></strong></p>
                        <ul style="margin-left: 20px;">
                            <li><code>[share_buttons]</code> - <?php _e('Insert share buttons anywhere', 'youtube-suite'); ?></li>
                            <li><code>[click_to_tweet]Your tweetable quote here[/click_to_tweet]</code> - <?php _e('Create click-to-tweet boxes', 'youtube-suite'); ?></li>
                        </ul>
                    </div>
                </div>

                <!-- UX Settings -->
                <div class="yts-tab-content" data-tab="ux">
                    <table class="form-table">
                        <tr>
                            <th><?php _e('Performance', 'youtube-suite'); ?></th>
                            <td>
                                <label><input type="checkbox" name="lazy_load" value="1" <?php checked($g('lazy_load'), 1); ?>> <?php _e('Lazy Load Videos', 'youtube-suite'); ?></label><br>
                                <label><input type="checkbox" name="responsive_embeds" value="1" <?php checked($g('responsive_embeds'), 1); ?>> <?php _e('Responsive Embeds', 'youtube-suite'); ?></label>
                            </td>
                        </tr>
                        <tr>
                            <th><?php _e('Features', 'youtube-suite'); ?></th>
                            <td>
                                <label><input type="checkbox" name="enable_search" value="1" <?php checked($g('enable_search'), 1); ?>> <?php _e('Video Search', 'youtube-suite'); ?></label><br>
                                <label><input type="checkbox" name="enable_notification" value="1" <?php checked($g('enable_notification'), 1); ?>> <?php _e('Notification Bar', 'youtube-suite'); ?></label>
                            </td>
                        </tr>
                        <tr>
                            <th><?php _e('Notification Duration', 'youtube-suite'); ?></th>
                            <td>
                                <input type="number" name="notification_duration" value="<?php echo esc_attr($g('notification_duration', 7)); ?>" min="1" max="30"> <?php _e('days', 'youtube-suite'); ?>
                            </td>
                        </tr>
                    </table>
                </div>

                <!-- Comments Settings -->
                <div class="yts-tab-content" data-tab="comments">
                    <table class="form-table">
                        <tr>
                            <th><?php _e('Enable Comments Sync', 'youtube-suite'); ?></th>
                            <td>
                                <label>
                                    <input type="checkbox" name="enable_comments_sync" value="1" <?php checked($g('enable_comments_sync'), 1); ?>>
                                    <?php _e('Fetch and display YouTube comments', 'youtube-suite'); ?>
                                </label>
                            </td>
                        </tr>
                    </table>
                </div>

                <p class="submit">
                    <input type="submit" name="yts_save_settings" class="button button-primary" value="<?php _e('Save All Settings', 'youtube-suite'); ?>">
                </p>
            </form>
        </div>
        <?php
    }

    public function render_subscribers() {
        $db = YTS_Database::get_instance();

        // Handle export
        if (isset($_POST['action']) && $_POST['action'] === 'export_subscribers') {
            $this->export_subscribers();
        }

        $per_page = 20;
        $page = isset($_GET['paged']) ? max(1, intval($_GET['paged'])) : 1;
        $offset = ($page - 1) * $per_page;

        $subscribers = $db->get_active_subscribers($per_page, $offset);
        $total_count = $db->get_subscriber_count('active');
        $total_pages = ceil($total_count / $per_page);

        ?>
        <div class="wrap yts-admin">
            <h1><?php _e('Email Subscribers', 'youtube-suite'); ?></h1>

            <div class="yts-stats-row">
                <div class="yts-stat-box">
                    <h3><?php echo esc_html($db->get_subscriber_count('active')); ?></h3>
                    <p><?php _e('Active', 'youtube-suite'); ?></p>
                </div>
                <div class="yts-stat-box">
                    <h3><?php echo esc_html($db->get_subscriber_count('pending')); ?></h3>
                    <p><?php _e('Pending', 'youtube-suite'); ?></p>
                </div>
                <div class="yts-stat-box">
                    <h3><?php echo esc_html($db->get_subscriber_count('unsubscribed')); ?></h3>
                    <p><?php _e('Unsubscribed', 'youtube-suite'); ?></p>
                </div>
            </div>

            <form method="post">
                <input type="hidden" name="action" value="export_subscribers">
                <button type="submit" class="button"><?php _e('Export to CSV', 'youtube-suite'); ?></button>
            </form>

            <table class="wp-list-table widefat fixed striped" style="margin-top: 20px;">
                <thead>
                    <tr>
                        <th><?php _e('Email', 'youtube-suite'); ?></th>
                        <th><?php _e('Name', 'youtube-suite'); ?></th>
                        <th><?php _e('Status', 'youtube-suite'); ?></th>
                        <th><?php _e('Date', 'youtube-suite'); ?></th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($subscribers)): ?>
                        <tr><td colspan="4"><?php _e('No subscribers yet.', 'youtube-suite'); ?></td></tr>
                    <?php else: ?>
                        <?php foreach ($subscribers as $sub): ?>
                            <tr>
                                <td><?php echo esc_html($sub->email); ?></td>
                                <td><?php echo esc_html($sub->name); ?></td>
                                <td><?php echo esc_html($sub->status); ?></td>
                                <td><?php echo esc_html(date('Y-m-d', strtotime($sub->subscribed_date))); ?></td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
        <?php
    }

    public function render_analytics() {
        $db = YTS_Database::get_instance();
        $start_date = date('Y-m-d', strtotime('-30 days'));

        ?>
        <div class="wrap yts-admin">
            <h1><?php _e('Analytics Dashboard', 'youtube-suite'); ?></h1>

            <div class="yts-stats-row">
                <div class="yts-stat-box">
                    <h3><?php echo esc_html($db->get_analytics_count('subscribe_click', $start_date)); ?></h3>
                    <p><?php _e('Subscribe Clicks', 'youtube-suite'); ?></p>
                </div>
                <div class="yts-stat-box">
                    <h3><?php echo esc_html($db->get_analytics_count('email_signup', $start_date)); ?></h3>
                    <p><?php _e('Email Signups', 'youtube-suite'); ?></p>
                </div>
                <div class="yts-stat-box">
                    <h3><?php echo esc_html($db->get_analytics_count('social_share', $start_date)); ?></h3>
                    <p><?php _e('Social Shares', 'youtube-suite'); ?></p>
                </div>
                <div class="yts-stat-box">
                    <h3><?php echo esc_html($db->get_analytics_count('cta_click', $start_date)); ?></h3>
                    <p><?php _e('CTA Clicks', 'youtube-suite'); ?></p>
                </div>
            </div>

            <p><?php _e('Last 30 days', 'youtube-suite'); ?></p>
        </div>
        <?php
    }

    private function export_subscribers() {
        $db = YTS_Database::get_instance();
        $subscribers = $db->get_active_subscribers();

        header('Content-Type: text/csv');
        header('Content-Disposition: attachment; filename="subscribers-' . date('Y-m-d') . '.csv"');

        $output = fopen('php://output', 'w');
        fputcsv($output, array('Email', 'Name', 'Status', 'Date'));

        foreach ($subscribers as $subscriber) {
            fputcsv($output, array(
                $subscriber->email,
                $subscriber->name,
                $subscriber->status,
                $subscriber->subscribed_date
            ));
        }

        fclose($output);
        exit;
    }
}
}
?>
