// Gemeinsame SVG-Bausteine für die Demo-Produktbilder (Studio-Look, dunkles Anthrazit).
// Leinwand 2000×1500 (4:3, wie aspect-[4/3] in Produktkarte und Galerie).
export const W = 2000;
export const H = 1500;
export const FLOOR = 1180; // gedachte Bodenlinie für Schatten/Reflexion

/** Rechtwinklige Pfade aus Punktlisten; Punkte mit r runden die Ecke. */
export function poly(pts, close = true) {
    return 'M' + pts.map((p) => p.join(',')).join(' L') + (close ? ' Z' : '');
}

export const defs = `
<defs>
  <radialGradient id="bg" cx="50%" cy="40%" r="75%">
    <stop offset="0" stop-color="#3d3e42"/>
    <stop offset=".45" stop-color="#2a2b2e"/>
    <stop offset="1" stop-color="#121314"/>
  </radialGradient>
  <linearGradient id="floorGlow" x1="0" y1="0" x2="0" y2="1">
    <stop offset="0" stop-color="#000" stop-opacity="0"/>
    <stop offset="1" stop-color="#000" stop-opacity=".55"/>
  </linearGradient>
  <radialGradient id="spot" cx="50%" cy="0%" r="70%">
    <stop offset="0" stop-color="#fff" stop-opacity=".07"/>
    <stop offset="1" stop-color="#fff" stop-opacity="0"/>
  </radialGradient>
  <radialGradient id="vign" cx="50%" cy="50%" r="72%">
    <stop offset=".6" stop-color="#000" stop-opacity="0"/>
    <stop offset="1" stop-color="#000" stop-opacity=".5"/>
  </radialGradient>
  <radialGradient id="shadow" cx="50%" cy="50%" r="50%">
    <stop offset="0" stop-color="#000" stop-opacity=".75"/>
    <stop offset=".6" stop-color="#000" stop-opacity=".3"/>
    <stop offset="1" stop-color="#000" stop-opacity="0"/>
  </radialGradient>

  <!-- Materialien (objectBoundingBox: passen sich jeder Form an) -->
  <linearGradient id="polymer" x1="0" y1="0" x2="0" y2="1">
    <stop offset="0" stop-color="#55575c"/><stop offset=".04" stop-color="#34363a"/>
    <stop offset=".3" stop-color="#222326"/><stop offset=".75" stop-color="#161719"/><stop offset="1" stop-color="#0c0d0e"/>
  </linearGradient>
  <linearGradient id="polymerG" x1="0" y1="0" x2="0" y2="1">
    <stop offset="0" stop-color="#3f4144"/><stop offset=".08" stop-color="#2a2b2e"/>
    <stop offset=".6" stop-color="#18191b"/><stop offset="1" stop-color="#0b0c0d"/>
  </linearGradient>
  <linearGradient id="olive" x1="0" y1="0" x2="0" y2="1">
    <stop offset="0" stop-color="#5a5d52"/><stop offset=".08" stop-color="#3d4038"/>
    <stop offset=".5" stop-color="#2a2c26"/><stop offset="1" stop-color="#161713"/>
  </linearGradient>
  <linearGradient id="steel" x1="0" y1="0" x2="0" y2="1">
    <stop offset="0" stop-color="#7d8086"/><stop offset=".08" stop-color="#45484d"/>
    <stop offset=".35" stop-color="#2a2c30"/><stop offset=".7" stop-color="#1b1c1f"/>
    <stop offset=".9" stop-color="#2c2e32"/><stop offset="1" stop-color="#141517"/>
  </linearGradient>
  <linearGradient id="blued" x1="0" y1="0" x2="0" y2="1">
    <stop offset="0" stop-color="#8a8f99"/><stop offset=".1" stop-color="#3f444d"/>
    <stop offset=".4" stop-color="#22262c"/><stop offset=".75" stop-color="#15171b"/>
    <stop offset=".92" stop-color="#2d3138"/><stop offset="1" stop-color="#101114"/>
  </linearGradient>
  <linearGradient id="stainless" x1="0" y1="0" x2="0" y2="1">
    <stop offset="0" stop-color="#f2f3f4"/><stop offset=".12" stop-color="#c9ccd0"/>
    <stop offset=".45" stop-color="#8b8f95"/><stop offset=".62" stop-color="#a9adb2"/>
    <stop offset=".85" stop-color="#6a6e74"/><stop offset="1" stop-color="#3e4145"/>
  </linearGradient>
  <linearGradient id="silverH" x1="0" y1="0" x2="1" y2="0">
    <stop offset="0" stop-color="#6c7076"/><stop offset=".3" stop-color="#d9dcdf"/>
    <stop offset=".55" stop-color="#9a9ea4"/><stop offset=".8" stop-color="#e4e6e8"/>
    <stop offset="1" stop-color="#7a7e84"/>
  </linearGradient>
  <linearGradient id="anod" x1="0" y1="0" x2="0" y2="1">
    <stop offset="0" stop-color="#5d6066"/><stop offset=".1" stop-color="#34363a"/>
    <stop offset=".5" stop-color="#1f2023"/><stop offset=".85" stop-color="#16171a"/>
    <stop offset="1" stop-color="#26282c"/>
  </linearGradient>
  <linearGradient id="tube" x1="0" y1="0" x2="0" y2="1">
    <stop offset="0" stop-color="#2a2b2e"/><stop offset=".14" stop-color="#6b6e74"/>
    <stop offset=".22" stop-color="#8d9096"/><stop offset=".34" stop-color="#3a3c40"/>
    <stop offset=".7" stop-color="#17181a"/><stop offset=".9" stop-color="#2b2d31"/>
    <stop offset="1" stop-color="#0e0f10"/>
  </linearGradient>
  <linearGradient id="tubeRubber" x1="0" y1="0" x2="0" y2="1">
    <stop offset="0" stop-color="#202123"/><stop offset=".18" stop-color="#4a4c50"/>
    <stop offset=".3" stop-color="#2a2b2e"/><stop offset=".75" stop-color="#121314"/>
    <stop offset="1" stop-color="#0a0a0b"/>
  </linearGradient>
  <linearGradient id="barrel" x1="0" y1="0" x2="0" y2="1">
    <stop offset="0" stop-color="#1c1d20"/><stop offset=".2" stop-color="#6f737a"/>
    <stop offset=".32" stop-color="#9a9ea5"/><stop offset=".45" stop-color="#3a3d42"/>
    <stop offset=".8" stop-color="#151619"/><stop offset="1" stop-color="#26282c"/>
  </linearGradient>
  <linearGradient id="barrelSS" x1="0" y1="0" x2="0" y2="1">
    <stop offset="0" stop-color="#55595e"/><stop offset=".2" stop-color="#d7dadd"/>
    <stop offset=".35" stop-color="#f5f6f7"/><stop offset=".55" stop-color="#8d9196"/>
    <stop offset=".85" stop-color="#5a5e63"/><stop offset="1" stop-color="#7d8186"/>
  </linearGradient>
  <linearGradient id="walnut" x1="0" y1="0" x2="0" y2="1">
    <stop offset="0" stop-color="#7a4a2e"/><stop offset=".15" stop-color="#5c321c"/>
    <stop offset=".6" stop-color="#3f2213"/><stop offset="1" stop-color="#22110a"/>
  </linearGradient>
  <linearGradient id="beech" x1="0" y1="0" x2="0" y2="1">
    <stop offset="0" stop-color="#9a6e44"/><stop offset=".18" stop-color="#7c5230"/>
    <stop offset=".65" stop-color="#5a381c"/><stop offset="1" stop-color="#30200e"/>
  </linearGradient>
  <linearGradient id="brass" x1="0" y1="0" x2="1" y2="0">
    <stop offset="0" stop-color="#6b4d1e"/><stop offset=".18" stop-color="#c9a24e"/>
    <stop offset=".32" stop-color="#f6e2a4"/><stop offset=".5" stop-color="#b8903f"/>
    <stop offset=".8" stop-color="#7c5a22"/><stop offset="1" stop-color="#4a3410"/>
  </linearGradient>
  <linearGradient id="brassV" x1="0" y1="0" x2="0" y2="1">
    <stop offset="0" stop-color="#6b4d1e"/><stop offset=".18" stop-color="#c9a24e"/>
    <stop offset=".32" stop-color="#f6e2a4"/><stop offset=".5" stop-color="#b8903f"/>
    <stop offset=".8" stop-color="#7c5a22"/><stop offset="1" stop-color="#4a3410"/>
  </linearGradient>
  <linearGradient id="copper" x1="0" y1="0" x2="1" y2="0">
    <stop offset="0" stop-color="#5a2a14"/><stop offset=".2" stop-color="#b8673c"/>
    <stop offset=".34" stop-color="#f0b48a"/><stop offset=".52" stop-color="#a95a32"/>
    <stop offset=".85" stop-color="#6a3018"/><stop offset="1" stop-color="#3e1a0b"/>
  </linearGradient>
  <linearGradient id="copperV" x1="0" y1="0" x2="0" y2="1">
    <stop offset="0" stop-color="#5a2a14"/><stop offset=".2" stop-color="#b8673c"/>
    <stop offset=".34" stop-color="#f0b48a"/><stop offset=".52" stop-color="#a95a32"/>
    <stop offset=".85" stop-color="#6a3018"/><stop offset="1" stop-color="#3e1a0b"/>
  </linearGradient>
  <linearGradient id="blade" x1="0" y1="0" x2="0" y2="1">
    <stop offset="0" stop-color="#9ea2a8"/><stop offset=".25" stop-color="#e9ebed"/>
    <stop offset=".5" stop-color="#b9bdc2"/><stop offset=".56" stop-color="#f7f8f9"/>
    <stop offset=".75" stop-color="#8e9297"/><stop offset="1" stop-color="#d0d3d6"/>
  </linearGradient>
  <linearGradient id="leather" x1="0" y1="0" x2="0" y2="1">
    <stop offset="0" stop-color="#7b4a2a"/><stop offset=".2" stop-color="#5e341b"/>
    <stop offset=".8" stop-color="#3d200f"/><stop offset="1" stop-color="#26130a"/>
  </linearGradient>
  <linearGradient id="glass" x1="0" y1="0" x2="1" y2="1">
    <stop offset="0" stop-color="#1b2a3a"/><stop offset=".35" stop-color="#3b2e57"/>
    <stop offset=".55" stop-color="#1f4a4a"/><stop offset="1" stop-color="#090c10"/>
  </linearGradient>
  <linearGradient id="hiTop" x1="0" y1="0" x2="0" y2="1">
    <stop offset="0" stop-color="#fff" stop-opacity=".35"/><stop offset="1" stop-color="#fff" stop-opacity="0"/>
  </linearGradient>
  <linearGradient id="sheenL" x1="0" y1="0" x2="1" y2="0">
    <stop offset="0" stop-color="#fff" stop-opacity="0"/><stop offset=".45" stop-color="#fff" stop-opacity=".09"/>
    <stop offset=".55" stop-color="#fff" stop-opacity=".09"/><stop offset="1" stop-color="#fff" stop-opacity="0"/>
  </linearGradient>
  <linearGradient id="darkBottom" x1="0" y1="0" x2="0" y2="1">
    <stop offset=".55" stop-color="#000" stop-opacity="0"/><stop offset="1" stop-color="#000" stop-opacity=".45"/>
  </linearGradient>

  <!-- Texturen -->
  <pattern id="stipple" width="7" height="7" patternUnits="userSpaceOnUse" patternTransform="rotate(30)">
    <rect width="7" height="7" fill="#1a1b1d"/>
    <circle cx="2" cy="2" r="1.5" fill="#2e3033"/><circle cx="5.5" cy="5.5" r="1.3" fill="#0b0c0d"/>
  </pattern>
  <pattern id="pyramid" width="9" height="9" patternUnits="userSpaceOnUse" patternTransform="rotate(45)">
    <rect width="9" height="9" fill="#18191b"/>
    <path d="M0 0 L4.5 4.5 L9 0Z" fill="#2c2d30"/><path d="M0 9 L4.5 4.5 L9 9Z" fill="#0c0d0e"/>
  </pattern>
  <pattern id="knurl" width="8" height="8" patternUnits="userSpaceOnUse">
    <rect width="8" height="8" fill="#141517"/>
    <path d="M0 0 L8 8 M8 0 L0 8" stroke="#3b3d41" stroke-width="1.6"/>
  </pattern>
  <pattern id="ribs" width="10" height="10" patternUnits="userSpaceOnUse">
    <rect width="10" height="10" fill="#000" fill-opacity="0"/>
    <rect width="4" height="10" fill="#000" fill-opacity=".35"/><rect x="4" width="1.2" height="10" fill="#fff" fill-opacity=".12"/>
  </pattern>
  <pattern id="foam" width="34" height="34" patternUnits="userSpaceOnUse">
    <rect width="34" height="34" fill="#161718"/>
    <rect x="1.5" y="1.5" width="31" height="31" rx="5" fill="#1e1f21"/>
    <rect x="4" y="4" width="22" height="10" rx="5" fill="#26272a" opacity=".6"/>
  </pattern>
  <pattern id="fleece" width="6" height="6" patternUnits="userSpaceOnUse">
    <rect width="6" height="6" fill="#000" fill-opacity="0"/>
    <circle cx="1.5" cy="1.5" r="1" fill="#fff" fill-opacity=".05"/><circle cx="4.5" cy="4" r="1" fill="#000" fill-opacity=".12"/>
  </pattern>

  <filter id="blur6" x="-20%" y="-50%" width="140%" height="200%"><feGaussianBlur stdDeviation="6"/></filter>
  <filter id="blur18" x="-30%" y="-200%" width="160%" height="500%"><feGaussianBlur stdDeviation="18"/></filter>
  <filter id="blur40" x="-30%" y="-200%" width="160%" height="500%"><feGaussianBlur stdDeviation="40"/></filter>
  <filter id="grain" x="0" y="0" width="100%" height="100%">
    <feTurbulence type="fractalNoise" baseFrequency=".004 .09" numOctaves="3" seed="7"/>
    <feColorMatrix values="0 0 0 0 .1  0 0 0 0 .04  0 0 0 0 .01  0 0 0 -1.6 1.15"/>
    <feComposite in2="SourceGraphic" operator="in"/>
  </filter>
  <filter id="grainLight" x="0" y="0" width="100%" height="100%">
    <feTurbulence type="fractalNoise" baseFrequency=".006 .14" numOctaves="2" seed="3"/>
    <feColorMatrix values="0 0 0 0 .25  0 0 0 0 .13  0 0 0 0 .04  0 0 0 -1.4 1.0"/>
    <feComposite in2="SourceGraphic" operator="in"/>
  </filter>
  <filter id="halo" x="-10%" y="-20%" width="120%" height="140%">
    <feGaussianBlur in="SourceAlpha" stdDeviation="30"/>
    <feColorMatrix values="0 0 0 0 .85  0 0 0 0 .87  0 0 0 0 .9  0 0 0 .09 0"/>
  </filter>
  <filter id="soft" x="-5%" y="-5%" width="110%" height="110%">
    <feTurbulence type="fractalNoise" baseFrequency=".9" numOctaves="1" seed="2"/>
    <feColorMatrix values="0 0 0 0 1  0 0 0 0 1  0 0 0 0 1  0 0 0 .05 0"/>
    <feComposite in2="SourceGraphic" operator="in"/>
  </filter>
</defs>`;

/** Hintergrund: Anthrazit-Studio mit Lichtkegel, Bodenverlauf und Vignette. */
export function background() {
    return `
<rect width="${W}" height="${H}" fill="url(#bg)"/>
<rect width="${W}" height="${H}" fill="url(#spot)"/>
<rect y="${FLOOR - 120}" width="${W}" height="${H - FLOOR + 120}" fill="url(#floorGlow)" opacity=".7"/>`;
}

export function vignette() {
    return `<rect width="${W}" height="${H}" fill="url(#vign)"/>`;
}

/** Weicher Bodenschatten (Kontakt- + Streuschatten). */
export function shadow(cx, cy, rx, ry = 40, o = 1) {
    return `<g opacity="${o}">
  <ellipse cx="${cx}" cy="${cy}" rx="${rx}" ry="${ry * 1.8}" fill="url(#shadow)" opacity=".55"/>
  <ellipse cx="${cx}" cy="${cy}" rx="${rx * 0.86}" ry="${ry * 0.45}" fill="#000" opacity=".55" filter="url(#blur18)"/>
</g>`;
}

/** Spiegelung eines Objekts auf dem Boden (für stehende Objekte). */
export function reflection(id, floorY, o = 0.16, depth = 260) {
    return `<mask id="m-${id}" maskUnits="userSpaceOnUse" x="0" y="${floorY}" width="${W}" height="${depth}">
    <rect x="0" y="${floorY}" width="${W}" height="${depth}" fill="url(#reflFade-${id})"/></mask>
  <linearGradient id="reflFade-${id}" gradientUnits="userSpaceOnUse" x1="0" y1="${floorY}" x2="0" y2="${floorY + depth}">
    <stop offset="0" stop-color="#fff" stop-opacity="${o}"/><stop offset="1" stop-color="#fff" stop-opacity="0"/></linearGradient>
  <g mask="url(#m-${id})"><use href="#${id}" transform="translate(0 ${2 * floorY}) scale(1 -1)"/></g>`;
}

export function svg(body) {
    return `<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 ${W} ${H}" width="${W}" height="${H}">${defs}${background()}${body}${vignette()}</svg>`;
}

/** Glanzlinie entlang einer Oberkante. */
export function rim(d, o = 0.35, w = 2) {
    return `<path d="${d}" fill="none" stroke="#fff" stroke-opacity="${o}" stroke-width="${w}" stroke-linecap="round"/>`;
}

/** Wiederholte Linien (Riffelungen, Rippen). */
export function lines(x0, x1, step, y0, y1, slant = 0, stroke = '#000', o = 0.6, w = 2, hi = true) {
    let s = '';
    for (let x = x0; x <= x1 + 0.01; x += step) {
        s += `<line x1="${x}" y1="${y0}" x2="${x + slant}" y2="${y1}" stroke="${stroke}" stroke-opacity="${o}" stroke-width="${w}"/>`;
        if (hi) s += `<line x1="${x + w * 0.9}" y1="${y0}" x2="${x + slant + w * 0.9}" y2="${y1}" stroke="#fff" stroke-opacity=".1" stroke-width="${w * 0.3}"/>`;
    }
    return s;
}
