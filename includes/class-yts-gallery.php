<?php
if (!defined('ABSPATH')) exit;

if ( ! class_exists( 'YTS_Gallery' ) ) {
class YTS_Gallery {
    private static $instance = null;

    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function __construct() {
        add_shortcode('youtube_gallery', array($this, 'gallery_shortcode'));
    }

    public function gallery_shortcode($atts) {
        $atts = shortcode_atts(array(
            'layout' => YouTube_Suite::get_setting('layout_type', 'grid'),
            'columns' => YouTube_Suite::get_setting('columns', 3),
            'posts_per_page' => YouTube_Suite::get_setting('videos_per_page', 12),
            'post_type' => 'all' // NEW: 'all', 'videos', or 'standard'
        ), $atts);

        $paged = get_query_var('paged') ? get_query_var('paged') : 1;

        // Build query args
        $query_args = array(
            'post_type' => 'post',
            'posts_per_page' => intval($atts['posts_per_page']),
            'paged' => $paged,
            'orderby' => 'date',
            'order' => 'DESC'
        );

        // Add meta query based on post_type parameter
        if ($atts['post_type'] === 'videos') {
            // Only show posts with video ID
            $query_args['meta_query'] = array(
                array('key' => 'yt_video_id', 'compare' => 'EXISTS')
            );
        } elseif ($atts['post_type'] === 'standard') {
            // Only show posts WITHOUT video ID
            $query_args['meta_query'] = array(
                array('key' => 'yt_video_id', 'compare' => 'NOT EXISTS')
            );
        }
        // If 'all', no meta_query - shows everything

        $query = new WP_Query($query_args);

        if (!$query->have_posts()) {
            return '<p>No posts found.</p>';
        }

        ob_start();
        echo '<div class="yts-gallery yts-layout-' . esc_attr($atts['layout']) . ' yts-cols-' . esc_attr($atts['columns']) . '">';

        while ($query->have_posts()) {
            $query->the_post();
            $video_id = get_post_meta(get_the_ID(), 'yt_video_id', true);
            $thumbnail = get_the_post_thumbnail_url(get_the_ID(), 'large');
            
            // Get duration only if it's a video post
            $duration = '';
            if ($video_id) {
                $duration = get_post_meta(get_the_ID(), 'yt_video_duration', true);
            }

            echo '<div class="yts-gallery-item">';
            echo '<a href="' . esc_url(get_permalink()) . '">';
            
            if ($thumbnail) {
                echo '<div class="yts-gallery-thumbnail">';
                echo '<img src="' . esc_url($thumbnail) . '" alt="' . esc_attr(get_the_title()) . '">';
                
                // Only show duration badge if it's a video post with duration
                if ($video_id && $duration) {
                    echo '<span class="yts-duration">' . esc_html(yts_format_duration($duration)) . '</span>';
                }
                
                echo '</div>';
            }
            
            echo '<h3>' . esc_html(get_the_title()) . '</h3>';
            echo '</a></div>';
        }

        echo '</div>';

        if ($query->max_num_pages > 1) {
            echo '<div class="yts-pagination">';
            echo paginate_links(array('total' => $query->max_num_pages, 'current' => $paged));
            echo '</div>';
        }

        wp_reset_postdata();
        return ob_get_clean();
    }
}
}
?>
