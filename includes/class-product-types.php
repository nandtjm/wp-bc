<?php
/**
 * Product Types Class
 *
 * @package Bracelet_Customizer
 * @since 1.0.0
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Handle custom WooCommerce product types for bracelets and charms
 */
class Bracelet_Customizer_Product_Types {
    
    /**
     * Initialize product types
     */
    public function __construct() {
        $this->init_hooks();
        $this->include_product_classes();
    }
    
    /**
     * Initialize WordPress hooks
     */
    private function init_hooks() {
        // Register product types
        add_action('init', [$this, 'register_product_types'], 20);
        
        // Add product type to dropdown
        add_filter('product_type_selector', [$this, 'add_product_type_selector']);
        
        // Add product data tabs
        add_filter('woocommerce_product_data_tabs', [$this, 'add_product_data_tabs']);
        
        // Add product data panels
        add_action('woocommerce_product_data_panels', [$this, 'add_product_data_panels']);
        
        // Save product data
        add_action('woocommerce_process_product_meta', [$this, 'save_product_data']);
        
        // Add product type specific JavaScript
        add_action('admin_footer', [$this, 'add_product_type_js']);
        
        // Filter product class
        add_filter('woocommerce_product_class', [$this, 'woocommerce_product_class'], 10, 2);
        
        // Enqueue admin scripts
        add_action('admin_enqueue_scripts', [$this, 'enqueue_admin_scripts']);
    }
    
    /**
     * Include custom product classes
     */
    private function include_product_classes() {
        require_once BRACELET_CUSTOMIZER_PLUGIN_PATH . 'includes/product-types/class-wc-product-standard-bracelet.php';
        require_once BRACELET_CUSTOMIZER_PLUGIN_PATH . 'includes/product-types/class-wc-product-charm.php';
        require_once BRACELET_CUSTOMIZER_PLUGIN_PATH . 'includes/product-types/class-wc-product-bracelet-collabs.php';
    }
    
    /**
     * Register custom product types
     */
    public function register_product_types() {
        // Register Standard Bracelet product type
        if (class_exists('WC_Product_Type')) {
            class_alias('WC_Product_Standard_Bracelet', 'WC_Product_Standard_Bracelet_Type');
        }
        
        // Register Charm product type
        if (class_exists('WC_Product_Type')) {
            class_alias('WC_Product_Charm', 'WC_Product_Charm_Type');
        }
    }
    
    /**
     * Add product types to the selector dropdown
     */
    public function add_product_type_selector($types) {
        $types['standard_bracelet'] = __('Standard Bracelet', 'bracelet-customizer');
        $types['bracelet_collabs'] = __('Bracelet Collabs', 'bracelet-customizer');
        $types['charm'] = __('Charm', 'bracelet-customizer');
        
        return $types;
    }
    
    /**
     * Add custom product data tabs
     */
    public function add_product_data_tabs($tabs) {
        // Charm Configuration Tab
        $tabs['charm_config'] = [
            'label' => __('Charm Config', 'bracelet-customizer'),
            'target' => 'charm_config_data',
            'class' => ['show_if_charm'],
            'priority' => 21
        ];
        
        // Bracelet Collabs Configuration Tab
        $tabs['bracelet_collabs_config'] = [
            'label' => __('Collabs Config', 'bracelet-customizer'),
            'target' => 'bracelet_collabs_config_data',
            'class' => ['show_if_bracelet_collabs'],
            'priority' => 21
        ];
        
        return $tabs;
    }
    
    /**
     * Add custom product data panels
     */
    public function add_product_data_panels() {
        global $post;
        
        // Charm Configuration Panel
        ?>
        <div id="charm_config_data" class="panel woocommerce_options_panel hidden">
            <div class="options_group">
                <h4><?php _e('Charm Configuration', 'bracelet-customizer'); ?></h4>
                
                <?php
                woocommerce_wp_select([
                    'id' => '_charm_category',
                    'label' => __('Charm Category', 'bracelet-customizer'),
                    'description' => __('Select the category for this charm.', 'bracelet-customizer'),
                    'desc_tip' => true,
                    'options' => [
                        'bestsellers' => __('Bestsellers', 'bracelet-customizer'),
                        'new_drops' => __('New Drops & Favs', 'bracelet-customizer'),
                        'personalize' => __('Personalize it', 'bracelet-customizer'),
                        'by_vibe' => __('By Vibe', 'bracelet-customizer')
                    ]
                ]);
                
                woocommerce_wp_checkbox([
                    'id' => '_charm_is_new',
                    'label' => __('New Charm', 'bracelet-customizer'),
                    'description' => __('Mark this charm as new.', 'bracelet-customizer')
                ]);
                
                woocommerce_wp_checkbox([
                    'id' => '_charm_is_bestseller',
                    'label' => __('Bestseller', 'bracelet-customizer'),
                    'description' => __('Mark this charm as a bestseller.', 'bracelet-customizer')
                ]);
                
                // Get current values
                $main_charm_image_id = get_post_meta($post->ID, '_charm_main_image', true);
                $main_charm_url = get_post_meta($post->ID, '_charm_main_url', true);
                
                // Auto-fill URL if image is uploaded but URL is empty
                if ($main_charm_image_id && empty($main_charm_url)) {
                    $main_charm_url = wp_get_attachment_url($main_charm_image_id);
                }
                
                echo '<div class="main-charm-image-group" style="border: 1px solid #ddd; padding: 15px; margin-bottom: 15px; border-radius: 5px;">';
                echo '<h4 style="margin: 0 0 10px 0;">' . __('Main Charm Image', 'bracelet-customizer') . '</h4>';
                
                // Image URL field
                woocommerce_wp_text_input([
                    'id' => '_charm_main_url',
                    'label' => __('Main Charm Image URL', 'bracelet-customizer'),
                    'description' => __('Image URL (auto-filled when uploaded via WordPress, or enter external CDN/Cloud URL)', 'bracelet-customizer'),
                    'desc_tip' => true,
                    'value' => $main_charm_url,
                    'wrapper_class' => 'form-row form-row-wide',
                    'type' => 'url',
                    'custom_attributes' => [
                        'placeholder' => 'https://example.com/main-charm.webp',
                        'data-auto-fill-field' => '_charm_main_image'
                    ]
                ]);
                
                // Image upload field
                $this->render_image_field(
                    $post->ID, 
                    '_charm_main_image', 
                    __('Upload Main Charm Image', 'bracelet-customizer')
                );
                
                echo '</div>';
                ?>
            </div>
        </div>
        
        <!-- Bracelet Collabs Configuration Panel -->
        <div id="bracelet_collabs_config_data" class="panel woocommerce_options_panel hidden">
            <div class="options_group">
                <h4><?php _e('Bracelet Collabs Configuration', 'bracelet-customizer'); ?></h4>
                
                <?php
                // Get current values
                $collabs_image_id = get_post_meta($post->ID, '_collabs_main_image', true);
                $collabs_url = get_post_meta($post->ID, '_collabs_main_url', true);
                
                // Auto-fill URL if image is uploaded but URL is empty
                if ($collabs_image_id && empty($collabs_url)) {
                    $collabs_url = wp_get_attachment_url($collabs_image_id);
                }
                
                echo '<div class="collabs-image-group" style="border: 1px solid #ddd; padding: 15px; margin-bottom: 15px; border-radius: 5px;">';
                echo '<h4 style="margin: 0 0 10px 0;">' . __('Bracelet Collabs Image', 'bracelet-customizer') . '</h4>';
                
                // Image URL field
                woocommerce_wp_text_input([
                    'id' => '_collabs_main_url',
                    'label' => __('Collabs Image URL', 'bracelet-customizer'),
                    'description' => __('Image URL (auto-filled when uploaded via WordPress, or enter external CDN/Cloud URL)', 'bracelet-customizer'),
                    'desc_tip' => true,
                    'value' => $collabs_url,
                    'wrapper_class' => 'form-row form-row-wide',
                    'type' => 'url',
                    'custom_attributes' => [
                        'placeholder' => 'https://example.com/collabs-bracelet.webp',
                        'data-auto-fill-field' => '_collabs_main_image'
                    ]
                ]);
                
                // Image upload field
                $this->render_image_field(
                    $post->ID, 
                    '_collabs_main_image', 
                    __('Upload Collabs Image', 'bracelet-customizer')
                );
                
                echo '</div>';
                ?>
            </div>
        </div>
        <?php
    }
    
    /**
     * Save custom product data
     */
    public function save_product_data($post_id) {
        // Save charm data
        if (isset($_POST['_charm_category'])) {
            update_post_meta($post_id, '_charm_category', sanitize_text_field($_POST['_charm_category']));
        }
        
        if (isset($_POST['_charm_is_new'])) {
            update_post_meta($post_id, '_charm_is_new', 'yes');
        } else {
            update_post_meta($post_id, '_charm_is_new', 'no');
        }
        
        if (isset($_POST['_charm_is_bestseller'])) {
            update_post_meta($post_id, '_charm_is_bestseller', 'yes');
        } else {
            update_post_meta($post_id, '_charm_is_bestseller', 'no');
        }
        
        if (isset($_POST['_charm_base_image'])) {
            update_post_meta($post_id, '_charm_base_image', sanitize_url($_POST['_charm_base_image']));
        }
        
        if (isset($_POST['_charm_description'])) {
            update_post_meta($post_id, '_charm_description', sanitize_textarea_field($_POST['_charm_description']));
        }
        
        if (isset($_POST['_charm_tags'])) {
            update_post_meta($post_id, '_charm_tags', sanitize_text_field($_POST['_charm_tags']));
        }
        
        // Save main charm image and URL
        if (isset($_POST['_charm_main_image'])) {
            update_post_meta($post_id, '_charm_main_image', sanitize_text_field($_POST['_charm_main_image']));
        }
        
        if (isset($_POST['_charm_main_url'])) {
            update_post_meta($post_id, '_charm_main_url', esc_url_raw($_POST['_charm_main_url']));
        }
        
        // Auto-fill URL when main charm image is uploaded
        $main_charm_image_id = isset($_POST['_charm_main_image']) ? $_POST['_charm_main_image'] : '';
        $main_charm_url = isset($_POST['_charm_main_url']) ? $_POST['_charm_main_url'] : '';
        
        if ($main_charm_image_id && empty($main_charm_url)) {
            $auto_url = wp_get_attachment_url($main_charm_image_id);
            if ($auto_url) {
                update_post_meta($post_id, '_charm_main_url', $auto_url);
            }
        }
        
        // Save Bracelet Collabs data
        if (isset($_POST['_collabs_main_image'])) {
            update_post_meta($post_id, '_collabs_main_image', sanitize_text_field($_POST['_collabs_main_image']));
        }
        
        if (isset($_POST['_collabs_main_url'])) {
            update_post_meta($post_id, '_collabs_main_url', esc_url_raw($_POST['_collabs_main_url']));
        }
        
        // Auto-fill URL when collabs image is uploaded
        $collabs_image_id = isset($_POST['_collabs_main_image']) ? $_POST['_collabs_main_image'] : '';
        $collabs_url = isset($_POST['_collabs_main_url']) ? $_POST['_collabs_main_url'] : '';
        
        if ($collabs_image_id && empty($collabs_url)) {
            $auto_url = wp_get_attachment_url($collabs_image_id);
            if ($auto_url) {
                update_post_meta($post_id, '_collabs_main_url', $auto_url);
            }
        }
        
        // Save position images
        $position_images = [];
        for ($i = 1; $i <= 9; $i++) {
            if (isset($_POST["_charm_position_image_{$i}"])) {
                $position_images[$i] = sanitize_url($_POST["_charm_position_image_{$i}"]);
            }
        }
        update_post_meta($post_id, '_charm_position_images', $position_images);
    }
    
    /**
     * Render an image upload field
     */
    private function render_image_field($product_id, $meta_key, $label) {
        $image_id = get_post_meta($product_id, $meta_key, true);
        $uploaded_image_url = $image_id ? wp_get_attachment_url($image_id) : '';
        
        // Check for external URL
        $external_url = '';
        if ($meta_key === '_charm_main_image') {
            $external_url = get_post_meta($product_id, '_charm_main_url', true);
        } elseif ($meta_key === '_collabs_main_image') {
            $external_url = get_post_meta($product_id, '_collabs_main_url', true);
        }
        
        // Determine which image to show (external URL takes precedence)
        $display_image_url = '';
        $image_source = '';
        if (!empty($external_url)) {
            $display_image_url = $external_url;
            $image_source = 'external';
        } elseif ($uploaded_image_url) {
            $display_image_url = $uploaded_image_url;
            $image_source = 'uploaded';
        }
        
        echo '<div class="form-field ' . esc_attr($meta_key) . '_field">';
        echo '<label for="' . esc_attr($meta_key) . '">' . esc_html($label) . '</label>';
        echo '<div class="image-upload-container">';
        
        echo '<input type="hidden" id="' . esc_attr($meta_key) . '" name="' . esc_attr($meta_key) . '" value="' . esc_attr($image_id) . '" />';
        
        echo '<div class="image-preview" style="margin-bottom: 10px;">';
        if ($display_image_url) {
            echo '<img src="' . esc_url($display_image_url) . '" style="max-width: 150px; max-height: 150px; display: block;" />';
            if ($image_source === 'external') {
                echo '<p style="font-size: 11px; color: #666; margin: 5px 0 0 0;">External URL Image</p>';
            } elseif ($image_source === 'uploaded') {
                echo '<p style="font-size: 11px; color: #666; margin: 5px 0 0 0;">WordPress Upload</p>';
            }
        } else {
            echo '<div style="width: 150px; height: 150px; border: 2px dashed #ccc; display: flex; align-items: center; justify-content: center; color: #999;">No image selected</div>';
        }
        echo '</div>';
        
        echo '<button type="button" class="button upload-image-button" data-field="' . esc_attr($meta_key) . '">' . __('Upload Image', 'bracelet-customizer') . '</button>';
        
        if ($image_id || $external_url) {
            echo ' <button type="button" class="button remove-image-button" data-field="' . esc_attr($meta_key) . '">' . __('Remove', 'bracelet-customizer') . '</button>';
        }
        
        echo '</div>';
        echo '</div>';
    }
    
    /**
     * Enqueue admin scripts and styles
     */
    public function enqueue_admin_scripts($hook) {
        global $post_type;
        
        if ($hook === 'post.php' && $post_type === 'product' || $hook === 'post-new.php' && $post_type === 'product') {
            wp_enqueue_media();
        }
    }
    
    /**
     * Add JavaScript for product type functionality
     */
    public function add_product_type_js() {
        global $post;
        
        if (!$post || $post->post_type !== 'product') {
            return;
        }
        
        ?>
        <script type="text/javascript">
        jQuery(document).ready(function($) {
			// Show/hide tabs based on product type
			function toggleProductTypeTabs() {
				var productType = $('#product-type').val();
				
				// Hide all custom tabs
				$('.charm_config_tab').hide();
				$('.show_if_standard_bracelet, .show_if_charm, .show_if_bracelet_collabs').hide();
				
				// For all custom product types, show general tab fields (like simple products)
				if (productType === 'standard_bracelet' || productType === 'charm' || productType === 'bracelet_collabs') {
					$('.general_options').show();
					$('.show_if_simple').show();
					$('.pricing').show();
					$('._regular_price_field, ._sale_price_field').show();
					$('.sale_price_dates_fields').show();
					
					// Hide Virtual and Downloadable checkboxes for custom product types
					$('label[for="_virtual"], label[for="_downloadable"]').hide();
					$('._virtual_field, ._downloadable_field').hide();
					$('.show_if_virtual, .show_if_downloadable').hide();
				}
				
				// Show relevant tabs
				if (productType === 'standard_bracelet') {
					$('.show_if_standard_bracelet').show();
				} else if (productType === 'bracelet_collabs') {
					$('.show_if_bracelet_collabs').show();
				} else if (productType === 'charm') {
					$('.charm_config_tab').show();
					$('.show_if_charm').show();
				}
			}
            
            // Initial toggle
            toggleProductTypeTabs();
            
            // Toggle on product type change
            $('#product-type').on('change', toggleProductTypeTabs);
            
            // Image upload functionality
            $(document).on('click', '.upload-image-button', function(e) {
                e.preventDefault();
                var button = $(this);
                var fieldId = button.data('field');
                
                var mediaUploader = wp.media({
                    title: '<?php _e('Choose Image', 'bracelet-customizer'); ?>',
                    button: {
                        text: '<?php _e('Use this image', 'bracelet-customizer'); ?>'
                    },
                    multiple: false
                });
                
                mediaUploader.on('select', function() {
                    var attachment = mediaUploader.state().get('selection').first().toJSON();
                    $('#' + fieldId).val(attachment.id);
                    
                    // Update preview
                    var preview = button.siblings('.image-upload-container').find('.image-preview');
                    preview.html('<img src="' + attachment.url + '" style="max-width: 150px; max-height: 150px; display: block;" /><p style="font-size: 11px; color: #666; margin: 5px 0 0 0;">WordPress Upload</p>');
                    
                    // Auto-fill URL field if exists
                    var urlField = $('#' + fieldId.replace('_image', '_url'));
                    if (urlField.length && !urlField.val()) {
                        urlField.val(attachment.url);
                    }
                    
                    // Show remove button
                    if (!button.siblings('.remove-image-button').length) {
                        button.after(' <button type="button" class="button remove-image-button" data-field="' + fieldId + '"><?php _e('Remove', 'bracelet-customizer'); ?></button>');
                    }
                });
                
                mediaUploader.open();
            });
            
            // Image remove functionality
            $(document).on('click', '.remove-image-button', function(e) {
                e.preventDefault();
                var button = $(this);
                var fieldId = button.data('field');
                
                $('#' + fieldId).val('');
                
                // Update preview
                var preview = button.siblings('.image-upload-container').find('.image-preview');
                preview.html('<div style="width: 150px; height: 150px; border: 2px dashed #ccc; display: flex; align-items: center; justify-content: center; color: #999;">No image selected</div>');
                
                // Remove this button
                button.remove();
            });
            
            // Bulk upload functionality for charms
            $('#bulk-upload-position-images').on('click', function(e) {
                e.preventDefault();
                var mediaUploader = wp.media({
                    title: '<?php _e('Select Position Images', 'bracelet-customizer'); ?>',
                    button: {
                        text: '<?php _e('Use Images', 'bracelet-customizer'); ?>'
                    },
                    multiple: true
                });
                
                mediaUploader.on('select', function() {
                    var selection = mediaUploader.state().get('selection');
                    var images = selection.toJSON();
                    
                    // Auto-assign images based on order
                    images.forEach(function(image, index) {
                        var position = index + 1; // Start from position 1
                        if (position <= 9) {
                            $('#_charm_position_image_' + position).val(image.url);
                        }
                    });
                });
                
                mediaUploader.open();
            });
        });
        </script>
        <?php
    }
    
    /**
     * Filter product class for custom product types
     */
    public function woocommerce_product_class($classname, $product_type) {
        if ($product_type === 'standard_bracelet') {
            return 'WC_Product_Standard_Bracelet';
        } elseif ($product_type === 'bracelet_collabs') {
            return 'WC_Product_Bracelet_Collabs';
        } elseif ($product_type === 'charm') {
            return 'WC_Product_Charm';
        }
        return $classname;
    }
    
    /**
     * Get bracelet products for customizer
     */
    public static function get_bracelet_products($args = []) {
        // Check if we have WooCommerce, otherwise return hardcoded data
        if (!class_exists('WooCommerce') || !function_exists('wc_get_product')) {
            return self::get_hardcoded_bracelet_products($args);
        }
        
        $default_args = [
            'post_type' => 'product',
            'post_status' => 'publish',
            'posts_per_page' => -1,
            'meta_query' => [
                [
                    'key' => '_product_type',
                    'value' => 'standard_bracelet'
                ]
            ]
        ];
        
        $args = wp_parse_args($args, $default_args);
        
        $products = get_posts($args);
        
        // If no custom products found, return hardcoded data
        if (empty($products)) {
            return self::get_hardcoded_bracelet_products($args);
        }
        
        $bracelet_data = [];
        
        foreach ($products as $product_post) {
            $product = wc_get_product($product_post->ID);
            if (!$product) continue;
            
            $bracelet_data[] = [
                'id' => $product->get_id(),
                'name' => $product->get_name(),
                'price' => $product->get_price(),
                'image' => wp_get_attachment_url($product->get_image_id()),
                'base_image' => get_post_meta($product->get_id(), '_bracelet_base_image', true),
                'gap_images' => get_post_meta($product->get_id(), '_bracelet_gap_images', true) ?: [],
                'category' => get_post_meta($product->get_id(), '_bracelet_style_category', true),
                'is_bestseller' => get_post_meta($product->get_id(), '_bracelet_is_bestseller', true) === 'yes',
                'customizable' => get_post_meta($product->get_id(), '_bracelet_customizable', true) === 'yes',
                'available_sizes' => explode("\n", get_post_meta($product->get_id(), '_bracelet_available_sizes', true) ?: "XS\nS/M\nM/L\nL/XL")
            ];
        }
        
        return $bracelet_data;
    }
    
    /**
     * Get charm products for customizer
     */
    public static function get_charm_products($args = []) {
        // Check if we have WooCommerce, otherwise return hardcoded data
        if (!class_exists('WooCommerce') || !function_exists('wc_get_product')) {
            return self::get_hardcoded_charm_products($args);
        }
        
        $default_args = [
            'post_type' => 'product',
            'post_status' => 'publish',
            'posts_per_page' => -1,
            'meta_query' => [
                [
                    'key' => '_product_type',
                    'value' => 'charm'
                ]
            ]
        ];
        
        $args = wp_parse_args($args, $default_args);
        
        $products = get_posts($args);
        
        // If no custom products found, return hardcoded data
        if (empty($products)) {
            return self::get_hardcoded_charm_products($args);
        }
        
        $charm_data = [];
        
        foreach ($products as $product_post) {
            $product = wc_get_product($product_post->ID);
            if (!$product) continue;
            
            $charm_data[] = [
                'id' => $product->get_id(),
                'name' => $product->get_name(),
                'price' => $product->get_price(),
                'image' => wp_get_attachment_url($product->get_image_id()),
                'base_image' => get_post_meta($product->get_id(), '_charm_base_image', true),
                'position_images' => get_post_meta($product->get_id(), '_charm_position_images', true) ?: [],
                'category' => get_post_meta($product->get_id(), '_charm_category', true),
                'is_new' => get_post_meta($product->get_id(), '_charm_is_new', true) === 'yes',
                'is_bestseller' => get_post_meta($product->get_id(), '_charm_is_bestseller', true) === 'yes',
                'description' => get_post_meta($product->get_id(), '_charm_description', true),
                'tags' => get_post_meta($product->get_id(), '_charm_tags', true)
            ];
        }
        
        return $charm_data;
    }
    
    /**
     * Get hardcoded bracelet products (fallback for when no WooCommerce products exist)
     */
    public static function get_hardcoded_bracelet_products($args = []) {
        $bracelets = [
            [
                'id' => 'gold-plated',
                'name' => 'Gold Plated',
                'image' => BRACELET_CUSTOMIZER_PLUGIN_URL . 'assets/images/bracelets/gold-plated.webp',
                'basePrice' => 0,
                'isBestSeller' => true,
                'category' => 'standard',
                'availableSizes' => ['XS', 'S/M', 'M/L', 'L/XL']
            ],
            [
                'id' => 'bluestone',
                'name' => 'Bluestone',
                'image' => BRACELET_CUSTOMIZER_PLUGIN_URL . 'assets/images/bracelets/bluestone.webp',
                'gapImages' => [
                    '2' => BRACELET_CUSTOMIZER_PLUGIN_URL . 'assets/images/bracelets/bluestone-2char.webp',
                    '3' => BRACELET_CUSTOMIZER_PLUGIN_URL . 'assets/images/bracelets/bluestone-3char.webp',
                    '4' => BRACELET_CUSTOMIZER_PLUGIN_URL . 'assets/images/bracelets/bluestone-4char.webp',
                    '5' => BRACELET_CUSTOMIZER_PLUGIN_URL . 'assets/images/bracelets/bluestone-5char.webp',
                    '6' => BRACELET_CUSTOMIZER_PLUGIN_URL . 'assets/images/bracelets/bluestone-6char.webp',
                    '7' => BRACELET_CUSTOMIZER_PLUGIN_URL . 'assets/images/bracelets/bluestone-7char.webp',
                    '8' => BRACELET_CUSTOMIZER_PLUGIN_URL . 'assets/images/bracelets/bluestone-8char.webp',
                    '9' => BRACELET_CUSTOMIZER_PLUGIN_URL . 'assets/images/bracelets/bluestone-9char.webp',
                    '10' => BRACELET_CUSTOMIZER_PLUGIN_URL . 'assets/images/bracelets/bluestone-10char.webp',
                    '11' => BRACELET_CUSTOMIZER_PLUGIN_URL . 'assets/images/bracelets/bluestone-11char.webp',
                    '12' => BRACELET_CUSTOMIZER_PLUGIN_URL . 'assets/images/bracelets/bluestone-12char.webp',
                    '13' => BRACELET_CUSTOMIZER_PLUGIN_URL . 'assets/images/bracelets/bluestone-13char.webp'
                ],
                'basePrice' => 0,
                'isBestSeller' => true,
                'category' => 'standard',
                'availableSizes' => ['XS', 'S/M', 'M/L', 'L/XL']
            ],
            [
                'id' => 'amethyst-dreams',
                'name' => 'Amethyst Dreams',
                'image' => BRACELET_CUSTOMIZER_PLUGIN_URL . 'assets/images/bracelets/amethyst-dreams.webp',
                'basePrice' => 5,
                'isBestSeller' => false,
                'category' => 'special',
                'availableSizes' => ['XS', 'S/M', 'M/L', 'L/XL']
            ],
            [
                'id' => 'rose-gold',
                'name' => 'Rose Gold',
                'image' => BRACELET_CUSTOMIZER_PLUGIN_URL . 'assets/images/bracelets/rose-gold.webp',
                'basePrice' => 10,
                'isBestSeller' => false,
                'category' => 'special',
                'availableSizes' => ['XS', 'S/M', 'M/L', 'L/XL']
            ]
        ];

        // Apply filters if specified
        if (isset($args['category']) && $args['category'] !== 'All') {
            $bracelets = array_filter($bracelets, function($bracelet) use ($args) {
                return $bracelet['category'] === strtolower($args['category']);
            });
        }

        if (isset($args['bestsellers_only']) && $args['bestsellers_only']) {
            $bracelets = array_filter($bracelets, function($bracelet) {
                return $bracelet['isBestSeller'];
            });
        }

        return array_values($bracelets);
    }
    
    /**
     * Get hardcoded charm products (fallback for when no WooCommerce products exist)
     */
    public static function get_hardcoded_charm_products($args = []) {
        $charms = [
            [
                'id' => 'teacher',
                'name' => '#1 Teacher',
                'image' => BRACELET_CUSTOMIZER_PLUGIN_URL . 'assets/images/charms/teacher-banner.jpg',
                'price' => 14,
                'isNew' => true,
                'category' => 'bestsellers'
            ],
            [
                'id' => 'heart',
                'name' => 'Heart',
                'image' => BRACELET_CUSTOMIZER_PLUGIN_URL . 'assets/images/charms/apple.jpg',
                'price' => 12,
                'isNew' => false,
                'category' => 'bestsellers'
            ],
            [
                'id' => 'star',
                'name' => 'Star',
                'image' => BRACELET_CUSTOMIZER_PLUGIN_URL . 'assets/images/charms/paint-palette.jpg',
                'price' => 10,
                'isNew' => false,
                'category' => 'by-vibe'
            ],
            [
                'id' => 'moon',
                'name' => 'Moon',
                'image' => BRACELET_CUSTOMIZER_PLUGIN_URL . 'assets/images/charms/moon.png',
                'price' => 13,
                'isNew' => true,
                'category' => 'new-drops'
            ],
            [
                'id' => 'butterfly',
                'name' => 'Butterfly',
                'image' => BRACELET_CUSTOMIZER_PLUGIN_URL . 'assets/images/charms/butterfly.png',
                'price' => 15,
                'isNew' => false,
                'category' => 'by-vibe'
            ],
            [
                'id' => 'anchor',
                'name' => 'Anchor',
                'image' => BRACELET_CUSTOMIZER_PLUGIN_URL . 'assets/images/charms/anchor.png',
                'price' => 11,
                'isNew' => false,
                'category' => 'bestsellers'
            ]
        ];

        // Apply filters if specified
        if (isset($args['category']) && $args['category'] !== 'All') {
            $category_map = [
                'Bestsellers' => 'bestsellers',
                'New Drops & Favs' => 'new-drops',
                'By Vibe' => 'by-vibe'
            ];
            
            $filter_category = $category_map[$args['category']] ?? strtolower($args['category']);
            
            $charms = array_filter($charms, function($charm) use ($filter_category) {
                return $charm['category'] === $filter_category;
            });
        }

        if (isset($args['new_only']) && $args['new_only']) {
            $charms = array_filter($charms, function($charm) {
                return $charm['isNew'];
            });
        }

        return array_values($charms);
    }
}