---
name: xsyte-custom-page-with-form
description: Build a custom xsyte page with an embedded form that posts to /forms/handle_form_response and renders a Twig {% if success %} thank-you state on submission. Use when the user wants to create a sponsor interest page, signup form, registration page, contact form, RSVP, beta list, waiver, mailing list capture, or any data-capture page that lives as a custom page on an xsyte (hockeysyte, sportssyte, soccersyte, mihwa.hockeysyte.com, powerplay209.hockeysyte.com, vihl.hockeysyte.com, etc.). Triggers include phrases like "make a custom page on xsyte", "build a signup form for [event]", "wire a form into an xsyte page", "create a thank-you page", "data capture page on xsyte", "Twig form on xsyte", "add a contact form on the league site".
---

# xsyte custom page with form

Build a custom xsyte page that captures structured data via the xsyte forms system and renders a thank-you state on submission. Custom pages on xsyte are HTML+Twig templates pasted into the admin; this skill encodes the wiring patterns that are easy to get wrong.

## Use this skill when

Use this skill any time the user wants the page to capture data on an xsyte. Common shapes:

- Sponsor interest / sales lead capture
- Event signup, tournament team registration, camp or clinic enrolment
- Mailing list capture (newsletter, VIP list, "notify me when X opens")
- Contact / inquiry forms
- RSVPs, waiver acceptance, volunteer applications, coaching applications
- Beta access requests, feedback collection

Do NOT use this skill for forms that live inside the xsyte admin already (login, profile edit, league registration through the standard xsyte flow). This skill is for *custom pages the league owner is composing themselves* and pasting into the xsyte custom page editor.

## Architecture in 30 seconds

1. The league admin creates a **custom form** in xsyte admin. The form has a numeric `form_id` and a defined schema of field names (e.g. `company`, `email`, `sponsor_pack`).
2. The league admin creates a **custom page** in xsyte admin. The page has a numeric `page_id`. The body of the page is HTML + Twig — that's what this skill produces.
3. The form posts to `/forms/handle_form_response` with the `form_id` and `page_id` as hidden fields. The xsyte system handles the honeypot check, storage, and notification emails.
4. On successful submission, xsyte re-renders the same custom page with `success=true` available in the Twig context. Wrap content in `{% if success %} ... {% else %} ... {% endif %}` to flip the page to a thank-you state.

The page lives at one URL. It serves as both the form and the confirmation. No separate thank-you URL needed.

## Inputs to gather before building

Ask the user for these — do NOT assume defaults:

1. **Which xsyte** — the subdomain (e.g. `mihwa.hockeysyte.com`). Determines branding and which xsyte's admin to find form/page IDs in.
2. **`form_id`** — numeric ID of the custom form in xsyte admin. The user creates the form first, then comes here.
3. **`page_id`** — numeric ID of the custom page that will host the form. Required for the success redirect to fire correctly.
4. **Form field schema** — the exact `name` attributes of each field as defined in xsyte admin. Field names in the rendered HTML MUST match the form's schema in xsyte, or submitted data won't bind. If the user provides the form HTML from the admin "public preview," extract field names from there.
5. **Page purpose** — what's being captured and why (sponsor interest? signup? contact?). Drives copy, tone, and which sections the page needs.
6. **Branding** — colours, fonts, pattern, tone. Either pull from the xsyte's existing styling, from a brand voice document, or ask explicitly.
7. **Layout context — component or standalone? (decide this first)** — will the content be dropped into a **Layout Builder block** (a `Page` block sitting in a row/column the page layout already sizes), or is it a **standalone page** that owns its own layout? This flips the whole approach: a component must be **size-agnostic** (fill the block, no `max-width`, no self-added `.row`/`.columns`, reflow with `flex-wrap`/`auto-fit` grid — not viewport media queries); a standalone page may own its layout (Foundation grid, full-bleed). Ask if the request doesn't make it obvious. When unsure, build size-agnostic — it also works standalone. Full detail in `references/layout-context.md`.
8. **Full-bleed or in-shell** — (standalone pages only) does the page take over the whole viewport (like the MIHWA sponsor page or PowerPlay 209 signup), or sit inside the standard xsyte chrome? Full-bleed needs the chrome-hiding CSS in `references/full-bleed-css.md`. Never full-bleed a layout component — the `100vw` break-out spills outside its block.
9. **Success state copy** — what should the thank-you panel say? Short rule of thumb: confirm receipt, set expectation for next steps, give a fallback contact, optionally CTA back into the rest of the site.

If any of these are missing and you can't infer from context, ask. Do not invent a `form_id` or guess field names.

## The form wiring boilerplate

**Never emit a raw `input`, `textarea`, `select`, `button` or `form` tag.**
`Page_validator` reports each one as an **error**, and it is right to: a raw
`textarea`'s closing tag terminates the editor's own field and mangles
everything after it on save, and a raw `input` becomes a live control inside
the editing surface. Every field goes through a CodeIgniter form helper, which
is stored as plain text and never seen by the editor.

Helpers take **positional** arguments, never an attribute hash.
`form_input('name', 'value', 'extra attributes as one string')`. The hash form
`form_input({name: '…'})` is a validator error — `check_helper_hash_misuse`
catches it.

```twig
{{ form_open('/forms/handle_form_response') }}
{{ form_hidden('form_id', '2') }}
{{ form_hidden('page_id', '49') }}

  … visible fields, each through form_input / form_textarea / form_dropdown …

{{ form_input('website', '', 'class="honeypot" tabindex="-1" autocomplete="off" aria-hidden="true"') }}
{{ form_submit('submit', 'Send', 'class="btn"') }}
{{ form_close() }}
```

- `form_id` — the numeric ID from xsyte admin. Hard-code the actual value.
- `page_id` — the numeric ID of the custom page. Without it the controller
  redirects to `/page/0` and the `{% if success %}` branch never fires.
- `website` — the honeypot, and **the entire spam defence**. It must exist and
  must stay empty. `handle_form_response()` checks it first and, if it is
  filled, **fakes a success** and discards the submission, so bots learn
  nothing. Hide it offscreen (`position:absolute; left:-9999px`), NOT with
  `display:none` — bots that respect `display:none` skip the field and walk
  straight through the trap.

### There is no reCAPTCHA on custom forms

`Forms_controller::handle_form_response()` does not verify one — verified by
reading the controller, which contains no reCAPTCHA reference at all. (The
Contact and Account controllers DO use `callback_recaptcha`; custom forms do
not.) Do not place a `g-recaptcha` widget on a custom page: it asks the visitor
to prove they are human and then nothing checks the answer, which is worse than
not asking.

### Never put `type=` in the extra-attributes string

`form_input()` emits `type="text"` first and appends your extras after it, so
`type="email"` produces a tag with two `type` attributes — and HTML keeps the
**first**. You silently get a text input. Use `inputmode` and `autocomplete`
instead, which give the right mobile keyboard and the right autofill with no
clash:

```twig
{{ form_input('email', '', 'inputmode="email" autocomplete="email" required="required"') }}
{{ form_input('phone', '', 'inputmode="tel" autocomplete="tel"') }}
```

### Field names must match the form schema exactly

The controller loops the form's `custom_fields` and reads
`$_POST[$field->field_name]`. A mismatch logs a blank response row with no
error anywhere — the submission appears to work and the data is gone.

For complete templates of common form types, see `references/form-templates.md`.

## The Twig success state pattern

After form submission, xsyte re-renders the page with `success=true` in the Twig context. Wrap the swappable parts of the page in:

```twig
{% if success %}
  [thank-you content]
{% else %}
  [original content / form]
{% endif %}
```

For a polished feel, wrap BOTH the hero/header AND the form section with their own conditionals. The hero swaps to a celebratory state ("Got it — thanks!") and the form section swaps to a "what happens next" panel. The rest of the page (proof points, package info, browse sections) stays visible so the user can keep exploring or share with colleagues.

A complete success-state pattern with checkmark animation, "what happens next" steps, and CTA back into the site lives in `references/twig-success-pattern.md`.

## Available globally — do not re-import

The xsyte page shell already loads these on every page. Use them; don't add fresh `<link>` tags:

- **Font Awesome 6.5.1** — `<i class="fa-solid fa-check"></i>`, `fa-minus`, `fa-xmark`, `fa-circle-check`, etc. Cleaner than Unicode entities like `&#10003;` which render inconsistently.
- **flag-icons 7.2.3** — `<span class="fi fi-au"></span>` shows the Australia flag. ISO 3166-1 alpha-2 codes (lowercase). Beautiful for international tournaments — flag next to country name reads as proper product.
- **CodeIgniter form helpers** — `{{ form_open() }}`, `{{ form_close() }}`, `{{ form_textarea() }}`, etc. Use these instead of plain `<form>` for CSRF protection.
- **Foundation 6 grid** — xsyte is built on Zurb Foundation, so its grid CSS (`.row` / `.columns`, block grid, `.grid-x` / `.cell`) is available on every page. Use it for column layout instead of hand-rolling CSS grid/flex. See "Layout" below and `references/foundation-grid.md`.
- **`get_graphic(image_id, size)`** — global Twig function that turns an image ID (as stored on data-source records, e.g. `advertiser_logo`, `division_logo`) into a usable image URL. `size` is `'small'`/`'medium'`/`'large'`. Use single quotes inside `src`: `<img src="{{ get_graphic(rec.logo_field, 'medium') }}">`. Data-source image fields are IDs, never URLs — always resolve them through this. See `references/images-and-data.md`.

Other libraries the page might want (Google Fonts, Chart.js, etc.) need to be loaded explicitly via `<link>` or `<script>` tags. CDN allowlist for the xsyte system is broad — `cdnjs.cloudflare.com`, `fonts.googleapis.com`, `unpkg.com`, etc. all work.

## Layout — Foundation grid vs size-agnostic (depends on context)

**First check the layout context (input #7).** These two rules are opposite:

- **Layout component** (dropped into a Layout Builder block — the common case):
  the page layout already sized the block, so **do not add your own columns**.
  Build size-agnostic: fill the block, reflow with `flex-wrap` / `auto-fit` grid,
  no `max-width`, no viewport media queries. See `references/layout-context.md`.
  The rest of this section does **not** apply — skip to it only for standalone pages.
- **Standalone page that owns its layout**: use the Foundation grid below.

For a standalone page, xsyte runs on Foundation 6 and its grid is loaded globally,
so build every multi-column layout with Foundation classes rather than a bespoke
`display:grid`/`display:flex` scaffold. Two reasons: it's already there, and the
league owner can retune it in the WYSIWYG (change `medium-8` to `medium-6`)
without touching custom media queries. Keep the split clean — **Foundation
classes for the scaffold, your `<style>` block only for the skin** (colours,
cards, buttons, spacing).

Default to the classic float grid (`.row` + `small-12 medium-8 columns`) and the
block grid (`<div class="row small-up-1 medium-up-2 large-up-3">` with
`column column-block` children) for equal card rows. Most xsyte builds ship the
float grid (confirmed on lilydalerats.hockeysyte.com, July 2026); if a page
stacks instead of forming columns, the build is XY-only — use
`grid-x grid-margin-x` + `cell` instead.

Full detail — float vs XY grid, the equal-height-cards override the float block
grid needs, grid detection, and a preview gotcha (the public Foundation CDN
ships only the XY grid, so float-grid previews stack unless you shim the
classes) — is in `references/foundation-grid.md`. Read it before laying out any
custom page with more than one column.

## Full-bleed pages

When the page should take over the entire viewport (no xsyte header, footer, nav, sponsor strip), include the chrome-hiding CSS overrides at the top of the `<style>` block. The full snippet is in `references/full-bleed-css.md`. This is the same pattern used on `powerplay209.hockeysyte.com` and `mihwa.hockeysyte.com` for their sponsor / signup pages.

When the page should sit *inside* the xsyte's normal chrome (header, footer, nav still visible), do not include the chrome-hiding overrides. Style the form as a content block instead.

## Build process

Follow this order. Skipping steps causes rework.

1. **Confirm the inputs.** Specifically: **layout context (component vs standalone — decide first)**, `form_id`, `page_id`, the form field schema (every `name` attribute), full-bleed or in-shell, branding direction. Don't generate without these.

2. **Write the page in a workspace file.** Save as `.html` (Twig fragments inside HTML render fine in editors). Place in `/Users/tracyhocutt/Documents/Claude/Projects/XSYTE Marketing/` or whatever workspace folder is active.

3. **Structure the page** in this canonical order:
   - `<style>` block at top (chrome-hiding overrides if full-bleed, then theme CSS — skin only, no column-layout grid)
   - Hero / header (wrapped in `{% if success %}` if you want the celebratory swap)
   - Body content sections (proof points, packages, inventory tables, whatever the page needs)
   - Form section (wrapped in `{% if success %}` to flip to thank-you panel)
   - Footer with "powered by xsyte" credit
   - Lay out any multi-column structure with the Foundation grid (`references/foundation-grid.md`), not custom CSS grid.

4. **Wire the form** with the boilerplate from this document — `form_open`, `form_hidden` for `form_id` and `page_id`, fields through the helpers with names matching the xsyte form schema, the honeypot, `form_submit`, `form_close`. No reCAPTCHA, no raw input tags.

5. **Match field names exactly.** If the xsyte admin form has `name="sponsor_pack"`, the dropdown in the rendered HTML must be `name="sponsor_pack"` — not `sponsorship_package` or `interest`. Field name mismatches silently swallow data on submission.

6. **Render the success path, do not imagine it.** `{% if true %}` is flagged
   by the validator as a leftover preview, and swapping it back is easy to
   forget. Use the `xsyte-verify` skill instead: its renderer takes a
   `success` flag, so both branches can be produced and screenshotted without
   touching the template.

7. **Declare the hand-edited values as Page Fields.** The hero heading and
   deck, the thank-you copy, a deadline, a "spots left" number, the contact
   phone, a sponsor logo — anything the club will change without a developer
   is a `{# @field … #}` declaration at the top of the page, read as
   `fields.x`. The page then opens in admin as a simple form. Form *controls*
   and their `name` attributes are never fields — they match the admin form
   schema and stay in the template. Grammar and conventions:
   `xsyte-page-blocks/references/page-fields.md`.

8. **Lint before handing over.** `xsyte-verify` runs the same checks the
   admin's "Check page content" does — parse, then mock-render with `success`
   false and true and the field defaults, and check every field is declared
   and used — against the platform's own Twig build. A page that fails there
   is a white screen on the public site.

8. **Hand back the file** with a clear note of any placeholders the user needs to swap before going live (typically `page_id` if it's not yet known, or a recipient email address).

## Common pitfalls

- **Field name mismatches** — biggest source of "I submitted but nothing arrived." Always double-check rendered field names against the form schema in xsyte admin.
- **Missing `page_id`** — submission works, but the success redirect goes nowhere useful. The `{% if success %}` branch never fires.
- **Honeypot via `display:none`** — sophisticated bots skip these. Use offscreen positioning (`position: absolute; left: -9999px;`) instead.
- **Re-importing Font Awesome / flag-icons** — these are already loaded by the xsyte shell. Re-importing causes version drift and wasted bytes.
- **Hardcoded chrome IDs** — the chrome-hiding CSS targets specific IDs/classes from the xsyte template (`#header-hero`, `.top-bar`, etc.). Use the canonical snippet in `references/full-bleed-css.md`; don't reinvent it.
- **Twig syntax inside CSS** — avoid Twig `{% ... %}` constructs inside `<style>` blocks. Use Twig only in the HTML body.
- **Forgetting CSRF** — using a plain `<form>` tag instead of `{{ form_open() }}` works for the POST itself but fails CSRF validation. Always use `form_open`.
- **`type=` in a helper's extra string** — silently ignored, because the helper already emitted `type="text"` and HTML keeps the first duplicate. Use `inputmode` / `autocomplete`.
- **A reCAPTCHA widget** — nothing verifies it on custom forms. It is theatre, and it costs the visitor a click.
- **`{% for x in y if cond %}`** — the inline for-filter was removed in **Twig 2**; the platform runs **3.21.1**. It is a fatal parse error, which means a white screen, not a warning. Write `{% for %}{% if %} … {% endif %}{% endfor %}`. Twig 3's replacement, `y|filter(x => …)`, is unusable in the page editor because the arrow needs a greater-than sign and the editor encodes it.
- **A Twig tag written as documentation inside an HTML comment** — an HTML comment hides nothing from the Twig lexer. An example tag in a header block really opens a block, and the parser then reports "Unexpected end of template" at the LAST line of the file, which sends you hunting in the wrong place. Describe the delimiters in words, or put example code inside a Twig comment, which the lexer does skip.

## Worked example

The MIHWA Garmisch 2026 sponsor interest page is the canonical worked example. It demonstrates:

- Twig success state on both hero AND form section
- `form_open` / hidden fields / honeypot wiring
- Font Awesome icons (cyan ticks, grey minus dashes) and flag-icons (per-country flag next to nation name)
- Click-to-reserve buttons that pre-fill form fields and scroll to the form
- Custom styling matching MIHWA's Bavarian / cyan / yellow brand
- Full-bleed page (chrome hidden via the CSS overrides)

The file lives at `/Users/tracyhocutt/Documents/Claude/Projects/XSYTE Marketing/mihwa-sponsor-interest.html` in the workspace. Read it before starting a similar build — many decisions about structure, animation timing, and visual hierarchy were resolved there.

For excerpted snippets and what to copy versus rebuild, see `references/worked-example-mihwa.md`.

## References

- `references/layout-context.md` — the component-vs-standalone decision (Layout Builder blocks vs standalone pages) and how to build a size-agnostic component. Read this FIRST when a page might be dropped into the Layout Builder.
- `references/foundation-grid.md` — laying out custom pages with the Foundation 6 grid (float vs XY grid, block grid, equal-height cards, grid detection, preview gotcha). Read before building a multi-column *standalone* page.
- `references/images-and-data.md` — looping data sources (seasons, advertisers/sponsors) with Twig, the `get_graphic(image_id, size)` image helper, known feed field names, and link patterns. Read before wiring a data-source-driven page.
- `references/form-architecture.md` — deeper detail on how `/forms/handle_form_response` processes submissions, what context variables are available in the success state, and notification email behaviour.
- `references/full-bleed-css.md` — the complete chrome-hiding stylesheet for full-viewport pages.

## Companion skills

- **`xsyte-verify`** — lint and render this page before it is pasted. Non-optional for anything with Twig in it.
- **`xsyte-brand-kit`** — if the site has a Brand Kit, the page should compose from its classes rather than carrying its own `<style>` block.
- **`xsyte-page-blocks`** — for pages driven by a data source (events, news, products) rather than a form; its `references/page-fields.md` is the Page Fields reference this skill uses too.
- `references/twig-success-pattern.md` — full success-state pattern with checkmark animation, "what happens next" numbered steps, and CTA design.
- `references/form-templates.md` — copy-paste-able form templates for the common shapes (sponsor interest, event signup, mailing list, contact, RSVP).
- `references/worked-example-mihwa.md` — annotated walkthrough of the MIHWA page with what to keep and what to adapt.
