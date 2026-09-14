# How the measurements work, and what they deliberately skip

## Contrast

For every element with a direct text child, the script walks up the ancestors
for the first opaque painted background, composites the text colour onto it if
the text itself is translucent, and compares against the WCAG AA threshold for
that text's computed size and weight (3:1 for large, 4.5:1 otherwise).

**It refuses to guess.** If the walk hits a gradient or a semi-transparent
layer before it finds an opaque colour, the element is counted as unmeasurable
and reported separately rather than compared against whatever is further up.
Hero copy over a photo scrim is the usual case, and a naive walker will call it
a failure at 2.5:1 when the scrim makes it fine — or worse, call it a pass.
Check those by eye.

Things it will not catch: text over an image, focus-state contrast, and
anything that only appears on hover. Test those deliberately.

## Overflow

Two signals, and they mean different things:

- **`scrollWidth` > `clientWidth`** — the document is wider than the viewport.
  Every full-width band then stops at the viewport edge rather than the scroll
  width, which reads as a gap on the right when you scroll.
- **An element's `right` past the viewport** — names the culprit.

A negative-margin row inside a container whose cap has been lifted is the
classic cause: `100% + 30px`.

## Container geometry

Reports the x and width of `#full-width-content` and of the first `.row` inside
it. That pair answers most layout questions at a glance:

| wrapper | first row | means |
|---|---|---|
| `x=0 w=1920` | `x=0 w=1920` | full-bleed working |
| `x=360 w=1200` | `x=375 w=1170` | properly centred and contained |
| `x=0 w=1920` | `x=-15 w=1200` | nested row still capped **and** pulled left |

## Building a full-chrome harness

Measuring a page fragment on its own catches most things but not interactions
with the site's other stylesheets. For those, rebuild the real page:

1. Fetch a live page from the target xsyte with a **browser user-agent** — a
   bare `curl` gets a 403 from the WAF.
2. Replace the Brand Kit's inline style block with the candidate stylesheet,
   **at the same position in the document**. Any third-party stylesheet that
   loads before it loses ties; one that loads after wins them. Getting the
   order wrong makes a real bug invisible.
3. Swap the page body for the rendered candidate content.
4. Serve locally, intercepting requests: fulfil the platform stylesheet from a
   local copy, allow images, block the rest.

This is how a link-colour rule was found beating a third-party calendar's own
`.fc-event{color:#fff}` — a tie at (0,1,0) decided by source order, invisible
in any isolated harness.

## When JavaScript is involved

Third-party widgets bind their own handlers, and some stop propagation. Verify
behaviour rather than assuming it: hover, click, navigate, and re-check that
anything added on render survives a re-render. A calendar library that rebuilds
its whole grid on every month change will drop classes added from outside.

## Reporting

Give the number and the width it was taken at. "4.16:1 at 1440, needs 4.5" is
actionable. "Looks a bit washed out" is not.
