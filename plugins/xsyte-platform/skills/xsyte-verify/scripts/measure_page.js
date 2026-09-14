#!/usr/bin/env node
/**
 * Measure a rendered xsyte page: contrast, horizontal overflow, container
 * geometry. Reports numbers, not impressions.
 *
 *   node measure_page.js rendered.html
 *   node measure_page.js rendered.html --widths=1920,1440,820,390
 *   node measure_page.js https://site/page --shot=out.png --scope="#full-width-content"
 *
 * Options
 *   --widths=…    comma separated viewport widths. Default 1440,820,390.
 *   --shot=FILE   full-page screenshot at the FIRST width.
 *   --scope=SEL   restrict the audit. Default #full-width-content, falling
 *                 back to body when that is absent.
 *   --css=FILE    extra stylesheet injected after everything else, for
 *                 trying a fix without editing the page.
 *   --offline     block every remote request. Faster and deterministic, but
 *                 remember that a missing stylesheet changes the answer.
 *
 * Needs Playwright: npx playwright install chromium
 */

const { chromium } = require('playwright');
const fs = require('fs');
const path = require('path');

const args = process.argv.slice(2);
const target = args.find(a => !a.startsWith('--'));
const opt = k => { const a = args.find(x => x.startsWith('--' + k + '=')); return a ? a.slice(k.length + 3) : null; };
const flag = k => args.includes('--' + k);

if (!target) {
  console.error('usage: node measure_page.js <file-or-url> [--widths=…] [--shot=out.png] [--scope=SEL] [--css=FILE] [--offline]');
  process.exit(2);
}

const widths = (opt('widths') || '1440,820,390').split(',').map(Number);
const scope = opt('scope') || '#full-width-content';
const shot = opt('shot');
const extraCss = opt('css') ? fs.readFileSync(opt('css'), 'utf8') : null;
const url = /^https?:/.test(target) ? target : 'file://' + path.resolve(target);

/* The audit runs in the page. Kept as one function so it can be read whole. */
function audit(scopeSel) {
  const lin = c => { c /= 255; return c <= 0.04045 ? c / 12.92 : Math.pow((c + 0.055) / 1.055, 2.4); };
  const px = s => (String(s).match(/[\d.]+/g) || [0, 0, 0]).map(Number);
  const lum = ([r, g, b]) => 0.2126 * lin(r) + 0.7152 * lin(g) + 0.0722 * lin(b);
  const ratio = (a, b) => { const x = lum(a), y = lum(b); const [hi, lo] = x > y ? [x, y] : [y, x]; return (hi + 0.05) / (lo + 0.05); };

  /* Walk up for the first opaque painted background. Returns null when the
     answer would be a guess — a gradient or a translucent layer. Reporting
     "unmeasurable" is honest; inventing a background is not. */
  const backdrop = el => {
    let n = el;
    while (n && n !== document.documentElement) {
      const cs = getComputedStyle(n);
      if (cs.backgroundImage !== 'none') return null;
      const p = px(cs.backgroundColor);
      if (!(p.length === 4 && p[3] === 0)) {
        if (p.length === 4 && p[3] < 1) return null;
        return p.slice(0, 3);
      }
      n = n.parentElement;
    }
    return [255, 255, 255];
  };

  const root = document.querySelector(scopeSel) || document.body;
  const fails = [];
  let unmeasurable = 0;

  root.querySelectorAll('*').forEach(el => {
    const hasText = [...el.childNodes].some(n => n.nodeType === 3 && n.textContent.trim());
    if (!hasText) return;
    const cs = getComputedStyle(el);
    if (cs.display === 'none' || cs.visibility === 'hidden' || parseFloat(cs.opacity) === 0) return;

    const back = backdrop(el);
    if (!back) { unmeasurable++; return; }

    let fg = px(cs.color);
    if (fg.length === 4 && fg[3] < 1) {
      const a = fg[3];
      fg = [0, 1, 2].map(i => Math.round(fg[i] * a + back[i] * (1 - a)));
    } else {
      fg = fg.slice(0, 3);
    }

    const size = parseFloat(cs.fontSize);
    const bold = parseInt(cs.fontWeight, 10) >= 700;
    const need = (size >= 24 || (size >= 18.66 && bold)) ? 3 : 4.5;
    const r = ratio(fg, back);
    if (r < need) {
      fails.push({
        sel: el.tagName.toLowerCase() + (el.className ? '.' + String(el.className).trim().split(/\s+/).join('.') : ''),
        text: el.textContent.trim().replace(/\s+/g, ' ').slice(0, 40),
        ratio: +r.toFixed(2), need
      });
    }
  });

  const vw = document.documentElement.clientWidth;
  const over = [];
  root.querySelectorAll('*').forEach(el => {
    const b = el.getBoundingClientRect();
    if (b.width > 0 && b.right > vw + 1) {
      over.push({
        sel: el.tagName.toLowerCase() + (el.className ? '.' + String(el.className).trim().split(/\s+/)[0] : ''),
        right: Math.round(b.right)
      });
    }
  });

  const geom = e => e ? { x: Math.round(e.getBoundingClientRect().x), w: Math.round(e.getBoundingClientRect().width) } : null;
  const wrapper = document.querySelector('#full-width-content');

  return {
    scrollWidth: document.documentElement.scrollWidth,
    clientWidth: vw,
    wrapper: geom(wrapper),
    firstInnerRow: geom(wrapper && wrapper.querySelector('.row')),
    fails, unmeasurable,
    overflow: over.slice(0, 8)
  };
}

(async () => {
  const browser = await chromium.launch();
  let bad = 0;

  for (const [i, width] of widths.entries()) {
    const ctx = await browser.newContext({ viewport: { width, height: 1000 } });
    if (flag('offline')) {
      await ctx.route('**/*', r => r.request().url().startsWith('file://') ? r.continue() : r.abort());
    }
    const page = await ctx.newPage();
    await page.goto(url, { waitUntil: 'domcontentloaded' });
    await page.waitForFunction(() => [...document.images].every(im => im.complete), null, { timeout: 15000 }).catch(() => {});
    await page.waitForTimeout(500);
    if (extraCss) await page.addStyleTag({ content: extraCss });

    const r = await page.evaluate(audit, scope);

    console.log(`\n── ${width}px ──────────────────────────────────`);
    console.log(`scrollWidth ${r.scrollWidth} / clientWidth ${r.clientWidth}` +
      (r.scrollWidth > r.clientWidth ? `   ⚠ ${r.scrollWidth - r.clientWidth}px of horizontal overflow` : ''));
    if (r.wrapper) console.log(`wrapper  x=${r.wrapper.x} w=${r.wrapper.w}` +
      (r.firstInnerRow ? `   first inner row  x=${r.firstInnerRow.x} w=${r.firstInnerRow.w}` : ''));

    if (r.overflow.length) {
      bad++;
      console.log('overflowing elements:');
      r.overflow.forEach(o => console.log(`   ${o.sel} right=${o.right}`));
    }

    if (r.fails.length) {
      bad++;
      console.log(`contrast failures: ${r.fails.length}`);
      r.fails.forEach(f => console.log(`   ${String(f.ratio).padStart(6)} (needs ${f.need})  ${f.sel}  "${f.text}"`));
    } else {
      console.log('contrast failures: none');
    }
    if (r.unmeasurable) {
      console.log(`(${r.unmeasurable} elements over a gradient or translucent layer — not measurable automatically, check by eye)`);
    }

    if (shot && i === 0) {
      await page.screenshot({ path: shot, fullPage: true, timeout: 30000 });
      console.log(`screenshot → ${shot}`);
    }
    await ctx.close();
  }

  await browser.close();
  process.exit(bad ? 1 : 0);
})();
