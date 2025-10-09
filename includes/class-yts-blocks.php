<?php
if (!defined('ABSPATH')) exit;

class YTS_Blocks {
    private static $instance = null;

    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function __construct() {
        add_action('init', array($this, 'register_blocks'));
    }

    public function register_blocks() {
        // Placeholder - blocks can be added later
        // register_block_type('yts/subscribe-button', array(...));
    }
}
?>
