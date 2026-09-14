# Data sources — what each one actually gives you

Attach in Admin → Pages → the page → Data Sources. Resolved rows are published
under a key derived from the source name and read as `ds.<key>`.

> **Two sources of the same type collide.** The second auto-suffixes to
> `events_2`, and the admin UI has no field for naming it, so the order they
> were added in decides which is which. Prefer one source and split in the
> template.

Sources available: clusters, divisions, seasons, teams, players, products,
sponsors, news, events. This file covers the three a public content page
usually wants. **Verify field names against the resolver in the codebase before
relying on them** — resolvers get extended, and a mismatch fails silently.

---

## Events

Filters: date range (today → next 90 → this week → this month → upcoming →
last 30), short name (comma separated), location, tags, exclude tags, and
"only events taking signups".

Rows are **one per occurrence date**, not one per series, and everything
comparable arrives already decided — because the page editor cannot do
comparisons at all.

| Field | Notes |
|---|---|
| `occurrence_id`, `event_id` | link with `/event/{{ occurrence_id }}` |
| `name`, `short_name`, `location`, `description` | `short_name` is the machine handle (varchar 20) |
| `date`, `day_name`, `day_abbr`, `date_label`, `date_short` | formatted server-side |
| `all_day`, `time_start`, `time_end`, `time_label` | `time_label` is ready to print |
| `is_today`, `is_tomorrow` | **decided server-side** — never compute a date in the template |
| `tags` (array), `tags_label` (string) | proper many-per-event tags |
| `signups_open`, `capacity`, `spots_left`, `is_full`, `has_spots` | |
| `signup_label`, `signup_url` | e.g. "9 going, 7 spots left" — print it, don't build it |

`description` is HTML. On a detail page it is printed with `|raw` by the
platform template, so a description can be a full styled block. In a **card**,
use `|striptags` and clamp it — dropping a full layout into a quarter-width
card is not a saving.

Two handles for categorising, and they are different things:

- **`short_name`** — one per event, the machine key. `karaoke`, `trivia`.
- **`tags`** — many per event. One grouping can collect unrelated events:
  `food` pulls taco night, rib night *and* the Friday band-and-dinner.

Emit both as classes so a page works whichever way the events were entered.

---

## News

Filters: tag include / exclude (pattern fields — `game_#` still matches next
season), an exact tag picker, article state, and published-within-days.
Unapproved articles are always excluded; that is not a filter.

Field names come from `News::get_resolved_articles()` — **check them in the
codebase**. Bodies are HTML: `|striptags` for a card, `|raw` only where a full
article belongs.

To see the real keys, print them once from the page:
`{% for a in ds.news %}{% for k, v in a %}[{{ k }}] {% endfor %}{% endfor %}`

---

## Products

Filter: category.

| Field | Notes |
|---|---|
| `product_id` | link with `/shop/product/{{ product_id }}` |
| `name`, `short_desc`, `long_desc` | colourway is often suffixed to the name |
| `image` | an image **ID**, not a URL — resolve with `get_graphic(id, size)` |
| `price`, `sale_price`, `retail_price` | **CENTS**. `"2000"` is $20.00 |
| `new_product` | `"0"` / `"1"` |
| `category_id`, `category_name` | |

**Always print money through `money()`** — it converts from cents and supplies
the currency symbol and decimal places for that syte. A hardcoded `$` is a bug
waiting for the next site in a different currency. `money()` always prints
decimals, so `$20.00` rather than `$20`; take the correctness.

`sale_price` is `"0"` when there is no sale, and Twig reads the string `"0"` as
false, so a plain truthiness test is enough — no comparison needed.

**What products do NOT give you:** extra images and options/sizes. Both exist
as tables and the real product page reads them, but the resolver does not
attach either. Link the card to `/shop/product/ID`, where the gallery and the
size picker already work, rather than faking chips from nothing.

---

## Extending a resolver

When a page genuinely needs something the feed lacks, the established pattern
is one grouped query for the whole result set — never N+1 — attached after the
main query, with any comparison **pre-computed** into a ready-to-print field.
The events resolver's tag and signup attachers are the model to copy.
