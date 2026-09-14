# Worked example — eleven pages on one site

Surfside Beach Moose Lodge #2351 (`moose2351.clubsyte.com`), built from a
Claude Design handoff on top of a Brand Kit. Useful for the *decisions*, not
for copying markup.

| Page | Data source | The interesting decision |
|---|---|---|
| Homepage hero | none | CSS-only carousel: radios, `:has()`, negative animation delays. Chips select a slide, no dots. |
| Tonight strip | events, any window | Filters to `is_today` in the template and drops each event once its end time passes. All time maths in minutes since midnight, because the editor bans `<`. |
| What's On | events, this week | Seven day cards, each looping the whole feed and keeping its own day. Today's card lit by `:has(.is-today)` — the page never needs to know the date. |
| Pool | events, location filter | Location as the categoriser rather than short name, because the tables *are* the venue. |
| Kitchen | events, short name `food` | A menu built as its own primitives — priced row, cream sheet, option pills — rather than forced into cards. |
| Karaoke & music | events `karaoke,music` + news tag `music` | Two sources of *different* types, so no key collision. The news band is wrapped in `{% if %}` and vanishes cleanly when the tag lapses. |
| Trivia | events `trivia` | Format asserted from convention, with the seven questions to ask the host listed in the file header as CONFIRM markers. |
| Membership | events + a form | The form skill's territory; the events source only supplies the next meeting date. |
| Store block | products | Prices in cents through `money()`; colourway split off the product name; links out for sizes, because the resolver does not attach options. |
| About ×4 | none | A shared tab strip copy-pasted into four files, each with `is-current` on its own tab. No include mechanism exists. |

## Things worth stealing

**Put the paste instructions, the data source config and the open questions in
a header comment in the file — but keep it short.** Slug, filters, limit, what
to confirm before it goes live, and why any non-obvious decision was made. The
page outlives the conversation that produced it, and the next person opening
it in admin has no other context. What the header must *not* carry any more is
"how to edit this each month": that guidance goes on the field that needs it
as `help=`, because the volunteer sees the fields form, not the comment.

**The honour cards, before and after.** The first version of the honor roll
carried `{% set motm_name = "Claudia Conway" %}` and a 300-word comment
explaining which quoted string to change and how to swap the initials block
for a photo. The volunteer had to open the template to change a name. The
current version declares six fields — name, citation and photo for each of
the two honours, in two groups — and the page opens as a form. Same markup
underneath, one comment line at the top. That is the pattern for every
spotlight block on this site: the charity drive (title, deadline, current,
goal, needs, artwork), the featured golf event (status, title, when/where/
format facts, images), and the hero slides' copy. `references/page-fields.md`
has the conversion checklist.

**Mark invented facts.** Several of these pages state things nobody had
confirmed — a trivia format, a lodge history. Every one carries a `CONFIRM`
marker and the file header says plainly which claims are drafts. A page of
plausible invention is worse than a shorter true one, and history pages get
quoted back at anniversaries.

**Say what the data cannot do, in the file.** The store block explains in its
own header why there are no size chips and what it would take to have them. The
next person does not have to rediscover it.

**Prefer CSS to a template variable.** Several problems that look like they need
logic do not:

- *Which day is today?* Rows carry `is-today`; `:has()` lights the card.
- *Hide the empty-state line when the list has rows?*
  `.list:has(.row) .list__empty{display:none}` — which is also the only way to
  do it once the inline for-filter is off the table.
- *A filter with no script?* Radios through `form_radio()`, labels as chips,
  `:has()` hiding both the non-matching rows and the groups that end up empty.

## One that went wrong

The What's On page shipped with `{% for e in ds.events if e.day_name == 'Monday' %}`
seven times. It rendered perfectly in a Jinja-based harness and was a **white
screen** on the site — Twig 2 removed the inline for-filter. The harness was the
bug: it accepted a superset of the language. Everything is now linted against
the platform's own Twig build before it is handed over. See the `xsyte-verify`
skill.
