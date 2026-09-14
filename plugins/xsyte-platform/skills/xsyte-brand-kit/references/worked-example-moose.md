# Worked example — Surfside Beach Moose Lodge #2351

A complete Brand Kit build for a social club (not a league), from a Claude
Design handoff. `moose2351.clubsyte.com`. Roughly 95KB of CSS, a header and a
footer drop-in, and eleven custom pages composed from the classes.

Useful as a reference for **shape**, not for copying: the palette and the
`.ml-*` namespace are specific to this club. What transfers is the section
order, the neutralisation layer, and the list of things that went wrong.

## Shape

- **Bands** — `.ml-band` (cream), `--alt`, `--strip`, `--ink`, `--darkest`,
  `--red`. Full-bleed background, nothing else.
- **Container** — `.ml-shell`, capped and guttered.
- **Rhythm** — `.ml-sec`, `--tight`, `--tall`.
- Every block is `band → shell → sec`. No block carries a `<style>` tag.

## Bugs found during the build, and what each taught

| Symptom | Cause | Lesson |
|---|---|---|
| Logo rendered 48×236 at 390px | CSS width override left the HTML `height` attribute in place | Set `height:auto` wherever CSS sets a width. Caught by measuring, not by eye. |
| 3-up grid silently became 2-up | `auto-fit` reads *available* width, and the grid was nested | State column counts explicitly; collapse them at breakpoints. |
| Every band lost its side gutter | `.ml-sec{padding: X 0}` shorthand reset all four sides, wiping the shell's gutter | Use `padding-block`. It passed review because the screenshot was taken at 1920, where the container's auto-margin faked a gutter. |
| Homepage squeezed and hard left | Foundation **6** does not uncap nested rows; a capped row with a negative margin renders 1200px at `x=-15` | See `full-bleed.md`. |
| Persistent gap on the right | Same rule, other direction: cap lifted, negative margins left, `100% + 30px` | Measured `scrollWidth 400` vs `clientWidth 390` at 390px. |
| Callout headings ignored their own classes | `#full-width-content .callout h2` scores (1,1,1) | The `:where()` doctrine — and this was the *second* time after documenting it. |
| Menu item names invisible | Cream card inside an ink band; `.band--ink h4` (0,1,1) beat `.menuitem__name` (0,1,0) | Paper islands. Only the red prices survived, because they were spans and no band rule targets spans. |
| Every calendar event title unreadable | A link-colour fix at (0,1,0) beat `fullcalendar.css`'s `.fc-event{color:#fff}` at (0,1,0) by source order | A site-wide link rule reaches into third-party widgets. Scope the fix at (1,1,0). |
| Inline links failing AA on one band only | Platform's per-org `a{color}` at (0,0,1) beat the `:where()` default; the org red is 4.53:1 on cream-50 but **4.16:1** on cream-300 | See the link section of `specificity-doctrine.md`. |

The pattern in that table: **five of nine were specificity**, and two of those
were the same mistake made twice. The `:where()` discipline is not pedantry.

## Deliberate departures from the handoff

Record these somewhere the next person will find them. On this build there were
four, all contrast-driven — a muted body colour lightened, an eyebrow gold
darkened on paper, a tag red taken from 4.06:1 to 5.58:1, and emoji pictograms
replaced with Font Awesome. A departure that is written down is a decision; one
that is not is a bug someone will "fix" back.
