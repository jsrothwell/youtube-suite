<?php
/**
 * Single Post Layout Handler
 */

if (!defined('ABSPATH')) exit;

if (!class_exists('YTS_Single_Post')) {
class YTS_Single_Post {
    private static $instance = null;

    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function __construct() {
        add_filter('the_content', array($this, 'modify_single_post_content'), 20);
    }

    public function modify_single_post_content($content) {
        // Only apply to single posts with video metadata
        if (!is_single() || !get_post_meta(get_the_ID(), 'yt_video_id', true)) {
            return $content;
        }

        $video_id = get_post_meta(get_the_ID(), 'yt_video_id', true);
        $video_size = YouTube_Suite::get_setting('single_video_size', 'large');
        $video_position = YouTube_Suite::get_setting('single_video_position', 'top');
        $show_details = YouTube_Suite::get_setting('show_video_details', true);
        $show_description = YouTube_Suite::get_setting('show_video_description', true);
        $show_related = YouTube_Suite::get_setting('show_related_videos', false);

        // Build the video embed
        $video_html = $this->get_video_embed($video_id, $video_size);

        // Add video details if enabled
        if ($show_details) {
            $video_html .= $this->get_video_details($video_id);
        }

        // Add video description if enabled
        if ($show_description) {
            $video_html .= $this->get_video_description();
        }

        // Add related videos if enabled
        if ($show_related) {
            $video_html .= $this->get_related_videos();
        }

        // Apply position setting
        switch ($video_position) {
            case 'top':
                return $video_html . $content;
            case 'bottom':
                return $content . $video_html;
            case 'replace':
                return $video_html;
            default:
                return $video_html . $content;
        }
    }

    private function get_video_embed($video_id, $size) {
        $sizes = array(
            'small' => array('width' => 560, 'height' => 315),
            'medium' => array('width' => 720, 'height' => 405),
            'large' => array('width' => 960, 'height' => 540),
            'full' => array('width' => '100%', 'height' => '56.25%') // 16:9 ratio
        );

        $dimensions = isset($sizes[$size]) ? $sizes[$size] : $sizes['large'];
        
        if ($size === 'full') {
            $html = '<div class="yts-video-wrapper yts-video-full">';
            $html .= '<div class="yts-responsive-video">';
            $html .= '<iframe src="https://www.youtube.com/embed/' . esc_attr($video_id) . '" ';
            $html .= 'frameborder="0" allowfullscreen></iframe>';
            $html .= '</div></div>';
        } else {
            $html = '<div class="yts-video-wrapper yts-video-' . esc_attr($size) . '">';
            $html .= '<iframe width="' . esc_attr($dimensions['width']) . '" ';
            $html .= 'height="' . esc_attr($dimensions['height']) . '" ';
            $html .= 'src="https://www.youtube.com/embed/' . esc_attr($video_id) . '" ';
            $html .= 'frameborder="0" allowfullscreen></iframe>';
            $html .= '</div>';
        }

        return $html;
    }

    private function get_video_details($video_id) {
        $duration = get_post_meta(get_the_ID(), 'yt_video_duration', true);
        $thumbnail = get_post_meta(get_the_ID(), 'yt_video_thumbnail', true);
        
        $html = '<div class="yts-video-details">';
        
        if ($duration) {
            $formatted_duration = yts_format_duration($duration);
            $html .= '<span class="yts-video-duration">⏱️ ' . esc_html($formatted_duration) . '</span>';
        }
        
        $html .= '<span class="yts-video-date">📅 ' . get_the_date() . '</span>';
        $html .= '</div>';
        
        return $html;
    }

    private function get_video_description() {
        $content = get_the_content();
        
        // Extract description from content if it was wrapped in a div
        if (preg_match('/<div class="yts-video-description">(.*?)<\/div>/s', $content, $matches)) {
            return '<div class="yts-video-description-section">' . $matches[1] . '</div>';
        }
        
        return '';
    }

    private function get_related_videos() {
        $count = YouTube_Suite::get_setting('related_videos_count', 3);
        $current_post_id = get_the_ID();

        $related_posts = get_posts(array(
            'post_type' => 'post',
            'posts_per_page' => $count,
            'post__not_in' => array($current_post_id),
            'meta_query' => array(
                array('key' => 'yt_video_id', 'compare' => 'EXISTS')
            ),
            'orderby' => 'rand'
        ));

        if (empty($related_posts)) {
            return '';
        }

        $html = '<div class="yts-related-videos">';
        $html .= '<h3>' . __('Related Videos', 'youtube-suite') . '</h3>';
        $html .= '<div class="yts-related-grid">';

        foreach ($related_posts as $post) {
            $thumbnail_url = get_the_post_thumbnail_url($post->ID, 'medium');
            $video_url = get_permalink($post->ID);
            
            $html .= '<div class="yts-related-item">';
            $html .= '<a href="' . esc_url($video_url) . '">';
            if ($thumbnail_url) {
                $html .= '<img src="' . esc_url($thumbnail_url) . '" alt="' . esc_attr($post->post_title) . '">';
            }
            $html .= '<h4>' . esc_html($post->post_title) . '</h4>';
            $html .= '</a>';
            $html .= '</div>';
        }

        $html .= '</div></div>';

        return $html;
    }
}
}
?>
