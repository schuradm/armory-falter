import { lines, rim, shadow, reflection, FLOOR, W } from './common.mjs';

const FONT = `font-family="Inter, 'Liberation Sans', Arial, sans-serif"`;

/** Quader in leichter Aufsicht: Front (x,y,w,h), Tiefe als Versatz (dx,dy). */
function boxFaces(x, y, w, h, dx, dy, fills) {
    const top = `M ${x},${y} L ${x + dx},${y + dy} L ${x + w + dx},${y + dy} L ${x + w},${y} Z`;
    const side = `M ${x + w},${y} L ${x + w + dx},${y + dy} L ${x + w + dx},${y + h + dy} L ${x + w},${y + h} Z`;
    const front = `M ${x},${y} L ${x + w},${y} L ${x + w},${y + h} L ${x},${y + h} Z`;
    return { top, side, front, svg: `<path d="${side}" fill="${fills.side}"/><path d="${top}" fill="${fills.top}"/><path d="${front}" fill="${fills.front}"/>` };
}

/** Patrone liegend (Seitenansicht), Spitze rechts. Maße in mm, Ursprung Hülsenboden Mitte. */
export function cartridgeSide(kind = '9mm') {
    if (kind === '9mm') {
        return `<path d="M 0,-4.6 L 1.2,-4.8 L 1.2,-4.3 L 2,-4.3 L 2.3,-4.85 L 19,-4.7 L 19,4.7 L 2.3,4.85 L 2,4.3 L 1.2,4.3 L 1.2,4.8 L 0,4.6 Z" fill="url(#brassV)"/>
<path d="M 19,-4.5 L 22,-4.45 C 26,-4.2 29.4,-2.4 29.7,0 C 29.4,2.4 26,4.2 22,4.45 L 19,4.5 Z" fill="url(#copperV)"/>
<path d="M 19,-4.7 L 19,4.7" stroke="#3a2a10" stroke-width=".25"/>
<path d="M 2.6,-4.3 L 18.6,-4.2" stroke="#fff6d8" stroke-opacity=".6" stroke-width=".35"/>`;
    }
    // .308 Win mit Kupfergeschoss
    return `<path d="M 0,-5.9 L 1.3,-6 L 1.3,-5.2 L 2.2,-5.2 L 2.6,-6 L 39,-5.6 L 43.5,-3.6 L 51,-3.5 L 51,3.5 L 43.5,3.6 L 39,5.6 L 2.6,6 L 2.2,5.2 L 1.3,5.2 L 1.3,6 L 0,5.9 Z" fill="url(#brassV)"/>
<path d="M 51,-3.3 L 56,-3.3 C 62,-3 68,-1.4 71,0 C 68,1.4 62,3 56,3.3 L 51,3.3 Z" fill="url(#copperV)"/>
<path d="M 64,-1.9 L 64,1.9" stroke="#4a2410" stroke-width=".35" opacity=".7"/>
<path d="M 2.8,-5.4 L 38.6,-5" stroke="#fff6d8" stroke-opacity=".55" stroke-width=".45"/>`;
}

/** Patrone stehend (Spitze oben), Ursprung Hülsenboden Mitte. */
export function cartridgeUp(kind = '308') {
    return `<g transform="rotate(-90)">${cartridgeSide(kind)
        .replaceAll('url(#brassV)', 'url(#brassUp)').replaceAll('url(#copperV)', 'url(#copperUp)')}</g>`;
}

const upDefs = `<defs>
  <linearGradient id="brassUp" x1="0" y1="1" x2="0" y2="0">
    <stop offset="0" stop-color="#4a3410"/><stop offset=".2" stop-color="#8a6526"/><stop offset=".42" stop-color="#f6e2a4"/>
    <stop offset=".55" stop-color="#c9a24e"/><stop offset=".85" stop-color="#6b4d1e"/><stop offset="1" stop-color="#3a2808"/></linearGradient>
  <linearGradient id="copperUp" x1="0" y1="1" x2="0" y2="0">
    <stop offset="0" stop-color="#3e1a0b"/><stop offset=".2" stop-color="#7a3a1c"/><stop offset=".42" stop-color="#f0b48a"/>
    <stop offset=".58" stop-color="#b8673c"/><stop offset=".85" stop-color="#5a2a14"/><stop offset="1" stop-color="#2e1206"/></linearGradient>
  <radialGradient id="noseCu" cx="38%" cy="35%" r="65%"><stop offset="0" stop-color="#ffd6b0"/><stop offset=".35" stop-color="#c47544"/><stop offset="1" stop-color="#4a200c"/></radialGradient>
  <radialGradient id="noseBr" cx="38%" cy="35%" r="65%"><stop offset="0" stop-color="#fff0c4"/><stop offset=".35" stop-color="#c79a46"/><stop offset="1" stop-color="#4a3410"/></radialGradient>
  <linearGradient id="cardDark" x1="0" y1="0" x2="0" y2="1"><stop offset="0" stop-color="#2c2d30"/><stop offset="1" stop-color="#17181a"/></linearGradient>
  <linearGradient id="cardTop" x1="0" y1="1" x2="1" y2="0"><stop offset="0" stop-color="#3a3b3f"/><stop offset="1" stop-color="#4a4b50"/></linearGradient>
  <linearGradient id="cardSide" x1="0" y1="0" x2="1" y2="0"><stop offset="0" stop-color="#151618"/><stop offset="1" stop-color="#0c0c0d"/></linearGradient>
  <linearGradient id="silverBand" x1="0" y1="0" x2="1" y2="1"><stop offset="0" stop-color="#e8eaec"/><stop offset=".5" stop-color="#b9bdc2"/><stop offset="1" stop-color="#8d9196"/></linearGradient>
</defs>`;

/** Munitionsschachtel offen mit Patronenböden/-spitzen von oben, davor lose Patronen. */
export function ammoBox(o) {
    const out = [upDefs];
    const { x, y, w, h, dx, dy, cols, rows, kind } = o;
    // Rückwand des offenen Deckels (aufgeklappt nach hinten)
    const lidH = o.lidH ?? h * 0.9;
    out.push(`<path d="M ${x + dx},${y + dy} L ${x + w + dx},${y + dy} L ${x + w + dx + 12},${y + dy - lidH} L ${x + dx + 12},${y + dy - lidH} Z" fill="url(#cardDark)"/>`);
    out.push(`<path d="M ${x + dx},${y + dy} L ${x + w + dx},${y + dy} L ${x + w + dx + 12},${y + dy - lidH} L ${x + dx + 12},${y + dy - lidH} Z" fill="#000" opacity=".25"/>`);
    // Innenseite Deckel: dezentes Raster
    out.push(`<path d="M ${x + dx + 30},${y + dy - 20} L ${x + w + dx - 20},${y + dy - 20} L ${x + w + dx - 12},${y + dy - lidH + 26} L ${x + dx + 38},${y + dy - lidH + 26} Z" fill="none" stroke="#fff" stroke-opacity=".06" stroke-width="2"/>`);
    const f = boxFaces(x, y, w, h, dx, dy, { top: '#0c0c0d', side: 'url(#cardSide)', front: 'url(#cardDark)' });
    out.push(f.svg);
    // Patronen im Einsatz (Ellipsen-Raster in der Deckfläche)
    const tip = kind === '9mm' ? 'url(#noseCu)' : 'url(#noseCu)';
    for (let r = rows - 1; r >= 0; r--) {
        for (let c = 0; c < cols; c++) {
            const u = (c + 0.5) / cols;
            const v = (r + 0.5) / rows; // 0 = vorn, 1 = hinten
            const px = x + u * w + v * dx;
            const py = y + v * dy;
            const rr = (w / cols) * 0.38;
            out.push(`<ellipse cx="${px}" cy="${py + 4}" rx="${rr * 1.12}" ry="${rr * 0.62}" fill="#000" opacity=".7"/>`);
            out.push(`<ellipse cx="${px}" cy="${py - rr * 0.25}" rx="${rr}" ry="${rr * 0.72}" fill="${tip}"/>`);
            out.push(`<ellipse cx="${px - rr * 0.3}" cy="${py - rr * 0.5}" rx="${rr * 0.25}" ry="${rr * 0.16}" fill="#fff" opacity=".35"/>`);
        }
    }
    // Einsatz-Vorderkante (verdeckt die unteren Hälften der vorderen Reihe)
    out.push(`<path d="M ${x},${y} L ${x + w},${y} L ${x + w},${y + 10} L ${x},${y + 10} Z" fill="url(#cardDark)"/>`);
    out.push(rim(`M ${x},${y + 1} L ${x + w},${y + 1}`, 0.35, 2));
    out.push(rim(`M ${x + w},${y + 1} L ${x + w + dx},${y + dy + 1}`, 0.15, 2));
    // Etikett
    const lx = x + w * 0.07, lw = w * 0.86;
    out.push(`<rect x="${x}" y="${y + h * 0.18}" width="${w}" height="${h * 0.06}" fill="#9d4640"/>`);
    out.push(`<text x="${lx}" y="${y + h * 0.46}" ${FONT} font-size="${h * 0.17}" font-weight="800" fill="#f2f2f2" letter-spacing="2">${o.title}</text>`);
    out.push(`<text x="${lx}" y="${y + h * 0.62}" ${FONT} font-size="${h * 0.075}" font-weight="500" fill="#b9bdc2" letter-spacing="4">${o.sub}</text>`);
    out.push(`<rect x="${lx}" y="${y + h * 0.72}" width="${lw}" height="1.5" fill="#fff" opacity=".18"/>`);
    out.push(`<text x="${lx}" y="${y + h * 0.86}" ${FONT} font-size="${h * 0.07}" font-weight="600" fill="#e8eaec" letter-spacing="3">${o.count}</text>`);
    out.push(`<text x="${lx + lw}" y="${y + h * 0.86}" ${FONT} font-size="${h * 0.06}" font-weight="500" fill="#8d9196" text-anchor="end" letter-spacing="2">${o.note ?? ''}</text>`);
    // Licht auf der Frontfläche
    out.push(`<path d="${f.front}" fill="url(#sheenL)" opacity=".7"/>`);
    return out.join('\n');
}

/** Jagdmesser (feststehend), Klinge rechts. Maße in mm, Ursprung Griffende Mitte. */
export function knife() {
    const out = [];
    // Griff
    const handle = `M 2,-8 C 20,-13 60,-14 92,-11 L 104,-12 L 104,12 L 92,11 C 66,15 46,16 30,13 C 16,11 4,10 0,6 Q -3,0 2,-8 Z`;
    out.push(`<path d="${handle}" fill="url(#polymer)"/>`);
    out.push(`<path d="M 14,-6 C 40,-10 70,-10 88,-8 L 88,8 C 66,12 40,12 16,9 Z" fill="url(#stippleK)" opacity=".85"/>`);
    out.push(`<path d="${handle}" fill="url(#roundH)"/>`);
    out.push(`<circle cx="8" cy="0" r="3.2" fill="#0a0a0b"/><circle cx="8" cy="0" r="3.2" fill="none" stroke="url(#stainless)" stroke-width="1"/>`);
    // Parierelement
    out.push(`<path d="M 102,-15 Q 104,-17 108,-16 L 110,-12 L 110,14 Q 108,20 103,19 Q 100,17 102,13 Z" fill="url(#stainless)"/>`);
    // Klinge (Drop Point)
    const blade = `M 110,-11 L 160,-12 C 186,-12 204,-9 218,-4 C 210,5 192,12 168,12.5 C 150,12.6 130,12 110,11 Z`;
    out.push(`<path d="${blade}" fill="url(#blade)"/>`);
    // Flachschliff mit Schneidfase
    out.push(`<path d="M 116,1 L 168,1 C 188,0.5 206,-2 216,-3.6 C 208,5 192,11.6 168,12 C 150,12.2 130,11.8 116,11 Z" fill="#fff" opacity=".2"/>`);
    out.push(`<path d="M 116,1 L 168,1 C 188,0.5 206,-2 216,-3.6" fill="none" stroke="#5d6166" stroke-width=".5"/>`);
    out.push(`<path d="M 114,10.4 C 140,11.4 160,11.8 170,11.6 C 190,10.6 206,4 216,-3" fill="none" stroke="#fff" stroke-opacity=".85" stroke-width=".5"/>`);
    out.push(rim('M 112,-10.6 L 160,-11.6 C 186,-11.6 204,-8.6 216,-4', 0.6, 0.5));
    // Daumenrillen auf dem Rücken
    for (let i = 0; i < 6; i++) out.push(`<path d="M ${114 + i * 3},-11.2 L ${115 + i * 3},-12.4" stroke="#444" stroke-width=".8"/>`);
    return `<defs>
  <pattern id="stippleK" width="1.4" height="1.4" patternUnits="userSpaceOnUse" patternTransform="rotate(45)"><rect width="1.4" height="1.4" fill="#18191b"/><rect width=".7" height=".7" fill="#2e3034"/></pattern>
  <linearGradient id="roundH" x1="0" y1="0" x2="0" y2="1"><stop offset="0" stop-color="#fff" stop-opacity=".15"/><stop offset=".25" stop-color="#fff" stop-opacity=".02"/><stop offset=".7" stop-color="#000" stop-opacity=".1"/><stop offset="1" stop-color="#000" stop-opacity=".55"/></linearGradient>
</defs>` + out.join('\n');
}

/** Lederscheide, Öffnung links. Maße in mm. */
export function sheath() {
    const p = `M 0,-18 L 120,-16 C 150,-14 168,-8 178,2 C 170,12 150,18 120,20 L 0,22 Q -6,2 0,-18 Z`;
    return `<path d="${p}" fill="url(#leather)"/>
<path d="${p}" fill="#000" filter="url(#leatherTex)" opacity=".5"/>
<path d="M 6,-12 L 118,-10.5 C 146,-9 160,-4 168,2 C 160,9 146,13 118,15 L 6,16" fill="none" stroke="#d8b48a" stroke-opacity=".55" stroke-width=".8" stroke-dasharray="2.2 1.6"/>
<path d="M -4,-20 L 30,-19 L 30,24 L -4,25 Q -10,2 -4,-20 Z" fill="url(#leather)"/>
<path d="M 0,-15 L 26,-14.5 M 0,20 L 26,20" stroke="#d8b48a" stroke-opacity=".5" stroke-width=".8" stroke-dasharray="2.2 1.6"/>
<rect x="8" y="-4" width="8" height="8" rx="1.5" fill="none" stroke="url(#stainless)" stroke-width="1.6"/>
${rim('M 2,-17.4 L 120,-15.4 C 150,-13.4 166,-8 176,1.5', 0.25, 0.7)}
<defs><filter id="leatherTex" x="0" y="0" width="100%" height="100%"><feTurbulence type="fractalNoise" baseFrequency=".6" numOctaves="2" seed="4"/><feColorMatrix values="0 0 0 0 0  0 0 0 0 0  0 0 0 0 0  0 0 0 -1.4 .9"/><feComposite in2="SourceGraphic" operator="in"/></filter></defs>`;
}

/** Offener Reinigungskoffer von oben. px-Koordinaten. */
export function cleaningKit(cx, cy) {
    const w = 1240, h = 760;
    const x = cx - w / 2, y = cy - h / 2;
    const out = [];
    out.push(`<rect x="${x + 16}" y="${y + 30}" width="${w}" height="${h}" rx="40" fill="#000" opacity=".55" filter="url(#blur18)"/>`);
    out.push(`<rect x="${x}" y="${y}" width="${w}" height="${h}" rx="40" fill="url(#caseShell)"/>`);
    out.push(`<rect x="${x + 6}" y="${y + 6}" width="${w - 12}" height="${h - 12}" rx="35" fill="none" stroke="#fff" stroke-opacity=".08" stroke-width="3"/>`);
    // Innenraum mit Schaum
    const ix = x + 34, iy = y + 34, iw = w - 68, ih = h - 68;
    out.push(`<rect x="${ix}" y="${iy}" width="${iw}" height="${ih}" rx="18" fill="#111213"/>`);
    out.push(`<rect x="${ix}" y="${iy}" width="${iw}" height="${ih}" rx="18" fill="url(#foamTex)" opacity=".9"/>`);
    out.push(`<rect x="${ix}" y="${iy}" width="${iw}" height="${ih}" rx="18" fill="none" stroke="#000" stroke-width="6" opacity=".6"/>`);
    const slot = (sx, sy, sw, sh, r = sh / 2) => `<rect x="${sx}" y="${sy}" width="${sw}" height="${sh}" rx="${r}" fill="#070707"/><rect x="${sx}" y="${sy}" width="${sw}" height="${sh}" rx="${r}" fill="none" stroke="#000" stroke-width="4" opacity=".8"/>`;
    // Putzstock-Segmente (Messing)
    for (let i = 0; i < 3; i++) {
        const ry = iy + 60 + i * 52;
        out.push(slot(ix + 60, ry - 18, 620, 36));
        out.push(`<rect x="${ix + 72}" y="${ry - 9}" width="596" height="18" rx="9" fill="url(#brassV)"/>`);
        out.push(`<rect x="${ix + 650}" y="${ry - 11}" width="22" height="22" rx="3" fill="url(#brassV)"/>`);
    }
    // Griff des Putzstocks
    out.push(slot(ix + 720, iy + 36, 360, 70));
    out.push(`<path d="M ${ix + 736},${iy + 56} L ${ix + 1010},${iy + 50} Q ${ix + 1060},${iy + 50} ${ix + 1064},${iy + 71} Q ${ix + 1060},${iy + 92} ${ix + 1010},${iy + 92} L ${ix + 736},${iy + 86} Z" fill="url(#polymer)"/>`);
    out.push(`<rect x="${ix + 700}" y="${iy + 64}" width="40" height="14" rx="3" fill="url(#brassV)"/>`);
    out.push(rim(`M ${ix + 760},${iy + 56} L ${ix + 1010},${iy + 52}`, 0.3, 2));
    // Bürsten (Bronze) und Wischer
    const brushes = [[60, 'bronze'], [130, 'bronze'], [200, 'bronze'], [270, 'nylon'], [340, 'mop'], [410, 'jag'], [480, 'jag']];
    for (const [bx, t] of brushes) {
        const sx = ix + 60 + bx, sy = iy + 230;
        out.push(slot(sx - 16, sy, 32, 220, 16));
        out.push(`<rect x="${sx - 4}" y="${sy + 90}" width="8" height="120" rx="3" fill="url(#brass)"/>`);
        if (t === 'bronze' || t === 'nylon') {
            const c = t === 'bronze' ? '#b07a3e' : '#2a2b2e';
            out.push(`<rect x="${sx - 12}" y="${sy + 12}" width="24" height="82" rx="10" fill="${c}"/>`);
            out.push(lines(sx - 12, sx + 12, 3, sy + 14, sy + 92, 0, t === 'bronze' ? '#5a3a14' : '#000', 0.6, 1.4, false));
            for (let k = 0; k < 9; k++) out.push(`<line x1="${sx - 12}" y1="${sy + 16 + k * 9}" x2="${sx + 12}" y2="${sy + 20 + k * 9}" stroke="${t === 'bronze' ? '#e8c48a' : '#555'}" stroke-opacity=".55" stroke-width="1.6"/>`);
        } else if (t === 'mop') {
            out.push(`<rect x="${sx - 13}" y="${sy + 14}" width="26" height="80" rx="12" fill="#e8e4dc"/><rect x="${sx - 13}" y="${sy + 14}" width="26" height="80" rx="12" fill="url(#fleeceTex)" opacity=".6"/>`);
        } else {
            out.push(`<path d="M ${sx - 8},${sy + 30} L ${sx + 8},${sy + 30} L ${sx + 9},${sy + 80} L ${sx},${sy + 92} L ${sx - 9},${sy + 80} Z" fill="url(#brass)"/>`);
            out.push(lines(sx - 8, sx + 6, 4, sy + 36, sy + 76, 0, '#5a3a14', 0.6, 1.2, false));
        }
    }
    // Ölflasche
    const ox = ix + 720, oy = iy + 200;
    out.push(slot(ox - 6, oy - 6, 172, 300, 30));
    out.push(`<rect x="${ox + 20}" y="${oy + 40}" width="120" height="240" rx="26" fill="url(#bottle)"/>`);
    out.push(`<rect x="${ox + 52}" y="${oy + 6}" width="56" height="44" rx="8" fill="url(#capWhite)"/>`);
    out.push(`<rect x="${ox + 32}" y="${oy + 110}" width="96" height="110" rx="6" fill="#e8eaec" opacity=".92"/>`);
    out.push(`<rect x="${ox + 32}" y="${oy + 126}" width="96" height="8" fill="#9d4640"/>`);
    out.push(`<text x="${ox + 80}" y="${oy + 170}" ${FONT} font-size="26" font-weight="800" fill="#1a1a1a" text-anchor="middle">ÖL</text>`);
    out.push(`<text x="${ox + 80}" y="${oy + 198}" ${FONT} font-size="13" font-weight="500" fill="#444" text-anchor="middle" letter-spacing="2">50 ml</text>`);
    out.push(`<rect x="${ox + 30}" y="${oy + 50}" width="14" height="220" rx="7" fill="#fff" opacity=".12"/>`);
    // Putzpflaster-Stapel
    const px0 = ix + 930, py0 = iy + 210;
    out.push(slot(px0 - 10, py0 - 10, 180, 180, 12));
    for (let k = 5; k >= 0; k--) out.push(`<rect x="${px0 + k * 3}" y="${py0 + k * 3}" width="150" height="150" rx="6" fill="${k ? '#d9d5cc' : '#f1eee8'}" stroke="#bdb8ad" stroke-width="1"/>`);
    out.push(`<rect x="${px0}" y="${py0}" width="150" height="150" rx="6" fill="url(#fleeceTex)" opacity=".5"/>`);
    // Adapter / Laufschutz
    for (let k = 0; k < 4; k++) {
        const ax = ix + 720 + k * 96, ay = iy + 560;
        out.push(slot(ax, ay, 70, 70, 35));
        out.push(`<circle cx="${ax + 35}" cy="${ay + 35}" r="24" fill="url(#brassRad)"/><circle cx="${ax + 35}" cy="${ay + 35}" r="9" fill="#2a1d08"/>`);
    }
    // Schienen für Stangen-Mündungsschutz
    out.push(slot(ix + 60, iy + 520, 600, 40));
    out.push(`<rect x="${ix + 76}" y="${iy + 530}" width="560" height="20" rx="10" fill="url(#polymer)"/>`);
    // Scharniere oben, Verschlüsse unten
    for (const sx of [x + 220, x + w - 280]) out.push(`<rect x="${sx}" y="${y - 10}" width="60" height="22" rx="5" fill="url(#steel)"/>`);
    return `<defs>
  <linearGradient id="caseShell" x1="0" y1="0" x2="0" y2="1"><stop offset="0" stop-color="#3a3b3f"/><stop offset=".1" stop-color="#2a2b2e"/><stop offset="1" stop-color="#151617"/></linearGradient>
  <pattern id="foamTex" width="24" height="24" patternUnits="userSpaceOnUse"><rect width="24" height="24" fill="#141516"/><circle cx="6" cy="6" r="2" fill="#1e1f21"/><circle cx="18" cy="16" r="1.6" fill="#0c0c0d"/></pattern>
  <pattern id="fleeceTex" width="6" height="6" patternUnits="userSpaceOnUse"><circle cx="2" cy="2" r="1.2" fill="#000" opacity=".12"/><circle cx="5" cy="5" r="1" fill="#fff" opacity=".2"/></pattern>
  <linearGradient id="bottle" x1="0" y1="0" x2="1" y2="0"><stop offset="0" stop-color="#0d0d0e"/><stop offset=".3" stop-color="#3a3b3f"/><stop offset=".55" stop-color="#1d1e20"/><stop offset="1" stop-color="#08080a"/></linearGradient>
  <linearGradient id="capWhite" x1="0" y1="0" x2="1" y2="0"><stop offset="0" stop-color="#9a9a9a"/><stop offset=".35" stop-color="#f4f4f4"/><stop offset="1" stop-color="#8a8a8a"/></linearGradient>
  <radialGradient id="brassRad" cx="40%" cy="35%" r="70%"><stop offset="0" stop-color="#f6e2a4"/><stop offset=".5" stop-color="#b8903f"/><stop offset="1" stop-color="#4a3410"/></radialGradient>
</defs>` + out.join('\n');
}

/** Pistolenkoffer geschlossen, leicht von oben. px-Koordinaten, Mitte cx, Unterkante floor. */
export function pistolCase(cx, floor) {
    const w = 1180, fh = 230, depth = 520;
    const x = cx - w / 2, y = floor - fh;
    const inset = 70; // Perspektive der Deckelfläche
    const out = [];
    const lid = `M ${x + inset},${y - depth * 0.62} L ${x + w - inset},${y - depth * 0.62} Q ${x + w - inset + 30},${y - depth * 0.62} ${x + w - 10},${y - 20} L ${x + w},${y} L ${x},${y} L ${x + 10},${y - 20} Q ${x + inset - 30},${y - depth * 0.62} ${x + inset},${y - depth * 0.62} Z`;
    out.push(`<g id="caseObj">`);
    out.push(`<path d="${lid}" fill="url(#lidGrad)"/>`);
    // Rippen auf dem Deckel
    for (let i = 1; i < 6; i++) {
        const t = i / 6;
        const yy = y - depth * 0.62 + depth * 0.62 * t;
        const xl = x + inset * (1 - t) + 30, xr = x + w - inset * (1 - t) - 30;
        out.push(`<path d="M ${xl},${yy} L ${xr},${yy}" stroke="#000" stroke-opacity=".45" stroke-width="${4 + t * 4}"/><path d="M ${xl},${yy + 4 + t * 3} L ${xr},${yy + 4 + t * 3}" stroke="#fff" stroke-opacity=".07" stroke-width="2"/>`);
    }
    out.push(rim(`M ${x + inset + 10},${y - depth * 0.62 + 2} L ${x + w - inset - 10},${y - depth * 0.62 + 2}`, 0.25, 3));
    // Front: Deckel- und Unterschale mit Trennfuge
    out.push(`<path d="M ${x},${y} L ${x + w},${y} L ${x + w},${y + fh - 30} Q ${x + w},${y + fh} ${x + w - 30},${y + fh} L ${x + 30},${y + fh} Q ${x},${y + fh} ${x},${y + fh - 30} Z" fill="url(#caseFront)"/>`);
    out.push(`<rect x="${x}" y="${y + fh * 0.42}" width="${w}" height="10" fill="#050505"/>`);
    out.push(`<rect x="${x}" y="${y + fh * 0.42 + 10}" width="${w}" height="2" fill="#fff" opacity=".1"/>`);
    out.push(`<rect x="${x}" y="${y}" width="${w}" height="3" fill="#fff" opacity=".18"/>`);
    // Griff
    const hx = cx - 170;
    out.push(`<path d="M ${hx},${y + fh * 0.2} L ${hx + 340},${y + fh * 0.2} L ${hx + 330},${y + fh * 0.62} Q ${hx + 320},${y + fh * 0.8} ${hx + 290},${y + fh * 0.8} L ${hx + 50},${y + fh * 0.8} Q ${hx + 20},${y + fh * 0.8} ${hx + 10},${y + fh * 0.62} Z" fill="#0b0b0c"/>`);
    out.push(`<path d="M ${hx + 40},${y + fh * 0.5} L ${hx + 300},${y + fh * 0.5} Q ${hx + 300},${y + fh * 0.72} ${hx + 280},${y + fh * 0.72} L ${hx + 60},${y + fh * 0.72} Q ${hx + 40},${y + fh * 0.72} ${hx + 40},${y + fh * 0.5} Z" fill="url(#polymer)"/>`);
    out.push(rim(`M ${hx + 60},${y + fh * 0.52} L ${hx + 280},${y + fh * 0.52}`, 0.3, 2));
    // Verschlüsse
    for (const lx of [x + 140, x + w - 260]) {
        out.push(`<rect x="${lx}" y="${y + fh * 0.18}" width="120" height="${fh * 0.56}" rx="12" fill="#0a0a0a"/>`);
        out.push(`<rect x="${lx + 10}" y="${y + fh * 0.22}" width="100" height="${fh * 0.48}" rx="9" fill="url(#latch)"/>`);
        out.push(`<rect x="${lx + 30}" y="${y + fh * 0.5}" width="60" height="10" rx="5" fill="#000" opacity=".5"/>`);
    }
    // Vorhängeschloss-Ösen
    for (const lx of [x + 300, x + w - 340]) {
        out.push(`<rect x="${lx}" y="${y + fh * 0.3}" width="40" height="${fh * 0.36}" rx="6" fill="url(#steel)"/><circle cx="${lx + 20}" cy="${y + fh * 0.48}" r="8" fill="#050505"/>`);
    }
    out.push(`</g>`);
    return `<defs>
  <linearGradient id="lidGrad" x1="0" y1="0" x2="0" y2="1"><stop offset="0" stop-color="#1d1e20"/><stop offset=".6" stop-color="#2e2f33"/><stop offset="1" stop-color="#3c3d42"/></linearGradient>
  <linearGradient id="caseFront" x1="0" y1="0" x2="0" y2="1"><stop offset="0" stop-color="#2a2b2e"/><stop offset=".5" stop-color="#1a1b1d"/><stop offset="1" stop-color="#0c0c0d"/></linearGradient>
  <linearGradient id="latch" x1="0" y1="0" x2="0" y2="1"><stop offset="0" stop-color="#5a5c61"/><stop offset=".2" stop-color="#2c2d31"/><stop offset="1" stop-color="#121314"/></linearGradient>
</defs>` + out.join('\n') + shadow(cx, floor + 6, w * 0.52, 24, 0.9) + reflection('caseObj', floor, 0.1, 160);
}

/** Pulverdose (Kunststoff), Etikett ohne Herstellerlogo. */
export function powderCan(cx, floor) {
    const w = 560, h = 760;
    const x = cx - w / 2, y = floor - h;
    const out = [`<g id="canObj">`];
    // Körper
    out.push(`<path d="M ${x + 20},${y + 130} Q ${x},${y + 140} ${x},${y + 170} L ${x},${h + y - 30} Q ${x},${y + h} ${x + 40},${y + h} L ${x + w - 40},${y + h} Q ${x + w},${y + h} ${x + w},${y + h - 30} L ${x + w},${y + 170} Q ${x + w},${y + 140} ${x + w - 20},${y + 130} Z" fill="url(#canBody)"/>`);
    // Schulter + Hals
    out.push(`<path d="M ${x + 20},${y + 132} Q ${x + 60},${y + 96} ${cx - 120},${y + 92} L ${cx + 120},${y + 92} Q ${x + w - 60},${y + 96} ${x + w - 20},${y + 132} Z" fill="url(#canBody)"/>`);
    // Deckel (geriffelt)
    out.push(`<rect x="${cx - 150}" y="${y}" width="300" height="100" rx="14" fill="url(#canCap)"/>`);
    out.push(lines(cx - 140, cx + 140, 10, y + 8, y + 92, 0, '#000', 0.45, 3, true));
    out.push(`<rect x="${cx - 150}" y="${y}" width="300" height="8" rx="4" fill="#fff" opacity=".12"/>`);
    // Etikett
    const ly = y + 230, lh = 420;
    out.push(`<rect x="${x}" y="${ly}" width="${w}" height="${lh}" fill="url(#labelGrad)"/>`);
    out.push(`<rect x="${x}" y="${ly + 24}" width="${w}" height="10" fill="#9d4640"/>`);
    out.push(`<text x="${cx - 40}" y="${ly + 110}" ${FONT} font-size="42" font-weight="800" fill="#f2f2f2" text-anchor="middle" letter-spacing="3">TREIB-</text>`);
    out.push(`<text x="${cx - 40}" y="${ly + 158}" ${FONT} font-size="42" font-weight="800" fill="#f2f2f2" text-anchor="middle" letter-spacing="3">LADUNGS-</text>`);
    out.push(`<text x="${cx - 40}" y="${ly + 206}" ${FONT} font-size="42" font-weight="800" fill="#f2f2f2" text-anchor="middle" letter-spacing="3">PULVER</text>`);
    out.push(`<text x="${cx - 40}" y="${ly + 256}" ${FONT} font-size="20" font-weight="500" fill="#b9bdc2" text-anchor="middle" letter-spacing="5">BÜCHSENPULVER</text>`);
    out.push(`<rect x="${x + 50}" y="${ly + 290}" width="${w - 100}" height="1.5" fill="#fff" opacity=".2"/>`);
    out.push(`<text x="${x + 50}" y="${ly + 350}" ${FONT} font-size="56" font-weight="800" fill="#e8eaec">500 g</text>`);
    out.push(`<text x="${x + 50}" y="${ly + 384}" ${FONT} font-size="16" font-weight="500" fill="#8d9196" letter-spacing="3">NETTO · UN 0161</text>`);
    // GHS-Piktogramm (explodierende Bombe, vereinfacht)
    const gx = x + w - 120, gy = ly + 340;
    out.push(`<g transform="translate(${gx} ${gy}) rotate(45)"><rect x="-44" y="-44" width="88" height="88" fill="#fff"/><rect x="-40" y="-40" width="80" height="80" fill="none" stroke="#c8352c" stroke-width="7"/></g>`);
    out.push(`<g transform="translate(${gx} ${gy + 6})"><circle cx="0" cy="6" r="12" fill="#111"/><path d="M -2,-6 L 0,-26 L 4,-8 L 18,-20 L 10,-2 L 30,-4 L 12,6 M -6,-4 L -22,-18 L -12,0 L -30,-2" fill="#111" stroke="#111" stroke-width="3" stroke-linejoin="round"/></g>`);
    // Glanz
    out.push(`<rect x="${x + 70}" y="${y + 150}" width="46" height="${h - 190}" rx="23" fill="#fff" opacity=".08"/>`);
    out.push(`<rect x="${x + 82}" y="${y + 160}" width="10" height="${h - 210}" rx="5" fill="#fff" opacity=".12"/>`);
    out.push(`</g>`);
    return `<defs>
  <linearGradient id="canBody" x1="0" y1="0" x2="1" y2="0"><stop offset="0" stop-color="#0a0a0b"/><stop offset=".18" stop-color="#2c2d31"/><stop offset=".32" stop-color="#3d3e43"/><stop offset=".6" stop-color="#18191b"/><stop offset=".9" stop-color="#0d0d0e"/><stop offset="1" stop-color="#1a1b1d"/></linearGradient>
  <linearGradient id="canCap" x1="0" y1="0" x2="1" y2="0"><stop offset="0" stop-color="#0b0b0c"/><stop offset=".3" stop-color="#36373b"/><stop offset=".7" stop-color="#151617"/><stop offset="1" stop-color="#0b0b0c"/></linearGradient>
  <linearGradient id="labelGrad" x1="0" y1="0" x2="1" y2="0"><stop offset="0" stop-color="#0e0e0f"/><stop offset=".25" stop-color="#2a2b2e"/><stop offset=".45" stop-color="#232427"/><stop offset=".85" stop-color="#121314"/><stop offset="1" stop-color="#0a0a0b"/></linearGradient>
</defs>` + out.join('\n') + shadow(cx, floor + 4, w * 0.62, 22, 0.9) + reflection('canObj', floor, 0.07, 160);
}

/** Jagdjacke (Ghost-Mannequin-Ansicht von vorn). px-Koordinaten, Mitte cx, Oberkante y. */
export function jacket(cx, y) {
    const out = [];
    const sh = 330; // halbe Schulterbreite
    const len = 900;
    const hem = y + len;
    const body = `M ${cx - 90},${y + 10} Q ${cx - 200},${y + 30} ${cx - sh},${y + 70}
      C ${cx - sh - 20},${y + 160} ${cx - sh - 10},${y + 260} ${cx - sh + 6},${y + 340}
      L ${cx - sh + 14},${hem - 30} Q ${cx - sh + 16},${hem} ${cx - sh + 50},${hem + 4}
      L ${cx + sh - 50},${hem + 4} Q ${cx + sh - 16},${hem} ${cx + sh - 14},${hem - 30}
      L ${cx + sh - 6},${y + 340} C ${cx + sh + 10},${y + 260} ${cx + sh + 20},${y + 160} ${cx + sh},${y + 70}
      Q ${cx + 200},${y + 30} ${cx + 90},${y + 10} Q ${cx},${y + 60} ${cx - 90},${y + 10} Z`;
    const sleeve = (s) => `M ${cx + s * (sh - 6)},${y + 72} C ${cx + s * (sh + 90)},${y + 110} ${cx + s * (sh + 140)},${y + 330} ${cx + s * (sh + 170)},${y + 640}
      L ${cx + s * (sh + 176)},${y + 790} L ${cx + s * (sh + 60)},${y + 800} L ${cx + s * (sh + 44)},${y + 640}
      C ${cx + s * (sh + 30)},${y + 500} ${cx + s * (sh + 8)},${y + 420} ${cx + s * (sh - 4)},${y + 360} Z`;
    out.push(`<path d="${sleeve(-1)}" fill="url(#cloth)"/><path d="${sleeve(-1)}" fill="url(#clothShadeL)"/>`);
    out.push(`<path d="${sleeve(1)}" fill="url(#cloth)"/><path d="${sleeve(1)}" fill="url(#clothShadeR)"/>`);
    // Bündchen
    for (const s of [-1, 1]) {
        out.push(`<path d="M ${cx + s * (sh + 54)},${y + 740} L ${cx + s * (sh + 174)},${y + 732} L ${cx + s * (sh + 176)},${y + 790} L ${cx + s * (sh + 60)},${y + 800} Z" fill="url(#cuff)"/>`);
        out.push(`<path d="M ${cx + s * (sh + 120)},${y + 700} L ${cx + s * (sh + 160)},${y + 696} L ${cx + s * (sh + 162)},${y + 744} L ${cx + s * (sh + 124)},${y + 748} Z" fill="url(#cloth)" stroke="#000" stroke-opacity=".3"/>`);
    }
    // Innenseite Rücken (sichtbar im Kragen)
    out.push(`<path d="M ${cx - 90},${y + 10} Q ${cx},${y - 10} ${cx + 90},${y + 10} Q ${cx},${y + 60} ${cx - 90},${y + 10} Z" fill="#141512"/>`);
    out.push(`<path d="${body}" fill="url(#cloth)"/>`);
    out.push(`<path d="${body}" fill="url(#fleeceP)"/>`);
    out.push(`<path d="${body}" fill="url(#bodyShade)"/>`);
    // Kragen (Stehkragen mit Cord-Optik)
    out.push(`<path d="M ${cx - 96},${y + 8} Q ${cx - 120},${y - 50} ${cx - 104},${y - 76} Q ${cx},${y - 50} ${cx + 104},${y - 76} Q ${cx + 120},${y - 50} ${cx + 96},${y + 8} Q ${cx},${y + 70} ${cx - 96},${y + 8} Z" fill="url(#collar)"/>`);
    out.push(`<path d="M ${cx - 100},${y - 66} Q ${cx},${y - 40} ${cx + 100},${y - 66}" fill="none" stroke="#000" stroke-opacity=".5" stroke-width="2"/>`);
    // Reißverschluss mit Abdeckleiste
    out.push(`<path d="M ${cx - 4},${y + 50} L ${cx + 4},${y + 50} L ${cx + 6},${hem + 4} L ${cx - 6},${hem + 4} Z" fill="#0b0c0a"/>`);
    out.push(`<path d="M ${cx + 4},${y + 50} L ${cx + 40},${y + 46} L ${cx + 44},${hem + 4} L ${cx + 6},${hem + 4} Z" fill="url(#cloth)"/>`);
    out.push(`<path d="M ${cx + 40},${y + 46} L ${cx + 44},${hem + 4}" stroke="#000" stroke-opacity=".45" stroke-width="3"/>`);
    out.push(`<path d="M ${cx + 32},${y + 60} L ${cx + 36},${hem - 6}" stroke="#c9c2a8" stroke-opacity=".35" stroke-width="1.4" stroke-dasharray="7 5"/>`);
    out.push(`<rect x="${cx - 12}" y="${y + 40}" width="16" height="40" rx="4" fill="url(#stainless)"/>`);
    out.push(`<path d="M ${cx - 4},${y + 80} L ${cx - 4},${y + 120}" stroke="#2a2b26" stroke-width="5" stroke-linecap="round"/>`);
    // Brusttaschen mit Patten
    for (const s of [-1, 1]) {
        const px = cx + s * 170;
        out.push(`<path d="M ${px - 90},${y + 200} L ${px + 90},${y + 200} L ${px + 88},${y + 380} Q ${px + 86},${y + 396} ${px + 70},${y + 396} L ${px - 70},${y + 396} Q ${px - 86},${y + 396} ${px - 88},${y + 380} Z" fill="url(#cloth)" stroke="#000" stroke-opacity=".35" stroke-width="2"/>`);
        out.push(`<path d="M ${px - 80},${y + 210} L ${px + 80},${y + 210} L ${px + 78},${y + 386} L ${px - 78},${y + 386} Z" fill="none" stroke="#c9c2a8" stroke-opacity=".3" stroke-width="1.4" stroke-dasharray="7 5"/>`);
        out.push(`<path d="M ${px - 96},${y + 180} L ${px + 96},${y + 180} L ${px + 94},${y + 250} Q ${px},${y + 262} ${px - 94},${y + 250} Z" fill="url(#flap)"/>`);
        out.push(`<path d="M ${px - 86},${y + 190} L ${px + 86},${y + 190}" stroke="#c9c2a8" stroke-opacity=".3" stroke-width="1.4" stroke-dasharray="7 5"/>`);
        out.push(`<circle cx="${px}" cy="${y + 238}" r="10" fill="#2a2b26" stroke="#000" stroke-opacity=".5" stroke-width="2"/>`);
        // Untere Taschen
        const qy = y + 560;
        out.push(`<path d="M ${px - 120},${qy} L ${px + 110},${qy} L ${px + 112},${qy + 230} Q ${px + 110},${qy + 250} ${px + 90},${qy + 250} L ${px - 100},${qy + 250} Q ${px - 120},${qy + 250} ${px - 122},${qy + 230} Z" fill="url(#cloth)" stroke="#000" stroke-opacity=".35" stroke-width="2"/>`);
        out.push(`<path d="M ${px - 128},${qy - 24} L ${px + 118},${qy - 24} L ${px + 116},${qy + 56} Q ${px},${qy + 70} ${px - 126},${qy + 56} Z" fill="url(#flap)"/>`);
        out.push(`<path d="M ${px - 118},${qy - 12} L ${px + 108},${qy - 12}" stroke="#c9c2a8" stroke-opacity=".3" stroke-width="1.4" stroke-dasharray="7 5"/>`);
        // Patronenschlaufen-Andeutung (Stoffschlaufen) in der Patte
        out.push(`<path d="M ${px - 140 * s - 10},${qy + 120} C ${px - 150 * s},${qy + 160} ${px - 150 * s},${qy + 200} ${px - 140 * s},${qy + 240}" fill="none" stroke="#000" stroke-opacity=".25" stroke-width="2"/>`);
    }
    // Abnäher/Falten
    out.push(`<path d="M ${cx - 260},${y + 420} C ${cx - 250},${y + 500} ${cx - 262},${y + 560} ${cx - 270},${y + 620}" stroke="#000" stroke-opacity=".18" stroke-width="10" fill="none" filter="url(#blur6)"/>`);
    out.push(`<path d="M ${cx + 250},${y + 420} C ${cx + 240},${y + 500} ${cx + 252},${y + 560} ${cx + 262},${y + 620}" stroke="#000" stroke-opacity=".18" stroke-width="10" fill="none" filter="url(#blur6)"/>`);
    // Saum mit Steppnaht
    out.push(`<path d="M ${cx - sh + 30},${hem - 40} L ${cx + sh - 30},${hem - 40}" stroke="#c9c2a8" stroke-opacity=".28" stroke-width="1.4" stroke-dasharray="7 5"/>`);
    out.push(`<path d="M ${cx - sh + 14},${hem - 50} L ${cx + sh - 14},${hem - 50}" stroke="#000" stroke-opacity=".3" stroke-width="2"/>`);
    // Kleines Webetikett in Markenrot (ohne Schrift)
    out.push(`<rect x="${cx - sh + 40}" y="${hem - 110}" width="34" height="14" rx="2" fill="#9d4640"/>`);
    return `<defs>
  <linearGradient id="cloth" x1="0" y1="0" x2="0" y2="1"><stop offset="0" stop-color="#4e4f42"/><stop offset=".5" stop-color="#3f4036"/><stop offset="1" stop-color="#2d2e26"/></linearGradient>
  <linearGradient id="flap" x1="0" y1="0" x2="0" y2="1"><stop offset="0" stop-color="#55564a"/><stop offset=".7" stop-color="#404137"/><stop offset="1" stop-color="#25261f"/></linearGradient>
  <linearGradient id="collar" x1="0" y1="0" x2="0" y2="1"><stop offset="0" stop-color="#3a2a1c"/><stop offset="1" stop-color="#22180f"/></linearGradient>
  <linearGradient id="cuff" x1="0" y1="0" x2="0" y2="1"><stop offset="0" stop-color="#2a2b24"/><stop offset="1" stop-color="#1a1b16"/></linearGradient>
  <linearGradient id="bodyShade" x1="0" y1="0" x2="1" y2="0"><stop offset="0" stop-color="#000" stop-opacity=".45"/><stop offset=".18" stop-color="#000" stop-opacity=".05"/><stop offset=".4" stop-color="#fff" stop-opacity=".05"/><stop offset=".8" stop-color="#000" stop-opacity=".08"/><stop offset="1" stop-color="#000" stop-opacity=".5"/></linearGradient>
  <linearGradient id="clothShadeL" x1="0" y1="0" x2="1" y2="0"><stop offset="0" stop-color="#000" stop-opacity=".5"/><stop offset=".5" stop-color="#000" stop-opacity=".1"/><stop offset="1" stop-color="#000" stop-opacity=".45"/></linearGradient>
  <linearGradient id="clothShadeR" x1="0" y1="0" x2="1" y2="0"><stop offset="0" stop-color="#000" stop-opacity=".45"/><stop offset=".5" stop-color="#000" stop-opacity=".1"/><stop offset="1" stop-color="#000" stop-opacity=".55"/></linearGradient>
  <pattern id="fleeceP" width="5" height="5" patternUnits="userSpaceOnUse"><circle cx="1.5" cy="1.5" r="1" fill="#fff" fill-opacity=".04"/><circle cx="4" cy="3.5" r="1" fill="#000" fill-opacity=".1"/></pattern>
</defs>` + out.join('\n');
}
