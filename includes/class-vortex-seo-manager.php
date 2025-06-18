<?php
/**
 * VORTEX SEO Manager
 * 
 * Handles dynamic SEO metadata injection and integration with the metadata API
 * 
 * @package VORTEX_ARTEC
 * @since 1.0.0
 */

if (!defined('ABSPATH')) {
    exit;
}

class Vortex_SEO_Manager {
    
    private $metadata_api_url;
    private $cache_duration = 3600; // 1 hour
    private $fallback_meta;
    
    public function __construct() {
        $this->metadata_api_url = get_option('vortex_seo_api_url', 'https://api.vortexartec.com/api/page-meta');
        
        $this->fallback_meta = array(
            'title' => 'VORTEX ARTEC | Where Art Awakens AI',
            'description' => 'Discover VORTEX ARTEC—the immersive art platform powered by AI, blockchain, and community.',
            'ogImage' => get_site_url() . '/assets/images/VORTEX_ROUND_BLACK.png',
            'keywords' => 'AI art, blockchain art, NFT marketplace, TOLA token',
            'canonical' => get_site_url()
        );
        
        $this->init_hooks();
    }
    
    private function init_hooks() {
        // Remove default WordPress SEO
        remove_action('wp_head', '_wp_render_title_tag', 1);
        
        // Add our custom SEO
        add_action('wp_head', array($this, 'inject_seo_tags'), 1);
        add_action('wp_head', array($this, 'inject_structured_data'), 5);
        
        // Admin settings
        add_action('admin_menu', array($this, 'add_admin_menu'));
        add_action('admin_init', array($this, 'register_settings'));
        
        // AJAX endpoints
        add_action('wp_ajax_vortex_update_seo_meta', array($this, 'ajax_update_meta'));
        add_action('wp_ajax_vortex_test_seo_api', array($this, 'ajax_test_api'));
        
        // REST API endpoints
        add_action('rest_api_init', array($this, 'register_rest_routes'));
    }
    
    public function inject_seo_tags() {
        $current_path = $this->get_current_path();
        $meta = $this->get_page_metadata($current_path);
        
        // Title tag
        echo '<title>' . esc_html($meta['title']) . '</title>' . "\n";
        
        // Meta description
        echo '<meta name="description" content="' . esc_attr($meta['description']) . '">' . "\n";
        
        // Keywords
        if (!empty($meta['keywords'])) {
            echo '<meta name="keywords" content="' . esc_attr($meta['keywords']) . '">' . "\n";
        }
        
        // Canonical URL
        echo '<link rel="canonical" href="' . esc_url($meta['canonical']) . '">' . "\n";
        
        // Open Graph tags
        echo '<meta property="og:title" content="' . esc_attr($meta['title']) . '">' . "\n";
        echo '<meta property="og:description" content="' . esc_attr($meta['description']) . '">' . "\n";
        echo '<meta property="og:image" content="' . esc_url($meta['ogImage']) . '">' . "\n";
        echo '<meta property="og:url" content="' . esc_url($meta['canonical']) . '">' . "\n";
        echo '<meta property="og:type" content="website">' . "\n";
        echo '<meta property="og:site_name" content="VORTEX ARTEC">' . "\n";
        
        // Twitter Card tags
        echo '<meta name="twitter:card" content="summary_large_image">' . "\n";
        echo '<meta name="twitter:title" content="' . esc_attr($meta['title']) . '">' . "\n";
        echo '<meta name="twitter:description" content="' . esc_attr($meta['description']) . '">' . "\n";
        echo '<meta name="twitter:image" content="' . esc_url($meta['ogImage']) . '">' . "\n";
        
        // Additional SEO tags
        echo '<meta name="robots" content="index, follow">' . "\n";
        echo '<meta name="viewport" content="width=device-width, initial-scale=1.0">' . "\n";
        echo '<meta charset="UTF-8">' . "\n";
    }
    
    public function inject_structured_data() {
        $current_path = $this->get_current_path();
        $meta = $this->get_page_metadata($current_path);
        
        $structured_data = array(
            '@context' => 'https://schema.org',
            '@type' => 'WebSite',
            'name' => 'VORTEX ARTEC',
            'description' => $meta['description'],
            'url' => $meta['canonical'],
            'image' => $meta['ogImage'],
            'sameAs' => array(
                'https://twitter.com/vortexartec',
                'https://linkedin.com/company/vortexartec'
            ),
            'potentialAction' => array(
                '@type' => 'SearchAction',
                'target' => get_site_url() . '/search?q={search_term_string}',
                'query-input' => 'required name=search_term_string'
            )
        );
        
        // Add organization data for homepage
        if ($current_path === '/') {
            $structured_data['@type'] = array('WebSite', 'Organization');
            $structured_data['founder'] = array(
                '@type' => 'Person',
                'name' => 'Marianne NEMS'
            );
            $structured_data['foundingDate'] = '2024';
            $structured_data['industry'] = 'Digital Art and AI Technology';
        }
        
        echo '<script type="application/ld+json">' . "\n";
        echo wp_json_encode($structured_data, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
        echo "\n" . '</script>' . "\n";
    }
    
    public function get_page_metadata($path) {
        $cache_key = 'vortex_seo_meta_' . md5($path);
        $cached_meta = get_transient($cache_key);
        
        if ($cached_meta !== false) {
            return $cached_meta;
        }
        
        $meta = $this->fetch_metadata_from_api($path);
        
        if ($meta) {
            set_transient($cache_key, $meta, $this->cache_duration);
            return $meta;
        }
        
        // Fallback to default meta with path-specific adjustments
        $fallback = $this->fallback_meta;
        $fallback['canonical'] = get_site_url() . $path;
        
        return $fallback;
    }
    
    private function fetch_metadata_from_api($path) {
        $url = add_query_arg('path', $path, $this->metadata_api_url);
        
        $response = wp_remote_get($url, array(
            'timeout' => 5,
            'headers' => array(
                'Accept' => 'application/json'
            )
        ));
        
        if (is_wp_error($response)) {
            error_log('VORTEX SEO: API request failed - ' . $response->get_error_message());
            return false;
        }
        
        $body = wp_remote_retrieve_body($response);
        $data = json_decode($body, true);
        
        if (!$data || !isset($data['success']) || !$data['success']) {
            error_log('VORTEX SEO: Invalid API response - ' . $body);
            return false;
        }
        
        return $data['data'];
    }
    
    private function get_current_path() {
        $path = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
        return rtrim($path, '/') ?: '/';
    }
    
    public function add_admin_menu() {
        add_options_page(
            'VORTEX SEO Settings',
            'VORTEX SEO',
            'manage_options',
            'vortex-seo',
            array($this, 'admin_page')
        );
    }
    
    public function register_settings() {
        register_setting('vortex_seo_settings', 'vortex_seo_api_url');
        register_setting('vortex_seo_settings', 'vortex_seo_cache_duration');
    }
    
    public function admin_page() {
        ?>
        <div class="wrap">
            <h1>VORTEX SEO Settings</h1>
            <form method="post" action="options.php">
                <?php settings_fields('vortex_seo_settings'); ?>
                <?php do_settings_sections('vortex_seo_settings'); ?>
                
                <table class="form-table">
                    <tr>
                        <th scope="row">Metadata API URL</th>
                        <td>
                            <input type="url" name="vortex_seo_api_url" value="<?php echo esc_attr(get_option('vortex_seo_api_url', $this->metadata_api_url)); ?>" class="regular-text" />
                            <p class="description">URL of the metadata API endpoint</p>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">Cache Duration (seconds)</th>
                        <td>
                            <input type="number" name="vortex_seo_cache_duration" value="<?php echo esc_attr(get_option('vortex_seo_cache_duration', $this->cache_duration)); ?>" class="small-text" />
                            <p class="description">How long to cache metadata responses</p>
                        </td>
                    </tr>
                </table>
                
                <?php submit_button(); ?>
            </form>
            
            <h2>Test API Connection</h2>
            <button type="button" id="test-api" class="button">Test API</button>
            <div id="api-test-result"></div>
            
            <h2>Clear Cache</h2>
            <button type="button" id="clear-cache" class="button">Clear SEO Cache</button>
            <div id="cache-clear-result"></div>
        </div>
        
        <script>
        jQuery(document).ready(function($) {
            $('#test-api').click(function() {
                $.ajax({
                    url: ajaxurl,
                    type: 'POST',
                    data: {
                        action: 'vortex_test_seo_api',
                        nonce: '<?php echo wp_create_nonce('vortex_seo_nonce'); ?>'
                    },
                    success: function(response) {
                        $('#api-test-result').html('<div class="notice notice-success"><p>' + response.data + '</p></div>');
                    },
                    error: function() {
                        $('#api-test-result').html('<div class="notice notice-error"><p>API test failed</p></div>');
                    }
                });
            });
            
            $('#clear-cache').click(function() {
                $.ajax({
                    url: ajaxurl,
                    type: 'POST',
                    data: {
                        action: 'vortex_clear_seo_cache',
                        nonce: '<?php echo wp_create_nonce('vortex_seo_nonce'); ?>'
                    },
                    success: function(response) {
                        $('#cache-clear-result').html('<div class="notice notice-success"><p>Cache cleared successfully</p></div>');
                    }
                });
            });
        });
        </script>
        <?php
    }
    
    public function ajax_test_api() {
        check_ajax_referer('vortex_seo_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_die('Unauthorized');
        }
        
        $test_path = '/';
        $meta = $this->fetch_metadata_from_api($test_path);
        
        if ($meta) {
            wp_send_json_success('API connection successful. Retrieved metadata for homepage.');
        } else {
            wp_send_json_error('API connection failed. Check the URL and try again.');
        }
    }
    
    public function register_rest_routes() {
        register_rest_route('vortex/v1', '/seo/meta/(?P<path>.*)', array(
            'methods' => 'GET',
            'callback' => array($this, 'rest_get_meta'),
            'permission_callback' => '__return_true'
        ));
        
        register_rest_route('vortex/v1', '/seo/cache/clear', array(
            'methods' => 'POST',
            'callback' => array($this, 'rest_clear_cache'),
            'permission_callback' => array($this, 'check_admin_permission')
        ));
    }
    
    public function rest_get_meta($request) {
        $path = '/' . ltrim($request->get_param('path'), '/');
        $meta = $this->get_page_metadata($path);
        
        return rest_ensure_response(array(
            'success' => true,
            'path' => $path,
            'meta' => $meta
        ));
    }
    
    public function rest_clear_cache($request) {
        global $wpdb;
        
        $wpdb->query("DELETE FROM {$wpdb->options} WHERE option_name LIKE '_transient_vortex_seo_meta_%'");
        $wpdb->query("DELETE FROM {$wpdb->options} WHERE option_name LIKE '_transient_timeout_vortex_seo_meta_%'");
        
        return rest_ensure_response(array(
            'success' => true,
            'message' => 'SEO cache cleared successfully'
        ));
    }
    
    public function check_admin_permission() {
        return current_user_can('manage_options');
    }
}

// Initialize the SEO Manager
new Vortex_SEO_Manager(); 