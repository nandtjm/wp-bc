<?php
/**
 * WooCommerce Integration Class
 *
 * @package Bracelet_Customizer
 * @since 1.0.0
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Handle WooCommerce integration functionality
 */
class Bracelet_Customizer_WooCommerce {
    
    /**
     * Initialize WooCommerce integration
     */
    public function __construct() {
        $this->init_hooks();
    }
    
    /**
     * Initialize WordPress hooks
     */
    private function init_hooks() {
        // Product page hooks
        add_action('woocommerce_single_product_summary', [$this, 'add_customize_button'], 35);
        
        // Cart hooks
        add_filter('woocommerce_add_cart_item_data', [$this, 'add_customization_to_cart'], 10, 3);
        add_filter('woocommerce_get_cart_item_from_session', [$this, 'get_cart_item_from_session'], 10, 3);
        add_filter('woocommerce_get_item_data', [$this, 'display_customization_in_cart'], 10, 2);
        add_action('woocommerce_cart_item_name', [$this, 'add_customization_to_cart_item'], 10, 3);
        
        // Price modification hooks
        add_action('woocommerce_before_calculate_totals', [$this, 'modify_cart_item_price']);
        
        // Cart image hooks
        add_filter('woocommerce_cart_item_thumbnail', [$this, 'custom_cart_item_thumbnail'], 10, 3);
        add_filter('woocommerce_admin_order_item_thumbnail', [$this, 'custom_order_item_thumbnail'], 10, 3);
        
        // Checkout and email image hooks
        add_filter('woocommerce_order_item_thumbnail', [$this, 'custom_order_item_thumbnail'], 10, 3);
        add_filter('woocommerce_email_order_item_thumbnail', [$this, 'custom_email_order_item_thumbnail'], 10, 3);
        
        // Order hooks
        add_action('woocommerce_checkout_create_order_line_item', [$this, 'add_customization_to_order_item'], 10, 4);
        add_action('woocommerce_order_item_meta_end', [$this, 'display_customization_in_order'], 10, 3);
        
        // Admin order hooks
        add_action('woocommerce_admin_order_item_headers', [$this, 'add_customization_column_header']);
        add_action('woocommerce_admin_order_item_values', [$this, 'add_customization_column_content'], 10, 3);
        
        // AJAX hooks
        add_action('wp_ajax_add_custom_bracelet_to_cart', [$this, 'ajax_add_to_cart']);
        add_action('wp_ajax_nopriv_add_custom_bracelet_to_cart', [$this, 'ajax_add_to_cart']);
        
        // New AJAX hooks for React app
        add_action('wp_ajax_bracelet_add_to_cart', [$this, 'ajax_add_to_cart_v2']);
        add_action('wp_ajax_nopriv_bracelet_add_to_cart', [$this, 'ajax_add_to_cart_v2']);
    }
    
    /**
     * Add customize button to product page
     */
    public function add_customize_button() {
        global $product;
        
        if (!$product || !$this->is_customizable_product($product)) {
            return;
        }
        
        $settings = get_option('bracelet_customizer_settings', []);
        $button_text = $settings['button_labels']['customize'] ?? __('Customize This Bracelet', 'bracelet-customizer');
        
        ?>
        <div class="bracelet-customizer-button-wrapper">
            <button class="bracelet-customize-btn" data-product-id="<?php echo esc_attr($product->get_id()); ?>">
                <?php echo esc_html($button_text); ?>
            </button>
        </div>
        <?php
    }
    
    /**
     * Check if product is customizable
     */
    private function is_customizable_product($product) {
        if (!$product) {
            return false;
        }
        
        // Check if it's a standard bracelet product type
        if (has_term('standard_bracelet', 'product_type', $product->get_id())) {
            return true;
        }
        
        // Check if customizable meta is set
        return get_post_meta($product->get_id(), '_bracelet_customizable', true) === 'yes';
    }
    
    /**
     * Add customization data to cart
     */
    public function add_customization_to_cart($cart_item_data, $product_id, $variation_id) {
        if (isset($_POST['bracelet_customization'])) {
            $customization = json_decode(stripslashes($_POST['bracelet_customization']), true);
            
            if ($customization) {
                $cart_item_data['bracelet_customization'] = $customization;
                $cart_item_data['unique_key'] = md5(microtime().rand());
                
                // Note: Custom image URL will be provided by the React app in the future
            }
        }
        
        return $cart_item_data;
    }
    
    /**
     * Get cart item from session
     */
    public function get_cart_item_from_session($item, $values, $key) {
        if (array_key_exists('bracelet_customization', $values)) {
            $item['bracelet_customization'] = $values['bracelet_customization'];
        }
        
        if (array_key_exists('custom_image_url', $values)) {
            $item['custom_image_url'] = $values['custom_image_url'];
        }
        
        return $item;
    }
    
    /**
     * Display customization in cart
     */
    public function display_customization_in_cart($item_data, $cart_item) {
        if (isset($cart_item['bracelet_customization'])) {
            $customization = $cart_item['bracelet_customization'];
            
            if (isset($customization['word'])) {
                $item_data[] = [
                    'key' => __('Word', 'bracelet-customizer'),
                    'value' => strtoupper($customization['word'])
                ];
            }
            
            if (isset($customization['letterColor'])) {
                $item_data[] = [
                    'key' => __('Letter Color', 'bracelet-customizer'),
                    'value' => ucfirst($customization['letterColor'])
                ];
            }
            
            // Handle both possible charm field names
            $selected_charms = $customization['selectedCharms'] ?? $customization['selected_charms'] ?? [];
            if (!empty($selected_charms)) {
                $charm_names = array_column($selected_charms, 'name');
                $item_data[] = [
                    'key' => __('Charms', 'bracelet-customizer'),
                    'value' => implode(', ', $charm_names)
                ];
            }
            
            if (isset($customization['size'])) {
                $item_data[] = [
                    'key' => __('Size', 'bracelet-customizer'),
                    'value' => strtoupper($customization['size'])
                ];
            }
        }
        
        return $item_data;
    }
    
    /**
     * Modify cart item price based on customization
     */
    public function modify_cart_item_price($cart) {
        if (is_admin() && !defined('DOING_AJAX')) {
            return;
        }

        foreach ($cart->get_cart() as $cart_item_key => $cart_item) {
            if (isset($cart_item['bracelet_customization'])) {
                $customization = $cart_item['bracelet_customization'];
                $product = $cart_item['data'];
                $base_price = $product->get_regular_price();
                $additional_price = 0;

                // Add letter color pricing (gold letters cost extra)
                $letter_color = $customization['letter_color'] ?? $customization['letterColor'] ?? 'white';
                if ($letter_color === 'gold') {
                    $additional_price += 15; // Gold letters cost $15 extra
                }

                // Add charm pricing
                $selected_charms = $customization['selected_charms'] ?? $customization['selectedCharms'] ?? [];
                foreach ($selected_charms as $charm) {
                    if (isset($charm['price'])) {
                        $additional_price += (float) $charm['price'];
                    }
                }

                // Set the new price
                if ($additional_price > 0) {
                    $new_price = $base_price + $additional_price;
                    $product->set_price($new_price);
                }
            }
        }
    }
    
    /**
     * Add customization to cart item name
     */
    public function add_customization_to_cart_item($product_name, $cart_item, $cart_item_key) {
        if (isset($cart_item['bracelet_customization']['word'])) {
            $word = $cart_item['bracelet_customization']['word'];
            $product_name .= '<br><small>' . sprintf(__('Word: %s', 'bracelet-customizer'), strtoupper($word)) . '</small>';
        }
        
        return $product_name;
    }
    
    /**
     * Add customization to order item
     */
    public function add_customization_to_order_item($item, $cart_item_key, $values, $order) {
        if (isset($values['bracelet_customization'])) {
            $customization = $values['bracelet_customization'];
            
            // Add as order item meta
            $item->add_meta_data('_bracelet_customization', $customization);
            
            // Save custom image URL if available
            if (isset($values['custom_image_url'])) {
                $item->add_meta_data('_custom_image_url', $values['custom_image_url']);
            }
            
            // Add individual meta for easy access
            if (isset($customization['word'])) {
                $item->add_meta_data(__('Word', 'bracelet-customizer'), strtoupper($customization['word']));
            }
            
            if (isset($customization['letterColor'])) {
                $item->add_meta_data(__('Letter Color', 'bracelet-customizer'), ucfirst($customization['letterColor']));
            }
            
            if (isset($customization['selectedCharms']) && !empty($customization['selectedCharms'])) {
                $charm_names = array_column($customization['selectedCharms'], 'name');
                $item->add_meta_data(__('Charms', 'bracelet-customizer'), implode(', ', $charm_names));
            }
            
            if (isset($customization['size'])) {
                $item->add_meta_data(__('Size', 'bracelet-customizer'), strtoupper($customization['size']));
            }
        }
    }
    
    /**
     * Display customization in order
     */
    public function display_customization_in_order($item_id, $item, $order) {
        $customization = $item->get_meta('_bracelet_customization');
        
        if ($customization) {
            echo '<div class="bracelet-customization-summary">';
            echo '<h4>' . __('Customization Details', 'bracelet-customizer') . '</h4>';
            
            if (isset($customization['word'])) {
                echo '<p><strong>' . __('Word:', 'bracelet-customizer') . '</strong> ' . strtoupper($customization['word']) . '</p>';
            }
            
            if (isset($customization['letterColor'])) {
                echo '<p><strong>' . __('Letter Color:', 'bracelet-customizer') . '</strong> ' . ucfirst($customization['letterColor']) . '</p>';
            }
            
            if (isset($customization['selectedCharms']) && !empty($customization['selectedCharms'])) {
                echo '<p><strong>' . __('Charms:', 'bracelet-customizer') . '</strong></p>';
                echo '<ul>';
                foreach ($customization['selectedCharms'] as $charm) {
                    echo '<li>' . esc_html($charm['name']) . '</li>';
                }
                echo '</ul>';
            }
            
            echo '</div>';
        }
    }
    
    /**
     * Add customization column header in admin
     */
    public function add_customization_column_header() {
        echo '<th class="item-customization">' . __('Customization', 'bracelet-customizer') . '</th>';
    }
    
    /**
     * Add customization column content in admin
     */
    public function add_customization_column_content($product, $item, $item_id) {
        $customization = $item->get_meta('_bracelet_customization');
        
        echo '<td class="item-customization">';
        if ($customization) {
            if (isset($customization['word'])) {
                echo '<strong>' . strtoupper($customization['word']) . '</strong><br>';
            }
            
            if (isset($customization['letterColor'])) {
                echo '<small>' . ucfirst($customization['letterColor']) . ' letters</small><br>';
            }
            
            if (isset($customization['selectedCharms']) && !empty($customization['selectedCharms'])) {
                echo '<small>' . count($customization['selectedCharms']) . ' charm(s)</small>';
            }
        } else {
            echo '-';
        }
        echo '</td>';
    }
    
    /**
     * AJAX add to cart
     */
    public function ajax_add_to_cart() {
        check_ajax_referer('bracelet_customizer_nonce', 'nonce');
        
        $product_id = intval($_POST['product_id']);
        $quantity = intval($_POST['quantity']) ?: 1;
        $customization_data = json_decode(stripslashes($_POST['customization_data']), true);
        
        if (!$product_id || !$customization_data) {
            wp_send_json_error(['message' => __('Invalid data provided.', 'bracelet-customizer')]);
        }
        
        // Validate product
        $product = wc_get_product($product_id);
        if (!$product || !$this->is_customizable_product($product)) {
            wp_send_json_error(['message' => __('Product is not customizable.', 'bracelet-customizer')]);
        }
        
        // Validate customization
        $validation = $this->validate_customization($customization_data);
        if (is_wp_error($validation)) {
            wp_send_json_error(['message' => $validation->get_error_message()]);
        }
        
        // Add to cart
        $cart_item_data = ['bracelet_customization' => $customization_data];
        $cart_item_key = WC()->cart->add_to_cart($product_id, $quantity, 0, [], $cart_item_data);
        
        if ($cart_item_key) {
            wp_send_json_success([
                'message' => __('Bracelet added to cart!', 'bracelet-customizer'),
                'cart_url' => wc_get_cart_url()
            ]);
        } else {
            wp_send_json_error(['message' => __('Failed to add bracelet to cart.', 'bracelet-customizer')]);
        }
    }
    
    /**
     * AJAX add to cart (v2 for React app)
     */
    public function ajax_add_to_cart_v2() {
        // Verify nonce
        if (!wp_verify_nonce($_POST['nonce'], 'bracelet_customizer_nonce')) {
            wp_send_json_error(['message' => __('Security check failed.', 'bracelet-customizer')]);
        }
        
        // Get product data
        $product_data = json_decode(stripslashes($_POST['product_data']), true);
        $customization_id = sanitize_text_field($_POST['customization_id']);
        
        // Debug logging
        error_log('Add to cart v2 called with product_data: ' . print_r($product_data, true));
        error_log('Add to cart v2 called with customization_id: ' . $customization_id);
        
        if (!$product_data || !$customization_id) {
            wp_send_json_error(['message' => __('Invalid data provided.', 'bracelet-customizer')]);
        }
        
        $product_id = intval($product_data['product_id']);
        $quantity = intval($product_data['quantity']) ?: 1;
        $variation_data = $product_data['variation_data'] ?? [];
        
        // Get customization from database
        global $wpdb;
        $table_name = $wpdb->prefix . 'bracelet_customizations';
        $customization = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM $table_name WHERE id = %s OR session_id = %s ORDER BY created_at DESC LIMIT 1",
            $customization_id,
            $customization_id
        ));
        
        if (!$customization) {
            error_log('Customization not found for ID: ' . $customization_id);
            wp_send_json_error(['message' => __('Customization not found.', 'bracelet-customizer')]);
        }
        
        error_log('Found customization: ' . print_r($customization, true));
        
        $customization_data = json_decode($customization->customization_data, true);
        
        // Validate product
        $product = wc_get_product($product_id);
        if (!$product || !$this->is_customizable_product($product)) {
            wp_send_json_error(['message' => __('Product is not customizable.', 'bracelet-customizer')]);
        }
        
        // Prepare cart item data
        $cart_item_data = [
            'bracelet_customization' => $customization_data,
            'customization_id' => $customization_id,
            'unique_key' => md5($customization_id . microtime())
        ];
        
        // Save custom image URL if provided by React app
        if (isset($product_data['custom_image_url'])) {
            $cart_item_data['custom_image_url'] = $product_data['custom_image_url'];
        }
        
        // Add variation data if present
        if (!empty($variation_data)) {
            $cart_item_data['variation_data'] = $variation_data;
        }
        
        // Add to cart
        $cart_item_key = WC()->cart->add_to_cart($product_id, $quantity, 0, [], $cart_item_data);
        
        if ($cart_item_key) {
            error_log('Successfully added to cart with key: ' . $cart_item_key);
            wp_send_json_success([
                'message' => __('Bracelet added to cart!', 'bracelet-customizer'),
                'cart_url' => wc_get_cart_url(),
                'cart_item_key' => $cart_item_key
            ]);
        } else {
            error_log('Failed to add to cart for product_id: ' . $product_id . ' with data: ' . print_r($cart_item_data, true));
            wp_send_json_error(['message' => __('Failed to add bracelet to cart.', 'bracelet-customizer')]);
        }
    }
    
    /**
     * Generate custom cart item thumbnail
     */
    public function custom_cart_item_thumbnail($thumbnail, $cart_item, $cart_item_key) {
        if (isset($cart_item['bracelet_customization'])) {
            // Try to use pre-generated image URL first
            $custom_image_url = $cart_item['custom_image_url'] ?? null;
            
            // If not available, generate it now
            if (!$custom_image_url) {
                $custom_image_url = $this->generate_customization_image($cart_item['bracelet_customization'], $cart_item);
            }
            
            if ($custom_image_url) {
                $product_name = $cart_item['data']->get_name();
                $thumbnail = sprintf('<img src="%s" alt="%s" class="attachment-woocommerce_thumbnail size-woocommerce_thumbnail" style="max-width: 64px; height: auto;">', 
                    esc_url($custom_image_url), 
                    esc_attr($product_name)
                );
            }
        }
        return $thumbnail;
    }
    
    /**
     * Generate custom order item thumbnail
     */
    public function custom_order_item_thumbnail($thumbnail, $item, $order) {
        $customization = $item->get_meta('_bracelet_customization');
        if ($customization) {
            // Try to use pre-saved custom image URL first
            $custom_image_url = $item->get_meta('_custom_image_url');
            
            // If not available, generate it now
            if (!$custom_image_url) {
                // Convert order item to cart item format for image generation
                $cart_item = [
                    'data' => $item->get_product(),
                    'bracelet_customization' => $customization
                ];
                
                $custom_image_url = $this->generate_customization_image($customization, $cart_item);
            }
            
            if ($custom_image_url) {
                $product_name = $item->get_name();
                $thumbnail = sprintf('<img src="%s" alt="%s" class="attachment-woocommerce_thumbnail size-woocommerce_thumbnail" style="max-width: 64px; height: auto;">', 
                    esc_url($custom_image_url), 
                    esc_attr($product_name)
                );
            }
        }
        return $thumbnail;
    }
    
    /**
     * Generate custom email order item thumbnail
     */
    public function custom_email_order_item_thumbnail($thumbnail, $item, $order) {
        $customization = $item->get_meta('_bracelet_customization');
        if ($customization) {
            // Try to use pre-saved custom image URL first
            $custom_image_url = $item->get_meta('_custom_image_url');
            
            // If not available, generate it now
            if (!$custom_image_url) {
                // Convert order item to cart item format for image generation
                $cart_item = [
                    'data' => $item->get_product(),
                    'bracelet_customization' => $customization
                ];
                
                $custom_image_url = $this->generate_customization_image($customization, $cart_item);
            }
            
            if ($custom_image_url) {
                $product_name = $item->get_name();
                $thumbnail = sprintf('<img src="%s" alt="%s" style="max-width: 64px; height: auto; border: none;">', 
                    esc_url($custom_image_url), 
                    esc_attr($product_name)
                );
            }
        }
        return $thumbnail;
    }
    
    /**
     * Generate customization preview image URL
     */
    private function generate_customization_image($customization, $cart_item) {
        // For now, check if a custom image URL was saved with the cart item
        if (isset($cart_item['custom_image_url'])) {
            return $cart_item['custom_image_url'];
        }
        
        // If no custom image is available, fall back to product image
        $product = $cart_item['data'];
        if ($product && $product->get_image_id()) {
            return wp_get_attachment_url($product->get_image_id());
        }
        
        return false;
    }
    
    
    /**
     * Validate customization data
     */
    private function validate_customization($customization) {
        $settings = get_option('bracelet_customizer_settings', []);
        
        // Validate word
        if (isset($customization['word'])) {
            $word = trim($customization['word']);
            $min_length = $settings['min_word_length'] ?? 2;
            $max_length = $settings['max_word_length'] ?? 13;
            
            if (strlen($word) < $min_length) {
                return new WP_Error('word_too_short', sprintf(__('Word must be at least %d characters long.', 'bracelet-customizer'), $min_length));
            }
            
            if (strlen($word) > $max_length) {
                return new WP_Error('word_too_long', sprintf(__('Word cannot be longer than %d characters.', 'bracelet-customizer'), $max_length));
            }
        }
        
        // Validate letter color
        if (isset($customization['letterColor'])) {
            $letter_colors = $settings['letter_colors'] ?? [];
            if (!isset($letter_colors[$customization['letterColor']])) {
                return new WP_Error('invalid_letter_color', __('Invalid letter color selected.', 'bracelet-customizer'));
            }
        }
        
        return true;
    }
}