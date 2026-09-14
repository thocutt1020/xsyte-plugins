# Neutralising the xsyte / Foundation chrome

Every rule here fixes a specific behaviour. Read the reason before deleting one.

## Sticky positioning

```css
/* Foundation sets .off-canvas-wrapper{overflow:hidden}, which silently kills
   position:sticky for everything inside it. A non-clipping ancestor chain is
   the whole requirement for sticky, and this one rule breaks it site-wide. */
.off-canvas-wrapper,
.off-canvas-content{ overflow:visible !important; }
```

Then put `position:sticky` on the platform's own header wrapper — commonly
`#header-hero` — not on your header element. A sticky element is clipped by its
own parent's box. If your header sits inside a wrapper that is exactly as tall
as the header, it unsticks the instant it starts to move. The wrapper's parent
is the full-page content element, which is the right box.

## The header wrapper's own styling

```css
/* Strip the platform chrome off the element we are about to sticky. */
#header-hero,
#header-hero.background-org-dark-color{
  background:none !important; padding:0 !important; margin:0 !important;
  overflow:visible !important;
}
```

## Retiring the platform nav

```css
/* We ship our own nav and our own drawer. */
#main-nav,
.title-bar,
#off-canvas-mobile-menu,
.off-canvas.position-left,
.mobile-nav{ display:none !important; }
```

**Do not hide the utility bar** if it carries Login / My Account / Admin /
Logout. Those are Twig-conditional and cannot be rebuilt in a static Brand Kit
field. Restyle it instead and treat it as the site's utility strip.

## Dropdowns and overlays

```css
/* Inside the header's stacking context a dropdown competes with page content
   on its own z-index, not the header's. At a low value the page wins the hit
   test and the menu snaps shut the moment the pointer crosses it. */
.your-dropdown{ z-index:1000; }

/* An opacity:0 fixed inset:0 overlay is a live click-catcher over the whole
   site. Hidden means pointer-events:none as well. */
.your-drawer{ opacity:0; pointer-events:none; }
.your-drawer.is-open{ opacity:1; pointer-events:auto; }

/* visibility is INHERITED. A descendant that re-asserts `visible` un-hides
   itself inside a closed parent. Nested panels use inherit. */
.your-drawer .submenu{ visibility:inherit; }
```

Never transition `visibility` on a panel that must be clickable immediately —
visibility steps rather than interpolating, so a `.16s` transition leaves the
panel un-hittable for its whole fade-in. Instant on open, delayed on close.

## Foundation callouts and buttons on platform pages

Platform pages (event detail, news, account) render Foundation callouts and
buttons inside your type system. Left alone they look like a different website
bolted on below the header. Restyle them — but wrap the *typography* defaults
in `:where()`, or a callout heading will out-specify your own heading classes.
See `specificity-doctrine.md`.

## Images

Anywhere CSS sets a width on an image that also carries `width`/`height`
attributes, set `height:auto`. Without it the attribute height survives and the
image renders as a smear. This is invisible at desktop widths and obvious at
390px — measure, do not eyeball.
