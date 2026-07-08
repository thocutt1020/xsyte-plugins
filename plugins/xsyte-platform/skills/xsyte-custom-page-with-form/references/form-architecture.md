# xsyte form system — architecture detail

## The submission flow

1. User fills the form on a custom page at, say, `https://mihwa.hockeysyte.com/page/sponsor`
2. Form posts to `/forms/handle_form_response` with hidden `form_id` and `page_id`
3. The xsyte handler:
   - Validates the CSRF token (provided by `{{ form_open() }}`)
   - Checks the honeypot (`website` field) — non-empty value = spam, silently dropped
   - Looks up the form by `form_id` and validates submitted fields against the form's schema
   - Stores the submission in the xsyte database under that form
   - Sends a notification email to the form's configured recipient list (configured in xsyte admin)
   - Re-renders the page identified by `page_id` with `success=true` injected into the Twig context
4. User sees the same URL with the `{% if success %}` branch active

## Twig context variables

In the rendered custom page, these variables are available:

- `success` — boolean, `true` after a successful submission, undefined/falsy otherwise. The primary control variable for the success state.
- Standard xsyte page context (xsyte name, current user if logged in, brand colours, etc.) — varies by xsyte version.

`success` is the only variable this skill cares about. Don't depend on submitted form values being available in the success-state render — they're stored in the database, not echoed back.

## Form admin (where field schemas live)

Each xsyte has a form admin under `/admin/forms` (exact path varies by xsyte version). The admin shows:

- Form ID (numeric)
- Form name (human label)
- Field list with `name` attributes, types, and validation rules
- Notification recipient email(s)
- Public preview HTML (what the form looks like rendered in default styling)

When building a custom page that uses a form, the user must already have created the form here. If they haven't, redirect them to do that first — without a form definition, submissions have nowhere to bind.

## Field name binding — strict

The `name` attribute on each rendered input/select/textarea must match a field name in the xsyte form schema *exactly*. Mismatched names are silently dropped (the submission still succeeds for the matching fields, but the unmatched data vanishes).

Always extract the canonical field names from the xsyte admin's "public preview" HTML. Don't paraphrase them.

## reCAPTCHA — not required

The forms handler does NOT validate reCAPTCHA; spam protection is the honeypot `website` field. Do not add a reCAPTCHA widget to new forms — it complicates the form for no benefit. (Older pages like MIHWA carry a widget; it renders but isn't checked.)

If a league specifically asks for a visible reCAPTCHA anyway: the library script is loaded globally on every xsyte page, so just place the widget div — never add `<script src="https://www.google.com/recaptcha/api.js">` in the page body, it'll double-load.

## Notification emails

The xsyte system sends a notification email to addresses configured against the form in admin. The email contains every submitted field with its value. The user receiving the email is typically the sales / signup recipient (e.g. `sponsorship@mihwa.com`).

If the user wants different recipients per submission *type* (e.g. one inbox for sponsor interest, another for general contact), they should create separate forms in admin with different `form_id`s, then the same custom page can host them — but typically simpler to have one form per recipient set.

## CSRF tokens

`{{ form_open() }}` automatically injects a CSRF token as a hidden input. The xsyte handler validates this on submission. Any plain `<form>` tag without `form_open` will fail CSRF and the submission will be rejected. Always use `form_open` / `form_close`.

## Custom page vs custom URL

xsyte custom pages are accessed at predictable URLs (typically `/page/<slug>` or similar). The `page_id` is internal — the URL slug is configured separately in admin. When sharing the page, use the public URL, not anything containing the numeric `page_id`.

## Page content storage — size limit history

Custom page bodies are stored in a database text column. Historically this was `TEXT` (65,535-byte max) and MySQL silently truncated longer pages on save, producing a broken template that throws `Twig SyntaxError: Unexpected end of template` on view (the truncation typically cuts off a closing `{% endif %}`). The column was migrated to `MEDIUMTEXT` (16MB) in July 2026 — verify the target environment has the migration before pasting long pages. Note the WYSIWYG entity-encodes content (`&mdash;`, escaped quotes), so stored byte size can be much larger than the typed length.

## What this system does NOT do

- Multi-step / multi-page wizards (one form, one page, one POST)
- File uploads (text fields only in the standard form schema)
- Conditional fields (e.g. "show this question only if X" — not natively supported, would need JavaScript)
- Webhooks to third-party services (notifications go via email only by default)

If the user needs any of the above, the standard xsyte form system isn't enough — they'd need a custom integration outside this skill's scope.
