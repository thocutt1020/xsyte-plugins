# Twig success state pattern

After form submission, xsyte re-renders the same page with `success=true` in the Twig context. Wrap content in a conditional to flip the page to a thank-you state.

## The basic conditional

```twig
{% if success %}
  <h1>Thanks!</h1>
  <p>We'll be in touch.</p>
{% else %}
  [the form goes here]
{% endif %}
```

## The two-conditional pattern (recommended)

For a polished feel, wrap BOTH the hero AND the form section in their own conditionals so both swap independently. The rest of the page (proof points, packages, browseable content) stays visible — the user can keep exploring, share with a colleague, or click into other sections.

```twig
<header class="hero">
{% if success %}
  [celebratory hero — checkmark, "thanks", CTA back to browsing]
{% else %}
  [original hero — countdown, urgency, value prop]
{% endif %}
</header>

[unchanged middle sections — proof points, packages, etc.]

<section class="form-section">
{% if success %}
  [thank-you panel with "what happens next" steps]
{% else %}
  [the form]
{% endif %}
</section>
```

## A polished celebratory hero — full snippet

Yellow ring with black checkmark, animated pop-and-glow, themed to MIHWA's palette. Adapt colours to whatever brand the page is wearing.

```html
<div class="hero-success">
  <div class="hero-success-ring">
    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="3">
      <path d="M20 6L9 17l-5-5"></path>
    </svg>
  </div>
  <div class="hero-tag">Interest Received</div>
  <h1>Thanks — <span class="highlight">we've got it</span></h1>
  <p class="hero-success-sub">You're in the queue</p>
  <p class="hero-success-detail">
    Our team will be in touch within 24 hours to confirm next steps.
  </p>
  <a href="#packages" class="hero-success-cta">Keep Browsing ↓</a>
</div>
```

CSS for the ring:

```css
.hero-success-ring {
  width: 120px;
  height: 120px;
  margin: 0 auto 32px;
  border-radius: 50%;
  background: var(--brand-accent);
  border: 5px solid var(--brand-dark);
  display: flex;
  align-items: center;
  justify-content: center;
  box-shadow: 0 0 40px rgba(255, 199, 44, 0.5);
  animation: success-pop 0.6s ease both, success-glow 3s ease-in-out 0.6s infinite;
}

.hero-success-ring svg {
  width: 60px;
  height: 60px;
  color: var(--brand-dark);
  stroke-width: 3;
}

@keyframes success-pop {
  0% { transform: scale(0); opacity: 0; }
  50% { transform: scale(1.2); }
  100% { transform: scale(1); opacity: 1; }
}

@keyframes success-glow {
  0%, 100% { box-shadow: 0 0 40px rgba(255, 199, 44, 0.5); }
  50% { box-shadow: 0 0 60px rgba(255, 199, 44, 0.7); }
}
```

## A "what happens next" thank-you panel — full snippet

Replaces the form section with a numbered list of expectations. Helps the user feel like the conversation isn't over — they know what to expect.

```html
<div class="success-panel">
  <div class="success-panel-icon">
    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="3">
      <path d="M20 6L9 17l-5-5"></path>
    </svg>
  </div>
  <h3>You're In The Queue</h3>
  <p>Your interest is timestamped. We treat slots first in best dressed.</p>

  <div class="success-steps">
    <ol>
      <li><strong>Within 24 hours</strong> — a real person from our team reaches out.</li>
      <li><strong>We confirm details</strong> — package, timing, assets.</li>
      <li><strong>Goes live</strong> — your branding is up across the site.</li>
    </ol>
  </div>

  <p>Questions before then? <strong>contact@example.com</strong></p>
</div>
```

CSS for the steps numbering:

```css
.success-steps ol {
  list-style: none;
  counter-reset: step;
}

.success-steps li {
  counter-increment: step;
  padding-left: 44px;
  position: relative;
  margin-bottom: 14px;
}

.success-steps li::before {
  content: counter(step);
  position: absolute;
  left: 0;
  top: -2px;
  width: 28px;
  height: 28px;
  background: var(--brand-dark);
  color: var(--brand-accent);
  border-radius: 50%;
  display: flex;
  align-items: center;
  justify-content: center;
  font-family: var(--display-font);
  font-weight: 600;
}
```

## Testing the success state during development

Replace `{% if success %}` with `{% if true %}` temporarily to preview the success branch in the editor without submitting the form. **Restore before delivering** — the unrestored test version will permanently show the success state and break the actual page flow.

## The single-conditional shortcut (when in a hurry)

If only one swap is needed (say, just hide the form and show a small thank-you message), one conditional around the form section is fine. The hero stays as-is. Less polished but still functional.

```twig
<section class="form-section">
{% if success %}
  <div class="thank-you">
    <p>Thanks — we'll be in touch.</p>
  </div>
{% else %}
  [form]
{% endif %}
</section>
```

Ship the polished two-conditional version when the page is sales-facing or external-shareable; the shortcut is fine for internal tools and quick utility forms.
