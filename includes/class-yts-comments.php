<?php
if (!defined('ABSPATH')) exit;

class YTS_Engagement {
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
        if (!YouTube_Suite::get_setting('enable_subscribe')) return '';

        $channel_id = YouTube_Suite::get_setting('channel_id');
        if (empty($channel_id)) return '';

        return '<script src="https://apis.google.com/js/platform.js"></script>
                <div class="g-ytsubscribe" data-channelid="' . esc_attr($channel_id) . '" data-layout="default" data-count="default"></div>';
    }

    public function email_signup_shortcode($atts) {
        if (!YouTube_Suite::get_setting('enable_email_signup')) return '';

        $atts = shortcode_atts(array(
            'title' => 'Subscribe to Updates',
            'button_text' => 'Subscribe'
        ), $atts);

        ob_start();
        ?>
        <div class="yts-email-signup">
            <h3><?php echo esc_html($atts['title']); ?></h3>
            <form class="yts-email-form">
                <input type="email" name="email" placeholder="Your Email" required>
                <button type="submit"><?php echo esc_html($atts['button_text']); ?></button>
                <div class="yts-message"></div>
            </form>
        </div>
        <?php
        return ob_get_clean();
    }

    public function social_share_shortcode($atts) {
        if (!YouTube_Suite::get_setting('enable_social_share')) return '';

        $url = urlencode(get_permalink());
        $title = urlencode(get_the_title());

        return '<div class="yts-social-share">
                <a href="https://www.facebook.com/sharer.php?u=' . $url . '" target="_blank">Facebook</a>
                <a href="https://twitter.com/intent/tweet?url=' . $url . '&text=' . $title . '" target="_blank">Twitter</a>
                <a href="https://www.linkedin.com/sharing/share-offsite/?url=' . $url . '" target="_blank">LinkedIn</a>
                </div>';
    }
}
?>
