<?php
/**
 * Plugin Name: Document Library
 * Description: Temporary access system for documents
 * Version: 1.0
 * Requires PHP: 8.2
 */

declare(strict_types=1);

namespace DocAccessManager;

if (!defined('ABSPATH')) exit;

final class DocumentAccessPlugin {
    private const CPT_ID = 'shared_document';
    private const SEED_META_KEY = '_doc_access_seed';
    private const URL_META_KEY = '_doc_access_last_url';

    public function __construct() {
        add_action('init', [$this, 'register_document_cpt']);
        add_action('add_meta_boxes', [$this, 'init_meta_box']);
        add_action('wp_ajax_generate_doc_token', [$this, 'ajax_generate_token']);
        add_action('template_redirect', [$this, 'handle_document_request']);
        add_action('admin_enqueue_scripts', [$this, 'enqueue_admin_assets']);
        add_action('wp_enqueue_scripts', [$this, 'enqueue_public_assets']);
    }

    public function register_document_cpt(): void {
        register_post_type(self::CPT_ID, [
            'labels' => ['name' => 'Documents', 'singular_name' => 'Document'],
            'public' => false,
            'show_ui' => true,
            'show_in_menu' => true,
            'supports' => ['title', 'editor'],
            'menu_icon' => 'dashicons-pdf',
            'publicly_queryable' => false,
            'has_archive' => false,
        ]);
    }

    public function init_meta_box(): void {
        add_meta_box('doc_manager', 'Access Management', [$this, 'render_meta_box'], self::CPT_ID, 'side');
    }

    public function render_meta_box(\WP_Post $post): void {
        $last_url = get_post_meta($post->ID, self::URL_META_KEY, true);
        $is_published = $post->post_status === 'publish';

        wp_nonce_field('doc_access_action', 'doc_access_nonce');
        ?>
        <div class="doc-access-wrapper">
            <div class="doc-link-container">
                <input type="text" id="doc-link-display" class="widefat" readonly
                       value="<?php echo esc_url((string)$last_url); ?>"
                       placeholder="Link will appear here...">
                <button type="button" id="js-copy-link" class="button" <?php echo $last_url ? '' : 'disabled'; ?>>
                    Copy
                </button>
            </div>
            <div class="doc-actions-wrapper">
                <button type="button" id="js-generate-link" class="button button-primary"
                        data-post-id="<?php echo $post->ID; ?>">
                    Generate Access Link
                </button>
                <span class="spinner"></span>
            </div>
            <?php if (!$is_published): ?>
                <p class="description">
                    Please publish the document to enable link generation
                </p>
            <?php endif; ?>
        </div>
        <?php
    }

    public function enqueue_admin_assets(string $hook): void {
        if (!in_array($hook, ['post.php', 'post-new.php']) || get_post_type() !== self::CPT_ID) return;

        wp_enqueue_style(
            'doc-access-admin-css',
            plugin_dir_url(__FILE__) . 'assets/admin-styles.css',
            [],
            '1.0.0'
        );

        wp_enqueue_script(
            'doc-access-admin-js',
            plugin_dir_url(__FILE__) . 'assets/admin-scripts.js',
            ['jquery'],
            '1.0.0',
            true
        );
    }

    public function enqueue_public_assets(): void {
        if (isset($_GET['view_doc'], $_GET['token'])) {
            wp_enqueue_style(
                'doc-access-public-css',
                plugin_dir_url(__FILE__) . 'assets/public-styles.css',
                [],
                '1.0.0'
            );
        }
    }

    public function ajax_generate_token(): void {
        check_ajax_referer('doc_access_action', '_ajax_nonce');

        $post_id = absint($_POST['post_id'] ?? 0);
        $post    = get_post($post_id);

        if (!$post_id || !current_user_can('edit_post', $post_id)) {
            wp_send_json_error('Access denied');
        }

        if ($post->post_status !== 'publish') {
            wp_send_json_error('Link generation is only allowed for published documents.');
        }

        $seed = wp_generate_password(12, false);
        $expires = time() + HOUR_IN_SECONDS;

        $token = $this->create_hash($post_id, $expires, $seed);

        $link = add_query_arg([
            'view_doc' => $post_id,
            'token'    => $token,
            'expires'  => $expires
        ], home_url('/'));

        update_post_meta($post_id, self::SEED_META_KEY, $seed);
        update_post_meta($post_id, self::URL_META_KEY, $link);

        wp_send_json_success(['url' => $link]);
    }

    public function handle_document_request(): void {
        if (!isset($_GET['view_doc'], $_GET['token'], $_GET['expires'])) return;

        $post_id = absint($_GET['view_doc']);
        $expires = absint($_GET['expires']);
        $token   = sanitize_text_field($_GET['token']);

        if (time() > $expires) {
            wp_die('Link has expired.', '403 Forbidden', ['response' => 403]);
        }

        $seed = get_post_meta($post_id, self::SEED_META_KEY, true);
        if (!$seed) {
            wp_die('Access revoked.', '403 Forbidden', ['response' => 403]);
        }

        $valid_token = $this->create_hash($post_id, $expires, (string)$seed);

        if (!hash_equals($valid_token, $token)) {
            wp_die('Invalid access token.', '403 Forbidden', ['response' => 403]);
        }

        $post = get_post($post_id);
        if ($post && $post->post_type === self::CPT_ID && $post->post_status === 'publish') {
            include plugin_dir_path(__FILE__) . 'templates/document-view.php';
            exit;
        }
        else {
            wp_die('Document not found or unavailable.', '404 Not Found', ['response' => 404]);
        }
    }

    private function create_hash(int $id, int $exp, string $seed): string {
        return hash_hmac('sha256', "{$id}|{$exp}|{$seed}", wp_salt());
    }
}

new DocumentAccessPlugin();