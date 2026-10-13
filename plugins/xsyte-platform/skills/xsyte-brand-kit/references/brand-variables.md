# Working with the Brand Kit's own settings

A Code Mode build does not replace the Brand Kit's Visual settings — it sits on
top of them. The platform still emits the club's colours, fonts, logo and
background on every page, and the build should *use* them rather than hard-code
the same values. That way the club can recolour or re-font the site from the
pickers later without anyone touching the code.

## What the platform emits, in order

`Brand_kit_model::render_header()` prints this above the page content:

```
<link …fonts.googleapis.com…>      heading + body font from the Fonts tab
<style>
  Brand Kit Base                  .bk-header-wrap / .bk-footer-wrap scaffolding
  Brand Kit Fonts                 body{font-family:<body>}  h1–h6,.fancy{font-family:<heading>}
  Brand Kit Variables             :root{ --bk-* }  (table below)
  Brand Kit Background            body background image + tint — conditional, see below
  Header CSS                      your Code Mode header CSS
  Global CSS Overrides            your Code Mode global CSS
</style>
Header HTML
```

Everything the platform adds comes **before** your CSS and uses bare element
selectors, so any rule you write wins without `!important`.

## The variables

| Variable | Value |
|---|---|
| `--bk-primary` | Primary colour (nav, buttons, footer) |
| `--bk-secondary` | Secondary colour (top bar, links, sub-nav) |
| `--bk-accent` | Accent colour (highlights, effects) |
| `--bk-background` | Page background colour |
| `--bk-text` | Text colour |
| `--bk-primary-rgb` … `--bk-text-rgb` | The same colours as `r, g, b` for `rgba(var(--bk-primary-rgb), .6)` |
| `--bk-font-heading` | `'Font Name', sans-serif` — the Fonts tab heading font |
| `--bk-font-body` | `'Font Name', sans-serif` — the Fonts tab body font |
| `--bk-logo` | `url('…')` of the Brand Kit logo, or `none` — ready for `background-image` |

Map the design's tokens onto these in section 0 of the stylesheet, so
everything downstream follows the club's choices:

```css
:root{
  --xx-brand:      var(--bk-primary);
  --xx-brand-rgb:  var(--bk-primary-rgb);
  --xx-ink-on-brand: #fff;              /* not a picker value — keep as a token */
  --xx-highlight:  var(--bk-accent);
  --xx-font-display: var(--bk-font-heading);
  --xx-font-body:    var(--bk-font-body);
}
```

Only alias what the club should control. A colour that is part of the design's
structure (a neutral surface, a shadow, a dark-band ink) stays a fixed token.
If the design's palette does not line up with five picker slots, use the
pickers for the brand colours and fixed tokens for the rest — and set the
Visual Mode pickers to the design's values before handing over, so the
variables start out matching the design.

## Fonts

- Set the design's fonts on the **Fonts tab** (about 150 Google fonts, with a
  live heading/body preview) rather than hard-coding them. The platform then
  loads them and applies them site-wide, and `var(--bk-font-heading)` /
  `var(--bk-font-body)` carry them into your CSS.
- Only add your own `<link>` in Header HTML for a font the Fonts tab does not
  offer, or a third face (a mono, a script accent). The `<link>`-not-`@import`
  rule still applies.
- Do not re-declare `body{font-family}` with a hard-coded name unless the
  design truly fixes it — that silently disconnects the Fonts tab.

## Background

The Visual Mode background image and tint are added automatically when **no
header template is selected** (Code Mode always qualifies) — *unless* your
Header CSS or Global CSS styles the `html` or `body` background itself. The
check is a simple scan for an `html`/`body` rule containing `background`
(comments ignored).

- Want the club's picker background? Leave `body` background out of your CSS.
- The design fixes the background? Set it on `body` and the picker background
  steps aside.

## How saves treat Code Mode

- **Saving Code Mode** stores the code exactly as written and clears the
  selected header and footer templates.
- **Saving Visual Mode afterwards** updates colours, fonts, logo and background
  but never touches the code; the kit stays in Code Mode.
- **Picking a header template** (or a *different* footer template) and saving
  is the only thing that replaces Code Mode code. The editor asks first.
  Tell the club to "Save as Version" before experimenting with templates.
- The **Fonts tab** saves only the two fonts.

## The Brand Kit owns the header

While a Brand Kit is active, the Organization settings page's *Custom Header*
field is locked and never saved. To go back to the Organization header, use
**Delete Brand Kit** (bottom of Visual Mode) — it saves a backup version
first, and choosing that version from the Version menu restores the kit.

## Visual Mode alone may be enough

Before building custom chrome, check whether a header template does the job.
**Basic** is just the logo centred on the background (or the club name in the
heading font when there is no logo); Compact / Standard / Hero sets the logo
size. If the brief is "logo, colours, fonts, background", that is a Visual
Mode setup, not a Code Mode build.
