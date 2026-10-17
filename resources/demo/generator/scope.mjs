import { lines, rim } from './common.mjs';

/**
 * Zielfernrohr in Seitenansicht (rechte Seite), Okular links, Objektiv rechts. Maße in mm, Mittelachse y = 0.
 * o: len, obj (Objektiv-Ø), eye (Okular-Ø), tube (Mittelrohr-Ø), rail (Montageschiene unten), lensEnd (Objektivglas sichtbar)
 */
export function scope(o = {}) {
    const len = o.len ?? 360;
    const ro = (o.obj ?? 56) / 2 + 5; // Außenradius Objektiv
    const re = (o.eye ?? 44) / 2;
    const rt = (o.tube ?? 30) / 2;
    const ey = o.eyeLen ?? 82; // Okulargehäuse
    const pr = o.powerLen ?? 40; // Vergrößerungsring
    const objLen = o.objLen ?? Math.max(30, ro * 2.1); // zylindrisches Objektivgehäuse
    const bell = o.bellLen ?? Math.max(20, (ro - rt) * 2.2);
    const xObj = len - objLen;
    const xBell = xObj - bell;
    const xTurret = o.turretX ?? (ey + pr + (xBell - ey - pr) * 0.42);
    const tw = o.turretW ?? 30;
    const out = [];

    // Hauptkörper als ein Umriss
    const body = `M 2,${-re} L ${ey - 10},${-re} C ${ey - 2},${-re} ${ey},${-re + 2} ${ey + 2},${-rt - 4}
      L ${ey + pr},${-rt - 4} L ${ey + pr + 6},${-rt} L ${xBell},${-rt}
      C ${xBell + bell * 0.55},${-rt} ${xBell + bell * 0.5},${-ro} ${xObj},${-ro}
      L ${len - 3},${-ro} Q ${len},${-ro} ${len},${-ro + 3} L ${len},${ro - 3} Q ${len},${ro} ${len - 3},${ro}
      L ${xObj},${ro} C ${xBell + bell * 0.5},${ro} ${xBell + bell * 0.55},${rt} ${xBell},${rt}
      L ${ey + pr + 6},${rt} L ${ey + pr},${rt + 4} L ${ey + 2},${rt + 4} C ${ey},${re - 2} ${ey - 2},${re} ${ey - 10},${re}
      L 2,${re} Q 0,${re} 0,${re - 2} L 0,${-re + 2} Q 0,${-re} 2,${-re} Z`;
    out.push(`<path d="${body}" fill="url(#tube)"/>`);

    // Augenmuschel (Gummi)
    out.push(`<rect x="0" y="${-re}" width="14" height="${re * 2}" rx="3" fill="url(#tubeRubber)"/>`);
    out.push(lines(3, 11, 2.6, -re + 1, re - 1, 0, '#000', 0.5, 0.8, false));
    // Okular-Fuge
    out.push(`<line x1="${ey - 26}" y1="${-re}" x2="${ey - 26}" y2="${re}" stroke="#000" stroke-opacity=".6" stroke-width="1"/>`);
    // Vergrößerungsring mit Griffrillen + Hebel
    out.push(`<rect x="${ey + 4}" y="${-rt - 4}" width="${pr - 6}" height="${(rt + 4) * 2}" fill="url(#tubeRubber)"/>`);
    out.push(lines(ey + 7, ey + pr - 5, 3, -rt - 3, rt + 3, 0, '#000', 0.55, 1.2, true));
    if (o.throwLever !== false) out.push(`<path d="M ${ey + pr * 0.45},${-rt - 4} L ${ey + pr * 0.4},${-rt - 13} Q ${ey + pr * 0.5},${-rt - 16} ${ey + pr * 0.62},${-rt - 13} L ${ey + pr * 0.6},${-rt - 4} Z" fill="url(#anod)"/>`);
    // Vergrößerungsskala (nur Striche, keine Schrift)
    for (let i = 0; i < 5; i++) out.push(`<rect x="${ey - 22 + i * 4.5}" y="${-re + 3}" width=".8" height="${i % 2 ? 3 : 5}" fill="#c9ccd0" opacity=".6"/>`);
    if (o.accentDot) out.push(`<circle cx="${ey - 12}" cy="${-re + 5}" r="1.4" fill="#9d4640"/>`);

    // Turmsattel
    const sx = xTurret - tw * 0.5 - 10;
    const sw = tw + 20;
    const sh = rt + 6;
    out.push(`<path d="M ${sx},${-rt} Q ${sx + 3},${-sh} ${sx + 10},${-sh} L ${sx + sw - 10},${-sh} Q ${sx + sw - 3},${-sh} ${sx + sw},${-rt} L ${sx + sw},${rt} Q ${sx + sw - 3},${sh} ${sx + sw - 10},${sh} L ${sx + 10},${sh} Q ${sx + 3},${sh} ${sx},${rt} Z" fill="url(#tube)"/>`);
    // Höhenturm oben
    const th = o.turretH ?? 22;
    out.push(`<rect x="${xTurret - tw / 2 + 3}" y="${-sh - 6}" width="${tw - 6}" height="7" fill="url(#anod)"/>`);
    out.push(`<rect x="${xTurret - tw / 2}" y="${-sh - th}" width="${tw}" height="${th - 6}" rx="2" fill="url(#silverH)" opacity=".18"/>`);
    out.push(`<rect x="${xTurret - tw / 2}" y="${-sh - th}" width="${tw}" height="${th - 6}" rx="2" fill="url(#knurl)" opacity=".92"/>`);
    out.push(`<rect x="${xTurret - tw / 2}" y="${-sh - th}" width="${tw}" height="3" rx="1.5" fill="url(#anod)"/>`);
    out.push(`<rect x="${xTurret - tw / 2}" y="${-sh - th}" width="${tw}" height="${th - 6}" rx="2" fill="url(#roundTurret)"/>`);
    // Seitenturm (zeigt zum Betrachter)
    const wr = o.windR ?? 15;
    out.push(`<circle cx="${xTurret}" cy="${2}" r="${wr + 2}" fill="#0d0e0f"/>`);
    out.push(`<circle cx="${xTurret}" cy="${2}" r="${wr}" fill="url(#knurlRad)"/>`);
    out.push(`<circle cx="${xTurret}" cy="${2}" r="${wr * 0.72}" fill="url(#anodRad)"/>`);
    out.push(`<circle cx="${xTurret}" cy="${2}" r="${wr * 0.72}" fill="none" stroke="#fff" stroke-opacity=".12" stroke-width=".6"/>`);
    for (let a = 0; a < 360; a += 30) {
        const r1 = wr * 0.58, r2 = wr * 0.68;
        const c = Math.cos((a * Math.PI) / 180), s = Math.sin((a * Math.PI) / 180);
        out.push(`<line x1="${xTurret + c * r1}" y1="${2 + s * r1}" x2="${xTurret + c * r2}" y2="${2 + s * r2}" stroke="#d0d3d6" stroke-opacity=".55" stroke-width=".7"/>`);
    }
    if (o.illum) {
        // Beleuchtungsregler auf dem Okular-seitigen Sattel
        out.push(`<rect x="${sx - 2}" y="${-sh - 10}" width="10" height="12" rx="2" fill="url(#knurl)"/>`);
    }

    // Objektivgehäuse-Fugen, Glanz
    out.push(`<line x1="${xObj + 6}" y1="${-ro}" x2="${xObj + 6}" y2="${ro}" stroke="#000" stroke-opacity=".5" stroke-width="1"/>`);
    out.push(`<line x1="${len - 8}" y1="${-ro}" x2="${len - 8}" y2="${ro}" stroke="#fff" stroke-opacity=".12" stroke-width="1"/>`);
    out.push(rim(`M 4,${-re + 0.8} L ${ey - 10},${-re + 0.8}`, 0.25, 0.7));
    out.push(rim(`M ${ey + pr + 6},${-rt + 0.8} L ${xBell},${-rt + 0.8}`, 0.22, 0.7));
    out.push(rim(`M ${xObj},${-ro + 0.8} L ${len - 3},${-ro + 0.8}`, 0.25, 0.7));

    // Montageschiene (z. B. Innenschiene) unten
    if (o.rail) {
        out.push(`<path d="M ${ey + pr + 8},${rt - 1} L ${xBell - 4},${rt - 1} L ${xBell - 8},${rt + 9} L ${ey + pr + 12},${rt + 9} Z" fill="url(#anod)"/>`);
        out.push(lines(ey + pr + 16, xBell - 14, 7, rt + 5, rt + 9, 0, '#000', 0.7, 2.4, false));
    }
    // Objektivglas (leicht schräge Ansicht)
    if (o.lensEnd) {
        const rx = ro * 0.2;
        out.push(`<ellipse cx="${len}" cy="0" rx="${rx}" ry="${ro}" fill="url(#tube)"/>`);
        out.push(`<ellipse cx="${len + 0.5}" cy="0" rx="${rx * 0.8}" ry="${ro - 4}" fill="url(#glass)"/>`);
        out.push(`<ellipse cx="${len + 1}" cy="${-ro * 0.3}" rx="${rx * 0.35}" ry="${ro * 0.35}" fill="#9fb7ff" opacity=".18"/>`);
        out.push(`<path d="M ${len + rx * 0.3},${-ro * 0.75} Q ${len + rx * 0.7},${-ro * 0.2} ${len + rx * 0.45},${ro * 0.35}" stroke="#d8f0e8" stroke-opacity=".35" stroke-width="1" fill="none"/>`);
    }
    return `<defs>
  <radialGradient id="anodRad" cx="40%" cy="35%" r="70%"><stop offset="0" stop-color="#55585e"/><stop offset=".6" stop-color="#26282c"/><stop offset="1" stop-color="#101113"/></radialGradient>
  <pattern id="knurlRadP" width="2" height="2" patternUnits="userSpaceOnUse"><rect width="2" height="2" fill="#18191b"/><path d="M0 0 L2 2" stroke="#4a4c50" stroke-width=".5"/></pattern>
  <radialGradient id="knurlRad" cx="45%" cy="40%" r="60%"><stop offset="0" stop-color="#3a3c40"/><stop offset="1" stop-color="#141517"/></radialGradient>
  <linearGradient id="roundTurret" x1="0" y1="0" x2="1" y2="0"><stop offset="0" stop-color="#000" stop-opacity=".55"/><stop offset=".35" stop-color="#fff" stop-opacity=".12"/><stop offset=".6" stop-color="#000" stop-opacity="0"/><stop offset="1" stop-color="#000" stop-opacity=".6"/></linearGradient>
</defs>` + out.join('\n');
}

/** Kompaktes Leuchtpunktvisier mit Picatinny-Klemme (Seitenansicht, Front rechts). Maße in mm. */
export function redDot() {
    const out = [];
    // Klemmbasis
    out.push(`<path d="M -4,44 L 70,44 L 72,50 L 66,56 L 2,56 L -6,50 Z" fill="url(#anod)"/>`);
    out.push(`<rect x="22" y="47" width="16" height="7" rx="1.5" fill="url(#steel)"/><circle cx="30" cy="50.5" r="2.4" fill="#0b0b0c"/>`);
    out.push(lines(2, 66, 6, 52, 56, 0, '#000', 0.6, 2.4, false));
    // Gehäuse
    const body = `M 2,42 L 2,12 Q 2,6 8,6 L 18,6 L 22,-4 Q 24,-8 30,-8 L 44,-8 Q 50,-8 52,-4 L 56,6 L 66,6 Q 70,6 70,12 L 70,42 Z`;
    out.push(`<path d="${body}" fill="url(#anod)"/>`);
    // Frontschutz-Röhre (größer) und Okular-Röhre
    out.push(`<path d="M 46,0 L 68,0 Q 74,0 74,6 L 74,40 Q 74,44 70,44 L 46,44 Z" fill="url(#polymer)"/>`);
    out.push(`<path d="M -2,6 L 16,6 L 16,44 L -2,44 Q -6,44 -6,40 L -6,10 Q -6,6 -2,6 Z" fill="url(#polymer)"/>`);
    // Höhenturm oben, Seitenturm zum Betrachter
    out.push(`<rect x="27" y="-16" width="20" height="9" rx="2" fill="url(#knurl)"/><rect x="27" y="-16" width="20" height="2.5" rx="1" fill="url(#anod)"/>`);
    out.push(`<circle cx="34" cy="22" r="10" fill="#0c0d0e"/><circle cx="34" cy="22" r="8.4" fill="url(#anodRad2)"/><circle cx="34" cy="22" r="3.6" fill="#1b1c1e" stroke="#777" stroke-opacity=".4" stroke-width=".5"/>`);
    out.push(`<path d="M 31,22 L 37,22" stroke="#bbb" stroke-opacity=".5" stroke-width=".8"/>`);
    // Bedientasten
    out.push(`<rect x="54" y="14" width="10" height="7" rx="2.5" fill="#1a1b1d" stroke="#000" stroke-width=".6"/><rect x="54" y="25" width="10" height="7" rx="2.5" fill="#1a1b1d" stroke="#000" stroke-width=".6"/>`);
    out.push(`<path d="M 57,17.5 L 61,17.5 M 59,15.5 L 59,19.5 M 57,28.5 L 61,28.5" stroke="#999" stroke-width=".7"/>`);
    // Frontlinse (leicht schräg)
    out.push(`<ellipse cx="74" cy="22" rx="5" ry="22" fill="url(#polymer)"/><ellipse cx="74.6" cy="22" rx="3.8" ry="18.5" fill="url(#glassRD)"/>`);
    out.push(`<ellipse cx="75" cy="15" rx="1.4" ry="6" fill="#ffd9a8" opacity=".25"/>`);
    out.push(rim('M 4,6.6 L 18,6.6 M 30,-7.4 L 44,-7.4 M 48,0.6 L 70,0.6', 0.3, 0.6));
    return `<defs><radialGradient id="anodRad2" cx="40%" cy="35%" r="70%"><stop offset="0" stop-color="#55585e"/><stop offset="1" stop-color="#141517"/></radialGradient>
  <linearGradient id="glassRD" x1="0" y1="0" x2="0" y2="1"><stop offset="0" stop-color="#5a3a1a"/><stop offset=".4" stop-color="#8a4a22"/><stop offset=".75" stop-color="#2a3a3a"/><stop offset="1" stop-color="#0a0c0e"/></linearGradient></defs>` + out.join('\n');
}
