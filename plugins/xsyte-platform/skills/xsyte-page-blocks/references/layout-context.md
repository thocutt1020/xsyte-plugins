# Layout context: component vs standalone (ask this first)

**Decide this before writing any layout CSS.** xsyte custom pages are used two
very different ways, and the layout approach is opposite for each. Getting it
wrong is the #1 cause of "it slipped under the other content" and "it overflowed
its box."

## The two contexts

### 1. Layout-component (the common case)

xsyte has a **Layout Builder**: the page is built from rows, each row split into
columns with a **span** out of 12 (e.g. a `9 / 3` row, a `6 / 6` row, a `12`
row). Blocks are dropped into those columns — native ones (`Page`, `News`,
`Seasons`, `Standings`, `Sponsors`, `Video`, `Calendar`, `Ad`, …). A custom page
you author goes into a **`Page` block**, which sits in one of those already-sized
columns.

So the layout engine has *already* set the width. Your content is a **component
inside a pre-sized box**. It must be **size-agnostic**:

- Fill the block: `width:100%`, no `max-width`, no fixed `px` widths on the outer
  container.
- **Do not add your own `.row`/`.columns`** (or `grid-x`/`cell`). The engine owns
  the columns. A second column system inside a block fights it, and two loose
  blocks with fixed widths just stack ("slip under") instead of sitting side by
  side — because side-by-side is achieved by putting each in a *different Layout
  Builder column*, not by styling.
- Reflow to the **block's** width, not the screen. Use container-driven CSS:
  - `display:flex; flex-wrap:wrap;` with `flex:` bases for a rail-plus-body split
    (wraps to stacked when the block is narrow).
  - `display:grid; grid-template-columns:repeat(auto-fit,minmax(230px,1fr));` for
    card rows (fits 1..N across based on block width).
  - Avoid viewport media queries (`@media (max-width…)`) and Foundation's
    `medium-*`/`*-up-*` classes for internal layout — those key off the **screen**,
    so a widget in a narrow span-3 block on a wide monitor still thinks it's
    "desktop" and overflows. Prefer intrinsic sizing / `flex-wrap` / `auto-fit`;
    reach for `@container` queries if you need explicit breakpoints.
- Don't use the full-bleed `100vw` break-out (see `full-bleed-css.md`) — it spills
  outside the block. Full-bleed is only for standalone takeover pages.

To place two components side by side, the user drops each into its own Layout
Builder column (e.g. registrations in Col 1 span-8, divisions widget in Col 2
span-4). You don't build that split — you just make each widget fill its column.

### 2. Standalone page (owns its layout)

The page is its own URL / occupies the whole content area, or is a full-viewport
takeover (sponsor/signup landing pages like MIHWA, PowerPlay 209). Here **you own
the layout**: use the Foundation grid for real column splits (see
`foundation-grid.md`), and use the full-bleed CSS if it should cover the viewport.

## Ask or detect

If the request doesn't make it obvious, **ask** (AskUserQuestion):

> "Will this be dropped into a Layout Builder block (sized by the page layout), or
> is it a standalone page / full-width takeover?"

Detect without asking when signals are clear:

- **Component** signals: "drop into a block/column", "next to the [other block]",
  mentions spans/rows/Layout Builder, "goes in the sidebar column", a widget meant
  to sit beside other content.
- **Standalone** signals: "its own page", a URL/slug, "full-width landing page",
  "takes over the screen", a form landing page with no surrounding site content.

## Default when unsure: build size-agnostic

A size-agnostic component also renders fine as a standalone page (it just fills
the content area). A layout-owning page does **not** work dropped into a block.
So when in doubt, build the size-agnostic component version — it's the safe
superset. Only add page-level Foundation columns / full-bleed when you've
confirmed it's a standalone takeover page.

## Quick checklist for a size-agnostic component

- [ ] Outer container `width:100%`, no `max-width`, no fixed px width.
- [ ] No `.row`/`.columns`/`grid-x`/`cell` you added yourself.
- [ ] Internal splits use `flex-wrap` (rail+body) or `auto-fit`/`auto-fill` grid
      (cards) — not viewport media queries or Foundation breakpoint classes.
- [ ] No `100vw` full-bleed break-out.
- [ ] Sanity-check by rendering at a wide width AND a narrow width — it should
      reflow on its own with no grid framework loaded.
