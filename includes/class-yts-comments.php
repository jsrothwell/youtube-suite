<?php
if (!defined('ABSPATH')) exit;

if ( ! class_exists( 'YTS_Comments' ) ) {
  class YTS_Comments {
      private static $instance = null;

      public static function get_instance() {
          if (null === self::$instance) {
              self::$instance = new self();
          }
          return self::$instance;
      }

      private function __construct() {
          add_shortcode('youtube_comments', array($this, 'comments_shortcode'));
      }

      public function comments_shortcode($atts) {
          if (!YouTube_Suite::get_setting('enable_comments_sync')) return '';

          $atts = shortcode_atts(array(
              'video_url' => '',
              'api_key' => YouTube_Suite::get_setting('api_key')
          ), $atts);

          if (empty($atts['video_url']) || empty($atts['api_key'])) {
              return '<p>Error: Please provide video_url and ensure API key is configured.</p>';
          }

          $video_id = yts_get_video_id($atts['video_url']);
          if (!$video_id) {
              return '<p>Error: Invalid YouTube URL.</p>';
          }

          ob_start();
          ?>
          <div id="yts-comments-wrapper" data-video-id="<?php echo esc_attr($video_id); ?>" data-api-key="<?php echo esc_attr($atts['api_key']); ?>">
              <h3 class="yts-comments-title">Comments from YouTube</h3>
              <div id="yts-comments-container">
                  <div class="yts-comments-loader">Loading comments...</div>
              </div>
          </div>
          <?php
          return ob_get_clean();
      }
  }
}
?>
