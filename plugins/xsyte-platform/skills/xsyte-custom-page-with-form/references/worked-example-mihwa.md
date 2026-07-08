# Worked example — MIHWA Garmisch 2026 sponsor interest page

The MIHWA sponsor interest page is the canonical first build of this skill. It demonstrates every pattern in the skill on a real, in-production page. This walkthrough captures its structure, key snippets, and design decisions so similar builds don't have to re-derive them.

> **Caveat (July 2026):** the MIHWA build predates the helpers-only rule. Its form uses raw `<input>` / `<textarea>` / `<select>` markup, which breaks the textarea-based WYSIWYG page editor (a raw `</textarea>` terminates the editor's own field). Copy the structure and wiring, but convert every form control to CI form helpers (`form_input`, `form_textarea`, `form_dropdown`, etc.) in any new build. See SKILL.md.

## What it does

Captures sponsor interest for the MIHWA World Cup Inline Hockey tournament in Garmisch-Partenkirchen, May–June 2026. Sponsors browse two sponsorship tiers (Sponsor a Division, Sponsor a Nation), click Reserve on a specific slot, and submit a form. On success, the page flips to a celebratory thank-you state.

## Page structure (top to bottom)

1. **Full-bleed CSS overrides** — chrome hidden via the `references/full-bleed-css.md` snippet
2. **Hero** — Bavarian-pattern background, countdown timer (36 days), urgency message ("first in best dressed"). Wrapped in `{% if success %}` for celebratory swap.
3. **Growth proof section** — 4× year-on-year traffic comparison (real GA4 data), strength-seeded brackets story
4. **Two package cards** — Division (flagship, yellow accent) and Nation (national accent), with full inclusion lists and pricing tables
5. **Nation inventory table** — 19 nations alphabetical, flag-icon next to name, FA-icon ticks for division participation, per-nation pricing, Available/Locked status pill, Reserve button per row
6. **Inventory strip** — black band with "4 divisions / 19 nations / 36 days left" counts
7. **Form section** — `form_open`, hidden `form_id=2`, `page_id`, honeypot, fields, reCAPTCHA, submit. Wrapped in `{% if success %}` for thank-you panel swap.
8. **Footer** — MIHWA branding, "powered by xsyte" credit

## What to copy directly

These patterns work as-is for any tournament-style sponsor page on any xsyte:

- The `{{ form_open() }}` / hidden-field / honeypot / reCAPTCHA wiring (just swap `form_id` and `page_id` — and use the helper versions of the fields, per the caveat above)
- The Twig `{% if success %}` two-conditional pattern (hero + form section)
- The success ring with checkmark animation
- The "what happens next" numbered steps panel
- The chrome-hiding CSS overrides
- The `reserveNation` JavaScript pattern — pre-fill the dropdown by slug, append a comment line, smooth-scroll to form, focus first field
- The Font Awesome ticks (`<i class="fa-solid fa-check"></i>`) for division participation
- The flag-icons (`<span class="fi fi-au"></span>`) next to country names

## What to adapt

These need to be re-themed for any non-MIHWA build:

- **Form controls** — convert MIHWA's raw `<input>` / `<textarea>` / `<select>` markup to CI form helpers (see SKILL.md). Do not copy the raw controls verbatim.
- **Brand palette** — MIHWA uses Bavarian cyan / yellow / black. Other xsytes will have their own colours pulled from their league bible or brand voice doc.
- **Typography** — MIHWA uses Oswald + Open Sans. Other xsytes may already have a `custom_google_font` configured; match it where possible.
- **Pattern background** — Bavarian diamonds work for MIHWA (Garmisch is in Bavaria). Substitute with whatever pattern fits the host xsyte's identity, or skip patterns entirely.
- **Inventory tiers** — MIHWA has Division + Nation. Other tournaments / leagues may want Division + Team + Player, or just one tier, or no tiers at all (e.g. a contact form has no inventory). Drop the inventory table if it doesn't apply.
- **Pricing model** — MIHWA is flat-fee (one-shot per tournament). For season-style xsytes, prices should be monthly with a multi-month commitment.
- **Urgency framing** — MIHWA uses "first in best dressed, 36 days until kickoff." Different events need different urgency: ongoing season ("limited slots remaining"), evergreen signup ("new cohort opens [date]"), no urgency at all (general contact).
- **Field names in the form** — must match the xsyte admin's form definition for the *target* xsyte. Don't carry over MIHWA's `sponsor_pack` field name verbatim — confirm the actual schema.

## Key snippets — what they look like in the page

### Click-to-reserve JS that pre-fills the form

```javascript
var NATION_SLUGS = {
  'Australia': 'australia',
  // ... 19 entries
};

function reserveNation(nation, tier, price) {
  var slug = NATION_SLUGS[nation];
  var divCount = parseInt(tier, 10);

  document.getElementById('sponsor_pack').value = slug;

  var commentsField = document.getElementById('comments');
  var prefill = 'Specifically interested in: ' + nation +
    ' (' + divCount + ' division' + (divCount > 1 ? 's' : '') + ', €' + price + ')';
  commentsField.value = prefill;

  document.querySelector('.form-card').scrollIntoView({
    behavior: 'smooth', block: 'start'
  });

  setTimeout(function() {
    document.getElementById('company').focus();
  }, 600);
}
```

This pattern — pre-fill dropdown + pre-fill comments + scroll + focus — works for any "browse inventory then submit" page shape. Generalise the slug map and adapt the comment template. It works identically with helper-rendered controls, since the helpers produce ordinary HTML with the same `id` attributes at render time.

### Status pills

Available, Pending, Locked. The CSS:

```css
.status-pill {
  display: inline-block;
  font-size: 11px;
  padding: 4px 12px;
  letter-spacing: 1.5px;
  text-transform: uppercase;
}

.status-available { background: var(--accent); color: var(--dark); }
.status-pending { background: var(--warning); color: var(--warning-dark); }
.status-locked { background: var(--border); color: var(--grey); }
```

Easy to update by hand when a slot sells — change the row's pill class, change the button class to `locked`, add `disabled`, remove the `onclick` or change its text.

## Things deliberately NOT in the MIHWA build

(Worth knowing so future builds can decide whether to add):

- **No backend submissions inventory dashboard.** The form drops submissions into the standard xsyte form admin — Mike checks email and the admin panel. No custom dashboard yet.
- **No automatic "lock" behaviour.** Locking a slot is a manual HTML edit. A future iteration might wire status to a database table the page reads from, but that's a different skill.
- **No payment capture.** The form is interest-only — payment happens in a separate conversation (invoice / Stripe link sent by Mike). The skill is built for lead capture, not commerce.
- **No multi-step flow.** One page, one form, one submission. If a future build needs multi-step (e.g. select package → enter details → confirm), the standard xsyte form system isn't enough — would need custom JavaScript or a different approach entirely.

## Page structure in detail

The MIHWA page's source follows this order:

1. `<head>` — Google Fonts + Font Awesome + flag-icons + reCAPTCHA + theme CSS
2. `<style>` — chrome overrides, theme variables, components (hero, packages, nation table, form, success states), responsive breakpoints
3. `<header class="hero">` — Twig conditional, success state and default state
4. `<section class="growth">` — proof point cards
5. `<section class="packages" id="packages">` — Division + Nation cards side by side
6. `<section class="nations">` — the 19-row inventory table
7. `<section class="inventory-strip">` — count summary
8. `<section class="form-section" id="signup">` — Twig conditional, form and success panel
9. `<footer>` — credit
10. `<script>` — `NATION_SLUGS`, `reserveNation` function

The full page is around 1700 lines of HTML + Twig + CSS.
