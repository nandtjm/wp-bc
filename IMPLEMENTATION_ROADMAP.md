# Implementation Roadmap
## Converting React Customizer to WordPress Plugin

### Phase 1: Core Plugin Foundation (Days 1-3)

#### Step 1.1: Create Main Plugin Structure
```bash
# Create main plugin file
touch exp-bracelets-customizer.php

# Create directory structure
mkdir -p includes admin public assets/{css,js,images} templates languages
```

**File: `exp-bracelets-customizer.php`**
```php
<?php
/**
 * Plugin Name: Bracelet Customizer
 * Description: WooCommerce bracelet customization with React interface
 * Version: 1.0.0
 * Author: Your Name
 * Text Domain: bracelet-customizer
 * Domain Path: /languages
 * Requires at least: 5.0
 * Tested up to: 6.3
 * WC requires at least: 4.0
 * WC tested up to: 8.0
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

// Define plugin constants
define('BRACELET_CUSTOMIZER_VERSION', '1.0.0');
define('BRACELET_CUSTOMIZER_PLUGIN_URL', plugin_dir_url(__FILE__));
define('BRACELET_CUSTOMIZER_PLUGIN_PATH', plugin_dir_path(__FILE__));

// Check if WooCommerce is active
if (!in_array('woocommerce/woocommerce.php', apply_filters('active_plugins', get_option('active_plugins')))) {
    add_action('admin_notices', function() {
        echo '<div class="notice notice-error"><p>Bracelet Customizer requires WooCommerce to be installed and active.</p></div>';
    });
    return;
}

// Include main class
require_once BRACELET_CUSTOMIZER_PLUGIN_PATH . 'includes/class-plugin-main.php';

// Initialize plugin
function bracelet_customizer_init() {
    new Bracelet_Customizer_Main();
}
add_action('plugins_loaded', 'bracelet_customizer_init');

// Activation hook
register_activation_hook(__FILE__, 'bracelet_customizer_activate');
function bracelet_customizer_activate() {
    require_once BRACELET_CUSTOMIZER_PLUGIN_PATH . 'includes/class-plugin-main.php';
    Bracelet_Customizer_Main::activate();
}

// Deactivation hook
register_deactivation_hook(__FILE__, 'bracelet_customizer_deactivate');
function bracelet_customizer_deactivate() {
    // Cleanup tasks
}
```

#### Step 1.2: Create Main Plugin Class
**File: `includes/class-plugin-main.php`**
```php
<?php
class Bracelet_Customizer_Main {
    
    public function __construct() {
        add_action('init', [$this, 'init']);
        add_action('wp_enqueue_scripts', [$this, 'enqueue_scripts']);
        add_action('admin_enqueue_scripts', [$this, 'admin_enqueue_scripts']);
    }
    
    public function init() {
        // Load plugin textdomain
        load_plugin_textdomain('bracelet-customizer', false, dirname(plugin_basename(__FILE__)) . '/languages');
        
        // Initialize classes
        $this->init_includes();
    }
    
    private function init_includes() {
        require_once BRACELET_CUSTOMIZER_PLUGIN_PATH . 'includes/class-settings.php';
        require_once BRACELET_CUSTOMIZER_PLUGIN_PATH . 'includes/class-product-types.php';
        require_once BRACELET_CUSTOMIZER_PLUGIN_PATH . 'includes/class-rest-api.php';
        require_once BRACELET_CUSTOMIZER_PLUGIN_PATH . 'includes/class-woocommerce-integration.php';
        
        new Bracelet_Customizer_Settings();
        new Bracelet_Customizer_Product_Types();
        new Bracelet_Customizer_Rest_API();
        new Bracelet_Customizer_WooCommerce();
    }
    
    public function enqueue_scripts() {
        // Frontend scripts
        wp_enqueue_script(
            'bracelet-customizer-public',
            BRACELET_CUSTOMIZER_PLUGIN_URL . 'assets/js/public.js',
            ['jquery'],
            BRACELET_CUSTOMIZER_VERSION,
            true
        );
    }
    
    public function admin_enqueue_scripts() {
        // Admin scripts
        wp_enqueue_script(
            'bracelet-customizer-admin',
            BRACELET_CUSTOMIZER_PLUGIN_URL . 'assets/js/admin.js',
            ['jquery'],
            BRACELET_CUSTOMIZER_VERSION,
            true
        );
    }
    
    public static function activate() {
        // Create database tables
        self::create_tables();
        
        // Create sample products
        self::create_sample_products();
        
        // Set default settings
        self::set_default_settings();
        
        // Flush rewrite rules
        flush_rewrite_rules();
    }
    
    private static function create_tables() {
        global $wpdb;
        
        $charset_collate = $wpdb->get_charset_collate();
        
        // Customizations table
        $table_name = $wpdb->prefix . 'bracelet_customizations';
        $sql = "CREATE TABLE $table_name (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            session_id varchar(255) NOT NULL,
            product_id bigint(20) NOT NULL,
            customization_data longtext NOT NULL,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY session_id (session_id),
            KEY product_id (product_id)
        ) $charset_collate;";
        
        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql);
    }
    
    private static function create_sample_products() {
        // Create sample bracelet product
        $bracelet = new WC_Product_Simple();
        $bracelet->set_name('Sample Bluestone Bracelet');
        $bracelet->set_regular_price(25.00);
        $bracelet->set_description('A beautiful bluestone bracelet that can be customized with your own words and charms.');
        $bracelet->set_manage_stock(false);
        $bracelet->set_status('publish');
        
        $bracelet_id = $bracelet->save();
        
        // Set product type
        wp_set_object_terms($bracelet_id, 'standard_bracelet', 'product_type');
        
        // Add bracelet meta
        update_post_meta($bracelet_id, '_bracelet_style_category', 'standard');
        update_post_meta($bracelet_id, '_bracelet_is_bestseller', true);
        
        // Sample gap images (placeholder URLs)
        $gap_images = [];
        for ($i = 2; $i <= 13; $i++) {
            $gap_images[$i] = BRACELET_CUSTOMIZER_PLUGIN_URL . "assets/images/sample-bracelet-{$i}char.jpg";
        }
        update_post_meta($bracelet_id, '_bracelet_gap_images', $gap_images);
        
        // Create sample charm product
        $charm = new WC_Product_Simple();
        $charm->set_name('Apple Charm');
        $charm->set_regular_price(14.00);
        $charm->set_description('A beautiful apple charm to add to your bracelet.');
        $charm->set_manage_stock(false);
        $charm->set_status('publish');
        
        $charm_id = $charm->save();
        
        // Set product type
        wp_set_object_terms($charm_id, 'charm', 'product_type');
        
        // Add charm meta
        update_post_meta($charm_id, '_charm_category', 'bestsellers');
        update_post_meta($charm_id, '_charm_is_new', false);
        
        // Sample position images
        $position_images = [];
        for ($i = 1; $i <= 9; $i++) {
            $position_images[$i] = BRACELET_CUSTOMIZER_PLUGIN_URL . "assets/images/apple-pos-{$i}.jpg";
        }
        update_post_meta($charm_id, '_charm_position_images', $position_images);
    }
    
    private static function set_default_settings() {
        $default_settings = [
            'letter_source' => 'cloud',
            'cloud_base_url' => 'https://res.cloudinary.com/drvnwq9bm/image/upload',
            'max_word_length' => 13,
            'button_colors' => [
                'primary' => '#4F46E5',
                'secondary' => '#FFB6C1'
            ],
            'button_labels' => [
                'next' => 'NEXT',
                'review' => 'REVIEW',
                'add_to_cart' => 'ADD TO CART'
            ]
        ];
        
        update_option('bracelet_customizer_settings', $default_settings);
    }
}
```

### Phase 2: Custom Product Types (Days 4-6)

#### Step 2.1: Create Product Types Class
**File: `includes/class-product-types.php`**
```php
<?php
class Bracelet_Customizer_Product_Types {
    
    public function __construct() {
        add_action('init', [$this, 'register_product_types']);
        add_filter('product_type_selector', [$this, 'add_product_types']);
        add_action('woocommerce_product_data_tabs', [$this, 'add_product_data_tabs']);
        add_action('woocommerce_product_data_panels', [$this, 'add_product_data_panels']);
        add_action('woocommerce_process_product_meta', [$this, 'save_product_meta']);
    }
    
    public function register_product_types() {
        // Register custom product types
        register_taxonomy_type('standard_bracelet', 'product_type');
        register_taxonomy_type('charm', 'product_type');
    }
    
    public function add_product_types($types) {
        $types['standard_bracelet'] = __('Standard Bracelet', 'bracelet-customizer');
        $types['charm'] = __('Charm', 'bracelet-customizer');
        return $types;
    }
    
    public function add_product_data_tabs($tabs) {
        // Add bracelet data tab
        $tabs['bracelet_data'] = [
            'label' => __('Bracelet Data', 'bracelet-customizer'),
            'target' => 'bracelet_data_panel',
            'class' => ['show_if_standard_bracelet']
        ];
        
        // Add charm data tab
        $tabs['charm_data'] = [
            'label' => __('Charm Data', 'bracelet-customizer'),
            'target' => 'charm_data_panel',
            'class' => ['show_if_charm']
        ];
        
        return $tabs;
    }
    
    public function add_product_data_panels() {
        global $post;
        
        // Bracelet data panel
        echo '<div id="bracelet_data_panel" class="panel woocommerce_options_panel">';
        
        // Style category
        woocommerce_wp_select([
            'id' => '_bracelet_style_category',
            'label' => __('Style Category', 'bracelet-customizer'),
            'options' => [
                'standard' => __('Standard', 'bracelet-customizer'),
                'collabs' => __('Collaborations', 'bracelet-customizer'),
                'limited-edition' => __('Limited Edition', 'bracelet-customizer'),
                'engraving' => __('Engraving', 'bracelet-customizer'),
                'tiny-words' => __('Tiny Words', 'bracelet-customizer')
            ]
        ]);
        
        // Best seller checkbox
        woocommerce_wp_checkbox([
            'id' => '_bracelet_is_bestseller',
            'label' => __('Best Seller', 'bracelet-customizer'),
            'description' => __('Mark this bracelet as a bestseller', 'bracelet-customizer')
        ]);
        
        // Gap images fields
        echo '<h4>' . __('Gap Images (2-13 Characters)', 'bracelet-customizer') . '</h4>';
        for ($i = 2; $i <= 13; $i++) {
            woocommerce_wp_text_input([
                'id' => "_bracelet_gap_image_{$i}",
                'label' => sprintf(__('%d Character Image URL', 'bracelet-customizer'), $i),
                'desc_tip' => true,
                'description' => sprintf(__('Image URL for %d character bracelet', 'bracelet-customizer'), $i)
            ]);
        }
        
        echo '</div>';
        
        // Charm data panel
        echo '<div id="charm_data_panel" class="panel woocommerce_options_panel">';
        
        // Charm category
        woocommerce_wp_select([
            'id' => '_charm_category',
            'label' => __('Charm Category', 'bracelet-customizer'),
            'options' => [
                'bestsellers' => __('Bestsellers', 'bracelet-customizer'),
                'new-drops' => __('New Drops & Favs', 'bracelet-customizer'),
                'personalize-it' => __('Personalize It', 'bracelet-customizer')
            ]
        ]);
        
        // New charm checkbox
        woocommerce_wp_checkbox([
            'id' => '_charm_is_new',
            'label' => __('New Charm', 'bracelet-customizer'),
            'description' => __('Mark this charm as new', 'bracelet-customizer')
        ]);
        
        // Position images
        echo '<h4>' . __('Position Images (1-9)', 'bracelet-customizer') . '</h4>';
        for ($i = 1; $i <= 9; $i++) {
            woocommerce_wp_text_input([
                'id' => "_charm_position_image_{$i}",
                'label' => sprintf(__('Position %d Image URL', 'bracelet-customizer'), $i),
                'desc_tip' => true,
                'description' => sprintf(__('Image URL for charm at position %d', 'bracelet-customizer'), $i)
            ]);
        }
        
        echo '</div>';
    }
    
    public function save_product_meta($post_id) {
        // Save bracelet meta
        if (isset($_POST['_bracelet_style_category'])) {
            update_post_meta($post_id, '_bracelet_style_category', sanitize_text_field($_POST['_bracelet_style_category']));
        }
        
        update_post_meta($post_id, '_bracelet_is_bestseller', isset($_POST['_bracelet_is_bestseller']) ? 'yes' : 'no');
        
        // Save gap images
        $gap_images = [];
        for ($i = 2; $i <= 13; $i++) {
            if (isset($_POST["_bracelet_gap_image_{$i}"])) {
                $gap_images[$i] = esc_url_raw($_POST["_bracelet_gap_image_{$i}"]);
            }
        }
        update_post_meta($post_id, '_bracelet_gap_images', $gap_images);
        
        // Save charm meta
        if (isset($_POST['_charm_category'])) {
            update_post_meta($post_id, '_charm_category', sanitize_text_field($_POST['_charm_category']));
        }
        
        update_post_meta($post_id, '_charm_is_new', isset($_POST['_charm_is_new']) ? 'yes' : 'no');
        
        // Save position images
        $position_images = [];
        for ($i = 1; $i <= 9; $i++) {
            if (isset($_POST["_charm_position_image_{$i}"])) {
                $position_images[$i] = esc_url_raw($_POST["_charm_position_image_{$i}"]);
            }
        }
        update_post_meta($post_id, '_charm_position_images', $position_images);
    }
}
```

### Phase 3: REST API Implementation (Days 7-9)

#### Step 3.1: Create REST API Class
**File: `includes/class-rest-api.php`**
```php
<?php
class Bracelet_Customizer_Rest_API {
    
    private $namespace = 'bracelet-customizer/v1';
    
    public function __construct() {
        add_action('rest_api_init', [$this, 'register_routes']);
    }
    
    public function register_routes() {
        // Get bracelets
        register_rest_route($this->namespace, '/bracelets', [
            'methods' => 'GET',
            'callback' => [$this, 'get_bracelets'],
            'permission_callback' => '__return_true'
        ]);
        
        // Get charms
        register_rest_route($this->namespace, '/charms', [
            'methods' => 'GET',
            'callback' => [$this, 'get_charms'],
            'permission_callback' => '__return_true',
            'args' => [
                'category' => [
                    'required' => false,
                    'default' => 'all'
                ]
            ]
        ]);
        
        // Get settings
        register_rest_route($this->namespace, '/settings', [
            'methods' => 'GET',
            'callback' => [$this, 'get_settings'],
            'permission_callback' => '__return_true'
        ]);
        
        // Save customization
        register_rest_route($this->namespace, '/save-customization', [
            'methods' => 'POST',
            'callback' => [$this, 'save_customization'],
            'permission_callback' => [$this, 'check_nonce']
        ]);
    }
    
    public function get_bracelets($request) {
        $bracelets = [];
        
        $products = get_posts([
            'post_type' => 'product',
            'meta_query' => [
                [
                    'key' => '_product_type',
                    'value' => 'standard_bracelet'
                ]
            ],
            'posts_per_page' => -1
        ]);
        
        foreach ($products as $product) {
            $product_obj = wc_get_product($product->ID);
            $gap_images = get_post_meta($product->ID, '_bracelet_gap_images', true);
            
            $bracelets[] = [
                'id' => $product->ID,
                'name' => $product_obj->get_name(),
                'category' => get_post_meta($product->ID, '_bracelet_style_category', true),
                'is_bestseller' => get_post_meta($product->ID, '_bracelet_is_bestseller', true) === 'yes',
                'base_price' => $product_obj->get_price(),
                'image' => wp_get_attachment_image_url($product_obj->get_image_id(), 'full'),
                'gap_images' => $gap_images ?: []
            ];
        }
        
        return rest_ensure_response($bracelets);
    }
    
    public function get_charms($request) {
        $category = $request->get_param('category');
        $charms = [];
        
        $args = [
            'post_type' => 'product',
            'meta_query' => [
                [
                    'key' => '_product_type',
                    'value' => 'charm'
                ]
            ],
            'posts_per_page' => -1
        ];
        
        if ($category !== 'all') {
            $args['meta_query'][] = [
                'key' => '_charm_category',
                'value' => $category
            ];
        }
        
        $products = get_posts($args);
        
        foreach ($products as $product) {
            $product_obj = wc_get_product($product->ID);
            $position_images = get_post_meta($product->ID, '_charm_position_images', true);
            
            $charms[] = [
                'id' => $product->ID,
                'name' => $product_obj->get_name(),
                'category' => get_post_meta($product->ID, '_charm_category', true),
                'is_new' => get_post_meta($product->ID, '_charm_is_new', true) === 'yes',
                'price' => $product_obj->get_price(),
                'image' => wp_get_attachment_image_url($product_obj->get_image_id(), 'full'),
                'position_images' => $position_images ?: []
            ];
        }
        
        return rest_ensure_response($charms);
    }
    
    public function get_settings($request) {
        $settings = get_option('bracelet_customizer_settings', []);
        
        // Add letter colors
        $settings['letter_colors'] = [
            ['id' => 'white', 'name' => 'White', 'price' => 0],
            ['id' => 'pink', 'name' => 'Pink', 'price' => 0],
            ['id' => 'black', 'name' => 'Black', 'price' => 0],
            ['id' => 'gold', 'name' => 'Gold', 'price' => 15]
        ];
        
        return rest_ensure_response($settings);
    }
    
    public function save_customization($request) {
        global $wpdb;
        
        $session_id = $request->get_param('session_id');
        $product_id = $request->get_param('product_id');
        $customization_data = $request->get_param('customization_data');
        
        $table_name = $wpdb->prefix . 'bracelet_customizations';
        
        $result = $wpdb->replace(
            $table_name,
            [
                'session_id' => $session_id,
                'product_id' => $product_id,
                'customization_data' => json_encode($customization_data)
            ]
        );
        
        if ($result) {
            return rest_ensure_response(['success' => true]);
        } else {
            return new WP_Error('save_failed', 'Failed to save customization', ['status' => 500]);
        }
    }
    
    public function check_nonce($request) {
        $nonce = $request->get_header('X-WP-Nonce');
        return wp_verify_nonce($nonce, 'wp_rest');
    }
}
```

### Phase 4: React App Integration (Days 10-12)

#### Step 4.1: Modify React App for Dynamic Data
**File: `bracelet-customizer/src/hooks/useWordPressData.js`**
```javascript
import { useState, useEffect } from 'react';

export const useWordPressData = () => {
  const [bracelets, setBracelets] = useState([]);
  const [charms, setCharms] = useState([]);
  const [settings, setSettings] = useState({});
  const [loading, setLoading] = useState(true);

  useEffect(() => {
    const fetchData = async () => {
      try {
        // Get API base URL from WordPress
        const apiBase = window.BraceletCustomizerConfig?.apiBase || '/wp-json/bracelet-customizer/v1/';
        
        // Fetch all data in parallel
        const [braceletsRes, charmsRes, settingsRes] = await Promise.all([
          fetch(`${apiBase}bracelets`),
          fetch(`${apiBase}charms`),
          fetch(`${apiBase}settings`)
        ]);

        const [braceletsData, charmsData, settingsData] = await Promise.all([
          braceletsRes.json(),
          charmsRes.json(),
          settingsRes.json()
        ]);

        setBracelets(braceletsData);
        setCharms(charmsData);
        setSettings(settingsData);
        setLoading(false);
      } catch (error) {
        console.error('Error fetching WordPress data:', error);
        setLoading(false);
      }
    };

    fetchData();
  }, []);

  return { bracelets, charms, settings, loading };
};
```

**File: `bracelet-customizer/src/utils/assetManager.js`**
```javascript
export class AssetManager {
  constructor(settings) {
    this.settings = settings;
  }

  getLetterImagePath(letter, letterPosition, totalCharCount, letterColor, isTrailingSpace = false) {
    if (this.settings.letter_source === 'local') {
      return this.getLocalLetterPath(letter, letterPosition, totalCharCount, letterColor);
    } else {
      return this.getCloudLetterPath(letter, letterPosition, totalCharCount, letterColor);
    }
  }

  getCloudLetterPath(letter, letterPosition, totalCharCount, letterColor) {
    const colorMap = {
      'white': 'WL',
      'pink': 'PK', 
      'black': 'BL',
      'gold': 'GL'
    };
    
    const colorCode = colorMap[letterColor] || 'WL';
    const formatCode = totalCharCount % 2 === 1 ? 'O' : 'E';
    
    // Get centered positions
    const centeredPositions = this.getCenteredBraceletPositions(totalCharCount);
    const actualBraceletPosition = centeredPositions[letterPosition];
    const urlPosition = actualBraceletPosition.toString().padStart(2, '0');
    
    return `${this.settings.cloud_base_url}/w_915,f_auto/customizer-v2/colors/${colorCode}/${letter.toUpperCase()}/${colorCode}-${letter.toUpperCase()}-${formatCode}-${urlPosition}.png`;
  }

  getLocalLetterPath(letter, letterPosition, totalCharCount, letterColor) {
    // Implementation for local WordPress assets
    const uploadDir = window.BraceletCustomizerConfig?.uploadUrl || '/wp-content/uploads/';
    return `${uploadDir}bracelet-customizer/letters/${letterColor}/${letter.toUpperCase()}/${totalCharCount}char-pos${letterPosition + 1}.png`;
  }

  getCenteredBraceletPositions(wordLength) {
    const positionMaps = {
      1: [7],
      2: [7, 8],
      3: [6, 7, 8],
      4: [6, 7, 8, 9],
      5: [5, 6, 7, 8, 9],
      6: [5, 6, 7, 8, 9, 10],
      7: [4, 5, 6, 7, 8, 9, 10],
      8: [4, 5, 6, 7, 8, 9, 10, 11],
      9: [3, 4, 5, 6, 7, 8, 9, 10, 11],
      10: [3, 4, 5, 6, 7, 8, 9, 10, 11, 12],
      11: [2, 3, 4, 5, 6, 7, 8, 9, 10, 11, 12],
      12: [2, 3, 4, 5, 6, 7, 8, 9, 10, 11, 12, 13],
      13: [1, 2, 3, 4, 5, 6, 7, 8, 9, 10, 11, 12, 13]
    };
    
    return positionMaps[wordLength] || [7];
  }
}
```

#### Step 4.2: Update App.js to Use WordPress Data
**Modify: `bracelet-customizer/src/App.js`**
```javascript
import React, { useState, useEffect } from 'react';
import './App.css';
import { useWordPressData } from './hooks/useWordPressData';
import { AssetManager } from './utils/assetManager';
// ... other imports

function App() {
  const { bracelets, charms, settings, loading } = useWordPressData();
  const [assetManager, setAssetManager] = useState(null);
  
  // ... existing state

  useEffect(() => {
    if (settings && Object.keys(settings).length > 0) {
      setAssetManager(new AssetManager(settings));
    }
  }, [settings]);

  // Show loading state
  if (loading || !assetManager) {
    return (
      <div style={{ 
        display: 'flex', 
        justifyContent: 'center', 
        alignItems: 'center', 
        height: '100vh' 
      }}>
        <div>Loading customizer...</div>
      </div>
    );
  }

  // Organize bracelets by category (using WordPress data)
  const braceletsByCategory = {
    'All': bracelets,
    'Standard': bracelets.filter(b => b.category === 'standard'),
    'Collabs': bracelets.filter(b => b.category === 'collabs'),
    'Limited Edition': bracelets.filter(b => b.category === 'limited-edition'),
    'Engraving': bracelets.filter(b => b.category === 'engraving'),
    'Tiny Words': bracelets.filter(b => b.category === 'tiny-words')
  };

  // Organize charms by category (using WordPress data)
  const charmsByCategory = {
    'All': charms,
    'Bestsellers': charms.filter(c => c.category === 'bestsellers'),
    'New Drops & Favs': charms.filter(c => c.category === 'new-drops'),
    'Personalize it': charms.filter(c => c.category === 'personalize-it')
  };

  // Update getLetterImagePath to use AssetManager
  const getLetterImagePath = (letter, letterPosition, totalCharCount, letterColor, isTrailingSpace = false) => {
    return assetManager.getLetterImagePath(letter, letterPosition, totalCharCount, letterColor, isTrailingSpace);
  };

  // Use WordPress settings for letter colors
  const letterColors = settings.letter_colors || [
    { id: 'white', name: 'White', price: 0 },
    { id: 'pink', name: 'Pink', price: 0 },
    { id: 'black', name: 'Black', price: 0 },
    { id: 'gold', name: 'Gold', price: 15 }
  ];

  // ... rest of component logic remains the same but uses WordPress data
}
```

### Phase 5: WordPress Integration (Days 13-15)

#### Step 5.1: Create Shortcode for Customizer
**File: `public/shortcodes.php`**
```php
<?php
class Bracelet_Customizer_Shortcodes {
    
    public function __construct() {
        add_shortcode('bracelet_customizer', [$this, 'render_customizer']);
    }
    
    public function render_customizer($atts) {
        $atts = shortcode_atts([
            'product_id' => 0,
            'width' => '100%',
            'height' => '800px'
        ], $atts);
        
        // Enqueue React app
        $this->enqueue_react_app();
        
        // Generate unique session ID
        $session_id = uniqid('bc_', true);
        
        ob_start();
        ?>
        <div id="bracelet-customizer-root" 
             data-product-id="<?php echo esc_attr($atts['product_id']); ?>"
             data-session-id="<?php echo esc_attr($session_id); ?>"
             style="width: <?php echo esc_attr($atts['width']); ?>; height: <?php echo esc_attr($atts['height']); ?>;">
        </div>
        
        <script>
        window.BraceletCustomizerConfig = {
            apiBase: '<?php echo rest_url('bracelet-customizer/v1/'); ?>',
            nonce: '<?php echo wp_create_nonce('wp_rest'); ?>',
            sessionId: '<?php echo $session_id; ?>',
            productId: <?php echo intval($atts['product_id']); ?>,
            uploadUrl: '<?php echo wp_upload_dir()['baseurl']; ?>',
            pluginUrl: '<?php echo BRACELET_CUSTOMIZER_PLUGIN_URL; ?>',
            woocommerce: {
                cartUrl: '<?php echo wc_get_cart_url(); ?>',
                checkoutUrl: '<?php echo wc_get_checkout_url(); ?>',
                currency: '<?php echo get_woocommerce_currency(); ?>',
                currencySymbol: '<?php echo get_woocommerce_currency_symbol(); ?>'
            }
        };
        </script>
        <?php
        return ob_get_clean();
    }
    
    private function enqueue_react_app() {
        // Enqueue the built React app
        wp_enqueue_script(
            'bracelet-customizer-app',
            BRACELET_CUSTOMIZER_PLUGIN_URL . 'bracelet-customizer/build/static/js/main.js',
            [],
            BRACELET_CUSTOMIZER_VERSION,
            true
        );
        
        wp_enqueue_style(
            'bracelet-customizer-app-css',
            BRACELET_CUSTOMIZER_PLUGIN_URL . 'bracelet-customizer/build/static/css/main.css',
            [],
            BRACELET_CUSTOMIZER_VERSION
        );
    }
}

new Bracelet_Customizer_Shortcodes();
```

#### Step 5.2: Add Product Page Integration
**File: `includes/class-woocommerce-integration.php`**
```php
<?php
class Bracelet_Customizer_WooCommerce {
    
    public function __construct() {
        add_action('woocommerce_single_product_summary', [$this, 'add_customizer_button'], 25);
        add_action('wp_ajax_add_custom_bracelet_to_cart', [$this, 'add_custom_bracelet_to_cart']);
        add_action('wp_ajax_nopriv_add_custom_bracelet_to_cart', [$this, 'add_custom_bracelet_to_cart']);
        add_filter('woocommerce_add_cart_item_data', [$this, 'add_cart_item_data'], 10, 3);
        add_filter('woocommerce_get_item_data', [$this, 'display_cart_item_data'], 10, 2);
    }
    
    public function add_customizer_button() {
        global $product;
        
        // Only show for bracelet products
        if (has_term('standard_bracelet', 'product_type', $product->get_id())) {
            echo '<div class="bracelet-customizer-button-wrapper">';
            echo '<button type="button" class="button alt bracelet-customize-btn" data-product-id="' . $product->get_id() . '">';
            echo __('Customize This Bracelet', 'bracelet-customizer');
            echo '</button>';
            echo '</div>';
            
            // Add modal for customizer
            $this->render_customizer_modal($product->get_id());
        }
    }
    
    private function render_customizer_modal($product_id) {
        ?>
        <div id="bracelet-customizer-modal" class="bracelet-modal" style="display: none;">
            <div class="bracelet-modal-content">
                <span class="bracelet-modal-close">&times;</span>
                <div id="bracelet-customizer-container">
                    <?php echo do_shortcode('[bracelet_customizer product_id="' . $product_id . '"]'); ?>
                </div>
            </div>
        </div>
        
        <script>
        jQuery(document).ready(function($) {
            $('.bracelet-customize-btn').on('click', function() {
                $('#bracelet-customizer-modal').show();
            });
            
            $('.bracelet-modal-close').on('click', function() {
                $('#bracelet-customizer-modal').hide();
            });
            
            $(window).on('click', function(event) {
                if (event.target.id === 'bracelet-customizer-modal') {
                    $('#bracelet-customizer-modal').hide();
                }
            });
        });
        </script>
        <?php
    }
    
    public function add_custom_bracelet_to_cart() {
        check_ajax_referer('wp_rest', 'nonce');
        
        $product_id = intval($_POST['product_id']);
        $customization_data = json_decode(stripslashes($_POST['customization_data']), true);
        $quantity = intval($_POST['quantity']) ?: 1;
        
        // Calculate custom price based on customization
        $custom_price = $this->calculate_custom_price($product_id, $customization_data);
        
        // Add to cart with custom data
        $cart_item_key = WC()->cart->add_to_cart(
            $product_id,
            $quantity,
            0, // variation_id
            [], // variation
            [
                'bracelet_customization' => $customization_data,
                'custom_price' => $custom_price
            ]
        );
        
        if ($cart_item_key) {
            wp_send_json_success([
                'message' => __('Bracelet added to cart!', 'bracelet-customizer'),
                'cart_url' => wc_get_cart_url()
            ]);
        } else {
            wp_send_json_error([
                'message' => __('Failed to add bracelet to cart', 'bracelet-customizer')
            ]);
        }
    }
    
    private function calculate_custom_price($product_id, $customization_data) {
        $product = wc_get_product($product_id);
        $base_price = $product->get_price();
        
        // Add letter color surcharge
        if (isset($customization_data['letterColor']) && $customization_data['letterColor'] === 'gold') {
            $base_price += 15; // Gold letters surcharge
        }
        
        // Add charm prices
        if (isset($customization_data['selectedCharms'])) {
            foreach ($customization_data['selectedCharms'] as $charm) {
                $charm_product = wc_get_product($charm['id']);
                if ($charm_product) {
                    $base_price += $charm_product->get_price();
                }
            }
        }
        
        return $base_price;
    }
    
    public function add_cart_item_data($cart_item_data, $product_id, $variation_id) {
        if (isset($_POST['bracelet_customization'])) {
            $cart_item_data['bracelet_customization'] = json_decode(stripslashes($_POST['bracelet_customization']), true);
        }
        
        if (isset($_POST['custom_price'])) {
            $cart_item_data['custom_price'] = floatval($_POST['custom_price']);
        }
        
        return $cart_item_data;
    }
    
    public function display_cart_item_data($item_data, $cart_item) {
        if (isset($cart_item['bracelet_customization'])) {
            $customization = $cart_item['bracelet_customization'];
            
            if (isset($customization['word'])) {
                $item_data[] = [
                    'key' => __('Word', 'bracelet-customizer'),
                    'value' => $customization['word']
                ];
            }
            
            if (isset($customization['letterColor'])) {
                $item_data[] = [
                    'key' => __('Letter Color', 'bracelet-customizer'),
                    'value' => ucfirst($customization['letterColor'])
                ];
            }
            
            if (isset($customization['selectedCharms']) && !empty($customization['selectedCharms'])) {
                $charm_names = [];
                foreach ($customization['selectedCharms'] as $charm) {
                    $charm_names[] = $charm['name'];
                }
                $item_data[] = [
                    'key' => __('Charms', 'bracelet-customizer'),
                    'value' => implode(', ', $charm_names)
                ];
            }
        }
        
        return $item_data;
    }
}
```

This roadmap provides a complete implementation plan for converting the React customizer into a WordPress plugin. Each phase builds upon the previous one, creating a fully functional WooCommerce-integrated bracelet customizer.