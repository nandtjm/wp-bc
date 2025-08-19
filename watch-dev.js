#!/usr/bin/env node

const chokidar = require('chokidar');
const { execSync } = require('child_process');
const path = require('path');

console.log('👀 Starting development watch mode for React app...');
console.log('📁 Watching: /Users/nandlal/Local Sites/braclet-customizer/app/public/wp-content/plugins/exp-bracelets-customizer/bracelet-customizer/src');
console.log('🔄 Auto-building on file changes...
');

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
      console.log('✅ Rebuild completed!\n');
    } catch (error) {
      console.error('❌ Rebuild failed:', error.message);
    }
  }, 1000); // Debounce builds by 1 second
}

watcher
  .on('change', (path) => {
    console.log(`📝 Changed: ${path}`);
    triggerBuild();
  })
  .on('add', (path) => {
    console.log(`➕ Added: ${path}`);
    triggerBuild();
  })
  .on('unlink', (path) => {
    console.log(`➖ Removed: ${path}`);
    triggerBuild();
  });

process.on('SIGINT', () => {
  console.log('\n👋 Stopping watch mode...');
  process.exit(0);
});
