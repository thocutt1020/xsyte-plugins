# Full-bleed page CSS overrides

> **Check the Standalone page type first.** Setting the page's type to *Standalone* in the admin strips all chrome natively — no CSS needed. Use the overrides below only when the page must stay type Normal/Hero, or on templates without standalone support.

When a custom xsyte page should take over the entire viewport — no header, no nav, no sponsor strip, no footer, no breadcrumb — drop this CSS at the top of the page's `<style>` block.

This is the canonical pattern from PowerPlay 209 and MIHWA. It targets the specific IDs and classes from the xsyte page shell template.

```css
/* Hide xsyte chrome */
.title-bar.show-for-small-only,
.top-bar.show-for-medium,
#header-hero,
#main-nav,
#off-canvas-mobile-menu {
  display: none !important;
}

/* Hide footer and sponsors strip */
#footer,
#sponsors-large {
  display: none !important;
}

/* Make content area full-width — break out of Foundation grid */
#full-width-content.row {
  max-width: 100% !important;
  width: 100% !important;
  margin: 0 !important;
  padding: 0 !important;
}

#full-width-content > .large-12.columns {
  padding: 0 !important;
  margin: 0 !important;
}

/* Page-level body background — pick whatever fits the design */
body {
  background: #06080d !important;
  margin: 0 !important;
}

/* Hide page title bar and breadcrumbs */
.page-title-bar,
.breadcrumbs {
  display: none !important;
}
```

## What each rule does

- `.title-bar.show-for-small-only` — mobile top bar
- `.top-bar.show-for-medium` — desktop nav bar
- `#header-hero` — the big banner area at the top of standard xsyte pages
- `#main-nav` — primary nav menu
- `#off-canvas-mobile-menu` — slide-out mobile nav
- `#footer` — site-wide footer
- `#sponsors-large` — the sponsors strip that runs above the footer
- `#full-width-content` overrides — Foundation 5's `.row` and `.large-12.columns` add max-width and padding by default; we strip them so content can extend to viewport edges
- `body` background — the xsyte default body colour will show through anywhere your content doesn't cover; set it to match your hero / theme
- `.page-title-bar`, `.breadcrumbs` — the strip that says "You are here: Home > [page title]" — usually unwanted on full-bleed designs

## When NOT to use this

If the custom page should sit *inside* the normal xsyte chrome (header visible, footer visible, nav visible), do NOT include these rules. The page will then render as a content block in the standard template, which is what you want for in-shell pages like an "About us" page or a small contact form embedded in the regular site flow.

## Scoping the page styles

When using full-bleed mode, prefix all your custom CSS classes with a unique namespace (e.g. `.mh-` for MIHWA, `.pp-` for PowerPlay 209). This prevents your styles from leaking into other parts of the xsyte if any chrome elements *do* end up rendering (e.g. dynamic admin overlays). PowerPlay 209 uses `.pp209` as a wrapper class; MIHWA uses `.mh` for the same purpose.

## Body background gotcha

The `body` background rule uses `!important` to override the xsyte's default body background. Without it, the original site background shows through any gaps in your design. Pick a colour that matches your hero or the dominant section background so the page feels seamless.
