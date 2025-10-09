<?php
if (!defined('ABSPATH')) exit;

// Wrap the class declaration in a check to prevent fatal errors
if ( ! class_exists( 'YTS_Engagement_Module' ) ) {

class YTS_Engagement_Module {

    private static $instance = null;

    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function __construct() {
        add_shortcode('yts_subscribe', array($this, 'subscribe_shortcode'));
        add_shortcode('yts_email_signup', array($this, 'email_signup_shortcode'));
        add_shortcode('yts_social_share', array($this, 'social_share_shortcode'));
    }

    public function subscribe_shortcode($atts) {
        $channel_id = YouTube_Suite::get_setting('channel_id');
        if (empty($channel_id)) {
            return '';
        }

        $atts = shortcode_atts(array(
            'layout' => 'default',
            'theme' => 'default',
            'count' => 'default'
        ), $atts);

        $output = '<div class="yts-subscribe-button">';
        $output .= '<script src="https://apis.google.com/js/platform.js"></script>';
        $output .= '<div class="g-ytsubscribe" ';
        $output .= 'data-channelid="' . esc_attr($channel_id) . '" ';
        $output .= 'data-layout="' . esc_attr($atts['layout']) . '" ';
        $output .= 'data-theme="' . esc_attr($atts['theme']) . '" ';
        $output .= 'data-count="' . esc_attr($atts['count']) . '">';
        $output .= '</div></div>';

        return $output;
    }

    public function email_signup_shortcode($atts) {
        $atts = shortcode_atts(array(
            'title' => 'Subscribe to Updates',
            'button_text' => 'Subscribe'
        ), $atts);

        $output = '<div class="yts-email-signup">';
        $output .= '<h3>' . esc_html($atts['title']) . '</h3>';
        $output .= '<form class="yts-email-form">';
        $output .= '<input type="email" name="email" placeholder="Your Email" required>';
        $output .= '<button type="submit">' . esc_html($atts['button_text']) . '</button>';
        $output .= '<div class="yts-form-message"></div>';
        $output .= '</form></div>';

        return $output;
    }

    public function social_share_shortcode($atts) {
        $url = urlencode(get_permalink());
        $title = urlencode(get_the_title());

        $output = '<div class="yts-social-share">';
        $output .= '<a href="https://www.facebook.com/sharer.php?u=' . $url . '" target="_blank">Facebook</a> ';
        $output .= '<a href="https://twitter.com/intent/tweet?url=' . $url . '&text=' . $title . '" target="_blank">Twitter</a> ';
        $output .= '<a href="https://www.linkedin.com/sharing/share-offsite/?url=' . $url . '" target="_blank">LinkedIn</a>';
        $output .= '</div>';

        return $output;
    }
}
}
