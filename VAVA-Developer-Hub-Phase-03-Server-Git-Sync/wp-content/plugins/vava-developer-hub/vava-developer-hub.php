<?php
/**
 * Plugin Name: Vava Developer Hub
 * Description: Operational Vava engineering control room with GitHub monitoring, safe file browsing, controlled file pushes, and TCRM-style Server Git Sync.
 * Version: 2.0.0
 * Author: Vava Living
 */

if (!defined('ABSPATH')) {
    exit;
}

define('VAVA_DEVHUB_VERSION', '2.0.0');
define('VAVA_DEVHUB_FILE', __FILE__);
define('VAVA_DEVHUB_DIR', plugin_dir_path(__FILE__));
define('VAVA_DEVHUB_URL', plugin_dir_url(__FILE__));

require_once VAVA_DEVHUB_DIR . 'includes/class-vava-devhub-security.php';
require_once VAVA_DEVHUB_DIR . 'includes/class-vava-devhub-credentials.php';
require_once VAVA_DEVHUB_DIR . 'includes/class-vava-devhub-github.php';
require_once VAVA_DEVHUB_DIR . 'includes/class-vava-devhub-admin.php';
require_once VAVA_DEVHUB_DIR . 'includes/class-vava-devhub-file-browser.php';
require_once VAVA_DEVHUB_DIR . 'includes/class-vava-devhub-server-git-runtime.php';
require_once VAVA_DEVHUB_DIR . 'includes/class-vava-devhub-server-git.php';

function vava_devhub_bootstrap() {
    Vava_DevHub_Credentials::boot();
    Vava_DevHub_Admin::instance();
    Vava_DevHub_File_Browser::boot();
    Vava_DevHub_Server_Git::boot();
}
add_action('plugins_loaded', 'vava_devhub_bootstrap');
