<?php
if (!defined('ABSPATH')) exit;
if ( ! class_exists( 'YTS_UX' ) ) {
class YTS_UX {
    private static $instance = null;

    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function __construct() {
        add_shortcode('video_search', array($this, 'search_shortcode'));
        add_shortcode('latest_videos', array($this, 'latest_videos_shortcode'));
        add_filter('the_content', array($this, 'make_videos_responsive'));
    }

    public function search_shortcode($atts) {
        if (!YouTube_Suite::get_setting('enable_search')) return '';

        return '<div class="yts-search">
                <form class="yts-search-form">
                    <input type="search" placeholder="Search videos..." name="s">
                    <button type="submit">Search</button>
                </form>
                <div class="yts-search-results"></div>
                </div>';
    }

    public function latest_videos_shortcode($atts) {
        $atts = shortcode_atts(array('count' => 6, 'columns' => 3), $atts);

        $posts = get_posts(array(
            'post_type' => 'post',
            'posts_per_page' => $atts['count'],
            'meta_query' => array(array('key' => 'yt_video_id', 'compare' => 'EXISTS'))
        ));

        if (empty($posts)) return '<p>No videos found.</p>';

        ob_start();
        echo '<div class="yts-latest-videos yts-cols-' . esc_attr($atts['columns']) . '">';
        foreach ($posts as $post) {
            echo '<div class="yts-video-item">';
            echo '<a href="' . get_permalink($post) . '">';
            if (has_post_thumbnail($post)) {
                echo get_the_post_thumbnail($post, 'medium');
            }
            echo '<h4>' . get_the_title($post) . '</h4>';
            echo '</a></div>';
        }
        echo '</div>';
        return ob_get_clean();
    }

    public function make_videos_responsive($content) {
        if (!YouTube_Suite::get_setting('responsive_embeds')) return $content;

        return preg_replace(
            '/<iframe[^>]*youtube[^>]*><\/iframe>/i',
            '<div class="yts-responsive-video">$0</div>',
            $content
        );
    }
}
}
?>
