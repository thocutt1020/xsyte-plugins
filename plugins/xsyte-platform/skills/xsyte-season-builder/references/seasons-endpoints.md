# Season-level endpoints — teams and games in one season

Use these when the group, division and season already exist (find the
`season_id` with `GET /api/setup/structure`) and the job is only teams or
only games. Both are idempotent and support `dry_run`.

## `GET /api/seasons/structure?season_id=N`

Conferences and teams in the season, with ids, nicknames, colours.

## `POST /api/seasons/structure`

```json
{ "season_id": 12, "dry_run": true, "on_conflict": "update",
  "conferences": [
    { "conference_name": "North", "ranking": 1,
      "teams": [ { "team_full_name": "Chermside Tavern", "team_nickname": "Tav" } ] }
  ],
  "teams": [ { "team_full_name": "Yard Doggs" } ]      // flat list, no conference
}
```

- `team_full_name` is the upsert key (exact match within the season).
- `on_conflict`: `update` (default — matching teams get the fields you sent),
  `skip`, or `error`.
- Optional team fields: `team_nickname`, `team_abbreviation`, `dark_color`,
  `light_color`, jersey colours.
- Response: `summary` with `teams_created` / `teams_updated` / `teams_skipped`,
  plus every team with its `team_id` and `action`. Replaying gives
  `teams_created: 0`.

## `GET /api/seasons/schedule?season_id=N`

Every game in the season with ids, teams, date/time, location, round, type
and whether results exist.

## `POST /api/seasons/schedule`

```json
{ "season_id": 12, "dry_run": true, "on_conflict": "update",
  "games": [
    { "date": "2026-08-15", "time": "17:00", "home_team": "Chermside Tavern",
      "away_team": "Yard Doggs", "round": 1, "game_type": "RS",
      "location": "Rink 1", "comment": "" },
    { "date": "2026-09-12", "time": "17:00", "home_team": "TBD", "away_team": "TBD",
      "round": 2, "game_type": "QF", "comment": "QF1 - North winner v South runner-up" }
  ]
}
```

- `home_team` / `away_team`: exact team name, numeric `team_id`, or `"TBD"`.
- A game matches an existing one on date + teams; send `game_id` to pin a
  specific game (that is how a reschedule works).
- `location`: name or id; omitted = the season's default (first) location.
- `game_type`: short code (`RS`, `QF`, `SF`, `F`, …) or id; the error lists
  valid codes.
- **A game with recorded results is never modified** — it reports `skipped`
  with a reason. Safe to reload a corrected fixture list mid-season.
- A team cannot be given two games at the same minute; the error names both.

## Error shape

Identical across all endpoints:

```json
{ "status": "failed", "errors": [
  { "code": "team_not_found", "message": "...", "path": "games[3].home_team",
    "hint": "...", "valid_values": ["..."] } ] }
```
