# Bracelet Customizer WordPress Plugin

A comprehensive WordPress plugin that integrates a React-based bracelet customization interface with WooCommerce for e-commerce functionality.

## Features

- **React-powered customizer**: Modern, interactive bracelet customization interface
- **WooCommerce integration**: Seamless product and cart management
- **Real-time preview**: Live bracelet preview with letter blocks and charms
- **WordPress REST API**: Custom endpoints for data management
- **Responsive design**: Mobile-friendly customization experience
- **Asset management**: Optimized image handling and delivery

## Installation

### Prerequisites
- WordPress 5.0+
- WooCommerce 4.0+
- PHP 7.4+
- Node.js 14+ (for development)

### Setup

1. **Install the plugin**:
   ```bash
   # Upload plugin files to wp-content/plugins/exp-bracelets-customizer/
   ```

2. **Install dependencies and build**:
   ```bash
   cd wp-content/plugins/exp-bracelets-customizer/
   npm run setup
   ```

3. **Activate the plugin** in WordPress admin

## Development

### Available Commands

```bash
# Plugin root directory
npm run setup           # Install dependencies and build everything
npm run build           # Build React app for WordPress
npm run dev             # Development watch mode
npm install-deps        # Install all dependencies
npm run clean           # Clean built assets

# React app directory
cd bracelet-customizer/
npm start               # Development server (localhost:3000)
npm run build           # Standard React build
npm run build:wordpress # Build with WordPress integration
npm test                # Run tests
```

### Development Workflow

1. **Start development**:
   ```bash
   npm run dev
   ```
   This watches for changes in the React app and automatically rebuilds for WordPress.

2. **Make changes** to React components in `bracelet-customizer/src/`

3. **Assets are automatically built** and copied to `assets/` directory

4. **Test in WordPress** - assets are automatically enqueued

### React App Integration

The React app is integrated with WordPress through:

- **Global namespace**: `window.BraceletCustomizer`
- **WordPress hooks**: REST API integration
- **Asset management**: Proper WordPress enqueuing
- **WooCommerce integration**: Cart and checkout functionality

### File Structure

```
exp-bracelets-customizer/
├── admin/                  # WordPress admin functionality
├── assets/                 # Built assets (auto-generated)
│   ├── css/
│   ├── js/
│   ├── media/
│   └── images/
├── bracelet-customizer/    # React application source
│   ├── src/
│   ├── public/
│   └── package.json
├── includes/              # Core plugin classes
├── public/                # Public-facing functionality
├── templates/             # WordPress templates
├── build-integration.js   # Build system
├── watch-dev.js          # Development watcher
└── exp-bracelets-customizer.php
```

## Usage

### Shortcodes

```php
[bracelet_customizer]              # Full customizer interface
[bracelet_customize_button]        # Customize button only
```

### Programmatic Usage

```php
// Initialize customizer in template
<?php
if (function_exists('bracelet_customizer_init')) {
    bracelet_customizer_init();
}
?>

// Or use the shortcode
<?php echo do_shortcode('[bracelet_customizer]'); ?>
```

### JavaScript Integration

```javascript
// Initialize customizer manually
if (window.BraceletCustomizer) {
    window.BraceletCustomizer.init('my-container-id');
}

// Render with props
window.BraceletCustomizer.render('container-id', {
    productId: 123,
    initialStyle: 'gold-plated'
});
```

## Configuration

### Plugin Settings

Access plugin settings in WordPress admin under **WooCommerce > Bracelet Customizer**.

### REST API Endpoints

- `GET /wp-json/bracelet-customizer/v1/bracelets` - Available bracelet styles
- `GET /wp-json/bracelet-customizer/v1/charms` - Available charms
- `POST /wp-json/bracelet-customizer/v1/customization` - Save customization
- `GET /wp-json/bracelet-customizer/v1/settings` - Plugin settings

### Hooks and Filters

```php
// Modify available bracelet styles
add_filter('bracelet_customizer_bracelets', 'my_custom_bracelets');

// Customize modal behavior
add_action('bracelet_customizer_modal_open', 'my_modal_handler');

// Filter charm categories
add_filter('bracelet_customizer_charm_categories', 'my_charm_categories');
```

## Troubleshooting

### Common Issues

1. **Assets not loading**:
   ```bash
   npm run build  # Rebuild assets
   ```

2. **Development changes not reflecting**:
   ```bash
   npm run dev    # Start watch mode
   ```

3. **Plugin activation errors**:
   - Check PHP version (7.4+ required)
   - Ensure WooCommerce is installed
   - Check file permissions

### Debug Mode

Enable debug mode in wp-config.php:
```php
define('WP_DEBUG', true);
define('BRACELET_CUSTOMIZER_DEBUG', true);
```

## Contributing

1. **Fork the repository**
2. **Create feature branch**: `git checkout -b feature/my-feature`
3. **Run development setup**: `npm run setup`
4. **Make changes and test**
5. **Build for production**: `npm run build`
6. **Submit pull request**

## Support

- **Documentation**: See inline code comments and WordPress Codex
- **Issues**: Report bugs or feature requests
- **Development**: Follow WordPress coding standards

## License

GPL v2 or later - see LICENSE file for details.

---

**Author**: Nand Lal  
**Plugin URI**: https://fiverr.com/expert2014  
**Version**: 1.0.0