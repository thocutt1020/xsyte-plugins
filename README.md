# xsyte-plugins

Claude plugins for building on the [xsyte](https://hockeysyte.com) sports-league platform (hockeysyte, sportssyte, soccersyte, and friends).

## Install

In Claude Code (or the Claude desktop app's plugin settings):

```
/plugin marketplace add YOUR_GITHUB_USER/xsyte-plugins
/plugin install xsyte-platform@xsyte-plugins
```

## What's inside

### xsyte-platform

**xsyte-custom-page-with-form** — builds custom xsyte pages that capture data through the xsyte forms system and flip to a thank-you state on submission. It encodes the platform wiring that's easy to get wrong:

- Form controls via CodeIgniter Twig helpers (positional arguments), never raw HTML — raw `<textarea>`/`<input>` tags destroy the page editor
- `form_open` / hidden `form_id` + `page_id` / offscreen honeypot wiring for `/forms/handle_form_response`
- `{% if success %}` thank-you states on hero and form sections
- Full-bleed chrome-hiding CSS for takeover pages
- Copy-paste templates for sponsor interest, signups, registration, contact, and RSVP forms

## Updating

Versions track git commits — pull updates with:

```
/plugin marketplace update xsyte-plugins
```

## License

MIT
