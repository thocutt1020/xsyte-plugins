# Specificity doctrine

One rule underneath everything: **a layer that exists to be overridden must not
out-specify the thing it defaults.**

## Defaults go in `:where()`

```css
/* WRONG — (1,0,1). Beats .your-dark-band h1 (0,1,1) and every class you own.
   This ships ink-on-ink headings inside dark bands. */
#full-width-content h1{ color:var(--text-on-paper); }

/* RIGHT — scores 0. Every class wins cleanly. */
:where(#full-width-content) :where(h1,h2,h3,h4,h5,h6){ color:var(--text-on-paper); }
```

Same for anything scoped to a platform container:

```css
:where(#full-width-content .callout) :where(h2){ /* … */ }
```

## The platform recolours every unclassed link

The platform emits a per-org block in its own inline style:

```css
a { color: #<org colour>; }
a:hover { color: #333333; }
```

That is (0,0,1) — it beats a `:where()` default at (0,0,0). So a plain
`:where(#full-width-content) :where(a){ color:var(--text-link) }` loses, and
every inline link on the site renders in the org colour and hovers to grey.

The fix needs **exactly one class of weight, and no more**:

```css
:where(#full-width-content) :where(a):link,
:where(#full-width-content) :where(a):visited{ color:var(--text-link); }
:where(#full-width-content) :where(a):hover{ color:var(--text-link-hover); }
```

`:link` / `:visited` / `:hover` are pseudo-**classes**, so these score (0,1,0):
enough to beat a bare element selector, low enough that component classes and
band-scoped rules still win. **Do not simplify to `#full-width-content a`** —
that is (1,0,1) and eats every class on the site.

> Watch for collateral: any third-party stylesheet that styles anchors by class
> and loads *before* the Brand Kit block is now beaten by that (0,1,0) rule.
> A calendar or carousel library is the usual victim. Scope a fix to its
> container at (1,1,0).

## Band colour rules reach further than you think

A rule like `.band--dark h4` scores (0,1,1) and applies at **any depth**. Two
consequences:

**Paper islands.** Drop a light card inside a dark band and every heading and
paragraph in it is repainted for the dark band — light text on a light card.
The component's own `.card__title{color:…}` at (0,1,0) loses. Re-establish
paper context on the island at (0,2,0):

```css
:is(.band--dark,.band--darkest,.band--red) :is(.sheet,.daycard__list) :where(h1,h2,h3,h4,h5,h6){ color:var(--text-on-paper); }
:is(.band--dark,.band--darkest,.band--red) :is(.sheet,.daycard__list) :where(p,li,td,th){ color:var(--text-on-paper-muted); }
```

Target the light *half* of a component, not the whole component, or you will
paint paper colours onto its dark half.

**Divs and spans are not covered.** Band rules reach `h1`–`h6`, `p` and `a`.
Any primitive that paints a colour onto a `div` or `span` is invisible on the
wrong band and gives no warning — it renders, and nobody can read it. Keep a
"band-aware primitives" section and add to it rather than patching in a page.
Real examples measured on a live build:

| Primitive | On the wrong band | Measured |
|---|---|---|
| eyebrow (gold, a *dark*-band colour) on cream | washed out | 1.8:1 |
| numbered-point title (ink on a `div`) on dark | invisible | 1:1 |
| quiet button (ink text, hairline border) on dark | invisible | 1:1 |

## Small-text colour needs checking, not assuming

An 11px uppercase tag needs 4.5:1. A brand red that passes on white can fail on
a warm cream by a tenth of a point. Compute it; do not judge by eye. The
`xsyte-verify` skill measures every text node on the page against its real
painted background.
