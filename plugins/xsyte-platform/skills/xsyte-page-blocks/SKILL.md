---
name: xsyte-page-blocks
description: Build xsyte custom pages and Custom Layout blocks that pull live data from a page data source — calendar events, news articles, shop products — and declare the hand-edited values as Page Fields so a club can update names, dates, blurbs and photos from a simple form. Use when the user wants a page fed by the site's own data rather than hand-typed: "a page for our pool nights that pulls the calendar", "show upcoming events on this page", "a what's on page", "put the store products in a section", "list our news tagged X", "make the homepage block show tonight's events", or when asked to build any standalone custom page or Custom Layout block against an existing Brand Kit; also when asked to "make this page editable", "add page fields", "convert this page to Simple mode", or "let the club change the names themselves". For data-capture forms use xsyte-custom-page-with-form instead; for the site-wide chrome use xsyte-brand-kit.
---

# xsyte page blocks — pages driven by data sources

A custom page is HTML + Twig stored in the database and rendered with
`template_from_string()`. Attach a **data source** and the page gets live rows
from the site's own admin — no copy-pasting a schedule that goes stale.

## Anatomy

```html
<section class="band">
  <div class="shell sec">
    <div class="sechead"> eyebrow · heading · lead </div>
    … content, composed from Brand Kit classes only …
  </div>
</section>
```

A block never carries its own `<style>` tag. If it needs one, the Brand Kit has
a gap — add the primitive there so restyling never means re-pasting pages.

Decide first whether the output is a **standalone page** (owns its layout) or a
**Custom Layout block** (dropped into a row/column the layout already sizes).
This flips the whole approach — a block must be size-agnostic. See
`references/layout-context.md`.

## Data sources

Admin → Pages → the page → Data Sources. Each resolved source is published to
Twig under a key derived from its source name — `events`, `news`, `products` —
and reachable as `ds.<key>`. **A second source of the same type auto-suffixes**
to `events_2`, and the admin UI does not let you name it, so the order you add
them in is load-bearing. Prefer one source per page and split in the template.

Field tables for events, news and products — including which fields arrive
pre-computed and which do not exist — are in `references/data-sources.md`.
Read it before writing a loop. The short version:

- **Events** arrive as one row per occurrence date, with `is_today`,
  `date_label`, `time_label`, tags and signup state **already decided**,
  because the page editor cannot do comparisons.
- **News** rows carry HTML in their body. Use `|striptags` for a card blurb;
  `|raw` belongs on a detail page, not in a 300px card.
- **Products** carry prices **in cents** and an image **ID**, not a URL.
  `money()` and `get_graphic()` exist for exactly this.

## Hand-edited values are Page Fields, not markup

Anything a club will change by hand — a member's name, a citation, a goal
count, a deadline, a status pill, a photo — is declared at the top of the page
as a **field** and read as `fields.<name>`:

```twig
{# @field motm_name  text  "Name" group="Moose of the Month" default="Claudia Conway" #}
{# @field motm_photo image "Photo (optional)" group="Moose of the Month" help="Leave empty to show initials" #}
…
<div class="ml-honor__name">{{ fields.motm_name }}</div>
```

A page with declarations opens in **Simple mode** in admin: a form of exactly
those fields, with the template folded away under Advanced. That is the
difference between a site the club runs and a site the developer gets called
about, so **every page ships with fields for its hand-edited values** — the
old habit of `{% set motm_name = "…" %}` plus a long how-to comment is retired.
`{% set %}` is for values the template computes; data sources are for rows
from admin; fields are for what a person types or picks.

Types: `text`, `textarea`, `richtext`, `number`, `date`, `select`, `toggle`,
`image`, `url`. Grammar, the type table, templating patterns, the conventions
for defaults / help / groups, and the checklist for converting an existing
template are in `references/page-fields.md`. Read it before writing any
page that has a name, a number or a picture a club will change.

## What the page editor will do to your markup

The editor is TinyMCE with `valid_elements: '*[*]'`. `script` and `style` are
both explicitly allowed — a block *can* carry a script. What still bites is in
`references/editor-constraints.md`; the four that cost the most time:

1. **`{% for x in y if cond %}` does not exist.** Removed in Twig 2; the
   platform runs 3.21. It is a fatal parse error — a white screen, not a
   warning. Write `{% for %}{% if %} … {% endif %}{% endfor %}`. The loop then
   always has iterations, so `{% else %}` never fires and you lose the empty
   state — print it unconditionally and hide it with `:has()`.
2. **An HTML comment does not hide Twig from Twig.** A Twig tag written as
   documentation inside `<!-- -->` really opens a block, and the parser then
   blames the last line of the file.
3. **No `<`, `>` or `&` inside a Twig tag.** The editor encodes all three even
   in there. Comparison operators and arrow functions are out; `==`, `in`,
   `and`, `not`, `or` are fine. `filter(x => …)` is unusable for this reason.
4. **Never write a bare tag name in angle brackets in prose**, comments
   included — TinyMCE treats the mention as a real tag and balances it.

## Patterns that keep coming up

**Grouping a flat list.** Twig for-loops copy their scope, so nothing set
inside survives to the next iteration and you cannot carry a "current group"
variable. Write the groups out and let each one filter the whole feed:
`{% for e in ds.events %}{% if e.day_name == 'Monday' %}`. Seven passes over
sixty rows costs nothing and cannot break.

**Deciding anything the template cannot compute.** If a page needs to know
"is this today", "is this full", "how many spots left" — that has to arrive
decided from the resolver. Where it doesn't, the honest options are to link out
to a page that does know, or to extend the resolver. Do not fake it.

**Categorising.** Events carry proper tags *and* a short-name handle. Use tags
where they exist and fall back to the short name, so a page works whichever way
the events were entered.

**Filtering client-side.** Radios through `form_radio()` plus labels as chips,
with `:has()` doing the hiding, gives a working filter with no script. Hide the
empty groups too, not just the rows — `:has()` still sees `display:none`
children, which is what makes that possible.

## Empty states are not optional

Every data-driven section needs an answer for "the feed returned nothing":
either `{% if ds.x %}` with a graceful line, or wrap the whole section so it
disappears cleanly. A heading over an empty grid reads as broken.

## Before handing over

Run the **`xsyte-verify`** skill. It parses with the platform's own Twig build
and mock-renders both branches with the field defaults, checks every
`fields.x` is declared and every declaration is used, then renders the page
with sample rows so the layout, contrast and overflow can be measured. A page
that only ever ran in a different template engine has not been checked.

Then tell the user what the form will look like: which fields, in which
groups, and that the page opens in Simple mode after the first save.

## References

- `references/layout-context.md` — standalone page vs Custom Layout block, and how to build a size-agnostic block. Read this FIRST.
- `references/page-fields.md` — Page Fields: the `@field` grammar, types, templating patterns, conventions, and how to convert an existing template. Read this for any page with hand-edited values.
- `references/data-sources.md` — events, news and products field tables; what each resolver does and does not attach.
- `references/editor-constraints.md` — everything TinyMCE and Twig 3.21 will do to a custom page.
- `references/worked-example-moose.md` — eleven pages on one site, and the design decisions behind them.
