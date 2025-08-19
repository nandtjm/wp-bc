#!/usr/bin/env node

const fs = require('fs');
const path = require('path');
const { execSync } = require('child_process');

/**
 * WordPress Plugin Build Integration Script
 * 
 * This script builds the React app and integrates it with the WordPress plugin
 * by copying the built files to the plugin's assets directory and updating
 * file references for WordPress compatibility.
 */

const REACT_APP_DIR = path.join(__dirname, 'bracelet-customizer');
const PLUGIN_ASSETS_DIR = path.join(__dirname, 'assets');
const BUILD_DIR = path.join(REACT_APP_DIR, 'build');

console.log('🔨 Starting WordPress Plugin Build Integration...\n');

// Step 1: Build the React application
console.log('📦 Building React application...');
try {
  process.chdir(REACT_APP_DIR);
  execSync('npm run build', { stdio: 'inherit' });
  console.log('✅ React build completed successfully\n');
} catch (error) {
  console.error('❌ React build failed:', error.message);
  process.exit(1);
}

// Step 2: Ensure plugin assets directory exists
console.log('📁 Setting up plugin assets directory...');
if (!fs.existsSync(PLUGIN_ASSETS_DIR)) {
  fs.mkdirSync(PLUGIN_ASSETS_DIR, { recursive: true });
}

// Create subdirectories
const assetSubdirs = ['js', 'css', 'media'];
assetSubdirs.forEach(dir => {
  const dirPath = path.join(PLUGIN_ASSETS_DIR, dir);
  if (!fs.existsSync(dirPath)) {
    fs.mkdirSync(dirPath, { recursive: true });
  }
});

console.log('✅ Plugin assets directory ready\n');

// Step 3: Copy built files to plugin assets
console.log('🔄 Copying built files to plugin assets...');

function copyBuildFiles() {
  const buildStaticDir = path.join(BUILD_DIR, 'static');
  
  // Copy CSS files
  const cssDir = path.join(buildStaticDir, 'css');
  if (fs.existsSync(cssDir)) {
    const cssFiles = fs.readdirSync(cssDir);
    cssFiles.forEach(file => {
      if (file.endsWith('.css')) {
        const srcPath = path.join(cssDir, file);
        const destPath = path.join(PLUGIN_ASSETS_DIR, 'css', 'bracelet-customizer.css');
        fs.copyFileSync(srcPath, destPath);
        console.log(`  ✅ Copied CSS: ${file} → bracelet-customizer.css`);
      }
    });
  }
  
  // Copy JS files
  const jsDir = path.join(buildStaticDir, 'js');
  if (fs.existsSync(jsDir)) {
    const jsFiles = fs.readdirSync(jsDir);
    jsFiles.forEach(file => {
      if (file.endsWith('.js') && !file.endsWith('.map')) {
        const srcPath = path.join(jsDir, file);
        const destPath = path.join(PLUGIN_ASSETS_DIR, 'js', 'bracelet-customizer.js');
        fs.copyFileSync(srcPath, destPath);
        console.log(`  ✅ Copied JS: ${file} → bracelet-customizer.js`);
      }
    });
  }
  
  // Copy media files
  const mediaDir = path.join(buildStaticDir, 'media');
  if (fs.existsSync(mediaDir)) {
    const mediaFiles = fs.readdirSync(mediaDir);
    mediaFiles.forEach(file => {
      const srcPath = path.join(mediaDir, file);
      const destPath = path.join(PLUGIN_ASSETS_DIR, 'media', file);
      fs.copyFileSync(srcPath, destPath);
      console.log(`  ✅ Copied media: ${file}`);
    });
  }
}

copyBuildFiles();
console.log('✅ File copying completed\n');

// Step 4: Create WordPress-compatible asset manager file
console.log('🔧 Creating WordPress asset manager...');

const assetManagerContent = `<?php
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
            'checkoutUrl' => function_exists('wc_get_checkout_url') ? wc_get_checkout_url() : ''
        ]);
        
        // Ensure React and ReactDOM are available
        wp_enqueue_script('react', 'https://unpkg.com/react@18/umd/react.production.min.js', [], '18.0.0');
        wp_enqueue_script('react-dom', 'https://unpkg.com/react-dom@18/umd/react-dom.production.min.js', ['react'], '18.0.0');
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
`;

fs.writeFileSync(path.join(__dirname, 'includes', 'class-asset-integration.php'), assetManagerContent);
console.log('✅ WordPress asset manager created\n');

// Step 5: Update the main plugin file to include the asset integration
console.log('🔧 Updating main plugin file...');

const mainPluginFile = path.join(__dirname, 'exp-bracelets-customizer.php');
let mainPluginContent = fs.readFileSync(mainPluginFile, 'utf8');

// Add asset integration include if not already present
if (!mainPluginContent.includes('class-asset-integration.php')) {
  const includeStatement = "require_once BRACELET_CUSTOMIZER_PLUGIN_DIR . 'includes/class-asset-integration.php';";
  const insertAfter = "require_once BRACELET_CUSTOMIZER_PLUGIN_DIR . 'includes/class-asset-manager.php';";
  
  if (mainPluginContent.includes(insertAfter)) {
    mainPluginContent = mainPluginContent.replace(
      insertAfter,
      insertAfter + '\n' + includeStatement
    );
    fs.writeFileSync(mainPluginFile, mainPluginContent);
    console.log('✅ Main plugin file updated with asset integration\n');
  }
}

// Step 6: Create a development watch script
console.log('🔧 Creating development watch script...');

const watchScriptContent = `#!/usr/bin/env node

const chokidar = require('chokidar');
const { execSync } = require('child_process');
const path = require('path');

console.log('👀 Starting development watch mode for React app...');
console.log('📁 Watching: ${path.join(__dirname, 'bracelet-customizer', 'src')}');
console.log('🔄 Auto-building on file changes...\n');

const watcher = chokidar.watch(path.join(__dirname, 'bracelet-customizer', 'src'), {
  ignored: /node_modules/,
  persistent: true
});

let buildTimeout;

function triggerBuild() {
  clearTimeout(buildTimeout);
  buildTimeout = setTimeout(() => {
    console.log('🔄 File change detected, rebuilding...');
    try {
      execSync('node build-integration.js', { stdio: 'inherit' });
      console.log('✅ Rebuild completed!\\n');
    } catch (error) {
      console.error('❌ Rebuild failed:', error.message);
    }
  }, 1000); // Debounce builds by 1 second
}

watcher
  .on('change', (path) => {
    console.log(\`📝 Changed: \${path}\`);
    triggerBuild();
  })
  .on('add', (path) => {
    console.log(\`➕ Added: \${path}\`);
    triggerBuild();
  })
  .on('unlink', (path) => {
    console.log(\`➖ Removed: \${path}\`);
    triggerBuild();
  });

process.on('SIGINT', () => {
  console.log('\\n👋 Stopping watch mode...');
  process.exit(0);
});
`;

fs.writeFileSync(path.join(__dirname, 'watch-dev.js'), watchScriptContent);
fs.chmodSync(path.join(__dirname, 'watch-dev.js'), '755');
console.log('✅ Development watch script created\n');

// Step 7: Update package.json with WordPress-specific scripts
console.log('🔧 Adding WordPress integration scripts...');

const pluginPackageJsonPath = path.join(__dirname, 'package.json');
let pluginPackageJson;

if (fs.existsSync(pluginPackageJsonPath)) {
  pluginPackageJson = JSON.parse(fs.readFileSync(pluginPackageJsonPath, 'utf8'));
} else {
  pluginPackageJson = {
    name: "exp-bracelets-customizer",
    version: "1.0.0",
    description: "WordPress plugin for bracelet customization with React integration",
    scripts: {},
    devDependencies: {}
  };
}

// Add integration scripts
pluginPackageJson.scripts = {
  ...pluginPackageJson.scripts,
  "build": "node build-integration.js",
  "dev": "node watch-dev.js",
  "setup": "cd bracelet-customizer && npm install && cd .. && npm run build"
};

// Add chokidar for watch mode
pluginPackageJson.devDependencies = {
  ...pluginPackageJson.devDependencies,
  "chokidar": "^3.5.3"
};

fs.writeFileSync(pluginPackageJsonPath, JSON.stringify(pluginPackageJson, null, 2));
console.log('✅ Package.json updated with WordPress scripts\n');

console.log('🎉 WordPress Plugin Build Integration Complete!\n');
console.log('📋 Available commands:');
console.log('  npm run setup    - Install dependencies and build');
console.log('  npm run build    - Build React app for WordPress');
console.log('  npm run dev      - Watch mode for development');
console.log('\n✅ Your React app is now integrated with the WordPress plugin!');
console.log('🚀 The customizer will be available through WordPress shortcodes and hooks.');