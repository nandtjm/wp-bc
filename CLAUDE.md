# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## Project Overview

This is a React-based Bracelet Customizer application that recreates the functionality of the Little Words Project customizer (https://www.littlewordsproject.com/products/custom-bluestone). The app allows users to customize bracelets through a 3-step process with real-time visual feedback.

## Architecture & Key Components

### Core Layout Structure
- **Two-column layout**: Left side for live preview, right side for customization options
- **Step-based navigation**: 3-step process (Design → Word → Charms) with progress indicator
- **Real-time preview**: Dynamic image layering system using transparent PNGs

### Component Hierarchy
```
App
├── CustomizerLayout
│   ├── PreviewPanel (Left Column)
│   │   ├── BraceletPreview
│   │   └── DragDropOverlay
│   └── OptionPanel (Right Column)
│       ├── StepNavigation
│       ├── DesignStep
│       ├── WordStep
│       └── CharmsStep
```

### Image Layering System
The bracelet preview uses a layered approach:
1. **Base bracelet image** (background)
2. **Letter blocks layer** (positioned dynamically based on word length)
3. **Charms layer** (positioned at highlighted spots)
4. **Additional accents** (gold spacers, decorative elements)

### State Management
- **Base product selection**: Different bracelet styles (Gold Plated, Bluestone, Amethyst Dreams, etc.)
- **Text customization**: User input with validation (13 chars max, letters/numbers/symbols only)
- **Letter color options**: White, Pink, Black, Gold (+$15)
- **Charm selection**: Optional add-ons with individual pricing
- **Size selection**: XS, S/M, M/L, L/XL

## Development Commands

Currently using hardcoded data. Future implementation will integrate with WooCommerce API.

### Initial Setup
```bash
npx create-react-app bracelet-customizer
cd bracelet-customizer
npm install
```

### Development
```bash
npm start          # Run development server on localhost:3000
npm run build      # Create production build
npm test           # Run test suite
npm run lint       # Check code quality (if configured)
```

## Key Features to Implement

### Step 1: Design Selection
- Grid of bracelet style options with thumbnails
- "BEST SELLER" badges on popular items
- Style categories: Standard, Special Collections
- Real-time preview update when style is selected

### Step 2: Word Customization
- Text input field with character counter (13 max)
- Letter color selector (color swatches)
- Trending words suggestions ("LET THEM", "STRENGTH", "YOU GOT THIS")
- Live preview of letter blocks on bracelet
- Character validation (letters, numbers, basic symbols only)

### Step 3: Charms Selection
- Grid layout of available charms with "NEW" badges
- Category filtering (All, Bestsellers, New Drops & Favs, By Vibe)
- Search functionality
- Drag-and-drop interaction for charm placement
- Individual charm pricing display
- "Your Charms" summary section

### Final Review Screen
- Complete customization summary
- Size selection (XS, S/M, M/L, L/XL)
- Pricing breakdown
- Add to cart functionality

## Technical Implementation Notes

### Image Handling
- Store base bracelet images in `/public/images/bracelets/`
- Letter block images in `/public/images/letters/`
- Charm images in `/public/images/charms/`
- Use CSS positioning for dynamic placement
- Implement lazy loading for performance

### Responsive Design
- Mobile-first approach
- Stack layout on mobile (preview on top, options below)
- Touch-friendly interactions for drag-and-drop
- Responsive grid layouts for product selections

### Data Structure (Hardcoded Phase)
```javascript
const braceletStyles = [
  {
    id: 'gold-plated',
    name: 'Gold Plated',
    image: '/images/bracelets/gold-plated.png',
    price: 0,
    isBestSeller: true
  },
  // ... more styles
];

const charms = [
  {
    id: 'teacher',
    name: '#1 Teacher',
    image: '/images/charms/teacher.png',
    price: 14,
    isNew: true,
    category: 'bestsellers'
  },
  // ... more charms
];
```

### Validation Rules
- Word input: 13 characters maximum, minimum 2 letters
- Allowed characters: A-Z, a-z, 0-9, basic symbols (., !, ?, &, +)
- Charm limit: No specific limit mentioned in reference
- Size selection: Required before adding to cart

## Future API Integration Points

When moving from hardcoded to API-driven:
- Replace hardcoded arrays with API calls to WooCommerce
- Implement product category fetching for charms
- Add real-time pricing calculations
- Integrate with cart/checkout system
- Add inventory management for charms and styles

## Performance Considerations

- Preload critical bracelet images
- Implement virtualization for large charm lists
- Use React.memo for expensive renders
- Debounce text input changes
- Optimize image formats (WebP with PNG fallback)

## Testing Strategy

- Unit tests for validation functions
- Component testing for user interactions
- Visual regression testing for preview accuracy
- Mobile responsiveness testing
- Performance testing with large charm inventories