---
name: xsyte-brand-kit
description: Build the site-wide chrome for an xsyte from a design handoff — the Brand Kit's Custom CSS, Header HTML and Footer HTML. Use when the user wants a custom look for a whole xsyte rather than one page: "custom header for my syte", "brand kit CSS", "implement this design system on xsyte", "make the nav match the design", "global CSS for the site", "custom footer", "the site chrome", or when a design bundle (Figma export, Claude Design handoff, style guide) needs turning into something pasteable. Also use when platform chrome is fighting the design — sticky header not sticking, full-bleed bands not reaching the edge, platform colours leaking into custom pages.
---

# xsyte Brand Kit — the site chrome

Three fields in Admin → Brand Kit dress every page on an xsyte. This skill
builds all three from a design handoff, and encodes the platform behaviour that
makes the difference between a design that works and one that half-works.

Set the Brand Kit edit mode to **code**, and make sure the kit is **active** —
`Brand_kit_model::render_header()` returns `''` unless `is_active = 1`.

| Field | What goes in it |
|---|---|
| **Custom CSS** | One stylesheet. Tokens, reset, platform neutralisation, layout, type, components. |
| **Header HTML** | Font `<link>`, the header bar, the nav, the mobile drawer, one script. |
| **Footer HTML** | The replacement footer. Any legally required line lives here. |

## Before writing anything

1. **Read the design handoff properly** — not just the colours. A handoff has
   voice rules, spacing scale, and stated intentions. Those matter as much as
   the hex values, and departing from them should be deliberate and recorded.
2. **Fetch a live page from the target xsyte** and read its DOM. Every site has
   a slightly different chrome. Guessing which wrapper is which wastes a whole
   build. Note: the WAF blocks a bare `curl` — send a browser user-agent.
3. **Establish the two output shapes**: bands that run edge to edge, and a
   container that holds the content. Everything else composes from those.
4. **Namespace every class.** Pick a two-or-three-letter prefix and use it
   without exception. Custom pages compose from these classes alone — if a
   block needs its own `<style>` block, the system has a gap.

## The stylesheet, in order

Order is not cosmetic. Later sections depend on earlier ones, and the
neutralisation layer has to land before anything tries to lay out on top of it.

```
0  Tokens              colour, type scale, spacing, radii, shadows, gradients
1  Reset + type base   defaults, wrapped in :where() — see below
2  Platform            neutralising the xsyte / Foundation chrome
3  Layout              container, bands, section rhythm, grids
4  Type                display / heading / body / eyebrow classes
5  Buttons + links
6  Surfaces            cards, chips, icons, tags
7  Content blocks      hero, day card, news, stats, steps, menu, store …
8  Header + nav
9  Drawer
10 Footer
11 Responsive
12 Print
```

## The three rules that decide whether this works

**1. Defaults must score zero.** Anything that exists to be overridden goes in
`:where()`. Written plainly, `#full-width-content h1` scores (1,0,1) and beats
every class you will ever write, including your own dark-band heading rule.
This mistake ships ink-on-ink headings and it is the single most common way a
Brand Kit goes wrong. Full treatment in `references/specificity-doctrine.md`,
including the platform's own per-org `a { color }` block, which beats a
`:where()` default and quietly recolours every unclassed link on the site.

**2. Neutralise the platform deliberately, one rule at a time, each with a
comment saying why.** The overrides in `references/platform-neutralisation.md`
are not stylistic — each one fixes a specific behaviour: an ancestor with
`overflow:hidden` silently killing `position:sticky`, a Foundation row capping
the content at 75rem, closed overlays still catching every click. Copy them
with their comments. Deleting one because it "looks unnecessary" is how the
next person reintroduces the bug.

**3. Scope full-bleed to your own pages.** `#full-width-content` is a
Foundation row. Lifting its cap unconditionally drags the platform's own pages
— calendar, event detail, news, account — out to full width with it. Key the
override off markup only your pages have. See `references/full-bleed.md`, which
also covers the Foundation 6 nested-row behaviour that makes a lifted cap
overflow by exactly one gutter.

## The header drop-in

Structure and the reasoning behind each part is in
`references/chrome-anatomy.md`. The parts that always come up:

- **Sticky goes on the platform's own wrapper**, not on your header element. A
  sticky element is clipped by its own parent's box, so sticking inside a
  wrapper that is exactly as tall as the header unsticks immediately.
- **The mobile drawer is a checkbox and CSS.** No script. A closed overlay
  needs `pointer-events:none`, not just `opacity:0`, or it catches every click
  on the page. Nested submenus use `visibility:inherit`, never `visible`.
- **The platform's utility bar cannot be hidden** if it carries the
  Login / My Account / Admin links — those are Twig-conditional and cannot be
  rebuilt in a static field. Restyle it and treat it as the utility strip.
  Items can be *injected* into it by the header script, since Twig markup
  cannot be extended from a Brand Kit field or from CSS.
- **Fonts load from a `<link>`, never `@import`.** The platform prepends its
  own base CSS inside the same `<style>` as your custom CSS, and `@import` is
  only valid at the very top of a sheet.

## After saving

**Log out or clear the session.** The rendered header is cached into session
userdata and only overwritten when the render is non-empty — the old header
keeps serving until the session turns over. Every "my change didn't apply"
report starts here.

Then verify with the **`xsyte-verify`** skill: it renders real pages against the
new sheet and measures contrast, overflow and layout at several widths. A
Brand Kit is a site-wide change; eyeballing one page at one width is not a check.

## Asset naming

Uploads run through `get_friendly_filename()`, which strips underscores.
`murrell_waving.png` becomes `murrellwaving.png` and the URL in your CSS 404s.
**Name every asset with hyphens.**

## References

- `references/platform-neutralisation.md` — the override ruleset, each with the behaviour it fixes.
- `references/specificity-doctrine.md` — `:where()` defaults, the per-org link block, band-aware primitives, paper islands on dark bands.
- `references/full-bleed.md` — scoping full-bleed to your pages; Foundation 6 nested rows.
- `references/chrome-anatomy.md` — header, nav, drawer, footer, and the header script's jobs.
- `references/worked-example-moose.md` — a complete build (Surfside Beach Moose Lodge #2351), with the bugs found along the way.
