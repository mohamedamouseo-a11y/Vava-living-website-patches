<?php

if (!defined('ABSPATH')) {
    exit;
}

final class Vava_DevHub_Admin {
    private static $instance = null;
    private $page_hook = '';

    public static function instance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function __construct() {
        add_action('admin_menu', array($this, 'admin_menu'));
        add_action('admin_enqueue_scripts', array($this, 'enqueue_assets'));
        add_action('wp_ajax_vava_devhub_snapshot', array($this, 'ajax_snapshot'));
        add_action('wp_ajax_vava_devhub_compare', array($this, 'ajax_compare'));
        add_action('wp_ajax_vava_devhub_review', array($this, 'ajax_review'));
        add_action('wp_ajax_vava_devhub_push', array($this, 'ajax_push'));
    }

    public function admin_menu() {
        $this->page_hook = add_menu_page(
            'Vava Developer Hub',
            'Developer Hub',
            'manage_options',
            'vava-developer-hub',
            array($this, 'render_page'),
            'dashicons-editor-code',
            3
        );
    }

    public function enqueue_assets($hook) {
        if ($hook !== $this->page_hook) {
            return;
        }
        wp_enqueue_style(
            'vava-devhub-admin',
            VAVA_DEVHUB_URL . 'assets/admin.css',
            array(),
            VAVA_DEVHUB_VERSION
        );
        wp_enqueue_script(
            'vava-devhub-admin',
            VAVA_DEVHUB_URL . 'assets/admin.js',
            array(),
            VAVA_DEVHUB_VERSION,
            true
        );
        wp_localize_script('vava-devhub-admin', 'VavaDevHub', array(
            'ajaxUrl' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('vava_devhub_admin'),
            'defaultRepo' => 'Vava-living-website',
            'repositories' => Vava_DevHub_Security::allowed_repositories(),
            'hasToken' => Vava_DevHub_GitHub::has_token(),
        ));
    }

    private function error_message($result) {
        return is_wp_error($result) ? $result->get_error_message() : '';
    }

    private function normalize_repository($data) {
        if (is_wp_error($data)) {
            return array('error' => $data->get_error_message());
        }
        return array(
            'full_name' => isset($data['full_name']) ? $data['full_name'] : '',
            'name' => isset($data['name']) ? $data['name'] : '',
            'private' => !empty($data['private']),
            'default_branch' => isset($data['default_branch']) ? $data['default_branch'] : 'main',
            'html_url' => isset($data['html_url']) ? $data['html_url'] : '',
            'updated_at' => isset($data['updated_at']) ? $data['updated_at'] : '',
            'pushed_at' => isset($data['pushed_at']) ? $data['pushed_at'] : '',
            'permissions' => isset($data['permissions']) && is_array($data['permissions']) ? $data['permissions'] : array(),
        );
    }

    private function normalize_branches($data) {
        if (is_wp_error($data)) {
            return array('error' => $data->get_error_message(), 'items' => array());
        }
        $items = array();
        foreach ((array) $data as $item) {
            if (empty($item['name'])) {
                continue;
            }
            $items[] = array(
                'name' => $item['name'],
                'protected' => !empty($item['protected']),
                'sha' => isset($item['commit']['sha']) ? $item['commit']['sha'] : '',
            );
        }
        return array('error' => '', 'items' => $items);
    }

    private function normalize_commits($data) {
        if (is_wp_error($data)) {
            return array('error' => $data->get_error_message(), 'items' => array());
        }
        $items = array();
        foreach ((array) $data as $item) {
            $commit = isset($item['commit']) && is_array($item['commit']) ? $item['commit'] : array();
            $author = isset($commit['author']) && is_array($commit['author']) ? $commit['author'] : array();
            $message = isset($commit['message']) ? (string) $commit['message'] : '';
            $items[] = array(
                'sha' => isset($item['sha']) ? $item['sha'] : '',
                'short_sha' => isset($item['sha']) ? substr($item['sha'], 0, 7) : '',
                'message' => strtok($message, "\n"),
                'author' => isset($author['name']) ? $author['name'] : '',
                'date' => isset($author['date']) ? $author['date'] : '',
                'url' => isset($item['html_url']) ? $item['html_url'] : '',
            );
        }
        return array('error' => '', 'items' => $items);
    }

    private function normalize_pulls($data) {
        if (is_wp_error($data)) {
            return array('error' => $data->get_error_message(), 'items' => array());
        }
        $items = array();
        foreach ((array) $data as $item) {
            $items[] = array(
                'number' => isset($item['number']) ? (int) $item['number'] : 0,
                'title' => isset($item['title']) ? $item['title'] : '',
                'draft' => !empty($item['draft']),
                'user' => isset($item['user']['login']) ? $item['user']['login'] : '',
                'head' => isset($item['head']['ref']) ? $item['head']['ref'] : '',
                'base' => isset($item['base']['ref']) ? $item['base']['ref'] : '',
                'updated_at' => isset($item['updated_at']) ? $item['updated_at'] : '',
                'url' => isset($item['html_url']) ? $item['html_url'] : '',
            );
        }
        return array('error' => '', 'items' => $items);
    }

    public function ajax_snapshot() {
        Vava_DevHub_Security::authorize_admin_request();
        $repository = Vava_DevHub_Security::sanitize_repository(isset($_POST['repository']) ? wp_unslash($_POST['repository']) : '');
        if ($repository === '') {
            wp_send_json_error(array('message' => 'Invalid repository.'), 400);
        }

        $repo_data = Vava_DevHub_GitHub::repository($repository);
        $default_branch = !is_wp_error($repo_data) && !empty($repo_data['default_branch']) ? $repo_data['default_branch'] : 'main';
        $requested_branch = isset($_POST['branch']) ? wp_unslash($_POST['branch']) : $default_branch;
        $branch = Vava_DevHub_Security::sanitize_branch($requested_branch);
        if ($branch === '') {
            $branch = $default_branch;
        }

        $branches = Vava_DevHub_GitHub::branches($repository);
        $commits = Vava_DevHub_GitHub::commits($repository, $branch, 15);
        $pulls = Vava_DevHub_GitHub::pulls($repository);

        wp_send_json_success(array(
            'connection' => Vava_DevHub_GitHub::connection(),
            'repository' => $this->normalize_repository($repo_data),
            'selected_branch' => $branch,
            'branches' => $this->normalize_branches($branches),
            'commits' => $this->normalize_commits($commits),
            'pulls' => $this->normalize_pulls($pulls),
            'security' => array(
                'mode' => 'controlled-push',
                'token_storage' => 'server-only',
                'main_repo_scope' => array(
                    'wp-content/themes/vava-living-theme-ar-v1/',
                    'wp-content/plugins/vava-developer-hub/',
                ),
                'preview_expiry_minutes' => 10,
                'max_file_kb' => 512,
            ),
        ));
    }

    public function ajax_compare() {
        Vava_DevHub_Security::authorize_admin_request();
        $repository = Vava_DevHub_Security::sanitize_repository(isset($_POST['repository']) ? wp_unslash($_POST['repository']) : '');
        $base = isset($_POST['base']) ? wp_unslash($_POST['base']) : '';
        $head = isset($_POST['head']) ? wp_unslash($_POST['head']) : '';
        $result = Vava_DevHub_GitHub::compare($repository, $base, $head);
        if (is_wp_error($result)) {
            wp_send_json_error(array('message' => $result->get_error_message()), 400);
        }

        $files = array();
        foreach ((array) (isset($result['files']) ? $result['files'] : array()) as $file) {
            $files[] = array(
                'filename' => isset($file['filename']) ? $file['filename'] : '',
                'status' => isset($file['status']) ? $file['status'] : '',
                'additions' => isset($file['additions']) ? (int) $file['additions'] : 0,
                'deletions' => isset($file['deletions']) ? (int) $file['deletions'] : 0,
                'changes' => isset($file['changes']) ? (int) $file['changes'] : 0,
                'patch' => isset($file['patch']) ? substr($file['patch'], 0, 12000) : '',
            );
        }

        wp_send_json_success(array(
            'status' => isset($result['status']) ? $result['status'] : '',
            'ahead_by' => isset($result['ahead_by']) ? (int) $result['ahead_by'] : 0,
            'behind_by' => isset($result['behind_by']) ? (int) $result['behind_by'] : 0,
            'total_commits' => isset($result['total_commits']) ? (int) $result['total_commits'] : 0,
            'html_url' => isset($result['html_url']) ? $result['html_url'] : '',
            'files' => $files,
        ));
    }

    private function line_delta($before, $after) {
        $before_lines = $before === '' ? array() : preg_split('/\r\n|\r|\n/', $before);
        $after_lines = $after === '' ? array() : preg_split('/\r\n|\r|\n/', $after);
        $before_count = array_count_values($before_lines);
        $after_count = array_count_values($after_lines);
        $additions = 0;
        $deletions = 0;
        foreach ($after_count as $line => $count) {
            $additions += max(0, $count - (isset($before_count[$line]) ? $before_count[$line] : 0));
        }
        foreach ($before_count as $line => $count) {
            $deletions += max(0, $count - (isset($after_count[$line]) ? $after_count[$line] : 0));
        }
        return array('additions' => $additions, 'deletions' => $deletions);
    }

    public function ajax_review() {
        Vava_DevHub_Security::authorize_admin_request();
        $repository = Vava_DevHub_Security::sanitize_repository(isset($_POST['repository']) ? wp_unslash($_POST['repository']) : '');
        $branch = Vava_DevHub_Security::sanitize_branch(isset($_POST['branch']) ? wp_unslash($_POST['branch']) : '');
        $path_raw = isset($_POST['path']) ? wp_unslash($_POST['path']) : '';
        $content = isset($_POST['content']) ? wp_unslash($_POST['content']) : '';
        $message = sanitize_text_field(isset($_POST['message']) ? wp_unslash($_POST['message']) : '');

        $path = Vava_DevHub_Security::validate_path($repository, $path_raw);
        $content_check = Vava_DevHub_Security::validate_content($content);
        if ($repository === '' || $branch === '' || is_wp_error($path) || is_wp_error($content_check)) {
            $error = is_wp_error($path) ? $path : (is_wp_error($content_check) ? $content_check : new WP_Error('invalid_review', 'Invalid review request.'));
            wp_send_json_error(array('message' => $error->get_error_message()), 400);
        }

        $existing = Vava_DevHub_GitHub::file($repository, $path, $branch);
        $before = '';
        $expected_sha = '';
        $is_new = false;
        if (is_wp_error($existing)) {
            $data = $existing->get_error_data();
            $status = is_array($data) && isset($data['status']) ? (int) $data['status'] : 0;
            if ($status !== 404) {
                wp_send_json_error(array('message' => $existing->get_error_message()), 400);
            }
            $is_new = true;
        } else {
            $expected_sha = isset($existing['sha']) ? (string) $existing['sha'] : '';
            $before = isset($existing['decoded_content']) && is_string($existing['decoded_content']) ? $existing['decoded_content'] : '';
        }

        $delta = $this->line_delta($before, $content);
        $payload = array(
            'repository' => $repository,
            'branch' => $branch,
            'path' => $path,
            'message' => $message,
            'content_hash' => hash('sha256', $content),
            'expected_sha' => $expected_sha,
        );
        $preview_token = Vava_DevHub_Security::create_preview_token($payload);

        wp_send_json_success(array(
            'preview_token' => $preview_token,
            'repository' => $repository,
            'branch' => $branch,
            'path' => $path,
            'is_new' => $is_new,
            'current_sha' => $expected_sha,
            'content_sha256' => $payload['content_hash'],
            'additions' => $delta['additions'],
            'deletions' => $delta['deletions'],
            'bytes' => strlen($content),
            'expires_in_seconds' => 600,
            'checks' => array(
                'admin_capability' => 'passed',
                'nonce' => 'passed',
                'repository_allowlist' => 'passed',
                'path_scope' => 'passed',
                'file_type' => 'passed',
                'size' => 'passed',
                'secret_scan' => 'passed',
                'race_protection' => 'armed',
            ),
        ));
    }

    public function ajax_push() {
        Vava_DevHub_Security::authorize_admin_request();
        $preview_token = isset($_POST['preview_token']) ? wp_unslash($_POST['preview_token']) : '';
        $record = Vava_DevHub_Security::consume_preview_token($preview_token);
        if (!$record || empty($record['payload'])) {
            wp_send_json_error(array('message' => 'Review authorization expired or has already been used. Run Review again.'), 409);
        }

        $approved = $record['payload'];
        $repository = Vava_DevHub_Security::sanitize_repository(isset($_POST['repository']) ? wp_unslash($_POST['repository']) : '');
        $branch = Vava_DevHub_Security::sanitize_branch(isset($_POST['branch']) ? wp_unslash($_POST['branch']) : '');
        $path = isset($_POST['path']) ? ltrim(str_replace('\\', '/', trim(wp_unslash($_POST['path']))), '/') : '';
        $content = isset($_POST['content']) ? wp_unslash($_POST['content']) : '';
        $message = sanitize_text_field(isset($_POST['message']) ? wp_unslash($_POST['message']) : '');

        $same = $repository === $approved['repository']
            && $branch === $approved['branch']
            && $path === $approved['path']
            && $message === $approved['message']
            && hash('sha256', $content) === $approved['content_hash'];
        if (!$same) {
            wp_send_json_error(array('message' => 'Content or push target changed after review. Run Review again.'), 409);
        }

        $current = Vava_DevHub_GitHub::file($repository, $path, $branch);
        if (is_wp_error($current)) {
            $data = $current->get_error_data();
            $status = is_array($data) && isset($data['status']) ? (int) $data['status'] : 0;
            if ($status !== 404 || $approved['expected_sha'] !== '') {
                wp_send_json_error(array('message' => 'Remote file changed or could not be revalidated. Push stopped.'), 409);
            }
        } else {
            $current_sha = isset($current['sha']) ? (string) $current['sha'] : '';
            if ($current_sha !== $approved['expected_sha']) {
                wp_send_json_error(array('message' => 'Remote file changed after review. Refresh and review again.'), 409);
            }
        }

        $result = Vava_DevHub_GitHub::controlled_push(
            $repository,
            $branch,
            $path,
            $content,
            $message,
            $approved['expected_sha']
        );
        if (is_wp_error($result)) {
            wp_send_json_error(array('message' => $result->get_error_message()), 400);
        }

        wp_send_json_success(array(
            'message' => 'Controlled push completed successfully.',
            'content_url' => isset($result['content']['html_url']) ? $result['content']['html_url'] : '',
            'commit_url' => isset($result['commit']['html_url']) ? $result['commit']['html_url'] : '',
            'commit_sha' => isset($result['commit']['sha']) ? $result['commit']['sha'] : '',
        ));
    }

    public function render_page() {
        if (!current_user_can('manage_options')) {
            wp_die(esc_html__('You do not have permission to access this page.', 'vava-devhub'));
        }
        ?>
        <div class="wrap vava-devhub" id="vava-devhub-app">
            <div class="vdh-hero">
                <div>
                    <span class="vdh-eyebrow">VAVA LIVING · ENGINEERING</span>
                    <h1>Developer Hub</h1>
                    <p>GitHub operations, code review and guarded pushes from one control room.</p>
                </div>
                <button type="button" class="button button-primary vdh-refresh" id="vdh-refresh">Refresh</button>
            </div>

            <div class="vdh-toolbar">
                <label>Repository
                    <select id="vdh-repository">
                        <option value="Vava-living-website">Vava-living-website</option>
                        <option value="Vava-living-website-patches">Vava-living-website-patches</option>
                    </select>
                </label>
                <label>Branch
                    <select id="vdh-branch"><option value="main">main</option></select>
                </label>
                <div class="vdh-connection" id="vdh-connection">Checking GitHub…</div>
            </div>

            <div class="vdh-cards" id="vdh-cards">
                <div class="vdh-card"><span>GitHub</span><strong id="vdh-card-github">—</strong><small id="vdh-card-github-sub">Connection</small></div>
                <div class="vdh-card"><span>Repository</span><strong id="vdh-card-repo">—</strong><small id="vdh-card-repo-sub">Primary</small></div>
                <div class="vdh-card"><span>Branch</span><strong id="vdh-card-branch">—</strong><small id="vdh-card-branch-sub">Selected</small></div>
                <div class="vdh-card"><span>Open PRs</span><strong id="vdh-card-prs">—</strong><small>Needs attention</small></div>
            </div>

            <div class="vdh-tabs" role="tablist">
                <button class="vdh-tab is-active" data-tab="activity">Activity</button>
                <button class="vdh-tab" data-tab="pulls">Pull Requests</button>
                <button class="vdh-tab" data-tab="compare">Compare</button>
                <button class="vdh-tab" data-tab="push">Controlled Push</button>
                <button class="vdh-tab" data-tab="security">Security</button>
            </div>

            <section class="vdh-panel is-active" data-panel="activity">
                <div class="vdh-panel-head"><div><h2>Recent commits</h2><p>Latest work on the selected branch.</p></div></div>
                <div id="vdh-commits" class="vdh-list"><div class="vdh-empty">Loading…</div></div>
            </section>

            <section class="vdh-panel" data-panel="pulls">
                <div class="vdh-panel-head"><div><h2>Open pull requests</h2><p>Review active changes before merge.</p></div></div>
                <div id="vdh-pulls" class="vdh-list"><div class="vdh-empty">Loading…</div></div>
            </section>

            <section class="vdh-panel" data-panel="compare">
                <div class="vdh-panel-head"><div><h2>Branch compare</h2><p>Inspect commit distance and changed files.</p></div></div>
                <div class="vdh-form-row">
                    <label>Base <select id="vdh-compare-base"></select></label>
                    <label>Head <select id="vdh-compare-head"></select></label>
                    <button class="button" type="button" id="vdh-run-compare">Compare branches</button>
                </div>
                <div id="vdh-compare-result"></div>
            </section>

            <section class="vdh-panel" data-panel="push">
                <div class="vdh-panel-head">
                    <div><h2>Controlled Push</h2><p>Every write requires review, safety checks and a short-lived authorization.</p></div>
                    <span class="vdh-badge vdh-badge-safe">Guarded</span>
                </div>
                <div class="vdh-warning" id="vdh-token-warning">A server-side <code>VAVA_GITHUB_TOKEN</code> with repository write permission is required. The token is never sent to the browser.</div>
                <div class="vdh-push-grid">
                    <label>File path<input id="vdh-push-path" type="text" placeholder="wp-content/themes/vava-living-theme-ar-v1/..." autocomplete="off"></label>
                    <label>Commit message<input id="vdh-push-message" type="text" placeholder="Describe the change" autocomplete="off"></label>
                    <label class="vdh-full">File content<textarea id="vdh-push-content" rows="18" spellcheck="false" placeholder="Paste the complete reviewed file content here"></textarea></label>
                </div>
                <div class="vdh-actions">
                    <button type="button" class="button" id="vdh-review-push">1. Review & Security Check</button>
                    <button type="button" class="button button-primary" id="vdh-confirm-push" disabled>2. Authorize Controlled Push</button>
                </div>
                <div id="vdh-push-review"></div>
            </section>

            <section class="vdh-panel" data-panel="security">
                <div class="vdh-panel-head"><div><h2>Security policy</h2><p>Hard limits applied before any repository write.</p></div></div>
                <div class="vdh-security-grid">
                    <div><strong>Admin only</strong><span><code>manage_options</code> + WordPress nonce on every action.</span></div>
                    <div><strong>Server-side token</strong><span>GitHub credentials are loaded from environment / wp-config and never exposed to JavaScript.</span></div>
                    <div><strong>Repository allowlist</strong><span>Only Vava website and Vava patch repositories are accepted.</span></div>
                    <div><strong>Protected paths</strong><span>WordPress core, config, uploads, caches, secrets and workflow files are blocked.</span></div>
                    <div><strong>Secret scanner</strong><span>Known private key and token signatures stop the push.</span></div>
                    <div><strong>Race protection</strong><span>Remote SHA is rechecked after review and immediately before push.</span></div>
                </div>
            </section>

            <div class="vdh-toast" id="vdh-toast" aria-live="polite"></div>
        </div>
        <?php
    }
}
