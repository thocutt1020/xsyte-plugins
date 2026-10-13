# xsyte-plugins

Claude plugins for building on the [xsyte](https://hockeysyte.com) sports-league platform (hockeysyte, sportssyte, soccersyte, and friends).

## Install

In Claude Code (or the Claude desktop app's plugin settings):

```
/plugin marketplace add thocutt1020/xsyte-plugins
/plugin install xsyte-platform@xsyte-plugins
```

## What's inside

### xsyte-platform

Four skills — full descriptions in [`plugins/xsyte-platform/README.md`](plugins/xsyte-platform/README.md):

- **xsyte-brand-kit** — the site-wide chrome (Brand Kit Code Mode CSS, header, footer) from a design handoff, built on the kit's `--bk-*` colour and font variables so clubs can still re-brand from the pickers.
- **xsyte-page-blocks** — custom pages and Custom Layout blocks fed by calendar, news and shop data sources, with Page Fields so clubs edit their own values.
- **xsyte-custom-page-with-form** — data-capture pages that post to the xsyte forms system and flip to a thank-you state.
- **xsyte-verify** — lint with the platform's own Twig, render with sample data, and measure contrast and overflow before anything is pasted.

## Updating

Versions track git commits — pull updates with:

```
/plugin marketplace update xsyte-plugins
```

## License

MIT
