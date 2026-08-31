<?php

if (!defined('ABSPATH')) {
    exit;
}

final class Vava_DevHub_Security {
    const MAX_CONTENT_BYTES = 524288;

    public static function allowed_repositories() {
        return array(
            'Vava-living-website',
            'Vava-living-website-patches',
        );
    }

    public static function sanitize_repository($repository) {
        $repository = sanitize_text_field((string) $repository);
        return in_array($repository, self::allowed_repositories(), true) ? $repository : '';
    }

    public static function sanitize_branch($branch) {
        $branch = trim((string) $branch);
        if ($branch === '' || strlen($branch) > 180) {
            return '';
        }
        if (!preg_match('/^[A-Za-z0-9._\/-]+$/', $branch)) {
            return '';
        }
        if (strpos($branch, '..') !== false || strpos($branch, '@{') !== false || strpos($branch, '/.') !== false || substr($branch, -1) === '/' || substr($branch, -5) === '.lock') {
            return '';
        }
        return $branch;
    }

    public static function validate_path($repository, $path) {
        $path = ltrim(str_replace('\\', '/', trim((string) $path)), '/');
        if ($path === '' || strlen($path) > 500 || strpos($path, '../') !== false || strpos($path, "\0") !== false) {
            return new WP_Error('invalid_path', 'Invalid file path.');
        }

        $lower = strtolower($path);
        $blocked_fragments = array(
            '.git/', '.github/workflows/', 'wp-config.php', '.env', '.htaccess',
            'id_rsa', 'id_ed25519', 'private_key', 'credentials', 'secrets',
            'wp-content/uploads/', 'wp-content/cache/', 'node_modules/', 'vendor/',
        );
        foreach ($blocked_fragments as $fragment) {
            if (strpos($lower, $fragment) !== false) {
                return new WP_Error('blocked_path', 'This path is protected by Developer Hub security policy.');
            }
        }

        $extension = strtolower(pathinfo($path, PATHINFO_EXTENSION));
        $allowed_extensions = array('php', 'css', 'js', 'json', 'md', 'txt', 'html', 'yml', 'yaml', 'xml');
        if (!in_array($extension, $allowed_extensions, true)) {
            return new WP_Error('blocked_extension', 'This file type is not allowed for controlled push.');
        }

        if ($repository === 'Vava-living-website') {
            $allowed_prefixes = array(
                'wp-content/themes/vava-living-theme-ar-v1/',
                'wp-content/plugins/vava-developer-hub/',
            );
            $allowed = false;
            foreach ($allowed_prefixes as $prefix) {
                if (strpos($path, $prefix) === 0) {
                    $allowed = true;
                    break;
                }
            }
            if (!$allowed) {
                return new WP_Error('outside_safe_scope', 'Main repository pushes are limited to the Vava custom theme and Developer Hub plugin.');
            }
        }

        return $path;
    }

    public static function validate_content($content) {
        $content = (string) $content;
        if (strlen($content) > self::MAX_CONTENT_BYTES) {
            return new WP_Error('content_too_large', 'Controlled push is limited to 512 KB per file.');
        }

        $secret_patterns = array(
            '/-----BEGIN (?:RSA |EC |OPENSSH )?PRIVATE KEY-----/i',
            '/\bgithub_pat_[A-Za-z0-9_]{20,}\b/',
            '/\bgh[pousr]_[A-Za-z0-9]{20,}\b/',
            '/\bAKIA[0-9A-Z]{16}\b/',
        );
        foreach ($secret_patterns as $pattern) {
            if (preg_match($pattern, $content)) {
                return new WP_Error('secret_detected', 'Potential credential or private key detected. Push blocked.');
            }
        }

        return true;
    }

    public static function authorize_admin_request() {
        if (!current_user_can('manage_options')) {
            wp_send_json_error(array('message' => 'Administrator permission is required.'), 403);
        }
        check_ajax_referer('vava_devhub_admin', 'nonce');
    }

    public static function create_preview_token($payload) {
        $user_id = get_current_user_id();
        $token = wp_generate_password(32, false, false);
        $record = array(
            'payload' => $payload,
            'created_at' => time(),
        );
        set_transient('vava_devhub_preview_' . $user_id . '_' . $token, $record, 10 * MINUTE_IN_SECONDS);
        return $token;
    }

    public static function consume_preview_token($token) {
        $user_id = get_current_user_id();
        $key = 'vava_devhub_preview_' . $user_id . '_' . sanitize_text_field((string) $token);
        $record = get_transient($key);
        if ($record) {
            delete_transient($key);
        }
        return is_array($record) ? $record : false;
    }
}
