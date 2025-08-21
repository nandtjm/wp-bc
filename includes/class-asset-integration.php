<?php
/**
 * WordPress Asset Manager for React Build Integration
 * Generated automatically by build-integration.js
 */

if (!defined('ABSPATH')) {
    exit;
}

class Bracelet_Customizer_Assets {
    
    private static $instance = null;
    
    public static function get_instance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    private function __construct() {
        add_action('wp_enqueue_scripts', [$this, 'enqueue_customizer_assets']);
        add_action('admin_enqueue_scripts', [$this, 'enqueue_customizer_assets']);
    }
    
    /**
     * Enqueue React app assets for the customizer
     */
    public function enqueue_customizer_assets() {
        $plugin_url = defined('BRACELET_CUSTOMIZER_PLUGIN_URL') ? BRACELET_CUSTOMIZER_PLUGIN_URL : plugin_dir_url(dirname(dirname(__FILE__)));
        $plugin_version = defined('BRACELET_CUSTOMIZER_VERSION') ? BRACELET_CUSTOMIZER_VERSION : '1.0.0';
        
        // Enqueue CSS
        wp_enqueue_style(
            'bracelet-customizer-css',
            $plugin_url . 'assets/css/bracelet-customizer.css',
            [],
            $plugin_version
        );
        
        // Enqueue JS
        wp_enqueue_script(
            'bracelet-customizer-js',
            $plugin_url . 'assets/js/bracelet-customizer.js',
            ['wp-element'],
            $plugin_version,
            true
        );
        
        // Get WooCommerce currency information
        $currency_code = class_exists('WooCommerce') ? get_woocommerce_currency() : 'USD';
        $currency_symbol = class_exists('WooCommerce') ? html_entity_decode(get_woocommerce_currency_symbol(), ENT_QUOTES, 'UTF-8') : '$';
        $currency_position = class_exists('WooCommerce') ? get_option('woocommerce_currency_pos', 'left') : 'left';
        $thousand_separator = class_exists('WooCommerce') ? get_option('woocommerce_price_thousand_sep', ',') : ',';
        $decimal_separator = class_exists('WooCommerce') ? get_option('woocommerce_price_decimal_sep', '.') : '.';
        $price_decimals = class_exists('WooCommerce') ? get_option('woocommerce_price_num_decimals', 2) : 2;
        
        // Add WordPress integration data
        wp_localize_script('bracelet-customizer-js', 'braceletCustomizerData', [
            'ajaxUrl' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('bracelet_customizer_nonce'),
            'restUrl' => rest_url('bracelet-customizer/v1/'),
            'restNonce' => wp_create_nonce('wp_rest'),
            'pluginUrl' => $plugin_url,
            'imagesUrl' => $plugin_url . 'assets/images/',
            'isUserLoggedIn' => is_user_logged_in(),
            'currentUser' => wp_get_current_user()->ID,
            'woocommerceActive' => class_exists('WooCommerce'),
            'cartUrl' => function_exists('wc_get_cart_url') ? wc_get_cart_url() : '',
            'checkoutUrl' => function_exists('wc_get_checkout_url') ? wc_get_checkout_url() : '',
            'currency' => [
                'code' => $currency_code,
                'symbol' => $currency_symbol,
                'position' => $currency_position,
                'thousandSeparator' => $thousand_separator,
                'decimalSeparator' => $decimal_separator,
                'decimals' => $price_decimals
            ],
            'letterColors' => self::get_letter_colors_for_frontend()
        ]);
        
        // Ensure React and ReactDOM are available
        wp_enqueue_script('react', 'https://unpkg.com/react@18/umd/react.production.min.js', [], '18.0.0');
        wp_enqueue_script('react-dom', 'https://unpkg.com/react-dom@18/umd/react-dom.production.min.js', ['react'], '18.0.0');
    }
    
    /**
     * Get letter colors for frontend from settings
     */
    private static function get_letter_colors_for_frontend() {
        $settings = get_option('bracelet_customizer_settings', []);
        $letter_colors = $settings['letter_colors'] ?? [];
        
        $frontend_colors = [];
        foreach ($letter_colors as $color_id => $color_data) {
            if (!empty($color_data['enabled'])) {
                $frontend_colors[] = [
                    'id' => $color_id,
                    'name' => $color_data['name'] ?? ucfirst($color_id),
                    'price' => (float) ($color_data['price'] ?? 0),
                    'color' => $color_data['color'] ?? '#ffffff'
                ];
            }
        }
        
        // Fallback to defaults if no settings found
        if (empty($frontend_colors)) {
            return [
                ['id' => 'white', 'name' => 'White', 'price' => 0, 'color' => '#ffffff'],
                ['id' => 'pink', 'name' => 'Pink', 'price' => 0, 'color' => '#ffc0cb'],
                ['id' => 'black', 'name' => 'Black', 'price' => 0, 'color' => '#000000'],
                ['id' => 'gold', 'name' => 'Gold', 'price' => 15, 'color' => '#ffd700']
            ];
        }
        
        return $frontend_colors;
    }
    
    /**
     * Initialize the customizer in the DOM
     */
    public static function render_customizer_container() {
        echo '<div id="bracelet-customizer-root"></div>';
        echo '<script>
            document.addEventListener("DOMContentLoaded", function() {
                if (window.React && window.ReactDOM && window.BraceletCustomizer) {
                    const container = document.getElementById("bracelet-customizer-root");
                    if (container) {
                        const root = ReactDOM.createRoot(container);
                        root.render(React.createElement(window.BraceletCustomizer.App));
                    }
                }
            });
        </script>';
    }
}

// Initialize the asset manager
Bracelet_Customizer_Assets::get_instance();
