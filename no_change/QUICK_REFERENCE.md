# Quick Reference Guide - Modern Design System

## 🎨 Color Quick Reference

```css
/* Blue Theme (Primary) */
--primary: #4a90e2
--primary-light: #7eb3f5
--primary-dark: #2e5c8a

/* Pink Theme (Accent) */
--accent: #ff6b9d
--accent-light: #ffb3d9

/* Status Colors */
--success: #2ecc71
--warning: #f39c12
--danger: #e74c3c

/* Gray Scale */
--dark: #1a1a1a (headings)
--gray-600: #666666 (body text)
--gray-400: #b0b0b0 (light text)
--gray-200: #e8e8e8 (borders)
--gray-100: #f5f5f5 (backgrounds)
--white: #ffffff
```

## 📐 Sizing Scale

| Size | Usage |
|------|-------|
| 0.6rem | Small spacing |
| 0.8rem | Standard spacing |
| 1rem | Medium spacing |
| 1.5rem | Large spacing |
| 2rem | Section spacing |

## 🔲 Border Radius

| Radius | Usage |
|--------|-------|
| 0.5rem | Inputs, small elements |
| 0.8rem | Cards, buttons |
| 1rem | Large containers |
| 50% | Circular avatars |

## 💫 Shadow System

```css
/* Light - Hover effects */
box-shadow: 0 2px 8px rgba(0,0,0,0.08)

/* Medium - Cards */
box-shadow: 0 4px 12px rgba(0,0,0,0.1)

/* Large - Elevated elements */
box-shadow: 0 8px 24px rgba(0,0,0,0.12)

/* Focus */
box-shadow: 0 0 0 3px rgba(74, 144, 226, 0.1)
```

## ⚡ Animation Speeds

| Speed | Usage |
|-------|-------|
| 0.15s | Quick feedback |
| 0.3s | Standard transition |
| 0.6s | Scroll animation |
| 1s | Slow entrance |

## 📱 Breakpoints

```css
1200px+  /* Desktop */
991-1200px /* Laptop */
768-991px /* Tablet */
600-768px /* Mobile landscape */
≤600px   /* Mobile portrait */
≤450px   /* Small mobile */
```

## 🎯 Component Usage

### Button Classes
```html
<button class="btn">Primary Action</button>
<button class="btn delete-btn">Delete</button>
<button class="btn option-btn">Alternative</button>
<button class="modern-icon-btn"><i class="fas fa-heart"></i></button>
<button class="cart-btn">Add to Cart</button>
```

### Typography
```html
<h1 class="title">Page Title</h1>
<h3>Heading</h3>
<p>Body text</p>
<span class="label">Label</span>
```

### Product Card Structure
```html
<div class="box">
  <img src="image.jpg" alt="Product">
  <div class="name">Product Name</div>
  <div class="price">Price</div>
  <div class="button-wrapper">
    <button class="modern-icon-btn heart">
      <i class="fas fa-heart"></i>
    </button>
    <button class="cart-btn">Add to Cart</button>
  </div>
</div>
```

## 🎨 Gradient Usage

```css
/* Primary Gradient */
background: linear-gradient(135deg, #4a90e2 0%, #2e5c8a 100%)

/* Accent Gradient */
background: linear-gradient(135deg, #ff6b9d 0%, #ffb3d9 100%)

/* Dark Gradient */
background: linear-gradient(135deg, #1a1a1a 0%, #2d2d2d 100%)
```

## 🖱️ Hover Effects Pattern

```css
.element:hover {
   background: linear-gradient(135deg, PRIMARY 0%, DARK 100%);
   transform: translateY(-2px);
   box-shadow: var(--box-shadow-lg);
}
```

## 📦 CSS Classes Organization

### Layout
- `.container` - Max-width wrapper
- `.flex` - Flexbox container
- `.box-container` - Grid container
- `.row` - Flex row

### States
- `.active` - Active state
- `.disabled` - Disabled state
- `.hover` - Hover state (can use CSS)

### Components
- `.header` - Main header
- `.footer` - Main footer
- `.box` - Card/item container
- `.button-wrapper` - Button group

## 🎬 Animation Classes

```css
@keyframes fadeIn { /* Fade in animation */ }
@keyframes slideDown { /* Slide down animation */ }
@keyframes ripple-animation { /* Ripple effect */ }
@keyframes slideshow { /* Image slideshow */ }
```

## ♿ Accessibility Notes

- Color contrast: WCAG AA compliant
- Focus states: Clearly visible
- Touch targets: Minimum 48x48px
- Text size: Readable at all breakpoints

## 🚀 Performance Tips

1. Use CSS variables for consistency
2. Leverage hardware acceleration (transform, opacity)
3. Batch DOM changes
4. Use Intersection Observer for lazy loading
5. Debounce scroll/resize handlers
6. Optimize images for mobile

## 🔧 Common Customizations

### Change Primary Color
```css
:root {
   --primary: #YOUR_COLOR;
   --primary-dark: #YOUR_DARK_COLOR;
}
```

### Increase Border Radius
```css
/* Update: 0.8rem to 1rem throughout */
```

### Adjust Font Size
```css
html {
   font-size: 65%; /* Increase or decrease */
}
```

### Modify Shadows
```css
:root {
   --box-shadow: 0 2px 12px rgba(0,0,0,0.12);
}
```

## 📋 File Organization

```
test_store/
├── css/
│   ├── components.css (Base styles)
│   ├── style.css (Page-specific)
│   └── index.css (Landing page)
├── js/
│   └── script.js (Interactions)
├── header.php (Navigation)
├── footer.php (Footer)
└── REDESIGN_DOCUMENTATION.md
```

## 💾 Variable Reference Guide

### CSS Root Variables
```css
:root {
   /* Colors */
   --primary: #4a90e2
   --primary-dark: #2e5c8a
   --accent: #ff6b9d
   --dark: #1a1a1a
   --white: #ffffff
   
   /* Spacing */
   --border: .1rem solid #e8e8e8
   
   /* Effects */
   --box-shadow: 0 2px 8px rgba(0,0,0,0.08)
   --box-shadow-lg: 0 8px 24px rgba(0,0,0,0.12)
   --transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1)
}
```

## 🎓 Design Principles Used

1. **Minimalism** - Clean, uncluttered design
2. **Consistency** - Unified visual language
3. **Hierarchy** - Clear information structure
4. **Accessibility** - Inclusive design
5. **Performance** - Smooth 60fps animations
6. **Responsiveness** - Mobile-first approach
7. **User Feedback** - Clear hover/focus states
8. **Brand Identity** - Cohesive color system

---

**Last Updated**: November 13, 2025  
**Version**: 1.0 Modern Redesign  
**Compatibility**: All modern browsers, IE 11 partial support
