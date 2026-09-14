---
name: xsyte-verify
description: Check an xsyte custom page or Brand Kit change before it is pasted into admin — parse it with the platform's own Twig, render it with sample data, and measure contrast, overflow and layout at real widths. Use when about to hand over any custom page, block or global CSS, and when the user reports a rendering problem: "check this page before I paste it", "lint this", "why is my page a white screen", "the validator says X", "is this readable", "text is unreadable on that background", "there's a gap on the right", "it looks wrong on mobile", "did I break anything". Run it after every build produced by xsyte-brand-kit, xsyte-page-blocks or xsyte-custom-page-with-form.
---

# xsyte verify — lint it and measure it

Two failure modes cost the most on this platform, and neither is visible by
reading the file:

1. **A parse error is a white screen.** Twig syntax that another engine accepts
   can be fatal here.
2. **A layout or contrast bug is invisible at the width you happened to look
   at.** A gutter that is actually zero, a 30px overflow, cream text on a cream
   card — all of them look fine in one screenshot.

Both are cheap to catch and expensive to ship.

## 1 · Lint with the platform's own Twig

```bash
php scripts/twig_lint.php --vendor=/path/to/aws_xsyte/vendor page.html [more.html ...]
```

The script autoloads Twig **out of the platform's own vendor directory**, so it
is the same build the site runs — never a different parser. It mirrors
`Page_validator`:

1. a pre-check for Twig tags left inside HTML comments, which the real parser
   reports at the wrong line;
2. tokenize + parse, printing `getRawMessage()` and the line — the exact string
   the admin's report shows;
3. `createTemplate()->render()` twice, with `success` false and true, an
   empty `ds` and the page's **field defaults**, to catch runtime-only errors;
4. **Page Fields** — every `{# @field … #}` declaration must parse (unknown
   type, duplicate name, unbalanced quotes are errors), every `fields.x` the
   template reads must be declared (error), a declared field nothing reads is
   a warning, and a `richtext` field printed without `|raw` is a warning.
   These are the checks `Page_validator::check_fields` runs in admin.

Exit 0 means every file is clean; warnings do not fail the run but should be
read — an unused field is almost always a typo in the template.

> **Never substitute a different template engine for this.** Jinja happily
> accepts `{% for x in y if cond %}`, which Twig 2 removed and Twig 3 treats as
> a fatal error. A page can render perfectly in a stand-in harness, screenshot
> perfectly, and still be a white screen on the site. That has happened.

## 2 · Render it with real data

`scripts/render_page.php` takes the same environment and renders a page with a
JSON context, so the output can be screenshotted or clicked:

```bash
php scripts/render_page.php page.html ds.json [success] > rendered.html
```

`ds.json` is the data-source payload, e.g. `{"events":[…]}`, and reaches the
template as `ds`. The optional third argument sets `success` to true. Page
Fields render with their declared defaults, as a freshly pasted page does;
`--fields=values.json` overrides any of them so the page can be measured as a
club would fill it in — a long name, a photo instead of initials, a sold-out
status.

The form helpers here emit **real markup** rather than stubs, so radios,
labels and inputs behave in a browser. That matters — a CSS-only filter built
on `form_radio()` cannot be tested against a stub.

Use this rather than temporarily editing `{% if success %}` to `{% if true %}`:
the validator flags leftover previews, and swapping it back is easy to forget.

## 3 · Measure the rendered page

```bash
node scripts/measure_page.js rendered.html --widths=1440,820,390 --shot=out.png
```

Reports, per width:

- **Contrast failures** — every text node against its *real painted*
  background, walking up the ancestors to find it, at the WCAG AA threshold
  for that text's size and weight. Elements over a gradient or a translucent
  layer are reported separately as unmeasurable rather than guessed at.
- **Horizontal overflow** — any element whose right edge passes the viewport,
  plus `scrollWidth` vs `clientWidth`. This is how a "mysterious gap on the
  right" gets located: the document is wider than the viewport, so full-width
  bands stop short of the scroll width.
- **Container geometry** — the width and x-offset of the content wrapper and
  the first inner row, which is what tells you whether a full-bleed rule is
  working or a nested row is squeezing the page.

To check the page inside the **real site chrome**, fetch a live page from the
target xsyte, splice the candidate stylesheet in at the position the Brand Kit
block actually occupies, and swap the body content. Order matters: a stylesheet
that loads before yours loses ties, one that loads after wins them. Getting the
order wrong turns a real bug invisible. Send a browser user-agent — a bare
`curl` gets a 403 from the WAF.

## What to check, beyond "does it look right"

- **Every width the design has a breakpoint for**, plus one either side of each.
  A component that only collapses at 639 may be broken from 640 to 900.
- **Both branches** of any `{% if success %}`.
- **The empty state** — render with `{"events":[]}` and look at it.
- **Text that wraps.** Day names, long product titles, two-line headings in a
  fixed-width spine.
- **After a Brand Kit change, pages you did not touch.** A global rule reaches
  the calendar, the event detail page, news, and any third-party widget on
  them. Re-measure a platform page as well as your own.

## Reporting findings

State the measured number, not an impression: "4.16:1, needs 4.5" beats "a bit
low". When a fix is a specificity problem, give both scores. When it is a
layout problem, give the measured pixel value and the width it was taken at.

## References

- `references/measuring.md` — how the contrast and overflow checks work, what they deliberately skip, and how to build a full-chrome harness.
- `scripts/twig_lint.php` — the linter.
- `scripts/render_page.php` — render with a data context.
- `scripts/measure_page.js` — the browser measurements. Needs Playwright.
