# Form templates for common shapes

Copy-paste templates for the most common xsyte custom-page forms. Each template assumes the user has already created a matching form in xsyte admin and knows the `form_id` and `page_id`.

Replace placeholder values:
- `FORM_ID` — numeric form ID
- `PAGE_ID` — numeric page ID
- Field names — must match what's in xsyte admin

## 1. Sponsor interest

For lead capture from prospective sponsors. Modeled on MIHWA Garmisch 2026.

```html
{{ form_open('/forms/handle_form_response') }}
{{ form_hidden('form_id', 'FORM_ID') }}
{{ form_hidden('page_id', 'PAGE_ID') }}
<input type="text" name="website" value="" class="honeypot" tabindex="-1" autocomplete="off" />

<label>Company / Brand</label>
<input type="text" name="company" required>

<label>Your Name</label>
<input type="text" name="name" required>

<label>Email</label>
<input type="email" name="email" required>

<label>Phone (Optional)</label>
<input type="tel" name="phone">

<label>I'm Interested In</label>
<select name="sponsor_pack" required>
  <option value="">Choose a package...</option>
  <option value="masters">Masters Division — €2,500</option>
  <option value="veterans">Veterans Division — €2,000</option>
  <!-- etc -->
</select>

<label>Anything Else?</label>
<textarea name="comments" placeholder="Specifics, timing, requirements..."></textarea>


<button type="submit">Submit Interest</button>
{{ form_close() }}
```

## 2. Mailing list / VIP signup

For "notify me when X opens" / newsletter capture. Modeled on PowerPlay 209's signup form.

```html
{{ form_open('/forms/handle_form_response') }}
{{ form_hidden('form_id', 'FORM_ID') }}
{{ form_hidden('page_id', 'PAGE_ID') }}
<input type="text" name="website" value="" class="honeypot" tabindex="-1" autocomplete="off" />

<label>First Name</label>
<input type="text" name="firstname" required>

<label>Last Name</label>
<input type="text" name="lastname" required>

<label>Email</label>
<input type="email" name="email" required>

<label>Phone (Optional)</label>
<input type="tel" name="phone">

<!-- Multi-interest checkboxes — pair each checkbox with a hidden default
     so the field is always submitted with either "Yes" or "-". This is the
     pattern xsyte forms use for boolean tag fields. -->
<div class="checkbox-group">
  {{ form_hidden('hockey', '-') }}
  <input type="checkbox" name="hockey" value="Yes" id="opt_hockey">
  <label for="opt_hockey">Inline Hockey</label>
</div>

<div class="checkbox-group">
  {{ form_hidden('updates', '-') }}
  <input type="checkbox" name="updates" value="Yes" id="opt_updates">
  <label for="opt_updates">General Updates</label>
</div>

<label>Experience Level</label>
<select name="experience">
  <option value="">Select...</option>
  <option value="new">Brand new</option>
  <option value="beginner">Beginner</option>
  <option value="intermediate">Intermediate</option>
  <option value="advanced">Advanced</option>
  <option value="pro">Former pro / semi-pro</option>
</select>

<label>Anything Else?</label>
<textarea name="message"></textarea>


<button type="submit">Sign Me Up</button>
{{ form_close() }}
```

## 3. Event / tournament team registration

For team captains registering a team into a tournament.

```html
{{ form_open('/forms/handle_form_response') }}
{{ form_hidden('form_id', 'FORM_ID') }}
{{ form_hidden('page_id', 'PAGE_ID') }}
<input type="text" name="website" value="" class="honeypot" tabindex="-1" autocomplete="off" />

<label>Team Name</label>
<input type="text" name="team_name" required>

<label>Captain's Name</label>
<input type="text" name="captain_name" required>

<label>Captain's Email</label>
<input type="email" name="captain_email" required>

<label>Captain's Phone</label>
<input type="tel" name="captain_phone" required>

<label>Division</label>
<select name="division" required>
  <option value="">Choose a division...</option>
  <option value="masters">Masters</option>
  <option value="veterans">Veterans</option>
  <option value="legends">Legends</option>
  <option value="womens">Women's</option>
</select>

<label>Number of Players</label>
<input type="number" name="player_count" min="1" max="20" required>

<label>Home Country / Region</label>
<input type="text" name="home_region">

<label>Notes / Special Requirements</label>
<textarea name="notes"></textarea>


<button type="submit">Register Team</button>
{{ form_close() }}
```

## 4. Contact / inquiry

Simple general-purpose contact form.

```html
{{ form_open('/forms/handle_form_response') }}
{{ form_hidden('form_id', 'FORM_ID') }}
{{ form_hidden('page_id', 'PAGE_ID') }}
<input type="text" name="website" value="" class="honeypot" tabindex="-1" autocomplete="off" />

<label>Your Name</label>
<input type="text" name="name" required>

<label>Email</label>
<input type="email" name="email" required>

<label>Subject</label>
<select name="subject" required>
  <option value="">What's this about?</option>
  <option value="general">General question</option>
  <option value="registration">Registration help</option>
  <option value="technical">Technical issue</option>
  <option value="press">Press / media</option>
</select>

<label>Message</label>
<textarea name="message" required></textarea>


<button type="submit">Send Message</button>
{{ form_close() }}
```

## 5. RSVP / event attendance

For RSVPing to an event with attendee count.

```html
{{ form_open('/forms/handle_form_response') }}
{{ form_hidden('form_id', 'FORM_ID') }}
{{ form_hidden('page_id', 'PAGE_ID') }}
<input type="text" name="website" value="" class="honeypot" tabindex="-1" autocomplete="off" />

<label>Your Name</label>
<input type="text" name="name" required>

<label>Email</label>
<input type="email" name="email" required>

<label>Attending?</label>
<select name="attending" required>
  <option value="">Choose...</option>
  <option value="yes">Yes — count me in</option>
  <option value="maybe">Maybe</option>
  <option value="no">No — sorry, can't make it</option>
</select>

<label>Number of Guests (Including You)</label>
<input type="number" name="guest_count" min="1" max="10" value="1">

<label>Dietary Restrictions / Notes</label>
<textarea name="notes"></textarea>


<button type="submit">RSVP</button>
{{ form_close() }}
```

## Patterns used across all templates

- `form_open` / `form_close` for CSRF
- Three required hidden fields: `form_id`, `page_id`, honeypot `website`
- the honeypot immediately before the submit button (no reCAPTCHA — nothing verifies one on custom forms)
- Field names match what's in xsyte admin (these templates are *examples* — confirm actual names with the user)
- Plain HTML for inputs/selects/textareas — no need for `{{ form_input() }}` helpers unless the user prefers them
- Submit button is plain `<button type="submit">` — no special class required
