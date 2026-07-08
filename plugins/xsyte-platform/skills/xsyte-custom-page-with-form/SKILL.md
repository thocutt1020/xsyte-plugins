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
3. The form posts to `/forms/handle_form_response` with the `form_id` and `page_id` as hidden fields. The xsyte system handles validation, reCAPTCHA verification, storage, and notification emails.
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
7. **Full-bleed or in-shell** — does the page take over the whole viewport (like the MIHWA sponsor page or PowerPlay 209 signup), or sit inside the standard xsyte chrome (header, footer, nav still visible)? Full-bleed needs the chrome-hiding CSS in `references/full-bleed-css.md`.
8. **Success state copy** — what should the thank-you panel say? Short rule of thumb: confirm receipt, set expectation for next steps, give a fallback contact, optionally CTA back into the rest of the site.

If any of these are missing and you can't infer from context, ask. Do not invent a `form_id` or guess field names.

## The form wiring boilerplate

Every form goes inside `{{ form_open() }}` and `{{ form_close() }}` (CodeIgniter form helpers — they handle CSRF tokens automatically). Add these required hidden fields:

```twig
{{ form_open('/forms/handle_form_response') }}
{{ form_hidden('form_id', '2') }}
{{ form_hidden('page_id', '49') }}
{{ form_input('website', '', 'class="honeypot" tabindex="-1" autocomplete="off"') }}
```

- `form_id` — the numeric ID from xsyte admin. Hard-code the actual value.
- `page_id` — the numeric ID of the custom page. Without this, the success redirect doesn't return to the right page.
- `website` — anti-spam honeypot. Bots fill it; humans don't see it. Hide via offscreen positioning, NOT `display:none` (some bots ignore display:none-d fields and skip them, defeating the trap).

Then your visible form fields, then the submit button, then `{{ form_close() }}`.

**Spam protection is the honeypot — reCAPTCHA is NOT required.** The forms handler does not validate reCAPTCHA, so don't add the widget by default; it just complicates the form. If a league specifically asks for it, the widget div is `<div class="g-recaptcha" data-sitekey="..."></div>` (key from that xsyte's admin) and the script is already loaded globally — never add a `<script>` tag for it.

For complete templates of common form types, see `references/form-templates.md`.

## Form controls MUST use the CI form helpers — never raw HTML

The xsyte custom page editor is a WYSIWYG built on top of a `<textarea>`. Raw form-control markup in the page body becomes live markup *inside the editor itself*:

- A raw `<textarea>` in your content emits a closing `</textarea>` that terminates the **editor's own textarea**. Everything after that point spills out below the editor and gets mangled or dropped on the next save. This is the confirmed, page-destroying failure (hit on benchies.hockeysyte.com, July 2026).
- Raw `<input>` elements render as live controls inside the editing surface and can break the editing experience the same way.

The fix: emit every form control through a CodeIgniter form helper. In the stored page content the helper is just text — `{{ form_textarea('notes', '') }}` — so the editor never sees a form element. The real control only exists at render time.

**The helpers take positional arguments, NOT attribute hashes.** The signature pattern is `(name, value, extra)` where `extra` is a plain string of additional HTML attributes. Do not pass Twig hashes like `{name: 'x', required: 'required'}` — that is not how the xsyte Twig wrappers are wired.

Helper equivalents for every control:

| Control | Helper |
|---|---|
| Text input | `{{ form_input('player_name', '', 'id="player_name" required') }}` |
| Pre-filled input | `{{ form_input('date_start', status_log.date_start, 'class="fdatepicker"') }}` |
| Hidden field | `{{ form_hidden('form_id', '5') }}` |
| Textarea | `{{ form_textarea('notes', '', 'id="notes" rows="4" placeholder="..."') }}` |
| Select / dropdown | `{{ form_dropdown('division', {'': 'Choose...', 'masters': 'Masters', 'veterans': 'Veterans'}, '', 'required') }}` |
| Radio | `{{ form_radio('gear_hire', 'Yes', false, 'required') }}` |
| Checkbox | `{{ form_checkbox('agree', 'Yes', false, 'id="agree" required') }}` |
| Submit | `{{ form_submit('submit', 'Send Message', 'class="btn-submit"') }}` |

Signatures:

- `form_input(name, value, extra)` / `form_textarea(name, value, extra)`
- `form_hidden(name, value)`
- `form_dropdown(name, options_hash, selected, extra)` — the options hash here is *data* (value → label), not attributes, so a Twig hash is correct for that argument only
- `form_radio(name, value, checked, extra)` / `form_checkbox(name, value, checked, extra)` — `checked` is a boolean
- `form_submit(name, value, extra)`

Notes:

- The `extra` string carries any additional attributes verbatim: `'id="email" class="fdatepicker" required placeholder="..."'`.
- Inputs render as `type="text"` — there is no type override. For date fields use `class="fdatepicker"` (the xsyte shell's Foundation datepicker) rather than `type="date"`. Email/phone validation happens server-side against the form schema, so plain text inputs are fine.
- `form_submit` renders `<input type="submit">`, not `<button>` — write the submit button's CSS against its class, and don't rely on button-only selectors.
- Labels, wrapper `<div>`s, the reCAPTCHA `<div>`, and all other non-form-control markup are safe as raw HTML — only elements the editor would treat as live form controls (`input`, `textarea`, `select`, `button`) need helpers.
- Your CSS selectors like `input[type="text"]` still match the rendered output — the helpers produce ordinary HTML in the browser.

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
- **CodeIgniter form helpers** — `{{ form_open() }}`, `{{ form_close() }}`, `{{ form_input() }}`, `{{ form_textarea() }}`, `{{ form_dropdown() }}`, `{{ form_radio() }}`, `{{ form_checkbox() }}`, `{{ form_hidden() }}`, `{{ form_submit() }}`. Use these for the form tag (CSRF protection) and for EVERY form control (WYSIWYG safety — see the helpers section above).
- **reCAPTCHA** — script is loaded, but reCAPTCHA is NOT required on forms (the honeypot handles spam). Only place the widget div if specifically requested.

Other libraries the page might want (Google Fonts, Chart.js, etc.) need to be loaded explicitly via `<link>` or `<script>` tags. CDN allowlist for the xsyte system is broad — `cdnjs.cloudflare.com`, `fonts.googleapis.com`, `unpkg.com`, etc. all work.

## Full-bleed pages

When the page should take over the entire viewport (no xsyte header, footer, nav, sponsor strip), include the chrome-hiding CSS overrides at the top of the `<style>` block. The full snippet is in `references/full-bleed-css.md`. This is the same pattern used on `powerplay209.hockeysyte.com` and `mihwa.hockeysyte.com` for their sponsor / signup pages.

When the page should sit *inside* the xsyte's normal chrome (header, footer, nav still visible), do not include the chrome-hiding overrides. Style the form as a content block instead.

## Build process

Follow this order. Skipping steps causes rework.

1. **Confirm the inputs.** Specifically: `form_id`, `page_id`, the form field schema (every `name` attribute), full-bleed or in-shell, branding direction. Don't generate without these.

2. **Write the page in a workspace file.** Save as `.html` (Twig fragments inside HTML render fine in editors) in the active workspace or project folder, so the user can review and iterate before pasting into the xsyte admin.

3. **Structure the page** in this canonical order:
   - `<style>` block at top (chrome-hiding overrides if full-bleed, then theme CSS)
   - Hero / header (wrapped in `{% if success %}` if you want the celebratory swap)
   - Body content sections (proof points, packages, inventory tables, whatever the page needs)
   - Form section (wrapped in `{% if success %}` to flip to thank-you panel)
   - Footer with "powered by xsyte" credit

4. **Wire the form** with the boilerplate from this document — `form_open`, `form_hidden` for `form_id` and `page_id`, honeypot via `form_input`, every field via the CI helpers with names matching the xsyte form schema, `form_submit`, `form_close`. No raw `<input>`, `<textarea>`, `<select>`, or `<button>` anywhere in the page body. No reCAPTCHA unless asked.

5. **Match field names exactly.** If the xsyte admin form has `name="sponsor_pack"`, the dropdown in the rendered HTML must be `name="sponsor_pack"` — not `sponsorship_package` or `interest`. Field name mismatches silently swallow data on submission.

6. **Test the success path mentally.** Replace `{% if success %}` with `{% if true %}` temporarily to preview the thank-you state. Restore before delivering.

7. **Hand back the file** with a clear note of any placeholders the user needs to swap before going live (typically `page_id` if it's not yet known, or a recipient email address).

## Common pitfalls

- **Raw form controls in the page body** — the biggest editor-killer. A raw `<textarea>`'s closing tag terminates the WYSIWYG's own textarea; content after it spills outside the editor and is mangled or lost on save. Use the CI form helpers for every `input` / `textarea` / `select` / `button` (see the helpers section above).
- **Field name mismatches** — biggest source of "I submitted but nothing arrived." Always double-check rendered field names against the form schema in xsyte admin.
- **Missing `page_id`** — submission works, but the success redirect goes nowhere useful. The `{% if success %}` branch never fires.
- **"Unexpected end of template" Twig error on the live page** — two known causes: (a) an unclosed Twig tag (`{% if %}` without `{% endif %}`, unclosed `{#` comment), or (b) the saved content was silently truncated by the database. The pages content column was historically `TEXT` (64KB max — entity-encoded editor output inflates byte size well past what was typed); it was migrated to `MEDIUMTEXT` in July 2026, but verify the target environment has the migration. Diagnose with `SELECT LENGTH(content)` against the column max.
- **Honeypot via `display:none`** — sophisticated bots skip these. Use offscreen positioning (`position: absolute; left: -9999px;`) instead.
- **Re-importing Font Awesome / flag-icons** — these are already loaded by the xsyte shell. Re-importing causes version drift and wasted bytes.
- **Hardcoded chrome IDs** — the chrome-hiding CSS targets specific IDs/classes from the xsyte template (`#header-hero`, `.top-bar`, etc.). Use the canonical snippet in `references/full-bleed-css.md`; don't reinvent it.
- **Twig syntax inside CSS** — avoid Twig `{% ... %}` constructs inside `<style>` blocks. Use Twig only in the HTML body.
- **Forgetting CSRF** — using a plain `<form>` tag instead of `{{ form_open() }}` works for the POST itself but fails CSRF validation. Always use `form_open`.

## Worked example

The MIHWA Garmisch 2026 sponsor interest page is the canonical worked example. It demonstrates:

- Twig success state on both hero AND form section
- `form_open` / hidden fields / honeypot / reCAPTCHA wiring
- Font Awesome icons (cyan ticks, grey minus dashes) and flag-icons (per-country flag next to nation name)
- Click-to-reserve buttons that pre-fill form fields and scroll to the form
- Custom styling matching MIHWA's Bavarian / cyan / yellow brand
- Full-bleed page (chrome hidden via the CSS overrides)

**Caveat:** the MIHWA build predates the helpers-only rule — its form uses raw `<input>` / `<textarea>` / `<select>` markup. Copy its structure and wiring, but convert every form control to the CI helper equivalents when building anything new.

For excerpted snippets and what to copy versus rebuild, see `references/worked-example-mihwa.md` — it captures the structure, key patterns, and design decisions from that build.

## References

- `references/form-architecture.md` — deeper detail on how `/forms/handle_form_response` processes submissions, what context variables are available in the success state, and notification email behaviour.
- `references/full-bleed-css.md` — the complete chrome-hiding stylesheet for full-viewport pages.
- `references/twig-success-pattern.md` — full success-state pattern with checkmark animation, "what happens next" numbered steps, and CTA design.
- `references/form-templates.md` — copy-paste-able form templates for the common shapes (sponsor interest, event signup, mailing list, contact, RSVP), all using WYSIWYG-safe CI form helpers.
- `references/worked-example-mihwa.md` — annotated walkthrough of the MIHWA page with what to keep and what to adapt.
