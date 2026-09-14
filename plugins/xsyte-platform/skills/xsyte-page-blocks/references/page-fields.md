# Page Fields — make the hand-edited bits a form

A custom page can declare the values a non-technical editor is allowed to
change. The platform reads the declarations, stores the current values on the
page, and opens **Edit Page in Simple mode**: a plain form on top (names,
dates, blurbs, photos), with the template, data sources and TinyMCE folded
away under *Advanced* for people who hold the Edit Layouts capability. The
template is never touched by the form.

**Every page you build gets fields for everything a club will change by
hand.** The month-to-month edits — who is Moose of the Month, how many
backpacks are in, whether the golf day is sold out — are the whole reason a
club pays for a site it can run itself. A page without fields is a page the
developer gets called about.

## The three kinds of value, and where each lives

| The value is… | It belongs in… | Example |
|---|---|---|
| Rows from the site's own admin | a **data source** (`ds.events`, `ds.news`, `ds.products`) | tonight's events, latest news, shop items |
| Something the template works out | `{% set %}` | `now_min`, `kitchen_till`, `icon = …`, `drive_pct` |
| Something a person types or picks | a **field** (`fields.x`) | a name, a citation, a goal, a deadline, a photo, a status pill |

Never fake the third with the second. `{% set motm_name = "Claudia Conway" %}`
looks harmless but it means the volunteer has to open Advanced, find the line
inside 200 lines of markup and edit a quoted string in a WYSIWYG that is trying
to help. Declare it instead.

## Grammar

One Twig comment per field. Put them in a block at the very top of the page,
after the one-line header comment, so they read as the page's "settings panel".

```
{# @field <name> <type> "<Label>" [key="value" …] #}
```

- **name** — `[a-z][a-z0-9_]*`, unique on the page. Prefix by card when a page
  has several of the same shape: `motm_name`, `vv_name`.
- **type** — one of the table below.
- **"Label"** — what the editor sees. Short, sentence case, no colon.
- **options** — all optional, double-quoted, `\"` to escape a quote inside:
  `default`, `help`, `group`, `placeholder`, `required="1"`,
  `options="A|B|C"` (select), `rows="4"` (textarea), `min` / `max` / `step`
  (number), `order="10"` (form order; default is declaration order).

Whitespace including newlines may appear between tokens, so a long
declaration can wrap. It is a Twig comment, so the renderer, the validator's
parse and the TV compiler all ignore it; TinyMCE keeps it as text (and may
entity-encode typography inside it — the platform decodes that).

| type | editor gets | the template gets | use for |
|---|---|---|---|
| `text` | one line | string, auto-escaped | names, headings, pill text, short facts |
| `textarea` | multi-line plain text | string; `{{ v\|nl2br }}` keeps line breaks | citations, blurbs, notes |
| `richtext` | small bold/italic/link/list editor | sanitised HTML — output with `\|raw` | a paragraph that needs a link or emphasis |
| `number` | numeric input, honours `min`/`max`/`step` | int or float | goals, counts, prices in whole units |
| `date` | date picker | `YYYY-MM-DD` — `{{ v\|date("F j") }}` | deadlines, event dates the calendar does not own |
| `select` | dropdown from `options="A\|B\|C"` | one of the options | status pills: `Open\|Sold out\|Cancelled` |
| `toggle` | checkbox | `true` / `false` | show/hide a block, "sold out" switch |
| `image` | the admin image picker (org images, media library, upload) with thumbnail + Remove | image URL string | photos, artwork, a sponsor logo |
| `url` | one line, validated as http(s) | string | a link target the club changes |

Not available yet: repeaters / lists (an officers grid, a menu). For those the
choice is still a data source or hand-edited markup — say so in the header
comment rather than inventing a fake list field.

## Reading a field in the template

The values arrive as `fields.<name>` (alias `f.<name>`). A field the template
reads but never declares renders empty and is a **validator error**; a field
declared but never read is a **warning** (the editor would see an input that
does nothing).

```twig
{# ── SURFSIDE BEACH MOOSE LODGE #2351 · MEMBER HONOURS · edit the names, citations and photos in the form above #}

{# @field motm_name     text     "Name" group="Moose of the Month" default="Claudia Conway" help="First and last name — also builds the initials." #}
{# @field motm_citation textarea "Why they're being honoured" group="Moose of the Month" rows="3"
        default="Thanks so much to Claudia for being such a great contributor to our Moose Lodge."
        help="One or two concrete sentences. Sentence case, no exclamation marks." #}
{# @field motm_photo    image    "Photo (optional)" group="Moose of the Month" help="Only with the member's permission. Leave empty to show their initials." #}

<div class="ml-honor__side">
  {% if fields.motm_photo %}
    <img class="ml-honor__photo" src="{{ fields.motm_photo }}" alt="{{ fields.motm_name }}">
  {% else %}
    <div class="ml-honor__initials"><b>{%- for w in fields.motm_name|split(" ") -%}{%- if w and loop.index in 1..2 -%}{{ w|slice(0, 1)|upper }}{%- endif -%}{%- endfor -%}</b></div>
  {% endif %}
</div>
<div class="ml-honor__name">{{ fields.motm_name }}</div>
<p class="ml-honor__cite">{{ fields.motm_citation }}</p>
```

Patterns that come up:

- **Optional image with a fallback** — `{% if fields.photo %}…{% else %}…{% endif %}`, as above. Never leave an `img` with an empty `src`.
- **A status pill** — `select` with the real states as options, then
  `{% if fields.status == 'Sold out' %}` to swap copy or hide a signup link.
- **A progress meter** — `number` fields for `current` and `goal`, and the
  percentage stays a `{% set %}`: `{% set pct = min(100, (fields.current / fields.goal * 100)|round) %}`.
  Guard the divide: `default="1"` and `min="1"` on the goal.
- **A short list in one field** — `text` with `help="Comma-separated"` and
  `{% for item in fields.needs|split(',') %}{{ item|trim }}{% endfor %}` is
  fine for chips; it is not fine for anything with two attributes per item.
- **Line breaks in a textarea** — `{{ fields.note|nl2br }}`.
- **Rich text** — `{{ fields.body|raw }}`; it was sanitised on save.
- **A date the calendar does not own** — `{{ fields.deadline|date("l, F j", "America/New_York") }}`; always pass the timezone.
- **Twig inside an attribute is fine** — `src="{{ fields.art }}"`,
  `style="width: {{ pct }}%;"`. The editor-constraint on `<`, `>`, `&` still
  applies inside the braces.

## Writing the declarations well

- **Defaults are the page as it ships.** Put the real current values in
  `default=` so the page renders correctly the moment it is pasted, before
  anyone opens the form. A freshly pasted template with empty defaults shows
  empty cards.
- **`help=` replaces the how-to comment.** Everything the old 300-word header
  used to say about *how* to edit belongs on the field that needs it, in one
  sentence. The header comment shrinks to one line naming the page and where
  the artwork rules live.
- **`group=` is a card, not a category.** One group per visual card or
  section the editor recognises on the page ("Moose of the Month", "Valued
  Veteran of the Month", "Featured event"). Two groups render side by side; more
  wrap. Leave `group` off on a page with fewer than four fields.
- **Order follows the page.** Declare fields in the order the values appear
  top-to-bottom, so the form reads like the page.
- **Name the thing, not the markup.** `deadline_label`, not `pill_text`;
  `venue_logo`, not `img2`.
- **Don't field the furniture.** Section eyebrows, button labels, "Nominate a
  member" — the fixed words of the design stay in the template. If a club
  needs to reword those, that is a template change for a developer.
- **Don't field what a data source already owns.** Tonight's events come from
  the calendar; a field for "tonight's headline act" is a second source of
  truth that drifts.

## Converting an existing template

1. Read the page as the volunteer would: which values change monthly,
   seasonally, or when an event sells out? List them.
2. Any `{% set x = "literal" %}` or `{% set x = 123 %}` whose value is typed
   rather than computed becomes a field with that literal as `default=`.
   Computed sets stay.
3. Literal text in the markup that is on the list — a name in a heading, a
   number in a meter, a date in a fact box — becomes `{{ fields.x }}` with the
   literal as `default=`.
4. Any hard-coded image URL a club would swap (a member photo, this year's
   crest, the drive artwork) becomes an `image` field with the URL as `default=`.
   Badges, logos and the mascot stay hard-coded.
5. Delete the how-to comment; move what matters into `help=`; keep a one-line
   header.
6. Run `xsyte-verify` — the lint reports declaration errors, undeclared
   references and unused fields, and renders with the defaults.
7. Paste into Advanced, save, and open the page again: it should come up as
   the form, already filled in.

## What the platform does with it

- Values are stored on the page row (`pages.fields_json`), keyed by field name.
  The schema is the content, so pasting a template into a new page brings its
  form with it. A saved empty value stays empty — the default only applies
  while nothing has been saved for that field.
- The public page, the Custom Layout block and the TV slide all render with the
  same `fields`, and the share image falls back to the first image field.
- Saving a page with fields lands back on the edit screen with a "Saved — view
  the page" note, because these pages are edited repeatedly.
- The Advanced section — template, data sources, validator — needs the Edit
  Layouts capability. Everyone with page access can fill in the form.
