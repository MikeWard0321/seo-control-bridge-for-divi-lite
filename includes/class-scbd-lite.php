<?php
namespace SCBD_Lite;

defined('ABSPATH') || exit;

final class Plugin {
    private static ?Plugin $instance = null;

    private array $fields = [
        'title' => [
            'label' => 'SEO Title',
            'rank_math_key' => 'rank_math_title',
            'type' => 'text',
            'placeholder' => '%title% %sep% %sitename%',
        ],
        'description' => [
            'label' => 'Meta Description',
            'rank_math_key' => 'rank_math_description',
            'type' => 'textarea',
            'placeholder' => 'Write a concise search description. Recommended: 160 characters or less.',
        ],
        'focus_keyword' => [
            'label' => 'Focus Keyword',
            'rank_math_key' => 'rank_math_focus_keyword',
            'type' => 'text',
            'placeholder' => 'Primary keyword or phrase',
        ],
        'canonical' => [
            'label' => 'Canonical URL',
            'rank_math_key' => 'rank_math_canonical_url',
            'type' => 'url',
            'placeholder' => 'https://example.com/canonical-page/',
        ],
        'facebook_title' => [
            'label' => 'OpenGraph Title',
            'rank_math_key' => 'rank_math_facebook_title',
            'type' => 'text',
            'placeholder' => 'Optional social share title',
        ],
        'facebook_description' => [
            'label' => 'OpenGraph Description',
            'rank_math_key' => 'rank_math_facebook_description',
            'type' => 'textarea',
            'placeholder' => 'Optional social share description',
        ],
        'facebook_image' => [
            'label' => 'OpenGraph Image URL',
            'rank_math_key' => 'rank_math_facebook_image',
            'type' => 'url',
            'placeholder' => 'https://example.com/image.jpg',
        ],
        'twitter_title' => [
            'label' => 'X/Twitter Title',
            'rank_math_key' => 'rank_math_twitter_title',
            'type' => 'text',
            'placeholder' => 'Optional X/Twitter share title',
        ],
        'twitter_description' => [
            'label' => 'X/Twitter Description',
            'rank_math_key' => 'rank_math_twitter_description',
            'type' => 'textarea',
            'placeholder' => 'Optional X/Twitter share description',
        ],
        'twitter_image' => [
            'label' => 'X/Twitter Image URL',
            'rank_math_key' => 'rank_math_twitter_image',
            'type' => 'url',
            'placeholder' => 'https://example.com/image.jpg',
        ],
    ];

    public static function instance(): Plugin {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function __construct() {
        add_action('init', [$this, 'load_textdomain']);
        add_action('rest_api_init', [$this, 'register_rest_routes']);
        add_action('admin_menu', [$this, 'admin_menu']);
        add_action('add_meta_boxes', [$this, 'add_meta_boxes']);
        add_action('save_post', [$this, 'save_post'], 10, 2);
        add_action('admin_enqueue_scripts', [$this, 'admin_assets']);
        add_action('wp_enqueue_scripts', [$this, 'frontend_assets']);
        add_action('admin_bar_menu', [$this, 'admin_bar_link'], 90);
        add_filter('manage_post_posts_columns', [$this, 'columns']);
        add_filter('manage_page_posts_columns', [$this, 'columns']);
        add_action('manage_post_posts_custom_column', [$this, 'column_content'], 10, 2);
        add_action('manage_page_posts_custom_column', [$this, 'column_content'], 10, 2);
    }

    public function load_textdomain(): void {
        load_plugin_textdomain('seo-control-bridge-for-divi-lite', false, dirname(plugin_basename(SCBD_LITE_FILE)) . '/languages');
    }

    public function register_rest_routes(): void {
        register_rest_route('scbd-lite/v1', '/post/(?P<id>\d+)/seo', [
            [
                'methods' => \WP_REST_Server::READABLE,
                'callback' => [$this, 'rest_get_seo'],
                'permission_callback' => [$this, 'rest_can_edit_post'],
                'args' => [
                    'id' => [
                        'required' => true,
                        'validate_callback' => static fn($value): bool => is_numeric($value) && (int) $value > 0,
                    ],
                ],
            ],
            [
                'methods' => \WP_REST_Server::EDITABLE,
                'callback' => [$this, 'rest_save_seo'],
                'permission_callback' => [$this, 'rest_can_edit_post'],
                'args' => [
                    'id' => [
                        'required' => true,
                        'validate_callback' => static fn($value): bool => is_numeric($value) && (int) $value > 0,
                    ],
                ],
            ],
        ]);
    }

    public function rest_can_edit_post(\WP_REST_Request $request): bool {
        $post_id = (int) $request['id'];
        return $post_id > 0 && current_user_can('edit_post', $post_id);
    }

    public function rest_get_seo(\WP_REST_Request $request): \WP_REST_Response {
        $post_id = (int) $request['id'];
        return new \WP_REST_Response([
            'postId' => $post_id,
            'fields' => $this->field_schema(),
            'values' => $this->get_seo_values($post_id),
        ]);
    }

    public function rest_save_seo(\WP_REST_Request $request): \WP_REST_Response {
        $post_id = (int) $request['id'];
        $posted = $request->get_param('values');
        if (!is_array($posted)) {
            $posted = [];
        }

        $values = $this->sanitize_seo_values($posted);
        $this->save_seo_values($post_id, $values);

        return new \WP_REST_Response([
            'postId' => $post_id,
            'saved' => true,
            'values' => $this->get_seo_values($post_id),
        ]);
    }

    public function admin_menu(): void {
        add_options_page(
            __('SEO Bridge Lite', 'seo-control-bridge-for-divi-lite'),
            __('SEO Bridge Lite', 'seo-control-bridge-for-divi-lite'),
            'manage_options',
            'scbd-lite',
            [$this, 'render_settings_page']
        );
    }

    public function render_settings_page(): void {
        $rank_math_active = $this->is_rank_math_active();
        ?>
        <div class="wrap scbd-lite-wrap">
            <h1><?php esc_html_e('SEO Control Bridge for Divi Lite', 'seo-control-bridge-for-divi-lite'); ?></h1>
            <div class="scbd-lite-card">
                <h2><?php esc_html_e('Status', 'seo-control-bridge-for-divi-lite'); ?></h2>
                <p><strong><?php esc_html_e('Rank Math detected:', 'seo-control-bridge-for-divi-lite'); ?></strong> <?php echo $rank_math_active ? esc_html__('Yes', 'seo-control-bridge-for-divi-lite') : esc_html__('No', 'seo-control-bridge-for-divi-lite'); ?></p>
                <p><?php esc_html_e('Lite adds fast SEO metadata editing for pages and posts. When Rank Math is active, values are saved directly to Rank Math-compatible meta keys.', 'seo-control-bridge-for-divi-lite'); ?></p>
            </div>
            <div class="scbd-lite-card">
                <h2><?php esc_html_e('Lite Feature Set', 'seo-control-bridge-for-divi-lite'); ?></h2>
                <ul>
                    <li><?php esc_html_e('SEO title, meta description, focus keyword, and canonical URL fields.', 'seo-control-bridge-for-divi-lite'); ?></li>
                    <li><?php esc_html_e('OpenGraph and X/Twitter title, description, and image URL fields.', 'seo-control-bridge-for-divi-lite'); ?></li>
                    <li><?php esc_html_e('Admin list SEO status columns.', 'seo-control-bridge-for-divi-lite'); ?></li>
                    <li><?php esc_html_e('Visual Builder overlay for editing the current page without leaving Divi.', 'seo-control-bridge-for-divi-lite'); ?></li>
                </ul>
            </div>
            <div class="scbd-lite-card scbd-lite-pro-card">
                <h2><?php esc_html_e('Need the Pro workflow?', 'seo-control-bridge-for-divi-lite'); ?></h2>
                <p><?php esc_html_e('SEO Control Bridge for Divi Pro adds bulk SEO tools, schema templates, social previews, imports/exports, white-labeling, and agency workflow features.', 'seo-control-bridge-for-divi-lite'); ?></p>
                <p><a class="button button-primary" href="https://eecons.com/product/seo-control-bridge-for-divi/" target="_blank" rel="noopener noreferrer"><?php esc_html_e('View Pro Version', 'seo-control-bridge-for-divi-lite'); ?></a></p>
            </div>
        </div>
        <?php
    }

    public function add_meta_boxes(): void {
        foreach (['post', 'page'] as $screen) {
            add_meta_box(
                'scbd_lite_seo_fields',
                __('SEO Bridge Lite', 'seo-control-bridge-for-divi-lite'),
                [$this, 'render_meta_box'],
                $screen,
                'normal',
                'high'
            );
        }
    }

    public function render_meta_box(\WP_Post $post): void {
        wp_nonce_field('scbd_lite_save_meta', 'scbd_lite_nonce');
        echo '<div class="scbd-lite-meta-box">';
        echo '<p class="description">' . esc_html__('These fields map to Rank Math metadata when Rank Math is active. They remain stored safely even when Rank Math is not active.', 'seo-control-bridge-for-divi-lite') . '</p>';
        foreach ($this->fields as $key => $field) {
            $value = $this->get_seo_value($post->ID, $key);
            printf('<p class="scbd-lite-field scbd-lite-field-%1$s"><label for="scbd_lite_%1$s"><strong>%2$s</strong></label>', esc_attr($key), esc_html($field['label']));
            if ('textarea' === $field['type']) {
                printf('<textarea id="scbd_lite_%1$s" name="scbd_lite[%1$s]" rows="3" placeholder="%3$s">%2$s</textarea>', esc_attr($key), esc_textarea((string) $value), esc_attr($field['placeholder']));
            } else {
                printf('<input id="scbd_lite_%1$s" name="scbd_lite[%1$s]" type="%4$s" value="%2$s" placeholder="%3$s" />', esc_attr($key), esc_attr((string) $value), esc_attr($field['placeholder']), esc_attr($field['type']));
            }
            echo '</p>';
        }
        echo '</div>';
    }

    public function save_post(int $post_id, \WP_Post $post): void {
        if (!isset($_POST['scbd_lite_nonce']) || !wp_verify_nonce(sanitize_text_field(wp_unslash($_POST['scbd_lite_nonce'])), 'scbd_lite_save_meta')) {
            return;
        }
        if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
            return;
        }
        if (!current_user_can('edit_post', $post_id)) {
            return;
        }
        if (!isset($_POST['scbd_lite']) || !is_array($_POST['scbd_lite'])) {
            return;
        }

        $posted = wp_unslash($_POST['scbd_lite']);
        $this->save_seo_values($post_id, is_array($posted) ? $posted : []);
    }

    public function admin_assets(string $hook): void {
        wp_enqueue_style('scbd-lite-admin', SCBD_LITE_URL . 'assets/css/admin.css', [], SCBD_LITE_VERSION);
        wp_enqueue_script('scbd-lite-admin', SCBD_LITE_URL . 'assets/js/admin.js', [], SCBD_LITE_VERSION, true);
    }

    public function frontend_assets(): void {
        if (!is_user_logged_in()) {
            return;
        }

        $post_id = get_queried_object_id();
        if (!$post_id || !current_user_can('edit_post', $post_id)) {
            return;
        }

        wp_enqueue_style('scbd-lite-builder', SCBD_LITE_URL . 'assets/css/builder.css', [], SCBD_LITE_VERSION);
        wp_enqueue_script('scbd-lite-builder', SCBD_LITE_URL . 'assets/js/builder.js', [], SCBD_LITE_VERSION, true);
        wp_localize_script('scbd-lite-builder', 'SCBDLiteBuilder', [
            'postId' => $post_id,
            'restUrl' => esc_url_raw(rest_url('scbd-lite/v1/post/' . $post_id . '/seo')),
            'nonce' => wp_create_nonce('wp_rest'),
            'adminUrl' => esc_url_raw(admin_url('options-general.php?page=scbd-lite')),
            'strings' => [
                'button' => __('SEO Bridge Lite', 'seo-control-bridge-for-divi-lite'),
                'title' => __('SEO Bridge Lite', 'seo-control-bridge-for-divi-lite'),
                'description' => __('Edit SEO metadata for this page without leaving the Divi Visual Builder.', 'seo-control-bridge-for-divi-lite'),
                'loading' => __('Loading SEO fields…', 'seo-control-bridge-for-divi-lite'),
                'save' => __('Save SEO Fields', 'seo-control-bridge-for-divi-lite'),
                'saving' => __('Saving…', 'seo-control-bridge-for-divi-lite'),
                'saved' => __('Saved.', 'seo-control-bridge-for-divi-lite'),
                'error' => __('Unable to load or save SEO fields. Please refresh and try again.', 'seo-control-bridge-for-divi-lite'),
                'close' => __('Close', 'seo-control-bridge-for-divi-lite'),
            ],
        ]);
    }

    public function admin_bar_link(\WP_Admin_Bar $bar): void {
        if (!is_admin_bar_showing() || !is_user_logged_in()) {
            return;
        }
        $object_id = get_queried_object_id();
        if ($object_id && current_user_can('edit_post', $object_id)) {
            $href = '#scbd-lite-open';
        } else {
            $href = admin_url('options-general.php?page=scbd-lite');
        }
        $bar->add_node([
            'id' => 'scbd-lite',
            'title' => __('SEO Bridge Lite', 'seo-control-bridge-for-divi-lite'),
            'href' => $href,
            'meta' => ['class' => 'scbd-lite-admin-bar'],
        ]);
    }

    public function columns(array $columns): array {
        $columns['scbd_lite_seo'] = __('SEO', 'seo-control-bridge-for-divi-lite');
        return $columns;
    }

    public function column_content(string $column, int $post_id): void {
        if ('scbd_lite_seo' !== $column) {
            return;
        }
        $title = get_post_meta($post_id, 'rank_math_title', true) ?: get_post_meta($post_id, $this->meta_key('title'), true);
        $description = get_post_meta($post_id, 'rank_math_description', true) ?: get_post_meta($post_id, $this->meta_key('description'), true);
        $keyword = get_post_meta($post_id, 'rank_math_focus_keyword', true) ?: get_post_meta($post_id, $this->meta_key('focus_keyword'), true);
        $score = 0;
        foreach ([$title, $description, $keyword] as $item) {
            if ('' !== trim((string) $item)) {
                $score++;
            }
        }
        printf('<span class="scbd-lite-pill scbd-lite-pill-%1$d">%2$d/3</span>', esc_attr((string) $score), esc_html((string) $score));
    }

    private function field_schema(): array {
        $schema = [];
        foreach ($this->fields as $key => $field) {
            $schema[] = [
                'key' => $key,
                'label' => $field['label'],
                'type' => $field['type'],
                'placeholder' => $field['placeholder'],
            ];
        }
        return $schema;
    }

    private function get_seo_values(int $post_id): array {
        $values = [];
        foreach (array_keys($this->fields) as $key) {
            $values[$key] = $this->get_seo_value($post_id, $key);
        }
        return $values;
    }

    private function get_seo_value(int $post_id, string $key): string {
        if (!isset($this->fields[$key])) {
            return '';
        }
        $value = get_post_meta($post_id, $this->meta_key($key), true);
        if ('' === $value) {
            $rank_math_value = get_post_meta($post_id, $this->fields[$key]['rank_math_key'], true);
            if ('' !== $rank_math_value) {
                $value = $rank_math_value;
            }
        }
        return is_string($value) ? $value : '';
    }

    private function save_seo_values(int $post_id, array $values): void {
        $values = $this->sanitize_seo_values($values);
        foreach ($values as $key => $value) {
            $field = $this->fields[$key];
            update_post_meta($post_id, $this->meta_key($key), $value);
            update_post_meta($post_id, $field['rank_math_key'], $value);
        }
    }

    private function sanitize_seo_values(array $values): array {
        $clean = [];
        foreach ($this->fields as $key => $field) {
            $raw = $values[$key] ?? '';
            $value = is_string($raw) ? $raw : '';
            $clean[$key] = ('url' === $field['type']) ? esc_url_raw($value) : sanitize_textarea_field($value);
        }
        return $clean;
    }

    private function meta_key(string $key): string {
        return '_scbd_lite_' . $key;
    }

    private function is_rank_math_active(): bool {
        return defined('RANK_MATH_VERSION') || class_exists('RankMath');
    }
}
