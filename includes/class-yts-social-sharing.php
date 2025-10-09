<?php
/**
 * Advanced Social Sharing Module
 * Similar to Social Warfare / Monarch
 */

if (!defined('ABSPATH')) exit;

if (!class_exists('YTS_Social_Sharing')) {
class YTS_Social_Sharing {
    private static $instance = null;

    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function __construct() {
        // Content filters
        add_filter('the_content', array($this, 'add_inline_share_buttons'), 100);
        
        // Shortcodes
        add_shortcode('click_to_tweet', array($this, 'click_to_tweet_shortcode'));
        add_shortcode('share_buttons', array($this, 'share_buttons_shortcode'));
        
        // Footer action for floating bar
        add_action('wp_footer', array($this, 'add_floating_share_bar'));
        
        // AJAX for share tracking
        add_action('wp_ajax_yts_track_share', array($this, 'track_share'));
        add_action('wp_ajax_nopriv_yts_track_share', array($this, 'track_share'));
    }

    /**
     * Add inline share buttons to content
     */
    public function add_inline_share_buttons($content) {
        if (!is_single() || !YouTube_Suite::get_setting('enable_social_share')) {
            return $content;
        }

        $position = YouTube_Suite::get_setting('share_button_position', 'both');
        $buttons = $this->get_share_buttons('inline');

        switch ($position) {
            case 'top':
                return $buttons . $content;
            case 'bottom':
                return $content . $buttons;
            case 'both':
                return $buttons . $content . $buttons;
            default:
                return $content;
        }
    }

    /**
     * Add floating share bar
     */
    public function add_floating_share_bar() {
        if (!is_single() || !YouTube_Suite::get_setting('enable_floating_share_bar')) {
            return;
        }

        $position = YouTube_Suite::get_setting('floating_bar_position', 'left');
        $buttons = $this->get_share_buttons('floating', $position);
        
        echo '<div class="yts-floating-share-bar yts-floating-' . esc_attr($position) . '">';
        echo $buttons;
        echo '</div>';
    }

    /**
     * Generate share buttons HTML
     */
    private function get_share_buttons($style = 'inline', $position = 'left') {
        $networks = YouTube_Suite::get_setting('share_networks', array('facebook', 'twitter', 'linkedin', 'pinterest'));
        $button_style = YouTube_Suite::get_setting('share_button_style', 'flat');
        $show_counts = YouTube_Suite::get_setting('show_share_counts', true);
        
        $post_url = urlencode(get_permalink());
        $post_title = urlencode(get_the_title());
        $post_image = urlencode(get_the_post_thumbnail_url(get_the_ID(), 'large'));

        $html = '<div class="yts-share-buttons yts-share-' . esc_attr($style) . ' yts-style-' . esc_attr($button_style) . '">';
        
        if ($style === 'inline') {
            $html .= '<span class="yts-share-label">' . __('Share this:', 'youtube-suite') . '</span>';
        }

        foreach ($networks as $network) {
            $html .= $this->get_single_button($network, $post_url, $post_title, $post_image, $show_counts);
        }

        $html .= '</div>';
        return $html;
    }

    /**
     * Generate single share button
     */
    private function get_single_button($network, $url, $title, $image, $show_counts) {
        $share_urls = array(
            'facebook' => 'https://www.facebook.com/sharer/sharer.php?u=' . $url,
            'twitter' => 'https://twitter.com/intent/tweet?url=' . $url . '&text=' . $title,
            'linkedin' => 'https://www.linkedin.com/sharing/share-offsite/?url=' . $url,
            'pinterest' => 'https://pinterest.com/pin/create/button/?url=' . $url . '&media=' . $image . '&description=' . $title,
            'reddit' => 'https://reddit.com/submit?url=' . $url . '&title=' . $title,
            'whatsapp' => 'https://api.whatsapp.com/send?text=' . $title . '%20' . $url,
            'email' => 'mailto:?subject=' . $title . '&body=' . $url,
            'copy' => '#'
        );

        $labels = array(
            'facebook' => 'Facebook',
            'twitter' => 'Twitter',
            'linkedin' => 'LinkedIn',
            'pinterest' => 'Pinterest',
            'reddit' => 'Reddit',
            'whatsapp' => 'WhatsApp',
            'email' => 'Email',
            'copy' => 'Copy Link'
        );

        // Font Awesome icons
        $icons = array(
            'facebook' => 'fab fa-facebook-f',
            'twitter' => 'fab fa-twitter',
            'linkedin' => 'fab fa-linkedin-in',
            'pinterest' => 'fab fa-pinterest-p',
            'reddit' => 'fab fa-reddit-alien',
            'whatsapp' => 'fab fa-whatsapp',
            'email' => 'fas fa-envelope',
            'copy' => 'fas fa-link'
        );

        if (!isset($share_urls[$network])) {
            return '';
        }

        $share_url = $share_urls[$network];
        $label = $labels[$network];
        $icon = $icons[$network];
        
        $onclick = ($network === 'copy') ? 'return ytsShareCopyLink(this);' : 'return ytsShareWindow(this);';
        
        $html = '<a href="' . esc_url($share_url) . '" ';
        $html .= 'class="yts-share-button yts-share-' . esc_attr($network) . '" ';
        $html .= 'data-network="' . esc_attr($network) . '" ';
        $html .= 'onclick="' . $onclick . '" ';
        $html .= 'target="_blank" rel="noopener noreferrer">';
        $html .= '<span class="yts-share-icon"><i class="' . esc_attr($icon) . '"></i></span>';
        $html .= '<span class="yts-share-text">' . esc_html($label) . '</span>';
        
        if ($show_counts) {
            $count = $this->get_share_count($network);
            if ($count > 0) {
                $html .= '<span class="yts-share-count">' . $this->format_count($count) . '</span>';
            }
        }
        
        $html .= '</a>';
        
        return $html;
    }

    /**
     * Click to Tweet shortcode
     */
    public function click_to_tweet_shortcode($atts, $content = '') {
        $atts = shortcode_atts(array(
            'tweet' => $content,
            'via' => YouTube_Suite::get_setting('twitter_username', ''),
            'style' => 'default'
        ), $atts);

        if (empty($atts['tweet'])) {
            return '';
        }

        $tweet_text = urlencode(strip_tags($atts['tweet']));
        $via = !empty($atts['via']) ? '&via=' . urlencode($atts['via']) : '';
        $url = urlencode(get_permalink());
        $twitter_url = 'https://twitter.com/intent/tweet?text=' . $tweet_text . '&url=' . $url . $via;

        $html = '<div class="yts-click-to-tweet yts-ctt-' . esc_attr($atts['style']) . '">';
        $html .= '<div class="yts-ctt-text">' . wp_kses_post($content) . '</div>';
        $html .= '<a href="' . esc_url($twitter_url) . '" class="yts-ctt-button" target="_blank" rel="noopener noreferrer" onclick="return ytsShareWindow(this);">';
        $html .= '<i class="fab fa-twitter"></i> ' . __('Click to Tweet', 'youtube-suite');
        $html .= '</a></div>';

        return $html;
    }

    /**
     * Share buttons shortcode
     */
    public function share_buttons_shortcode($atts) {
        $atts = shortcode_atts(array(
            'style' => 'inline',
            'networks' => 'facebook,twitter,linkedin'
        ), $atts);

        return $this->get_share_buttons($atts['style']);
    }

    /**
     * Track share via AJAX
     */
    public function track_share() {
        check_ajax_referer('yts_nonce', 'nonce');

        $network = isset($_POST['network']) ? sanitize_text_field($_POST['network']) : '';
        $post_id = isset($_POST['post_id']) ? intval($_POST['post_id']) : 0;

        if (!$network || !$post_id) {
            wp_send_json_error();
        }

        // Track in analytics
        if (YouTube_Suite::get_setting('enable_analytics')) {
            $db = YTS_Database::get_instance();
            $db->log_analytics('social_share', array(
                'post_id' => $post_id,
                'additional_data' => json_encode(array('network' => $network))
            ));
        }

        // Update share count meta
        $count_key = 'yts_share_count_' . $network;
        $current_count = get_post_meta($post_id, $count_key, true);
        $new_count = intval($current_count) + 1;
        update_post_meta($post_id, $count_key, $new_count);

        wp_send_json_success(array('count' => $new_count));
    }

    /**
     * Get share count for a network
     */
    private function get_share_count($network) {
        $count_key = 'yts_share_count_' . $network;
        return intval(get_post_meta(get_the_ID(), $count_key, true));
    }

    /**
     * Format count for display (1k, 1.5k, etc.)
     */
    private function format_count($count) {
        if ($count < 1000) {
            return $count;
        } elseif ($count < 1000000) {
            return round($count / 1000, 1) . 'k';
        } else {
            return round($count / 1000000, 1) . 'M';
        }
    }
}
}
?>
