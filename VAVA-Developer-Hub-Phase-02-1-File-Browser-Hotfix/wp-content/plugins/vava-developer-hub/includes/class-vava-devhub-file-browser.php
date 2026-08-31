<?php

if (!defined('ABSPATH')) {
    exit;
}

final class Vava_DevHub_File_Browser {
    private static $booted = false;

    public static function boot() {
        if (self::$booted) {
            return;
        }
        self::$booted = true;

        add_action('admin_enqueue_scripts', array(__CLASS__, 'enqueue_assets'));
        add_action('wp_ajax_vava_devhub_browse', array(__CLASS__, 'ajax_browse'));
        add_action('wp_ajax_vava_devhub_load_file', array(__CLASS__, 'ajax_load_file'));
    }

    public static function enqueue_assets($hook) {
        if ($hook !== 'toplevel_page_vava-developer-hub') {
            return;
        }

        wp_enqueue_style(
            'vava-devhub-file-browser',
            VAVA_DEVHUB_URL . 'assets/file-browser.css',
            array('vava-devhub-admin'),
            VAVA_DEVHUB_VERSION
        );
        wp_enqueue_script(
            'vava-devhub-file-browser',
            VAVA_DEVHUB_URL . 'assets/file-browser.js',
            array('vava-devhub-admin'),
            VAVA_DEVHUB_VERSION,
            true
        );
    }

    private static function allowed_roots($repository) {
        if ($repository === 'Vava-living-website') {
            return array(
                'wp-content/themes/vava-living-theme-ar-v1',
            );
        }

        if ($repository === 'Vava-living-website-patches') {
            return array('');
        }

        return array();
    }

    private static function normalize_path($path) {
        $path = ltrim(str_replace('\\', '/', trim((string) $path)), '/');
        while (strpos($path, '//') !== false) {
            $path = str_replace('//', '/', $path);
        }
        return rtrim($path, '/');
    }

    private static function validate_browse_path($repository, $path) {
        $repository = Vava_DevHub_Security::sanitize_repository($repository);
        if ($repository === '') {
            return new WP_Error('invalid_repository', 'Repository is not allowed.');
        }

        $path = self::normalize_path($path);
        if (strlen($path) > 500 || strpos($path, '../') !== false || strpos($path, "\0") !== false) {
            return new WP_Error('invalid_path', 'Invalid browse path.');
        }

        $lower = strtolower($path);
        $blocked_fragments = array(
            '.git/', '.github/workflows/', 'wp-config.php', '.env', '.htaccess',
            'id_rsa', 'id_ed25519', 'private_key', 'credentials', 'secrets',
            'wp-content/uploads/', 'wp-content/cache/', 'node_modules/', 'vendor/',
        );
        foreach ($blocked_fragments as $fragment) {
            if ($lower !== '' && strpos($lower . '/', $fragment) !== false) {
                return new WP_Error('blocked_path', 'This path is protected by Developer Hub security policy.');
            }
        }

        if ($repository === 'Vava-living-website' && $path !== '') {
            $allowed = false;
            foreach (self::allowed_roots($repository) as $root) {
                if ($path === $root || strpos($path, $root . '/') === 0) {
                    $allowed = true;
                    break;
                }
            }
            if (!$allowed) {
                return new WP_Error('outside_safe_scope', 'File browser is limited to the Vava custom theme. Developer Hub source is maintained in the patches repository.');
            }
        }

        return $path;
    }

    private static function encode_path($path) {
        $segments = array_map('rawurlencode', explode('/', $path));
        return implode('/', $segments);
    }

    private static function parent_path($repository, $path) {
        $path = self::normalize_path($path);
        if ($path === '') {
            return '';
        }

        if ($repository === 'Vava-living-website') {
            foreach (self::allowed_roots($repository) as $root) {
                if ($path === $root) {
                    return '';
                }
            }
        }

        $parent = dirname($path);
        return $parent === '.' ? '' : $parent;
    }

    private static function virtual_roots($repository) {
        if ($repository !== 'Vava-living-website') {
            return array();
        }

        return array(
            array(
                'name' => 'Vava Theme',
                'path' => 'wp-content/themes/vava-living-theme-ar-v1',
                'type' => 'dir',
                'size' => 0,
                'sha' => '',
            ),
        );
    }

    public static function ajax_browse() {
        Vava_DevHub_Security::authorize_admin_request();

        $repository = Vava_DevHub_Security::sanitize_repository(isset($_POST['repository']) ? wp_unslash($_POST['repository']) : '');
        $branch = Vava_DevHub_Security::sanitize_branch(isset($_POST['branch']) ? wp_unslash($_POST['branch']) : '');
        $path_raw = isset($_POST['path']) ? wp_unslash($_POST['path']) : '';
        $path = self::validate_browse_path($repository, $path_raw);

        if ($repository === '' || $branch === '' || is_wp_error($path)) {
            $error = is_wp_error($path) ? $path : new WP_Error('invalid_browse_request', 'Invalid repository or branch.');
            wp_send_json_error(array('message' => $error->get_error_message()), 400);
        }

        if ($repository === 'Vava-living-website' && $path === '') {
            wp_send_json_success(array(
                'path' => '',
                'parent' => '',
                'items' => self::virtual_roots($repository),
            ));
        }

        $endpoint = '/contents';
        if ($path !== '') {
            $endpoint .= '/' . self::encode_path($path);
        }
        $endpoint .= '?ref=' . rawurlencode($branch);

        $result = Vava_DevHub_GitHub::api($repository, $endpoint);
        if (is_wp_error($result)) {
            wp_send_json_error(array('message' => $result->get_error_message()), 400);
        }

        if (isset($result['type'])) {
            $result = array($result);
        }

        $items = array();
        foreach ((array) $result as $item) {
            if (empty($item['path']) || empty($item['type'])) {
                continue;
            }

            $item_path = self::normalize_path($item['path']);
            if ($item['type'] === 'dir') {
                $safe = self::validate_browse_path($repository, $item_path);
                if (is_wp_error($safe)) {
                    continue;
                }
            } elseif ($item['type'] === 'file') {
                $safe = Vava_DevHub_Security::validate_path($repository, $item_path);
                if (is_wp_error($safe)) {
                    continue;
                }
            } else {
                continue;
            }

            $items[] = array(
                'name' => isset($item['name']) ? sanitize_text_field($item['name']) : basename($item_path),
                'path' => $item_path,
                'type' => $item['type'],
                'size' => isset($item['size']) ? (int) $item['size'] : 0,
                'sha' => isset($item['sha']) ? sanitize_text_field($item['sha']) : '',
            );
        }

        usort($items, function ($a, $b) {
            if ($a['type'] !== $b['type']) {
                return $a['type'] === 'dir' ? -1 : 1;
            }
            return strcasecmp($a['name'], $b['name']);
        });

        wp_send_json_success(array(
            'path' => $path,
            'parent' => self::parent_path($repository, $path),
            'items' => $items,
        ));
    }

    public static function ajax_load_file() {
        Vava_DevHub_Security::authorize_admin_request();

        $repository = Vava_DevHub_Security::sanitize_repository(isset($_POST['repository']) ? wp_unslash($_POST['repository']) : '');
        $branch = Vava_DevHub_Security::sanitize_branch(isset($_POST['branch']) ? wp_unslash($_POST['branch']) : '');
        $path_raw = isset($_POST['path']) ? wp_unslash($_POST['path']) : '';
        $path = Vava_DevHub_Security::validate_path($repository, $path_raw);

        if ($repository === '' || $branch === '' || is_wp_error($path)) {
            $error = is_wp_error($path) ? $path : new WP_Error('invalid_file_request', 'Invalid file request.');
            wp_send_json_error(array('message' => $error->get_error_message()), 400);
        }

        $result = Vava_DevHub_GitHub::file($repository, $path, $branch);
        if (is_wp_error($result)) {
            wp_send_json_error(array('message' => $result->get_error_message()), 400);
        }

        $content = isset($result['decoded_content']) && is_string($result['decoded_content']) ? $result['decoded_content'] : null;
        if ($content === null) {
            wp_send_json_error(array('message' => 'This GitHub file could not be decoded as editable text.'), 400);
        }
        if (strlen($content) > Vava_DevHub_Security::MAX_CONTENT_BYTES) {
            wp_send_json_error(array('message' => 'This file is larger than the 512 KB controlled-push limit.'), 400);
        }

        wp_send_json_success(array(
            'path' => $path,
            'content' => $content,
            'sha' => isset($result['sha']) ? sanitize_text_field($result['sha']) : '',
            'bytes' => strlen($content),
        ));
    }
}
