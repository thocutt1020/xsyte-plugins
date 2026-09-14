# What the page editor and Twig will do to your markup

The editor is TinyMCE, configured with:

```js
valid_elements: '*[*]',
extended_valid_elements: 'script[src|type|defer|async|charset],style[*]',
valid_children: '+body[script|style],+div[script],+section[script]',
forced_root_block: false,
```

So the schema is wide open — **`script` and `style` are both explicitly
allowed**, and `script` may sit inside `body`, `div` or `section`. TinyMCE
wraps the contents of both in comment markers on save, which is normal.

## Twig version

The platform runs **Twig 3.21**. Two removals matter:

**`{% for x in y if cond %}` is gone.** Deprecated in Twig 1, removed in
Twig 2. It raises `Unexpected token "name" of value "if"` and that is a fatal
parse error — a **white screen**, not a warning. Write:

```twig
{% for e in ds.events %}{% if e.day_name == 'Monday' %}
  …
{% endif %}{% endfor %}
```

The loop then always has iterations, so `{% else %}` on the `for` never fires
and the empty state is lost. Print it unconditionally and let CSS hide it:

```css
.list:has(.row) .list__empty{ display:none; }
```

**`y|filter(x => …)`**, Twig 3's replacement, is unusable in the page editor:
the arrow needs a greater-than sign and the editor encodes it.

## Things that produce a white screen

- **A Twig tag inside an HTML comment.** An HTML comment hides nothing from the
  Twig lexer. Writing `{% if x %}` as *documentation* in a header block really
  opens a block, and the parser then reports "Unexpected end of template" at
  the LAST line of the file. Describe the delimiters in words, or put example
  code inside a Twig comment (`{# … #}`), which the lexer does skip.
- **`{{ }}` with nothing in it**, for the same reason.

## Things the editor mangles

- **Never write a bare tag name in angle brackets** — including inside a Twig
  comment. `valid_elements: '*[*]'` means TinyMCE treats the mention as a real
  tag and balances it, leaving a stray closing tag in the saved markup.
- **Raw form controls destroy the page.** A raw `textarea`'s closing tag
  terminates the editor's own field and everything after it is mangled on save.
  A raw `input` becomes a live control in the editing surface. Both are
  validator **errors** — use the form helpers.
- **The editor strips `dl` / `dt` / `dd` on save.** Use `div` + `b` / `em`.
- **Paste via Tools ▸ Source code, never the visual pane.** The visual pane
  turns line breaks inside `style` and `script` blocks into `br` tags, which
  silently breaks both.

## Twig that does work

- `==`, `!=`, `in`, `and`, `or`, `not`
- `{% set %}` at top level, ternaries, `elseif`
- `|date` with an explicit timezone — always pass one; never rely on the server
  default or the visitor's browser
- `|slice`, `|split`, `|striptags`, `|replace({' ': '-'})`, `|first`, `|last`,
  `|length`, `~` concatenation
- Form helpers, all **positional**: `form_input('name', 'value', 'extras')`.
  The hash form is a validator error.

## No `<` or `>` anywhere in a Twig tag

The editor encodes them even inside `{{ }}` and `{% %}`. Time comparisons are
the usual casualty. The workaround that works: convert to minutes since
midnight and phrase every comparison as range membership —
`(a - b) in 0..1439` means "a is not before b".

## Before saving

Run **Check page content** in the page editor, or the `xsyte-verify` skill
locally, which does the same two checks against the platform's own Twig build.
