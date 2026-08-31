<?php

if (!defined('ABSPATH')) {
    exit;
}

final class Vava_DevHub_Credentials {
    const OPTION = 'vava_devhub_github_token_encrypted';

    public static function boot() {
        add_action('wp_ajax_vava_devhub_token_save', array(__CLASS__, 'ajax_save'));
        add_action('wp_ajax_vava_devhub_token_remove', array(__CLASS__, 'ajax_remove'));
    }

    private static function key() {
        return hash('sha256', wp_salt('auth') . '|' . wp_salt('nonce') . '|vava-devhub-github', true);
    }

    private static function encrypt($token) {
        if (!function_exists('openssl_encrypt')) {
            return new WP_Error('openssl_required', 'OpenSSL is required to store the GitHub token securely.');
        }
        $iv = random_bytes(12);
        $tag = '';
        $ciphertext = openssl_encrypt((string) $token, 'aes-256-gcm', self::key(), OPENSSL_RAW_DATA, $iv, $tag, 'vava-devhub', 16);
        if ($ciphertext === false || $tag === '') {
            return new WP_Error('token_encrypt_failed', 'GitHub token encryption failed.');
        }
        return base64_encode(wp_json_encode(array(
            'v' => 1,
            'iv' => base64_encode($iv),
            'tag' => base64_encode($tag),
            'data' => base64_encode($ciphertext),
        )));
    }

    public static function stored_token() {
        $encoded = get_option(self::OPTION, '');
        if (!is_string($encoded) || $encoded === '' || !function_exists('openssl_decrypt')) {
            return '';
        }
        $json = base64_decode($encoded, true);
        $record = $json !== false ? json_decode($json, true) : null;
        if (!is_array($record) || (int) ($record['v'] ?? 0) !== 1) {
            return '';
        }
        $iv = base64_decode((string) ($record['iv'] ?? ''), true);
        $tag = base64_decode((string) ($record['tag'] ?? ''), true);
        $data = base64_decode((string) ($record['data'] ?? ''), true);
        if ($iv === false || $tag === false || $data === false) {
            return '';
        }
        $plain = openssl_decrypt($data, 'aes-256-gcm', self::key(), OPENSSL_RAW_DATA, $iv, $tag, 'vava-devhub');
        return is_string($plain) ? trim($plain) : '';
    }

    public static function source() {
        $env = getenv('VAVA_GITHUB_TOKEN');
        if (is_string($env) && trim($env) !== '') {
            return 'environment';
        }
        if (defined('VAVA_GITHUB_TOKEN') && is_string(VAVA_GITHUB_TOKEN) && trim(VAVA_GITHUB_TOKEN) !== '') {
            return 'wp-config';
        }
        return self::stored_token() !== '' ? 'encrypted-db' : 'none';
    }

    public static function status() {
        $source = self::source();
        return array(
            'configured' => $source !== 'none',
            'source' => $source,
            'removable' => $source === 'encrypted-db',
        );
    }

    public static function ajax_save() {
        Vava_DevHub_Security::authorize_admin_request();
        $token = isset($_POST['token']) ? trim((string) wp_unslash($_POST['token'])) : '';
        if ($token === '' || strlen($token) < 20 || strlen($token) > 500 || preg_match('/\s/', $token)) {
            wp_send_json_error(array('message' => 'Enter a valid GitHub token.'), 400);
        }
        if (self::source() === 'environment' || self::source() === 'wp-config') {
            wp_send_json_error(array('message' => 'A server-level VAVA_GITHUB_TOKEN is already configured. Remove or change it at server level instead.'), 409);
        }
        $encrypted = self::encrypt($token);
        if (is_wp_error($encrypted)) {
            wp_send_json_error(array('message' => $encrypted->get_error_message()), 500);
        }
        update_option(self::OPTION, $encrypted, false);

        $repo = Vava_DevHub_GitHub::repository('Vava-living-website');
        if (is_wp_error($repo)) {
            delete_option(self::OPTION);
            wp_send_json_error(array('message' => 'Token verification failed: ' . $repo->get_error_message()), 400);
        }
        $permissions = isset($repo['permissions']) && is_array($repo['permissions']) ? $repo['permissions'] : array();
        if (empty($permissions['push']) && empty($permissions['maintain']) && empty($permissions['admin'])) {
            delete_option(self::OPTION);
            wp_send_json_error(array('message' => 'The token is valid but does not have write permission for Vava-living-website.'), 403);
        }
        $connection = Vava_DevHub_GitHub::connection();
        wp_send_json_success(array(
            'message' => 'GitHub write token saved securely and verified.',
            'credentials' => self::status(),
            'connection' => $connection,
        ));
    }

    public static function ajax_remove() {
        Vava_DevHub_Security::authorize_admin_request();
        if (self::source() !== 'encrypted-db') {
            wp_send_json_error(array('message' => 'Only a token stored by Developer Hub can be removed here.'), 409);
        }
        delete_option(self::OPTION);
        wp_send_json_success(array(
            'message' => 'Stored GitHub token removed.',
            'credentials' => self::status(),
        ));
    }
}
