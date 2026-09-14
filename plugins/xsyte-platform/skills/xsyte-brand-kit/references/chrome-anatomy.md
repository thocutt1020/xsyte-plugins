# Header, nav, drawer, footer

## Header HTML, in order

1. **Font `<link>` tags.** Never `@import` — the platform prepends its own base
   CSS inside the same `<style>` as your custom CSS, and `@import` is only
   valid at the very top of a sheet. Do not re-import Font Awesome or
   flag-icons; both are already global.
2. **The drawer toggle checkbox**, first sibling. The CSS drives both the
   burger and the drawer off `:checked ~ …`, so its position is load-bearing.
3. **The header bar** — brand mark, nav, actions, burger.
4. **The drawer** — pure CSS, no script.
5. **One script**, described below.

## The brand mark

If the design has a badge that overhangs the bar and overlaps the hero, it
needs a rule for pages that have no hero, or it lands on the first heading:

```css
.off-canvas-content:not(:has(.your-hero)) .header__badge{
  top:50%; transform:translateY(-50%) scale(.6);
}
@supports not selector(:has(*)){
  #full-width-content{ padding-top:120px; }
  .your-hero:first-child{ margin-top:-120px; }
}
```

## The script's jobs

Keep it to what genuinely cannot be done in CSS:

- **Scroll state.** Add a marker class immediately so a CSS
  `animation-timeline: scroll()` fallback switches off, then toggle a compact
  class past a threshold. If the script is ever stripped, browsers with scroll
  timelines still get the transition and older ones keep the tall header.
- **Injecting items into the platform's utility bar.** That bar is rendered by
  a Twig template, so its list items cannot be added from a Brand Kit field or
  from CSS — they have to be inserted. Call the injector immediately *and* on
  `DOMContentLoaded`, with a guard class so the second call is a no-op.
- **Drawer housekeeping** — close on link click and on Escape.

Anything reacting to a specific page's widgets belongs in a guarded block:
**the Brand Kit header renders above the page content, so its script runs
before the page's own elements exist.** Wire up on `DOMContentLoaded` as well
as immediately, and guard against doing the work twice.

## Footer

The platform footer is usually hidden by the global sheet, so the drop-in
replaces it entirely. Any legally required line — a "not sanctioned by" notice,
a members-only disclaimer — lives here and must not be deleted. Say so in the
file's header comment, because the next person to edit it will not know.

## Brand Kit fields take raw HTML

`global_xss_filtering` is off for these fields, so the editor constraints that
apply to custom pages — no raw form controls, no bare tag names in prose — do
**not** apply here. Header and footer drop-ins can contain anything.
