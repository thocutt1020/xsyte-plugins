# Use the Foundation grid for layout — don't hand-roll CSS grid

xsyte is built on **Zurb Foundation 6**, and Foundation's grid CSS is loaded
globally on every page (custom pages included). Use it for all multi-column
layout. Do **not** write custom `display:grid` / `display:flex` scaffolding for
column layout — it duplicates what the platform already ships and, more
importantly, it's much harder for the league owner to edit later. A committee
member can change `medium-8` to `medium-6` in the WYSIWYG; they can't safely
edit a bespoke `grid-template-columns` media query.

**Rule of thumb:** Foundation classes build the *scaffold* (rows, columns, how
many cards per row, responsive widths). Your own `<style>` block is only for the
*skin* (colours, cards, buttons, badges, spacing, grunge). Keep the two
separate.

Confirmed on lilydalerats.hockeysyte.com (July 2026): that build ships the
classic **float grid** (`.row` / `.columns`), and columns sit side-by-side as
expected. Assume float grid by default; fall back to XY grid only if a page
stacks (see detection below).

## Classic float grid (default on most xsyte builds)

Row with two columns — widths are out of 12, and total 12 per row:

```html
<div class="row">
  <div class="small-12 medium-4 columns">…left rail…</div>
  <div class="small-12 medium-8 columns">…main content…</div>
</div>
```

- `small-*` applies on phones, `medium-*` on tablets+, `large-*` on desktop.
  A cell keeps the smallest breakpoint's width until a larger one overrides it,
  so `small-12 medium-4` = full width on phones, one-third from tablet up.
- To retune the split, the editor just changes the numbers (they must still sum
  to 12 across the row). Leave a short HTML comment next to each column noting
  which class controls its width — it makes self-service edits obvious.

### Block grid for equal cards (divisions, sponsors, tiles)

Foundation's block grid lays out N equal cards per row and wraps automatically.
Parent sets how many per breakpoint; each child is a `column`:

```html
<div class="row small-up-1 medium-up-2 large-up-3">
  <div class="column column-block"> …card… </div>
  <div class="column column-block"> …card… </div>
</div>
```

- `small-up-1 medium-up-2 large-up-3` = 1 across on phones, 2 on tablets, 3 on
  desktop. Change these to restyle density (e.g. `large-up-4` for four across).
- `column-block` adds the bottom gutter so wrapped rows don't collide.

### Equal-height cards in the float block grid

The float block grid uses `float`, which does **not** equalise card heights, so
a taller card leaves ragged bottoms. Fix it with a tiny skin override (scope it
to your own wrapper class, e.g. `.rats-cards`, so it can't affect other rows):

```css
.rats-cards{display:flex;flex-wrap:wrap;}
.rats-cards > .columns,
.rats-cards > .column{display:flex;float:none;}
.rats-cards > .columns > .card,
.rats-cards > .column > .card{width:100%;}
```

The percentage widths from Foundation's `*-up-*` rules still apply as the flex
basis, so you keep Foundation's responsive column counts and gain equal heights.
(XY-grid cells already stretch to equal height — this override is only needed on
the float grid.)

## Newer XY grid (some builds)

Foundation 6.4+ can ship the XY grid instead of (or alongside) the float grid.
Equivalent markup:

```html
<!-- two columns -->
<div class="grid-x grid-margin-x">
  <div class="cell small-12 medium-4">…left rail…</div>
  <div class="cell small-12 medium-8">…main content…</div>
</div>

<!-- equal cards, N per row -->
<div class="grid-x grid-margin-x small-up-1 medium-up-2 large-up-3">
  <div class="cell"> …card… </div>
</div>
```

XY cells stretch to equal height automatically, so no flex override is needed.

### Detecting which grid a build uses

If a `.row` / `.columns` layout renders as a single stacked column instead of
side-by-side, the build is XY-only — swap `.row`→`.grid-x grid-margin-x`,
`.columns`→`.cell`, and add breakpoint width classes to the cells.

## Do not re-import Foundation

Foundation's CSS/JS is already global on the xsyte shell. Never add a Foundation
`<link>` or `<script>` — a second copy risks version drift and wasted bytes.
Same rule as Font Awesome and flag-icons (see SKILL.md "Available globally").

## Preview gotcha

The public Foundation CDN default build (`foundation.min.css` on cdnjs) ships
**only the XY grid**. If you preview a float-grid page locally by loading that
CDN file, every `.row` / `.columns` layout will stack into one column and look
broken — even though it's correct for the live xsyte. To preview a float-grid
page faithfully, either shim the handful of float classes you actually use
(`.row`, `.columns`, `.small-/medium-/large-N`, `.*-up-N`, `.column-block`) or
just test on the live site. Don't "fix" a layout based on that misleading
stacked preview.
