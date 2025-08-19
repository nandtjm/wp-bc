<?php
/**
 * REST API Class
 *
 * @package Bracelet_Customizer
 * @since 1.0.0
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Handle REST API endpoints for the React app
 */
class Bracelet_Customizer_Rest_API {
    
    /**
     * API namespace
     */
    const NAMESPACE = 'bracelet-customizer/v1';
    
    /**
     * Initialize REST API
     */
    public function __construct() {
        $this->init_hooks();
    }
    
    /**
     * Initialize WordPress hooks
     */
    private function init_hooks() {
        add_action('rest_api_init', [$this, 'register_routes']);
    }
    
    /**
     * Register REST API routes
     */
    public function register_routes() {
        // Get bracelets
        register_rest_route(self::NAMESPACE, '/bracelets', [
            'methods' => 'GET',
            'callback' => [$this, 'get_bracelets'],
            'permission_callback' => '__return_true'
        ]);
        
        // Get charms
        register_rest_route(self::NAMESPACE, '/charms', [
            'methods' => 'GET',
            'callback' => [$this, 'get_charms'],
            'permission_callback' => '__return_true'
        ]);
        
        // Get settings
        register_rest_route(self::NAMESPACE, '/settings', [
            'methods' => 'GET',
            'callback' => [$this, 'get_settings'],
            'permission_callback' => '__return_true'
        ]);
        
        // Save customization
        register_rest_route(self::NAMESPACE, '/customization', [
            'methods' => 'POST',
            'callback' => [$this, 'save_customization'],
            'permission_callback' => [$this, 'check_permission']
        ]);
    }
    
    /**
     * Get bracelets from WooCommerce Standard Bracelet products
     */
    public function get_bracelets($request) {
        try {
            // Get category filter from request
            $category = $request->get_param('category');
            
            if (!class_exists('WooCommerce')) {
                // Fallback to hardcoded data if WooCommerce not available
                $bracelets = Bracelet_Customizer_Product_Types::get_hardcoded_bracelet_products();
                return rest_ensure_response([
                    'success' => true,
                    'data' => $bracelets,
                    'source' => 'fallback'
                ]);
            }
            
            // Query WooCommerce products with Standard Bracelet type
            $args = [
                'post_type' => 'product',
                'post_status' => 'publish',
                'posts_per_page' => -1,
                'tax_query' => [
                    [
                        'taxonomy' => 'product_type',
                        'field' => 'slug',
                        'terms' => 'standard_bracelet'
                    ]
                ]
            ];
            
            
            $products = get_posts($args);
            $bracelets = [];
            
            // If no products found, and we have less than expected, use fallback for development/testing
            if (empty($products)) {
                error_log('No standard bracelet products found in WooCommerce. Using fallback data.');
                $bracelets = Bracelet_Customizer_Product_Types::get_hardcoded_bracelet_products();
                
                return rest_ensure_response([
                    'success' => true,
                    'data' => $bracelets,
                    'source' => 'fallback_no_products',
                    'message' => 'No standard bracelet products found in WooCommerce database'
                ]);
            }
            
            foreach ($products as $product_post) {
                $product = wc_get_product($product_post->ID);
                if (!$product) continue;
                
                // Get product meta data
                $bracelet_id = get_post_meta($product->get_id(), '_bracelet_id', true) ?: sanitize_title($product->get_name());
                $is_best_seller = get_post_meta($product->get_id(), '_is_best_seller', true) === 'yes';
                
                // Get available sizes from product attributes
                $available_sizes = [];
                $size_attribute = $product->get_attribute('pa_size') ?: $product->get_attribute('size');
                if ($size_attribute) {
                    $available_sizes = array_map('trim', explode(',', $size_attribute));
                } else {
                    // Fallback to default sizes if no attribute is set
                    $available_sizes = ['XS', 'S/M', 'M/L', 'L/XL'];
                }
                
                // Get main bracelet image
                $main_image = '';
                $main_image_id = get_post_meta($product->get_id(), '_bracelet_main_image', true);
                if ($main_image_id) {
                    $main_image = wp_get_attachment_url($main_image_id);
                } else {
                    // Fallback to product featured image
                    $main_image = wp_get_attachment_url($product->get_image_id());
                }
                
                // Get gap images (space images for different character counts)
                $gap_images = [];
                $gap_data = [];
                for ($i = 2; $i <= 13; $i++) {
                    $gap_image_id = get_post_meta($product->get_id(), "_bracelet_gap_image_{$i}char", true);
                    $gap_url = get_post_meta($product->get_id(), "_bracelet_gap_url_{$i}char", true);
                    
                    // Determine the final image URL (URL field takes precedence, auto-filled from upload)
                    $final_image_url = '';
                    if (!empty($gap_url)) {
                        $final_image_url = $gap_url;
                    } elseif ($gap_image_id) {
                        $final_image_url = wp_get_attachment_url($gap_image_id);
                    }
                    
                    if ($final_image_url) {
                        $gap_images[$i] = $final_image_url;
                    }
                    
                    // Store detailed gap data
                    $gap_data[$i] = [
                        'image_url' => $final_image_url,
                        'url_field' => $gap_url ?: '',
                        'uploaded_image_id' => $gap_image_id ?: '',
                        'uploaded_image_url' => $gap_image_id ? wp_get_attachment_url($gap_image_id) : ''
                    ];
                }
                
                // Get main charm image
                $main_charm_image = '';
                $main_charm_data = [];
                $main_charm_image_id = get_post_meta($product->get_id(), '_bracelet_main_charm_image', true);
                $main_charm_url = get_post_meta($product->get_id(), '_bracelet_main_charm_url', true);
                
                // Determine the final main charm image URL (URL field takes precedence)
                if (!empty($main_charm_url)) {
                    $main_charm_image = $main_charm_url;
                } elseif ($main_charm_image_id) {
                    $main_charm_image = wp_get_attachment_url($main_charm_image_id);
                }
                
                // Store detailed main charm data
                $main_charm_data = [
                    'image_url' => $main_charm_image,
                    'url_field' => $main_charm_url ?: '',
                    'uploaded_image_id' => $main_charm_image_id ?: '',
                    'uploaded_image_url' => $main_charm_image_id ? wp_get_attachment_url($main_charm_image_id) : ''
                ];
                
                // Get space stone images for all positions and formats
                $space_stone_images = [];
                $space_stone_data = [];
                for ($position = 1; $position <= 13; $position++) {
                    $position_padded = str_pad($position, 2, '0', STR_PAD_LEFT);
                    $formats = ['O', 'E'];
                    
                    foreach ($formats as $format_code) {
                        $field_key = "_space_stone_pos_{$position_padded}_{$format_code}";
                        $url_field_key = "_space_stone_url_pos_{$position_padded}_{$format_code}";
                        
                        $stone_image_id = get_post_meta($product->get_id(), $field_key, true);
                        $stone_url = get_post_meta($product->get_id(), $url_field_key, true);
                        
                        // Determine the final stone image URL (URL field takes precedence)
                        $final_stone_url = '';
                        if (!empty($stone_url)) {
                            $final_stone_url = $stone_url;
                        } elseif ($stone_image_id) {
                            $final_stone_url = wp_get_attachment_url($stone_image_id);
                        }
                        
                        $stone_key = "{$position_padded}_{$format_code}";
                        if ($final_stone_url) {
                            $space_stone_images[$stone_key] = $final_stone_url;
                        }
                        
                        // Store detailed space stone data
                        $space_stone_data[$stone_key] = [
                            'position' => $position,
                            'position_padded' => $position_padded,
                            'format_code' => $format_code,
                            'image_url' => $final_stone_url,
                            'url_field' => $stone_url ?: '',
                            'uploaded_image_id' => $stone_image_id ?: '',
                            'uploaded_image_url' => $stone_image_id ? wp_get_attachment_url($stone_image_id) : ''
                        ];
                    }
                }
                
                $bracelets[] = [
                    'id' => $bracelet_id,
                    'woocommerce_id' => $product->get_id(),
                    'name' => $product->get_name(),
                    'description' => $product->get_short_description() ?: $product->get_description(),
                    'basePrice' => (float) $product->get_price(),
                    'image' => $main_image,
                    'gapImages' => $gap_images,
                    'gapData' => $gap_data,
                    'mainCharmImage' => $main_charm_image,
                    'mainCharmData' => $main_charm_data,
                    'spaceStoneImages' => $space_stone_images,
                    'spaceStoneData' => $space_stone_data,
                    'availableSizes' => $available_sizes,
                    'isBestSeller' => $is_best_seller,
                    'category' => 'standard',
                    'slug' => $product->get_slug(),
                    'sku' => $product->get_sku()
                ];
            }
            
            // If no products found, return hardcoded fallback
            if (empty($bracelets)) {
                $bracelets = Bracelet_Customizer_Product_Types::get_hardcoded_bracelet_products();
                $source = 'fallback';
            } else {
                $source = 'woocommerce';
            }
            
            return rest_ensure_response([
                'success' => true,
                'data' => $bracelets,
                'source' => $source,
                'total' => count($bracelets)
            ]);
            
        } catch (Exception $e) {
            error_log('Bracelet API Error: ' . $e->getMessage());
            
            // Return fallback data on error
            $bracelets = Bracelet_Customizer_Product_Types::get_hardcoded_bracelet_products();
            return rest_ensure_response([
                'success' => true,
                'data' => $bracelets,
                'source' => 'fallback_error',
                'error' => $e->getMessage()
            ]);
        }
    }
    
    /**
     * Get charms from WooCommerce Charm products
     */
    public function get_charms($request) {
        try {
            $category = $request->get_param('category') ?: 'All';
            
            if (!class_exists('WooCommerce')) {
                // Fallback to hardcoded data if WooCommerce not available
                $charms = Bracelet_Customizer_Product_Types::get_hardcoded_charm_products();
                return rest_ensure_response([
                    'success' => true,
                    'data' => $charms,
                    'source' => 'fallback'
                ]);
            }
            
            // Query WooCommerce products with Charm type
            $args = [
                'post_type' => 'product',
                'post_status' => 'publish',
                'posts_per_page' => -1,
                'tax_query' => [
                    [
                        'taxonomy' => 'product_type',
                        'field' => 'slug',
                        'terms' => 'charm'
                    ]
                ]
            ];
            
            // Add category filter if specified
            if ($category && $category !== 'All') {
                $args['meta_query'][] = [
                    'key' => '_charm_category',
                    'value' => strtolower(str_replace(' ', '-', $category)),
                    'compare' => '='
                ];
            }
            
            $products = get_posts($args);
            $charms = [];
            
            foreach ($products as $product_post) {
                $product = wc_get_product($product_post->ID);
                if (!$product) continue;
                
                // Get product meta data
                $charm_id = get_post_meta($product->get_id(), '_charm_id', true) ?: sanitize_title($product->get_name());
                $category = get_post_meta($product->get_id(), '_charm_category', true) ?: 'bestsellers';
                $is_new = get_post_meta($product->get_id(), '_is_new', true) === 'yes';
                
                // Get main charm image
                $main_image = '';
                $main_image_id = get_post_meta($product->get_id(), '_charm_main_image', true);
                if ($main_image_id) {
                    $main_image = wp_get_attachment_url($main_image_id);
                } else {
                    // Fallback to product featured image
                    $main_image = wp_get_attachment_url($product->get_image_id());
                }
                
                // Get position images (for 9 different positions on bracelet)
                $position_images = [];
                for ($pos = 1; $pos <= 9; $pos++) {
                    $pos_image_id = get_post_meta($product->get_id(), "_charm_position_image_{$pos}", true);
                    if ($pos_image_id) {
                        $position_images[$pos] = wp_get_attachment_url($pos_image_id);
                    }
                }
                
                // Get charm description/vibe
                $vibe = get_post_meta($product->get_id(), '_charm_vibe', true) ?: '';
                
                // Get charm tags
                $tags = get_post_meta($product->get_id(), '_charm_tags', true);
                if (is_string($tags)) {
                    $tags = array_map('trim', explode(',', $tags));
                }
                if (!is_array($tags)) {
                    $tags = [];
                }
                
                $charms[] = [
                    'id' => $charm_id,
                    'woocommerce_id' => $product->get_id(),
                    'name' => $product->get_name(),
                    'description' => $product->get_short_description() ?: $product->get_description(),
                    'price' => (float) $product->get_price(),
                    'image' => $main_image,
                    'positionImages' => $position_images,
                    'isNew' => $is_new,
                    'category' => $category,
                    'vibe' => $vibe,
                    'tags' => $tags,
                    'slug' => $product->get_slug(),
                    'sku' => $product->get_sku()
                ];
            }
            
            // If no products found, return hardcoded fallback
            if (empty($charms)) {
                $charms = Bracelet_Customizer_Product_Types::get_hardcoded_charm_products();
                $source = 'fallback';
            } else {
                $source = 'woocommerce';
            }
            
            return rest_ensure_response([
                'success' => true,
                'data' => $charms,
                'source' => $source,
                'total' => count($charms),
                'category' => $category
            ]);
            
        } catch (Exception $e) {
            error_log('Charm API Error: ' . $e->getMessage());
            
            // Return fallback data on error
            $charms = Bracelet_Customizer_Product_Types::get_hardcoded_charm_products();
            return rest_ensure_response([
                'success' => true,
                'data' => $charms,
                'source' => 'fallback_error',
                'error' => $e->getMessage()
            ]);
        }
    }
    
    /**
     * Get settings
     */
    public function get_settings($request) {
        $settings = get_option('bracelet_customizer_settings', []);
        
        // Remove sensitive settings for frontend
        unset($settings['advanced_settings']);
        
        return rest_ensure_response($settings);
    }
    
    /**
     * Save customization
     */
    public function save_customization($request) {
        global $wpdb;
        
        $session_id = $request->get_param('session_id');
        $product_id = $request->get_param('product_id');
        $customization_data = $request->get_param('customization_data');
        
        if (!$session_id || !$product_id || !$customization_data) {
            return new WP_Error('missing_data', 'Missing required data', ['status' => 400]);
        }
        
        $table_name = $wpdb->prefix . 'bracelet_customizations';
        
        $result = $wpdb->replace($table_name, [
            'session_id' => sanitize_text_field($session_id),
            'product_id' => intval($product_id),
            'customization_data' => wp_json_encode($customization_data)
        ]);
        
        if ($result === false) {
            return new WP_Error('save_failed', 'Failed to save customization', ['status' => 500]);
        }
        
        return rest_ensure_response(['success' => true]);
    }
    
    /**
     * Check permission for protected endpoints
     */
    public function check_permission($request) {
        return true; // For now, allow all requests
    }
}