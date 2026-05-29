<?php
namespace SCBD_Lite;

defined('ABSPATH') || exit;

final class GitHub_Updater {
    private const OWNER = 'MikeWard0321';
    private const REPO = 'seo-control-bridge-for-divi-lite';
    private const CACHE_KEY = 'scbd_lite_github_release';
    private const CACHE_TTL = 30 * MINUTE_IN_SECONDS;

    private string $plugin_file;
    private string $plugin_basename;

    public function __construct(string $plugin_file) {
        $this->plugin_file = $plugin_file;
        $this->plugin_basename = plugin_basename($plugin_file);
    }

    public function hooks(): void {
        add_filter('pre_set_site_transient_update_plugins', [$this, 'inject_update']);
        add_filter('site_transient_update_plugins', [$this, 'normalize_update_transient']);
        add_filter('plugins_api', [$this, 'plugin_info'], 20, 3);
        add_filter('upgrader_post_install', [$this, 'rename_after_install'], 10, 3);
        add_action('admin_notices', [$this, 'dashboard_update_notice']);
        add_action('admin_init', [$this, 'maybe_force_update_check']);
        add_action('upgrader_process_complete', [$this, 'clear_cache_after_update'], 10, 2);
        add_action('admin_init', [$this, 'clear_stale_update_state']);
    }

    public function inject_update(object $transient): object {
        if (empty($transient->checked) || !isset($transient->checked[$this->plugin_basename])) {
            return $transient;
        }

        // WordPress is actively rebuilding the update_plugins transient here.
        // Do not use the cached GitHub response, or a site can miss a newly
        // published release until the separate display cache expires.
        $release = $this->get_latest_release(false);
        if (!$release || empty($release['version'])) {
            return $transient;
        }

        if (!version_compare($release['version'], SCBD_LITE_VERSION, '>')) {
            return $this->mark_current($transient);
        }

        $transient->response[$this->plugin_basename] = $this->update_object($release);

        if (isset($transient->no_update[$this->plugin_basename])) {
            unset($transient->no_update[$this->plugin_basename]);
        }

        return $transient;
    }

    public function normalize_update_transient($transient) {
        if (!is_object($transient)) {
            return $transient;
        }

        if (empty($transient->response[$this->plugin_basename])) {
            return $transient;
        }

        $update = $transient->response[$this->plugin_basename];
        $new_version = is_object($update) && !empty($update->new_version) ? (string) $update->new_version : '';

        if ('' !== $new_version && !version_compare($new_version, SCBD_LITE_VERSION, '>')) {
            return $this->mark_current($transient);
        }

        return $transient;
    }

    public function plugin_info($result, string $action, object $args) {
        if ('plugin_information' !== $action || empty($args->slug) || self::REPO !== $args->slug) {
            return $result;
        }

        $release = $this->get_latest_release(false) ?: [];
        $version = (string) ($release['version'] ?? SCBD_LITE_VERSION);
        $body = (string) ($release['body'] ?? '');
        $package = (string) ($release['package'] ?? 'https://github.com/' . self::OWNER . '/' . self::REPO . '/releases/latest');
        $html_url = (string) ($release['html_url'] ?? 'https://github.com/' . self::OWNER . '/' . self::REPO);
        $published_at = (string) ($release['published_at'] ?? '');

        return (object) [
            'name' => 'SEO Control Bridge for Divi Lite',
            'slug' => self::REPO,
            'version' => $version,
            'author' => '<a href="https://eecons.com">Electronic Enterprises, Inc.</a>',
            'author_profile' => 'https://eecons.com',
            'homepage' => 'https://github.com/' . self::OWNER . '/' . self::REPO,
            'requires' => (string) ($release['requires'] ?? '6.4'),
            'tested' => (string) ($release['tested'] ?? '7.0'),
            'requires_php' => (string) ($release['requires_php'] ?? '8.0'),
            'last_updated' => $published_at,
            'download_link' => $package,
            'banners' => $this->banners(),
            'icons' => $this->icons(),
            'short_description' => 'Free SEO workflow bridge for Divi and Rank Math.',
            'sections' => [
                'description' => $this->plugin_description_html(),
                'installation' => $this->plugin_installation_html(),
                'faq' => $this->plugin_faq_html(),
                'changelog' => wp_kses_post($this->markdown_to_basic_html($body ?: $this->local_changelog_markdown())),
            ],
            'external' => false,
            'donate_link' => 'https://eecons.com/product/seo-control-bridge-for-divi/',
        ];
    }

    public function rename_after_install(bool $response, array $hook_extra, array $result) {
        if (empty($hook_extra['plugin']) || $hook_extra['plugin'] !== $this->plugin_basename || empty($result['destination'])) {
            return $response;
        }

        global $wp_filesystem;
        if (!$wp_filesystem) {
            return $response;
        }

        $plugin_dir = trailingslashit(WP_PLUGIN_DIR) . self::REPO;
        $destination = untrailingslashit($result['destination']);

        if ($destination !== untrailingslashit($plugin_dir)) {
            if ($wp_filesystem->exists($plugin_dir)) {
                $wp_filesystem->delete($plugin_dir, true);
            }
            $wp_filesystem->move($destination, $plugin_dir, true);
            $result['destination'] = $plugin_dir;
        }

        return $response;
    }

    public function dashboard_update_notice(): void {
        if (!current_user_can('update_plugins')) {
            return;
        }

        $screen = get_current_screen();
        if (!$screen || !in_array($screen->id, ['dashboard', 'plugins'], true)) {
            return;
        }

        // Do not print SCBD Lite's custom notice on WordPress' update-core
        // screens. WordPress renders admin_notices before the plugin upgrade
        // routine completes, so an update notice can appear above a successful
        // upgrade result on the same request. The native Updates screen already
        // shows this plugin through the update_plugins transient.


        if (!empty($_GET['scbd-lite-update-check'])) {
            printf(
                '<div class="notice notice-info is-dismissible"><p><strong>%1$s</strong> %2$s</p></div>',
                esc_html__('SEO Bridge Lite checked GitHub for updates.', 'seo-control-bridge-for-divi-lite'),
                esc_html__('If a newer release exists, WordPress will show it after the native update check completes.', 'seo-control-bridge-for-divi-lite')
            );
        }

        $release = $this->get_latest_release();
        if (!$release || empty($release['version']) || !version_compare($release['version'], SCBD_LITE_VERSION, '>')) {
            return;
        }

        $updates_url = self_admin_url('update-core.php');
        $force_url = wp_nonce_url(
            self_admin_url('plugins.php?action=scbd_lite_force_update_check'),
            'scbd_lite_force_update_check'
        );
        printf(
            '<div class="notice notice-warning"><p><strong>%1$s</strong> %2$s <a href="%3$s">%4$s</a> | <a href="%5$s">%6$s</a></p></div>',
            esc_html__('SEO Bridge Lite update available.', 'seo-control-bridge-for-divi-lite'),
            esc_html(sprintf(__('Version %s is available from GitHub.', 'seo-control-bridge-for-divi-lite'), $release['version'])),
            esc_url($updates_url),
            esc_html__('Open WordPress Updates', 'seo-control-bridge-for-divi-lite'),
            esc_url($force_url),
            esc_html__('Check again now', 'seo-control-bridge-for-divi-lite')
        );
    }

    public function maybe_force_update_check(): void {
        if (!is_admin() || !current_user_can('update_plugins')) {
            return;
        }

        $action = isset($_GET['action']) ? sanitize_key((string) wp_unslash($_GET['action'])) : '';
        if ('scbd_lite_force_update_check' !== $action) {
            return;
        }

        check_admin_referer('scbd_lite_force_update_check');
        $this->clear_all_update_state();

        if (!function_exists('wp_update_plugins')) {
            require_once ABSPATH . 'wp-admin/includes/update.php';
        }
        wp_update_plugins();

        wp_safe_redirect(add_query_arg('scbd-lite-update-check', '1', self_admin_url('plugins.php')));
        exit;
    }

    public function clear_cache_after_update($upgrader, array $hook_extra): void {
        $is_this_plugin = false;

        if (!empty($hook_extra['plugins']) && in_array($this->plugin_basename, (array) $hook_extra['plugins'], true)) {
            $is_this_plugin = true;
        }
        if (!empty($hook_extra['plugin']) && $hook_extra['plugin'] === $this->plugin_basename) {
            $is_this_plugin = true;
        }

        if ($is_this_plugin) {
            $this->clear_all_update_state();
        }
    }

    public function clear_stale_update_state(): void {
        $updates = get_site_transient('update_plugins');
        if (!is_object($updates) || empty($updates->response[$this->plugin_basename])) {
            return;
        }

        $update = $updates->response[$this->plugin_basename];
        $new_version = is_object($update) && !empty($update->new_version) ? (string) $update->new_version : '';

        if ('' !== $new_version && !version_compare($new_version, SCBD_LITE_VERSION, '>')) {
            $updates = $this->mark_current($updates);
            set_site_transient('update_plugins', $updates);
        }
    }

    private function clear_all_update_state(): void {
        delete_site_transient(self::CACHE_KEY);
        delete_site_transient('update_plugins');
        wp_clean_plugins_cache(true);
    }


    private function update_object(array $release): object {
        return (object) [
            'id' => $this->plugin_basename,
            'slug' => self::REPO,
            'plugin' => $this->plugin_basename,
            'new_version' => $release['version'],
            'url' => $release['html_url'],
            'package' => $release['package'],
            'tested' => $release['tested'],
            'requires_php' => $release['requires_php'],
            'requires' => $release['requires'],
            'icons' => $this->icons(),
        ];
    }

    private function current_object(): object {
        return (object) [
            'id' => $this->plugin_basename,
            'slug' => self::REPO,
            'plugin' => $this->plugin_basename,
            'new_version' => SCBD_LITE_VERSION,
            'url' => 'https://github.com/' . self::OWNER . '/' . self::REPO,
            'package' => '',
            'tested' => '7.0',
            'requires_php' => '8.0',
            'requires' => '6.4',
            'icons' => $this->icons(),
        ];
    }

    private function mark_current(object $transient): object {
        if (isset($transient->response[$this->plugin_basename])) {
            unset($transient->response[$this->plugin_basename]);
        }

        if (!isset($transient->no_update) || !is_array($transient->no_update)) {
            $transient->no_update = [];
        }

        $transient->no_update[$this->plugin_basename] = $this->current_object();
        return $transient;
    }

    private function get_latest_release(bool $cached = true): ?array {
        if ($cached) {
            $cached_release = get_site_transient(self::CACHE_KEY);
            if (is_array($cached_release)) {
                return $cached_release;
            }
        }

        $url = 'https://api.github.com/repos/' . self::OWNER . '/' . self::REPO . '/releases/latest';
        $response = wp_remote_get($url, [
            'timeout' => 12,
            'headers' => [
                'Accept' => 'application/vnd.github+json',
                'User-Agent' => 'WordPress/' . get_bloginfo('version') . '; ' . home_url('/'),
            ],
        ]);

        if (is_wp_error($response) || 200 !== wp_remote_retrieve_response_code($response)) {
            return null;
        }

        $data = json_decode(wp_remote_retrieve_body($response), true);
        if (!is_array($data) || empty($data['tag_name'])) {
            return null;
        }

        $version = ltrim((string) $data['tag_name'], 'vV');
        $package = $this->asset_download_url($data, self::REPO . '.zip');
        if (!$package) {
            $package = 'https://github.com/' . self::OWNER . '/' . self::REPO . '/archive/refs/tags/' . rawurlencode((string) $data['tag_name']) . '.zip';
        }

        $release = [
            'version' => $version,
            'tag' => (string) $data['tag_name'],
            'html_url' => esc_url_raw((string) ($data['html_url'] ?? 'https://github.com/' . self::OWNER . '/' . self::REPO)),
            'package' => esc_url_raw($package),
            'body' => (string) ($data['body'] ?? ''),
            'published_at' => (string) ($data['published_at'] ?? ''),
            'requires' => '6.4',
            'requires_php' => '8.0',
            'tested' => '7.0',
        ];

        set_site_transient(self::CACHE_KEY, $release, self::CACHE_TTL);
        return $release;
    }

    private function asset_download_url(array $data, string $asset_name): string {
        if (empty($data['assets']) || !is_array($data['assets'])) {
            return '';
        }

        foreach ($data['assets'] as $asset) {
            if (!is_array($asset) || empty($asset['name'])) {
                continue;
            }
            if ((string) $asset['name'] === $asset_name && !empty($asset['browser_download_url'])) {
                return (string) $asset['browser_download_url'];
            }
        }

        return '';
    }

    private function icons(): array {
        return [
            'default' => SCBD_LITE_URL . 'assets/brand/scbd-lite-social.svg',
        ];
    }

    private function banners(): array {
        return [
            'low' => SCBD_LITE_URL . 'assets/brand/scbd-lite-social.svg',
            'high' => SCBD_LITE_URL . 'assets/brand/scbd-lite-social.svg',
        ];
    }

    private function plugin_description_html(): string {
        $html = '<p>' . esc_html__('SEO Control Bridge for Divi Lite gives WordPress site owners a lightweight way to edit Rank Math-compatible SEO metadata from a clean WordPress and Divi workflow.', 'seo-control-bridge-for-divi-lite') . '</p>';
        $html .= '<h2>' . esc_html__('Getting Started', 'seo-control-bridge-for-divi-lite') . '</h2>';
        $html .= '<ol>';
        $html .= '<li>' . esc_html__('Install and activate the plugin.', 'seo-control-bridge-for-divi-lite') . '</li>';
        $html .= '<li>' . esc_html__('Open Settings > SEO Bridge Lite for the Getting Started screen.', 'seo-control-bridge-for-divi-lite') . '</li>';
        $html .= '<li>' . esc_html__('Open a page or post and fill in the SEO Bridge Lite meta box.', 'seo-control-bridge-for-divi-lite') . '</li>';
        $html .= '<li>' . esc_html__('In the Divi Visual Builder, click the draggable SEO Bridge Lite button to edit metadata without leaving the builder.', 'seo-control-bridge-for-divi-lite') . '</li>';
        $html .= '</ol>';
        $html .= '<h2>' . esc_html__('Lite Includes', 'seo-control-bridge-for-divi-lite') . '</h2>';
        $html .= '<ul>';
        $html .= '<li>' . esc_html__('SEO title, meta description, focus keyword, and canonical URL fields.', 'seo-control-bridge-for-divi-lite') . '</li>';
        $html .= '<li>' . esc_html__('OpenGraph and X/Twitter metadata fields.', 'seo-control-bridge-for-divi-lite') . '</li>';
        $html .= '<li>' . esc_html__('Divi Visual Builder overlay with draggable, remembered launcher position.', 'seo-control-bridge-for-divi-lite') . '</li>';
        $html .= '<li>' . esc_html__('GitHub-powered updates through the native WordPress updater.', 'seo-control-bridge-for-divi-lite') . '</li>';
        $html .= '</ul>';
        return $html;
    }

    private function plugin_installation_html(): string {
        $html = '<ol>';
        $html .= '<li>' . esc_html__('Upload the installable plugin ZIP from Plugins > Add New > Upload Plugin, or copy the plugin folder to wp-content/plugins.', 'seo-control-bridge-for-divi-lite') . '</li>';
        $html .= '<li>' . esc_html__('Activate SEO Control Bridge for Divi Lite.', 'seo-control-bridge-for-divi-lite') . '</li>';
        $html .= '<li>' . esc_html__('Open the Getting Started link from the Plugins screen or Settings > SEO Bridge Lite.', 'seo-control-bridge-for-divi-lite') . '</li>';
        $html .= '<li>' . esc_html__('Open a page, post, or Divi Visual Builder page and begin editing SEO metadata.', 'seo-control-bridge-for-divi-lite') . '</li>';
        $html .= '</ol>';
        return $html;
    }

    private function plugin_faq_html(): string {
        $html = '<h3>' . esc_html__('Does this replace Rank Math?', 'seo-control-bridge-for-divi-lite') . '</h3>';
        $html .= '<p>' . esc_html__('No. It is a workflow bridge that saves Rank Math-compatible metadata. Rank Math should remain installed and active for complete SEO output.', 'seo-control-bridge-for-divi-lite') . '</p>';
        $html .= '<h3>' . esc_html__('Does Lite include Pro license activation?', 'seo-control-bridge-for-divi-lite') . '</h3>';
        $html .= '<p>' . esc_html__('No. Lite is public and does not contain EECONS License Manager code, private updater code, license endpoints, or protected download logic.', 'seo-control-bridge-for-divi-lite') . '</p>';
        $html .= '<h3>' . esc_html__('How do updates work?', 'seo-control-bridge-for-divi-lite') . '</h3>';
        $html .= '<p>' . esc_html__('The plugin checks the public GitHub Releases feed and exposes newer tagged releases to the native WordPress update system.', 'seo-control-bridge-for-divi-lite') . '</p>';
        return $html;
    }

    private function local_changelog_markdown(): string {
        return "## 1.0.7
- Stopped showing the custom SCBD Lite update notice on the native WordPress Updates screen to prevent stale notice output during successful upgrades.
- Kept update detection available through Dashboard and Installed Plugins while leaving Dashboard > Updates to WordPress core.

## 1.0.6
- Made WordPress update checks bypass the cached GitHub release response.
- Added a manual Check Again action for SCBD Lite update detection.
- Reduced display cache lifetime for GitHub release metadata.

## 1.0.5
- Improved GitHub updater transient cleanup after one-click updates.
- Prevented stale Installed Plugins update banners after successful updates.
- Changed the custom dashboard notice to open the native WordPress Updates screen.

## 1.0.4
- Added Getting Started links on the Plugins screen.
- Added View Details modal support for the Installed Plugins screen.
- Added first-run redirect to the Getting Started page after activation.
- Expanded plugin details content for GitHub-distributed installs.

## 1.0.3
- Added GitHub release update checks for public Lite builds.";
    }

    private function markdown_to_basic_html(string $markdown): string {
        $markdown = trim($markdown);
        if ('' === $markdown) {
            return '';
        }

        $lines = preg_split('/\r\n|\r|\n/', $markdown);
        $html = '';
        $in_list = false;

        foreach ($lines as $line) {
            $line = trim($line);
            if ('' === $line) {
                if ($in_list) {
                    $html .= '</ul>';
                    $in_list = false;
                }
                continue;
            }
            if (str_starts_with($line, '### ')) {
                if ($in_list) {
                    $html .= '</ul>';
                    $in_list = false;
                }
                $html .= '<h3>' . esc_html(substr($line, 4)) . '</h3>';
                continue;
            }
            if (str_starts_with($line, '## ')) {
                if ($in_list) {
                    $html .= '</ul>';
                    $in_list = false;
                }
                $html .= '<h2>' . esc_html(substr($line, 3)) . '</h2>';
                continue;
            }
            if (str_starts_with($line, '# ')) {
                if ($in_list) {
                    $html .= '</ul>';
                    $in_list = false;
                }
                $html .= '<h1>' . esc_html(substr($line, 2)) . '</h1>';
                continue;
            }
            if (str_starts_with($line, '- ') || str_starts_with($line, '* ')) {
                if (!$in_list) {
                    $html .= '<ul>';
                    $in_list = true;
                }
                $html .= '<li>' . esc_html(substr($line, 2)) . '</li>';
                continue;
            }
            if ($in_list) {
                $html .= '</ul>';
                $in_list = false;
            }
            $html .= '<p>' . esc_html($line) . '</p>';
        }

        if ($in_list) {
            $html .= '</ul>';
        }

        return $html;
    }
}
