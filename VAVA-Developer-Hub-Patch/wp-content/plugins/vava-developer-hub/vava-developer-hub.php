<?php
/**
 * Plugin Name: Vava Developer Hub
 * Description: Operational GitHub developer hub for the Vava Living website with repository monitoring, branch/commit/PR visibility, guarded file review, and controlled GitHub pushes.
 * Version: 1.0.0
 * Author: Vava Living
 */

if (!defined('ABSPATH')) {
    exit;
}

define('VAVA_DEVHUB_VERSION', '1.0.0');
define('VAVA_DEVHUB_FILE', __FILE__);
define('VAVA_DEVHUB_DIR', plugin_dir_path(__FILE__));
define('VAVA_DEVHUB_URL', plugin_dir_url(__FILE__));

require_once VAVA_DEVHUB_DIR . 'includes/class-vava-devhub-security.php';
require_once VAVA_DEVHUB_DIR . 'includes/class-vava-devhub-github.php';
require_once VAVA_DEVHUB_DIR . 'includes/class-vava-devhub-admin.php';

function vava_devhub_bootstrap() {
    Vava_DevHub_Admin::instance();
}
add_action('plugins_loaded', 'vava_devhub_bootstrap');
