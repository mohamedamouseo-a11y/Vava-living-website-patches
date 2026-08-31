<?php

if (!defined('ABSPATH')) {
    exit;
}

final class Vava_DevHub_GitHub {
    const API_BASE = 'https://api.github.com';

    public static function owner() {
        $owner = getenv('VAVA_GITHUB_OWNER');
        if (!$owner && defined('VAVA_GITHUB_OWNER')) {
            $owner = VAVA_GITHUB_OWNER;
        }
        return $owner ? sanitize_text_field($owner) : 'mohamedamouseo-a11y';
    }

    public static function token() {
        $token = getenv('VAVA_GITHUB_TOKEN');
        if (!$token && defined('VAVA_GITHUB_TOKEN')) {
            $token = VAVA_GITHUB_TOKEN;
        }
        if ((!is_string($token) || trim($token) === '') && class_exists('Vava_DevHub_Credentials')) {
            $token = Vava_DevHub_Credentials::stored_token();
        }
        return is_string($token) ? trim($token) : '';
    }

    public static function has_token() {
        return self::token() !== '';
    }

    private static function headers() {
        $headers = array(
            'Accept' => 'application/vnd.github+json',
            'X-GitHub-Api-Version' => '2022-11-28',
            'User-Agent' => 'Vava-Developer-Hub/' . VAVA_DEVHUB_VERSION,
        );
        $token = self::token();
        if ($token !== '') {
            $headers['Authorization'] = 'Bearer ' . $token;
        }
        return $headers;
    }

    private static function request_url($url, $method = 'GET', $body = null) {
        $args = array(
            'method' => $method,
            'headers' => self::headers(),
            'timeout' => 20,
            'redirection' => 3,
        );
        if ($body !== null) {
            $args['headers']['Content-Type'] = 'application/json';
            $args['body'] = wp_json_encode($body);
        }
        $response = wp_remote_request($url, $args);
        if (is_wp_error($response)) {
            return $response;
        }
        $status = (int) wp_remote_retrieve_response_code($response);
        $raw = wp_remote_retrieve_body($response);
        $data = $raw !== '' ? json_decode($raw, true) : array();
        if ($status < 200 || $status >= 300) {
            $message = is_array($data) && !empty($data['message']) ? $data['message'] : 'GitHub request failed.';
            return new WP_Error('github_http_' . $status, $message, array('status' => $status, 'response' => $data));
        }
        return is_array($data) ? $data : array();
    }

    public static function api($repository, $endpoint = '', $method = 'GET', $body = null) {
        $repository = Vava_DevHub_Security::sanitize_repository($repository);
        if ($repository === '') {
            return new WP_Error('invalid_repository', 'Repository is not allowed.');
        }
        $url = self::API_BASE . '/repos/' . rawurlencode(self::owner()) . '/' . rawurlencode($repository) . $endpoint;
        return self::request_url($url, $method, $body);
    }

    public static function connection() {
        if (!self::has_token()) {
            return array(
                'configured' => false,
                'authenticated' => false,
                'login' => '',
                'message' => 'Read-only public GitHub access. Configure a write token for controlled push and Server Git Sync.',
            );
        }
        $user = self::request_url(self::API_BASE . '/user');
        if (is_wp_error($user)) {
            return array('configured' => true, 'authenticated' => false, 'login' => '', 'message' => $user->get_error_message());
        }
        return array(
            'configured' => true,
            'authenticated' => true,
            'login' => isset($user['login']) ? sanitize_text_field($user['login']) : '',
            'message' => 'GitHub authentication verified.',
        );
    }

    public static function repository($repository) {
        return self::api($repository);
    }

    public static function branches($repository) {
        return self::api($repository, '/branches?per_page=100');
    }

    public static function commits($repository, $branch, $limit = 15) {
        $branch = Vava_DevHub_Security::sanitize_branch($branch);
        if ($branch === '') {
            return new WP_Error('invalid_branch', 'Invalid Git branch.');
        }
        $limit = max(1, min(30, (int) $limit));
        return self::api($repository, '/commits?sha=' . rawurlencode($branch) . '&per_page=' . $limit);
    }

    public static function pulls($repository) {
        return self::api($repository, '/pulls?state=open&per_page=30&sort=updated&direction=desc');
    }

    public static function compare($repository, $base, $head) {
        $base = Vava_DevHub_Security::sanitize_branch($base);
        $head = Vava_DevHub_Security::sanitize_branch($head);
        if ($base === '' || $head === '') {
            return new WP_Error('invalid_compare', 'Both compare branches are required.');
        }
        return self::api($repository, '/compare/' . rawurlencode($base . '...' . $head));
    }

    private static function encode_path($path) {
        $segments = array_map('rawurlencode', explode('/', $path));
        return implode('/', $segments);
    }

    public static function file($repository, $path, $branch) {
        $branch = Vava_DevHub_Security::sanitize_branch($branch);
        if ($branch === '') {
            return new WP_Error('invalid_branch', 'Invalid Git branch.');
        }
        $safe_path = Vava_DevHub_Security::validate_path($repository, $path);
        if (is_wp_error($safe_path)) {
            return $safe_path;
        }
        $result = self::api($repository, '/contents/' . self::encode_path($safe_path) . '?ref=' . rawurlencode($branch));
        if (is_wp_error($result)) {
            return $result;
        }
        if (isset($result['encoding']) && $result['encoding'] === 'base64' && isset($result['content'])) {
            $result['decoded_content'] = base64_decode(str_replace("\n", '', $result['content']), true);
        }
        return $result;
    }

    public static function controlled_push($repository, $branch, $path, $content, $message, $expected_sha = '') {
        if (!self::has_token()) {
            return new WP_Error('token_required', 'A GitHub write token is required for controlled push.');
        }
        $repository = Vava_DevHub_Security::sanitize_repository($repository);
        $branch = Vava_DevHub_Security::sanitize_branch($branch);
        $path = Vava_DevHub_Security::validate_path($repository, $path);
        $content_check = Vava_DevHub_Security::validate_content($content);
        if ($repository === '' || $branch === '' || is_wp_error($path)) {
            return is_wp_error($path) ? $path : new WP_Error('invalid_push_target', 'Invalid controlled push target.');
        }
        if (is_wp_error($content_check)) {
            return $content_check;
        }
        $repo = self::repository($repository);
        if (is_wp_error($repo)) {
            return $repo;
        }
        $permissions = isset($repo['permissions']) && is_array($repo['permissions']) ? $repo['permissions'] : array();
        if (empty($permissions['push']) && empty($permissions['maintain']) && empty($permissions['admin'])) {
            return new WP_Error('no_push_permission', 'Configured GitHub token does not have write permission for this repository.');
        }
        $payload = array(
            'message' => sanitize_text_field($message),
            'content' => base64_encode((string) $content),
            'branch' => $branch,
        );
        if ($payload['message'] === '') {
            $payload['message'] = 'Vava Developer Hub controlled update';
        }
        if ($expected_sha !== '') {
            $payload['sha'] = sanitize_text_field($expected_sha);
        }
        return self::api($repository, '/contents/' . self::encode_path($path), 'PUT', $payload);
    }
}
