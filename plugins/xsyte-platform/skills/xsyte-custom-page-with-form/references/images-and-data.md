# Images and data sources on custom pages

Custom pages can loop over xsyte **data sources** (seasons, advertisers/sponsors,
etc.) with Twig. Two things trip people up: image fields are stored as **IDs, not
URLs**, and the field names are the raw column names from the feed. This file
records both.

## Resolving images — `get_graphic(image_id, size)`

Data-source records reference images by a numeric **image ID** (e.g.
`advertiser_logo: "2370"`, `division_logo: "x278"`), not a path. You cannot build
the URL by hand. xsyte exposes a global Twig function that turns an image ID into
a URL:

```twig
get_graphic(image_id, size)   →  a usable image URL
```

- `size` is a string: typically `'small'`, `'medium'`, or `'large'`.
- Use it as the `src`. **Use single quotes for the size inside an HTML attribute**
  so they don't collide with the attribute's double quotes:

```twig
<img src="{{ get_graphic(advertiser.advertiser_logo, 'medium') }}" alt="{{ advertiser.advertiser_name }}">
<img src="{{ get_graphic(season.division_logo, 'large') }}" alt="{{ season.division_long_name }}">
```

Pick the size to match the rendered dimensions — `'medium'` for small badges,
`'large'` for big sponsor logos where crispness matters.

Direct CDN paths like `https://d1c2851ymfarr0.cloudfront.net/images/<ORG>/large/<filename>`
exist for images you already know the filename of (e.g. a QR code you uploaded),
but for anything coming out of a data source, go through `get_graphic` — the feed
gives you the ID, not the filename.

## Known data sources and their fields

Field names below are the raw feed columns — reference them as
`{{ record.field_name }}` inside the loop. Bind the data source to a variable of
your choosing in the page builder (names used here are conventions, not fixed).

### Seasons  (bind as `seasons`)

Home-league / competition seasons. One record per division-season.

| Field | Notes |
|---|---|
| `season_id` | build links: `/season/{{ season.season_id }}` (standings), `/season/{{ season.season_id }}/join` (register) |
| `season_name` | e.g. "2026 Winter Season" |
| `season_status` | filter on this — `'active'` for current; upcoming seasons come through a separate feed/binding |
| `division_long_name` / `division_short_name` | e.g. "Senior Division 1" / "SNR-ONE" |
| `division_logo` | image ID → `get_graphic(season.division_logo, 'medium')` |
| `cluster_long_name` | grouping, e.g. "Home League" |
| `minimum_age` / `maximum_age` | numbers as strings; `maximum_age` 99 = no upper limit (render "Ages N+") |
| `game_length` | minutes |

### Advertisers / sponsors  (bind as `advertisers`)

Club sponsors. Powers the sponsor slider.

| Field | Notes |
|---|---|
| `advertiser_name` | display name / alt text |
| `advertiser_url` | click-through; may be empty — render a non-link chip when blank |
| `advertiser_logo` | image ID → `get_graphic(advertiser.advertiser_logo, 'medium')` |
| `logo_size` | the size that was uploaded (e.g. "large") |
| `advertiser_rank` | ordering hint |
| `status` | filter on `'ACTIVE'` |
| `advertiser_contact` / `advertiser_phone` / `advertiser_email` | contact details, usually not shown publicly |

## Link patterns

- Season standings/stats page: `/season/{id}`
- Season join / register-interest page: `/season/{id}/join`

(Confirmed on lilydalerats.hockeysyte.com, July 2026.)

## Loop hygiene

- Standard Twig: `{% for season in seasons %} … {% endfor %}` (swap to
  `{% foreach %}` only if a specific build requires it).
- Filter status inside the loop: `{% if season.season_status == 'active' %}`.
- External sponsor links open in a new tab: `target="_blank" rel="noopener noreferrer"`.
- For a seamless CSS marquee (sponsor crawl), render the loop **twice** and mark
  the second copy `aria-hidden="true"` so screen readers don't announce duplicates.
