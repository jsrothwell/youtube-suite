<?php
// CACHE BUST v2.0
if (!defined('ABSPATH')) exit;

class YTS_Engagement {

  /**
 * Engagement Features Module
 */

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
        add_shortcode('yts_cta_button', array($this, 'cta_button_shortcode'));
    }

    public function subscribe_shortcode($atts) {
        if (!YouTube_Suite::get_setting('enable_subscribe')) {
            return '';
        }

        $channel_id = YouTube_Suite::get_setting('channel_id');
        if (empty($channel_id)) {
            return '';
        }

        $atts = shortcode_atts(array(
            'layout' => 'default',
            'theme' => 'default',
            'count' => 'default'
        ), $atts);

        ob_start();
        ?>
        <div class="yts-subscribe-button">
            <script src="https://apis.google.com/js/platform.js"></script>
            <div class="g-ytsubscribe"
                 data-channelid="<?php echo esc_attr($channel_id); ?>"
                 data-layout="<?php echo esc_attr($atts['layout']); ?>"
                 data-theme="<?php echo esc_attr($atts['theme']); ?>"
                 data-count="<?php echo esc_attr($atts['count']); ?>">
            </div>
        </div>
        <?php
        return ob_get_clean();
    }

    public function email_signup_shortcode($atts) {
        if (!YouTube_Suite::get_setting('enable_email_signup')) {
            return '';
        }

        $atts = shortcode_atts(array(
            'title' => 'Subscribe to Our Newsletter',
            'description' => 'Get notified about new videos!',
            'button_text' => 'Subscribe',
            'show_name' => false
        ), $atts);

        ob_start();
        ?>
        <div class="yts-email-signup">
            <div class="yts-email-signup-inner">
                <?php if (!empty($atts['title'])): ?>
                    <h3 class="yts-email-title"><?php echo esc_html($atts['title']); ?></h3>
                <?php endif; ?>

                <?php if (!empty($atts['description'])): ?>
                    <p class="yts-email-description"><?php echo esc_html($atts['description']); ?></p>
                <?php endif; ?>

                <form class="yts-email-form">
                    <?php if ($atts['show_name']): ?>
                        <div class="yts-form-field">
                            <input type="text" name="name" placeholder="Your Name" class="yts-input">
                        </div>
                    <?php endif; ?>

                    <div class="yts-form-field">
                        <input type="email" name="email" placeholder="Your Email" required class="yts-input">
                    </div>

                    <div class="yts-form-field">
                        <button type="submit" class="yts-button yts-button-primary">
                            <?php echo esc_html($atts['button_text']); ?>
                        </button>
                    </div>

                    <div class="yts-form-message"></div>
                </form>
            </div>
        </div>
        <?php
        return ob_get_clean();
    }

    public function social_share_shortcode($atts) {
        if (!YouTube_Suite::get_setting('enable_social_share')) {
            return '';
        }

        $atts = shortcode_atts(array(
            'layout' => 'horizontal',
            'size' => 'medium',
            'networks' => 'facebook,twitter,linkedin'
        ), $atts);

        $networks = explode(',', $atts['networks']);
        $post_url = urlencode(get_permalink());
        $post_title = urlencode(get_the_title());

        $share_urls = array(
            'facebook' => 'https://www.facebook.com/sharer/sharer.php?u=' . $post_url,
            'twitter' => 'https://twitter.com/intent/tweet?url=' . $post_url . '&text=' . $post_title,
            'linkedin' => 'https://www.linkedin.com/sharing/share-offsite/?url=' . $post_url,
            'pinterest' => 'https://pinterest.com/pin/create/button/?url=' . $post_url . '&description=' . $post_title,
            'reddit' => 'https://reddit.com/submit?url=' . $post_url . '&title=' . $post_title,
            'whatsapp' => 'https://api.whatsapp.com/send?text=' . $post_title . '%20' . $post_url
        );

        $network_labels = array(
            'facebook' => 'Facebook',
            'twitter' => 'Twitter',
            'linkedin' => 'LinkedIn',
            'pinterest' => 'Pinterest',
            'reddit' => 'Reddit',
            'whatsapp' => 'WhatsApp'
        );

        ob_start();
        ?>
        <div class="yts-social-share yts-layout-<?php echo esc_attr($atts['layout']); ?> yts-size-<?php echo esc_attr($atts['size']); ?>">
            <?php foreach ($networks as $network):
                $network = trim($network);
                if (!isset($share_urls[$network])) continue;
            ?>
                <a href="<?php echo esc_url($share_urls[$network]); ?>"
                   class="yts-share-button yts-share-<?php echo esc_attr($network); ?>"
                   target="_blank"
                   rel="noopener noreferrer"
                   data-network="<?php echo esc_attr($network); ?>">
                    <?php echo esc_html($network_labels[$network]); ?>
                </a>
            <?php endforeach; ?>
        </div>
        <?php
        return ob_get_clean();
    }

    public function cta_button_shortcode($atts) {
        $atts = shortcode_atts(array(
            'text' => 'Watch on YouTube',
            'url' => '',
            'style' => 'primary',
            'size' => 'medium'
        ), $atts);

        if (empty($atts['url'])) {
            $atts['url'] = get_post_meta(get_the_ID(), 'yt_video_url', true);
        }

        if (empty($atts['url'])) {
            return '';
        }

        ob_start();
        ?>
        <div class="yts-cta-button-wrapper">
            <a href="<?php echo esc_url($atts['url']); ?>"
               class="yts-button yts-button-<?php echo esc_attr($atts['style']); ?> yts-button-<?php echo esc_attr($atts['size']); ?> yts-cta-button"
               target="_blank"
               rel="noopener noreferrer">
                <?php echo esc_html($atts['text']); ?>
            </a>
        </div>
        <?php
        return ob_get_clean();
    }
}
