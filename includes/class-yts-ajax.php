<?php
if (!defined('ABSPATH')) exit;

class YTS_Ajax {
    private static $instance = null;

    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function __construct() {
        add_action('wp_ajax_yts_email_signup', array($this, 'handle_email_signup'));
        add_action('wp_ajax_nopriv_yts_email_signup', array($this, 'handle_email_signup'));
    }

    public function handle_email_signup() {
        check_ajax_referer('yts_nonce', 'nonce');

        $email = isset($_POST['email']) ? sanitize_email($_POST['email']) : '';

        if (empty($email) || !is_email($email)) {
            wp_send_json_error(array('message' => 'Invalid email address.'));
        }

        $db = YTS_Database::get_instance();

        if ($db->subscriber_exists($email)) {
            wp_send_json_error(array('message' => 'Email already subscribed.'));
        }

        $subscriber_id = $db->add_subscriber($email);

        if ($subscriber_id) {
            if (YouTube_Suite::get_setting('enable_analytics')) {
                $db->log_analytics('email_signup');
            }
            wp_send_json_success(array('message' => 'Thank you for subscribing!'));
        } else {
            wp_send_json_error(array('message' => 'An error occurred.'));
        }
    }
}
?>
