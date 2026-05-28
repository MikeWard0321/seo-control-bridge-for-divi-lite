<?php
namespace SCBD_Lite;

defined('ABSPATH') || exit;

final class GitHub_Updater {
    private const OWNER = 'MikeWard0321';
    private const REPO = 'seo-control-bridge-for-divi-lite';
    private const CACHE_KEY = 'scbd_lite_github_release';
    private const CACHE_TTL = 6 * HOUR_IN_SECONDS;

    private string $plugin_file;
    private string $plugin_basename;

    public function __construct(string $plugin_file) {
        $this->plugin_file = $plugin_file;
        $this->plugin_basename = plugin_basename($plugin_file);
    }

    public function hooks(): void {
        add_filter('pre_set_site_transient_update_plugins', [$this, 'inject_update']);
        add_filter('plugins_api', [$this, 'plugin_info'], 20, 3);
        add_filter('upgrader_post_install', [$this, 'rename_after_install'], 10, 3);
        add_action('admin_notices', [$this, 'dashboard_update_notice']);
        add_action('upgrader_process_complete', [$this, 'clear_cache_after_update'], 10, 2);
    }

    public function inject_update(object $transient): object {
        if (empty($transient->checked) || !isset($transient->checked[$this->plugin_basename])) {
            return $transient;
        }

        $release = $this->get_latest_release();
        if (!$release || empty($release['version'])) {
            return $transient;
        }

        if (!version_compare($release['version'], SCBD_LITE_VERSION, '>')) {
            unset($transient->response[$this->plugin_basename]);
            return $transient;
        }

        $transient->response[$this->plugin_basename] = (object) [
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

        return $transient;
    }

    public function plugin_info($result, string $action, object $args) {
        if ('plugin_information' !== $action || empty($args->slug) || self::REPO !== $args->slug) {
            return $result;
        }

        $release = $this->get_latest_release(false);
        if (!$release) {
            return $result;
        }

        return (object) [
            'name' => 'SEO Control Bridge for Divi Lite',
            'slug' => self::REPO,
            'version' => $release['version'],
            'author' => '<a href="https://eecons.com">Electronic Enterprises, Inc.</a>',
            'author_profile' => 'https://eecons.com',
            'homepage' => 'https://github.com/' . self::OWNER . '/' . self::REPO,
            'requires' => $release['requires'],
            'tested' => $release['tested'],
            'requires_php' => $release['requires_php'],
            'last_updated' => $release['published_at'],
            'download_link' => $release['package'],
            'banners' => $this->banners(),
            'icons' => $this->icons(),
            'sections' => [
                'description' => wp_kses_post($this->markdown_to_basic_html($release['body'] ?: 'Public Lite release for SEO Control Bridge for Divi.')),
                'changelog' => wp_kses_post($this->markdown_to_basic_html($release['body'] ?: 'See the GitHub release notes for details.')),
            ],
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
        if (!$screen || !in_array($screen->id, ['dashboard', 'plugins', 'update-core'], true)) {
            return;
        }

        $release = $this->get_latest_release();
        if (!$release || empty($release['version']) || !version_compare($release['version'], SCBD_LITE_VERSION, '>')) {
            return;
        }

        $update_url = wp_nonce_url(self_admin_url('update.php?action=upgrade-plugin&plugin=' . rawurlencode($this->plugin_basename)), 'upgrade-plugin_' . $this->plugin_basename);
        printf(
            '<div class="notice notice-warning"><p><strong>%1$s</strong> %2$s <a href="%3$s">%4$s</a></p></div>',
            esc_html__('SEO Bridge Lite update available.', 'seo-control-bridge-for-divi-lite'),
            esc_html(sprintf(__('Version %s is available from GitHub.', 'seo-control-bridge-for-divi-lite'), $release['version'])),
            esc_url($update_url),
            esc_html__('Update now', 'seo-control-bridge-for-divi-lite')
        );
    }

    public function clear_cache_after_update($upgrader, array $hook_extra): void {
        if (!empty($hook_extra['plugins']) && in_array($this->plugin_basename, (array) $hook_extra['plugins'], true)) {
            delete_site_transient(self::CACHE_KEY);
        }
        if (!empty($hook_extra['plugin']) && $hook_extra['plugin'] === $this->plugin_basename) {
            delete_site_transient(self::CACHE_KEY);
        }
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
