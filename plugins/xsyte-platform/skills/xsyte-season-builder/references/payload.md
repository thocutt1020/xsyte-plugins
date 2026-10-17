# `POST /api/setup/competition` — the payload

One JSON object. Everything is matched by exact, case-insensitive, trimmed
name, so a replayed payload converges instead of duplicating.

```json
{
  "dry_run": true,                       // true = return the plan, write nothing
  "type": "league",                      // league | tournament | casual
  "name": "Winter League",               // the division group (required)
  "divisions": ["Seniors", "Juniors"],   // >= 1 name (required)
  "season": "Winter 2026",               // default: the current year, e.g. "2026"

  "teams": {                             // keyed by division name
    "Seniors": ["Sharks", "Bears", "Wolves", "Eagles"],   // names, or
    "Juniors": 6                         // a count -> placeholders Team 1..6
  },

  "venue": { "name": "Boondall Ice Arena", "address": "1 Entertainment Dr",
             "city": "Boondall", "state": "QLD", "postcode": "4034",
             "location": "Rink 1" },     // or { "venue_id": 3 } or { "location_id": 7 }

  "games": [
    { "division": "Seniors", "date": "2026-10-24", "time": "19:30",
      "home": "Sharks", "away": "Bears", "round": 1 }
  ],
  "round_robin": { "division": "Juniors", "date": "2026-11-07", "time": "09:00" },
  "weekly": { "division": "Thursdays", "date": "2026-10-22", "time": "20:00",
              "count": 4, "home": "Light", "away": "Dark" }
}
```

## Field notes

| Field | Notes |
| --- | --- |
| `type` | Decides defaults and vocabulary. `casual` creates two standing sides `Light` / `Dark` per division and ignores `teams`. |
| `name` | Division group. Existing name → reused (`"action": "existing"` in the plan). |
| `divisions` | Each becomes a division in the group and gets a season named `season`. Existing division + season pair → reused. |
| `season` | One name applied to every division in the payload. Use `"2026"` for a tournament; `"Winter 2026"` when a club runs more than one a year. |
| `teams` | Keys must be names from `divisions`. A division not listed gets no teams (fine — the club adds them later). One team is an error. Team names are the upsert key inside the season: resending the same names changes nothing. |
| `venue` | Required only when there are games. `name` is matched against existing venues first; a new venue gets one game location named `location` (default: the venue name). `venue_id` uses that venue's first location; `location_id` is exact. |
| `games[]` | `division` may be omitted when the payload has exactly one division. `home` / `away` must be team names in that division — either already there or in `teams` in this payload. `time` optional (TBA). |
| `round_robin` | Tournament helper. Every team in the division plays every other team on `date`; rounds are one hour apart starting at `time`. Needs 3+ teams. |
| `weekly` | Casual helper. `count` games (default 4) a week apart, home/away alternating. `home`/`away` default to the first two teams. |

Dates: `YYYY-MM-DD`. Times: `HH:MM`, 24-hour. Season status is set to active.

## Responses

**Dry run (200)**

```json
{ "status": "ok", "dry_run": true,
  "plan": { "type": "league",
            "group": { "name": "Winter League", "cluster_id": 0, "action": "create" },
            "season": "Winter 2026",
            "divisions": [ { "name": "Seniors", "division_id": 0, "division_action": "create",
                             "season_id": 0, "season_action": "create" } ],
            "teams": { "Seniors": 4, "Juniors": 6 },
            "venue": { "action": "existing", "venue_id": 1, "location_id": 1, "name": "Boondall Ice Arena" },
            "games": 16,
            "games_preview": [ ... first 10 ... ] } }
```

**Apply (200)**

```json
{ "status": "ok", "dry_run": false,
  "cluster_id": 7,
  "divisions": [ { "name": "Seniors", "division_id": 11, "season_id": 11, "division": "create", "season": "create" } ],
  "season_ids": [11, 12],
  "teams": { "Seniors": { "Sharks": 35, "Bears": 36 } },
  "venue": { "venue_id": 1, "location_id": 1, "action": "existing" },
  "games": { "Seniors": [14], "Juniors": [15, 16] },
  "summary": { "group": "create", "divisions": 2, "teams": 10, "games": 16 } }
```

Public season page: `$XSYTE_SITE_URL/season/<season_id>`.

**Validation failure (422)** — every problem, not just the first:

```json
{ "status": "failed", "errors": [
  { "code": "division_not_found", "message": "teams.U15 does not match a division in this payload.",
    "path": "teams.U15", "hint": "Keys of \"teams\" must be names from \"divisions\".",
    "valid_values": ["Seniors", "Juniors"] } ] }
```

Codes: `invalid_json`, `invalid_field`, `missing_required_field`,
`division_not_found`, `team_not_found`, `venue_not_found`,
`location_not_found`, `apply_failed` (500 — nothing written, retry).

**Auth failure (403)** — `invalid_api_key` or `no_xsyte_resolved`. The key is
wrong, revoked, or for a different site than `XSYTE_SITE_URL`.

## `GET /api/setup/structure`

```json
{ "xsyte_id": 610, "org_name": "ADWGC", "sport_id": 1, "sport": "Ice Hockey",
  "groups": [ { "cluster_id": 2, "name": "Winter League",
                "divisions": [ { "division_id": 1, "name": "Seniors", "sport_id": 1,
                                 "seasons": [ { "season_id": 1, "season_name": "Winter 2026",
                                                "season_status": "active",
                                                "teams": ["Bears", "Sharks"] } ] } ] } ],
  "venues": [ { "venue_id": 1, "name": "Boondall Ice Arena", "city": "Boondall",
                "locations": [ { "location_id": 1, "name": "Rink 1" } ] } ],
  "onboarding": "done" }
```

Only active and upcoming seasons are listed. `onboarding` is `active` while
the site's new-admin wizard is still locked, `done`, `skipped`, or `none`
for a site that predates it.

## Worked examples

**From a spreadsheet** with columns `Division, Team`:

```json
{ "dry_run": true, "type": "league", "name": "Winter League",
  "divisions": ["A Grade", "B Grade", "C Grade"], "season": "Winter 2026",
  "teams": { "A Grade": ["Thunder", "Storm"], "B Grade": ["Lightning", "Hail", "Sleet"], "C Grade": ["Drizzle", "Mist"] } }
```

**A one-day tournament, entries open**:

```json
{ "dry_run": true, "type": "tournament", "name": "Club Cup 2026",
  "divisions": ["Open", "Women's"], "season": "2026",
  "teams": { "Open": 8, "Women's": 4 },
  "venue": { "name": "Boondall Ice Arena" },
  "round_robin": { "division": "Women's", "date": "2026-11-07", "time": "09:00" } }
```

**Thursday pickup**:

```json
{ "dry_run": true, "type": "casual", "name": "Thursday Pickup",
  "divisions": ["Thursdays"], "season": "2026",
  "venue": { "name": "Boondall Ice Arena" },
  "weekly": { "division": "Thursdays", "date": "2026-10-22", "time": "20:00", "count": 8 } }
```

**Adding a division to an existing group** — same group name, new division
only; everything else reports `existing`:

```json
{ "dry_run": true, "type": "league", "name": "Winter League",
  "divisions": ["Masters"], "season": "Winter 2026",
  "teams": { "Masters": ["Old Boys", "Legends", "Vintage"] } }
```
