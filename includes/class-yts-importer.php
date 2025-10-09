<?php
/**
 * Video Importer Module
 */

if (!defined('ABSPATH')) exit;
	if ( ! class_exists( 'YTS_Importer' ) ) {
class YTS_Importer {

    private static $instance = null;

    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function __construct() {
        add_action('yts_auto_import', array($this, 'import_videos'));
    }

    public function import_videos() {
        $api_key = YouTube_Suite::get_setting('api_key');
        $channel_id = YouTube_Suite::get_setting('channel_id');

        if (empty($api_key) || empty($channel_id)) {
            return array('new' => 0, 'updated' => 0);
        }

        $new_count = 0;
        $updated_count = 0;

        $url = "https://www.googleapis.com/youtube/v3/search?key={$api_key}&channelId={$channel_id}&part=snippet&order=date&maxResults=10&type=video";
        $response = wp_remote_get($url);

        if (is_wp_error($response)) {
            return array('new' => 0, 'updated' => 0);
        }

        $data = json_decode(wp_remote_retrieve_body($response), true);
        if (!isset($data['items'])) {
            return array('new' => 0, 'updated' => 0);
        }

        foreach ($data['items'] as $item) {
            $video_id = $item['id']['videoId'];

            // Check if already exists
            $existing = get_posts(array(
                'post_type' => 'post',
                'meta_key' => 'yt_video_id',
                'meta_value' => $video_id,
                'posts_per_page' => 1
            ));

            // Get video details
            $details_url = "https://www.googleapis.com/youtube/v3/videos?key={$api_key}&id={$video_id}&part=snippet,contentDetails";
            $details_response = wp_remote_get($details_url);

            if (is_wp_error($details_response)) continue;

            $details_data = json_decode(wp_remote_retrieve_body($details_response), true);
            if (!isset($details_data['items'][0])) continue;

            $video = $details_data['items'][0];

            if (!empty($existing) && YouTube_Suite::get_setting('update_existing')) {
                $this->update_post($existing[0]->ID, $video, $video_id);
                $updated_count++;
            } elseif (empty($existing)) {
                $post_id = $this->create_post($video, $video_id);
                if ($post_id) $new_count++;
            }
        }

        return array('new' => $new_count, 'updated' => $updated_count);
    }

    private function create_post($video, $video_id) {
        $snippet = $video['snippet'];
        $content_details = $video['contentDetails'];

        $title = $snippet['title'];
        $content = '';

        if (YouTube_Suite::get_setting('embed_video')) {
            $content .= '<div class="yts-video-embed"><iframe width="560" height="315" src="https://www.youtube.com/embed/' . $video_id . '" frameborder="0" allowfullscreen></iframe></div>';
        }

        if (!empty($snippet['description'])) {
            $content .= '<div class="yts-video-description">' . wpautop($snippet['description']) . '</div>';
        }

        $post_data = array(
            'post_title' => $title,
            'post_content' => $content,
            'post_status' => YouTube_Suite::get_setting('post_status', 'publish'),
            'post_type' => 'post',
            'post_date' => date('Y-m-d H:i:s', strtotime($snippet['publishedAt']))
        );

        $post_id = wp_insert_post($post_data);

        if ($post_id) {
            update_post_meta($post_id, 'yt_video_id', $video_id);
            update_post_meta($post_id, 'yt_video_url', 'https://www.youtube.com/watch?v=' . $video_id);
            update_post_meta($post_id, 'yt_video_duration', $content_details['duration']);
            update_post_meta($post_id, 'yt_video_thumbnail', $snippet['thumbnails']['high']['url']);

            if (YouTube_Suite::get_setting('set_featured_image')) {
                $thumb_url = isset($snippet['thumbnails']['maxres']['url']) ?
                    $snippet['thumbnails']['maxres']['url'] :
                    $snippet['thumbnails']['high']['url'];
                $this->set_featured_image($post_id, $thumb_url, $title);
            }
        }

        return $post_id;
    }

    private function update_post($post_id, $video, $video_id) {
        $snippet = $video['snippet'];
        $content_details = $video['contentDetails'];

        $content = '';
        if (YouTube_Suite::get_setting('embed_video')) {
            $content .= '<div class="yts-video-embed"><iframe width="560" height="315" src="https://www.youtube.com/embed/' . $video_id . '" frameborder="0" allowfullscreen></iframe></div>';
        }

        if (!empty($snippet['description'])) {
            $content .= '<div class="yts-video-description">' . wpautop($snippet['description']) . '</div>';
        }

        wp_update_post(array(
            'ID' => $post_id,
            'post_title' => $snippet['title'],
            'post_content' => $content
        ));

        update_post_meta($post_id, 'yt_video_duration', $content_details['duration']);
    }

    private function set_featured_image($post_id, $image_url, $title) {
        require_once(ABSPATH . 'wp-admin/includes/media.php');
        require_once(ABSPATH . 'wp-admin/includes/file.php');
        require_once(ABSPATH . 'wp-admin/includes/image.php');

        $tmp = download_url($image_url);
        if (is_wp_error($tmp)) return false;

        $file_array = array(
            'name' => basename($image_url) . '.jpg',
            'tmp_name' => $tmp
        );

        $attachment_id = media_handle_sideload($file_array, $post_id, $title);

        if (is_wp_error($attachment_id)) {
            @unlink($file_array['tmp_name']);
            return false;
        }

        set_post_thumbnail($post_id, $attachment_id);
        return true;
    }

    public function refresh_all_posts() {
        $posts = get_posts(array(
            'post_type' => 'post',
            'posts_per_page' => -1,
            'meta_query' => array(array('key' => 'yt_video_id', 'compare' => 'EXISTS'))
        ));

        $api_key = YouTube_Suite::get_setting('api_key');
        $updated = 0;

        foreach ($posts as $post) {
            $video_id = get_post_meta($post->ID, 'yt_video_id', true);
            if (empty($video_id)) continue;

            $url = "https://www.googleapis.com/youtube/v3/videos?key={$api_key}&id={$video_id}&part=snippet,contentDetails";
            $response = wp_remote_get($url);

            if (is_wp_error($response)) continue;

            $data = json_decode(wp_remote_retrieve_body($response), true);
            if (!isset($data['items'][0])) continue;

            $this->update_post($post->ID, $data['items'][0], $video_id);
            $updated++;

            usleep(100000);
        }

        return array('updated' => $updated);
    }
}
}
?>
