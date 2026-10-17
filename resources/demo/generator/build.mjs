// Rendert alle Demo-Produktbilder: node build.mjs [slug...]  → SVG + PNG in $OUT (Standard ./out, nicht einchecken)
// Danach WebP erzeugen, z. B.: python3 -c "from PIL import Image; Image.open('out/x.png').convert('RGB').save('../products/x.webp', 'WEBP', quality=80, method=6)"
// Kontaktbogen zur Sichtprüfung: python3 sheet.py out bogen.png 4
import { chromium } from '/opt/node22/lib/node_modules/playwright/index.mjs';
import { products } from './products.mjs';
import { W, H } from './common.mjs';
import fs from 'node:fs';
const out = process.env.OUT ?? './out';
fs.mkdirSync(out, { recursive: true });
const only = process.argv.slice(2);
const browser = await chromium.launch();
const page = await browser.newPage({ viewport: { width: W, height: H } });
for (const [slug, fn] of Object.entries(products)) {
    if (only.length && !only.some((o) => slug.startsWith(o))) continue;
    const s = fn();
    fs.writeFileSync(`${out}/${slug}.svg`, s);
    await page.setContent(`<html><body style="margin:0;background:#000">${s}</body></html>`);
    await page.screenshot({ path: `${out}/${slug}.png`, clip: { x: 0, y: 0, width: W, height: H } });
    console.log('ok', slug);
}
await browser.close();
