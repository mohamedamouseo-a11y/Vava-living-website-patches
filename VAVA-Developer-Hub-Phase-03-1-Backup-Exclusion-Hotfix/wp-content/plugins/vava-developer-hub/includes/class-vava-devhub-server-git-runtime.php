<?php

if (!defined('ABSPATH')) {
    exit;
}

final class Vava_DevHub_Server_Git_Runtime {
    const EXPECTED_REPO = 'Vava-living-website';
    const EXPECTED_BRANCH = 'main';
    const PREVIEW_TTL = 600;
    const MAX_DIFF_BYTES = 120000;
    const MAX_SCAN_BYTES = 1048576;
    const AUDIT_OPTION = 'vava_devhub_server_git_audit';
    const LOCK_OPTION = 'vava_devhub_server_git_lock';

    public static function root() {
        $configured = getenv('VAVA_GIT_ROOT');
        $root = is_string($configured) && trim($configured) !== '' ? trim($configured) : ABSPATH;
        $real = realpath($root);
        return $real !== false ? rtrim($real, DIRECTORY_SEPARATOR) : rtrim($root, DIRECTORY_SEPARATOR);
    }

    public static function git_metadata_exists() {
        $root = self::root();
        return is_dir($root . DIRECTORY_SEPARATOR . '.git') || is_file($root . DIRECTORY_SEPARATOR . '.git');
    }

    public static function proc_open_available() {
        if (!function_exists('proc_open')) {
            return false;
        }
        $disabled = array_filter(array_map('trim', explode(',', (string) ini_get('disable_functions'))));
        return !in_array('proc_open', $disabled, true);
    }

    public static function environment($network = false, $author = false) {
        $env = getenv();
        if (!is_array($env)) {
            $env = array();
        }
        $env['GIT_TERMINAL_PROMPT'] = '0';
        $env['LC_ALL'] = 'C';
        $env['LANG'] = 'C';

        if ($author) {
            $env['GIT_AUTHOR_NAME'] = 'Vava Developer Hub';
            $env['GIT_AUTHOR_EMAIL'] = 'developer-hub@thevavaliving.com';
            $env['GIT_COMMITTER_NAME'] = 'Vava Developer Hub';
            $env['GIT_COMMITTER_EMAIL'] = 'developer-hub@thevavaliving.com';
        }

        if ($network) {
            $token = Vava_DevHub_GitHub::token();
            if ($token !== '') {
                $env['GIT_CONFIG_COUNT'] = '1';
                $env['GIT_CONFIG_KEY_0'] = 'http.extraHeader';
                $env['GIT_CONFIG_VALUE_0'] = 'AUTHORIZATION: basic ' . base64_encode('x-access-token:' . $token);
            }
        }

        return $env;
    }

    public static function run_git($args, $options = array()) {
        if (!self::proc_open_available()) {
            return new WP_Error('proc_open_disabled', 'Server Git Sync requires PHP proc_open, but it is unavailable or disabled on this server.');
        }

        $root = self::root();
        if (!is_dir($root)) {
            return new WP_Error('git_root_missing', 'Configured WordPress/Git root does not exist.');
        }

        $timeout = isset($options['timeout']) ? max(5, min(180, (int) $options['timeout'])) : 30;
        $network = !empty($options['network']);
        $author = !empty($options['author']);
        $command = array_merge(array('git'), array_values($args));
        $descriptors = array(
            0 => array('pipe', 'r'),
            1 => array('pipe', 'w'),
            2 => array('pipe', 'w'),
        );
        $pipes = array();

        $process = @proc_open(
            $command,
            $descriptors,
            $pipes,
            $root,
            self::environment($network, $author),
            array('bypass_shell' => true)
        );
        if (!is_resource($process)) {
            return new WP_Error('git_start_failed', 'Unable to start Git on the server.');
        }

        fclose($pipes[0]);
        stream_set_blocking($pipes[1], false);
        stream_set_blocking($pipes[2], false);
        $stdout = '';
        $stderr = '';
        $deadline = microtime(true) + $timeout;
        $exit = null;

        while (true) {
            $stdout .= stream_get_contents($pipes[1]);
            $stderr .= stream_get_contents($pipes[2]);
            $status = proc_get_status($process);
            if (!$status['running']) {
                $exit = (int) $status['exitcode'];
                break;
            }
            if (microtime(true) >= $deadline) {
                proc_terminate($process, 15);
                usleep(150000);
                $status = proc_get_status($process);
                if ($status['running']) {
                    proc_terminate($process, 9);
                }
                fclose($pipes[1]);
                fclose($pipes[2]);
                proc_close($process);
                return new WP_Error('git_timeout', 'Git operation timed out.');
            }
            usleep(50000);
        }

        $stdout .= stream_get_contents($pipes[1]);
        $stderr .= stream_get_contents($pipes[2]);
        fclose($pipes[1]);
        fclose($pipes[2]);
        proc_close($process);

        $combined = trim($stderr !== '' ? $stderr : $stdout);
        if ($exit !== 0) {
            return new WP_Error(
                'git_failed',
                self::redact($combined !== '' ? $combined : 'Git command failed.'),
                array('exit_code' => $exit)
            );
        }

        return array('stdout' => $stdout, 'stderr' => $stderr, 'exit_code' => 0);
    }

    public static function redact($value) {
        $value = (string) $value;
        $value = preg_replace('/github_pat_[A-Za-z0-9_]+/', '[REDACTED]', $value);
        $value = preg_replace('/gh[pousr]_[A-Za-z0-9]+/', '[REDACTED]', $value);
        $value = preg_replace('#https://[^\s/@:]+:[^\s/@]+@github\.com#i', 'https://[REDACTED]@github.com', $value);
        return substr($value, 0, 4000);
    }

    public static function normalize_remote($remote) {
        $remote = trim((string) $remote);
        $patterns = array(
            '#^https://github\.com/([^/]+)/([^/]+?)(?:\.git)?$#i',
            '#^git@github\.com:([^/]+)/([^/]+?)(?:\.git)?$#i',
            '#^ssh://git@github\.com/([^/]+)/([^/]+?)(?:\.git)?$#i',
        );
        foreach ($patterns as $pattern) {
            if (preg_match($pattern, $remote, $match)) {
                return strtolower($match[1] . '/' . $match[2]);
            }
        }
        return '';
    }

    public static function expected_remote_slug() {
        return strtolower(Vava_DevHub_GitHub::owner() . '/' . self::EXPECTED_REPO);
    }

    public static function expected_remote_url() {
        return 'https://github.com/' . rawurlencode(Vava_DevHub_GitHub::owner()) . '/' . self::EXPECTED_REPO . '.git';
    }

    public static function clean_path($path) {
        $path = str_replace('\\', '/', ltrim((string) $path, '/'));
        while (strpos($path, '//') !== false) {
            $path = str_replace('//', '/', $path);
        }
        return $path;
    }

    /**
     * Return the first repository-relative backup directory root found inside a
     * WordPress source-code path. This intentionally targets backup directory
     * names, not legitimate plugins such as "backupbuddy".
     */
    public static function backup_root($path) {
        $path = self::clean_path($path);
        $segments = explode('/', $path);
        if (count($segments) < 3 || strtolower($segments[0]) !== 'wp-content') {
            return '';
        }

        $scope = strtolower($segments[1]);
        if (!in_array($scope, array('themes', 'plugins', 'mu-plugins'), true)) {
            return '';
        }

        for ($i = 2, $count = count($segments); $i < $count; $i++) {
            $segment = strtolower((string) $segments[$i]);
            $is_plain_backup = in_array($segment, array('backup', 'backups'), true);
            $is_suffixed_backup = preg_match('/(?:[-_.]backups?)(?:$|[-_.0-9])/', $segment) === 1;
            if ($is_plain_backup || $is_suffixed_backup) {
                return implode('/', array_slice($segments, 0, $i + 1));
            }
        }

        return '';
    }

    public static function is_backup_path($path) {
        return self::backup_root($path) !== '';
    }

    public static function excluded_reason($path) {
        $path = self::clean_path($path);
        $lower = strtolower($path);
        if ($path === '' || strpos($path, '../') !== false || strpos($path, "\0") !== false) {
            return 'invalid path';
        }
        if (
            strpos($lower, 'wp-content/themes/') !== 0 &&
            strpos($lower, 'wp-content/plugins/') !== 0 &&
            strpos($lower, 'wp-content/mu-plugins/') !== 0
        ) {
            return 'outside WordPress source-code scope';
        }
        if (self::is_backup_path($path)) {
            return 'backup directory';
        }

        $blocked = array(
            'wp-content/uploads/',
            'wp-content/cache/',
            'wp-content/litespeed/',
            'wp-content/upgrade/',
            'wp-content/backups/',
            'wp-content/ai1wm-backups/',
            '/.git/',
            '/.env',
            '.htaccess',
            'wp-config.php',
            'error_log',
            'debug.log',
            '.user.ini',
            'php.ini',
            'id_rsa',
            'id_ed25519',
            'private_key',
            'credentials',
            'secrets',
        );
        foreach ($blocked as $fragment) {
            if (strpos('/' . $lower, $fragment) !== false || strpos($lower, $fragment) !== false) {
                return 'protected/generated/sensitive path';
            }
        }
        if (preg_match('/\.(?:sql|sql\.gz|zip|tar|tgz|bak|pem|key)$/i', $lower)) {
            return 'backup/database/key file';
        }
        return '';
    }

    public static function parse_status($raw) {
        $tokens = explode("\0", (string) $raw);
        $items = array();
        $count = count($tokens);
        for ($i = 0; $i < $count; $i++) {
            $entry = $tokens[$i];
            if ($entry === '' || strlen($entry) < 4) {
                continue;
            }
            $xy = substr($entry, 0, 2);
            $path = self::clean_path(substr($entry, 3));
            $original = '';
            if ((strpos($xy, 'R') !== false || strpos($xy, 'C') !== false) && isset($tokens[$i + 1]) && $tokens[$i + 1] !== '') {
                $original = self::clean_path($tokens[++$i]);
            }
            $reason = self::excluded_reason($path);
            if ($reason === '' && $original !== '') {
                $reason = self::excluded_reason($original);
            }
            $items[] = array(
                'status' => $xy,
                'path' => $path,
                'original_path' => $original,
                'untracked' => $xy === '??',
                'safe' => $reason === '',
                'reason' => $reason,
            );
        }
        return $items;
    }

    public static function change_paths($changes) {
        $paths = array();
        foreach ($changes as $change) {
            if (empty($change['safe'])) {
                continue;
            }
            if (!empty($change['path'])) {
                $paths[] = $change['path'];
            }
            if (!empty($change['original_path'])) {
                $paths[] = $change['original_path'];
            }
        }
        return array_values(array_unique($paths));
    }

    public static function tracked_backup_roots() {
        if (!self::git_metadata_exists()) {
            return array();
        }
        $result = self::run_git(array(
            'ls-files', '-z', '--',
            'wp-content/themes',
            'wp-content/plugins',
            'wp-content/mu-plugins',
        ));
        if (is_wp_error($result)) {
            return $result;
        }

        $roots = array();
        foreach (explode("\0", (string) $result['stdout']) as $tracked_path) {
            $tracked_path = self::clean_path($tracked_path);
            if ($tracked_path === '') {
                continue;
            }
            $root = self::backup_root($tracked_path);
            if ($root !== '') {
                $roots[$root] = true;
            }
        }
        return array_keys($roots);
    }

    public static function secret_scan($paths) {
        $root = self::root();
        $patterns = array(
            '/-----BEGIN (?:RSA |EC |OPENSSH )?PRIVATE KEY-----/i' => 'private key',
            '/\bgithub_pat_[A-Za-z0-9_]{20,}\b/' => 'GitHub token',
            '/\bgh[pousr]_[A-Za-z0-9]{20,}\b/' => 'GitHub token',
            '/\bAKIA[0-9A-Z]{16}\b/' => 'AWS access key',
        );
        $text_exts = array('php', 'css', 'js', 'json', 'md', 'txt', 'html', 'htm', 'yml', 'yaml', 'xml', 'svg', 'ini', 'conf');

        foreach ($paths as $path) {
            $reason = self::excluded_reason($path);
            if ($reason !== '') {
                return new WP_Error('unsafe_path', $path . ' is excluded: ' . $reason);
            }
            $full = $root . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $path);
            if (!is_file($full)) {
                continue;
            }
            $size = @filesize($full);
            $ext = strtolower(pathinfo($full, PATHINFO_EXTENSION));
            if ($size === false || $size > self::MAX_SCAN_BYTES || !in_array($ext, $text_exts, true)) {
                continue;
            }
            $content = @file_get_contents($full);
            if (!is_string($content)) {
                continue;
            }
            foreach ($patterns as $pattern => $label) {
                if (preg_match($pattern, $content)) {
                    return new WP_Error('secret_detected', 'Potential ' . $label . ' detected in ' . $path . '. Commit blocked.');
                }
            }
        }
        return true;
    }

    public static function acquire_lock() {
        $now = time();
        $value = get_option(self::LOCK_OPTION, null);
        if (is_array($value) && isset($value['time']) && ($now - (int) $value['time']) < 180) {
            return new WP_Error('git_busy', 'Another Server Git operation is already running.');
        }
        if ($value !== null) {
            delete_option(self::LOCK_OPTION);
        }
        $token = wp_generate_password(24, false, false);
        if (!add_option(self::LOCK_OPTION, array('token' => $token, 'time' => $now), '', false)) {
            return new WP_Error('git_busy', 'Another Server Git operation is already running.');
        }
        return $token;
    }

    public static function release_lock($token) {
        $value = get_option(self::LOCK_OPTION, null);
        if (is_array($value) && isset($value['token']) && hash_equals((string) $value['token'], (string) $token)) {
            delete_option(self::LOCK_OPTION);
        }
    }

    public static function audit($action, $success, $details = array()) {
        $items = get_option(self::AUDIT_OPTION, array());
        if (!is_array($items)) {
            $items = array();
        }
        array_unshift($items, array(
            'time' => gmdate('c'),
            'user_id' => get_current_user_id(),
            'action' => sanitize_key($action),
            'success' => (bool) $success,
            'details' => array_map(function ($value) {
                return is_scalar($value) ? substr(self::redact((string) $value), 0, 500) : '';
            }, (array) $details),
        ));
        update_option(self::AUDIT_OPTION, array_slice($items, 0, 40), false);
    }

    public static function audit_items() {
        $items = get_option(self::AUDIT_OPTION, array());
        return is_array($items) ? array_slice($items, 0, 20) : array();
    }

    public static function git_version() {
        $result = self::run_git(array('--version'));
        return is_wp_error($result) ? '' : trim($result['stdout']);
    }

    public static function status_snapshot() {
        $root = self::root();
        $git_available = self::proc_open_available();
        $snapshot = array(
            'root' => $root,
            'git_available' => $git_available,
            'git_version' => '',
            'repository_present' => self::git_metadata_exists(),
            'setup_available' => false,
            'remote' => '',
            'remote_valid' => false,
            'branch' => '',
            'head' => '',
            'head_short' => '',
            'ahead' => 0,
            'behind' => 0,
            'changes' => array(),
            'safe_count' => 0,
            'excluded_count' => 0,
            'credentials' => Vava_DevHub_Credentials::status(),
            'audit' => self::audit_items(),
        );

        if (!$git_available) {
            return $snapshot;
        }
        $snapshot['git_version'] = self::git_version();
        $snapshot['setup_available'] = !$snapshot['repository_present'] && is_writable($root);
        if (!$snapshot['repository_present']) {
            return $snapshot;
        }

        $top = self::run_git(array('rev-parse', '--show-toplevel'));
        if (is_wp_error($top)) {
            $snapshot['error'] = $top->get_error_message();
            return $snapshot;
        }
        $resolved_top = realpath(trim($top['stdout']));
        $resolved_root = realpath($root);
        if ($resolved_top === false || $resolved_root === false || $resolved_top !== $resolved_root) {
            $snapshot['error'] = 'Git repository root does not match the configured WordPress root.';
            return $snapshot;
        }

        $branch = self::run_git(array('branch', '--show-current'));
        $head = self::run_git(array('rev-parse', 'HEAD'));
        $remote = self::run_git(array('remote', 'get-url', 'origin'));
        $status = self::run_git(array(
            'status', '--porcelain=v1', '-z', '--untracked-files=all', '--',
            'wp-content/themes',
            'wp-content/plugins',
            'wp-content/mu-plugins',
        ));
        if (is_wp_error($branch) || is_wp_error($head) || is_wp_error($remote) || is_wp_error($status)) {
            $first = is_wp_error($branch) ? $branch : (is_wp_error($head) ? $head : (is_wp_error($remote) ? $remote : $status));
            $snapshot['error'] = $first->get_error_message();
            return $snapshot;
        }

        $snapshot['branch'] = trim($branch['stdout']);
        $snapshot['head'] = trim($head['stdout']);
        $snapshot['head_short'] = substr($snapshot['head'], 0, 10);
        $snapshot['remote'] = trim($remote['stdout']);
        $snapshot['remote_valid'] = self::normalize_remote($snapshot['remote']) === self::expected_remote_slug();
        $snapshot['changes'] = self::parse_status($status['stdout']);
        foreach ($snapshot['changes'] as $change) {
            if (!empty($change['safe'])) {
                $snapshot['safe_count']++;
            } else {
                $snapshot['excluded_count']++;
            }
        }

        if ($snapshot['branch'] !== '') {
            $counts = self::run_git(array('rev-list', '--left-right', '--count', 'origin/' . $snapshot['branch'] . '...HEAD'));
            if (!is_wp_error($counts)) {
                $parts = preg_split('/\s+/', trim($counts['stdout']));
                if (count($parts) >= 2) {
                    $snapshot['behind'] = (int) $parts[0];
                    $snapshot['ahead'] = (int) $parts[1];
                }
            }
        }

        return $snapshot;
    }

    public static function ensure_operational($snapshot) {
        if (empty($snapshot['git_available'])) {
            return new WP_Error('git_unavailable', 'Server Git is unavailable because PHP proc_open is disabled or missing.');
        }
        if (empty($snapshot['repository_present'])) {
            return new WP_Error('repo_not_connected', 'The WordPress root is not connected to Git yet. Use Connect Server Repository first.');
        }
        if (!empty($snapshot['error'])) {
            return new WP_Error('repo_invalid', $snapshot['error']);
        }
        if (empty($snapshot['remote_valid'])) {
            return new WP_Error('remote_mismatch', 'Origin remote is not the approved Vava-living-website repository. Operation blocked.');
        }
        if (empty($snapshot['branch']) || Vava_DevHub_Security::sanitize_branch($snapshot['branch']) === '') {
            return new WP_Error('branch_invalid', 'The server is in a detached or invalid Git branch state.');
        }
        return true;
    }

    public static function fingerprint($snapshot, $message) {
        $safe = array();
        foreach ($snapshot['changes'] as $change) {
            if (!empty($change['safe'])) {
                $safe[] = array($change['status'], $change['path'], $change['original_path']);
            }
        }
        $cleanup = self::tracked_backup_roots();
        if (is_wp_error($cleanup)) {
            $cleanup = array();
        }
        sort($cleanup);
        return hash('sha256', wp_json_encode(array($snapshot['head'], $snapshot['branch'], $message, $safe, $cleanup)));
    }

    public static function create_preview_token($payload) {
        $token = wp_generate_password(32, false, false);
        set_transient('vava_devhub_git_preview_' . get_current_user_id() . '_' . $token, $payload, self::PREVIEW_TTL);
        return $token;
    }

    public static function consume_preview_token($token) {
        $key = 'vava_devhub_git_preview_' . get_current_user_id() . '_' . sanitize_text_field((string) $token);
        $payload = get_transient($key);
        if ($payload !== false) {
            delete_transient($key);
        }
        return is_array($payload) ? $payload : false;
    }

    public static function diff_preview($paths) {
        $diff = '';
        if ($paths) {
            $args = array_merge(array('diff', '--no-ext-diff', '--unified=2', '--'), $paths);
            $result = self::run_git($args, array('timeout' => 45));
            if (is_wp_error($result)) {
                $diff = 'Diff preview unavailable: ' . $result->get_error_message();
            } else {
                $diff = (string) $result['stdout'];
            }
        }

        $cleanup = self::tracked_backup_roots();
        if (!is_wp_error($cleanup) && $cleanup) {
            $diff .= ($diff !== '' ? "\n\n" : '') . "[Repository cleanup]\n";
            $diff .= "The following tracked backup directories will be removed from Git tracking only. Live server files will NOT be deleted:\n";
            foreach ($cleanup as $root) {
                $diff .= '- ' . $root . "\n";
            }
        }

        if (strlen($diff) > self::MAX_DIFF_BYTES) {
            $diff = substr($diff, 0, self::MAX_DIFF_BYTES) . "\n\n[Diff preview truncated]";
        }
        return $diff;
    }

    public static function stage_paths($paths) {
        foreach (array_chunk($paths, 80) as $chunk) {
            $result = self::run_git(array_merge(array('add', '-A', '--'), $chunk), array('timeout' => 60));
            if (is_wp_error($result)) {
                return $result;
            }
        }

        $cleanup = self::tracked_backup_roots();
        if (is_wp_error($cleanup)) {
            return $cleanup;
        }
        foreach (array_chunk($cleanup, 30) as $chunk) {
            if (!$chunk) {
                continue;
            }
            $result = self::run_git(
                array_merge(array('rm', '-r', '--cached', '--ignore-unmatch', '--'), $chunk),
                array('timeout' => 60)
            );
            if (is_wp_error($result)) {
                self::run_git(array('reset', '--mixed', 'HEAD'));
                return new WP_Error(
                    'backup_cleanup_failed',
                    'Could not safely remove a tracked backup directory from the Git index. Live backup files were not deleted. ' . $result->get_error_message()
                );
            }
        }

        return true;
    }

    public static function verify_staged_paths() {
        $result = self::run_git(array('diff', '--cached', '--name-only', '-z'));
        if (is_wp_error($result)) {
            return $result;
        }
        $deleted_result = self::run_git(array('diff', '--cached', '--diff-filter=D', '--name-only', '-z'));
        if (is_wp_error($deleted_result)) {
            self::run_git(array('reset', '--mixed', 'HEAD'));
            return $deleted_result;
        }

        $paths = array_values(array_filter(array_map(array(__CLASS__, 'clean_path'), explode("\0", $result['stdout']))));
        $deleted_paths = array_values(array_filter(array_map(array(__CLASS__, 'clean_path'), explode("\0", $deleted_result['stdout']))));
        $deleted_lookup = array_fill_keys($deleted_paths, true);

        foreach ($paths as $path) {
            $reason = self::excluded_reason($path);
            if ($reason === '') {
                continue;
            }
            if (self::is_backup_path($path) && isset($deleted_lookup[$path])) {
                continue;
            }

            self::run_git(array('reset', '--mixed', 'HEAD'));
            return new WP_Error('staged_scope_violation', 'Unsafe staged path detected: ' . $path . '. Staging was reset.');
        }

        return $paths;
    }
}
