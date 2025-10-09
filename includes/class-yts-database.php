<?php
/**
 * Database Handler - Combined from all plugins
 */

if (!defined('ABSPATH')) exit;
if ( ! class_exists( 'YTS_Database' ) ) {
class YTS_Database {

    private static $instance = null;
    private $subscribers_table;
    private $analytics_table;

    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function __construct() {
        global $wpdb;
        $this->subscribers_table = $wpdb->prefix . 'yts_subscribers';
        $this->analytics_table = $wpdb->prefix . 'yts_analytics';
    }

    public static function create_tables() {
        global $wpdb;
        $charset_collate = $wpdb->get_charset_collate();
        $instance = self::get_instance();

        // Subscribers table
        $sql_subscribers = "CREATE TABLE IF NOT EXISTS {$instance->subscribers_table} (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            email varchar(255) NOT NULL,
            name varchar(255) DEFAULT NULL,
            status varchar(20) DEFAULT 'active',
            source varchar(100) DEFAULT NULL,
            ip_address varchar(45) DEFAULT NULL,
            subscribed_date datetime DEFAULT CURRENT_TIMESTAMP,
            confirmed_date datetime DEFAULT NULL,
            unsubscribed_date datetime DEFAULT NULL,
            PRIMARY KEY (id),
            UNIQUE KEY email (email),
            KEY status (status)
        ) $charset_collate;";

        // Analytics table
        $sql_analytics = "CREATE TABLE IF NOT EXISTS {$instance->analytics_table} (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            action_type varchar(50) NOT NULL,
            post_id bigint(20) DEFAULT NULL,
            video_id varchar(50) DEFAULT NULL,
            additional_data longtext DEFAULT NULL,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY action_type (action_type),
            KEY created_at (created_at)
        ) $charset_collate;";

        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql_subscribers);
        dbDelta($sql_analytics);
    }

    // Subscriber methods
    public function add_subscriber($email, $data = array()) {
        global $wpdb;

        $defaults = array(
            'name' => '',
            'status' => 'active',
            'source' => 'website',
            'ip_address' => $this->get_user_ip()
        );

        $data = wp_parse_args($data, $defaults);
        $data['email'] = sanitize_email($email);

        $result = $wpdb->insert($this->subscribers_table, $data);
        return $result ? $wpdb->insert_id : false;
    }

    public function subscriber_exists($email) {
        global $wpdb;
        $count = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM {$this->subscribers_table} WHERE email = %s",
            sanitize_email($email)
        ));
        return $count > 0;
    }

    public function get_subscriber($email) {
        global $wpdb;
        return $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM {$this->subscribers_table} WHERE email = %s",
            sanitize_email($email)
        ));
    }

    public function update_subscriber_status($email, $status) {
        global $wpdb;
        return $wpdb->update(
            $this->subscribers_table,
            array('status' => $status),
            array('email' => sanitize_email($email))
        );
    }

    public function get_active_subscribers($limit = null, $offset = 0) {
        global $wpdb;
        $sql = "SELECT * FROM {$this->subscribers_table} WHERE status = 'active' ORDER BY subscribed_date DESC";
        if ($limit) {
            $sql .= $wpdb->prepare(" LIMIT %d OFFSET %d", $limit, $offset);
        }
        return $wpdb->get_results($sql);
    }

    public function get_subscriber_count($status = 'active') {
        global $wpdb;
        return $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM {$this->subscribers_table} WHERE status = %s",
            $status
        ));
    }

    // Analytics methods
    public function log_analytics($action_type, $data = array()) {
        global $wpdb;

        $defaults = array(
            'post_id' => get_the_ID(),
            'video_id' => null,
            'additional_data' => null
        );

        $data = wp_parse_args($data, $defaults);

        if (is_array($data['additional_data'])) {
            $data['additional_data'] = json_encode($data['additional_data']);
        }

        return $wpdb->insert($this->analytics_table, array(
            'action_type' => $action_type,
            'post_id' => $data['post_id'],
            'video_id' => $data['video_id'],
            'additional_data' => $data['additional_data']
        ));
    }

    public function get_analytics_count($action_type = null, $start_date = null, $end_date = null) {
        global $wpdb;
        $where = array();
        $where_values = array();

        if ($action_type) {
            $where[] = "action_type = %s";
            $where_values[] = $action_type;
        }
        if ($start_date) {
            $where[] = "created_at >= %s";
            $where_values[] = $start_date;
        }
        if ($end_date) {
            $where[] = "created_at <= %s";
            $where_values[] = $end_date;
        }

        $sql = "SELECT COUNT(*) FROM {$this->analytics_table}";
        if (!empty($where)) {
            $sql .= " WHERE " . implode(' AND ', $where);
            $sql = $wpdb->prepare($sql, $where_values);
        }

        return $wpdb->get_var($sql);
    }

    private function get_user_ip() {
        $ip_keys = array('HTTP_CLIENT_IP', 'HTTP_X_FORWARDED_FOR', 'REMOTE_ADDR');
        foreach ($ip_keys as $key) {
            if (array_key_exists($key, $_SERVER)) {
                $ip = trim(explode(',', $_SERVER[$key])[0]);
                if (filter_var($ip, FILTER_VALIDATE_IP) !== false) {
                    return $ip;
                }
            }
        }
        return 'UNKNOWN';
    }
}
}
?>
