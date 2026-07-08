# Form templates for common shapes

Copy-paste templates for the most common xsyte custom-page forms. Each template assumes the user has already created a matching form in xsyte admin and knows the `form_id` and `page_id`.

**All form controls use CI form helpers, never raw HTML.** The xsyte page editor is a textarea-based WYSIWYG — a raw `<textarea>` closing tag terminates the editor's own field and destroys the page on save, and raw `<input>`/`<select>`/`<button>` elements render live inside the editing surface. Helpers are stored as plain text and only become controls at render time.

**Helpers take positional arguments — `(name, value, extra)` — not attribute hashes.** The `extra` argument is a plain string of HTML attributes: `'id="email" required placeholder="..."'`. See SKILL.md for the full signature list.

Replace placeholder values:
- `FORM_ID` — numeric form ID
- `PAGE_ID` — numeric page ID
- Field names — must match what's in xsyte admin

**No reCAPTCHA** — the honeypot `website` field is the spam defense; the forms handler doesn't validate reCAPTCHA. Only add a widget if the league specifically asks.

## 1. Sponsor interest

For lead capture from prospective sponsors. Modeled on MIHWA Garmisch 2026.

```twig
{{ form_open('/forms/handle_form_response') }}
{{ form_hidden('form_id', 'FORM_ID') }}
{{ form_hidden('page_id', 'PAGE_ID') }}
{{ form_input('website', '', 'class="honeypot" tabindex="-1" autocomplete="off"') }}

<label>Company / Brand</label>
{{ form_input('company', '', 'required') }}

<label>Your Name</label>
{{ form_input('name', '', 'required') }}

<label>Email</label>
{{ form_input('email', '', 'required') }}

<label>Phone (Optional)</label>
{{ form_input('phone', '') }}

<label>I'm Interested In</label>
{{ form_dropdown('sponsor_pack', {
    '': 'Choose a package...',
    'masters': 'Masters Division — €2,500',
    'veterans': 'Veterans Division — €2,000'
}, '', 'required') }}

<label>Anything Else?</label>
{{ form_textarea('comments', '', 'placeholder="Specifics, timing, requirements..."') }}


{{ form_submit('submit', 'Submit Interest') }}
{{ form_close() }}
```

## 2. Mailing list / VIP signup

For "notify me when X opens" / newsletter capture. Modeled on PowerPlay 209's signup form.

```twig
{{ form_open('/forms/handle_form_response') }}
{{ form_hidden('form_id', 'FORM_ID') }}
{{ form_hidden('page_id', 'PAGE_ID') }}
{{ form_input('website', '', 'class="honeypot" tabindex="-1" autocomplete="off"') }}

<label>First Name</label>
{{ form_input('firstname', '', 'required') }}

<label>Last Name</label>
{{ form_input('lastname', '', 'required') }}

<label>Email</label>
{{ form_input('email', '', 'required') }}

<label>Phone (Optional)</label>
{{ form_input('phone', '') }}

{# Multi-interest checkboxes — pair each checkbox with a hidden default
   so the field is always submitted with either "Yes" or "-". This is the
   pattern xsyte forms use for boolean tag fields. #}
<div class="checkbox-group">
  {{ form_hidden('hockey', '-') }}
  {{ form_checkbox('hockey', 'Yes', false, 'id="opt_hockey"') }}
  <label for="opt_hockey">Inline Hockey</label>
</div>

<div class="checkbox-group">
  {{ form_hidden('updates', '-') }}
  {{ form_checkbox('updates', 'Yes', false, 'id="opt_updates"') }}
  <label for="opt_updates">General Updates</label>
</div>

<label>Experience Level</label>
{{ form_dropdown('experience', {
    '': 'Select...',
    'new': 'Brand new',
    'beginner': 'Beginner',
    'intermediate': 'Intermediate',
    'advanced': 'Advanced',
    'pro': 'Former pro / semi-pro'
}) }}

<label>Anything Else?</label>
{{ form_textarea('message', '') }}


{{ form_submit('submit', 'Sign Me Up') }}
{{ form_close() }}
```

## 3. Event / tournament team registration

For team captains registering a team into a tournament.

```twig
{{ form_open('/forms/handle_form_response') }}
{{ form_hidden('form_id', 'FORM_ID') }}
{{ form_hidden('page_id', 'PAGE_ID') }}
{{ form_input('website', '', 'class="honeypot" tabindex="-1" autocomplete="off"') }}

<label>Team Name</label>
{{ form_input('team_name', '', 'required') }}

<label>Captain's Name</label>
{{ form_input('captain_name', '', 'required') }}

<label>Captain's Email</label>
{{ form_input('captain_email', '', 'required') }}

<label>Captain's Phone</label>
{{ form_input('captain_phone', '', 'required') }}

<label>Division</label>
{{ form_dropdown('division', {
    '': 'Choose a division...',
    'masters': 'Masters',
    'veterans': 'Veterans',
    'legends': 'Legends',
    'womens': "Women's"
}, '', 'required') }}

<label>Number of Players</label>
{{ form_input('player_count', '', 'required') }}

<label>Home Country / Region</label>
{{ form_input('home_region', '') }}

<label>Notes / Special Requirements</label>
{{ form_textarea('notes', '') }}


{{ form_submit('submit', 'Register Team') }}
{{ form_close() }}
```

## 4. Contact / inquiry

Simple general-purpose contact form.

```twig
{{ form_open('/forms/handle_form_response') }}
{{ form_hidden('form_id', 'FORM_ID') }}
{{ form_hidden('page_id', 'PAGE_ID') }}
{{ form_input('website', '', 'class="honeypot" tabindex="-1" autocomplete="off"') }}

<label>Your Name</label>
{{ form_input('name', '', 'required') }}

<label>Email</label>
{{ form_input('email', '', 'required') }}

<label>Subject</label>
{{ form_dropdown('subject', {
    '': "What's this about?",
    'general': 'General question',
    'registration': 'Registration help',
    'technical': 'Technical issue',
    'press': 'Press / media'
}, '', 'required') }}

<label>Message</label>
{{ form_textarea('message', '', 'required') }}


{{ form_submit('submit', 'Send Message') }}
{{ form_close() }}
```

## 5. RSVP / event attendance

For RSVPing to an event with attendee count.

```twig
{{ form_open('/forms/handle_form_response') }}
{{ form_hidden('form_id', 'FORM_ID') }}
{{ form_hidden('page_id', 'PAGE_ID') }}
{{ form_input('website', '', 'class="honeypot" tabindex="-1" autocomplete="off"') }}

<label>Your Name</label>
{{ form_input('name', '', 'required') }}

<label>Email</label>
{{ form_input('email', '', 'required') }}

<label>Attending?</label>
{{ form_dropdown('attending', {
    '': 'Choose...',
    'yes': 'Yes — count me in',
    'maybe': 'Maybe',
    'no': "No — sorry, can't make it"
}, '', 'required') }}

<label>Number of Guests (Including You)</label>
{{ form_input('guest_count', '1') }}

<label>Dietary Restrictions / Notes</label>
{{ form_textarea('notes', '') }}


{{ form_submit('submit', 'RSVP') }}
{{ form_close() }}
```

## 6. Radio group example

Radios follow the same positional pattern — `form_radio(name, value, checked, extra)` — wrapped in labels:

```twig
<label>Gear Hire Required?</label>
<div class="radio-group">
  <label>{{ form_radio('gear_hire', 'Yes', false, 'required') }} Yes — add gear hire</label>
  <label>{{ form_radio('gear_hire', 'No', false) }} No — player has own gear</label>
</div>
```

## 7. Date field example

Dates use the shell's Foundation datepicker via class, not `type="date"`:

```twig
<label>Date of Birth</label>
{{ form_input('player_dob', '', 'class="fdatepicker" required') }}
```

## Patterns used across all templates

- `form_open` / `form_close` for CSRF
- Three required hidden fields: `form_id`, `page_id`, honeypot `website`
- No reCAPTCHA — the honeypot is the spam defense
- Field names match what's in xsyte admin (these templates are *examples* — confirm actual names with the user)
- **Every form control via CI helpers with positional args** — `form_input(name, value, extra)`, `form_textarea(name, value, extra)`, `form_dropdown(name, options, selected, extra)`, `form_radio` / `form_checkbox(name, value, checked, extra)`, `form_hidden(name, value)`, `form_submit(name, value, extra)`. Raw `<input>` / `<textarea>` / `<select>` / `<button>` break the WYSIWYG page editor, and attribute-hash syntax is not supported.
- Labels, wrapper divs, and the reCAPTCHA div stay as plain HTML — they're harmless in the editor
- Inputs render as `type="text"` — email/phone/number validation is server-side; dates via `class="fdatepicker"`
- `form_submit` renders `<input type="submit">` — style its class rather than relying on `button` selectors
