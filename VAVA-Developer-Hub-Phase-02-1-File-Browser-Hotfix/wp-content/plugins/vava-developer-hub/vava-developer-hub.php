<?php
/**
 * Plugin Name: Vava Developer Hub
 * Description: Operational GitHub developer hub for the Vava Living website with repository monitoring, branch/commit/PR visibility, guarded file review, controlled GitHub pushes, and a safe GitHub file browser.
 * Version: 1.1.1
 * Author: Vava Living
 */

if (!defined('ABSPATH')) {
    exit;
}

define('VAVA_DEVHUB_VERSION', '1.1.1');
define('VAVA_DEVHUB_FILE', __FILE__);
define('VAVA_DEVHUB_DIR', plugin_dir_path(__FILE__));
define('VAVA_DEVHUB_URL', plugin_dir_url(__FILE__));

require_once VAVA_DEVHUB_DIR . 'includes/class-vava-devhub-security.php';
require_once VAVA_DEVHUB_DIR . 'includes/class-vava-devhub-github.php';
require_once VAVA_DEVHUB_DIR . 'includes/class-vava-devhub-admin.php';
require_once VAVA_DEVHUB_DIR . 'includes/class-vava-devhub-file-browser.php';

function vava_devhub_bootstrap() {
    Vava_DevHub_Admin::instance();
    Vava_DevHub_File_Browser::boot();
}
add_action('plugins_loaded', 'vava_devhub_bootstrap');
