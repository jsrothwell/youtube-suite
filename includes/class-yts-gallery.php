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
            'posts_per_page' => YouTube_Suite::get_setting('videos_per_page', 12)
        ), $atts);

        $paged = get_query_var('paged') ? get_query_var('paged') : 1;

        $query = new WP_Query(array(
            'post_type' => 'post',
            'posts_per_page' => $atts['posts_per_page'],
            'paged' => $paged,
            'meta_query' => array(array('key' => 'yt_video_id', 'compare' => 'EXISTS'))
        ));

        if (!$query->have_posts()) return '<p>No videos found.</p>';

        ob_start();
        echo '<div class="yts-gallery yts-layout-' . esc_attr($atts['layout']) . ' yts-cols-' . esc_attr($atts['columns']) . '">';

        while ($query->have_posts()) {
            $query->the_post();
            $video_id = get_post_meta(get_the_ID(), 'yt_video_id', true);
            $thumbnail = get_the_post_thumbnail_url(get_the_ID(), 'large');

            echo '<div class="yts-gallery-item">';
            echo '<a href="' . get_permalink() . '">';
            if ($thumbnail) {
                echo '<img src="' . esc_url($thumbnail) . '" alt="' . esc_attr(get_the_title()) . '">';
            }
            echo '<h3>' . get_the_title() . '</h3>';
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
