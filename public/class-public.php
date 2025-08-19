<?php
/**
 * Public Class
 *
 * @package Bracelet_Customizer
 * @since 1.0.0
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Handle public-facing functionality
 */
class Bracelet_Customizer_Public {
    
    /**
     * Initialize public functionality
     */
    public function __construct() {
        $this->init_hooks();
    }
    
    /**
     * Initialize WordPress hooks
     */
    private function init_hooks() {
        add_action('wp_enqueue_scripts', [$this, 'enqueue_public_assets']);
        add_action('woocommerce_single_product_summary', [$this, 'add_customize_button'], 35);
        add_action('wp_head', [$this, 'add_redirect_script']);
    }
    
    /**
     * Enqueue public assets
     */
    public function enqueue_public_assets() {
        // Public assets are handled by the main plugin class
    }
    
    /**
     * Add customize button to product pages
     */
    public function add_customize_button() {
        global $product;
        
        if (!$product || !$this->is_customizable_product($product)) {
            return;
        }
        
        $settings = get_option('bracelet_customizer_settings');
        $customizer_page_id = $settings['ui_settings']['customizer_page_id'] ?? 0;
        
        if (!$customizer_page_id) {
            return;
        }
        
        $customizer_url = get_permalink($customizer_page_id);
        $button_text = $settings['button_labels']['customize'] ?? __('Customize This Bracelet', 'bracelet-customizer');
        
        if ($customizer_url) {
            echo '<div class="bracelet-customize-section" style="margin: 20px 0;">';
            echo '<a href="' . esc_url($customizer_url . '?product_id=' . $product->get_id()) . '" class="button bracelet-customize-btn" style="display: block; text-align: center; padding: 15px 30px; font-size: 16px; font-weight: bold; text-decoration: none;">';
            echo esc_html($button_text);
            echo '</a>';
            echo '</div>';
        }
    }
    
    /**
     * Add redirect script for handling product context
     */
    public function add_redirect_script() {
        ?>
        <script type="text/javascript">
            // Store referring product page URL for customizer close button
            if (document.location.href.includes('product_id=')) {
                const urlParams = new URLSearchParams(window.location.search);
                const productId = urlParams.get('product_id');
                if (productId) {
                    // Store the referring product URL in session storage
                    const referrer = document.referrer;
                    if (referrer) {
                        sessionStorage.setItem('bracelet_customizer_referrer', referrer);
                    }
                }
            }
            
            // Handle close button on customizer page
            window.closeBraceletCustomizer = function() {
                const referrer = sessionStorage.getItem('bracelet_customizer_referrer');
                if (referrer) {
                    window.location.href = referrer;
                } else {
                    window.history.back();
                }
            };
        </script>
        <?php
    }
    
    /**
     * Check if product is customizable (has bracelet product type)
     */
    private function is_customizable_product($product) {
        if (!$product) {
            return false;
        }
        
        // Check if product has standard_bracelet product type meta
        $product_type = get_post_meta($product->get_id(), '_product_type', true);
        return $product_type === 'standard_bracelet';
    }
    
    /**
     * Check if modal should be shown
     */
    private function should_show_modal() {
        // Check if we're on a page that should show the customizer modal
        return is_product() || is_cart() || is_checkout();
    }
}