# Full-bleed, scoped

`#full-width-content` **is** a Foundation row: `max-width:75rem`, auto margins,
and a gutter on its `.columns` child. All three have to go for a band to reach
the viewport edge.

## Do not do it unconditionally

Lifting the cap site-wide drags the platform's own pages out with it — calendar,
event detail, news, contact all suddenly run full width with no container. Back
it off again and your designed pages get squeezed instead. The two needs are
opposite, so the rule has to know which page it is on.

Key it off markup only your pages have. A Custom Layout emits a section
wrapper; a standalone custom page opens with your hero or a band class:

```css
#full-width-content.row:has(.hp-section, .your-hero, [class*="your-band"]){
  max-width:none !important; width:100%; padding:0 !important; margin:0 !important;
}
#full-width-content.row:has(.hp-section, .your-hero, [class*="your-band"]) > .columns{
  padding-left:0 !important; padding-right:0 !important;
}
```

Platform pages then get **no override at all**, which is exactly why they come
out correctly centred — that is Foundation's own `.row{margin:0 auto}` working
once you stop fighting it.

Provide a `@supports not selector(:has(*))` fallback that reverts to the old
blanket behaviour.

## Foundation 6 does not uncap nested rows

This is the part that catches people, because Foundation 5 did:

- **F5**: `.row .row { max-width: none }` — a nested row uncaps itself.
- **F6**: keeps the 75rem cap and only swaps the auto margins for
  `-0.9375rem` gutters.

Two consequences, both measured on a real build:

1. A capped row with a *negative* margin renders 1200px **hard against the
   left**, not centred. (Measured at 1920: `x=-15, w=1200`.)
2. Lift the cap but leave the margins and the row is `100% + 30px` — the
   document scrolls wider than the viewport, and bands at `width:100%` stop
   short of the scroll width. That is the mysterious gap on the right.
   (Measured at 390: `scrollWidth 400` vs `clientWidth 390`.)

Cap and margins are one problem. Kill them together on the rows your layout
blocks emit:

```css
.hp-section > .row{ margin-left:auto !important; margin-right:auto !important; }
.hp-section.hp-cols-1 > .row{
  max-width:none !important; width:100%; margin-left:0 !important; margin-right:0 !important;
}
.hp-section.hp-cols-1 > .row > .columns{ padding-left:0 !important; padding-right:0 !important; }
```

## Wide components need their own grid

A shared 2-up grid that only collapses at 639px is wrong for any component
wider than a card. A day-schedule card — spine, time column, title, action
button — was pushed 98px past its container between 640 and 900. Give wide
components their own grid class that collapses where they actually stop
fitting, and measure it rather than guessing the breakpoint.
