---
name: xsyte-season-builder
description: "Build or extend a competition on an xsyte through its Setup API — a league, tournament or casual-games group with divisions, a season, teams per division, a venue and the first games — from a spreadsheet, a photo of a draw, a fixture list or plain English. Use when the user wants to set up a season, create divisions, add teams, load a draw or schedule games on their xsyte without clicking through admin: \"set up our winter league\", \"add these teams to U15\", \"load this draw\", \"schedule the first round\", \"build the tournament from this spreadsheet\", \"what's on my site already\". Needs XSYTE_SITE_URL and XSYTE_API_KEY in the environment (the key comes from admin → Settings → API keys). Always dry-run first and show the plan; apply only when the user says go. Not for page content, Brand Kit or forms — those have their own skills."
---

# xsyte season builder — the 15-minute setup, from a prompt

The platform has one endpoint that builds a whole competition from a single
JSON payload, and one that reports what already exists. This skill is the
discipline around them: **read first, plan second, apply only on a yes.**

## 0 · Preconditions

Two environment variables, never pasted into the conversation:

```
XSYTE_SITE_URL   https://club.hockeysyte.com      (no trailing slash)
XSYTE_API_KEY    the site's key from admin → Settings → API keys
```

Check with `scripts/xsyte_api.sh check`. If either is missing, stop and tell
the user where the key lives (admin → Settings → API keys → Create API key)
and how to set the variables. Do not ask them to paste the key into chat; if
they do anyway, tell them to rotate it afterwards from the same page.

The key is scoped to one site. `XSYTE_SITE_URL` must be that site.

## 1 · Read what exists

```bash
scripts/xsyte_api.sh structure
```

Returns the site's sport, every division group → division → active/upcoming
season → team names, and every venue → game location. **Always run this
first**, and again after any apply. It answers most questions before a
payload is written:

- the user says "add teams to U15" → find the group and season U15 lives in,
  and send a payload naming that group so the builder reuses it;
- the user says "set up our winter league" and a group called that already
  exists → extend it, don't make a second one;
- the user names a venue → match it by name; a payload with the same venue
  name reuses the row.

Say back what you found in one or two lines before building anything.

## 2 · Turn the ask into a payload

The payload shape is in `references/payload.md` — read it before writing
one. The short version:

```json
{
  "dry_run": true,
  "type": "league",
  "name": "Winter League",
  "divisions": ["Seniors", "Juniors"],
  "season": "Winter 2026",
  "teams": { "Seniors": ["Sharks", "Bears", "Wolves"], "Juniors": 6 },
  "venue": { "name": "Boondall Ice Arena", "city": "Brisbane", "state": "QLD" },
  "games": [ { "division": "Seniors", "date": "2026-10-24", "time": "19:30", "home": "Sharks", "away": "Bears" } ]
}
```

Rules that matter when drafting:

- **Names are the keys.** Group, division, season and venue are matched by
  exact (case-insensitive) name. A replayed payload reuses what exists and
  only adds what is new — so it is safe to send the whole thing again.
- **Teams per division, never shared.** `teams` is keyed by division name.
  A number means placeholders (`Team 1`…`Team n`) the club renames later.
  A division with one team is rejected; leave it out or add a second.
- **Casual games** make their own two sides, `Light` and `Dark`; `teams` is
  ignored. Use `weekly` for a recurring slot.
- **Tournaments** can take `round_robin` for one division: every team plays
  every team on one date, one hour per round, and the club fixes times
  afterwards.
- **Games need a venue.** Either `venue: {name: …}` (new or matched) or
  `venue: {venue_id: N}` / `{location_id: N}` from the structure read.
- Dates are `YYYY-MM-DD`; times are 24-hour `HH:MM`. Convert what the user
  gave you and say so if you had to assume a year or a time.

From a **spreadsheet or CSV**: one column of team names per division is the
common shape — read it with the usual tools, de-duplicate, trim, and keep
the user's spelling. From a **photo of a draw**: transcribe teams and
fixtures, then show the transcription before the dry run, because a wrong
read of a name becomes a wrong team on a public site. From **plain
English**: ask only for what the payload cannot default — the user's "put
the first game on Saturday" needs a date and a time.

Write the payload to a file (never inline in the shell command — quoting
breaks on apostrophes like *Men's*):

```bash
cat > /tmp/competition.json <<'JSON'
{ ... }
JSON
```

## 3 · Dry run, show the plan, wait

```bash
scripts/xsyte_api.sh competition /tmp/competition.json
```

Without `--apply` the script forces `dry_run: true`. The response's `plan`
says, per thing, `create` or `existing`, the team counts, the venue it
resolved, and the first ten games. Summarise it for the user in their
words — "creates group Winter League, 2 divisions, season Winter 2026, 3 + 6
teams, reuses Boondall Ice Arena, 1 game" — and **stop**. The user says go.

A `422` lists every problem at once (`code`, `message`, `path`, `hint`, often
`valid_values`). Fix them all, dry-run again. Do not apply a payload that
has not had a clean dry run.

## 4 · Apply, then read again

```bash
scripts/xsyte_api.sh competition /tmp/competition.json --apply
```

One transaction: everything or nothing. The response carries the ids and a
`summary`. Then `structure` once more and tell the user what the site now
has, with the public link to the season (`$XSYTE_SITE_URL/season/<season_id>`).
Placeholder logos are generated for new groups, divisions and teams; real
logos replace them in admin.

## 5 · Smaller jobs: teams or games in one season

When the group and season already exist and the user only wants teams added
or a schedule loaded, the lower-level endpoints are simpler and equally
idempotent — `references/seasons-endpoints.md` has both:

```bash
scripts/xsyte_api.sh seasons-structure <season_id>                       # GET
scripts/xsyte_api.sh seasons-structure <season_id> teams.json [--apply]  # POST
scripts/xsyte_api.sh seasons-schedule  <season_id>                       # GET
scripts/xsyte_api.sh seasons-schedule  <season_id> games.json [--apply]  # POST
```

The schedule endpoint never changes a game that already has results, so
reloading a corrected fixture list is safe.

## What this skill does not do

- Rosters and players — not in the API yet; point the user at the season's
  admin page or the registration link.
- Deleting anything. Nothing here removes a team, game or season.
- Brand Kit, pages, forms — `xsyte-brand-kit`, `xsyte-page-blocks`,
  `xsyte-custom-page-with-form`.
