# WordPress Plugin Architecture Plan
## Bracelet Customizer Integration

### 1. Plugin Structure

```
exp-bracelets-customizer/
├── exp-bracelets-customizer.php          # Main plugin file
├── includes/
│   ├── class-plugin-main.php             # Core plugin class
│   ├── class-settings.php                # Settings management
│   ├── class-product-types.php           # Custom WooCommerce product types
│   ├── class-rest-api.php                # REST API endpoints
│   ├── class-woocommerce-integration.php # WooCommerce hooks & filters
│   ├── class-asset-manager.php           # Image/asset management
│   └── class-database.php                # Database operations
├── admin/
│   ├── class-admin.php                   # Admin interface
│   ├── settings-page.php                 # Plugin settings page
│   ├── product-meta-boxes.php            # Product edit meta boxes
│   └── js/admin.js                       # Admin JavaScript
├── public/
│   ├── class-public.php                  # Frontend functionality
│   ├── shortcodes.php                    # Shortcode handlers
│   └── js/customizer-integration.js      # React-WordPress bridge
├── assets/
│   ├── css/
│   │   ├── admin.css                     # Admin styles
│   │   └── public.css                    # Frontend styles
│   ├── js/
│   │   ├── admin.js                      # Admin scripts
│   │   └── public.js                     # Frontend scripts
│   └── images/                           # Default images
├── templates/
│   ├── customizer-page.php               # Customizer page template
│   └── product-customizer.php            # Product page integration
├── bracelet-customizer/                  # React app (existing)
├── languages/                            # Translation files
└── uninstall.php                         # Cleanup on uninstall
```

### 2. Custom WooCommerce Product Types

#### A. Standard Bracelet Product Type
**Features:**
- Custom data tab in product edit page
- Gap image uploads for 2-13 character variations
- Base price configuration
- Style metadata (category, bestseller status)

**Custom Fields:**
```php
// Meta fields for bracelet products
_bracelet_style_category     // 'standard', 'collabs', 'limited-edition'
_bracelet_is_bestseller      // boolean
_bracelet_base_image         // URL to base bracelet image
_bracelet_gap_images         // Array of gap images (2-13 chars)
_bracelet_preview_image      // Thumbnail for selection
```

#### B. Charm Product Type
**Features:**
- 9 position-specific image uploads
- Individual pricing
- Category assignment
- New/bestseller badges

**Custom Fields:**
```php
// Meta fields for charm products
_charm_category              // 'bestsellers', 'new-drops', 'personalize-it'
_charm_is_new               // boolean
_charm_base_image           // Main charm image
_charm_position_images      // Array of 9 position-specific images
_charm_individual_price     // Override WooCommerce price if needed
```

### 3. Settings System

#### Global Plugin Settings Page
**Location:** WooCommerce → Bracelet Customizer Settings

**Settings Sections:**

1. **Asset Configuration**
   - Letter blocks source: Cloud URLs vs Local files
   - Cloud service settings (Cloudinary credentials)
   - Local asset upload directory

2. **Styling Options**
   - Primary button color
   - Secondary button color
   - Text labels and translations
   - Font settings

3. **Feature Configuration**
   - Maximum word length (default: 13)
   - Available letter colors
   - Default bracelet style
   - Charm limit per bracelet

4. **API Settings**
   - REST API endpoints enabled/disabled
   - CORS settings for React app
   - Cache settings

### 4. Database Schema

#### Custom Tables (if needed)
```sql
-- Table for storing customization sessions
CREATE TABLE wp_bracelet_customizations (
    id bigint(20) NOT NULL AUTO_INCREMENT,
    session_id varchar(255) NOT NULL,
    product_id bigint(20) NOT NULL,
    customization_data longtext NOT NULL, -- JSON data
    created_at datetime DEFAULT CURRENT_TIMESTAMP,
    updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    KEY session_id (session_id),
    KEY product_id (product_id)
);

-- Table for order line item customizations
CREATE TABLE wp_order_item_customizations (
    id bigint(20) NOT NULL AUTO_INCREMENT,
    order_item_id bigint(20) NOT NULL,
    customization_data longtext NOT NULL, -- JSON data
    created_at datetime DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    KEY order_item_id (order_item_id)
);
```

#### Product Meta Fields Structure
```php
// Bracelet product meta
$bracelet_meta = [
    '_bracelet_gap_images' => [
        '2' => 'url-to-2char-image.jpg',
        '3' => 'url-to-3char-image.jpg',
        // ... up to 13
    ],
    '_bracelet_style_data' => [
        'category' => 'standard',
        'is_bestseller' => true,
        'preview_image' => 'url-to-preview.jpg'
    ]
];

// Charm product meta
$charm_meta = [
    '_charm_position_images' => [
        '1' => 'url-to-position-1.jpg',
        '2' => 'url-to-position-2.jpg',
        // ... up to 9
    ],
    '_charm_data' => [
        'category' => 'bestsellers',
        'is_new' => false,
        'individual_price' => 14.00
    ]
];
```

### 5. REST API Endpoints

#### A. Data Endpoints for React App
```php
// Get all bracelet styles
GET /wp-json/bracelet-customizer/v1/bracelets

// Get all charms by category
GET /wp-json/bracelet-customizer/v1/charms?category=all

// Get plugin settings
GET /wp-json/bracelet-customizer/v1/settings

// Get letter colors and pricing
GET /wp-json/bracelet-customizer/v1/letter-colors

// Save customization session
POST /wp-json/bracelet-customizer/v1/save-customization

// Get customization by session
GET /wp-json/bracelet-customizer/v1/customization/{session_id}
```

#### B. Sample API Response Structure
```json
{
  "bracelets": [
    {
      "id": 123,
      "name": "Bluestone",
      "category": "standard",
      "is_bestseller": true,
      "base_price": 25.00,
      "image": "https://example.com/bluestone.jpg",
      "gap_images": {
        "2": "https://example.com/bluestone-2char.jpg",
        "3": "https://example.com/bluestone-3char.jpg",
        // ... up to 13
      }
    }
  ],
  "charms": [
    {
      "id": 456,
      "name": "Apple",
      "category": "bestsellers",
      "is_new": false,
      "price": 14.00,
      "image": "https://example.com/apple.jpg",
      "position_images": {
        "1": "https://example.com/apple-pos-1.jpg",
        // ... up to 9
      }
    }
  ],
  "settings": {
    "letter_source": "cloud", // or "local"
    "cloud_base_url": "https://res.cloudinary.com/...",
    "max_word_length": 13,
    "letter_colors": [
      {"id": "white", "name": "White", "price": 0},
      {"id": "gold", "name": "Gold", "price": 15}
    ]
  }
}
```

### 6. React App Integration

#### A. WordPress Data Injection
**Method 1: wp_localize_script()**
```php
// In main plugin class
wp_localize_script('bracelet-customizer-app', 'BraceletCustomizerData', [
    'api_base' => rest_url('bracelet-customizer/v1/'),
    'nonce' => wp_create_nonce('wp_rest'),
    'settings' => $this->get_plugin_settings(),
    'woocommerce_settings' => $this->get_wc_settings()
]);
```

**Method 2: REST API Calls**
```javascript
// In React app
const fetchBracelets = async () => {
    const response = await fetch('/wp-json/bracelet-customizer/v1/bracelets');
    return response.json();
};
```

#### B. Dynamic Configuration Object
```javascript
// WordPress passes this to React
window.BraceletCustomizerConfig = {
    apiBase: '/wp-json/bracelet-customizer/v1/',
    nonce: 'wp_rest_nonce_here',
    settings: {
        letterSource: 'cloud', // 'cloud' or 'local'
        cloudBaseUrl: 'https://res.cloudinary.com/...',
        colors: {
            primary: '#4F46E5',
            secondary: '#FFB6C1'
        },
        labels: {
            nextButton: 'NEXT',
            reviewButton: 'REVIEW',
            addToCartButton: 'ADD TO CART'
        }
    },
    woocommerce: {
        cartUrl: '/cart/',
        checkoutUrl: '/checkout/',
        currency: 'USD',
        currencySymbol: '$'
    }
};
```

### 7. Asset Management System

#### A. Letter Blocks Configuration
```php
class Asset_Manager {
    
    public function get_letter_image_url($letter, $color, $position = null, $total_chars = null) {
        $settings = get_option('bracelet_customizer_settings');
        
        if ($settings['letter_source'] === 'cloud') {
            return $this->get_cloud_letter_url($letter, $color, $position, $total_chars);
        } else {
            return $this->get_local_letter_url($letter, $color, $position, $total_chars);
        }
    }
    
    private function get_cloud_letter_url($letter, $color, $position, $total_chars) {
        $color_codes = [
            'white' => 'WL',
            'pink' => 'PK',
            'black' => 'BL',
            'gold' => 'GL'
        ];
        
        $color_code = $color_codes[$color] ?? 'WL';
        $format_code = ($total_chars % 2 === 1) ? 'O' : 'E';
        $position_str = str_pad($position, 2, '0', STR_PAD_LEFT);
        
        return "https://res.cloudinary.com/drvnwq9bm/image/upload/w_915,f_auto/customizer-v2/colors/{$color_code}/{$letter}/{$color_code}-{$letter}-{$format_code}-{$position_str}.png";
    }
    
    private function get_local_letter_url($letter, $color, $position, $total_chars) {
        $upload_dir = wp_upload_dir();
        return $upload_dir['baseurl'] . "/bracelet-customizer/letters/{$color}/{$letter}/{$total_chars}char-pos{$position}.png";
    }
}
```

#### B. Image Upload Interfaces
- WordPress media library integration
- Bulk upload tools for letter blocks
- Position-specific charm image uploads
- Image optimization and conversion

### 8. WooCommerce Integration

#### A. Add to Cart Functionality
```php
// Handle customized product add to cart
add_action('woocommerce_add_to_cart', 'save_bracelet_customization', 10, 6);

function save_bracelet_customization($cart_item_key, $product_id, $quantity, $variation_id, $variation, $cart_item_data) {
    if (isset($_POST['bracelet_customization'])) {
        $customization = json_decode(stripslashes($_POST['bracelet_customization']), true);
        
        // Save to cart item meta
        WC()->cart->cart_contents[$cart_item_key]['bracelet_customization'] = $customization;
        
        // Calculate custom pricing
        $custom_price = calculate_bracelet_price($customization);
        WC()->cart->cart_contents[$cart_item_key]['custom_price'] = $custom_price;
    }
}
```

#### B. Order Processing
```php
// Save customization data to order
add_action('woocommerce_checkout_create_order_line_item', 'save_order_customization', 10, 4);

function save_order_customization($item, $cart_item_key, $values, $order) {
    if (isset($values['bracelet_customization'])) {
        $item->add_meta_data('_bracelet_customization', $values['bracelet_customization']);
        
        // Save to custom table for easier querying
        global $wpdb;
        $wpdb->insert(
            $wpdb->prefix . 'order_item_customizations',
            [
                'order_item_id' => $item->get_id(),
                'customization_data' => json_encode($values['bracelet_customization'])
            ]
        );
    }
}
```

### 9. Plugin Activation & Sample Data

#### A. Activation Hook
```php
register_activation_hook(__FILE__, 'bracelet_customizer_activate');

function bracelet_customizer_activate() {
    // Create custom tables
    bracelet_customizer_create_tables();
    
    // Create sample products
    bracelet_customizer_create_sample_products();
    
    // Set default settings
    bracelet_customizer_set_default_settings();
    
    // Create upload directories
    bracelet_customizer_create_directories();
}
```

#### B. Sample Product Creation
```php
function bracelet_customizer_create_sample_products() {
    // Create sample bracelet
    $bracelet = new WC_Product_Simple();
    $bracelet->set_name('Bluestone Bracelet');
    $bracelet->set_regular_price(25.00);
    $bracelet->set_manage_stock(false);
    $bracelet->set_status('publish');
    
    $bracelet_id = $bracelet->save();
    
    // Set as bracelet product type
    wp_set_object_terms($bracelet_id, 'standard_bracelet', 'product_type');
    
    // Add bracelet meta
    update_post_meta($bracelet_id, '_bracelet_style_category', 'standard');
    update_post_meta($bracelet_id, '_bracelet_is_bestseller', true);
    
    // Sample gap images
    $gap_images = [];
    for ($i = 2; $i <= 13; $i++) {
        $gap_images[$i] = "https://example.com/bluestone-{$i}char.jpg";
    }
    update_post_meta($bracelet_id, '_bracelet_gap_images', $gap_images);
    
    // Create sample charm
    $charm = new WC_Product_Simple();
    $charm->set_name('Apple Charm');
    $charm->set_regular_price(14.00);
    $charm->set_manage_stock(false);
    $charm->set_status('publish');
    
    $charm_id = $charm->save();
    
    // Set as charm product type
    wp_set_object_terms($charm_id, 'charm', 'product_type');
    
    // Add charm meta
    update_post_meta($charm_id, '_charm_category', 'bestsellers');
    update_post_meta($charm_id, '_charm_is_new', false);
    
    // Sample position images
    $position_images = [];
    for ($i = 1; $i <= 9; $i++) {
        $position_images[$i] = "https://example.com/apple-pos-{$i}.jpg";
    }
    update_post_meta($charm_id, '_charm_position_images', $position_images);
}
```

### 10. Implementation Roadmap

#### Phase 1: Core Plugin Structure
1. Create main plugin file and class structure
2. Implement custom product types
3. Create basic admin settings page
4. Set up REST API endpoints

#### Phase 2: WooCommerce Integration
1. Product meta boxes and fields
2. Cart and checkout integration
3. Order processing and storage
4. Pricing calculations

#### Phase 3: React App Integration
1. Modify React app to accept dynamic data
2. Implement WordPress-React communication
3. Asset management system
4. Testing and debugging

#### Phase 4: Advanced Features
1. Bulk import tools
2. Advanced customization options
3. Performance optimization
4. Documentation and tutorials

### 11. Development Considerations

#### A. Performance
- Cache API responses
- Optimize image loading
- Database query optimization
- React app code splitting

#### B. Security
- Nonce verification for all AJAX calls
- Sanitize and validate all inputs
- Secure file uploads
- Rate limiting for API endpoints

#### C. Extensibility
- Filter hooks for developers
- Action hooks for customization
- Modular architecture
- Third-party integration points

This architecture provides a solid foundation for converting the React bracelet customizer into a full WordPress plugin with WooCommerce integration.