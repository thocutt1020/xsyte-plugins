# xsyte-platform

Foundational skills for working on the xsyte platform.

xsyte powers a network of club and league websites — hockeysyte, sportssyte,
soccersyte, clubsyte and more. Each site supports a Brand Kit, custom pages,
data sources and embedded forms, all on a Twig-based templating system. This
plugin encodes the working patterns for building against it reliably, and the
platform behaviours that are easy to get wrong.

## What's inside

### `xsyte-brand-kit`

Build the site-wide chrome from a design handoff: the Brand Kit's Custom CSS,
Header HTML and Footer HTML. Covers the section order for a global stylesheet,
the platform-neutralisation ruleset (each override paired with the behaviour it
fixes), the specificity doctrine that keeps a defaults layer from eating every
class you write, scoping full-bleed so it does not drag the platform's own
pages out with it, and the header/drawer/footer anatomy.

Read this when a whole site needs a look, or when platform chrome is fighting
the design.

### `xsyte-page-blocks`

Build custom pages and Custom Layout blocks fed by a data source — calendar
events, news articles, shop products. Field tables for each resolver, including
what arrives pre-computed and what does not exist at all; the grouping and
filtering patterns that work inside the page editor's constraints; and the
Twig 3.21 and TinyMCE rules that turn a small mistake into a white screen.

Also home to **Page Fields**: the `{# @field … #}` declarations that turn the
hand-edited values on a page — names, dates, blurbs, photos, a status pill —
into a simple form in admin, so a club updates its own honour roll or charity
drive without opening the template. Every page built with these skills
declares its fields; `references/page-fields.md` has the grammar and the
checklist for converting an existing template.

### `xsyte-custom-page-with-form`

Build a custom page with an embedded form that posts to the xsyte forms system
and renders a thank-you state on submission. Sponsor interest, event signup,
contact, registration, RSVP — any data-capture page.

The skill encodes the wiring that is easy to get wrong: `form_open` /
`form_close` for CSRF, the required hidden fields, the honeypot, the positional
form helpers, the `{% if success %}` conditional, and the global icon libraries
already loaded by every xsyte.

### `xsyte-verify`

Lint and measure before anything is pasted. Parses with the platform's **own**
Twig build rather than a stand-in, mock-renders both branches the way the admin
validator does, renders with sample data so a page can be screenshotted or
clicked, and measures contrast, horizontal overflow and container geometry at
real widths.

Ships three scripts: `twig_lint.php`, `render_page.php`, `measure_page.js`.

## How they fit together

```
xsyte-brand-kit      →  the chrome and the class system, once per site
xsyte-page-blocks    →  pages composed from it, fed by data sources
  or
xsyte-custom-page-with-form  →  pages that capture data

              ↓  always, before pasting

xsyte-verify         →  parse it, render it, measure it
```

## Installation

Drop the `.plugin` file into Cowork or Claude Code's plugin install flow. The
skills activate on their own when the work matches.

`xsyte-verify` needs PHP on the machine and read access to an xsyte checkout's
`vendor/` directory — it deliberately loads the platform's own Twig rather than
its own copy, so the parser is never a different version from the one the site
runs. `measure_page.js` needs Playwright.

## Changelog

**0.5.0** — Added `xsyte-brand-kit`, `xsyte-page-blocks` and `xsyte-verify`.
Corrected `xsyte-custom-page-with-form`: it previously told Claude to emit raw
hidden `input` tags and place a reCAPTCHA widget. Raw form controls are a
`Page_validator` **error**, and `Forms_controller::handle_form_response()` does
not verify a captcha — the honeypot is the entire spam defence for custom
forms. Both are now documented as things not to do, along with the `type=`
duplicate-attribute trap in the form helpers.

**0.4.0** — `xsyte-custom-page-with-form`.
